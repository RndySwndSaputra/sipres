(() => {
  const table = document.getElementById('presensiTable');
  const tbody = table ? table.querySelector('tbody') : null;
  const search = document.getElementById('presensiSearch');
  const btnExport = document.getElementById('btnExport');
  const emptyState = document.getElementById('emptyState');
  const tableWrapper = document.querySelector('.table-wrapper');
  
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

  // 1. Ambil Event ID dari URL (mendukung UUID maupun Integer)
  // Contoh URL: /admin/presensi/view/abc-123-uuid
  const pathMatch = window.location.pathname.match(/\/presensi\/view\/([a-zA-Z0-9-]+)/);
  const eventId = pathMatch ? decodeURIComponent(pathMatch[1]) : null;

  // Format Helpers
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

  // State Management untuk Pagination
  let presensiData = [];
  let keyword = '';
  let page = 1;
  let hasMore = true;
  let isLoading = false;
  const perPage = 20; // Load 20 baris per request agar ringan

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

    fetch(`/admin/presensi/stats/${eventId}`, { headers: { 'Accept': 'application/json' } })
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

  // --- Render Tabel ---
  const render = () => {
    // Filter lokal (opsional jika ingin search di data yg sudah terload)
    const filtered = presensiData.filter(p => {
      const q = keyword.toLowerCase();
      return (
        (p.nama || '').toLowerCase().includes(q) ||
        (p.nip || '').includes(q) ||
        (p.skpd || '').toLowerCase().includes(q)
      );
    });

    // Jika data kosong
    if (filtered.length === 0 && !isLoading) {
      if (page === 1) { // Benar-benar kosong
          tbody.innerHTML = '';
          tableWrapper.style.display = 'none';
          emptyState.hidden = false;
      }
      return;
    }

    tableWrapper.style.display = 'block';
    emptyState.hidden = true;

    // Render baris
    const html = filtered.map((p, idx) => {
      const statusInfo = getStatusInfo(p.status_kehadiran);
      // Hitung nomor urut absolut berdasarkan pagination jika mau (opsional), 
      // disini kita pakai index array saja biar simpel.
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
    
    // Tambahkan loading spinner di bawah jika masih loading
    if (isLoading) {
        const loadingRow = `
          <tr class="is-loading">
            <td colspan="6" class="text-center p-4">
              <div class="sk sk-sm mx-auto"></div> Loading data...
            </td>
          </tr>`;
        tbody.insertAdjacentHTML('beforeend', loadingRow);
    }
  };

  // --- Render Skeleton Awal ---
  const renderSkeleton = () => {
      if (tableWrapper) tableWrapper.style.display = 'none';
      if (emptyState) emptyState.hidden = true;
      if (skeletonTable) skeletonTable.hidden = false;
  };

  // --- Load Data Utama (dengan Pagination) ---
  const loadPresent = (reset = true) => {
    if (!eventId || isLoading) return;
    
    if (reset) {
        page = 1;
        presensiData = [];
        hasMore = true;
        renderSkeleton(); // Tampilkan skeleton full hanya saat load pertama
    }

    isLoading = true;
    if (!reset) render(); // Render ulang untuk menampilkan spinner loading bawah

    // Panggil endpoint yang sudah diperbaiki di Controller
    const url = `/admin/presensi/data/${eventId}?page=${page}&per_page=${perPage}`;
    
    fetch(url, { headers: { 'Accept': 'application/json' } })
      .then(res => res.json())
      .then(json => {
        if (json?.success) {
          const rows = json.data || [];
          
          if (reset) {
            presensiData = rows;
          } else {
            // Gabungkan data baru ke array lama
            presensiData = presensiData.concat(rows);
          }

          // Cek pagination dari Laravel
          const p = json.pagination || {};
          hasMore = !!p.has_more; // Convert to boolean
          
          if (hasMore) {
              page++; // Siapkan halaman berikutnya
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

  // --- Search Handler ---
  search.addEventListener('input', (e) => {
    keyword = (e.target.value || '').trim();
    // Jika search, idealnya request ke server (search server-side).
    // Tapi untuk simplifikasi, kita filter data yg sudah ada dulu (client-side).
    render(); 
  });

  // --- Export Handler ---
  btnExport.addEventListener('click', () => {
    if (!eventId) return;
    const url = `/admin/presensi/export-document/${eventId}?print=1`;
    window.open(url, '_blank');
  });

  // --- Infinite Scroll Listener ---
  window.addEventListener('scroll', () => {
    if (isLoading || !hasMore) return;
    
    // Cek apakah scroll sudah dekat bawah (sisa 200px)
    const nearBottom = (window.innerHeight + window.scrollY) >= (document.body.offsetHeight - 200);
    
    if (nearBottom) {
      loadPresent(false); // Load page berikutnya (false = jangan reset)
    }
  }, { passive: true });

  // --- Initial Load ---
  loadEventInfo();
  loadStats();
  loadPresent(true); // Reset load
})();