export function InitTrackLearner() {

    // ================================
    // 1️⃣ MODAL: Lihat Learner per Item (Debug Mode)
    // ================================
    const learnerModal = document.getElementById("learnerModal");
    if (learnerModal) {
        const learnerItemTable = document.getElementById("learnerItemTable");
        const selectedItemTitle = document.getElementById("selectedItemTitle");

        document.querySelectorAll("[data-bs-target='#learnerModal']").forEach(btn => {
            btn.addEventListener("click", async function () {
                const itemName = this.dataset.item;
                const itemId   = this.dataset.itemId;
                const courseId = this.dataset.courseId;

                // // 🔍 DEBUG 1: Pastikan data tombol benar
                // console.groupCollapsed("🧩 [DEBUG] Modal Triggered");
                // console.log("Item Name :", itemName);
                // console.log("Item ID   :", itemId);
                // console.log("Course ID :", courseId);
                // console.groupEnd();

                selectedItemTitle.textContent = `📌 ${itemName}`;
                learnerItemTable.innerHTML = `<tr><td colspan="9" class="text-center text-muted">⏳ Memuat data...</td></tr>`;

                try {
                    // 🔍 DEBUG 2: URL Fetch
                    const url = `/track-course/${courseId}/item/${itemId}/learners`;
                    // console.log("📡 Fetch URL:", url);

                    const res = await fetch(url);
                    const data = await res.json();

                    // 🔍 DEBUG 3: Cek respon
                    // console.groupCollapsed("📦 [DEBUG] API Response");
                    // console.log("Response JSON:", data);
                    // console.groupEnd();

                    learnerItemTable.innerHTML = "";

                    // 🔍 DEBUG 4: Cek kondisi utama
                    if (!data.success) {
                        // console.warn("⚠️ API gagal:", data.error || "Tidak ada pesan error");
                    }

                    if (data.success && Array.isArray(data.learners) && data.learners.length > 0) {
                        // console.log(`✅ ${data.learners.length} learner ditemukan`);
                        data.learners.forEach((learner, index) => {
                            // console.log(`👤 [${index + 1}]`, learner);

                            learnerItemTable.insertAdjacentHTML("beforeend", `
                                <tr>
                                    <td>${learner.full_name} <small class="text-muted">(${learner.emp_id})</small></td>
                                    <td>${renderCheck(learner.submit)}</td>
                                    <td>${renderCheck(!learner.submit)}</td>
                                    <td>${renderCheck(learner.passed)}</td>
                                    <td>${renderCheck(learner.not_passed)}</td>
                                    <td>${learner.grade ?? '-'}</td>
                                    <td>${learner.passing_grade ?? 'Tidak pakai Passing Grade'}</td>
                                </tr>
                            `);
                        });
                    } else {
                        // console.warn("❌ Tidak ada learner ditemukan (array kosong atau gagal parsing)");
                        learnerItemTable.innerHTML = `
                            <tr>
                                <td colspan="9" class="text-center text-muted fst-italic">
                                    Belum ada learner.
                                </td>
                            </tr>
                        `;
                    }
                } catch (err) {
                    // console.error("💥 [ERROR] Gagal load learner:", err);
                    learnerItemTable.innerHTML = `
                        <tr>
                            <td colspan="9" class="text-center text-danger">
                                ⚠️ Error memuat data learner.
                            </td>
                        </tr>
                    `;
                }
            });
        });
    }
    // ================================
    // 2️⃣ MODAL: Learner Remedial
    // ================================
    const remedialModal = document.getElementById("learnerRemedialModal");

    if (remedialModal) {
        remedialModal.addEventListener("show.bs.modal", async (event) => {

            const button = event.relatedTarget;
            const courseId = button.dataset.courseId;

            const table = document.getElementById("remedialLearnerTable");
            table.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-muted">
                        ⏳ Memuat data...
                    </td>
                </tr>
            `;

            try {
                const res = await fetch(`/track-course/${courseId}/remedial`);
                const data = await res.json();
                table.innerHTML = "";

                // Jika tidak ada learner remedial
                if (!data.success || !data.learners || data.learners.length === 0) {
                    table.innerHTML = `
                        <tr>
                            <td colspan="5" class="text-center text-muted fst-italic">
                                Belum ada learner remedial.
                            </td>
                        </tr>`;
                    return;
                }

                data.learners.forEach(l => {

                    // 🚫 SKIP jika role = 1 atau sub_role = 1
                    if (l.role_id == 1 || l.sub_role == 1) {
                        return; // ⛔ langsung skip
                    }

                    const failed = l.failed_items ?? 0;
                    const total = l.total_items ?? 0;
                    const threshold = l.threshold ?? 0;

                    let remedialAction = "";

                    if (l.consult_status === "waiting" || l.consult_status === "progress") {
                        remedialAction = `
                            <span class="text-primary fw-semibold">
                                🕒 Konsultasi Sedang Berlangsung
                            </span>`;
                    } 
                    else if (l.is_remedial) {
                        remedialAction = `
                            <a href="/approval?course_id=${courseId}&learner_id=${l.user_id}&approval_id=3"
                            class="btn btn-sm btn-outline-danger text-nowrap w-100">
                                🚨 Ajukan Konsultasi Learner
                            </a>`;
                    } 
                    else {
                        remedialAction = `
                            <span class="text-muted fst-italic">
                                Tidak memenuhi syarat remedial.
                            </span>`;
                    }

                    table.insertAdjacentHTML("beforeend", `
                        <tr>
                            <td>${l.full_name}</td>
                            <td>${l.emp_id}</td>
                            <td class="fw-semibold text-center">
                                ${failed} / ${total}
                                <br><small class="text-muted">(Batas: ${threshold})</small>
                            </td>
                            <td class="text-center">${remedialAction}</td>
                        </tr>
                    `);
                });

            } catch (err) {
                // console.error("❌ Error remedial:", err);
                table.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center text-danger">
                            ⚠️ Gagal memuat data remedial.
                        </td>
                    </tr>`;
            }
        });
    }
    // ================================
    // 3️⃣ MODAL: Progress Learner di Course
    // ================================
    // const progressModal = document.getElementById("learnerProgressModal");
    // if (progressModal) {
    //     progressModal.addEventListener("show.bs.modal", async (event) => {
    //         const button = event.relatedTarget;
    //         const courseId = button.dataset.courseId;
    //         const table = document.getElementById("progressLearnerTable");

    //         // console.log("📘 Fetching progress for course:", courseId);
    //         table.innerHTML = `<tr><td colspan="4" class="text-center text-muted">⏳ Memuat data...</td></tr>`;

    //         try {
    //             const res = await fetch(`/track-course/${courseId}/progress`);
    //             const data = await res.json();
    //             table.innerHTML = "";

    //             if (!data.success || !data.learners || data.learners.length === 0) {
    //                 table.innerHTML = `<tr><td colspan="4" class="text-center text-muted fst-italic">Tidak ada data progress.</td></tr>`;
    //                 return;
    //             }

    //             data.learners.forEach(l => {
    //                 const progress = l.progress ?? 0;
    //                 const progressColor = progress >= 80 ? "bg-success" : progress >= 50 ? "bg-warning" : "bg-secondary";
    //                 const status = progress >= 100 
    //                     ? '<span class="text-success fw-bold">✅ Selesai</span>'
    //                     : '<span class="text-muted">⏳ Belum Selesai</span>';

    //                 table.insertAdjacentHTML("beforeend", `
    //                     <tr>
    //                         <td>${l.full_name}</td>
    //                         <td>${l.emp_id}</td>
    //                         <td>
    //                             <div class="progress" style="height: 18px; width: 150px;">
    //                                 <div class="progress-bar ${progressColor}" style="width:${progress}%">${progress}%</div>
    //                             </div>
    //                         </td>
    //                         <td>${status}</td>
    //                     </tr>
    //                 `);
    //             });
    //         } catch (err) {
    //             console.error("❌ Error progress:", err);
    //             table.innerHTML = `<tr><td colspan="4" class="text-center text-danger">⚠️ Gagal memuat data progress.</td></tr>`;
    //         }
    //     });
    // }
    // ================================
    // 3️⃣ MODAL: Progress Learner di Course
    // ================================
    const progressModal = document.getElementById("learnerProgressModal");
    
    if (progressModal) {
        progressModal.addEventListener("show.bs.modal", async (event) => {
    
            const button = event.relatedTarget;
            const courseId = button.dataset.courseId;
            const table = document.getElementById("progressLearnerTable");
    
            table.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center text-muted">
                        ⏳ Memuat data...
                    </td>
                </tr>
            `;
    
            try {
                const res = await fetch(`/track-course/${courseId}/progress`);
                const data = await res.json();
    
                // console.log("📦 RAW DATA RETURN:", data);
                // console.log("📦 data.success:", data?.success);
                // console.log("📦 data.learners:", data?.learners);
                // console.log("📦 Type of learners:", typeof data?.learners);
                // console.log("📦 IsArray learners:", Array.isArray(data?.learners));
    
                // =============================================
                // 🔥 FIX: Convert object → array jika perlu
                // =============================================
                if (data.learners && !Array.isArray(data.learners)) {
                    data.learners = Object.values(data.learners);
                    // console.log("📦 learners converted to ARRAY:", data.learners);
                }
    
                table.innerHTML = "";
    
                // =============================================
                // ❌ Tidak ada data learners
                // =============================================
                if (!data.success || !data.learners || data.learners.length === 0) {
                    table.innerHTML = `
                        <tr>
                            <td colspan="4" class="text-center text-muted fst-italic">
                                Tidak ada data progress.
                            </td>
                        </tr>
                    `;
                    return;
                }
    
                // =============================================
                // ✔️ Render Data
                // =============================================
                data.learners.forEach(l => {
                    const progress = parseFloat(l.progress ?? 0);
    
                    const progressColor =
                        progress >= 80 ? "bg-success" :
                        progress >= 50 ? "bg-warning" :
                        "bg-secondary";
    
                    const status =
                        progress >= 100
                            ? '<span class="text-success fw-bold">✅ Selesai</span>'
                            : '<span class="text-muted">⏳ Belum Selesai</span>';
    
                    table.insertAdjacentHTML("beforeend", `
                        <tr>
                            <td>${l.full_name}</td>
                            <td>${l.emp_id}</td>
                            <td>
                                <div class="progress" style="height: 18px; width: 150px;">
                                    <div class="progress-bar ${progressColor}" style="width:${progress}%">
                                        ${progress}%
                                    </div>
                                </div>
                            </td>
                            <td>${status}</td>
                        </tr>
                    `);
                });
    
            } catch (err) {
                // console.error("❌ Error progress:", err);
                table.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center text-danger">
                            ⚠️ Gagal memuat data progress.
                        </td>
                    </tr>
                `;
            }
        });
    }



    // ================================
    // 🔧 Helper
    // ================================
    function renderCheck(value) {
        return value 
            ? '<span class="text-success fw-bold">✅</span>' 
            : '<span class="text-danger">✘</span>';
    }
}
