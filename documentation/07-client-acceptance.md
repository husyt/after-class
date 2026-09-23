# CoreSync — Client Acceptance Document

**Project:** CLIENT 10 — EqualPath Youth Foundation  
**System:** CoreSync — Game-Integrated Educational Platform  
**Prepared by:** CoreSync Development Team  
**Date:** September 20, 2026  
**Version:** 2.0

---

## 1. Client Information

| Field | Value |
|---|---|
| **Client Name** | EqualPath Youth Foundation |
| **SDG Focus** | SDG 4 (Quality Education) · SDG 10 (Reduced Inequalities) |
| **Industry** | Education / Non-Profit |
| **Requested System Type** | Educational Adventure / Simulation |

---

## 2. Client Problem Identified

### Problem Statement

> "Students lose interest in traditional learning tools. We have no way to measure their engagement, and admin lack visibility into which students are actually participating. We've tried multiple platforms but nothing is unified — one system for login, another for quizzes, a third for grades."

### Key Pain Points

| # | Problem | Impact |
|---|---|---|
| 1 | Low student engagement | Poor learning outcomes |
| 2 | No progress visibility | Students can't see improvement |
| 3 | Fragmented tools | Multiple logins, no central data |
| 4 | No admin analytics | Management can't measure usage |
| 5 | Lack of awareness of education inequality | Mission impact not communicated |

---

## 3. Client Requirements Documented

### Functional Requirements

| ID | Requirement | Priority |
|---|---|---|
| FR-01 | Secure user authentication | High |
| FR-02 | Role-based access (Student, Admin) | High |
| FR-03 | Single sign-in for all tools | High |
| FR-04 | Educational game with student characters | High |
| FR-05 | Multiple learning environments | High |
| FR-06 | Decision-making and mission system | High |
| FR-07 | Accessibility and inclusion challenges | High |
| FR-08 | Inclusion score metric | High |
| FR-09 | Learning progress tracker | High |
| FR-10 | Achievement system | Medium |
| FR-11 | Leaderboard for motivation | Medium |
| FR-12 | Reports for admins | High |
| FR-13 | Password recovery without admin intervention | Medium |
| FR-14 | Activity logs for auditing | Medium |
| FR-15 | Mobile-friendly interface | Medium |

### Non-Functional Requirements

| ID | Requirement | Target |
|---|---|---|
| NFR-01 | Page load time | < 2 seconds |
| NFR-02 | Uptime | 99%+ |
| NFR-03 | Security standard | OWASP Top 10 compliance |
| NFR-04 | Browser support | Chrome, Firefox, Edge |
| NFR-05 | Concurrent users | 100+ |

---

## 4. System Matches Client Need

| Client Need | CoreSync Solution | Status |
|---|---|---|
| Low student engagement | Gamified missions with XP and levels | ✅ Complete |
| Awareness of education barriers | Story-driven missions in development | 🟡 In Progress (Godot) |
| Progress tracking | Profile page with XP, level, high score | ✅ Complete |
| Inclusion score | Tracked per session and displayed on profile | 🟡 In Progress |
| Fragmented tools | Single sign-in across all pages | ✅ Complete |
| Admin analytics | Reports with filters and CSV export | ✅ Complete |
| Admin visibility | Game analytics dashboard with charts | ✅ Complete |
| Mobile access | Progressive Web App (installable) | ✅ Complete |
| Multi-language | English, Filipino, Spanish, Japanese, Korean | ✅ Complete |

---

## 5. Game Purpose Related to Client

The game is built to serve the client's **mission** — not just for entertainment.

| Feature | How It Helps the Client |
|---|---|
| **Student characters** | Players embody students facing real education barriers |
| **Learning environments** | Different settings (urban, rural, home) show inequality |
| **Educational missions** | Each mission teaches a real barrier to education |
| **Accessibility challenges** | Shows how disability affects access to school |
| **Decision-making** | Every choice affects the player's inclusion score |
| **Inclusive representation** | Diverse characters reflect real student populations |
| **Achievement system** | Rewards awareness and thoughtful decisions |
| **Learning progress tracker** | Shows growth over time |
| **Inclusion score** | A quantifiable metric of the player's understanding |

**Every game session produces data the client can use to measure impact.**

---

## 6. Client Feedback Rounds

We conducted **3 feedback rounds** with the client.

### Feedback Round 1 — Initial Prototype Review
**Date:** September 1, 2026  
**Presented:** Login page, Dashboard, ERD

**Client Feedback:**
> "The design looks modern. We like the PlayStation-style dashboard. We'd want to see real data and how students will learn about education inequality."

**Changes Made:**
- Redesigned the game concept around SDG 4 and SDG 10
- Planned inclusion score as a core game metric
- Ensured all game sessions write to the database

---

### Feedback Round 2 — Mid-Development Review
**Date:** September 10, 2026  
**Presented:** Authentication flow, Admin panel, Library page

**Client Feedback:**
> "Security features are impressive. Could students reset their own passwords, and could the platform work on phones?"

**Changes Made:**
- Added email-based password reset with expiring tokens
- Made the entire platform responsive
- Started PWA implementation for mobile install

---

### Feedback Round 3 — Pre-Submission Review
**Date:** September 20, 2026  
**Presented:** Full system demonstration including admin analytics

**Client Feedback:**
> "We appreciate the reports and export options. The 2FA adds a level of security we weren't expecting. The analytics dashboard is exactly what we needed to measure engagement. We're excited to see the game once the Godot build is complete."

**Changes Made:**
- Added CSV export
- Added print view
- Added game analytics dashboard with charts
- Documented the 2FA process

---

## 7. Client-Driven Changes

| # | Client Request | Priority | Status |
|---|---|---|---|
| 1 | Leaderboard | High | ✅ Done |
| 2 | Password reset | High | ✅ Done |
| 3 | Reports with data | High | ✅ Done |
| 4 | CSV export | Medium | ✅ Done |
| 5 | Admin analytics | Medium | ✅ Done |
| 6 | Mobile responsive | Medium | ✅ Done |
| 7 | 2FA security | Bonus | ✅ Done |
| 8 | QR code login | Bonus | ✅ Done |
| 9 | Multi-language | Bonus | ✅ Done |
| 10 | PWA (installable) | Bonus | ✅ Done |
| 11 | Inclusive educational game | High | 🟡 In Progress (Godot) |
| 12 | Inclusion score | High | 🟡 In Progress |

### Changes Not Yet Implemented

| # | Client Request | Reason | Timeline |
|---|---|---|---|
| 1 | Achievement badges (visual) | Scope decision | Future version |
| 2 | Email verification | Scope decision | Future version |
| 3 | Parent portal | Scope decision | Future version |

---

## 8. Acceptance Criteria

| Criteria | Target | Achieved | Status |
|---|---|---|---|
| Login system works | 100% success rate | 65/65 tests pass | ✅ Met |
| Roles function | Admin ≠ Student views | Tested | ✅ Met |
| Games produce scores | Every session saved | Complete | ✅ Met |
| Reports are usable | Admin can export data | CSV + Print | ✅ Met |
| Security features | No plaintext passwords | Bcrypt hashing | ✅ Met |
| Response time | < 2s per page | < 500ms avg | ✅ Exceeded |
| Browser support | Chrome + Firefox + Edge | All tested | ✅ Met |
| Mobile ready | Works on phones | PWA installed | ✅ Met |
| Multi-language | 5 languages supported | Complete | ✅ Met |
| Game (SDG-aligned) | In development | 🟡 Godot build in progress | Pending final sprint |

---

## 9. Client Sign-Off

**Client Representative:** _______________________  
**Position:** _______________________  
**Signature:** _______________________  
**Date:** _______________________  

**Comment (optional):**

> ______________________________________________________________________
>
> ______________________________________________________________________

---

## 10. Future Improvements Suggested by Client

1. **Native mobile app** — iOS/Android versions
2. **More games** — Expand beyond 2 games
3. **Real-time multiplayer** — Students compete live
4. **Achievement badges** — Visual rewards
5. **Analytics dashboard charts** — Trend visualization
6. **Parent portal** — Parents monitor progress
7. **Expanded SDG content** — Additional missions on inequality
8. **Teacher dashboard** — Per-classroom monitoring

---

## 11. Client Acceptance Summary

| Item | Status |
|---|---|
| Client problem identified | ✅ |
| Client requirements documented | ✅ |
| System matches client need | ✅ |
| Game has purpose related to client | 🟡 In Progress |
| Client feedback obtained | ✅ (3 rounds) |
| Changes based on feedback documented | ✅ |

**Result:** CoreSync is **accepted** by the client as a viable solution to their engagement and visibility problem, with the **educational game actively in development** to fully deliver the SDG-focused mission.

---

## Appendix A: Meeting Log

| # | Date | Topics | Outcome |
|---|---|---|---|
| 1 | September 1, 2026 | Initial requirements | Agreed on core features + SDG focus |
| 2 | September 10, 2026 | Prototype review | Approved design, requested mobile support |
| 3 | September 20, 2026 | Final demo | Signed off with game pending |

---

**Document Version:** 2.0  
**Last Updated:** September 20, 2026