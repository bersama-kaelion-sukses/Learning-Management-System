export function InitApproval() {

    const modal = document.getElementById('approveModal');
    const approvalForm = document.getElementById('approvalForm');
    const approvalList = document.getElementById('approvalList');
    const resultTable = document.getElementById('approvalResultTable');
    const searchInput = document.getElementById('searchInput');
    const dropdown = document.getElementById('searchDropdown');
    const moveRightBtn = document.querySelector('.approval-move-right');
    const moveLeftBtn = document.querySelector('.approval-move-left');
    const approvalRadios = document.querySelectorAll('input[name="approval_type"]');
    const courseAction = document.getElementById('courseAction');
    const sections = document.querySelectorAll('.approval-extra');

    let activeUser = null;     // user di kiri (accordion)
    let activeApproval = null; // user di kanan (daftar approval)
    let currentApprovalType = '';
    
    let isSubmittingApproval = false;

    // =====================================================
    // 🧩 UTILITIES
    // =====================================================
    const currentUserId = window.currentUserId || document.getElementById('currentUserId')?.value;
    const isSuperAdminFlag = document.getElementById('isSuperAdmin')?.value;
    const approvalModeInput = document.getElementById('approvalModeInput'); // hidden input di form

    function isSuperAdmin() {
        return String(isSuperAdminFlag) === "1";
    }
    
    const filterDrafterList = document.getElementById("filterDrafter");

    filterDrafterList?.addEventListener("change", function () {
        const drafterId = this.value;
        const currentParam = new URLSearchParams(window.location.search).get("drafter_id");

        // 🔒 Jika All Drafter & memang sudah tidak ada param → STOP
        if (drafterId === "" && !currentParam) {
            return;
        }

        // 🔥 All Drafter → clean URL
        if (drafterId === "") {
            window.location.href = window.location.pathname;
            return;
        }

        // 🔁 Drafter dipilih → set param
        if (currentParam !== drafterId) {
            window.location.href =
                `${window.location.pathname}?drafter_id=${drafterId}`;
        }
    });
    
    // =====================================================
    // 🔁 RESET MODAL SAAT DIBUKA
    // =====================================================
    modal?.addEventListener('show.bs.modal', () => {
        approvalForm?.reset();
        approvalList.innerHTML = '';
        resultTable.innerHTML = '';
        dropdown.innerHTML = '';
        searchInput.value = '';
        activeUser = null;
        activeApproval = null;
        currentApprovalType = '';
    });

    // =====================================================
    // 🔍 SEARCH AUTOCOMPLETE (dengan navigasi panah & Enter)
    // =====================================================
    let activeIndex = -1; // posisi item yang sedang disorot

    searchInput?.addEventListener('keydown', function (e) {
        const results = dropdown.querySelectorAll('li[data-id]');
        const total = results.length;

        // jika tidak ada hasil, biarkan default behavior
        if (!total) return;

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                activeIndex = (activeIndex + 1) % total;
                updateActiveItem(results);
                break;

            case 'ArrowUp':
                e.preventDefault();
                activeIndex = (activeIndex - 1 + total) % total;
                updateActiveItem(results);
                break;

            case 'Enter':
                e.preventDefault();
                if (activeIndex >= 0 && activeIndex < total) {
                    const selected = results[activeIndex];
                    addToApproval(selected.dataset);
                    dropdown.style.display = 'none';
                    searchInput.value = '';
                    activeIndex = -1;
                } else {
                    const firstResult = results[0];
                    if (firstResult) {
                        addToApproval(firstResult.dataset);
                        dropdown.style.display = 'none';
                        searchInput.value = '';
                        activeIndex = -1;
                    }
                }
                break;

            case 'Escape':
                dropdown.style.display = 'none';
                activeIndex = -1;
                break;
        }
    });

    // =====================================================
    // 🔎 KEYUP → TAMPILKAN HASIL PENCARIAN
    // =====================================================
    searchInput?.addEventListener('keyup', function (e) {
        const ignoreKeys = ['ArrowUp', 'ArrowDown', 'Enter', 'Escape'];
        if (ignoreKeys.includes(e.key)) return;

        const term = this.value.toLowerCase().trim();
        dropdown.innerHTML = '';
        activeIndex = -1;

        if (!term) {
            dropdown.style.display = 'none';
            return;
        }

        const users = document.querySelectorAll('.user-item');
        const matches = Array.from(users).filter(u =>
            u.dataset.name.toLowerCase().includes(term)
        );

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
                li.dataset.division = u.dataset.division;
                dropdown.appendChild(li);
            });
        } else {
            dropdown.innerHTML = `<li class="list-group-item text-muted small">Tidak ditemukan</li>`;
            dropdown.style.display = 'block';
        }
    });

    // =====================================================
    // 🖱️ KLIK HASIL PENCARIAN
    // =====================================================
    dropdown?.addEventListener('click', function (e) {
        if (e.target.matches('li[data-id]')) {
            addToApproval(e.target.dataset);
            dropdown.style.display = 'none';
            searchInput.value = '';
            activeIndex = -1;
        }
    });

    // =====================================================
    // 🧹 KLIK LUARAN → TUTUP DROPDOWN
    // =====================================================
    document.addEventListener('click', function (e) {
        if (!dropdown.contains(e.target) && e.target !== searchInput) {
            dropdown.style.display = 'none';
            activeIndex = -1;
        }
    });

    // =====================================================
    // ✨ FUNGSI: UPDATE HIGHLIGHT
    // =====================================================
    function updateActiveItem(results) {
        results.forEach((li, idx) => {
            if (idx === activeIndex) {
                li.classList.add('bg-warning', 'text-dark');
                li.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            } else {
                li.classList.remove('bg-warning', 'text-dark');
            }
        });
    }


    // =====================================================
    // 🟨 KLIK USER KIRI → SELECT
    // =====================================================
    const userItems = document.querySelectorAll('.user-item');
    userItems.forEach(item => {
        item.addEventListener('click', () => {
            if (activeUser === item) {
                item.classList.remove('bg-warning', 'text-dark', 'border', 'border-warning');
                activeUser = null;
                return;
            }
            userItems.forEach(u => u.classList.remove('bg-warning', 'text-dark', 'border', 'border-warning'));
            item.classList.add('bg-warning', 'text-dark', 'border', 'border-warning');
            activeUser = item;
        });
    });

    // =====================================================
    // 🔘 RADIO APPROVAL TYPE
    // =====================================================
    approvalRadios.forEach(r => {
        r.addEventListener('change', function () {
            currentApprovalType = this.value;
            if (approvalModeInput) {
                approvalModeInput.value = this.value; // 🟢 isi hidden field agar terkirim otomatis
            }
            // console.log("🔘 approval_mode set to:", this.value);
        });
    });

    // =====================================================
    // ➕ TAMBAH KE DAFTAR KANAN
    // =====================================================
    moveRightBtn?.addEventListener('click', () => {
        if (!activeUser) return alert('⚠️ Pilih pengguna terlebih dahulu!');
        if (!currentApprovalType) return alert('⚠️ Pilih jenis approval (Penyetuju / Setuju) terlebih dahulu!');

        const id = activeUser.dataset.id;

        if (String(id) === String(currentUserId) && !isSuperAdmin())
            return alert('🚫 Anda tidak bisa menambahkan diri sendiri.');

        if (document.querySelector(`#approvalList li[data-id="${id}"]`))
            return alert('⚠️ Pengguna ini sudah ada di daftar persetujuan.');

        const li = document.createElement('li');
        li.className = 'list-group-item d-flex justify-content-between align-items-center py-2 px-3 selectable-approval';
        li.dataset.id = id;
        li.dataset.name = activeUser.dataset.name;
        li.dataset.empid = activeUser.dataset.empid;
        li.dataset.position = activeUser.dataset.position;
        li.dataset.type = currentApprovalType;
        li.dataset.division = activeUser.dataset.division;

        li.innerHTML = `
            <div style="font-size:0.9rem;">
                <strong>${activeUser.dataset.name}</strong><br>
                <small class=" text-muted">
                    (${activeUser.dataset.empid}) – ${activeUser.dataset.position} - ${currentApprovalType}
                </small>
            </div>
        `;
        approvalList.appendChild(li);
        activeUser.classList.remove('bg-warning', 'border', 'border-warning');
        activeUser = null;
        renderApprovalTable();
    });

    // =====================================================
    // 👈 HAPUS USER KANAN
    // =====================================================
    moveLeftBtn?.addEventListener('click', () => {
        if (activeApproval) {
            activeApproval.remove();
            activeApproval = null;
        } else {
            const lastItem = approvalList.lastElementChild;
            if (!lastItem) return alert('Tidak ada item yang dapat dihapus.');
            lastItem.remove();
        }
        renderApprovalTable();
    });

    // =====================================================
    // ✳️ KLIK USER DI KANAN
    // =====================================================
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

    // =====================================================
    // 📋 RENDER TABEL URUTAN APPROVAL (dengan kolom Divisi)
    // =====================================================
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
                    <table class="table table-sm table-bordered mb-0" style="font-size:0.95rem;">
                        <thead class="table-secondary text-center">
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Emp ID</th>
                                <th>Departemen</th>
                                <th>Jabatan</th>
                                <th>Jenis</th>
                            </tr>
                        </thead>
                        <tbody>
        `;

        items.forEach((li, i) => {
            html += `
                <tr class="text-center">
                    <td class="text-center">${i + 1}</td>
                    <td>${li.dataset.name}</td>
                    <td>${li.dataset.empid}</td>
                    <td>${li.dataset.division || '-'}</td>
                    <td>${li.dataset.position}</td>
                    <td class="text-center">${li.dataset.type}</td>
                </tr>
            `;
        });

        html += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;

        resultTable.innerHTML = html;

        // Hidden Inputs (rebuild)
        document.querySelectorAll('.hidden-approver').forEach(el => el.remove());
        items.forEach(li => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'approvers[]';
            input.value = li.dataset.id;
            input.classList.add('hidden-approver');
            approvalForm.appendChild(input);
        });
    }


    // =====================================================
    // ➕ TAMBAH DARI SEARCH
    // =====================================================
    function addToApproval(data) {
        if (!currentApprovalType) return alert('⚠️ Pilih jenis approval terlebih dahulu!');
        if (String(data.id) === String(currentUserId) && !isSuperAdmin())
            return alert('🚫 Anda tidak bisa menambahkan diri sendiri.');
        if (document.querySelector(`#approvalList li[data-id="${data.id}"]`))
            return alert('⚠️ Pengguna ini sudah ada dalam daftar.');

        const li = document.createElement('li');
        li.className = 'list-group-item d-flex justify-content-between align-items-center py-2 px-3 selectable-approval';
        li.dataset.id = data.id;
        li.dataset.name = data.name;
        li.dataset.empid = data.empid;
        li.dataset.position = data.position;
        li.dataset.type = currentApprovalType;
        li.dataset.division = data.division;

        li.innerHTML = `
            <div style="font-size:0.9rem;">
                <strong>${data.name}</strong><br>
                <small class="text-muted">
                    (${data.empid}) – ${data.division}- ${data.position} - ${currentApprovalType}
                </small>
            </div>
        `;
        approvalList.appendChild(li);
        renderApprovalTable();
    }

    // =====================================================
    // 📦 DYNAMIC FORM SECTION SWITCHER
    // =====================================================
    if (courseAction) {
        courseAction.addEventListener('change', function () {
            sections.forEach(s => {
                s.classList.add('d-none');
                s.querySelectorAll('select, input, textarea').forEach(el => el.disabled = true);
            });

            let targetId = null;
            switch (this.value) {
                case "1": targetId = 'form-course-create'; break;
                case "2": targetId = 'form-course-delete'; break;
                case "3": targetId = 'form-course-consultation'; break;
                case "4": targetId = 'form-course-publish'; break;
                case "5": targetId = 'form-course-takeover'; break;
            }

            if (targetId) {
                const target = document.getElementById(targetId);
                target?.classList.remove('d-none');
                target?.querySelectorAll('select, input, textarea').forEach(el => el.disabled = false);
            }
        });
    }
    // =====================================================
    // 📨 HANDLE SUBMIT APPROVAL FORM
    // =====================================================
    // approvalForm?.addEventListener('submit', async function (event) {
    //     event.preventDefault(); // cegah refresh bawaan browser
    //     // console.groupCollapsed("🚀 [ApprovalForm Submit Debug]");

    //     try {
    //         // console.log("✅ Step 1: Validasi dasar dijalankan");

    //         // ========================
    //         // ✅ 1. Validasi manual
    //         // ========================
    //         let valid = true;

    //         // Validasi input HTML bawaan Bootstrap
    //         if (!approvalForm.checkValidity()) {
    //             approvalForm.classList.add("was-validated");
    //             valid = false;
    //         }

    //         // Validasi daftar approver
    //         const items = approvalList.querySelectorAll('li');
    //         if (!items.length) {
    //             alert("⚠️ Minimal harus memilih satu pengguna persetujuan.");
    //             valid = false;
    //         }

    //         const hasApprover = Array.from(items).some(li => li.dataset.type === "Penyetuju");
    //         if (!hasApprover && !isSuperAdmin()) {
    //             alert("⚠️ Minimal harus ada satu 'Penyetuju'.");
    //             valid = false;
    //         }

    //         // Pastikan approval_mode ada
    //         if (!approvalModeInput.value) {
    //             alert("⚠️ Pilih jenis approval terlebih dahulu (Penyetuju/Setuju).");
    //             valid = false;
    //         }

    //         // ❌ Jika ada yang tidak valid → hentikan submit
    //         if (!valid) {
    //             // console.warn("❌ Validasi gagal, form tidak dikirim.");
    //             // console.groupEnd();
    //             return;
    //         }

    //         // ========================
    //         // 📦 2. Siapkan FormData
    //         // ========================
    //         const formData = new FormData(approvalForm);
    //         formData.delete('approval_type'); // hapus field lama

    //         // console.log("📦 Payload data siap:", Object.fromEntries(formData.entries()));

    //         // ========================
    //         // 🌐 3. Kirim ke server
    //         // ========================
    //         const res = await fetch(approvalForm.action, {
    //             method: approvalForm.method || "POST",
    //             body: formData,
    //             headers: { "X-Requested-With": "XMLHttpRequest" }
    //         });

    //         const contentType = res.headers.get("content-type");
    //         let data;
    //         if (contentType && contentType.includes("application/json")) {
    //             data = await res.json();
    //             // console.log("📬 Response JSON:", data);
    //         } else {
    //             data = await res.text();
    //             // console.log("📬 Response Text:", data);
    //         }

    //         // ========================
    //         // 🎉 4. Tangani hasil
    //         // ========================
    //         if (res.ok) {
    //             alert("✅ Permohonan berhasil dikirim!");

    //             // Tutup modal dan reset form
    //             const modalEl = document.getElementById("approveModal");
    //             const modal = bootstrap.Modal.getInstance(modalEl);
    //             if (modal) modal.hide();

    //             approvalForm.reset();
    //             approvalList.innerHTML = '';
    //             resultTable.innerHTML = '';

    //             // 🧹 Bersihkan query string agar tidak auto-prefill lagi
    //             const cleanUrl = window.location.origin + window.location.pathname;
    //             window.history.replaceState({}, document.title, cleanUrl);
    //             // console.log("🧹 URL dibersihkan setelah submit:", cleanUrl);

    //             // 🔄 Refresh halaman bersih
    //             setTimeout(() => {
    //                 window.location.href = cleanUrl;
    //             }, 800);
    //         } else {
    //             // console.warn("⚠️ Server mengembalikan error status:", res.status);
    //             alert("⚠️ Gagal mengirim data. Silakan periksa kembali input Anda.");
    //         }

    //     } catch (err) {
    //         // ========================
    //         // ❌ 5. Tangani Error
    //         // ========================
    //         // console.error("🔥 Error saat submit:", err);
    //         alert("Terjadi error jaringan atau server. Silakan coba lagi.");
    //     }

    //     // console.groupEnd();
    // });
    
    approvalForm?.addEventListener('submit', async function (event) {
        event.preventDefault(); // cegah refresh bawaan browser
        // console.groupCollapsed("🚀 [ApprovalForm Submit Debug]");

        if (isSubmittingApproval) return;
        isSubmittingApproval = true;

        const submitBtn = approvalForm.querySelector('[type="submit"]');
        const originalText = submitBtn.innerHTML;

        // Proses Lock Submit Button 
        submitBtn?.setAttribute('disabled', true);
        submitBtn?.classList.add('disabled');
        submitBtn.innerHTML = `
                <span class="spinner-border text-dark spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                Sedang Memuat...
            `;

        try {
            // console.log("✅ Step 1: Validasi dasar dijalankan");

            // ========================
            // ✅ 1. Validasi manual
            // ========================
            let valid = true;

            // Validasi input HTML bawaan Bootstrap
            if (!approvalForm.checkValidity()) {
                approvalForm.classList.add("was-validated");
                valid = false;
            }

            // Validasi daftar approver
            const items = approvalList.querySelectorAll('li');
            if (!items.length) {
                alert("⚠️ Minimal harus memilih satu pengguna persetujuan.");
                valid = false;
            }

            const hasApprover = Array.from(items).some(li => li.dataset.type === "Penyetuju");
            if (!hasApprover && !isSuperAdmin()) {
                alert("⚠️ Minimal harus ada satu 'Penyetuju'.");
                valid = false;
            }

            // Pastikan approval_mode ada
            if (!approvalModeInput.value) {
                alert("⚠️ Pilih jenis approval terlebih dahulu (Penyetuju/Setuju).");
                valid = false;
            }

            // ❌ Jika ada yang tidak valid → hentikan submit
            if (!valid) {
                isSubmittingApproval = false;
                submitBtn.innerHTML = originalText;
                submitBtn?.removeAttribute('disabled');
                submitBtn?.classList.remove('disabled');

                // console.warn("❌ Validasi gagal, form tidak dikirim.");
                // console.groupEnd();
                return;
            }

            // ========================
            // 📦 2. Siapkan FormData
            // ========================
            const formData = new FormData(approvalForm);
            formData.delete('approval_type'); // hapus field lama

            // console.log("📦 Payload data siap:", Object.fromEntries(formData.entries()));

            // ========================
            // 🌐 3. Kirim ke server
            // ========================
            const res = await fetch(approvalForm.action, {
                method: approvalForm.method || "POST",
                body: formData,
                headers: { "X-Requested-With": "XMLHttpRequest" }
            });

            const contentType = res.headers.get("content-type");
            let data;
            if (contentType && contentType.includes("application/json")) {
                data = await res.json();
                // console.log("📬 Response JSON:", data);
            } else {
                data = await res.text();
                // console.log("📬 Response Text:", data);
            }

            // ========================
            // 🎉 4. Tangani hasil
            // ========================
            if (res.ok) {
                alert("✅ Permohonan berhasil dikirim!");

                // Tutup modal dan reset form
                const modalEl = document.getElementById("approveModal");
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();

                approvalForm.reset();
                approvalList.innerHTML = '';
                resultTable.innerHTML = '';

                // 🧹 Bersihkan query string agar tidak auto-prefill lagi
                const cleanUrl = window.location.origin + window.location.pathname;
                window.history.replaceState({}, document.title, cleanUrl);
                // console.log("🧹 URL dibersihkan setelah submit:", cleanUrl);

                // 🔄 Refresh halaman bersih
                setTimeout(() => {
                    window.location.href = cleanUrl;
                }, 800);
            } else {
                // console.warn("⚠️ Server mengembalikan error status:", res.status);
                alert("⚠️ Gagal mengirim data. Silakan periksa kembali input Anda.");
            }

        } catch (err) {
            // ========================
            // ❌ 5. Tangani Error
            // ========================
            console.error("🔥 Error saat submit:", err);
            isSubmittingApproval = false;
            submitBtn.innerHTML = originalText;
            submitBtn?.removeAttribute('disabled');
            submitBtn?.classList.remove('disabled');
            alert("Terjadi error jaringan atau server. Silakan coba lagi.");
        }

        // console.groupEnd();
    });



    // ==============================
    // 🎯 KONSULTASI LEARNER
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
                        // console.error(err);
                        learnerSelect.innerHTML = '<option value="">Gagal memuat learner</option>';
                    });
            } else {
                learnerSelect.innerHTML = '<option value="">-- Pilih Learner --</option>';
            }
        });
    }

    // ==============================
    // 🧩 COURSE TAKEOVER (Payload)
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

        // console.log("Takeover Payload:", payload);

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
    // 🧭 PREFILL DARI QUERY STRING
    // ==============================
    const params = new URLSearchParams(window.location.search);
    if (!params.has("course_id")) return;

    const courseId = params.get("course_id");
    const isDelete = params.has("delete");
    const hasLearner = params.has("learner_id");

    let actionValue = null;
    if (isDelete) actionValue = "2";
    else if (hasLearner) actionValue = "3";
    else actionValue = "4";

    // console.log("🔹 Prefill detected:", { courseId, actionValue });

    // target elemen
    const modalEl = document.getElementById("approveModal");

    if (courseAction && modalEl) {
        // 🟢 set value dropdown
        courseAction.value = actionValue;

        // 🟢 pastikan opsi tampil terpilih secara visual
        Array.from(courseAction.options).forEach(opt => {
            opt.selected = (opt.value === actionValue);
        });

        // 🕒 jeda sedikit agar UI selesai render sebelum trigger change
        setTimeout(() => {
            courseAction.dispatchEvent(new Event("change"));
            // console.log("✅ Jenis Permohonan terpilih:", courseAction.options[courseAction.selectedIndex].text);
        }, 100);

        // 🕒 lanjut setelah form aktif
        setTimeout(() => {
            if (actionValue === "2") {
                // Prefill Penghapusan
                const courseSelect = document.getElementById("approval_course_delete");
                if (courseSelect) {
                    courseSelect.disabled = false;
                    courseSelect.value = courseId;
                    courseSelect.dispatchEvent(new Event("change"));
                    // console.log("✅ Prefill (delete) course_id:", courseId);
                }
            } 
            else if (actionValue === "4") {
                // Prefill Publikasi
                const publishSelect = document.getElementById("approval_course_select");
                if (publishSelect) {
                    publishSelect.disabled = false;
                    publishSelect.value = courseId;
                    publishSelect.dispatchEvent(new Event("change"));
                    // console.log("✅ Prefill (publish) course_id:", courseId);
                }
            } 
            else if (actionValue === "3") {
                // Prefill Konsultasi
                const courseSelect = document.getElementById("courseSelect");
                const learnerSelect = document.getElementById("learnerSelect");
                const learnerId = params.get("learner_id");

                if (courseSelect && learnerSelect) {
                    courseSelect.value = courseId;
                    fetch(`/approval/course/${courseId}/learners`)
                        .then(res => res.json())
                        .then(data => {
                            learnerSelect.innerHTML = '<option value="">-- Pilih Learner --</option>';
                            data.forEach(l => {
                                learnerSelect.innerHTML += `<option value="${l.id}">${l.name} (${l.emp})</option>`;
                            });
                            learnerSelect.disabled = false;
                            learnerSelect.value = learnerId;
                            // console.log("✅ Prefill (consultation) learner:", learnerId);
                        })
                        .catch(err => console.error("❌ Prefill Konsultasi gagal:", err));
                }
            }

            // 🟢 buka modal setelah prefill selesai
            const modal = new bootstrap.Modal(modalEl);
            // Jalankan prefill ulang saat modal benar-benar tampil
            modalEl.addEventListener("shown.bs.modal", () => {
                // console.log("🎨 Modal telah terbuka, memaksa UI select untuk render ulang...");

                // ✅ pastikan opsi terpilih sesuai actionValue
                Array.from(courseAction.options).forEach(opt => {
                    opt.selected = (opt.value === actionValue);
                });

                // ✅ paksa re-render tampilan select (trik visual browser)
                courseAction.style.display = "none"; // sembunyikan sejenak
                void courseAction.offsetHeight;      // paksa reflow DOM
                courseAction.style.display = "";     // tampilkan lagi

                // ✅ trigger event change ulang agar section terkait aktif
                courseAction.dispatchEvent(new Event("change"));
                // console.log("🎯 Dropdown visually updated:", courseAction.options[courseAction.selectedIndex].text);

                // 🔹 Jalankan prefill tambahan sesuai jenis permohonan
                if (actionValue === "2") {
                    // Prefill Penghapusan
                    const courseSelect = document.getElementById("approval_course_delete");
                    if (courseSelect) {
                        courseSelect.disabled = false;
                        courseSelect.value = courseId;
                        courseSelect.dispatchEvent(new Event("change"));
                        // console.log("✅ Prefill (delete) course_id:", courseId);
                    }
                } 
                else if (actionValue === "4") {
                    // Prefill Publikasi
                    const publishSelect = document.getElementById("approval_course_select");
                    if (publishSelect) {
                        publishSelect.disabled = false;
                        publishSelect.value = courseId;
                        publishSelect.dispatchEvent(new Event("change"));
                        // console.log("✅ Prefill (publish) course_id:", courseId);
                    }
                } 
                else if (actionValue === "3") {
                    // Prefill Konsultasi
                    const courseSelect = document.getElementById("courseSelect");
                    const learnerSelect = document.getElementById("learnerSelect");
                    const learnerId = params.get("learner_id");

                    if (courseSelect && learnerSelect) {
                        courseSelect.value = courseId;
                        fetch(`/approval/course/${courseId}/learners`)
                            .then(res => res.json())
                            .then(data => {
                                learnerSelect.innerHTML = '<option value="">-- Pilih Learner --</option>';
                                data.forEach(l => {
                                    learnerSelect.innerHTML += `<option value="${l.id}">${l.name} (${l.emp})</option>`;
                                });
                                learnerSelect.disabled = false;
                                learnerSelect.value = learnerId;
                                // console.log("✅ Prefill (consultation) learner:", learnerId);
                            })
                            .catch(err => console.error("❌ Prefill Konsultasi gagal:", err));
                    }
                }
            });

            modal.show();
        }, 400);
    }

    // =====================
    // Validasi berfore contionue Process
    // =====================
    const form = document.getElementById('approvalForm');

    form.querySelector('.btn-approve').addEventListener('click', function(e) {
        const confirmApprove = confirm('Apakah Anda yakin ingin MENYETUJUI permohonan ini?');
        if (!confirmApprove) e.preventDefault();
    });

    form.querySelector('.btn-reject').addEventListener('click', function(e) {
        const confirmReject = confirm('Apakah Anda yakin ingin MENOLAK permohonan ini?');
        if (!confirmReject) e.preventDefault();
    });

}
