<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/img/skbf_logo.png') }}">

    <style>
        body {
            background: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            padding: 40px;
            width: 380px;
            text-align: center;
        }
        .login-card img {
            width: 300px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!--<div class="login-card">-->
        <!-- Logo Perusahaan -->
    <!--    <img src="{{ asset('assets/img/skbf_logo.png') }}" alt="Company Logo">-->

        <!-- Judul -->
    <!--    <h4 class="mb-4">Ubah Password Pertama</h4>-->

        <!-- Notifikasi Error / Success -->
    <!--    @if (session('success'))-->
    <!--        <div class="alert alert-success alert-dismissible fade show" role="alert">-->
    <!--            {{ session('success') }}-->
    <!--            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>-->
    <!--        </div>-->
    <!--    @endif-->
    <!--    @if (session('warning'))-->
    <!--        <div class="alert alert-warning alert-dismissible fade show" role="alert">-->
    <!--            {{ session('warning') }}-->
    <!--            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>-->
    <!--        </div>-->
    <!--    @endif-->
    <!--    @if ($errors->any())-->
    <!--        <div class="alert alert-danger alert-dismissible fade show" role="alert">-->
    <!--            {{ $errors->first() }}-->
    <!--            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>-->
    <!--        </div>-->
    <!--    @endif-->

        <!-- Form Ganti Password -->
    <!--    <form method="POST" -->
    <!--          action="{{ route('password.update') }}" -->
    <!--          class="needs-validation" -->
    <!--          novalidate>-->
    <!--        @csrf-->

    <!--        <div class="mb-3 text-start">-->
    <!--            <label for="password" class="form-label">Password Baru</label>-->
    <!--            <input type="password" -->
    <!--                   class="form-control @error('password') is-invalid @enderror" -->
    <!--                   name="password" -->
    <!--                   id="password" -->
    <!--                   placeholder="Masukkan password baru" -->
    <!--                   required>-->
    <!--            @error('password')-->
    <!--                <div class="invalid-feedback">{{ $message }}</div>-->
    <!--            @else-->
    <!--                <div class="invalid-feedback">Password baru wajib diisi.</div>-->
    <!--            @enderror-->
    <!--        </div>-->

    <!--        <div class="mb-3 text-start">-->
    <!--            <label for="password_confirmation" class="form-label">Konfirmasi Password</label>-->
    <!--            <input type="password" -->
    <!--                   class="form-control @error('password_confirmation') is-invalid @enderror" -->
    <!--                   name="password_confirmation" -->
    <!--                   id="password_confirmation" -->
    <!--                   placeholder="Konfirmasi password baru" -->
    <!--                   required>-->
    <!--            @error('password_confirmation')-->
    <!--                <div class="invalid-feedback">{{ $message }}</div>-->
    <!--            @else-->
    <!--                <div class="invalid-feedback">Konfirmasi password wajib diisi.</div>-->
    <!--            @enderror-->
    <!--        </div>-->

    <!--        <button type="submit" class="btn btn-secondary w-100">Ubah Password</button>-->
    <!--    </form>-->

        <!-- Footer kecil -->
    <!--    <p class="mt-4 mb-0 text-muted" style="font-size: 0.85rem;">-->
    <!--        &copy; {{ date('Y') }} Your Company. All rights reserved.-->
    <!--    </p>-->
    <!--</div>-->
    
    <div class="login-card">
        <!-- Logo Perusahaan -->
        <img src="{{ asset('assets/img/skbf_logo.png') }}" alt="Company Logo">

        <!-- Judul -->
        <h4 class="mb-4">{{ json_lang('Change First Password') }}</h4>

        <!-- Notifikasi Error / Success -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if (session('warning'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                {{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ $errors->first() }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Form Ganti Password -->
        <form method="POST" 
              action="{{ route('password.update') }}" 
              class="needs-validation" 
              novalidate>
            @csrf

            <div class="mb-3 text-start">
                <label for="password" class="form-label">{{ json_lang('New Password') }}</label>
                <input type="password" 
                       class="form-control @error('password') is-invalid @enderror" 
                       name="password" 
                       id="password" 
                       placeholder="{{ json_lang('Enter new password') }}"
                       required>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @else
                    <div class="invalid-feedback">{{ json_lang('New password is required.') }}</div>
                @enderror
            </div>

            <div class="mb-3 text-start">
                <label for="password_confirmation" class="form-label"> {{ json_lang('Confirm Password') }}</label>
                <input type="password" 
                       class="form-control @error('password_confirmation') is-invalid @enderror" 
                       name="password_confirmation" 
                       id="password_confirmation" 
                       placeholder="{{ json_lang('Confirm new password') }}"
                       required>
                @error('password_confirmation')
                    <div class="invalid-feedback">{{ $message }}</div>
                @else
                    <div class="invalid-feedback">  {{ json_lang('Password confirmation is required.') }}</div>
                @enderror
            </div>

        <button type="submit" class="btn btn-secondary w-100">{{ json_lang('Update Password') }}</button>
        </form>

        <!-- Footer kecil -->
        <p class="mt-4 mb-0 text-muted" style="font-size: 0.85rem;">
            &copy; {{ date('Y') }} Your Company. All rights reserved.
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Aktifkan Bootstrap validation
        (function () {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');

            Array.from(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>
</html>
