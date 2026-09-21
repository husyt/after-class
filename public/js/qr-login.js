document.addEventListener('DOMContentLoaded', () => {

    const tabs = document.querySelectorAll('.tab');

    const signinPanel =
        document.getElementById('signinPanel');

    const qrPanel =
        document.getElementById('qrPanel');

    const qrCodeContainer =
        document.getElementById('qrCodeContainer');

    const qrStatus =
        document.getElementById('qrStatus');

    const refreshBtn =
        document.getElementById('refreshQR');

    const verifyBtn =
        document.getElementById('verifyQR');

    const codeInput =
        document.getElementById('qrVerificationCode');

    let expirationTimer = null;


    // ==========================================
    // TAB SWITCHING
    // ==========================================

    tabs.forEach(tab => {

        tab.addEventListener('click', () => {

            tabs.forEach(t => {
                t.classList.remove('active');
            });

            tab.classList.add('active');


            if (tab.dataset.tab === 'qr') {

                signinPanel.style.display = 'none';
                qrPanel.style.display = 'block';

                generateQR();

            } else {

                signinPanel.style.display = 'block';
                qrPanel.style.display = 'none';

                stopTimer();
            }
        });
    });


    // ==========================================
    // GENERATE QR
    // ==========================================

    async function generateQR() {

        stopTimer();

        qrCodeContainer.innerHTML =
            '<div class="qr-loading">Generating...</div>';

        qrStatus.className = 'qr-status';

        qrStatus.textContent =
            'Generating verification QR...';

        if (codeInput) {

            codeInput.value = '';
            codeInput.disabled = false;
        }

        if (verifyBtn) {

            verifyBtn.disabled = false;
        }


        try {

            const response = await fetch(
                'qr_generate.php?ts=' + Date.now(),
                {
                    cache: 'no-store'
                }
            );

            const data = await response.json();


            if (!data.success) {

                throw new Error(
                    data.error ||
                    'Unable to generate QR.'
                );
            }


            if (!data.qr_text) {

                throw new Error(
                    'QR verification data is missing.'
                );
            }


            qrCodeContainer.innerHTML = '';


            new QRCode(
                qrCodeContainer,
                {
                    text: data.qr_text,
                    width: 200,
                    height: 200,
                    colorDark: '#1a1a1a',
                    colorLight: '#ffffff',
                    correctLevel:
                        QRCode.CorrectLevel.H
                }
            );


            qrStatus.className =
                'qr-status';

            qrStatus.textContent =
                'Scan QR with your phone and enter the 6-digit code below.';


            expirationTimer =
                setTimeout(() => {

                    qrStatus.className =
                        'qr-status expired';

                    qrStatus.textContent =
                        '⚠ QR expired. Generate a new QR.';

                    if (codeInput) {
                        codeInput.disabled = true;
                    }

                    if (verifyBtn) {
                        verifyBtn.disabled = true;
                    }

                }, 300000);


        } catch (error) {

            console.error(error);

            qrCodeContainer.innerHTML =
                '<div class="qr-loading" style="color:#d32f2f;">QR Error</div>';

            qrStatus.className =
                'qr-status expired';

            qrStatus.textContent =
                error.message;
        }
    }


    // ==========================================
    // VERIFY CODE
    // ==========================================

    async function verifyCode() {

        const code =
            codeInput.value.trim();


        if (!/^\d{6}$/.test(code)) {

            qrStatus.className =
                'qr-status expired';

            qrStatus.textContent =
                'Enter the 6-digit code shown on your phone.';

            return;
        }


        qrStatus.className =
            'qr-status';

        qrStatus.textContent =
            'Verifying...';

        verifyBtn.disabled =
            true;


        try {

            const response =
                await fetch(
                    'qr_verify.php',
                    {
                        method: 'POST',

                        headers: {
                            'Content-Type':
                                'application/json'
                        },

                        body: JSON.stringify({
                            code: code
                        })
                    }
                );


            const data =
                await response.json();


            if (data.success) {

                stopTimer();

                qrStatus.className =
                    'qr-status approved';

                qrStatus.textContent =
                    '✓ QR verified! Returning to Sign-in...';

                codeInput.disabled =
                    true;

                verifyBtn.disabled =
                    true;


                setTimeout(() => {

                    // Switch back to Sign-in tab
                    tabs.forEach(t => {
                        t.classList.remove('active');
                    });

                    const signinTab =
                        document.querySelector(
                            '.tab[data-tab="signin"]'
                        );

                    if (signinTab) {
                        signinTab.classList.add('active');
                    }

                    qrPanel.style.display =
                        'none';

                    signinPanel.style.display =
                        'block';

                    const username =
                        document.getElementById(
                            'username'
                        );

                    if (username) {
                        username.focus();
                    }

                }, 1000);


                return;
            }


            qrStatus.className =
                'qr-status expired';

            qrStatus.textContent =
                data.message ||
                'Incorrect verification code.';

            verifyBtn.disabled =
                false;


        } catch (error) {

            console.error(error);

            qrStatus.className =
                'qr-status expired';

            qrStatus.textContent =
                'Verification failed.';

            verifyBtn.disabled =
                false;
        }
    }


    function stopTimer() {

        if (expirationTimer) {

            clearTimeout(
                expirationTimer
            );

            expirationTimer =
                null;
        }
    }


    if (refreshBtn) {

        refreshBtn.addEventListener(
            'click',
            generateQR
        );
    }


    if (verifyBtn) {

        verifyBtn.addEventListener(
            'click',
            verifyCode
        );
    }


    if (codeInput) {

        codeInput.addEventListener(
            'input',
            () => {

                codeInput.value =
                    codeInput.value
                        .replace(/\D/g, '')
                        .slice(0, 6);
            }
        );


        codeInput.addEventListener(
            'keydown',
            event => {

                if (event.key === 'Enter') {

                    event.preventDefault();

                    verifyCode();
                }
            }
        );
    }

});