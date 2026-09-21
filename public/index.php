<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';


// ============================================
// IF ALREADY LOGGED IN
// ============================================

if (isset($_SESSION['user_id'])) {

    header('Location: dashboard.php');
    exit;
}


// ============================================
// MESSAGES
// ============================================

$error =
    $_SESSION['login_error'] ?? '';

$success =
    $_SESSION['login_success'] ?? '';

unset(
    $_SESSION['login_error'],
    $_SESSION['login_success']
);


// ============================================
// GET REMEMBERED USERNAME
// ============================================

$remembered_username =
    $_SESSION['remember_username'] ?? '';

unset(
    $_SESSION['remember_username']
);

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        EqualPath | Sign In
    </title>


    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <link
        rel="stylesheet"
        href="css/style.css?v=<?= time() ?>"
    >

</head>


<body>


<div class="login-wrapper">


    <div class="login-panel">


        <!-- ====================================
             BRAND
        ===================================== -->

        <div class="brand">


            <div class="brand-mark">

                <svg
                    viewBox="0 0 24 24"
                    fill="currentColor"
                >

                    <path
                        d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"
                    />

                </svg>

            </div>


            <h1>
                EqualPath
            </h1>


        </div>


        <!-- ====================================
             TABS
        ===================================== -->

        <div class="tabs">


            <button
                type="button"
                class="tab active"
                data-tab="signin"
            >

                <svg
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >

                    <rect
                        x="3"
                        y="11"
                        width="18"
                        height="11"
                        rx="2"
                    />

                    <path
                        d="M7 11V7a5 5 0 0110 0v4"
                    />

                </svg>

                Sign-in

            </button>


            <button
                type="button"
                class="tab"
                data-tab="qr"
            >

                <svg
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >

                    <rect
                        x="3"
                        y="3"
                        width="7"
                        height="7"
                    />

                    <rect
                        x="14"
                        y="3"
                        width="7"
                        height="7"
                    />

                    <rect
                        x="14"
                        y="14"
                        width="7"
                        height="7"
                    />

                    <rect
                        x="3"
                        y="14"
                        width="7"
                        height="7"
                    />

                </svg>

                QR Code

            </button>


        </div>


        <!-- ====================================
             LOGIN MESSAGES
        ===================================== -->

        <?php if ($error): ?>

            <div
                class="alert alert-error"
                id="alertBox"
            >

                <span>
                    <?= sanitize($error) ?>
                </span>

            </div>

        <?php endif; ?>


        <?php if ($success): ?>

            <div class="alert alert-success">

                <span>
                    <?= sanitize($success) ?>
                </span>

            </div>

        <?php endif; ?>


        <!-- ====================================
             NORMAL SIGN-IN
        ===================================== -->

        <div id="signinPanel">


            <form
                action="authenticate.php"
                method="POST"
                id="loginForm"
                novalidate
            >


                <!-- USERNAME -->

                <div class="form-group">


                    <label for="username">
                        Username
                    </label>


                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Enter your username"
                        autocomplete="username"
                        value="<?= htmlspecialchars(
                            $remembered_username,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        required
                        maxlength="50"
                    >


                    <span
                        class="field-error"
                        id="username-error"
                    ></span>


                </div>


                <!-- PASSWORD -->

                <div class="form-group">


                    <label for="password">
                        Password
                    </label>


                    <div class="password-wrapper">


                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                            minlength="8"
                        >


                        <button
                            type="button"
                            class="toggle-password"
                            aria-label="Show password"
                        >

                            <svg
                                width="18"
                                height="18"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >

                                <path
                                    d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"
                                />

                                <circle
                                    cx="12"
                                    cy="12"
                                    r="3"
                                />

                            </svg>

                        </button>


                    </div>


                    <!-- CAPS LOCK WARNING -->

                    <div
                        class="caps-warning"
                        id="capsWarning"
                        style="display:none;"
                    >

                        ⚠️ Caps Lock is ON

                    </div>


                    <span
                        class="field-error"
                        id="password-error"
                    ></span>


                </div>


                <!-- EMAIL 2FA -->

                <div
                    class="email-notice"
                    title="You'll receive a verification code by email."
                >


                    <svg
                        width="12"
                        height="12"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >

                        <rect
                            x="3"
                            y="11"
                            width="18"
                            height="11"
                            rx="2"
                        />

                        <path
                            d="M7 11V7a5 5 0 0110 0v4"
                        />

                    </svg>


                    <span>
                        2FA code sent by email
                    </span>


                </div>


                <!-- SOCIAL BUTTONS -->

                <div class="social-row">


                    <button
                        type="button"
                        class="social-btn fb"
                    >
                        f
                    </button>


                    <button
                        type="button"
                        class="social-btn google"
                    >
                        G
                    </button>


                    <button
                        type="button"
                        class="social-btn apple"
                    >
                        A
                    </button>


                    <button
                        type="button"
                        class="social-btn xbox"
                    >
                        X
                    </button>


                    <button
                        type="button"
                        class="social-btn ps"
                    >
                        PS
                    </button>


                </div>


                <!-- STAY SIGNED IN -->

                <div class="checkbox-row">


                    <label class="checkbox-label">


                        <input
                            type="checkbox"
                            name="stay_signed_in"
                            id="stay_signed_in"
                        >


                        <span class="checkmark"></span>


                        Stay signed in


                    </label>


                </div>


                <!-- LOGIN BUTTON -->

                <div class="submit-row">


                    <button
                        type="submit"
                        class="submit-btn"
                        id="submitBtn"
                        aria-label="Sign In"
                    >


                        <svg
                            class="arrow-icon"
                            width="24"
                            height="24"
                            viewBox="0 0 24 24"
                            fill="currentColor"
                        >

                            <path
                                d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"
                            />

                        </svg>


                        <svg
                            class="spinner"
                            width="24"
                            height="24"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                        >

                            <circle
                                cx="12"
                                cy="12"
                                r="10"
                                opacity="0.25"
                            />

                            <path
                                d="M12 2a10 10 0 019.95 9"
                                stroke-linecap="round"
                            />

                        </svg>


                    </button>


                </div>


            </form>


        </div>


        <!-- ====================================
             QR VERIFICATION
        ===================================== -->

        <div
            id="qrPanel"
            style="
                display:none;
                text-align:center;
                margin-bottom:24px;
            "
        >


            <p
                style="
                    font-size:13px;
                    color:#666;
                    margin-bottom:16px;
                    line-height:1.5;
                "
            >

                Scan this QR code with your phone.

                <br>

                Enter the 6-digit verification code
                shown on your phone.

            </p>


            <!-- QR IMAGE -->

            <div class="qr-box">


                <div id="qrCodeContainer">


                    <div class="qr-loading">

                        Generating...

                    </div>


                </div>


            </div>


            <!-- QR STATUS -->

            <div
                id="qrStatus"
                class="qr-status"
            >

                <span class="pulse-dot"></span>

                Scan QR with your phone

            </div>


            <!-- CODE INPUT -->

            <div
                style="
                    margin-top:18px;
                    display:flex;
                    justify-content:center;
                "
            >


                <input
                    type="text"
                    id="qrVerificationCode"
                    placeholder="000000"
                    maxlength="6"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    aria-label="QR verification code"

                    style="
                        width:190px;
                        padding:13px;
                        text-align:center;
                        font-size:20px;
                        font-weight:700;
                        letter-spacing:6px;
                        border:1px solid #ccc;
                        border-radius:10px;
                        outline:none;
                    "
                >


            </div>


            <!-- VERIFY CODE -->

            <button
                type="button"
                id="verifyQR"
                class="qr-refresh-btn"
                style="
                    margin-top:12px;
                "
            >

                Verify Code

            </button>


            <!-- GENERATE NEW QR -->

            <button
                type="button"
                id="refreshQR"
                class="qr-refresh-btn"
                style="
                    margin-top:8px;
                "
            >

                Generate New QR

            </button>


        </div>


        <!-- ====================================
             FOOTER
        ===================================== -->

        <div class="footer-links">


            <a href="forgot_password.php">
                Can't sign in?
            </a>


            <a href="register.php">
                Create account
            </a>


        </div>


        <div class="legal">


            <p>

                This app is protected by hCaptcha
                and its Privacy Policy and Terms of
                Service apply.

            </p>


            <span class="version">
                v1.0.0
            </span>


        </div>


    </div>


    <!-- ========================================
         RIGHT SIDE VIDEO
    ========================================= -->

    <div class="visual-panel">


        <video
            class="bg-video"
            autoplay
            muted
            loop
            playsinline
            preload="auto"
        >


            <source
                src="../assets/cyberpunk.mp4"
                type="video/mp4"
            >


        </video>


        <div class="visual-overlay"></div>


    </div>


</div>


<!-- ==========================================
     SESSION TIMER
========================================== -->

<div
    class="session-timer"
    id="sessionTimer"
    style="display:none;"
>

    ⏱️ Session expires in

    <span id="timerValue">
        30:00
    </span>

</div>


<!-- ==========================================
     LOADING
========================================== -->

<div
    class="loading-overlay"
    id="loadingOverlay"
>


    <div class="loading-content">


        <div class="loading-spinner"></div>


        <p>
            Signing in...
        </p>


    </div>


</div>


<!-- ==========================================
     JAVASCRIPT
========================================== -->

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

<script src="js/script.js?v=<?= time() ?>"></script>

<script src="js/qr-login.js?v=<?= time() ?>"></script>


</body>

</html>