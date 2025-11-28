@extends('layouts.admin.template')

@section('title', 'Riwayat Notifikasi')

@section('content')
<div class="page-header" style="margin-bottom: 24px;">
    <div>
        <h1 style="font-size: 24px; font-weight: 700; margin: 0;">Riwayat Notifikasi</h1>
        <p style="color: #64748b; margin-top: 4px;">Lihat semua aktivitas sistem</p>
    </div>
</div>

<div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;">
    <div style="padding: 0;">
        @forelse($notifications as $notification)
            @php
                $data = $notification->data;
                $url = $data['url'] ?? '#';
                $iconColor = match($data['type'] ?? 'info') {
                    'danger' => '#fee2e2', // Merah muda
                    'warning' => '#fef3c7', // Kuning muda
                    default => '#dbeafe',   // Biru muda
                };
                $iconText = match($data['type'] ?? 'info') {
                    'danger' => '#dc2626',
                    'warning' => '#d97706',
                    default => '#2563eb',
                };
            @endphp
            <a href="{{ $url }}" style="display: flex; gap: 16px; padding: 16px 24px; border-bottom: 1px solid #f1f5f9; text-decoration: none; color: inherit; align-items: flex-start; transition: background 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: {{ $iconColor }}; color: {{ $iconText }}; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    @if(($data['category'] ?? '') == 'keamanan')
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    @elseif(($data['category'] ?? '') == 'acara')
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    @else
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    @endif
                </div>
                <div style="flex: 1;">
                    <p style="margin: 0 0 4px 0; font-weight: 600; font-size: 14px; color: #0f172a;">{!! $data['message'] ?? 'Notifikasi' !!}</p>
                    <p style="margin: 0; font-size: 13px; color: #64748b;">{{ $notification->created_at->diffForHumans() }}</p>
                </div>
            </a>
        @empty
            <div style="padding: 40px; text-align: center; color: #94a3b8;">
                <p>Belum ada notifikasi.</p>
            </div>
        @endforelse
    </div>
    
    <div style="padding: 16px 24px; border-top: 1px solid #e2e8f0;">
        {{ $notifications->links() }} 
    </div>
</div>
@endsection