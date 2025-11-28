document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const tableBody = document.querySelector('#pegawaiTable tbody');
    const searchInput = document.getElementById('searchInput');
    let debounceTimer;

    // --- 1. Load Data ---
    function loadData(query = '', pageUrl = null) {
        tableBody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:30px; color:#64748b;">Memuat data...</td></tr>';
        
        const url = pageUrl || `/admin/pegawai/data?q=${query}`;

        fetch(url, { headers: {'Accept': 'application/json'} })
            .then(res => res.json())
            .then(json => {
                if (!json.data || json.data.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:40px; color:#94a3b8;">Tidak ada data pegawai.</td></tr>';
                    document.getElementById('pagination').innerHTML = ''; 
                    return;
                }
                
                const currentPage = json.current_page || 1;
                const perPage = json.per_page || 10;

                tableBody.innerHTML = json.data.map((p, index) => {
                    const rowNumber = (currentPage - 1) * perPage + (index + 1);

                    // URUTAN DATA: No, Nama, NIP, Lokasi, SKPD, Aksi
                    return `
                        <tr>
                            <td style="text-align:center;">${rowNumber}</td>
                            <td>
                                <div style="font-weight:500; color:#1e293b;">${p.nama}</div>
                                <div style="font-size:0.75rem; color:#64748b;">${p.email || ''}</div>
                            </td>
                            <td style="color:#334155;">${p.nip}</td>
                            <td>${p.lokasi_unit_kerja}</td>
                            <td>${p.skpd}</td>
                            <td class="col-actions">
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-outline" onclick="editPegawai(${p.id}, '${p.nip}', '${p.nama}', '${p.skpd}', '${p.lokasi_unit_kerja}', '${p.email||''}', '${p.ponsel||''}')">Edit</button>
                                    <button class="btn btn-sm btn-danger" onclick="deletePegawai(${p.id})">Hapus</button>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');

                renderPagination(json);
            })
            .catch(err => {
                console.error(err);
                tableBody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:20px; color:red;">Gagal memuat data.</td></tr>';
            });
    }

    function renderPagination(json) {
        const paginationDiv = document.getElementById('pagination');
        if (!json.next_page_url && !json.prev_page_url) {
            paginationDiv.innerHTML = '';
            return;
        }

        let html = '';
        if (json.prev_page_url) {
            html += `<button onclick="loadDataPage('${json.prev_page_url}')" class="btn btn-sm btn-outline">Previous</button>`;
        }
        html += `<span style="margin: 0 12px; font-size: 0.85rem; color:#64748b; font-weight:500;">Halaman ${json.current_page}</span>`;
        if (json.next_page_url) {
            html += `<button onclick="loadDataPage('${json.next_page_url}')" class="btn btn-sm btn-outline">Next</button>`;
        }
        paginationDiv.innerHTML = html;
    }

    window.loadDataPage = (url) => {
        const query = searchInput.value;
        const separator = url.includes('?') ? '&' : '?';
        loadData(query, `${url}${separator}q=${query}`);
    };

    searchInput.addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => loadData(e.target.value), 300);
    });

    loadData(); 

    // --- 2. Modal Logic (Add/Edit) ---
    const modal = document.getElementById('pegawaiModal');
    const form = document.getElementById('pegawaiForm');
    
    document.getElementById('btnAdd').addEventListener('click', () => {
        form.reset();
        document.getElementById('pegawaiId').value = '';
        document.getElementById('modalTitle').innerText = 'Tambah Pegawai';
        document.getElementById('inNip').readOnly = false;
        modal.classList.add('show');
    });

    window.editPegawai = (id, nip, nama, skpd, unit, email, ponsel) => {
        document.getElementById('pegawaiId').value = id;
        document.getElementById('inNip').value = nip;
        document.getElementById('inNip').readOnly = true; 
        document.getElementById('inNama').value = nama;
        document.getElementById('inSkpd').value = skpd;
        document.getElementById('inUnit').value = unit;
        document.getElementById('inEmail').value = email;
        document.getElementById('inPonsel').value = ponsel;
        
        document.getElementById('modalTitle').innerText = 'Edit Pegawai';
        modal.classList.add('show');
    };

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const id = document.getElementById('pegawaiId').value;
        const url = id ? `/admin/pegawai/${id}` : '/admin/pegawai';
        const method = id ? 'PUT' : 'POST';
        
        const btnSave = form.querySelector('button[type="submit"]');
        const originalText = btnSave.innerText;
        btnSave.innerText = 'Menyimpan...';
        btnSave.disabled = true;
        
        const formData = new FormData(form);
        const payload = Object.fromEntries(formData.entries());

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
        .then(data => {
            if(data.success) {
                modal.classList.remove('show');
                loadData(searchInput.value); 
                alert(data.message);
            } else {
                alert('Gagal menyimpan: ' + (data.message || 'Error validasi'));
            }
        })
        .catch(() => alert('Terjadi kesalahan sistem.'))
        .finally(() => {
            btnSave.innerText = originalText;
            btnSave.disabled = false;
        });
    });

    // --- 3. Delete Logic ---
    window.deletePegawai = (id) => {
        if(!confirm('Yakin hapus pegawai ini?')) return;
        fetch(`/admin/pegawai/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken }
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) loadData(searchInput.value);
        });
    };

    // --- 4. Import Logic & UI Handling ---
    const importModal = document.getElementById('importModal');
    const fileInput = document.querySelector('.file-input');
    const fileMsg = document.querySelector('.file-msg');
    const dropArea = document.querySelector('.file-drop-area');
    const btnDoImport = document.getElementById('btnDoImport');

    // Buka Modal
    document.getElementById('btnImport').addEventListener('click', () => {
        importModal.classList.add('show');
        resetFileUI(); // Reset tampilan setiap kali buka modal
    });

    // Fungsi Reset Tampilan File
    function resetFileUI() {
        fileInput.value = ''; // Kosongkan input
        fileMsg.textContent = 'Klik atau seret file CSV disini';
        dropArea.classList.remove('has-file');
        btnDoImport.disabled = false;
        btnDoImport.innerText = 'Import';
    }

    // Event: Saat file dipilih lewat klik
    fileInput.addEventListener('change', () => {
        if (fileInput.files.length > 0) {
            updateFileUI(fileInput.files[0].name);
        } else {
            resetFileUI();
        }
    });

    // Event: Drag & Drop Visual Effect
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.addEventListener(eventName, () => dropArea.classList.add('highlight'), false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, () => dropArea.classList.remove('highlight'), false);
    });

    // Event: Saat file di-drop
    dropArea.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;

        if (files.length > 0) {
            fileInput.files = files; // Assign file ke input element
            updateFileUI(files[0].name);
        }
    });

    // Fungsi Update Teks Nama File
    function updateFileUI(filename) {
        fileMsg.innerHTML = `<strong>File Terpilih:</strong><br>${filename}`;
        dropArea.classList.add('has-file');
    }

    // Event: Submit Form Import
    document.getElementById('importForm').addEventListener('submit', (e) => {
        e.preventDefault();
        
        if (fileInput.files.length === 0) {
            alert('Silakan pilih file terlebih dahulu.');
            return;
        }

        const formData = new FormData(document.getElementById('importForm'));
        const originalText = btnDoImport.innerText;
        
        btnDoImport.innerText = 'Mengupload...';
        btnDoImport.disabled = true;

        fetch('/admin/pegawai/import', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            importModal.classList.remove('show');
            loadData();
        })
        .catch(() => alert('Gagal import file.'))
        .finally(() => {
            btnDoImport.innerText = originalText;
            btnDoImport.disabled = false;
            resetFileUI();
        });
    });

    // Close Modals
    document.querySelectorAll('[data-close]').forEach(el => {
        el.addEventListener('click', () => {
            modal.classList.remove('show');
            importModal.classList.remove('show');
        });
    });
});