<?php

use Klangch\LaravelIRCaptcha\LaravelIRCaptcha;

if (!function_exists('ir_captcha')) {
    function ir_captcha(): LaravelIRCaptcha
    {
        return app(LaravelIRCaptcha::class);
    }
}
