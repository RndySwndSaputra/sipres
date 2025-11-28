<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/auth/forget-password.css') }}">
    <link rel="icon" href="{{ asset('assets/image/sipres.png') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="brand">
                <img src="{{ asset('assets/image/sipres.webp') }}" alt="Logo" class="brand-logo">
                <h1 class="brand-title">Reset Password</h1>
                <p class="brand-subtitle">Masukkan email terdaftar untuk menerima link reset</p>
            </div>

            @if (session('status'))
                <div class="alert-success" style="padding: 12px; background-color: #e6f7e9; color: #1b5e20; border-radius: 8px; margin-bottom: 14px; text-align: center; font-size: 14px;">
                    {{ session('status') }}
                </div>
            @endif

            <form id="forgotForm" class="login-form" 
                  method="POST" 
                  action="{{ route('password.send') }}" 
                  novalidate>
                
                @csrf 

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Masukkan email" 
                           autocomplete="email" 
                           value="{{ old('email') }}" 
                           required>
                    
                    @error('email')
                        <span style="color: #ef4444; font-size: 13px; margin-top: 4px;">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn-otp">Kirim Link Reset</button>
            </form>

            <div class="back-login">
                <a href="{{ route('login') }}" class="back-link">Kembali ke Login</a>
            </div>
        </div>
    </div>

    </body>
</html>