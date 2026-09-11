export function InitApproval() {
    const modal = document.getElementById('approveModal');
    const approvalForm = document.getElementById('approvalForm');
    const approvalList = document.getElementById('approvalList');
    const resultTable = document.getElementById('approvalResultTable');
    const searchInput = document.getElementById('searchInput');
    const dropdown = document.getElementById('searchDropdown');
    const approvalSelect = document.getElementById('courseAction');
    const moveRightBtn = document.querySelector('.approval-move-right');
    const moveLeftBtn = document.querySelector('.approval-move-left');

    let activeUser = null; // user di kiri
    let activeApproval = null; // user yang sedang disorot di kanan

    // ==============================
    // 🔁 RESET MODAL SAAT DIBUKA (Fix)
    // ==============================
    modal.addEventListener('show.bs.modal', () => {
        approvalForm.reset();
        approvalList.innerHTML = '';
        resultTable.innerHTML = '';
        dropdown.innerHTML = '';
        dropdown.style.display = 'none';
        document.querySelectorAll('.approval-extra').forEach(e => e.classList.add('d-none'));
        document.querySelectorAll('.user-item').forEach(u => u.classList.remove('bg-warning', 'border', 'border-warning'));
        activeUser = null;
        activeApproval = null;
    });

    // ====================================
    // 🎛️ HANDLE JENIS PERMOHONAN (Fix)
    // ====================================
    approvalSelect?.addEventListener('change', function() {
        const value = this.value;
        document.querySelectorAll('.approval-extra').forEach(e => e.classList.add('d-none'));
        if (value == '1') document.getElementById('form-course-create').classList.remove('d-none');
        if (value == '2') document.getElementById('form-course-delete').classList.remove('d-none');
        if (value == '3') document.getElementById('form-course-consultation').classList.remove('d-none');
        if (value == '4') document.getElementById('form-course-publish').classList.remove('d-none');
        if (value == '5') document.getElementById('form-course-takeover').classList.remove('d-none');
    });

    // ======================================================
    // 🔍 SEARCH INPUT — Dropdown Autocomplete (Fix)
    // ======================================================
    searchInput?.addEventListener('keyup', function(e) {
        const term = this.value.toLowerCase().trim();
        dropdown.innerHTML = '';
        if (!term) {
            dropdown.style.display = 'none';
            return;
        }

        const users = document.querySelectorAll('.user-item');
        let matches = [];
        users.forEach(u => {
            const name = u.dataset.name.toLowerCase();
            if (name.includes(term)) matches.push(u);
        });

        if (matches.length) {
            dropdown.style.display = 'block';
            matches.slice(0, 8).forEach(u => {
                const li = document.createElement('li');
                li.className = 'list-group-item list-group-item-action';
                li.textContent = `${u.dataset.name} (${u.dataset.empid}) – ${u.dataset.position}`;
                li.dataset.id = u.dataset.id;
                li.dataset.name = u.dataset.name;
                li.dataset.empid = u.dataset.empid;
                li.dataset.position = u.dataset.position;
                dropdown.appendChild(li);
            });
        } else {
            dropdown.innerHTML = `<li class="list-group-item text-muted small">Tidak ditemukan</li>`;
            dropdown.style.display = 'block';
        }
    });

    searchInput?.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && dropdown.firstChild && dropdown.firstChild.dataset.id) {
            e.preventDefault();
            addToApproval(dropdown.firstChild.dataset);
            dropdown.style.display = 'none';
            this.value = '';
        }
    });

    dropdown?.addEventListener('click', function(e) {
        if (e.target.matches('li[data-id]')) {
            addToApproval(e.target.dataset);
            dropdown.style.display = 'none';
            searchInput.value = '';
        }
    });

    // ======================================================
    // ➕ Fungsi Tambah User ke Daftar Kanan (dipanggil dari pencarian) (Fix)
    // ======================================================
    function addToApproval(data) {
        const approvalType = document.querySelector('input[name="approval_type"]:checked');
        if (!approvalType) {
            alert('⚠️ Pilih jenis approval (Penyetuju/Setuju) terlebih dahulu!');
            return;
        }

        const id = data.id;
        const currentUserId = window.currentUserId || null;
        const isSuperAdmin = typeof window.isSuperAdmin === 'function' ? window.isSuperAdmin() : false;

        // 🚫 Tidak boleh pilih diri sendiri (kecuali Super Admin)
        if (String(id) === String(currentUserId) && !isSuperAdmin) {
            alert('🚫 Anda tidak bisa menambahkan diri sendiri ke daftar persetujuan.');
            return;
        }

        // 🚫 Cegah duplikat user
        if (document.querySelector(`#approvalList li[data-id="${id}"]`)) {
            alert('⚠️ Pengguna ini sudah ada dalam daftar persetujuan.');
            return;
        }

        // ✅ Tambahkan user ke daftar approval
        const li = document.createElement('li');
        li.className = 'list-group-item d-flex justify-content-between align-items-center py-2 px-3 selectable-approval';
        li.dataset.id = id;
        li.dataset.name = data.name;
        li.dataset.empid = data.empid;
        li.dataset.position = data.position;
        li.dataset.type = approvalType.value;

        li.innerHTML = `
            <div style="font-size:0.9rem;">
                <strong>${data.name}</strong><br>
                <small class="text-muted">
                    (${data.empid}) – ${data.position} - ${approvalType.value}
                </small>
            </div>
        `;

        approvalList.appendChild(li);
        renderApprovalTable();
    }

    // ======================================================
    // 🟨 Klik user di kiri → toggle aktif/nonaktif (Fix)
    // ======================================================
    const userItems = document.querySelectorAll('.user-item');
    userItems.forEach(item => {
        item.addEventListener('click', () => {
            // Jika diklik lagi user yang sama → deselect
            if (activeUser === item) {
                item.classList.remove('bg-warning', 'text-dark', 'border', 'border-warning');
                activeUser = null;
                return;
            }

            // Hilangkan highlight di semua user lain
            userItems.forEach(u => u.classList.remove('bg-warning', 'text-dark', 'border', 'border-warning'));

            // Tandai user baru
            item.classList.add('bg-warning', 'text-dark', 'border', 'border-warning');
            activeUser = item;
        });
    });

    // ======================================================
    // 👉 Tombol > pindahkan user aktif ke kanan (Fix)
    // ======================================================
    moveRightBtn?.addEventListener('click', () => {
        if (!activeUser) {
            alert('⚠️ Pilih pengguna terlebih dahulu!');
            return;
        }

        const approvalType = document.querySelector('input[name="approval_type"]:checked');
        if (!approvalType) {
            alert('⚠️ Pilih jenis approval (Penyetuju/Setuju) terlebih dahulu!');
            return;
        }

        const id = activeUser.dataset.id;
        const currentUserId = window.currentUserId || null;

        // 🚫 Tidak boleh pilih diri sendiri kecuali Super Admin
        if (String(id) === String(currentUserId)) {
            alert('🚫 Anda tidak bisa menambahkan diri sendiri ke daftar persetujuan.');
            return;
        }

        // 🚫 Cegah duplikat user
        if (document.querySelector(`#approvalList li[data-id="${id}"]`)) {
            alert('⚠️ Pengguna ini sudah ada dalam daftar persetujuan.');
            return;
        }

        // ✅ Tambahkan user ke daftar approval
        const li = document.createElement('li');
        li.className = 'list-group-item d-flex justify-content-between align-items-center py-2 px-3 selectable-approval';
        li.dataset.id = id;
        li.dataset.name = activeUser.dataset.name;
        li.dataset.empid = activeUser.dataset.empid;
        li.dataset.position = activeUser.dataset.position;
        li.dataset.type = approvalType.value;

        li.innerHTML = `
            <div style="font-size:0.9rem;">
                <strong>${activeUser.dataset.name}</strong><br>
                <small class="text-muted">
                    (${activeUser.dataset.empid}) – ${activeUser.dataset.position} - ${approvalType.value}
                </small>
            </div>
        `;

        approvalList.appendChild(li);

        // 🔄 Reset highlight user kiri
        activeUser.classList.remove('bg-warning', 'border', 'border-warning');
        activeUser = null;

        // 🔁 Refresh tabel urutan
        renderApprovalTable();
    });

    // ======================================================
    // 👈 Tombol < menghapus user aktif (kalau ada) atau terakhir (Fix)
    // ======================================================
    moveLeftBtn?.addEventListener('click', () => {
        if (activeApproval) {
            activeApproval.remove();
            activeApproval = null;
        } else {
            const lastItem = approvalList.lastElementChild;
            if (!lastItem) {
                alert('Tidak ada item yang dapat dihapus.');
                return;
            }
            lastItem.remove();
        }
        renderApprovalTable();
    });

    // ======================================================
    // ✳️ Klik user di kanan (Daftar Persetujuan) untuk pilih/deselect (Fix)
    // ======================================================
    approvalList.addEventListener('click', e => {
        const li = e.target.closest('.selectable-approval');
        if (!li) return;

        if (activeApproval === li) {
            li.classList.remove('bg-warning', 'border', 'border-warning');
            activeApproval = null;
        } else {
            approvalList.querySelectorAll('li').forEach(el => el.classList.remove('bg-warning', 'border', 'border-warning'));
            li.classList.add('bg-warning', 'border', 'border-warning');
            activeApproval = li;
        }
    });

    // ======================================================
    // 📋 Render Tabel Urutan Approval
    // ======================================================
    function renderApprovalTable() {
        const items = approvalList.querySelectorAll('li');
        if (!items.length) {
            resultTable.innerHTML = '';
            return;
        }

        let html = `
            <div class="card mt-3">
                <div class="card-header bg-light fw-semibold py-2">Urutan Persetujuan</div>
                <div class="card-body p-3">
                    <table class="table table-sm table-bordered mb-0" style="font-size:0.9rem;">
                        <thead class="table-secondary text-center">
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Emp ID</th>
                                <th>Jabatan</th>
                                <th>Jenis</th>
                            </tr>
                        </thead>
                        <tbody>
        `;

        items.forEach((li, i) => {
            html += `
                <tr>
                    <td class="text-center">${i + 1}</td>
                    <td>${li.dataset.name}</td>
                    <td>${li.dataset.empid}</td>
                    <td>${li.dataset.position}</td>
                    <td class="text-center">${li.dataset.type}</td>
                </tr>
            `;
        });

        html += '</tbody></table></div></div>';
        resultTable.innerHTML = html;
    }

    // ======================================================
    // 🧩 VALIDASI FORM + PREVIEW PAYLOAD (tanpa kirim ke server)
    // ======================================================
    approvalForm?.addEventListener('submit', async function (event) {
    event.preventDefault(); // 🚫 cegah refresh halaman

    console.groupCollapsed("🚀 [ApprovalForm Submit Debug]");

    try {
        // =====================================================
        // 🧾 1. Validasi dasar
        // =====================================================
        console.log("✅ Step 1: Validasi dasar dijalankan");

        let valid = true;
        const items = approvalList.querySelectorAll('li');

        if (!items.length) {
            console.warn("⚠️ Tidak ada approver di daftar!");
            alert("Minimal harus memilih satu pengguna persetujuan.");
            valid = false;
        }

        const hasApprover = Array.from(items).some(li => li.dataset.type === "Penyetuju");
        if (!hasApprover && !isSuperAdmin()) {
            console.warn("⚠️ Tidak ada penyetuju (approver) ditemukan!");
            alert("Minimal harus ada satu 'Penyetuju'.");
            valid = false;
        }

        if (!valid) {
            console.warn("❌ Validasi gagal, submit dibatalkan.");
            event.stopPropagation();
            console.groupEnd();
            return;
        }

        // =====================================================
        // 🧭 2. Copy nilai dari radio → approval_mode
        // =====================================================
        const selectedType = approvalForm.querySelector('input[name="approval_type"]:checked');
        const approvalModeInput = approvalForm.querySelector('input[name="approval_mode"]');

        if (selectedType && approvalModeInput) {
            approvalModeInput.value = selectedType.value;
            console.log(`📌 approval_mode diisi otomatis dari radio: ${selectedType.value}`);
        } else {
            console.warn("⚠️ Tidak ditemukan radio approval_type atau input approval_mode!");
        }

        // =====================================================
        // 📦 3. Bentuk FormData dan log payload
        // =====================================================
        console.log("✅ Step 3: Membentuk FormData");
        const formData = new FormData(approvalForm);
        const payload = {};

        formData.forEach((value, key) => {
            if (payload[key]) {
                if (Array.isArray(payload[key])) payload[key].push(value);
                else payload[key] = [payload[key], value];
            } else {
                payload[key] = value;
            }
        });

        console.log("📄 Action URL :", approvalForm.action);
        console.log("📮 Method     :", approvalForm.method || "POST");
        console.log("📦 Payload (FormData):", payload);

        approvalForm.classList.add("was-validated");

        // =====================================================
        // 🌐 4. Kirim ke server pakai Fetch
        // =====================================================
        console.log("🚀 Step 4: Kirim ke server...");
        const res = await fetch(approvalForm.action, {
            method: approvalForm.method || "POST",
            body: formData,
            headers: { "X-Requested-With": "XMLHttpRequest" }
        });

        console.log("📡 Response Status :", res.status, res.statusText);
        console.log("📡 Response Headers:", Object.fromEntries(res.headers.entries()));

        const contentType = res.headers.get("content-type");
        let data;
        if (contentType && contentType.includes("application/json")) {
            data = await res.json();
            console.log("📬 Response JSON:", data);
        } else {
            data = await res.text();
            console.log("📬 Response Text:", data);
        }

        // =====================================================
        // 🎉 5. Tangani hasil respon
        // =====================================================
        if (res.ok && (data.success || res.status === 200)) {
            console.log("✅ Step 5: Sukses. Tutup modal dan reset form.");
            alert("✅ Data berhasil dikirim!");

            const modalEl = document.getElementById("approveModal");
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            approvalForm.reset();
            approvalList.innerHTML = '';
            resultTable.innerHTML = '';
        } else {
            console.warn("⚠️ Step 5: Server mengembalikan error atau data.success = false");
            alert("⚠️ Gagal mengirim data. Periksa konsol untuk detail.");
        }
    } catch (err) {
        // =====================================================
        // ❌ 6. Tangani error network / parsing
        // =====================================================
        console.error("🔥 Step 6: Terjadi error fatal:", err);
        alert("Terjadi error jaringan / server. Lihat console untuk detail.");
    }

    console.groupEnd();
});



    // ==============================
    // MODAL: PROSES APPROVE / REJECT
    // ==============================
    const processModal = document.getElementById('processModal');
    if (processModal) {
        processModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const reqId = button.getAttribute('data-id');
            const action = button.getAttribute('data-action');

            const form = document.getElementById('processForm');
            if (form) {
                form.action = `/approval/${reqId}/process`;
                document.getElementById('processAction').value = action;
                document.getElementById('processTitle').textContent = `${action} Request`;
            }
        });
    }
        // ==============================
    // KONSULTASI LEARNER
    // ==============================
    const courseSelect = document.getElementById('courseSelect');
    const learnerSelect = document.getElementById('learnerSelect');

    if (courseSelect) {
        courseSelect.addEventListener('change', function () {
            const courseId = this.value;

            learnerSelect.innerHTML = '<option value="">Loading...</option>';
            learnerSelect.disabled = true;

            if (courseId) {
                fetch(`/approval/course/${courseId}/learners`)
                    .then(res => res.json())
                    .then(data => {
                        learnerSelect.innerHTML = '<option value="">-- Pilih Learner --</option>';
                        data.forEach(l => {
                            learnerSelect.innerHTML += `<option value="${l.id}">${l.name} (${l.emp})</option>`;
                        });
                        learnerSelect.disabled = false;
                    })
                    .catch(err => {
                        console.error(err);
                        learnerSelect.innerHTML = '<option value="">Gagal memuat learner</option>';
                    });
            } else {
                learnerSelect.innerHTML = '<option value="">-- Pilih Learner --</option>';
            }
        });
    }

    // ==============================
    // COURSE TAKEOVER (Payload)
    // ==============================
    const courseTakeover = document.getElementById('courseTakeover');
    const InstructorTakeoverCourse = document.getElementById('InstructorTakeoverCourse');
    const OldTrainerName = document.getElementById('OldTrainerName');
    const OldTrainerId = document.getElementById('OldTrainerId');
    const TakeoverStartDate = document.getElementById('TakeoverStartDate');
    const TakeoverEndDate = document.getElementById('TakeoverEndDate');
    const TakeoverRemarks = document.getElementById('TakeoverRemarks');

    function clearTakeoverInputs() {
        document.querySelectorAll('.hidden-takeover').forEach(el => el.remove());
        if (courseTakeover) courseTakeover.value = '';
        if (InstructorTakeoverCourse) InstructorTakeoverCourse.value = '';
        if (OldTrainerName) OldTrainerName.value = '-';
        if (OldTrainerId) OldTrainerId.value = '';
        if (TakeoverStartDate) TakeoverStartDate.value = '';
        if (TakeoverEndDate) TakeoverEndDate.value = '';
        if (TakeoverRemarks) TakeoverRemarks.value = '';
    }

    function saveTakeoverPayload() {
        document.querySelectorAll('.hidden-takeover').forEach(el => el.remove());

        const payload = {
            course_id: courseTakeover?.value || '',
            old_trainer_id: OldTrainerId?.value || '',
            new_trainer_id: InstructorTakeoverCourse?.value || '',
            start_date: TakeoverStartDate?.value || '',
            end_date: TakeoverEndDate?.value || '',
            remarks: TakeoverRemarks?.value || '',
            type: 'takeover'
        };

        console.log("Takeover Payload:", payload);

        const form = document.querySelector('#approvalForm');
        if (form && payload.course_id && payload.new_trainer_id) {
            Object.keys(payload).forEach(k => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `takeover[${k}]`;
                input.value = payload[k];
                input.classList.add('hidden-takeover');
                form.appendChild(input);
            });
        }
    }

    if (courseTakeover) {
        courseTakeover.addEventListener('change', function () {
            const selected = this.options[this.selectedIndex];
            if (selected) {
                const trainerId = selected.dataset.trainerId || '';
                const trainerName = selected.dataset.trainerName || '-';
                if (OldTrainerName) OldTrainerName.value = trainerName;
                if (OldTrainerId) OldTrainerId.value = trainerId;
            }
            saveTakeoverPayload();
        });
    }

    if (InstructorTakeoverCourse) InstructorTakeoverCourse.addEventListener('change', saveTakeoverPayload);
    if (TakeoverStartDate) TakeoverStartDate.addEventListener('change', saveTakeoverPayload);
    if (TakeoverEndDate) TakeoverEndDate.addEventListener('change', saveTakeoverPayload);
    if (TakeoverRemarks) TakeoverRemarks.addEventListener('input', saveTakeoverPayload);

    // ==============================
    // Prefill dari Query String
    // (Ajukan Pratinjau / Publikasi / Konsultasi / Penghapusan)
    // ==============================
    const params = new URLSearchParams(window.location.search);

    if (params.has("course_id") && params.has("delete")) {
        // --- Prefill Penghapusan ---
        const courseId = params.get("course_id");

        const courseAction = document.getElementById("courseAction");
        if (courseAction) {
            courseAction.value = "2"; // kode aksi penghapusan
            courseAction.dispatchEvent(new Event("change"));
        }

        const courseSelect = document.getElementById("approval_course_delete");
        if (courseSelect && courseId) {
            courseSelect.disabled = false;
            courseSelect.value = courseId;

            // trigger change agar sinkronisasi hidden berjalan
            courseSelect.dispatchEvent(new Event("change"));
            console.log("Prefill (delete) select value:", courseId);
        }

        const hiddenInput = document.getElementById("approval_course_id");
        if (hiddenInput) {
            hiddenInput.value = courseId;
            console.log("Prefill (delete) hidden value:", courseId);
        }

        // buka modal otomatis
        const modalEl = document.getElementById("approveModal");
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    }
    else if (params.has("course_id") && !params.has("learner_id")) {
        // --- Prefill Publikasi ---
        const courseId = params.get("course_id");

        const courseAction = document.getElementById("courseAction");
        if (courseAction) {
            courseAction.value = "4"; // publikasi
            courseAction.dispatchEvent(new Event("change"));
        }

        const publishSelect = document.getElementById("approval_course_select");
        if (publishSelect && courseId) {
            publishSelect.disabled = false;
            publishSelect.value = courseId;
            publishSelect.dispatchEvent(new Event("change"));
            console.log("Prefill (publish) select value:", courseId);
        }

        const hiddenInput = document.getElementById("approval_course_id");
        if (hiddenInput) {
            hiddenInput.value = courseId;
            console.log("Prefill (publish) hidden value:", courseId);
        }

        // buka modal otomatis
        const modalEl = document.getElementById("approveModal");
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    }
    else if (params.has("course_id") && params.has("learner_id")) {
        // --- Prefill Konsultasi ---
        const courseId  = params.get("course_id");
        const learnerId = params.get("learner_id");

        const courseAction = document.getElementById("courseAction");
        if (courseAction) {
            courseAction.value = "3"; // konsultasi
            courseAction.dispatchEvent(new Event("change"));
        }

        const courseSelect  = document.getElementById("courseSelect");
        const learnerSelect = document.getElementById("learnerSelect");

        if (courseSelect && learnerSelect) {
            courseSelect.value = courseId;

            // 🔥 Fetch learners dulu biar opsi muncul
            fetch(`/approval/course/${courseId}/learners`)
                .then(res => res.json())
                .then(data => {
                    learnerSelect.innerHTML = '<option value="">-- Pilih Learner --</option>';
                    data.forEach(l => {
                        learnerSelect.innerHTML += `<option value="${l.id}">${l.name} (${l.emp})</option>`;
                    });
                    learnerSelect.disabled = false;

                    // Prefill learner
                    learnerSelect.value = learnerId;
                    console.log("Prefill learner:", learnerId);
                })
                .catch(err => console.error("❌ Prefill Konsultasi gagal:", err));
        }

        // buka modal otomatis
        const modalEl = document.getElementById("approveModal");
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    }


    // ==============================
    // sinkronisasi select → hidden (khusus publikasi)
    // ==============================
    const publishSelect = document.getElementById("approval_course_select");
    if (publishSelect) {
        publishSelect.addEventListener("change", function () {
            const hiddenInput = document.getElementById("approval_course_id");
            if (hiddenInput) {
                hiddenInput.value = this.value;
                console.log("Sync hidden value:", this.value);
            }
        });
    }


 // ==============================
    // Prefill dari Query String
    // (Ajukan Pratinjau / Publikasi / Konsultasi / Penghapusan)
    // ==============================

    if (params.has("course_id") && params.has("delete")) {
        // --- Prefill Penghapusan ---
        const courseId = params.get("course_id");

        const courseAction = document.getElementById("courseAction");
        if (courseAction) {
            courseAction.value = "2"; // kode aksi penghapusan
            courseAction.dispatchEvent(new Event("change"));
        }

        const courseSelect = document.getElementById("approval_course_delete");
        if (courseSelect && courseId) {
            courseSelect.disabled = false;
            courseSelect.value = courseId;

            // trigger change agar sinkronisasi hidden berjalan
            courseSelect.dispatchEvent(new Event("change"));
            console.log("Prefill (delete) select value:", courseId);
        }

        const hiddenInput = document.getElementById("approval_course_id");
        if (hiddenInput) {
            hiddenInput.value = courseId;
            console.log("Prefill (delete) hidden value:", courseId);
        }

        // buka modal otomatis
        const modalEl = document.getElementById("approveModal");
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    }
    else if (params.has("course_id") && !params.has("learner_id")) {
        // --- Prefill Publikasi ---
        const courseId = params.get("course_id");

        const courseAction = document.getElementById("courseAction");
        if (courseAction) {
            courseAction.value = "4"; // publikasi
            courseAction.dispatchEvent(new Event("change"));
        }

        const publishSelect = document.getElementById("approval_course_select");
        if (publishSelect && courseId) {
            publishSelect.disabled = false;
            publishSelect.value = courseId;
            publishSelect.dispatchEvent(new Event("change"));
            console.log("Prefill (publish) select value:", courseId);
        }

        const hiddenInput = document.getElementById("approval_course_id");
        if (hiddenInput) {
            hiddenInput.value = courseId;
            console.log("Prefill (publish) hidden value:", courseId);
        }

        // buka modal otomatis
        const modalEl = document.getElementById("approveModal");
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    }
    else if (params.has("course_id") && params.has("learner_id")) {
        // --- Prefill Konsultasi ---
        const courseId  = params.get("course_id");
        const learnerId = params.get("learner_id");

        const courseAction = document.getElementById("courseAction");
        if (courseAction) {
            courseAction.value = "3"; // konsultasi
            courseAction.dispatchEvent(new Event("change"));
        }

        const courseSelect  = document.getElementById("courseSelect");
        const learnerSelect = document.getElementById("learnerSelect");

        if (courseSelect && learnerSelect) {
            courseSelect.value = courseId;

            // 🔥 Fetch learners dulu biar opsi muncul
            fetch(`/approval/course/${courseId}/learners`)
                .then(res => res.json())
                .then(data => {
                    learnerSelect.innerHTML = '<option value="">-- Pilih Learner --</option>';
                    data.forEach(l => {
                        learnerSelect.innerHTML += `<option value="${l.id}">${l.name} (${l.emp})</option>`;
                    });
                    learnerSelect.disabled = false;

                    // Prefill learner
                    learnerSelect.value = learnerId;
                    console.log("Prefill learner:", learnerId);
                })
                .catch(err => console.error("❌ Prefill Konsultasi gagal:", err));
        }

        // buka modal otomatis
        const modalEl = document.getElementById("approveModal");
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    }




}

