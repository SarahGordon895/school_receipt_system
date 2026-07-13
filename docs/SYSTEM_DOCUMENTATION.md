# Mbonea FTRS — Complete System Documentation

**Fee Tracking & Receipt System (FTRS)** for Mbonea Secondary School  
**UDSM IS098 Project**  
**Repository:** https://github.com/SarahGordon895/school_receipt_system  
**Generated:** June 2026  
**Stack:** Laravel 12 · PHP 8.2+ · MariaDB · Bootstrap 5 · DomPDF · Maatwebsite Excel · iMart SMS API

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [How the System Runs](#2-how-the-system-runs)
3. [Project Directory Map](#3-project-directory-map)
4. [User Roles & Access Control](#4-user-roles--access-control)
5. [Complete Route Reference](#5-complete-route-reference)
6. [Backend — Controllers](#6-backend--controllers)
7. [Backend — Models](#7-backend--models)
8. [Backend — Services](#8-backend--services)
9. [Backend — Commands, Mail, Notifications](#9-backend--commands-mail-notifications)
10. [Backend — Middleware, Requests, Support](#10-backend--middleware-requests-support)
11. [Database — Complete Schema](#11-database--complete-schema)
12. [Database — Seeders & Demo Data](#12-database--seeders--demo-data)
13. [Frontend — Layouts & Design System](#13-frontend--layouts--design-system)
14. [Frontend — Every Page & UI Action](#14-frontend--every-page--ui-action)
15. [Parent Portal vs Admin Portal](#15-parent-portal-vs-admin-portal)
16. [End-to-End Feature Flows](#16-end-to-end-feature-flows)
17. [Configuration & Environment](#17-configuration--environment)
18. [Tests Coverage](#18-tests-coverage)
19. [File-by-File Inventory](#19-file-by-file-inventory)
20. [Known Gaps & Technical Notes](#20-known-gaps--technical-notes)

---

## 1. Executive Summary

### What the system does

FTRS is a school fee management web application that lets **school administrators** and **super admins**:

- Register and manage students with parent/guardian links
- Define fee structures and payment categories
- Generate numbered fee receipts (cash, bank, mobile money)
- Run collection and unpaid-balance reports (HTML, Excel, PDF)
- Send **SMS** and **email** fee reminders and payment confirmations via iMart SMS gateway and SMTP
- Manage notification logs (view, resend, mark delivered, refresh gateway status)
- Configure school branding, receipt footer, and SMS settings

**Parents** log in with **phone + password** and can:

- View their children's fee balances and due dates
- See payment history (read-only)
- Read notification messages and mark them as read
- **Pay school fees via NMB/CRDB bank** — upload the bank receipt PDF for automatic verification
- Download **clearance certificate** when a child is fully paid

### Architecture at a glance

```
Browser (Bootstrap UI)
    ↓ HTTP (routes/web.php, routes/auth.php)
Middleware (auth, role, CSRF)
    ↓
Controllers (20 classes)
    ↓
Services (SmsService, ParentReminderService, BankReceiptParser, BankPaymentVerificationService)
    ↓
Models (Eloquent) ↔ MariaDB
    ↓
External: iMart SMS API, SMTP mail, DomPDF, Excel export
```

---

## 2. How the System Runs

### Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan ftrs:install          # migrate + seed + storage:link
php artisan serve --host=127.0.0.1 --port=8088
```

Or via XAMPP: `http://localhost/1/school_receipt_system/public`

### Scheduled tasks

| Command | Schedule | Purpose |
|---------|----------|---------|
| `fees:send-reminders` | Daily **06:00** | Automated SMS/email at **14, 7, 3, and 0 days** before due date + daily overdue notices |

Requires `php artisan schedule:work` or system cron.

### Demo accounts (after seed)

| Role | Login | Password |
|------|-------|----------|
| Super Admin | `sarahgeorge7224@gmail.com` | `Super@FTRS2025` |
| School Admin | `admin@mbonea.sc.tz` | `Mbonea@Admin2025` |
| Parent (Mkumbo) | Phone `+255655139724` | `Mkumbo@2025` |
| Parent (Gordon) | Phone `+255655139724` | `Gordon@2025` |
| Parent (Chaula) | Phone `+255773255214` | `Chaula@2025` |
| Other demo parents | Each student's unique `+255620…` phone | `Parent@2025` |

After seed: **~106 students**, **~101 parent accounts** across Forms I–IV (some siblings share a parent).

Parents choose role tab **Parent** on login; admins use **School Admin** or **Super Admin** tab with email.

---

## 3. Project Directory Map

```
school_receipt_system/
├── app/
│   ├── Console/Commands/     # Artisan CLI commands (3)
│   ├── Exports/              # Excel export class
│   ├── Http/
│   │   ├── Controllers/      # 11 main + 9 Auth controllers
│   │   ├── Middleware/       # EnsureRole
│   │   └── Requests/         # LoginRequest, ProfileUpdateRequest
│   ├── Imports/              # Student CSV/Excel import
│   ├── Mail/                 # FeeReminderMailable
│   ├── Models/               # 9 Eloquent models
│   ├── Notifications/        # Payment + Fee reminder notifications
│   ├── Providers/            # AppServiceProvider
│   ├── Services/             # SMS, reminders, payment notifier
│   ├── Support/              # ParentStudentAdmission
│   └── View/Components/      # AppLayout, GuestLayout PHP classes
├── bootstrap/app.php         # App bootstrap, middleware, exceptions
├── config/                   # Laravel + services.php (SMS)
├── database/
│   ├── migrations/           # 28 migration files
│   ├── seeders/              # Database, Setting, DemoData
│   └── factories/            # UserFactory
├── docs/                     # This documentation
├── public/
│   ├── css/school-theme.css  # Main UI theme (~875 lines)
│   ├── css/bootstrap.min.css
│   └── icons/                # Bootstrap Icons
├── resources/views/          # 67 Blade templates
├── routes/web.php            # Main application routes
├── routes/auth.php           # Authentication routes
├── routes/console.php        # Schedule + inspire command
├── storage/                  # Logs, cache, compiled views
└── tests/                    # 18 test files (~55 tests)
```

---

## 4. User Roles & Access Control

### Roles (`users.role`)

| Role | Who | Home route | Access |
|------|-----|------------|--------|
| `school_admin` | **School staff** (Mbonea admin) | `/dashboard` | **All school operations:** receipts, students (import/delete), reports, notifications, bank payment review |
| `super_admin` | **Developer** (system owner) | `/settings` | **System setup only:** school branding, SMS templates, bank accounts, fee structures, payment categories |
| `parent` | Guardian | `/parent/dashboard` | Own linked children only |

### Authorization mechanism

- **No Laravel Policies** — authorization uses:
  1. `EnsureRole` middleware (`app/Http/Middleware/EnsureRole.php`)
  2. Custom route binding in `AppServiceProvider` — parents get 403 on other students
  3. Inline checks in `ParentDashboardController`- According to student id registered through parents phone number

### Login (`app/Http/Requests/Auth/LoginRequest.php`)

- **Parents:** phone + password (supports multiple accounts sharing one phone; password disambiguates)
- **Admins:** email + password filtered by `login_type` hidden field
- Rate limit: 5 attempts per phone/email + IP

---

## 5. Complete Route Reference

### Public

| Method | Path | Name | Handler |
|--------|------|------|---------|
| GET | `/` | — | Redirect to login or home |

### Auth (`routes/auth.php`)

| Method | Path | Name | Middleware |
|--------|------|------|------------|
| GET/POST | `/login` | `login` | guest |
| GET/POST | `/register` | `register` | guest |
| GET/POST | `/forgot-password` | `password.request` / `password.email` | guest |
| GET/POST | `/reset-password/{token}` | `password.reset` / `password.store` | guest |
| POST | `/logout` | `logout` | auth |
| GET | `/verify-email` | `verification.notice` | auth |
| GET | `/verify-email/{id}/{hash}` | `verification.verify` | auth, signed |
| POST | `/email/verification-notification` | `verification.send` | auth |
| GET/POST | `/confirm-password` | `password.confirm` | auth |
| PUT | `/password` | `password.update` | auth |

### Authenticated — all roles

| Method | Path | Name | Controller |
|--------|------|------|------------|
| GET | `/profile` | `profile.edit` | ProfileController@edit |
| PATCH | `/profile` | `profile.update` | ProfileController@update |
| DELETE | `/profile` | `profile.destroy` | ProfileController@destroy |

### Admin (`role:super_admin,school_admin`)

| Area | Resource routes | Extra routes |
|------|-----------------|--------------|
| Dashboard | GET `/dashboard` | — |
| Receipts | CRUD `/receipts` | GET `/receipts/partial`, GET `/receipts/{id}/pdf` |
| Students | CRUD `/students` (no show) | import, search API, send-reminder, **clearance certificate** |
| Reports | GET `/reports`, `/reports/unpaid`, **`/reports/clearance`** | generate, export excel/pdf, send-reminders, **clearance PDF** |
| Notification logs | CRUD `/notification-logs` | resend, refresh-status, mark-delivered, send |
| **Bank payments** | GET `/bank-payments` | show, download PDF, approve, reject |

### Super admin only (`role:super_admin`)

| Area | Resource routes | Extra routes |
|------|-----------------|--------------|
| Fee structures | CRUD `/fee-structures` | — |
| Payment categories | CRUD `/payment-categories` | — |
| Admin Settings | GET/PUT `/settings` | School info, SMS templates, **NMB/CRDB bank accounts** |
| Students | DELETE `/students/{id}` | import form |
| Receipts | DELETE `/receipts/{id}` | — |

### Parent (`role:parent`)

| Method | Path | Name |
|--------|------|------|
| GET | `/parent/dashboard` | `parent.dashboard` |
| GET | `/parent/students/{student}` | `parent.students.show` |
| GET | `/parent/students/{student}/clearance-certificate` | `parent.students.clearance-certificate` |
| GET | `/parent/notifications` | `parent.notifications` |
| POST | `/parent/notifications/{log}/read` | `parent.notifications.read` |
| POST | `/parent/notifications/read-all` | `parent.notifications.read-all` |
| GET | `/parent/bank-payments` | `parent.bank-payments.index` |
| POST | `/parent/bank-payments` | `parent.bank-payments.store` |

---

## 6. Backend — Controllers

### `DashboardController.php` (invokable)
**File:** `app/Http/Controllers/DashboardController.php`  
**View:** `resources/views/dashboard.blade.php`

Calculates and displays:
- Total collected, today, this month
- Outstanding balance across students
- Overdue student count
- Payment mode breakdown (current month)
- Recent 8 receipts
- Top 5 classes by collection
- Category totals via receipt pivot

---

### `ReceiptController.php`
**File:** `app/Http/Controllers/ReceiptController.php`

| Method | What it does | View/Response |
|--------|--------------|---------------|
| `index` | Paginated receipt list with filters (search, class, date, category) | `receipts.index` |
| `create` | New receipt form with students, categories | `receipts.create` |
| `store` | Validates, creates receipt, syncs category amounts, calls `ParentPaymentNotifier` (SMS+email), redirects to show with print flag | redirect |
| `show` | Receipt detail + thermal print layout | `receipts.show` |
| `edit` / `update` | Edit receipt and categories | `receipts.edit` |
| `destroy` | Delete receipt | redirect back |
| `partial` | AJAX HTML table fragment | `receipts.partials.table` |
| `pdf` | DomPDF download of single receipt | PDF file |

**Receipt numbering** (`Receipt` model boot): `RCPT-{year}-T{n}-{seq}` using `receipt_counters` table with row lock.

---

### `StudentController.php`
**File:** `app/Http/Controllers/StudentController.php`

| Method | What it does |
|--------|--------------|
| `index` | Student list with search, balance (`withSum receipts`) |
| `create` / `store` | Admit student, link guardian via `ParentStudentAdmission`, sync fee structures |
| `edit` / `update` | Update student; separate notification email vs portal login email |
| `destroy` | Delete student |
| `search` | JSON autocomplete for receipt form (`api.students.search`) |
| `importForm` / `importStore` | Excel/CSV bulk import |

**Key fields:** admission_no, name, class_name, parent contacts, fee_due_date, expected_total_fee, fee structure links.

---

### `NotificationLogController.php`
**File:** `app/Http/Controllers/NotificationLogController.php`  
**Services injected:** `ParentReminderService`, `SmsService`

| Method | What it does |
|--------|--------------|
| `index` | Filterable log list + status stat cards (delivered/failed/skipped) |
| `create` / `store` | Manual log entry (admin records a phone call, etc.) |
| `show` / `edit` / `update` / `destroy` | CRUD on log rows |
| `sendCreate` / `sendStore` | Manual SMS/email fee reminder to one student |
| `sendToStudent` | Quick reminder from students list or unpaid report |
| `resend` | Retry failed/skipped log — updates same row |
| `refreshStatus` | Poll iMart gateway for delivery confirmation |
| `markDelivered` | Admin confirms parent received message |

---

### `ReportController.php`
**File:** `app/Http/Controllers/ReportController.php`

| Method | What it does |
|--------|--------------|
| `index` | Report filter form |
| `generate` | Filtered paid receipts + summary stats |
| `exportExcel` | Maatwebsite Excel via `ReceiptsReportExport` |
| `exportPdf` | DomPDF report download |
| `unpaid` | Students with balance > 0, overdue flags |
| `sendReminders` | Runs `Artisan fees:send-reminders` |

---

### `SettingController.php`
**File:** `app/Http/Controllers/SettingController.php`

| Method | What it does |
|--------|--------------|
| `edit` | School info + SMS settings form |
| `update` | Save settings, logo upload/remove, optional test SMS |

---

### `ParentDashboardController.php`
**File:** `app/Http/Controllers/ParentDashboardController.php`

| Method | What it does | View |
|--------|--------------|------|
| `__invoke` | Portfolio: linked students, balances, due dates | `parents.dashboard` |
| `showStudent` | Payment history for one child (403 if not linked) | `parents.student-history` |
| `notifications` | Filterable inbox of notification logs | `parents.notifications` |
| `markNotificationRead` | Sets `read_at` on one log | redirect |
| `markAllNotificationsRead` | Bulk mark unread | redirect |

---

### Other admin controllers

| Controller | File | Views |
|------------|------|-------|
| `FeeStructureController` | `app/Http/Controllers/FeeStructureController.php` | `fee-structures/*` |
| `PaymentCategoryController` | `app/Http/Controllers/PaymentCategoryController.php` | `payment_categories/*` |
| `ProfileController` | `app/Http/Controllers/ProfileController.php` | `profile.edit` |

### Auth controllers (9 files in `app/Http/Controllers/Auth/`)

Standard Laravel Breeze: login, register, password reset, email verification, confirm password.

---

## 7. Backend — Models

### `User` — `app/Models/User.php`

| Field | Purpose |
|-------|---------|
| name, email, phone, password, role | Core identity |
| `home_route` accessor | `parent.dashboard` or `dashboard` |
| `login_identifier` accessor | Phone or email for navbar display |
| `hasRole()`, `isParent()` | Role checks |
| `parentStudents()` | BelongsToMany Student via `student_parent_links` |

---

### `Student` — `app/Models/Student.php`

| Field | Purpose |
|-------|---------|
| admission_no | Unique school ID |
| name, class_name | Identity |
| parent_name, parent_phone, parent_email | Notification contacts |
| parent_user_id | Linked portal account |
| fee_due_date, expected_total_fee | Fee tracking |
| admitted_at, registered_by_user_id | Audit |

**Computed accessors:**
- `paid_amount` — sum of receipt amounts
- `expected_amount` — fee structures sum OR `expected_total_fee`
- `balance` — max(0, expected - paid)

**Methods:**
- `resolveParentPhone()` — cascade: student phone → link phone → user phone
- `resolveParentEmail()` — student email → user email
- `hasParentContact()` — phone or email present

**Relationships:** parentUser, guardians, receipts, feeStructures, notificationLogs, parentLinks

---

### `Receipt` — `app/Models/Receipt.php`

| Field | Purpose |
|-------|---------|
| receipt_no | Auto-generated scoped number |
| student_id, student_name, class_name | Student link + print snapshot |
| amount, payment_date, payment_mode | Payment data |
| reference, note | Optional metadata |
| user_id | Cashier who created receipt |

**Relationships:** paymentCategories (pivot with per-category amount), user

---

### `NotificationLog` — `app/Models/NotificationLog.php`

| Field | Purpose |
|-------|---------|
| student_id, channel, status, sent_on, message | Core log |
| gateway_uid, delivery_status | SMS gateway tracking |
| read_at | Parent read timestamp |

**Statuses:** `sent`, `failed`, `skipped`  
**Channels:** `sms`, `email`  
**Methods:** `isResolvableFailure()`, `statusLabel()`, `statusBadge()`

---

### Other models

| Model | File | Table | Purpose |
|-------|------|-------|---------|
| `Setting` | `app/Models/Setting.php` | settings | School branding + SMS config singleton |
| `FeeStructure` | `app/Models/FeeStructure.php` | fee_structures | Class fee templates |
| `PaymentCategory` | `app/Models/PaymentCategory.php` | payment_categories | Tuition, transport, etc. |
| `ReceiptCounter` | `app/Models/ReceiptCounter.php` | receipt_counters | Atomic receipt sequence |
| `StudentParentLink` | `app/Models/StudentParentLink.php` | student_parent_links | Many-to-many parent↔student |

---

## 8. Backend — Services

### `SmsService` — `app/Services/SmsService.php` (264 lines)

**Purpose:** Send SMS via iMart HTTP API.

| Method | Lines (approx) | Behavior |
|--------|----------------|----------|
| `send($to, $message)` | 11–105 | Full send pipeline |
| `checkDelivery($uid)` | 107–136 | Poll gateway status |
| `deliveryIndicatesSuccess()` | 138–152 | Interpret carrier response |
| `normalizeRecipient()` | 170–187 | `0xxx` → `255xxx` |
| `resolveConfig()` | 190–228 | DB settings → .env fallback |
| `buildPayload()` | 154–168 | iMart vs generic JSON shape |
| `fetchDeliveryStatus()` | 188+ | GET `{endpoint}/{uid}` |

**Decision tree in `send()`:**
1. SMS disabled in settings → `skipped`
2. Simulate mode ON → log only, `skipped`
3. No API endpoint/token → `failed`
4. HTTP POST to iMart → if API accepts → `sent` (even if carrier later reports delay)
5. Stores `gateway_uid` and `delivery_status` in result

---

### `ParentReminderService` — `app/Services/ParentReminderService.php`

**Purpose:** Central orchestration for all parent notifications.

| Method | Purpose |
|--------|---------|
| `sendScheduledReminders($days)` | **Deprecated wrapper** — use `runAutomatedReminders()` |
| `runAutomatedReminders()` | Daily cron: exact milestones at 14/7/3/0 days + overdue; dedup via `event_type` |
| `notifyPayment($receipt)` | Payment confirmation email + SMS on new receipt |
| `notifyAdmission($student)` | Admission SMS/email when student registered |
| `sendFeeReminder($student, ...)` | Manual or scheduled fee reminder with template |
| `resendLog($log)` | Retry failed log, update same row |

**Templates:** `NotificationTemplateService` — placeholders `{student_name}`, `{amount}`, `{balance}`, `{due_date}`, etc. Configured in Admin Settings.

**Email:** `FeeReminderMailable` uses same template text as SMS  
**Logging:** Every action writes to `notification_logs` with `event_type` for milestone dedup

---

### `SmsSendResult` — `app/Services/SmsSendResult.php`

Readonly DTO: `status`, `detail`, `recipient`, `gatewayUid`, `deliveryStatus`  
Factories: `sent()`, `failed()`, `skipped()`  
Helpers: `succeeded()`, `delivered()`

---

### `ParentPaymentNotifier` — `app/Services/ParentPaymentNotifier.php`

Thin wrapper — delegates to `ParentReminderService::notifyPayment()`.  
Called from `ReceiptController@store` and auto-verified bank payments.

---

### `BankReceiptParser` — `app/Services/BankReceiptParser.php`

Extracts payment data from **NMB** and **CRDB** bank receipt PDFs (via `smalot/pdfparser`):

| Field | Detection |
|-------|-----------|
| Bank | NMB / CRDB keywords |
| Amount | TZS/TSH patterns (Tanzania `DD/MM/YYYY` dates) |
| Reference | Transaction ref / receipt no |
| Account | Beneficiary / credit account number |
| Date | Payment / value date |

---

### `BankPaymentVerificationService` — `app/Services/BankPaymentVerificationService.php`

Validates parsed receipt against school settings and student balance:

1. Beneficiary account matches school's NMB or CRDB account (Admin Settings)
2. Amount &gt; 0 and ≤ outstanding balance for **selected student**
3. Reference not already used
4. Payment date valid (not future, within 120 days)

**Outcomes:** `verified` (auto-creates `Receipt` + notifies parent), `review` (bursar approves), `rejected`

---

### `FeeCollectionReportService` / `TermClearanceReportService`

- **Fee collection report:** students sorted lowest→highest paid; whole TZS amounts
- **Term clearance report:** fully paid students for bursar; PDF export

---

### `ParentStudentAdmission` — `app/Support/ParentStudentAdmission.php`

| Method | Purpose |
|--------|---------|
| `linkGuardian()` | Create/update `student_parent_links`, set primary |
| `syncStudentPrimaryGuardian()` | Sync `parent_user_id`, phone (NOT notification email) |
| `updateParentPortalEmail()` | Update parent user login email |
| `parentOwnsStudent()` | Authorization check |

---

## 9. Backend — Commands, Mail, Notifications

### Artisan commands

| Command | File | What it does |
|---------|------|--------------|
| `ftrs:install {--fresh}` | `app/Console/Commands/InstallFtrs.php` | Migrate, seed, storage:link, print demo logins |
| `fees:send-reminders {--days=3}` | `app/Console/Commands/SendFeeReminders.php` | Run scheduled reminders |
| `ftrs:sync-parent-phones` | `app/Console/Commands/SyncParentPhones.php` | Re-seed demo data, print phones |

### Mail

| Class | File | Used when |
|-------|------|-----------|
| `FeeReminderMailable` | `app/Mail/FeeReminderMailable.php` | Fee reminder emails |
| View | `resources/views/emails/fee-reminder.blade.php` | Email body template |

### Notifications

| Class | File | Status |
|-------|------|--------|
| `PaymentReceivedNotification` | `app/Notifications/PaymentReceivedNotification.php` | **Active** — payment emails |
| `FeeReminderNotification` | `app/Notifications/FeeReminderNotification.php` | **Unused** — replaced by Mailable |

### Exports / Imports

| Class | File | Purpose |
|-------|------|---------|
| `ReceiptsReportExport` | `app/Exports/ReceiptsReportExport.php` | Excel report |
| `StudentsImport` | `app/Imports/StudentsImport.php` | Bulk student import |

---

## 10. Backend — Middleware, Requests, Support

### `EnsureRole` — `app/Http/Middleware/EnsureRole.php`

- Checks authenticated user has one of allowed roles
- Parent accessing admin route → redirect to `parent.dashboard`
- Admin accessing parent route → redirect to `dashboard`
- Otherwise → 403

### `LoginRequest` — `app/Http/Requests/Auth/LoginRequest.php`

- Validates `login_type`, password, phone OR email
- `authenticate()`: multi-candidate phone login for parents
- Rate limiting with lockout event

### `AppServiceProvider` — `app/Providers/AppServiceProvider.php`

| Boot logic | Purpose |
|------------|---------|
| `Schema::defaultStringLength(191)` | MySQL compatibility |
| Bootstrap 5 pagination | UI consistency |
| Custom `{student}` binding | Parents can only access own children |
| View composer | Injects `$appSetting`, `$parentUnreadNotifications` globally |

### Exception handling — `bootstrap/app.php`

Missing `NotificationLog` → redirect to index with yellow warning (not 404 page).

---

## 11. Database — Complete Schema

### Application tables (20 domain + 7 Laravel infra)

#### `users`
| Column | Type | Notes |
|--------|------|-------|
| id | PK | |
| name, email (unique), password | | |
| phone | string(32) nullable | NOT unique (shared phones) |
| role | string | super_admin, school_admin, parent |
| email_verified_at, remember_token | | |

#### `students`
| Column | Type | Notes |
|--------|------|-------|
| id | PK | |
| admission_no | unique nullable | |
| name | indexed | |
| class_name | nullable | Free text (not FK to classes table) |
| parent_name, parent_phone, parent_email | nullable | Notification contacts |
| parent_user_id | FK → users | Portal account |
| fee_due_date | date nullable | |
| expected_total_fee | unsigned bigint default 0 | Fallback if no fee structures |
| admitted_at, registered_by_user_id | nullable | Audit |

#### `student_parent_links`
| Column | Type | Notes |
|--------|------|-------|
| student_id, parent_user_id | FKs | unique pair |
| relationship | string | Father/Mother/Guardian/Other |
| is_primary | boolean | One primary per student |
| parent_phone | nullable | Link-specific phone |
| linked_by_user_id, linked_at | audit | |

#### `receipts`
| Column | Type | Notes |
|--------|------|-------|
| receipt_no | unique | Auto-generated |
| student_id | FK nullable | |
| student_name, class_name | snapshots | For printing |
| amount | unsigned bigint | TZS whole numbers |
| payment_date | date | |
| payment_mode | enum | Cash, Bank, Mobile Money, Other |
| reference, note | nullable | |
| user_id | FK | Cashier |
| payment_category_id | FK nullable | Legacy single category |

#### `receipt_payment_category` (pivot)
| Column | Type |
|--------|------|
| receipt_id, payment_category_id | unique pair |
| amount | per-category amount |

#### `payment_categories`
| Column | Type |
|--------|------|
| name | unique |
| default_amount | nullable TZS |

#### `fee_structures`
| Column | Type |
|--------|------|
| name, class_name, amount, due_date, is_active | |

#### `fee_structure_student` (pivot)
Links students to applicable fee structures.

#### `receipt_counters`
| Column | Type |
|--------|------|
| year, term | unique pair |
| current | sequence number |

#### `notification_logs`
| Column | Type |
|--------|------|
| student_id | FK cascade |
| channel | email/sms |
| status | sent/failed/skipped |
| sent_on | date |
| message | text |
| gateway_uid, delivery_status | SMS tracking |
| read_at | parent read timestamp |

**Indexes:** (student_id, channel, sent_on), (status, sent_on), gateway_uid

#### `settings` (singleton row)
| Column | Purpose |
|--------|---------|
| school_name, contact_phone, contact_email, address, reg_number | Branding |
| logo_path, receipt_footer | Receipt customization |
| sms_enabled, sms_simulate | SMS toggles |
| sms_api_endpoint, sms_api_token, sms_sender_id | Live SMS config |

#### Legacy unused tables
- `classes` — id, name (no model)
- `streams` — id, class_id, name (no model)

#### Laravel infrastructure
`sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `password_reset_tokens`

### Migration count: 28 files
Chronological from `0001_01_01_*` through `2026_06_18_100000_add_gateway_fields_to_notification_logs_table.php`

---

## 12. Database — Seeders & Demo Data

### `DatabaseSeeder.php`
1. Runs `SettingSeeder`
2. Backfills null roles → `school_admin`
3. Creates super admin + school admin
4. Runs `DemoDataSeeder`

### `SettingSeeder.php`
Creates Mbonea Secondary School settings with SMS config from `.env`.

### `DemoDataSeeder.php`
Creates a realistic school population:
- **~106 students** (Forms I–IV) with Tanzanian names
- **~101 parent accounts** (one per family; some siblings share a parent)
- Showcase parents: Mkumbo, Gordon, Chaula (known demo logins)
- Other parents: unique `+255620…` phones, password `Parent@2025`
- 4 payment categories, 4 fee structures
- Varied payment status: fully paid, partial, overdue, unpaid
- Receipts across Cash, Mobile Money, Bank

### `bank_payment_submissions` table

| Column | Purpose |
|--------|---------|
| parent_user_id, student_id | Who uploaded, for which child |
| file_path | Stored PDF (`storage/app/bank-receipts/`) |
| bank, extracted_amount, extracted_reference | Parsed from PDF |
| extracted_payment_date, extracted_account_number | Parsed from PDF |
| status | pending, verified, review, rejected |
| receipt_id | Linked school receipt when verified |

### Settings bank fields (`settings` table)

`bank_nmb_account_name`, `bank_nmb_account_number`, `bank_crdb_account_name`, `bank_crdb_account_number`

---

## 13. Frontend — Layouts & Design System

### Authenticated layout — `resources/views/layouts/app.blade.php`

**Structure:**
```
┌─────────────────────────────────────────────┐
│ Fixed navbar (school name, user chip,       │
│ page actions, Generate Receipt, Profile,    │
│ Logout)                                     │
├──────────┬──────────────────────────────────┤
│ Sidebar  │ Main content area                │
│ (16.25rem│ - Flash alerts (success/warning/ │
│  fixed)  │   error)                         │
│          │ - Page title                     │
│          │ - @yield('content')              │
└──────────┴──────────────────────────────────┘
```

**CSS:** `public/css/school-theme.css` (~875 lines)

**Design tokens:**
- Primary green: `#0f3d2e`
- Accent gold: `#b8860b`
- Surface: `#f0f4f2`
- Font: Plus Jakarta Sans, DM Sans

**JavaScript (inline in layout):**
- Mobile sidebar toggle
- Form loading states (`.form-with-loading`)

### Guest layout — `resources/views/layouts/guest.blade.php`

Split panel: school branding left, auth card right.  
Uses Vite + Alpine.js for Breeze components.

### Sidebar — `resources/views/layouts/partials/sidebar-nav.blade.php`

Admin: Dashboard, Receipts, Students, Reports, Notifications, **Bank Payments**  
Super admin only: Fee Structures, Payment Categories, Admin Settings  
Parent: Parent Portal, **Bank Payments**, My Notifications (unread badge), My Profile

### Key Blade components — `resources/views/components/`

| Component | File | Purpose |
|-----------|------|---------|
| `icon-btn` | `icon-btn.blade.php` | Primary UI button (icon + optional label) |
| `table-actions` | `table-actions.blade.php` | View/Edit/Delete row actions |
| `form-actions` | `form-actions.blade.php` | Submit + Cancel pair |

---

## 14. Frontend — Every Page & UI Action

### Login — `resources/views/auth/login.blade.php`
**Route:** GET/POST `/login`  
**Actions:** Role tabs (School Admin / Parent / Super Admin), email OR phone field, password, remember me, forgot password (hidden for parents)  
**JS:** Toggles email vs phone input per role tab

---

### Dashboard — `resources/views/dashboard.blade.php`
**Route:** GET `/dashboard`  
**Displays:** 4 KPI cards, payment mode chart, top classes, recent receipts, category totals  
**Actions:** Generate receipt, Register student, Reports, Notifications, View unpaid

---

### Receipts

| Page | File | Actions |
|------|------|---------|
| List | `receipts/index.blade.php` | Filter, New receipt, View/Edit/Delete per row |
| Create | `receipts/create.blade.php` | Student typeahead (API), dynamic category rows, total calculator, Save and print |
| Show | `receipts/show.blade.php` | Thermal 80mm print layout, auto-print on `?print=1`, PDF download link |
| Edit | `receipts/edit.blade.php` | Full edit form |
| PDF | `receipts/pdf.blade.php` | DomPDF template (download, not browser page) |

---

### Students

| Page | File | Actions |
|------|------|---------|
| List | `students/index.blade.php` | Search, Import, Add, Edit/Delete/Send reminder per row |
| Create/Edit | `students/create.blade.php`, `edit.blade.php` | Admission, class, parent fields partial, fee structures |
| Parent fields | `students/partials/parent-fields.blade.php` | Portal account, relationship, notification email vs portal email |
| Import | `students/import.blade.php` | File upload .xlsx/.csv |

---

### Fee Structures — `resources/views/fee-structures/`
index, create, edit + `_form` partial: name, amount, class, due date, active flag

### Payment Categories — `resources/views/payment_categories/`
index, create, edit: name, default amount

---

### Reports

| Page | File | Actions |
|------|------|---------|
| Filter | `reports/index.blade.php` | Date presets, class, category, mode, amount range; Generate; Export Excel/PDF |
| Results | `reports/results.blade.php` | Summary cards, receipt table, export buttons |
| Unpaid | `reports/unpaid.blade.php` | Overdue flags, bulk Send reminders, per-student Send reminder |
| PDF | `reports/pdf.blade.php` | DomPDF template |

---

### Notification Logs

| Page | File | Actions |
|------|------|---------|
| Index | `notification-logs/index.blade.php` | Stat cards (Delivered/Failed/Skipped/All), filters, Send to parent, Record log, row actions |
| Show | `notification-logs/show.blade.php` | Full detail, gateway UID, resend/refresh/mark delivered |
| Send | `notification-logs/send.blade.php` | Student select, SMS/email checkboxes, custom SMS text |
| Create/Edit | `notification-logs/create.blade.php`, `edit.blade.php` | Manual log CRUD |
| Resend buttons | `notification-logs/partials/resend-button.blade.php` | Resend SMS, Refresh status, Mark delivered |

---

### Admin Settings — `resources/views/settings/edit.blade.php` (super admin only)
School name, reg number, contacts, address, logo upload, receipt footer, SMS enable/simulate/API/sender ID, test SMS phone

---

### Profile — `resources/views/profile/edit.blade.php`
Update name/email, change password, delete account (modal)

---

### Parent Portal

| Page | File | Actions |
|------|------|---------|
| Dashboard | `parents/dashboard.blade.php` | Per-child balance cards, bank pay CTA, clearance cert |
| Student history | `parents/student-history.blade.php` | Fee structures, receipts, upload bank receipt |
| **Bank payments** | `parents/bank-payments.blade.php` | School bank accounts, upload PDF, submission history |
| Notifications | `parents/notifications.blade.php` | Filter, Mark all read, Mark as read per row |

### Bank payment review (admin)

| Page | File | Actions |
|------|------|---------|
| Index | `bank-payments/index.blade.php` | Filter by status, review queue |
| Show | `bank-payments/show.blade.php` | View extracted data, approve/reject, download PDF |

---

## 15. Parent Portal vs Admin Portal

| Feature | Admin | Parent |
|---------|-------|--------|
| Login | Email + password | Phone + password |
| Home | `/dashboard` | `/parent/dashboard` |
| Students | Full CRUD | Read-only own children |
| Receipts | Create/edit/delete (super admin delete) | View history only |
| **Bank payments** | Review queue, approve/reject | Upload PDF, auto-verify |
| Reports | Full access + clearance | None |
| **Clearance certificate** | Per student | Download when fully paid |
| Notifications | Send + manage logs | Read inbox only |
| Admin Settings | Super admin only | None |
| Generate receipt button | Yes (navbar) | No |
| Data scope | All school data | Linked children only |

---

## 16. End-to-End Feature Flows

### A. Record a payment (receipt)

```
Admin → Receipts → Create
  → Select student (typeahead: GET api.students.search)
  → Add payment categories + amounts
  → POST receipts.store (ReceiptController)
    → Receipt model boot generates receipt_no
    → syncPaymentCategories()
    → ParentPaymentNotifier::notify()
      → ParentReminderService::notifyPayment()
        → Email: PaymentReceivedNotification
        → SMS: SmsService::send()
        → notification_logs created
  → Redirect to receipts.show?print=1 (thermal print)
```

### B. Send fee reminder (manual)

```
Admin → Notification Logs → Send to parent
  → Select student, check SMS and/or email
  → POST notification-logs.send.store
    → ParentReminderService::sendFeeReminder(manual=true)
      → Email: FeeReminderMailable
      → SMS: SmsService::send()
      → notification_logs created
```

### C. Scheduled reminders (automated milestones)

```
Cron 06:00 → fees:send-reminders
  → ParentReminderService::runAutomatedReminders()
    → 14 days before due: fee_reminder_14 template
    → 7, 3, 0 days before due: fee_reminder template
    → After due date: overdue template (daily)
    → Skip fully paid students; dedup by event_type in notification_logs
```

### D. Parent bank payment upload

```
Parent → Bank Payments (or child profile → Upload bank receipt)
  → Pay fees at school NMB/CRDB account (shown on page)
  → Select which child (important for multi-child families)
  → Upload bank receipt PDF
  → BankReceiptParser extracts amount, ref, account, date
  → BankPaymentVerificationService validates against settings + balance
  → If verified: Receipt created (mode Bank), parent notified SMS/email
  → If review: school admin → Bank Payments → Approve/Reject
```

### E. Resend failed SMS

```
Admin → Notification Logs → Resend SMS button
  → POST notification-logs/{id}/resend
    → ParentReminderService::resendLog()
      → SmsService::send()
      → UPDATE same notification_logs row (status, gateway_uid, message)
```

### F. SMS pipeline detail

```
SmsService::send()
  1. resolveConfig() from settings DB + .env
  2. normalizeRecipient() → 255XXXXXXXXX
  3. If disabled → skipped
  4. If simulate → log to storage/logs/laravel.log → skipped
  5. POST to iMart API with sender_id (uppercase COLLEGE)
  6. If API accepts → sent (poll delivery status optionally)
  7. Return SmsSendResult → logged in notification_logs
```

### G. Report export

```
Admin → Reports → set filters → Export Excel
  → POST reports.export.excel
    → ReceiptsReportExport (Maatwebsite)
    → Download .xlsx

Admin → Export PDF
  → POST reports.export.pdf
    → DomPDF renders reports/pdf.blade.php
    → Download .pdf
```

---

## 17. Configuration & Environment

### Key `.env` variables

| Variable | Purpose |
|----------|---------|
| `APP_URL` | Base URL (e.g. http://127.0.0.1:8088) |
| `DB_*` | MariaDB connection |
| `MAIL_*` | SMTP for emails |
| `SMS_DRIVER` | `imart` |
| `SMS_API_ENDPOINT` | iMart send URL |
| `SMS_API_TOKEN` | API bearer token |
| `SMS_SENDER_ID` | Sender ID (COLLEGE) |

### Admin UI overrides (database `settings` table)

SMS can be configured in Super Admin → Admin Settings without editing `.env`:
- Enable SMS ON/OFF
- Simulate SMS ON/OFF (log only, no real send)
- API endpoint, token, sender ID

### `config/services.php`

```php
'sms' => [
    'driver' => env('SMS_DRIVER', 'generic'),
    'endpoint' => env('SMS_API_ENDPOINT'),
    'token' => env('SMS_API_TOKEN'),
    'sender_id' => env('SMS_SENDER_ID', 'SCHOOL'),
],
```

---

## 18. Tests Coverage

**19 test files, 79 test methods**

| Test file | What it verifies |
|-----------|------------------|
| `BankPaymentSubmissionTest` | NMB/CRDB PDF parsing, auto-verify, parent upload, admin review |
| `AutomatedReminderTest` | 14-day milestone, dedup, role access to fee structures |
| `FeeCollectionReportTest` | Student sort order, payment SMS templates |
| `FullyPaidStudentTest` | No reminders when paid; parent dashboard badge |
| `TermClearanceReportTest` | Clearance report + certificate PDF |
| `SettingControllerTest` | Super admin only settings access |
| `SmsServiceTest` | Simulate, disabled, iMart payload, gateway acceptance, delivery check |
| `NotificationLogControllerTest` | Full CRUD, resend, mark delivered, missing log redirect |
| `ManualParentReminderTest` | Manual send, channel validation, unpaid quick-send |
| `ParentNotificationsTest` | Parent scope, no duplicate payment logs |
| `ParentPortalScopeTest` | Parent只能 see own children |
| `StudentUpdateTest` | Parent contact persistence, portal vs notification email |
| `AuthenticationTest` | Phone login, shared phone disambiguation |
| `InstallFtrsCommandTest` | Install command seeds data |
| Auth tests (6 files) | Register, reset password, verification, profile |
| `ProfileTest` | Profile CRUD |

**Not tested:** PDF generation, Excel export, receipt UI, live SMS API.

---

## 19. File-by-File Inventory

### `app/` — 48 PHP files

| Path | Lines (approx) | Role |
|------|----------------|------|
| `Console/Commands/InstallFtrs.php` | ~80 | Install command |
| `Console/Commands/SendFeeReminders.php` | ~40 | Scheduled reminders |
| `Console/Commands/SyncParentPhones.php` | ~50 | Demo sync |
| `Exports/ReceiptsReportExport.php` | ~100 | Excel export |
| `Http/Controllers/DashboardController.php` | ~80 | Admin dashboard |
| `Http/Controllers/FeeStructureController.php` | ~80 | Fee CRUD |
| `Http/Controllers/NotificationLogController.php` | ~310 | Notification admin |
| `Http/Controllers/ParentDashboardController.php` | ~120 | Parent portal |
| `Http/Controllers/PaymentCategoryController.php` | ~70 | Category CRUD |
| `Http/Controllers/ProfileController.php` | ~50 | User profile |
| `Http/Controllers/ReceiptController.php` | ~200 | Receipt CRUD + PDF |
| `Http/Controllers/ReportController.php` | ~180 | Reports + export |
| `Http/Controllers/SettingController.php` | ~80 | School settings |
| `Http/Controllers/StudentController.php` | ~250 | Student CRUD + import |
| `Http/Controllers/Auth/*` (9 files) | ~50 each | Breeze auth |
| `Http/Middleware/EnsureRole.php` | ~30 | Role guard |
| `Http/Requests/Auth/LoginRequest.php` | ~120 | Multi-mode login |
| `Http/Requests/ProfileUpdateRequest.php` | ~30 | Profile validation |
| `Imports/StudentsImport.php` | ~30 | Bulk import |
| `Mail/FeeReminderMailable.php` | ~40 | Reminder email |
| `Models/*` (9 files) | 30–160 each | Eloquent models |
| `Notifications/PaymentReceivedNotification.php` | ~60 | Payment email |
| `Notifications/FeeReminderNotification.php` | ~40 | **Unused** |
| `Providers/AppServiceProvider.php` | ~65 | Boot + view composer |
| `Services/ParentReminderService.php` | ~320 | Notification orchestration |
| `Services/ParentPaymentNotifier.php` | ~20 | Receipt notify wrapper |
| `Services/SmsService.php` | 264 | iMart SMS integration |
| `Services/SmsSendResult.php` | ~45 | SMS result DTO |
| `Support/ParentStudentAdmission.php` | ~120 | Guardian linking |
| `View/Components/AppLayout.php` | ~15 | Layout component |
| `View/Components/GuestLayout.php` | ~15 | Guest layout component |

### `resources/views/` — 67 Blade files

Listed in Section 14. Key directories:
- `auth/` (6) — login, register, password flows
- `receipts/` (6) — receipt UI + PDF
- `students/` (5) — student management
- `notification-logs/` (7) — reminder admin
- `reports/` (4) — reporting
- `parents/` (3) — parent portal
- `components/` (17) — reusable UI pieces
- `layouts/` (5) — app, guest, sidebar, icons

### `database/migrations/` — 28 files
Full schema in Section 11.

### `tests/` — 18 files
Full coverage in Section 18.

### Root helpers
| File | Purpose |
|------|---------|
| `.htaccess` | Redirect to `public/` for XAMPP |
| `index.php` | Root redirect to public |
| `routes/web.php` | Main routes |
| `routes/auth.php` | Auth routes |
| `routes/console.php` | Schedule |
| `bootstrap/app.php` | App config + exception handlers |

---

## 20. Known Gaps & Technical Notes

1. **`FeeReminderNotification`** exists but is unused; **`FeeReminderMailable`** is the active email path.
2. **`classes` / `streams` tables** have no models; app uses free-text `class_name`.
3. **`Receipt` model** lacks `student()` BelongsTo relationship (column exists in DB).
4. **`receipt_payment_category` pivot** has no database foreign keys.
5. **Route ordering:** `/receipts/partial` and `/students/import` registered after resource routes (potential binding conflict).
6. **README vs seeder** demo emails differ slightly (`@school.tz` vs `@mbonea.sc.tz`).
7. **Queue** configured as `database` but notifications use synchronous `notifyNow()` / `Mail::send()`.
8. **SMS delivery:** Gateway may report failure before carrier delivers; system marks `sent` on API acceptance; admin can Mark delivered manually.
9. **No tests** for PDF/Excel export or receipt creation UI flow.
10. **`layouts/navigation.blade.php`** is orphaned Breeze legacy — not used.

---

## Quick Reference — Where to Find Things

| Need to change… | Look in… |
|-----------------|----------|
| SMS sending logic | `app/Services/SmsService.php` |
| Reminder content/timing | `app/Services/ParentReminderService.php` |
| Login behavior | `app/Http/Requests/Auth/LoginRequest.php` |
| Admin sidebar menu | `resources/views/layouts/partials/sidebar-nav.blade.php` |
| UI colors/theme | `public/css/school-theme.css` |
| School name/logo | Super Admin → Admin Settings OR `database/seeders/SettingSeeder.php` |
| Demo data | `database/seeders/DemoDataSeeder.php` |
| Routes | `routes/web.php` |
| Database schema | `database/migrations/` |
| Receipt number format | `app/Models/Receipt.php` → `generateScopedNo()` |
| Parent-student linking | `app/Support/ParentStudentAdmission.php` |
| Bank receipt parsing | `app/Services/BankReceiptParser.php` |
| Bank payment verification | `app/Services/BankPaymentVerificationService.php` |
| School bank accounts | Super Admin → Admin Settings |
| Exception handling | `bootstrap/app.php` |

---

*This document describes the complete FTRS system as of June 2026 (bank payment verification, expanded demo school, milestone SMS automation). For live source code, refer to the repository files listed above.*
