<?php
// includes/settings_panel.php
// Uses i18n system with fallbacks

if (!function_exists('__')) {
    require_once __DIR__ . '/i18n.php';
}

$user = $user ?? [];

// Current language shortcuts with safe fallbacks
$lang = [
    'settings'           => __('settings', 'Settings'),
    'account'            => __('account', 'Account'),
    'username'           => __('username', 'Username'),
    'email'              => __('email', 'Email'),
    'role'               => __('role', 'Role'),
    'change_password'    => __('change_password', 'Change password'),
    'preferences'        => __('preferences', 'Preferences'),
    'two_fa'             => __('two_fa', 'Two-Factor Auth'),
    'two_fa_desc'        => __('two_fa_desc', 'Email OTP on login'),
    'dark_mode'          => __('dark_mode', 'Dark mode'),
    'dark_mode_desc'     => __('dark_mode_desc', 'Always on by default'),
    'reduce_motion'      => __('reduce_motion', 'Reduce motion'),
    'reduce_motion_desc' => __('reduce_motion_desc', 'Disable animations'),
    'auto_play'          => __('auto_play', 'Auto-play videos'),
    'auto_play_desc'     => __('auto_play_desc', 'Game backgrounds'),
    'background_theme'   => __('background_theme', 'Background Theme'),
    'language'           => __('language', 'Language'),
    'audio'              => __('audio', 'Audio'),
    'master_volume'      => __('master_volume', 'Master volume'),
    'mute_all'           => __('mute_all', 'Mute all sound'),
    'mute_all_desc'      => __('mute_all_desc', 'Silence everything'),
    'bg_music'           => __('bg_music', 'Background music'),
    'bg_music_desc'      => __('bg_music_desc', 'Play theme music'),
    'notifications'      => __('notifications', 'Notifications'),
    'email_alerts'       => __('email_alerts', 'Email alerts'),
    'email_alerts_desc'  => __('email_alerts_desc', 'Login and security alerts'),
    'game_reminders'     => __('game_reminders', 'Game reminders'),
    'game_reminders_desc'=> __('game_reminders_desc', 'Daily play reminders'),
    'about'              => __('about', 'About'),
    'version'            => __('version', 'Version'),
    'sign_out'           => __('sign_out', 'Sign out'),
];
?>
<div class="settings-overlay" id="settingsOverlay" aria-hidden="true">
    <div class="settings-backdrop" id="settingsBackdrop"></div>

    <aside class="settings-drawer" role="dialog" aria-label="Settings">
        <header class="settings-header">
            <h2><?= $lang['settings'] ?></h2>
            <button class="settings-close" id="settingsClose" aria-label="Close">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </button>
        </header>

        <div class="settings-body">

            <!-- ACCOUNT -->
            <section class="settings-section">
                <h3><?= $lang['account'] ?></h3>

                <div class="settings-field">
                    <label><?= $lang['username'] ?></label>
                    <div class="settings-value"><?= htmlspecialchars($user['username'] ?? '—') ?></div>
                </div>

                <div class="settings-field">
                    <label><?= $lang['email'] ?></label>
                    <div class="settings-value"><?= htmlspecialchars($user['email'] ?? '—') ?></div>
                </div>

                <div class="settings-field">
                    <label><?= $lang['role'] ?></label>
                    <div class="settings-value">
                        <span class="settings-badge"><?= htmlspecialchars($user['role'] ?? 'student') ?></span>
                    </div>
                </div>

                <a href="forgot_password.php" class="settings-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2"/>
                        <path d="M7 11V7a5 5 0 0110 0v4"/>
                    </svg>
                    <?= $lang['change_password'] ?>
                </a>
            </section>

            <!-- PREFERENCES -->
            <section class="settings-section">
                <h3><?= $lang['preferences'] ?></h3>

                <div class="settings-toggle-row">
                    <div>
                        <div class="settings-toggle-label"><?= $lang['two_fa'] ?></div>
                        <div class="settings-toggle-desc"><?= $lang['two_fa_desc'] ?></div>
                    </div>
                    <label class="toggle">
                        <input type="checkbox" id="pref2FA" <?= ($user['two_factor_enabled'] ?? 0) ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="settings-toggle-row">
                    <div>
                        <div class="settings-toggle-label"><?= $lang['dark_mode'] ?></div>
                        <div class="settings-toggle-desc"><?= $lang['dark_mode_desc'] ?></div>
                    </div>
                    <label class="toggle">
                        <input type="checkbox" id="prefDark" checked disabled>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="settings-toggle-row">
                    <div>
                        <div class="settings-toggle-label"><?= $lang['reduce_motion'] ?></div>
                        <div class="settings-toggle-desc"><?= $lang['reduce_motion_desc'] ?></div>
                    </div>
                    <label class="toggle">
                        <input type="checkbox" id="prefMotion">
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="settings-toggle-row">
                    <div>
                        <div class="settings-toggle-label"><?= $lang['auto_play'] ?></div>
                        <div class="settings-toggle-desc"><?= $lang['auto_play_desc'] ?></div>
                    </div>
                    <label class="toggle">
                        <input type="checkbox" id="prefAutoplay" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <!-- BACKGROUND PICKER -->
                <div class="settings-field-block">
                    <label class="settings-field-block-label"><?= $lang['background_theme'] ?></label>
                    <div class="background-picker" id="backgroundPicker">
                        <?php
                        $backgrounds = [
                            'bg-home'   => ['name' => 'Home',   'gradient' => 'linear-gradient(135deg, #d13639, #f97316)'],
                            'bg-city'   => ['name' => 'City',   'gradient' => 'linear-gradient(135deg, #7c3aed, #ec4899)'],
                            'bg-forest' => ['name' => 'Forest', 'gradient' => 'linear-gradient(135deg, #059669, #84cc16)'],
                            'bg-space'  => ['name' => 'Space',  'gradient' => 'linear-gradient(135deg, #1e3a8a, #312e81)'],
                            'bg-ocean'  => ['name' => 'Ocean',  'gradient' => 'linear-gradient(135deg, #0ea5e9, #06b6d4)'],
                        ];
                        $current_bg = $user['preferred_background'] ?? 'bg-home';
                        foreach ($backgrounds as $key => $bg): ?>
                            <button type="button"
                                    class="background-option <?= $key === $current_bg ? 'active' : '' ?>"
                                    data-bg="<?= $key ?>"
                                    title="<?= $bg['name'] ?>"
                                    style="background: <?= $bg['gradient'] ?>;">
                                <span class="background-check">✓</span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- LANGUAGE PICKER -->
                <div class="settings-field-block">
                    <label class="settings-field-block-label"><?= $lang['language'] ?></label>
                    <select class="settings-select" id="languageSelect">
                        <?php
                        $languages = [
                            'en' => '🇬🇧 English',
                            'tl' => '🇵🇭 Filipino',
                            'es' => '🇪🇸 Español',
                            'ja' => '🇯🇵 日本語',
                            'ko' => '🇰🇷 한국어',
                        ];
                        $current_lang_code = $user['preferred_language'] ?? 'en';
                        foreach ($languages as $code => $name): ?>
                            <option value="<?= $code ?>" <?= $code === $current_lang_code ? 'selected' : '' ?>>
                                <?= $name ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </section>

            <!-- AUDIO -->
            <section class="settings-section">
                <h3><?= $lang['audio'] ?></h3>

                <div class="settings-slider-row">
                    <label><?= $lang['master_volume'] ?></label>
                    <input type="range" min="0" max="100" value="70" class="settings-range" id="prefVolume">
                    <span class="settings-range-value" id="volumeValue">70%</span>
                </div>

                <div class="settings-toggle-row">
                    <div>
                        <div class="settings-toggle-label"><?= $lang['mute_all'] ?></div>
                        <div class="settings-toggle-desc"><?= $lang['mute_all_desc'] ?></div>
                    </div>
                    <label class="toggle">
                        <input type="checkbox" id="prefMute">
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="settings-toggle-row">
                    <div>
                        <div class="settings-toggle-label"><?= $lang['bg_music'] ?></div>
                        <div class="settings-toggle-desc"><?= $lang['bg_music_desc'] ?></div>
                    </div>
                    <label class="toggle">
                        <input type="checkbox" id="prefMusic" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </section>

            <!-- NOTIFICATIONS -->
            <section class="settings-section">
                <h3><?= $lang['notifications'] ?></h3>

                <div class="settings-toggle-row">
                    <div>
                        <div class="settings-toggle-label"><?= $lang['email_alerts'] ?></div>
                        <div class="settings-toggle-desc"><?= $lang['email_alerts_desc'] ?></div>
                    </div>
                    <label class="toggle">
                        <input type="checkbox" id="prefEmail" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="settings-toggle-row">
                    <div>
                        <div class="settings-toggle-label"><?= $lang['game_reminders'] ?></div>
                        <div class="settings-toggle-desc"><?= $lang['game_reminders_desc'] ?></div>
                    </div>
                    <label class="toggle">
                        <input type="checkbox" id="prefReminders">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </section>

            <!-- ABOUT -->
            <section class="settings-section">
                <h3><?= $lang['about'] ?></h3>

                <div class="settings-field">
                    <label><?= $lang['version'] ?></label>
                    <div class="settings-value">CoreSync v1.0.0</div>
                </div>

                <a href="logout.php" class="settings-link danger">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                        <path d="M16 17l5-5-5-5M21 12H9"/>
                    </svg>
                    <?= $lang['sign_out'] ?>
                </a>
            </section>

        </div>
    </aside>
</div>

<script>
// ============================================
// SETTINGS DRAWER — Open/Close + Preferences
// ============================================
document.addEventListener('DOMContentLoaded', () => {

    // ========================================
    // 1. OPEN / CLOSE DRAWER
    // ========================================
    const overlay  = document.getElementById('settingsOverlay');
    const backdrop = document.getElementById('settingsBackdrop');
    const closeBtn = document.getElementById('settingsClose');

    // Find the gear button in the top nav
    const gearBtn = document.querySelector('button[aria-label="Settings"]')
                 || document.querySelector('button[aria-label="settings"]')
                 || document.querySelector('.nav-right .icon-btn:first-child');

    function openSettings() {
        if (!overlay) return;
        overlay.classList.add('open');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeSettings() {
        if (!overlay) return;
        overlay.classList.remove('open');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    // Clone the gear button to strip any conflicting listeners
    if (gearBtn) {
        const cloned = gearBtn.cloneNode(true);
        gearBtn.parentNode.replaceChild(cloned, gearBtn);
        cloned.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            openSettings();
        });
    }

    if (closeBtn) closeBtn.addEventListener('click', closeSettings);
    if (backdrop) backdrop.addEventListener('click', closeSettings);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && overlay && overlay.classList.contains('open')) {
            closeSettings();
        }
    });

    // ========================================
    // 2. BACKGROUND PICKER
    // ========================================
    const backgroundPicker = document.getElementById('backgroundPicker');
    if (!document.body.dataset.bg) document.body.dataset.bg = 'bg-home';

    if (backgroundPicker) {
        backgroundPicker.querySelectorAll('.background-option').forEach(btn => {
            btn.addEventListener('click', async () => {
                const bg = btn.dataset.bg;
                document.body.dataset.bg = bg;
                backgroundPicker.querySelectorAll('.background-option').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                try {
                    await fetch('update_preference.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ type: 'preferred_background', value: bg })
                    });
                } catch (err) {
                    console.error('Background save error:', err);
                }
            });
        });
    }

    // ========================================
    // 3. LANGUAGE SELECTOR
    // ========================================
    const languageSelect = document.getElementById('languageSelect');
    if (languageSelect) {
        languageSelect.addEventListener('change', async () => {
            try {
                const res = await fetch('update_preference.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ type: 'preferred_language', value: languageSelect.value })
                });
                const result = await res.json();
                if (result.success) location.reload();
            } catch (err) {
                console.error('Language update error:', err);
            }
        });
    }

    console.log('✓ Settings drawer script loaded');
});
</script>