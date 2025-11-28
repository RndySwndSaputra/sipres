document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('absenOnlineForm');
  const nipInput = document.getElementById('nip');
  const btnSubmit = document.getElementById('btnSubmit');
  const messageEl = document.getElementById('message');
  const btnCopyZoom = document.getElementById('btnCopyZoom');
  const idAcara = document.getElementById('idAcara')?.value;
  
  // Elemen Toast
  const toastEl = document.getElementById('toast');

  // Fokus input
  if (nipInput) nipInput.focus();

  // Bersihkan error saat mengetik
  if (nipInput) {
    nipInput.addEventListener('input', () => {
      nipInput.style.borderColor = '#e2e8f0';
      messageEl.style.display = 'none';
      messageEl.className = 'absen-message';
    });
  }

  // --- FUNGSI TOAST ---
  function showToast() {
    if (toastEl) {
      toastEl.classList.add('show');
      setTimeout(() => {
        toastEl.classList.remove('show');
      }, 3000); // Hilang setelah 3 detik
    }
  }

  // --- LOGIKA COPY LINK ZOOM ---
  if (btnCopyZoom) {
    btnCopyZoom.addEventListener('click', async () => {
      const link = btnCopyZoom.getAttribute('data-zoom-link');
      if (link) {
        try {
          await navigator.clipboard.writeText(link);
          
          // Panggil Toast (Notif Melayang)
          showToast();
          
          // Ubah tombol sedikit sebagai feedback klik
          btnCopyZoom.style.backgroundColor = '#f0fdf4';
          setTimeout(() => btnCopyZoom.style.backgroundColor = '', 200);

        } catch (err) {
          console.error('Gagal menyalin:', err);
          alert('Gagal menyalin link. Silakan salin manual.');
        }
      }
    });
  }

  // --- LOGIKA SUBMIT ABSEN ---
  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      const nipVal = nipInput.value.trim();
      if (!nipVal) {
        showError('NIP wajib diisi.');
        return;
      }

      // UI Loading
      const originalBtnContent = btnSubmit.innerHTML;
      btnSubmit.disabled = true;
      btnSubmit.innerHTML = `
        <svg class="animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation:spin 1s linear infinite; width:20px; height:20px;">
            <path d="M21 12a9 9 0 1 1-6.219-8.56"></path>
        </svg>
        <span>Memproses...</span>`;

      try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        
        const res = await fetch('/presensi/online/submit', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
          },
          body: JSON.stringify({ id_acara: idAcara, nip: nipVal })
        });

        const json = await res.json();

        if (res.ok && json.success) {
          showSuccess(json.message || 'Absensi berhasil!');
          form.reset();
          btnSubmit.disabled = false;
          btnSubmit.innerHTML = originalBtnContent;
        } else {
          throw new Error(json.message || 'Gagal melakukan absensi.');
        }

      } catch (err) {
        showError(err.message);
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = originalBtnContent;
      }
    });
  }

  function showSuccess(msg) {
    messageEl.textContent = msg;
    messageEl.className = 'absen-message success';
    messageEl.style.display = 'block';
  }

  function showError(msg) {
    messageEl.textContent = msg;
    messageEl.className = 'absen-message error';
    messageEl.style.display = 'block';
    
    if (nipInput) {
      nipInput.style.borderColor = '#ef4444';
      nipInput.parentElement.classList.add('shake');
      setTimeout(() => nipInput.parentElement.classList.remove('shake'), 400);
      nipInput.focus();
    }
  }

  if (!document.getElementById('spin-anim')) {
      const style = document.createElement('style');
      style.id = 'spin-anim';
      style.innerHTML = `@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }`;
      document.head.appendChild(style);
  }
});