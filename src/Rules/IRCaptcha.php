<?php

namespace Klangch\LaravelIRCaptcha\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IRCaptcha implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (ir_captcha()->validateCaptchaToken($value) !== true) {
            $fail('ir-captcha::messages.invalid_captcha')->translate();
        }
    }
}
