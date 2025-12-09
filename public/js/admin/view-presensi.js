(() => {
  const table = document.getElementById('presensiTable');
  const tbody = table ? table.querySelector('tbody') : null;
  const search = document.getElementById('presensiSearch');
  const btnExport = document.getElementById('btnExport');
  const emptyState = document.getElementById('emptyState');
  const tableWrapper = document.querySelector('.table-wrapper');
  
  // [TAMBAHAN] Element Date Filter
  const dateFilter = document.getElementById('dateFilterSelect');
  
  // Statistik Elements
  const statsCards = document.getElementById('statsCards');
  const skeletonStats = document.getElementById('vpSkeletonStats');
  const skeletonTable = document.getElementById('vpSkeletonTable');

  // Header Info Elements
  const eventTitle = document.getElementById('eventTitle');
  const eventInfo = document.getElementById('eventInfo');
  const statTotal = document.getElementById('statTotal');
  const statHadir = document.getElementById('statHadir');
  const statBelumHadir = document.getElementById('statBelumHadir');
  const statTidakHadir = document.getElementById('statTidakHadir');

  if (!table || !tbody || !search || !btnExport) return;

  // [PERBAIKAN] Ambil ID dari Variable Global (lebih aman) atau Fallback URL
  let eventId = (typeof INITIAL_ACARA_ID !== 'undefined') ? INITIAL_ACARA_ID : null;
  let selectedDate = (typeof INITIAL_DATE !== 'undefined') ? INITIAL_DATE : '';

  if (!eventId) {
      const pathMatch = window.location.pathname.match(/\/presensi\/view\/([^\/?#]+)/);
      if (pathMatch) eventId = pathMatch[1];
  }

  // Format Helpers (ASLI ANDA)
  const formatDateTime = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    return `${day}/${month}/${year} ${hours}:${minutes}`;
  };

  const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
  };

  // State Management
  let presensiData = [];
  let keyword = '';
  let page = 1;
  let hasMore = true;
  let isLoading = false;
  const perPage = 20;

  // --- Load Info Acara ---
  const loadEventInfo = () => {
    if (!eventId) return;
    fetch(`/admin/peserta/event/${eventId}`, { headers: { 'Accept': 'application/json' } })
      .then(res => res.json())
      .then(json => {
        if (json?.success && json.data) {
          const ev = json.data;
          eventTitle.textContent = `Daftar Kehadiran - ${ev.nama_acara}`;
          eventInfo.textContent = `${formatDate(ev.waktu_mulai)} • ${ev.lokasi || '-'}`;
        }
      })
      .catch(() => {});
  };

  // --- Load Statistik ---
  const loadStats = () => {
    if (!eventId) return;
    if (statsCards) statsCards.style.display = 'none';
    if (skeletonStats) skeletonStats.hidden = false;

    // [MODIFIKASI] Tambah ?date=...
    fetch(`/admin/presensi/stats/${eventId}?date=${selectedDate}`, { headers: { 'Accept': 'application/json' } })
      .then(res => res.json())
      .then(json => {
        if (json?.success && json.data) {
          const d = json.data;
          statTotal.textContent = d.total ?? 0;
          statHadir.textContent = d.hadir ?? 0;
          statBelumHadir.textContent = d.belum_hadir ?? 0;
          statTidakHadir.textContent = d.tidak_hadir ?? 0;
        }
        if (skeletonStats) skeletonStats.hidden = true;
        if (statsCards) statsCards.style.display = 'grid';
      })
      .catch(() => {});
  };

  const getStatusInfo = (status) => {
    const norm = String(status || '').toLowerCase();
    const statusMap = {
      'hadir': { label: 'Hadir', class: 'status-hadir' },
      '?': { label: 'Belum Hadir', class: 'status-belum-hadir' },
    };
    return statusMap[norm] || statusMap['?'];
  };

  // --- Render Tabel (ASLI ANDA) ---
  const render = () => {
    const filtered = presensiData.filter(p => {
      const q = keyword.toLowerCase();
      return (
        (p.nama || '').toLowerCase().includes(q) ||
        (p.nip || '').includes(q) ||
        (p.skpd || '').toLowerCase().includes(q)
      );
    });

    if (filtered.length === 0 && !isLoading) {
      if (page === 1) { 
          tbody.innerHTML = '';
          tableWrapper.style.display = 'none';
          emptyState.hidden = false;
      }
      return;
    }

    tableWrapper.style.display = 'block';
    emptyState.hidden = true;

    const html = filtered.map((p, idx) => {
      // [FIX] Mengembalikan layout asli Anda (badge-time)
      return `
        <tr>
          <td class="text-center">${idx + 1}</td>
          <td>${p.nama}</td>
          <td>${p.nip}</td>
          <td>${p.skpd}</td>
          <td><span class="badge-time">${p.jam_masuk}</span></td>
          <td><span class="badge-time">${p.jam_istirahat}</span></td>
          <td><span class="badge-time">${p.jam_pulang}</span></td>
        </tr>
      `;
    }).join('');

    tbody.innerHTML = html;
    
    if (isLoading) {
        const loadingRow = `
          <tr class="is-loading">
            <td colspan="7" class="text-center p-4">
              <div class="spinner-border text-primary" role="status">
                  <span class="visually-hidden">Loading...</span>
              </div>
            </td>
          </tr>`;
        tbody.insertAdjacentHTML('beforeend', loadingRow);
    }
  };

  // --- Render Skeleton Awal (ASLI ANDA) ---
  const renderSkeleton = () => {
      if (tableWrapper) tableWrapper.style.display = 'none';
      if (emptyState) emptyState.hidden = true;
      if (skeletonTable) skeletonTable.hidden = false;
  };

  // --- Load Data Utama (ASLI ANDA + FILTER DATE) ---
  const loadPresent = (reset = true) => {
    if (!eventId || isLoading) return;
    
    if (reset) {
        page = 1;
        presensiData = [];
        hasMore = true;
        renderSkeleton(); 
    }

    isLoading = true;
    if (!reset) render(); 

    // [MODIFIKASI] URL dengan parameter date
    const url = `/admin/presensi/data/${eventId}?page=${page}&per_page=${perPage}&date=${selectedDate}`;
    
    fetch(url, { headers: { 'Accept': 'application/json' } })
      .then(res => res.json())
      .then(json => {
        if (json?.success) {
          const rows = json.data || [];
          
          if (reset) {
            presensiData = rows;
          } else {
            presensiData = presensiData.concat(rows);
          }

          const p = json.pagination || {};
          hasMore = !!p.has_more; 
          
          if (hasMore) {
              page++; 
          }
        } else {
            hasMore = false;
        }
      })
      .catch(err => {
         console.error(err);
         hasMore = false;
      })
      .finally(() => {
        isLoading = false;
        if (skeletonTable) skeletonTable.hidden = true;
        render(); // Render hasil akhir
      });
  };

  // --- Listeners ---
  search.addEventListener('input', (e) => {
    keyword = (e.target.value || '').trim();
    render(); 
  });

  // [TAMBAHAN] Listener Dropdown Tanggal
  if(dateFilter) {
      dateFilter.addEventListener('change', (e) => {
          selectedDate = e.target.value;
          loadStats(); // Reload stats
          loadPresent(true); // Reload tabel
      });
  }

  btnExport.addEventListener('click', () => {
    if (!eventId) return;
    // Bisa tambah ?date= jika ingin export per tanggal
    const url = `/admin/presensi/export-document/${eventId}?print=1`;
    window.open(url, '_blank');
  });

  window.addEventListener('scroll', () => {
    if (isLoading || !hasMore) return;
    const nearBottom = (window.innerHeight + window.scrollY) >= (document.body.offsetHeight - 200);
    if (nearBottom) {
      loadPresent(false); 
    }
  }, { passive: true });

  // Init
  loadEventInfo();
  loadStats();
  loadPresent(true); 
})();