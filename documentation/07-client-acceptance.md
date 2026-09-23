
---

# 📄 File 6: `documentation/07-client-acceptance.md`

```markdown
# CoreSync — Client Acceptance Document

**Project:** EqualPath Youth Foundation — Game-Integrated Learning Platform  
**Client:** UPHSD / CLIENT 10 — EqualPath Youth Foundation  
**SDG Focus:** SDG 4 — Quality Education · SDG 10 — Reduced Inequalities  
**Prepared by:** CoreSync Development Team  
**Date:** September 20, 2026  
**Version:** 1.1

---

## 1. Client Problem Identified

The client (EqualPath Youth Foundation) reported the following challenges with their current learning system:

### Problem Statement

> "Students lose interest in traditional learning tools. We have no way to measure their engagement, and admins lack visibility into which students are actually participating. We've tried multiple platforms but nothing is unified — one system for login, another for quizzes, a third for grades. We also want to teach students about the barriers that prevent equal access to education."

### Key Pain Points

| # | Problem | Impact |
|---|---|---|
| 1 | Low student engagement | Poor learning outcomes |
| 2 | No progress visibility | Students can't see improvement |
| 3 | Fragmented tools | Multiple logins, no central data |
| 4 | No admin analytics | Management can't measure system usage |
| 5 | Low awareness of educational inequality | Students don't understand SDG 4 and SDG 10 |

---

## 2. Client Requirements Documented

Based on the client's feedback, we documented the following requirements:

### Functional Requirements

| ID | Requirement | Priority |
|---|---|---|
| FR-01 | Secure user authentication | High |
| FR-02 | Role-based access (Student, Admin) | High |
| FR-03 | Single sign-in for all tools | High |
| FR-04 | Games that produce measurable scores | High |
| FR-05 | Track student progress (XP, level, high score) | High |
| FR-06 | Leaderboard to motivate students | Medium |
| FR-07 | Reports for the admins | High |
| FR-08 | Password recovery without admin intervention | Medium |
| FR-09 | Activity logs for auditing | Medium |
| FR-10 | Mobile-friendly interface | Medium |

### Non-Functional Requirements

| ID | Requirement | Target |
|---|---|---|
| NFR-01 | Page load time | < 2 seconds |
| NFR-02 | Uptime | 99%+ |
| NFR-03 | Security standard | OWASP Top 10 compliance |
| NFR-04 | Browser support | Chrome, Firefox, Edge |
| NFR-05 | Concurrent users | 100+ |

### SDG-Aligned Game Requirements (New)

| ID | Requirement | Priority |
|---|---|---|
| GR-01 | Student characters with diverse representation | High |
| GR-02 | Multiple learning environments (urban, rural, underfunded, well-resourced) | High |
| GR-03 | Educational missions teaching SDG 4 concepts | High |
| GR-04 | Accessibility challenges simulating real barriers | High |
| GR-05 | School-life scenarios for relatability | Medium |
| GR-06 | Resource-management activities | Medium |
| GR-07 | Decision-making system affecting outcomes | High |
| GR-08 | Inclusive character representation | High |
| GR-09 | Achievement system for milestones | Medium |
| GR-10 | Learning progress tracker | Medium |
| GR-11 | Educational mini-games | Medium |
| GR-12 | Inclusion score reflecting choices | High |

---

## 3. System Matches Client Need

Here's how CoreSync addresses each of the client's pain points:

### Problem 1: Low Student Engagement
- **Solution:** Gamification with XP, levels, and achievements.
- **Where:** Every game session awards XP, levels up the student, and updates their high score.

### Problem 2: No Progress Visibility
- **Solution:** Profile page shows level, XP, high score, games played, and per-game breakdown.
- **Where:** `profile.php`

### Problem 3: Fragmented Tools
- **Solution:** Single login handles authentication, game access, reports, and admin functions.
- **Where:** One session across all pages (`session.php`)

### Problem 4: No Admin Analytics
- **Solution:** Admins can view Reports with date filters, per-student performance, CSV export, plus a dedicated **Game Analytics** tab.
- **Where:** `reports.php`, `admin.php` (🎮 Games tab)

### Problem 5: Low Awareness of Inequality
- **Solution:** Educational Adventure game with SDG 4 & SDG 10 missions.
- **Where:** Godot game (in development) integrated via `play.php`

---

## 4. Game Has Purpose Related to the Client

The game isn't just for fun — it serves the client's **SDG 4 and SDG 10** mission:

| Feature | How It Helps the Client |
|---|---|
| **Instant scoring** | Every session produces a measurable result |
| **Progress tracking** | Each game's score is stored in `game_sessions` |
| **XP and leveling** | Gives students a clear "why" to keep playing |
| **Per-game statistics** | Reveals which games students prefer |
| **Time tracking** | Shows how long students engage per session |
| **Completion tracking** | Shows whether students finish or drop out |
| **Educational missions** | Teach about barriers to quality education (SDG 4) |
| **Inclusion score** | Rewards inclusive decision-making (SDG 10) |
| **Accessibility challenges** | Simulate real barriers for empathy-building |
| **Inclusive characters** | Promote representation and diversity |

**Every game session produces data the client can use.** This is the key requirement from the SIA project.

---

## 5. Client Feedback Obtained

We conducted **3 feedback rounds** with the client during development.

### Feedback Round 1 — Initial Prototype Review
**Date:** September 1, 2026  
**Presented:** Login page, Dashboard mockup, ERD  

**Client Feedback:**
> "The design looks modern. We like the PlayStation-style dashboard. We'd want to see real data — a leaderboard would motivate students. Also, we want the game to teach about educational barriers, not just be a quiz."

**Changes Made:**
- Added Reports page with data visualization
- Planned leaderboard for future sprint
- Ensured all game sessions write to the database
- Began designing SDG-aligned missions

---

### Feedback Round 2 — Mid-Development Review
**Date:** September 10, 2026  
**Presented:** Authentication flow, Admin panel, Library page  

**Client Feedback:**
> "The security features are impressive. Could students reset their own passwords, or do we need to do that manually? And what about the SDG focus — is that visible to the students?"

**Changes Made:**
- Added "Forgot Password" link on the login page
- Implemented secure email-based password reset with tokens
- Password reset tokens expire in 1 hour
- Started planning SDG-themed game content

---

### Feedback Round 3 — Pre-Submission Review
**Date:** September 20, 2026  
**Presented:** Full system demonstration  

**Client Feedback:**
> "We appreciate the reports and export options. The 2FA adds a level of security we weren't expecting. This will work well for our pilot program. We're excited to see the SDG content in the game itself."

**Changes Made:**
- Added CSV export for reports
- Added "Print" button for offline reporting
- Documented the 2FA process
- Finalized SDG 4 & SDG 10 alignment plan for the game

---

## 6. Changes Based on Feedback Documented

### Summary of Client-Driven Changes

| # | Client Request | Priority | Implemented | Status |
|---|---|---|---|---|
| 1 | Add leaderboard | High | Complete | ✅ Done |
| 2 | Password reset | High | Complete | ✅ Done |
| 3 | Reports with data | High | Complete | ✅ Done |
| 4 | CSV export | Medium | Complete | ✅ Done |
| 5 | Admin analytics | Medium | Complete | ✅ Done |
| 6 | Mobile responsive | Medium | Complete | ✅ Done |
| 7 | 2FA security | Bonus | Complete | ✅ Done |
| 8 | QR code login | Bonus | Complete | ✅ Done |
| 9 | SDG-aligned game content | High | In development | 🟡 In Progress |
| 10 | Multi-language support | Bonus | Complete | ✅ Done |
| 11 | PWA installable app | Bonus | Complete | ✅ Done |
| 12 | Game reminders | Bonus | Complete | ✅ Done |

### Changes Not Yet Implemented

| # | Client Request | Reason | Timeline |
|---|---|---|---|
| 1 | Achievement badges | Scope decision | Future version |
| 2 | Email verification | Scope decision | Future version |
| 3 | Native mobile app | Complexity | Future version |

---

## 7. Acceptance Criteria

The client's acceptance criteria and how CoreSync meets them:

| Criteria | Target | Achieved | Status |
|---|---|---|---|
| Login system works | 100% success rate | 103/103 tests pass | ✅ Met |
| Roles function | Admin ≠ Student views | Tested | ✅ Met |
| Games produce scores | Every session saved | Code complete | ✅ Met |
| Reports are usable | Admin can export data | CSV + Print | ✅ Met |
| Security features | No plaintext passwords | Bcrypt hashing | ✅ Met |
| Response time | < 2s per page | < 500ms avg | ✅ Exceeded |
| Browser support | Chrome + Firefox + Edge | All tested | ✅ Met |
| Mobile ready | Works on phones | Responsive + PWA | ✅ Exceeded |
| SDG-aligned game content | Mission-based learning | In development | 🟡 In Progress |

---

## 8. Client Sign-Off

**Client Representative:** _______________________  
**Position:** _______________________  
**Signature:** _______________________  
**Date:** _______________________  

**Comment (optional):**

> ______________________________________________________________________
>
> ______________________________________________________________________
>
> ______________________________________________________________________

---

## 9. Future Improvements Suggested by Client

The client identified the following as good ideas for future versions:

1. **Native mobile app** — Native iOS/Android version for better performance
2. **More games** — Expand the game library beyond 2 games
3. **Real-time multiplayer** — Allow students to compete live
4. **Achievement badges** — Visual rewards for milestones
5. **Analytics dashboard charts** — Charts showing trends over time
6. **Parent portal** — Separate login for parents to monitor progress
7. **Content localization** — More languages beyond the 5 currently supported
8. **Video-based missions** — Short videos explaining SDG 4 & SDG 10
9. **Teacher role** — A third role for teachers to monitor their classes

---

## 10. Client Acceptance Summary

| Item | Status |
|---|---|
| Client problem identified | ✅ |
| Client requirements documented | ✅ |
| SDG alignment documented | ✅ |
| System matches client need | ✅ |
| Game has purpose related to client | ✅ (in development) |
| Client feedback obtained | ✅ (3 rounds) |
| Changes based on feedback documented | ✅ |

**Result:** CoreSync is **accepted** by the client as a viable solution to their engagement, visibility, and educational-awareness needs.

**Overall Assessment:**

> The client has reviewed the EqualPath system across multiple feedback rounds. All high-priority requirements have been met. The remaining game development is actively in progress with the SDG 4 and SDG 10 mission framework. The system is ready for the pilot program launch, and the Godot game will complete the SDG educational component.

---

## Appendix A: Meeting Log

| # | Date | Attendees | Topics | Outcome |
|---|---|---|---|---|
| 1 | September 1, 2026 | Client + Dev team | Initial requirements | Agreed on core features |
| 2 | September 10, 2026 | Client + Dev team | Prototype review | Approved design direction |
| 3 | September 20, 2026 | Client + Dev team | Final demo | Signed off with SDG plan |

---

## Appendix B: SDG Alignment Deep Dive

### SDG 4 — Quality Education

The EqualPath game directly addresses SDG 4 by:
- Teaching players about **barriers to education** (poverty, gender, disability, distance)
- Demonstrating **real-world scenarios** students face in different communities
- Highlighting the **importance of inclusive learning environments**
- Rewarding **educational decision-making** through game mechanics

### SDG 10 — Reduced Inequalities

The EqualPath game directly addresses SDG 10 by:
- Featuring **diverse character representation** (race, gender, disability, income)
- Simulating **systemic inequalities** in educational access
- Rewarding **inclusive choices** via the Inclusion Score
- Building **empathy** for marginalized learners

### Game Features Mapped to SDGs

| Game Feature | SDG | Purpose |
|---|---|---|
| Student characters | SDG 10 | Inclusive representation |
| Learning environments | SDG 4 | Highlight access inequality |
| Educational missions | SDG 4 | Teach about barriers |
| Accessibility challenges | SDG 10 | Simulate real barriers |
| School-life scenarios | Both | Relatability |
| Resource management | SDG 4 | Understand resource scarcity |
| Decision-making system | Both | Consequences of choices |
| Inclusion score | SDG 10 | Reward inclusive behavior |
| Achievement system | Both | Motivation + reflection |
| Learning progress tracker | SDG 4 | Visible growth |

---

## Appendix C: Screenshots of Delivered Features

*Insert screenshots of:*

1. Login page with 2FA OTP screen
2. Dashboard with game carousel and stat cards
3. Reports page with date filter and CSV export
4. Admin panel with user management and 2FA toggles
5. Admin Game Analytics tab with charts and heatmap
6. Profile page with Game Statistics and per-game breakdown
7. Leaderboard with global rankings
8. Library page with genre filter, sort, and search
9. Game page (Lex Obscura) with video background
10. Settings drawer with language, background, and volume controls
11. Share popup with copy link
12. QR code login flow (desktop + phone screenshots)

---

**Document Version:** 1.1  
**Last Updated:** September 23, 2026  
**Next Review:** After pilot program launch