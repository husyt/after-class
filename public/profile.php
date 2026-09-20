<?php
require_once __DIR__ . '/../includes/session.php';
require2FA();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Flash messages
$profile_error = $_SESSION['profile_error'] ?? '';
$profile_success = $_SESSION['profile_success'] ?? '';
unset($_SESSION['profile_error'], $_SESSION['profile_success']);

// Recent activity
$stmt = $pdo->prepare(
    "SELECT activity, created_at FROM activity_logs 
     WHERE user_id = ? ORDER BY created_at DESC LIMIT 5"
);
$stmt->execute([$_SESSION['user_id']]);
$activities = $stmt->fetchAll();

// Stats
$level   = (int)($user['level'] ?? 1);
$joined  = $user['created_at'] ? date('M Y', strtotime($user['created_at'])) : '—';
$lastlog = $user['last_login'] ? date('M j, Y · g:i A', strtotime($user['last_login'])) : 'Never';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | CoreSync</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/settings.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
<!-- ============ TOP NAV ============ -->
<header class="topnav">
    <div class="nav-left">
        <div class="logo-mark">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
            </svg>
        </div>
        <nav class="nav-tabs">
            <a href="dashboard.php" class="nav-tab" title="Home">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 12l9-9 9 9M5 10v10h14V10"/>
                </svg>
            </a>
            <a href="library.php" class="nav-tab" title="Library">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"/>
                    <rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/>
                    <rect x="3" y="14" width="7" height="7"/>
                </svg>
            </a>
            <a href="profile.php" class="nav-tab active" title="Profile">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/>
                </svg>
            </a>
            <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
<a href="admin.php" class="nav-tab" title="Admin">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 2l8 4v6c0 5.5-3.8 10.7-8 12-4.2-1.3-8-6.5-8-12V6l8-4z"/>
    </svg>
</a>
<?php endif; ?>
        </nav>
    </div>

    <div class="nav-right">
        <button class="icon-btn" aria-label="Settings">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 01-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
            </svg>
        </button>
        <div class="user-avatar">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                <circle cx="12" cy="8" r="4"/>
                <path d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/>
            </svg>
        </div>
        <a href="logout.php" class="icon-btn" aria-label="Sign out">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                <path d="M16 17l5-5-5-5M21 12H9"/>
            </svg>
        </a>
    </div>
</header>

<!-- ============ MAIN ============ -->
<main class="dashboard">

    <!-- Section head with Back button -->
    <div class="section-head">
        <div class="section-head-left">
            <a href="dashboard.php" class="back-link-small" title="Back to Home">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back
            </a>
            <h1>Profile</h1>
        </div>
        <span class="user-greeting">Member since <?= $joined ?></span>
    </div>

    <?php if ($profile_error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($profile_error) ?></div>
    <?php endif; ?>
    <?php if ($profile_success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($profile_success) ?></div>
    <?php endif; ?>

    <!-- Profile header -->
    <div class="profile-header">
        <div class="profile-avatar">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <circle cx="12" cy="8" r="4"/>
                <path d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/>
            </svg>
        </div>
        <div class="profile-meta">
            <h2><?= htmlspecialchars($user['username']) ?></h2>
            <div class="profile-tags">
                <span class="tag role"><?= htmlspecialchars($user['role']) ?></span>
                <span class="tag level">Level <?= $level ?></span>
            </div>
            <p class="profile-last-seen">Last login: <?= $lastlog ?></p>
        </div>
    </div>

    <!-- Display Name & Pronouns -->
    <div class="profile-info-cards">

        <div class="info-card">
            <div class="info-card-left">
                <div class="info-card-label">Display Name</div>
                <div class="info-card-value"><?= htmlspecialchars($user['username'] ?? '—') ?></div>
            </div>
            <button class="info-card-edit" type="button" data-field="username">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                Edit
            </button>
        </div>

        <div class="info-card">
            <div class="info-card-left">
                <div class="info-card-label">Pronouns</div>
                <div class="info-card-value <?= empty($user['pronouns']) ? 'empty-value' : '' ?>">
                    <?= htmlspecialchars($user['pronouns'] ?? 'Not set') ?>
                </div>
            </div>
            <button class="info-card-edit" type="button" data-field="pronouns">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                Edit
            </button>
        </div>

    </div>

    <!-- Account Details -->
    <div class="profile-section">
        <h3>Account Details</h3>
        <div class="account-details-grid">
            <div class="account-detail">
                <div class="account-detail-label">Username</div>
                <div class="account-detail-value"><?= htmlspecialchars($user['username'] ?? '—') ?></div>
            </div>
            <div class="account-detail">
                <div class="account-detail-label">Email</div>
                <div class="account-detail-value"><?= htmlspecialchars($user['email'] ?? '—') ?></div>
            </div>
            <div class="account-detail">
                <div class="account-detail-label">Role</div>
                <div class="account-detail-value">
                    <span class="tag role"><?= htmlspecialchars($user['role'] ?? 'user') ?></span>
                </div>
            </div>
            <div class="account-detail">
                <div class="account-detail-label">Member Since</div>
                <div class="account-detail-value"><?= $joined ?></div>
            </div>
            <div class="account-detail">
                <div class="account-detail-label">Last Login</div>
                <div class="account-detail-value"><?= $lastlog ?></div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="profile-section">
        <h3>Recent Activity</h3>
        <?php if (empty($activities)): ?>
            <p class="empty-text">No activity yet. Play a game to get started!</p>
        <?php else: ?>
            <ul class="activity-list">
                <?php foreach ($activities as $a): ?>
                    <li>
                        <span class="activity-dot"></span>
                        <span class="activity-text"><?= htmlspecialchars($a['activity']) ?></span>
                        <span class="activity-time"><?= date('M j, g:i A', strtotime($a['created_at'])) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

</main>

<!-- ============ EDIT PROFILE MODAL ============ -->
<div class="edit-modal" id="editModal" aria-hidden="true">
    <div class="edit-modal-backdrop" id="editBackdrop"></div>
    <div class="edit-modal-panel" role="dialog">
        <button class="edit-modal-close" id="editClose" aria-label="Close">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 6L6 18M6 6l12 12"/>
            </svg>
        </button>

        <h2 id="editTitle">Edit</h2>
        <p class="edit-modal-sub" id="editSub"></p>

        <form id="editForm" method="POST" action="update_profile.php">
            <input type="hidden" name="field" id="editField" value="">

            <div class="form-group">
                <label id="editLabel" for="editInput">Value</label>
                <input type="text" id="editInput" name="value" required autocomplete="off">
                <span class="field-error" id="editError"></span>
            </div>

            <div class="edit-modal-actions">
                <button type="button" class="action-btn secondary" id="editCancel">Cancel</button>
                <button type="submit" class="action-btn primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Background layer -->
<div class="bg-layer" id="bgLayer">
    <!-- Background music -->
<audio id="bgMusic" loop preload="auto">
    <source src="/after-class/assets/audio/theme.mp3" type="audio/mpeg">
</audio>
    <video class="bg-video" autoplay muted loop playsinline preload="auto">
        <source src="/after-class/assets/games/bg-home.mp4" type="video/mp4">
    </video>
</div>

<?php require_once __DIR__ . '/../includes/settings_panel.php'; ?>

<script src="js/settings.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('editModal');
    const backdrop = document.getElementById('editBackdrop');
    const closeBtn = document.getElementById('editClose');
    const cancelBtn = document.getElementById('editCancel');
    const form = document.getElementById('editForm');
    const title = document.getElementById('editTitle');
    const sub = document.getElementById('editSub');
    const fieldInput = document.getElementById('editField');
    const inputField = document.getElementById('editInput');
    const inputLabel = document.getElementById('editLabel');
    const errorEl = document.getElementById('editError');

    const userData = {
        username: '<?= htmlspecialchars($user['username'] ?? '', ENT_QUOTES) ?>',
        pronouns: '<?= htmlspecialchars($user['pronouns'] ?? '', ENT_QUOTES) ?>',
    };

    function openEdit(field) {
        fieldInput.value = field;

        if (field === 'username') {
            title.textContent = 'Edit Display Name';
            sub.textContent = 'This is how your name appears throughout CoreSync.';
            inputLabel.textContent = 'Display Name';
            inputField.value = userData.username;
            inputField.placeholder = 'Enter display name';
            inputField.maxLength = 50;
        } else if (field === 'pronouns') {
            title.textContent = 'Edit Pronouns';
            sub.textContent = 'Let others know how to refer to you.';
            inputLabel.textContent = 'Pronouns';
            inputField.value = userData.pronouns;
            inputField.placeholder = 'e.g. he/him, she/her, they/them';
            inputField.maxLength = 30;
        }

        errorEl.textContent = '';
        inputField.classList.remove('error');
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        setTimeout(() => inputField.focus(), 100);
    }

    function closeModal() {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    // Wire up the Edit buttons using data-field
    document.querySelectorAll('.info-card-edit').forEach(btn => {
        btn.addEventListener('click', () => {
            openEdit(btn.dataset.field);
        });
    });

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    if (backdrop) backdrop.addEventListener('click', closeModal);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('open')) closeModal();
    });

    // Client-side validation
    if (form) {
        form.addEventListener('submit', (e) => {
            const val = inputField.value.trim();
            const field = fieldInput.value;

            if (field === 'username') {
                if (val.length < 3) {
                    e.preventDefault();
                    errorEl.textContent = 'Display name must be at least 3 characters.';
                    inputField.classList.add('error');
                    return;
                }
                if (!/^[a-zA-Z0-9_\s]+$/.test(val)) {
                    e.preventDefault();
                    errorEl.textContent = 'Only letters, numbers, spaces, and underscores.';
                    inputField.classList.add('error');
                    return;
                }
            } else if (field === 'pronouns') {
                if (val.length > 30) {
                    e.preventDefault();
                    errorEl.textContent = 'Pronouns must be 30 characters or less.';
                    inputField.classList.add('error');
                    return;
                }
            }
        });
    }
});
</script>
</body>
</html>