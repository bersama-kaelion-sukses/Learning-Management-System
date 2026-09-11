export function InitNotification() {
     const alertEl = document.getElementById('alert-message');
        if (alertEl) {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alertEl);
                bsAlert.close();
            }, 3000); // hilang setelah 3 detik
        }
}