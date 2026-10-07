# D'fitness Gym: step-by-step development guide

## 1. Project goal and current phase

Build the gym system shown in the supplied flowchart with Laravel, PHP, and XAMPP MySQL. The interface uses orange and brown, simple wording, and no decorative icons. The navbar has Home, Logout for signed-in users, and a saved light/dark mode switch. Authentication, membership management, attendance, automatic payment records, member account management, the SMS renewal reminder queue, and self-service password resets are implemented. Actual SMS submission requires gateway credentials.

## 2. Flow from the diagram

1. A visitor opens the D'fitness Gym app.
2. Admin and existing members use the same login form. The server checks the saved account role, never a role chosen in the browser.
3. Admin accounts go to `/admin/dashboard`; approved member accounts go to `/member/dashboard`.
4. New visitors register as members. Their account starts as `pending` and appears only on the admin dashboard. Admin can approve it or reject it; rejection deletes the pending account record.
5. Admin assigns fixed Student or Regular memberships to approved members, sees status, and can delete expired records without payments. Admin scans member QR codes or records walk-in visitors. Every new membership, renewal, and walk-in records a received payment automatically. Admin can view, remove, and restore member accounts.
6. Members see their membership status, scan the gym QR code from their dashboard to check in or out, and see recent attendance and their own payment history.
7. Opted-in members with a Philippine mobile number get a renewal reminder queued during the three days before expiry. A scheduled sender submits queued messages through Semaphore when configured. A member who forgot a password can request a one-time SMS code and set a new password without admin approval when Semaphore is configured.

## 3. Run the project with XAMPP

1. Start **MySQL** in the XAMPP Control Panel. Apache is optional if using Laravel's built-in development server.
2. Open `http://localhost/phpmyadmin` and create a database named `dfitness_gym` with `utf8mb4` collation. Alternatively, run `CREATE DATABASE dfitness_gym CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;` in MySQL.
3. In this project folder, run `composer install` if `vendor` is missing.
4. Copy `.env.example` to `.env` if `.env` is missing. The supplied `.env` already points to XAMPP MySQL. Check `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`; XAMPP commonly uses `root` with a blank password in a fresh local installation.
5. Set `ADMIN_NAME`, `ADMIN_USERNAME`, and a strong `ADMIN_PASSWORD` in `.env`. Do not commit your real `.env` file. These values create the first admin account.
6. Run `php artisan key:generate`, `php artisan migrate`, then `php artisan db:seed`.
7. Run `php artisan serve` and open `http://127.0.0.1:8000/login`.

The project uses CSS directly from `public/css/app.css`; no npm build is required for this phase. The Google Fonts import is optional: system fonts are used if offline.

## 4. Test the first phase

1. Sign in with the admin username and password from `.env`. Confirm that the admin dashboard opens.
2. Sign out and select **Create a member account** on the login page.
3. Register with a different username. Confirm that the site returns to login with a pending-approval message.
4. Attempt to sign in as that member. Access should be denied while pending.
5. Sign in as admin and approve the member in **Pending registrations**. The **Reject** button deletes the pending registration, so it no longer appears or reserves its username and mobile number.
6. Sign in as the member. Confirm that the member dashboard opens.
7. Verify that a member cannot open the admin dashboard or approve another account. The approval route returns HTTP 403 for non-admin users.
8. In the admin dashboard, open **Memberships** and assign the Student (₱900) or Regular (₱950) 30-day plan to an approved member. Verify the member dashboard displays the plan and a payment marked **Received**. For renewal, assign the next period after the current one expires.
9. Open **Attendance** as admin and display the **Gym QR code** at the gym. On the member dashboard, select **Start camera** and scan that code to check in; repeat when leaving to check out. The member camera stops after each successful scan. The admin can still scan a member's personal QR as a fallback: select **Enable sound** once, then the first scan records check-in with a green confirmation and a rising beep. Move the code out of view for three seconds before scanning again to check out with a red confirmation and a falling beep. A third scan that day is rejected. Record a walk-in visitor and verify the history table.
10. Open **Payments** as admin. Verify a ₱120 received payment appears for each walk-in and the fixed payment appears for each new membership or renewal. Review the collected totals for today, this week, and this month. The member dashboard shows each payment as **Received**.
11. Open **Member accounts** as admin. Select **View** to inspect a member's details and membership status. Expired memberships show how many days have passed since expiry. Remove an account and confirm its attendance and payment records remain visible; restore it from the same member list.
12. Add a mobile number and opt in to SMS on a member dashboard. Create a membership expiring within three days, then use **Queue and send reminders now** in admin **Memberships**. The action queues eligible reminders and submits queued messages when Semaphore is configured. Without gateway credentials, reminders stay queued and no text is sent.
13. Sign out and choose **Forgot Password?** on the login page. Enter the mobile number registered to an approved member account. With Semaphore configured, the app sends a five-digit code by SMS and opens a code-only verification page. Enter the code within 15 minutes to open a separate new-password form. The code cannot be reused. Without Semaphore credentials, the request page explains that SMS is unavailable.

## 5. Database plan for the remaining flowchart

The `users` table includes `role` (`admin` or `member`), `approval_status` (`pending` or `approved`), an opaque `qr_token`, a mobile number, SMS consent, and `deleted_at` for recoverable account removal. The `membership_plans`, `memberships`, `attendance`, `payments`, `appeals`, and `sms_reminders` tables are implemented.

| Table | Main fields | Purpose |
| --- | --- | --- |
| `membership_plans` | name, duration_days, price | Fixed Student and Regular plans |
| `memberships` | user_id, plan_id, starts_at, expires_at, status | Member status and renewals |
| `attendance` | user_id nullable, checked_in_at, checked_out_at nullable, check_in_date, method, walk_in_name nullable | QR check-in/check-out and walk-in records; implemented |
| `payments` | user_id nullable, membership_id nullable, attendance_id nullable, amount, status, paid_at nullable, method nullable | Automatic received membership and walk-in payments; implemented |
| `appeals` | user_id nullable, username, message, status, admin_note, reviewed_at, reset_code_hash, reset_expires_at, reset_used_at | Password reset code history; new codes are issued automatically after Semaphore accepts the SMS |
| `sms_reminders` | user_id, membership_id, phone, scheduled_for, status, submitted_at | Renewal reminder audit trail; implemented |

Add foreign keys and indexes on member IDs and event dates. Keep QR codes as signed or random opaque tokens mapped to member IDs; do not put private member details in a QR payload.

## 6. Suggested build order after login

1. **Member account management:** Admin approval, rejection with deletion, read-only viewing, and recoverable removal are done. Add a detailed approval audit trail later if needed.
2. **Membership plans and records:** Student (₱900) and Regular (₱950) are fixed 30-day plans. Assignment and renewal record payment automatically. Add renewal shortcuts later.
3. **Attendance:** QR generation, automatic admin camera check-in/check-out, member history, and walk-in records are done. The browser must allow camera access. If the in-app browser blocks access, open the attendance page in Chrome or Edge. The scanner uses a locally served QR decoder, so no external scanning service receives the code.
4. **Payments:** Membership and walk-in payments are recorded as received automatically at ₱900, ₱950, and ₱120. The admin page shows totals collected today, this week, and this month. These records do not process online transactions. Corrections and refunds can be added later.
5. **Renewal reminders:** Members can opt in and provide a phone number. The daily scheduler queues one reminder per eligible membership and submits it to Semaphore only when credentials are configured. The admin **Memberships** page tracks queued, submitted, failed, and cancelled records. `submitted` means accepted by the provider, not confirmed delivered.
6. **Password reset requests:** Members submit their registered mobile number. Semaphore sends a five-digit code automatically when configured; no admin review is needed. The code is stored as a hash, expires in 15 minutes, and is consumed when the member sets a new password. Requests and reset attempts are rate limited by mobile number and request IP.
7. **Hardening and release:** Add password reset, rate limiting, tests for each role, backups, and production configuration. Turn off `APP_DEBUG` in production.

## 7. Main files

- `routes/web.php`: login, registration, approval, dashboard, and logout routes.
- `app/Http/Controllers/AuthController.php`: validation and account routing.
- `database/migrations/0001_01_01_000000_create_users_table.php`: account schema.
- `database/seeders/DatabaseSeeder.php`: first admin from `.env`.
- `resources/views/auth/`: shared login and registration screens.
- `resources/views/dashboards/`: current role landing pages.
- `app/Http/Controllers/MembershipController.php` and `resources/views/memberships/`: membership management.
- `app/Http/Controllers/AttendanceController.php` and `resources/views/attendance/`: QR and walk-in check-ins.
- `app/Http/Controllers/PaymentController.php` and `resources/views/payments/`: payment records and totals.
- `app/Http/Controllers/MemberAccountController.php` and `resources/views/members/`: member account management.
- `app/Services/RenewalReminders.php`, `routes/console.php`, and `resources/views/memberships/`: reminder queue, scheduler, gateway submission, and log within Memberships.
- `app/Http/Controllers/AppealController.php`, `app/Services/SemaphoreSms.php`, and `resources/views/appeals/`: SMS password reset request and member reset form.
- `public/css/app.css`: orange and brown visual system.

## 8. Enable real SMS sending

Semaphore handles both password reset codes and renewal reminders. Reset codes use its OTP endpoint; reminders use its regular messages endpoint.

1. Set `SEMAPHORE_API_KEY` in `.env` using your Semaphore account. Set `SEMAPHORE_SENDER_NAME` to a sender name registered in that account, or leave it blank to use your account's default sender name. Keep the API key private. See the [Semaphore API documentation](https://www.semaphore.co/docs).
2. Run `php artisan config:clear`. Request a reset code using an approved member's registered Philippine mobile number to test the OTP flow. A missing key or rejected request produces an error on the request page.
3. Use **Queue and send reminders now** in admin **Memberships** to test reminder submission. You can also run `php artisan reminders:queue` followed by `php artisan reminders:send` from the project directory. Without a key, reminders remain queued.
4. Keep Laravel's scheduler running. On a development machine, run `C:\xampp\php\php.exe artisan schedule:work` in the project directory while the computer is on. Restart it after a reboot. For deployment, configure a reliable once-per-minute `php artisan schedule:run` job. The app schedules queueing at 9:00 AM and submission at 9:05 AM in the `Asia/Manila` application timezone.
5. Review **Renewal reminders** on the admin **Memberships** page. A submitted record was accepted by Semaphore; it does not confirm handset delivery.
