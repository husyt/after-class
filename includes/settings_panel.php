<?php
// includes/settings_panel.php
// Self-contained — does NOT rely on $lang being computed at the top

// Ensure i18n is loaded
if (!function_exists('__')) {
    require_once __DIR__ . '/i18n.php';
}

// Ensure $current_lang is fresh
global $user, $current_lang;
if (!empty($user['preferred_language'])) {
    $current_lang = $user['preferred_language'];
} elseif (!empty($_SESSION['preferred_language'])) {
    $current_lang = $_SESSION['preferred_language'];
} else {
    $current_lang = 'en';
}
$_SESSION['preferred_language'] = $current_lang;

$user = $user ?? [];
?>
<div class="settings-overlay" id="settingsOverlay" aria-hidden="true">
    <div class="settings-backdrop" id="settingsBackdrop"></div>

    <aside class="settings-drawer" role="dialog" aria-label="Settings">
        <header class="settings-header">
            <h2><?= __("settings") ?></h2>
            <button class="settings-close" id="settingsClose" aria-label="Close">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </button>
        </header>

        <div class="settings-body">

            <!-- ACCOUNT -->
            <section class="settings-section">
                <h3><?= __("account") ?></h3>

                <div class="settings-field">
                    <label><?= __("username") ?></label>
                    <div class="settings-value"><?= htmlspecialchars($user['username'] ?? '—') ?></div>
                </div>

                <div class="settings-field">
                    <label><?= __("email") ?></label>
                    <div class="settings-value"><?= htmlspecialchars($user['email'] ?? '—') ?></div>
                </div>

                <div class="settings-field">
                    <label><?= __("role") ?></label>
                    <div class="settings-value">
                        <span class="settings-badge"><?= htmlspecialchars($user['role'] ?? 'student') ?></span>
                    </div>
                </div>

                <a href="forgot_password.php" class="settings-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2"/>
                        <path d="M7 11V7a5 5 0 0110 0v4"/>
                    </svg>
                    <?= __("change_password") ?>
                </a>
            </section>

            <!-- PREFERENCES -->
            <section class="settings-section">
                <h3><?= __("preferences") ?></h3>

                <div class="settings-toggle-row">
                    <div>
                        <div class="settings-toggle-label"><?= __("two_fa") ?></div>
                        <div class="settings-toggle-desc"><?= __("two_fa_desc") ?></div>
                    </div>
                    <label class="toggle">
                        <input type="checkbox" id="pref2FA" <?= ($user['two_factor_enabled'] ?? 0) ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="settings-toggle-row">
                    <div>
                        <div class="settings-toggle-label"><?= __("dark_mode") ?></div>
                        <div class="settings-toggle-desc"><?= __("dark_mode_desc") ?></div>
                    </div>
                    <label class="toggle">
                        <input type="checkbox" id="prefDark" checked disabled>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="settings-toggle-row">
                    <div>
                        <div class="settings-toggle-label"><?= __("reduce_motion") ?></div>
                        <div class="settings-toggle-desc"><?= __("reduce_motion_desc") ?></div>
                    </div>
                    <label class="toggle">
                        <input type="checkbox" id="prefMotion">
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="settings-toggle-row">
                    <div>
                        <div class="settings-toggle-label"><?= __("auto_play") ?></div>
                        <div class="settings-toggle-desc"><?= __("auto_play_desc") ?></div>
                    </div>
                    <label class="toggle">
                        <input type="checkbox" id="prefAutoplay" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <!-- BACKGROUND PICKER -->
                <div class="settings-field-block">
                    <label class="settings-field-block-label"><?= __("background_theme") ?></label>
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
                    <label class="settings-field-block-label"><?= __("language") ?></label>
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
                <h3><?= __("audio") ?></h3>

                <div class="settings-slider-row">
                    <label><?= __("master_volume") ?></label>
                    <input type="range" min="0" max="100" value="70" class="settings-range" id="prefVolume">
                    <span class="settings-range-value" id="volumeValue">70%</span>
                </div>

                <div class="settings-toggle-row">
                    <div>
                        <div class="settings-toggle-label"><?= __("mute_all") ?></div>
                        <div class="settings-toggle-desc"><?= __("mute_all_desc") ?></div>
                    </div>
                    <label class="toggle">
                        <input type="checkbox" id="prefMute">
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="settings-toggle-row">
                    <div>
                        <div class="settings-toggle-label"><?= __("bg_music") ?></div>
                        <div class="settings-toggle-desc"><?= __("bg_music_desc") ?></div>
                    </div>
                    <label class="toggle">
                        <input type="checkbox" id="prefMusic" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </section>

            <!-- NOTIFICATIONS -->
            <section class="settings-section">
                <h3><?= __("notifications") ?></h3>

                <div class="settings-toggle-row">
                    <div>
                        <div class="settings-toggle-label"><?= __("email_alerts") ?></div>
                        <div class="settings-toggle-desc"><?= __("email_alerts_desc") ?></div>
                    </div>
                    <label class="toggle">
                        <input type="checkbox" id="prefEmail" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="settings-toggle-row">
                    <div>
                        <div class="settings-toggle-label"><?= __("game_reminders") ?></div>
                        <div class="settings-toggle-desc"><?= __("game_reminders_desc") ?></div>
                    </div>
                    <label class="toggle">
                        <input type="checkbox" id="prefReminders">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </section>

            <!-- ABOUT -->
            <section class="settings-section">
                <h3><?= __("about") ?></h3>

                <div class="settings-field">
                    <label><?= __("version") ?></label>
                    <div class="settings-value">CoreSync v1.0.0</div>
                </div>

                <a href="logout.php" class="settings-link danger">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                        <path d="M16 17l5-5-5-5M21 12H9"/>
                    </svg>
                    <?= __("sign_out") ?>
                </a>
            </section>

        </div>
    </aside>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // ========================================
    // OPEN / CLOSE DRAWER
    // ========================================
    const overlay  = document.getElementById('settingsOverlay');
    const backdrop = document.getElementById('settingsBackdrop');
    const closeBtn = document.getElementById('settingsClose');

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
    // BACKGROUND PICKER
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
    // LANGUAGE SELECTOR
    // ========================================
    const languageSelect = document.getElementById('languageSelect');
    if (languageSelect) {
        languageSelect.addEventListener('change', async () => {
            const selected = languageSelect.value;
            console.log('🌐 Changing language to:', selected);
            try {
                const res = await fetch('update_preference.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ type: 'preferred_language', value: selected })
                });
                const result = await res.json();
                console.log('📦 Server response:', result);
                if (result.success) {
                    console.log('✅ Reloading page...');
                    location.reload();
                } else {
                    alert('Language change failed: ' + (result.error || 'Unknown'));
                }
            } catch (err) {
                console.error('❌ Error:', err);
                alert('Network error: ' + err.message);
            }
        });
    }
});
</script>