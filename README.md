# Laravel IR Captcha (Alpha Stage)

Laravel IR Captcha (Image Rotation Captcha) is a customizable image rotation CAPTCHA package for Laravel 11 and 12.

## Features
- Interactive, intuitive, and more user-friendly than classic text-based CAPTCHAs.
- Customizable UI and flexible configuration.
- No third-party services required. Everything are handled locally.

## Preview
![LaravelIRCaptcha Screenshot 1](https://github.com/user-attachments/assets/7baac265-c504-4d42-8efe-02a6fb7889cf)
![LaravelIRCaptcha Screenshot 2](https://github.com/user-attachments/assets/6c4efeb1-9903-4305-9de3-fb24f42353b3)



https://github.com/user-attachments/assets/71f9b85c-6a83-4b4c-b63a-a92418e36209



## Installation
Make sure the PHP GD extension is installed and enabled.

Install with composer using following command:
```bash
composer require klangch/laravel-ir-captcha
```

## Configuration
Publish configuration, view, localization files by using following command:
```bash
$ php artisan vendor:publish --provider="Klangch\LaravelIRCaptcha\LaravelIRCaptchaServiceProvider"
```

## Usage
Show captcha in iframe
```html
<iframe src="{{ ir_captcha()->iframeUrl() }}" height="276" width="280"></iframe>
```

Listen for iframe's post message
```js
window.addEventListener("message", (event) => {
    if (event.data && event.data.type === "irCaptcha" && event.data.status === "success") {
        // Get captcha token and set to input field
        document.querySelector("input[name='captcha_token']").value = event.data.captchaToken;

        // Continue form submission...
    }
});
```

### URL for iframe
```php
// Light theme
ir_captcha()->iframeUrl();

// Dark theme
ir_captcha()->iframeUrl(true);

// Or append dark theme param manually
$url = ir_captcha()->iframeUrl() . '?theme=dark';
```

or set dark theme in Laravel Blade + JS
```js
const url = "{{ ir_captcha()->iframeUrl() }}?theme=dark";
```

### Validate captcha token
```php
if (ir_captcha()->validateCaptchaToken($token) === true) {
    // Validation success
}
```

or by using Laravel validation rule:

```php
use Illuminate\Support\Facades\Validator;
use Klangch\LaravelIRCaptcha\Rules\IRCaptcha;

$rules = [
    'captcha_token' => ['required', new IRCaptcha],
];

$validator = Validator::make($request->all(), $rules);
$validator->validate();
```

### Clear expired captcha files
Clear expired captcha files by using following command:

```bash
$ php artisan ir-captcha:clear-expired
```

## Code Example
Coming soon
