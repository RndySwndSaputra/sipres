(() => {
  const btnSync = document.getElementById('btnSync');
  const btnSend = document.getElementById('btnSend');
  const statusText = document.getElementById('statusText');
  const logList = document.getElementById('logList');

  // Sync modal elements
  const syncModal = document.getElementById('syncModal');
  const syncModalBar = document.getElementById('syncModalBar');
  const syncModalText = document.getElementById('syncModalText');
  const btnSyncClose = document.getElementById('btnSyncClose');
  const syncModalTitle = document.getElementById('syncModalTitle');
  const loadingState = document.getElementById('loadingState');
  const loadingText = document.getElementById('loadingText');
  const successState = document.getElementById('successState');
  const successTitle = document.getElementById('successTitle');
  const successDesc = document.getElementById('successDesc');
  const btnModalSend = document.getElementById('btnModalSend');

  if (!btnSync || !btnSend || !syncModal) return;

  const deliveryOptions = document.getElementById('deliveryOptions');
  const sourceOptions = document.getElementById('sourceOptions');
  const statusPanel = document.getElementById('statusPanel');

  const pathMatch = window.location.pathname.match(/\/peserta\/send-qr\/([a-zA-Z0-9-]+)$/);
  const eventId = pathMatch ? decodeURIComponent(pathMatch[1]) : null;
  
  // Ambil CSRF token dari meta tag
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  let contacts = []; // normalized contacts (email/phone)
  let pesertaCount = 0;
  let isSyncing = false;

  const getSelectedDelivery = () => (deliveryOptions.querySelector('input[name="delivery"]:checked')?.value) || 'email';
  const getSelectedSource = () => (sourceOptions.querySelector('input[name="source"]:checked')?.value) || 'sim-asn';

  const appendLog = (msg) => {
    if (!logList) return;
    const li = document.createElement('li');
    li.textContent = msg;
    logList.appendChild(li);
    logList.scrollTop = logList.scrollHeight;
  };

  const openModal = (title, text, icon = 'sync') => {
    syncModal.classList.add('is-open');
    document.body.classList.add('no-scroll');
    syncModal.setAttribute('aria-hidden', 'false');
    
    if (syncModalTitle) {
      let iconSvg = '';
      if (icon === 'sync') {
        iconSvg = '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/></svg>';
      } else if (icon === 'send') {
        iconSvg = '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>';
      }
      syncModalTitle.innerHTML = iconSvg + (title || 'Proses');
    }
    
    if (loadingState) loadingState.style.display = 'flex';
    if (successState) successState.style.display = 'none';
    if (loadingText) loadingText.textContent = text || 'Memulai...';
    if (logList) logList.innerHTML = '';
    if (btnModalSend) btnModalSend.style.display = 'none';
    btnSyncClose.disabled = true;
  };

  const updateModalTitle = (title, icon = 'sync') => {
    if (!syncModalTitle) return;
    let iconSvg = '';
    if (icon === 'sync') {
      iconSvg = '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/></svg>';
    } else if (icon === 'send') {
      iconSvg = '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>';
    }
    syncModalTitle.innerHTML = iconSvg + (title || 'Proses');
  };

  const showSuccess = (title, desc, showSendButton = true) => {
    if (loadingState) loadingState.style.display = 'none';
    if (successState) successState.style.display = 'flex';
    if (successTitle) successTitle.textContent = title;
    if (successDesc) successDesc.textContent = desc;
    if (btnModalSend) btnModalSend.style.display = showSendButton ? 'inline-flex' : 'none';
    btnSyncClose.disabled = false;
  };

  const closeModal = () => {
    syncModal.classList.remove('is-open');
    document.body.classList.remove('no-scroll');
    syncModal.setAttribute('aria-hidden', 'true');
  };

  btnSyncClose?.addEventListener('click', () => {
    if (!isSyncing) closeModal();
  });

  const fetchAllPeserta = async () => {
    contacts = [];
    pesertaCount = 0;
    let page = 1;
    const perPage = 100;
    while (true) {
      const res = await fetch(`/admin/peserta/data/${eventId}?page=${page}&per_page=${perPage}`, { headers: { 'Accept': 'application/json' } });
      const json = await res.json();
      if (!json?.success) break;
      const rows = json.data || [];
      pesertaCount += rows.length;
      
      // Ambil email/ponsel asli
      rows.forEach(r => {
        contacts.push({
            nama: r.nama,
            email: r.email || null,
            ponsel: r.ponsel || null
        });
      });
      
      const p = json.pagination || {};
      if (!p.has_more) break;
      page = p.next_page || (page + 1);
    }
  };

  const updateUIForSource = () => {
    const source = getSelectedSource();
    if (source === 'non-sim-asn') {
      if (btnSync) btnSync.style.display = 'none';
      closeModal();
      if (logList) logList.innerHTML = '';
      if (btnSend) btnSend.disabled = false;
    } else {
      if (btnSync) btnSync.style.display = '';
      if (btnSend) btnSend.disabled = true;
    }
  };

  sourceOptions.addEventListener('change', updateUIForSource);

  const simulateSync = async () => {
    if (!eventId) return;
    isSyncing = true;
    btnSync.disabled = true;
    btnSend.disabled = true;

    const source = getSelectedSource();
    const delivery = getSelectedDelivery();
    
    if (source === 'sim-asn') {
      openModal('Sinkronisasi Kontak', 'Mengambil data peserta dari SIM-ASN...', 'sync');
    } else {
      openModal('Sinkronisasi Kontak', 'Memproses data...', 'sync');
    }

    await new Promise(r => setTimeout(r, 500));
    if (loadingText) loadingText.textContent = 'Mengambil data peserta...';

    await fetchAllPeserta();
    const total = pesertaCount;
    
    if (total === 0) {
      if (loadingText) loadingText.textContent = 'Tidak ada peserta ditemukan untuk sinkronisasi.';
      btnSyncClose.disabled = false;
      btnSync.disabled = false;
      isSyncing = false;
      return;
    }

    if (loadingText) loadingText.textContent = `Menyinkronkan ${total} kontak...`;
    appendLog(`✓ Ditemukan ${total} peserta`);
    appendLog(`⟳ Menyinkronkan kontak ${delivery === 'email' ? 'email' : 'WhatsApp'}...`);
    
    appendLog(`✓ Sinkronisasi selesai! ${total} kontak siap`);
    
    showSuccess(
      'Sinkronisasi Berhasil!',
      `${total} kontak siap untuk pengiriman QR Code via ${delivery === 'email' ? 'Email' : 'WhatsApp'}`
    );
    
    btnSend.disabled = false;
    btnSync.disabled = false;
    isSyncing = false;
  };

  btnSync.addEventListener('click', () => {
    if (isSyncing) return;
    simulateSync();
  });

  // --- FUNGSI PENGIRIMAN UTAMA ---
  const sendQRCode = async () => {
    const method = getSelectedDelivery();
    const source = getSelectedSource();
    const methodLabel = method === 'email' ? 'Email' : 'WhatsApp';
    
    let modalTitle = 'Pengiriman QR Code';
    let modalIcon = 'send';
    if (method === 'email') modalTitle = 'Pengiriman via Email';
    if (method === 'whatsapp') modalTitle = 'Pengiriman via WhatsApp';

    openModal(modalTitle, `Menyiapkan pengiriman via ${methodLabel}...`, modalIcon);

    try {
        let url;
        let payload = { source: source };

        if (method === 'email') {
            url = `/admin/peserta/send-qr/${eventId}/email`;
        } else {
            url = `/admin/peserta/send-qr/${eventId}/whatsapp`;
        }

        if (loadingText) loadingText.textContent = `Mengirim QR Code via ${methodLabel}... (Ini mungkin perlu beberapa saat)`;
        
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(payload)
        });

        const json = await res.json();
        
        if (json?.success) {
            const s = json.summary || {};
            
            // [MODIFIKASI RENDY]: Menghilangkan logika pembeda 'isQueued' agar tampilan UI seragam
            // Backend Anda di PresensiQrController.php sudah mengirim 'sent' dan 'failed' untuk email juga.
            
            appendLog(`✓ Total Peserta: ${s.total ?? 0}`);
            
            // Tampilkan status Terkirim dan Gagal untuk SEMUA metode (Email & WA)
            appendLog(`✓ Terkirim: ${s.sent ?? 0}`);
            appendLog(`✗ Gagal: ${s.failed ?? 0}`);

            showSuccess(
                'Pengiriman Selesai!',
                `QR Code berhasil diproses. Total: ${s.total}.`, // Pesan diseragamkan
                false
            );

            if (btnSend) {
                btnSend.disabled = true;
                btnSend.style.display = 'none';
            }

            // Refresh otomatis setelah 2 detik agar pengguna melihat status terbaru
            setTimeout(() => {
                location.reload();
            }, 4000);

        } else {
            appendLog(json?.message || 'Pengiriman gagal.');
            if (loadingText) loadingText.textContent = json?.message || 'Pengiriman gagal.';
            btnSyncClose.disabled = false;
        }
    } catch (e) {
        console.error(e);
        appendLog('Terjadi kesalahan saat mengirim.');
        if (loadingText) loadingText.textContent = 'Terjadi kesalahan saat mengirim.';
        btnSyncClose.disabled = false;
    }
  };

  btnSend.addEventListener('click', sendQRCode);
  
  btnModalSend?.addEventListener('click', () => {
    closeModal();
    sendQRCode();
  });

  updateUIForSource();
})();