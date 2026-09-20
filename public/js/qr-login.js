document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.tab');
    const signinPanel = document.getElementById('signinPanel');
    const qrPanel = document.getElementById('qrPanel');
    const qrCodeContainer = document.getElementById('qrCodeContainer');
    const qrStatus = document.getElementById('qrStatus');
    const refreshBtn = document.getElementById('refreshQR');

    let pollInterval = null;

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            if (tab.dataset.tab === 'qr') {
                if (signinPanel) signinPanel.style.display = 'none';
                if (qrPanel) qrPanel.style.display = 'block';
                generateQR();
            } else {
                if (signinPanel) signinPanel.style.display = 'block';
                if (qrPanel) qrPanel.style.display = 'none';
                stopPolling();
            }
        });
    });

    async function generateQR() {
        stopPolling();
        qrCodeContainer.innerHTML = '<div class="qr-loading">Generating...</div>';
        qrStatus.className = 'qr-status';
        qrStatus.innerHTML = '<span class="pulse-dot"></span> Waiting for scan...';

        try {
            const res = await fetch('qr_generate.php');
            const data = await res.json();
            if (!data.success) throw new Error('Failed');

            qrCodeContainer.innerHTML = '';
            new QRCode(qrCodeContainer, {
                text: data.scan_url,
                width: 200, height: 200,
                colorDark: '#1a1a1a', colorLight: '#fff',
                correctLevel: QRCode.CorrectLevel.H
            });

            startPolling(data.token);

            setTimeout(() => {
                if (pollInterval) {
                    stopPolling();
                    qrStatus.className = 'qr-status expired';
                    qrStatus.innerHTML = '⚠ QR code expired. Click refresh.';
                }
            }, 300000);
        } catch (err) {
            qrCodeContainer.innerHTML = '<div class="qr-loading" style="color:#d32f2f;">Error</div>';
        }
    }

    function startPolling(token) {
        pollInterval = setInterval(async () => {
            try {
                const res = await fetch(`qr_check.php?token=${token}`);
                const data = await res.json();
                if (data.status === 'approved') {
                    stopPolling();
                    qrStatus.className = 'qr-status approved';
                    qrStatus.innerHTML = '✓ Approved! Redirecting...';
                    setTimeout(() => window.location.href = 'dashboard.php', 800);
                } else if (data.status === 'expired') {
                    stopPolling();
                    qrStatus.className = 'qr-status expired';
                    qrStatus.innerHTML = '⚠ QR code expired.';
                }
            } catch (err) {}
        }, 2000);
    }

    function stopPolling() {
        if (pollInterval) { clearInterval(pollInterval); pollInterval = null; }
    }

    if (refreshBtn) refreshBtn.addEventListener('click', generateQR);
});