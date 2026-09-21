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
    if (togglePassword) {
        togglePassword.addEventListener('click', () => {
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
        });
    }

    // ========================================
    // ERROR HELPERS
    // ========================================
    function showError(input, msg) {
        input.classList.add('error');
        const el = document.getElementById(`${input.id}-error`);
        if (el) el.textContent = msg;
    }

    function clearError(input) {
        input.classList.remove('error');
        const el = document.getElementById(`${input.id}-error`);
        if (el) el.textContent = '';
    }

    usernameInput.addEventListener('input', () => clearError(usernameInput));
    passwordInput.addEventListener('input', () => clearError(passwordInput));

    // ========================================
    // CAPS LOCK WARNING
    // ========================================
    if (passwordInput && capsWarning) {
        passwordInput.addEventListener('keyup', (e) => {
            if (e.getModifierState && e.getModifierState('CapsLock')) {
                capsWarning.style.display = 'flex';
            } else {
                capsWarning.style.display = 'none';
            }
        });

        passwordInput.addEventListener('keydown', (e) => {
            if (e.getModifierState && e.getModifierState('CapsLock')) {
                capsWarning.style.display = 'flex';
            } else {
                capsWarning.style.display = 'none';
            }
        });

        // Hide warning when the field loses focus
        passwordInput.addEventListener('blur', () => {
            capsWarning.style.display = 'none';
        });
        const test = document.getElementById('capsWarning');
console.log('Found:', test);
if (test) {
    test.style.display = 'flex';
    console.log('Display set to flex');
}
    }

    // ========================================
    // FORM SUBMISSION
    // ========================================
    if (loginForm) {
        loginForm.addEventListener('submit', (e) => {
            let valid = true;

            if (usernameInput.value.trim().length < 3) {
                showError(usernameInput, 'Username must be at least 3 characters');
                valid = false;
            }

            if (passwordInput.value.length < 8) {
                showError(passwordInput, 'Password must be at least 8 characters');
                valid = false;
            }

            if (!valid) {
                e.preventDefault();
                return;
            }

            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            if (loadingOverlay) loadingOverlay.classList.add('active');
        });
    }

    // ========================================
    // AUTO-DISMISS ALERTS
    // ========================================
    const alertBox = document.getElementById('alertBox');
    if (alertBox) {
        setTimeout(() => {
            alertBox.style.transition = 'opacity 0.4s';
            alertBox.style.opacity = '0';
            setTimeout(() => alertBox.remove(), 400);
        }, 5000);
    }

});