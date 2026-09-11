export function InitModifyCourse() {

    const container      = document.getElementById('materi-container');
    const addBtn         = document.getElementById('add-materi');
    const modifyModalEl  = document.getElementById('modifyModal');
    const assignModalEl  = document.getElementById('assignModal');
    const modifyModal    = new bootstrap.Modal(modifyModalEl);
    const assignModal    = new bootstrap.Modal(assignModalEl);

    let materiCount = parseInt(document.getElementById('materiCountInitial')?.value || "0");
    let THREAD_TEMPLATE = null;
    
     // ============================
    // SORT PARENT (materi-item)
    // ============================
    new Sortable(document.getElementById("materi-container"), {
        animation: 150,
        handle: ".drag-handle",
        draggable: ".materi-item",

        onEnd: () => {
            const parentOrder = [];
            document.querySelectorAll(".materi-item").forEach((el, i) => {
                parentOrder.push({
                    course_week_id: el.dataset.moduleId,
                    new_order: i + 1
                });
            });

            // console.log("🔥 PARENT ORDER:", parentOrder);

            fetch("/modify-course/course/update-week-order", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ order: parentOrder })
            })
            .then(res => res.json())
            .then(data => {

                if (data.success) {
                    // console.log("✅ Successfully saved:", data.message);
                } else {
                    // console.warn("⚠️ Save failed:", data.message);
                }

            })
            .catch(err => {
                // console.error("❌ Server error:", err);
            });
        }
    });


    // ============================
    // SORT CHILD (sub-item di dalam masing² parent)
    // ============================
    document.querySelectorAll(".sub-container").forEach(container => {
        new Sortable(container, {
            animation: 150,
            draggable: ".sub-item",

            onEnd: () => {
                const parentId = container.closest(".materi-item").dataset.moduleId;

                const childOrder = [];
                container.querySelectorAll(".sub-item").forEach((el, i) => {
                    childOrder.push({
                        course_week_id: parentId,
                        item_id: el.dataset.itemId,
                        new_order: i + 1
                    });
                });

                // console.log(`🔥 CHILD ORDER for parent ${parentId}:`, childOrder);

                // ✅ FIX: correct endpoint + correct variable
                fetch("/modify-course/course/update-item-order", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ order: childOrder }) // FIXED
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // console.log("✅ Successfully saved item order");
                    } else {
                        // console.warn("⚠️ Failed to save item order");
                    }
                })
                .catch(err => {
                    // console.error("❌ Server error:", err);
                });
            }
        });
    });
    
    // function reorderMateri() {
    //     const allMateri = container.querySelectorAll('.materi-item');
        
    //     allMateri.forEach((materi, index) => {
    //         const title = materi.querySelector('.materi-title');
    //         if (title) {
    //             title.textContent = `Bagian ${index + 1}`;
    //         }
    //         materi.dataset.order = index + 1;
    //     });

    //     materiCount = allMateri.length;
    //     // console.log("🔄 Reorder materi:", materiCount);

    //     // ✅ kasih event agar modul lain bisa dengar kalau perlu
    //     container.dispatchEvent(new CustomEvent("materiReordered", {
    //         detail: { count: materiCount }
    //     }));
    // }
    
    function reorderMateri() {
        const allMateri = container.querySelectorAll('.materi-item');

        // console.log("=== REORDER START ===");

        allMateri.forEach((materi, index) => {
            const title = materi.querySelector('.materi-title');

            // console.log(`Item ${index + 1}:`);
            // console.log(" - BEFORE textContent:", title?.textContent);
            // console.log(" - BEFORE dataset.courseWeekTitle:", materi.dataset.courseWeekTitle);

            if (title) {

                // Replace ONLY the `Bagian X` prefix,
                // keep the rest of the title intact
                title.innerHTML = title.innerHTML.replace(
                    /Bagian\s+\d+/,
                    `Bagian ${index + 1}`
                );
            }

            // Update dataset
            materi.dataset.order = index + 1;
            materi.dataset.courseWeekTitle = `Bagian ${index + 1}`;

            // console.log(" - AFTER textContent:", title?.textContent);
            // console.log(" - AFTER dataset.courseWeekTitle:", materi.dataset.courseWeekTitle);
        });

        // console.log("=== REORDER END ===");
    }

    // =============================
    // Event Delegation Materi
    // =============================

    // =============================
    // Add Materi (Course Week Module)
    // =============================
    if (addBtn) {
        addBtn.addEventListener("click", async function () {
            materiCount++;

            if (container.textContent.includes("Belum ada bagian")) {
                container.innerHTML = "";
            }

            const payload = {
                course_id: addBtn.dataset.courseId || '',
                course_week_title: `Bagian ${materiCount}`,
                course_week_visibility: 1,
                week_order: materiCount,
                course_start: null,
                course_end: null,
                is_checked: 0,
                person_process: "system"
            };

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                const res = await fetch(`/modify-course/autoSave/${payload.course_id}`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();

                if (!data.success) {
                    alert("❌ Gagal tambah bagian!");
                    return;
                }

                // ✅ re-render pakai data dari server
                const materi = document.createElement("div");
                materi.classList.add("mb-3", "border", "rounded", "bg-white", "materi-item");
                materi.dataset.order = materiCount;
                materi.dataset.moduleId = data.module.course_week_id; // ambil ID asli dari DB
                materi.dataset.courseId = data.module.course_id;

                materi.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center p-2 bg-light border-bottom border-2 border-secondary">
                        <span class="drag-handle" style="cursor: grab;">☰</span>
                        <strong class="materi-title">${data.module.course_week_title}</strong>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-success add-sub"
                                    data-bs-course-id="${data.module.course_id}"
                                    data-bs-course-week-id="${data.module.course_week_id}"
                                    data-bs-course-week-title="${data.module.course_week_title}">
                                ➕
                            </button>
                            <button class="btn btn-danger delete-btn"
                                    data-module-id="${data.module.course_week_id}">
                                🗑
                            </button>
                        </div>
                    </div>
                    <div class="p-3 text-center sub-container">
                        Belum ada sub-materi <br>
                        <button class="btn btn-outline-primary btn-sm mt-2 add-sub"
                                data-bs-course-id="${data.module.course_id}"
                                data-bs-course-week-id="${data.module.course_week_id}"
                                data-bs-course-week-title="${data.module.course_week_title}">
                            Tambah Sub Materi
                        </button>
                    </div>
                `;

                container.appendChild(materi);
                reorderMateri();

                // console.log("✅ Modul tersimpan & dirender:", data.module);

            } catch (err) {
                // console.error("Error tambah materi:", err);
            }
        });
    }
         
   document.addEventListener("click", async function (e) {
        // ================================
        // Tambah Sub Materi → Simpan ke DB
        // ================================
        if (e.target.classList.contains("add-sub")) {
            const materiItem   = e.target.closest(".materi-item");
            const subContainer = materiItem?.querySelector(".sub-container");

            const courseId        = e.target.dataset.bsCourseId || materiItem?.dataset.courseId;
            const courseWeekId    = e.target.dataset.bsCourseWeekId || materiItem?.dataset.moduleId;
            const courseWeekTitle = e.target.dataset.bsCourseWeekTitle || materiItem?.dataset.weekTitle;

            if (subContainer.textContent.includes("Belum ada sub-materi")) {
                subContainer.innerHTML = "";
            }

            const payload = {
                course_id: courseId,
                course_week_id: courseWeekId,
                course_item_name: `(${courseWeekTitle}) : Sub-materi baru`,
                course_describe: "",
                course_item_type: 0,
                course_due_start: null,
                course_due_end: null,
                course_duration: null,
                course_media: null,
                course_assignment: null,
                person_process: "system"
            };

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                const res = await fetch(`/modify-course/autoSaveItem/${courseWeekId}`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();
                if (!data.success) {
                    alert("❌ Gagal tambah sub-materi!");
                    return;
                }

                // console.log("✅ Sub tersimpan di DB:", data.item);

                // 🚀 Langsung refresh browser biar aman
                location.reload();

            } catch (err) {
                // console.error("💥 Error tambah sub:", err);
            }
        }

    // ================================
    // Edit Sub (prefill modal)
    // ================================
    if (e.target.classList.contains("modify-btn")) {
        const d = e.target.dataset;
        const form = document.getElementById("modifyCourseForm");

        // ===============================
        // 🔹 Set Action & Hidden Fields
        // ===============================
        form.action = `/modify-course/item/${d.itemId || ''}`;
        form.querySelector('input[name="_method"]').value = "PUT";

        form.querySelector("#course_id").value = d.courseId || "";
        form.querySelector("#course_week_id").value = d.courseWeekId || "";
        form.querySelector("#hiddenCourseWeekTitle").value = d.courseWeekTitle || "";

        document.getElementById("current_item_id").value = d.itemId || "";
        document.getElementById("quizItemId").value = d.itemId || "";
        document.getElementById("forumItemId").value = d.itemId || "";

        // ===============================
        // 🔹 Prefill Main Fields
        // ===============================
        form.querySelector("#course_item_name").value =
            d.courseItemName ||
            e.target.closest(".sub-item")?.querySelector(".sub-title")?.textContent.trim() ||
            "";

        form.querySelector("#courseDescription").value = d.courseDescribe || "";

        const courseTypeSelect = form.querySelector("#courseType");
        courseTypeSelect.value =
            !d.courseItemType || d.courseItemType === "null" ? "" : d.courseItemType;

        form.querySelector("#courseStart").value = d.courseDueStart || "";
        form.querySelector("#courseEnd").value = d.courseDueEnd || "";
        form.querySelector("#courseDuration").value = d.courseDuration || "";

        // ===============================
        // 🔹 Prefill Media Notes & Existing
        // ===============================
        const courseMedia = d.courseMedia || "";
        const lowerMedia = courseMedia.toLowerCase();

        const noteVideo = form.querySelector("#existingFileNoteVideo");
        const notePdf = form.querySelector("#existingFileNotePdf");
        const noteAssignment = form.querySelector("#existingAssignmentNote");
        const existingHidden = form.querySelector("#courseExistingMedia");

        [noteVideo, notePdf, noteAssignment].forEach(note => {
            if (note) {
                note.textContent = "";
                note.classList.add("d-none");
            }
        });
        existingHidden.value = "";

        // kalau ada media lama
        if (courseMedia) {
            existingHidden.value = courseMedia;

            if (/\.(mp4|mkv|avi|webm|mov)$/i.test(lowerMedia)) {
                noteVideo.textContent = "🎥 Sudah ada video yang diunggah sebelumnya.";
                noteVideo.classList.remove("d-none");
            } else if (/\.pdf$/i.test(lowerMedia)) {
                notePdf.textContent = "📄 Sudah ada file PDF yang diunggah sebelumnya.";
                notePdf.classList.remove("d-none");
                noteAssignment.textContent = "📎 Sudah ada dokumen materi (assignment) yang diunggah sebelumnya.";
                noteAssignment.classList.remove("d-none");
            }
        }

        // ===============================
        // 🔹 Prefill Media Type & URL
        // ===============================
        const courseVideoTypeSelect = form.querySelector("#CourseVideoType");
        const videoUrlInput = form.querySelector("#courseVideoUrl");
        courseVideoTypeSelect.value = "";
        videoUrlInput.value = "";

        if (courseMedia) {
            if (courseMedia.startsWith("http://") || courseMedia.startsWith("https://")) {
                courseVideoTypeSelect.value = "url";
                videoUrlInput.value = courseMedia;
            } else if (courseMedia.startsWith("assets/") || /\.(mp4|mkv|avi|webm|mov)$/i.test(lowerMedia)) {
                courseVideoTypeSelect.value = "upload";
            }
        }

        courseVideoTypeSelect.dispatchEvent(new Event("change"));
        toggleFields(d.courseItemType);

        if (d.courseItemType === "1") {
            let videoType = "";
            if (courseMedia) {
                if (courseMedia.startsWith("assets/")) videoType = "upload";
                else if (courseMedia.startsWith("http")) videoType = "url";
            }
            const courseVideoType = document.getElementById("CourseVideoType");
            courseVideoType.value = videoType;
            courseVideoType.dispatchEvent(new Event("change"));

            if (videoType === "url") document.getElementById("courseVideoUrl").value = courseMedia;
        }

        modifyModal.show();
    }
        // ------------------------
        // Essay Modal Prefill
        // ------------------------
        if (e.target.classList.contains("openEssayModal")) {
            const itemId = document.getElementById("current_item_id").value;
            const form = document.getElementById("essayForm");

            form.reset();
            document.querySelectorAll("#EssayItemModal .attachment-field").forEach(el => el.style.display = "none");

            const notePdf = document.getElementById("essayNotePdf");
            const noteVideo = document.getElementById("essayNoteVideo");
            [notePdf, noteVideo].forEach(note => {
                if (note) {
                    note.textContent = "";
                    note.classList.add("d-none");
                }
            });

            document.getElementById("essayItemId").value = itemId;
            document.getElementById("essayId").value = "";
            document.getElementById("essayExistingAttachment").value = ""; // reset dulu

            const res = await fetch(`/api/item/${itemId}/essay`);
            if (res.ok) {
                const essay = await res.json();
                if (essay) {
                    form.querySelector("#essayTitle").value = essay.essay_title ?? "";
                    form.querySelector("#essayInstruction").value = essay.instruction ?? "";
                    form.querySelector("#essayAttachmentType").value = essay.attachment_type ?? "";

                    document.getElementById("essayId").value = essay.essay_id;
                    document.getElementById("essayExistingAttachment").value = essay.attachment_value ?? "";

                    const type = essay.attachment_type;
                    const value = essay.attachment_value ?? "";

                    if (type === "pdf") {
                        document.getElementById("essayPdfWrapper").style.display = "block";
                        if (value && !value.startsWith("http")) {
                            notePdf.textContent = "📄 Sudah ada file PDF yang diunggah sebelumnya.";
                            notePdf.classList.remove("d-none");
                        }
                    }

                    if (type === "video") {
                        document.getElementById("essayVideoWrapper").style.display = "block";
                        if (value && !value.startsWith("http")) {
                            noteVideo.textContent = "🎥 Sudah ada video yang diunggah sebelumnya.";
                            noteVideo.classList.remove("d-none");
                        }
                    }
                }
            }
        }


        // ------------------------
        // Quiz Modal Prefill
        // ------------------------
        if (e.target.classList.contains("openQuizModal")) {
            const itemId = document.getElementById("quizItemId").value;
            document.getElementById("mc_item_id").value = itemId;

            const container = document.getElementById("mcQuestionsContainer");
            container.innerHTML = "";

            // console.log("📡 Fetch quiz data untuk itemId:", itemId);

            const res = await fetch(`/api/item/${itemId}/quiz`);
            if (res.ok) {
                const questions = await res.json();
                // console.log("📥 Data quiz dari server:", questions);

                if (questions.length > 0) {
                    questions.forEach((q, qIndex) => {
                        // console.log(`📝 Render Soal ${qIndex + 1}:`, q);

                        let html = `
                        <div class="mc-question border rounded p-3 mb-3" data-index="${qIndex}">
                            <input type="hidden" class="question-id" value="${q.question_id || ''}">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold">Soal ${qIndex + 1}</h6>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-question">🗑 Hapus Soal</button>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Pertanyaan <span class="text-danger">*</span></label>
                                <textarea class="form-control question-field" rows="3"
                                    name="mc_questions[${qIndex}][question]">${q.question_text ?? ""}</textarea>
                                <div class="invalid-feedback">Pertanyaan wajib diisi.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Opsi Jawaban <span class="text-danger">*</span></label>
                                <div class="mc-options-container">`;

                        q.options.forEach((opt, oIndex) => {
                            // console.log(`   ➡️ Opsi ${oIndex}:`, opt);

                            html += `
                            <div class="input-group mb-2 option-item">
                                <input type="hidden" class="option-id" value="${opt.option_id || ''}">
                                <div class="input-group-text">
                                    <input type="radio" 
                                        name="mc_questions[${qIndex}][correct]" 
                                        value="${oIndex}" 
                                        ${(opt.is_correct === true || opt.is_correct === 1 || opt.is_correct === "1") ? "checked" : ""}>
                                </div>
                                <input type="text" class="form-control option-field"
                                    name="mc_questions[${qIndex}][options][]" value="${opt.option_text ?? ""}">
                                <div class="invalid-feedback">Opsi wajib diisi.</div>
                            </div>`;
                        });

                        html += `
                                </div>
                            </div>
                        </div>`;

                        container.insertAdjacentHTML("beforeend", html);
                    });
                }
            } else {
                console.error("❌ Gagal fetch quiz:", res.status);
            }
        }


        // ------------------------
        // Forum Modal Prefill
        // ------------------------
        if (e.target.classList.contains("openForumModal")) {
            const itemId = document.getElementById("forumItemId").value;
            const form = document.querySelector("#DiscussionForumModal form") || document;

            form.reset?.();

            const res = await fetch(`/api/item/${itemId}/forum`);
            if (res.ok) {
                const forum = await res.json();
                if (forum) {
                    form.querySelector("input[name='forum_title']").value = forum.forum_title ?? "";
                    form.querySelector("textarea[name='forum_question']").value = forum.forum_question ?? "";
                    form.querySelector("select[name='forum_attachment_type']").value = forum.attachment_type ?? "";

                    // toggle wrapper
                    document.querySelectorAll("#DiscussionForumModal .attachment-field").forEach(el => el.style.display = "none");
                    if (forum.attachment_type === "url") {
                        document.getElementById("forumUrlWrapper").style.display = "block";
                        form.querySelector("input[name='forum_attachment_url']").value = forum.attachment_value ?? "";
                    } else if (forum.attachment_type === "pdf") {
                        document.getElementById("forumPdfWrapper").style.display = "block";
                    } else if (forum.attachment_type === "video") {
                        document.getElementById("forumVideoWrapper").style.display = "block";
                    }
                }
            }
        }

    });

    document.querySelectorAll('.modify-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const itemId = this.dataset.itemId;

            // ✅ set action ke route PUT
            modifyCourseForm.action = `/modify-course/item/${itemId}`;

            // ✅ isi field hidden utama
            document.getElementById('course_id').value = this.dataset.courseId;
            document.getElementById('course_week_id').value = this.dataset.courseWeekId;
            document.getElementById('hiddenCourseWeekTitle').value = this.dataset.courseWeekTitle;

            // ✅ prefill form fields
            document.getElementById('course_item_name').value = this.dataset.courseItemName || "";
            document.getElementById('courseDescription').value = this.dataset.courseDescribe || "";
            document.getElementById('courseType').value = this.dataset.courseItemType || "";
            document.getElementById('courseStart').value = this.dataset.courseDueStart || "";
            document.getElementById('courseEnd').value = this.dataset.courseDueEnd || "";
            document.getElementById('courseDuration').value = this.dataset.courseDuration || "";
            document.getElementById('CourseVideoType').value = this.dataset.courseMedia || "";
            document.getElementById('coursePassingGrade').value = this.dataset.courseGrade || "";
            
            const passingGradeInput = document.getElementById('coursePassingGrade');
            const rawGrade = this.dataset.courseGrade;
    
            // Tampilkan kosong untuk UX jika null/undefined
            passingGradeInput.value = (rawGrade === undefined || rawGrade === null || rawGrade === "null")
                ? ""
                : rawGrade;
    
            // Simpan versi number di dataset agar submit mudah
            passingGradeInput.dataset.normalizedGrade = (rawGrade === undefined || rawGrade === null || rawGrade === "null")
                ? 0
                : Number(rawGrade);
                // document.getElementById('assignedUsersJson').value = this.dataset.courseAssignment || "";
            });
    });

    // =======================
    // ------------------------
    // Delete Materi atau Sub
    // ------------------------
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('delete-btn')) {
            const target = e.target.closest('.materi-item, .sub-item');
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            if (target && confirm('Yakin hapus item ini?')) {
                // ✅ Hapus Modul
                if (target.classList.contains('materi-item')) {
                    const moduleId = target?.dataset.moduleId;
                    if (moduleId) {
                        fetch(`/modify-course/module/${moduleId}`, {
                            method: 'DELETE',
                            headers: { "X-CSRF-TOKEN": csrfToken }
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                target.remove();
                                reorderMateri();
                                alert("✅ Modul berhasil dihapus!");
                            } else {
                                alert("❌ Gagal menghapus modul!");
                            }
                        })
                    } else {
                        target.remove();
                        reorderMateri();
                    }
                }

                // ✅ Hapus Sub-Item
                if (target.classList.contains('sub-item')) {
                    const itemId = target?.dataset.itemId;
                    // console.log("🗑️ Klik hapus sub-item dengan ID:", itemId);

                    if (itemId) {
                        fetch(`/modify-course/item/${itemId}`, {
                            method: 'DELETE',
                            headers: { 
                                "X-CSRF-TOKEN": csrfToken,
                                "Accept": "application/json" // supaya server kirim JSON
                            }
                        })
                        .then(async res => {
                            // console.log("🔎 Response status:", res.status);

                            // kalau bukan 200, log raw text untuk lihat error HTML/Whoops Laravel
                            if (!res.ok) {
                                const text = await res.text();
                                // console.error("❌ Server error response:", text);
                                throw new Error("Server error");
                            }

                            return res.json();
                        })
                        .then(data => {
                            // console.log("📦 Response JSON:", data);

                            if (data.success) {
                                target.remove();
                                alert("✅ Sub-materi berhasil dihapus!");
                            } else {
                                alert("❌ Gagal menghapus sub-materi!");
                                // console.error("⚠️ Error detail:", data);
                            }
                        })
                        .catch(err => {
                            // console.error("💥 Fetch error:", err);
                            alert("❌ Terjadi error saat menghapus sub-materi!");
                        });
                    } else {
                        // console.warn("⚠️ Tidak ada itemId pada target, langsung remove element.");
                        target.remove();
                    }
                }
            }
        }
    });

    // ================================
    // Toggle Functions
    // ================================
    function toggleFields(selectedType, selectedVideoType = "") {
        const videoTypeWrapper   = document.getElementById("videoTypeWrapper");
        const fileUploadWrapper  = document.getElementById("courseFileWrapper");
        const videoUrlWrapper    = document.getElementById("videoUrlWrapper");
        const videoUploadWrapper = document.getElementById("videoUploadWrapper");
        const audioWrapper       = document.getElementById("audioFileWrapper");
        const essayWrapper       = document.querySelector(".essay-wrapper");
        const quizWrapper        = document.querySelector(".quiz-wrapper");
        const forumWrapper       = document.querySelector(".forum-wrapper");
        const assignmentWrapper  = document.querySelector(".assignment-wrapper");
        const passingGradeInput  = document.getElementById("coursePassingGrade");
        const passingGradeWrapper = passingGradeInput?.closest(".mb-3");

        const timeFields = [
            document.getElementById("courseStart")?.closest(".mb-3"),
            document.getElementById("courseEnd")?.closest(".mb-3"),
            document.getElementById("courseDuration")?.closest(".mb-3")
        ].filter(Boolean);

        const allWrappers = [
            videoTypeWrapper, fileUploadWrapper, videoUrlWrapper, videoUploadWrapper,
            audioWrapper, essayWrapper, quizWrapper, forumWrapper, assignmentWrapper
        ].filter(Boolean);

        // ✅ Default: semua sembunyi dulu
        allWrappers.forEach(w => w.classList.add("d-none"));
        timeFields.forEach(f => f.classList.add("d-none"));
        passingGradeWrapper?.classList.add("d-none"); // ⬅️ tambahkan baris ini

        // ✅ Munculkan sesuai tipe
        switch (selectedType) {
            case "1": // Video
                videoTypeWrapper?.classList.remove("d-none");
                break;
            case "2": // Dokumen
                fileUploadWrapper?.classList.remove("d-none");
                break;
            case "3": // Essay
                essayWrapper?.classList.remove("d-none");
                timeFields.forEach(f => f.classList.remove("d-none"));
                passingGradeWrapper?.classList.remove("d-none");
                break;
            case "4": // Quiz
                quizWrapper?.classList.remove("d-none");
                timeFields.forEach(f => f.classList.remove("d-none"));
                passingGradeWrapper?.classList.remove("d-none");
                break;
            case "5": // Forum
                forumWrapper?.classList.remove("d-none");
                timeFields.forEach(f => f.classList.remove("d-none"));
                break;
            case "7": // 🔥 Unggahan Tugas
                assignmentWrapper?.classList.remove("d-none");
                timeFields.forEach(f => f.classList.remove("d-none"));
                passingGradeWrapper?.classList.remove("d-none");
                break;
            case "8": // Audio
                audioWrapper?.classList.remove("d-none");
                break;
        }
    }

    // ===================================
    // Menampilkan Video List dan Category
    // ===================================
    function toggleVideoFields(selectedVideoType) {
        const videoUrlWrapper    = document.getElementById("videoUrlWrapper");
        const videoUploadWrapper = document.getElementById("videoUploadWrapper");

        if (videoUrlWrapper) videoUrlWrapper.classList.add("d-none");
        if (videoUploadWrapper) videoUploadWrapper.classList.add("d-none");

        if (selectedVideoType === "url") {
            videoUrlWrapper?.classList.remove("d-none");
        }
        if (selectedVideoType === "upload") {
            videoUploadWrapper?.classList.remove("d-none");
        }
    }
    // ================================
    // Toggle Attachment di Modal Esai
    // ================================
    function toggleEssayAttachment(type) {
        const pdfWrapper   = document.getElementById("essayPdfWrapper");
        const urlWrapper   = document.getElementById("essayUrlWrapper");
        const videoWrapper = document.getElementById("essayVideoWrapper");

        // default: hide semua
        [pdfWrapper, urlWrapper, videoWrapper].forEach(w => {
            if (w) w.style.display = "none";
        });

        // tampilkan sesuai pilihan
        if (type === "pdf")   pdfWrapper.style.display = "block";
        if (type === "url")   urlWrapper.style.display = "block";
        if (type === "video") videoWrapper.style.display = "block";
    }
    // pasang listener
    const essayAttachmentType = document.getElementById("essayAttachmentType");
    if (essayAttachmentType) {
        essayAttachmentType.addEventListener("change", function () {
            toggleEssayAttachment(this.value);
        });

        // init (saat modal dibuka kembali)
        toggleEssayAttachment(essayAttachmentType.value || "");
    }
    // ================================
    // Toggle Attachment di Modal Forum
    // ================================
    function toggleForumAttachment(type) {
        const pdfWrapper   = document.getElementById("forumPdfWrapper");
        const urlWrapper   = document.getElementById("forumUrlWrapper");
        const videoWrapper = document.getElementById("forumVideoWrapper");

        // default: hide semua
        [pdfWrapper, urlWrapper, videoWrapper].forEach(w => {
            if (w) w.style.display = "none";
        });

        // tampilkan sesuai pilihan
        if (type === "pdf")   pdfWrapper.style.display = "block";
        if (type === "url")   urlWrapper.style.display = "block";
        if (type === "video") videoWrapper.style.display = "block";
    }

    // pasang listener
    const forumAttachmentType = document.getElementById("forumAttachmentType");
    if (forumAttachmentType) {
        forumAttachmentType.addEventListener("change", function () {
            toggleForumAttachment(this.value);
        });

        // init saat modal dibuka kembali
        toggleForumAttachment(forumAttachmentType.value || "");
    }
    // ================================
    // Hide/Show Form Setup (Init)
    // ================================
    function hiddenForm() {
        const courseTypeSelect   = document.getElementById("courseType");
        const courseVideoType    = document.getElementById("CourseVideoType");
        const assignLaterCheckbox= document.getElementById("assignLaterCheckbox");
        const openAssignModalBtn = document.getElementById("openAssignModal");

        // listener perubahan
        courseTypeSelect?.addEventListener("change", function () {
            toggleFields(this.value, courseVideoType?.value || "");
        });
        courseVideoType?.addEventListener("change", function () {
            toggleVideoFields(this.value);
        });

        assignLaterCheckbox?.addEventListener("change", function () {
            openAssignModalBtn.style.display = this.checked ? "none" : "block";
        });

        // init saat halaman pertama kali load
        toggleFields(courseTypeSelect?.value || "", courseVideoType?.value || "");
    }
    hiddenForm();

    // ============================
    // Mandatory Field Checking
    // ============================
    const modifyCourseForm = document.getElementById("modifyCourseForm");
    const saveCourseBtn = document.getElementById("saveCourseBtn"); // tombol di modal-footer

    if (modifyCourseForm) {
        modifyCourseForm.addEventListener("submit", async function (e) {
            e.preventDefault();
            let isValid = true;

            // ✅ Disable tombol saat proses
            if (saveCourseBtn) {
                saveCourseBtn.disabled = true;
                saveCourseBtn.classList.add("disabled");
                saveCourseBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...`;
            }

            const existingMedia = document.getElementById("courseExistingMedia")?.value?.trim();

            // =======================
            // Basic Mandatory Fields
            // =======================
            const requiredFields = ["course_item_name", "courseDescription", "courseType"];
            requiredFields.forEach(id => {
                const el = document.getElementById(id);
                if (!el) return;
                const value = el.value?.trim();
                if (!value) {
                    el.classList.add("is-invalid");
                    isValid = false;
                } else {
                    el.classList.remove("is-invalid");
                    el.classList.add("is-valid");
                }
            });

            // =======================
            // Media Validation
            // =======================
            const videoUrlInput = document.getElementById("courseVideoUrl");
            const videoUploadInput = document.getElementById("courseVideoUpload");
            const audioFileInput = document.getElementById("courseAudio");
            const fileUploadInput = document.getElementById("courseFile");
            const assignmentInput = document.getElementById("courseAssignmentFile");

            // Helper untuk cek wrapper aktif
            const checkWrapper = (wrapperId, inputEl) => {
                const wrapper = document.getElementById(wrapperId);
                if (!wrapper) return;
                if (!wrapper.classList.contains("d-none")) {
                    if ((!inputEl.value && !inputEl.files?.length) && !existingMedia) {
                        inputEl.classList.add("is-invalid");
                        isValid = false;
                    } else inputEl.classList.remove("is-invalid");
                }
            };

            checkWrapper("videoUrlWrapper", videoUrlInput);
            checkWrapper("videoUploadWrapper", videoUploadInput);
            checkWrapper("audioFileWrapper", audioFileInput);
            checkWrapper("courseFileWrapper", fileUploadInput);

            // const assignmentWrapper = document.querySelector(".assignment-wrapper");
            // if (assignmentWrapper && !assignmentWrapper.classList.contains("d-none")) {
            //     if (!assignmentInput.files.length && !existingMedia) {
            //         assignmentInput.classList.add("is-invalid");
            //         isValid = false;
            //     } else assignmentInput.classList.remove("is-invalid");
            // }
            const assignmentWrapper = document.querySelector(".assignment-wrapper");
            if (assignmentWrapper && !assignmentWrapper.classList.contains("d-none")) {
                // Assignment tidak wajib diisi
                assignmentInput.classList.remove("is-invalid");
            }

            if (!isValid) {
                alert("⚠️ Mohon lengkapi kolom yang wajib diisi.");
                if (saveCourseBtn) {
                    saveCourseBtn.disabled = false;
                    saveCourseBtn.classList.remove("disabled");
                    saveCourseBtn.innerHTML = "Simpan Perubahan";
                }
                return;
            }

            // =======================
            // Proses Simpan
            // =======================
            const formData = new FormData(modifyCourseForm);
            const videoType = document.getElementById("CourseVideoType")?.value;

            if (videoType === "url") {
                formData.set("course_media_url", videoUrlInput.value.trim());
            } else if (videoType === "upload") {
                if (videoUploadInput.files.length) {
                    formData.set("course_media_video", videoUploadInput.files[0]);
                } else if (existingMedia) {
                    formData.set("course_existing_media", existingMedia);
                }
            } else {
                if (fileUploadInput.files.length) {
                    formData.set("course_media_file", fileUploadInput.files[0]);
                } else if (existingMedia) {
                    formData.set("course_existing_media", existingMedia);
                }
            }

            if (assignmentInput.files.length) {
                // formData.set("course_media_assignment", assignmentInput.files[0]);
                formData.set("course_assignment_file", assignmentInput.files[0]);
            } else if (existingMedia) {
                formData.set("course_existing_media", existingMedia);
            }

            // Upload dan re-enable tombol setelah selesai
            uploadWithProgress(modifyCourseForm.action, formData, (data) => {
                if (data.success) {
                    alert("✅ Perubahan berhasil disimpan!");
                    location.reload();
                } else {
                    alert("❌ Gagal menyimpan perubahan!");
                    // console.error("⚠️ Detail error:", data);
                }

                // ✅ aktifkan kembali tombol
                if (saveCourseBtn) {
                    saveCourseBtn.disabled = false;
                    saveCourseBtn.classList.remove("disabled");
                    saveCourseBtn.innerHTML = "Simpan Perubahan";
                }
            });
        });
    }

    // ===============================
    // OPEN ESSAY MODAL (set action + itemId)
    // ===============================
    document.addEventListener("click", function(e) {
        if (e.target.classList.contains("openEssayModal")) {

            const itemId = document.getElementById("current_item_id").value;
            const essayForm = document.getElementById("essayForm");

            essayForm.action = `/modify-course/item/${itemId}/essay`;
            document.getElementById("essayItemId").value = itemId;

            // console.log("➡️ EssayForm action set:", essayForm.action);
        }
    });

    // ===============================
    // MANDATORY VALIDATION + HANDLE SUBMIT + PROGRESS BAR
    // ===============================
    document.getElementById("essayForm")?.addEventListener("submit", function (e) {
        e.preventDefault();
        let isValid = true;

        // Ambil elemen form dan tombol simpan
        const form = this;
        const saveBtn = form.querySelector("button[type='submit']");

        // 🟡 Disable tombol sementara & tampilkan spinner
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.classList.add("disabled");
            saveBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...`;
        }

        const title = document.getElementById("essayTitle");
        const instruction = document.getElementById("essayInstruction");
        const attachmentType = document.getElementById("essayAttachmentType");
        const existingAttachment = document.getElementById("essayExistingAttachment")?.value?.trim();

        // =====================================
        // 🧩 1. Validasi field utama
        // =====================================
        [title, instruction].forEach(el => {
            if (!el.value.trim()) {
                el.classList.add("is-invalid");
                el.classList.remove("is-valid");
                isValid = false;
            } else {
                el.classList.remove("is-invalid");
                el.classList.add("is-valid");
            }
        });

        // =====================================
        // 🧩 2. Validasi Lampiran
        // =====================================
        if (attachmentType.value === "pdf") {
            const pdf = document.getElementById("essayAttachmentPdf");
            if (!pdf.files.length && !existingAttachment) {
                pdf.classList.add("is-invalid");
                isValid = false;
            } else {
                pdf.classList.remove("is-invalid");
                pdf.classList.add("is-valid");
            }
        }

        if (attachmentType.value === "url") {
            const url = document.getElementById("essayAttachmentUrl");
            if (!url.value.trim() && !existingAttachment) {
                url.classList.add("is-invalid");
                isValid = false;
            } else {
                url.classList.remove("is-invalid");
                url.classList.add("is-valid");
            }
        }

        if (attachmentType.value === "video") {
            const video = document.getElementById("essayAttachmentVideo");
            if (!video.files.length && !existingAttachment) {
                video.classList.add("is-invalid");
                isValid = false;
            } else {
                video.classList.remove("is-invalid");
                video.classList.add("is-valid");
            }
        }

        // =====================================
        // 🧩 3. Stop kalau invalid
        // =====================================
        if (!isValid) {
            alert("⚠️ Mohon lengkapi kolom yang wajib diisi sebelum melanjutkan.");
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.classList.remove("disabled");
                saveBtn.innerHTML = "Simpan Esai";
            }
            return;
        }

        // =====================================
        // 🧩 4. Siapkan data upload
        // =====================================
        const formData = new FormData(form);

        ["essay_id", "attachment_type", "essay_attachment_url"].forEach(key => {
            const val = formData.get(key);
            if (val === null || val === "undefined") formData.set(key, "");
        });

        if (existingAttachment) {
            const type = attachmentType.value;

            if (type === "pdf") {
                const pdf = document.getElementById("essayAttachmentPdf");
                if (!pdf.files.length) {
                    formData.set("essay_attachment_pdf_existing", existingAttachment);
                }
            }

            if (type === "video") {
                const video = document.getElementById("essayAttachmentVideo");
                if (!video.files.length) {
                    formData.set("essay_attachment_video_existing", existingAttachment);
                }
            }

            if (type === "url") {
                const url = document.getElementById("essayAttachmentUrl");
                if (!url.value.trim()) {
                    formData.set("essay_attachment_url_existing", existingAttachment);
                }
            }
        }

        // =====================================
        // 🧩 5. Progress bar setup
        // =====================================
        const wrapper = document.getElementById("uploadProgressEssay");
        const bar = document.getElementById("uploadProgressEssayBar");

        wrapper.classList.remove("d-none");
        bar.style.width = "0%";
        bar.textContent = "0%";
        bar.classList.remove("bg-success", "bg-danger");

        // =====================================
        // 🧩 6. Upload pakai XMLHttpRequest
        // =====================================
        const xhr = new XMLHttpRequest();
        xhr.open("POST", form.action, true);
        xhr.setRequestHeader("X-CSRF-TOKEN", document.querySelector('meta[name="csrf-token"]').content);

        xhr.upload.onprogress = function (e) {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                bar.style.width = percent + "%";
                bar.textContent = percent + "%";
            }
        };

        xhr.onload = function () {
            if (xhr.status === 200) {
                try {
                    const data = JSON.parse(xhr.responseText);
                    if (data.success) {
                        bar.classList.add("bg-success");
                        bar.textContent = "✅ Selesai";
                        alert(data.message);

                        const essayModal = bootstrap.Modal.getInstance(document.getElementById("EssayItemModal"));
                        const modifyModal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modifyModal"));
                        essayModal.hide();
                        modifyModal.show();

                        if (typeof loadCourseItems === "function") loadCourseItems();
                    } else {
                        bar.classList.add("bg-danger");
                        bar.textContent = "❌ Gagal";
                        alert("❌ Gagal: " + (data.message ?? "Unknown error"));
                    }
                } catch (err) {
                    bar.classList.add("bg-danger");
                    bar.textContent = "❌ Error JSON";
                    alert("❌ Terjadi error parsing response server!");
                }
            } else {
                bar.classList.add("bg-danger");
                bar.textContent = "❌ Upload Failed";
                alert("❌ Upload gagal, status " + xhr.status);
            }

            // ✅ Re-enable tombol simpan
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.classList.remove("disabled");
                saveBtn.innerHTML = "Simpan Esai";
            }
        };

        xhr.onerror = function () {
            bar.classList.add("bg-danger");
            bar.textContent = "❌ Network Error";
            alert("❌ Network error saat upload!");

            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.classList.remove("disabled");
                saveBtn.innerHTML = "Simpan Esai";
            }
        };

        // =====================================
        // 🧩 7. Kirim form
        // =====================================
        xhr.send(formData);
    });

    // ===============================
    // Mandatory + SAVE MULTIPLE CHOICE
    // ===============================
    // Original Code
    // document.getElementById("saveMcBtn")?.addEventListener("click", async function (e) {
    //     e.preventDefault();
    //     let isValid = true;

    //     const itemId = document.getElementById("mc_item_id").value;
    //     const questions = [];

    //     const questionEls = document.querySelectorAll("#mcQuestionsContainer .mc-question");
    //     // console.log("🔎 Jumlah soal di container:", questionEls.length);

    //     if (questionEls.length === 0) {
    //         alert("❌ Minimal 1 soal wajib ditambahkan.");
    //         return;
    //     }

    //     questionEls.forEach((qEl, qIndex) => {
    //         const questionField = qEl.querySelector(".question-field");
    //         const questionText = questionField?.value.trim() || "";
    //         const questionId = qEl.querySelector(".question-id")?.value || null;

    //         // console.log(`📝 Soal[${qIndex}] -> ID: ${questionId}, Text: ${questionText}`);

    //         if (!questionText) {
    //             questionField?.classList.add("is-invalid");
    //             isValid = false;
    //         } else {
    //             questionField?.classList.remove("is-invalid");
    //         }

    //         const options = [];
    //         let hasCorrect = false; // ✅ track apakah ada jawaban benar

    //         qEl.querySelectorAll(".option-item").forEach((optEl, oIndex) => {
    //             const optionId = optEl.querySelector(".option-id")?.value || null;
    //             const radio = optEl.querySelector("input[type='radio']");
    //             const textInput = optEl.querySelector(".option-field");
    //             const textValue = textInput?.value.trim() || "";

    //             const isCorrect = radio ? !!radio.checked : false;
    //             if (isCorrect) hasCorrect = true; // ✅ tandai kalau ada yang benar

    //             options.push({
    //                 option_id: optionId,
    //                 option_text: textValue || "(empty)",
    //                 is_correct: isCorrect,
    //             });

    //             // console.log(`   ➡️ Opsi[${qIndex}][${oIndex}] ->`, {
    //             //     option_id: optionId,
    //             //     option_text: textValue,
    //             //     is_correct: isCorrect,
    //             // });

    //             if (!textValue) {
    //                 textInput?.classList.add("is-invalid");
    //                 isValid = false;
    //             } else {
    //                 textInput?.classList.remove("is-invalid");
    //             }
    //         });

    //         // ✅ Validasi harus ada minimal 1 jawaban benar
    //         if (!hasCorrect) {
    //             // console.warn(`❌ Soal[${qIndex}] belum punya jawaban benar!`);
    //             alert(`❌ Soal ${qIndex + 1} belum punya jawaban yang ditandai benar!`);
    //             isValid = false;
    //         }

    //         questions.push({
    //             item_id: itemId,
    //             question_id: questionId,
    //             question_text: questionText || "(empty)",
    //             options,
    //         });
    //     });

    //     if (!isValid) {
    //         // console.warn("❌ Validasi gagal, tidak bisa kirim ke server.");
    //         return;
    //     }

    //     // console.log("📦 Payload final yang akan dikirim:", questions);

    //     try {
    //         const res = await fetch(`/modify-course/item/${itemId}/choice`, {
    //             method: "POST",
    //             headers: {
    //                 "Content-Type": "application/json",
    //                 "Accept": "application/json",
    //                 "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
    //             },
    //             body: JSON.stringify({ questions }),
    //         });

    //         const data = await res.json();
    //         // console.log("📥 Response dari server:", data);

    //         if (!res.ok || !data.success) {
    //             alert("❌ Gagal menyimpan soal: " + (data.message || "server error"));
    //             return;
    //         }

    //         alert(data.message || "✅ Soal Multiple Choice berhasil disimpan!");
    //         bootstrap.Modal.getInstance(document.getElementById("MultiplyChoiceItemModal")).hide();
    //         bootstrap.Modal.getOrCreateInstance(document.getElementById("modifyModal")).show();
    //     } catch (err) {
    //         // console.error("💥 Fetch error:", err);
    //         alert("Error: " + err.message);
    //     }
    // });
    // Modified 1.4.1
    document.getElementById("saveMcBtn")?.addEventListener("click", async function (e) {
        e.preventDefault();
    
        const saveBtn = this;
        if (saveBtn.disabled) return; // 🔥 anti double click
    
        const originalText = saveBtn.innerHTML;
    
        const setLoading = () => {
            saveBtn.disabled = true;
            saveBtn.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2"></span>
                Sedang Menyimpan...
            `;
        };
    
        const resetButton = () => {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        };
    
        let isValid = true;
    
        const itemId = document.getElementById("mc_item_id")?.value;
        if (!itemId) {
            alert("❌ Item ID tidak ditemukan.");
            return;
        }
    
        const questions = [];
        const questionEls = document.querySelectorAll("#mcQuestionsContainer .mc-question");
    
        if (questionEls.length === 0) {
            alert("❌ Minimal 1 soal wajib ditambahkan.");
            return;
        }
    
        questionEls.forEach((qEl, qIndex) => {
            const questionField = qEl.querySelector(".question-field");
            const questionText = questionField?.value.trim() || "";
            const questionId = qEl.querySelector(".question-id")?.value || null;
    
            if (!questionText) {
                questionField?.classList.add("is-invalid");
                isValid = false;
            } else {
                questionField?.classList.remove("is-invalid");
            }
    
            const options = [];
            let hasCorrect = false;
    
            qEl.querySelectorAll(".option-item").forEach((optEl) => {
                const optionId = optEl.querySelector(".option-id")?.value || null;
                const radio = optEl.querySelector("input[type='radio']");
                const textInput = optEl.querySelector(".option-field");
                const textValue = textInput?.value.trim() || "";
    
                const isCorrect = radio ? radio.checked : false;
                if (isCorrect) hasCorrect = true;
    
                if (!textValue) {
                    textInput?.classList.add("is-invalid");
                    isValid = false;
                } else {
                    textInput?.classList.remove("is-invalid");
                }
    
                options.push({
                    option_id: optionId,
                    option_text: textValue,
                    is_correct: isCorrect,
                });
            });
    
            if (!hasCorrect) {
                alert(`❌ Soal ${qIndex + 1} belum punya jawaban benar.`);
                isValid = false;
            }
    
            questions.push({
                item_id: itemId,
                question_id: questionId, // ✅ tetap kirim ID
                question_text: questionText,
                options
            });
        });
    
        if (!isValid) {
            return; // 🔥 button tidak pernah disable
        }
    
        // 🔥 hanya aktifkan loading kalau valid
        setLoading();
    
        try {
            const res = await fetch(`/modify-course/item/${itemId}/choice`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ questions }),
            });
    
            const data = await res.json();
    
            if (!res.ok || !data.success) {
                alert("❌ Gagal menyimpan soal: " + (data.message || "server error"));
                resetButton();
                return;
            }
    
            alert(data.message || "✅ Soal Multiple Choice berhasil disimpan!");
    
            bootstrap.Modal.getInstance(document.getElementById("MultiplyChoiceItemModal"))?.hide();
            bootstrap.Modal.getOrCreateInstance(document.getElementById("modifyModal"))?.show();
    
        } catch (err) {
            alert("Error: " + err.message);
        } finally {
            resetButton(); // 🔥 selalu reset
        }
    });
    
    // document.getElementById("saveMcBtn")?.addEventListener("click", async function (e) {
    //     e.preventDefault();

    //     const saveBtn = this;
    //     const originalText = saveBtn.innerHTML;

    //     // ⛔ disable button
    //     saveBtn.disabled = true;
    //     saveBtn.innerHTML = `
    //         <span class="spinner-border spinner-border-sm me-2"></span>
    //         Sedang Menyimpan...
    //     `;

    //     function resetButton() {
    //         saveBtn.disabled = false;
    //         saveBtn.innerHTML = originalText;
    //     }

    //     let isValid = true;

    //     const itemId = document.getElementById("mc_item_id").value;
    //     const questions = [];

    //     const questionEls = document.querySelectorAll("#mcQuestionsContainer .mc-question");

    //     if (questionEls.length === 0) {
    //         alert("❌ Minimal 1 soal wajib ditambahkan.");
    //         resetButton(); // ❗ FIX
    //         return;
    //     }

    //     questionEls.forEach((qEl, qIndex) => {
    //         const questionField = qEl.querySelector(".question-field");
    //         const questionText = questionField?.value.trim() || "";

    //         if (!questionText) {
    //             questionField?.classList.add("is-invalid");
    //             isValid = false;
    //         } else {
    //             questionField?.classList.remove("is-invalid");
    //         }

    //         const options = [];
    //         let hasCorrect = false;

    //         qEl.querySelectorAll(".option-item").forEach(optEl => {
    //             const radio = optEl.querySelector("input[type='radio']");
    //             const textInput = optEl.querySelector(".option-field");
    //             const textValue = textInput?.value.trim() || "";

    //             if (radio?.checked) hasCorrect = true;

    //             if (!textValue) {
    //                 textInput?.classList.add("is-invalid");
    //                 isValid = false;
    //             } else {
    //                 textInput?.classList.remove("is-invalid");
    //             }

    //             options.push({
    //                 option_text: textValue || "(empty)",
    //                 is_correct: !!radio?.checked
    //             });
    //         });

    //         if (!hasCorrect) {
    //             alert(`❌ Soal ${qIndex + 1} belum punya jawaban yang benar!`);
    //             isValid = false;
    //         }

    //         questions.push({
    //             item_id: itemId,
    //             question_text: questionText,
    //             options,
    //         });
    //     });

    //     if (!isValid) {
    //         console.warn("❌ Validasi gagal.");
    //         resetButton(); // ❗ FIX
    //         return;
    //     }

    //     try {
    //         const res = await fetch(`/modify-course/item/${itemId}/choice`, {
    //             method: "POST",
    //             headers: {
    //                 "Content-Type": "application/json",
    //                 "Accept": "application/json",
    //                 "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
    //             },
    //             body: JSON.stringify({ questions }),
    //         });

    //         const data = await res.json();

    //         if (!res.ok || !data.success) {
    //             alert("❌ Gagal menyimpan soal.");
    //             resetButton(); // ❗ FIX
    //             return;
    //         }

    //         alert("✅ Soal berhasil disimpan!");
    //         bootstrap.Modal.getInstance(
    //             document.getElementById("MultiplyChoiceItemModal")
    //         ).hide();

    //     } catch (err) {
    //         console.error(err);
    //         alert("❌ Terjadi error.");
    //     } finally {
    //         resetButton(); // ✅ selalu aman
    //     }
    // });
    // ===============================
    // VALIDASI + SIMPAN FORUM DISKUSI + PROGRESS BAR
    // ===============================
    document.getElementById("saveForumBtn")?.addEventListener("click", function (e) {
        e.preventDefault();
        let isValid = true;

        const itemId = document.getElementById("forumItemId").value; // ✅ dynamic
        const titleEl = document.querySelector(".forum-title-field");
        const questionEl = document.querySelector(".forum-question-field");
        const attachmentType = document.getElementById("forumAttachmentType").value;

        // --- Validasi Judul ---
        if (!titleEl.value.trim()) {
            titleEl.classList.add("is-invalid");
            isValid = false;
        } else {
            titleEl.classList.remove("is-invalid");
        }

        // --- Validasi Pertanyaan ---
        if (!questionEl.value.trim()) {
            questionEl.classList.add("is-invalid");
            isValid = false;
        } else {
            questionEl.classList.remove("is-invalid");
        }

        // --- Validasi attachment sesuai pilihan ---
        if (attachmentType === "pdf") {
            const pdf = document.querySelector("input[name='forum_attachment_pdf']");
            if (!pdf.files.length) {
                pdf.classList.add("is-invalid");
                isValid = false;
            }
        }
        if (attachmentType === "url") {
            const url = document.querySelector("input[name='forum_attachment_url']");
            if (!url.value.trim()) {
                url.classList.add("is-invalid");
                isValid = false;
            }
        }
        if (attachmentType === "video") {
            const video = document.querySelector("input[name='forum_attachment_video']");
            if (!video.files.length) {
                video.classList.add("is-invalid");
                isValid = false;
            }
        }

        // ❌ Stop kalau tidak valid
        if (!isValid) {
            alert("❌ Masih ada field forum yang kosong!");
            return;
        }

        // ✅ Kalau valid → kirim ke backend dengan progress bar
        const formData = new FormData();
        formData.append("forum_title", titleEl.value.trim());
        formData.append("forum_question", questionEl.value.trim());
        formData.append("attachment_type", attachmentType);

        if (attachmentType === "url") {
            const url = document.querySelector("input[name='forum_attachment_url']").value.trim();
            formData.append("forum_attachment_url", url);
        } else if (attachmentType === "pdf") {
            const file = document.querySelector("input[name='forum_attachment_pdf']").files[0];
            if (file) formData.append("forum_attachment_pdf", file);
        } else if (attachmentType === "video") {
            const file = document.querySelector("input[name='forum_attachment_video']").files[0];
            if (file) formData.append("forum_attachment_video", file);
        }

        // Progress bar refs
        const wrapper = document.getElementById("uploadProgressForum");
        const bar = document.getElementById("uploadProgressForumBar");

        wrapper.classList.remove("d-none");
        bar.style.width = "0%";
        bar.textContent = "0%";
        bar.classList.remove("bg-success", "bg-danger");

        // Pakai XMLHttpRequest biar bisa track progress
        const xhr = new XMLHttpRequest();
        xhr.open("POST", `/modify-course/${itemId}/forum-discussion`, true);
        xhr.setRequestHeader("X-CSRF-TOKEN", document.querySelector('meta[name="csrf-token"]').content);

        xhr.upload.onprogress = function (e) {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                bar.style.width = percent + "%";
                bar.textContent = percent + "%";
            }
        };

        xhr.onload = function () {
            if (xhr.status === 200) {
                try {
                    const data = JSON.parse(xhr.responseText);
                    if (data.success) {
                        bar.classList.add("bg-success");
                        bar.textContent = "✅ Selesai";
                        alert("✅ Forum berhasil disimpan!");

                        const forumModal = bootstrap.Modal.getInstance(document.getElementById("DiscussionForumModal"));
                        const modifyModal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modifyModal"));

                        forumModal.hide();
                        modifyModal.show();
                    } else {
                        bar.classList.add("bg-danger");
                        bar.textContent = "❌ Gagal";
                        alert("❌ Gagal menyimpan forum: " + (data.message || ""));
                    }
                } catch (err) {
                    // console.error("💥 JSON parse error:", err);
                    bar.classList.add("bg-danger");
                    bar.textContent = "❌ Error JSON";
                }
            } else {
                bar.classList.add("bg-danger");
                bar.textContent = "❌ Upload Failed";
            }
        };

        xhr.onerror = function () {
            bar.classList.add("bg-danger");
            bar.textContent = "❌ Network Error";
            alert("❌ Network error saat upload!");
        };

        xhr.send(formData);
    });



    // ============================
    // Setup Nested Modal
    // ============================
    function setupNestedModal(parentModal, childModalEl, childModalInstance, triggerSelector) {
        if (!childModalEl || !childModalInstance) return;

        // Open child modal → hide parent dulu
        document.querySelectorAll(triggerSelector).forEach(btn => {
            btn.addEventListener('click', () => {
                parentModal.hide();
                childModalInstance.show();
            });
        });

        // Saat child modal ditutup → buka kembali parent
        childModalEl.addEventListener('hidden.bs.modal', () => {
            parentModal.show();

            // reset semua invalid state agar gak carry over
            childModalEl.querySelectorAll(".is-invalid").forEach(el => {
                el.classList.remove("is-invalid");
            });
        });
    }

    // Init semua nested modal
    const essayModalEl = document.getElementById("EssayItemModal");
    const mcModalEl    = document.getElementById("MultiplyChoiceItemModal");
    const forumModalEl = document.getElementById("DiscussionForumModal");

    const essayModal = essayModalEl ? new bootstrap.Modal(essayModalEl) : null;
    const mcModal    = mcModalEl ? new bootstrap.Modal(mcModalEl) : null;
    const forumModal = forumModalEl ? new bootstrap.Modal(forumModalEl) : null;

    setupNestedModal(modifyModal, essayModalEl, essayModal, ".openEssayModal");
    setupNestedModal(modifyModal, mcModalEl, mcModal, ".openQuizModal");
    setupNestedModal(modifyModal, forumModalEl, forumModal, ".openForumModal");

    // ============================
    // Reset Validation saat Modal Ditutup
    // ============================
    [modifyModalEl, essayModalEl, mcModalEl, forumModalEl].forEach(modalEl => {
        if (!modalEl) return;
        modalEl.addEventListener("hidden.bs.modal", () => {
            modalEl.querySelectorAll(".is-invalid, .is-valid").forEach(el => {
                el.classList.remove("is-invalid", "is-valid");
            });
        });
    });

    // ============================
    // Multiply Choice 
    // ============================

    const questionsContainer = document.getElementById("mcQuestionsContainer");
    const addQuestionBtn = document.getElementById("addMcQuestion");

    addQuestionBtn?.addEventListener("click", () => {
        const index = questionsContainer.querySelectorAll(".mc-question").length;

        const questionDiv = document.createElement("div");
        questionDiv.className = "mc-question border rounded p-3 mb-3";
        questionDiv.dataset.index = index;
        questionDiv.innerHTML = `
            <input type="hidden" class="question-id" value="">

            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold">Soal ${index + 1}</h6>
                <button type="button" class="btn btn-sm btn-outline-danger remove-question">🗑 Hapus Soal</button>
            </div>   
            
            <!-- Pertanyaan -->
            <div class="mb-3">
                <label class="form-label">Pertanyaan <span class="text-danger">*</span></label>
                <textarea class="form-control question-field" rows="3" 
                    name="mc_questions[${index}][question]" placeholder="Tulis pertanyaan di sini..."></textarea>
                <div class="invalid-feedback">Pertanyaan wajib diisi.</div>
            </div>

            <!-- Opsi Jawaban -->
            <div class="mb-3">
                <label class="form-label">Opsi Jawaban <span class="text-danger">*</span></label>
                <div class="mc-options-container">

                   ${["A", "B", "C", "D"].map((letter, i) => `
                    <div class="input-group mb-2 option-item">
                        <input type="hidden" class="option-id" value="">
                        <div class="input-group-text">
                            <input type="radio" name="mc_questions[${index}][correct]" value="${i}">
                        </div>
                        <input type="text" class="form-control option-field" 
                            name="mc_questions[${index}][options][]" placeholder="Opsi ${letter}">
                        <div class="invalid-feedback">Opsi wajib diisi.</div>
                    </div>
                `).join("")}
                </div>
            </div>
        `;
        questionsContainer.appendChild(questionDiv);
    });

    // --- Delegasi Event (tambah opsi / hapus soal) ---
    document.addEventListener("click", (e) => {
        // Tambah opsi baru
        if (e.target.classList.contains("add-option")) {
            const questionDiv = e.target.closest(".mc-question");
            const index = questionDiv.dataset.index;
            const optionsContainer = questionDiv.querySelector(".mc-options-container");
            const optionCount = optionsContainer.querySelectorAll(".option-item").length;

            const optionDiv = document.createElement("div");
            optionDiv.className = "input-group mb-2 option-item";
            optionDiv.innerHTML = `
                <div class="input-group-text">
                    <input type="radio" name="mc_questions[${index}][correct]" value="${optionCount}">
                </div>
                <input type="text" class="form-control" name="mc_questions[${index}][options][]" placeholder="Opsi ${String.fromCharCode(65 + optionCount)}">
            `;
            optionsContainer.appendChild(optionDiv);
        }

        // Hapus soal
        if (e.target.classList.contains("remove-question")) {
            const questionDiv = e.target.closest(".mc-question");
            const questionId = questionDiv.querySelector(".question-id")?.value || null;
            const itemId = document.getElementById("mc_item_id").value;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            // Fungsi re-index ulang
            const reindexQuestions = () => {
                document.querySelectorAll(".mc-question").forEach((qEl, newIndex) => {
                    qEl.dataset.index = newIndex;
                    const title = qEl.querySelector("h6");
                    if (title) title.textContent = `Soal ${newIndex + 1}`;

                    // Update semua name input (textarea, radio, opsi)
                    const textarea = qEl.querySelector("textarea");
                    if (textarea) textarea.name = `mc_questions[${newIndex}][question]`;

                    qEl.querySelectorAll(".option-item").forEach((optEl, optIndex) => {
                        const radio = optEl.querySelector("input[type=radio]");
                        const textInput = optEl.querySelector("input[type=text]");

                        if (radio) {
                            radio.name = `mc_questions[${newIndex}][correct]`;
                            radio.value = optIndex;
                        }
                        if (textInput) {
                            textInput.name = `mc_questions[${newIndex}][options][]`;
                        }
                    });
                });
            };

            if (questionId) {
                // 🔥 Soal lama → hapus dari server
                if (confirm("Yakin hapus soal ini dari database?")) {
                    fetch(`/modify-course/item/question/${questionId}`, {
                        method: "DELETE",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": csrfToken
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            alert("✅ Soal berhasil dihapus!");
                            questionDiv.remove();
                            reindexQuestions();
                        } else {
                            alert("❌ Gagal menghapus soal: " + (data.message || ""));
                        }
                    });
                }
            } else {
                // 🆕 Soal baru (belum ada di DB) → cukup hapus front-end
                questionDiv.remove();
                reindexQuestions();
            }
        }
    });
    
    function updateSaveBtnState() {
        const blocks = document.querySelectorAll(".Thread_FormBlock");
        let hasValue = false;

        blocks.forEach(block => {

            // ========== CHECK FIELD BASIC ==========
            const title = block.querySelector("input[name='Thread_topic_title[]']")?.value.trim();
            const forumTitle = block.querySelector("input[name='Thread_forum_title[]']")?.value.trim();
            const question = block.querySelector("textarea[name='Thread_forum_question[]']")?.value.trim();

            // ========== CHECK ATTACHMENT ==========
            const attachType = block.querySelector("select[name='Thread_forum_attachment_type[]']")?.value;
            const pdf = block.querySelector("input[name='Thread_forum_attachment_pdf[]']");
            const video = block.querySelector("input[name='Thread_forum_attachment_video[]']");
            const url = block.querySelector("input[name='Thread_forum_attachment_url[]']");
            const existing = block.querySelector(".Thread_ExistingMedia")?.value.trim();

            let attachmentFilled = false;

            if (attachType === "pdf" && (pdf?.files.length > 0 || existing)) {
                attachmentFilled = true;
            }

            if (attachType === "video" && (video?.files.length > 0 || existing)) {
                attachmentFilled = true;
            }

            if (attachType === "url" && (url?.value.trim() || existing)) {
                attachmentFilled = true;
            }

            // ========== APAKAH FORM INI PUNYA VALUE? ==========
            if (title || forumTitle || question || attachmentFilled) {
                hasValue = true;
            }
        });

    // ========== TOGGLE SAVE BUTTON ==========
    if (hasValue) {
        saveBtnThread.disabled = false;
        saveBtnThread.classList.remove("disabled");
    } else {
        saveBtnThread.disabled = true;
        saveBtnThread.classList.add("disabled");
    }

    // console.log("🔄 updateSaveBtnState() →", hasValue ? "ADA DATA" : "KOSONG"); 
    }

    async function loadExistingThreads(courseId) {
        
        const list = document.getElementById("Thread_FormList");

        try {
            // Ambil data thread dari backend
            const res = await fetch(`/modify-course/forum/get-threads/${courseId}`);
            const result = await res.json();

            // console.log("Data Thread dari Server: ", result);
            // console.log("List Thread :", result.data)

            // Simpan template sebelum clear list
            if (!THREAD_TEMPLATE) {
                THREAD_TEMPLATE = document.querySelector(".Thread_FormBlock")?.cloneNode(true);
            }

            // const template = document.querySelector(".Thread_FormBlock")?.cloneNode(true);
            list.innerHTML = "";

            if (!THREAD_TEMPLATE) {
                // console.error("❌ Template Thread_FormBlock tidak ditemukan di DOM!");
                alert("Template Thread tidak ditemukan. Silakan refresh halaman.");
                return;
            }

            // Jika belum ada thread di DB
            if (!result.success || !result.data.length) {
                const block = THREAD_TEMPLATE.cloneNode(true);
                list.appendChild(block);
                return;
            }

            // console.log(`📥 Memuat ${result.data.length} thread dari server...`);

            // Loop setiap thread sesuai urutan sequence
            result.data.forEach((thread, i) => {
                const index = i + 1;
                // console.log(`🟡 Thread ke-${index}:`, thread);

                const base = THREAD_TEMPLATE.cloneNode(true);

                // ===============================
                // 🟡 Isi data dasar
                // ===============================
                base.querySelector("h6").textContent = `🟡 Diskusi ${index}`;
                base.querySelector("input[name='Thread_topic_title[]']").value = thread.topic_title || "";
                base.querySelector("input[name='Thread_forum_title[]']").value = thread.forum_title || "";
                base.querySelector("textarea[name='Thread_forum_question[]']").value = thread.forum_question || "";

                // ===============================
                // 🆔 Hidden ID & Sequence
                // ===============================
                const seqInput = base.querySelector("input[name='thread_seq']");
                if (seqInput) seqInput.value = thread.thread_seq || index;

                const idInput = base.querySelector("input[name='thread_id']");
                if (idInput) idInput.value = thread.thread_id || "";

                // ===============================
                // 📎 Set tipe lampiran
                // ===============================
                const attachSelect = base.querySelector(".Thread_AttachType");
                attachSelect.value = thread.attachment_type || "";

                const pdfWrapper = base.querySelector(".Thread_PDFWrapper");
                const urlWrapper = base.querySelector(".Thread_URLWrapper");
                const videoWrapper = base.querySelector(".Thread_VideoWrapper");

                const existingPDFNote = base.querySelector(".Thread_ExistingPDFNote");
                const existingVideoNote = base.querySelector(".Thread_ExistingVideoNote");
                const existingHidden = base.querySelector(".Thread_ExistingMedia");

                // Reset semua wrapper
                [pdfWrapper, urlWrapper, videoWrapper].forEach(w => (w.style.display = "none"));
                existingHidden.value = "";
                existingPDFNote.classList.add("d-none");
                existingVideoNote.classList.add("d-none");

                // ===============================
                // 🧾 Jika ada attachment existing
                // ===============================
                if (thread.attachment_path) {
                    existingHidden.value = thread.attachment_path;
                    const lower = thread.attachment_path.toLowerCase();

                    if (thread.attachment_type === "pdf" || /\.pdf$/i.test(lower)) {
                        pdfWrapper.style.display = "block";
                        existingPDFNote.textContent = `📄 Sudah ada PDF tersimpan sebelumnya (${thread.attachment_path.split('/').pop()})`;
                        existingPDFNote.classList.remove("d-none");
                    } 
                    else if (thread.attachment_type === "video" || /\.(mp4|mkv|avi|webm)$/i.test(lower)) {
                        videoWrapper.style.display = "block";
                        existingVideoNote.textContent = `🎥 Sudah ada video tersimpan sebelumnya (${thread.attachment_path.split('/').pop()})`;
                        existingVideoNote.classList.remove("d-none");
                    } 
                    else if (thread.attachment_type === "url" && thread.attachment_path.startsWith("http")) {
                        urlWrapper.style.display = "block";
                        urlWrapper.querySelector("input").value = thread.attachment_path;
                    }
                } else {
                    // Jika belum ada file sama sekali → tampilkan wrapper default sesuai tipe
                    if (thread.attachment_type === "pdf") pdfWrapper.style.display = "block";
                    if (thread.attachment_type === "url") urlWrapper.style.display = "block";
                    if (thread.attachment_type === "video") videoWrapper.style.display = "block";
                }

                // ===============================
                // 🗑️ Tampilkan tombol hapus + set dataset
                // ===============================
                const removeBtn = base.querySelector(".Thread_RemoveBtn");
                if (removeBtn) {
                    removeBtn.style.display = "inline-block";
                    // simpan id thread ke dataset agar JS tahu mana yang dihapus
                    removeBtn.dataset.threadId = thread.thread_id || thread.id || "";
                }

                // ===============================
                // ➕ Masukkan ke dalam list
                // ===============================
                list.appendChild(base);
            });

            updateSaveBtnState();
        } catch (err) {
            // console.error("❌ Gagal load thread:", err);
            alert("Terjadi kesalahan saat memuat thread.");
        }
    }

    // =============================
    // Multi Soal Thread & Delegation
    // =============================
    const list = document.getElementById("Thread_FormList");
    const addBtnThread = document.getElementById("Thread_AddFormBtn");
    const saveBtnThread = document.getElementById("Thread_saveForumBtn");
    
    list.addEventListener("input", () => {
        updateSaveBtnState();
    });

    // 🟡 Tambah soal baru
    addBtnThread.addEventListener("click", () => {
        const blocks = list.querySelectorAll(".Thread_FormBlock");
        const index = blocks.length + 1;

        // Gunakan block pertama sebagai template
        // const template = document.querySelector(".Thread_FormBlock");
        if (!THREAD_TEMPLATE) return alert("Template tidak ditemukan. Silakan refresh halaman.");

        const clone = THREAD_TEMPLATE.cloneNode(true);

        clone.querySelectorAll("input, textarea, select").forEach(el => {
            el.value = "";
            el.classList.remove("is-invalid");
        });

        clone.querySelector("h6").textContent = `🟡 Diskusi ${index}`;
        clone.querySelector(".Thread_RemoveBtn").style.display = "inline-block";
        clone.querySelectorAll(".Thread_PDFWrapper, .Thread_URLWrapper, .Thread_VideoWrapper")
            .forEach(el => el.style.display = "none");

        // Hapus flag existing_attachment agar validasi tetap wajib
        const oldFlag = clone.querySelector("input[name='existing_attachment']");
        if (oldFlag) oldFlag.remove();

        // Reset hidden input jika ada
        if (clone.querySelector("input[name='thread_id']")) clone.querySelector("input[name='thread_id']").value = "";
        if (clone.querySelector("input[name='thread_seq']")) clone.querySelector("input[name='thread_seq']").value = index;

        list.appendChild(clone);
    });

        // 🗑 Hapus soal (dengan konfirmasi + request ke backend)
    list.addEventListener("click", async e => {
        const btn = e.target.closest(".Thread_RemoveBtn");
        if (!btn) return;

        // Ambil ID thread dari dataset
        const threadId = btn.dataset.threadId;

        // ================================
        // CASE 1: Thread belum pernah disimpan ke DB
        // ================================
        if (!threadId) {
            if (confirm("Apakah kamu yakin ingin menghapus soal ini (belum tersimpan)?")) {
                btn.closest(".Thread_FormBlock").remove();

                // Re-index sisa form
                list.querySelectorAll(".Thread_FormBlock").forEach((b, i) => {
                    b.querySelector("h6").textContent = `🟡 Diskusi ${i + 1}`;
                    if (b.querySelector("input[name='thread_seq']"))
                        b.querySelector("input[name='thread_seq']").value = i + 1;
                });

                // Jika semua terhapus → buat 1 form kosong
                if (list.querySelectorAll(".Thread_FormBlock").length === 0) {
                    const empty = THREAD_TEMPLATE.cloneNode(true);

                    empty.querySelectorAll("input, textarea, select").forEach(el => {
                        el.value = "";
                        el.classList.remove("is-invalid");
                    });

                    empty.querySelector("h6").textContent = "🟡 Diskusi 1";
                    empty.querySelector(".Thread_RemoveBtn").style.display = "none";

                    empty.querySelector(".Thread_PDFWrapper").style.display = "none";
                    empty.querySelector(".Thread_URLWrapper").style.display = "none";
                    empty.querySelector(".Thread_VideoWrapper").style.display = "none";

                    if (empty.querySelector("input[name='thread_id']"))
                        empty.querySelector("input[name='thread_id']").value = "";

                    if (empty.querySelector("input[name='thread_seq']"))
                        empty.querySelector("input[name='thread_seq']").value = 1;

                    list.appendChild(empty);

                    saveBtnThread.disabled = true;
                    saveBtnThread.classList.add("disabled");
                }
            }
            return;
        }

        // ================================
        // CASE 2: Thread sudah tersimpan di server
        // ================================
        if (!confirm("Apakah kamu yakin ingin menghapus thread ini dari server?")) return;

        try {
            const res = await fetch(`/modify-course/forum/thread/${threadId}`, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                    "Accept": "application/json",
                },
            });

            const data = await res.json();
            if (data.success) {
                alert("✅ Thread berhasil dihapus!");

                // Hapus dari DOM
                btn.closest(".Thread_FormBlock").remove();

                // Re-index sisa form
                list.querySelectorAll(".Thread_FormBlock").forEach((b, i) => {
                    b.querySelector("h6").textContent = `🟡 Diskusi ${i + 1}`;
                    if (b.querySelector("input[name='thread_seq']"))
                        b.querySelector("input[name='thread_seq']").value = i + 1;
                });

                // ----------------------------
                // PATCH: Jika semua thread telah terhapus
                // ----------------------------
                if (list.querySelectorAll(".Thread_FormBlock").length === 0) {
                    const empty = THREAD_TEMPLATE.cloneNode(true);

                    empty.querySelectorAll("input, textarea, select").forEach(el => {
                        el.value = "";
                        el.classList.remove("is-invalid");
                    });

                    empty.querySelector("h6").textContent = "🟡 Diskusi 1";
                    empty.querySelector(".Thread_RemoveBtn").style.display = "none";

                    empty.querySelector(".Thread_PDFWrapper").style.display = "none";
                    empty.querySelector(".Thread_URLWrapper").style.display = "none";
                    empty.querySelector(".Thread_VideoWrapper").style.display = "none";

                    if (empty.querySelector("input[name='thread_id']"))
                        empty.querySelector("input[name='thread_id']").value = "";

                    if (empty.querySelector("input[name='thread_seq']"))
                        empty.querySelector("input[name='thread_seq']").value = 1;

                    list.appendChild(empty);

                    saveBtnThread.disabled = true;
                    saveBtnThread.classList.add("disabled");
                }

            } else {
                alert(`⚠️ Gagal menghapus thread: ${data.message || 'Tidak diketahui'}`);
            }

        } catch (err) {
            // console.error("Error:", err);
            alert("❌ Terjadi kesalahan koneksi ke server!");
        }
    });

    // 📎 Toggle lampiran + hapus flag existing kalau file diubah
    list.addEventListener("change", e => {
        const block = e.target.closest(".Thread_FormBlock");

        // toggle tipe
        if (e.target.classList.contains("Thread_AttachType")) {
            const pdf = block.querySelector(".Thread_PDFWrapper");
            const url = block.querySelector(".Thread_URLWrapper");
            const vid = block.querySelector(".Thread_VideoWrapper");
            [pdf, url, vid].forEach(w => w.style.display = "none");
            if (e.target.value === "pdf") pdf.style.display = "block";
            if (e.target.value === "url") url.style.display = "block";
            if (e.target.value === "video") vid.style.display = "block";
        }

        // kalau user ubah file → hapus flag existing_attachment
        if (e.target.matches("input[type='file'], input[name='Thread_forum_attachment_url[]']")) {
            const flag = block.querySelector("input[name='existing_attachment']");
            if (flag) flag.remove();
        }
    });

    // ===============================
    // VALIDASI & SIMPAN (dengan progress + auto close modal + tombol disable)
    // ===============================
    saveBtnThread.addEventListener("click", (e) => {
        e.preventDefault();

        // Ambil referensi tombol simpan
        const saveBtn = e.target;

        // 🟡 Disable tombol & tampilkan spinner
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.classList.add("disabled");
            saveBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...`;
        }

        const blocks = list.querySelectorAll(".Thread_FormBlock");
        let isValid = true;
        let firstInvalid = null;

        if (!blocks.length) {
            alert("❌ Tambahkan minimal satu soal terlebih dahulu!");
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.classList.remove("disabled");
                saveBtn.innerHTML = "Simpan Thread";
            }
            return;
        }

        // 🔎 VALIDASI INPUT
        blocks.forEach(block => {
            const topic = block.querySelector("input[name='Thread_topic_title[]']");
            const forumTitle = block.querySelector("input[name='Thread_forum_title[]']");
            const forumQuestion = block.querySelector("textarea[name='Thread_forum_question[]']");
            const attachType = block.querySelector("select[name='Thread_forum_attachment_type[]']").value;

            const pdf = block.querySelector("input[name='Thread_forum_attachment_pdf[]']");
            const url = block.querySelector("input[name='Thread_forum_attachment_url[]']");
            const video = block.querySelector("input[name='Thread_forum_attachment_video[]']");
            const existingMedia = block.querySelector(".Thread_ExistingMedia")?.value?.trim();

            [topic, forumTitle, forumQuestion, pdf, url, video].forEach(el => el?.classList.remove("is-invalid"));

            if (!topic.value.trim()) { topic.classList.add("is-invalid"); isValid = false; if (!firstInvalid) firstInvalid = topic; }
            if (!forumTitle.value.trim()) { forumTitle.classList.add("is-invalid"); isValid = false; if (!firstInvalid) firstInvalid = forumTitle; }
            if (!forumQuestion.value.trim()) { forumQuestion.classList.add("is-invalid"); isValid = false; if (!firstInvalid) firstInvalid = forumQuestion; }

            // 🔸 Validasi file
            if (attachType === "pdf" && !pdf.files.length && !existingMedia) {
                pdf.classList.add("is-invalid"); isValid = false; if (!firstInvalid) firstInvalid = pdf;
            }
            if (attachType === "url" && !url.value.trim() && !existingMedia) {
                url.classList.add("is-invalid"); isValid = false; if (!firstInvalid) firstInvalid = url;
            }
            if (attachType === "video") {
                if (!video.files.length && !existingMedia) {
                    video.classList.add("is-invalid"); isValid = false; if (!firstInvalid) firstInvalid = video;
                } else if (video.files.length) {
                    const file = video.files[0];
                    const maxSize = 100 * 1024 * 1024; // 100MB
                    if (file.size > maxSize) {
                        alert(`⚠️ File "${file.name}" melebihi 100 MB (${(file.size / 1024 / 1024).toFixed(2)} MB).`);
                        video.value = "";
                        video.classList.add("is-invalid");
                        isValid = false;
                        if (!firstInvalid) firstInvalid = video;
                    }
                }
            }
        });

        if (!isValid) {
            alert("⚠️ Mohon lengkapi kolom yang wajib diisi atau file lampiran.");
            if (firstInvalid) firstInvalid.scrollIntoView({ behavior: "smooth", block: "center" });
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.classList.remove("disabled");
                saveBtn.innerHTML = "Simpan Thread";
            }
            return;
        }

        // ===============================
        // BENTUK FORM DATA (Thread)
        // ===============================
        const formData = new FormData();
        const courseId = document.getElementById("Thread_course_id")?.value || "";
        formData.append("course_id", courseId);

        blocks.forEach((block, i) => {
            const seq = i + 1;
            const attachType = block.querySelector("select[name='Thread_forum_attachment_type[]']").value;
            const existingMedia = block.querySelector(".Thread_ExistingMedia")?.value?.trim() || "";
            const threadId = block.querySelector("input[name='thread_id']")?.value || "";

            formData.append(`threads[${i}][thread_id]`, threadId);
            formData.append(`threads[${i}][thread_seq]`, seq);
            formData.append(`threads[${i}][topic_title]`, block.querySelector("input[name='Thread_topic_title[]']").value);
            formData.append(`threads[${i}][forum_title]`, block.querySelector("input[name='Thread_forum_title[]']").value);
            formData.append(`threads[${i}][forum_question]`, block.querySelector("textarea[name='Thread_forum_question[]']").value);
            formData.append(`threads[${i}][attachment_type]`, attachType);

            if (attachType === "pdf") {
                const pdf = block.querySelector("input[name='Thread_forum_attachment_pdf[]']");
                if (pdf.files.length) formData.append(`threads[${i}][Thread_forum_attachment_pdf]`, pdf.files[0]);
                else if (existingMedia) formData.append(`threads[${i}][existing_attachment]`, existingMedia);
            }
            if (attachType === "video") {
                const video = block.querySelector("input[name='Thread_forum_attachment_video[]']");
                if (video.files.length) formData.append(`threads[${i}][Thread_forum_attachment_video]`, video.files[0]);
                else if (existingMedia) formData.append(`threads[${i}][existing_attachment]`, existingMedia);
            }
            if (attachType === "url") {
                const url = block.querySelector("input[name='Thread_forum_attachment_url[]']").value;
                if (url.trim()) formData.append(`threads[${i}][attachment_path]`, url);
                else if (existingMedia) formData.append(`threads[${i}][existing_attachment]`, existingMedia);
            }
        });

        // ===============================
        // PROGRESS BAR HANDLER
        // ===============================
        const progressBar = document.getElementById("Thread_ProgressBar");
        const progressWrapper = document.getElementById("Thread_ProgressWrapper");
        const progressText = document.getElementById("Thread_ProgressText");

        progressWrapper.style.display = "block";
        progressText.style.display = "block";
        progressBar.style.width = "0%";
        progressBar.textContent = "0%";
        progressBar.classList.remove("bg-danger");
        progressBar.classList.add("progress-bar-animated", "bg-success");

        // ===============================
        // UPLOAD DENGAN PROGRESS
        // ===============================
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "/modify-course/forum/discussion-thread", true);
        xhr.setRequestHeader("X-CSRF-TOKEN", document.querySelector('meta[name="csrf-token"]').content);

        xhr.upload.addEventListener("progress", (event) => {
            if (event.lengthComputable) {
                const percent = Math.round((event.loaded / event.total) * 100);
                progressBar.style.width = percent + "%";
                progressBar.textContent = percent + "%";

                // ✳️ Update teks tombol juga
                if (saveBtn) saveBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan ${percent}%...`;
            }
        });

        // xhr.onload = function () {
        //     progressBar.classList.remove("progress-bar-animated");

        //     // ✅ Aktifkan tombol kembali
        //     if (saveBtn) {
        //         saveBtn.disabled = false;
        //         saveBtn.classList.remove("disabled");
        //         saveBtn.innerHTML = "Simpan Thread";
        //     }

        //     if (xhr.status === 200 || xhr.status === 201) {
        //         const result = JSON.parse(xhr.responseText);
        //         if (result.success) {
        //             progressBar.style.width = "100%";
        //             progressBar.textContent = "Upload selesai ✅";

        //             setTimeout(() => {
        //                 alert(`✅ ${result.message}`);
        //                 progressWrapper.style.display = "none";
        //                 progressText.style.display = "none";

        //                 const modalEl = document.getElementById("ThreadDiscussion");
        //                 const modalInstance = bootstrap.Modal.getInstance(modalEl);
        //                 modalInstance.hide();

        //                 setTimeout(() => {
        //                     window.location.reload();
        //                 }, 300);
        //             }, 800);
        //         } else {
        //             progressBar.classList.add("bg-danger");
        //             progressBar.textContent = "Gagal ❌";
        //             alert(`⚠️ Gagal menyimpan thread: ${result.message}`);
        //         }
        //     } else {
        //         progressBar.classList.add("bg-danger");
        //         progressBar.textContent = "Server Error ❌";
        //     }
        // };
        
        xhr.onload = function () {
            // Matikan animasi progress bar
            progressBar.classList.remove("progress-bar-animated");

            // Re-enable tombol simpan
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.classList.remove("disabled");
                saveBtn.innerHTML = "Simpan Thread";
            }

            // ===============================
            // 1️⃣ Jika status bukan 200/201 → ERROR
            // ===============================
            if (xhr.status !== 200 && xhr.status !== 201) {
                progressBar.classList.add("bg-danger");
                progressBar.textContent = "Gagal ❌";

                alert("Terjadi kesalahan dalam proses menyimpan Thread.");
                return;
            }

            // ===============================
            // 2️⃣ Coba parse JSON → kalau gagal tetap error
            // ===============================
            let result = null;
            try {
                result = JSON.parse(xhr.responseText);
            } catch (e) {
                progressBar.classList.add("bg-danger");
                progressBar.textContent = "Gagal ❌";

                alert("Terjadi kesalahan dalam proses menyimpan Thread.");
                return;
            }

            // ===============================
            // 3️⃣ Kalau success=false dari server → tetap error
            // ===============================
            if (!result || !result.success) {
                progressBar.classList.add("bg-danger");
                progressBar.textContent = "Gagal ❌";

                alert("Terjadi kesalahan dalam proses menyimpan Thread.");
                return;
            }

            // ===============================
            // 4️⃣ Jika sukses
            // ===============================
            progressBar.style.width = "100%";
            progressBar.textContent = "Upload selesai ✅";

            setTimeout(() => {
                alert(`✅ ${result.message}`);

                progressWrapper.style.display = "none";
                progressText.style.display = "none";

                const modalEl = document.getElementById("ThreadDiscussion");
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                modalInstance.hide();

                setTimeout(() => window.location.reload(), 300);
            }, 800);
        };

        xhr.onerror = function () {
            progressBar.classList.add("bg-danger");
            progressBar.textContent = "Koneksi gagal ❌";
            alert("Terjadi kesalahan koneksi ke server!");

            // ✅ Re-enable tombol jika error
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.classList.remove("disabled");
                saveBtn.innerHTML = "Simpan Thread";
            }
        };

        xhr.send(formData);
    });


    // =========== 
    // THREAD MAIN LOGIC
    // ===========
    const openBtn = document.getElementById("add-thread-forum");
    const hiddenCourseId = document.getElementById("Thread_course_id");
    const modal = document.getElementById("ThreadDiscussion");

    if (openBtn && hiddenCourseId && modal) {
        // Saat tombol diklik, ambil course_id
        openBtn.addEventListener("click", function () {
            const courseId = this.getAttribute("data-course-id") || "";
            hiddenCourseId.value = courseId;
            // console.log("🎯 Course ID diset:", courseId);
        });

        // Saat modal benar-benar terbuka → load thread existing
        modal.addEventListener("shown.bs.modal", function () {
            const courseId = hiddenCourseId.value;
            // console.log("🧾 Course ID di modal:", courseId);
            loadExistingThreads(courseId);
        });
    }


    // =============================
    // Generate Payload (Satu Sumber)
    // =============================
    function generatePayload() {
        const materiItems = document.querySelectorAll(".materi-item");
        let payload = [];

        materiItems.forEach((materi, i) => {
            const materiTitle = materi.querySelector(".materi-title")?.textContent.trim() || "";
            const subs = [];

            materi.querySelectorAll(".sub-item").forEach((sub, j) => {
                subs.push({
                    sub_index: j + 1,
                    course_id: sub.dataset.courseId || "",
                    week_id: sub.dataset.courseWeekId || "",
                    week_title: sub.dataset.courseWeekTitle || "",
                    title: sub.querySelector(".sub-title")?.textContent.trim() || ""
                });
            });

            payload.push({
                materi_index: i + 1,
                title: materiTitle,
                subs: subs
            });
        });

        return payload;
    }

    // function uploadWithProgress(url, formData, onSuccess) {
    //     const xhr = new XMLHttpRequest();
    //     const wrapper = document.getElementById("uploadProgressWrapper");
    //     const bar = document.getElementById("uploadProgress");

    //     // reset bar setiap upload baru
    //     wrapper.classList.remove("d-none");
    //     bar.style.width = "0%";
    //     bar.textContent = "0%";
    //     bar.classList.remove("bg-success", "bg-danger");

    //     xhr.open("POST", url, true);
    //     xhr.setRequestHeader("X-CSRF-TOKEN", document.querySelector('meta[name="csrf-token"]').content);

    //     // update progress
    //     xhr.upload.onprogress = function (e) {
    //         if (e.lengthComputable) {
    //             const percent = Math.round((e.loaded / e.total) * 100);
    //             bar.style.width = percent + "%";
    //             bar.textContent = percent + "%";
    //         }
    //     };

    //     // selesai
    //     xhr.onload = function () {
    //         if (xhr.status === 200) {
    //             bar.classList.add("bg-success");
    //             bar.textContent = "✅ Selesai";
    //             if (onSuccess) onSuccess(JSON.parse(xhr.responseText));
    //         } else {
    //             bar.classList.add("bg-danger");
    //             bar.textContent = "❌ Gagal";
    //         }
    //     };

    //     xhr.send(formData);
    // }
    function uploadWithProgress(url, formData, onSuccess) {
        const xhr = new XMLHttpRequest();
        const wrapper = document.getElementById("uploadProgressWrapper");
        const bar = document.getElementById("uploadProgress");

        // Reset progress bar
        if (wrapper) wrapper.classList.remove("d-none");
        if (bar) {
            bar.style.width = "0%";
            bar.textContent = "0%";
            bar.classList.remove("bg-success", "bg-danger");
        }

        xhr.open("POST", url, true);
        xhr.timeout = 600000; // 10 menit
        xhr.setRequestHeader("X-CSRF-TOKEN", document.querySelector('meta[name="csrf-token"]').content);

        // Update progress
        xhr.upload.onprogress = function (e) {
            if (e.lengthComputable && bar) {
                const percent = Math.round((e.loaded / e.total) * 100);
                bar.style.width = percent + "%";
                bar.textContent = percent + "%";
            }
        };

        // Saat upload selesai
        xhr.onload = function () {
            if (xhr.status === 200) {
                try {
                    const data = JSON.parse(xhr.responseText);
                    if (bar) {
                        bar.classList.add("bg-success");
                        bar.textContent = "✅ Selesai";
                    }
                    if (onSuccess) onSuccess(data);
                } catch {
                    if (bar) {
                        bar.classList.add("bg-danger");
                        bar.textContent = "⚠️ Gagal memproses hasil";
                    }
                    alert("Terjadi kesalahan setelah file dikirim. Silakan coba lagi nanti.");
                }
            } else {
                if (bar) {
                    bar.classList.add("bg-danger");
                    bar.textContent = "❌ Gagal";
                }
                alert("Gagal mengunggah file. Silakan coba lagi nanti.");
            }
        };

        // Timeout (jika upload terlalu lama)
        xhr.ontimeout = function () {
            if (bar) {
                bar.classList.add("bg-danger");
                bar.textContent = "⚠️ Waktu habis";
            }
            alert("Proses unggahan memakan waktu terlalu lama. Silakan coba lagi nanti.");
        };

        // Error jaringan
        xhr.onerror = function () {
            if (bar) {
                bar.classList.add("bg-danger");
                bar.textContent = "❌ Gagal";
            }
            alert("Terjadi kesalahan jaringan saat mengunggah file. Silakan periksa koneksi Anda dan coba lagi.");
        };

        xhr.send(formData);
    }


    function uploadWithProgressEssay(url, formData, onSuccess) {
        const wrapper = document.getElementById("uploadProgressEssay");
        const bar = document.getElementById("uploadProgressEssayBar");

        // reset progress bar setiap kali mulai upload
        wrapper.classList.remove("d-none");
        bar.style.width = "0%";
        bar.textContent = "0%";
        bar.classList.remove("bg-success", "bg-danger");

        const xhr = new XMLHttpRequest();
        xhr.open("POST", url, true);
        xhr.setRequestHeader("X-CSRF-TOKEN", document.querySelector('meta[name="csrf-token"]').content);

        // progress berjalan
        xhr.upload.onprogress = function (e) {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                bar.style.width = percent + "%";
                bar.textContent = percent + "%";
            }
        };

        // jika selesai
        xhr.onload = function () {
            if (xhr.status === 200) {
                try {
                    const data = JSON.parse(xhr.responseText);
                    bar.classList.add("bg-success");
                    bar.textContent = "✅ Selesai";

                    if (onSuccess) onSuccess(data);
                } catch (err) {
                    // console.error("❌ JSON parse error:", err);
                    bar.classList.add("bg-danger");
                    bar.textContent = "❌ Gagal Parsing";
                }
            } else {
                bar.classList.add("bg-danger");
                bar.textContent = "❌ Gagal Upload";
            }
        };

        xhr.onerror = function () {
            bar.classList.add("bg-danger");
            bar.textContent = "❌ Network Error";
        };

        xhr.send(formData);
    }
}
