document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.tab');
    const signinPanel = document.getElementById('signinPanel');
    const qrPanel = document.getElementById('qrPanel');
    const qrCodeContainer = document.getElementById('qrCodeContainer');
    const qrStatus = document.getElementById('qrStatus');
    const refreshBtn = document.getElementById('refreshQR');

    let pollInterval = null;
    let expireTimer = null;

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            if (tab.dataset.tab === 'qr') {
                signinPanel.style.display = 'none';
                qrPanel.style.display = 'block';

                generateQR();
            } else {
                signinPanel.style.display = 'block';
                qrPanel.style.display = 'none';

                stopPolling();
            }
        });
    });


    async function generateQR() {
        stopPolling();

        qrCodeContainer.innerHTML =
            '<div class="qr-loading">Generating...</div>';

        qrStatus.className = 'qr-status';

        qrStatus.innerHTML =
            '<span class="pulse-dot"></span> Generating secure QR...';

        try {

            const response = await fetch(
                'qr_generate.php?ts=' + Date.now(),
                {
                    method: 'GET',
                    cache: 'no-store'
                }
            );

            const rawResponse = await response.text();

            console.log('QR GENERATE RESPONSE:', rawResponse);

            let data;

            try {
                data = JSON.parse(rawResponse);
            } catch (error) {
                throw new Error(
                    'qr_generate.php did not return valid JSON: ' +
                    rawResponse
                );
            }


            if (!response.ok || !data.success) {
                throw new Error(
                    data.error || 'QR generation failed.'
                );
            }


            if (!data.token || !data.scan_url) {
                throw new Error(
                    'QR response is missing token or scan URL.'
                );
            }


            console.log('QR TOKEN:', data.token);
            console.log('QR SCAN URL:', data.scan_url);


            qrCodeContainer.innerHTML = '';


            if (typeof QRCode === 'undefined') {
                throw new Error(
                    'QRCode JavaScript library failed to load.'
                );
            }


            new QRCode(qrCodeContainer, {
                text: data.scan_url,
                width: 200,
                height: 200,
                colorDark: '#1a1a1a',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.H
            });


            qrStatus.className = 'qr-status';

            qrStatus.innerHTML =
                '<span class="pulse-dot"></span> Waiting for phone scan...';


            startPolling(data.token);


            expireTimer = setTimeout(() => {

                stopPolling();

                qrStatus.className =
                    'qr-status expired';

                qrStatus.innerHTML =
                    '⚠ QR code expired. Click refresh.';

            }, 300000);


        } catch (error) {

            console.error('QR ERROR:', error);

            qrCodeContainer.innerHTML =
                '<div class="qr-loading" style="color:#d32f2f;">QR Error</div>';

            qrStatus.className =
                'qr-status expired';

            qrStatus.textContent =
                error.message;
        }
    }


    function startPolling(token) {

        stopPolling(false);

        pollInterval = setInterval(async () => {

            try {

                const response = await fetch(
                    'qr_check.php?token=' +
                    encodeURIComponent(token) +
                    '&ts=' +
                    Date.now(),
                    {
                        cache: 'no-store'
                    }
                );


                const rawResponse =
                    await response.text();

                let data;

                try {
                    data = JSON.parse(rawResponse);
                } catch (error) {

                    console.error(
                        'QR CHECK INVALID RESPONSE:',
                        rawResponse
                    );

                    return;
                }


                console.log(
                    'QR STATUS:',
                    data.status
                );


                if (data.status === 'approved') {

                    stopPolling();

                    qrStatus.className =
                        'qr-status approved';

                    qrStatus.innerHTML =
                        '✓ Approved! Signing you in...';


                    setTimeout(() => {

                        window.location.href =
                            'dashboard.php';

                    }, 700);

                }


                else if (
                    data.status === 'expired' ||
                    data.status === 'error'
                ) {

                    stopPolling();

                    qrStatus.className =
                        'qr-status expired';

                    qrStatus.innerHTML =
                        '⚠ QR code expired. Click refresh.';
                }


            } catch (error) {

                console.error(
                    'QR polling error:',
                    error
                );
            }

        }, 2000);
    }


    function stopPolling(clearExpiry = true) {

        if (pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
        }

        if (clearExpiry && expireTimer) {
            clearTimeout(expireTimer);
            expireTimer = null;
        }
    }


    if (refreshBtn) {

        refreshBtn.addEventListener(
            'click',
            generateQR
        );
    }
});