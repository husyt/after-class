# CoreSync — Technology Stack

## Overview

EqualPath is built on a modern web stack, combining proven server-side technologies with modern front-end techniques. Every technology choice was made for **stability**, **security**, and **ease of maintenance**.

---

## Frontend

| Technology     | Version | Purpose                                             |
|---|---|---|
| **HTML5**      | Latest  | Semantic markup for all pages                       |
| **CSS3**       | Latest  | Custom styling, animations, responsive design       |
| **JavaScript** | ES6+    | Client-side validation, dynamic UI, QR code logic   |
| **Web APIs**   | —       | Fetch API, postMessage, localStorage, sessionStorage, Clipboard API, MutationObserver |
| **QRCode.js**  | 1.0.0   | QR code generation for QR login                     |
| **Inter Font** | —       | Modern, clean typography via Google Fonts           |
| **DiceBear API**| 7.x    | Generated user avatars                              |

### Key Frontend Features
- **Custom CSS scrollbars** with `::-webkit-scrollbar`
- **CSS Grid & Flexbox** for layout
- **CSS variables** for theming (`--primary`, `--surface`)
- **Animations & transitions** for smooth UX
- **Reduced-motion media queries** for accessibility
- **PWA manifest + service worker** for installable app experience
- **Persistent background music** using sessionStorage
- **Event delegation** for reliable click handling
- **MutationObserver watchdog** for password toggle

---

## Backend

| Technology    | Version | Purpose                                      |
|---|---|---|
| **PHP**       | 8.2.12  | Server-side logic, routing, session handling |
| **Apache**    | 2.4.58  | Web server (via XAMPP)                       |
| **Composer**  | Latest  | Dependency management                        |
| **PHPMailer** | 7.1.1   | Transactional email (OTP, password reset, QR approval, game reminders) |

### Key Backend Features
- **PDO Prepared Statements** — SQL injection prevention
- **`password_hash()` / `password_verify()`** — bcrypt password security
- **Session management** — `session_start()`, regeneration, timeout
- **Custom helper functions** — `sanitize()`, `logActivity()`, `requireAdmin()`, `render_nav_avatar()`, `get_avatar_url()`
- **Multi-language i18n system** — 5 languages with fallbacks
- **PHPMailer + Gmail SMTP** — email delivery for OTP, password reset, QR approval, game reminders
- **Email-based QR approval flow** — no username/password entry on phone

---

## Database

| Technology     | Version | Purpose                     |
|---|---|---|
| **MySQL**      | 8.0     | Relational database         |
| **phpMyAdmin** | 5.x     | Database administration GUI |

### Tables (9 total)
- `users` — accounts, roles, stats, preferences (language, background, etc.)
- `activity_logs` — audit trail
- `password_resets` — password reset tokens
- `otp_codes` — 2FA verification codes
- `qr_sessions` — QR login tokens with email approval
- `game_sessions` — game score history
- `user_favorites` — saved games per user
- `remember_tokens` — "Stay signed in" cookies
- `issue_reports` — player-submitted bug reports

### Database Design Principles
- **3NF normalization** — no duplicate data
- **Foreign keys with CASCADE** — referential integrity
- **Indexes** on frequently queried columns
- **ENUM types** for role and status fields

---

## Server / Development Environment

| Tool        | Purpose                                        |
|---|---|
| **XAMPP**   | Local development stack (Apache + MySQL + PHP) |
| **Apache**  | HTTP server on port 80                         |
| **MySQL**   | Database server on port 3306                   |
| **Windows** | Development OS                                 |

---

## Game Engine

| Technology       | Version | Purpose                                         |
|---|---|---|
| **Godot**        | 4.x     | 2D/3D game engine (Educational Adventure / Simulation) |
| **GDScript**     | —       | Godot's native scripting language               |
| **HTML5 Export** | —       | Compiles game to WebAssembly for browser play   |

### Integration Method

The game runs inside an `<iframe>` on `play.php`. When the game ends, it uses `JavaScriptBridge.eval()` to call `window.parent.postMessage()` — sending the score to the parent PHP page. The parent page then POSTs the score to `save_score.php` for database storage.

### Game Design (Client Requested)

The EqualPath game is an **Educational Adventure / Simulation** focused on **SDG 4 (Quality Education)** and **SDG 10 (Reduced Inequalities)**.

| Element | Description | Status |
|---|---|---|
| Student characters | Diverse, inclusive representation | 🟡 In Design |
| Learning environments | Urban, rural, underfunded, well-resourced | 🟡 In Design |
| Educational missions | SDG-aligned quests | 🟡 In Design |
| Accessibility challenges | Simulate real barriers | 🟡 In Design |
| School-life scenarios | Relatable everyday situations | 🟡 In Design |
| Resource management | Manage limited resources | 🟡 In Design |
| Decision-making system | Choices affect Inclusion Score | 🟡 In Design |
| Inclusion score | Tracks inclusive choices | 🟡 In Design |
| Achievement system | Badges for milestones | 🟡 In Design |
| Learning progress tracker | Visualizes knowledge growth | 🟡 In Design |
| Educational mini-games | Short activities | 🟡 In Design |

### Integration Status

| Component | Status |
|---|---|
| `play.php` (iframe wrapper) | ✅ Complete |
| `save_score.php` (with retry logic) | ✅ Complete |
| `game_sessions` database table | ✅ Complete |
| Score display on dashboard / profile / leaderboard | ✅ Complete |
| Admin Game Analytics dashboard | ✅ Complete |
| Godot game export (HTML5) | 🟡 In Development |

---

## PWA (Progressive Web App)

| Component | Purpose |
|---|---|
| `manifest.json` | App name, icons, theme color, shortcuts |
| `sw.js` (Service Worker) | Offline caching + install support |
| `js/pwa.js` | Registers service worker + install prompt |
| `assets/icons/` | Icon set (48px to 512px) |

### PWA Features
- Installable on **Android, iOS, Windows, macOS, Linux**
- Fullscreen app mode (no URL bar)
- Custom app icon on home screen / desktop
- Offline fallback page
- Cache-first for static assets, network-first for HTML

---

## Development Tools

| Tool                   | Purpose                                            |
|---|---|
| **Visual Studio Code** | Primary code editor                                |
| **VS Code Extensions** | PHP Intelephense, PHP Debug, SQLTools, Live Server |
| **Git**                | Version control                                    |
| **GitHub**             | Remote repository hosting                          |
| **GitHub Desktop**     | Git GUI client                                     |
| **Trello**             | Project management                                 |
| **draw.io / Mermaid**  | Diagram creation                                   |
| **Postman**            | API testing (optional)                             |
| **Chrome DevTools**    | Debugging and testing                              |
| **FileZilla**          | FTP client for deployment                          |
| **PWABuilder**         | PWA icon generation                                |

---

## Architecture Summary

```
┌─────────────────────────────────────────┐
│  FRONTEND                               │
│  HTML5 + CSS3 + JavaScript              │
│  PWA (installable, offline-capable)     │
│  (Chrome, Firefox, Edge, Safari)        │
└──────────────┬──────────────────────────┘
               │ HTTP/HTTPS
               ▼
┌─────────────────────────────────────────┐
│  BACKEND                                │
│  PHP 8.2 + Apache 2.4                   │
│  PHPMailer + Gmail SMTP                 │
│  Multi-language i18n                    │
└──────────────┬──────────────────────────┘
               │ PDO Prepared Statements
               ▼
┌─────────────────────────────────────────┐
│  DATABASE                               │
│  MySQL 8.0                              │
│  (9 normalized tables)                  │
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│  GAME ENGINE                            │
│  Godot 4.x → HTML5 Export               │
│  Educational Adventure / Simulation     │
│  SDG 4 + SDG 10 Themes                  │
│  Runs in iframe, communicates via       │
│  JavaScript postMessage()               │
└─────────────────────────────────────────┘
```

---

## Why These Technologies?

| Choice                  | Reason                                                           |
|---|---|
| **PHP**                 | Widely used for education, easy to learn, great MySQL support    |
| **MySQL**               | Free, reliable, industry-standard relational DB                  |
| **XAMPP**               | One-click local server setup, ideal for students                 |
| **Godot**               | Free, open-source, exports to Web, GDScript is beginner-friendly |
| **Vanilla JS**          | No build step, no framework, runs everywhere                     |
| **PHPMailer**           | Most popular PHP email library, handles SMTP auth correctly      |
| **PWA**                 | Native app feel without native app development                   |
| **MySQL vs MongoDB**    | MySQL chosen because data is highly relational                   |

---

## Security Technologies

| Feature                    | Implementation                                                    |
|---|---|
| Password hashing           | `password_hash()` with PASSWORD_DEFAULT (bcrypt)                  |
| SQL injection prevention   | PDO prepared statements with bound parameters                     |
| XSS prevention             | `htmlspecialchars()` on all output                                |
| CSRF mitigation            | Session-based tokens, SameSite cookies                            |
| Session security           | `session_regenerate_id()`, httponly cookies, 30-min timeout       |
| Rate limiting              | 5-attempt login lockout for 15 minutes                            |
| 2FA                        | Email OTP via PHPMailer (admin-controlled per user)               |
| Password reset             | 256-bit hex tokens with 1-hour expiration                         |
| QR login                   | 256-bit hex tokens with 5-minute expiration + email approval      |
| **Credential protection**  | `config/mail_config.php` excluded from Git via `.gitignore`       |
| **Debug file cleanup**     | `debug.php`, `hash.php`, `phpinfo.php` deleted before submission  |
| **Input validation**       | Client-side (JS) + server-side (PHP) validation on every form     |
| **Error handling**         | User-friendly messages; database errors logged not displayed      |

---

## Browser Support

| Browser       | Minimum Version | Tested         |
|---|---|---|
| Chrome        | 90+             | ✅             |
| Firefox       | 88+             | ✅             |
| Edge          | 90+             | ✅             |
| Safari        | 14+             | ⚠️ (limited)   |
| Mobile Chrome | 90+             | ✅             |
| Mobile Safari | 14+             | ⚠️ (limited)   |

**Requires:** ES6 JavaScript, CSS Grid, Flexbox, WebAssembly (for Godot game), Fetch API, Service Worker (for PWA).

---

## Hosting / Deployment

| Environment             | Setup                                      |
|---|---|
| **Local Development**   | XAMPP on Windows                           |
| **Production (planned)**| InfinityFree / Railway / Render (free PHP+MySQL host with HTTPS) |
| **Alternative**         | Any LAMP/LEMP host with PHP 8+ and MySQL   |

**Deployment Notes:**
- HTTPS is required for full PWA install (except on `localhost`)
- `config/mail_config.php` should move to environment variables in production
- Free hosts may block outgoing SMTP — alternative email services (Resend, Brevo, SendGrid) are drop-in replacements
- Static assets use absolute paths (`/after-class/...`) so no changes needed after deploy

---

## File Structure Overview

```
after-class/
├── assets/                    # Static files (videos, audio, game)
│   ├── audio/                 # Background music
│   ├── avatars/               # (if local avatars used)
│   ├── games/                 # Game thumbnails, backgrounds, exports
│   └── icons/                 # PWA icons (48–512px)
├── config/                    # Configuration
│   ├── database.php           # PDO connection
│   └── mail_config.php        # SMTP credentials (gitignored)
├── documentation/             # Project documentation
│   ├── 00-team.md
│   ├── 01-purpose.md
│   ├── 02-architecture.md
│   ├── 03-flowchart.md
│   ├── 04-testing.md
│   ├── 05-tech-stack.md
│   ├── 06-challenges.md
│   ├── 07-client-acceptance.md
│   ├── architecture-diagram.png
│   ├── documentation_ERD.png
│   └── erd.md
├── includes/                  # Shared PHP
│   ├── PHPMailer/
│   ├── avatar.php
│   ├── functions.php
│   ├── i18n.php
│   ├── mailer.php
│   ├── session.php
│   └── settings_panel.php
├── public/                    # Web-accessible files
│   ├── css/                   # style.css, dashboard.css, settings.css
│   ├── js/                    # script.js, dashboard.js, settings.js, music.js, pwa.js, qr-login.js
│   ├── admin.php
│   ├── authenticate.php
│   ├── dashboard.php
│   ├── forgot_password.php
│   ├── game.php
│   ├── index.php
│   ├── leaderboard.php
│   ├── library.php
│   ├── logout.php
│   ├── manifest.json
│   ├── offline.php
│   ├── play.php
│   ├── profile.php
│   ├── qr_approve.php
│   ├── qr_check.php
│   ├── qr_generate.php
│   ├── qr_scan.php
│   ├── register.php
│   ├── reports.php
│   ├── reset_password.php
│   ├── resolve_report.php
│   ├── save_score.php
│   ├── send_reminders.php
│   ├── submit_report.php
│   ├── sw.js
│   ├── toggle_favorite.php
│   ├── update_preference.php
│   ├── update_profile.php
│   └── verify_otp.php
├── database.sql               # Database schema
├── README.md
└── .gitignore                 # Excludes sensitive files
```