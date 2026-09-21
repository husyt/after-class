// ============================================
// CORESYNC - Login Page Scripts
// ============================================

document.addEventListener('DOMContentLoaded', () => {

    const loginForm = document.getElementById('loginForm');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const submitBtn = document.getElementById('submitBtn');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const togglePassword = document.querySelector('.toggle-password');
    const capsWarning = document.getElementById('capsWarning');


    // ========================================
    // PASSWORD VISIBILITY TOGGLE
    // ========================================

    if (togglePassword && passwordInput) {

        togglePassword.addEventListener('click', () => {

            const type =
                passwordInput.type === 'password'
                    ? 'text'
                    : 'password';

            passwordInput.type = type;
        });
    }


    // ========================================
    // ERROR HELPERS
    // ========================================

    function showError(input, msg) {

        if (!input) return;

        input.classList.add('error');

        const el =
            document.getElementById(
                `${input.id}-error`
            );

        if (el) {
            el.textContent = msg;
        }
    }


    function clearError(input) {

        if (!input) return;

        input.classList.remove('error');

        const el =
            document.getElementById(
                `${input.id}-error`
            );

        if (el) {
            el.textContent = '';
        }
    }


    if (usernameInput) {

        usernameInput.addEventListener(
            'input',
            () => clearError(usernameInput)
        );
    }


    if (passwordInput) {

        passwordInput.addEventListener(
            'input',
            () => clearError(passwordInput)
        );
    }


    // ========================================
    // CAPS LOCK WARNING
    // ========================================

    if (passwordInput && capsWarning) {

        // Always hidden when page first loads
        capsWarning.style.display = 'none';


        function checkCapsLock(event) {

            const capsOn =
                event.getModifierState &&
                event.getModifierState('CapsLock');

            capsWarning.style.display =
                capsOn ? 'flex' : 'none';
        }


        passwordInput.addEventListener(
            'keydown',
            checkCapsLock
        );


        passwordInput.addEventListener(
            'keyup',
            checkCapsLock
        );


        // Hide when leaving password field
        passwordInput.addEventListener(
            'blur',
            () => {
                capsWarning.style.display = 'none';
            }
        );
    }


    // ========================================
    // FORM SUBMISSION
    // ========================================

    if (
        loginForm &&
        usernameInput &&
        passwordInput
    ) {

        loginForm.addEventListener(
            'submit',
            (e) => {

                let valid = true;


                if (
                    usernameInput.value
                        .trim()
                        .length < 3
                ) {

                    showError(
                        usernameInput,
                        'Username must be at least 3 characters'
                    );

                    valid = false;
                }


                if (
                    passwordInput.value.length < 8
                ) {

                    showError(
                        passwordInput,
                        'Password must be at least 8 characters'
                    );

                    valid = false;
                }


                if (!valid) {

                    e.preventDefault();

                    return;
                }


                if (submitBtn) {

                    submitBtn.classList.add(
                        'loading'
                    );

                    submitBtn.disabled = true;
                }


                if (loadingOverlay) {

                    loadingOverlay.classList.add(
                        'active'
                    );
                }
            }
        );
    }


    // ========================================
    // AUTO-DISMISS ALERTS
    // ========================================

    const alertBox =
        document.getElementById('alertBox');


    if (alertBox) {

        setTimeout(() => {

            alertBox.style.transition =
                'opacity 0.4s';

            alertBox.style.opacity = '0';


            setTimeout(() => {

                alertBox.remove();

            }, 400);

        }, 5000);
    }

});