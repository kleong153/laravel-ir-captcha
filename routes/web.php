<?php

use Illuminate\Support\Facades\Route;
use Klangch\LaravelIRCaptcha\LaravelIRCaptchaController;

Route::middleware('web')
    ->controller(LaravelIRCaptchaController::class)
    ->group(function () {
        Route::get('ir-captcha', 'getView');

        Route::middleware('throttle:ir-captcha')->group(function () {
            Route::get('ir-captcha-data', 'getIRCaptchaData');
            Route::post('ir-captcha-verify', 'verify');
        });
    });
