                    USER
                      │
                      ▼
          ┌───────────────────────┐
          │  PRESENTATION LAYER   │
          │  HTML / CSS / JS      │
          │  - Login Page         │
          │  - Dashboard          │
          │  - Library            │
          │  - Profile            │
          └──────────┬────────────┘
                     │
                     ▼
          ┌───────────────────────┐
          │  APPLICATION LAYER    │
          │  PHP Scripts          │
          │  - authenticate.php   │
          │  - verify_otp.php     │
          │  - dashboard.php      │
          │  - qr_generate.php    │
          └──┬─────────────────┬──┘
             │                 │
             ▼                 ▼
      ┌──────────┐      ┌──────────────┐
      │GAME MOD  │      │USER SYSTEM   │
      │game.php  │      │login/roles   │
      └────┬─────┘      └──────┬───────┘
           │                   │
           └─────────┬─────────┘
                     ▼
          ┌───────────────────────┐
          │   DATA LAYER (MySQL)  │
          │   - users             │
          │   - activity_logs     │
          │   - otp_codes         │
          │   - password_resets   │
          │   - qr_sessions       │
          └───────────────────────┘