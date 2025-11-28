<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\Password;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use App\Notifications\SystemNotification; // Pastikan import ini ada

class AuthController extends Controller
{
    public function index()
    {
        return view('auth.login');
    }

    public function forgot()
    {
        return view('auth.forgot-password');
    }

    // --- FUNGSI UNTUK MENGIRIM LINK RESET ---
    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'Kami tidak dapat menemukan pengguna dengan alamat email tersebut.']);
        }

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Link reset password telah kami kirimkan ke email Anda!');
        }

        return back()->withErrors(['email' => 'Gagal mengirim link reset. Silakan coba lagi.']);
    }
    
    // --- DUA FUNGSI BARU UNTUK MENANGANI RESET PASSWORD ---

    public function showResetForm(Request $request, $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->email
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', Rules\Password::min(8)],
        ]);

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();
                
                // (Opsional) Kirim notifikasi keamanan saat reset password berhasil
                try {
                    $user->notify(new SystemNotification(
                        'keamanan',
                        'danger',
                        'Password akun Anda telah direset ulang melalui fitur Lupa Password.',
                        route('pengaturan') . '#section-security'
                    ));
                } catch (\Exception $e) {}
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('status', 'Password Anda telah berhasil direset! Silakan login.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'Token reset password ini tidak valid atau telah kedaluwarsa.']);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');
        $remember = (bool) $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            $user = Auth::guard()->user();

            if ($remember) {
                if (empty($user->getRememberToken())) {
                    $user->setRememberToken(Str::random(60));
                    $user->save();
                }

                $cookieName = method_exists(Auth::guard(), 'getRecallerName') ? Auth::guard()->getRecallerName() : 'remember_web';
                // Perbaikan typo: sgetAuthPassword -> getAuthPassword
                $recallerValue = $user->getAuthIdentifier().'|'.$user->getRememberToken().'|'.$user->getAuthPassword();
                $minutes = 60 * 24 * 3; 

                cookie()->queue(
                    cookie()->make(
                        $cookieName,
                        $recallerValue,
                        $minutes,
                        config('session.path', '/'),
                        config('session.domain', null),
                        (bool) config('session.secure', false),
                        (bool) config('session.http_only', true),
                        false,
                        config('session.same_site', 'lax')
                    )
                );
            }

            // --- NOTIFIKASI KEAMANAN: LOGIN BARU ---
            // Memberi tahu user bahwa ada login baru
            try {
                $time = now()->format('H:i');
                // Link diarahkan ke Pengaturan -> Tab Keamanan (History login biasanya ada di sana jika fitur dibuat nanti)
                // Atau ke Dashboard
                $link = route('dashboard');

                $user->notify(new SystemNotification(
                    'keamanan',
                    'info',
                    "Login baru terdeteksi pada jam $time WIB.",
                    $link
                ));
            } catch (\Exception $e) {
                // Abaikan error notifikasi agar login tetap jalan
            }

            return redirect()->intended(route('dashboard'));
        }

        return back()
            ->withInput($request->only('email', 'remember'))
            ->withErrors([
                'email' => 'Kredensial tidak valid.',
            ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}