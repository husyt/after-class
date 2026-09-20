# CoreSync — System Flowchart

## User Journey

```mermaid
flowchart TD
    START([🚀 Start]) --> LOGIN_PAGE[Login Page<br/>index.php]
    
    LOGIN_PAGE --> HAS_ACCOUNT{Has account?}
    
    HAS_ACCOUNT -->|No| REGISTER[Register<br/>register.php]
    REGISTER --> CONFIRM[Account created]
    CONFIRM --> LOGIN_PAGE
    
    HAS_ACCOUNT -->|Yes| ENTER_CREDS[Enter username<br/>& password]
    ENTER_CREDS --> VALIDATE{Valid credentials?}
    
    VALIDATE -->|No| LOCKOUT{5+ failed<br/>attempts?}
    LOCKOUT -->|No| SHOW_ERROR[Show error]
    SHOW_ERROR --> LOGIN_PAGE
    LOCKOUT -->|Yes| LOCK_ACCOUNT[Lock account<br/>15 minutes]
    LOCK_ACCOUNT --> LOGIN_PAGE
    
    VALIDATE -->|Yes| TWO_FA{2FA enabled?}
    
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
    CHOOSE -->|View profile| PROFILE[Profile<br/>profile.php]
    CHOOSE -->|Admin tasks| ADMIN[Admin Panel<br/>admin.php]
    CHOOSE -->|Play game| SELECT_GAME[Select game]
    
    LIBRARY --> DASHBOARD
    PROFILE --> DASHBOARD
    ADMIN --> DASHBOARD
    
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
```

## Flow Description

### 1. Entry Point
User opens `index.php` (login page). If they already have a session, they go directly to the dashboard.

### 2. Registration Branch
New users click "Create account" → fill out the form → account is created and hashed → redirected back to login.

### 3. Authentication
- User enters credentials
- System checks against bcrypt hash in database
- After 5 failed attempts, account locks for 15 minutes

### 4. Two-Factor Authentication
If 2FA is enabled:
- 6-digit code sent to user's email
- Code expires in 5 minutes
- Max 3 attempts

### 5. Main Dashboard
User can choose between:
- **Library** — browse and filter games
- **Profile** — view/edit account info
- **Admin Panel** — manage users (admin only)
- **Play Game** — select and play

### 6. Game Integration
- User picks a game → sees intro page → clicks Play
- Game runs in iframe
- On game over, score is posted via JavaScript to `save_score.php`
- Server updates XP, level, high_score, games_played
- Activity logged, user returns to dashboard

### 7. Session Termination
Logout destroys session + cookie + regenerates ID.