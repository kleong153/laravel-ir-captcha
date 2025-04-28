<?php

return [
    // Captcha image dimensions.
    'width' => 280,
    'height' => 160,

    // Number of allowed requests per minute per IP.
    'throttle_per_minute' => 6,

    // Expiration time in seconds.
    'expire' => 60,

    // Degree tolerance for validation.
    'validate_degree_tolerance' => 5,

    // Disk and directory for storing temporary files while generating captcha images.
    'temp_disk' => 'local',
    'temp_dir' => 'image_captcha_temp',

    // Disk and directory for storing generated captcha images.
    'cache_disk' => 'public',
    'cache_dir' => 'captcha_cache',

    // Background color (RGBA format).
    'bg_color' => [255, 255, 255, 1],

    // Number of noise dots.
    'noise_dots' => 200,

    // Number of noise lines.
    'noise_lines' => 6,

    // Colors for noise dots and lines (RGBA format).
    'noise_colors' => [
        [0, 114, 178, 1],
        [230, 159, 0, 1],
        [86, 180, 233, 1],
        [213, 94, 0, 1],
        [0, 158, 115, 1],
        [240, 228, 66, 1],
        [204, 121, 167, 1],
        [82, 89, 117, 1],
    ],

    // Available shapes: circle, rectangle, triangle
    'shapes' => [
        // 'circle',
        'rectangle',
        'rectangle',
        'triangle',
        'triangle',
    ],
    'shuffle_shapes' => true,

    // Colors for shapes (RGBA format).
    'shape_colors' => [
        [0, 114, 178, 0.5],
        [230, 159, 0, 0.5],
        [86, 180, 233, 0.5],
        [213, 94, 0, 0.5],
        [0, 158, 115, 0.5],
        [240, 228, 66, 0.5],
        [204, 121, 167, 0.5],
        [82, 89, 117, 0.5],
    ],
];
