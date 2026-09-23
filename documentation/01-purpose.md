# CoreSync — Client & System Purpose

## 1. Client Information

| Field | Value |
|---|---|
| **Client** | CLIENT 10 — EqualPath Youth Foundation |
| **Industry** | Education / Non-Profit / Youth Development |
| **SDG Focus** | SDG 4 — Quality Education · SDG 10 — Reduced Inequalities |
| **Assigned via** | Draw lots (SIA Project) |
| **Project Type** | Game-Integrated Educational Platform |

---

## 2. Client's Mission

EqualPath Youth Foundation advocates for **inclusive education** and works to help young people understand the **barriers that prevent equal access to quality education**. Their mission directly aligns with two UN Sustainable Development Goals:

- **SDG 4 — Quality Education:** Ensure inclusive and equitable quality education for all
- **SDG 10 — Reduced Inequalities:** Reduce inequality within and among countries

---

## 3. Client's Main Problem

The client reported three core challenges:

1. **Low engagement** in traditional learning tools — students lose interest quickly
2. **No visible progress tracking** — students cannot see how they're improving
3. **Fragmented systems** — authentication, learning content, and analytics live in separate tools

### Problem Statement

> "Students lose interest in traditional learning tools. We have no way to measure their engagement, and admins lack visibility into which students are actually participating. We've tried multiple platforms but nothing is unified — one system for login, another for quizzes, a third for grades."

---

## 4. Client's Requested System

The client specifically requested:

### Core System Requirements
- **Student characters** — playable avatars representing diverse students
- **Different learning environments** — classrooms, communities, home settings
- **Educational missions** — tasks that teach real-world education barriers
- **Accessibility challenges** — scenarios that show how disability, poverty, distance, and language affect access to school
- **School-life scenarios** — daily life of students facing educational inequality
- **Resource-management activities** — budgeting, time, and opportunity management
- **Decision-making system** — choices that affect the student's journey
- **Inclusive character representation** — diverse races, genders, abilities, and backgrounds
- **Achievement system** — rewards for completing missions and milestones
- **Learning progress tracker** — visible growth over time
- **Educational mini-games** — small interactive lessons
- **Inclusion score** — a metric that reflects how inclusive the player's decisions were

### Game Type
**Educational Adventure / Simulation** built in **Godot 4.x**, integrated into the web platform.

### SDG Connection
The game promotes **equal educational opportunities** and creates **awareness of social and educational inequalities** — directly serving SDG 4 and SDG 10.

---

## 5. Target Users

| User Type | Description | Primary Needs |
|---|---|---|
| **Students** | Primary users, ages 12–20 | Fun, gamified learning; awareness of education inequality; visible progress |
| **Administrators** | Teachers / Foundation staff | Manage users, monitor engagement, track learning outcomes |

---

## 6. Purpose of the Proposed System

CoreSync is a **game-integrated educational platform** that:

- Combines authentication, gamified learning, and analytics into one system
- Rewards students with XP, levels, and achievements as they learn
- Teaches **real-world education inequality** through interactive storytelling
- Provides administrators with reports and user management tools
- Tracks a **learning progress** score and an **inclusion score**

---

## 7. How the Game Relates to the Client's Needs

| Client Need | How CoreSync Addresses It |
|---|---|
| Low engagement | Gamified missions with XP, levels, and achievements |
| Awareness of education inequality | Missions based on real barriers (poverty, distance, disability, language) |
| Progress tracking | Every session logged with score, duration, and mission progress |
| Inclusion metric | An **Inclusion Score** rewards thoughtful decisions |
| Fragmented tools | One login handles authentication, game access, and reports |

---

## 8. Expected Benefits

### For Students
- 🎮 Fun, gamified learning experience
- 🌍 Understanding of real education barriers
- 📈 Visible progress through XP, levels, and inclusion score
- 🏆 Achievement system for motivation
- 🔐 Secure login with 2FA protection

### For Administrators
- 🛡️ Role-based access control
- 📋 Activity logs for auditing
- 🎯 System-wide statistics and per-game analytics
- 📤 CSV export for offline reporting

---

## 9. Main System Users / Roles

### 👤 Student
- Play the EqualPath game
- View own profile, stats, and inclusion score
- View learning progress tracker
- Cannot access admin pages

### 🛡️ Administrator
- Full access to all features
- Manage users (view, edit roles, delete)
- Toggle 2FA per user
- View all activity logs
- View game analytics dashboard
- Export reports as CSV
- Cannot delete their own account

---

## 10. System Scope

### In Scope
- Web-based platform (Chrome, Firefox, Edge)
- Progressive Web App (installable on phone and desktop)
- Multi-language support (English, Filipino, Spanish, Japanese, Korean)
- Educational adventure game (Lex Obscura, After Class)
- Email-based authentication with 2FA
- QR code login
- Admin-controlled 2FA toggles
- Game analytics dashboard for admins

### Out of Scope (Future Enhancements)
- Native mobile apps (iOS/Android)
- Real-time multiplayer
- Payment/subscription system
- Parent portal

---

## 11. Success Metrics

| Metric | Target |
|---|---|
| Login success rate | > 95% |
| Average session duration | > 5 minutes |
| Games played per user per week | > 3 |
| Learning progress tracker updated | 100% of sessions |
| Inclusion score tracked | 100% of sessions |
| Report generation time | < 2 seconds |
| System uptime | > 99% |
| Score save success rate | > 99% (with retry logic) |

---

## 12. Client Requirements Summary

| Category | Count | Priority |
|---|---|---|
| Functional Requirements | 13 | 8 High, 5 Medium |
| Non-Functional Requirements | 5 | All met |
| Bonus Features Delivered | 4 | 2FA, QR Login, PWA, Multi-language |

See [07-client-acceptance.md](./07-client-acceptance.md) for full sign-off documentation.

---

## Related Documentation

- [02-architecture.md](./02-architecture.md) — System architecture and design
- [03-flowchart.md](./03-flowchart.md) — User journey flowchart
- [04-testing.md](./04-testing.md) — 65+ test cases
- [05-tech-stack.md](./05-tech-stack.md) — Full technology list
- [07-client-acceptance.md](./07-client-acceptance.md) — Client requirements and sign-off