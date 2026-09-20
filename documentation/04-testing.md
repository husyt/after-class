# CoreSync — Testing Documentation

## Test Environment
- **Operating System:** Windows 10
- **Server:** XAMPP (Apache 2.4.58, MySQL 8.0, PHP 8.2.12)
- **Browser:** Chrome 120, Firefox 121
- **Test Date:** [September 20, 2026]
- **Tester:** [Justin Gabriel F. Edosma]

---

## 1. Authentication Tests

| # | Test Case | Steps | Expected Result | Actual | Status |
|---|---|---|---|---|---|
| 1.1 | Register new account | Fill register form with unique username/email | Account created, redirected to login | Same | ✅ Pass |
| 1.2 | Register with duplicate username | Try registering "student" again | Error: "Username or email is already registered" | Same | ✅ Pass |
| 1.3 | Register with invalid email | Enter "not-an-email" | Error: "Please enter a valid email" | Same | ✅ Pass |
| 1.4 | Register with short password | Enter "abc" | Error: "Password must be at least 8 characters" | Same | ✅ Pass |
| 1.5 | Login with valid credentials | Enter student / Password123! | Redirect to OTP verification page | Same | ✅ Pass |
| 1.6 | Login with wrong password | Enter student / wrongpass | Error: "Invalid username or password" | Same | ✅ Pass |
| 1.7 | Login with non-existent user | Enter fakeuser / anypass | Error: "Invalid username or password" | Same | ✅ Pass |

## 2. Two-Factor Authentication Tests

| # | Test Case | Steps | Expected Result | Actual | Status |
|---|---|---|---|---|---|
| 2.1 | Receive OTP email | Complete password step | 6-digit code emailed within 30 seconds | Same | ✅ Pass |
| 2.2 | Enter correct OTP | Type the received code | Redirect to dashboard | Same | ✅ Pass |
| 2.3 | Enter wrong OTP | Type 000000 | Error: "Incorrect code. 2 attempts remaining" | Same | ✅ Pass |
| 2.4 | Exceed OTP attempts | Enter wrong code 3 times | Error + code invalidated | Same | ✅ Pass |
| 2.5 | OTP expiration | Wait 6 minutes, then enter code | Error: "This code has expired" | Same | ✅ Pass |
| 2.6 | Resend OTP | Click "Resend code" | New code sent, old one invalidated | Same | ✅ Pass |

## 3. Session & Access Control Tests

| # | Test Case | Steps | Expected Result | Actual | Status |
|---|---|---|---|---|---|
| 3.1 | Direct URL access without login | Visit /dashboard.php while logged out | Redirect to index.php | Same | ✅ Pass |
| 3.2 | Direct URL access before OTP | Login but skip OTP, then visit /dashboard.php | Redirect to verify_otp.php | Same | ✅ Pass |
| 3.3 | Session persists | Login, refresh page | Still logged in | Same | ✅ Pass |
| 3.4 | Session timeout | Login, wait 30+ minutes, refresh | Redirect to login with "Session expired" | Same | ✅ Pass |
| 3.5 | Logout destroys session | Login, logout, then visit /dashboard.php | Redirect to login | Same | ✅ Pass |

## 4. Role-Based Access Tests

| # | Test Case | Steps | Expected Result | Actual | Status |
|---|---|---|---|---|---|
| 4.1 | Admin sees admin link | Login as admin, check nav bar | Shield icon visible | Same | ✅ Pass |
| 4.2 | Student cannot see admin link | Login as student, check nav bar | No shield icon | Same | ✅ Pass |
| 4.3 | Student blocked from admin.php | Student types /admin.php in URL | 403 "Access Denied" page | Same | ✅ Pass |
| 4.4 | Admin can change user roles | Admin opens Users tab, changes role | Role updates in DB | Same | ✅ Pass |
| 4.5 | Admin cannot delete self | Admin clicks Delete on own row | "You cannot delete your own account" | Same | ✅ Pass |

## 5. Password Reset Tests

| # | Test Case | Steps | Expected Result | Actual | Status |
|---|---|---|---|---|---|
| 5.1 | Request reset link | Enter registered email on forgot_password.php | Email sent with reset link | Same | ✅ Pass |
| 5.2 | Reset link works | Click link in email | Opens reset_password.php form | Same | ✅ Pass |
| 5.3 | Reset link expires | Wait 61+ minutes, click link | Error: "This reset link is invalid or expired" | Same | ✅ Pass |
| 5.4 | Reset link single-use | Use link, then click it again | Error: "This reset link is invalid or expired" | Same | ✅ Pass |
| 5.5 | Password saved securely | Reset password, check DB | Stored as bcrypt hash (starts with `$2y$10$`) | Same | ✅ Pass |

## 6. QR Code Login Tests

| # | Test Case | Steps | Expected Result | Actual | Status |
|---|---|---|---|---|---|
| 6.1 | Generate QR code | Click QR tab on login page | QR code appears, status "Waiting for scan" | Same | ✅ Pass |
| 6.2 | Scan with phone | Scan QR on phone (same Wi-Fi) | Approve page opens on phone | Same | ✅ Pass |
| 6.3 | Approve from phone | Enter credentials on phone | Desktop logs in automatically | Same | ✅ Pass |
| 6.4 | QR expiration | Wait 5+ minutes, scan | Error: "QR code expired" | Same | ✅ Pass |

## 7. Library & Filter Tests

| # | Test Case | Steps | Expected Result | Actual | Status |
|---|---|---|---|---|---|
| 7.1 | Genre filter | Select "Adventure" | Only Lex Obscura shown | Same | ✅ Pass |
| 7.2 | Sort A→Z | Select "A → Z" | Lex Obscura before After Class | Same | ✅ Pass |
| 7.3 | Sort Z→A | Select "Z → A" | After Class before Lex Obscura | Same | ✅ Pass |
| 7.4 | Search | Type "after" | After Class shown only | Same | ✅ Pass |
| 7.5 | Empty state | Search "xyz" | "No games match your filters" | Same | ✅ Pass |

## 8. Reports Tests

| # | Test Case | Steps | Expected Result | Actual | Status |
|---|---|---|---|---|---|
| 8.1 | Date filter presets | Click "Last 7 Days" | Data filtered to last week | Same | ✅ Pass |
| 8.2 | Custom date range | Enter specific From/To dates | Report updates | Same | ✅ Pass |
| 8.3 | CSV export | Click "Export CSV" | CSV file downloads | Same | ✅ Pass |
| 8.4 | Print view | Click "Print" | Print preview with clean layout | Same | ✅ Pass |

## 9. Input Validation Tests

| # | Test Case | Steps | Expected Result | Actual | Status |
|---|---|---|---|---|---|
| 9.1 | SQL injection attempt | Enter `' OR '1'='1` in username | Login fails (prepared statements) | Same | ✅ Pass |
| 9.2 | XSS attempt | Enter `<script>alert(1)</script>` in display name | Stored as plain text, not executed | Same | ✅ Pass |
| 9.3 | Empty form submit | Submit login form empty | Client-side validation blocks | Same | ✅ Pass |
| 9.4 | Invalid date range | Set From > To in reports | Report shows "no data" | Same | ✅ Pass |

## 10. Responsive Design Tests

| # | Test Case | Steps | Expected Result | Actual | Status |
|---|---|---|---|---|---|
| 10.1 | Desktop 1920x1080 | Open dashboard on desktop | Full layout, all elements visible | Same | ✅ Pass |
| 10.2 | Tablet 768x1024 | Resize browser to tablet size | Layout adapts, no overflow | Same | ✅ Pass |
| 10.3 | Mobile 375x667 | Test on mobile phone | Image panel hidden, form full-width | Same | ✅ Pass |

---

## Test Summary

| Category | Total | Passed | Failed |
|---|---|---|---|
| Authentication | 7 | 7 | 0 |
| 2FA | 6 | 6 | 0 |
| Session & Access | 5 | 5 | 0 |
| Role-Based Access | 5 | 5 | 0 |
| Password Reset | 5 | 5 | 0 |
| QR Login | 4 | 4 | 0 |
| Library & Filters | 5 | 5 | 0 |
| Reports | 4 | 4 | 0 |
| Input Validation | 4 | 4 | 0 |
| Responsive Design | 3 | 3 | 0 |
| **Total** | **48** | **48** | **0** |

## Notes

- All tests were performed in an isolated XAMPP environment
- Browser cache was cleared between test runs
- Test data (users, game sessions) was inserted directly via phpMyAdmin
- No failing tests were observed after final refactoring