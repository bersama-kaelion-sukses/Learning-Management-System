export function InitDetailCourse() {
    const itemOptions = document.querySelectorAll(".item-option");
    const detailPanel = document.getElementById("course-detail-panel");

    function truncateWithToggle(text, limit = 150, type = null) {
        // khusus sertifikat → jangan render deskripsi
        if (type === "6") return "";

        if (!text || text.trim() === "") return "";

        if (text.length <= limit) {
            return `<p class="desc-text">${text}</p>`;
        }

        const shortText = text.substring(0, limit) + "...";
        return `
            <p class="desc-text">${shortText}</p>
            <a href="#" class="read-more text-primary">Read More</a>
        `;
    }

    itemOptions.forEach(el => {
        el.addEventListener("click", async function () {
            // Reset active state
            document.querySelectorAll(".item-option").forEach(i => i.classList.remove("active", "bg-secondary", "text-white"));
            this.classList.add("active", "bg-secondary", "text-white");

            // Ambil data dari atribut
            const name = this.dataset.name || "Tanpa Judul";
            const desc = this.dataset.desc || "Tidak ada deskripsi.";
            const file = this.dataset.file || null;
            const type = this.dataset.type || null;
            const itemId = this.dataset.itemId; // untuk fetch API assignment

            // ✅ Render awal pakai fungsi truncate → pass type
            let content = `<h5 class="mb-3">${name}</h5>${truncateWithToggle(desc, 150, type)}`;

            // --------------------------
            // TYPE = VIDEO (1)
            // --------------------------
            if (type === "1" && file) {
                if (file.startsWith("http")) {
                    // ✅ YouTube embed
                    if (file.includes("youtube.com") || file.includes("youtu.be")) {
                        let embedUrl = file;

                        // convert youtube normal link → embed
                        if (file.includes("watch?v=")) {
                            embedUrl = file.replace("watch?v=", "embed/");
                        }
                        if (file.includes("youtu.be")) {
                            const videoId = file.split("youtu.be/")[1];
                            embedUrl = `https://www.youtube.com/embed/${videoId}`;
                        }

                        content += `
                            <div class="ratio ratio-16x9 mt-3">
                                <iframe src="${embedUrl}" frameborder="0" allowfullscreen></iframe>
                            </div>`;
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

                    // ❌ selain YouTube/Drive → tolak tampilkan
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
                            <video controls>
                                <source src="/${file}" type="video/mp4">
                                Browser tidak mendukung video
                            </video>
                        </div>`;
                }
            }
            // --------------------------
            // TYPE = PDF / Dokumen (2)
            // --------------------------
            else if (type === "2" && file) {
                const modalId = `pdfModal-${Date.now()}`; // bikin ID unik biar gak tabrakan

                content += `
                    <div class="mt-3">
                        <!-- Tombol trigger -->
                        <button class="btn btn-sm btn-outline-secondary mb-2" 
                                data-bs-toggle="modal" data-bs-target="#${modalId}">
                            ⛶ Lihat Fullscreen
                        </button>

                        <!-- Preview ringkas -->
                        <div class="ratio ratio-16x9 border rounded overflow-hidden">
                            <iframe src="/${file}" 
                                style="border:0;" 
                                title="Preview PDF">
                            </iframe>
                        </div>
                    </div>

                    <!-- Modal fullscreen -->
                    <div class="modal fade" id="${modalId}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-fullscreen">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"> Detail Tugas</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
            // --------------------------
            // TYPE = ESSAY (3)
            // --------------------------
           else if (type === "3") {
                try {
                    const res = await fetch(`/api/item/${itemId}/essay`);
                    if (res.ok) {
                        const essay = await res.json();
                        content += `
                            <div class="card mt-3 shadow-sm">
                                <div class="card-body">
                                    <h5 class="fw-bold fs-6 mb-3">Materi Essai</h5>
                                    
                                    <dl class="row mb-3">
                                        <dt class="col-sm-3">Judul</dt>
                                        <dd class="col-sm-9">${essay.essay_title || "-"}</dd>

                                        <dt class="col-sm-3">Instruksi</dt>
                                        <dd class="col-sm-9">${essay.instruction || "-"}</dd>
                                    </dl>
                        `;

                        // ✅ Lampiran PDF
                        if (essay.attachment_type === "pdf" && essay.attachment_value) {
                            const modalId = `pdfModal-${Date.now()}`; // id unik
                            content += `
                                <div class="mt-4">
                                    <h6 class="fw-bold">📄 Lampiran PDF</h6>

                                    <!-- Tombol buka fullscreen -->
                                    <button class="btn btn-sm btn-outline-secondary mb-2" 
                                            data-bs-toggle="modal" data-bs-target="#${modalId}">
                                        ⛶ Lihat Fullscreen
                                    </button>

                                    <!-- Preview kecil -->
                                    <div class="ratio ratio-16x9 border rounded overflow-hidden">
                                        <iframe src="/${essay.attachment_value}" 
                                                style="border:none;" 
                                                title="Preview Lampiran PDF"></iframe>
                                    </div>
                                </div>

                                <!-- Modal fullscreen -->
                                <div class="modal fade" id="${modalId}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-fullscreen">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">📄 Lampiran PDF - Fullscreen</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-0">
                                                <iframe src="/${essay.attachment_value}" 
                                                        style="border:0; width:100%; height:100%;" 
                                                        title="Lampiran PDF Fullscreen"></iframe>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }

                        // ✅ Lampiran URL (YouTube / Google Drive / lainnya)
                        else if (essay.attachment_type === "url" && essay.attachment_value) {
                            const file = essay.attachment_value.trim();
                            let embedUrl = "";
                            let embedHtml = "";

                            // 🎥 YouTube link detection
                            if (file.includes("youtube.com") || file.includes("youtu.be")) {
                                if (file.includes("watch?v=")) {
                                    embedUrl = file.replace("watch?v=", "embed/");
                                } else if (file.includes("youtu.be")) {
                                    const videoId = file.split("youtu.be/")[1].split(/[?&]/)[0];
                                    embedUrl = `https://www.youtube.com/embed/${videoId}`;
                                } else {
                                    embedUrl = file;
                                }

                                embedHtml = `
                                    <div class="mt-4">
                                        <h6 class="fw-bold">🎥 Video YouTube</h6>
                                        <div class="ratio ratio-16x9 border rounded">
                                            <iframe src="${embedUrl}" frameborder="0" allowfullscreen></iframe>
                                        </div>
                                    </div>`;
                            }

                            // 📁 Google Drive link detection
                            else if (file.includes("drive.google.com")) {
                                embedUrl = file.includes("/view")
                                    ? file.replace("/view", "/preview")
                                    : file;

                                embedHtml = `
                                    <div class="mt-4">
                                        <h6 class="fw-bold">📁 Lampiran Google Drive</h6>
                                        <div class="ratio ratio-16x9 border rounded">
                                            <iframe src="${embedUrl}" frameborder="0" allowfullscreen></iframe>
                                        </div>
                                    </div>`;
                            }

                            // 🌐 Other URLs (fallback link)
                            else {
                                embedHtml = `
                                    <div class="mt-4">
                                        <h6 class="fw-bold">🔗 Lampiran URL</h6>
                                        <a href="${file}" target="_blank" 
                                        class="btn btn-outline-primary">
                                            Buka Link
                                        </a>
                                    </div>`;
                            }

                            content += embedHtml;
                        }

                        // 🎞️ Lampiran Video Lokal
                        else if (essay.attachment_type === "video" && essay.attachment_value) {
                            content += `
                                <div class="mt-4">
                                    <h6 class="fw-bold">🎥 Lampiran Video</h6>
                                    <div class="ratio ratio-16x9 border rounded">
                                        <video controls>
                                            <source src="/${essay.attachment_value}" type="video/mp4">
                                            Browser tidak mendukung video.
                                        </video>
                                    </div>
                                </div>`;
                        }

                        // 🟡 Fallback jika tidak ada lampiran
                        else {
                            content += `
                                <div class="alert alert-secondary mt-4 mb-0">
                                    Tidak ada lampiran pada materi essai ini.
                                </div>`;
                        }

                        content += `
                                </div> <!-- end card-body -->
                            </div> <!-- end card -->
                        `;
                    }
                } catch (err) {
                    content += `<p class="text-danger">⚠️ Gagal memuat essay: ${err.message}</p>`;
                }
            }

            // --------------------------
            // TYPE = MULTIPLE CHOICE (4)
            // --------------------------
            else if (type === "4") {
                try {
                    const res = await fetch(`/api/item/${itemId}/quiz`);
                    if (res.ok) {
                        const quiz = await res.json();
            
                        content += `
                            <div class="card mt-3 shadow-sm">
                                <div class="card-body">
                                    <h5 class="fw-bold fs-6 mb-3"> Pilihan Ganda</h5>
                        `;
            
                        quiz.forEach((q, i) => {
                            content += `
                                <div class="mb-4 p-3 border rounded bg-light">
                                    <h6 class="fw-bold">Soal ${i + 1}</h6>
                                    <p class="mb-2">${q.question_text}</p>
                                    <ul class="list-group">
                            `;
            
                            q.options.forEach(opt => {
                                // 🔥 FORCE convert ke integer → hasil pasti 0 atau 1
                                const isCorrect = parseInt(opt.is_correct) === 1;
            
                                content += `
                                    <li class="list-group-item d-flex justify-content-between align-items-center
                                            ${isCorrect ? 'list-group-item-success' : ''}">
                                        <span>${opt.option_text}</span>
                                        ${isCorrect 
                                            ? '<span class="badge bg-success">Benar</span>' 
                                            : '<span class="badge bg-secondary">Salah</span>'}
                                    </li>`;
                            });
            
                            content += `
                                    </ul>
                                </div>
                            `;
                        });
            
                        content += `
                                </div> <!-- end card-body -->
                            </div> <!-- end card -->
                        `;
                    }
                } catch (err) {
                    content += `<p class="text-danger">⚠️ Gagal memuat quiz.</p>`;
                }
            }

            // --------------------------
            // TYPE = FORUM DISKUSI (5)
            // --------------------------
           else if (type === "5") {
                try {
                    const res = await fetch(`/api/item/${itemId}/forum`);
                    if (res.ok) {
                        const forum = await res.json();
                        content += `
                            <div class="card mt-3 shadow-sm">
                                <div class="card-body">
                                    <h5 class="fw-bold mb-3">💬 Forum Diskusi</h5>
                                    
                                    <dl class="row mb-3">
                                        <dt class="col-sm-3">Judul</dt>
                                        <dd class="col-sm-9">${forum.forum_title || "-"}</dd>

                                        <dt class="col-sm-3">Pertanyaan</dt>
                                        <dd class="col-sm-9">${forum.forum_question || "-"}</dd>
                                    </dl>
                        `;

                        // ✅ cek attachment type
                       if (forum.attachment_type === "pdf" && forum.attachment_value) {
                            const modalId = `forumPdfModal-${Date.now()}`; // id unik biar tidak tabrakan

                            content += `
                                <div class="mt-4">
                                    <h6 class="fw-bold">📄 Lampiran PDF</h6>

                                    <!-- Tombol buka fullscreen -->
                                    <button class="btn btn-sm btn-outline-secondary mb-2"
                                            data-bs-toggle="modal" data-bs-target="#${modalId}">
                                        ⛶ Lihat Fullscreen
                                    </button>

                                    <!-- Preview kecil -->
                                    <div class="ratio ratio-16x9 border rounded overflow-hidden">
                                        <iframe src="/${forum.attachment_value}" 
                                                style="border:0;" 
                                                title="Preview Lampiran PDF Forum">
                                        </iframe>
                                    </div>
                                </div>

                                <!-- Modal fullscreen -->
                                <div class="modal fade" id="${modalId}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-fullscreen">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">📄 Lampiran PDF - Fullscreen</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-0">
                                                <iframe src="/${forum.attachment_value}" 
                                                        style="border:0; width:100%; height:100%;" 
                                                        title="Forum PDF Fullscreen">
                                                </iframe>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        } else if (forum.attachment_type === "url" && forum.attachment_value) {
                            content += `
                                <div class="mt-4">
                                    <h6 class="fw-bold">🔗 Lampiran URL</h6>
                                    <a href="${forum.attachment_value}" target="_blank" 
                                    class="btn btn-outline-primary">
                                        Buka Link
                                    </a>
                                </div>`;
                        } else if (forum.attachment_type === "video" && forum.attachment_value) {
                            content += `
                                <div class="mt-4">
                                    <h6 class="fw-bold">🎥 Lampiran Video</h6>
                                    <div class="ratio ratio-16x9 border rounded">
                                        <video controls>
                                            <source src="/${forum.attachment_value}" type="video/mp4">
                                            Browser tidak mendukung video
                                        </video>
                                    </div>
                                </div>`;
                        }

                        content += `
                                </div> <!-- end card-body -->
                            </div> <!-- end card -->
                        `;
                    }
                } catch (err) {
                    content += `<p class="text-danger">⚠️ Gagal memuat forum.</p>`;
                }
            }
            // --------------------------
            // TYPE = CERTIFICATE (6)
            // --------------------------
            else if (type === "6") {
                try {
                    const res = await fetch(`/api/item/${itemId}/certificate`);
                    if (res.ok) {
                        const cert = await res.json();
                        if (cert && cert.file_path) {
                            // ✅ kalau ada file_path dari API
                            content += `
                                <div class="mt-3 text-center">
                                    <h6>🎓 Sertifikat Kelulusan</h6>
                                    <img src="/${cert.file_path}" 
                                        alt="Certificate" 
                                        class="img-fluid rounded border"
                                        style="max-height:400px;">
                                </div>`;
                        } else {
                            throw new Error("Certificate not found");
                        }
                    } else {
                        throw new Error("API error");
                    }
                } catch (err) {
                    content += `
                        <div class="mt-3 text-center">
                            <h6>🎓 Sertifikat Kelulusan</h6>
                            <img src="/assets/img/course/default-certificated.jpg" 
                                alt="Default Certificate" 
                                class="img-fluid rounded border"
                                style="max-height:400px;">
                        </div>`;
                }
            }
            // --------------------------
            // TYPE = UNGGAHAN LEARNER (7)
            // --------------------------
            else if (type === "7" && file) {
                    const modalId = `assignmentModal-${Date.now()}`; // id unik

                    content += `
                        <div class="mt-3 border rounded p-3">
                            <div class="card shadow-sm">
                                <div class="m-2">
                                    <h6 class="fw-bold fs-6">📑 Contoh Submit Tugas</h6>
                                    <p class="text-muted mb-2">Contoh/Soal yang harus diikuti:</p>
                                </div>

                                <!-- Tombol buka fullscreen -->
                                <div class="mb-2 ms-2">
                                    <button class="btn btn-sm btn-outline-secondary" 
                                            data-bs-toggle="modal" data-bs-target="#${modalId}">
                                        ⛶ Lihat Fullscreen
                                    </button>
                                </div>

                                <!-- Preview kecil -->
                                <div class="ratio ratio-16x9 border rounded overflow-hidden">
                                    <iframe src="/${file}" 
                                        title="Preview Attachment"
                                        style="border:0;">
                                    </iframe>
                                </div>
                            </div>
                        </div>

                        <!-- Modal fullscreen -->
                        <div class="modal fade" id="${modalId}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-fullscreen">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">📑 Preview Submit Tugas - Fullscreen</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body p-0">
                                        <iframe src="/${file}" 
                                            style="border:0; width:100%; height:100%;" 
                                            title="Attachment Fullscreen">
                                        </iframe>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
            }
            // --------------------------
            // FALLBACK
            // --------------------------
            else {
                content += `<p class="text-muted fst-italic">Belum ada konten yang bisa ditampilkan.</p>`;
            }

            // Render ke panel
            detailPanel.innerHTML = content;

            // ✅ Aktifkan toggle Read More
            const readMoreLink = detailPanel.querySelector(".read-more");
            if (readMoreLink) {
                readMoreLink.addEventListener("click", function (ev) {
                    ev.preventDefault();
                    const descEl = detailPanel.querySelector(".desc-text");

                    if (this.innerText === "Read More") {
                        descEl.textContent = desc; // tampil full
                        this.innerText = "Show Less";
                    } else {
                        descEl.textContent = desc.substring(0, 150) + "..."; // kembali pendek
                        this.innerText = "Read More";
                    }
                });
            }
        });
    });
    
}
