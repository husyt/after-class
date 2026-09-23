# CoreSync — Technology Stack

## Overview

CoreSync is built on a modern web stack combining **proven server-side technologies** with **modern front-end techniques** and a **Godot-built educational game**. Every choice was made for stability, security, and educational impact.

---

## Frontend

| Technology | Version | Purpose |
|---|---|---|
| **HTML5** | Latest | Semantic markup |
| **CSS3** | Latest | Custom styling, animations, responsive design |
| **JavaScript** | ES6+ | Client-side validation, dynamic UI, QR logic, PWA |
| **Web APIs** | — | Fetch, postMessage, localStorage, Clipboard, Service Worker |
| **QRCode.js** | 1.0.0 | QR code generation for passwordless login |
| **Inter Font** | — | Clean typography |
| **Service Worker API** | — | Offline caching and PWA install support |
| **Web App Manifest** | — | Makes the app installable on phones and desktops |

### Key Frontend Features
- Progressive Web App (installable on phone and desktop)
- Custom CSS scrollbars, animations, hover states
- Responsive layout (desktop, tablet, mobile)
- Multi-language support (5 languages)
- Persistent background music across pages

---

## Backend

| Technology | Version | Purpose |
|---|---|---|
| **PHP** | 8.2.12 | Server-side logic, routing, session handling |
| **Apache** | 2.4.58 | Web server (via XAMPP) |
| **Composer** | Latest | Dependency management |
| **PHPMailer** | 7.1.1 | Transactional email (OTP, password reset, QR approval, game reminders) |

### Key Backend Features
- PDO prepared statements — SQL injection prevention
- `password_hash()` / `password_verify()` — bcrypt passwords
- Session management with regeneration and 30-min timeout
- Custom helpers: `sanitize()`, `logActivity()`, `requireAdmin()`
- Translation system supporting 5 languages
- Retry logic for score saves (3 server + 3 client attempts)

---

## Database

| Technology | Version | Purpose |
|---|---|---|
| **MySQL** | 8.0 | Relational database |
| **phpMyAdmin** | 5.x | Database administration |

### Tables

| Table | Purpose |
|---|---|
| `users` | Accounts, roles, XP, level, high score, preferences |
| `activity_logs` | Audit trail of all user actions |
| `password_resets` | Password reset tokens (1-hour expiry) |
| `otp_codes` | 2FA verification codes (5-min expiry) |
| `qr_sessions` | QR login tokens (5-min expiry) |
| `game_sessions` | Game score history, duration, level |
| `remember_tokens` | "Stay signed in" tokens (30-day expiry) |
| `user_favorites` | Games favorited per user |
| `issue_reports` | Player-submitted bug reports |

### Design Principles
- 3NF normalization
- Foreign keys with CASCADE
- Indexes on frequently queried columns
- ENUM types for roles and statuses

---

## Game Engine

| Technology | Version | Purpose |
|---|---|---|
| **Godot** | 4.x | 2D educational adventure engine |
| **GDScript** | — | Godot's native scripting language |
| **HTML5 Export** | — | WebAssembly export for browser play |

### Game Type
**Educational Adventure / Simulation** focused on SDG 4 (Quality Education) and SDG 10 (Reduced Inequalities).

### Game Features (in development)
- Student characters with diverse backgrounds
- Multiple learning environments
- Educational missions based on real barriers to education
- Accessibility challenges
- Decision-making system affecting the **Inclusion Score**
- Achievement system
- Learning progress tracker
- Educational mini-games

### Integration Method

The Godot game exports to WebAssembly and runs inside an `<iframe>` on `play.php`. When the game ends, it uses `JavaScriptBridge.eval()` to call `window.parent.postMessage()`, sending score data to the parent page. The parent page then POSTs the score to `save_score.php` for database storage and XP calculation.

### Integration Status

| Component | Status |
|---|---|
| `play.php` (iframe wrapper) | ✅ Complete |
| `save_score.php` (with retry logic) | ✅ Complete |
| `game_sessions` database table | ✅ Complete |
| Score display (dashboard, profile, leaderboard) | ✅ Complete |
| Godot game HTML5 build | 🟡 In Progress |
| Inclusion score tracking | 🟡 In Progress |

---

## Progressive Web App (PWA)

| Component | File | Purpose |
|---|---|---|
| Web App Manifest | `public/manifest.json` | App metadata, icons, shortcuts |
| Service Worker | `public/sw.js` | Offline caching, install support |
| Registration Script | `public/js/pwa.js` | Registers SW, shows install prompt |
| Icons | `assets/icons/` | 8 sizes from 72px to 512px |

**Installable on:** Android Chrome, iOS Safari, Windows Chrome, macOS Chrome, Edge.

---

## Development Tools

| Tool | Purpose |
|---|---|
| Visual Studio Code | Primary code editor |
| VS Code Extensions | PHP Intelephense, SQLTools, Live Server |
| Git / GitHub | Version control |
| Trello | Project management |
| draw.io / Mermaid | Diagrams |
| Chrome DevTools | Debugging and testing |

---

## Security Technologies

| Feature | Implementation |
|---|---|
| Password hashing | `password_hash()` with bcrypt |
| SQL injection prevention | PDO prepared statements |
| XSS prevention | `htmlspecialchars()` on all output |
| Session security | Regeneration, httponly, SameSite=Strict, 30-min timeout |
| Rate limiting | 5-attempt lockout for 15 minutes |
| 2FA | Email OTP with per-user admin toggle |
| Password reset | 256-bit tokens, 1-hour expiry |
| QR login | 256-bit tokens with approval flow |
| Credential protection | `config/mail_config.php` in `.gitignore` |
| Input validation | Client + server-side on every form |

---

## Browser Support

| Browser | Minimum Version | Status |
|---|---|---|
| Chrome | 90+ | ✅ Tested |
| Firefox | 88+ | ✅ Tested |
| Edge | 90+ | ✅ Tested |
| Safari | 14+ | ⚠️ Untested |
| Mobile Chrome | 90+ | ✅ Tested (PWA) |
| Mobile Safari | 14+ | ⚠️ Untested |

---

## Hosting / Deployment

| Environment | Setup |
|---|---|
| **Local Development** | XAMPP on Windows |
| **Production (planned)** | InfinityFree / Railway with HTTPS |
| **PWA** | Installable via browser on HTTPS |

**Note:** The project is currently runnable on local XAMPP and can be installed as a PWA on the same WiFi network. Full public deployment requires HTTPS.