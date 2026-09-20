# CoreSync — Client & System Purpose

## 1. Client Information

| Field | Value |
|---|---|
| **Client** | UPHSD / CLIENT 10 — EqualPath Youth Foundation |
| **Industry** | Education / Learning |
| **Assigned via** | Draw lots (SIA Project) |
| **Project Type** | Game-Integrated Client System |

---

## 2. Client's Main Problem

Students today struggle with:

1. **Low engagement** in traditional learning systems — no motivation to complete exercises
2. **No visible progress tracking** — students can't see how they're improving over time
3. **Fragmented tools** — separate systems for login, assignments, quizzes, and grades
4. **Teacher visibility gap** — teachers don't have a centralized dashboard to monitor student performance

### Problem Statement

> "Students lose interest in traditional learning tools. We have no way to measure their engagement, and teachers lack visibility into which students are actually participating. We've tried multiple platforms but nothing is unified — one system for login, another for quizzes, a third for grades."

---

## 3. Target Users

| User Type | Description | Primary Needs |
|---|---|---|
| **Students** | Primary users, ages 12–20 | Fun, gamified learning; visible progress |
| **Teachers** | Secondary users, educators | Monitor student performance, view reports |
| **Administrators** | System managers | Manage users, view system-wide analytics |

---

## 4. Purpose of the Proposed System

CoreSync is a **game-integrated learning platform** that:

- Combines authentication, game progression, and analytics into one system
- Rewards students with XP, levels, and achievements as they play
- Gives teachers a dashboard to monitor participation and performance
- Provides administrators with reports and user management tools

---

## 5. How the Game Relates to the Client's Needs

| Client Need | How CoreSync Addresses It |
|---|---|
| Low engagement | Games reward play with XP, levels, and achievements |
| No progress tracking | Every game session is logged with score, duration, level reached |
| Fragmented tools | One login handles authentication, game access, and reports |
| Teacher visibility | Reports page shows per-student and per-game performance |

---

## 6. Expected Benefits

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

---

## 7. Main System Users / Roles

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
- Toggle 2FA per user
- View all activity logs
- Export reports as CSV
- Cannot delete their own account

---

## 8. System Scope

### In Scope
- Web-based platform (Chrome, Firefox, Edge)
- Desktop-first design with mobile responsiveness
- Two games (Lex Obscura, After Class)
- Email-based authentication with 2FA
- QR code login
- Password reset via email
- Admin-controlled 2FA toggles

### Out of Scope (Future Enhancements)
- Native mobile apps (iOS/Android)
- Multi-language support
- Payment/subscription system
- Real-time multiplayer games
- Video chat or streaming features

---

## 9. Success Metrics

| Metric | Target |
|---|---|
| Login success rate | > 95% |
| Average session duration | > 5 minutes |
| Games played per user per week | > 3 |
| Report generation time | < 2 seconds |
| System uptime | > 99% |
| Auth flow completion rate | > 90% |
| Score save success rate | > 99% (with retry logic) |

---

## 10. Client Requirements Summary

| Category | Count | Priority |
|---|---|---|
| Functional Requirements | 10 | 6 High, 4 Medium |
| Non-Functional Requirements | 5 | All met |
| Bonus Features Delivered | 2 | 2FA + QR Login |

See [07-client-acceptance.md](./07-client-acceptance.md) for the full requirements and acceptance documentation.

---

## Related Documentation

- [02-architecture.md](./02-architecture.md) — System architecture and design
- [03-flowchart.md](./03-flowchart.md) — User journey flowchart
- [05-tech-stack.md](./05-tech-stack.md) — Full technology list
- [07-client-acceptance.md](./07-client-acceptance.md) — Client requirements and sign-off