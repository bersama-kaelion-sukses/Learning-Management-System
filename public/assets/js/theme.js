export function InitTheme() {
    const root = document.documentElement;
    const lightBtn = document.getElementById('btn-light');
    const darkBtn = document.getElementById('btn-dark');

    function updateButtonStyles(theme) {
        if (!lightBtn || !darkBtn) return;
        if (theme === 'dark') {
            lightBtn.className = 'btn btn-outline-light';
            darkBtn.className = 'btn btn-light text-dark fw-bold';
        } else {
            lightBtn.className = 'btn btn-success text-white fw-bold';
            darkBtn.className = 'btn btn-outline-dark';
        }
    }

    // Ambil tema tersimpan dari localStorage
    const savedTheme = localStorage.getItem('theme') || 'light';
    root.setAttribute('data-bs-theme', savedTheme);
    updateButtonStyles(savedTheme);

    // Mode Terang
    if (lightBtn) {
        lightBtn.addEventListener('click', () => {
            root.setAttribute('data-bs-theme', 'light');
            localStorage.setItem('theme', 'light');
            updateButtonStyles('light');
        });
    }

    // Mode Gelap
    if (darkBtn) {
        darkBtn.addEventListener('click', () => {
            root.setAttribute('data-bs-theme', 'dark');
            localStorage.setItem('theme', 'dark');
            updateButtonStyles('dark');
        });
    }
}
