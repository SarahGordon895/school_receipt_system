# Mbonea FTRS — Complete System Documentation

**Fee Tracking & Receipt System (FTRS)** for Mbonea Secondary School  
**UDSM IS098 Project**  
**Repository:** https://github.com/SarahGordon895/school_receipt_system  
**Updated:** July 2026  
**Stack:** Laravel 12 · PHP 8.2+ · **MySQL** · Bootstrap 5 · DomPDF · Maatwebsite Excel · iMart SMS API · Vite (optional guest assets)

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
13. [Frontend — Layouts, Design & Responsiveness](#13-frontend--layouts-design--responsiveness)
14. [Frontend — Every Page & UI Action](#14-frontend--every-page--ui-action)
15. [Parent Portal vs Admin Portal](#15-parent-portal-vs-admin-portal)
16. [End-to-End Feature Flows](#16-end-to-end-feature-flows)
17. [Bursar Reports Suite](#17-bursar-reports-suite)
18. [SMS & Email Notification Rules](#18-sms--email-notification-rules)
19. [Configuration & Environment](#19-configuration--environment)
20. [Tests Coverage](#20-tests-coverage)
21. [File-by-File Inventory](#21-file-by-file-inventory)
22. [UML Diagrams](#22-uml-diagrams)
23. [Known Gaps & Technical Notes](#23-known-gaps--technical-notes)

---

## 1. Executive Summary

### What the system does

FTRS is a school fee management web application that lets **school administrators** and **super admins**:

- Register and manage students with official parent/guardian links (`student_parent_links`)
- Import students from Excel/CSV
- Define fee structures and payment categories (**super admin**)
- Generate numbered fee receipts (Cash, Bank, Mobile Money, Other)
- Run a full **bursar report suite** (collection, fee position, receipt register, unpaid, paid/clearance, SMS/email history, bank proofs) with HTML + PDF/Excel export
- Send **SMS** and **email** fee reminders (manual batch **1–5 parents**, or automated daily cron)
- Manage notification logs (view, resend, mark delivered, refresh gateway status)
- Review parent bank payment proof uploads (NMB/CRDB PDF)
- Configure school branding, SMS templates, and bank accounts (**super admin**)

**Parents** log in with **phone + password** and can:

- View **only their officially linked** children (fee balances, due dates)
- See payment history (read-only)
- Read notification messages and mark them as read
- **Pay via NMB/CRDB bank** — upload bank receipt PDF for automatic or bursar verification
- Download a **clearance certificate** when a child is fully paid

### Architecture at a glance

```
Browser (Bootstrap 5 + school-theme.css, mobile-first)
    ↓ HTTP (routes/web.php, routes/auth.php)
Middleware (auth, role:*, CSRF)
    ↓
Controllers (15 app + 9 Auth)
    ↓
Services (SMS, reminders, bank verify, reports, import, templates)
    ↓
Models (Eloquent) ↔ MySQL (school_receipts)
    ↓
External: iMart SMS API, SMTP mail, DomPDF, Excel export
```

---

## 2. How the System Runs

### Requirements

- PHP 8.2+ with extensions: `pdo_mysql`, `gd`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`
- Composer
- **MySQL 8+** (XAMPP MySQL on port 3306)
- Node.js (optional — only for `npm run build` Vite assets on login/guest pages)

### Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
# Start MySQL in XAMPP, then:
# CREATE DATABASE school_receipts CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
php artisan ftrs:install          # migrate + seed + storage:link
php artisan serve --host=127.0.0.1 --port=8088
```

Windows/XAMPP:

```cmd
C:\xampp\php\php.exe artisan ftrs:install
C:\xampp\php\php.exe artisan serve
```

Or via XAMPP document root: `http://localhost/school_receipt_system/public`

Helper script (macOS): `scripts/setup-mysql.sh`

### Optional frontend build (guest/login Vite assets)

```bash
npm install
npm run build   # creates public/build/manifest.json
```

If `manifest.json` is missing, login still works — guest layout skips `@vite` and uses Bootstrap + `school-theme.css` only.

### Scheduled tasks

| Command | Schedule | Purpose |
|---------|----------|---------|
| `fees:send-reminders` | Daily **06:00** | Automated SMS/email at **14, 7, 3, and 0 days** before due date + daily overdue notices |

Requires `php artisan schedule:work` or system cron pointing at `php artisan schedule:run`.

### Demo accounts (after seed)

| Role | Login | Password | Login tab |
|------|-------|----------|-----------|
| Super Admin | `sarahgeorge7224@gmail.com` | `Super@FTRS2025` | Super Admin |
| School Admin | `admin@mbonea.sc.tz` | `Mbonea@Admin2025` | School Admin |
| Parent (Mkumbo) | Phone `+255655139724` | `Mkumbo@2025` | Parent |
| Parent (Gordon) | Phone `+255755666899` | `Gordon@2025` | Parent |
| Parent (Chaula) | Phone `+255718216434` | `Chaula@2025` | Parent |
| Other demo parents | Showcase phones from seeder | `Parent@2025` | Parent |

After seed: large demo school across Forms I–IV (students, parents, receipts, notifications). Showcase contacts use **real phone numbers and Gmail addresses** cycling across the population (not `@mbonea.demo.tz` / fake `+255620…`).

---

## 3. Project Directory Map

```
school_receipt_system/
├── app/
│   ├── Console/Commands/       # ftrs:install, fees:send-reminders, ftrs:sync-parent-phones
│   ├── Data/                   # BankReceiptData DTO
│   ├── Exports/                # FeeCollectionReportExport, ReceiptsReportExport
│   ├── Http/
│   │   ├── Controllers/        # 15 app + Auth/*
│   │   ├── Middleware/         # EnsureRole
│   │   └── Requests/           # LoginRequest, BatchParentReminderRequest, ProfileUpdateRequest
│   ├── Imports/                # StudentsImport
│   ├── Mail/                   # FeeReminderMailable
│   ├── Models/                 # 10 Eloquent models
│   ├── Notifications/          # PaymentReceivedNotification, FeeReminderNotification
│   ├── Providers/              # AppServiceProvider
│   ├── Services/               # 14 service classes
│   ├── Support/                # ParentStudentAdmission, helpers.php
│   └── View/Components/
├── bootstrap/app.php
├── config/                     # includes notifications.php (batch 1–5), services.php (SMS)
├── database/
│   ├── migrations/             # 33 migration files
│   ├── seeders/                # Database, Setting, DemoData
│   └── factories/
├── docs/                       # This documentation + diagrams/
├── public/
│   ├── css/school-theme.css    # Responsive school theme
│   ├── css/bootstrap.min.css
│   └── icons/
├── resources/views/            # ~85 Blade templates
├── routes/web.php, auth.php, console.php
├── scripts/setup-mysql.sh
├── tests/                      # ~26 Feature/Unit test files
└── storage/
```

---

## 4. User Roles & Access Control

### Roles (`users.role`)

| Role | Who | Home route | Access |
|------|-----|------------|--------|
| `school_admin` | School bursar/staff | `/dashboard` | All **school operations**: receipts, students, import, reports, messages, notification logs, bank proof review |
| `super_admin` | System owner | `/dashboard` | **School operations + system setup**: fee structures, payment categories, Admin Settings (branding, SMS templates, bank accounts) |
| `parent` | Guardian | `/parent/dashboard` | Own linked children only (via `student_parent_links`) |

> **Important:** Super Admin is **not** “settings only”. Code places `role:school_admin,super_admin` on all school-ops routes. `User::canManageSchool()` returns true for both. Sidebar shows school ops **and** system setup for super admin.

### Role access matrix

| Capability | Parent | School Admin | Super Admin |
|------------|--------|--------------|-------------|
| Login | Phone + password | Email + password | Email + password |
| Profile | Yes | Yes | Yes |
| Parent portal (children, notifs, bank upload, clearance) | Yes | No | No |
| Receipts / Students / Import / Reports / Messages / Bank review / Notification logs | No | Yes | Yes |
| Fee structures / Payment categories / Settings | No | No | Yes |

### Authorization mechanisms

1. `EnsureRole` middleware (`role:parent`, `role:school_admin,super_admin`, `role:super_admin`)
2. Custom route binding in `AppServiceProvider` for `{student}` and `{log}` — parents get **403** on foreign records
3. `Student::scopeForParent` / `belongsToParent` / `ParentStudentAdmission::parentOwnsStudent()`
4. Controllers call `$request->user()->canManageSchool()` where needed (e.g. batch reminder FormRequest)

### Login (`LoginRequest`)

- **Parents:** phone + password; multiple portal accounts may share one phone — password disambiguates
- **Admins:** email + password filtered by `login_type` (school_admin / super_admin)
- Rate limit: **5 attempts** per phone/email + IP

---

## 5. Complete Route Reference

### Public

| Method | Path | Name | Handler |
|--------|------|------|---------|
| GET | `/` | — | Redirect to user home or login |
| GET | `/up` | — | Health check (`bootstrap/app.php`) |

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

| Method | Path | Name |
|--------|------|------|
| GET/PATCH/DELETE | `/profile` | `profile.edit/update/destroy` |

### School ops (`auth` + `role:school_admin,super_admin`)

| Area | Routes |
|------|--------|
| Dashboard | GET `/dashboard` |
| Receipts | resource `index,create,store,show,edit,update,destroy`; `GET /receipts/partial`; `GET /receipts/{receipt}/pdf` |
| Students | resource except show; `GET/POST /students/import`; `GET /students/import/result`; `GET /students/{student}/clearance-certificate`; `GET /api/students`; `POST /students/{student}/send-reminder` |
| Messages | GET `/messages` — SMS/email centre |
| Reports hub | GET `/reports` |
| Fee position | GET `/reports/fee-position`, `/reports/fee-position/pdf` |
| Receipt register | GET/POST `/reports/receipts`, POST `/reports/receipts/pdf` |
| Unpaid | GET `/reports/unpaid`, `/reports/unpaid/pdf`; POST `/reports/unpaid/send-reminders` |
| Paid / clearance | GET `/reports/paid` (redirect → clearance); GET `/reports/clearance`, `/reports/clearance/pdf` |
| Message history report | GET/POST `/reports/messages`, POST `/reports/messages/pdf` |
| Bank proof report | GET/POST `/reports/bank-proofs`, POST `/reports/bank-proofs/pdf` |
| Collection report | POST `/reports/generate`, `/reports/export/excel`, `/reports/export/pdf` |
| Notification logs | resource CRUD; `GET/POST /notification-logs/send`; resend, refresh-status, mark-delivered |
| Bank payments (bursar) | GET index/show; download; POST approve/reject |

### Super admin only (`auth` + `role:super_admin`)

| Area | Routes |
|------|--------|
| Fee structures | resource except show |
| Payment categories | resource except show |
| Settings | GET `/settings`, PUT `/settings` |

### Parent (`auth` + `role:parent`)

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

### School / bursar controllers

| Controller | Key methods |
|------------|-------------|
| `DashboardController` | `__invoke` — KPIs, modes, recent receipts, top classes |
| `ReceiptController` | CRUD, `partial`, `pdf`; on store → `ParentPaymentNotifier` |
| `StudentController` | CRUD, `search`, `importForm/Store/Result`; links guardian via `ParentStudentAdmission` |
| `ReportController` | Full bursar suite (see §17), `sendReminders` via `BatchParentReminderRequest` |
| `MessageController` | `index` — Message Centre (templates legend, stats, automation) |
| `NotificationLogController` | CRUD, `sendCreate`/`sendStore` (batch 1–5), `sendToStudent`, `resend`, `refreshStatus`, `markDelivered` |
| `BankPaymentSubmissionController` | `index`, `show`, `approve`, `reject`, `download` |
| `ClearanceCertificateController` | DomPDF when student fully paid |
| `FeeStructureController` | CRUD (super admin) |
| `PaymentCategoryController` | CRUD (super admin) |
| `SettingController` | `edit`/`update` branding, SMS, bank accounts, optional test SMS |
| `ProfileController` | `edit`/`update`/`destroy` |

### Parent controllers

| Controller | Key methods |
|------------|-------------|
| `ParentDashboardController` | Dashboard, student history, notifications inbox, mark read |
| `ParentBankPaymentController` | List/upload bank receipt PDF |

### Auth controllers (9)

Breeze: `AuthenticatedSessionController`, `RegisteredUserController`, password reset/confirm/update, email verification.

---

## 7. Backend — Models

### `User` — `app/Models/User.php`

| Item | Detail |
|------|--------|
| Fields | name, email (unique), phone (nullable, **not unique**), password, role |
| Role helpers | `hasRole()`, `isParent()`, `isSchoolAdmin()`, `isSuperAdmin()`, **`canManageSchool()`** |
| Accessors | `normalized_role`, `home_route` (parent → parent.dashboard; else dashboard), `login_identifier` |
| Static | `normalizePhone()` — TZ formats to +255… |
| Relations | `parentStudents()` via `student_parent_links`; `admittedStudents()` |

### `Student` — `app/Models/Student.php`

| Item | Detail |
|------|--------|
| Fields | admission_no, name, class_name, parent_*, parent_user_id, fee_due_date, expected_total_fee, admitted_at, registered_by_user_id |
| Accessors | `paid_amount`, `expected_amount` (fee structures sum OR fallback), `balance` |
| Methods | `isFullyPaid()`, `hasOutstandingBalance()`, `resolveParentPhone()`, `resolveParentEmail()`, `hasParentContact()`, `belongsToParent()`, `scopeForParent` |
| Relations | parentUser, registeredBy, parentLinks, primaryParentLink, guardians, receipts, feeStructures, notificationLogs |

### `Receipt` — `app/Models/Receipt.php`

| Item | Detail |
|------|--------|
| Fields | receipt_no, student_id, snapshots, amount, payment_date (**cast as date**), payment_mode, reference, note, user_id |
| Boot | Auto `receipt_no` via `ReceiptCounter` (`RCPT-{year}-T{n}-{seq}`) |
| Methods | `syncPaymentCategories()`, `generateScopedNo()` |
| Relations | **`student()`**, `user()`, `paymentCategories()` pivot with amount |

### `NotificationLog` — `app/Models/NotificationLog.php`

| Item | Detail |
|------|--------|
| Fields | student_id, channel (`sms`/`email`), **event_type**, status, sent_on, message, gateway_uid, delivery_status, read_at |
| Statuses | sent, failed, skipped |
| Methods | `isResolvableFailure()`, `statusLabel()`, `statusBadge()` |

### `BankPaymentSubmission` — `app/Models/BankPaymentSubmission.php`

| Item | Detail |
|------|--------|
| Fields | parent_user_id, student_id, file paths, bank (`nmb`/`crdb`), extracted_*, status, verification_message, receipt_id, reviewed_by_user_id, reviewed_at |
| Statuses | pending, verified, review, rejected |
| Relations | parentUser, student, receipt, reviewedBy |
| Helpers | `statusLabel()`, `statusBadge()`, `bankLabel()` |

### Other models

| Model | Purpose |
|-------|---------|
| `Setting` | Singleton school config; `current()` caches as `app_setting`; `forgetCache()`; `smsConfig()` |
| `FeeStructure` | Class fee templates → M2M students |
| `PaymentCategory` | Tuition/transport etc. |
| `ReceiptCounter` | Atomic year/term sequence |
| `StudentParentLink` | Official parent↔student link (relationship, is_primary, phones) |

### DTO

| Class | Purpose |
|-------|---------|
| `App\Data\BankReceiptData` | Parsed bank PDF fields |

---

## 8. Backend — Services

| Service | Purpose |
|---------|---------|
| `SmsService` | Send via iMart; simulate/disabled/failed paths; `checkDelivery`; normalize phone to 255… |
| `SmsSendResult` | Result DTO |
| `ParentReminderService` | `runAutomatedReminders`, `notifyPayment`, `notifyAdmission`, `sendFeeReminder`, **`sendBatchToStudents` (1–5)**, `resendLog`, `summarizeSendResults` |
| `ParentPaymentNotifier` | Thin wrapper → `notifyPayment` after receipt create |
| `NotificationTemplateService` | Event types + placeholders (`{student_name}`, `{balance}`, `{due_date}`, etc.); `manualSendEventTypes()`; resolve milestone from student |
| `BankReceiptParser` | Parse NMB/CRDB PDF text (smalot/pdfparser) |
| `BankPaymentVerificationService` | Validate amount/account/ref/date; auto-create receipt or queue for review |
| `StudentImportService` | Excel/CSV row import orchestration |
| `FeeCollectionReportService` | Period collection report + date presets |
| `SchoolFeePositionReportService` | Expected / paid / balance / status grid |
| `ReceiptRegisterReportService` | Official receipt register |
| `TermClearanceReportService` | Fully paid students + `clearanceReference()` |
| `MessageHistoryReportService` | SMS/email history from `notification_logs` |
| `BankPaymentReportService` | Bank proof submissions report |

### SMS send decision tree (`SmsService::send`)

1. SMS disabled in settings → `skipped`  
2. Simulate ON → log only → `skipped`  
3. Missing API config → `failed`  
4. HTTP POST to iMart → API accept → `sent` (optional delivery poll)  
5. Result → caller writes `notification_logs`

---

## 9. Backend — Commands, Mail, Notifications

### Artisan commands

| Command | Purpose |
|---------|---------|
| `ftrs:install {--fresh}` | Migrate (+fresh), seed, storage:link, print demo logins |
| `fees:send-reminders {--milestone=}` | Run automated milestone + overdue reminders |
| `ftrs:sync-parent-phones` | Sync demo parent phones / contacts |

### Mail & Laravel notifications

| Class | Status |
|-------|--------|
| `FeeReminderMailable` | **Active** — fee reminder emails |
| `PaymentReceivedNotification` | **Active** — payment confirmation emails |
| `FeeReminderNotification` | Unused legacy (replaced by Mailable) |

### Exports / Imports

| Class | Purpose |
|-------|---------|
| `FeeCollectionReportExport` | Excel collection report |
| `ReceiptsReportExport` | Excel receipts report |
| `StudentsImport` | Maatwebsite ToArray helper for import |

---

## 10. Backend — Middleware, Requests, Support

### `EnsureRole`

- Auth required + one of allowed roles  
- Wrong portal: parent → `parent.dashboard`; school manager → `dashboard`  
- Else 403  

### Form requests

| Request | Rules |
|---------|-------|
| `LoginRequest` | Role-specific auth + rate limit |
| **`BatchParentReminderRequest`** | `student_ids` **min 1 / max 5**; message_type; at least one of SMS/email; authorize via `canManageSchool()` |
| `ProfileUpdateRequest` | Name/email unique |

### `AppServiceProvider`

- `Schema::defaultStringLength(191)`  
- Bootstrap 5 pagination  
- Custom `{student}` / `{log}` bindings for parent scope  
- View composer: `$appSetting`, `$parentUnreadNotifications`  

### `bootstrap/app.php`

- Alias `role` → `EnsureRole`  
- Health `/up`  
- Missing `NotificationLog` → friendly redirect (not raw 404)  

### Support helpers

| File | Purpose |
|------|---------|
| `ParentStudentAdmission` | linkGuardian, sync primary, parentOwnsStudent, portal email update |
| `app/Support/helpers.php` | `format_tzs()` and shared helpers |

---

## 11. Database — Complete Schema

**Default connection:** MySQL, database `school_receipts`, charset `utf8mb4`.

### Domain tables

#### `users`
id, name, email (unique), phone (nullable, **not unique**), password, role (`super_admin`|`school_admin`|`parent`), email_verified_at, remember_token, timestamps

#### `students`
id, admission_no (unique), name, class_name, parent_name/phone/email, parent_user_id (FK), fee_due_date, expected_total_fee, admitted_at, registered_by_user_id (FK)

#### `student_parent_links`
student_id + parent_user_id (unique pair), relationship, is_primary, parent_phone, linked_by_user_id, linked_at

#### `receipts`
receipt_no (unique), student_id, snapshots, amount (unsigned bigint TZS), payment_date, payment_mode enum, reference, note, user_id, legacy payment_category_id

#### `receipt_payment_category` (pivot)
receipt_id, payment_category_id, amount

#### `payment_categories`
name (unique), default_amount

#### `fee_structures`
name, class_name, amount, due_date, is_active

#### `fee_structure_student` (pivot)
fee_structure_id, student_id

#### `receipt_counters`
year, term (unique pair), current

#### `notification_logs`
student_id, channel, **event_type**, status, sent_on, message, gateway_uid, delivery_status, read_at  
Indexes on (student_id, channel, sent_on), (status, sent_on), gateway_uid

#### `bank_payment_submissions`
parent_user_id, student_id, original_filename, file_path, bank, extracted_amount/reference/payment_date/account_number/raw_text, status, verification_message, receipt_id, reviewed_by_user_id, reviewed_at

#### `settings` (singleton)
Branding: school_name, contacts, address, reg_number, logo_path, receipt_footer  
SMS: sms_enabled, sms_simulate, api endpoint/token/sender_id  
Templates: sms_template_payment_received, fee_reminder, fee_reminder_14, overdue  
Banks: bank_nmb_account_name/number, bank_crdb_account_name/number

### Legacy unused tables
`classes`, `streams` — migrated but **no Eloquent models**; app uses free-text `class_name`.

### Laravel infrastructure
`sessions`, `password_reset_tokens`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`

### Migration count: **33 files**
From `0001_01_01_*` through `2026_06_20_100100_create_bank_payment_submissions_table.php` (includes SMS templates, event_type, bank accounts).

---

## 12. Database — Seeders & Demo Data

### `DatabaseSeeder`
1. `SettingSeeder`  
2. Backfill null roles → `school_admin`  
3. Upsert Super Admin + School Admin  
4. `DemoDataSeeder`  

### `SettingSeeder`
Mbonea branding, Swahili SMS templates, NMB/CRDB demo accounts; `sms_simulate` preferred when no live token.

### `DemoDataSeeder`
- Payment categories + fee structures  
- Showcase parents (Mkumbo, Gordon, Chaula, …) with known passwords  
- School population Forms I–IV; siblings may share a parent  
- Receipts (Cash / Bank / Mobile Money); fully paid / partial / overdue mix  
- **`SHOWCASE_PHONES` (10)** and **`SHOWCASE_NOTIFICATION_EMAILS` (4)** applied across contacts via `syncRealContactsThroughoutDatabase()`  

Default other-parent password: `Parent@2025`.

---

## 13. Frontend — Layouts, Design & Responsiveness

### Authenticated layout — `layouts/app.blade.php`

```
┌─────────────────────────────────────────────┐
│ Fixed navbar (brand, user, page actions)    │
├──────────┬──────────────────────────────────┤
│ Sidebar  │ Main: flash alerts + content     │
│ (fixed)  │                                  │
└──────────┴──────────────────────────────────┘
(+ Parent bottom nav on ≤ lg)
```

**Assets:** Bootstrap CSS, Bootstrap Icons, `school-theme.css` (no Vite required).

**Design tokens:** primary `#0f3d2e`, gold `#b8860b`, surface `#f0f4f2`, font Plus Jakarta Sans.

### Guest layout — `layouts/guest.blade.php`

Split brand panel + auth card. Uses Bootstrap + `school-theme.css`.  
**Vite is optional:** only loads if `public/build/manifest.json` exists (prevents `ViteManifestNotFoundException`).

### Responsive behaviour (implemented)

| Feature | Implementation |
|---------|----------------|
| Mobile sidebar | Hamburger `#sidebarToggle` (`d-lg-none`) |
| Parent bottom nav | `layouts/partials/parent-mobile-nav.blade.php` — Dashboard / Notifications / Bank / Profile |
| Mobile data cards | `.mobile-card-list` / `.mobile-data-card` — show on small screens; tables use `.table-responsive` / desktop-only variants |
| Touch targets | Min heights on controls in `school-theme.css` |
| Stacked toolbars | Page actions wrap on tablet/phone |
| Parent portal body | `.parent-portal-body` spacing above bottom nav |

### Sidebar menus

**School Admin:** Dashboard, Receipts, Students, Reports, Messages, Notifications, Bank Payments  
**Super Admin:** all of the above + Fee Structures, Payment Categories, Admin Settings  
**Parent:** Parent Portal, Bank Payments, My Notifications (unread badge), My Profile + mobile bottom nav

### Blade components

`icon-btn`, `table-actions`, `form-actions`, plus Breeze auth components under `components/`.

---

## 14. Frontend — Every Page & UI Action

### Auth
Login (role tabs), register, forgot/reset password, verify email, confirm password — `resources/views/auth/*`

### Admin dashboard
KPI cards, payment modes, top classes, recent receipts — links to receipt/student/reports/unpaid

### Receipts
index (filters), create (student typeahead + category rows), show (thermal print `?print=1`), edit, PDF download

### Students
index (search, send reminder, clearance PDF), create/edit (parent fields, fee structures), import + result page

### Fee structures / Payment categories
CRUD forms (super admin)

### Reports hub & suite
See §17 — index shortcuts + filter forms + HTML results + PDF exports

### Messages centre — `messages/index.blade.php`
Template overview, monthly SMS/email stats, automation schedule legend, link to send (1–5) and logs

### Notification logs
Stat cards, filters, manual log CRUD, send form (checkboxes max 5), resend/refresh/mark delivered

### Bank payments (bursar)
Status filters, show extracted data, approve/reject, download proof PDF

### Settings (super admin)
School info, logo, receipt footer, SMS enable/simulate/API/sender, Swahili templates, NMB/CRDB accounts, test SMS

### Parent portal
Dashboard (balances + CTAs), student history, notifications inbox, bank payments upload + history, clearance certificate download

---

## 15. Parent Portal vs Admin Portal

| Feature | School Admin | Super Admin | Parent |
|---------|--------------|-------------|--------|
| Login | Email | Email | Phone |
| Home | `/dashboard` | `/dashboard` | `/parent/dashboard` |
| Data scope | All school | All school + settings | **Linked children only** |
| Create receipts | Yes | Yes | No |
| Upload bank PDF | No (reviews) | No (reviews) | Yes |
| Reports / Messages | Yes | Yes | No |
| Send SMS batch 1–5 | Yes | Yes | No |
| Fee structures / Settings | No | Yes | No |
| Clearance certificate | Yes (any student) | Yes | Only own fully paid children |

---

## 16. End-to-End Feature Flows

### A. Record a payment (receipt)

```
Admin → Receipts → Create
  → Select student (api.students.search)
  → Categories + amounts + mode
  → ReceiptController@store
    → receipt_no via ReceiptCounter
    → syncPaymentCategories()
    → ParentPaymentNotifier → SMS + email → notification_logs
  → receipts.show?print=1
```

### B. Manual / template reminder (batch 1–5)

```
Admin → Messages or Notification Logs → Send
   OR Unpaid report → select checkboxes
  → BatchParentReminderRequest (min 1, max 5; ≥1 channel)
  → ParentReminderService::sendBatchToStudents()
  → Per student: template render → SMS/email → notification_logs
```

### C. Automated milestones (cron)

```
06:00 → fees:send-reminders
  → runAutomatedReminders()
  → Exact 14 / 7 / 3 / 0 days before due + overdue daily
  → Skip fully paid; dedup by event_type
```

### D. Parent bank payment

```
Parent → Bank Payments → select child → upload PDF
  → BankReceiptParser → BankPaymentVerificationService
  → verified: create Receipt (Bank) + notify
  → review: bursar Approves/Rejects
  → rejected: show error
```

### E. Clearance certificate

```
Fully paid student → ClearanceCertificateController
  → DomPDF certificates/paid-in-full
  → Parent or bursar download
```

### F. Student import

```
Admin → Students → Import (.xlsx/.csv)
  → StudentImportService → results page
```

### G. Resend failed SMS

```
Notification Logs → Resend
  → resendLog() → SmsService → update same log row
```

---

## 17. Bursar Reports Suite

All built from **live DB data** (receipts, fee structures, notification_logs, bank_payment_submissions).

| Report | Route name(s) | Output |
|--------|---------------|--------|
| Hub + fee collection filters | `reports.index` | HTML form → generate / Excel / PDF |
| Full fee position | `reports.fee-position` (+ `.pdf`) | Expected / paid / balance / status |
| Receipt register | `reports.receipts` (+ `.pdf`) | Period receipt list |
| Unpaid balances | `reports.unpaid` (+ `.pdf`) | Outstanding + send reminders 1–5 |
| Paid / term clearance | `reports.paid` → `reports.clearance` (+ `.pdf`) | Fully paid list + per-student certificates |
| SMS & email history | `reports.messages` (+ `.pdf`) | From notification_logs |
| Bank payment proofs | `reports.bank-proofs` (+ `.pdf`) | Submissions + verified amounts |

Services: `FeeCollectionReportService`, `SchoolFeePositionReportService`, `ReceiptRegisterReportService`, `TermClearanceReportService`, `MessageHistoryReportService`, `BankPaymentReportService`.

---

## 18. SMS & Email Notification Rules

### Config — `config/notifications.php`

```php
'min_batch_parents' => 1,
'max_batch_parents' => 5,
```

### Manual / bursar-triggered sends

- UI: checkboxes; cannot select more than 5  
- Server: `BatchParentReminderRequest` enforces **min 1 / max 5**  
- Channels: SMS and/or email — at least one required  
- Template selected or auto-resolved from student milestone  
- Single-student send (`students.send-reminder`) = batch of 1  

### Automated cron

- Full school scan for matching milestones (not limited to 5)  
- Templates: `fee_reminder_14`, fee reminder variants, `overdue`  
- Stored under Admin Settings (editable Swahili templates)

### Placeholders (examples)

`{student_name}`, `{amount}`, `{balance}`, `{due_date}`, `{days_until_due}`, `{receipt_no}`, `{school_name}`

### Event types on logs

Used for automation dedup and history reports (e.g. payment confirmation vs milestone reminder).

---

## 19. Configuration & Environment

### `.env.example` essentials

| Variable | Default / purpose |
|----------|-------------------|
| `DB_CONNECTION` | **`mysql`** |
| `DB_HOST` / `DB_PORT` | 127.0.0.1 / 3306 |
| `DB_DATABASE` | `school_receipts` |
| `DB_USERNAME` / `DB_PASSWORD` | root / (empty for default XAMPP) |
| `SESSION_DRIVER` | database |
| `CACHE_STORE` | **database** (Setting also cached 1h as `app_setting`) |
| `QUEUE_CONNECTION` | database (notifications currently sync) |
| `MAIL_*` | SMTP (e.g. Gmail) |
| `SMS_DRIVER` | imart |
| `SMS_API_ENDPOINT` / `SMS_API_TOKEN` / `SMS_SENDER_ID` | iMart credentials |
| `APP_URL` | http://127.0.0.1:8088 |

### Admin UI overrides (`settings` table)

SMS enable/simulate/API/templates and NMB/CRDB accounts without editing `.env`.

### `config/services.php` SMS block

Driver, endpoint, token, sender_id from env (overridden by DB when set).

---

## 20. Tests Coverage

**~26 test files** under `tests/Feature` and `tests/Unit`.

| Area | Test files |
|------|------------|
| Auth | Authentication, Registration, Password*, EmailVerification, Profile |
| Roles | RoleSeparationTest, SettingControllerTest |
| Parent scope | ParentPortalScopeTest, ParentNotificationsTest |
| SMS / reminders | ManualParentReminderTest, AutomatedReminderTest, SmsServiceTest, MessageCentreTest, NotificationLogControllerTest |
| Bank payments | BankPaymentSubmissionTest |
| Reports | BursarGeneratedReportsTest, FeeCollectionReportTest, TermClearanceReportTest, FullyPaidStudentTest |
| Students | StudentUpdateTest, StudentImportTest |
| Install | InstallFtrsCommandTest |

Support trait: `tests/Support/AdmitsStudents.php`  
PHPUnit uses **SQLite in-memory** (`phpunit.xml`) so tests run without MySQL.

---

## 21. File-by-File Inventory

### Controllers (`app/Http/Controllers/`)

Dashboard, Receipt, Student, Report, Message, NotificationLog, BankPaymentSubmission, ParentDashboard, ParentBankPayment, ClearanceCertificate, FeeStructure, PaymentCategory, Setting, Profile + `Auth/*`

### Services (`app/Services/`) — 14

SmsService, SmsSendResult, ParentReminderService, ParentPaymentNotifier, NotificationTemplateService, BankReceiptParser, BankPaymentVerificationService, StudentImportService, FeeCollectionReportService, SchoolFeePositionReportService, ReceiptRegisterReportService, TermClearanceReportService, MessageHistoryReportService, BankPaymentReportService

### Models — 10

User, Student, Receipt, NotificationLog, BankPaymentSubmission, Setting, FeeStructure, PaymentCategory, ReceiptCounter, StudentParentLink

### Views — ~85 Blade files

auth, bank-payments, certificates, dashboard, emails, fee-structures, messages, notification-logs, parents, payment_categories, profile, receipts, reports (HTML + PDF templates), settings, students, layouts, components

### Diagrams — `docs/diagrams/`

PlantUML + PNG/SVG + Word-style hand-drawn SVGs (use case, activity, class, sequence, ER). See §22.

---

## 22. UML Diagrams

| Location | Contents |
|----------|----------|
| `docs/diagrams/png/` | Rendered PlantUML diagrams |
| `docs/diagrams/word-style/png/` | Clean Word-import style diagrams |
| `docs/diagrams/FTRS-Diagrams-Word-Import.zip` | Bundle |
| `docs/diagrams/FTRS-Word-Style-Diagrams.zip` | Clean-style bundle |

Covered: Use Case, Activity (admin payment + parent bank), Class, Sequence (payment / bank / reminders), ER Diagram.

---

## 23. Known Gaps & Technical Notes

1. **`FeeReminderNotification`** exists but is unused; **`FeeReminderMailable`** is the active email path.  
2. **`classes` / `streams` tables** have no models; app uses free-text `class_name`.  
3. **`receipt_payment_category` pivot** may lack DB-level FKs depending on migration history.  
4. **README demo credentials** may still list older `@school.tz` / `password` — **seeders are authoritative** (§2).  
5. **Queue** is `database` but SMS/email typically run synchronously.  
6. **SMS delivery:** API acceptance → `sent`; carrier lag possible — admin can Mark delivered / refresh status.  
7. **Vite** optional for guest pages; dashboard does not depend on Vite.  
8. **XAMPP PHP gd extension** required for Excel (PhpSpreadsheet) on Windows.  
9. **Composer on Windows:** use `C:\xampp\php\php.exe` + Composer path; prefer CMD if PowerShell blocks `npm.ps1`.  
10. **Shared parent phones** intentional (unique dropped); login disambiguates by password.  
11. Fixed/obsolete doc items since earlier revisions: Receipt now has `student()` relation; MariaDB references replaced by MySQL; super admin school-ops access documented correctly; bursar report suite + batch 1–5 documented.

---

## Quick Reference — Where to Find Things

| Need to change… | Look in… |
|-----------------|----------|
| SMS sending | `app/Services/SmsService.php` |
| Reminder timing / batch send | `app/Services/ParentReminderService.php` |
| Batch 1–5 validation | `config/notifications.php`, `BatchParentReminderRequest` |
| Login | `app/Http/Requests/Auth/LoginRequest.php` |
| Sidebar / mobile nav | `resources/views/layouts/partials/sidebar-nav.blade.php`, `parent-mobile-nav.blade.php` |
| Theme / responsive CSS | `public/css/school-theme.css` |
| Settings / templates / banks | Super Admin → Settings OR `SettingSeeder` |
| Demo data / phones | `database/seeders/DemoDataSeeder.php` |
| Routes | `routes/web.php` |
| Schema | `database/migrations/` |
| Receipt numbers | `app/Models/Receipt.php` |
| Parent linking / ownership | `app/Support/ParentStudentAdmission.php` |
| Bank parse / verify | `BankReceiptParser`, `BankPaymentVerificationService` |
| Bursar reports | `ReportController` + `app/Services/*ReportService.php` |
| Exceptions / role middleware | `bootstrap/app.php`, `EnsureRole` |

---

*This document describes the complete FTRS system as of July 2026: MySQL, full bursar reports, batch SMS/email (1–5), bank payment verification, parent scoping, responsive UI, super-admin full access, and live-code inventory. For source of truth, prefer the repository files referenced above.*
