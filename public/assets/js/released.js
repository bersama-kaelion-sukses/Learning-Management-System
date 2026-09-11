export function InitReleased() {
    // ===========================
    // Render Content By Course         
    // ===========================
    // Base Logic -> Take Module Data from Server  (Done) -> 
    // Trigger Click to getting know this Value is Getting Click form JS. 
    // -> Get Route From Js to WEb -> Controller. 

    const itemLinks = document.querySelectorAll('.item-trigger');

    itemLinks.forEach(link => {
        link.addEventListener('click', async function (e) {
            e.preventDefault();

            // ===============================
            // 1️⃣ Ambil Data dari HTML (Blade)
            // ===============================
            const itemId = this.dataset.itemId;
            const itemName = this.dataset.itemName;
            const courseId = document.getElementById('courseSelect')?.value;

            if (!courseId) {
                alert("⚠️ Pilih course terlebih dahulu!");
                return;
            }

            // ===============================
            // 2️⃣ Tentukan Endpoint / Route
            // ===============================
            const url = `/learner-submission-realeased/submissions/${courseId}/${itemId}`;
            // console.log("Fetching data from:", url);

            // ===============================
            // 3️⃣ Tampilkan loading state
            // ===============================
            const container = document.getElementById('submissionList');
            container.innerHTML = `
                <div class="text-center text-muted py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-2">Memuat data submission...</div>
                </div>
            `;

            try {
                // ===============================
                // 4️⃣ Panggil API Laravel
                // ===============================
                const response = await fetch(url);
                const data = await response.json();

                if (!data.success) {
                    container.innerHTML = `
                        <div class="alert alert-warning">⚠️ ${data.message || "Gagal memuat data."}</div>
                    `;
                    return;
                }

                // ===============================
                // 5️⃣ Render hasil ke panel kanan
                // ===============================
                renderSubmissionPanel(data);
            } catch (error) {
                // console.error(error);
                container.innerHTML = `
                    <div class="alert alert-danger">❌ Terjadi kesalahan saat mengambil data.</div>
                `;
            }
        });
    });



    // ==================================================
    // ✨ Function: Render Submission Panel
    // ==================================================
    function formatDateTime(dateString) {
        if (!dateString) return "-";
        const d = new Date(dateString);
        const dd = String(d.getDate()).padStart(2, "0");
        const mm = String(d.getMonth() + 1).padStart(2, "0");
        const yyyy = d.getFullYear();
        const hh = String(d.getHours()).padStart(2, "0");
        const mi = String(d.getMinutes()).padStart(2, "0");
        return `${dd}/${mm}/${yyyy} ${hh}:${mi}`;
    }

   function renderSubmissionPanel(data) {
        const container = document.getElementById('submissionList');
        if (!container) return;

        const { item_name, item_type, submissions } = data;

        if (!submissions.length) {
            container.innerHTML = `
                <div class="alert alert-warning mb-0">
                    ⚠️ Tidak ada submission ditemukan.
                </div>
            `;
            return;
        }

            // ======================================
            // 1️⃣ Sort: yang sudah submit tampil dulu
            // ======================================
            const sortedSubs = [...submissions].sort((a, b) => {
                return (b.is_submitted ? 1 : 0) - (a.is_submitted ? 1 : 0);
            });

            // ======================================
            // 2️⃣ Tipe materi label
            // ======================================
            const typeLabel = {
                3: "Essay",
                4: "Multiple Choice",
                7: "Attachment Upload"
            }[item_type] || "Unknown";

            // ======================================
            // 3️⃣ Generate list dengan format waktu
            // ======================================
            const listHTML = sortedSubs.map((s, i) => `
                <div class="border-bottom py-2 d-flex justify-content-between align-items-start">
                    <div>
                        <div><strong>${i + 1}. ${s.full_name}</strong></div>
                        ${s.is_submitted
                            ? `<span class="text-success">✅ Sudah Mengumpulkan</span>
                            <br><small>Dikirim: ${formatDateTime(s.submitted_at)}<br>
                            Nilai: ${s.grade ?? '-'} | Status: ${s.status ?? '-'}</small>`
                            : `<span class="text-danger">❌ Belum Mengumpulkan</span>`
                        }
                    </div>
                    ${s.is_submitted ? `
                        <button class="btn btn-outline-danger btn-sm ms-2 delete-submission-btn"
                                data-user-id="${s.user_id}"
                                data-item-id="${data.item_id}"
                                data-course-id="${data.course_id}">
                            Delete
                        </button>` : ''}
                </div>
            `).join('');

            // ======================================
            // 4️⃣ Render ke DOM
            // ======================================
            container.innerHTML = `
                <h5 class="fw-bold">${item_name}</h5>
                <div class="small text-muted mb-2">Tipe Materi: ${typeLabel}</div>
                <div class="border rounded p-2 bg-light" style="max-height: 650px;overflow-y:auto;">
                    ${listHTML}
                </div>
            `;

            // ======================================
            // 5️⃣ Event tombol delete
            // ======================================
            const deleteButtons = container.querySelectorAll('.delete-submission-btn');
            deleteButtons.forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    e.preventDefault();
                    const userId = btn.dataset.userId;
                    const itemId = btn.dataset.itemId;
                    const courseId = btn.dataset.courseId;

                    if (!confirm(`Yakin ingin menghapus jawaban ${userId}?`)) return;

                    try {
                        const res = await fetch(`/learner-submission-realeased/delete/${courseId}/${itemId}/${userId}`, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                        });

                        const result = await res.json();
                        if (result.success) {
                            // ✅ 1. Ubah status tampilan jadi belum mengumpulkan
                            const row = btn.closest('.border-bottom');
                            const successBadge = row.querySelector('.text-success');
                            if (successBadge) {
                                successBadge.outerHTML = `<span class="text-danger">❌ Belum Mengumpulkan</span>`;
                            }

                            // ✅ 2. Hapus elemen "Dikirim" dan tombol delete
                            const infoLines = row.querySelectorAll('small');
                            infoLines.forEach(line => line.remove());
                            btn.remove();

                            // ✅ 3. (Opsional) ubah urutan list supaya user belum submit pindah ke bawah
                            const parentContainer = row.parentElement;
                            parentContainer.appendChild(row);

                        } else {
                            alert('❌ Gagal menghapus submission.');
                        }
                    } catch (err) {
                        // console.error(err);
                        alert('❌ Error saat menghapus submission.');
                    }
                });
            });
        }

    // ===============================
    //  Search Input + Dropdown Result 
    // ===============================
        const searchInput = document.getElementById("courseSearch");
        const hiddenInput = document.getElementById("courseSelected");
        const applyBtn = document.getElementById("applyCourseBtn");
        const moduleContainer = document.getElementById("moduleList");
        const submissionContainer = document.getElementById("submissionList");

        // Ambil semua course dari Blade
        const courses = Array.from(document.querySelectorAll("#courseSelect option"))
            .filter(opt => opt.value !== "")
            .map(opt => ({
                id: opt.value,
                name: opt.textContent.replace(/\s{2,}/g, " ").trim()
            }));

        // 🔹 Buat dropdown hasil pencarian
        const dropdown = document.createElement("div");
        dropdown.classList.add("list-group", "position-absolute", "shadow-sm", "bg-white");
        dropdown.style.width = `${searchInput.offsetWidth}px`;
        dropdown.style.maxHeight = "200px";
        dropdown.style.overflowY = "auto";
        dropdown.style.zIndex = "1050";
        dropdown.style.display = "none";
        dropdown.style.position = "absolute";
        dropdown.style.top = `${searchInput.offsetTop + searchInput.offsetHeight}px`;
        dropdown.style.left = `${searchInput.offsetLeft}px`;
        searchInput.parentElement.style.position = "relative";
        searchInput.parentElement.appendChild(dropdown);

        let currentIndex = -1;

        // 🔹 Render dropdown hasil pencarian
        function renderDropdown(keyword) {
            dropdown.innerHTML = "";
            const lower = keyword.toLowerCase().trim();
            const filtered = courses.filter(c => c.name.toLowerCase().includes(lower));

            currentIndex = -1;
            if (filtered.length === 0) {
                dropdown.innerHTML = `<div class="list-group-item text-muted fst-italic">Tidak ada hasil</div>`;
                dropdown.style.display = "block";
                return;
            }

            filtered.forEach((c) => {
                const item = document.createElement("button");
                item.type = "button";
                item.classList.add("list-group-item", "list-group-item-action");
                item.textContent = c.name;
                item.dataset.id = c.id;
                item.addEventListener("click", () => selectCourse(c));
                dropdown.appendChild(item);
            });

            dropdown.dataset.filtered = JSON.stringify(filtered);
            dropdown.style.display = "block";
        }

        function selectCourse(course) {
            searchInput.value = course.name.replace(/\s{2,}/g, " ").trim();
            hiddenInput.value = course.id;
            dropdown.style.display = "none";
            searchInput.dataset.selected = "true";
        }

        function highlightItem(index) {
            const items = dropdown.querySelectorAll(".list-group-item");
            items.forEach((item, i) => {
                if (i === index) {
                    item.classList.add("active");
                    item.scrollIntoView({ block: "nearest" });
                } else {
                    item.classList.remove("active");
                }
            });
        }

        // Keyboard navigation
        searchInput.addEventListener("keydown", (e) => {
            const items = dropdown.querySelectorAll(".list-group-item");
            const filtered = dropdown.dataset.filtered ? JSON.parse(dropdown.dataset.filtered) : [];
            if (dropdown.style.display === "none" || items.length === 0) return;

            switch (e.key) {
                case "ArrowDown":
                    e.preventDefault();
                    if (currentIndex < items.length - 1) currentIndex++;
                    highlightItem(currentIndex);
                    break;
                case "ArrowUp":
                    e.preventDefault();
                    if (currentIndex > 0) currentIndex--;
                    highlightItem(currentIndex);
                    break;
                case "Enter":
                    e.preventDefault();
                    if (currentIndex >= 0 && filtered[currentIndex]) selectCourse(filtered[currentIndex]);
                    break;
                case "Escape":
                    dropdown.style.display = "none";
                    break;
            }
        });

        // Saat mengetik
        searchInput.addEventListener("keyup", (e) => {
            if (["ArrowDown", "ArrowUp", "Enter", "Escape"].includes(e.key)) return;
            const keyword = e.target.value;
            if (!keyword.trim()) {
                dropdown.style.display = "none";
                hiddenInput.value = "";
                return;
            }
            renderDropdown(keyword);
        });

        // Klik di luar dropdown
        document.addEventListener("click", (e) => {
            if (!dropdown.contains(e.target) && e.target !== searchInput) dropdown.style.display = "none";
        });

}
