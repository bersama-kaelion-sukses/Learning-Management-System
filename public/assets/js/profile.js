export function InitProfile(defaultImgUrl) {
    const modalEl = document.getElementById('EditProfileModal');
    if (!modalEl) return;


    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const actionUrl = modalEl.getAttribute('data-action') || '/profile';

    modalEl.addEventListener('show.bs.modal', (evt) => {
        const btn = evt.relatedTarget; // the trigger button (optional on profile page)
        const form          = document.getElementById('editUserForm');
        const photoPreview  = modalEl.querySelector('#photoPreview');
        const fullNameInput = modalEl.querySelector('#full_name');
        const empInput      = modalEl.querySelector('#emp_id');
        const empHidden     = modalEl.querySelector('#emp_id_hidden');
        const photoInput    = modalEl.querySelector('#photo_profile');
        const resetBtn      = modalEl.querySelector('#btnResetPassword');
        const currentPasswordInput = modalEl.querySelector('#currentPassword');
        const resetFeedback = modalEl.querySelector('#profileResetFeedback');

        // If opened from a button with data-*, use those values; otherwise keep existing values
        const id    = btn?.getAttribute('data-id') || '';
        const name  = btn?.getAttribute('data-full_name') || fullNameInput?.value || '';
        const emp   = btn?.getAttribute('data-emp_id') || empHidden?.value || '';
        const photo = btn?.getAttribute('data-photo') || photoPreview?.src || '';

        if (form) form.action = actionUrl; // << NO ID in URL (matches Route::put('/profile'))

        if (fullNameInput) fullNameInput.value = name;
        if (empInput)      empInput.value = emp;
        if (empHidden)     empHidden.value = emp;

        if (photoPreview) {
        photoPreview.src = (photo && photo.trim() !== '') ? photo : (defaultImgUrl || photoPreview.src);
        }

        // reset file input & preview each time
        if (photoInput) {
            photoInput.value = '';
            photoInput.onchange = (e) => {
                const file = e.target.files?.[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = (ev) => { if (photoPreview) photoPreview.src = ev.target.result; };
                reader.readAsDataURL(file);
            };
        }

        if (currentPasswordInput) {
            currentPasswordInput.value = '';
            currentPasswordInput.classList.remove('is-invalid');
        }
        if (resetFeedback) {
            resetFeedback.textContent = '';
            resetFeedback.className = 'profile-reset-feedback d-none mb-0';
        }

       if (resetBtn) {
            resetBtn.onclick = async () => {
                if (!emp) {
                    showResetFeedback('ID karyawan tidak ditemukan.', 'danger');
                    return;
                }

                const currentPassword = currentPasswordInput?.value.trim() || '';

                if (!currentPassword) {
                    currentPasswordInput?.classList.add('is-invalid');
                    currentPasswordInput?.focus();
                    showResetFeedback('Masukkan password saat ini untuk melanjutkan.', 'danger');
                    return;
                }

                currentPasswordInput.classList.remove('is-invalid');
                const confirmReset = confirm(`Reset password untuk ${name} dan keluar dari akun ini?`);
                if (!confirmReset) return;

                resetBtn.disabled = true;
                resetBtn.textContent = 'Memproses...';
                showResetFeedback('Memverifikasi akun...', 'muted');

                try {
                    const res = await fetch(`/profile/reset-password/${emp}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            current_password: currentPassword
                        })
                    });

                    const data = await res.json();

                    if (data.success) {
                        showResetFeedback('Password berhasil direset. Mengalihkan ke halaman masuk...', 'success');
                        window.location.replace(data.redirect);
                    } else {
                        showResetFeedback(data.message || 'Password tidak dapat direset.', 'danger');
                    }

                } catch (err) {
                    console.error(err);
                    showResetFeedback('Layanan tidak dapat dihubungi. Silakan coba lagi.', 'danger');
                } finally {
                    resetBtn.disabled = false;
                    resetBtn.textContent = 'Reset Password';
                }
            };
        }

        function showResetFeedback(message, type) {
            if (!resetFeedback) return;
            resetFeedback.textContent = message;
            resetFeedback.className = `profile-reset-feedback mb-0 text-${type}`;
        }
    });
}

