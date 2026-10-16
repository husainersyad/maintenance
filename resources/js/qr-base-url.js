import { renderQrCode } from './qrcode-canvas';

/**
 * Composes the full URL for every QR code on employees/qr-codes.blade.php
 * from the site-wide base URL (a server-side setting that only the
 * Administrator can change) plus each employee's relative verify path.
 */
function initQrBaseUrl() {
    const panel = document.getElementById('qr-base-url-panel');

    if (!panel) {
        return;
    }

    const baseUrl = panel.dataset.baseUrl.replace(/\/+$/, '');

    document.querySelectorAll('canvas[data-qr-path]').forEach((canvas) => {
        const fullUrl = baseUrl + canvas.dataset.qrPath;
        canvas.dataset.qrcode = fullUrl;
        renderQrCode(canvas, fullUrl);

        const urlText = canvas.closest('div')?.parentElement?.querySelector('[data-qr-url-text]');

        if (urlText) {
            urlText.textContent = fullUrl;
        }
    });
}

document.addEventListener('DOMContentLoaded', initQrBaseUrl);
