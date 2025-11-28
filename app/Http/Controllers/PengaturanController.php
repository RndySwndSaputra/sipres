<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use App\Notifications\SystemNotification; // Pastikan notifikasi ini di-import

class PengaturanController extends Controller
{
    /**
     * Menampilkan halaman pengaturan dengan data user saat ini.
     */
    public function index()
    {
        $user = Auth::user();
        return view('admin.pengaturan.index', [
            'user' => $user
        ]);
    }

    /**
     * Memperbarui HANYA nama user.
     */
    public function updateName(Request $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user->name = $validated['name'];
        $user->save();

        return response()->json(['success' => true, 'message' => 'Nama berhasil diperbarui.']);
    }

    /**
     * Memperbarui HANYA email user.
     */
    public function updateEmail(Request $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validate([
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
        ]);

        $oldEmail = $user->email;
        $emailChanged = $user->email !== $validated['email'];
        $user->email = $validated['email'];

        if ($emailChanged) {
            $user->email_verified_at = null;
            $user->save();
            // $user->sendEmailVerificationNotification(); // Aktifkan jika menggunakan verifikasi email
            
            // --- NOTIFIKASI KEAMANAN: EMAIL BERUBAH ---
            try {
                $link = route('pengaturan') . '#section-account'; // Link ke tab akun
                
                $user->notify(new SystemNotification(
                    'keamanan',
                    'warning',
                    "Email akun Anda telah diubah dari <b>$oldEmail</b> menjadi <b>{$validated['email']}</b>.",
                    $link
                ));
            } catch (\Exception $e) {
                // Abaikan error notifikasi agar proses update email tetap berhasil
            }

            return response()->json(['success' => true, 'message' => 'Email berhasil diperbarui.']);
        }

        return response()->json(['success' => true, 'message' => 'Email berhasil diperbarui.']);
    }

    /**
     * Memperbarui password user.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $user = Auth::user();

        // 1. Validasi data
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => ['required', 'confirmed', Password::min(8)],
        ]);

        // 2. Cek apakah password saat ini cocok
        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Password saat ini yang Anda masukkan salah.',
                'errors' => [
                    'current_password' => ['Password saat ini yang Anda masukkan salah.']
                ]
            ], 422);
        }

        // 3. Update ke password baru
        $user->password = Hash::make($validated['new_password']);
        $user->save();

        // --- NOTIFIKASI KEAMANAN: GANTI PASSWORD ---
        try {
            // Link ke tab keamanan (atau section ganti password)
            $link = route('pengaturan') . '#section-account';
            
            $user->notify(new SystemNotification(
                'keamanan', 
                'danger', 
                'Password akun Anda baru saja diubah. Jika ini bukan Anda, segera amankan akun.',
                $link
            ));
        } catch (\Exception $e) {
            // Abaikan error notifikasi agar tidak menggagalkan ganti password
        }

        return response()->json(['success' => true, 'message' => 'Password berhasil diubah.']);
    }
}