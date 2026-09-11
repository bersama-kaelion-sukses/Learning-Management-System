export function InitAssignUser() {
    const divisionSelect    = document.getElementById('divisionSelect');
    const userSelect        = document.getElementById('userSelect');
    const assignedSelect    = document.getElementById('assignedSelect');
    const moveRightBtn      = document.getElementById('moveRight');
    const moveLeftBtn       = document.getElementById('moveLeft');
    const selectAllBtn      = document.getElementById('selectAllLearner');
    const searchUserInput   = document.getElementById('searchUserInput');    // input pencarian learner
    const searchCourseInput = document.getElementById('searchCourse');       // input pencarian kursus
    const resetBtn          = document.getElementById('resetLearner');
    const assignModal       = document.getElementById('assignModal');
    const assignForm        = document.getElementById('assignForm');
    const courseNameLabel   = document.getElementById('courseNameLabel');
    const currentLearner    = document.getElementById('currentLearner');
    const courseIdInput     = document.getElementById('courseIdInput');

    const operateModal      = document.getElementById('operateModal');

    // ✅ Safe fallback jika meta hilang
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const enrollUrl = document.querySelector('meta[name="enroll-url"]')?.content || "/course-list/assign";
    const getAssignUrl = document.querySelector('meta[name="get-assign-url"]')?.content || "/course-list/get-assign";
    const rollbackUrl  = document.querySelector('meta[name="rollback-url"]')?.content || "/course-list/rollback";

    // Filter Divisi Gabung Learner (Sprint 1)
    function filterByDivision() {
        const selectedDiv = divisionSelect?.value;
        let hiddenCount = 0;
        let visibleCount = 0;

        [...(userSelect?.options || [])].forEach(opt => {
            const division = opt.dataset.division?.trim() || "";

            // 🚫 Sembunyikan kalau tidak punya divisi valid
            if (!division || division === "null" || division === "undefined") {
                opt.hidden = true;
                opt.selected = false;
                hiddenCount++;
                return;
            }

            // ✅ Tampilkan sesuai filter
            if (selectedDiv === "all" || division === selectedDiv) {
                opt.hidden = false;
                visibleCount++;
            } else {
                opt.hidden = true;
                opt.selected = false;
                hiddenCount++;
            }
        });

    }

    // --- FILTER SEARCH USER ---
    function filterUser() {
        const search = searchUserInput?.value.toLowerCase() || "";
        [...(userSelect?.options || [])].forEach(opt => {
            const text = opt.textContent.toLowerCase();
            opt.hidden = !(search === '' || text.includes(search));
        });
    }

    // --- FILTER SEARCH COURSE ---
    function filterCourse() {
        const keyword = searchCourseInput?.value.toLowerCase().trim() || "";
        document.querySelectorAll(".course-card").forEach(card => {
            const title = card.querySelector(".course-title")?.textContent.toLowerCase() || "";
            const desc  = card.querySelector(".course-desc")?.textContent.toLowerCase() || "";
            card.style.display = (title.includes(keyword) || desc.includes(keyword)) ? "" : "none";
        });
    }

    // // --- MOVE RIGHT ---
    // moveRightBtn?.addEventListener('click', () => {
    //     [...(userSelect?.selectedOptions || [])].forEach(opt => {
    //         const exists = [...(assignedSelect?.options || [])].some(a => a.value === opt.value);
    //         if (!exists) assignedSelect?.appendChild(opt.cloneNode(true));
    //         opt.selected = false;
    //     });
    // });

    // MOVE RIGHT - Sprint 1
    moveRightBtn?.addEventListener('click', () => {
        const max = parseInt(assignModal?.getAttribute('data-max-participant'));
        const current = parseInt(currentLearner?.textContent?.match(/\d+/)?.[0] || 0, 10);
        const assignedCount = assignedSelect?.options?.length || 0;
        const selectedOpts = [...(userSelect?.selectedOptions || [])].filter(opt => !opt.hidden);

        const remainingSlots = max - (current + assignedCount);

        if (remainingSlots <= 0) {
            alert(`❌  Kapasitas Kursus sudah penuh dengan calon (${max} peserta).`);
            return;
        }

        const toAdd = selectedOpts.slice(0, remainingSlots);
        const skipped = selectedOpts.slice(remainingSlots);

        let addedCount = 0;
        toAdd.forEach(opt => {
            const exists = [...(assignedSelect?.options || [])].some(a => a.value === opt.value);
            if (!exists) {
                assignedSelect?.appendChild(opt.cloneNode(true));
                addedCount++;
            }
            opt.selected = false;
        });

        if (skipped.length > 0) {
            alert(`⚠️ Hanya ${toAdd.length} learner ditambahkan.\n${skipped.length} learner tidak masuk karena kapasitas maksimum (${max}).`);
        }
    });

    // --- MOVE LEFT ---
    moveLeftBtn?.addEventListener('click', () => {
        [...(assignedSelect?.selectedOptions || [])].forEach(opt => opt.remove());
    });

    // // --- SELECT ALL ---
    // selectAllBtn?.addEventListener('click', () => {
    //     [...(userSelect?.options || [])].forEach(opt => {
    //         if (!opt.hidden) {
    //             const exists = [...(assignedSelect?.options || [])].some(a => a.value === opt.value);
    //             if (!exists) assignedSelect?.appendChild(opt.cloneNode(true));
    //         }
    //     });
    // });

    //  Select All - Sprint 1
    selectAllBtn?.addEventListener('click', () => {
        const max = parseInt(assignModal?.getAttribute('data-max-participant'));
        const current = parseInt(currentLearner?.textContent?.match(/\d+/)?.[0] || 0, 10);
        const assignedCount = assignedSelect?.options?.length || 0;

        // ambil learner yang terlihat (tidak hidden)
        const visibleOpts = [...(userSelect?.options || [])].filter(opt => !opt.hidden);
        const notYetAssigned = visibleOpts.filter(opt =>
            ![...(assignedSelect?.options || [])].some(a => a.value === opt.value)
        );

        const remainingSlots = max - (current + assignedCount);

        if (remainingSlots <= 0) {
            alert(`❌ Kapasitas Kursus sudah penuh dengan calon (${max} peserta).`);
            return;
        }

        const toAdd = notYetAssigned.slice(0, remainingSlots);
        const skipped = notYetAssigned.slice(remainingSlots);

        let addedCount = 0;
        toAdd.forEach(opt => {
            assignedSelect?.appendChild(opt.cloneNode(true));
            addedCount++;
        });

        if (skipped.length > 0) {
            alert(`⚠️ Hanya ${toAdd.length} learner ditambahkan.\n${skipped.length} learner tidak masuk karena kapasitas maksimum (${max}).`);
        } else {
            alert(`✅ ${toAdd.length} learner berhasil ditambahkan.`);
        }
    });

    // --- LISTENERS ---
    divisionSelect?.addEventListener('change', filterByDivision);
    searchUserInput?.addEventListener('input', filterUser);
    searchCourseInput?.addEventListener('input', filterCourse);
    resetBtn?.addEventListener('click', () => { if (assignedSelect) assignedSelect.innerHTML = "" });

    // --- MODAL SHOW (Assign) ---
    assignModal?.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const courseName = button?.getAttribute('data-course-name');
        const courseId   = button?.getAttribute('data-course-id');
        const maxParticipants = button?.getAttribute('data-max-participant');

        if (courseNameLabel) courseNameLabel.textContent = courseName || "";
        if (courseIdInput) courseIdInput.value = courseId || "";

        if (assignModal && maxParticipants) {
            assignModal.setAttribute('data-max-participant', maxParticipants);
        }

        // ✅ render jumlah peserta saat ini
        fetch(getAssignUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken
            },
            body: JSON.stringify({ course_id: courseId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && currentLearner) {
                currentLearner.textContent = `${data.users.length} dari ${maxParticipants} peserta`;

                // 🚫 Sembunyikan learner yang sudah tergabung
                const enrolledIds = data.users.map(u => String(u.user_id));

                [...(userSelect?.options || [])].forEach(opt => {
                    if (enrolledIds.includes(opt.value)) {
                        opt.hidden = true;
                        opt.disabled = true;
                    } else {
                        opt.hidden = false;
                        opt.disabled = false;
                    }
                });
            }
        });
    })

    // --- SUBMIT FORM (Assign) ---
    assignForm?.addEventListener("submit", function (e) {
        e.preventDefault();

        // console.log("🟧 [AssignForm] Submit triggered");

        const courseId = courseIdInput?.value;
        const learners = [...(assignedSelect?.options || [])].map(opt => opt.value);

        // console.log("📤 Payload:", { courseId, learners });

        const payload = {
            course_id: courseId,
            user_ids: learners
        };

        fetch(enrollUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {

            // console.log("📥 Server response:", data);

            if (data.success) {
                alert(data.message);

                // ===== FORCE WINDOW RESET =====
                // console.log("🔄 Reloading window to fully reset UI state…");

                bootstrap.Modal.getInstance(assignModal).hide();

                setTimeout(() => {
                    window.location.reload();
                }, 250);

            } 
            else {
                alert("Gagal: " + (data.message ?? 'unknown error'));
            }
        })
        .catch(err => {
            // console.error("🔥 Submit error:", err);
            alert("Terjadi error saat menyimpan data.");
        });
    });

    // ✅ Fetch enrolled users saat modal Operate dibuka
    operateModal?.addEventListener("show.bs.modal", function (event) {
        const button   = event.relatedTarget;
        const courseId = button.getAttribute("data-course-id");
        document.getElementById("rollbackCourseId").value = courseId;

        // Kosongkan area dulu biar nggak ngestuck dari sebelumnya
        const enrolledContainer = document.getElementById("enrolledUsers");
        enrolledContainer.innerHTML = `<div class="text-center text-muted py-3">
            <div class="spinner-border spinner-border-sm text-secondary me-2"></div>
            Memuat data peserta...
        </div>`;

        fetch(getAssignUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken
            },
            body: JSON.stringify({ course_id: courseId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (!data.users || data.users.length === 0) {
                    // 🟡 Fallback jika tidak ada user terdaftar
                    enrolledContainer.innerHTML = `
                        <div class="text-center mb-4 mt-4">
                                Belum ada peserta terdaftar pada course ini.
                        </div>`;
                    return;
                }

                let html = '<ul class="list-group">';
                data.users.forEach(u => {
                    const isIT =
                        Number(u.role_id) === 1 ||
                        (Array.isArray(u.sub_role) && u.sub_role.includes(1));

                    // 🚫 Skip user kategori IT
                    if (isIT) return;

                    html += `
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <input type="checkbox" class="form-check-input me-2 enrolled-check fw-bold" value="${u.user_id}">
                                ${u.full_name}
                            </div>
                            <span class="badge bg-success">Tergabung</span>
                        </li>
                    `;
                });
                html += '</ul>';

                // Kalau semua user yang dikembalikan ternyata IT → juga fallback
                if (html === '<ul class="list-group"></ul>') {
                    enrolledContainer.innerHTML = `
                        <div class="alert alert-warning text-center mb-0">
                            🚫 Tidak ada peserta non-IT terdaftar.
                        </div>`;
                } else {
                    enrolledContainer.innerHTML = html;
                }

            } else {
                enrolledContainer.innerHTML = `
                    <div class="alert alert-danger text-center mb-0">
                        ⚠️ ${data.message || 'Gagal memuat data pengguna.'}
                    </div>`;
            }
        })
        .catch(err => {
            enrolledContainer.innerHTML = `
                <div class="alert alert-danger text-center mb-0">
                    ❌ Terjadi kesalahan: ${err.message}
                </div>`;
        });
    });
    // ✅ Rollback learner saat submit
    
    const selectAllCheck = document.getElementById("selectAllCheck");
    let allSelected = false;
    selectAllCheck?.addEventListener("click", function() {
        const checkboxes = document.querySelectorAll(".enrolled-check");
        if (checkboxes.length === 0) return;

        allSelected = !allSelected; // toggle

        checkboxes.forEach(cb => cb.checked = allSelected);
        selectAllCheck.textContent = allSelected ? "Batal Pilih Semua" : "Pilih Semua";
    });
    
    const rollbackForm = document.getElementById("rollbackForm");
    rollbackForm?.addEventListener("submit", function (e) {
        e.preventDefault();

        const courseId = document.getElementById("rollbackCourseId").value;
        const selected = Array.from(document.querySelectorAll(".enrolled-check:checked"))
                              .map(cb => cb.value);

        if (selected.length === 0) {
            alert("Tidak ada user dipilih.");
            return;
        }

        fetch(rollbackUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken
            },
            body: JSON.stringify({
                course_id: courseId,
                user_ids: selected
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);

                // ✅ update jumlah peserta di assign modal juga
                if (currentLearner) {
                    let currentVal = parseInt(currentLearner.textContent || "0", 10);
                    currentLearner.textContent = Math.max(currentVal - selected.length, 0);
                }

                bootstrap.Modal.getInstance(operateModal).hide();
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(err => {
            alert("Error: " + err.message);
        });
    });
}
