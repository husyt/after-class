<?php
// includes/settings_panel.php
// Settings drawer shared across dashboard, library, profile
?>
<div class="settings-overlay" id="settingsOverlay" aria-hidden="true">
    <div class="settings-backdrop" id="settingsBackdrop"></div>

    <aside class="settings-drawer" role="dialog" aria-label="Settings">
        <header class="settings-header">
            <h2>Settings</h2>
            <button class="settings-close" id="settingsClose" aria-label="Close">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </button>
        </header>

        <div class="settings-body">

            <!-- ==============================
                 ACCOUNT
                 ============================== -->
            <section class="settings-section">
                <h3>Account</h3>

                <div class="settings-field">
                    <label>Username</label>
                    <div class="settings-value"><?= htmlspecialchars($user['username'] ?? '—') ?></div>
                </div>

                <div class="settings-field">
                    <label>Email</label>
                    <div class="settings-value"><?= htmlspecialchars($user['email'] ?? '—') ?></div>
                </div>

                <div class="settings-field">
                    <label>Role</label>
                    <div class="settings-value">
                        <span class="settings-badge"><?= htmlspecialchars($user['role'] ?? 'user') ?></span>
                    </div>
                </div>

                <a href="forgot_password.php" class="settings-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2"/>
                        <path d="M7 11V7a5 5 0 0110 0v4"/>
                    </svg>
                    Change password
                </a>
            </section>

            <!-- ==============================
                 PREFERENCES
                 ============================== -->
            <section class="settings-section">
                <h3>Preferences</h3>

                <div class="settings-toggle-row">
                    <div>
                        <div class="settings-toggle-label">Dark mode</div>
                        <div class="settings-toggle-desc">Always on by default</div>
                    </div>
                    <label class="toggle">
                        <input type="checkbox" id="prefDark" checked disabled>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="settings-toggle-row">
                    <div>
                        <div class="settings-toggle-label">Reduce motion</div>
                        <div class="settings-toggle-desc">Disable animations</div>
                    </div>
                    <label class="toggle">
                        <input type="checkbox" id="prefMotion">
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="settings-toggle-row">
                    <div>
                        <div class="settings-toggle-label">Auto-play videos</div>
                        <div class="settings-toggle-desc">Game backgrounds</div>
                    </div>
                    <label class="toggle">
                        <input type="checkbox" id="prefAutoplay" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </section>

            <!-- ==============================
                 AUDIO
                 ============================== -->
            <section class="settings-section">
                <h3>Audio</h3>

                <div class="settings-slider-row">
                    <label>Master volume</label>
                    <input type="range" min="0" max="100" value="70" class="settings-range" id="prefVolume">
                    <span class="settings-range-value" id="volumeValue">70%</span>
                </div>

                <div class="settings-toggle-row">
                    <div>
                        <div class="settings-toggle-label">Mute all sound</div>
                        <div class="settings-toggle-desc">Silence everything</div>
                    </div>
                    <label class="toggle">
                        <input type="checkbox" id="prefMute">
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="settings-toggle-row">
                    <div>
                        <div class="settings-toggle-label">Background music</div>
                        <div class="settings-toggle-desc">Play theme music</div>
                    </div>
                    <label class="toggle">
                        <input type="checkbox" id="prefMusic" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </section>

            <!-- ==============================
                 NOTIFICATIONS
                 ============================== -->
            <section class="settings-section">
                <h3>Notifications</h3>

                <div class="settings-toggle-row">
                    <div>
                        <div class="settings-toggle-label">Email alerts</div>
                        <div class="settings-toggle-desc">Login and security alerts</div>
                    </div>
                    <label class="toggle">
                        <input type="checkbox" id="prefEmail" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="settings-toggle-row">
                    <div>
                        <div class="settings-toggle-label">Game reminders</div>
                        <div class="settings-toggle-desc">Daily play reminders</div>
                    </div>
                    <label class="toggle">
                        <input type="checkbox" id="prefReminders">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </section>

            <!-- ==============================
                 ABOUT
                 ============================== -->
            <section class="settings-section">
                <h3>About</h3>

                <div class="settings-field">
                    <label>Version</label>
                    <div class="settings-value">EqualPath v1.0.0</div>
                </div>

                <a href="logout.php" class="settings-link danger">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                        <path d="M16 17l5-5-5-5M21 12H9"/>
                    </svg>
                    Sign out
                </a>
            </section>

        </div>
    </aside>
</div>