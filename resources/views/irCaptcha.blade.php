<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ Lang::get('ir-captcha::messages.verification') }}</title>

    <style>
        html {
            --primary-color: #007bff;
            --primary-hover-color: #0056b3;
            --primary-color-foreground: #ffffff;
            --success-color: #009933;
            --error-color: #ff5e5e;
            --status-overlay-background: rgba(255, 255, 255, 0.85);
            --rotation-slider-background: #dddddd;
            --rotation-slider-shadow: rgba(0, 0, 0, 0.3);
            --disabled-color: #d3d3d3;
            --rotation-slider-background: #d3d3d3;

            background: #ffffff;
            color: #000000;
        }

        html[data-theme='dark'] {
            --primary-color: #007bff;
            --primary-hover-color: #0056b3;
            --primary-color-foreground: #ffffff;
            --success-color: #0ab945;
            --error-color: #ff5e5e;
            --status-overlay-background: rgba(27, 27, 27, 0.85);
            --rotation-slider-background: #c7c7c7;
            --rotation-slider-shadow: rgba(255, 255, 255, 0.3);
            --disabled-color: #b8b8b8;
            --rotation-slider-background: #b8b8b8;

            background: #1b1b1b;
            color: #f8f8f2;
        }

        body {
            font-family: sans-serif;
            margin: 0;
            padding: 0;
            /* Safari */
            -webkit-user-select: none;
            /* IE 10 and IE 11 */
            -ms-user-select: none;
            /* Standard syntax */
            user-select: none;
        }

        .root-container {
            margin: 0 auto;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        #status_overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--status-overlay-background);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 10;
        }

        #status_label {
            font-size: 1em;
            text-align: center;
        }

        .captcha-container {
            position: relative;
            height: 220px;
            width: 100%;
        }

        .captcha-background {
            height: 100%;
            width: 100%;
        }

        .rotate-label {
            font-size: 0.9rem;
            margin: 10px 0;
        }

        #rotated_piece {
            position: absolute;
            top: calc(50% + 2px);
            left: calc(50% + 2px);
            transform: translate(-50%, -50%) rotate(0deg);
            transform-origin: center center;
            pointer-events: none;
        }

        #rotation_slider {
            -webkit-appearance: none;
            width: 99%;
            height: 15px;
            background: var(--rotation-slider-background);
            border-radius: 5px;
            outline: none;
        }

        /* Chrome, Safari, Opera */
        #rotation_slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            height: 25px;
            width: 25px;
            background: var(--primary-color);
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 0 2px var(--rotation-slider-shadow);
        }

        /* Firefox */
        #rotation_slider::-moz-range-thumb {
            height: 25px;
            width: 25px;
            background: var(--primary-color);
            border: none;
            border-radius: 50%;
            cursor: pointer;
        }

        /* Firefox track */
        #rotation_slider::-moz-range-track {
            background: #ddd;
            height: 10px;
            border-radius: 5px;
        }

        /* Edge / IE */
        #rotation_slider::-ms-thumb {
            height: 25px;
            width: 25px;
            background: var(--primary-color);
            border: none;
            border-radius: 50%;
            cursor: pointer;
        }

        #rotation_slider::-ms-track {
            background: transparent;
            border-color: transparent;
            color: transparent;
            height: 10px;
        }

        #rotation_slider:disabled::-webkit-slider-thumb {
            background: var(--rotation-slider-background);
            cursor: not-allowed;
        }

        #rotation_slider:disabled::-moz-range-thumb {
            background: var(--rotation-slider-background);
            cursor: not-allowed;
        }

        #rotation_slider:disabled::-ms-thumb {
            background: var(--rotation-slider-background);
            cursor: not-allowed;
        }

        button {
            margin-top: 20px;
            background-color: var(--primary-color);
            color: var(--primary-color-foreground);
            padding: 10px 20px;
            font-size: 0.9rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.2s ease-in-out;
        }

        button.btn-block {
            width: 100%;
        }

        button:hover {
            background-color: var(--primary-hover-color);
        }

        button:disabled,
        button:disabled:hover {
            background: var(--disabled-color);
            cursor: not-allowed;
        }
    </style>
</head>

<body onload="initPage();">
    <div class="root-container" style="width: {{ $width }}px;">
        <div id="status_overlay">
            <span id="status_label"></span>

            <button id="retry_button" onclick="fetchCaptcha();" style="display: none;">{{ Lang::get('ir-captcha::messages.retry') }}</button>
        </div>

        <div class="captcha-container" style="height: {{ $height }}px;">
            <img id="captcha_background" alt="Captcha Background" style="visibility: hidden;" />

            <img id="rotated_piece" alt="Rotated Piece" style="visibility: hidden;" />
        </div>

        <span class="rotate-label">{{ Lang::get('ir-captcha::messages.rotate_to_match') }}</span>

        <input type="range" id="rotation_slider" oninput="onSlide();" min="0" max="360" step="1" value="180" />

        <button type="button" id="verify_btn" onclick="onVerify();" class="btn-block">{{ Lang::get('ir-captcha::messages.verify') }}</button>
    </div>

    <script>
        const statusOverlay = document.getElementById("status_overlay");
        const statusLabel = document.getElementById("status_label");
        const retryButton = document.getElementById("retry_button");

        const slider = document.getElementById("rotation_slider");
        const captchaBackground = document.getElementById("captcha_background");
        const rotatedPiece = document.getElementById("rotated_piece");
        const verifyButton = document.getElementById("verify_btn");

        let captchaUid = "";
        let retryInterval = null;
        let parentOrigin = "";

        function initPage() {
            const urlParams = new URLSearchParams(window.location.search);
            const theme = urlParams.get("theme");

            if (theme === "dark") {
                document.documentElement.setAttribute("data-theme", "dark");
            }

            parentOrigin = urlParams.get("parent_origin") ?? "";

            fetchCaptcha();
        }

        function resetSlider() {
            slider.value = 180;
            rotatedPiece.style.transform = `translate(-50%, -50%) rotate(${slider.value}deg)`;
        }

        function hideStatusOverlay() {
            statusOverlay.style.display = "none";
        }

        function showLoadingStatusOverlay() {
            statusLabel.style.color = "";
            statusLabel.innerText = "{{ Lang::get('ir-captcha::messages.loading') }}";
            retryButton.style.display = "none";
            statusOverlay.style.display = "flex";
        }

        function showErrorStatusOverlay(text) {
            statusLabel.style.color = "var(--error-color)";
            statusLabel.innerText = text;
            retryButton.innerText = "{{ Lang::get('ir-captcha::messages.retry') }}";
            retryButton.style.display = "block";
            statusOverlay.style.display = "flex";
        }

        function showTooManyRequests() {
            if (retryInterval) {
                clearInterval(retryInterval);
                retryInterval = null;
            }

            statusLabel.style.color = "var(--error-color)";
            statusLabel.innerText = "{{ Lang::get('ir-captcha::messages.result_too_many_requests') }}";
            statusOverlay.style.display = "flex";

            const retryButtonLabel = "{{ Lang::get('ir-captcha::messages.retry') }}";
            let waitSeconds = 60;

            retryButton.style.display = "block";
            retryButton.innerText = `${retryButtonLabel} (${waitSeconds})`;
            retryButton.disabled = true;

            retryInterval = setInterval(() => {
                if (waitSeconds > 1) {
                    waitSeconds--;

                    retryButton.innerText = `${retryButtonLabel} (${waitSeconds})`;
                } else {
                    retryButton.innerText = retryButtonLabel;
                    retryButton.disabled = false;

                    if (retryInterval) {
                        clearInterval(retryInterval);
                        retryInterval = null;
                    }
                }
            }, 1000);
        }

        function showSuccessStatusOverlay(text) {
            statusLabel.style.color = "var(--success-color)";
            statusLabel.innerText = text;
            retryButton.style.display = "none";
            statusOverlay.style.display = "flex";
        }

        async function fetchCaptcha() {
            captchaBackground.style.visibility = "hidden";
            rotatedPiece.style.visibility = "hidden";

            slider.disabled = true;
            verifyButton.disabled = true;
            verifyButton.innerText = "{{ Lang::get('ir-captcha::messages.verify') }}";

            showLoadingStatusOverlay();

            try {
                const response = await fetch("{{ url('ir-captcha-data') }}", {
                    method: "GET",
                });

                if (response.ok) {
                    const result = await response.json();

                    captchaUid = result.captcha_uid;
                    captchaBackground.src = result.background_url;
                    rotatedPiece.src = result.rotated_piece_url;

                    captchaBackground.style.visibility = "visible";
                    rotatedPiece.style.visibility = "visible";

                    slider.disabled = false;
                    verifyButton.disabled = false;

                    resetSlider();
                    hideStatusOverlay();

                    animateSlider();
                } else if (response.status === 429) {
                    showTooManyRequests();
                } else {
                    throw result;
                }
            } catch (error) {
                showErrorStatusOverlay(`${error}`);
            }
        }

        function onSlide() {
            rotatedPiece.style.transform = `translate(-50%, -50%) rotate(${slider.value}deg)`;
        }

        async function onVerify() {
            slider.disabled = true;
            verifyButton.disabled = true;
            verifyButton.innerText = "{{ Lang::get('ir-captcha::messages.verifying') }}";

            try {
                const body = new FormData();
                body.append("captcha_uid", captchaUid);
                body.append("input_degree", slider.value);

                const response = await fetch("{{ url('ir-captcha-verify') }}", {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector("meta[name='csrf-token']").content,
                    },
                    body,
                });

                if (response.ok) {
                    const result = await response.json();

                    if (result.success === true) {
                        showSuccessStatusOverlay("{{ Lang::get('ir-captcha::messages.result_success') }}");

                        setTimeout(() => {
                            window.parent.postMessage({
                                type: "irCaptcha",
                                status: "success",
                                captchaToken: result.captcha_token,
                            }, parentOrigin);
                        }, 1000);
                    } else if (result.expired === true) {
                        showErrorStatusOverlay("{{ Lang::get('ir-captcha::messages.result_captcha_expired') }}");
                    } else {
                        showErrorStatusOverlay("{{ Lang::get('ir-captcha::messages.result_incorrect_angle') }}");
                    }
                } else {
                    throw result;
                }
            } catch (error) {
                showErrorStatusOverlay(`${error}`);
            }
        }

        function animateSlider() {
            const animateTo = (target, duration = 300) => {
                return new Promise(resolve => {
                    const start = Number(slider.value);
                    const startTime = performance.now();

                    function step(now) {
                        const elapsed = now - startTime;
                        const progress = Math.min(elapsed / duration, 1);
                        const value = Math.round(start + (target - start) * progress);

                        slider.value = value;
                        rotatedPiece.style.transform = `translate(-50%, -50%) rotate(${value}deg)`;

                        if (progress < 1) {
                            requestAnimationFrame(step);
                        } else {
                            resolve();
                        }
                    }

                    requestAnimationFrame(step);
                });
            };

            animateTo(130, 200)
                .then(() => animateTo(230, 300))
                .then(() => animateTo(180, 200));
        }
    </script>
</body>

</html>
