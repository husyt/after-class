# CoreSync — Client & System Purpose

## Client Information

**Client:** [UPHSD / CLIENT 10 – EqualPath Youth Foundation]
**Industry:** Education / Learning
**Assigned via:** Draw lots (SIA Project)

## Client's Main Problem

Students today struggle with:

1. **Low engagement** in traditional learning systems — no motivation to complete exercises
2. **No visible progress tracking** — students can't see how they're improving over time
3. **Fragmented tools** — separate systems for login, assignments, quizzes, and grades
4. **Teacher visibility gap** — teachers don't have a centralized dashboard to monitor student performance

## Target Users

| User Type          | Description                | Primary Needs                             |
|---                 |---                         |---                                        |
| **Students**       | Primary users, ages 12-20  | Fun, gamified learning; visible progress  |
| **Teachers**       | Secondary users, educators | Monitor student performance, view reports |
| **Administrators** | System managers            | Manage users, view system-wide analytics  |

## Purpose of the Proposed System

CoreSync is a **game-integrated learning platform** that:

- Combines authentication, game progression, and analytics into one system
- Rewards students with XP, levels, and achievements as they play
- Gives teachers a dashboard to monitor participation and performance
- Provides administrators with reports and user management tools

## How the Game Relates to the Client's Needs

| Client Need          | How CoreSync Addresses It                                        |
|---                   |---                                                               |
| Low engagement       | Games reward play with XP, levels, and achievements              |
| No progress tracking | Every game session is logged with score, duration, level reached |
| Fragmented tools     | One login handles authentication, game access, and reports       |
| Teacher visibility   | Reports page shows per-student and per-game performance          |

## Expected Benefits

### For Students
- 🎮 Fun, gamified learning experience
- 📈 Visible progress through XP and levels
- 🏆 Achievement system for motivation
- 🔐 Secure login with 2FA protection

### For Teachers
- 📊 Reports on game participation
- 👥 User management (view, edit roles)
- 📅 Date-filtered analytics (daily, weekly, monthly)
- 📥 CSV export for offline analysis

### For Administrators
- 🛡️ Role-based access control
- 📋 Activity logs for auditing
- 🎯 System-wide statistics
- 💾 Backup-ready database schema

## Main System Users / Roles

### 👤 Student
- Play games
- View own profile and stats
- View own game history
- Cannot access admin pages

### 👨‍🏫 Teacher
- View reports on students
- Monitor participation
- Cannot delete users

### 🛡️ Administrator
- Full access to all features
- Manage users (view, edit roles, delete)
- View all activity logs
- Export reports as CSV
- Cannot delete their own account

## System Scope

### In Scope
- Web-based platform (Chrome, Firefox, Edge)
- Desktop-first design with mobile responsiveness
- Two games (Lex Obscura, After Class)
- Email-based authentication with 2FA
- QR code login
- Password reset via email

### Out of Scope (Future Enhancements)
- Native mobile apps (iOS/Android)
- Multi-language support
- Payment/subscription system
- Real-time multiplayer games
- Video chat or streaming features

## Success Metrics

| Metric                         | Target      |
|---                             |---          |
| Login success rate             | > 95%       |
| Average session duration       | > 5 minutes |
| Games played per user per week | > 3         |
| Report generation time         | < 2 seconds |
| System uptime                  | > 99%       |