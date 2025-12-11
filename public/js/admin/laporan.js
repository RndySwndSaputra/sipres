(() => {
    // --- Elements ---
    const tableBody = document.getElementById('laporanTableBody');
    const printTableBody = document.getElementById('printTableBody');
    const tableContainer = document.getElementById('tableContainer');
    const search = document.getElementById('laporanSearch');
    const filterJenis = document.getElementById('filterJenis');
    const filterStartDate = document.getElementById('filterStartDate');
    const filterEndDate = document.getElementById('filterEndDate');
    const btnPrint = document.getElementById('btnPrint');
    const emptyState = document.getElementById('emptyState');
    const loadingOverlay = document.getElementById('laporanLoading');
    const printFilterInfo = document.getElementById('printFilterInfo');

    if (!tableBody || !search) return;

    // VARIABLE GLOBAL LOKAL UNTUK MENYIMPAN HASIL FILTER SAAT INI
    let currentFilteredData = []; 

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

    const getStatus = (start, end) => {
        try {
            const nowWIB = getNowWIB();
            const startStr = normalizeToInput(start);
            const endStr = normalizeToInput(end);
            if (!endStr) return { label: 'Akan Datang', class: 'badge-blue' };
            if (nowWIB < startStr) return { label: 'Akan Datang', class: 'badge-blue' };
            if (nowWIB > endStr) return { label: 'Selesai', class: 'badge-gray' };
            return { label: 'Berlangsung', class: 'badge-green' };
        } catch {
            return { label: 'Akan Datang', class: 'badge-blue' };
        }
    };

    // --- Helpers Format Tampilan ---
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

    const formatDateRange = (start, end) => {
        const s = dateFromInput(start);
        const e = dateFromInput(end);
        if (s === '-' || e === '-') return s;
        if (s === e) return s;
        return `${s} - ${e}`; 
    };

    const timeFromInput = (value) => {
        const norm = normalizeToInput(value);
        const m = norm.match(/T(\d{2}):(\d{2})$/);
        return m ? `${m[1]}:${m[2]}` : '';
    };

    const formatTimeRange = (start, end) => {
        const s = timeFromInput(start);
        const e = timeFromInput(end);
        if (!s && !e) return '-';
        return `${s} - ${e} WIB`;
    };

    let acaraData = [];

    // --- RENDER LOGIC ---
    const render = () => {
        const keyword = (search.value || '').toLowerCase().trim();
        const vJenis = filterJenis.value;
        const vStart = filterStartDate.value;
        const vEnd = filterEndDate.value;

        // Filtering Logic
        const filtered = acaraData.filter(a => {
            const matchKey = (a.nama_acara || '').toLowerCase().includes(keyword) || 
                             (a.lokasi || '').toLowerCase().includes(keyword);
            
            let matchJenis = true;
            if (vJenis) matchJenis = a.mode_presensi === vJenis;

            let matchDate = true;
            if (vStart || vEnd) {
                const eventStartFull = normalizeToInput(a.waktu_mulai);
                if (eventStartFull) {
                    const eventDateStr = eventStartFull.split('T')[0];
                    if (vStart && eventDateStr < vStart) matchDate = false;
                    if (vEnd && eventDateStr > vEnd) matchDate = false;
                }
            }
            return matchKey && matchJenis && matchDate;
        });

        // SIMPAN DATA TERFILTER KE VARIABLE GLOBAL LOKAL
        currentFilteredData = filtered;

        // Toggle View State
        if (filtered.length === 0) {
            tableContainer.style.display = 'none';
            emptyState.hidden = false;
            // Kita juga kosongkan tabel print agar bersih jika user memaksa print via browser
            if(printTableBody) printTableBody.innerHTML = '';
            return;
        }
        
        tableContainer.style.display = 'block';
        emptyState.hidden = true;

        // 1. Render Web Table
        tableBody.innerHTML = filtered.map((a, index) => {
            const status = getStatus(a.waktu_mulai, a.waktu_selesai);
            
            let jenisBadge = '';
            if (a.mode_presensi === 'Online') jenisBadge = '<span class="jenis-badge jenis-online">Online</span>';
            else if (a.mode_presensi === 'Kombinasi') jenisBadge = '<span class="jenis-badge jenis-hybrid">Hybrid</span>';
            else jenisBadge = '<span class="jenis-badge jenis-offline">Offline</span>';

            let linkElement = '';
            if ((a.mode_presensi === 'Online' || a.mode_presensi === 'Kombinasi')) {
                if (a.link_meeting) {
                    linkElement = `
                        <a href="${a.link_meeting}" target="_blank" class="link-meeting-btn" title="${a.link_meeting}">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                            Join Meeting
                        </a>`;
                } else {
                    linkElement = `<span class="text-muted text-xs" style="font-style:italic;">(Link belum tersedia)</span>`;
                }
            }

            const lokasiText = (a.lokasi && a.mode_presensi !== 'Online') 
                ? `<div class="lokasi-text">
                     <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg> 
                     ${a.lokasi}
                   </div>` 
                : '';

            return `
                <tr>
                    <td class="text-center text-muted">${index + 1}</td>
                    <td>
                        <div style="display:flex; align-items:flex-start; gap:8px;">
                            <div>
                                <span class="nama-acara">${a.nama_acara}</span>
                                <span class="status-badge ${status.class}">${status.label}</span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="time-col">
                            <span class="tgl">${formatDateRange(a.waktu_mulai, a.waktu_selesai)}</span>
                            <span class="jam">${formatTimeRange(a.waktu_mulai, a.waktu_selesai)}</span>
                        </div>
                    </td>
                    <td>
                        <div class="jenis-col">
                            ${jenisBadge}
                            ${lokasiText}
                            ${linkElement}
                        </div>
                    </td>
                    <td class="text-center">
                        <div class="peserta-wrapper">
                            <span class="peserta-count" title="Maksimal Peserta">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                ${a.maximal_peserta || 0}
                            </span>
                        </div>
                    </td>
                    <td class="text-center">
                        <a href="/admin/laporan/view/${a.id_acara}" class="btn-detail">
                            Lihat Detail
                        </a>
                    </td>
                </tr>
            `;
        }).join('');

        // 2. Render Print Table (Disiapkan untuk cetak)
        if (printTableBody) {
            printTableBody.innerHTML = filtered.map((a, idx) => {
                let info = a.mode_presensi === 'Offline' ? a.lokasi : `Link: ${a.link_meeting || '-'}`;
                if (a.mode_presensi === 'Kombinasi') info = `${a.lokasi} / ${a.link_meeting}`;
                
                return `
                    <tr>
                        <td style="text-align:center">${idx + 1}</td>
                        <td>${a.nama_acara}</td>
                        <td>${formatDateRange(a.waktu_mulai, a.waktu_selesai)}<br><small>${formatTimeRange(a.waktu_mulai, a.waktu_selesai)}</small></td>
                        <td>${a.mode_presensi}<br><small>${info}</small></td>
                        <td style="text-align:center">${a.maximal_peserta || 0}</td>
                    </tr>`;
            }).join('');
        }

        // Update Info Header Cetak
        if (printFilterInfo) {
            let periodText = 'Semua Waktu';
            if (vStart && vEnd) periodText = `${dateFromInput(vStart)} s/d ${dateFromInput(vEnd)}`;
            else if (vStart) periodText = `Dari ${dateFromInput(vStart)}`;
            else if (vEnd) periodText = `Sampai ${dateFromInput(vEnd)}`;
            
            printFilterInfo.textContent = `Periode: ${periodText} | Jenis: ${vJenis || 'Semua'}`;
        }
    };

    // --- Fetch ---
    const fetchData = async () => {
        loadingOverlay.hidden = false;
        try {
            const res = await fetch('/admin/acara/data', { headers: { 'Accept': 'application/json' } });
            const json = await res.json();
            acaraData = (json && json.success && Array.isArray(json.data)) ? json.data : [];
            render();
        } catch (e) {
            console.error('Error fetching data:', e);
            acaraData = [];
            render();
        } finally {
            loadingOverlay.hidden = true;
        }
    };

    search.addEventListener('input', render);
    filterJenis.addEventListener('change', render);
    filterStartDate.addEventListener('change', render);
    filterEndDate.addEventListener('change', render);

    // --- VALIDASI SAAT KLIK CETAK (PERBAIKAN LOGIC) ---
    if (btnPrint) {
        btnPrint.addEventListener('click', () => {
            // Cek apakah ada data yang sedang ditampilkan
            if (currentFilteredData.length === 0) {
                // Tampilkan pesan error (bisa diganti SweetAlert jika pakai)
                alert("Tidak ada data acara pada periode atau filter yang dipilih.\nSilakan ubah filter tanggal untuk menampilkan data sebelum mencetak.");
                return; // Batalkan proses cetak
            }
            // Jika ada data, jalankan print
            window.print();
        });
    }

    fetchData();
})();