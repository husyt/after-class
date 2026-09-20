# CoreSync — Challenges & Solutions

This document records the technical challenges encountered during development and how they were solved. Each entry includes the problem, the investigation, and the final solution.

---

## Challenge 1: Godot Game Won't Send Score to PHP

### Problem
The Godot game exported to Web ran correctly inside the iframe on `play.php`, but when the game ended, the score never reached the database.

### Investigation
The Godot game runs in an isolated iframe. Direct PHP calls from inside the iframe don't work because:
1. The iframe cannot make server-side requests on its own
2. Session cookies from the parent page are not automatically shared with the iframe

### Solution
Used the **`postMessage` API** to enable cross-frame communication:
1. Godot uses `JavaScriptBridge.eval()` to call `window.parent.postMessage({type: 'gameOver', score: ...}, '*')`
2. The parent page (`play.php`) listens for the message
3. On receiving the message, the parent page `fetch`es `save_score.php` with the score data

This keeps session cookies in the parent page context and lets PHP validate the score.

### Code Reference
- `assets/game/index.html` — Godot export
- `public/play.php` — iframe wrapper with message listener
- `public/save_score.php` — score save endpoint

---

## Challenge 2: Password Hash Was Truncated in Database

### Problem
After importing the initial `database.sql`, login always failed with "Invalid username or password" even though the password was correct.

### Investigation
Ran a debug query:
```sql
SELECT username, LENGTH(password_hash) FROM users;
```
Result: `43` characters — but bcrypt hashes should always be **60 characters**.

The hash was being **truncated** during import because the `password_hash` column was defined as `VARCHAR(45)` instead of `VARCHAR(255)`.

### Solution
1. Changed the column type:
   ```sql
   ALTER TABLE users MODIFY password_hash VARCHAR(255) NOT NULL;
   ```
2. Regenerated real bcrypt hashes using PHP's `password_hash()`:
   ```php
   echo password_hash('Password123!', PASSWORD_DEFAULT);
   ```
3. Updated the SQL seed to use the full 60-character hashes

### Lesson Learned
Always size password hash columns generously (255 chars). bcrypt hashes have a fixed length (60 chars) but future algorithms may need more.

---

## Challenge 3: Video Background Black Screen on Login Page

### Problem
The video background on the login page showed a black screen instead of the video, even though visiting the video URL directly played it fine.

### Investigation
- Tested the direct URL: `http://localhost/after-class/assets/games/bg-home.mp4` → played ✅
- Tested on the login page → black screen ❌

The issue was a **relative path problem**. The `<source>` tag used:
```html
<source src="assets/games/bg-home.mp4" type="video/mp4">
```
Since `index.php` lives in `/public/`, the browser looked for the file at `/public/assets/games/bg-home.mp4` — which doesn't exist.

### Solution
Changed the path to an absolute server path:
```html
<source src="/after-class/assets/games/bg-home.mp4" type="video/mp4">
```

Now the browser always resolves from the server root, regardless of which folder the PHP file is in.

### Lesson Learned
Always use **absolute paths** (`/folder/file.ext`) for assets, never relative paths. Relative paths break when files move or are included from different directories.

---

## Challenge 4: MySQL Won't Start in XAMPP

### Problem
XAMPP showed an error: `Error: MySQL shutdown unexpectedly`.

### Investigation
Checked the XAMPP log files — MySQL was failing because port `3306` was already in use by another MySQL installation (from a previous XAMPP install or a Windows service).

### Solution
1. Opened XAMPP Control Panel
2. Clicked **Config** next to MySQL → **my.ini**
3. Changed the port from `3306` to `3307`:
   ```ini
   [mysqld]
   port=3307
   ```
4. Updated `config/database.php` to use the new port:
   ```php
   $pdo = new PDO("mysql:host=localhost;port=3307;dbname=after_class_db", ...);
   ```
5. Restarted MySQL — started successfully

### Lesson Learned
If port conflicts occur, change to a non-standard port (3307, 3308) and update all connection strings accordingly.

---

## Challenge 5: QR Login Expires Too Fast / Can't Reach Phone

### Problem
1. QR code expired in 120 seconds — too short to scan and approve
2. QR code contained `localhost` — phone couldn't reach it

### Investigation
- The QR code linked to `http://localhost/after-class/public/qr_scan.php` — but `localhost` on the phone means the phone itself, not the PC
- The default expiration was hardcoded too short

### Solution
**Part 1: Extend expiration**
```php
// In qr_generate.php
$expires = date('Y-m-d H:i:s', time() + 300); // 5 minutes
```

**Part 2: Use the PC's LAN IP instead of localhost**
```php
// In qr_generate.php
function getLocalIP() {
    $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    socket_connect($sock, '8.8.8.8', 53);
    socket_getsockname($sock, $local_ip);
    socket_close($sock);
    return $local_ip;
}
$base_url = "http://" . getLocalIP() . dirname($_SERVER['PHP_SELF']);
```

**Part 3: Ensure Apache listens on all interfaces**
In `httpd.conf`, changed `Require local` to `Require all granted` for the project folder.

**Part 4: Open Windows Firewall**
Added an inbound rule allowing TCP ports 80 and 443.

### Lesson Learned
QR codes meant for phones must use the server's **LAN IP**, not `localhost`. Also need to consider firewall rules when letting external devices reach a local server.

---

## Challenge 6: PHPMailer Won't Authenticate with Gmail

### Problem
PHPMailer failed with:
```
SMTP Error: Could not authenticate
```

### Investigation
Gmail requires an **App Password** when 2-Factor Authentication is enabled. The regular Gmail password no longer works for SMTP.

### Solution
1. Enabled 2-Step Verification on the Google account
2. Generated an App Password at https://myaccount.google.com/apppasswords
3. Copied the 16-character password (removed spaces)
4. Used it in `config/mail_config.php`:
   ```php
   'password' => 'abcdefghijklmnop', // 16 chars, no spaces
   ```

### Lesson Learned
Never hardcode real credentials in files that might be committed. Add `config/mail_config.php` to `.gitignore` and provide a `mail_config.example.php` template instead.

---

## Challenge 7: CSS Scrollbar Styles Not Applying

### Problem
Custom `::-webkit-scrollbar` styles weren't visible on the profile page.

### Investigation
1. The `.dashboard` container had `overflow: hidden` — prevented scrolling, so no scrollbar appeared
2. The `<body>` also had `overflow: hidden` — the page couldn't scroll at all

### Solution
Changed:
```css
/* Before */
html, body { overflow: hidden; }

/* After */
html { overflow-y: scroll; }     /* Force scrollbar visible */
body { overflow-x: hidden; }      /* Allow vertical scroll */

.dashboard {
    min-height: calc(100vh - 90px);  /* Was: height */
    overflow: visible;                /* Was: hidden */
}
```

### Lesson Learned
`overflow: hidden` disables scrollbars entirely. When styling scrollbars, make sure the parent container actually allows scrolling.

---

## Challenge 8: Session Lost Between Pages

### Problem
After logging in, the user would get logged out when navigating from `dashboard.php` to `library.php`.

### Investigation
1. Checked `session_start()` was being called on every page → ✅
2. Checked the session cookie in DevTools → the cookie value was different on each page

The issue was **`session_regenerate_id(true)`** was being called too often. On each page load, the session ID was regenerated, invalidating the previous one.

### Solution
Regenerated only **once every 5 minutes**:
```php
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} elseif (time() - $_SESSION['created'] > 300) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}
```

### Lesson Learned
`session_regenerate_id()` should be called sparingly — only on login, privilege escalation, or periodic rotation. Calling it on every page load breaks the session.

---

## Challenge 9: QR Code Not Rendering

### Problem
The QR code container was empty on the login page.

### Investigation
The QR library was being loaded **after** `qr-login.js` tried to use it. The order of `<script>` tags mattered.

### Solution
Ensured the QR library loads **before** the custom script:
```html
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script src="js/script.js"></script>
<script src="js/qr-login.js"></script>
```

Also added a check for the library:
```javascript
if (typeof QRCode === 'undefined') {
    console.error('QRCode library not loaded');
    return;
}
```

### Lesson Learned
Always load third-party libraries before your own scripts that depend on them. Adding a `typeof` check prevents silent failures.

---

## Challenge 10: Account Lockout Triggered During Testing

### Problem
After running multiple test cases, the admin account was locked for 15 minutes.

### Investigation
The login-attempt lockout was working correctly — but it was locking the wrong account. Test scripts were failing logins repeatedly, and after 5 attempts, the account was temporarily locked.

### Solution
Added an admin helper to unlock accounts manually:
```sql
UPDATE users 
SET login_attempts = 0, locked_until = NULL 
WHERE username = 'admin';
```

Also added a rule in `checkLockout()` to never lock the admin account:
```php
if ($username === 'admin') return false;
```

### Lesson Learned
Automatic security features can interfere with testing. Always have a manual override for admin accounts.

---

## Summary Table

| # | Challenge                     | Root Cause                | Solution                       |
|---|---                            |---                        |---                             |
| 1 | Godot → PHP                   | iframe isolation          | postMessage API                |
| 2 | Password verify fails         | Truncated hash column     | `VARCHAR(255)` + full hash     |  
| 3 | Black video background        | Relative path             | Absolute `/after-class/...`    |
| 4 | MySQL won't start             | Port 3306 in use          | Change to 3307                 |
| 5 | QR expires/reach              | Short timeout + localhost | 5-min expiry + LAN IP          |
| 6 | PHPMailer auth fails          | No App Password           | Generate Gmail App Password    |
| 7 | Scrollbar not showing         | `overflow: hidden`        | Change to `overflow-y: scroll` |
| 8 | Session lost between pages    | Frequent regeneration     | Regenerate every 5 min         |
| 9 | QR not rendering              | Load order                | Load library first             |
| 10 | Admin locked during tests    | Aggressive lockout        | Add admin exception            |

---

## Reflections

Looking back at these 10 challenges, the biggest lessons were:

1. **Path management matters** — Absolute paths prevent 80% of asset-loading issues
2. **Read error messages carefully** — Every bug's solution was hidden in the error log
3. **Test in isolation** — When something breaks, test the components separately first
4. **Security features need escape hatches** — Lockouts, timeouts, and rate limits need admin overrides
5. **Cross-origin communication is tricky** — iframe, postMessage, and session cookies require careful handling

Each challenge made the final system more robust. The debugging process was as valuable as the features built.