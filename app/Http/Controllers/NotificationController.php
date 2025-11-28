<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Ambil daftar notifikasi user (JSON) untuk Dropdown Navbar
     */
    public function index()
    {
        $user = Auth::user();
        
        // Ambil 5 notifikasi terbaru
        $notifications = $user->notifications()->limit(5)->get();
        
        $unreadCount = $user->unreadNotifications->count();

        $formatted = $notifications->map(function ($notif) {
            return [
                'id' => $notif->id,
                'category' => $notif->data['category'] ?? 'info',
                'type' => $notif->data['type'] ?? 'info',
                'title' => $notif->data['message'] ?? 'Notifikasi Baru',
                'url' => $notif->data['url'] ?? '#', // Ambil URL
                'time' => $notif->created_at->diffForHumans(),
                'read_at' => $notif->read_at
            ];
        });

        return response()->json([
            'success' => true,
            'count' => $unreadCount,
            'data' => $formatted
        ]);
    }

    /**
     * Halaman Riwayat Lengkap Notifikasi
     * (INI YANG MEMPERBAIKI ERROR undefined method history)
     */
    public function history()
    {
        // Ambil semua notifikasi dengan pagination 15 per halaman
        $notifications = Auth::user()->notifications()->paginate(15);
        
        // Tandai semua sebagai sudah dibaca saat membuka halaman ini
        Auth::user()->unreadNotifications->markAsRead();

        // Pastikan Anda sudah membuat view ini di: resources/views/admin/notifications/index.blade.php
        return view('admin.notifications.index', compact('notifications'));
    }

    /**
     * Tandai semua sebagai sudah dibaca (via AJAX)
     */
    public function markAllRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    }
}