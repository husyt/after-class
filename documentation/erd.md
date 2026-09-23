# CoreSync — Entity Relationship Diagram (ERD)

## Database: `after_class_db`

```mermaid
erDiagram
    USERS {
        INT id PK "AUTO_INCREMENT"
        VARCHAR username UK "UNIQUE, 50 chars"
        VARCHAR email UK "UNIQUE, 100 chars"
        VARCHAR pronouns "30 chars, NULL allowed"
        VARCHAR password_hash "bcrypt hash, 255 chars"
        ENUM role "admin, teacher, student"
        INT level "default 1"
        INT xp "default 0"
        INT high_score "default 0"
        INT games_played "default 0"
        INT login_attempts "default 0"
        DATETIME locked_until "lockout timestamp"
        DATETIME last_login "NULL until first login"
        TINYINT two_factor_enabled "1 = on, 0 = off"
        VARCHAR preferred_language "en, tl, es, ja, ko"
        VARCHAR preferred_background "bg-home, bg-city, etc."
        VARCHAR profile_picture "avatar filename"
        TINYINT reduce_motion "0 or 1"
        TINYINT auto_play "0 or 1"
        TINYINT bg_music "0 or 1"
        INT master_volume "0-100"
        TINYINT email_alerts "0 or 1"
        TINYINT game_reminders "0 or 1"
        TIMESTAMP created_at "auto"
    }
    
    ACTIVITY_LOGS {
        INT id PK "AUTO_INCREMENT"
        INT user_id FK "→ users.id, NULL on delete"
        VARCHAR activity "description"
        VARCHAR ip_address "45 chars"
        TIMESTAMP created_at "auto"
    }
    
    PASSWORD_RESETS {
        INT id PK "AUTO_INCREMENT"
        INT user_id FK "→ users.id"
        VARCHAR email "reset target email"
        VARCHAR token "256-bit hex"
        DATETIME expires_at "1-hour expiry"
        TINYINT used "0 = unused, 1 = used"
        TIMESTAMP created_at "auto"
    }
    
    OTP_CODES {
        INT id PK "AUTO_INCREMENT"
        INT user_id FK "→ users.id, CASCADE"
        VARCHAR code "6-digit code"
        INT attempts "max 3 tries"
        TINYINT used "0 = unused, 1 = used"
        DATETIME expires_at "5-minute expiry"
        TIMESTAMP created_at "auto"
    }
    
    QR_SESSIONS {
        INT id PK "AUTO_INCREMENT"
        VARCHAR token UK "64-char hex"
        VARCHAR email "email entered on phone"
        INT user_id FK "→ users.id, CASCADE"
        ENUM status "pending, approved, expired"
        TINYINT approved "0 = no, 1 = yes"
        DATETIME expires_at "5-minute expiry"
        DATETIME approved_at "NULL until approved"
        TIMESTAMP created_at "auto"
    }
    
    GAME_SESSIONS {
        INT id PK "AUTO_INCREMENT"
        INT user_id FK "→ users.id, CASCADE"
        VARCHAR game_id "e.g., lex-obscura"
        INT score "points earned"
        INT duration_seconds "session length"
        INT level_reached "highest level"
        TINYINT completed "0 = quit, 1 = finished"
        TIMESTAMP played_at "auto"
    }
    
    USER_FAVORITES {
        INT id PK "AUTO_INCREMENT"
        INT user_id FK "→ users.id, CASCADE"
        VARCHAR game_id "e.g., lex-obscura"
        TIMESTAMP created_at "auto"
    }
    
    REMEMBER_TOKENS {
        INT id PK "AUTO_INCREMENT"
        INT user_id FK "→ users.id, CASCADE"
        VARCHAR token UK "64-char hex"
        VARCHAR device_label "optional"
        DATETIME expires_at "30-day expiry"
        TIMESTAMP created_at "auto"
    }
    
    USERS ||--o{ ACTIVITY_LOGS   : "generates"
    USERS ||--o{ OTP_CODES       : "receives"
    USERS ||--o{ QR_SESSIONS     : "authorizes"
    USERS ||--o{ GAME_SESSIONS   : "plays"
    USERS ||--o{ USER_FAVORITES  : "favorites"
    USERS ||--o{ PASSWORD_RESETS : "requests"
    USERS ||--o{ REMEMBER_TOKENS : "remembers"