# Laravel IR Captcha (Beta Stage)

Laravel IR Captcha (Image Rotation Captcha) is a customizable image rotation CAPTCHA package for Laravel 11 and 12.

## Features
- Interactive, intuitive, and more user-friendly than classic text-based CAPTCHAs.
- Customizable UI and flexible configuration.
- No third-party services required. Everything are handled locally.

## Preview
![LaravelIRCaptcha Screenshot 1](https://github.com/user-attachments/assets/7baac265-c504-4d42-8efe-02a6fb7889cf)
![LaravelIRCaptcha Screenshot 2](https://github.com/user-attachments/assets/6c4efeb1-9903-4305-9de3-fb24f42353b3)



https://github.com/user-attachments/assets/32460513-4fe3-4bfa-bcf7-79c5e9b7f5e1



## Installation
Make sure the PHP ```gd``` extension is installed and enabled.

Install with composer using following command:
```bash
composer require klangch/laravel-ir-captcha
```

## Configuration
Publish configuration, view, localization files by using following command:
```bash
$ php artisan vendor:publish --provider="Klangch\LaravelIRCaptcha\LaravelIRCaptchaServiceProvider"
```
- ```config/ir-captcha.php```
- ```lang/vendor/ir-captcha/en/messages.php```
- ```resoures/views/vendor/ir-captcha/irCaptcha.blade.php```

## Note For Frontend Framework On Different Domains (e.g. Next.js)
When using this package with frontend running on different domain (such as a Next.js app embedding captcha verification page via an iframe), CSRF protection may block requests due to cross-origin restrictions. To allow verification requests from the iframe to bypass CSRF validation, you can explicitly exclude the verification endpoint:
```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    // ...

    $middleware->validateCsrfTokens(except: [
        'ir-captcha-verify',
    ]);
});
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
// Get captcha URL with helper
ir_captcha()->iframeUrl();

// Get captcha URL with parent origin and dark theme
ir_captcha()->iframeUrl('https://[your_parent_origin].com', true);

// Or append parent origin and dark theme param manually
$url = ir_captcha()->iframeUrl() . '?parent_origin=https://[your_parent_origin].com&theme=dark';
```

or set dark theme dynamically in Laravel Blade + JS
```js
// Get captcha URL with helper
let captchaUrl = "{{ ir_captcha()->iframeUrl() }}";

// Or without helper
let captchaUrl = "https://[your_captcha_domain].com/ir-captcha";

// Then append parent origin
captchaUrl += "?parent_origin=" + window.location.origin;

// Append theme
if (themeMode === "dark") {
    captchaUrl += "&theme=dark";
}

document.getElementById("captchaIframe").src = captchaUrl;
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

You can set this command in cron job to regularly clear expired files.

## Code Example
https://github.com/kleong153/laravel-ir-captcha-example
