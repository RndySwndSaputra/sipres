document.addEventListener('DOMContentLoaded', () => {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const pageEl = document.querySelector('.presensi-page');
  
  // --- [PERBAIKAN UTAMA] AMBIL ID ACARA SAAT INI ---
  // Ini penting agar backend bisa menolak QR Code dari acara lain.
  let currentAcaraId = pageEl ? pageEl.getAttribute('data-id') : null;
  
  // Fallback: Jika di Blade lupa dikasih data-id, ambil dari URL browser
  if (!currentAcaraId) {
      const pathMatch = window.location.pathname.match(/\/presensi\/([^\/?#]+)/);
      if (pathMatch) currentAcaraId = pathMatch[1];
  }

  const currentMode = pageEl ? pageEl.getAttribute('data-mode') || 'Offline' : 'Offline';
  const currentJenis = pageEl ? pageEl.getAttribute('data-jenis') || 'offline' : 'offline';
  
  // [PERBAIKAN DISINI]: Baca Tipe Presensi (Cepat/Tradisional)
  const currentTipe = pageEl ? pageEl.getAttribute('data-tipe') || 'Tradisional' : 'Tradisional';
  
  // Variabel ini dideklarasikan SEKALI disini untuk dipakai di seluruh file
  const isModeCepat = currentTipe === 'Cepat'; 

  const isOnline = currentJenis === 'online' || currentMode === 'Online';

  // --- LOGIKA PRESENSI ONLINE (Manual NIP) ---
  if (isOnline) {
      const onlineForm = document.getElementById('onlinePresensiForm');
      const inputNip = document.getElementById('inputNipOnline');
      const btnSubmit = document.getElementById('btnSubmitOnline');
      const msgBox = document.getElementById('onlineMessage');

      if (onlineForm) {
          onlineForm.addEventListener('submit', async (e) => {
              e.preventDefault();
              const nip = inputNip.value.trim();
              if(!nip) return;

              btnSubmit.disabled = true;
              btnSubmit.innerHTML = '<span class="spinner spinner-xs"></span> Memproses...';
              msgBox.style.display = 'none';
              msgBox.className = 'message-box';

              try {
                  const res = await fetch('/admin/presensi/confirm', {
                      method: 'POST',
                      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                      body: JSON.stringify({ 
                          nip_manual: nip, 
                          mode_presensi: 'Online',
                          current_acara_id: currentAcaraId // [FIX] Kirim ID Acara
                      })
                  });
                  const json = await res.json();

                  if (res.ok && json.success) {
                      msgBox.textContent = `Berhasil! Absensi tercatat untuk ${json.data?.nama || nip}.`;
                      msgBox.className = 'message-box success';
                      msgBox.style.display = 'block';
                      inputNip.value = ''; 
                  } else {
                      const errMsg = json.message || 'Gagal menyimpan presensi.';
                      if (errMsg.toLowerCase().includes('sudah melakukan')) {
                          msgBox.textContent = errMsg;
                          msgBox.className = 'message-box success'; 
                      } else {
                          msgBox.textContent = errMsg;
                          msgBox.className = 'message-box error';
                      }
                      msgBox.style.display = 'block';
                  }
              } catch (error) {
                  console.error(error);
                  msgBox.textContent = 'Terjadi kesalahan koneksi. Coba lagi.';
                  msgBox.className = 'message-box error';
                  msgBox.style.display = 'block';
              } finally {
                  btnSubmit.disabled = false;
                  btnSubmit.innerHTML = 'Kirim Presensi';
              }
          });
      }
      return; 
  }

  // ============================================================
  // --- LOGIKA PRESENSI OFFLINE (QR SCANNER) ---
  // ============================================================

  const video = document.getElementById('scanVideo');
  const inputNama = document.getElementById('scanNama');
  const inputNip = document.getElementById('scanNip');
  const inputSkpd = document.getElementById('scanSkpd');
  const canvas = document.getElementById('signaturePad');
  const btnClear = document.getElementById('btnClearSign');
  const btnSave = document.getElementById('btnSave');
  const scanGuide = document.getElementById('scanGuide');
  const scanGuideAnim = document.getElementById('scanGuideAnim');
  const scanGuideAnimSrc = scanGuideAnim ? scanGuideAnim.getAttribute('data-src') : null;
  const formFields = document.getElementById('formFields');
  
  const sigPanel = document.querySelector('.sig-panel');
  
  let scannedIdPresensi = null;
  let idleTimer = null;
  
  // [UX FIX] Perpanjang waktu reset form jadi 15 detik
  const resetIdle = () => {
    if (idleTimer) clearTimeout(idleTimer);
    idleTimer = setTimeout(() => {
      if (formFields && formFields.style.display !== 'none') {
        showScanGuide();
      }
    }, 5000); 
  };

  const showCameraError = (message) => {
    const videoWrap = document.querySelector('.scan-video-wrap');
    if (videoWrap) {
      videoWrap.innerHTML = `<div class="camera-error"><p>${message}</p><button id="btnRetryCamera" class="btn btn-primary">Coba Lagi</button></div>`;
      const btnRetry = document.getElementById('btnRetryCamera');
      btnRetry?.addEventListener('click', startCamera);
    }
  };

  const startCamera = async () => {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      showCameraError('Browser tidak mendukung akses kamera.');
      return;
    }
    try {
      let stream;
      try {
        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { exact: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } }, audio: false });
      } catch (backCamError) {
        stream = await navigator.mediaDevices.getUserMedia({ video: { width: { ideal: 1280 }, height: { ideal: 720 } }, audio: false });
      }
      video.srcObject = stream;
      await video.play();
      const errorDiv = document.querySelector('.camera-error');
      if (errorDiv) errorDiv.remove();
      startScan();
    } catch (error) {
      console.error('Camera error:', error);
      showCameraError('Tidak dapat mengakses kamera.');
    }
  };

  const offCanvas = document.createElement('canvas');
  const offCtx = offCanvas.getContext('2d', { willReadFrequently: true });
  let scanning = false;
  let cooldownUntil = 0;
  let audioCtx;
  let pendingLookup = false;
  const wait = (ms) => new Promise(r => setTimeout(r, ms));
  const ROI_SCALES = [0.95, 0.85, 0.7, 0.55];
  let roiIdx = 0;

  const beep = () => {
    try {
      if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      const o = audioCtx.createOscillator();
      const g = audioCtx.createGain();
      o.type = 'sine';
      o.frequency.value = 880;
      g.gain.setValueAtTime(0.001, audioCtx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.1, audioCtx.currentTime + 0.01);
      g.gain.exponentialRampToValueAtTime(0.00001, audioCtx.currentTime + 0.15);
      o.connect(g);
      g.connect(audioCtx.destination);
      o.start();
      o.stop(audioCtx.currentTime + 0.16);
    } catch {}
  };

  const beepError = () => {
    try {
      if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      const o = audioCtx.createOscillator();
      const g = audioCtx.createGain();
      o.type = 'sine';
      o.frequency.value = 440;
      g.gain.setValueAtTime(0.001, audioCtx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.12, audioCtx.currentTime + 0.01);
      g.gain.exponentialRampToValueAtTime(0.00001, audioCtx.currentTime + 0.18);
      o.connect(g);
      g.connect(audioCtx.destination);
      o.start();
      o.stop(audioCtx.currentTime + 0.2);
    } catch {}
  };

  const lookupModal = document.getElementById('lookupModal');
  const modalTextEl = lookupModal ? lookupModal.querySelector('.modal-text') : null;
  const showModal = () => { if (lookupModal) { lookupModal.classList.add('show'); if (modalTextEl) modalTextEl.textContent = 'Mencari data peserta...'; } };
  const hideModal = () => { if (lookupModal) lookupModal.classList.remove('show'); };
  
  const showFormFields = () => {
    if (scanGuide) scanGuide.style.display = 'none';
    if (formFields) {
      formFields.style.display = 'grid';
      setTimeout(() => resizeCanvas(), 100);
      
      if (isModeCepat && sigPanel) {
        sigPanel.style.display = 'none';
      } else if (sigPanel) {
        sigPanel.style.display = 'block';
      }
    }
    resetIdle();
  };

  // --- [UX FIX] BANNER FIXED ---
  const showSuccessBanner = (text) => {
    let banner = document.getElementById('successBanner');
    if (!banner) {
      banner = document.createElement('div');
      banner.id = 'successBanner';
      banner.className = 'success-banner fixed-banner'; 
      banner.innerHTML = `
        <div class="success-banner__icon">
          <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="9" stroke="currentColor" />
            <path d="M8 12l2.5 2.5L16 9" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </div>
        <div class="success-banner__text"></div>
      `;
      document.body.appendChild(banner); 
    }
    const textEl = banner.querySelector('.success-banner__text');
    if (textEl) textEl.textContent = text;
    
    banner.style.display = 'flex';
    banner.classList.remove('hide');
    banner.classList.add('show');

    setTimeout(() => {
        banner.classList.remove('show');
        banner.classList.add('hide');
        setTimeout(() => { banner.style.display = 'none'; }, 300);
    }, 4000);
  };

  const isAlreadyMessage = (msg) => {
    if (!msg) return false;
    const m = String(msg).toLowerCase();
    return m.includes('sudah melakukan') || m.includes('sudah terisi');
  };

  const showErrorBanner = (text) => {
    let banner = document.getElementById('errorBanner');
    if (!banner) {
      banner = document.createElement('div');
      banner.id = 'errorBanner';
      banner.className = 'error-banner fixed-banner'; 
      banner.innerHTML = `
        <div class="error-banner__icon">
          <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="9" stroke="currentColor" />
            <path d="M9 9l6 6M15 9l-6 6" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </div>
        <div class="error-banner__text"></div>
      `;
      document.body.appendChild(banner);
    }
    const textEl = banner.querySelector('.error-banner__text');
    if (textEl) textEl.textContent = text;
    
    banner.style.display = 'flex';
    banner.classList.remove('hide');
    banner.classList.add('show');
    
    setTimeout(() => {
        banner.classList.remove('show');
        banner.classList.add('hide');
        setTimeout(() => { banner.style.display = 'none'; }, 300);
    }, 4000);
  };

  const showAlreadyBanner = (msg) => {
    showErrorBanner(msg || 'Presensi sudah terisi sebelumnya.');
  };

  const showScanGuide = () => {
    if (idleTimer) { clearTimeout(idleTimer); idleTimer = null; }
    if (scanGuide) scanGuide.style.display = 'block';
    if (formFields) formFields.style.display = 'none';
    
    if (inputNama) inputNama.value = '';
    if (inputNip) inputNip.value = '';
    if (inputSkpd) inputSkpd.value = '';
    
    if (canvas) {
      const ctx = canvas.getContext('2d');
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      drawing = false;
    }
  };

  if (scanGuideAnim && scanGuideAnimSrc && typeof window.lottie !== 'undefined') {
    window.lottie.loadAnimation({ container: scanGuideAnim, renderer: 'svg', loop: true, autoplay: true, path: scanGuideAnimSrc });
  }

  const lookupAndFill = async (id) => {
    if (pendingLookup) return;
    pendingLookup = true;
    showModal();
    try {
      const url = `/admin/presensi/lookup/${encodeURIComponent(id)}?current_acara_id=${currentAcaraId}`;
      const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
      if (res.ok) {
        const json = await res.json();
        if (json.success) {
          scannedIdPresensi = id;
          inputNip.value = json.data.nip || '';
          inputNama.value = json.data.nama || '';
          inputSkpd.value = json.data.skpd || '';
          
          if (isModeCepat) {
            showFormFields();
            resetIdle();
            
            try {
              const resp = await fetch('/admin/presensi/confirm', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ 
                    id_presensi: scannedIdPresensi, 
                    mode_presensi: currentMode,
                    current_acara_id: currentAcaraId
                })
              });
              const j = await resp.json().catch(() => ({}));
              if (resp.ok && j && j.success) {
                beep();
                showSuccessBanner('Absensi berhasil (Mode Cepat).');
                scannedIdPresensi = null;
              } else {
                const msg = j?.message || 'Gagal menyimpan presensi.';
                if (isAlreadyMessage(msg)) {
                  beep();
                  showAlreadyBanner(msg);
                  scannedIdPresensi = null;
                } else {
                  beepError();
                  showErrorBanner(msg); // <--- [FIX] Menggunakan Banner
                }
              }
            } catch (e) {
              beepError();
              showErrorBanner('Terjadi kesalahan saat menyimpan presensi.'); // <--- [FIX] Menggunakan Banner
            }
          } else {
            showFormFields();
            resetIdle();
          }
        } else {
          if (modalTextEl) modalTextEl.textContent = 'Peserta Tidak Ditemukan';
          beepError();
          await wait(1200);
        }
      } else {
        if (modalTextEl) modalTextEl.textContent = 'Peserta Tidak Ditemukan';
        beepError();
        await wait(1200);
      }
    } catch {}
    hideModal();
    pendingLookup = false;
  };

  const getTargetSize = (boxSize) => {
    if (boxSize >= 1280) return 1024;
    if (boxSize >= 960) return 768;
    if (boxSize >= 720) return 640;
    return 512;
  };

  const scanLoop = () => {
    if (!scanning) return;
    if (video.videoWidth && video.videoHeight && typeof jsQR === 'function') {
      const vw = video.videoWidth;
      const vh = video.videoHeight;
      const minSide = Math.min(vw, vh);
      const now = Date.now();
      let code = null;

      for (let pass = 0; pass < 2; pass++) {
        const scale = ROI_SCALES[(roiIdx + pass) % ROI_SCALES.length];
        const boxSize = Math.floor(minSide * scale);
        const sx = Math.floor((vw - boxSize) / 2);
        const sy = Math.floor((vh - boxSize) / 2);
        const target = getTargetSize(boxSize);
        offCanvas.width = target;
        offCanvas.height = target;
        offCtx.imageSmoothingEnabled = false;
        offCtx.drawImage(video, sx, sy, boxSize, boxSize, 0, 0, target, target);
        const img = offCtx.getImageData(0, 0, target, target);
        code = jsQR(img.data, img.width, img.height);
        if (code) break;
      }

      roiIdx = (roiIdx + 1) % ROI_SCALES.length;
      if (code && now >= cooldownUntil && !pendingLookup) {
        const data = (code.data || '').trim();
        if (data) {
          beep();
          lookupAndFill(data);
          cooldownUntil = now + 2000;
        }
      }
    }
    requestAnimationFrame(scanLoop);
  };

  const startScan = () => {
    if (scanning) return;
    scanning = true;
    requestAnimationFrame(scanLoop);
  };

  // Canvas
  const resizeCanvas = () => {
    if (!canvas) return;
    const dpr = Math.max(1, window.devicePixelRatio || 1);
    const rect = canvas.getBoundingClientRect();
    if (rect.width === 0 || rect.height === 0) return;
    canvas.width = Math.floor(rect.width * dpr);
    canvas.height = Math.floor(rect.height * dpr);
    const ctx = canvas.getContext('2d');
    ctx.scale(dpr, dpr);
    ctx.lineWidth = 2.5;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.strokeStyle = '#111827';
  };

  let drawing = false;
  let lastX = 0;
  let lastY = 0;
  const getPos = (e) => {
    const rect = canvas.getBoundingClientRect();
    if (e.touches && e.touches.length) {
      return { x: e.touches[0].clientX - rect.left, y: e.touches[0].clientY - rect.top };
    }
    return { x: e.clientX - rect.left, y: e.clientY - rect.top };
  };

  const onDown = (e) => {
    drawing = true;
    const p = getPos(e);
    lastX = p.x; lastY = p.y;
    resetIdle();
  };
  const onMove = (e) => {
    if (!drawing || !canvas) return;
    e.preventDefault();
    const ctx = canvas.getContext('2d');
    const p = getPos(e);
    ctx.beginPath();
    ctx.moveTo(lastX, lastY);
    ctx.lineTo(p.x, p.y);
    ctx.stroke();
    lastX = p.x; lastY = p.y;
    resetIdle();
  };
  const onUp = () => { drawing = false; };

  if(canvas) {
      window.addEventListener('resize', resizeCanvas);
      resizeCanvas();
      canvas.addEventListener('mousedown', onDown);
      canvas.addEventListener('mousemove', onMove);
      window.addEventListener('mouseup', onUp);
      canvas.addEventListener('touchstart', onDown, { passive: false });
      canvas.addEventListener('touchmove', onMove, { passive: false });
      canvas.addEventListener('touchend', onUp);
  }

  btnClear?.addEventListener('click', () => {
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    drawing = false;
    resetIdle();
  });

  btnSave?.addEventListener('click', () => {
      resetIdle();
      const setLoading = (isLoading) => {
        if (!btnSave) return;
        if (isLoading) {
          btnSave.disabled = true;
          btnSave.classList.add('loading');
          btnSave._originalHtml = btnSave._originalHtml || btnSave.innerHTML;
          btnSave.innerHTML = `<span class="spinner spinner-xs" aria-hidden="true"></span><span>Menyimpan...</span>`;
        } else {
          btnSave.disabled = false;
          btnSave.classList.remove('loading');
          if (btnSave._originalHtml) btnSave.innerHTML = btnSave._originalHtml;
        }
      };

      const isBlank = (() => {
        if (!canvas) return true;
        const ctx = canvas.getContext('2d');
        const img = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
        for (let i = 3; i < img.length; i += 4) { 
          if (img[i] !== 0) return false;
        }
        return true;
      })();

      if (!scannedIdPresensi) {
        showErrorBanner('QR belum dipindai.'); // <--- [FIX] Menggunakan Banner
        return;
      }

      if (!isModeCepat && isBlank) {
        showErrorBanner('Tanda tangan belum diisi.'); // <--- [FIX] Menggunakan Banner
        return;
      }

      const toBlob = () => new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));

      (async () => {
        try {
          setLoading(true);
          let res;
          
          if (isModeCepat) {
            // Fallback (Rare case)
            res = await fetch('/admin/presensi/confirm', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
              body: JSON.stringify({ 
                  id_presensi: scannedIdPresensi, 
                  mode_presensi: currentMode,
                  current_acara_id: currentAcaraId
              }),
            });
          } else {
            const blob = await toBlob();
            const form = new FormData();
            form.append('id_presensi', scannedIdPresensi);
            form.append('mode_presensi', currentMode);
            form.append('current_acara_id', currentAcaraId);
            form.append('signature', blob, 'signature.png');
            
            res = await fetch('/admin/presensi/confirm', {
              method: 'POST',
              headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
              body: form,
            });
          }

          const json = await res.json().catch(() => ({}));
          
          if (res.ok && json && json.success) {
            beep();
            showSuccessBanner(isModeCepat ? 'Absensi berhasil (Mode Cepat).' : 'Absensi berhasil.');
            showScanGuide();
            scannedIdPresensi = null;
          } else {
            beepError();
            const msg = json?.message || 'Gagal menyimpan presensi.';
            if (isAlreadyMessage(msg)) {
              beep();
              showAlreadyBanner(msg); 
              showScanGuide();
              scannedIdPresensi = null;
            } else {
              showErrorBanner(msg); // <--- [FIX] Menggunakan Banner
            }
          }
        } catch (e) {
          beepError();
          showErrorBanner('Terjadi kesalahan saat menyimpan presensi.'); // <--- [FIX] Menggunakan Banner
        } finally {
          setLoading(false);
        }
      })();
  });

  // Camera Init
  const btnStartCamera = document.getElementById('btnStartCamera');
  const cameraPrompt = document.getElementById('cameraPrompt');
  
  btnStartCamera?.addEventListener('click', () => {
    if (cameraPrompt) cameraPrompt.style.display = 'none';
    startCamera();
  });

  const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
  if (isMobile) {
    if (cameraPrompt) cameraPrompt.style.display = 'flex';
  } else {
    if (cameraPrompt) cameraPrompt.style.display = 'none';
    startCamera();
  }

  showScanGuide();
});