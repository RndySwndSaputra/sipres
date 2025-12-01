(() => {
  // --- Deklarasi Elemen DOM ---
  const acaraGrid = document.getElementById('acaraGrid');
  const printTableBody = document.getElementById('printTableBody'); // Body tabel print
  const search = document.getElementById('laporanSearch');
  const filterJenis = document.getElementById('filterJenis');
  const filterBulan = document.getElementById('filterBulan');
  const filterTahun = document.getElementById('filterTahun');
  const btnPrint = document.getElementById('btnPrint');
  const emptyState = document.getElementById('emptyState');
  const loadingOverlay = document.getElementById('laporanLoading');

  // Cek apakah elemen penting ada, jika tidak stop
  if (!acaraGrid || !search || !emptyState) return;

  // --- HELPER FUNCTION: Formatting & Waktu ---

  // 1. Ambil Waktu WIB Saat Ini
  const getNowWIB = () => {
    const now = new Date();
    const parts = new Intl.DateTimeFormat('en-CA', {
      timeZone: 'Asia/Jakarta',
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
      hour12: false
    }).formatToParts(now);

    const part = (type) => parts.find(p => p.type === type).value;
    return `${part('year')}-${part('month')}-${part('day')}T${part('hour')}:${part('minute')}`;
  };

  // 2. Normalisasi Input Tanggal
  const normalizeToInput = (value) => {
    if (!value) return '';
    const m = String(value).match(/(\d{4}-\d{2}-\d{2})[T\s](\d{2}):(\d{2})/);
    return m ? `${m[1]}T${m[2]}:${m[3]}` : '';
  };

  // 3. Format Tanggal (Contoh: 29 Nov 2025)
  const dateFromInput = (value) => {
    const norm = normalizeToInput(value);
    if (!norm) return '-';
    const m = norm.match(/^(\d{4})-(\d{2})-(\d{2})T/);
    if (!m) return '-';
    const year = Number(m[1]);
    const monthIdx = Number(m[2]) - 1;
    const day = Number(m[3]);
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return `${day} ${months[Math.max(0, Math.min(11, monthIdx))]} ${year}`;
  };

  // 4. Format Jam (Contoh: 09:00)
  const timeFromInput = (value) => {
    const norm = normalizeToInput(value);
    const m = norm.match(/T(\d{2}):(\d{2})$/);
    return m ? `${m[1]}:${m[2]}` : '';
  };

  // 5. Gabungan Jam Mulai - Selesai
  const timeRange = (mulai, selesai) => {
    const s = timeFromInput(mulai);
    const e = timeFromInput(selesai);
    if (!s && !e) return '-';
    return `${s} - ${e} WIB`;
  };

  const formatDate = (value) => dateFromInput(value);

  // 6. Tentukan Status Acara (Berlangsung/Selesai/Akan Datang)
  const getEventStatus = (mulai, selesai) => {
    try {
      const nowWIB = getNowWIB();
      const startStr = normalizeToInput(mulai);
      const endStr = normalizeToInput(selesai);

      if (nowWIB < startStr) return {
        label: 'Akan Datang',
        class: 'status-upcoming'
      };
      if (nowWIB > endStr) return {
        label: 'Selesai',
        class: 'status-completed'
      };
      return {
        label: 'Berlangsung',
        class: 'status-ongoing'
      };
    } catch {
      return {
        label: 'Akan Datang',
        class: 'status-upcoming'
      };
    }
  };

  let acara = [];

  // --- LOGIKA RENDER UTAMA ---
  const render = () => {
    // Ambil nilai dari input filter
    const keyword = (search.value || '').toLowerCase().trim();
    const valJenis = filterJenis ? filterJenis.value : '';
    const valBulan = filterBulan ? filterBulan.value : '';
    const valTahun = filterTahun ? filterTahun.value : '';

    // Lakukan Filtering Data
    const filtered = acara.filter(a => {
      // 1. Filter Keyword (Nama & Lokasi)
      const matchKey = (a.nama_acara || '').toLowerCase().includes(keyword) ||
        (a.lokasi || '').toLowerCase().includes(keyword);

      // 2. Filter Jenis
      let matchJenis = true;
      if (valJenis) {
        matchJenis = (a.mode_presensi === valJenis);
      }

      // 3. Filter Waktu (Bulan & Tahun)
      let matchDate = true;
      if (valBulan || valTahun) {
        const dateObj = new Date(a.waktu_mulai);
        if (valBulan && (dateObj.getMonth() + 1) != valBulan) matchDate = false;
        if (valTahun && dateObj.getFullYear() != valTahun) matchDate = false;
      }

      return matchKey && matchJenis && matchDate;
    });

    // --- Handling jika data kosong ---
    if (filtered.length === 0) {
      acaraGrid.innerHTML = '';
      if (printTableBody) {
        printTableBody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding: 20px;">Tidak ada data ditemukan</td></tr>';
      }
      emptyState.hidden = false;
      return;
    }

    emptyState.hidden = true;

    // --- RENDER 1: GRID VIEW (Tampilan Web) ---
    acaraGrid.innerHTML = filtered.map(a => {
      const status = getEventStatus(a.waktu_mulai, a.waktu_selesai);
      const isOnline = a.mode_presensi === 'Online';
      const isHybrid = a.mode_presensi === 'Kombinasi';

      // SVG Icon Assets
      const iconLoc = `<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>`;
      const iconLink = `<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>`;

      // Tentukan HTML Lokasi/Link
      let infoLokasiHtml = '';
      // PERBAIKAN: Mengganti teks 'Link Meeting' dengan variabel url (a.link_meeting)
      if (isOnline) {
        infoLokasiHtml = `
            <div class="info-item" title="Link Meeting">
                ${iconLink} 
                <a href="${a.link_meeting || '#'}" target="_blank" style="color:#2563eb; text-decoration:none; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                    ${a.link_meeting || '#'}
                </a>
            </div>`;
      } else if (isHybrid) {
        infoLokasiHtml = `
            <div class="info-item" title="Lokasi">
                ${iconLoc} 
                <span>${a.lokasi || '-'}</span>
            </div>
            <div class="info-item" title="Link Meeting">
                ${iconLink} 
                <a href="${a.link_meeting || '#'}" target="_blank" style="color:#2563eb; text-decoration:none; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                    ${a.link_meeting || '#'}
                </a>
            </div>`;
      } else {
        infoLokasiHtml = `
            <div class="info-item" title="Lokasi">
                ${iconLoc} 
                <span>${a.lokasi || '-'}</span>
            </div>`;
      }

      // Return String HTML Kartu
      return `
        <div class="acara-card" data-id="${a.id_acara}">
          <div class="card-header">
            <div style="display:flex; gap:8px; align-items:center; width:100%; justify-content:space-between;">
                <div class="card-status ${status.class}">${status.label}</div>
                <div style="display:flex; gap:4px;">
                    ${isOnline ? '<span class="badge badge-blue">ONLINE</span>' : ''}
                    ${isHybrid ? '<span class="badge badge-green">HYBRID</span>' : ''}
                </div>
            </div>
          </div>
          <div class="card-body">
            <h3 class="card-title">${a.nama_acara}</h3>
            <div class="card-info">
              <div class="info-item">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" aria-hidden="true">
                  <rect x="3" y="4" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.5"/>
                  <path d="M16 2v4M8 2v4M3 10h18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                <span>${formatDate(a.waktu_mulai)} - ${formatDate(a.waktu_selesai)}</span>
              </div>
              
              <div class="info-item">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" aria-hidden="true">
                  <path d="M12 2v8l4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                  <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>
                </svg>
                <span>${timeRange(a.waktu_mulai, a.waktu_selesai)}</span>
              </div>

              ${infoLokasiHtml}

              <div class="info-item">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" aria-hidden="true">
                    <path d="M17 21v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    <circle cx="10" cy="8" r="4" stroke="currentColor" stroke-width="1.5"/>
                    <path d="M20 21v-1a4 4 0 0 0-3-3.8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    <path d="M17 3a4 4 0 0 1 0 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                <span>${a.maximal_peserta ?? 0} Peserta</span>
              </div>
            </div>
          </div>
          <div class="card-footer">
            <button class="btn btn-primary btn-view" data-action="view" title="Lihat Laporan Acara">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" aria-hidden="true">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z" stroke="currentColor" stroke-width="1.5"/>
                <path d="M14 2v6h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M16 13H8M16 17H8M10 9H8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
              </svg>
              <span>Lihat Laporan</span>
            </button>
          </div>
        </div>
      `;
    }).join('');

    // --- RENDER 2: TABLE VIEW (Tampilan Print) ---
    // Kita isi tabel tersembunyi dengan data yang sama
    if (printTableBody) {
      // Set Tanggal Cetak
      const printDateEl = document.getElementById('printDate');
      if (printDateEl) {
        const now = new Date();
        printDateEl.innerText = now.toLocaleDateString('id-ID', {
          day: 'numeric',
          month: 'long',
          year: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        });
      }

      printTableBody.innerHTML = filtered.map((a, index) => {
        const status = getEventStatus(a.waktu_mulai, a.waktu_selesai);
        const mode = a.mode_presensi || '-';

        // --- LOGIKA TAMPILAN LOKASI & LINK ---
        let lokasiContent = '-';
        const linkUrl = a.link_meeting ? a.link_meeting : '';
        const lokasiFisik = a.lokasi ? a.lokasi : '';

        if (a.mode_presensi === 'Online') {
          // Jika Online: Tampilkan Link saja
          lokasiContent = `
                <span class="print-label">Online Meeting</span>
                ${linkUrl ? `<a href="${linkUrl}" class="print-link">${linkUrl}</a>` : '<span class="text-small">-</span>'}
            `;
        } else if (a.mode_presensi === 'Kombinasi') {
          // Jika Hybrid: Tampilkan Lokasi DAN Link
          lokasiContent = `
                <span class="print-label">📍 ${lokasiFisik}</span>
                ${linkUrl ? `<div style="margin-top:6px; border-top:1px dashed #ccc; padding-top:4px;"><span class="print-label">🔗 Link:</span><br><a href="${linkUrl}" class="print-link">${linkUrl}</a></div>` : ''}
            `;
        } else {
          // Jika Offline: Tampilkan Lokasi saja
          lokasiContent = `<span class="print-label">📍 ${lokasiFisik}</span>`;
        }

        // Status Text Color (Opsional, biar rapi hitam saja saat print)
        const statusText = status.label;

        return `
            <tr>
                <td class="col-center">${index + 1}</td>
                <td>
                    <strong>${a.nama_acara}</strong><br>
                    <span class="text-small">Status: ${statusText}</span>
                </td>
                <td class="col-center">${mode}</td>
                <td>
                    ${formatDate(a.waktu_mulai)}<br>
                    <span class="text-small">${timeRange(a.waktu_mulai, a.waktu_selesai)}</span>
                </td>
                <td>
                    ${lokasiContent}
                </td>
                <td class="col-center">${a.maximal_peserta || 0}</td>
            </tr>
        `;
      }).join('');
    }
  };

  // --- Event Listeners ---
  search.addEventListener('input', render);
  if (filterJenis) filterJenis.addEventListener('change', render);
  if (filterBulan) filterBulan.addEventListener('change', render);
  if (filterTahun) filterTahun.addEventListener('input', render);

  // Tombol Print
  if (btnPrint) {
    btnPrint.addEventListener('click', () => {
      window.print();
    });
  }

  // Klik Kartu untuk ke Detail
  acaraGrid.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-action="view"]');
    if (!btn) return;

    const card = e.target.closest('.acara-card');
    const id = card.getAttribute('data-id');
    const event = acara.find(a => String(a.id_acara) === String(id));

    if (event) {
      window.location.href = `/admin/laporan/view/${id}`;
    }
  });

  // --- Initial Load Data ---
  if (loadingOverlay) loadingOverlay.hidden = false;
  fetch('/admin/acara/data', {
      headers: {
        'Accept': 'application/json'
      }
    })
    .then(res => res.json())
    .then(json => {
      if (json && json.success) {
        acara = json.data || [];
      } else {
        acara = [];
      }
      render();
      if (loadingOverlay) loadingOverlay.hidden = true;
    })
    .catch(() => {
      acara = [];
      render();
      if (loadingOverlay) loadingOverlay.hidden = true;
    });
})();