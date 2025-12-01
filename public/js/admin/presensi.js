(() => {
  const acaraGrid = document.getElementById('acaraGrid');
  const search = document.getElementById('presensiSearch');
  const filterStatus = document.getElementById('filterStatus'); // BARU
  const filterJenis = document.getElementById('filterJenis');
  const filterBulan = document.getElementById('filterBulan');
  const filterTahun = document.getElementById('filterTahun');
  const emptyState = document.getElementById('emptyState');
  const loadingOverlay = document.getElementById('presensiLoading');

  if (!acaraGrid || !search || !emptyState) return;

  // --- HELPER ZONA WAKTU WIB ---
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

  const normalizeToInput = (value) => {
    if (!value) return '';
    const m = String(value).match(/(\d{4}-\d{2}-\d{2})[T\s](\d{2}):(\d{2})/);
    return m ? `${m[1]}T${m[2]}:${m[3]}` : '';
  };
  
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

  const timeFromInput = (value) => {
    const norm = normalizeToInput(value);
    const m = norm.match(/T(\d{2}):(\d{2})$/);
    return m ? `${m[1]}:${m[2]}` : '';
  };

  const timeRange = (mulai, selesai) => {
    const s = timeFromInput(mulai);
    const e = timeFromInput(selesai);
    if (!s && !e) return '-';
    if (!s) return `- ${e} WIB`;
    if (!e) return `${s} WIB -`;
    return `${s} - ${e} WIB`;
  };

  const formatDate = (value) => dateFromInput(value);

  const getEventStatus = (mulai, selesai) => {
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

  let acara = [];

  const render = () => {
    const keyword = (search.value || '').toLowerCase().trim();
    const valStatus = filterStatus ? filterStatus.value : ''; // Value Status
    const valJenis = filterJenis ? filterJenis.value : '';
    const valBulan = filterBulan ? filterBulan.value : '';
    const valTahun = filterTahun ? filterTahun.value : '';

    const filtered = acara.filter(a => {
      // 1. Filter Search
      const matchKey = (a.nama_acara || '').toLowerCase().includes(keyword) ||
                       (a.lokasi || '').toLowerCase().includes(keyword);

      // 2. Filter Status (BARU)
      let matchStatus = true;
      if (valStatus) {
          const currentStatus = getEventStatus(a.waktu_mulai, a.waktu_selesai).label;
          if (currentStatus !== valStatus) matchStatus = false;
      }

      // 3. Filter Jenis
      let matchJenis = true;
      if (valJenis) {
         matchJenis = (a.mode_presensi === valJenis);
      }

      // 4. Filter Waktu (Bulan & Tahun)
      let matchDate = true;
      if (valBulan || valTahun) {
          const dateObj = new Date(a.waktu_mulai);
          if (valBulan && (dateObj.getMonth() + 1) != valBulan) matchDate = false;
          if (valTahun && dateObj.getFullYear() != valTahun) matchDate = false;
      }

      return matchKey && matchStatus && matchJenis && matchDate;
    });

    if (filtered.length === 0) {
      acaraGrid.innerHTML = '';
      emptyState.hidden = false;
      return;
    }

    emptyState.hidden = true;
    acaraGrid.innerHTML = filtered.map(a => {
      const status = getEventStatus(a.waktu_mulai, a.waktu_selesai);
      
      const isOnline = a.mode_presensi === 'Online';
      const isHybrid = a.mode_presensi === 'Kombinasi';

      // Data Lokasi & Link
      const lokasiStr = a.lokasi || '-';
      const linkUrl = a.link_meeting || '#';

      // --- ICON DEFINITION ---
      const iconLoc = `<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>`;
      const iconLink = `<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>`;

      // --- LOGIKA DISPLAY LOKASI / LINK ---
      let infoLokasiHtml = '';

      if (isOnline) {
          infoLokasiHtml = `
              <div class="info-item" title="Link Meeting">
                  ${iconLink} 
                  <a href="${linkUrl}" target="_blank" style="color:#2563eb; text-decoration:none; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${linkUrl}</a>
              </div>`;
      } else if (isHybrid) {
          infoLokasiHtml = `
              <div class="info-item" title="Lokasi">
                  ${iconLoc} 
                  <span>${lokasiStr}</span>
              </div>
              <div class="info-item" title="Link Meeting">
                  ${iconLink} 
                  <a href="${linkUrl}" target="_blank" style="color:#2563eb; text-decoration:none; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${linkUrl}</a>
              </div>`;
      } else {
          infoLokasiHtml = `
              <div class="info-item" title="Lokasi">
                  ${iconLoc} 
                  <span>${lokasiStr}</span>
              </div>`;
      }

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
            <button class="btn btn-primary btn-view" data-action="view" title="Lihat daftar presensi">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" aria-hidden="true">
                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5"/>
              </svg>
              <span>Lihat Peserta Yang Hadir</span>
            </button>
          </div>
        </div>
      `;
    }).join('');
  };

  // Event Listeners
  search.addEventListener('input', render);
  if(filterStatus) filterStatus.addEventListener('change', render); // Listener Status
  if(filterJenis) filterJenis.addEventListener('change', render);
  if(filterBulan) filterBulan.addEventListener('change', render);
  if(filterTahun) filterTahun.addEventListener('input', render);

  acaraGrid.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-action="view"]');
    if (!btn) return;
    
    const card = e.target.closest('.acara-card');
    const id = card.getAttribute('data-id');
    const event = acara.find(a => String(a.id_acara) === String(id));
    
    if (event) {
      window.location.href = `/admin/presensi/view/${id}`;
    }
  });

  if (loadingOverlay) loadingOverlay.hidden = false;
  fetch('/admin/acara/data', { headers: { 'Accept': 'application/json' } })
    .then(res => res.json())
    .then(json => {
      if (json?.success) {
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