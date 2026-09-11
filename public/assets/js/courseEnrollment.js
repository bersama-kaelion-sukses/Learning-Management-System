export function InitCourseEnrollment() {
    // ====================
    window.__QUIZ_DEBUG__ ??= {};
    window.__ACTIVE_QUIZ__ ??= null;
    window.__QUIZ_FINISHED__ ??= {};
    // ====================
    const itemOptions = document.querySelectorAll(".item-option");
    const detailPanel = document.getElementById("learner-detail-panel");
    const progressBox = document.getElementById("progress-box");
    const nextButton = document.getElementById('next-btn');
    
    const wrapper = document.getElementById('course-wrapper');
    const previewMode = wrapper.dataset.previewMode == '1';

    document.addEventListener("DOMContentLoaded", () => {
        const detailPanel = document.getElementById("learner-detail-panel");
        
        if (!detailPanel) {
            // console.error("❌ detailPanel (#learner-detail-panel) tidak ditemukan di DOM");
        } else {
            // console.log("✅ detailPanel ditemukan:", detailPanel);
        }
    });

    let currentModule = null;
    let currentIndex = 0;
    let currentTotal = 0;
    let currentTime = null;


    // function updateProgress(index, total) {
    //     if (progressBox) {
    //          progressBox.innerText = `Progress : ${index}/${total}`;
    //     }
    // }
    function updateProgress() {
        const progressBox = document.getElementById("progress-box");
        if (!progressBox) return;
    
        const allItems = [...document.querySelectorAll(".item-option")];
        const activeItem = document.querySelector(".item-option.active");
    
        if (!activeItem) return;
    
        const index = allItems.indexOf(activeItem) + 1;
        const total = allItems.length;
    
        progressBox.textContent = `Progress : ${index}/${total}`;
    }
    
     let hasShownRulesAlert = false;
     
     function renderRulesofCourse() {
        if (localStorage.getItem('rules_alert_shown')) return;

        const modalEl = document.getElementById('courseRulesModal');
        if (!modalEl) return;
        const modal = new bootstrap.Modal(modalEl);
        modal.show();

        localStorage.setItem('rules_alert_shown', '1');
    }
    
    // fungsi render isi materi
    function renderContent(el) {
        
        if (!previewMode) {
            renderRulesofCourse();
            setInterval(() => {
                if (localStorage.getItem('rules_alert_shown')) {
                    localStorage.removeItem('rules_alert_shown');
                }
            },  720000);           
        }

        // reset active styling
        itemOptions.forEach(i => i.classList.remove("active"));
        el.classList.add("active");

        // ambil atribut dari item
        const name   = el.dataset.name || "Tanpa Judul";
        const desc   = el.dataset.desc || "Tidak ada deskripsi.";
        const file   = el.dataset.file || null;
        const type   = el.dataset.type || null;
        const itemId = el.dataset.itemId || null;
        const essayId = el.dataset.essayId || null;
        const essayTitle = el.dataset.essayTitle || "Tanpa judul esai";
        const essayType = el.dataset.essayType || null;
        const essayAttach = el.dataset.essayAttach || null;
        const instruction = el.dataset.essayInstruction || "Tidak ada instruksi.";
        const forumTitle = el.dataset.forumTitle || "Forum Diskusi";
        const forumQuestion = el.dataset.forumQuestion || "Tidak ada pertanyaan forum.";
        const forumAttachType  = el.dataset.forumAttachmentType || null;
        const forumAttachValue = el.dataset.forumAttachmentValue || null;
        const submissionGrade   = el.dataset.gradeSubmission || null;
        const submissionFeedback= el.dataset.feedbackSubmission || "";
        const gradeEssay    = el.dataset.gradeEssay || null;
        const feedbackEssay = el.dataset.feedbackEssay || null;
        const gradeForum    = el.dataset.gradeForum || null;
        const feedbackForum = el.dataset.feedbackForum || null;
        const passingGrade = el.dataset.passingGradeItem || null;

        let questions = JSON.parse(el.dataset.questions || "[]");
        // console.log(questions); 

        let currentQ = 0; 

        // ambil dataset index & total
        currentIndex = parseInt(el.dataset.index) || 1;
        currentTotal = parseInt(el.dataset.total) || 0;

        let content = `<h5 class="mb-3">${name}</h5>`;
        if (type !== "6") {
            content += `<p>Deskripsi : ${desc}</p>`;
        }
        // render konten sesuai type
        if (type === "1" && file) {
            if (file.startsWith("http")) {
                // ✅ YouTube embed
                if (file.includes("youtube.com") || file.includes("youtu.be")) {
                    let embedUrl = "";

                    try {
                        // case: normal link https://www.youtube.com/watch?v=ID
                        if (file.includes("watch?v=")) {
                            const videoId = new URL(file).searchParams.get("v");
                            embedUrl = `https://www.youtube.com/embed/${videoId}`;
                        }
                        // case: short link https://youtu.be/ID
                        else if (file.includes("youtu.be")) {
                            const videoId = file.split("youtu.be/")[1].split("?")[0]; // buang query ?...
                            embedUrl = `https://www.youtube.com/embed/${videoId}`;
                        }
                        // case: sudah embed link tapi ada query (misal ?si=xxx)
                        else if (file.includes("/embed/")) {
                            const parts = file.split("/embed/")[1];
                            const videoId = parts.split("?")[0]; // buang query ?...
                            embedUrl = `https://www.youtube.com/embed/${videoId}`;
                        }
                    } catch (err) {
                        embedUrl = file; // fallback pakai apa adanya
                    }

                    content += `
                        <div class="ratio ratio-16x9 mt-3">
                            <iframe src="${embedUrl}" frameborder="0" allowfullscreen></iframe>
                        </div>
                        <p class="mt-2 text-muted">
                            🔔 Note: Jika video tidak bisa diputar, silakan klik 
                            <span class="text-muted" style="text-decoration: underline;">Watch on YouTube</span>
                        </p>`;
                }

                // ✅ Google Drive embed
                else if (file.includes("drive.google.com")) {
                    let embedUrl = file;

                    // convert https://drive.google.com/file/d/ID/view → /preview
                    if (file.includes("/view")) {
                        embedUrl = file.replace("/view", "/preview");
                    }

                    content += `
                        <div class="ratio ratio-16x9 mt-3">
                            <iframe src="${embedUrl}" frameborder="0" allowfullscreen></iframe>
                        </div>`;
                }

                // ❌ selain YouTube/Drive → tampilkan peringatan
                else {
                    content += `
                        <div class="alert alert-warning mt-3">
                            Hanya mendukung video dari YouTube atau Google Drive.
                        </div>`;
                }
            } else {
                // ✅ Upload lokal
                content += `
                    <div class="ratio ratio-16x9 mt-3">
                        <video 
                            controls 
                            preload="metadata" 
                            controlsList="nodownload" 
                            playsinline 
                            style="width:100%; height:100%; border-radius:8px; background:#000;"
                        >
                            <source src="/stream/${file.split('/').pop()}" type="video/mp4">
                            Browser tidak mendukung video
                        </video>
                    </div>`;
            }
        } 
        else if (type === "2" && file) { 
            const modalId = `pdfModal-${Date.now()}`; // ID unik

            content += `
                <div class="mt-3">
                    <h6 class="fw-bold">📄 Lampiran PDF</h6>

                    <!-- Tombol buka fullscreen -->
                    <button class="btn btn-sm btn-outline-secondary mb-2"
                            data-bs-toggle="modal" data-bs-target="#${modalId}">
                        ⛶ Lihat Fullscreen
                    </button>

                    <!-- Preview kecil -->
                    <div class="ratio ratio-16x9 border rounded overflow-hidden">
                        <iframe src="/${file}" 
                                style="border:none;" 
                                title="Preview PDF">
                        </iframe>
                    </div>
                </div>

                <!-- Modal fullscreen -->
                <div class="modal fade" id="${modalId}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-fullscreen">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">📄 PDF Fullscreen</h5>
                                <button type="button" class="btn-close p-3" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-0">
                                <iframe src="/${file}" 
                                        style="border:0; width:100%; height:100%;" 
                                        title="PDF Fullscreen Viewer">
                                </iframe>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } 
        else if (type === "3") {
        // ===============================================
        // 🧩 ESSAY TYPE HANDLING (TYPE === 3)
        // ===============================================

        let essayDuration = parseInt(el.dataset.courseDuration || 0); // minutes
        const storageKeyEnd = `essay-${itemId}-endtime`;
        const storageKeySubmit = `essay-${itemId}-submitted`;

        validateSubmission(el);

        // ============================
        // 📌 Build essay object (FIX)
        // ============================
        const essay = {
            attachment_type: el.dataset.essayType || null,
            attachment_value:  el.dataset.essayAttach|| null,
            title: essayTitle,
            instruction: instruction
        };

       function autoSubmitEssay(itemId, essayId, answerText = "") {

        // console.log("⏳ Auto-submitting essay...");

        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        fetch(`/course/${itemId}/essay-submission`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken
            },
            body: JSON.stringify({
                essay_id: essayId,
                item_id: itemId,
                answer_text: answerText || ""
            })
        })
        .then(res => res.json())
        .then(json => {
            if (json.success) {
                // console.log("📨 Auto-submit success");

                // Mark as submitted
                localStorage.setItem(`essay-${itemId}-submitted`, "true");

                // UI lock
                const textarea = document.getElementById("answer_text");
                const submitBtn = document.getElementById("submit-essay-btn");

                if (textarea) textarea.readOnly = true;
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = "⏳ Jawaban terkirim otomatis";
                }

                // Reload answer thread
                loadSubmissions(itemId, window.currentUserId);

            } else {
                // console.error("❌ Auto-submit failed:", json);
            }
        })
        .catch(err => {
            // console.error("❌ Auto-submit error:", err);
        });
    }

        //    console.log("📌 ESSAY OBJECT:", essay);

        // ===============================================
        // 🔹 Step 1. Get submission status from server
        // ===============================================
        fetch(`/course/${itemId}/essay-submissions`)
            .then(res => res.json())
            .then(data => {
                const userSubmissions = data.filter(sub => sub.user?.user_id == window.currentUserId);
                const hasNormalSubmission = userSubmissions.some(sub => parseInt(sub.is_remedial) == 0);
                const storedEndTime = localStorage.getItem(storageKeyEnd);

                let alreadySubmitted = localStorage.getItem(storageKeySubmit);

                if (!hasNormalSubmission) {
                    alreadySubmitted = "false";
                    localStorage.removeItem(storageKeySubmit);
                } else {
                    alreadySubmitted = "true";
                    localStorage.setItem(storageKeySubmit, "true");
                }

                // ===============================================
                // 🔒 Step 2. Handle status
                // ===============================================

                // 🧩 Jika sudah submit → readonly
                if (alreadySubmitted === "true") {
                    renderEssay(null, true, essay);
                    return;
                }

                // 🧩 Lanjutkan essay yang belum selesai
                if (storedEndTime && Date.now() < parseInt(storedEndTime)) {
                    renderEssay(parseInt(storedEndTime), false, essay);
                    return;
                }

                // 🧩 Tampilkan prescreen
                if (essayDuration > 0) {
                    content += `
                        <div id="essay-prescreen" class="mt-3 p-3 border rounded text-center">
                            <h6>✍️ Persiapan Esai</h6>
                            <p><strong>Pertanyaan:</strong> ${essayTitle}</p>
                            <p><strong>Instruksi:</strong> ${instruction}</p>
                            <p>Batas Waktu: <strong>${essayDuration} menit</strong></p>
                            ${!previewMode
                                ? `<button class="btn btn-success mt-3" id="start-essay-btn">Mulai Esai</button>`
                                : `<div class="text-muted mt-3">🔒 Mode Preview — Esai tidak dapat dimulai</div>`
                            }
                        </div>
                    `;
                    detailPanel.innerHTML = content;

                    document.getElementById("start-essay-btn").addEventListener("click", () => {
                        const endTime = Date.now() + essayDuration * 60 * 1000;
                        localStorage.setItem(storageKeyEnd, endTime);
                        renderEssay(endTime, false, essay);
                    });
                } else {
                    renderEssay(null, false, essay);
                }
            });

        // ===============================================
        // 🔧 RENDER ESSAY FUNCTION (FINAL + ATTACHMENTS)
        // ===============================================
        function renderEssay(endTime, isReadOnly = false, essay = null) {
            let essayContent = `
                <div class="mt-3 p-3 border rounded position-relative">
                    <h6>✍️ Penugasan Esai</h6>
                    <p><strong>Pertanyaan:</strong> ${essayTitle}</p>
                    <p><strong>Instruksi:</strong> ${instruction}</p>
            `;

            // ================================
            // 📎 Lampiran URL / Video / Drive
            // ================================
            if (essay?.attachment_type && essay?.attachment_value) {

                const file = essay.attachment_value.trim();
                let embedHtml = "";
                let embedUrl = "";

                // 🎥 YouTube
                if (file.includes("youtube.com") || file.includes("youtu.be")) {

                    if (file.includes("watch?v=")) {
                        embedUrl = file.replace("watch?v=", "embed/");
                    } else if (file.includes("youtu.be")) {
                        const id = file.split("youtu.be/")[1].split(/[?&]/)[0];
                        embedUrl = `https://www.youtube.com/embed/${id}`;
                    }

                    embedHtml = `
                        <div class="mt-4">
                            <h6 class="fw-bold">🎥 Video YouTube</h6>
                            <div class="ratio ratio-16x9 border rounded">
                                <iframe src="${embedUrl}" allowfullscreen></iframe>
                            </div>
                        </div>`;
                }

                // 📁 Google Drive
                else if (file.includes("drive.google.com")) {
                    embedUrl = file.includes("/view")
                        ? file.replace("/view", "/preview")
                        : file;

                    embedHtml = `
                        <div class="mt-4">
                            <h6 class="fw-bold">📁 Lampiran Google Drive</h6>
                            <div class="ratio ratio-16x9 border rounded">
                                <iframe src="${embedUrl}" allowfullscreen></iframe>
                            </div>
                        </div>`;
                }

                // 🎞️ Video lokal
                else if (essay.attachment_type === "video") {
                    embedHtml = `
                        <div class="mt-4">
                            <h6 class="fw-bold">🎞️ Video</h6>
                            <video controls class="w-100 border rounded">
                                <source src="/${file}" type="video/mp4">
                            </video>
                        </div>`;
                }
                // 📄 PDF File (local or URL langsung dari database)
                else if (essay.attachment_type === "pdf" || file.endsWith(".pdf")) {

                    // jika tidak ada "/" di depan → tambahkan
                    const pdfUrl = file.startsWith("/") ? file : `/${file}`;

                    embedHtml = `
                        <div class="mt-4">
                            <h6 class="fw-bold">📄 Lampiran PDF</h6>

                            <button 
                                class="btn btn-sm btn-outline-secondary bg-light text-dark mb-2"
                                onclick="window.open('${pdfUrl}', '_blank')">
                                Fullscreen View
                            </button>

                            
                            <div class="border rounded" style="height: 500px;">
                                <iframe 
                                    src="${pdfUrl}"
                                    class="w-100 h-100"
                                    frameborder="0">
                                </iframe>
                            </div>
                        </div>
                    `;
                }

                // 🔗 URL umum
                else {
                    embedHtml = `
                        <div class="mt-4">
                            <h6 class="fw-bold">🔗 Lampiran URL</h6>
                            <a href="${file}" target="_blank" class="btn btn-outline-primary">Buka Link</a>
                        </div>`;
                }

                essayContent += embedHtml;
            }

            // ================================
            // ⏱ Timer
            // ================================
            if (essayDuration > 0 && endTime && !isReadOnly) {
                essayContent += `
                    <div id="essay-timer" class="position-absolute top-0 end-0 m-2 badge text-dark fs-6">
                        <span id="essay-countdown"></span>
                    </div>`;
            }

            // ================================
            // ✍️ Input jawaban
            // ================================
            essayContent += `
                <div class="d-flex align-items-stretch mt-3">
                    <textarea id="answer_text" class="form-control me-2" rows="3"
                        placeholder="Tulis jawaban Anda di sini..." ${isReadOnly ? "readonly" : ""}></textarea>
                    <button class="btn btn-secondary h-100" id="submit-essay-btn"
                            data-essay-id="${essayId}" data-item-id="${itemId}" ${isReadOnly ? "disabled" : ""}>
                        ${isReadOnly ? "✅ Jawaban sudah terkirim" : "Kirim"}
                    </button>
                </div>
                <p class="text-danger mt-2 small">⚠️ Hanya satu kali unggah.</p>
                </div>

                <div class="mt-4">
                    <h6 class="fw-bold mb-2">📝 Jawaban Learner Sebelumnya</h6>
                    <div id="forum-thread-${itemId}" class="border rounded p-2 bg-light"
                        style="max-height:350px; overflow-y:auto;">
                        ${isReadOnly
                            ? `<p class="text-muted fst-italic mb-2">Jawaban Anda sudah terkirim.</p>`
                            : `<p class="text-muted fst-italic mb-2">🔒 Jawaban learner lain akan muncul setelah Anda mengirim jawaban.</p>`}
                    </div>
                </div>
            `;

            detailPanel.innerHTML = essayContent;

            // ================================
            // ⏱ Timer countdown
            // ================================
            const submitBtn = document.getElementById("submit-essay-btn");
            const textarea = document.getElementById("answer_text");

            if (essayDuration > 0 && endTime && !isReadOnly) {
                const countdownEl = document.getElementById("essay-countdown");
                const timer = setInterval(() => {
                    const remaining = Math.max(0, Math.floor((endTime - Date.now()) / 1000));
                    const mins = Math.floor(remaining / 60);
                    const secs = remaining % 60;

                    countdownEl.textContent = `${mins}:${secs.toString().padStart(2, "0")}`;

                    if (remaining <= 0) {
                        clearInterval(timer);
                        localStorage.removeItem(storageKeyEnd);
                        submitBtn.disabled = true;
                        textarea.readOnly = true;
                        submitBtn.textContent = "⏳ Mengirim otomatis...";
                        autoSubmitEssay(itemId, essayId, textarea.value);
                    }
                }, 1000);
            }

            // ================================
            // ✍️ Manual Submit
            // ================================
            if (!isReadOnly && submitBtn) {
                submitBtn.addEventListener("click", (e) => {
                    e.preventDefault();
                    submitBtn.disabled = true;
                    textarea.readOnly = true;
                    submitBtn.textContent = "✅ Jawaban terkirim";

                    localStorage.setItem(storageKeySubmit, "true");
                    localStorage.removeItem(storageKeyEnd);

                    loadSubmissions(itemId, window.currentUserId, essayDuration);
                });
            }

            // ================================
            // 🧾 Load Submissions
            // ================================
            loadSubmissions(itemId, window.currentUserId, essayDuration);
        }

        return;
        }
        else if (type === "4") {
            
            const itemId = el.dataset.itemId;
            
            // ====================
            // 🔍 Detect overlapping
            if (window.__ACTIVE_QUIZ__ === itemId) {
                // console.warn("🚫 QUIZ ALREADY ACTIVE — IGNORE", itemId);

                return;
            }
            
            window.__QUIZ_SUBMITTED__ ??= {};
            delete window.__QUIZ_SUBMITTED__[itemId];

            // console.log("🔄 RESET SUBMIT FLAG FOR NEW ATTEMPT", itemId);

            window.__ACTIVE_QUIZ__ = itemId;

            // console.log("🧩 QUIZ INSTANCE CREATED", { itemId });

            const instanceId = `${itemId}-${Date.now()}-${Math.random().toString(36).slice(2, 5)}`;

            window.__QUIZ_DEBUG__[itemId] ??= {
                instances: [],
                submitHits: 0
            };

            window.__QUIZ_DEBUG__[itemId].instances.push(instanceId);
            window.__ACTIVE_QUIZ__ = instanceId;

            // console.log("🧩 QUIZ INSTANCE CREATED", {
            //     itemId,
            //     instanceId,
            //     alive: window.__QUIZ_DEBUG__[itemId].instances.length
            // });

            // ===================
            let currentQ = 0;
            let correctCount = 0;
            let wrongCount = 0;
            let answers = {};
            let timerInterval = null;
            const duration = el.dataset.courseDuration || 0;
            const quizDuration = parseInt(duration) * 60; // detik
            
            
            let submissionLocked = false;
            let payloadBuildCount = 0;
            let submitHitCount = 0;
            
            const storageKeyEnd = `quiz-${itemId}-endtime`;
            const storageKeyActive = `quiz-${itemId}-active`;

            function countNormalAttempt(attempt) {
                return attempt.filter(a => a.is_remedial == 0).length;
            }

            function ensureQuizContainer() {
                let container = document.getElementById("quiz-container");
                if (!container) {
                    detailPanel.innerHTML = `<div id="quiz-container" class="mt-3 p-3 border rounded text-center"></div>`;
                    container = document.getElementById("quiz-container");
                }
                return container;
            }

            function formatDate(dateStr) {
                const d = new Date(dateStr);
                return d.toLocaleString("id-ID", {
                    day: "2-digit",
                    month: "short",
                    year: "numeric",
                    hour: "2-digit",
                    minute: "2-digit"
                });
            }

            function shuffleMultipleChoice(arr) {
                return arr.sort(() => Math.random() - 0.5);
            }

            function getRemainingTime() {
                const storedEnd = localStorage.getItem(storageKeyEnd);
                if (!storedEnd) return 0;
                return Math.max(0, Math.floor((parseInt(storedEnd) - Date.now()) / 1000));
            }
            function autoSubmitQuiz() {
                // ====================
                if (window.__QUIZ_FINISHED__[instanceId]) {
                    // console.warn("🚫 AUTO SUBMIT BLOCKED (INSTANCE FINISHED)", instanceId);
                    return;
                }
                // /* ======================================
                // 🛡️ GLOBAL SINGLE-SUBMIT GUARD (PER ITEM)
                // ====================================== */

                if (window.__QUIZ_SUBMITTED__[itemId]) {
                    // console.warn("🚫 AUTO SUBMIT BLOCKED (ALREADY SUBMITTED)", itemId);
                    return;
                }

                // /* ======================================
                // 🛡️ ACTIVE INSTANCE GUARD (EXISTING)
                // ====================================== */
                if (window.__ACTIVE_QUIZ__ !== instanceId) {
                    // console.warn("🚫 AUTO SUBMIT BLOCKED (NOT ACTIVE INSTANCE)", {
                    //     active: window.__ACTIVE_QUIZ__,
                    //     caller: instanceId
                    // });
                    return;
                }

                // // 🔒 HARD LOCK
                if (submissionLocked) {
                    // console.warn("🚫 AUTO SUBMIT BLOCKED (LOCKED)");
                    return;
                }
                submissionLocked = true;

                // // 🔐 MARK SUBMITTED (GLOBAL, PER ITEM)
                window.__QUIZ_SUBMITTED__[itemId] = true;

                window.__QUIZ_DEBUG__[itemId].submitHits++;

                // console.log("🔐 [AUTO SUBMIT] LOCK ACQUIRED");
                
                // =====================
                const currentQuestion = questions[currentQ];
                if (currentQuestion) {
                    const selected = document.querySelector(`input[name="answer_${currentQuestion.question_id}"]:checked`);
                    if (selected) {
                        const isCorrect = selected.dataset.correct === "1";
                        answers[currentQuestion.question_id] = { selected: selected.value, correct: isCorrect };
                    }
                }

                correctCount = Object.values(answers).filter(a => a.correct).length;
                wrongCount = Object.values(answers).filter(a => !a.correct).length;

                const totalQ = Array.isArray(questions) ? questions.length : 0;
                const grade = totalQ > 0 ? Math.round((correctCount / totalQ) * 100) : 0;

                const questionIds = Object.keys(answers).map(id => parseInt(id));
                const payload = { item_id: itemId, questions: questionIds, grade: grade };

                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                fetch(`/course/${itemId}/mc-submission`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken
                    },
                    body: JSON.stringify(payload)
                })
                .then(res => res.json())
                .then(data => {
                    // ====================
                    // /* ===============================
                    // 🔒 MARK INSTANCE AS FINISHED
                    // =============================== */
                    window.__QUIZ_FINISHED__ ??= {};
                    window.__QUIZ_FINISHED__[instanceId] = true;

                    // /* ===============================
                    // 🔓 RELEASE SUBMIT FLAG (ALLOW NEW ATTEMPT)
                    // =============================== */
                    window.__QUIZ_SUBMITTED__ ??= {};
                    delete window.__QUIZ_SUBMITTED__[itemId];

                    // console.log("🏁 QUIZ INSTANCE FINISHED", {
                    //     itemId,
                    //     instanceId
                    // });
                    // /* ===============================
                    // 🧹 QUIZ INSTANCE CLEANUP
                    // =============================== */
                    if (
                        window.__QUIZ_DEBUG__ &&
                        window.__QUIZ_DEBUG__[itemId] &&
                        Array.isArray(window.__QUIZ_DEBUG__[itemId].instances)
                    ) {
                        window.__QUIZ_DEBUG__[itemId].instances =
                            window.__QUIZ_DEBUG__[itemId].instances.filter(id => id !== instanceId);
                    }

                    if (window.__ACTIVE_QUIZ__ === instanceId) {
                        window.__ACTIVE_QUIZ__ = null;
                    }

                    if (timerInterval) {
                        clearInterval(timerInterval);
                        timerInterval = null;
                    }
                    
                    // ==========================
                    localStorage.removeItem(storageKeyEnd);
                    localStorage.removeItem(storageKeyActive);
                    if (timerInterval) clearInterval(timerInterval);
                    timerInterval = null;
                    renderResultDetail(grade, correctCount, wrongCount, totalQ, data.attempt, data.history || []);
                })
                .catch(err => console.error("❌ Error auto-submit quiz:", err));
            }

            function renderResultDetail(grade, correct, wrong, total, attemptNo, attemptsHistory = []) {
                const container = ensureQuizContainer();
                const requiredGrade = passingGrade ? parseFloat(passingGrade) : 0;
                const passed = grade >= requiredGrade;

                let html = passed
                    ? `<h5 class="text-success fw-bold">🎉 Anda sudah lulus!</h5>
                    <p>Skor Anda <strong>${grade}%</strong> (Minimal ${requiredGrade}%)</p>`
                    : `<h5 class="text-danger fw-bold">❌ Anda belum lulus</h5>
                    <p>Skor Anda <strong>${grade}%</strong> (Minimal ${requiredGrade}%)</p>`;

                html += `<hr><ul class="text-start small">
                            <li>✅ Benar: ${correct}</li>
                            <li>❌ Salah: ${wrong}</li>
                            <li>📊 Skor Akhir: ${grade}%</li>
                            <li>🧾 Percobaan ke-${attemptNo}</li>
                        </ul>`;

                if (attemptsHistory.length > 0) {
                    html += `<hr><p class="fw-semibold mb-2">📊 Riwayat Nilai Sebelumnya:</p>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle text-center">
                                <thead class="table-light">
                                    <tr>
                                    <th>Percobaan</th>
                                    <th>Skor</th>
                                    <th>Remedial</th>
                                    <th>Tanggal</th
                                    ></tr>
                                </thead><tbody>`;
                    attemptsHistory.forEach(a => {
                        const badgeClass = a.grade >= requiredGrade ? "bg-success" : "bg-danger";
                        const statusLabel = a.is_remedial == 1
                               ? `<span class="badge bg-danger text-light">Jawaban Remedial</span>`
                               : `<span class="badge bg-secondary">Jawaban Baru</span>`;

                        html += `
                            <tr>
                                <td><span class="badge bg-primary">#${a.attempt_no}</span></td>
                                <td><span class="badge ${badgeClass}">${a.grade}%</span></td>
                                <td>${statusLabel}</td>
                                <td><small>${formatDate(a.submitted_at)}</small></td>
                            </tr>`;
                    });
                    html += `</tbody></table></div>`;
                }

                // console.log("📌 [RESULT DETAIL] attemptsHistory:", attemptsHistory);
                const normalAttempt = countNormalAttempt(attemptsHistory);
                // console.log("📌 [RESULT DETAIL] normalAttempt:", normalAttempt);
                // console.log("📌 [RESULT DETAIL] retryAllowed:", normalAttempt < 3);

                if (normalAttempt < 3) {
                    if (!previewMode) {
                        html += `
                            <button class="btn btn-outline-secondary mt-3" id="retry-quiz-btn">
                                🔁 Coba Lagi ${passed ? "(Optional)" : ""}
                            </button>
                        `;
                    } else {
                        html += `
                            <div class="text-muted mt-3">
                                🔒 Mode Preview — Quiz tidak dapat dimulai
                            </div>
                        `;
                    }
                }

                // if (!passed) {
                //     html += `<button class="btn btn-outline-secondary mt-3" id="retry-quiz-btn">🔁 Coba Lagi</button>`;
                // } else {
                //     html += `<button class="btn btn-outline-secondary mt-3" id="retry-quiz-btn">
                //     🔁 Coba Lagi ${passed ? "(Optional)" : ""}
                //     </button>`;
                // }

                container.innerHTML = html;
                localStorage.removeItem(storageKeyEnd);
                localStorage.removeItem(storageKeyActive);

                const retryBtn = document.getElementById("retry-quiz-btn");
                
                // ====================
                if (retryBtn) {
                    retryBtn.addEventListener("click", () => {
                        // console.log("🔁 RETRY QUIZ REQUESTED — NEW INSTANCE");

                        // 🧼 pastikan instance lama tidak dianggap aktif
                        if (window.__ACTIVE_QUIZ__ === instanceId) {
                            window.__ACTIVE_QUIZ__ = null;
                        }

                        // ❗ JANGAN hapus __QUIZ_FINISHED__ lama
                        // itu justru penanda valid bahwa instance tsb selesai

                        // startQuiz(true, attemptsHistory);
                        el.click();
                    });
                }
                
                // ====================
                if (retryBtn) retryBtn.addEventListener("click", () => startQuiz(true, attemptsHistory));

            }

            function renderQuestion(index) {
                const q = questions[index];
                const totalQ = questions.length;
                const progressPercent = Math.round((index / totalQ) * 100);

                if (!q) {
                    autoSubmitQuiz();
                    return;
                }

                const remaining = getRemainingTime();
                const minutes = Math.floor(remaining / 60);
                const seconds = remaining % 60;
                const timeDisplay = quizDuration > 0
                    ? `<div id="quiz-timer" class="text-danger fw-bold">⏳ ${minutes}:${seconds.toString().padStart(2, "0")}</div>`
                    : "";

                const progressHtml = `
                    <div class="progress mb-3" style="height: 12px;">
                        <div class="progress-bar bg-success" role="progressbar"
                            style="width: ${progressPercent}%"
                            aria-valuenow="${progressPercent}" aria-valuemin="0" aria-valuemax="100">
                            ${progressPercent}%
                        </div>
                    </div>`;

                let html = `
                    <div class="d-flex justify-content-between mb-2 align-items-center">
                        <h6>Soal ${index + 1} dari ${totalQ}</h6>
                        ${timeDisplay}
                    </div>
                    ${progressHtml}
                    <p class="fw-semibold text-start mb-3">${q.question_text}</p>
                    <form id="quiz-form">`;

                q.options.forEach(opt => {
                    const checked = answers[q.question_id]?.selected == opt.option_id ? "checked" : "";
                    html += `
                        <div class="form-check mb-2 text-start">
                            <input class="form-check-input" type="radio"
                                name="answer_${q.question_id}" value="${opt.option_id}"
                                data-correct="${opt.is_correct}" id="opt-${opt.option_id}" ${checked}>
                            <label class="form-check-label" for="opt-${opt.option_id}">
                                ${opt.option_text}
                            </label>
                        </div>`;
                });

                html += `</form>
                    <div class="d-flex justify-content-between mt-3">
                        <button class="btn btn-secondary btn-sm" id="prev-question-btn"
                            ${index === 0 ? 'style="display:none;"' : ''}>Sebelumnya</button>
                        <button class="btn btn-warning btn-sm" id="next-question-btn">
                            ${index + 1 === totalQ ? "Selesai" : "Selanjutnya"}
                        </button>
                    </div>`;

                const container = ensureQuizContainer();
                container.innerHTML = html;

                document.getElementById("prev-question-btn").addEventListener("click", e => {
                    e.preventDefault();
                    if (currentQ > 0) {
                        currentQ--;
                        renderQuestion(currentQ);
                    }
                });

                document.getElementById("next-question-btn").addEventListener("click", e => {
                    e.preventDefault();

                    const nextBtn = e.currentTarget;
                    const isLast = index + 1 === totalQ;

                    const selected = document.querySelector(`input[name="answer_${q.question_id}"]:checked`);
                    if (!selected) {
                        alert("⚠️ Pilih salah satu jawaban dulu!");
                        return;
                    }

                    const isCorrect = selected.dataset.correct === "1";
                    answers[q.question_id] = { selected: selected.value, correct: isCorrect };

                    correctCount = Object.values(answers).filter(a => a.correct).length;
                    wrongCount = Object.values(answers).filter(a => !a.correct).length;

                    if (isLast) {

                        nextBtn.disabled = true;
                        nextBtn.textContent = "Menyimpan...";

                        autoSubmitQuiz()
                            .catch(() => {
                                nextBtn.disabled = false;
                                nextBtn.textContent = "Selesai";
                            });

                        return; // STOP supaya tidak renderQuestion lagi
                    }

                    currentQ++;
                    renderQuestion(currentQ);
                });
            }

            function startQuiz(forceNew = false, attemptData = []) {

                const normalAttempts = countNormalAttempt(attemptData || []);

                // console.log("📌 [START QUIZ] attemptData:", attemptData);
                // console.log("📌 [START QUIZ] normalAttempts:", normalAttempts);

                if (normalAttempts >= 3) {
                    alert("🚫 Anda sudah mencapai batas maksimal 3 percobaan.");
                    return;
                }

                if (timerInterval) {
                    clearInterval(timerInterval);
                    timerInterval = null;
                }

                currentQ = 0;
                correctCount = 0;
                wrongCount = 0;
                answers = {};
                questions = shuffleMultipleChoice(questions);
                renderQuestion(currentQ);


                // ==========================================================
                // ⛔ FIX: Jika quizDuration = 0 → jangan buat timer / endTime!
                // ==========================================================
                if (quizDuration === 0) {
                    // console.log("⏱️ Quiz TANPA waktu → timer disabled");
                    localStorage.removeItem(storageKeyEnd);
                    localStorage.removeItem(storageKeyActive);
                    return; // STOP DI SINI — tidak perlu timerInterval
                }


                let endTime;
                const storedEnd = localStorage.getItem(storageKeyEnd);

                if (storedEnd && !forceNew) {
                    endTime = parseInt(storedEnd);
                } else {
                    endTime = Date.now() + quizDuration * 1000;
                    localStorage.setItem(storageKeyEnd, endTime);
                    localStorage.setItem(storageKeyActive, "true");
                }

                timerInterval = setInterval(() => {
                    const remaining = getRemainingTime();
                    const minutes = Math.floor(remaining / 60);
                    const seconds = remaining % 60;
                    const timerEl = document.getElementById("quiz-timer");
                    if (timerEl) timerEl.textContent = `⏳ ${minutes}:${seconds.toString().padStart(2, "0")}`;

                    if (remaining <= 0) {
                        clearInterval(timerInterval);
                        timerInterval = null;
                        localStorage.removeItem(storageKeyEnd);
                        localStorage.removeItem(storageKeyActive);
                        autoSubmitQuiz();
                    }
                }, 1000);
            }

             // ==================================================
            // 🔹 Cek status submission & tampilkan tampilan sesuai kondisi
            // ==================================================
            fetch(`/course/${itemId}/mc-submission/check`)
                .then(res => res.json())
                .then(data => {

                    // console.log("📌 [CHECK API] Response:", data);
                    // console.log("📌 Normal attempts:", data.attempts?.filter(a => a.is_remedial === 0).length);
                    // console.log("📌 Remedial attempts:", data.attempts?.filter(a => a.is_remedial === 1).length);
                    // console.log("Dataset duration: ", el.dataset.courseDuration);

                    const requiredGrade = passingGrade ? parseFloat(passingGrade) : 0;
                    const localEnd = localStorage.getItem(storageKeyEnd);
                    const localActive = localStorage.getItem(storageKeyActive);

                    // 🧩 Jika masih ada sesi aktif → lanjutkan quiz
                    if (localEnd && localActive && Date.now() < parseInt(localEnd)) {
                        // startQuiz(false);
                        startQuiz(false, data.attempts || []);
                        return;
                    }

                    // 🔹 Pisahkan submission normal & remedial
                    const normalSubmissions = data.attempts?.filter(a => a.is_remedial == 0) || [];
                    const remedialSubmissions = data.attempts?.filter(a => a.is_remedial == 1) || [];

                    // 🧩 Jika tidak ada submission normal tapi ada remedial → prescreen ulang
                    if (remedialSubmissions.length > 0 && normalSubmissions.length == 0) {
                        // console.log("🔁 Tidak ada submission normal, tapi ada remedial → tampilkan prescreen lagi.");
                        renderPrescreen(data, requiredGrade);
                        return;
                    }

                    // 🧩 Kalau sudah pernah submit normal → tampilkan hasil terakhir
                    if (data.exists && normalSubmissions.length > 0) {
                        const latest = normalSubmissions[normalSubmissions.length - 1];
                        renderResultDetail(
                            latest.grade,
                            0,
                            0,
                            questions.length,
                            latest.attempt_no,
                            data.attempts || []
                        );
                        return;
                    }

                    // 🧩 Kalau belum pernah ikut → tampilkan prescreen
                    renderPrescreen(data, requiredGrade);
                });

        function renderPrescreen(data, requiredGrade) {
            let html = "";
            const attempts = data.attempts || [];
            const latest = attempts.length > 0 ? attempts[attempts.length - 1] : null;

            // 🔹 Hitung attempt normal (is_remedial = 0)
            const normalAttempt = attempts.filter(a => a.is_remedial == 0).length;
            const attemptLimitReached = normalAttempt >= 3;

            // ===========================================================
            // 🔥 CASE 1 — SUDAH PERNAH SUBMISSION
            // ===========================================================
            if (latest && latest.grade !== null && latest.grade !== undefined) {
                const grade = parseFloat(latest.grade);
                const passed = grade >= requiredGrade;

                html += `
                <div id="quiz-container" class="mt-3 p-3 border rounded text-center">

                    <h6 class="fw-bold ${passed ? 'text-success' : 'text-danger'}">
                        ${passed ? '✅ Anda sudah lulus kuis ini' : '❌ Anda belum lulus kuis ini'}
                    </h6>

                    <p>Skor terakhir Anda: <strong>${grade}%</strong> 
                        (Minimal ${requiredGrade}%)</p>

                    <hr>

                    <p class="fw-semibold mb-2">📊 Riwayat Nilai Sebelumnya:</p>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle text-center">
                            <thead class="table-light">
                                <tr>
                                    <th>Percobaan</th>
                                    <th>Skor</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                // 🔥 LIST ATTEMPTS
                attempts.forEach(a => {
                    const scoreBadge = a.grade >= requiredGrade ? "bg-success" : "bg-danger";

                    const statusBadge =
                        a.is_remedial == 1
                        ? `<span class="badge bg-danger text-light">Remedial</span>`
                        : `<span class="badge bg-secondary">Jawaban Baru</span>`;

                    html += `
                        <tr>
                            <td>#${a.attempt_no}</td>
                            <td><span class="badge ${scoreBadge}">${a.grade}%</span></td>
                            <td>${statusBadge}</td>
                            <td><small>${formatDate(a.submitted_at)}</small></td>
                        </tr>`;
                });

                html += `
                            </tbody>
                        </table>
                    </div>
                `;

                // 🚫 Sudah mentok 3x attempt
                if (attemptLimitReached) {
                    html += `
                        <p class="text-danger fw-bold mt-3">
                            🚫 Anda telah mencapai batas maksimal 3 percobaan.
                        </p>`;
                } else {
                    // 🔁 Masih boleh quiz lagi
                    if (!previewMode) {
                        html += `
                            <button class="btn btn-outline-secondary mt-3 " id="start-quiz-btn">
                                ${passed ? "🔁 Coba Lagi (Optional)" : "🔁 Ulangi Kuis"}
                            </button>
                        `;
                    } else {
                        html += `
                            <div class="text-muted mt-3">
                                🔒 Mode Preview — Quiz tidak dapat mulai
                            </div>
                        `;
                    }
                }

                html += `</div>`; // END container
            }

            // ===========================================================
            // 🔥 CASE 2 — BELUM PERNAH SUBMISSION
            // ===========================================================
            else {
                html += `
                <div id="quiz-container" class="mt-3 p-3 border rounded text-center">

                    <h6>📝 Persiapan Quiz</h6>
                    <p>Total Soal: <strong>${questions.length}</strong></p>
                    ${quizDuration > 0 ? `<p>Batas Waktu: <strong>${quizDuration / 60} menit</strong></p>` : ""}
                    <p>Passing Grade: <strong>${requiredGrade}%</strong></p>
                `;

                // Jika ada attempt sebelumnya tapi belum pernah submit final
                if (attempts.length > 0) {
                    html += `
                        <hr>
                        <p class="fw-semibold mb-2">📊 Riwayat Nilai:</p>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle text-center">
                                <thead class="table-light">
                                    <tr>
                                        <th>Percobaan</th>
                                        <th>Skor</th>
                                        <th>Status</th>
                                        <th>Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;

                    attempts.forEach(a => {
                        const scoreBadge = a.grade >= requiredGrade ? "bg-success" : "bg-danger";
                        const statusBadge =
                            a.is_remedial == 1
                            ? `<span class="badge bg-danger text-light">Remedial</span>`
                            : `<span class="badge bg-secondary">Jawaban Baru</span>`;

                        html += `
                            <tr>
                                <td>#${a.attempt_no}</td>
                                <td><span class="badge ${scoreBadge}">${a.grade}%</span></td>
                                <td>${statusBadge}</td>
                                <td><small>${formatDate(a.submitted_at)}</small></td>
                            </tr>`;
                    });

                    html += `
                                </tbody>
                            </table>
                        </div>
                    `;
                }

                // 🚫 Batas attempt
                if (attemptLimitReached) {
                    html += `
                        <p class="text-danger fw-bold mt-3">
                            🚫 Anda telah mencapai batas maksimal 3 percobaan.
                        </p>`;
                } else {
                    html += `
                        ${!previewMode
                            ? `<button class="btn btn-success mt-3" id="start-quiz-btn"> Mulai Quiz</button>`
                            : `<div class="text-muted mt-3">🔒 Mode Preview — Quiz tidak dapat dimulai</div>`
                        }
                         `;
                }

                html += `</div>`;
            }

            // ===========================================================
            // Render ke panel kanan
            // ===========================================================
            detailPanel.innerHTML = html;

            // ===========================================================
            // Bind tombol START quiz
            // ===========================================================
            const startBtn = document.getElementById("start-quiz-btn");

            if (startBtn && !attemptLimitReached) {
                startBtn.addEventListener("click", () => {
                    startQuiz(true, attempts);
                });
            }
        }

        } 
        else if (type === "5") {
            const forumDuration = parseInt(el.dataset.courseDuration || 0); // menit
            const storageKeyEnd = `forum-${el.dataset.forumId}-endtime`;
            const storageKeySubmit = `forum-${el.dataset.forumId}-submitted`;

            const savedEnd = localStorage.getItem(storageKeyEnd);
            const alreadySubmitted = localStorage.getItem(storageKeySubmit);

            if (
                (forumDuration > 0 && savedEnd && Date.now() < savedEnd) ||
                (forumDuration > 0 && alreadySubmitted === "true") ||
                forumDuration === 0
            ) {
                renderForum(savedEnd);
            } else {
                // Pre-screen (hanya kalau ada durasi & belum mulai)
                content += `
                    <div id="forum-prescreen" class="mt-3 p-3 border rounded text-center">
                        <h6>💬 Forum Diskusi</h6>
                        <p><strong>Topik:</strong> ${forumTitle}</p>
                        <p><strong>Pertanyaan:</strong> ${forumQuestion}</p>
                        ${forumDuration > 0 ? `<p>Batas Waktu: <strong>${forumDuration} menit</strong></p>` : ""}
                        <button class="btn btn-success mt-3" id="start-forum-btn">Mulai Diskusi</button>
                    </div>
                `;
                detailPanel.innerHTML = content;

                document.getElementById("start-forum-btn").addEventListener("click", () => {
                    if (forumDuration > 0) {
                        let endTime = Date.now() + forumDuration * 60 * 1000;
                        localStorage.setItem(storageKeyEnd, endTime);
                        renderForum(endTime);
                    } else {
                        renderForum(null);
                    }
                });
            }

            function renderForum(endTime) {
                let forumContent = `
                    <div class="mt-3 p-3 border rounded position-relative">
                        <h6>💬 Forum Diskusi</h6>
                        <p class="mb-1"><strong>Topik:</strong> ${forumTitle}</p>
                        <p class="mb-3"><strong>Pertanyaan:</strong> ${forumQuestion}</p>
                `;

                // 🔹 Attachment
                if (forumAttachValue) {
                    if (forumAttachType === "pdf") {
                        const modalId = `forumPdfModal-${Date.now()}`;
                        forumContent += `
                            <div class="mt-3">
                                <h6 class="fw-bold">📄 Lampiran Forum (PDF)</h6>
                                <button class="btn btn-sm btn-outline-secondary mb-2"
                                        data-bs-toggle="modal" data-bs-target="#${modalId}">
                                    ⛶ Lihat Fullscreen
                                </button>
                                <div class="ratio ratio-16x9 border rounded overflow-hidden">
                                    <iframe src="/${forumAttachValue}" style="border:none;" title="Preview Forum PDF"></iframe>
                                </div>
                            </div>
                            <div class="modal fade" id="${modalId}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-fullscreen">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">📄 Lampiran Forum - Fullscreen</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-0">
                                            <iframe src="/${forumAttachValue}" style="border:0; width:100%; height:100%;"></iframe>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    } else if (forumAttachType === "video") {
                        forumContent += `
                            <div class="ratio ratio-16x9 mt-3">
                                <video controls>
                                    <source src="/${forumAttachValue}" type="video/mp4">
                                </video>
                            </div>`;
                    } else if (forumAttachType === "url") {
                        if (forumAttachValue.includes("youtube.com") || forumAttachValue.includes("youtu.be")) {
                            const videoId = forumAttachValue.includes("watch?v=") 
                                ? forumAttachValue.split("watch?v=")[1].split("&")[0] 
                                : forumAttachValue.split("/").pop();
                            forumContent += `
                                <div class="ratio ratio-16x9 mt-3">
                                    <iframe src="https://www.youtube.com/embed/${videoId}" frameborder="0" allowfullscreen></iframe>
                                </div>`;
                        } else if (forumAttachValue.includes("drive.google.com")) {
                            const match = forumAttachValue.match(/[-\w]{25,}/);
                            const fileId = match ? match[0] : null;
                            if (fileId) {
                                forumContent += `
                                    <div class="ratio ratio-16x9 mt-3">
                                        <iframe src="https://drive.google.com/file/d/${fileId}/preview" frameborder="0" allowfullscreen></iframe>
                                    </div>`;
                            }
                        } else {
                                forumContent += `
                                <div class="mt-3">
                                    <h6 class="fw-bold">📁 Lampiran Forum</h6>
                                    <p class="text-muted mb-1">File mempunyai ekstensi lain.</p>
                                    <a href="/${forumAttachValue}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        🔗 Buka File
                                    </a>
                                </div>`;
                        }
                    }
                }

                // Timer badge
                if (forumDuration > 0) {
                    forumContent += `
                        <div id="forum-timer" 
                            class="position-absolute top-0 end-0 m-2 badge text-dark fs-6">
                            <span id="forum-countdown"></span>
                        </div>`;
                }

                // Form + thread
                forumContent += `
                    <div class="d-flex mt-3">
                        <textarea id="forum-reply" class="form-control me-2" rows="2" placeholder="✍️ Tulis komentar..."></textarea>
                        <button class="btn btn-secondary" id="submit-forum-reply-btn"
                                data-forum-id="${el.dataset.forumId}" data-item-id="${el.dataset.itemId}">
                            Kirim
                        </button>
                    </div>
                    <div id="forum-thread-${el.dataset.forumId}" 
                        class="mb-2 mt-4 border rounded p-2 bg-light" 
                        style="max-height:500px; overflow-y:auto;">
                        ${forumDuration > 0 
                            ? `<p class="text-muted fst-italic mb-2">🔒 Balasan forum akan muncul setelah Anda mengirim jawaban.</p>` 
                            : `<p class="text-muted fst-italic mb-2">⏳ Memuat diskusi...</p>`}
                    </div>
                    </div>
                `;

                detailPanel.innerHTML = forumContent;

                const replyBtn = document.getElementById("submit-forum-reply-btn");
                const textarea = document.getElementById("forum-reply");

                // Timer countdown
                if (forumDuration > 0 && endTime) {
                    const countdownEl = document.getElementById("forum-countdown");
                    const timer = setInterval(() => {
                        const remaining = Math.max(0, Math.floor((endTime - Date.now()) / 1000));
                        const mins = Math.floor(remaining / 60);
                        const secs = remaining % 60;
                        if (countdownEl) {
                            countdownEl.textContent = `${mins}:${secs.toString().padStart(2,"0")}`;
                        }
                        if (remaining <= 0) {
                            clearInterval(timer);
                            localStorage.removeItem(storageKeyEnd);
                            if (replyBtn) {
                                replyBtn.disabled = true;
                                replyBtn.textContent = "⛔ Waktu Habis";
                            }
                            if (textarea) textarea.readOnly = true;
                        }
                    }, 1000);
                }

                // Handler submit
                if (replyBtn) {
                    replyBtn.addEventListener("click", (e) => {
                        e.preventDefault();

                        if (forumDuration > 0) {
                            replyBtn.disabled = true;
                            if (textarea) textarea.readOnly = true;
                            localStorage.setItem(storageKeySubmit, "true");
                            localStorage.removeItem(storageKeyEnd);
                            replyBtn.textContent = "✅ Balasan terkirim";
                        } 
                        // Tanpa durasi → tetap bisa submit berkali-kali

                        loadForumReplies(el.dataset.forumId);
                    });
                }

                // Sudah submit sebelumnya
                if (forumDuration > 0 && alreadySubmitted === "true") {
                    if (replyBtn) {
                        replyBtn.disabled = true;
                        replyBtn.textContent = "✅ Balasan sudah terkirim";
                    }
                    if (textarea) textarea.readOnly = true;
                    loadForumReplies(el.dataset.forumId);
                }

                // Tanpa durasi → langsung load diskusi
                if (forumDuration === 0) {
                    loadForumReplies(el.dataset.forumId);
                }
            }
            return;
        }
        else if (type === "6") { 
        // Sertifikat
        content += `
            <div class="mt-3 text-center">
                <h6>🎓 Sertifikat Kelulusan</h6>
                <div class="certificate-wrapper mb-3">
                    <img src="/assets/img/course/default-certificated.jpg" 
                        class="img-fluid rounded border shadow-sm" 
                        style="max-height:400px; width:100%; object-fit:contain;">
                </div>
                <button class="btn btn-secondary" id="generate-cert-btn" data-course-id="${itemId}">
                    Ambil Sertifikat
                </button>
            </div>`;
        } 
        else if (type === "7") {
            // =====================================================
            // 1️⃣ Ambil data dasar dari elemen dataset
            // =====================================================
            const assignAttachment   = el.dataset.file || "";
            const gradeAttachment    = el.dataset.gradeAttachment || "";
            const feedbackAttachment = el.dataset.feedbackAttachment || "";
            const passingGrade       = el.dataset.passingGradeItem || null;
            const submittedFile      = el.dataset.submittedFile || "";
            const submittedAt        = el.dataset.submittedAt || "";
            const isRemedial         = parseInt(el.dataset.isRemedial || 1);
            const requiredGrade      = passingGrade ? parseFloat(passingGrade) : 0;
            const itemId             = el.dataset.itemId;
            const attachments        = JSON.parse(el.dataset.attachments || "[]");
            let duration             = parseInt(el.dataset.courseDuration); // ⏱ Durasi dalam menit
            let countdownInterval; // interval timer

            const storageKeyEnd = `attach-${itemId}-endtime`;
            const storageKeySubmit = `attach-${itemId}-submitted`;

            // console.log("Duration", duration);
            
            // =====================================================
            // 2️⃣ Lampiran dari Instruktur
            // =====================================================
            if (assignAttachment) {
                const modalId = `assignModal-${Date.now()}`;
                content += `
                    <div class="mt-4 p-3 border rounded position-relative">

                        <h6 class="fw-bold">📄 Contoh / Materi dari Instruktur</h6>
                        <button class="btn btn-sm btn-outline-secondary mb-2" 
                                data-bs-toggle="modal" data-bs-target="#${modalId}">
                            ⛶ Lihat Fullscreen
                        </button>
                        <div class="ratio ratio-16x9 border rounded overflow-hidden">
                            <iframe src="/${assignAttachment}" style="border:none;" title="Preview Attachment"></iframe>
                        </div>
                    </div>
                    <div class="modal fade" id="${modalId}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-fullscreen">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">📄 Contoh / Materi - Fullscreen</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body p-0">
                                    <iframe src="/${assignAttachment}" style="border:0; width:100%; height:100%;"></iframe>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }

            // =====================================================
            // 3️⃣ Loading sementara
            // =====================================================
            detailPanel.innerHTML = `
                ${content}
                <div class="text-center py-3">
                    <div class="spinner-border text-secondary" role="status"></div>
                    <p class="text-muted small mt-2 mb-0">Memuat data tugas...</p>
                </div>
            `;

            // =====================================================
            // 4️⃣ Cek status submission dari server
            // =====================================================
            // console.log("🟦 [TYPE 7] Checking submission for item:", itemId);
            // console.log("🟦 Duration received:", duration);
            // console.log("🟦 storageKeyEnd:", storageKeyEnd);
            // console.log("🟦 storageKeySubmit:", storageKeySubmit);
            // =====================================================
            // 4️⃣ Cek status submission dari server
            // =====================================================
            fetch(`/course/${itemId}/check-submission`)
                .then(res => res.json())
                .then(data => {

                    // console.log("🟦 [TYPE 7] Checking submission for item:", itemId);
                    // console.log("🟩 SERVER RESPONSE:", data);

                    const alreadySubmitted = data.exists === true;
                    const allowUpload = !alreadySubmitted;

                    // console.log("🟩 alreadySubmitted =", alreadySubmitted);
                    // console.log("🟩 allowUpload =", allowUpload);

                    // =====================================================
                    // 🧩 PRE-SCREEN 
                    // =====================================================
                    let localSubmitted = localStorage.getItem(storageKeySubmit);
                    let localEnd = localStorage.getItem(storageKeyEnd);

                    // console.log("🟦 localSubmitted =", localSubmitted);
                    // console.log("🟦 localEnd =", localEnd);

                    // =====================================================
                    // ❗ FIX UTAMA: Jika timer lama sudah expired → reset
                    // =====================================================
                    if (localEnd && Date.now() > parseInt(localEnd)) {
                        console.log("🟠 Timer expired — resetting localEnd");
                        localEnd = null;
                        localStorage.removeItem(storageKeyEnd);
                    }

                    // console.log("🟧 PRESCREEN CONDITION CHECK:");
                    // console.log("   - alreadySubmitted =", alreadySubmitted);
                    // console.log("   - duration > 0 =", duration > 0);
                    // console.log("   - localEnd is null =", localEnd === null);

                    // =====================================================
                    // Jika belum submit final di server
                    // =====================================================
                    if (!data.exists) {
                        localStorage.removeItem(storageKeySubmit);

                        // kalau belum pernah start timer (belum klik Mulai)
                        if (!localEnd) {
                            // console.log("🟩 Showing PRE-SCREEN, no timer found!");

                            detailPanel.innerHTML = `
                                <div id="attach-prescreen" class="mt-3 p-3 border rounded text-center">
                                    <h6>📦 Persiapan Pengumpulan Tugas</h6>
                                    <p>Batas waktu: <strong>${duration} menit</strong></p>
                                    <p>Instruksi Tugas: <strong>${desc ?? 'Tidak ada instruksi.'}</strong></p>
                                    <p>Pastikan Anda sudah menyiapkan file PDF sebelum memulai.</p>

                                    ${!previewMode
                                        ? `<button class="btn btn-success mt-3" id="start-attach-btn"> Mulai Pengumpulan</button>`
                                        : `<div class="text-muted mt-3">🔒 Mode Preview — Unggahan tugas tidak dapat dimulai</div>`
                                    }
                                </div>
                            `;

                            const startBtn = document.getElementById("start-attach-btn");
                            if (startBtn) {
                                startBtn.addEventListener("click", () => {
                                    let endTime = Date.now() + duration * 60 * 1000;

                                    // console.log("🟢 Timer started at:", Date.now());
                                    // console.log("🟢 Timer will end at:", endTime);

                                    localStorage.setItem(storageKeyEnd, endTime);
                                    renderAttachment(endTime, false);
                                });
                            }

                            return; // STOP — jangan render main content
                        }
                    } else {
                        // kalau sudah submit final
                        // console.log("🟩 Final submission found — disabling timer.");
                        localStorage.setItem(storageKeySubmit, "true");
                        localStorage.removeItem(storageKeyEnd);
                    }

                    // =====================================================
                    // 🔧 Render konten utama attachment
                    // =====================================================
                    // console.log("🟦 renderAttachment() triggered with endTime:", localEnd);
                    renderAttachment(localEnd, alreadySubmitted);
                })
                .catch(err => {
                    // console.error("⚠️ Gagal mengambil data submission:", err);
                    detailPanel.innerHTML = content;
                });

            // =====================================================
            // 🧩 RENDER ATTACHMENT FUNCTION (FINAL FIXED VERSION)
            // =====================================================
            function renderAttachment(endTime, alreadySubmitted = false) {
                // console.log("🟦 renderAttachment() triggered with endTime:", endTime);

                const allowUpload = !alreadySubmitted;

                // =====================================================
                // 🟦 1. SISIPKAN TIMER BOX DI PALING ATAS (WAJIB ADA)
                // =====================================================
                let headerTimer = "";

                if (endTime && duration > 0 && allowUpload) {
                    let remaining = Math.max(0, Math.floor((endTime - Date.now()) / 1000));
                    headerTimer = `
                        <div id="timer-box"
                            class="position-absolute top-0 end-0 mt-2 me-3 text-danger fw-bold small">
                            ⏱ ${formatTime(remaining)}
                        </div>
                    `;
                }

                // =====================================================
                // 🔹 2. BUNGKUS KONTEN DENGAN WRAPPER TIMER
                // =====================================================
                let html = `
                    <div class="mt-3 p-3 border rounded position-relative">
                        ${headerTimer}
                    </div>
                `;

                html += content; // append isi sebelumnya

                // =====================================================
                // 🔹 3. Render Riwayat Pengumpulan
                // =====================================================
                if (attachments.length > 0) {
                    html += `
                        <div class="mt-3 p-3 border rounded bg-light">
                            <h6 class="fw-bold text-primary mb-3">📜 Riwayat Pengumpulan (${attachments.length})</h6>
                    `;

                    attachments.forEach((a, i) => {
                        const date = a.submitted_at ? dayjs(a.submitted_at).format("DD MMM YYYY HH:mm") : "-";
                        const grade = a.grade ? parseFloat(a.grade).toFixed(2) : "-";
                        const status = a.is_remedial == 1 ? "Jawaban Remedial" : "Jawaban Baru";
                        const badgeClass = a.is_remedial == 1 ? "bg-warning text-dark" : "bg-success";

                        html += `
                            <div class="border rounded p-2 mb-2 bg-white shadow-sm">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong>Pengumpulan #${i + 1}</strong>
                                    <span class="badge ${badgeClass}">${status}</span>
                                </div>
                                <p class="small text-muted mb-1">${date}</p>
                                ${
                                    a.file_path
                                        ? `<a href="/${a.file_path}" target="_blank" class="btn btn-sm btn-outline-primary mb-2">
                                            📄 Lihat File
                                        </a>`
                                        : ""
                                }
                                <p class="small mb-1"><strong>Nilai:</strong> ${grade}</p>
                                <p class="small mb-0"><strong>Feedback:</strong> ${a.feedback || "Belum ada feedback"}</p>
                            </div>
                        `;
                    });

                    html += `</div>`;
                }

                // =====================================================
                // 🔹 4. Form Upload / Remedial
                // =====================================================
                if (allowUpload) {
                    const isRedo = isRemedial == 1 && (submittedFile || submittedAt);

                    html += `
                        <div class="mt-3 p-3 border rounded">
                            <h6>${isRedo ? "📎 Unggah Perbaikan Tugas (.pdf)" : "📎 Unggah Tugas (.pdf)"}</h6>
                            <form id="upload-task-form"
                                action="/item/${itemId}/submission"
                                method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="_token" 
                                    value="${document.querySelector('meta[name="csrf-token"]').content}">
                                <input type="file" name="task_file" class="form-control mb-2" accept=".pdf,application/pdf" required>
                                <button type="submit" class="btn btn-secondary btn-sm">
                                    ${isRedo ? "Kirim Ulang Tugas (Remedial)" : "Kirim Tugas"}
                                </button>
                            </form>
                            <div id="upload-result" class="mt-2"></div>
                        </div>
                    `;
                } else {
                    html += `
                        <div class="mt-3 p-3 border rounded bg-light text-center">
                            <p class="text-muted mb-0 fst-italic">
                                ✅ Anda sudah mengirim tugas final dan tidak dapat mengunggah ulang.
                            </p>
                        </div>
                    `;
                }

                // =====================================================
                // ⬇️ Render Semua HTML Ke Layar
                // =====================================================
                detailPanel.innerHTML = html;
                updateProgress(currentIndex, currentTotal);

                // =====================================================
                // 🔥 5. TIMER COUNTDOWN AKTIF
                // =====================================================
                if (endTime && duration > 0 && allowUpload) {
                    let remainingSeconds = Math.max(0, Math.floor((endTime - Date.now()) / 1000));
                    const timerBox = document.getElementById("timer-box");

                    // console.log("⏱ Timer element:", timerBox);

                    if (timerBox) {
                        const interval = setInterval(() => {
                            remainingSeconds--;

                            timerBox.textContent = `⏱ ${formatTime(remainingSeconds)}`;

                            if (remainingSeconds <= 0) {
                                clearInterval(interval);
                                localStorage.removeItem(storageKeyEnd);

                                // console.log("⏳ Timer finished → auto-submitting attachment...");
                                autoSubmitAttachment();
                            }
                        }, 1000);
                    } else {
                        // console.warn("⚠️ Timer box not found. Countdown skipped.");
                    }
                }
            }

            // =====================================================
            // 🧩 AUTO SUBMIT FUNCTION
            // =====================================================
            // function autoSubmitAttachment() {
            //     const form = document.getElementById("upload-task-form");
            //     if (!form) return;

            //     const fileInput = form.querySelector('input[name="task_file"]');
            //     // if (!fileInput || fileInput.files.length === 0) return;

            //         if (!fileInput || fileInput.files.length === 0) {
            //             // console.log("⚠️ Tidak ada file diunggah dan waktu habis!");
            //         }
            //     const formData = new FormData(form);
                
            //     fetch(form.action, { method: "POST", body: formData })
            //         .then(res => res.json())
            //         .then(data => {
            //             if (data.success) {
            //                 localStorage.setItem(storageKeySubmit, "true");
            //                 localStorage.removeItem(storageKeyEnd);
            //                 detailPanel.innerHTML = `
            //                     <div class="alert alert-info text-center mt-3">
            //                         ⏰ Waktu habis, tugas Anda telah dikirim otomatis.
            //                     </div>`;
            //                     setTimeout(() => {
            //                         const itemEl = document.querySelector(`[data-item-id="${itemId}"]`);
            //                         if (itemEl && typeof renderContent === "function") {
            //                             renderContent(itemEl); // panggil fungsi render isi materi
            //                         } else if (typeof renderCourseItem === "function") {
            //                             renderCourseItem(itemId); // fallback
            //                         } else {
            //                             // console.warn("⚠️ Tidak menemukan fungsi renderContent atau renderCourseItem");
            //                         }
            //                     }, 1500);
            //             } else {
            //                 // console.error("Auto-submit gagal:", data);
            //             }
            //         })
            //         .catch(err => console.error("❌ Auto submit error:", err));
            // }
            
            function autoSubmitAttachment() {
                const form = document.getElementById("upload-task-form");
                if (!form) return;

                const fileInput = form.querySelector('input[name="task_file"]');
                if (!fileInput || fileInput.files.length === 0) {
                    // console.log("⚠️ Tidak ada file diunggah dan waktu habis!");
                }

                const formData = new FormData(form);
                
                fetch(form.action, { method: "POST", body: formData })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            localStorage.setItem(storageKeySubmit, "true");
                            localStorage.removeItem(storageKeyEnd);

                            // Tampilkan pemberitahuan
                            detailPanel.innerHTML = `
                                <div class="alert alert-info text-center mt-3">
                                    ⏰ Waktu habis, tugas Anda telah dikirim otomatis.
                                </div>`;

                            // 🔥 Langsung REFRESH data dari server agar submission muncul
                            fetch(`/course/${itemId}/check-submission`)
                                .then(res => res.json())
                                .then(latest => {

                                    // console.log("🔄 Updated submission fetched:", latest);

                                    if (latest.attachments) {
                                        attachments.length = 0;
                                        attachments.push(...latest.attachments);
                                    }

                                    // Render ulang UI terbaru
                                    renderAttachment(null, latest.exists === true);
                                })
                                .catch(err => console.error("❌ Refresh after autosubmit error:", err));

                        } else {
                            // console.error("Auto-submit gagal:", data);
                        }
                    })
                    .catch(err => console.error("❌ Auto submit error:", err));
            }

            // =====================================================
            // Helper: ubah detik → mm:ss
            // =====================================================
            function formatTime(seconds) {
                const m = Math.floor(seconds / 60);
                const s = seconds % 60;
                return `${m}:${s.toString().padStart(2, "0")}`;
            }
        }

        // =====================================================
        // 7️⃣ Kembalikan ke posisi terakhir setelah reload
        // =====================================================
            const lastItemId = localStorage.getItem("lastCourseItemId");
            const lastScrollY = localStorage.getItem("lastScrollY");

            if (lastItemId) {
                const lastEl = document.querySelector(`[data-item-id="${lastItemId}"]`);
                if (lastEl) lastEl.scrollIntoView({ behavior: "smooth", block: "center" });

                if (lastScrollY) window.scrollTo(0, parseInt(lastScrollY));

                // bersihkan cache
                localStorage.removeItem("lastCourseItemId");
                localStorage.removeItem("lastScrollY");
            }

        // tampilkan ke panel
        detailPanel.innerHTML = content;
        updateProgress(currentIndex, currentTotal);
        // saveProgress(el);
        
        }

        itemOptions.forEach(el => {
            el.addEventListener("click", function (e) {
                const status = this.dataset.status || "unlocked"; 

                if (status.startsWith("locked")) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    alert("❌ Materi ini masih terkunci dan belum bisa dibuka.");
                    return;
                }

                // render kalau unlocked
                renderContent(this);

                // update tombol sesuai posisi
                updateNextButtonText();

                // cek apakah ini item terakhir
                const idx = allItems.indexOf(this);
                if (idx === allItems.length - 1) {
                    const storedProgress = localStorage.getItem("lastProgress");
                    if (!storedProgress) {
                        // console.log("⚠️ Belum ada progress, tombol finish tetap manual");
                        return; 
                    }

                    // ✅ validasi: hanya boleh auto-finish kalau semua item sudah completed
                    const notCompleted = allItems.filter(el => !el.classList.contains("bg-success"));
                    if (notCompleted.length > 0) {
                        // console.log("⚠️ Masih ada materi yang belum selesai, jangan confetti!");
                        return;
                    }

                    finishCourse(this);
                }
            });
        });

    // ==============================
    // Next Button and Saved Progress
    // ==============================
    const allItems = [...document.querySelectorAll(".item-option")];

    // 🔹 helper cek submission di server
    async function validateSubmission(itemEl) {
        try {
            let res = await fetch(`/course/${itemEl.dataset.itemId}/check-submission`);
            let data = await res.json();

            // 🟢 DEBUG (bisa dihapus nanti)
            // console.log("🔎 Submission check result:", {
            //     itemId: itemEl.dataset.itemId,
            //     type: itemEl.dataset.type,
            //     exists: data.exists,
            //     all_completed: data.all_completed
            // });

            // ✅ NEW: Auto-unlock localStorage if backend says submission is gone
            if (data.unlock === true) {
                const id = itemEl.dataset.itemId;
                localStorage.removeItem(`essay-${id}-endtime`);
                localStorage.removeItem(`essay-${id}-submitted`);
                // console.log(`🔓 Unlock applied for item ${id}`);
            }

            return {
                    exists: data.exists,
                    is_remedial: data.is_remedial,
                    all_completed: data.all_completed,
                    grade_passed: data.grade_passed
             }; // true = sudah submit

        } catch (err) {
            console.error("❌ Error cek submission:", err);
            return false;
        }
    }
    
     function handleRemarks(status) {
        const remarkAttention = document.getElementById('remarkAttention');
        const remarkList = document.getElementById('remarkList');
         const remarkTitle = document.getElementById('remarkTitle');

        if (!remarkAttention || !remarkList || !remarkTitle) return;

        remarkList.innerHTML = '';
        remarkTitle.classList.add('d-none');

        const remarks = [];

        if (!status.all_completed) {
            remarks.push('Beberapa aktivitas kursus belum memenuhi ketentuan penyelesaian.');
            remarks.push('Pastikan seluruh materi, kuis, dan tugas telah disubmit dengan benar dan tidak berada dalam status remedial.');
            remarks.push('Pastikan seluruh materi telah berwarna hijau dengan menekan tombol "Selanjutnya" hingga akhir materi.');
            remarkAttention.classList.add('text-dark');
        }

        // aman
        if (remarks.length === 0) {
            remarks.push('✅ Oke aman, Anda berhak menyelesaikan kursus');
            remarkAttention.classList.add('text-success');
        }

        remarks.forEach(text => {
            const li = document.createElement('li');
            li.innerHTML = text;
            remarkList.appendChild(li);
        });

        remarkAttention.classList.remove('d-none');
        remarkTitle.classList.remove('d-none');
    }
    
    function showCourseCompletedUI() {
        const message = document.getElementById("courseCompletedMessage");
        if (message) {
            message.classList.remove("d-none");
        }
            // console.log(" Called Course Completed UI updated");
    }
    
    // =====================
        async function finishCourse(activeItem) {
            try {
                // ⛔ Pastikan progress TERAKHIR benar-benar tersimpan
                await saveProgress(activeItem);
    
                // ✅ Optional: update progress bar setelah save sukses
                updateProgress();
    
                // 🎉 UX feedback SETELAH data aman
                confetti({
                    particleCount: 150,
                    spread: 80,
                    origin: { y: 0.6 },
                });
                
                showCourseCompletedUI();

                setTimeout(() => {
                    alert("🎉 Semua materi berhasil diselesaikan. Kursus ditandai selesai.");
                    // 👉 optional redirect ke sertifikat / summary
                    // window.location.href = `/course/${activeItem.dataset.courseId}/finish`;
                }, 300);
    
            } catch (err) {
                // console.error("❌ Gagal menyimpan progress item terakhir:", err);
    
                alert(
                    "⚠️ Progress materi terakhir belum berhasil disimpan.\n" +
                    "Silakan tunggu sebentar dan coba lagi sebelum menutup halaman."
                );
            }
        }
    // =====================

    // async function updateNextButtonText() {
    //     const activeItem = document.querySelector(".item-option.active");
    //     if (!activeItem || !nextButton) return;

    //     const idx = allItems.indexOf(activeItem);

    //     // =============================
    //     // 📌 ITEM TERAKHIR
    //     // =============================
    //     if (idx === allItems.length - 1) {
    //         nextButton.textContent = "Selesaikan Kursus";
    //         nextButton.classList.add("btn-success");
    //         nextButton.classList.remove("btn-primary");

    //         const status = await validateSubmission(activeItem);
    //         const type = activeItem.dataset.type;
    //         const isSubmissionItem = ["3", "4", "7"].includes(type);

    //         console.log("🔍 FINAL VALIDATION:");
    //         console.log("exists:", status.exists);
    //         console.log("is_remedial:", status.is_remedial);
    //         console.log("grade_passed:", status.grade_passed);
    //         console.log("all_completed:", status.all_completed);

    //         // =======================================================
    //         // RULE FINAL
    //         // - Submission item → full validation
    //         // - Non-submission  → hanya harus all_completed
    //         // =======================================================
    //         const mustDisable =
    //             (
    //                 isSubmissionItem && (
    //                     !status.exists ||
    //                     status.is_remedial != 0 ||
    //                     !status.grade_passed
    //                 )
    //             )
    //             || !status.all_completed;

    //         nextButton.disabled = mustDisable;
    //         return;
    //     }

    //     // =============================
    //     // 📌 BUKAN ITEM TERAKHIR
    //     // =============================
    //     nextButton.textContent = "Selanjutnya";
    //     nextButton.classList.add("btn-primary");
    //     nextButton.classList.remove("btn-success");
    //     nextButton.disabled = false;
    // }
    
    let canFinishCourse = false;
    async function updateNextButtonText() {
        const activeItem = document.querySelector(".item-option.active");
        if (!activeItem || !nextButton) return;
    
        const idx = allItems.indexOf(activeItem);
        const type = activeItem.dataset.type;
        const isSubmissionItem = ["3", "4", "7"].includes(type);
    
        // =============================
        // 📌 ITEM TERAKHIR
        // =============================
        if (idx === allItems.length - 1) {
            nextButton.textContent = "Selesaikan Kursus";
            nextButton.classList.add("btn-success");
            nextButton.classList.remove("btn-primary");
        
            const status = await validateSubmission(activeItem);
            const type = activeItem.dataset.type;
            const isSubmissionItem = ["3", "4", "7"].includes(type);
        
            // console.log("🔍 FINAL VALIDATION:");
            // console.log("exists:", status.exists);
            // console.log("is_remedial:", status.is_remedial);
            // console.log("grade_passed:", status.grade_passed);
            // console.log("all_completed:", status.all_completed);

            handleRemarks(status);
            
           const mustDisable =
                (
                    isSubmissionItem && (
                        !status.exists ||
                        status.is_remedial != 0 ||
                        !status.grade_passed
                    )
                )
                || !status.all_completed;

                canFinishCourse = !mustDisable;

            // nextButton.disabled = mustDisable;
            return;
        }
            
        // =============================
        // 📌 BUKAN ITEM TERAKHIR
        // =============================
        nextButton.textContent = "Selanjutnya";
        nextButton.classList.add("btn-primary");
        nextButton.classList.remove("btn-success");
        nextButton.disabled = false;
    }


    // ==============================
    // Klik Tombol Next
    // ==============================
    // function isModuleCompleted(moduleId) {
    //     const items = document.querySelectorAll(`.item-option[data-module-id="${moduleId}"]`);

        
    //     return Array.from(items).every(el => el.classList.contains("bg-success"));
    // }

    function isModuleCompleted(moduleId) {
        const items = document.querySelectorAll(`.item-option[data-module-id="${moduleId}"]`);

        // console.group(`🧩 Cek Modul ${moduleId}`);

        // console.log(`Total item dalam modul: ${items.length}`);

        let completedCount = 0;

        items.forEach((el, idx) => {
            const isDone = el.classList.contains("bg-success");

            // console.log(
            //     `   ▸ Item ${idx + 1} (ID: ${el.dataset.itemId})`,
            //     `→ status: ${isDone ? "✅ Selesai" : "❌ Belum"}`
            // );

            if (isDone) completedCount++;
        });

        // console.log(`🔍 Hasil akhir: ${completedCount}/${items.length} item selesai`);
        // console.groupEnd();

        // RETURN BOOLEAN
        return completedCount === items.length;
    }

    function unlockModuleBadge(moduleId) {
        const header = document.querySelector(`#materi${moduleId}Heading button`);
        if (!header) return;

        // Hapus badge lama
        header.querySelectorAll("span.badge").forEach(b => b.remove());

        // Tambahkan badge "Unlocked"
        const badge = document.createElement("span");
        badge.classList.add("badge", "bg-success", "ms-2");
        badge.textContent = "Unlocked";
        header.appendChild(badge);
    }

    // if (nextButton) {
    //     nextButton.addEventListener("click", async (e) => {
            
    //         const activeItem = document.querySelector(".item-option.active");
    //         if (!activeItem) {
    //             alert("⚠️ Belum ada materi yang dipilih!");
    //             // console.warn("⛔ Tidak ada item aktif saat klik tombol Next.");
    //             return;
    //         }

    //         nextButton.disabled = true; // prevent double click
    //         // console.log("🧩 Tombol 'Selanjutnya' diklik");
    //         // console.log("📘 Item aktif:", activeItem.dataset.itemId, "type:", activeItem.dataset.type);

    //         try {
    //             // ✅ Validasi submission (tipe 3/4/5/7)
    //             const requiresSubmission = ["3", "4", "5", "7"].includes(activeItem.dataset.type);
    //             // if (requiresSubmission) {
    //             //     const ok = await validateSubmission(activeItem);
    //             //     // console.log("📦 Hasil validasi submission:", ok);
    //             //     if (!ok) {
    //             //         alert("⚠️ Anda harus mengerjakan/mengirim submission sebelum lanjut.");
    //             //         // console.warn("⛔ Submission belum lengkap, render dibatalkan.");
    //             //         return;
    //             //     }
    //             // }

    //             if (requiresSubmission) {
    //                 const status = await validateSubmission(activeItem);

    //                 // ❌ Belum submit sama sekali
    //                 if (!status.exists) {
    //                     alert("⚠️ Anda belum mengirim submission.");
    //                     return;
    //                 }

    //                 // ❌ Sudah submit tapi masih remedial
    //                 if (status.is_remedial != 0) {
    //                     alert("⚠️ Submission Anda masih berstatus remedi.\nSilakan submit ulang sampai final (is_remedial = 0).");
    //                     return;
    //                 }

    //                 // ❌ Cek apakah item lain dalam course belum final (opsional)
    //                 if (!status.all_completed) {
    //                     // Tidak wajib blok di sini, tapi boleh kalau kamu mau
    //                     // alert("⚠️ Anda masih memiliki submission yang belum final.");
    //                     // return;
    //                 }


    //             }


    //             let idx = allItems.indexOf(activeItem);
    //             const nextItem = allItems[idx + 1];

    //             // ✅ Tidak ada item berikutnya (artinya terakhir)
    //           if (!nextItem) {
    //                 // console.log("🎯 Semua item selesai, buka feedback modal sebelum confetti.");

    //                 // Ambil course_id dari item aktif
    //                 const courseId = activeItem.dataset.courseId;
    //                 const feedbackModalEl = document.getElementById("feedbackModal");
    //                 const feedbackInput = feedbackModalEl.querySelector("#feedback_course_id");
    //                 feedbackInput.value = courseId; // ✅ pastikan hidden input terisi

    //                 // Buka modal feedback
    //                 const feedbackModal = new bootstrap.Modal(feedbackModalEl);
    //                 feedbackModal.show();

    //                 // Tambah listener submit (sekali saja)
    //                 const form = document.getElementById("feedbackForm");

    //                 const handleFeedbackSubmit = async (e) => {
    //                     e.preventDefault();

    //                     try {
    //                         const formData = new FormData(form);
    //                         const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    //                         // Kirim feedback ke route Laravel
    //                         const res = await fetch("/feedback/store", {
    //                             method: "POST",
    //                             headers: { "X-CSRF-TOKEN": csrfToken },
    //                             body: formData,
    //                         });

    //                         if (res.ok) {
    //                             // console.log("✅ Feedback terkirim, lanjutkan ke finishCourse()");
    //                             feedbackModal.hide();
    //                             finishCourse(activeItem); // confetti & alert
    //                         } else {
    //                             // console.warn("⚠️ Gagal mengirim feedback.");
    //                             alert("Terjadi kesalahan saat mengirim feedback. Silakan coba lagi.");
    //                         }
    //                     } catch (err) {
    //                         // console.error("❌ Error submit feedback:", err);
    //                         alert("Terjadi kesalahan tak terduga.");
    //                     } finally {
    //                         form.removeEventListener("submit", handleFeedbackSubmit);
    //                     }
    //                 };

    //                 // Pasang event listener sekali saja
    //                 form.addEventListener("submit", handleFeedbackSubmit);

    //                 return; // hentikan eksekusi lanjut
    //             }

    //             // ✅ CEK STATUS LOCKED (pakai nextItem, bukan this)
    //             const rawStatus = nextItem.dataset.status;
    //             const status = (rawStatus || "unlocked").trim().toLowerCase();
    //             const isLocked =
    //                 status === "locked" ||
    //                 status.startsWith("locked") ||
    //                 nextItem.classList.contains("locked");

    //             // console.log(
    //             //     `🔍 Cek status next item [${nextItem.dataset.itemId}]:`,
    //             //     `"${rawStatus}" → parsed="${status}"`,
    //             //     "class locked?", nextItem.classList.contains("locked")
    //             // );

    //             if (isLocked) {
    //                 const startDate = nextItem.dataset.courseStart || "Belum ditentukan";
    //                 const endDate = nextItem.dataset.courseEnd || "Belum ditentukan";

    //                 e.preventDefault();
    //                 e.stopPropagation();
    //                 e.stopImmediatePropagation();
    //                 alert(
    //                 "🔒 Materi berikutnya masih terkunci dan belum dimulai.\n\n" +
    //                 "📅 Jadwal Kursus:\n" +
    //                 `   🕐 Dibuka : ${startDate}\n` +
    //                 `   ⏰ Ditutup: ${endDate}`
    //                 );
    //                 return;
    //             }

    //             // ✅ Semua validasi lolos → lanjut renderContent()
    //             // console.log("✅ Semua validasi lolos → lanjut renderContent()");

    //             // saveProgress(activeItem);

    //             // ============================
    //             // 🟢 PROGRESS LOGIC
    //             // ============================
    //             const type = activeItem.dataset.type;
    //             const status2 = await validateSubmission(activeItem);
    //             // console.log("status2:", status2);

    //             // Jika item BUTUH submission: hanya save kalau final (bukan remedial)
    //             if (["3","4","7"].includes(type)) {

    //                 // kondisi final submission
    //                 if (status2.exists && status2.is_remedial == 0) {
    //                     saveProgress(activeItem);
    //                 }

    //             } else {
    //                 // item biasa → langsung save progress
    //                 saveProgress(activeItem);
    //             }

    //             const currentModule = activeItem.dataset.moduleId;
    //             const nextModule = nextItem.dataset.moduleId;
                
    //             // ======================================
    //             // 🔥 Jika CURRENT module sudah complete
    //             // ======================================
    //             // if (isModuleCompleted(currentModule)) {

    //             //     // 1. Ubah badge module berikutnya menjadi 'Unlocked'
    //             //     if (nextModule) {
    //             //         unlockModuleBadge(nextModule);
    //             //     }

    //             //     // 2. Buka accordion module berikutnya
    //             //     if (nextModule) {
    //             //         const nextCollapse = document.getElementById(`materi${nextModule}Items`);
    //             //         if (nextCollapse && !nextCollapse.classList.contains("show")) {
    //             //             new bootstrap.Collapse(nextCollapse, { toggle: true });
    //             //         }
    //             //     }
    //             // }

    //             // ======================================
    //             // 🔥 Jika akan PINDAH MODUL, cek dulu apakah modul sebelumnya sudah complete
    //             // ======================================
    //             const moduleIsDone = isModuleCompleted(currentModule);

    //             // console.log(`📌 Modul ${currentModule} complete? →`, moduleIsDone);

    //             // ❗ HANYA STOP jika pindah modul!
    //             if (currentModule !== nextModule && !moduleIsDone) {
    //                 alert(`⛔ Modul ${currentModule} BELUM selesai`);
    //                 // console.warn(`⛔ Modul ${currentModule} BELUM selesai → PERPINDAHAN KE MODUL ${nextModule} DIBATALKAN`);

    //                 // Kembalikan tombol
    //                 nextButton.disabled = false;

    //                 // Tetap highlight item sekarang
    //                 activeItem.classList.add("active", "bg-warning", "text-dark");

    //                 // STOP proses (tidak render nextItem)
    //                 return;
    //             }


    //             // reset highlight
    //             allItems.forEach(opt => opt.classList.remove("active", "bg-warning", "text-dark"));

    //             // render konten & highlight
    //             // console.log("🎨 renderContent() dijalankan untuk item:", nextItem.dataset.itemId);
    //             renderContent(nextItem);
    //             nextItem.classList.add("active", "bg-warning", "text-dark");

    //             // alert jika modul berpindah
    //             if (currentModule !== nextModule) {
    //                 alert(`✅ Modul ${currentModule} selesai. Lanjut ke Modul ${nextModule}`);
    //                 const collapseEl = document.getElementById(`materi${nextModule}Items`);
    //                 if (collapseEl && !collapseEl.classList.contains("show")) {
    //                     new bootstrap.Collapse(collapseEl, { toggle: true });
    //                 }
    //             }

    //             updateNextButtonText();
    //             nextItem.scrollIntoView({ behavior: "smooth", block: "center" });

    //         } catch (err) {
    //             // console.error("💥 Error pada klik tombol Next:", err);
    //         } finally {
    //             // nextButton.disabled = false; // re-enable setelah semua selesai
    //             // console.log("🔁 Tombol 'Selanjutnya' diaktifkan kembali (finally).");
    //             const activeItem = document.querySelector(".item-option.active");
    //             const idx = allItems.indexOf(activeItem);

    //             // jika bukan item terakhir → baru enable ulang
    //             if (idx !== allItems.length - 1) {
    //                 nextButton.disabled = false;
    //             }
    //         }
    //     });

    //     // cek tombol saat load pertama kali
    //     updateNextButtonText();
    // }
    
    // if (nextButton) {
    //     nextButton.addEventListener("click", async (e) => {

    //         const activeItem = document.querySelector(".item-option.active");
    //         if (!activeItem) {
    //             alert("⚠️ Belum ada materi yang dipilih!");
    //             return;
    //         }
            
    //          if (
    //             nextButton.textContent === "Selesaikan Kursus" &&
    //             !canFinishCourse
    //         ) {
    //             e.preventDefault();

    //             const modal = new bootstrap.Modal(
    //                 document.getElementById("courseRulesModal")
    //             );
    //             modal.show();

    //             return; // ⛔ STOP TOTAL
    //         }
    //         // nextButton.disabled = true;

    //         try {
    //             // ============================
    //             // VALIDASI SUBMISSION
    //             // ============================
    //             const type = activeItem.dataset.type;
    //             const requiresSubmission = ["3", "4", "7"].includes(type);

    //             let submissionStatus = null;

    //             if (requiresSubmission) {
    //                 submissionStatus = await validateSubmission(activeItem);

    //                 if (!submissionStatus.exists) {
    //                     alert("⚠️ Anda belum mengirim submission.");
    //                     nextButton.disabled = false;
    //                     return;
    //                 }

    //                 if (submissionStatus.is_remedial != 0) {
    //                     alert("⚠️ Submission Anda masih berstatus remedi.\nSilakan submit ulang sampai final.");
    //                     nextButton.disabled = false;
    //                     return;
    //                 }
    //             }

    //             // ============================
    //             // TENTUKAN NEXT ITEM
    //             // ============================
    //             let idx = allItems.indexOf(activeItem);
    //             const nextItem = allItems[idx + 1];
    if (nextButton) {
        nextButton.addEventListener("click", async (e) => {
    
            const activeItem = document.querySelector(".item-option.active");
            if (!activeItem) {
                alert("⚠️ Belum ada materi yang dipilih!");
                return;
            }
    
            // ===================================================
            // 🔥 FINAL CHECK — ALWAYS ASK BACKEND
            // ===================================================
            if (nextButton.textContent === "Selesaikan Kursus") {
                e.preventDefault();
    
                const freshStatus = await validateSubmission(activeItem);
    
                const type = activeItem.dataset.type;
                const isSubmissionItem = ["3", "4", "7"].includes(type);
    
                const mustDisable =
                    (
                        isSubmissionItem &&
                        (
                            !freshStatus.exists ||
                            freshStatus.is_remedial != 0 ||
                            !freshStatus.grade_passed
                        )
                    )
                    || !freshStatus.all_completed;
    
                handleRemarks(freshStatus);
    
                // console.log("🔁 FINAL RECHECK:", freshStatus);
    
                if (mustDisable) {
                    const modal = new bootstrap.Modal(
                        document.getElementById("courseRulesModal")
                    );
                    modal.show();
                    return; // ⛔ STOP TOTAL
                }
            }
    
            try {
                // ============================
                // VALIDASI SUBMISSION NORMAL
                // ============================
                const type = activeItem.dataset.type;
                const requiresSubmission = ["3", "4", "7"].includes(type);
    
                let submissionStatus = null;
    
                if (requiresSubmission) {
                    submissionStatus = await validateSubmission(activeItem);
    
                    if (!submissionStatus.exists) {
                        alert("⚠️ Anda belum mengirim submission.");
                        nextButton.disabled = false;
                        return;
                    }
    
                    if (submissionStatus.is_remedial != 0) {
                        alert("⚠️ Submission Anda masih berstatus remedi.\nSilakan submit ulang sampai final.");
                        nextButton.disabled = false;
                        return;
                    }
                }
    
                // ============================
                // NEXT ITEM
                // ============================
                let idx = allItems.indexOf(activeItem);
                const nextItem = allItems[idx + 1];

                // ============================
                // ITEM TERAKHIR → FEEDBACK
                // ============================
            //             if (!nextItem) {
            //                 const courseId = activeItem.dataset.courseId;
        
            //                 const feedbackModalEl = document.getElementById("feedbackModal");
            //                 const feedbackInput = feedbackModalEl.querySelector("#feedback_course_id");
            //                 feedbackInput.value = courseId;
        
            //                 const feedbackModal = new bootstrap.Modal(feedbackModalEl);
            //                 feedbackModal.show();
        
            //                 const form = document.getElementById("feedbackForm");
        
            //                 const handleFeedbackSubmit = async (e) => {
            //                     e.preventDefault();
        
            //                     try {
            //                         const formData = new FormData(form);
            //                         const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        
            //                         const res = await fetch("/feedback/store", {
            //                             method: "POST",
            //                             headers: { "X-CSRF-TOKEN": csrfToken },
            //                             body: formData,
            //                         });
        
            //                         if (res.ok) {
            //                             feedbackModal.hide();
            //                             finishCourse(activeItem);
            //                         } else {
            //                             alert("Terjadi kesalahan saat mengirim feedback.");
            //                         }
            //                     } catch (err) {
            //                         // console.error("❌ Error submit feedback:", err);
            //                         alert("Terjadi kesalahan tak terduga.");
            //                     } finally {
            //                         form.removeEventListener("submit", handleFeedbackSubmit);
            //                     }
            //                 };
        
            //                 form.addEventListener("submit", handleFeedbackSubmit);
            //                 return;
            //             }
        
            //             // ============================
            //             // CEK STATUS LOCKED ITEM
            //             // ============================
            //             const rawStatus = nextItem.dataset.status;
            //             const status = (rawStatus || "unlocked").trim().toLowerCase();
        
            //             const isLocked =
            //                 status === "locked" ||
            //                 status.startsWith("locked") ||
            //                 nextItem.classList.contains("locked");
        
            //             if (isLocked) {
            //                 alert("🔒 Materi berikutnya masih terkunci.");
            //                 nextButton.disabled = false;
            //                 return;
            //             }
        
            //             // ============================
            //             // 🟢 FIX — SAVE PROGRESS SEBELUM CEK MODUL
            //             // ============================
            //             if (requiresSubmission) {
            //                 if (submissionStatus.exists && submissionStatus.is_remedial == 0) {
            //                     await saveProgress(activeItem);
            //                 }
            //             } else {
            //                 await saveProgress(activeItem);
            //             }
        
            //             // ============================
            //             // MODUL CHECK SETELAH SAVE
            //             // ============================
            //             const currentModule = activeItem.dataset.moduleId;
            //             const nextModule = nextItem.dataset.moduleId;
        
            //             const moduleIsDone = isModuleCompleted(currentModule);
            //             // console.log(`📌 Modul ${currentModule} complete? →`, moduleIsDone);
        
            //             if (currentModule !== nextModule && !moduleIsDone) {
            //                 alert(`⛔ Modul ${currentModule} BELUM selesai`);
            //                 nextButton.disabled = false;
        
            //                 activeItem.classList.add("active", "bg-warning", "text-dark");
            //                 return;
            //             }
        
            //             // ============================
            //             // RENDER NEXT ITEM
            //             // ============================
            //             allItems.forEach(opt => opt.classList.remove("active", "bg-warning", "text-dark"));
        
            //             renderContent(nextItem);
            //             nextItem.classList.add("active", "bg-warning", "text-dark");
        
            //             updateProgress();
        
            //             if (currentModule !== nextModule) {
            //                 alert(`✅ Modul ${currentModule} selesai. Lanjut ke Modul ${nextModule}`);
            //                 const collapseEl = document.getElementById(`materi${nextModule}Items`);
            //                 if (collapseEl && !collapseEl.classList.contains("show")) {
            //                     new bootstrap.Collapse(collapseEl, { toggle: true });
            //                 }
            //             }
        
            //             updateNextButtonText();
            //             nextItem.scrollIntoView({ behavior: "smooth", block: "center" });
        
            //         } catch (err) {
            //             // console.error("💥 Error pada klik tombol Next:", err);
            //         } finally {
            //             const newActive = document.querySelector(".item-option.active");
            //             const newIndex = allItems.indexOf(newActive);
        
            //             if (newIndex !== allItems.length - 1) {
            //                 nextButton.disabled = false;
            //             }
            //         }
            //     });
        
            //     updateNextButtonText();
            // }
        if (!nextItem) {
                const courseId = activeItem.dataset.courseId;
    
                const feedbackModalEl = document.getElementById("feedbackModal");
                const feedbackInput = feedbackModalEl.querySelector("#feedback_course_id");
                feedbackInput.value = courseId;
    
                const feedbackModal = new bootstrap.Modal(feedbackModalEl);
                feedbackModal.show();
    
                const form = document.getElementById("feedbackForm");
    
                const handleFeedbackSubmit = async (e) => {
                        e.preventDefault();
        
                        try {
                            const formData = new FormData(form);
                            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        
                            const res = await fetch("/feedback/store", {
                                method: "POST",
                                headers: { "X-CSRF-TOKEN": csrfToken },
                                body: formData,
                            });
        
                            if (res.ok) {
                                feedbackModal.hide();
                                finishCourse(activeItem);
                            } else {
                                alert("Terjadi kesalahan saat mengirim feedback.");
                            }
                        } catch (err) {
                            console.error("❌ Error submit feedback:", err);
                            alert("Terjadi kesalahan tak terduga.");
                        } finally {
                            form.removeEventListener("submit", handleFeedbackSubmit);
                        }
                    };
        
                    form.addEventListener("submit", handleFeedbackSubmit);
                return;
                }

                // ============================
                // CEK STATUS LOCKED ITEM
                // ============================
                const rawStatus = nextItem.dataset.status;
                const status = (rawStatus || "unlocked").trim().toLowerCase();

                const isLocked =
                    status === "locked" ||
                    status.startsWith("locked") ||
                    nextItem.classList.contains("locked");

                if (isLocked) {
                    alert("🔒 Materi berikutnya masih terkunci.");
                    nextButton.disabled = false;
                    return;
                }

                // ============================
                // 🟢 FIX — SAVE PROGRESS SEBELUM CEK MODUL
                // ============================
                if (requiresSubmission) {
                    if (submissionStatus.exists && submissionStatus.is_remedial == 0) {
                        await saveProgress(activeItem);
                    }
                } else {
                    await saveProgress(activeItem);
                }

                // ============================
                // MODUL CHECK SETELAH SAVE
                // ============================
                const currentModule = activeItem.dataset.moduleId;
                const nextModule = nextItem.dataset.moduleId;

                const moduleIsDone = isModuleCompleted(currentModule);
                // console.log(`📌 Modul ${currentModule} complete? →`, moduleIsDone);

                if (currentModule !== nextModule && !moduleIsDone) {
                    alert(`⛔ Modul ${currentModule} BELUM selesai`);
                    nextButton.disabled = false;

                    activeItem.classList.add("active", "bg-warning", "text-dark");
                    return;
                }

                // ============================
                // RENDER NEXT ITEM
                // ============================
                allItems.forEach(opt => opt.classList.remove("active", "bg-warning", "text-dark"));

                renderContent(nextItem);
                nextItem.classList.add("active", "bg-warning", "text-dark");

                updateProgress();

                if (currentModule !== nextModule) {
                    const nextModuleButton = document.querySelector(
                        `[data-bs-target="#materi${nextModule}Items"]`
                    );

                    if (nextModuleButton) {
                        nextModuleButton.classList.remove("disabled");
                        nextModuleButton.removeAttribute("disabled");
                        nextModuleButton.dataset.statusModule = "unlocked";

                        const badge = nextModuleButton.querySelector(".badge");
                        if (badge) {
                            badge.classList.remove("bg-secondary");
                            badge.classList.add("bg-success");
                            badge.textContent = "Unlocked";
                        }
                    }
                    alert(`✅ Modul ${currentModule} selesai. Lanjut ke Modul ${nextModule}`);

                    const collapseEl = document.getElementById(`materi${nextModule}Items`);
                    if (collapseEl && !collapseEl.classList.contains("show")) {
                        new bootstrap.Collapse(collapseEl, { toggle: true });
                    }
                }

                updateNextButtonText();
                nextItem.scrollIntoView({ behavior: "smooth", block: "center" });

            } catch (err) {
                console.error("💥 Error pada klik tombol Next:", err);
            } finally {
                const newActive = document.querySelector(".item-option.active");
                const newIndex = allItems.indexOf(newActive);

                if (newIndex !== allItems.length - 1) {
                    nextButton.disabled = false;
                }
            }
        });

        updateNextButtonText();
    }

    // ==============================
    // Function Save Progress
    // ==============================
    async function saveProgress(itemEl) {
        // console.log("I got triggered sAvePRogress ")
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const payload = {
            course_id: itemEl.dataset.courseId,
            course_week_id: itemEl.dataset.moduleId,
            course_item_id: itemEl.dataset.itemId,
            current_step_module: itemEl.dataset.moduleId,
            current_step_item: itemEl.dataset.itemId
        };

        // console.log("📦 Payload dikirim:", payload);

        try {
            let res = await fetch("/course/save-progress", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken
                },
                body: JSON.stringify(payload)
            });

            let data = await res.json();
            if (data.success) {
                // console.log("✅ Progress tersimpan:", data.progress);

                // 🔥 Update UI langsung → kasih tanda hijau
                itemEl.classList.remove("bg-warning", "text-dark");
                itemEl.classList.add("bg-success", "text-white");

                // simpan posisi terakhir di localStorage
                localStorage.setItem("lastProgress", JSON.stringify(payload));
            } else {
                // console.error("❌ Gagal menyimpan progress:", data);
            }
        } catch (err) {
            // console.error("⚠️ Error simpan progress:", err);
        }
    }
    // =============================
    // Get Progress Course
    // =============================
    async function markProgress(courseId) {
        try {
            let res = await fetch(`/course/${courseId}/progress`);
            let data = await res.json();
            if (data.success) {
                // console.log("📌 Progress dari DB:", data.items);
                data.items.forEach(itemId => {
                    const el = document.querySelector(`.item-option[data-item-id="${itemId}"]`);
                    if (el) {
                        el.classList.add("bg-success", "text-white");
                    }
                });
            }
        } catch (err) {
            // console.error("⚠️ Error fetch progress:", err);
        }
    }

    // 🧭 Setelah reload, auto scroll dan buka materi terakhir -> Handling Tugas Unggahan
    window.addEventListener("load", () => {
        const moduleId = localStorage.getItem("restoreModule");
        const itemId = localStorage.getItem("restoreItem");
        if (moduleId && itemId) {
            const collapseEl = document.getElementById(`materi${moduleId}Items`);
            if (collapseEl && !collapseEl.classList.contains("show")) {
                new bootstrap.Collapse(collapseEl, { toggle: true });
            }

            const itemEl = document.querySelector(`.item-option[data-item-id="${itemId}"]`);
            if (itemEl) {
                itemEl.scrollIntoView({ behavior: "smooth", block: "center" });
                itemEl.click(); // auto-buka materi
            }

            // hapus cache restore agar tidak berulang
            localStorage.removeItem("restoreModule");
            localStorage.removeItem("restoreItem");
        }
    });

    // init load
    const firstItem = document.querySelector(".item-option");
    if (firstItem) {
        const courseId = firstItem.dataset.courseId;
        markProgress(courseId);
        fetchItemLocks(courseId);
    }
    // =============================
    // Restore Progress setelah Reload
    // =============================
    const nextProgress = localStorage.getItem("nextProgress");
    if (nextProgress) {
        try {
            if (nextProgress === "DONE") {
                if (nextButton) {
                    nextButton.textContent = "Selesaikan Kursus";
                    nextButton.classList.add("btn-success");
                    nextButton.classList.remove("btn-primary");
                }
            } else {
                const { course_id, course_week_id, course_item_id } = JSON.parse(nextProgress);

                const collapseEl = document.getElementById(`materi${course_week_id}Items`);
                if (collapseEl && !collapseEl.classList.contains("show")) {
                    new bootstrap.Collapse(collapseEl, { toggle: true });
                }

                const lastItem = document.querySelector(`.item-option[data-item-id="${course_item_id}"]`);
                if (lastItem) {
                    renderContent(lastItem);
                    lastItem.classList.add("active", "bg-warning", "text-dark");
                    lastItem.scrollIntoView({ behavior: "smooth", block: "center" });
                }
            }
        } catch (err) {
            // console.error("⚠️ Error parse nextProgress:", err);
        }
        localStorage.removeItem("nextProgress");
    }
    //================================
    // Handling Unggahan Tugas
    // ===============================
    document.addEventListener("submit", async function(e) {
        if (e.target && e.target.id === "upload-task-form") {
            e.preventDefault();

            let form = e.target;
            let formData = new FormData(form);

            try {
                let res = await fetch(form.action, {
                    method: "POST",
                    body: formData,
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                let data = await res.json();

                if (data.success) {
                    const resultBox = document.getElementById("upload-result");
                    resultBox.innerHTML = `<div class="alert alert-success">✅ ${data.message ?? 'Tugas berhasil diunggah.'}</div>`;

                    // ✅ Simpan posisi aktif (biar nanti setelah reload tetap di situ)
                    const activeModuleId = document.querySelector(".item-option.active")?.dataset.moduleId;
                    const activeItemId   = document.querySelector(".item-option.active")?.dataset.itemId;
                    localStorage.setItem("restoreModule", activeModuleId);
                    localStorage.setItem("restoreItem", activeItemId);

                    // 🧹 Bersihkan form & cache
                    form.reset();
                    if ("caches" in window) caches.keys().then(names => names.forEach(name => caches.delete(name)));

                    // 🔄 Refresh halaman setelah 1 detik
                    setTimeout(() => window.location.reload(true), 1000);
                } else {
                    document.getElementById("upload-result").innerHTML =
                        `<div class="alert alert-danger">❌ Gagal: ${JSON.stringify(data.errors)}</div>`;
                }

            } catch (err) {
                document.getElementById("upload-result").innerHTML =
                    `<div class="alert alert-danger">⚠️ Terjadi error: ${err.message}</div>`;
            }
        }
    });

    // =================================================
    // Handler khusus untuk essay submission (AJAX POST)
    // =================================================
    document.addEventListener("click", function (e) {
            if (e.target && e.target.id === "submit-essay-btn") {
                e.preventDefault();

                const essayId = e.target.getAttribute("data-essay-id");
                const itemId = e.target.getAttribute("data-item-id");
                const answerText = document.getElementById("answer_text").value;

                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            fetch(`/course/${itemId}/essay-submission`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": csrfToken
                    },
                    body: JSON.stringify({
                        essay_id: essayId,
                        item_id: itemId,
                        answer_text: answerText
                    })
                    })
                    .then(res => res.json()) // ambil body JSON
                    .then(json => {
                        if (json.success) {
                            alert("✅ Jawaban berhasil dikirim!");

                            // ✅ Simpan posisi aktif agar bisa dikembalikan setelah reload
                            const activeModuleId = document.querySelector(".item-option.active")?.dataset.moduleId;
                            const activeItemId   = document.querySelector(".item-option.active")?.dataset.itemId;
                            localStorage.setItem("restoreModule", activeModuleId);
                            localStorage.setItem("restoreItem", activeItemId);

                            // 🧹 Bersihkan textarea & cache
                            document.getElementById("answer_text").value = "";
                            if ("caches" in window) caches.keys().then(names => names.forEach(name => caches.delete(name)));

                            // ⏳ Delay sedikit agar alert muncul dulu
                            setTimeout(() => window.location.reload(true), 1000);
                        } else {
                            alert("❌ Gagal menyimpan jawaban!");
                        }
                    })
                    .catch(err => console.error("❌ Error submit:", err));
            }
    });
    // =====================================
    // Handler Simpan Komentar Utama (parentId = null)
    // =====================================
    document.addEventListener("click", function (e) {
        if (e.target && e.target.id === "submit-forum-reply-btn") {
            e.preventDefault();

            const forumId   = e.target.dataset.forumId;
            const replyText = document.getElementById("forum-reply").value.trim();
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            if (!forumId) {
                alert("⚠️ Forum ID tidak ditemukan. Silakan reload halaman atau hubungi admin.");
                return;
            }

            if (!replyText) {
                alert("⚠️ Teks tidak boleh kosong!");
                return;
            }

            fetch(`/course/${forumId}/forum-reply`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken
                },
                body: JSON.stringify({
                    reply_text: replyText,
                    parent_id: null
                })
            })
            .then(res => {
                if (!res.ok) {
                    throw new Error(`HTTP ${res.status}`);
                }
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    alert("✅ Komentar berhasil dikirim!");
                    document.getElementById("forum-reply").value = "";
                    loadForumReplies(forumId);
                } else {
                    alert("❌ Gagal mengirim komentar!");
                }
            })
            .catch(err => console.error("❌ Error submit reply:", err));
        }
    });
    // =====================================
    // Handler Simpan Balasan (child comment, ada parent_id)
    // =====================================
    document.addEventListener("click", function (e) {
        if (e.target && e.target.id === "submit-reply-btn") {
            e.preventDefault();

            const forumId   = e.target.dataset.forumId;
            const parentId  = e.target.dataset.parentId;
            const textarea  = e.target.closest(".d-flex").querySelector(".reply-textarea");
            const replyText = textarea.value.trim();
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            if (!replyText) {
                alert("⚠️ Balasan tidak boleh kosong!");
                return;
            }

            fetch(`/course/${forumId}/forum-reply`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken
                },
                body: JSON.stringify({
                    reply_text: replyText,
                    parent_id: parentId
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert("✅ Balasan berhasil dikirim!");
                    textarea.value = "";
                    loadForumReplies(forumId);
                } else {
                    alert("❌ Gagal mengirim balasan!");
                }
            })
            .catch(err => console.error("❌ Error submit reply:", err));
        }
    });
    // =====================================
    // Function untuk Load Forum Discussion
    // =====================================
    function loadForumReplies(forumId) {
        fetch(`/forum/${forumId}/replies`)
            .then(res => {
                if (!res.ok) {
                    throw new Error(`HTTP error! status: ${res.status}`);
                }
                return res.json();
            })
            .then(data => {
                const thread = document.getElementById(`forum-thread-${forumId}`);
                if (!thread) return;

                thread.innerHTML = "";

                if (!data || data.length === 0) {
                    thread.innerHTML = `
                        <p class="text-muted fst-italic mb-2">
                            💬 Belum ada diskusi. Jadilah yang pertama berkomentar!
                        </p>
                    `;
                    return;
                }

                // hanya render parent_id = null
                data
                    .filter(reply => reply.parent_id === null)
                    .forEach(reply => {
                        thread.appendChild(renderReply(reply, forumId));
                    });
            })
            .catch(() => {
                const thread = document.getElementById(`forum-thread-${forumId}`);
                if (thread) {
                    thread.innerHTML = `<p class="text-danger">⚠️ Gagal memuat diskusi.</p>`;
                }
            });
    }
    // =====================================
    // Shuffle Soal 
    // ===================================== 
    function shuffleMultipleChoice (array) {
        for (let i = array.length - 1; i > 0; i --) {
            const j = Math.floor(Math.random() * (i + 1));
            [array[i], array[j]] = [array[j], array[i]];
        } 
        return array;
    }

    // =====================================
    // 📝 Load Essay Submissions Function
    // =====================================
    function loadSubmissions(itemId, currentUserId, essayDuration = 0) {
        // console.group(`🧩 loadSubmissions(itemId: ${itemId})`);
        const thread = document.getElementById(`forum-thread-${itemId}`);
        if (!thread) return;
        thread.innerHTML = `<p class="text-center text-muted">⏳ Memuat data...</p>`;

        const itemEl = document.querySelector(`[data-item-id="${itemId}"]`);
        const passingGrade = parseFloat(itemEl?.dataset.passingGradeItem || 0);

        fetch(`/course/${itemId}/essay-submissions`)
            .then(res => res.json())
            .then(data => {
                if (!data || data.length === 0) {
                    thread.innerHTML = `<p class="text-muted fst-italic mb-2">Belum ada jawaban.</p>`;
                    // console.groupEnd();
                    return;
                }

                let filteredData = [];

                if (essayDuration > 0) {
                    // Mode ujian: tampilkan SEMUA jawaban milik user ini (termasuk remedial)
                    filteredData = data.filter(sub =>
                        sub.user?.user_id == currentUserId
                    ).sort((a, b) => new Date(b.submitted_at) - new Date(a.submitted_at));
                } else {
                    // Mode normal: tampilkan semua jawaban dari semua user
                    filteredData = data.sort((a, b) => new Date(b.submitted_at) - new Date(a.submitted_at));
                }

                thread.innerHTML = "";
                filteredData.forEach(sub => {
                    const div = document.createElement("div");
                    div.className = "border rounded p-3 mb-3 bg-white shadow-sm";

                    const grade = parseFloat(sub.grade || 0);
                    let badge = "";
                    let remedialMsg = "";

                    if (grade >= passingGrade) {
                        badge = `<span class="badge bg-success">✅ Lulus</span>`;
                    } else if (grade > 0 && grade < passingGrade) {
                        badge = `<span class="badge bg-warning text-dark">⚠️ Remedial</span>`;
                        remedialMsg = `<p class="text-danger small mt-1 mb-0">⚠️ Nilai belum mencapai standar (${passingGrade}%).</p>`;
                    } else {
                        badge = `<span class="badge bg-secondary">⏳ Belum Dinilai</span>`;
                    }

                    div.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong>${sub.user?.full_name ?? 'Anonim'} (${sub.user?.emp_id ?? '-'})</strong>
                            <small class="text-muted">${dayjs(sub.submitted_at).fromNow()}</small>
                        </div>
                        <p class="mb-0">${sub.answer_text ?? 'Tidak mengirimkan Jawaban. (Pesan Otomatis)'}</p>
                        ${(sub.grade !== null && sub.grade !== "" && sub.grade !== undefined) ? `
                            <div class="mt-2 p-2 border rounded bg-light">
                                <strong>👨‍🏫 Feedback:</strong>
                                <p><strong>📊 Grade:</strong> ${sub.grade ?? "-"} ${badge}</p>
                                <p><strong>💬 Komentar:</strong> ${
                                    sub.feedback && sub.feedback.trim() !== ""
                                        ? sub.feedback
                                        : "Belum ada feedback"
                                }</p>
                                ${remedialMsg}
                            </div>` : ""}
                    `;
                    thread.appendChild(div);
                });

                // console.groupEnd();
            })
            .catch(err => {
                // console.error("❌ Error load submissions:", err);
                thread.innerHTML = `<p class="text-danger fst-italic mb-2">⚠️ Gagal memuat jawaban.</p>`;
                // console.groupEnd();
            });
    }
    // ==========================
    // Render Reply
    // ==========================
    function renderReply(reply, forumId, level = 0) {
        const div = document.createElement("div");
        div.className = "reply-item mb-2";
        div.style.marginLeft = `${level * 20}px`;

        // Buat section instructor hanya jika ada grade/feedback
        let instructorSection = "";
        if ((reply.grade && reply.grade !== "") || (reply.feedback && reply.feedback !== "")) {
            instructorSection = `
                <div class="mt-2 p-2 border rounded bg-light">
                    <strong>👨‍🏫 Balasan Instructor :</strong>
                    <p class="mb-1"><strong>📊 Grade:</strong> ${reply.grade ?? "-"}</p>
                    <p class="mb-1"><strong>💬 Feedback:</strong> ${reply.feedback ?? "-"}</p>
                </div>
            `;
        }

        div.innerHTML = `
            <div class="border rounded p-2 bg-white">
                <strong>${reply.user?.full_name ?? "Anonim"} 
                    <span class="text-muted">(${reply.user?.emp_id ?? "-"})</span>
                </strong>
                <p class="mb-1">${reply.reply_text}</p>

                ${instructorSection}

                <small class="text-muted">${dayjs(reply.created_at).fromNow()}</small>
                <button class="btn btn-link btn-sm text-decoration-none reply-btn"
                        data-reply-id="${reply.reply_id}"
                        data-forum-id="${forumId}">
                    ↩️ Balas
                </button>

                <div class="reply-form-container mt-2" id="reply-form-${reply.reply_id}" style="display:none;"></div>
                <div class="reply-children" id="reply-children-${reply.reply_id}"></div>
            </div>
        `;

        // render children secara rekursif
        if (reply.children && reply.children.length > 0) {
            reply.children.forEach(child => {
                const childDiv = renderReply(child, forumId, level + 1);
                div.querySelector(`#reply-children-${reply.reply_id}`).appendChild(childDiv);
            });
        }

        return div;
    }
    // ===========================
    // Toggle reply form di forum
    // ===========================
    document.addEventListener("click", function (e) {
        if (e.target && e.target.classList.contains("reply-btn")) {
            e.preventDefault();

            const replyId = e.target.dataset.replyId;
            const forumId = e.target.dataset.forumId; // ✅ ambil forum id dari tombol balas
            const container = document.getElementById(`reply-form-${replyId}`);

            if (!container) return;

            if (container.style.display === "none" || container.innerHTML === "") {
                // inject form balasan
                container.innerHTML = `
                    <div class="d-flex">
                        <textarea class="form-control me-2 reply-textarea" 
                                rows="2" 
                                placeholder="✍️ Tulis balasan Anda..."></textarea>
                        <button class="btn btn-secondary"
                                id="submit-reply-btn" 
                                data-parent-id="${replyId}"
                                data-forum-id="${forumId}">
                            Kirim Balasan
                        </button>
                    </div>
                `;
                container.style.display = "block";
                e.target.textContent = "❌ Batal";
            } else {
                container.innerHTML = "";
                container.style.display = "none";
                e.target.textContent = "↩️ Balas";
            }
        }
    });
    async function fetchItemLocks(courseId) {
        try {
            let res = await fetch(`/course/${courseId}/locks`);
            let data = await res.json();

            // console.log("📦 Payload Lock Items:", data);

            if (data.success) {
                data.items.forEach(lock => {
                    const el = document.querySelector(`[data-item-id="${lock.item_id}"]`);
                    if (!el) return;

                    el.dataset.status = lock.status;
                    el.dataset.progress = lock.progress || 0;

                    if (el.classList.contains("bg-success")) {
                        return;
                    }

                    el.classList.remove("bg-success", "bg-secondary", "text-white");
                    el.style.opacity = "1";
                    el.style.cursor = "pointer";

                    if (lock.status.startsWith("locked") && lock.progress > 0) {
                        el.dataset.status = "completed";
                    }

                    if (el.dataset.status.startsWith("locked")) {
                        el.classList.add("bg-secondary", "text-white");
                        el.style.opacity = "0.5";
                        el.style.cursor = "not-allowed";

                        if (lock.unlock_at) {
                            const unlockTime = new Date(lock.unlock_at);
                            const options = { 
                                day: "2-digit", month: "2-digit", year: "numeric", 
                                hour: "2-digit", minute: "2-digit" 
                            };
                            const formatted = unlockTime.toLocaleString("id-ID", options);
                            el.title = `🔒 Materi ini akan dibuka pada ${formatted}`;
                        } else {
                            el.title = "🔒 Materi ini masih terkunci.";
                        }
                    } else if (el.dataset.status === "unlocked") {
                        el.title = "✅ Materi sudah bisa dibuka.";
                    } else if (el.dataset.status === "completed") {
                        el.classList.add("bg-success", "text-white");
                        el.title = "🏁 Materi sudah selesai, bisa dibuka kembali.";
                    }
                });
            }
        } catch (err) {
            // console.error("⚠️ Gagal ambil lock status:", err);
        }
    }
    function formatDate(isoString) {
        const date = new Date(isoString);
        return date.toLocaleString("id-ID", {
            day: "2-digit",
            month: "short",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit"
        });
    
    }
    // ===============================
    // 🧩 Forum Thread Function (Simplified & Clean)
    // ===============================

    // ===========================================
    // 1️⃣ LOAD THREAD LIST (Forum Thread per Course)
    // ===========================================
    async function loadForumThreads(courseId) {
        const container = document.getElementById("ForumThreadList");
        if (!container) return console.error("❌ Elemen ForumThreadList tidak ditemukan.");

        container.innerHTML = "<p class='text-center text-muted my-3'>⏳ Memuat thread diskusi...</p>";

        try {
            const res = await fetch(`/detail-course/course-enrolled/${courseId}/threads`);
            const result = await res.json();
            if (!res.ok || !result.success) throw new Error(result.message || res.statusText);

            if (!Array.isArray(result.data) || result.data.length === 0) {
                container.innerHTML = `<div class="alert alert-warning text-center mt-3">
                    Belum ada thread diskusi untuk kursus ini.
                </div>`;
                return;
            }

            container.innerHTML = "";

            // Loop setiap thread
            for (const [i, thread] of result.data.entries()) {
                const card = document.createElement("div");
                card.className = "card mb-4 border-0 shadow-sm";

                // Render attachment (optional)
                let attachmentHTML = "";
                if (thread.attachment_type === "pdf" && thread.attachment_path) {
                    attachmentHTML = `
                        <div class="text-end p-2 bg-light border-top">
                            <a href="/${thread.attachment_path}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                📄 Buka PDF di Tab Baru
                            </a>
                        </div>
                        <div class="mt-3 border rounded shadow-sm overflow-hidden">
                            <div class="ratio ratio-16x9">
                                <iframe 
                                    src="/${thread.attachment_path}" 
                                    title="PDF Viewer" 
                                    style="border:0; width:100%; height:100%;" 
                                    allowfullscreen>
                                </iframe>
                            </div>
                        </div>`;
                }   else if (thread.attachment_type === "url" && thread.attachment_path) {
                        const url = thread.attachment_path.trim();
                        let embedHTML = "";

                        // 🎥 YouTube: deteksi semua variasi
                        if (url.includes("youtube.com") || url.includes("youtu.be")) {
                            let videoId = null;

                            // Format: https://www.youtube.com/watch?v=xxxx
                            const matchWatch = url.match(/[?&]v=([^&]+)/);
                            if (matchWatch) videoId = matchWatch[1];

                            // Format: https://youtu.be/xxxx
                            const matchShort = url.match(/youtu\.be\/([^?]+)/);
                            if (matchShort) videoId = matchShort[1];

                            // Format: https://www.youtube.com/embed/xxxx
                            const matchEmbed = url.match(/embed\/([^?]+)/);
                            if (matchEmbed) videoId = matchEmbed[1];

                            if (videoId) {
                                embedHTML = `
                                    <div class="ratio ratio-16x9 mt-3">
                                        <iframe 
                                            src="https://www.youtube.com/embed/${videoId}" 
                                            title="YouTube video"
                                            frameborder="0" 
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                            allowfullscreen>
                                        </iframe>
                                    </div>`;
                            }
                        }

                        // 📁 Google Drive: deteksi semua variasi
                        else if (url.includes("drive.google.com")) {
                            let fileId = null;

                            // Format: /file/d/ID/view atau /preview
                            const matchFile = url.match(/\/d\/([a-zA-Z0-9_-]+)/);
                            if (matchFile) fileId = matchFile[1];

                            // Format: ?id=ID
                            const matchQuery = url.match(/[?&]id=([a-zA-Z0-9_-]+)/);
                            if (matchQuery) fileId = matchQuery[1];

                            if (fileId) {
                                embedHTML = `
                                    <div class="ratio ratio-16x9 mt-3">
                                        <iframe 
                                            src="https://drive.google.com/file/d/${fileId}/preview" 
                                            title="Google Drive file"
                                            frameborder="0"
                                            allow="autoplay"
                                            allowfullscreen>
                                        </iframe>
                                    </div>`;
                            }
                        }

                        // 🌐 Jika bukan YouTube/GDrive → tampilkan tombol link biasa
                        if (embedHTML) {
                            attachmentHTML = embedHTML;
                        } else {
                            attachmentHTML = `
                                <a href="${url}" target="_blank" class="btn btn-sm btn-outline-secondary mt-2">
                                    🔗 Buka Link
                                </a>`;
                        }
                    } else if (thread.attachment_type === "video" && thread.attachment_path) {
                    const fileName = thread.attachment_path.split("/").pop();
                    attachmentHTML = `
                        <div class="ratio ratio-16x9 mt-3">
                            <video 
                                controls 
                                preload="metadata" 
                                controlsList="nodownload" 
                                playsinline 
                                style="width:100%; height:100%; border-radius:8px; background:#000;">
                                <source src="/stream/${fileName}" type="video/mp4">
                                Browser tidak mendukung video.
                            </video>
                        </div>`;
                }

                // Template thread
                card.innerHTML = `
                    <div class="card-header bg-white border-bottom-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0 text-dark">${i + 1}. ${thread.topic_title || "(Tanpa Judul)"}</h6>
                            <span class="badge bg-light text-dark border shadow-sm fs-6">
                                👨‍🏫 ${thread.creator?.full_name || "Instruktur Tidak Diketahui"}
                            </span>
                        </div>
                    </div>

                    <div class="card-body pb-2">
                        <p class="fw-semibold mb-1">${thread.forum_title || "-"}</p>
                        <p class="text-muted mb-2">${thread.forum_question || ""}</p>
                        ${attachmentHTML}
                    </div>

                    <div class="card-footer bg-light border-top">
                        <!-- Daftar balasan -->
                        <div id="replyContainer-${thread.thread_id}" class="mb-3">
                            <p class="text-center text-muted small my-2">⏳ Memuat balasan...</p>
                        </div>

                        <!-- Form balasan -->
                        <div class="border-top pt-3">
                            <textarea class="form-control mb-2 reply-text" rows="2" placeholder="Tulis balasan Anda..."></textarea>
                            <div class="d-flex justify-content-end">
                                <button class="btn btn-sm bg-secondary text-white px-4 send-reply-btn" data-thread-id="${thread.thread_id}">
                                    Kirim
                                </button>
                            </div>
                        </div>
                    </div>
                `;

                container.appendChild(card);

                // Load replies
                const replyContainer = card.querySelector(`#replyContainer-${thread.thread_id}`);
                await loadForumReplies(thread.thread_id, replyContainer);
            }

            // Event listener kirim balasan utama
            container.querySelectorAll(".send-reply-btn").forEach(btn => {
                btn.addEventListener("click", async () => {
                    const threadId = btn.dataset.threadId;
                    const textarea = btn.closest(".card-footer").querySelector(".reply-text");
                    const content = textarea.value.trim();

                    if (!content) {
                        alert("❌ Balasan tidak boleh kosong!");
                        return;
                    }

                    // console.log("📤 Kirim balasan ke thread:", { thread_id: threadId, reply_content: content });

                    await sendReply(threadId, null, content);
                    textarea.value = "";
                    const replyContainer = document.getElementById(`replyContainer-${threadId}`);
                    await loadForumReplies(threadId, replyContainer);
                });
            });

        } catch (err) {
            // console.error("❌ Gagal memuat thread:", err);
            container.innerHTML = `<div class="alert alert-danger text-center mt-3">
                Terjadi kesalahan saat memuat forum.
            </div>`;
        }
    }
    // ===========================================
    // 2️⃣ LOAD REPLIES
    // ===========================================
    async function loadForumReplies(threadId, container) {
        if (!container) return;
        container.innerHTML = `<p class="text-center text-muted">⏳ Memuat balasan...</p>`;

        try {
            const res = await fetch(`/detail-course/course-enrolled/thread/${threadId}/replies`);
            const result = await res.json();
            if (!res.ok || !result.success) throw new Error(result.message || res.statusText);

            if (!Array.isArray(result.data) || result.data.length === 0) {
                container.innerHTML = `<div class="text-muted text-center mt-2">Belum ada balasan.</div>`;
                return;
            }

            renderReplies(container, result.data);

        } catch (err) {
            // console.error(`❌ Gagal memuat balasan untuk thread ${threadId}:`, err);
            container.innerHTML = `<div class="alert alert-danger text-center mt-2">Gagal memuat balasan.</div>`;
        }
    }

    // ===========================================
    // 3️⃣ RENDER REPLIES (Flat Display, Clean UI)
    // ===========================================
    function renderReplies(container, replies) {
        container.innerHTML = "";

        replies.forEach(reply => {
            const div = document.createElement("div");
            div.className = "p-3 mb-2 rounded bg-white border shadow-sm";

            const time = reply.created_at
                ? new Date(reply.created_at).toLocaleString("id-ID", { dateStyle: "short", timeStyle: "short" })
                : "-";

            let attachmentHTML = "";
            if (reply.attachment_type === "pdf") {
                attachmentHTML = `<a href="${reply.attachment_path}" target="_blank" class="btn btn-sm btn-outline-secondary mt-2">📄 PDF</a>`;
            } else if (reply.attachment_type === "url") {
                attachmentHTML = `<a href="${reply.attachment_path}" target="_blank" class="btn btn-sm btn-outline-secondary mt-2">🔗 URL</a>`;
            } else if (reply.attachment_type === "video") {
                attachmentHTML = `<video src="${reply.attachment_path}" class="w-100 mt-2 rounded" controls></video>`;
            }

            div.innerHTML = `
                <div class="d-flex justify-content-between mb-1">
                    <strong class="text-dark">${reply.full_name || "Anonim"}</strong>
                    <small class="text-muted">${time}</small>
                </div>
                <p class="mb-1">${reply.reply_content || "(tidak ada pesan)"}</p>
                ${attachmentHTML}
            `;

            container.appendChild(div);
        });
    }

    // ===========================================
    // 4️⃣ KIRIM BALASAN
    // ===========================================
    async function sendReply(threadId, parentId, content) {
        try {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const payload = { thread_id: threadId, parent_id: parentId, reply_content: content };
            // console.log("📦 Payload dikirim:", payload);

            const res = await fetch(`/detail-course/course-enrolled/thread/${threadId}/reply`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrf
                },
                body: JSON.stringify(payload)
            });

            const result = await res.json();
            if (!result.success) {
                alert("❌ Gagal mengirim balasan!");
            } else {
                //console.log("✅ Balasan berhasil dikirim:", result.data);
            }
        } catch (err) {
            // console.error("❌ Error kirim balasan:", err);
            alert("Terjadi kesalahan saat mengirim balasan!");
        }
    }

    // ===========================================
    // 5️⃣ MODAL LISTENER
    // ===========================================
    const btnOpenForumModal = document.getElementById("add-thread-forum");
    const modalForum = document.getElementById("ThreadDiscussionLearner");

    if (btnOpenForumModal && modalForum) {
        modalForum.addEventListener("shown.bs.modal", () => {
            const courseId = btnOpenForumModal.getAttribute("data-course-id");
            if (courseId) {
                // console.log("🧾 Memuat forum untuk Course ID:", courseId);
                loadForumThreads(courseId);
            }
        });
    }

}
