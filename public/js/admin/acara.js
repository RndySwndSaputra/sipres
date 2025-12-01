(() => {
    // =========================================================================
    // 1. STATE VARIABLES
    // =========================================================================
    let events = [];
    let keyword = '';
    let viewMode = 'grid';
    let currentStep = 1;
    const maxStep = 3;

    // =========================================================================
    // 2. SELECTOR ELEMEN
    // =========================================================================
    const grid = document.getElementById('eventGrid');
    const table = document.getElementById('eventTable');
    const search = document.getElementById('eventSearch');
    const filterStatus = document.getElementById('filterStatus'); // BARU: Filter Status
    const btnAdd = document.getElementById('btnAddEvent');
    const btnViewGrid = document.getElementById('btnViewGrid');
    const btnViewTable = document.getElementById('btnViewTable');
    const loadingOverlay = document.getElementById('mainLoading');
    
    const modal = document.getElementById('eventModal');
    const form = document.getElementById('eventForm');
    const modalTitle = document.getElementById('eventModalTitle');
    const toastContainer = document.getElementById('toastContainer');
    
    // --- Input Fields ---
    const inputId = document.getElementById('eventId');
    const inputNama = document.getElementById('namaAcara');
    const inputMateri = document.getElementById('materiAcara');
    const inputLokasi = document.getElementById('lokasiAcara');
    const inputLink = document.getElementById('linkMeeting');
    
    // Container untuk Show/Hide
    const containerLokasi = document.getElementById('fieldLokasiContainer');
    const containerLink = document.getElementById('fieldLinkContainer');

    // --- LOGIKA BARU: Mode & Tipe Presensi ---
    const inputModePresensi = document.getElementById('modePresensi'); // Offline, Online, Kombinasi
    const containerTipePresensi = document.getElementById('fieldTipePresensiContainer'); // Wadah dropdown tipe
    const inputTipePresensi = document.getElementById('tipePresensi'); // Tradisional, Cepat
    const hintTradisional = document.getElementById('hintTradisional'); // Teks petunjuk TTD
    const hintCepat = document.getElementById('hintCepat'); // Teks petunjuk Tanpa TTD

    // --- Waktu ---
    const inputMulai = document.getElementById('waktuMulai');
    const inputSelesai = document.getElementById('waktuSelesai');
    const inputIstirahatMulai = document.getElementById('waktuIstirahatMulai');
    const inputIstirahatSelesai = document.getElementById('waktuIstirahatSelesai');
    
    // --- Peserta & Note ---
    const inputPeserta = document.getElementById('totalPeserta');
    const absenNote = document.getElementById('absenNote'); 

    // --- Stepper Buttons ---
    const btnNext = document.getElementById('btnNextStep'); 
    const btnPrev = document.getElementById('btnPrevStep'); 
    const btnSave = document.getElementById('btnSaveStep'); 
    const btnCancel = document.getElementById('btnCancel');
    
    const stepItems = document.querySelectorAll('.stepper-item');
    const formSteps = document.querySelectorAll('.step-content');

    // --- Time Setting Modal ---
    const timeModal = document.getElementById('timeSettingModal');
    const timeForm = document.getElementById('timeSettingForm');
    const settingEventId = document.getElementById('settingEventId');
    const settingTolerance = document.getElementById('settingTolerance');

    // --- Empty State ---
    const emptyState = document.getElementById('emptyState');

    if (!grid || !modal || !form) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // =========================================================================
    // 3. HELPER FUNCTIONS
    // =========================================================================
    const getNowWIB = () => {
        const now = new Date();
        const parts = new Intl.DateTimeFormat('en-CA', {
            timeZone: 'Asia/Jakarta',
            year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit', hour12: false
        }).formatToParts(now);
        const part = (type) => parts.find(p => p.type === type).value;
        return `${part('year')}-${part('month')}-${part('day')}T${part('hour')}:${part('minute')}`;
    };

    const getRawTime = (datetimeStr) => {
        if (!datetimeStr) return '';
        const match = datetimeStr.match(/(\d{2}):(\d{2})/);
        return match ? `${match[1]}:${match[2]}` : '';
    };

    const formatDateIndo = (dateStr) => {
        if (!dateStr) return '-';
        const datePart = dateStr.includes('T') ? dateStr.split('T')[0] : dateStr.split(' ')[0];
        const [y, m, d] = datePart.split('-');
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        return `${d} ${months[parseInt(m) - 1]} ${y}`;
    };

    const formatDateRange = (startStr, endStr) => {
        if (!startStr) return '-';
        const start = formatDateIndo(startStr);
        const end = endStr ? formatDateIndo(endStr) : null;
        if (!end || start === end) return start;
        return `${start} - ${end}`;
    };

    const formatTimeRange = (startStr, endStr) => {
        const s = getRawTime(startStr);
        const e = getRawTime(endStr);
        if(!s && !e) return '-';
        if(!s) return `- ${e} WIB`;
        if(!e) return `${s} WIB -`;
        return `${s} - ${e} WIB`;
    };

    const normalizeToInput = (value) => {
        if (!value) return '';
        const str = String(value).trim();
        if (str.includes('T')) return str.substring(0, 16);
        const m = str.match(/(\d{4}-\d{2}-\d{2})[\sT](\d{2}):(\d{2})/);
        if (m) return `${m[1]}T${m[2]}:${m[3]}`;
        return '';
    };

    const showToast = (message, type = 'success') => {
        if (!toastContainer) return;
        let finalMessage = message;
        if (message.includes('must be a date after or equal to')) finalMessage = 'Waktu selesai harus setelah waktu mulai';
        const el = document.createElement('div');
        el.className = `toast ${type}`;
        el.innerHTML = `<span>${finalMessage}</span><button class="close">&times;</button>`;
        el.querySelector('.close').addEventListener('click', () => el.remove());
        toastContainer.appendChild(el);
        setTimeout(() => el.remove(), 3000);
    };

    const getLiveStatus = (mulai, selesai) => {
        try {
            const nowWIB = getNowWIB();
            const startStr = normalizeToInput(mulai);
            const endStr = normalizeToInput(selesai);
            
            if (nowWIB < startStr) return { label: 'Akan Datang', class: 'status-upcoming' };
            if (nowWIB > endStr) return { label: 'Selesai', class: 'status-completed' };
            return { label: 'Berlangsung', class: 'status-ongoing' };
        } catch {
            return { label: 'Akan Datang', class: 'status-upcoming' };
        }
    };

    // =========================================================================
    // 4. LOGIKA FORM
    // =========================================================================
    
    const updateFormDisplay = () => {
        const mode = inputModePresensi.value; 
        const tipe = inputTipePresensi.value; 

        if(inputLokasi) inputLokasi.required = false;
        if(inputLink) inputLink.required = false;
        
        containerLokasi.style.display = 'none';
        containerLink.style.display = 'none';
        
        if(containerTipePresensi) containerTipePresensi.style.display = 'none';

        if (mode === 'Online') {
            if(inputLink) {
                containerLink.style.display = 'block';
                inputLink.required = true;
                containerLink.style.gridColumn = '1 / -1'; 
            }
        } else if (mode === 'Kombinasi') {
            if(inputLokasi) {
                containerLokasi.style.display = 'block';
                inputLokasi.required = true;
            }
            if(inputLink) {
                containerLink.style.display = 'block';
                inputLink.required = true;
            }
            if(containerTipePresensi) containerTipePresensi.style.display = 'block';

        } else {
            if(inputLokasi) {
                containerLokasi.style.display = 'block';
                inputLokasi.required = true;
                containerLokasi.style.gridColumn = '1 / -1'; 
            }
            if(containerTipePresensi) containerTipePresensi.style.display = 'block';
        }

        if (tipe === 'Cepat') {
            if(hintTradisional) hintTradisional.style.display = 'none';
            if(hintCepat) hintCepat.style.display = 'inline';
        } else {
            if(hintTradisional) hintTradisional.style.display = 'inline';
            if(hintCepat) hintCepat.style.display = 'none';
        }

        updatePresensiHint(mode, tipe);
    };

    const updatePresensiHint = (mode, tipe) => {
        if(!absenNote) return;
        const noteOffline = absenNote.querySelector('.note-offline');
        const noteOnline = absenNote.querySelector('.note-online');
        const noteKombinasi = absenNote.querySelector('.note-kombinasi');

        [noteOffline, noteOnline, noteKombinasi].forEach(el => {
            if(el) el.style.display = 'none';
        });

        let infoTambahan = "";
        if (tipe === 'Cepat') {
            infoTambahan = "Mode Cepat: Scan Barcode langsung tercatat HADIR (Tanpa Tanda Tangan).";
        } else {
            infoTambahan = "Mode Tradisional: Scan Barcode dan wajib Tanda Tangan Digital.";
        }

        if(mode === 'Online' && noteOnline) {
            noteOnline.style.display = 'flex';
        }
        else if(mode === 'Kombinasi' && noteKombinasi) {
            noteKombinasi.style.display = 'flex';
            const textDiv = noteKombinasi.querySelector('div');
            if(textDiv) textDiv.innerHTML = `<strong>Kombinasi:</strong> Mendukung peserta Online (Link) dan Offline.<br><small>${infoTambahan}</small>`;
        }
        else if (noteOffline) {
            noteOffline.style.display = 'flex';
            const textDiv = noteOffline.querySelector('div');
            if(textDiv) textDiv.innerHTML = `<strong>Offline:</strong> Peserta hadir di lokasi.<br><small>${infoTambahan}</small>`;
        }
    };

    if(inputModePresensi) {
        inputModePresensi.addEventListener('change', updateFormDisplay);
    }
    if(inputTipePresensi) {
        inputTipePresensi.addEventListener('change', updateFormDisplay);
    }

    // =========================================================================
    // 5. STEPPER
    // =========================================================================
    const updateStepperUI = () => {
        stepItems.forEach(item => {
            const step = parseInt(item.dataset.step);
            item.classList.remove('active', 'completed');
            if(step === currentStep) item.classList.add('active');
            if(step < currentStep) item.classList.add('completed');
        });

        formSteps.forEach(fs => {
            fs.classList.remove('active');
            if(parseInt(fs.dataset.content) === currentStep) fs.classList.add('active');
        });

        if(btnPrev && btnCancel) {
            if(currentStep === 1) { btnPrev.style.display = 'none'; btnCancel.style.display = 'inline-flex'; }
            else { btnPrev.style.display = 'inline-flex'; btnCancel.style.display = 'none'; }
        }
        
        if(btnNext && btnSave) {
            if(currentStep === maxStep) { btnNext.style.display = 'none'; btnSave.style.display = 'inline-flex'; }
            else { btnNext.style.display = 'inline-flex'; btnSave.style.display = 'none'; }
        }
    };

    const validateCurrentStep = () => {
        const currentPanel = document.querySelector(`.step-content[data-content="${currentStep}"]`);
        if(!currentPanel) return true;

        const requiredInputs = currentPanel.querySelectorAll('[required]');
        let valid = true;
        requiredInputs.forEach(input => {
            if (input.offsetParent === null) return; 
            
            if (!input.value.trim()) { 
                valid = false; 
                input.style.borderColor = '#dc2626'; 
            } else { 
                input.style.borderColor = ''; 
            }
        });

        if (currentStep === 2 && valid) {
            if (inputSelesai.value < inputMulai.value) {
                showToast('Waktu selesai tidak boleh lebih awal dari waktu mulai', 'error');
                return false;
            }
        }
        if(!valid) showToast('Mohon lengkapi data bertanda *', 'error');
        return valid;
    };

    if(btnNext) btnNext.addEventListener('click', () => {
        if(validateCurrentStep() && currentStep < maxStep) { currentStep++; updateStepperUI(); }
    });

    if(btnPrev) btnPrev.addEventListener('click', () => {
        if(currentStep > 1) { currentStep--; updateStepperUI(); }
    });

    // =========================================================================
    // 6. CRUD (FETCH & RENDER) - UPDATE FILTERING STATUS
    // =========================================================================
    const fetchList = async () => {
        if(loadingOverlay) loadingOverlay.hidden = false;
        try {
            const res = await fetch('/admin/acara/data', { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error(res.statusText);
            const json = await res.json();
            events = json.success ? json.data : [];
        } catch(e) { console.error(e); events = []; showToast('Gagal memuat data', 'error'); }
        render();
        if(loadingOverlay) loadingOverlay.hidden = true;
    };

    const render = () => {
        if (typeof viewMode === 'undefined') viewMode = 'grid';
        
        // --- LOGIKA FILTER STATUS (BARU) ---
        const statusValue = filterStatus ? filterStatus.value : ''; // Ambil nilai dropdown

        const list = events.filter(e => {
            // 1. Filter Keyword
            const matchKeyword = (e.nama_acara||'').toLowerCase().includes(keyword);
            if (!matchKeyword) return false;

            // 2. Filter Status
            if (statusValue) {
                const currentStatus = getLiveStatus(e.waktu_mulai, e.waktu_selesai).label;
                // "Akan Datang", "Selesai", "Berlangsung"
                if (currentStatus !== statusValue) return false;
            }

            return true;
        });
        
        if(!list.length) {
            if(grid) grid.innerHTML = '';
            if(table) table.innerHTML = '';
            if(emptyState) {
                emptyState.hidden = false;
                const msgEl = emptyState.querySelector('.empty-message');
                if (msgEl) msgEl.textContent = (keyword || statusValue) ? 'Tidak ada acara ditemukan' : 'Belum ada acara';
            }
            return;
        } 
        if(emptyState) emptyState.hidden = true;

        if(viewMode === 'grid') {
            if(grid) grid.hidden = false; 
            if(table) table.hidden = true;
            
            grid.innerHTML = list.map(e => {
                const statusInfo = getLiveStatus(e.waktu_mulai, e.waktu_selesai);
                const istirahatStr = formatTimeRange(e.waktu_istirahat_mulai, e.waktu_istirahat_selesai);
                const materiStr = e.materi || '-';
                const mode = e.mode_presensi; 
                
                const lokasiStr = e.lokasi || '-';
                const linkUrl = e.link_meeting || '#';

                const iconLoc = `<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>`;
                const iconLink = `<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>`;

                let infoLokasiHtml = '';
                if (mode === 'Online') {
                    infoLokasiHtml = `<div class="info-item" title="Link Meeting">${iconLink} <a href="${linkUrl}" target="_blank" style="color:#2563eb; text-decoration:none; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${linkUrl}</a></div>`;
                } else if (mode === 'Kombinasi') {
                    infoLokasiHtml = `<div class="info-item" title="Lokasi">${iconLoc} <span>${lokasiStr}</span></div><div class="info-item" title="Link Meeting">${iconLink} <a href="${linkUrl}" target="_blank" style="color:#2563eb; text-decoration:none; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${linkUrl}</a></div>`;
                } else {
                    infoLokasiHtml = `<div class="info-item" title="Lokasi">${iconLoc} <span>${lokasiStr}</span></div>`;
                }

                const btnAbsenHtml = `<button class="btn btn-primary" data-action="absen" title="Halaman Presensi (Scan QR)">Absen</button>`;
                const btnLinkHtml = `<button class="btn btn-info" data-action="copyLink" data-id="${e.id_acara}" title="Salin Link Zoom/Gmeet">${iconLink} Link</button>`;

                let actionButtons = '';
                if (mode === 'Online') actionButtons = btnLinkHtml;
                else if (mode === 'Kombinasi') actionButtons = btnAbsenHtml + btnLinkHtml;
                else actionButtons = btnAbsenHtml;

                return `
                <div class="acara-card" data-id="${e.id_acara}">
                    <div class="card-header">
                         <div style="display:flex; gap:8px; align-items:center; width:100%; justify-content:space-between;">
                             <div class="card-status ${statusInfo.class}">${statusInfo.label}</div>
                             <div style="display:flex; gap:8px; align-items:center;">
                                ${mode === 'Online' ? '<span class="badge" style="background:#eff6ff;color:#2563eb;font-size:10px;padding:2px 8px;border-radius:4px;font-weight:bold;">ONLINE</span>' : ''}
                                ${mode === 'Kombinasi' ? '<span class="badge" style="background:#f0fdf4;color:#16a34a;font-size:10px;padding:2px 8px;border-radius:4px;font-weight:bold;">HYBRID</span>' : ''}
                                
                                <button class="btn-icon" data-action="timeSetting" title="Atur Batas Waktu" style="padding:4px; background:transparent; border:none; color:#64748b; cursor:pointer;">
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                                </button>
                             </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <h3 class="card-title" title="${e.nama_acara}">${e.nama_acara}</h3>
                        <div class="card-info">
                            <div class="info-item">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M16 2v4M8 2v4M3 10h18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                                <span>${formatDateRange(e.waktu_mulai, e.waktu_selesai)}</span>
                            </div>
                            <div class="info-item">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" aria-hidden="true"><path d="M12 2v8l4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/></svg>
                                <span>${formatTimeRange(e.waktu_mulai, e.waktu_selesai)}</span>
                            </div>
                            <div class="info-item" title="Jam Istirahat">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8h1a4 4 0 0 1 0 8h-1"></path><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path><line x1="6" y1="1" x2="6" y2="4"></line><line x1="10" y1="1" x2="10" y2="4"></line><line x1="14" y1="1" x2="14" y2="4"></line></svg>
                                <span>Istirahat: ${istirahatStr}</span>
                            </div>
                            <div class="info-item" title="Materi">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" aria-hidden="true"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                                <span style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${materiStr}</span>
                            </div>
                            ${infoLokasiHtml}
                            <div class="info-item">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" aria-hidden="true"><path d="M17 21v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><circle cx="10" cy="8" r="4" stroke="currentColor" stroke-width="1.5"/><path d="M20 21v-1a4 4 0 0 0-3-3.8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M17 3a4 4 0 0 1 0 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                                <span>${e.maximal_peserta ?? 0} Peserta</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        ${actionButtons} 
                        <button class="btn btn-outline" data-action="edit">Edit</button>
                        <button class="btn btn-danger" data-action="delete">Hapus</button>
                    </div>
                </div>`;
            }).join('');
        } 
        else {
            if(grid) grid.hidden = true; 
            if(table) table.hidden = false;
            
            const rows = list.length ? list.map(e => {
                const mode = e.mode_presensi;
                
                const btnAbsenIcon = `<button class="btn btn-primary btn-sm btn-icon" data-action="absen" title="Absen / Scan QR"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7V5a2 2 0 0 1 2-2h2"></path><path d="M17 3h2a2 2 0 0 1 2 2v2"></path><path d="M21 17v2a2 2 0 0 1-2 2h-2"></path><path d="M7 21H5a2 2 0 0 1-2-2v-2"></path><rect x="7" y="7" width="10" height="10" rx="1"></rect></svg></button>`;
                const btnLinkIcon = `<button class="btn btn-info btn-sm btn-icon" data-action="copyLink" data-id="${e.id_acara}" title="Salin Link"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg></button>`;
                const btnEditIcon = `<button class="btn btn-outline btn-sm btn-icon" data-action="edit" title="Edit"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg></button>`;
                const btnDeleteIcon = `<button class="btn btn-danger btn-sm btn-icon" data-action="delete" title="Hapus"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>`;

                let actionButtons = '';
                if (mode === 'Online') actionButtons = btnLinkIcon;
                else if (mode === 'Kombinasi') actionButtons = btnAbsenIcon + btnLinkIcon;
                else actionButtons = btnAbsenIcon;

                return `
                <tr data-id="${e.id_acara}">
                    <td>${e.nama_acara}</td>
                    <td>${formatDateRange(e.waktu_mulai, e.waktu_selesai)}</td>
                    <td>${formatTimeRange(e.waktu_mulai, e.waktu_selesai)}</td>
                    <td>${formatTimeRange(e.waktu_istirahat_mulai, e.waktu_istirahat_selesai)}</td>
                    <td>${e.maximal_peserta}</td>
                    <td class="col-actions">
                        <div class="cell-actions">
                            ${actionButtons}
                            ${btnEditIcon}
                            ${btnDeleteIcon}
                        </div>
                    </td>
                </tr>`;
            }).join('') : `<tr><td colspan="6" style="text-align:center;padding:20px;">Tidak ada data</td></tr>`;

            if(table) table.innerHTML = `
                <div class="table-wrapper">
                    <table class="table">
                        <thead><tr><th>Nama</th><th>Tanggal</th><th>Waktu</th><th>Istirahat</th><th>Peserta</th><th>Aksi</th></tr></thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
            `;
        }
    };

    // =========================================================================
    // 7. ACTION HANDLERS
    // =========================================================================
    const handleAction = (e) => {
        const btn = e.target.closest('button');
        if(!btn || !btn.dataset.action && !btn.id) return;
        const card = e.target.closest('[data-id]') || e.target.closest('tr');
        
        if (btn.id === 'btnViewGrid') { viewMode = 'grid'; btnViewGrid.classList.add('is-active'); if(btnViewTable) btnViewTable.classList.remove('is-active'); render(); return; } 
        if (btn.id === 'btnViewTable') { viewMode = 'table'; btnViewTable.classList.add('is-active'); if(btnViewGrid) btnViewGrid.classList.remove('is-active'); render(); return; }

        if(card && btn.dataset.action) {
            const id = card.dataset.id;
            const item = events.find(x => String(x.id_acara) === String(id));
            const action = btn.dataset.action;

            if (action === 'edit') openModal('edit', item);
            if (action === 'absen') window.location.href = `/admin/acara/presensi/${id}`;
            if (action === 'copyLink') copyLink(id);
            if (action === 'delete') {
                if (confirm(`Hapus acara "${item.nama_acara}"?`)) {
                    fetch(`/admin/acara/${id}`, {
                        method: 'DELETE', 
                        headers: {'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json'}
                    }).then(() => { showToast('Terhapus'); fetchList(); });
                }
            }
            if (action === 'timeSetting') handleTimeSetting(id);
        } 
    };

    document.body.addEventListener('click', handleAction);

    // =========================================================================
    // 8. MODAL FUNCTIONS (OPEN/RESET/EDIT)
    // =========================================================================
    const openModal = (mode, data) => {
        modal.classList.add('is-open');
        document.body.classList.add('no-scroll'); 
        currentStep = 1;
        updateStepperUI();
        form.reset();
        
        if(mode === 'create') {
            modalTitle.textContent = 'Tambah Acara';
            inputId.value = '';
            
            const now = new Date();
            const padLocal = (num) => String(num).padStart(2, '0');
            const localIso = `${now.getFullYear()}-${padLocal(now.getMonth()+1)}-${padLocal(now.getDate())}T${padLocal(now.getHours())}:${padLocal(now.getMinutes())}`;
            inputMulai.value = localIso;
            
            inputModePresensi.value = 'Offline';
            inputTipePresensi.value = 'Tradisional'; // Default Tradisional
            
        } else {
            modalTitle.textContent = 'Edit Acara';
            inputId.value = data.id_acara;
            inputNama.value = data.nama_acara;
            if(inputMateri) inputMateri.value = data.materi || '';
            
            inputLokasi.value = data.lokasi || '';
            if(inputLink) inputLink.value = data.link_meeting || '';
            
            inputMulai.value = normalizeToInput(data.waktu_mulai);
            inputSelesai.value = normalizeToInput(data.waktu_selesai);
            
            const istMul = normalizeToInput(data.waktu_istirahat_mulai);
            const istSel = normalizeToInput(data.waktu_istirahat_selesai);
            if(inputIstirahatMulai) inputIstirahatMulai.value = istMul.includes('T') ? istMul.split('T')[1] : istMul;
            if(inputIstirahatSelesai) inputIstirahatSelesai.value = istSel.includes('T') ? istSel.split('T')[1] : istSel;
            
            // Set Mode Presensi
            inputModePresensi.value = data.mode_presensi || 'Offline';
            
            // Set Tipe Presensi (ambil dari database)
            inputTipePresensi.value = data.tipe_presensi || 'Tradisional'; 
            
            inputPeserta.value = data.maximal_peserta || '';
        }
        
        // Panggil ini agar form menyesuaikan tampilan berdasarkan data yang baru diload
        updateFormDisplay();
    };

    window.copyLink = async (uid) => {
        const url = `${window.location.origin}/presensi/online/${uid}`;
        try { await navigator.clipboard.writeText(url); showToast('Link tersalin', 'success'); } 
        catch (err) {
            const textArea = document.createElement("textarea");
            textArea.value = url;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showToast('Link tersalin (manual)', 'success');
        }
    };

   const handleTimeSetting = (id) => {
        const item = events.find(x => String(x.id_acara) === String(id));
        if(!item) return;
        
        if(settingEventId) settingEventId.value = id;
        if(settingTolerance) settingTolerance.value = item.toleransi_menit || 15; 
        if(timeModal) timeModal.classList.add('is-open');
    };

    if(timeForm) {
        timeForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const id = settingEventId.value;
            const val = settingTolerance.value;
            
            const btnSubmit = timeForm.querySelector('button[type="submit"]');
            const originalText = btnSubmit.innerHTML;
            btnSubmit.innerText = 'Menyimpan...';
            btnSubmit.disabled = true;

            fetch(`/admin/acara/${id}/tolerance`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ toleransi_menit: val })
            })
            .then(res => res.json())
            .then(json => {
                if(json.success) {
                    const item = events.find(x => String(x.id_acara) === String(id));
                    if(item) item.toleransi_menit = val;
                    showToast(`Batas waktu berhasil diatur: ${val} menit`);
                    if(timeModal) timeModal.classList.remove('is-open');
                } else {
                    showToast('Gagal memperbarui toleransi', 'error');
                }
            })
            .catch(err => {
                console.error(err);
                showToast('Terjadi kesalahan sistem', 'error');
            })
            .finally(() => {
                btnSubmit.innerText = originalText;
                btnSubmit.disabled = false;
            });
        });
    }

    // =========================================================================
    // 9. MAIN FORM SAVE
    // =========================================================================
   if(btnSave) {
        btnSave.addEventListener('click', (e) => {
            e.preventDefault(); 
            
            if(!validateCurrentStep()) return;

            const isEdit = !!inputId.value;
            const url = isEdit ? `/admin/acara/${inputId.value}` : '/admin/acara';
            const method = isEdit ? 'PUT' : 'POST';
            
            const mode = inputModePresensi.value;
            // Ambil nilai tipe presensi (Tradisional / Cepat)
            const tipe = inputTipePresensi.value; 

            const rawMulai = inputMulai.value; 
            const baseDate = rawMulai ? rawMulai.split('T')[0] : null;

            const combineTime = (timeVal) => {
                if (!timeVal || !baseDate) return null;
                return `${baseDate}T${timeVal}`;
            };

            const payload = {
                nama_acara: inputNama.value,
                lokasi: inputLokasi.value,
                link_meeting: inputLink ? inputLink.value : '', 
                waktu_mulai: inputMulai.value,
                waktu_selesai: inputSelesai.value,
                waktu_istirahat_mulai: combineTime(inputIstirahatMulai.value),
                waktu_istirahat_selesai: combineTime(inputIstirahatSelesai.value),
                maximal_peserta: inputPeserta.value || 0,
                materi: inputMateri ? inputMateri.value : '',
                mode_presensi: mode,
                tipe_presensi: tipe, // KIRIM DATA TIPE KE SERVER
            };

            const originalText = btnSave.innerHTML;
            btnSave.innerHTML = 'Menyimpan...';
            btnSave.disabled = true;

            fetch(url, {
                method: method,
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': csrfToken, 
                    'Accept': 'application/json' 
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(json => {
                if(json.success) {
                    showToast('Berhasil disimpan', 'success');
                    modal.classList.remove('is-open');
                    document.body.classList.remove('no-scroll');
                    fetchList(); 
                } else { 
                    showToast(json.message || 'Gagal menyimpan', 'error'); 
                }
            })
            .catch(err => { 
                console.error(err); 
                showToast('Terjadi kesalahan server', 'error'); 
            })
            .finally(() => { 
                btnSave.innerHTML = originalText; 
                btnSave.disabled = false; 
            });
        });
    }

    if(btnAdd) btnAdd.addEventListener('click', () => openModal('create'));
    
    document.addEventListener('click', (e) => {
        if (e.target.matches('[data-dismiss="modal"]')) {
             if(modal) modal.classList.remove('is-open');
             if(timeModal) timeModal.classList.remove('is-open');
             document.body.classList.remove('no-scroll');
        }
    });
    
    modal.addEventListener('click', (e) => { if(e.target === document.querySelector('.modal__backdrop')) { modal.classList.remove('is-open'); document.body.classList.remove('no-scroll'); } });
    if(search) { search.addEventListener('input', (e) => { keyword = (e.target.value || '').toLowerCase().trim(); render(); }); }
    
    // --- EVENT LISTENER FILTER STATUS (BARU) ---
    if(filterStatus) { filterStatus.addEventListener('change', render); }

    fetchList();
})();