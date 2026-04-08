import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.css';
import intersect from '@alpinejs/intersect';

if (window.Alpine) {
    window.Alpine.plugin(intersect);
}

window.flatpickr = flatpickr;

let deferredPrompt = null;

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredPrompt = event;
    window.dispatchEvent(new CustomEvent('pwa-installable'));
});

window.addEventListener('appinstalled', () => {
    deferredPrompt = null;
});

window.triggerPwaInstall = async () => {
    if (!deferredPrompt) {
        return false;
    }

    deferredPrompt.prompt();
    await deferredPrompt.userChoice;
    deferredPrompt = null;
    return true;
};

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
