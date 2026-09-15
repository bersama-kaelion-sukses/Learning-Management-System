export function InitUserMgt() {
    initEditUserModal();
    initPrivilegesModal();
    initUserOperationModal();
    
    function initEditUserModal() {
        const modalEl = document.getElementById('EditUserModal');
        const form = document.getElementById('editUserForm');
        if (!modalEl || !form) return;

        // hidden untuk submit emp_id (karena input disabled)
        let empHidden = form.querySelector('input[name="emp_id_hidden"]');
        if (!empHidden) {
            empHidden = document.createElement('input');
            empHidden.type = 'hidden';
            empHidden.name = 'emp_id_hidden';
            form.appendChild(empHidden);
        }

        modalEl.addEventListener('show.bs.modal', function (evt) {
            const btn = evt.relatedTarget;
            if (!btn) return;

            const id = btn.getAttribute('data-id') ?? '';
            const name = btn.getAttribute('data-full_name') ?? '';
            const emp = btn.getAttribute('data-emp_id') ?? '';
            const div = btn.getAttribute('data-departement_cat') ?? '';
            const role = btn.getAttribute('data-role_id') ?? '';
            const pos = btn.getAttribute('data-position_id') ?? ''; // âœ… ambil position_id
            const photo = btn.getAttribute('data-photo') ?? '';

            const photoPreview = modalEl.querySelector('#photoPreview');
            const photoInput = modalEl.querySelector('#photo_profile');
            const fullNameInput = modalEl.querySelector('#full_name');
            const empInput = modalEl.querySelector('#emp_id');
            const departementSel = modalEl.querySelector('#departement_cat');
            const roleSel = modalEl.querySelector('#role_id');
            const positionSel = modalEl.querySelector('#position_id'); // âœ… ambil element select

            // set action PUT /user-management/{id}
            form.action = `/user-management/${id}`;

            if (fullNameInput) fullNameInput.value = name;
            if (empInput) empInput.value = emp;
            empHidden.value = emp;

            if (departementSel && [...departementSel.options].some(o => o.value == String(div))) {
                departementSel.value = String(div);
            }
            if (roleSel && [...roleSel.options].some(o => o.value == String(role))) {
                roleSel.value = String(role);
            }
            if (positionSel && [...positionSel.options].some(o => o.value == String(pos))) { // âœ… set value posisi
                positionSel.value = String(pos);
            }

            if (photoPreview) {
                photoPreview.src = (photo && photo.trim() !== '') ? photo : '/assets/img/defaultPic.jpeg';
            }

            if (photoInput) {
                photoInput.value = '';
                const onChange = (e) => {
                    const file = e.target.files && e.target.files[0];
                    if (!file) return;
                    const reader = new FileReader();
                    reader.onload = (ev) => { if (photoPreview) photoPreview.src = ev.target.result; };
                    reader.readAsDataURL(file);
                    photoInput.removeEventListener('change', onChange);
                };
                photoInput.addEventListener('change', onChange);
            }
        });

        modalEl.addEventListener('hidden.bs.modal', function () {
            const photoInput = modalEl.querySelector('#photo_profile');
            if (photoInput) photoInput.value = '';
        });
    }

    function initPrivilegesModal() {
        const modal = document.getElementById('userPrivilages');
        if (!modal) return;

        let lastTrigger = null;

        const left     = modal.querySelector('select.available-privileges');
        const right    = modal.querySelector('select.current-privileges');
        const btnRight = modal.querySelector('.move-right');
        const btnLeft  = modal.querySelector('.move-left');
        const applyBtn = modal.querySelector('.apply-privileges');

        if (!left || !right || !btnRight || !btnLeft || !applyBtn) return;

        const optionExists = (sel, value) =>
            Array.from(sel.options).some(o => String(o.value) === String(value));

        function moveSelected(src, dst) {
            const toMove = Array.from(src.selectedOptions).filter(o => !o.disabled);
            if (!toMove.length) return;

            toMove.forEach(opt => {
                if (!optionExists(dst, opt.value)) dst.add(opt.cloneNode(true));
                opt.remove();
            });

            [src, dst].forEach(sel => {
                const sorted = Array.from(sel.options)
                    .sort((a, b) => a.text.localeCompare(b.text, undefined, { sensitivity: 'base' }));
                sel.innerHTML = '';
                sorted.forEach(o => sel.add(o));
                sel.selectedIndex = -1;
            });
        }

        btnRight.addEventListener('click', () => moveSelected(left, right));
        btnLeft .addEventListener('click', () => moveSelected(right, left));

        left .addEventListener('dblclick', e => { 
            if (e.target.tagName === 'OPTION' && !e.target.disabled) moveSelected(left, right);
        });
        right.addEventListener('dblclick', e => { 
            if (e.target.tagName === 'OPTION' && !e.target.disabled) moveSelected(right, left);
        });

        function buildMainRoleOption(value, label) {
            const o = document.createElement('option');
            o.value = String(value);
            o.text  = label;
            o.disabled = true;
            o.style.color = 'rgba(39, 51, 56, 0.68)';
            o.dataset.main = '1';
            return o;
        }

        function resolveMainRoleId(trigger) {
            let id = trigger?.getAttribute('data-role_id');
            if (id) return parseInt(id, 10);

            const row = trigger?.closest('tr');
            const siblingWithRole = row?.querySelector('[data-role_id]');
            if (siblingWithRole) {
                const v = siblingWithRole.getAttribute('data-role_id');
                if (v) return parseInt(v, 10);
            }
            return NaN;
        }

        const roleLabel = { 1: 'IT', 2: 'Administrator/HR', 3: 'Instructor', 4: 'Learner' };

        modal.addEventListener('show.bs.modal', (evt) => {
            lastTrigger = evt.relatedTarget || null;

            const btn = evt.relatedTarget;
            if (!btn) return;

            const userId   = btn.getAttribute('data-id') || '';
            const subRoleS = btn.getAttribute('data-sub_role') || '';
            const mainRoleId = resolveMainRoleId(btn);
            modal.dataset.userId = userId;
            modal.dataset.mainRoleId = Number.isFinite(mainRoleId) ? String(mainRoleId) : '';

            let selectedIds = [];
            if (subRoleS) {
                try {
                    const parsed = JSON.parse(subRoleS);
                    selectedIds = Array.isArray(parsed) ? parsed : [];
                } catch {
                    selectedIds = subRoleS.split(',')
                        .map(s => parseInt(String(s).trim(), 10))
                        .filter(n => Number.isFinite(n));
                }
            }

            const back = Array.from(right.options).map(o => o.cloneNode(true));
            right.innerHTML = '';
            back.forEach(o => { if (!optionExists(left, o.value)) left.add(o); });

            if (Number.isFinite(mainRoleId)) {
                Array.from(left.options).forEach(opt => {
                    if (parseInt(opt.value, 10) === mainRoleId) opt.remove();
                });
            }

            Array.from(left.options).forEach(opt => {
                const val = parseInt(opt.value, 10);
                if (selectedIds.includes(val) && (!Number.isFinite(mainRoleId) || val !== mainRoleId)) {
                    right.add(opt.cloneNode(true));
                    opt.remove();
                }
            });

            if (Number.isFinite(mainRoleId)) {
                const label = roleLabel[mainRoleId] ?? `Role ${mainRoleId}`;
                Array.from(right.options).forEach(o => {
                    if (parseInt(o.value, 10) === mainRoleId) o.remove();
                });
                right.add(buildMainRoleOption(mainRoleId, label));
            }

            [left, right].forEach(sel => {
                const sorted = Array.from(sel.options)
                    .sort((a, b) => a.text.localeCompare(b.text, undefined, { sensitivity: 'base' }));
                sel.innerHTML = '';
                sorted.forEach(o => sel.add(o));
            });
        });

        modal.addEventListener('hidden.bs.modal', () => {
            if (lastTrigger && typeof lastTrigger.focus === 'function') lastTrigger.focus();

            right.innerHTML = ''; // kosongkan daftar privileges terpilih
            left.innerHTML  = ''; // kosongkan daftar privileges tersedia

            // Jika kamu ingin mengembalikan daftar default (role 2â€“4 misalnya),
            // kamu bisa rebuild lagi di sini.
            const defaultRoles = [
                { value: 2, text: 'Administrator/HR' },
                { value: 3, text: 'Instructor' },
                { value: 4, text: 'Learner' },
            ];

            defaultRoles.forEach(r => {
                const opt = document.createElement('option');
                opt.value = r.value;
                opt.text  = r.text;
                left.add(opt);
            });

            // Kosongkan data attributes agar tidak menyimpan data user sebelumnya
            delete modal.dataset.userId;
            delete modal.dataset.mainRoleId;
            lastTrigger = null;
        });

        applyBtn.addEventListener('click', async (e) => {
            e.preventDefault();

            const userId = modal.dataset.userId;
            if (!userId) return;

            const form  = modal.querySelector('#privForm');
            const token = form.querySelector('input[name="_token"]')?.value || '';
            const mainRoleId = parseInt(modal.dataset.mainRoleId || '', 10);

            const ids = [];
            Array.from(right.options).forEach(o => {
                const id = parseInt(o.value, 10);
                if (Number.isFinite(id) && !(o.disabled && o.dataset.main === '1')) {
                    ids.push(id);
                }
            });

            try {
                const fd = new FormData();
                fd.append('_token', token);
                fd.append('_method', 'PUT');
                ids.forEach(v => fd.append('sub_role[]', v));

                const res = await fetch(`/user-management/${userId}/roles`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: fd
                });

                const result = await res.json();
                
                localStorage.setItem('scrollPos', window.scrollY);

                // Hide modal
                bootstrap.Modal.getInstance(modal)?.hide();

                // Show alert
                const alert = document.createElement('div');
                alert.className = `alert alert-${result.ok ? 'success' : 'danger'} alert-dismissible fade show mt-3`;
                alert.role = 'alert';
                alert.innerHTML = `
                    ${result.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.querySelector('.container').prepend(alert);

                // Auto-close
                setTimeout(() => {
                    bootstrap.Alert.getOrCreateInstance(alert).close();
                }, 3000);
                
                // ======================
                // 🔥 Refresh kembali ke tempat terakhir
                // ======================
                setTimeout(() => {
                    window.location.reload();
                }, 600);

            } catch (err) {
                // console.error('Gagal menyimpan hak pengguna:', err);
            }
        });
    }
    function initUserOperationModal() {
        const modal = document.getElementById('DeleteUser');
        if (!modal) return;

        const selectAction = modal.querySelector('#confirmAction');
        const btnDelete    = modal.querySelector('#deleteUserBtn');
        const btnToggle    = modal.querySelector('#nonactiveBtn'); // activate/deactivate
        const csrf         = document.querySelector('meta[name="csrf-token"]')?.content || '';

        let openerBtn = null;

        // Helpers UI
        function resetState() {
            selectAction.value = '';
            btnDelete.disabled = true;
            btnToggle.disabled = true;
            btnToggle.textContent = 'Nonaktifkan';
        }

        function enableForSelection(val) {
            btnDelete.disabled = true;
            btnToggle.disabled = true;
            if (val === 'delete') {
            btnDelete.disabled = false;
            } else if (val === 'activate') {
            btnToggle.disabled = false;
            btnToggle.textContent = 'Aktifkan';
            } else if (val === 'deactivate') {
            btnToggle.disabled = false;
            btnToggle.textContent = 'Nonaktifkan';
            }
        }

        // Ambil status aktif dari row
        function getActiveFromRow(btn) {
            const attr = btn.getAttribute('data-active');
            if (attr !== null) return parseInt(attr, 10) === 1;
            const tr = btn.closest('tr');
            const badge = tr?.querySelector('td:nth-child(7) .badge');
            return badge?.classList.contains('bg-success') ?? false;
        }

        // Update tampilan baris setelah aksi
        function refreshRowUI(btn, { deleted, active }) {
            const tr = btn.closest('tr');
            if (!tr) return;

            if (deleted) {
            tr.remove();
            return;
            }

            // Update badge aktivasi (kolom ke-7)
            const tdStatus = tr.querySelector('td:nth-child(7)');
            if (tdStatus) {
            const span = tdStatus.querySelector('span') || document.createElement('span');
            span.className = `badge ${active ? 'bg-success' : 'bg-danger'}`;
            span.textContent = active ? 'Aktif' : 'Tidak Aktif';
            if (!span.parentNode) tdStatus.appendChild(span);
            }

            // Update data-active pada tombol aksi yg sama
            btn.setAttribute('data-active', active ? '1' : '0');
        }

        // Kirim request helper (PUT spoof) + tolerant JSON/HTML
        async function sendForm(url, method, fields = {}) {
            const fd = new FormData();
            fd.append('_token', csrf);
            fd.append('_method', method); // selalu PUT

            Object.entries(fields).forEach(([k, v]) => fd.append(k, v));

            const res = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
            });

            if (!res.ok) {
            const txt = await res.text();
            throw new Error(txt || `HTTP ${res.status}`);
            }

            const ctype = res.headers.get('content-type') || '';
            if (ctype.includes('application/json')) {
            return res.json();
            }
            // Jika bukan JSON (mis. redirect HTML), anggap sukses juga
            return {};
        }

        // Bootstrap modal events
        modal.addEventListener('show.bs.modal', (e) => {
            openerBtn = e.relatedTarget || null;
            resetState();

            const name = openerBtn?.getAttribute('data-name') || '';
            if (name) {
            const p = modal.querySelector('.modal-body p');
            if (p) p.textContent = `Apakah Anda yakin ingin menindaklanjuti pengguna "${name}"?`;
            }

            // Bisa auto-suggest berdasarkan status
            // const isActive = openerBtn ? getActiveFromRow(openerBtn) : false;
            // selectAction.value = isActive ? 'deactivate' : 'activate';
            enableForSelection(selectAction.value);
        });

        modal.addEventListener('hidden.bs.modal', () => {
            resetState();
            openerBtn = null;
        });

        selectAction.addEventListener('change', (e) => {
            enableForSelection(e.target.value);
        });

        // Klik Hapus (SOFT DELETE) -> PUT /user-management/{id}/delete
        btnDelete.addEventListener('click', async () => {
            if (!openerBtn) return;
            if (selectAction.value !== 'delete') return;

            const id = openerBtn.getAttribute('data-id');
            if (!id) return;

            try {
            await sendForm(`/user-management/${id}/delete`, 'PUT');
            refreshRowUI(openerBtn, { deleted: true });
            bootstrap.Modal.getInstance(modal)?.hide();
                const alert = document.createElement('div');
                alert.className = "alert alert-success alert-dismissible fade show mt-3";
                alert.role = "alert";
                alert.innerHTML = `
                    Pengguna berhasil <strong> dihapus </strong>.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.querySelector('.container').prepend(alert);

                setTimeout(() => {
                    bootstrap.Alert.getOrCreateInstance(alert).close();
                }, 3000);

            } catch (err) {
            // console.error('Gagal menghapus user:', err);
            }
        });

        // Klik Aktifkan / Nonaktifkan -> PUT /user-management/{id}/status
        btnToggle.addEventListener('click', async () => {
            if (!openerBtn) return;
            const val = selectAction.value;
            if (val !== 'activate' && val !== 'deactivate') return;

            const id = openerBtn.getAttribute('data-id');
            if (!id) return;

            // Will active = 1 jika pilih activate; 0 jika deactivate
            const willActive = (val === 'activate') ? 1 : 0;

            try {
                const result = await sendForm(`/user-management/${id}/status`, 'PUT', { is_active: String(willActive) });

                refreshRowUI(openerBtn, { deleted: false, active: !!willActive });
                bootstrap.Modal.getInstance(modal)?.hide();

                // ðŸ”” Success feedback (toastr or fallback alert)
                if (typeof toastr !== 'undefined') {
                    toastr.success(`Status pengguna berhasil diubah menjadi ${willActive ? 'Aktif' : 'Tidak Aktif'}`);
                } else {
                    const alert = document.createElement('div');
                    alert.className = `alert alert-success alert-dismissible fade show mt-3`;
                    alert.role = 'alert';
                    alert.innerHTML = `
                        Status pengguna berhasil diubah menjadi <strong>${willActive ? 'Aktif' : 'Tidak Aktif'}</strong>.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.querySelector('.container').prepend(alert);
                    setTimeout(() => bootstrap.Alert.getOrCreateInstance(alert).close(), 3000);
                }

            } catch (err) {
                // console.error('Gagal mengubah status user:', err);

                // ðŸ”” Error feedback
                if (typeof toastr !== 'undefined') {
                    toastr.error('Gagal mengubah status pengguna. Silakan coba lagi.');
                } else {
                    const alert = document.createElement('div');
                    alert.className = `alert alert-danger alert-dismissible fade show mt-3`;
                    alert.role = 'alert';
                    alert.innerHTML = `
                        Gagal mengubah status pengguna. Silakan coba lagi.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.querySelector('.container').prepend(alert);
                    setTimeout(() => bootstrap.Alert.getOrCreateInstance(alert).close(), 3000);
                }
            }
        });
    }
    
}
