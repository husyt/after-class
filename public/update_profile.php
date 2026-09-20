<?php
require_once __DIR__ . '/../includes/session.php';
require2FA();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$field = $_POST['field'] ?? '';
$value = trim($_POST['value'] ?? '');

$allowedFields = ['username' => 50, 'pronouns' => 30];

if (!isset($allowedFields[$field])) {
    $_SESSION['profile_error'] = 'Invalid field.';
    header('Location: profile.php');
    exit;
}

$maxLength = $allowedFields[$field];

if ($value === '' && $field !== 'pronouns') {
    $_SESSION['profile_error'] = 'Value cannot be empty.';
    header('Location: profile.php');
    exit;
}

if (strlen($value) > $maxLength) {
    $_SESSION['profile_error'] = "Value must be {$maxLength} characters or less.";
    header('Location: profile.php');
    exit;
}

// Validate username format
if ($field === 'username' && !preg_match('/^[a-zA-Z0-9_\s]{3,50}$/', $value)) {
    $_SESSION['profile_error'] = 'Display name must be 3-50 characters (letters, numbers, spaces, underscores).';
    header('Location: profile.php');
    exit;
}

try {
    // Check for duplicates on username
    if ($field === 'username') {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$value, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $_SESSION['profile_error'] = 'That display name is already taken.';
            header('Location: profile.php');
            exit;
        }
    }

    $stmt = $pdo->prepare("UPDATE users SET {$field} = ? WHERE id = ?");
    $stmt->execute([$value, $_SESSION['user_id']]);

    // Update session if username changed
    if ($field === 'username') {
        $_SESSION['username'] = $value;
    }

    logActivity($pdo, $_SESSION['user_id'], "Updated {$field}");
    $_SESSION['profile_success'] = ucfirst($field) . ' updated successfully.';

} catch (PDOException $e) {
    error_log("Profile Update Error: " . $e->getMessage());
    $_SESSION['profile_error'] = 'Unable to save. Please try again.';
}

header('Location: profile.php');
exit;
?>