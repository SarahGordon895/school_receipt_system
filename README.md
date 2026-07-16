# Web Based Fee Tracking & Reminder System (FTRS)

Laravel application for **Mbonea Secondary School** (UDSM IS098 project): track student fees, record payments, notify parents via SMS/email, and generate reports.

## Requirements

- PHP 8.2+
- Composer
- MySQL 8+ (XAMPP MySQL on port 3306)
- Node.js (optional, for Vite assets on auth pages)

## Quick install (Windows / XAMPP)

1. Copy `.env.example` to `.env` and set database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school_receipts
DB_USERNAME=root
DB_PASSWORD=
```

2. Generate app key and install:

```cmd
composer install
php artisan key:generate
install.cmd
```

Or without the batch file:

```cmd
php artisan ftrs:install
```

For a clean database:

```cmd
install.cmd --fresh
```

3. Start the server:

```cmd
serve.cmd
```

Open http://127.0.0.1:8088 (this project uses port **8088** to avoid clashing with other Laravel apps on 8000)

## Demo accounts

| Role | Email | Password |
|------|-------|----------|
| Super Admin | superadmin@school.tz | password |
| School Admin | admin@school.tz | password |
| Parent (1 child — Mkumbo) | parent.mkumbo@school.tz | password |
| Parent (1 child — Gordon) | parent.gordon@school.tz | password |
| Parent (1 child — Chaula) | parent.chaula@school.tz | password |

Demo data includes students, fee structures, sample receipts, and outstanding balances.

## Main features

- **Students** — register, import, link fee structures
- **Fee structures** — class-based expected fees and due dates
- **Receipts** — multi-category payments, PDF/print
- **Reports** — paid/unpaid, Excel/PDF export
- **Notifications** — email + SMS on payment; scheduled fee reminders
- **Parent portal** — balances, payment history, notifications
- **Settings** — school profile, receipt footer, SMS API (or simulate mode)

## SMS configuration

1. Admin → **Settings** → SMS Notifications
2. Enable SMS; use **Simulate SMS** on localhost (messages logged to `storage/logs/laravel.log`)
3. For production: set API endpoint, token, sender ID (or use `.env` `SMS_*` variables)

Send reminders manually: **Reports → Unpaid report → Send reminders**

Automatic reminders (daily 07:00):

```cmd
php artisan schedule:work
```

Or one-off:

```cmd
php artisan fees:send-reminders --days=3
```

## Tests

```cmd
php artisan test
```

## Project structure

- `app/Http/Controllers` — admin, parent, reports
- `app/Services/SmsService.php` — SMS with settings + simulate mode
- `app/Console/Commands/SendFeeReminders.php` — reminder job
- `database/seeders/DemoDataSeeder.php` — demo walkthrough data
- `resources/views` — Bootstrap UI with icon actions

## License

MIT (Laravel framework). Project work: UDSM CoICT — IS098 2024/2025.
