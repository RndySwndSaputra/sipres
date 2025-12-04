(() => {
    // --- ELEMENTS ---
    const tableBody = document.getElementById('laporanBody');
    const searchInput = document.getElementById('laporanSearch');
    const btnExport = document.getElementById('btnExport');
    const emptyState = document.getElementById('emptyState');
    const tableWrapper = document.querySelector('.table-wrapper');
    const dateFilterSelect = document.getElementById('dateFilterSelect');
    
    // Stats & Skeleton
    const statsCards = document.getElementById('statsCards');
    const skeletonStats = document.getElementById('vpSkeletonStats');
    const skeletonTable = document.getElementById('vpSkeletonTable');
    
    // Header Info
    const eventTitle = document.getElementById('eventTitle');
    const eventInfo = document.getElementById('eventInfo');
    const statTotal = document.getElementById('statTotal');
    const statHadir = document.getElementById('statHadir');
    const statBelum = document.getElementById('statBelumHadir');
    const statTidak = document.getElementById('statTidakHadir');

    // --- STATE ---
    let currentPage = 1;
    let hasMore = true;
    let isLoading = false;
    let currentKeyword = '';
    let selectedDate = ''; 

    if (typeof ACARA_ID === 'undefined' || !ACARA_ID) return;

    // --- HELPERS ---
    const formatDateIndo = (dateStr) => {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
    };

    // --- 1. LOAD INFO ACARA ---
    const initPage = async () => {
        try {
            const res = await fetch(`/admin/laporan/event-info/${ACARA_ID}`);
            const json = await res.json();
            
            if (json.success) {
                const data = json.data;
                eventTitle.textContent = `Laporan: ${data.nama_acara}`;
                eventInfo.textContent = data.lokasi || 'Lokasi tidak diset';

                const dates = data.dates || [];
                const today = data.today;

                if (dates.length > 0) {
                    selectedDate = dates.includes(today) ? today : dates[0];
                    if (dates.length > 1) {
                        dateFilterSelect.innerHTML = dates.map(d => 
                            `<option value="${d}" ${d === selectedDate ? 'selected' : ''}>${formatDateIndo(d)}</option>`
                        ).join('');
                        dateFilterSelect.style.display = 'block'; 
                    } else {
                        selectedDate = dates[0];
                        dateFilterSelect.style.display = 'none';
                    }
                }
                loadStats();
                loadData(true);
            }
        } catch (e) { console.error(e); }
    };

    // --- 2. LOAD STATS ---
    const loadStats = async () => {
        if (statsCards) statsCards.style.display = 'none';
        if (skeletonStats) skeletonStats.hidden = false;

        try {
            const res = await fetch(`/admin/laporan/stats/${ACARA_ID}?date=${selectedDate}`);
            const json = await res.json();
            if(json.success) {
                const s = json.data;
                statTotal.textContent = s.total;
                statHadir.textContent = s.hadir;
                statBelum.textContent = s.belum_hadir;
                statTidak.textContent = s.tidak_hadir;
            }
        } catch(e) {} finally {
            if (skeletonStats) skeletonStats.hidden = true;
            if (statsCards) statsCards.style.display = 'grid'; 
        }
    };

    // --- 3. LOAD TABLE DATA ---
    const loadData = async (reset = false) => {
        if (isLoading) return;
        if (reset) {
            currentPage = 1;
            hasMore = true;
            tableBody.innerHTML = '';
            if (skeletonTable) skeletonTable.hidden = false;
            tableWrapper.style.display = 'none';
            emptyState.hidden = true;
        }

        isLoading = true;

        try {
            const url = `/admin/laporan/data/${ACARA_ID}?page=${currentPage}&per_page=20&date=${selectedDate}&q=${currentKeyword}`;
            const res = await fetch(url);
            const json = await res.json();

            if (json.success) {
                const rows = json.data;
                const pagination = json.pagination;

                if (reset && rows.length === 0) {
                    emptyState.hidden = false;
                    tableWrapper.style.display = 'none';
                } else {
                    emptyState.hidden = true;
                    tableWrapper.style.display = 'block';
                    
                    rows.forEach((p, index) => {
                        const globalIndex = (pagination.current_page - 1) * 20 + (index + 1);
                        
                        const renderBadge = (statusData) => {
                            return `<span class="status-badge ${statusData.class}">${statusData.text}</span>`;
                        };

                        const html = `
                            <tr>
                                <td class="text-center">${globalIndex}</td>
                                <td><span class="nama-text">${p.nama}</span></td>
                                <td class="cell-nip">${p.nip}</td>
                                <td>${p.skpd}</td>
                                <td>${renderBadge(p.status_masuk)}</td>
                                <td>${renderBadge(p.status_istirahat)}</td>
                                <td>${renderBadge(p.status_pulang)}</td>
                            </tr>
                        `;
                        tableBody.insertAdjacentHTML('beforeend', html);
                    });

                    hasMore = pagination.has_more;
                    if (hasMore) currentPage++;
                }
            }
        } catch (e) { console.error(e); } finally {
            isLoading = false;
            if (reset && skeletonTable) skeletonTable.hidden = true;
        }
    };

    // --- EVENTS ---
    if (dateFilterSelect) {
        dateFilterSelect.addEventListener('change', (e) => {
            selectedDate = e.target.value;
            loadStats(); 
            loadData(true);
        });
    }

    let timeout = null;
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            clearTimeout(timeout);
            currentKeyword = e.target.value;
            timeout = setTimeout(() => { loadData(true); }, 500);
        });
    }

    window.addEventListener('scroll', () => {
        if (isLoading || !hasMore) return;
        if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 200) {
            loadData(false);
        }
    });

    // --- EXPORT DIRECT EXCEL (FIXED PATH) ---
    if (btnExport) {
        btnExport.addEventListener('click', () => {
            const url = `/admin/laporan/export/excel/${ACARA_ID}?date=${selectedDate}`;
            window.location.href = url;
        });
    }

    initPage();
})();