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
        const onChange = (e) => {
            const file = e.target.files?.[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (ev) => { if (photoPreview) photoPreview.src = ev.target.result; };
            reader.readAsDataURL(file);
            photoInput.removeEventListener('change', onChange);
        };
        photoInput.addEventListener('change', onChange);
        }

       if (resetBtn) {
            resetBtn.onclick = async () => {
                console.log("Trigger");
                if (!emp) {
                    alert('Employee ID not found. Cannot reset password.');
                    return;
                }

                const confirmReset = confirm(`Apakah ${name} yakin ingin RESET password? `);

                if (!confirmReset) return;

                const currentPassword = prompt('Masukkan Password Anda untuk melanjutkan proses : ');

                if (!currentPassword) {
                    alert ('Password Konfirmasi Wajib isi!');
                    return;

                }
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
                
                        alert("Password berhasil direset.");
                
                        window.location.replace(data.redirect);
                
                    } else {
                
                        alert(`⚠️ Failed: ${data.message || 'Unable to reset password.'}`);
                
                    }
                
                } catch (err) {
                    console.error(err);
                    alert('❌ Error: Unable to contact server.');
                }
            };
        }
    });
}

