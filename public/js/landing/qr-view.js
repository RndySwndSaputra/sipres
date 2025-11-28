document.addEventListener('DOMContentLoaded', () => {
  // 1. Tombol Reload
  const btnReload = document.getElementById('btnReload');
  btnReload?.addEventListener('click', () => {
    window.location.reload();
  });

  // 2. Tombol Download (Client-Side Converter)
  const btnDownload = document.getElementById('btnDownloadPng');
  
  btnDownload?.addEventListener('click', () => {
    const img = document.querySelector('.qr-image');
    
    // Validasi: Pastikan gambar QR ada
    if (!img || !img.src) {
      alert('Gambar QR belum dimuat sepenuhnya. Silakan muat ulang.');
      return;
    }

    // Ubah teks tombol agar user tahu proses berjalan
    const originalText = btnDownload.textContent;
    btnDownload.textContent = 'Memproses...';
    btnDownload.disabled = true;

    // --- PROSES KONVERSI SVG KE PNG DI BROWSER ---
    const image = new Image();
    image.crossOrigin = "Anonymous";
    
    image.onload = () => {
      // 1. Buat Canvas Resolusi Tinggi
      const canvas = document.createElement('canvas');
      const size = 1200; // Resolusi tinggi agar tidak pecah
      canvas.width = size;
      canvas.height = size;
      
      const ctx = canvas.getContext('2d');
      
      // 2. Beri Background Putih (Penting agar tidak transparan hitam di HP)
      ctx.fillStyle = '#ffffff';
      ctx.fillRect(0, 0, size, size);
      
      // 3. Gambar QR ke Canvas
      ctx.drawImage(image, 0, 0, size, size);
      
      // 4. Convert ke PNG Data URL
      const dataUrl = canvas.toDataURL('image/png');
      
      // 5. Download Otomatis
      const link = document.createElement('a');
      // Nama file unik pakai timestamp
      link.download = `QR-Presensi-${Date.now()}.png`; 
      link.href = dataUrl;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);

      // Reset Tombol
      btnDownload.textContent = originalText;
      btnDownload.disabled = false;
    };

    image.onerror = () => {
      alert('Gagal mengkonversi gambar. Coba screenshot manual.');
      btnDownload.textContent = originalText;
      btnDownload.disabled = false;
    };

    // Muat source gambar ke objek Image baru untuk diproses
    image.src = img.src;
  });
});