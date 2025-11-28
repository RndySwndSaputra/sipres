document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('forgotForm');
  if (!form) return;
  const email = document.getElementById('email');
  const btn = form.querySelector('.btn-otp');

  function setError(el, hasError) {
    el.classList.toggle('input-error', hasError);
  }

  function validate() {
    let ok = true;
    const emailVal = (email.value || '').trim();
    if (!emailVal) { setError(email, true); ok = false; } else { setError(email, false); }
    return ok;
  }

  email.addEventListener('input', () => setError(email, false));

  const saved = localStorage.getItem('reset_email');
  if (saved) email.value = saved;

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (!validate()) return;

    btn.disabled = true;
    const original = btn.textContent;
    btn.textContent = 'Memproses';

    setTimeout(() => {
      const emailVal = (email.value || '').trim();
      localStorage.setItem('reset_email', emailVal);
      alert('OTP telah dikirim ke: ' + emailVal);
      btn.disabled = false;
      btn.textContent = original;
    }, 800);
  });
});
