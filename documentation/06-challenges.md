## Challenge 11: PWA Won't Install on LAN IP (HTTP)

### Problem
After adding PWA support, the "Install" option appeared in Chrome on the phone but clicking it showed: **"This app cannot be installed."**

### Investigation
Chrome requires **HTTPS** for PWA install, with the only exception being `localhost`. On a phone accessing the app via `http://192.168.1.5/after-class/`, the browser refused to install.

### Solution
- **Short term:** Use "Create shortcut" (works but opens in Chrome)
- **Long term:** Deploy to a host with HTTPS (InfinityFree, Railway, Render)
- **Testing:** Verify PWA install works on `localhost` (Chrome allows it there)

### Lesson Learned
PWAs require HTTPS in production. Test on localhost during development, deploy with HTTPS before showing to real users.

---

## Challenge 12: Background Music Cutting Between Pages

### Problem
Every time the user navigated from `dashboard.php` to `library.php`, the background music restarted from the beginning.

### Investigation
Each PHP page loads its own `<audio>` element. When the browser navigates, the old page is destroyed and the new page starts the audio from zero.

### Solution
Used `sessionStorage` to persist playback state:
1. Before navigating, save `currentTime`, `playing`, and `savedAt` to `sessionStorage`
2. On the next page, read the saved state and seek `bgMusic.currentTime` to the saved position
3. If the state is older than 30 seconds, start from zero

Also made the audio source **absolute** (`/after-class/assets/audio/theme.mp3`) so it loads from any folder.

### Lesson Learned
Multi-page apps can't have true continuous audio — but sessionStorage gives a seamless "resume from where it left off" experience.

---

## Challenge 13: Settings Panel Translations Only Worked in English

### Problem
Changing the language to Spanish would translate most pages, but the settings drawer's labels (Reduce motion, Auto play, Audio, etc.) stayed in English.

### Investigation
The `settings_panel.php` was computing `$lang` array **too early**, before `$current_lang` was set. So the labels were frozen to whatever language was active at include-time.

### Solution
1. Moved `require_once i18n.php` and `$current_lang` refresh to the **top** of `settings_panel.php`
2. Added every settings-panel key to **all 5 language arrays** in `i18n.php`
3. Called `__()` **inline** in the HTML instead of pre-computing a `$lang` array

### Lesson Learned
In multi-file PHP apps, always ensure the i18n system runs **before** any file that needs translations.

---

## Challenge 14: Score Save Failed on Slow Connections

### Problem
On slow WiFi, the score from the Godot game never reached the database.

### Investigation
`save_score.php` was returning `503` under load, and the client gave up after the first failure.

### Solution
Implemented **layered retry logic**:
- **Server-side:** `save_score.php` retries the DB insert 3 times
- **Client-side:** `play.php` retries the POST 3 times with exponential backoff
- **User feedback:** After 3 failed attempts, a toast message asks the user if they want to retry

### Lesson Learned
Always assume network calls can fail. Add retry logic with backoff and give the user control over final failure.

---

## Challenge 15: Duplicate Column Error During Migration

### Problem
Adding new columns (`preferred_language`, `preferred_background`, `remember_tokens` table) caused `#1060 - Duplicate column name` errors when running the ALTER statement twice.

### Investigation
MySQL treats `ALTER TABLE users ADD COLUMN x, ADD COLUMN y` as one atomic operation. If one column already exists, the whole statement fails — even if the other columns are missing.

### Solution
Split each `ALTER TABLE` into **separate statements**:

```sql
ALTER TABLE users ADD COLUMN preferred_language VARCHAR(5) DEFAULT 'en';
ALTER TABLE users ADD COLUMN preferred_background VARCHAR(20) DEFAULT 'bg-home';