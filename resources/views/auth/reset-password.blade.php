<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Atur Password Baru</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="{{ asset('css/auth/forget-password.css') }}">
    <link rel="icon" href="{{ asset('assets/image/sipres.png') }}">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    </head>
    <body>
        <div class="login-wrapper">
            <div class="login-card">
                <div class="brand">
                    <img src="{{ asset('assets/image/sipres.webp') }}" alt="Logo" class="brand-logo">
                    <h1 class="brand-title">Atur Password Baru</h1>
                    <p class="brand-subtitle">Masukkan password baru Anda.</p>
                </div>

                @if (session('status'))
                    <div class="alert-success" style="padding: 12px; background-color: #e6f7e9; color: #1b5e20; border-radius: 8px; margin-bottom: 14px; text-align: center; font-size: 14px;">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert-error" style="padding: 12px; background-color: #ffebee; color: #b71c1c; border-radius: 8px; margin-bottom: 14px; text-align: left; font-size: 14px; list-style-position: inside;">
                        <ul style="margin: 0; padding: 0;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form class="login-form" 
                    method="POST" 
                    action="{{ route('password.update') }}" 
                    novalidate>
                    @csrf

                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="hidden" name="email" value="{{ $email ?? old('email') }}">

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" value="{{ $email ?? old('email') }}" disabled 
                            style="background: #e0e0e0; cursor: not-allowed; opacity: 0.8; font-weight: 500;">
                    </div>

                    <div class="form-group">
                        <label for="password">Password Baru</label>
                        <div class="password-input-group">
                            <input type="password" id="password" name="password" placeholder="Masukkan password baru" required>
                            <span class="toggle-password" data-target="password">
                                <i class="fas fa-eye-slash"></i>
                            </span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation">Konfirmasi Password</label>
                        <div class="password-input-group">
                            <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Konfirmasi password baru" required>
                            <span class="toggle-password" data-target="password_confirmation">
                                <i class="fas fa-eye-slash"></i>
                            </span>
                        </div>
                    </div>

                    <button type="submit" class="btn-otp">Simpan Password Baru</button>
                </form>
            </div>
        </div>

        <script src="{{ asset('js/auth/reset-password.js') }}"></script>

    </body>
</html>