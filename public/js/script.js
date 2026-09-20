document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const submitBtn = document.getElementById('submitBtn');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const togglePassword = document.querySelector('.toggle-password');

    if (togglePassword) {
        togglePassword.addEventListener('click', () => {
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
        });
    }

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
            if (!valid) { e.preventDefault(); return; }

            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            if (loadingOverlay) loadingOverlay.classList.add('active');
        });
    }

    const alertBox = document.getElementById('alertBox');
    if (alertBox) {
        setTimeout(() => {
            alertBox.style.transition = 'opacity 0.4s';
            alertBox.style.opacity = '0';
            setTimeout(() => alertBox.remove(), 400);
        }, 5000);
    }
});