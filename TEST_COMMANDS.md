# 🚀 Quick Test Commands Reference

Copy and paste these commands to run tests.

## Basic Commands

```bash
# Run all tests
php artisan test

# Run tests in parallel (faster!)
php artisan test --parallel

# Stop on first failure
php artisan test --stop-on-failure

# Show detailed output
php artisan test --verbose
```

## Run Specific Tests

```bash
# Run single test file
php artisan test tests/Feature/Services/RegistrationServiceTest.php

# Run specific test method
php artisan test --filter=it_creates_registration_with_percentage_coupon

# Run all service tests
php artisan test tests/Feature/Services

# Run all model tests
php artisan test tests/Feature/Models
```

## Coverage Reports

```bash
# Basic coverage (terminal)
php artisan test --coverage

# Minimum coverage threshold (fail if below 60%)
php artisan test --coverage --min=60

# HTML coverage report (detailed)
php artisan test --coverage-html coverage-report
# Then open: coverage-report/index.html

# Coverage for specific directory
php artisan test tests/Feature/Services --coverage
```

## Debugging Tests

```bash
# Show all SQL queries
php artisan test --verbose

# Single test with full output
php artisan test --filter=test_name --verbose

# Don't use cache
php artisan test --no-cache
```

## Useful Combinations

```bash
# Fast feedback during development
php artisan test --stop-on-failure --parallel

# Before committing
php artisan test --coverage --min=60

# Debugging single test
php artisan test --filter=my_test --verbose --stop-on-failure

# CI/CD simulation
php artisan test --parallel --coverage --min=60
```

## Watch Mode (Auto-run on file changes)

Install phpunit-watcher:
```bash
composer require --dev spatie/phpunit-watcher
```

Then:
```bash
# Watch and auto-run tests
phpunit-watcher watch

# Watch specific directory
phpunit-watcher watch --filter=Services
```

## Pre-Commit Hook

Add to `.git/hooks/pre-commit`:
```bash
#!/bin/sh
php artisan test --stop-on-failure || exit 1
```

Make executable:
```bash
chmod +x .git/hooks/pre-commit
```

## Test Database

```bash
# Reset test database
php artisan migrate:fresh --env=testing

# Seed test database
php artisan db:seed --env=testing
```

## Common Issues & Fixes

```bash
# "Class not found"
composer dump-autoload

# "Database locked"
php artisan cache:clear
php artisan config:clear

# "Too many connections"
# Use --parallel with lower process count
php artisan test --parallel --processes=4
```

## Test Specific Features

```bash
# Registration flow
php artisan test --filter=Registration

# Coupon logic
php artisan test --filter=Coupon

# Payment processing
php artisan test --filter=Stripe

# Event model
php artisan test --filter=EventTest

# API endpoints
php artisan test --filter=Api
```

## Continuous Integration

```bash
# Simulate CI environment
php artisan test --parallel --stop-on-failure --coverage --min=60
```

## Performance Testing

```bash
# Measure test speed
php artisan test --profile

# Top 10 slowest tests
php artisan test --profile --top=10
```

---

## 📊 Understanding Output

### ✅ Success
```
PASS  Tests\Feature\Services\RegistrationServiceTest
✓ it creates registration without coupon
Tests: 27 passed (89 assertions)
```

### ❌ Failure
```
FAIL  Tests\Feature\Services\RegistrationServiceTest
✗ it creates registration with coupon

Failed asserting that 100 matches expected 80.
at tests/Feature/Services/RegistrationServiceTest.php:45
```

### Coverage Report
```
Cov: 87.5%
RegistrationService ..................... 95.2%
CouponService ........................... 92.3%
Event .................................. 88.1%
```

---

**Pro Tip:** Alias for quick testing:
```bash
# Add to ~/.bashrc or ~/.zshrc
alias t='php artisan test'
alias tc='php artisan test --coverage'
alias tf='php artisan test --filter'
alias tw='php artisan test --stop-on-failure'
```

Then just use:
```bash
t                    # Run all tests
tc                   # With coverage
tf MyTest            # Filter tests
tw                   # Watch mode
```
