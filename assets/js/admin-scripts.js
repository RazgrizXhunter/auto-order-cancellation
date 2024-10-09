// admin-scripts.js

document.addEventListener('DOMContentLoaded', function () {
    // Confirm before clearing logs
    const clearLogButton = document.querySelector('form input[name="clear_log"]');
    if (clearLogButton) {
        clearLogButton.closest('form').addEventListener('submit', function (e) {
            if (!confirm("Are you sure you want to clear the log file? This action cannot be undone.")) {
                e.preventDefault();
            }
        });
    }

    // Auto-refresh logs display after clearing
    if (window.location.href.includes('tab=logs') && clearLogButton) {
        const clearLogForm = clearLogButton.closest('form');
        clearLogForm.addEventListener('submit', function () {
            setTimeout(function () {
                location.reload();
            }, 500); // Refresh after 500ms to ensure the log is cleared
        });
    }
});
