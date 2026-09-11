export function InitSubmissionCourse() {
    const submissionList = document.getElementById('submissionListLearner');
    const coursePage = document.getElementById('submissionPage');
    if (!coursePage) return;

    const courseId = coursePage.dataset.courseId;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // 🔹 Helper: format waktu tampil lebih rapi
    function formatDate(dateStr) {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        return `${d.getDate().toString().padStart(2, '0')}/${(d.getMonth() + 1)
            .toString()
            .padStart(2, '0')}/${d.getFullYear()} ${d
            .getHours()
            .toString()
            .padStart(2, '0')}:${d.getMinutes().toString().padStart(2, '0')}`;
    }
    // =====================================================
    // 🧭 1. Handle click on each course item
    // =====================================================
    document.querySelectorAll('.course-item').forEach(item => {
        item.addEventListener('click', async e => {
            e.preventDefault();
            const itemId = item.dataset.itemId;

            submissionList.innerHTML = `
                <div class="text-center py-5 text-muted">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2 mb-0">Memuat data submission...</p>
                </div>`;

            try {
                const res = await fetch(`/submission-data/${itemId}/${courseId}`);
                const data = await res.json();

                if (!data.success) {
                    submissionList.innerHTML = `<div class="alert alert-warning">${data.message ?? 'Gagal memuat data.'}</div>`;
                    return;
                }

                renderSubmissionCards(data);
            } catch (err) {
                // console.error('❌ Fetch error:', err);
                submissionList.innerHTML = `<div class="alert alert-danger">Gagal mengambil data submission.</div>`;
            }
        });
    });

 // =====================================================
// 🧩 2. Render per-student card + passing grade header
// =====================================================
function renderSubmissionCards(data) {
    const learners = data.learners || [];
    const passingGrade = data.passing_grade ?? null;
    const itemType = parseInt(data.type ?? 0, 10);

    // 🔹 Hanya tipe tertentu yang membutuhkan submission
    const submissionTypes = [3, 4, 5, 7];

    // 🔹 Jika bukan salah satu dari itu → tampilkan alert
    if (!submissionTypes.includes(itemType)) {
        submissionList.innerHTML = `
            <div class="alert alert-secondary text-center py-4">
                📌 Item ini tidak membutuhkan submission dari learner.
            </div>`;
        return;
    }

    // 🔹 Jika tidak ada learner sama sekali
    if (learners.length === 0) {
        submissionList.innerHTML = `<div class="alert alert-info">Tidak ada learner ditemukan.</div>`;
        return;
    }

    // =========================================================
    // 🧩 Header informasi item + pertanyaan / deskripsi
    // =========================================================
    let questionBlock = "";
    if (parseInt(data.type) === 3 && data.essay_title) {
        // 📝 Essay type (kode 3)
        questionBlock = `
            <div class="mt-3">
                <h6 class="fw-bold mb-1">🧠 Pertanyaan Essay</h6>
                <div>
                    ${data.essay_title}
                </div>
            </div>`;
    } else if (parseInt(data.type) === 7 && data.course_describe) {
        // 📎 Attachment type (kode 7)
        questionBlock = `
            <div class="mt-3">
                <h6 class="fw-bold mb-1">📄 Pertanyaan</h6>
                <div>
                    ${data.course_describe}
                </div>
            </div>`;
    }

    // 🔹 Header utama
    let headerHTML = `
        <div class="alert alert-warning mb-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <strong>📘 Item ID:</strong> ${data.item_id}<br>
                    <strong>Jenis Item:</strong> ${typeLabel(data.type)}
                </div>
                ${
                    passingGrade
                        ? `<div class="text-end"><strong>Passing Grade:</strong> ${passingGrade}</div>`
                        : ""
                }
            </div>
            ${questionBlock}
        </div>`;

    // 🔹 Urutkan: graded → submitted (not graded) → no submission
    const sorted = [...learners].sort((a, b) => {
        const aHas = !!a.submission;
        const bHas = !!b.submission;
        const aGraded = a.graded;
        const bGraded = b.graded;
        if (aGraded && !bGraded) return -1;
        if (!aGraded && bGraded) return 1;
        if (aHas && !bHas) return -1;
        if (!aHas && bHas) return 1;
        return a.name.localeCompare(b.name);
    });

    let html = `${headerHTML}<div class="row g-3">`;

    sorted.forEach(l => {
        const submissions = Array.isArray(l.submission) ? l.submission : (l.submission ? [l.submission] : []);
        const hasSubmission = submissions.length > 0;

        if (!hasSubmission) {
            // 🚫 Belum submit sama sekali
            html += `
                <div class="col-md-6">
                    <div class="card shadow-sm h-100 border-secondary">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                            <div>
                                <strong>${l.name}</strong><br>
                                <small class="text-muted">${l.emp}</small>
                            </div>
                            <span class="badge bg-secondary">Belum Submit</span>
                        </div>
                        <div class="card-body text-center text-muted fst-italic">
                            Belum ada submission dari learner ini.
                        </div>
                    </div>
                </div>`;
            return;
        }

        // ✅ render setiap submission jadi satu kartu sendiri
        submissions.forEach((s, idx) => {
            const submittedAt = s.submitted_at ? formatDate(s.submitted_at) : '-';
            // const grade = s.grade ?? '';
            const grade = s.grade !== null ? Number(s.grade) : null;
            const feedback = s.feedback ?? '';
            const isRemedial = s.is_remedial == 1;
            const isGraded = grade !== '' && grade !== null;

            // 🔹 Status badge (ubah jadi “Jawaban Remedial” dan “Jawaban Baru”)
            let statusBadge = isRemedial
                ? `<span class="badge bg-danger ms-2">Jawaban Remedial</span>`
                : `<span class="badge bg-success ms-2">Jawaban Baru</span>`;

            // 🔹 Nilai badge
            let gradeBadge = '';
            if (isGraded && passingGrade !== null) {
                gradeBadge =
                    grade >= Number(passingGrade)
                        ? `<span class="badge bg-success ms-2">Lulus ✅</span>`
                        : `<span class="badge bg-warning text-dark ms-2">Remed 🔁</span>`;
            } else if (!isGraded) {
                gradeBadge = `<span class="badge bg-secondary ms-2">Belum Dinilai</span>`;
            }
            
            // 🔹 Warna border
            const borderColor = isGraded
                ? 'success'
                : isRemedial
                ? 'warning'
                : 'secondary';

            // 🔹 Konten utama per submission
            let submissionContent = '';
            if (itemType === 7) {
                submissionContent = `
                    <div class="mb-2">
                        <small class="text-muted d-block">Tanggal Submit:</small>
                        <span>${submittedAt}</span>
                    </div>
                    <div class="mb-2">
                        ${
                            s.file_url
                                ? `<a href="${s.file_url}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    📎 Lihat File
                                  </a>`
                                : `<span class="text-muted">Tidak ada file</span>`
                        }
                    </div>`;
            } else if (itemType === 3) {
                submissionContent = `
                    <div class="mb-2">
                        <small class="text-muted d-block">Tanggal Submit:</small>
                        <span>${submittedAt}</span>
                    </div>
                    <div class="mb-2">
                        <strong>Jawaban:</strong><br>
                        ${
                            s.answer_text
                                ? `<div class="border p-2 rounded bg-white" style="max-height:450px; overflow-y:auto;">${s.answer_text}</div>`
                                : `<span class="text-muted fst-italic">Tidak ada jawaban.</span>`
                        }
                    </div>`;
            }

            // 🔹 Grade + feedback field
            submissionContent += `
                <div class="mt-2 small">
                    <strong>Grade:</strong> ${grade || '-'} ${gradeBadge}<br>
                    <strong>Feedback:</strong> ${
                        feedback || '<span class="text-muted fst-italic">Belum ada feedback.</span>'
                    }
                </div>

                <!-- 🧾 Tambahan: Form input baru tanpa ubah struktur -->
                <div class="mt-3 border-top pt-2">
                    <label class="form-label mb-1">Input Grade Baru</label>
                    <input type="number" class="form-control form-control-sm grade-input"
                        min="0" max="100" placeholder="Masukkan nilai..." value="${grade}">
                    <label class="form-label mt-2 mb-1">Input Feedback Baru</label>
                    <textarea class="form-control form-control-sm feedback-input"
                        rows="2" placeholder="Masukkan feedback...">${feedback}</textarea>
                    <div class="text-end mt-2">
                        <button class="btn btn-sm btn-primary save-feedback-btn"
                            data-learner-id="${l.id}"
                            data-item-id="${data.item_id}"
                            data-passing-grade="${passingGrade ?? 0}">
                            💾 Simpan
                        </button>
                    </div>
                </div>`;

            // 🔹 Render kartu individual per submission
            html += `
                <div class="col-md-6">
                    <div class="card shadow-sm border-${borderColor}">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                            <div>
                                <strong>${l.name}</strong><br>
                                <small class="text-muted">${l.emp}</small>
                            </div>
                            <div>${statusBadge}${gradeBadge}</div>
                        </div>
                        <div class="card-body">
                            ${submissionContent}
                        </div>
                    </div>
                </div>`;
        });
    });

    html += `</div>`;
    submissionList.innerHTML = html;
    attachSaveButtons();
}




    // =====================================================
    // 💾 3. Save grade & feedback (with validation)
    // =====================================================
    function attachSaveButtons() {
        document.querySelectorAll('.save-feedback-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const learnerId = btn.dataset.learnerId;
                const itemId = btn.dataset.itemId;
                const passingGrade = parseFloat(btn.dataset.passingGrade || 0);
                const card = btn.closest('.card');
                const gradeInput = card.querySelector('.grade-input');
                const feedbackInput = card.querySelector('.feedback-input');
                const grade = gradeInput.value.trim();
                const feedback = feedbackInput.value.trim();

                // ✅ Validasi: nilai wajib diisi
                if (grade === '' || isNaN(grade)) {
                    showAlert('error', 'Nilai wajib diisi sebelum menyimpan!');
                    gradeInput.focus();
                    return;
                }

                // ✅ Validasi: range nilai
                const numericGrade = parseFloat(grade);
                if (numericGrade < 0 || numericGrade > 100) {
                    showAlert('error', 'Nilai harus berada di antara 0–100!');
                    gradeInput.focus();
                    return;
                }

                btn.disabled = true;
                btn.innerHTML = '⏳ Menyimpan...';

                try {
                    const res = await fetch(`/submission-feedback/${itemId}/${learnerId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ grade: numericGrade, feedback })
                    });
                    const data = await res.json();

                    if (data.success) {
                        showAlert('success', 'Feedback & nilai berhasil disimpan!');

                        // Update status badge
                        const header = card.querySelector('.card-header div:last-child');
                        header.innerHTML = `
                            <span class="badge bg-success">Sudah Dinilai</span>
                            ${
                                passingGrade
                                    ? numericGrade >= passingGrade
                                        ? `<span class="badge bg-success ms-2">Lulus ✅</span>`
                                        : `<span class="badge bg-danger ms-2">Remed 🔁</span>`
                                    : ''
                            }
                        `;

                        card.classList.remove('border-warning', 'border-secondary');
                        card.classList.add('border-success');
                    } else {
                        showAlert('error', 'Gagal menyimpan feedback.');
                    }
                } catch (err) {
                    // console.error(err);
                    showAlert('error', 'Terjadi kesalahan saat menyimpan feedback.');
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = '💾 Simpan';
                }
            });
        });
    }
    // =====================================================
    // 🧩 Helper label untuk jenis item
    // =====================================================
    function typeLabel(type) {
        const map = {
            1: '🎬 Video',
            2: '📄 Dokumen',
            3: '📝 Essay',
            4: '❓ Multiple Choice',
            5: '💬 Forum',
            6: '🎓 Sertifikat',
            7: '💭 Upload / Diskusi Lain',
            8: '🎧 Audio'
        };
        return map[type] || 'Item Tidak Dikenal';
    }
    // =====================================================
    // 🔔 Simple browser alert
    // =====================================================
    function showAlert(type, message) {
        if (type === "success") alert("✅ " + message);
        else alert("❌ " + message);
    }

    // =====================================================
    // 🔔 Render Thread Discussion 
    // =====================================================
    const threadModal = document.getElementById("threadModal");
    if (threadModal) {
        threadModal.addEventListener("show.bs.modal", async (event) => {
            const button = event.relatedTarget;
            const courseId = button?.dataset.courseId;
            const container = document.getElementById("threadContent");

            if (!courseId) {
                container.innerHTML = `<p class="text-danger">⚠️ Course ID tidak ditemukan.</p>`;
                return;
            }

            container.innerHTML = `<div class="text-center text-muted py-3">
                <div class="spinner-border spinner-border-sm"></div> Memuat diskusi thread...
            </div>`;

            try {
                const res = await fetch(`/submission-course/${courseId}/thread`);
                const data = await res.json();

                if (!data.success) {
                    container.innerHTML = `<p class="text-danger">⚠️ ${data.error}</p>`;
                    return;
                }

                const threads = data.threads;
                if (!threads || threads.length === 0) {
                    container.innerHTML = `<p class="text-muted fst-italic text-center">Belum ada thread di course ini.</p>`;
                    return;
                }

                let html = "";
                threads.forEach((t, idx) => {
                    html += `
                        <div class="mb-4 border rounded p-3 bg-light shadow-sm">
                            <h6 class="fw-bold mb-1">🧵 ${idx + 1}. ${t.topic_title}</h6>
                            <p class="text-muted mb-2">👨‍🏫 Dibuat oleh: <strong>${t.creator_name}</strong></p>
                            <div class="border rounded p-2 mb-2 bg-white">${t.forum_question}</div>
                    `;

                    // ===============================
                    // 🎥 Attachment Renderer
                    // ===============================
                    if (t.attachment_type && t.attachment_path) {
                        const file = t.attachment_path;
                        const type = t.attachment_type;
                        html += `<div class="mt-3 p-3 bg-white border rounded">`;

                        // ================================
                        // 📄 PDF
                        // ================================
                        if (type === "pdf") {
                            html += `
                                <div class="mb-2">
                                    📄 <strong>PDF Attachment:</strong><br>
                                    <iframe src="${file}" width="100%" height="480" class="border rounded"></iframe>
                                </div>`;
                        }

                        // ================================
                        // 🎬 VIDEO / URL
                        // ================================
                        else if (type === "video" || type === "url") {
                            if (file.startsWith("http")) {
                                // ✅ YouTube embed
                                if (file.includes("youtube.com") || file.includes("youtu.be")) {
                                    let embedUrl = "";

                                    try {
                                        if (file.includes("watch?v=")) {
                                            const videoId = new URL(file).searchParams.get("v");
                                            embedUrl = `https://www.youtube.com/embed/${videoId}`;
                                        } else if (file.includes("youtu.be")) {
                                            const videoId = file.split("youtu.be/")[1].split("?")[0];
                                            embedUrl = `https://www.youtube.com/embed/${videoId}`;
                                        } else if (file.includes("/embed/")) {
                                            const parts = file.split("/embed/")[1];
                                            const videoId = parts.split("?")[0];
                                            embedUrl = `https://www.youtube.com/embed/${videoId}`;
                                        }
                                    } catch (err) {
                                        embedUrl = file;
                                    }

                                    html += `
                                        <div class="ratio ratio-16x9 mt-3">
                                            <iframe src="${embedUrl}" frameborder="0" allowfullscreen></iframe>
                                        </div>
                                        <p class="mt-2 text-muted small">
                                            🔔 Jika video tidak bisa diputar, klik “Watch on YouTube”.
                                        </p>`;
                                }

                                // ✅ Google Drive embed
                                else if (file.includes("drive.google.com")) {
                                    let embedUrl = file;
                                    if (file.includes("/view")) {
                                        embedUrl = file.replace("/view", "/preview");
                                    }

                                    html += `
                                        <div class="ratio ratio-16x9 mt-3">
                                            <iframe src="${embedUrl}" frameborder="0" allowfullscreen></iframe>
                                        </div>`;
                                }

                                // ✅ Selain YouTube & Drive → treat as video via /stream
                                else {
                                    const filename = file.split("/").pop();
                                    html += `
                                        <div class="ratio ratio-16x9 mt-3">
                                            <video 
                                                controls 
                                                preload="metadata" 
                                                controlsList="nodownload" 
                                                playsinline 
                                                style="width:100%; height:100%; border-radius:8px; background:#000;"
                                            >
                                                <source src="/stream/${filename}" type="video/mp4">
                                                Browser tidak mendukung video
                                            </video>
                                        </div>`;
                                }
                            } 
                            // ✅ Local upload (langsung file lokal)
                            else {
                                const filename = file.split("/").pop();
                                html += `
                                    <div class="ratio ratio-16x9 mt-3">
                                        <video 
                                            controls 
                                            preload="metadata" 
                                            controlsList="nodownload" 
                                            playsinline 
                                            style="width:100%; height:100%; border-radius:8px; background:#000;"
                                        >
                                            <source src="/stream/${filename}" type="video/mp4">
                                            Browser tidak mendukung video
                                        </video>
                                    </div>`;
                            }
                        }

                        // ================================
                        // 🖼️ IMAGE (optional)
                        // ================================
                        else if (type === "image") {
                            html += `
                                <div class="text-center mt-3">
                                    <img src="${file}" class="img-fluid rounded shadow-sm" alt="Attachment Image">
                                </div>`;
                        }

                        html += `</div>`;
                    }

                    // ===============================
                    // 💬 Replies
                    // ===============================
                    html += `<h6 class="fw-semibold mt-3 mb-2">💬 Balasan:</h6>`;

                    if (t.replies && t.replies.length > 0) {
                        html += `<ul class="list-group mb-2">`;
                        t.replies.forEach(r => {
                            html += `
                                <li class="list-group-item">
                                    <strong>${r.replier_name}</strong><br>
                                    <span>${r.reply_content}</span>
                                </li>`;
                        });
                        html += `</ul>`;
                    } else {
                        html += `<p class="fst-italic text-muted">Belum ada balasan.</p>`;
                    }

                    html += `</div>`;
                });

                container.innerHTML = html;

            } catch (err) {
                // console.error("❌ Error fetching threads:", err);
                container.innerHTML = `<p class="text-danger">⚠️ Gagal memuat data thread.</p>`;
            }
        });
    }

}