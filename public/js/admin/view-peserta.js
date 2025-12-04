(() => {
  // =========================================================================
  // 1. SELECTOR & SETUP
  // =========================================================================
  const table = document.getElementById('pesertaTable');
  const tbody = table ? table.querySelector('tbody') : null;
  const search = document.getElementById('pesertaSearch');
  
  // Buttons
  const btnAdd = document.getElementById('btnAddPeserta');
  const btnPrint = document.getElementById('btnPrintQr'); 
  const btnSendQrMass = document.getElementById('btnSendQrMass');
  const btnDraft = document.getElementById('btnDraftHistory'); // TOMBOL DRAFT
  
  // Layout Elements
  const emptyState = document.getElementById('emptyState');
  const tableWrapper = document.querySelector('.table-wrapper');
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const eventTitle = document.getElementById('eventTitle');
  const eventInfo = document.getElementById('eventInfo');
  const statTotal = document.getElementById('statTotal');
  const statSKPD = document.getElementById('statSKPD');
  const toastContainer = document.getElementById('toastContainer');

  // Modals
  const modal = document.getElementById('pesertaModal');
  const form = document.getElementById('pesertaForm');
  const modalTitle = document.getElementById('pesertaModalTitle');
  const inputId = document.getElementById('pesertaId');
  const inputNama = document.getElementById('psNama');
  const inputNip = document.getElementById('psNip');

  // Draft/History Modal Elements
  const draftModal = document.getElementById('draftHistoryModal');
  const timelineContainer = document.getElementById('historyTimeline');
  
  // Import
  const importModal = document.getElementById('importModal');
  const importForm = document.getElementById('importForm');
  const importFile = document.getElementById('importFile');
  const btnImport = document.getElementById('btnImportPeserta');
  const importLoading = document.getElementById('importLoading');
  
  // QR Send Choice
  const sendQrChoiceModal = document.getElementById('sendQrChoiceModal');
  const qrRecipientName = document.getElementById('qrRecipientName');
  const btnSendViaWA = document.getElementById('btnSendViaWA');
  const btnSendViaEmail = document.getElementById('btnSendViaEmail');
  
  // Print Modal & Filters
  const printModal = document.getElementById('printModal');
  const filterPrintSearch = document.getElementById('filterPrintSearch');
  const filterPrintSkpd = document.getElementById('filterPrintSkpd'); 
  const filterPrintLokasi = document.getElementById('filterPrintLokasi'); 
  const checkAllPrint = document.getElementById('checkAllPrint');
  const printListContainer = document.getElementById('printParticipantList');
  const selectedCountPrint = document.getElementById('selectedCount');

  // Bulk Employee Select
  const btnPilihPegawai = document.getElementById('btnPilihPegawai');
  const modalEmp = document.getElementById('employeeSelectorModal');
  const listEmpContainer = document.getElementById('employeeListContainer');
  const filterEmpSearch = document.getElementById('filterEmpSearch');
  const filterEmpSkpd = document.getElementById('filterEmpSkpd'); 
  const filterEmpLokasi = document.getElementById('filterEmpLokasi'); 
  const checkAllPegawai = document.getElementById('checkAllPegawai');
  const btnSubmitSelectedEmp = document.getElementById('btnSubmitSelectedEmp');
  const selectedEmpCountLabel = document.getElementById('selectedEmpCount');

  if (!table || !tbody) return;

  const pathMatch = window.location.pathname.match(/\/peserta\/view\/([a-zA-Z0-9-]+)/);
  const eventId = pathMatch ? decodeURIComponent(pathMatch[1]) : null;

  // =========================================================================
  // 2. HELPER FUNCTIONS
  // =========================================================================
  const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
  };

  const formatDateTime = (str) => {
      if(!str) return '-';
      const d = new Date(str);
      const day = d.getDate();
      const month = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'][d.getMonth()];
      const h = String(d.getHours()).padStart(2, '0');
      const m = String(d.getMinutes()).padStart(2, '0');
      return `${day} ${month} • ${h}:${m}`;
  };

  // --- TOAST ---
  const showToast = (message, type = 'success') => {
      if (!toastContainer) return;
      
      const el = document.createElement('div');
      const cssClass = type === 'error' ? 'toast-error' : 'toast-success';
      el.className = `toast ${cssClass}`;
      
      el.innerHTML = `
        <div class="toast-content">
            <span style="font-weight: 500;">${message}</span>
        </div>
        <button class="toast-close" title="Tutup"></button>
      `;

      el.querySelector('.toast-close').addEventListener('click', () => {
          el.classList.remove('show');
          setTimeout(() => el.remove(), 300);
      });
      
      toastContainer.appendChild(el);
      requestAnimationFrame(() => el.classList.add('show'));
      
      setTimeout(() => {
          if (el.parentElement) {
              el.classList.remove('show');
              el.addEventListener('transitionend', () => el.remove());
          }
      }, 3000);
  };

  // =========================================================================
  // 3. STATE VARIABLES
  // =========================================================================
  let pesertaData = [];
  let availableEmployees = [];
  
  // Variables for Print Modal
  let allPrintParticipants = []; 
  let currentFilteredPrintList = []; 

  // Variables for Employee Modal
  let currentVisibleEmployees = [];

  let keyword = '';
  let isLoading = false;
  let selectedPesertaForQr = null;
  let isOnlineEvent = false;

  // =========================================================================
  // 4. MAIN DATA LOADING
  // =========================================================================
  const loadEventInfo = () => {
    if (!eventId) return;
    fetch(`/admin/peserta/event/${eventId}`, { headers: { 'Accept': 'application/json' } })
      .then(res => res.json())
      .then(json => {
        if (json?.success && json.data) {
          const ev = json.data;
          eventTitle.textContent = `Daftar Peserta - ${ev.nama_acara}`;
          eventInfo.textContent = `${formatDate(ev.waktu_mulai)} • ${ev.lokasi || '-'}`;
          isOnlineEvent = (ev.mode_presensi === 'Online');
          
          if (isOnlineEvent) {
             if(btnPrint) btnPrint.style.display = 'none';
             if(btnSendQrMass) btnSendQrMass.style.display = 'none';
          } else {
             if(btnPrint) btnPrint.style.display = 'inline-flex';
             if(btnSendQrMass) {
                 btnSendQrMass.style.display = 'inline-flex';
                 btnSendQrMass.href = `/admin/peserta/send-qr/${eventId}`;
             }
          }
          fetchPesertaList();
        }
      });
  };

  const fetchStats = () => {
    if (!eventId) return;
    fetch(`/admin/peserta/stats/${eventId}`, { headers: { 'Accept': 'application/json' } })
      .then(res => res.json())
      .then(json => {
        if (json?.success && json.data) {
          statTotal.textContent = json.data.total ?? 0;
          statSKPD.textContent = json.data.jumlah_skpd ?? 0;
        }
      });
  };

  const fetchPesertaList = () => {
    if (!eventId || isLoading) return;
    isLoading = true;
    
    fetch(`/admin/peserta/data/${eventId}?page=1&per_page=100&q=${encodeURIComponent(keyword)}`, { headers: { 'Accept': 'application/json' } })
      .then(res => res.json())
      .then(json => {
        if (json?.success) {
          pesertaData = json.data;
        }
        render();
      })
      .finally(() => { isLoading = false; });
  };

  const render = () => {
    if (pesertaData.length === 0) {
      tbody.innerHTML = '';
      tableWrapper.style.display = 'none';
      emptyState.hidden = false;
      return;
    }
    tableWrapper.style.display = 'block';
    emptyState.hidden = true;

    tbody.innerHTML = pesertaData.map((p, idx) => {
      const qrBtn = !isOnlineEvent 
        ? `<button class="btn btn-sm btn-primary" data-action="qr" data-id="${p.id}" title="Kirim QR">Kirim QR</button>` 
        : '';
        
      return `
        <tr>
          <td class="text-center">${idx + 1}</td>
          <td>${p.nama}</td>
          <td>${p.nip}</td>
          <td>${p.lokasi_unit_kerja || '-'}</td>
          <td>${p.skpd}</td>
          <td class="col-actions">
            <div class="actions-cell">
              ${qrBtn}
              <button class="btn btn-sm btn-outline" data-action="edit" data-id="${p.id}">Edit</button>
              <button class="btn btn-sm btn-danger" data-action="delete" data-id="${p.id}">Hapus</button>
            </div>
          </td>
        </tr>`;
    }).join('');
  };

  // =========================================================================
  // 5. MODAL UTILITIES & CRUD
  // =========================================================================
  const closeModal = (modalEl) => {
    if (!modalEl) return;
    modalEl.classList.remove('is-open');
    document.body.classList.remove('no-scroll');
  };
  const openModalGeneric = (modalEl) => {
      modalEl.classList.add('is-open');
      document.body.classList.add('no-scroll');
  };

  if (btnAdd) btnAdd.addEventListener('click', () => { 
      form.reset(); 
      inputId.value = ''; 
      modalTitle.textContent = 'Tambah Peserta'; 
      if(inputNip) inputNip.readOnly = false;
      openModalGeneric(modal); 
  });

  if (form) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const isEdit = !!inputId.value; 
      
      const payload = {
        id_acara: eventId,
        nip: inputNip.value.trim(),
        nama: inputNama.value.trim(),
        lokasi_unit_kerja: document.getElementById('psLokasiUK').value,
        skpd: document.getElementById('psSkpd').value,
        email: document.getElementById('psEmail').value,
        ponsel: document.getElementById('psPonsel').value,
      };

      const url = isEdit ? `/admin/peserta/${inputId.value}` : '/admin/peserta';
      const method = isEdit ? 'PUT' : 'POST';

      fetch(url, { method, headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, body: JSON.stringify(payload) })
        .then(res => res.json())
        .then(json => {
          if (json?.success) { 
              closeModal(modal); 
              fetchPesertaList(); 
              fetchStats(); 
              showToast(isEdit ? 'Data diperbarui!' : 'Peserta ditambahkan!', 'success');
          } else { 
              showToast(json?.message || 'Gagal menyimpan.', 'error'); 
          }
        })
        .catch(() => showToast('Terjadi kesalahan koneksi.', 'error'));
    });
  }

  tbody.addEventListener('click', (e) => {
      const btn = e.target.closest('button');
      if(!btn) return;
      const action = btn.dataset.action;
      const id = btn.dataset.id;
      const p = pesertaData.find(x => String(x.id) === String(id));

      if(action === 'delete') {
          if(!confirm(`Hapus ${p.nama}?`)) return;
          fetch(`/admin/peserta/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrfToken } })
          .then(res => res.json())
          .then(json => {
              if(json.success) { fetchPesertaList(); fetchStats(); showToast('Peserta dihapus', 'success'); }
              else showToast('Gagal menghapus', 'error');
          });
      } else if (action === 'edit') {
          inputId.value = p.id;
          inputNama.value = p.nama;
          inputNip.value = p.nip;
          inputNip.readOnly = true; 
          document.getElementById('psLokasiUK').value = p.lokasi_unit_kerja || '';
          document.getElementById('psSkpd').value = p.skpd || '';
          document.getElementById('psEmail').value = p.email || '';
          document.getElementById('psPonsel').value = p.ponsel || '';
          modalTitle.textContent = 'Edit Peserta';
          openModalGeneric(modal);
      } else if (action === 'qr') {
          selectedPesertaForQr = p;
          qrRecipientName.textContent = p.nama;
          openModalGeneric(sendQrChoiceModal);
      }
  });

  // =========================================================================
  // 6. QR SEND LOGIC
  // =========================================================================
  const sendQrSingle = (method) => {
      if(!selectedPesertaForQr) return;
      const url = `/admin/peserta/send-qr/${eventId}/${method}/${encodeURIComponent(selectedPesertaForQr.nip)}`;
      
      fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }, body: JSON.stringify({ source: 'non-sim-asn' }) })
      .then(res => res.json())
      .then(json => {
          if(json.success) { 
            showToast('QR Berhasil dikirim!', 'success'); 
            closeModal(sendQrChoiceModal); 
          }
          else showToast(json.message, 'error');
      });
  };
  if(btnSendViaWA) btnSendViaWA.addEventListener('click', () => sendQrSingle('whatsapp'));
  if(btnSendViaEmail) btnSendViaEmail.addEventListener('click', () => sendQrSingle('email'));

  // =========================================================================
  // 7. DRAFT / RIWAYAT LOGIC (BARU)
  // =========================================================================
  if(btnDraft) {
      btnDraft.addEventListener('click', () => {
          openModalGeneric(draftModal);
          loadDraftHistory();
      });
  }

  const loadDraftHistory = () => {
      timelineContainer.innerHTML = '<div style="text-align:center; padding:20px;">Memuat data...</div>';
      
      fetch(`/admin/peserta/history/${eventId}`, { headers: {'Accept': 'application/json'} })
        .then(res => res.json())
        .then(json => {
            if(!json.success || json.data.length === 0) {
                timelineContainer.innerHTML = `
                    <div style="text-align:center; padding:40px; color:#64748b;">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="color:#cbd5e1; margin-bottom:10px;"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9"/></svg>
                        <p>Belum ada riwayat perubahan nama.</p>
                    </div>`;
                return;
            }
            
            // Render Timeline Vertikal
            timelineContainer.innerHTML = json.data.map(item => `
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <div class="timeline-time">${formatDateTime(item.diubah_pada)}</div>
                        <div class="timeline-body">
                            Nama peserta dengan NIP <strong>${item.nip}</strong> telah diganti.
                            <div class="timeline-change">
                                <span class="old">${item.nama_lama}</span>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                <span class="new">${item.nama_baru}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
        })
        .catch(err => {
            console.error(err);
            timelineContainer.innerHTML = '<div style="color:red; text-align:center;">Gagal memuat riwayat.</div>';
        });
  };

  // =========================================================================
  // 8. PRINT MODAL (FILTER & SELECT ALL)
  // =========================================================================
  const renderPrintListFiltered = () => {
      const keyword = filterPrintSearch.value.toLowerCase();
      const skpdVal = filterPrintSkpd.value.toLowerCase();
      const lokVal = filterPrintLokasi.value.toLowerCase();

      currentFilteredPrintList = allPrintParticipants.filter(p => {
          const matchName = (p.nama || '').toLowerCase().includes(keyword) || (p.nip || '').includes(keyword);
          const pSkpd = (p.skpd || '').toLowerCase();
          const matchSkpd = skpdVal === '' || pSkpd.includes(skpdVal);
          const pLokasi = (p.lokasi_unit_kerja || '').toLowerCase(); 
          const matchLok = lokVal === '' || pLokasi.includes(lokVal);
          return matchName && matchSkpd && matchLok;
      });

      if (currentFilteredPrintList.length === 0) {
          printListContainer.innerHTML = '<div style="padding:30px;text-align:center;color:#94a3b8;">Tidak ada data sesuai filter.</div>';
          if(checkAllPrint) checkAllPrint.checked = false;
          updatePrintSelectedCount();
          return;
      }

      printListContainer.innerHTML = currentFilteredPrintList.map(p => `
        <div class="employee-item">
           <label class="emp-checkbox-wrapper" style="width:100%; display:flex; gap:12px; align-items:center;">
              <input type="checkbox" class="print-cb" value="${p.nip}">
              <span class="custom-check"></span>
              <div class="emp-info">
                   <div class="emp-name">${p.nama}</div>
                   <div class="emp-detail">
                      ${p.nip} 
                      ${p.skpd ? `• <span style="color:#2563eb;">${p.skpd}</span>` : ''} 
                      ${p.lokasi_unit_kerja ? `• ${p.lokasi_unit_kerja}` : ''}
                   </div>
               </div>
           </label>
        </div>
      `).join('');

      const checkboxes = printListContainer.querySelectorAll('.print-cb');
      checkboxes.forEach(cb => {
          cb.addEventListener('change', updatePrintSelectedCount);
      });
      
      if(checkAllPrint) checkAllPrint.checked = false;
      updatePrintSelectedCount();
  };

  const updatePrintSelectedCount = () => {
      const checkboxes = printListContainer.querySelectorAll('.print-cb:checked');
      const count = checkboxes.length;
      
      if(selectedCountPrint) selectedCountPrint.textContent = count + ' terpilih';
      
      if(checkAllPrint && currentFilteredPrintList.length > 0) {
          checkAllPrint.checked = (count > 0 && count === currentFilteredPrintList.length);
      }
  };

  if(btnPrint) btnPrint.addEventListener('click', () => {
      openModalGeneric(printModal);
      printListContainer.innerHTML = '<div style="padding:20px;text-align:center;">Memuat data...</div>';
      
      filterPrintSearch.value = '';
      filterPrintSkpd.value = ''; 
      filterPrintLokasi.value = ''; 
      
      const listSkpd = document.getElementById('listPrintSkpd');
      const listLokasi = document.getElementById('listPrintLokasi');
      if(listSkpd) listSkpd.innerHTML = '';
      if(listLokasi) listLokasi.innerHTML = '';

      if(checkAllPrint) checkAllPrint.checked = false;
      if(selectedCountPrint) selectedCountPrint.textContent = '0 terpilih';

      fetch(`/admin/peserta/${eventId}/simple-list`, { headers: {'Accept': 'application/json'} })
      .then(res => res.json())
      .then(json => {
          if(json.success) {
              allPrintParticipants = json.data;
              
              const uniqueSkpd = [...new Set(allPrintParticipants.map(p => p.skpd))].filter(Boolean).sort();
              if(listSkpd) listSkpd.innerHTML = uniqueSkpd.map(s => `<option value="${s}">`).join('');

              const uniqueLok = [...new Set(allPrintParticipants.map(p => p.lokasi_unit_kerja))].filter(Boolean).sort();
              if(listLokasi) listLokasi.innerHTML = uniqueLok.map(l => `<option value="${l}">`).join('');

              renderPrintListFiltered();
          } else {
              printListContainer.innerHTML = '<div style="padding:20px;text-align:center;color:red;">Gagal memuat data.</div>';
          }
      })
      .catch(err => {
          printListContainer.innerHTML = '<div style="padding:20px;text-align:center;color:red;">Terjadi kesalahan server.</div>';
      });
  });

  if(filterPrintSearch) filterPrintSearch.addEventListener('input', renderPrintListFiltered);
  if(filterPrintSkpd) filterPrintSkpd.addEventListener('input', renderPrintListFiltered);
  if(filterPrintLokasi) filterPrintLokasi.addEventListener('input', renderPrintListFiltered);

  if(checkAllPrint) {
      checkAllPrint.addEventListener('change', (e) => {
          const isChecked = e.target.checked;
          const visibleCheckboxes = printListContainer.querySelectorAll('.print-cb');
          visibleCheckboxes.forEach(cb => cb.checked = isChecked);
          updatePrintSelectedCount();
      });
  }

  document.getElementById('btnPrintSelected')?.addEventListener('click', () => {
      const checkboxes = printListContainer.querySelectorAll('.print-cb:checked');
      const nips = Array.from(checkboxes).map(c => c.value).join(',');
      
      if(!nips) return showToast('Pilih minimal satu peserta', 'error');
      window.open(`/admin/peserta/print-qr/${eventId}?nips=${nips}`, '_blank');
  });

  // =========================================================================
  // 9. BULK EMPLOYEE SELECT MODAL
  // =========================================================================
  const loadEmployees = () => {
      listEmpContainer.innerHTML = '<div style="padding:40px;text-align:center;color:#64748b;">Memuat data pegawai...</div>';
      
      if(checkAllPegawai) checkAllPegawai.checked = false;
      if(selectedEmpCountLabel) selectedEmpCountLabel.textContent = '0 Pegawai dipilih';
      filterEmpSearch.value = '';
      filterEmpSkpd.value = ''; 
      filterEmpLokasi.value = ''; 
      
      const listSkpd = document.getElementById('listEmpSkpd');
      const listLokasi = document.getElementById('listEmpLokasi');
      if(listSkpd) listSkpd.innerHTML = '';
      if(listLokasi) listLokasi.innerHTML = '';

      fetch('/admin/pegawai/all-json', { headers: { 'Accept': 'application/json' } }) 
      .then(res => res.json())
      .then(json => {
          if(json.success) {
              availableEmployees = json.data; 
              
              const skpds = [...new Set(availableEmployees.map(e => e.skpd))].filter(Boolean).sort();
              if(listSkpd) listSkpd.innerHTML = skpds.map(s => `<option value="${s}">`).join('');
              
              const locations = [...new Set(availableEmployees.map(e => e.lokasi_unit_kerja))].filter(Boolean).sort();
              if(listLokasi) listLokasi.innerHTML = locations.map(l => `<option value="${l}">`).join('');

              renderEmployeeListFiltered();
          } else {
              listEmpContainer.innerHTML = '<div style="padding:20px;text-align:center;color:red;">Gagal memuat data pegawai.</div>';
          }
      })
      .catch(() => {
           listEmpContainer.innerHTML = '<div style="padding:20px;text-align:center;color:red;">Gagal menghubungi server.</div>';
      });
  };

  const renderEmployeeListFiltered = () => {
      const term = filterEmpSearch.value.toLowerCase();
      const skpd = filterEmpSkpd.value.toLowerCase();
      const lokasi = filterEmpLokasi.value.toLowerCase();
      
      currentVisibleEmployees = availableEmployees.filter(e => {
          const matchName = (e.nama||'').toLowerCase().includes(term) || (e.nip||'').includes(term);
          const eSkpd = (e.skpd || '').toLowerCase();
          const matchSkpd = skpd === '' || eSkpd.includes(skpd);
          const eLokasi = (e.lokasi_unit_kerja||'').toLowerCase();
          const matchLokasi = lokasi === '' || eLokasi.includes(lokasi);
          return matchName && matchSkpd && matchLokasi;
      });

      if(currentVisibleEmployees.length === 0) {
          listEmpContainer.innerHTML = '<div style="padding:40px;text-align:center;color:#94a3b8;">Tidak ditemukan.</div>';
          if(checkAllPegawai) checkAllPegawai.checked = false;
          updateSelectedEmpCount();
          return;
      }

      listEmpContainer.innerHTML = currentVisibleEmployees.map(emp => `
          <div class="employee-item">
               <label class="emp-checkbox-wrapper" style="width:100%; display:flex; gap:12px; align-items:center;">
                  <input type="checkbox" class="emp-checkbox" value="${emp.nip}">
                  <span class="custom-check"></span>
                  <div class="emp-info">
                      <div class="emp-name">${emp.nama}</div>
                      <div class="emp-detail">${emp.nip} • ${emp.skpd || 'Tanpa SKPD'}</div>
                  </div>
               </label>
          </div>
      `).join('');
      
      const checkboxes = listEmpContainer.querySelectorAll('.emp-checkbox');
      checkboxes.forEach(cb => {
          cb.addEventListener('change', updateSelectedEmpCount);
      });
      
      if(checkAllPegawai) checkAllPegawai.checked = false;
      updateSelectedEmpCount();
  };

  const updateSelectedEmpCount = () => {
      const checkboxes = listEmpContainer.querySelectorAll('.emp-checkbox:checked');
      const count = checkboxes.length;
      
      if(selectedEmpCountLabel) selectedEmpCountLabel.textContent = `${count} Pegawai dipilih`;

      if(checkAllPegawai && currentVisibleEmployees.length > 0) {
          checkAllPegawai.checked = (count === currentVisibleEmployees.length);
      }
  };

  if(btnPilihPegawai) {
      btnPilihPegawai.addEventListener('click', () => {
          openModalGeneric(modalEmp);
          loadEmployees();
      });
  }
  
  if(filterEmpSearch) filterEmpSearch.addEventListener('input', renderEmployeeListFiltered);
  if(filterEmpSkpd) filterEmpSkpd.addEventListener('input', renderEmployeeListFiltered);
  if(filterEmpLokasi) filterEmpLokasi.addEventListener('input', renderEmployeeListFiltered);
  
  if(checkAllPegawai) {
      checkAllPegawai.addEventListener('change', (e) => {
          const isChecked = e.target.checked;
          const visibleCheckboxes = listEmpContainer.querySelectorAll('.emp-checkbox');
          visibleCheckboxes.forEach(cb => cb.checked = isChecked);
          updateSelectedEmpCount();
      });
  }

  // Submit Bulk
  if(btnSubmitSelectedEmp) {
      btnSubmitSelectedEmp.addEventListener('click', () => {
          const checkboxes = listEmpContainer.querySelectorAll('.emp-checkbox:checked');
          
          if(checkboxes.length === 0) { showToast('Pilih minimal satu pegawai', 'error'); return; }

          const selectedNips = Array.from(checkboxes).map(cb => cb.value);
          btnSubmitSelectedEmp.textContent = 'Menyimpan...';
          btnSubmitSelectedEmp.disabled = true;

          fetch(`/admin/peserta/bulk-store`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
              body: JSON.stringify({ id_acara: eventId, nips: selectedNips })
          })
          .then(res => res.json())
          .then(json => {
              if(json.success) {
                  showToast(json.message, 'success');
                  closeModal(modalEmp);
                  fetchPesertaList(true);
                  fetchStats();
                  checkboxes.forEach(c => c.checked = false);
                  updateSelectedEmpCount();
              } else {
                  showToast(json.message || 'Gagal menyimpan.', 'error');
              }
          })
          .catch(() => showToast('Terjadi kesalahan server.', 'error'))
          .finally(() => {
              btnSubmitSelectedEmp.textContent = 'Tambahkan Terpilih';
              btnSubmitSelectedEmp.disabled = false;
          });
      });
  }

  // =========================================================================
  // 10. IMPORT LOGIC
  // =========================================================================
  if(btnImport) btnImport.addEventListener('click', () => openModalGeneric(importModal));
  if(importForm) {
      importForm.addEventListener('submit', (e) => {
          e.preventDefault();
          if(!importFile.files.length) return alert('Pilih file');
          
          const formData = new FormData();
          formData.append('file', importFile.files[0]);
          importLoading.hidden = false;
          
          fetch(`/admin/peserta/import/${eventId}`, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken }, body: formData })
          .then(res => res.json())
          .then(json => {
              if(json.success) {
                  closeModal(importModal);
                  fetchPesertaList(true);
                  fetchStats();
                  showToast(`Import Selesai. Masuk: ${json.summary.inserted}, Update: ${json.summary.updated}`, 'success');
              } else { showToast(json.message, 'error'); }
          })
          .finally(() => importLoading.hidden = true);
      });
  }

  // =========================================================================
  // 11. GLOBAL SEARCH & CLOSE
  // =========================================================================
  search.addEventListener('input', (e) => { keyword = e.target.value; fetchPesertaList(true); });

  window.addEventListener('click', (e) => {
    if (e.target.matches('[data-dismiss="modal"]') || e.target.matches('.modal__backdrop')) {
        document.querySelectorAll('.modal.is-open').forEach(m => closeModal(m));
    }
  });

  // Init
  loadEventInfo();
  if (eventId) { fetchStats(); }

})();