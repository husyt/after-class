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
| **Web APIs**   | —       | Fetch API, postMessage, localStorage, Clipboard API |
| **QRCode.js**  | 1.0.0   | QR code generation for passwordless login           |
| **Inter Font** | —       | Modern, clean typography via Google Fonts           |

### Key Frontend Features
- **Custom CSS scrollbars** with `::-webkit-scrollbar`
- **CSS Grid & Flexbox** for layout
- **CSS variables** for theming (`--primary`, `--surface`)
- **Animations & transitions** for smooth UX
- **Reduced-motion media queries** for accessibility

---

## Backend

| Technology    | Version | Purpose                                      |
|---|---|---|
| **PHP**       | 8.2.12  | Server-side logic, routing, session handling |
| **Apache**    | 2.4.58  | Web server (via XAMPP)                       |
| **Composer**  | Latest  | Dependency management                        |
| **PHPMailer** | 7.1.1   | Transactional email (OTP, password reset)    |

### Key Backend Features
- **PDO Prepared Statements** — SQL injection prevention
- **`password_hash()` / `password_verify()`** — bcrypt password security
- **Session management** — `session_start()`, regeneration, timeout
- **Custom helper functions** — `sanitize()`, `logActivity()`, `requireAdmin()`
- **PHPMailer + Gmail SMTP** — email delivery for 2FA and password reset

---

## Database

| Technology     | Version | Purpose                     |
|---|---|---|
| **MySQL**      | 8.0     | Relational database         |
| **phpMyAdmin** | 5.x     | Database administration GUI |

### Tables
- `users` — accounts, roles, stats
- `activity_logs` — audit trail
- `password_resets` — password reset tokens
- `otp_codes` — 2FA verification codes
- `qr_sessions` — QR login tokens
- `game_sessions` — game score history

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

## Game Engine (Planned)

| Technology       | Version | Purpose                                         |
|---|---|---|
| **Godot**        | 4.x     | 2D game engine (planned for final sprint)       |
| **GDScript**     | —       | Godot's native scripting language               |
| **HTML5 Export** | —       | Compiles game to WebAssembly for browser play   |

### Integration Method (Architecture Complete)

The integration infrastructure is **fully implemented** — only the exported game files are pending. Once the Godot project is exported to Web, it will run inside an `<iframe>` on `play.php`. When the game ends, it uses `JavaScriptBridge.eval()` to call `window.parent.postMessage()` — sending the score to the parent PHP page. The parent page then POSTs the score to `save_score.php` for database storage.

### Integration Status

| Component | Status |
|---|---|
| `play.php` (iframe wrapper) | ✅ Complete |
| `save_score.php` (with retry logic) | ✅ Complete |
| `game_sessions` database table | ✅ Complete |
| Score display on dashboard / profile / leaderboard | ✅ Complete |
| Godot game export (HTML5) | 🟡 Pending final sprint |

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

---

## Architecture Summary

```
┌─────────────────────────────────────────┐
│  FRONTEND                               │
│  HTML5 + CSS3 + JavaScript              │
│  (Chrome, Firefox, Edge, Safari)        │
└──────────────┬──────────────────────────┘
               │ HTTP/HTTPS
               ▼
┌─────────────────────────────────────────┐
│  BACKEND                                │
│  PHP 8.2 + Apache 2.4                   │
│  PHPMailer + Gmail SMTP                 │
└──────────────┬──────────────────────────┘
               │ PDO Prepared Statements
               ▼
┌─────────────────────────────────────────┐
│  DATABASE                               │
│  MySQL 8.0                              │
│  (6 normalized tables)                  │
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│  GAME ENGINE (planned)                  │
│  Godot 4.x → HTML5 Export               │
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
| **PostgreSQL vs MySQL** | MySQL chosen for simpler setup on Windows                        |
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
| QR login                   | 256-bit hex tokens with 5-minute expiration                       |
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
| Safari        | 14+             | ⚠️ (untested) |
| Mobile Chrome | 90+             | ✅             |
| Mobile Safari | 14+             | ⚠️ (untested) |

**Requires:** ES6 JavaScript, CSS Grid, Flexbox, WebAssembly (for Godot game), Fetch API.

---

## Hosting / Deployment

| Environment             | Setup                                      |
|---|---|
| **Local Development**   | XAMPP on Windows                           |
| **Production (future)** | Any LAMP/LEMP host with PHP 8+ and MySQL   |
| **Alternative**         | Docker container with Apache + PHP + MySQL |

**Note:** The project is currently only runnable on a local XAMPP server. Production deployment would require:
- Configuring HTTPS
- Moving `mail_config.php` to environment variables
- Setting up a real domain and DNS
- Using a managed MySQL instance (AWS RDS, DigitalOcean Managed DB)

---

## File Structure Overview

```
after-class/
├── assets/                    # Static files (videos, audio, game)
│   ├── audio/                 # Background music
│   └── games/                 # Game thumbnails, backgrounds, exports
├── config/                    # Configuration
│   ├── database.php           # PDO connection
│   └── mail_config.php        # SMTP credentials (gitignored)
├── documentation/             # Project documentation
│   ├── 01-purpose.md
│   ├── 02-architecture.md
│   ├── 03-flowchart.md
│   ├── 04-testing.md
│   ├── 05-tech-stack.md
│   ├── 06-challenges.md
│   ├── 07-client-acceptance.md
│   ├── architecture-diagram.png
│   └── erd.md
├── includes/                  # Shared PHP
│   ├── PHPMailer/
│   ├── functions.php
│   ├── mailer.php
│   ├── session.php
│   └── settings_panel.php
├── public/                    # Web-accessible files
│   ├── css/
│   ├── js/
│   ├── admin.php
│   ├── authenticate.php
│   ├── dashboard.php
│   ├── game.php
│   ├── index.php
│   ├── leaderboard.php
│   ├── library.php
│   ├── logout.php
│   ├── play.php
│   ├── profile.php
│   ├── register.php
│   ├── reports.php
│   ├── save_score.php
│   └── verify_otp.php
├── database.sql               # Database schema
├── README.md
└── .gitignore                 # Excludes sensitive files
```