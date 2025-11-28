(() => {
    // --- Elemen Global ---
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // --- Navigasi ---
    const navItems = document.querySelectorAll('.nav-item');
    const sections = document.querySelectorAll('.settings-section');

    // --- Elemen Akun ---
    const btnUpdateName = document.getElementById('btnUpdateName');
    const btnUpdateEmail = document.getElementById('btnUpdateEmail');
    const userName = document.getElementById('userName');
    const userEmail = document.getElementById('userEmail');
    
    const passwordForm = document.getElementById('passwordForm'); 
    const btnChangePassword = document.getElementById('btnChangePassword');
    const btnForgotPassword = document.getElementById('btnForgotPassword');
    
    // --- Modal Lupa Password ---
    const forgotPasswordModal = document.getElementById('forgotPasswordModal');
    const resetEmail = document.getElementById('resetEmail');

    // --- Elemen Lain (dari file Anda) ---
    const btnDeleteAccount = document.getElementById('btnDeleteAccount');
    const toggleToast = document.getElementById('toggleToast');
    const toggleLoginNotif = document.getElementById('toggleLoginNotif');
    const toggleSystemNotif = document.getElementById('toggleSystemNotif');
    const toggleEmailNotif = document.getElementById('toggleEmailNotif');
    const toggle2FA = document.getElementById('toggle2FA');
    const card2FASetup = document.getElementById('card2FASetup');
    const card2FAActive = document.getElementById('card2FAActive');
    const btnVerify2FA = document.getElementById('btnVerify2FA');
    const verify2FA = document.getElementById('verify2FA');
    const btnDownloadCodes = document.getElementById('btnDownloadCodes');
    let is2FAActive = false;
    
    // --- Helper: Tampilkan Notifikasi Toast ---
    const showToast = (message, isError = false) => {
        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        if (isError) {
             toast.classList.add('toast-error');
        }
        const icon = isError 
            ? '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true"><path d="M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
            : '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><polyline points="22 4 12 14.01 9 11.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        toast.innerHTML = `${icon} <span>${message}</span>`;
        document.body.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    };

    // --- Helper: Penanganan Error Validasi ---
    const clearValidationErrors = (container) => {
        container.querySelectorAll('.input-error-text').forEach(el => el.textContent = '');
    };
    const handleValidationErrors = (container, errors) => {
        clearValidationErrors(container);
        for (const field in errors) {
            const errorEl = container.querySelector(`.input-error-text[data-for="${field}"]`);
            if (errorEl) {
                errorEl.textContent = errors[field][0]; 
            }
        }
    };
    
    // --- 1. Navigasi (dari file Anda, sudah benar) ---
    const switchSection = (targetSection) => {
        navItems.forEach(item => item.classList.remove('active'));
        sections.forEach(section => section.classList.remove('active'));
        const targetNav = document.querySelector(`[data-section="${targetSection}"]`);
        const targetSectionEl = document.getElementById(`section-${targetSection}`);
        if (targetNav) targetNav.classList.add('active');
        if (targetSectionEl) {
            targetSectionEl.classList.add('active');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    };
    navItems.forEach(item => {
        item.addEventListener('click', () => switchSection(item.getAttribute('data-section')));
    });
    const handleHashNavigation = () => {
        const hash = window.location.hash.substring(1);
        if (hash && hash === 'notifications') {
            switchSection('notifications');
        }
    };
    handleHashNavigation();
    window.addEventListener('hashchange', handleHashNavigation);

    // --- 2. [DIGANTI] Logika Update Nama ---
    if (btnUpdateName) {
        btnUpdateName.addEventListener('click', async () => {
            const newName = userName.value.trim();
            const btn = btnUpdateName;
            const container = btn.closest('.card-body');
            clearValidationErrors(container); 

            if (!newName) {
                handleValidationErrors(container, { name: ['Nama tidak boleh kosong'] });
                return;
            }

            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Menyimpan...';

            try {
                const response = await fetch("/admin/pengaturan/nama", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ name: newName })
                });

                const result = await response.json();
                if (!response.ok) {
                    if (response.status === 422) handleValidationErrors(container, result.errors);
                    throw new Error(result.message || 'Gagal memperbarui nama.');
                }
                
                showToast(result.message || 'Nama berhasil diperbarui.');
                // [PERUBAHAN 1] Refresh halaman setelah 1 detik
                setTimeout(() => location.reload(), 1000); 

            } catch (error) {
                showToast(error.message, true);
            } finally {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        });
    }

    // --- 3. [DIGANTI] Logika Update Email ---
    if (btnUpdateEmail) {
        btnUpdateEmail.addEventListener('click', async () => {
            const newEmail = userEmail.value.trim();
            const btn = btnUpdateEmail;
            const container = btn.closest('.card-body');
            clearValidationErrors(container); 

            if (!newEmail || !newEmail.includes('@')) {
                handleValidationErrors(container, { email: ['Format email tidak valid'] });
                return;
            }

            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Menyimpan...';

            try {
                const response = await fetch("/admin/pengaturan/email", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ email: newEmail })
                });

                const result = await response.json();
                if (!response.ok) {
                    if (response.status === 422) handleValidationErrors(container, result.errors);
                    throw new Error(result.message || 'Gagal memperbarui email.');
                }
                
                showToast(result.message || 'Email berhasil diperbarui.');
                // [PERUBAHAN 2] Refresh halaman setelah 1 detik
                setTimeout(() => location.reload(), 1000);

            } catch (error) {
                showToast(error.message, true);
            } finally {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        });
    }

    // --- 4. [DIGANTI] Logika Ganti Password ---
    if (passwordForm) {
        passwordForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearValidationErrors(passwordForm);

            const btn = btnChangePassword;
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Memproses...';

            try {
                const formData = new FormData(passwordForm);
                const response = await fetch("/admin/pengaturan/password", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: formData
                });

                const result = await response.json();

                if (!response.ok) {
                    if (response.status === 422) {
                        handleValidationErrors(passwordForm, result.errors);
                    }
                    throw new Error(result.message || 'Gagal mengganti password.');
                }

                showToast(result.message || 'Password berhasil diubah.');
                passwordForm.reset(); 
                passwordForm.querySelectorAll('.toggle-password i').forEach(icon => {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                });
                // [PERUBAHAN 3] Refresh halaman setelah 1 detik (untuk ganti password, 
                // Anda mungkin ingin mengarahkan ke login, tapi refresh juga oke)
                setTimeout(() => location.reload(), 1000);

            } catch (error) {
                console.error('Error update password:', error);
                showToast(error.message, true);
            } finally {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        });
    }
    
    // --- 5. [BARU] Logika Ikon Mata Password ---
    document.querySelectorAll('.toggle-password').forEach(icon => {
        icon.addEventListener('click', function() {
            const targetId = this.dataset.target;
            const passwordInput = document.getElementById(targetId);
            const iconElement = this.querySelector('i');
            if (!passwordInput) return;

            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            iconElement.classList.toggle('fa-eye');
            iconElement.classList.toggle('fa-eye-slash');
        });
    });

    // --- 6. Sisa Logika (dari file Anda, TETAP SAMA) ---
    if (btnForgotPassword) {
        btnForgotPassword.addEventListener('click', () => {
            const email = userEmail.value;
            if (resetEmail) resetEmail.textContent = email;
            openForgotPasswordModal();
            // TODO: Tambah fetch ke backend untuk kirim email lupa password
            // fetch("/forgot-password", { ... body: { email: email } ... })
        });
    }

    const openForgotPasswordModal = () => {
        if (!forgotPasswordModal) return;
        forgotPasswordModal.classList.add('is-open');
        document.body.classList.add('no-scroll');
        forgotPasswordModal.setAttribute('aria-hidden', 'false');
    };

    const closeForgotPasswordModal = () => {
        if (!forgotPasswordModal) return;
        forgotPasswordModal.classList.remove('is-open');
        document.body.classList.remove('no-scroll');
        forgotPasswordModal.setAttribute('aria-hidden', 'true');
    };

    if (forgotPasswordModal) {
        forgotPasswordModal.addEventListener('click', (e) => {
            if (e.target.matches('[data-dismiss="modal"]')) {
                closeForgotPasswordModal();
            }
        });
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && forgotPasswordModal && forgotPasswordModal.classList.contains('is-open')) {
            closeForgotPasswordModal();
        }
    });

    if (btnDeleteAccount) {
        btnDeleteAccount.addEventListener('click', () => {
            const confirmation = confirm(
                'PERINGATAN!\n\n' +
                'Tindakan ini akan menghapus akun Anda secara permanen.\n' +
                'Semua data termasuk presensi, acara, dan peserta akan hilang.\n\n' +
                'Apakah Anda yakin ingin melanjutkan?'
            );

            if (confirmation) {
                const doubleConfirm = prompt('Ketik "HAPUS AKUN" untuk mengkonfirmasi:');
                if (doubleConfirm === 'HAPUS AKUN') {
                    alert('Akun Anda telah dihapus.\n\Anda akan diarahkan ke halaman login.');
                } else {
                    alert('Penghapusan akun dibatalkan.');
                }
            }
        });
    }

    // Notification Toggles
    const handleToggle = (toggle, name) => {
        if (!toggle) return;
        toggle.addEventListener('change', (e) => {
            const status = e.target.checked ? 'diaktifkan' : 'dinonaktifkan';
            console.log(`${name} ${status}`);
            if (toggleToast && toggleToast.checked && toggle !== toggleToast) {
                showToast(`${name} telah ${status}`);
            }
        });
    };
    handleToggle(toggleToast, 'Notifikasi Toast');
    handleToggle(toggleLoginNotif, 'Notifikasi Login');
    handleToggle(toggleSystemNotif, 'Notifikasi Sistem');
    handleToggle(toggleEmailNotif, 'Notifikasi Email');

    // 2FA Handlers
    if (toggle2FA) {
        toggle2FA.addEventListener('change', (e) => {
            // ... (logika 2FA Anda) ...
        });
    }
    if (btnVerify2FA) {
        btnVerify2FA.addEventListener('click', () => {
            // ... (logika 2FA Anda) ...
        });
    }
    if (btnDownloadCodes) {
        btnDownloadCodes.addEventListener('click', () => {
            // ... (logika 2FA Anda) ...
        });
    }
})();