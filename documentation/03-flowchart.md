# CoreSync — System Flowchart

This document describes the complete user journey through the CoreSync platform, including authentication, 2FA, gameplay, and the PWA install flow.

---

## 1. Main User Journey

```mermaid
flowchart TD
    START([🚀 Start]) --> LOGIN_PAGE[Login Page<br/>index.php]
    
    LOGIN_PAGE --> HAS_ACCOUNT{Has account?}
    
    HAS_ACCOUNT -->|No| REGISTER[Register<br/>register.php]
    REGISTER --> CONFIRM[Account created]
    CONFIRM --> LOGIN_PAGE
    
    HAS_ACCOUNT -->|Yes| LOGIN_METHOD{Login method?}
    
    LOGIN_METHOD -->|Password| ENTER_CREDS[Enter username<br/>& password]
    LOGIN_METHOD -->|QR Code| QR_FLOW[Scan QR with phone]
    
    ENTER_CREDS --> VALIDATE{Valid credentials?}
    
    VALIDATE -->|No| LOCKOUT{5+ failed<br/>attempts?}
    LOCKOUT -->|No| SHOW_ERROR[Show error]
    SHOW_ERROR --> LOGIN_PAGE
    LOCKOUT -->|Yes| LOCK_ACCOUNT[Lock account<br/>15 minutes]
    LOCK_ACCOUNT --> LOGIN_PAGE
    
    VALIDATE -->|Yes| TWO_FA{2FA enabled?}
    
    QR_FLOW --> QR_APPROVE[Approve on phone<br/>via Gmail link]
    QR_APPROVE --> DASHBOARD
    
    TWO_FA -->|Yes| SEND_OTP[Send 6-digit code<br/>to email]
    SEND_OTP --> ENTER_OTP[Enter OTP]
    ENTER_OTP --> VERIFY_OTP{OTP valid?}
    
    VERIFY_OTP -->|No| OTP_ATTEMPT{Attempts < 3?}
    OTP_ATTEMPT -->|Yes| ENTER_OTP
    OTP_ATTEMPT -->|No| LOGIN_PAGE
    
    VERIFY_OTP -->|Yes| DASHBOARD
    TWO_FA -->|No| DASHBOARD[Dashboard<br/>dashboard.php]
    
    DASHBOARD --> CHOOSE{Choose action}
    
    CHOOSE -->|Browse library| LIBRARY[Library<br/>library.php]
    CHOOSE -->|View leaderboard| LEADERBOARD[Leaderboard<br/>leaderboard.php]
    CHOOSE -->|View profile| PROFILE[Profile<br/>profile.php]
    CHOOSE -->|Admin tasks| ADMIN[Admin Panel<br/>admin.php]
    CHOOSE -->|Play game| SELECT_GAME[Select game]
    CHOOSE -->|Open settings| SETTINGS[Settings Drawer]
    
    LIBRARY --> DASHBOARD
    LEADERBOARD --> DASHBOARD
    PROFILE --> DASHBOARD
    ADMIN --> DASHBOARD
    SETTINGS --> DASHBOARD
    
    SELECT_GAME --> GAME_PAGE[Game Intro<br/>game.php]
    GAME_PAGE --> PLAY[Play Game<br/>play.php]
    PLAY --> GAME_OVER{Game over?}
    
    GAME_OVER -->|Quit| DASHBOARD
    GAME_OVER -->|Finished| SAVE_SCORE[Save score<br/>save_score.php]
    SAVE_SCORE --> UPDATE_XP[Update XP,<br/>level, high score]
    UPDATE_XP --> LOG_ACTIVITY[Log activity]
    LOG_ACTIVITY --> DASHBOARD
    
    DASHBOARD --> LOGOUT[Logout<br/>logout.php]
    LOGOUT --> DESTROY[Destroy session]
    DESTROY --> END([🏁 End])
    
    style START fill:#7c3aed,color:#fff
    style END fill:#7c3aed,color:#fff
    style DASHBOARD fill:#2ecc71,color:#000
    style LOGIN_PAGE fill:#3498db,color:#fff
    style PLAY fill:#f39c12,color:#000
    style SAVE_SCORE fill:#f39c12,color:#000
    style LOGOUT fill:#d13639,color:#fff
    style QR_FLOW fill:#3498db,color:#fff
    style SETTINGS fill:#7c3aed,color:#fff
```

### Flow Description

#### 1. Entry Point
User opens `index.php` (login page). If a session already exists, they go directly to the dashboard.

#### 2. Registration Branch
New users click "Create account" → fill out the form → account is created and password hashed → redirected back to login.

#### 3. Authentication
Two login methods are supported:
- **Password + 2FA:** Enter credentials → check against bcrypt hash → if 2FA enabled, send OTP by email
- **QR Code:** Scan QR with phone → enter email → receive approval link → approve on phone → desktop logs in automatically

After 5 failed password attempts, the account locks for 15 minutes.

#### 4. Two-Factor Authentication
If 2FA is enabled:
- 6-digit code sent to user's email
- Code expires in 5 minutes
- Max 3 attempts

#### 5. Main Dashboard
User can navigate to:
- **Library** — browse and filter games
- **Leaderboard** — view rankings
- **Profile** — view/edit account info
- **Admin Panel** — manage users (admin only)
- **Settings** — change language, theme, audio
- **Play Game** — select and play

#### 6. Game Integration
- User picks a game → sees intro page → clicks Play
- Game runs in iframe (Godot HTML5)
- On game over, score posted via `postMessage` → `save_score.php`
- Server updates XP, level, high_score, games_played
- Activity logged, user returns to dashboard

#### 7. Session Termination
Logout destroys session, clears cookie, and regenerates session ID.

---

## 2. PWA Install Flow

```mermaid
flowchart TD
    VISIT([🌐 Visit EqualPath URL]) --> DETECT{Browser supports PWA?}
    
    DETECT -->|No| BROWSER([Continue in browser only])
    DETECT -->|Yes| HTTPS{HTTPS or<br/>localhost?}
    
    HTTPS -->|No| NO_INSTALL[Install unavailable<br/>HTTP blocked]
    HTTPS -->|Yes| MANIFEST{Manifest valid?}
    
    MANIFEST -->|No| FIX_MANIFEST[Fix manifest.json]
    MANIFEST -->|Yes| SW{Service Worker<br/>registered?}
    
    SW -->|No| REGISTER_SW[Register sw.js]
    SW -->|Yes| SHOW_PROMPT[Show Install prompt<br/>in address bar]
    
    REGISTER_SW --> SHOW_PROMPT
    SHOW_PROMPT --> USER_CHOICE{User clicks Install?}
    
    USER_CHOICE -->|No| BROWSER
    USER_CHOICE -->|Yes| INSTALL[App installs]
    
    INSTALL --> HOME_SCREEN[Icon on home screen<br/>or desktop]
    HOME_SCREEN --> LAUNCH[Launch in<br/>standalone window]
    LAUNCH --> FULLSCREEN[Fullscreen, no URL bar]
    FULLSCREEN --> CACHED[Loads from cache<br/>if offline]
    CACHED --> DASHBOARD([Dashboard])
    
    style VISIT fill:#7c3aed,color:#fff
    style INSTALL fill:#2ecc71,color:#000
    style FULLSCREEN fill:#2ecc71,color:#000
    style NO_INSTALL fill:#d13639,color:#fff
```

### PWA Flow Description

1. **User visits the app** in any modern browser
2. **Browser checks compatibility** — PWA requires Chrome/Edge/Safari
3. **HTTPS requirement** — Chrome only allows install over HTTPS or localhost
4. **Manifest validation** — `manifest.json` must be valid with icons
5. **Service Worker** — must be registered for offline support
6. **Install prompt appears** — user sees an install icon in the address bar
7. **User clicks Install** — app is added to home screen / desktop
8. **Fullscreen launch** — no URL bar, feels like a native app
9. **Offline support** — cached pages load even without internet

### PWA Requirements Checklist

| Requirement | File | Status |
|---|---|---|
| Valid manifest | `public/manifest.json` | ✅ |
| Service Worker | `public/sw.js` | ✅ |
| Install script | `public/js/pwa.js` | ✅ |
| Icons (192, 512) | `assets/icons/` | ✅ |
| HTTPS or localhost | Server config | ✅ on localhost, ⚠️ needs HTTPS for public |

---

## 3. Educational Game Flow (SDG-Aligned)

```mermaid
flowchart TD
    PLAY_START([🎮 User clicks Play]) --> IFRAME[Load game in iframe<br/>play.php]
    IFRAME --> GODOT[Godot game loads<br/>HTML5 export]
    
    GODOT --> CHARACTER[Choose/play<br/>student character]
    CHARACTER --> ENVIRONMENT[Enter learning<br/>environment]
    ENVIRONMENT --> MISSION[Start educational<br/>mission]
    
    MISSION --> DECISION{Make a decision}
    
    DECISION -->|Inclusive choice| SCORE_UP[+Inclusion Score]
    DECISION -->|Exclusive choice| SCORE_DOWN[-Inclusion Score]
    
    SCORE_UP --> NEXT{More missions?}
    SCORE_DOWN --> NEXT
    
    NEXT -->|Yes| MISSION
    NEXT -->|No| ACHIEVEMENTS[Unlock achievements]
    
    ACHIEVEMENTS --> GAME_END[Game over]
    GAME_END --> POST[Send postMessage<br/>to parent]
    
    POST --> SAVE[POST to save_score.php]
    SAVE --> UPDATE_DB[Update XP, level,<br/>high score, inclusion score]
    UPDATE_DB --> LOG[Log activity]
    LOG --> DASHBOARD([Return to dashboard])
    
    style PLAY_START fill:#f39c12,color:#000
    style GODOT fill:#7c3aed,color:#fff
    style SCORE_UP fill:#2ecc71,color:#000
    style SCORE_DOWN fill:#d13639,color:#fff
    style SAVE fill:#3498db,color:#fff
```

### Game Flow Description

1. **User clicks Play** on a game intro page
2. **Godot game loads** inside an iframe on `play.php`
3. **Character selection** — student represents a diverse background
4. **Learning environment** — urban, rural, home, or classroom setting
5. **Educational mission** — task teaches a real barrier to education
6. **Decision-making** — player choices affect the Inclusion Score
7. **Achievement system** — milestones unlock rewards
8. **Game over** — sends score via `postMessage`
9. **Score saved** — XP, level, high score, and inclusion score updated
10. **Activity logged** — for admin analytics

### SDG Alignment

| SDG | How the Game Serves It |
|---|---|
| **SDG 4 — Quality Education** | Missions teach real barriers to education access |
| **SDG 10 — Reduced Inequalities** | Inclusive representation and awareness of inequality |

---

## 4. Admin Analytics Flow

```mermaid
flowchart TD
    ADMIN_LOGIN([Admin logs in]) --> ADMIN_PANEL[Admin Panel<br/>admin.php]
    
    ADMIN_PANEL --> TAB{Select tab}
    
    TAB -->|Overview| OVERVIEW[System stats<br/>Users, Games, Activity]
    TAB -->|Games| GAMES[Game Analytics<br/>Killer Feature]
    TAB -->|Users| USERS[User Management]
    TAB -->|Activity| ACTIVITY[Activity Logs]
    TAB -->|Issues| ISSUES[Issue Reports]
    TAB -->|Reports| REPORTS[Reports<br/>reports.php]
    
    GAMES --> GAME_VIEWS{View}
    GAME_VIEWS -->|Stats| STATS[Per-game stats<br/>Plays, Players, Completion]
    GAME_VIEWS -->|Chart| CHART[30-day plays chart]
    GAME_VIEWS -->|Top Players| TOP[Top 5 this week]
    GAME_VIEWS -->|Heatmap| HEATMAP[Peak playtimes<br/>7x24 grid]
    GAME_VIEWS -->|Recent| FEED[Recent sessions]
    
    USERS --> ACTIONS{Action}
    ACTIONS -->|Change role| ROLE[Update role in DB]
    ACTIONS -->|Toggle 2FA| TFA[Enable/disable 2FA]
    ACTIONS -->|Delete| DELETE[Remove user + data]
    
    ISSUES --> RESOLVE{Resolve?}
    RESOLVE -->|In Review| REVIEW[Mark as in_review]
    RESOLVE -->|Resolved| RESOLVED[Add notes, mark resolved]
    RESOLVE -->|Dismissed| DISMISS[Mark dismissed]
    
    style ADMIN_LOGIN fill:#7c3aed,color:#fff
    style GAMES fill:#f39c12,color:#000
    style STATS fill:#2ecc71,color:#000
    style CHART fill:#2ecc71,color:#000
    style HEATMAP fill:#2ecc71,color:#000
```

### Admin Flow Description

The admin panel gives full visibility into the system:

- **Overview** — total users, games played, activity count, active today
- **Games** (Killer Feature) — full analytics dashboard with charts, top players, heatmaps, and per-game breakdown
- **Users** — manage roles, toggle 2FA, delete accounts
- **Activity** — real-time feed of all user actions
- **Issues** — resolve, dismiss, or delete player-reported bugs
- **Reports** — filter by date, export CSV, print view

---

## Related Documentation

- [01-purpose.md](./01-purpose.md) — Client and system purpose
- [02-architecture.md](./02-architecture.md) — Architecture and layers
- [04-testing.md](./04-testing.md) — 65+ test cases
- [05-tech-stack.md](./05-tech-stack.md) — Full technology list
- [06-challenges.md](./06-challenges.md) — 15 technical challenges
- [07-client-acceptance.md](./07-client-acceptance.md) — Client sign-off
- [erd.md](./erd.md) — Entity Relationship Diagram