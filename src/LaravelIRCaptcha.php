<?php

namespace Klangch\LaravelIRCaptcha;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Geometry\Factories\CircleFactory;
use Intervention\Image\Geometry\Factories\LineFactory;
use Intervention\Image\Geometry\Factories\PolygonFactory;
use Intervention\Image\Geometry\Factories\RectangleFactory;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Laravel\Facades\Image;

class LaravelIRCaptcha
{
    public function iframeUrl(bool $darkTheme = false): string
    {
        $url = url('ir-captcha');
        $params = [];

        if ($darkTheme) {
            $params['theme'] = 'dark';
        }

        if (count($params) > 0) {
            $mappedParams = Arr::map($params, function (string $val, string $key) {
                return "$key=$val";
            });

            $url .= '?' . implode('&', $mappedParams);
        }

        return $url;
    }

    public function generateCaptcha(): array
    {
        $config = config('ir-captcha');
        $shapes = $config['shapes'];

        $captchaUid = Str::remove('-', 'cp' . Str::uuid() . mt_rand(1111, 9999));

        if (boolval($config['shuffle_shapes'])) {
            $shapes = Arr::shuffle($shapes);
        }

        $img = Image::create($config['width'], $config['height'])->fill($this->rgba($config['bg_color']));

        foreach ($shapes as $shape) {
            $this->drawShape($img, $shape, $config);
        }

        $this->addNoise($img, $config);

        $results = $this->createCaptchaFiles($img, $captchaUid, $config);

        Cache::store($config['cache_store'])->put("imageCaptchaChallenge:$captchaUid", $results['rotation'], now()->addSeconds($config['expire']));

        return [
            'captcha_uid' => $captchaUid,
            'rotated_piece_url' => $results['cropped_url'],
            'background_url' => $results['punched_url'],
        ];
    }

    public function verifyCaptchaChallenge(string $captchaUid, int $inputDegree): array
    {
        $config = config('ir-captcha');
        $success = false;
        $expired = false;
        $captchaToken = '';

        $realDegree = Cache::store($config['cache_store'])->pull("imageCaptchaChallenge:$captchaUid");

        if (!$realDegree) {
            $expired = true;
        } else {
            $config = config('ir-captcha');

            $delta = abs($realDegree - $inputDegree);
            $tolerance = $config['validate_degree_tolerance'];

            $success = ($delta <= $tolerance || abs($delta - 360) <= $tolerance);

            if ($success === true) {
                $captchaToken = Str::remove('-', 'cpt' . Str::uuid() . mt_rand(1111, 9999));

                Cache::store($config['cache_store'])->put("imageCaptchaToken:$captchaToken", true, 300); // 5 mins expiry.
            }
        }

        return [
            'success' => $success,
            'expired' => $expired,
            'captcha_token' => $captchaToken,
        ];
    }

    public function validateCaptchaToken(string $captchaToken): bool
    {
        $config = config('ir-captcha');

        return Cache::store($config['cache_store'])->pull("imageCaptchaToken:$captchaToken") === true;
    }

    public function clearExpiredFiles(): void
    {
        $config = config('ir-captcha');

        $tempStorage = Storage::disk($config['temp_file_disk']);
        $tempFiles = $tempStorage->files($config['temp_file_dir']);

        foreach ($tempFiles as $file) {
            if ($tempStorage->lastModified($file) < now()->subSeconds(10)->timestamp) {
                $tempStorage->delete($file);
            }
        }

        $expireSeconds = $config['expire'];
        $captchaStorage = Storage::disk($config['public_file_disk']);
        $captchaFiles = $captchaStorage->files($config['public_file_dir']);

        foreach ($captchaFiles as $file) {
            if ($captchaStorage->lastModified($file) < now()->subSeconds($expireSeconds)->timestamp) {
                $captchaStorage->delete($file);
            }
        }
    }

    private function rgba(array $rgba): string
    {
        return sprintf('rgba(%d,%d,%d,%.2f)', ...$rgba);
    }

    private function addNoise(ImageInterface &$img, array $config): void
    {
        for ($i = 0; $i < $config['noise_dots']; $i++) {
            $color = Arr::random($config['noise_colors']);

            $img->drawCircle(rand(0, $config['width']), rand(0, $config['height']), function (CircleFactory $draw) use ($color) {
                $draw->background($this->rgba($color));
            });
        }

        for ($i = 0; $i < $config['noise_lines']; $i++) {
            $width = $config['width'];
            $height = $config['height'];
            $color = Arr::random($config['noise_colors']);

            $img->drawLine(function (LineFactory $line) use ($width, $height, $color) {
                $line->from(rand(-10, $width), rand(-10, $height));
                $line->to(rand(-10, $width), rand(-10, $height));
                $line->color($this->rgba($color));
                $line->width(2);
            });
        }
    }

    private function drawShape(ImageInterface &$img, string $shape, array $config): void
    {
        $height = $config['height'];
        $width = $config['width'];

        $centerX = $width / 2;
        $centerY = $height / 2;

        $maxOffsetX = min(60, $width / 4); // limit based on image width
        $maxOffsetY = min(30, $height / 4); // limit based on image height

        $color = Arr::random($config['shape_colors']);

        switch ($shape) {
            case 'circle':
                $r = rand(intval(min($width, $height) / 4), intval(min($width, $height) / 2));
                $x = $centerX + rand(intval(-$maxOffsetX), intval($maxOffsetX));
                $y = $centerY + rand(intval(-$maxOffsetY), intval($maxOffsetY));

                $img->drawCircle($x, $y, function (CircleFactory $draw) use ($r, $color) {
                    $draw->radius($r);
                    $draw->background($this->rgba($color));
                });
                break;

            case 'rectangle':
                $rotate = rand(0, 90);
                $w = rand(intval($width / 2), intval($width));
                $h = rand(intval($height / 6), intval($height / 2));

                // Create a temp canvas just big enough to hold the shape.
                $shapeCanvas = Image::create($w + 20, $h + 20)->fill('rgba(0,0,0,0)');
                $shapeCanvas->drawRectangle(10, 10, function (RectangleFactory $draw) use ($w, $h, $color) {
                    $draw->size($w, $h);
                    $draw->background($this->rgba($color));
                });

                // Rotate the canvas.
                $shapeCanvas->rotate($rotate, 'rgba(0,0,0,0)');

                // Calculate final position in main image, centered with offset.
                $x = $centerX + rand(intval(-$maxOffsetX), intval($maxOffsetX)) - ($shapeCanvas->width() / 2);
                $y = $centerY + rand(intval(-$maxOffsetY), intval($maxOffsetY)) - ($shapeCanvas->height() / 2);

                // Place onto main image.
                $img->place($shapeCanvas, 'top-left', $x, $y);
                break;

            case 'triangle':
                $rotate = rand(0, 90);
                $size = rand(intval(min($width, $height) / 4), intval(min($width, $height) / 3));

                // Create a temp canvas just big enough to hold the triangle.
                $canvasSize = $size * 3;
                $shapeCanvas = Image::create($canvasSize, $canvasSize)->fill('rgba(0,0,0,0)');

                // Draw triangle centered in the shape canvas.
                $cx = $canvasSize / 2;
                $cy = $canvasSize / 2;

                $points = [
                    [$cx, $cy - $size], // top
                    [$cx - $size, $cy + $size], // bottom left
                    [$cx + $size, $cy + $size], // bottom right
                ];

                $shapeCanvas->drawPolygon(function (PolygonFactory $draw) use ($points, $color) {
                    foreach ($points as [$px, $py]) {
                        $draw->point($px, $py);
                    }

                    $draw->background($this->rgba($color));
                });

                // Rotate the triangle canvas.
                $shapeCanvas->rotate($rotate, 'rgba(0,0,0,0)');

                // Calculate final position in main image, centered with offset.
                $x = $centerX + rand(-$maxOffsetX, $maxOffsetX) - ($shapeCanvas->width() / 2);
                $y = $centerY + rand(-$maxOffsetY, $maxOffsetY) - ($shapeCanvas->height() / 2);

                // Place onto main image.
                $img->place($shapeCanvas, 'top-left', $x, $y);
                break;
        }
    }

    private function createCaptchaFiles(ImageInterface &$img, string $captchaUid, array $config): array
    {
        $tempStorage = Storage::disk($config['temp_file_disk']);
        $filenamePrefix = $captchaUid;
        $filepathPrefix = $config['temp_file_dir'] . DIRECTORY_SEPARATOR . $filenamePrefix;

        // Save generated image to temp storage.
        $sourceFilepath = $filepathPrefix . '_source.png';
        $tempCroppedFilepath = $filepathPrefix . '_cropped.png';
        $tempPunchedFilepath = $filepathPrefix . '_punched.png';

        $tempStorage->put($sourceFilepath, $img->toPng());

        // Create and prepare temp files.
        $tempStorage->put($tempCroppedFilepath, '');
        $tempStorage->put($tempPunchedFilepath, '');

        // Get temp files full path.
        $sourceFileFullpath = $tempStorage->path($sourceFilepath);
        $croppedFileFullpath = $tempStorage->path($tempCroppedFilepath);
        $punchedFileFullpath = $tempStorage->path($tempPunchedFilepath);

        // Load original image.
        $src = imagecreatefrompng($sourceFileFullpath);
        imagesavealpha($src, true);
        $width = imagesx($src);
        $height = imagesy($src);

        // Rotate original image for create cropped image.
        do {
            $rotation = rand(5, 355);
        } while ($rotation >= 175 && $rotation <= 185);

        imagepng($src, $croppedFileFullpath);
        $rotatedSrc = imagecreatefrompng($croppedFileFullpath);
        $transparent = imagecolorallocatealpha($rotatedSrc, 0, 0, 0, 127);
        imagealphablending($rotatedSrc, false);
        imagesavealpha($rotatedSrc, true);
        $rotatedSrc = imagerotate($rotatedSrc, $rotation, $transparent);

        // Determine circle size and center.
        $radius = intval(min($width, $height) * 0.8 / 2);
        $centerX = intval($width / 2);
        $centerY = intval($height / 2);
        $centerX2 = intval(imagesx($rotatedSrc) / 2);
        $centerY2 = intval(imagesy($rotatedSrc) / 2);

        // Create a blank image for the circular crop.
        $circle = imagecreatetruecolor($radius * 2, $radius * 2);
        imagealphablending($circle, false);
        imagesavealpha($circle, true);
        $transparent = imagecolorallocatealpha($circle, 0, 0, 0, 127);
        imagefill($circle, 0, 0, $transparent);

        // Draw circular mask onto it.
        $mask = imagecreatetruecolor($radius * 2, $radius * 2);
        imagealphablending($mask, false);
        imagesavealpha($mask, true);
        imagefill($mask, 0, 0, $transparent);
        $white = imagecolorallocate($mask, 255, 255, 255);
        imagefilledellipse($mask, $radius, $radius, $radius * 2, $radius * 2, $white);

        // Copy from src to circle using mask.
        for ($x = 0; $x < $radius * 2; $x++) {
            for ($y = 0; $y < $radius * 2; $y++) {
                $maskAlpha = (imagecolorat($mask, $x, $y) & 0xFF000000) >> 24;
                if ($maskAlpha == 0) {
                    $color = imagecolorat($rotatedSrc, $centerX2 - $radius + $x, $centerY2 - $radius + $y);
                    imagesetpixel($circle, $x, $y, $color);
                }
            }
        }

        // Save cropped circle.
        imagepng($circle, $croppedFileFullpath);

        // Now punch out a hole in the original.
        $punchBgColor = imagecolorallocatealpha($src, $config['bg_color'][0], $config['bg_color'][1], $config['bg_color'][2], 0);
        imagealphablending($src, false);
        for ($x = 0; $x < $radius * 2; $x++) {
            for ($y = 0; $y < $radius * 2; $y++) {
                $maskAlpha = (imagecolorat($mask, $x, $y) & 0xFF000000) >> 24;
                if ($maskAlpha == 0) {
                    // Fix gd rotation x y issue with -1 offset.
                    imagesetpixel($src, ($centerX - $radius + $x) + 1, ($centerY - $radius + $y) + 1, $punchBgColor);
                }
            }
        }

        imagesavealpha($src, true);
        imagepng($src, $punchedFileFullpath);

        // Clean up.
        imagedestroy($src);
        imagedestroy($rotatedSrc);
        imagedestroy($circle);
        imagedestroy($mask);

        // Move generated files to cache folder, then delete temp files.
        $cacheStorage = Storage::disk($config['public_file_disk']);
        $cacheCroppedFilepath = $config['public_file_dir'] . DIRECTORY_SEPARATOR . $filenamePrefix . '_cropped.png';
        $cachePunchedFilepath = $config['public_file_dir'] . DIRECTORY_SEPARATOR . $filenamePrefix . '_punched.png';

        $cacheStorage->put($cacheCroppedFilepath, $tempStorage->get($tempCroppedFilepath));
        $cacheStorage->put($cachePunchedFilepath, $tempStorage->get($tempPunchedFilepath));
        $tempStorage->delete($sourceFilepath);
        $tempStorage->delete($tempCroppedFilepath);
        $tempStorage->delete($tempPunchedFilepath);

        return [
            'rotation' => $rotation,
            'cropped_url' => Str::replace('\\', '/', $cacheStorage->url($cacheCroppedFilepath)),
            'punched_url' => Str::replace('\\', '/', $cacheStorage->url($cachePunchedFilepath)),
        ];
    }
}
