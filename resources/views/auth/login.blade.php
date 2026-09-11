<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/img/skbf_logo.png') }}">
    <link href="assets/css/style.css" rel="stylesheet">


</head>
<body>
    <div class="login-wrapper row g-0">
        <div class="login-image-mobile">
             <!-- <img src="{{ asset('assets/img/login_bg.png') }}" alt="Gambar"> -->
        </div>
        <!-- KIRI: FORM LOGIN -->
        <div class="col-md-6 d-flex align-items-center justify-content-center">
            <div class="login-card w-100 text-center" style="max-width: 380px;">              
                <!-- Flash Messages -->
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Logo Perusahaan -->
                <div class="text-center mb-4">
                    <img src="{{ asset('assets/img/skbf_logo.png') }}" 
                        alt="Company Logo" 
                        class="img-fluid shadow-sm p-2 bg-white rounded-3"
                        style="width: 150px; max-width: 100%; height: auto;">
                <label class="fs-6 fw-semibold mb-2" for=""> {{ json_lang('Please choose the system language') }}: </label>
                <select name="languages" id="languages" class="form-select">
                    <option value="id" {{ session('locale') === 'id' ? 'selected' : '' }}>
                        Bahasa Indonesia
                    </option>
                    <option value="en" {{ session('locale', 'en') === 'en' ? 'selected' : '' }}>
                        English (US)
                    </option>
                </select>  
                <!-- Judul -->
                </div>
                <h4 class="mb-2 mt-4 fs-5 text-start"> 
                    <span class="text-nowrap" id="typing" data-text="{{ json_lang('Hello, Welcome To LMS...') }}"></span>
                </h4>

                <!-- Form Login -->
                <form method="POST" 
                      action="{{ route('login.process') }}"
                      autocomplete="off"
                      class="needs-validation" 
                      novalidate>
                    @csrf

                    <div class="text-start">
                        <label for="emp_id" class="form-label">{{ json_lang('Employee ID') }}</label>
                        <input type="text" 
                               class="form-control" 
                               name="emp_id" 
                               id="emp_id" 
                               placeholder="ex. I99999" 
                               autocomplete="off" 
                               required>
                        <div class="invalid-feedback">
                            {{ json_lang('Employee ID is required.') }}
                        </div>
                    </div>

                    <div class="text-start">
                        <label for="password" class="form-label">{{ json_lang('Password') }}</label>
                        <input type="password" 
                               class="form-control" 
                               name="password" 
                               id="password" 
                               placeholder="ex.*******" 
                               autocomplete="off" 
                               required>
                        <div class="invalid-feedback">
                            {{ json_lang('Password is required.') }}
                        </div>
                    </div>
                    <div id="captcha-wrapper">
                        <div class="g-recaptcha" data-sitekey="6LdBbbUtAAAAAOvYqv-mMWAlf7Ci_eSiI-U8s3CC"></div>
                        <div class="invalid-feedback mb-2">
                            {{ json_lang('reCAPTCHA is required.') }}
                        </div>
                    </div>
                    <button type="submit" class="btn btn-secondary w-100"> {{ json_lang('Login') }}</button>
                </form>

                <!-- Footer kecil -->
                <p class="mt-4 mb-0 text-muted" style="font-size: 0.85rem;">
                    &copy; {{ date('Y') }} Your Company. All rights reserved.
                </p>
            </div>
        </div>

        <!-- KANAN: GAMBAR -->
        <div class="col-md-6 login-image">
            <img src="{{ asset('assets/img/login_bg.png') }}" alt="Gambar">
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/typed.js@2.0.12"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>

    <script>
        document.getElementById('languages')?.addEventListener('change', function () {
            if (!this.value) return;
            window.location.href = `/lang/${this.value}`;
        });

        document.addEventListener('DOMContentLoaded', function () {
            const el = document.getElementById('typing');
            if (!el || typeof Typed === 'undefined') return;

            new Typed(el, {
                strings: [el.dataset.text],
                typeSpeed: 50,
                backSpeed: 30,
                showCursor: false
            });
        });
        // Bootstrap validation
        (function () {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                    let captchaValid = true;

                    // cek captcha TANPA ganggu flow lama
                    if (typeof grecaptcha !== "undefined") {
                        if (grecaptcha.getResponse().length === 0) {
                            captchaValid = false;

                            const captchaWrapper = document.getElementById('captcha-wrapper');
                            captchaWrapper.classList.add('is-invalid');
                        } else {
                            document.getElementById('captcha-wrapper').classList.remove('is-invalid');
                        }
                    }

                    if (!form.checkValidity() || !captchaValid) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
        localStorage.removeItem("shownExpiryModal");
    </script>
</body>
</html>
