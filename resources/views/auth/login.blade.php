<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/auth/login.css') }}">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="brand">
                <img src="{{ asset('assets/image/sipres.webp') }}" alt="Logo Karawang" class="brand-logo">
                <h1 class="brand-title">Hai, Selamat Datang</h1>
                <p class="brand-subtitle"> untuk melanjutkan presensi</p>
            </div>

            <form method="POST" action="{{ route('login.perform') }}" id="loginForm" class="login-form" novalidate>
                @csrf
                <div class="form-group">
                    <label for="email" icon>Email</label>
                    <input type="email" id="email" name="email" placeholder="Masukkan email" autocomplete="email" required value="{{ old('email') }}">
                </div>

                <div class="form-group">
                    <label for="password">Kata Sandi</label>
                    <div class="password-input-group">
                        <input type="password" id="password" name="password" placeholder="Masukkan kata sandi" autocomplete="current-password" required>
                        <span class="toggle-password" data-target="password">
                            <i class="fas fa-eye-slash"></i>
                        </span>
                    </div>
                    @if ($errors->has('email') || $errors->has('password'))
                        <p class="input-error-text" role="alert">Email atau password salah !</p>
                    @endif
                </div>
                <div class="form-extras">
                    <label class="remember">
                        <input type="checkbox" id="remember" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                        <span>Ingat saya</span>
                    </label>
                    <a href="{{ route('forgot-password') }}" class="forgot-link">Lupa password?</a>
                </div>

                <button type="submit" class="btn-login">Login</button>
            </form>
            <p style="text-align: center; font-size: 11px;">© 2025 BKPSDM Karawang. All rights reserved.</p>
        </div>
    </div>

    <script src="{{ asset('js/auth/login.js') }}"></script>
</body>
</html>