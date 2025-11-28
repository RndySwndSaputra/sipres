(() => {
    // --- Elemen DOM ---
    const acaraId = window.ACARA_ID; 
    const tableHeader = document.getElementById('laporanHeader');
    const tableBody = document.getElementById('laporanBody');
    const loadingOverlay = document.getElementById('laporanLoading');
    const emptyState = document.getElementById('emptyState');
    const emptyHint = document.getElementById('emptyHint');
    const btnDownload = document.getElementById('btnDownload');
    const container = document.getElementById('laporanContainer');
    
    // Elemen BARU untuk Stats
    const searchInput = document.getElementById('laporanSearch');
    const statTotalPeserta = document.getElementById('statTotalPeserta');
    const statTotalHadir = document.getElementById('statTotalHadir');
    const statTotalBelumHadir = document.getElementById('statTotalBelumHadir');

    if (!acaraId || !tableHeader || !tableBody || !loadingOverlay || !container || !emptyState || !searchInput) {
        console.error('Elemen penting di halaman laporan tidak ditemukan.');
        return;
    }

    // --- Variabel State ---
    let allParticipants = [];
    let allDates = [];
    let currentKeyword = '';

    // --- Helper ---
    const formatShortDate = (dateString) => {
        try {
            const [year, month, day] = dateString.split('-');
            const d = new Date(year, month - 1, day);
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            return `${d.getDate()} ${months[d.getMonth()]} ${year}`;
        } catch {
            return dateString;
        }
    };

    // --- Fungsi Render ---
    const renderHeader = () => {
        let headerHtml = '<tr>';
        headerHtml += '<th class="col-nama">Nama Peserta</th>';
        headerHtml += '<th class="col-nip">NIP/ID</th>';

        if (allDates.length === 0) {
            headerHtml += '<th>Tidak ada data tanggal</th>';
        }

        allDates.forEach(date => {
            headerHtml += `<th class="col-tanggal">${formatShortDate(date)}</th>`;
        });
        headerHtml += '</tr>';
        tableHeader.innerHTML = headerHtml;
    };

    const renderBody = () => {
        // Filter peserta berdasarkan keyword
        const keyword = currentKeyword.toLowerCase();
        const filteredParticipants = allParticipants.filter(p => {
            return (p.nama || '').toLowerCase().includes(keyword) || 
                   (p.nip || '').toLowerCase().includes(keyword);
        });

        if (allParticipants.length === 0) {
            emptyHint.textContent = 'Belum ada data presensi untuk acara ini.';
            emptyState.hidden = false;
            container.hidden = true;
            return;
        }

        if (filteredParticipants.length === 0) {
            emptyHint.textContent = 'Tidak ada peserta yang cocok dengan pencarian Anda.';
            emptyState.hidden = false;
            container.hidden = true;
            return;
        }

        // Render baris tabel
        let bodyHtml = '';
        filteredParticipants.forEach(p => {
            bodyHtml += '<tr>';
            bodyHtml += `<td data-label="Nama">${p.nama || '-'}</td>`;
            bodyHtml += `<td data-label="NIP">${p.nip || '-'}</td>`;

            allDates.forEach(date => {
                const dataHarian = p.attendance[date];
                
                if (dataHarian && dataHarian.status === 'Hadir') {
                    bodyHtml += `<td data-label="${date}" class="status-hadir-cell"><strong>Hadir</strong><br><span class="time-stamp">${dataHarian.timestamp}</span></td>`;
                } else {
                    bodyHtml += `<td data-label="${date}" class="status-alpha-cell">Alpha</td>`;
                }
            });

            bodyHtml += '</tr>';
        });

        tableBody.innerHTML = bodyHtml;
        container.hidden = false;
        emptyState.hidden = true;
    };

    // --- Fungsi Fetch Data ---
    const fetchLaporan = async () => {
        try {
            loadingOverlay.hidden = false;
            container.hidden = true;
            emptyState.hidden = true;

            const response = await fetch(`/admin/laporan/data/${acaraId}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (!response.ok) throw new Error('Gagal mengambil data laporan dari server.');

            const json = await response.json();

            if (json.success && json.report) {
                const { dates, participants } = json.report;
                
                allDates = dates || [];
                allParticipants = participants || [];
                
                // --- [PERUBAHAN] Kalkulasi Statistik ---
                let hadirCount = 0;
                let belumHadirCount = 0;

                allParticipants.forEach(p => {
                    let hasAttendedOnce = false;
                    for (const date of allDates) {
                        const attendance = p.attendance[date];
                        if (attendance && attendance.status === 'Hadir') {
                            hasAttendedOnce = true;
                            break; // Hemat resource, stop looping jika sudah ketemu hadir 1x
                        }
                    }

                    if (hasAttendedOnce) {
                        hadirCount++;
                    } else {
                        belumHadirCount++;
                    }
                });

                // Perbarui Stats Cards
                if (statTotalPeserta) statTotalPeserta.textContent = allParticipants.length;
                if (statTotalHadir) statTotalHadir.textContent = hadirCount;
                if (statTotalBelumHadir) statTotalBelumHadir.textContent = belumHadirCount;
                // --- [AKHIR PERUBAHAN] ---

                // Render tabel
                renderHeader();
                renderBody();

            } else {
                throw new Error(json.message || 'Data laporan tidak valid.');
            }

        } catch (error) {
            console.error(error);
            emptyState.hidden = false;
            container.hidden = true;
        } finally {
            loadingOverlay.hidden = true;
        }
    };

    // --- Event Listeners ---

    // Search
    searchInput.addEventListener('input', (e) => {
        currentKeyword = e.target.value || '';
        renderBody(); // Hanya me-render ulang body tabel
    });

    // Download
    if (btnDownload) {
        btnDownload.addEventListener('click', async () => {
            btnDownload.disabled = true;
            btnDownload.querySelector('span').textContent = 'Memproses...';

            try {
                const response = await fetch(`/admin/laporan/export/${acaraId}`);
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message || 'Ekspor berhasil disiapkan.');
                } else {
                    alert('Gagal mengekspor laporan: ' + data.message);
                }

            } catch (error) {
                alert('Terjadi kesalahan saat mengekspor.');
            } finally {
                btnDownload.disabled = false;
                btnDownload.querySelector('span').textContent = 'Unduh Laporan';
            }
        });
    }

    // --- Inisialisasi ---
    fetchLaporan();

})();(() => {
    // --- Elemen DOM ---
    const acaraId = window.ACARA_ID; 
    const tableHeader = document.getElementById('laporanHeader');
    const tableBody = document.getElementById('laporanBody');
    const loadingOverlay = document.getElementById('laporanLoading');
    const emptyState = document.getElementById('emptyState');
    const emptyHint = document.getElementById('emptyHint');
    const btnDownload = document.getElementById('btnDownload');
    const container = document.getElementById('laporanContainer');
    
    // Elemen BARU untuk Stats
    const searchInput = document.getElementById('laporanSearch');
    const statTotalPeserta = document.getElementById('statTotalPeserta');
    const statTotalHari = document.getElementById('statTotalHari');
    const statTotalSKPD = document.getElementById('statTotalSKPD'); // <-- Diubah

    if (!acaraId || !tableHeader || !tableBody || !loadingOverlay || !container || !emptyState || !searchInput) {
        console.error('Elemen penting di halaman laporan tidak ditemukan.');
        return;
    }

    // --- Variabel State ---
    let allParticipants = [];
    let allDates = [];
    let currentKeyword = '';

    // --- Helper ---
    const formatShortDate = (dateString) => {
        try {
            const [year, month, day] = dateString.split('-');
            const d = new Date(year, month - 1, day);
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            return `${d.getDate()} ${months[d.getMonth()]} ${year}`;
        } catch {
            return dateString;
        }
    };

    // --- Fungsi Render ---
    const renderHeader = () => {
        let headerHtml = '<tr>';
        headerHtml += '<th class="col-nama">Nama Peserta</th>';
        headerHtml += '<th class="col-nip">NIP/ID</th>';
        if (allDates.length === 0) {
            headerHtml += '<th>Tidak ada data tanggal</th>';
        }
        allDates.forEach(date => {
            headerHtml += `<th class="col-tanggal">${formatShortDate(date)}</th>`;
        });
        headerHtml += '</tr>';
        tableHeader.innerHTML = headerHtml;
    };

    const renderBody = () => {
        const keyword = currentKeyword.toLowerCase();
        const filteredParticipants = allParticipants.filter(p => {
            return (p.nama || '').toLowerCase().includes(keyword) || 
                   (p.nip || '').toLowerCase().includes(keyword);
        });

        if (allParticipants.length === 0) {
            emptyHint.textContent = 'Belum ada data presensi untuk acara ini.';
            emptyState.hidden = false;
            container.hidden = true;
            return;
        }

        if (filteredParticipants.length === 0) {
            emptyHint.textContent = 'Tidak ada peserta yang cocok dengan pencarian Anda.';
            emptyState.hidden = false;
            container.hidden = true;
            return;
        }

        let bodyHtml = '';
        filteredParticipants.forEach(p => {
            bodyHtml += '<tr>';
            bodyHtml += `<td data-label="Nama">${p.nama || '-'}</td>`;
            bodyHtml += `<td data-label="NIP">${p.nip || '-'}</td>`;
            allDates.forEach(date => {
                const dataHarian = p.attendance[date];
                if (dataHarian && dataHarian.status === 'Hadir') {
                    bodyHtml += `<td data-label="${date}" class="status-hadir-cell"><strong>Hadir</strong><br><span class="time-stamp">${dataHarian.timestamp}</span></td>`;
                } else {
                    bodyHtml += `<td data-label="${date}" class="status-alpha-cell">Alpha</td>`;
                }
            });
            bodyHtml += '</tr>';
        });
        tableBody.innerHTML = bodyHtml;
        container.hidden = false;
        emptyState.hidden = true;
    };

    // --- Fungsi Fetch Data ---
    const fetchLaporan = async () => {
        try {
            loadingOverlay.hidden = false;
            container.hidden = true;
            emptyState.hidden = true;

            const response = await fetch(`/admin/laporan/data/${acaraId}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (!response.ok) throw new Error('Gagal mengambil data laporan dari server.');

            const json = await response.json();

            if (json.success && json.report) {
                const { dates, participants } = json.report;
                
                allDates = dates || [];
                allParticipants = participants || [];
                
                // --- [PERUBAHAN] Kalkulasi Statistik Baru ---
                const skpdSet = new Set();
                allParticipants.forEach(p => {
                    if (p.skpd && p.skpd.trim() !== '') {
                        skpdSet.add(p.skpd);
                    }
                });
                const skpdCount = skpdSet.size;

                // Perbarui Stats Cards
                if (statTotalPeserta) statTotalPeserta.textContent = allParticipants.length;
                if (statTotalHari) statTotalHari.textContent = allDates.length;
                if (statTotalSKPD) statTotalSKPD.textContent = skpdCount;
                // --- [AKHIR PERUBAHAN] ---

                renderHeader();
                renderBody();

            } else {
                throw new Error(json.message || 'Data laporan tidak valid.');
            }

        } catch (error) {
            console.error(error);
            emptyState.hidden = false;
            container.hidden = true;
        } finally {
            loadingOverlay.hidden = true;
        }
    };

    // --- Event Listeners ---
    searchInput.addEventListener('input', (e) => {
        currentKeyword = e.target.value || '';
        renderBody(); 
    });

    if (btnDownload) {
        btnDownload.addEventListener('click', async () => {
            btnDownload.disabled = true;
            btnDownload.querySelector('span').textContent = 'Memproses...';

            try {
                const response = await fetch(`/admin/laporan/export/${acaraId}`);
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message || 'Ekspor berhasil disiapkan.');
                } else {
                    alert('Gagal mengekspor laporan: ' + data.message);
                }

            } catch (error) {
                alert('Terjadi kesalahan saat mengekspor.');
            } finally {
                btnDownload.disabled = false;
                btnDownload.querySelector('span').textContent = 'Unduh Laporan';
            }
        });
    }

    // --- Inisialisasi ---
    fetchLaporan();

})();