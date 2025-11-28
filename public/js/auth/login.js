document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('loginForm');
    if (!form) return;
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    const remember = document.getElementById('remember');
    const btn = form.querySelector('.btn-login');

    function setError(el, hasError) {
        el.classList.toggle('input-error', hasError);
    }

    function validate() {
        let ok = true;
        const emailVal = (email.value || '').trim();
        const passVal = password.value || '';

        if (!emailVal) { setError(email, true); ok = false; } else { setError(email, false); }
        if (!passVal) { setError(password, true); ok = false; } else { setError(password, false); }
        return ok;
    }

    [email, password].forEach(el => el.addEventListener('input', () => setError(el, false)));

    const savedEmail = localStorage.getItem('email_remember');
    if (savedEmail) {
        email.value = savedEmail;
        if (remember) remember.checked = true;
    }

    // --- [KODE BARU DITAMBAHKAN DI SINI] ---
    const togglePasswordIcons = document.querySelectorAll('.toggle-password');
    togglePasswordIcons.forEach(icon => {
        icon.addEventListener('click', function () {
            const targetId = this.dataset.target;
            const passwordInput = document.getElementById(targetId);
            const iconElement = this.querySelector('i');
            if (!passwordInput) return;

            // Toggle tipe input (password/text)
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            // Toggle ikon mata (buka/tutup)
            iconElement.classList.toggle('fa-eye');
            iconElement.classList.toggle('fa-eye-slash');
        });
    });
    // --- [AKHIR KODE BARU] ---

    form.addEventListener('submit', function (e) {
        // Logika validasi Anda sedikit berbeda, kita harus memanggil setError
        // pada input di dalam .password-input-group
        const passContainer = password.closest('.password-input-group');
        if (!validate()) {
            e.preventDefault();
            // Terapkan error ke kontainer jika ada
            if (passContainer && password.classList.contains('input-error')) {
                passContainer.querySelector('input').classList.add('input-error');
            }
            return;
        }

        btn.disabled = true;
        const original = btn.textContent;
        btn.textContent = 'Memproses';

        const emailVal = (email.value || '').trim();
        if (remember && remember.checked) {
            localStorage.setItem('email_remember', emailVal);
        } else {
            localStorage.removeItem('email_remember');
        }
        // Allow native form submit
    });
    
    // Juga tambahkan listener input ke input password yang baru
    if (password) {
      password.addEventListener('input', () => {
        const passContainer = password.closest('.password-input-group');
        if (passContainer) {
            passContainer.querySelector('input').classList.remove('input-error');
        }
      });
    }
});