<?php

namespace Klangch\LaravelIRCaptcha;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\RateLimiter;

class LaravelIRCaptchaController extends Controller
{
    public function getView()
    {
        $config = config('ir-captcha');

        $data = [
            'height' => $config['height'],
            'width' => $config['width'],
        ];

        return response()->view('ir-captcha::irCaptcha', $data);
    }

    public function getIRCaptchaData(LaravelIRCaptcha $irCaptcha)
    {
        $captcha = $irCaptcha->generateCaptcha();

        return response()->json([
            'captcha_uid' => $captcha['captcha_uid'],
            'rotated_piece_url' => htmlentities($captcha['rotated_piece_url']),
            'background_url' => htmlentities($captcha['background_url']),
        ]);
    }

    public function verify(LaravelIRCaptcha $irCaptcha, Request $request)
    {
        $result = $irCaptcha->verifyCaptchaChallenge($request->input('captcha_uid'), intval($request->input('input_degree')));

        if ($result['success'] === true) {
            RateLimiter::clear(md5('ir-captcha' . $request->ip()));
        }

        return response()->json($result);
    }
}
