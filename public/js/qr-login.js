// public/js/qr-login.js
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        const container   = document.getElementById('qrCodeContainer');
        const statusEl    = document.getElementById('qrStatus');
        const refreshBtn  = document.getElementById('refreshQR');
        const qrPanel     = document.getElementById('qrPanel');
        const signinPanel = document.getElementById('signinPanel');

        if (!container) return;

        let pollInterval = null;

        document.querySelectorAll('.tab').forEach(function (tab) {
            tab.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                const which = tab.dataset.tab;
                if (which === 'qr') {
                    if (qrPanel) qrPanel.style.display = 'block';
                    if (signinPanel) signinPanel.style.display = 'none';

                    if (!container.querySelector('canvas') && !container.querySelector('img')) {
                        generateQR();
                    }
                } else {
                    if (qrPanel) qrPanel.style.display = 'none';
                    if (signinPanel) signinPanel.style.display = 'block';
                    if (pollInterval) { clearInterval(pollInterval); pollInterval = null; }
                }
            });
        });

        async function generateQR() {
            if (pollInterval) { clearInterval(pollInterval); pollInterval = null; }

            container.innerHTML = '<div class="qr-loading">Generating...</div>';
            if (statusEl) {
                statusEl.style.color = '';
                statusEl.innerHTML = '<span class="pulse-dot"></span> Generating...';
            }

            try {
                const res = await fetch('qr_generate.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
                const data = await res.json();

                if (!data.success) {
                    container.innerHTML = '<div class="qr-loading">' + (data.error || 'Failed') + '</div>';
                    if (statusEl) statusEl.textContent = data.error || 'Error';
                    return;
                }

                container.innerHTML = '';
                if (typeof QRCode !== 'undefined') {
                    new QRCode(container, {
                        text: data.qr_url,
                        width: 200,
                        height: 200,
                        colorDark: '#000000',
                        colorLight: '#ffffff',
                        correctLevel: QRCode.CorrectLevel.H
                    });
                }

                if (statusEl) statusEl.innerHTML = '<span class="pulse-dot"></span> Scan with your phone';
                startPolling();

            } catch (err) {
                console.error(err);
                container.innerHTML = '<div class="qr-loading">Network error</div>';
            }
        }

        function startPolling() {
            let attempts = 0;
            pollInterval = setInterval(async () => {
                attempts++;
                if (attempts > 150) {
                    clearInterval(pollInterval); pollInterval = null;
                    if (statusEl) {
                        statusEl.innerHTML = '<span class="pulse-dot"></span> Expired. Refreshing...';
                        statusEl.style.color = '#f39c12';
                    }
                    setTimeout(generateQR, 800);
                    return;
                }

                try {
                    const res = await fetch('qr_check.php');
                    const data = await res.json();

                    if (data.status === 'approved' && data.redirect) {
                        clearInterval(pollInterval); pollInterval = null;
                        if (statusEl) {
                            statusEl.innerHTML = '<span class="pulse-dot"></span> ✓ Approved!';
                            statusEl.style.color = '#2ecc71';
                        }
                        setTimeout(() => window.location.href = data.redirect, 400);

                    } else if (data.status === 'expired') {
                        clearInterval(pollInterval); pollInterval = null;
                        if (statusEl) {
                            statusEl.innerHTML = '<span class="pulse-dot"></span> Expired. Refreshing...';
                            statusEl.style.color = '#f39c12';
                        }
                        setTimeout(generateQR, 800);

                    } else if (data.status === 'not_found' || data.status === 'no_token') {
                        clearInterval(pollInterval); pollInterval = null;
                        generateQR();
                    }
                } catch (err) { console.error('Poll error:', err); }
            }, 2000);
        }

        if (refreshBtn) {
            refreshBtn.addEventListener('click', e => {
                e.preventDefault();
                generateQR();
            });
        }

        window.generateQR = generateQR;
        console.log('✓ qr-login.js loaded');
    });
})();