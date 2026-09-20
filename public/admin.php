<?php
require_once __DIR__ . '/../includes/session.php';
requireAdmin();   // ← This handles ALL the role protection

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Get current admin user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// ============================================
// SYSTEM STATISTICS
// ============================================
$stats = [];

$stats['total_users'] = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$stats['total_games'] = (int)$pdo->query("SELECT COUNT(*) FROM game_sessions")->fetchColumn();
$stats['total_sessions'] = (int)$pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();
$stats['active_today'] = (int)$pdo->query(
    "SELECT COUNT(DISTINCT user_id) FROM activity_logs 
     WHERE DATE(created_at) = CURDATE()"
)->fetchColumn();

// ============================================
// FETCH ALL USERS
// ============================================
$stmt = $pdo->query(
    "SELECT id, username, email, role, level, xp, high_score, games_played, 
            last_login, created_at 
     FROM users ORDER BY created_at DESC"
);
$users = $stmt->fetchAll();

// ============================================
// RECENT ACTIVITY (50 most recent)
// ============================================
$stmt = $pdo->query(
    "SELECT a.id, a.activity, a.ip_address, a.created_at, u.username 
     FROM activity_logs a
     LEFT JOIN users u ON u.id = a.user_id
     ORDER BY a.created_at DESC LIMIT 50"
);
$activities = $stmt->fetchAll();

// ============================================
// HANDLE DELETE USER
// ============================================
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // Delete user
    if ($_POST['action'] === 'delete_user') {
        $target_id = (int)($_POST['user_id'] ?? 0);
        
        if ($target_id === (int)$_SESSION['user_id']) {
            $message = 'You cannot delete your own account.';
            $message_type = 'error';
        } elseif ($target_id > 0) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$target_id]);
            logActivity($pdo, $_SESSION['user_id'], "Deleted user ID $target_id");
            $message = 'User deleted successfully.';
            $message_type = 'success';
            // Refresh users list
            $users = $pdo->query(
                "SELECT id, username, email, role, level, xp, high_score, games_played, 
                        last_login, created_at 
                 FROM users ORDER BY created_at DESC"
            )->fetchAll();
        }
    }
    
    // Change user role
    if ($_POST['action'] === 'change_role') {
        $target_id = (int)($_POST['user_id'] ?? 0);
        $new_role = $_POST['new_role'] ?? '';
        
        $allowed_roles = ['admin', 'student'];
        
        if (!in_array($new_role, $allowed_roles)) {
            $message = 'Invalid role.';
            $message_type = 'error';
        } elseif ($target_id === (int)$_SESSION['user_id']) {
            $message = 'You cannot change your own role.';
            $message_type = 'error';
        } elseif ($target_id > 0) {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$new_role, $target_id]);
            logActivity($pdo, $_SESSION['user_id'], "Changed user $target_id role to $new_role");
            $message = 'Role updated successfully.';
            $message_type = 'success';
            // Refresh
            $users = $pdo->query(
                "SELECT id, username, email, role, level, xp, high_score, games_played, 
                        last_login, created_at 
                 FROM users ORDER BY created_at DESC"
            )->fetchAll();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel | CoreSync</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/settings.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        /* Admin-specific styles */
        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            background: linear-gradient(135deg, #d13639, #7c3aed);
            color: white;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .admin-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            padding: 6px;
            background: rgba(20, 20, 30, 0.6);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 12px;
            width: fit-content;
        }

        .admin-tab {
            padding: 10px 22px;
            background: transparent;
            border: none;
            color: rgba(255,255,255,0.6);
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .admin-tab:hover { color: white; }
        .admin-tab.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .admin-panel { display: none; }
        .admin-panel.active { display: block; }

        /* Stats cards */
        .admin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .admin-stat {
            padding: 20px;
            background: rgba(20, 20, 30, 0.6);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 14px;
        }
        .admin-stat-label {
            font-size: 10px;
            font-weight: 700;
            color: rgba(255,255,255,0.4);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 8px;
        }
        .admin-stat-value {
            font-size: 32px;
            font-weight: 800;
            color: white;
        }

        /* Users table */
        .admin-table-wrap {
            background: rgba(20, 20, 30, 0.6);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 14px;
            overflow: hidden;
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .admin-table thead {
            background: rgba(255,255,255,0.04);
        }
        .admin-table th {
            text-align: left;
            padding: 14px 20px;
            font-size: 10px;
            font-weight: 700;
            color: rgba(255,255,255,0.5);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .admin-table td {
            padding: 14px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            color: rgba(255,255,255,0.85);
        }
        .admin-table tr:last-child td { border-bottom: none; }
        .admin-table tr:hover { background: rgba(255,255,255,0.02); }

        .role-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .role-badge.admin { background: #d13639; color: white; }
        .role-badge.student { background: #2ecc71; color: #0a1a10; }

        .admin-action-btn {
            padding: 6px 12px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 6px;
            color: rgba(255,255,255,0.75);
            font-family: inherit;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }
        .admin-action-btn:hover {
            background: rgba(255,255,255,0.12);
            color: white;
        }
        .admin-action-btn.danger {
            color: #ff7c7f;
            border-color: rgba(209,54,57,0.3);
        }
        .admin-action-btn.danger:hover {
            background: rgba(209,54,57,0.15);
        }

        .role-select {
            padding: 4px 8px;
            background: #1a1a25;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 6px;
            color: white;
            font-family: inherit;
            font-size: 12px;
            cursor: pointer;
        }

        /* Activity list */
        .admin-activity {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .admin-activity-row {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 12px 20px;
            background: rgba(20, 20, 30, 0.4);
            border-left: 2px solid transparent;
            transition: all 0.2s;
        }
        .admin-activity-row:hover {
            background: rgba(20, 20, 30, 0.7);
            border-left-color: #d13639;
        }
        .admin-activity-user {
            font-weight: 700;
            color: #7c3aed;
            min-width: 120px;
        }
        .admin-activity-text {
            flex: 1;
            color: rgba(255,255,255,0.85);
        }
        .admin-activity-time {
            font-size: 11px;
            color: rgba(255,255,255,0.4);
            font-family: monospace;
        }
    </style>
</head>
<body class="profile-page">

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
            <a href="admin.php" class="nav-tab active" title="Admin Panel">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2l8 4v6c0 5.5-3.8 10.7-8 12-4.2-1.3-8-6.5-8-12V6l8-4z"/>
                </svg>
            </a>
            <a href="profile.php" class="nav-tab" title="Profile">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2"/>
                </svg>
            </a>
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

    <div class="section-head">
        <div class="section-head-left">
            <a href="dashboard.php" class="back-link-small" title="Back">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back
            </a>
            <h1>Admin Panel</h1>
            <span class="admin-badge">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2l8 4v6c0 5.5-3.8 10.7-8 12-4.2-1.3-8-6.5-8-12V6l8-4z"/>
                </svg>
                Admin Only
            </span>
        </div>
        <span class="user-greeting">Signed in as <?= htmlspecialchars($user['username']) ?></span>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type === 'error' ? 'error' : 'success' ?>" style="margin-bottom:20px;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Tabs -->
       <div class="admin-tabs">
    <button class="admin-tab active" data-panel="overview">Overview</button>
    <button class="admin-tab" data-panel="users">Users</button>
    <button class="admin-tab" data-panel="activity">Activity Logs</button>
    <a href="reports.php" class="admin-tab" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 3v18h18"/>
            <path d="M18 17V9M13 17V5M8 17v-3"/>
        </svg>
        Reports
    </a>
</div>

    <!-- ==========================================
         PANEL: OVERVIEW
         ========================================== -->
    <div class="admin-panel active" data-panel="overview">
        <div class="admin-stats">
            <div class="admin-stat">
                <div class="admin-stat-label">Total Users</div>
                <div class="admin-stat-value"><?= $stats['total_users'] ?></div>
            </div>
            <div class="admin-stat">
                <div class="admin-stat-label">Games Played</div>
                <div class="admin-stat-value"><?= $stats['total_games'] ?></div>
            </div>
            <div class="admin-stat">
                <div class="admin-stat-label">Total Activities</div>
                <div class="admin-stat-value"><?= number_format($stats['total_sessions']) ?></div>
            </div>
            <div class="admin-stat">
                <div class="admin-stat-label">Active Today</div>
                <div class="admin-stat-value"><?= $stats['active_today'] ?></div>
            </div>
        </div>
    </div>

    <!-- ==========================================
         PANEL: USERS
         ========================================== -->
    <div class="admin-panel" data-panel="users">
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Level</th>
                        <th>XP</th>
                        <th>High Score</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="change_role">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <select name="new_role" class="role-select" onchange="this.form.submit()"
                                    <?= $u['id'] == $_SESSION['user_id'] ? 'disabled' : '' ?>>
                                    <option value="student" <?= $u['role'] === 'student' ? 'selected' : '' ?>>Student</option>
                                    <option value="admin"   <?= $u['role'] === 'admin'   ? 'selected' : '' ?>>Admin</option>
                                </select>
                            </form>
                        </td>
                        <td><?= $u['level'] ?></td>
                        <td><?= number_format($u['xp']) ?></td>
                        <td><?= number_format($u['high_score']) ?></td>
                        <td><?= $u['last_login'] ? date('M j, Y', strtotime($u['last_login'])) : 'Never' ?></td>
                        <td>
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" style="display:inline;"
                                      onsubmit="return confirm('Delete <?= htmlspecialchars($u['username']) ?>? This cannot be undone.');">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="admin-action-btn danger">Delete</button>
                                </form>
                            <?php else: ?>
                                <span style="color:rgba(255,255,255,0.3);font-size:12px;">(You)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ==========================================
         PANEL: ACTIVITY LOGS
         ========================================== -->
    <div class="admin-panel" data-panel="activity">
        <div class="admin-activity">
            <?php if (empty($activities)): ?>
                <p style="padding:40px;text-align:center;color:rgba(255,255,255,0.4);">
                    No activity yet.
                </p>
            <?php else: ?>
                <?php foreach ($activities as $a): ?>
                    <div class="admin-activity-row">
                        <div class="admin-activity-user">
                            <?= htmlspecialchars($a['username'] ?? 'System') ?>
                        </div>
                        <div class="admin-activity-text">
                            <?= htmlspecialchars($a['activity']) ?>
                        </div>
                        <div class="admin-activity-time">
                            <?= date('M j, g:i A', strtotime($a['created_at'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</main>

<!-- Background layer -->
<div class="bg-layer" id="bgLayer">
    <video class="bg-video" autoplay muted loop playsinline preload="auto">
        <source src="/after-class/assets/games/bg-home.mp4" type="video/mp4">
    </video>
</div>

<?php require_once __DIR__ . '/../includes/settings_panel.php'; ?>

<script src="js/settings.js"></script>
<script>
// Tab switching
document.querySelectorAll('.admin-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.admin-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.admin-panel').forEach(p => p.classList.remove('active'));
        tab.classList.add('active');
        document.querySelector(`.admin-panel[data-panel="${tab.dataset.panel}"]`).classList.add('active');
    });
});
</script>
</body>
</html>