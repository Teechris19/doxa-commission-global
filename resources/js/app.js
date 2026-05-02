import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.css';
import intersect from '@alpinejs/intersect';

if (window.Alpine) {
    window.Alpine.plugin(intersect);
}

window.flatpickr = flatpickr;

let deferredPrompt = null;

// The beforeinstallprompt and installation logic is now handled in the layout
// for better integration with the UI components there.

document.addEventListener('DOMContentLoaded', () => {
    const container = document.querySelector('#flash-message-container');

    if (container && window.bootstrap && window.bootstrap.Alert) {
        const alerts = container.querySelectorAll('.alert');
        alerts.forEach((alert) => {
            setTimeout(() => {
                const bsAlert = new window.bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    }
});

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Registration errors are non-fatal for page rendering.
        });
    });
}
