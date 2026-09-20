# CoreSync — Entity Relationship Diagram (ERD)

## Database: `after_class_db`

```mermaid
erDiagram
    USERS {
        INT id PK "AUTO_INCREMENT"
        VARCHAR username UK "UNIQUE, 50 chars"
        VARCHAR email UK "UNIQUE, 100 chars"
        VARCHAR pronouns "30 chars, NULL allowed"
        VARCHAR password_hash "bcrypt hash"
        ENUM role "admin, teacher, student"
        INT level "default 1"
        INT xp "default 0"
        INT high_score "default 0"
        INT games_played "default 0"
        INT login_attempts "default 0"
        DATETIME locked_until "lockout timestamp"
        DATETIME last_login "NULL until first login"
        TINYINT two_factor_enabled "1 = on, 0 = off"
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
        INT user_id FK "→ users.id, CASCADE"
        ENUM status "pending, approved, expired"
        DATETIME expires_at "5-minute expiry"
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
    
    USERS ||--o{ ACTIVITY_LOGS   : "generates"
    USERS ||--o{ OTP_CODES       : "receives"
    USERS ||--o{ QR_SESSIONS     : "authorizes"
    USERS ||--o{ GAME_SESSIONS   : "plays"

    