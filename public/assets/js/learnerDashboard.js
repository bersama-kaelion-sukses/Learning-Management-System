export function InitLearnerCourse() {
    const courseSelect = document.getElementById("filterCourseSelect");
    const taskList = document.querySelector("#taskList");

    // Ambil semua task li dengan atribut data-course-id
    const allTasks = taskList ? [...taskList.querySelectorAll("li[data-course-id]")] : [];

    // Tambahkan placeholder pesan kosong
    let emptyMsg = document.getElementById("emptyTaskMsg");
    if (!emptyMsg) {
        emptyMsg = document.createElement("li");
        emptyMsg.id = "emptyTaskMsg";
        emptyMsg.className = "list-group-item text-muted d-none";
        emptyMsg.textContent = "Belum ada aktivitas saat ini";
        taskList.appendChild(emptyMsg);
    }

    // Event ketika select berubah
    courseSelect?.addEventListener("change", function () {
        const selectedCourseId = this.value.trim(); // bisa kosong
        let visibleCount = 0;

        allTasks.forEach(task => {
            const taskCourseId = task.dataset.courseId;

            if (!selectedCourseId || taskCourseId === selectedCourseId) {
                task.classList.remove("d-none");
                visibleCount++;
            } else {
                task.classList.add("d-none");
            }
        });

        // Kalau tidak ada yang terlihat, tampilkan pesan kosong
        if (visibleCount === 0) {
            emptyMsg.classList.remove("d-none");
        } else {
            emptyMsg.classList.add("d-none");
        }
    });
}
