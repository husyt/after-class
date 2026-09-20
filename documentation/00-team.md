# CoreSync — Team Roles & Responsibilities

**Project:** CoreSync — Game-Integrated Learning Platform  
**Course:** System Integration & Architecture (SIA)  
**Date:** September 2026

---

## 👥 Team Members

| # | Name | Role | Assigned Tasks |
|---|---|---|---|
| 1 | **Edosma** | 🎯 Project Manager | Sprint planning, Trello, coordination, GitHub |
| 2 | **Kaur** | 🔍 System Analyst | Requirements, client acceptance, testing docs |
| 3 | **Colminas** | 🏗️ System Architect | Architecture, ERD, database design, diagrams |
| 4 | **Fernandez** | 🎮 Game Designer | Godot game, score integration, gameplay |
| 5 | **Carpio** | 🎨 UI Designer | Login, dashboard, CSS, modals, responsive design |

---

## 🎯 Detailed Role Breakdown

### 1. Edosma — Project Manager

**Primary Responsibilities:**
- Sprint planning and task delegation
- Maintained the **Trello board** with 5 columns (Backlog, To Do, In Progress, Testing, Done)
- Set due dates and tracked team progress
- Managed the **GitHub repository** — commits, pull requests, and version control
- Coordinated team meetings and final submission timeline
- Ensured deliverables matched the SIA checklist

**Key Deliverables:**
- Trello board with labeled and assigned cards
- GitHub repository with meaningful commit history
- Team documentation
- Final submission assembly

---

### 2. Kaur — System Analyst

**Primary Responsibilities:**
- Gathered and documented **client requirements** (functional and non-functional)
- Translated the client's problem into system features
- Created the **client acceptance document** with 3 feedback rounds
- Authored the **testing documentation** with 65 test cases across 11 categories
- Verified that all requirements were met before submission

**Key Deliverables:**
- `documentation/07-client-acceptance.md`
- `documentation/04-testing.md`
- Client feedback summary and acceptance criteria

---

### 3. Colminas — System Architect

**Primary Responsibilities:**
- Selected the architecture pattern (**Layered + Client-Server + Component-Based**)
- Designed the **Entity Relationship Diagram (ERD)** with 7 normalized tables
- Documented all **cross-layer communication** (HTTP, PDO, SMTP, postMessage)
- Created the **architecture diagram** in draw.io
- Wrote the **system flowchart** showing the full user journey
- Ensured foreign keys, indexes, and cascades were correctly implemented

**Key Deliverables:**
- `documentation/02-architecture.md`
- `documentation/03-flowchart.md`
- `documentation/erd.md`
- `documentation/architecture-diagram.png`
- `documentation/documentation_ERD.png`

---

### 4. Fernandez — Game Designer

**Primary Responsibilities:**
- Designed **two playable games** (Lex Obscura, After Class)
- Built them in **Godot 4.x** and exported to HTML5
- Integrated game → PHP via **JavaScript `postMessage` API**
- Implemented the **score save flow** through `play.php` → `save_score.php`
- Added **retry logic** (§24 Game Failure Handling) — 3 server + 3 client attempts
- Tested game performance across Chrome, Firefox, and Edge

**Key Deliverables:**
- `assets/game/` — Godot HTML5 export
- `public/play.php` — iframe wrapper
- `public/save_score.php` — with retry logic
- Game testing results

---

### 5. Carpio — UI Designer

**Primary Responsibilities:**
- Designed the **Riot Games-inspired login page** with split layout (form + video background)
- Built the **PS5-style dashboard** with game carousel and stat cards
- Created all **modal designs** (Info, Report Issue, Edit Profile)
- Built the **settings drawer** with toggle switches
- Made everything **responsive** for desktop, tablet, and mobile
- Implemented custom **CSS scrollbars**, animations, and hover states
- Designed the **leaderboard**, **library**, **profile**, and **admin panel** UIs

**Key Deliverables:**
- `public/css/style.css` — Login page
- `public/css/dashboard.css` — All dashboard views
- `public/css/settings.css` — Settings drawer
- `public/js/dashboard.js` — Frontend interactions
- All HTML templates with matching styling

---

## 🤝 Collaboration Model

| Activity | Lead | Supporters |
|---|---|---|
| Sprint planning | Edosma | All |
| Requirements gathering | Kaur | Edosma, Colminas |
| Architecture design | Colminas | Kaur, Edosma |
| UI/UX design | Carpio | Edosma |
| Game development | Fernandez | Carpio (UI), Colminas (integration) |
| Database design | Colminas | Kaur |
| Testing | Kaur | All |
| Documentation | All | Edosma (coordinator) |
| Version control | Edosma | All |
| Final submission | Edosma | All |

---

## 📅 Project Timeline

| Week | Focus | Lead |
|---|---|---|
| Week 1 | Requirements gathering, initial architecture | Kaur, Colminas |
| Week 2 | Authentication, login UI, database setup | Carpio, Colminas |
| Week 3 | Dashboard, library, profile pages | Carpio, Edosma |
| Week 4 | Admin panel, reports, 2FA, QR login | Edosma, Kaur |
| Week 5 | Game integration, score saving | Fernandez, Colminas |
| Week 6 | Testing, documentation, final polish | Kaur, All |

---

## 🏆 Team Achievements

- ✅ **Complete SIA checklist coverage** — all 36 items addressed
- ✅ **65 test cases** — 100% pass rate
- ✅ **7 normalized database tables** with full referential integrity
- ✅ **2 games** fully integrated with the score system
- ✅ **Admin-controlled 2FA** with per-user toggles
- ✅ **Full documentation** — 8 markdown files + 3 diagrams
- ✅ **GitHub repository** with meaningful commit history
- ✅ **Trello board** with sprint tracking

---

**Every team member contributed to the project's success. Roles were designed to match each member's strengths while ensuring complete coverage of the SIA requirements.**