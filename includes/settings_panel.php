<?php
// includes/settings_panel.php
require_once __DIR__ . '/i18n.php';

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

// Saved notification preferences (from DB or defaults)
$email_alerts_on   = (int)($user['email_alerts']    ?? 1);
$game_reminders_on = (int)($user['game_reminders']  ?? 1);
$reduce_motion_on  = (int)($user['reduce_motion']   ?? 0);
$auto_play_on      = (int)($user['auto_play']       ?? 1);
$bg_music_on       = (int)($user['bg_music']        ?? 1);
$master_volume     = (int)($user['master_volume']   ?? 70);
?>
<div class="settings-overlay" id="settingsOverlay" aria-hidden="true">
    <div class="settings-backdrop" id="settingsBackdrop"></div>
    <aside class="settings-drawer" role="dialog">
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
                        <div class="settings-toggle-label"><?= __("reduce_motion") ?></div>
                        <div class="settings-toggle-desc"><?= __("reduce_motion_desc") ?></div>
                    </div>
                    <label class="toggle">
                        <input type="checkbox"
                               id="prefMotion"
                               data-pref="reduce_motion"
                               <?= $reduce_motion_on ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="settings-toggle-row">
                    <div>
                        <div class="settings-toggle-label"><?= __("auto_play") ?></div>
                        <div class="settings-toggle-desc"><?= __("auto_play_desc") ?></div>
                    </div>
                    <label class="toggle">
                        <input type="checkbox"
                               id="prefAutoplay"
                               data-pref="auto_play"
                               <?= $auto_play_on ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <!-- BACKGROUND PICKER -->
                <div class="settings-field-block">
                    <label class="settings-field-block-label"><?= __("background_theme") ?></label>
                    <div class="background-picker" id="backgroundPicker">
                        <?php
                        $backgrounds = [
                            'bg-home'   => 'linear-gradient(135deg, #d13639, #f97316)',
                            'bg-city'   => 'linear-gradient(135deg, #7c3aed, #ec4899)',
                            'bg-forest' => 'linear-gradient(135deg, #059669, #84cc16)',
                            'bg-space'  => 'linear-gradient(135deg, #1e3a8a, #312e81)',
                            'bg-ocean'  => 'linear-gradient(135deg, #0ea5e9, #06b6d4)',
                        ];
                        $current_bg = $user['preferred_background'] ?? 'bg-home';
                        foreach ($backgrounds as $key => $gradient): ?>
                            <button type="button"
                                    class="background-option <?= $key === $current_bg ? 'active' : '' ?>"
                                    data-bg="<?= $key ?>"
                                    style="background: <?= $gradient ?>;">
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
                    <input type="range" min="0" max="100"
                           value="<?= $master_volume ?>"
                           class="settings-range"
                           id="prefVolume"
                           data-pref="master_volume">
                    <span class="settings-range-value" id="volumeValue"><?= $master_volume ?>%</span>
                </div>

                <div class="settings-toggle-row">
                    <div>
                        <div class="settings-toggle-label"><?= __("bg_music") ?></div>
                        <div class="settings-toggle-desc"><?= __("bg_music_desc") ?></div>
                    </div>
                    <label class="toggle">
                        <input type="checkbox"
                               id="prefMusic"
                               data-pref="bg_music"
                               <?= $bg_music_on ? 'checked' : '' ?>>
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
                        <input type="checkbox"
                               id="prefEmail"
                               data-pref="email_alerts"
                               <?= $email_alerts_on ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="settings-toggle-row">
                    <div>
                        <div class="settings-toggle-label"><?= __("game_reminders") ?></div>
                        <div class="settings-toggle-desc"><?= __("game_reminders_desc") ?></div>
                    </div>
                    <label class="toggle">
                        <input type="checkbox"
                               id="prefReminders"
                               data-pref="game_reminders"
                               <?= $game_reminders_on ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <!-- Live indicator: shows when the setting actually takes effect -->
                <div class="settings-note" id="notifStatus"
                     style="margin-top:12px;font-size:12px;color:#888;line-height:1.5;">
                    Changes are saved to your account instantly.
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

    // ============================================
    // OPEN / CLOSE DRAWER
    // ============================================
    const overlay  = document.getElementById('settingsOverlay');
    const backdrop = document.getElementById('settingsBackdrop');
    const closeBtn = document.getElementById('settingsClose');

    const gearBtn = document.querySelector('button[aria-label="Settings"]')
                 || document.querySelector('button[aria-label="settings"]')
                 || document.querySelector('.nav-right .icon-btn:first-child');

    function openSettings() {
        overlay.classList.add('open');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }
    function closeSettings() {
        overlay.classList.remove('open');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    if (gearBtn) {
        const cloned = gearBtn.cloneNode(true);
        gearBtn.parentNode.replaceChild(cloned, gearBtn);
        cloned.addEventListener('click', (e) => {
            e.preventDefault(); e.stopPropagation(); openSettings();
        });
    }
    if (closeBtn) closeBtn.addEventListener('click', closeSettings);
    if (backdrop) backdrop.addEventListener('click', closeSettings);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && overlay.classList.contains('open')) closeSettings();
    });

    // ============================================
    // BACKGROUND PICKER
    // ============================================
    const backgroundPicker = document.getElementById('backgroundPicker');
    if (!document.body.dataset.bg) document.body.dataset.bg = 'bg-home';

    if (backgroundPicker) {
        backgroundPicker.querySelectorAll('.background-option').forEach(btn => {
            btn.addEventListener('click', async () => {
                const bg = btn.dataset.bg;
                document.body.dataset.bg = bg;
                backgroundPicker.querySelectorAll('.background-option').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                await savePreference('preferred_background', bg);
            });
        });
    }

    // ============================================
    // VOLUME SLIDER
    // ============================================
    const volumeSlider = document.getElementById('prefVolume');
    const volumeValue  = document.getElementById('volumeValue');
    if (volumeSlider) {
        volumeSlider.addEventListener('input', () => {
            const v = volumeSlider.value;
            if (volumeValue) volumeValue.textContent = v + '%';
            const bgMusic = document.getElementById('bgMusic');
            if (bgMusic) bgMusic.volume = v / 100;
        });
        volumeSlider.addEventListener('change', () => {
            savePreference('master_volume', volumeSlider.value);
        });
        // Apply initial volume to audio elements
        const bgMusic = document.getElementById('bgMusic');
        if (bgMusic) bgMusic.volume = volumeSlider.value / 100;
    }

    // ============================================
    // TOGGLES (reduce_motion, auto_play, bg_music, email_alerts, game_reminders)
    // ============================================
    document.querySelectorAll('.settings-toggle-row input[type="checkbox"][data-pref]').forEach(cb => {
        if (cb.disabled) return;

        cb.addEventListener('change', async () => {
            const pref = cb.dataset.pref;
            const val  = cb.checked ? 1 : 0;

            // Apply live behavior per preference
            applyLiveBehavior(pref, cb.checked);

            // Save to DB
            const result = await savePreference(pref, val);

            if (pref === 'email_alerts' || pref === 'game_reminders') {
                showNotifFeedback(pref, cb.checked, result);
            }
        });
    });

    // ============================================
    // APPLY LIVE BEHAVIOR
    // ============================================
    function applyLiveBehavior(pref, on) {
        const bgMusic = document.getElementById('bgMusic');

        switch (pref) {
            case 'reduce_motion':
                // Disable all animations & transitions on the page
                document.documentElement.style.setProperty('--animation-duration', on ? '0s' : '');
                document.body.classList.toggle('reduce-motion', on);
                break;

            case 'auto_play':
                // Pause / play background videos
                document.querySelectorAll('video.bg-video, video[autoplay]').forEach(v => {
                    if (on) {
                        v.play().catch(() => {});
                    } else {
                        v.pause();
                    }
                });
                break;

            case 'bg_music':
                if (bgMusic) {
                    if (on) {
                        bgMusic.play().catch(() => {});
                    } else {
                        bgMusic.pause();
                    }
                }
                break;

            case 'email_alerts':
            case 'game_reminders':
                // No immediate visual effect — only affects future emails
                break;
        }
    }

    // ============================================
    // NOTIFICATION FEEDBACK
    // ============================================
    function showNotifFeedback(pref, on, result) {
        const el = document.getElementById('notifStatus');
        if (!el) return;

        const labels = {
            email_alerts:   'Email alerts',
            game_reminders: 'Game reminders'
        };

        if (result && result.success) {
            el.style.color = '#2ecc71';
            el.textContent = `${labels[pref]} ${on ? 'ENABLED' : 'DISABLED'} — saved to your account.`;
        } else {
            el.style.color = '#d13639';
            el.textContent = `Could not save ${labels[pref]}. Please try again.`;
        }

        // Reset back to neutral after a few seconds
        setTimeout(() => {
            el.style.color = '#888';
            el.textContent = 'Changes are saved to your account instantly.';
        }, 3000);
    }

    // ============================================
    // SAVE PREFERENCE TO DB
    // ============================================
    async function savePreference(type, value) {
        try {
            const res = await fetch('update_preference.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ type: type, value: value })
            });
            return await res.json();
        } catch (err) {
            console.error('Save preference error:', err);
            return { success: false };
        }
    }

    // ============================================
    // LANGUAGE SELECTOR
    // ============================================
    const languageSelect = document.getElementById('languageSelect');
    if (languageSelect) {
        languageSelect.addEventListener('change', async () => {
            const result = await savePreference('preferred_language', languageSelect.value);
            if (result.success) location.reload();
        });
    }

    console.log('✓ Settings drawer ready');
});
</script>