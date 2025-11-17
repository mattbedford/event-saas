# Laravel Starter Kit

A clean, minimal Laravel starter with authentication but without the modern JS framework bloat.

## What's Included

- ✅ Laravel 11.x
- ✅ Breeze authentication (login, password reset, profile management)
- ✅ Compiled Tailwind CSS + Alpine.js (no build process required)
- ✅ Registration disabled (add users via Tinker)
- ✅ All routes protected behind authentication
- ✅ SQLite for development, MySQL for production
- ✅ Brevo SMTP email configuration

## What's NOT Included

- ❌ Node.js / npm dependencies
- ❌ Vite / build process
- ❌ Public registration
- ❌ Inertia / Vue / React

## Quick Start

### 1. Clone and Install
```bash
git clone <your-repo-url> my-new-project
cd my-new-project
composer install
cp .env.example .env
php artisan key:generate
```

### 2. Database Setup

**For local development (SQLite - default):**
```bash
touch database/database.sqlite
php artisan migrate
```

**For production (MySQL):**
Edit `.env` and update:
```env
DB_CONNECTION=mysql
DB_HOST=your-host
DB_DATABASE=your-database
DB_USERNAME=your-username
DB_PASSWORD=your-password
```

Then run:
```bash
php artisan migrate
```

### 3. Create Your First User
```bash
php artisan tinker
```

Then in Tinker:
```php
User::create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'password' => Hash::make('your-secure-password')
]);
```

### 4. Email Configuration (Production)

Update `.env` with your Brevo credentials:
```env
MAIL_MAILER=smtp
MAIL_USERNAME=your-brevo-email@example.com
MAIL_PASSWORD=your-brevo-smtp-key
MAIL_FROM_ADDRESS=noreply@yourdomain.com
```

## Development
```bash
php artisan serve
```

Visit `http://localhost:8000` - you'll be redirected to login.

## Deployment

This starter works great with:
- Hidora/Jelastic environments
- AWS (with RDS for MySQL)
- Any VPS with PHP 8.2+ and MySQL/SQLite

## Adding New Users

Use Tinker (as shown above) or create an admin panel as needed for your project.

## Philosophy

This starter prioritizes:
- **Stability** over cutting-edge
- **Simplicity** over features
- **Security** by default (no public routes)
- **No build process** required

Built for internal tools, admin panels, and microservices - not public-facing marketing sites.

## Security Features

- All routes protected behind authentication by default
- Google reCAPTCHA v3 on login (invisible, score-based)
- Password reset functionality included
- Email verification ready (if needed)

### Adding Two-Factor Authentication

This starter doesn't include 2FA by default (YAGNI principle). When you need it:
- For email/SMS codes: Consider [Laravel Fortify](https://laravel.com/docs/fortify)
- For TOTP (Google Authenticator): Consider [pragmarx/google2fa-laravel](https://github.com/antonioribeiro/google2fa)
