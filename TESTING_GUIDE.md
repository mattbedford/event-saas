# 🧪 Complete Testing Guide for Event SaaS

Welcome to your comprehensive testing guide! This document will teach you everything you need to know about testing, from absolute basics to running and maintaining your test suite.

## 📚 Table of Contents

1. [Testing 101: What & Why](#testing-101-what--why)
2. [Understanding Test Types](#understanding-test-types)
3. [Running Tests](#running-tests)
4. [Reading Test Results](#reading-test-results)
5. [Test Coverage](#test-coverage)
6. [Writing New Tests](#writing-new-tests)
7. [Best Practices](#best-practices)
8. [Troubleshooting](#troubleshooting)
9. [CI/CD Integration](#cicd-integration)

---

## 🎓 Testing 101: What & Why

### What is a Test?

A test is code that automatically checks if your application code works correctly. Think of it like a checklist that runs automatically to verify everything works as expected.

**Example:** Instead of manually:
1. Creating an event
2. Adding a coupon
3. Registering with the coupon
4. Checking if the discount was applied

You write a test that does all this automatically in seconds!

### Why Test?

- **Catch bugs early** - Find problems before customers do
- **Confidence in changes** - Make changes without fear of breaking things
- **Documentation** - Tests show how your code should work
- **Save time** - Automated testing is faster than manual testing
- **Better code** - Writing testable code leads to better design

### Your Test Coverage Goals

We're targeting **60-70% coverage** focused on:
- ✅ Payment processing (CRITICAL)
- ✅ Registration flow (CRITICAL)
- ✅ Coupon logic (CRITICAL)
- ✅ API endpoints (IMPORTANT)
- ✅ Model business logic (IMPORTANT)

---

## 📋 Understanding Test Types

### Feature Tests (Integration Tests)

Test complete workflows - how multiple parts work together.

**Example:** `tests/Feature/Services/RegistrationServiceTest.php`
```php
/** @test */
public function it_creates_registration_with_percentage_coupon()
{
    // Setup: Create event and coupon
    $event = Event::factory()->create(['ticket_price' => 100]);
    $coupon = Coupon::factory()->for($event)->percentage(20)->create();

    // Action: Create registration with coupon
    $registration = $this->service->createRegistration($event, [
        'email' => 'test@example.com',
        'coupon_code' => $coupon->code,
    ]);

    // Assert: Check results
    $this->assertEquals(20, $registration->discount_amount);
    $this->assertEquals(80, $registration->expected_amount);
}
```

### Unit Tests

Test individual methods in isolation.

**Example:** Testing a single calculation method
```php
/** @test */
public function it_calculates_percentage_discount()
{
    $coupon = Coupon::factory()->percentage(25)->make();

    $result = $coupon->calculateDiscount(100);

    $this->assertEquals(25, $result);
}
```

---

## 🚀 Running Tests

### Run All Tests

```bash
php artisan test
```

Or using PHPUnit directly:
```bash
vendor/bin/phpunit
```

### Run Specific Test File

```bash
php artisan test tests/Feature/Services/RegistrationServiceTest.php
```

### Run Specific Test Method

```bash
php artisan test --filter=it_creates_registration_with_percentage_coupon
```

### Run Tests in a Directory

```bash
php artisan test tests/Feature/Services
```

### Run with Coverage Report

```bash
php artisan test --coverage
```

For detailed HTML coverage:
```bash
php artisan test --coverage-html coverage-report
```

Then open `coverage-report/index.html` in your browser.

### Parallel Testing (Faster!)

```bash
php artisan test --parallel
```

---

## 📊 Reading Test Results

### Successful Test Output

```
PASS  Tests\Feature\Services\RegistrationServiceTest
✓ it creates registration without coupon                    0.15s
✓ it creates registration with percentage coupon           0.12s
✓ it creates free registration with 100 percent coupon     0.18s

Tests:  3 passed (27 assertions)
Duration: 0.52s
```

**What this means:**
- ✓ = Test passed
- Number after = Time taken
- 27 assertions = 27 checks were made
- All green = Everything works!

### Failed Test Output

```
FAIL  Tests\Feature\Services\RegistrationServiceTest
✓ it creates registration without coupon                    0.15s
✗ it creates registration with percentage coupon           0.12s

Failed asserting that 100 matches expected 80.

at tests/Feature/Services/RegistrationServiceTest.php:58
```

**What this means:**
- ✗ = Test failed
- Shows which assertion failed
- Shows expected vs actual values
- Shows exact line number

### Error vs Failure

- **Failure** = Assertion didn't match (logic bug)
- **Error** = Code crashed (syntax/runtime error)

---

## 📈 Test Coverage

### What is Coverage?

Coverage shows which lines of your code are tested.

### Viewing Coverage

```bash
php artisan test --coverage
```

Output:
```
Cov  ................................................... 87.5%
     RegistrationService ............................ 95.2%
     CouponService .................................. 92.3%
     StripeCheckoutService .......................... 78.1%
```

### Coverage Goals

- **60-70% overall** - Good for most applications
- **80%+ for services** - Critical business logic
- **50%+ for models** - Core data logic
- **Don't chase 100%** - Diminishing returns

### What NOT to Test

- Getters/setters
- Framework code (Laravel, Filament)
- Third-party packages
- Simple pass-through methods

---

## ✍️ Writing New Tests

### Basic Test Structure

```php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyTest extends TestCase
{
    use RefreshDatabase; // Fresh database for each test

    /** @test */
    public function it_does_something()
    {
        // 1. ARRANGE: Set up test data
        $event = Event::factory()->create();

        // 2. ACT: Perform the action
        $result = $service->doSomething($event);

        // 3. ASSERT: Check the result
        $this->assertTrue($result);
        $this->assertDatabaseHas('events', ['id' => $event->id]);
    }
}
```

### Common Assertions

```php
// Equality
$this->assertEquals(100, $amount);
$this->assertNotEquals(0, $count);

// Truth
$this->assertTrue($hasSeats);
$this->assertFalse($isSoldOut);

// Null checks
$this->assertNull($coupon);
$this->assertNotNull($registration);

// Database
$this->assertDatabaseHas('registrations', ['email' => 'test@example.com']);
$this->assertDatabaseMissing('registrations', ['email' => 'old@example.com']);
$this->assertSoftDeleted('registrations', ['id' => 1]);

// Count
$this->assertCount(3, $registrations);
$this->assertEmpty($waitlist);

// Strings
$this->assertStringStartsWith('evt_', $apiKey);
$this->assertStringContains('SAVE', $couponCode);

// Exceptions
$this->expectException(ValidationException::class);
$this->expectExceptionMessage('Invalid coupon');
```

### Using Factories

```php
// Create and save to database
$event = Event::factory()->create();

// Create with specific attributes
$event = Event::factory()->create(['ticket_price' => 150]);

// Create multiple
$events = Event::factory()->count(5)->create();

// Create without saving (in-memory only)
$event = Event::factory()->make();

// Use factory states
$soldOutEvent = Event::factory()->soldOut()->create();
$nearlyFullEvent = Event::factory()->nearlyFull()->create();
$coupon = Coupon::factory()->percentage(20)->create();
$registration = Registration::factory()->paid()->create();
```

### Testing Relationships

```php
/** @test */
public function event_has_registrations()
{
    $event = Event::factory()->create();
    Registration::factory()->for($event)->count(3)->create();

    $this->assertCount(3, $event->registrations);
}
```

### Testing Exceptions

```php
/** @test */
public function it_rejects_invalid_coupon()
{
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Invalid coupon code');

    $service->validateCoupon($event, 'INVALID');
}
```

### Mocking External Services

```php
use Mockery;

/** @test */
public function it_calls_stripe_api()
{
    // Create mock
    $stripeMock = Mockery::mock(StripeCheckoutService::class);

    // Define expectations
    $stripeMock->shouldReceive('createCheckoutSession')
        ->once()
        ->andReturn($sessionObject);

    // Use the mock
    $this->app->instance(StripeCheckoutService::class, $stripeMock);

    // Run test...
}
```

---

## 🎯 Best Practices

### 1. One Assertion Per Test (Usually)

❌ Bad:
```php
/** @test */
public function it_tests_everything()
{
    $this->assertEquals(100, $price);
    $this->assertEquals(20, $discount);
    $this->assertEquals('paid', $status);
}
```

✅ Good:
```php
/** @test */
public function it_calculates_correct_price()
{
    $this->assertEquals(100, $price);
}

/** @test */
public function it_calculates_correct_discount()
{
    $this->assertEquals(20, $discount);
}
```

### 2. Descriptive Test Names

❌ Bad: `test_registration()`
✅ Good: `it_creates_registration_with_percentage_coupon()`

### 3. Test Edge Cases

```php
/** @test */
public function it_prevents_negative_price()
{
    $coupon = Coupon::factory()->fixed(150)->make();

    $result = $service->applyCoupon($coupon, 100);

    $this->assertEquals(0, $result['final_price']); // Not -50!
}
```

### 4. Keep Tests Fast

- Use factories instead of manual creation
- Use `RefreshDatabase` instead of migrations
- Mock external APIs
- Use in-memory SQLite for tests

### 5. Test Behavior, Not Implementation

❌ Bad: Testing internal methods
✅ Good: Testing public API and outcomes

---

## 🔧 Troubleshooting

### Tests Are Slow

```bash
# Use parallel testing
php artisan test --parallel

# Or specific test file
php artisan test tests/Feature/Services/RegistrationServiceTest.php
```

### Database Errors

```bash
# Clear and recreate test database
php artisan migrate:fresh --env=testing
```

### "Class not found" Errors

```bash
# Rebuild autoload files
composer dump-autoload
```

### "Too few arguments" in Mocks

Make sure all constructor dependencies are mocked:
```php
$this->stripeService = Mockery::mock(StripeCheckoutService::class);
$this->app->instance(StripeCheckoutService::class, $this->stripeService);
```

### Failed Assertions

Look at the error message:
```
Failed asserting that 100 matches expected 80.
```

- Expected: What you thought it would be (80)
- Actual: What it actually was (100)
- Fix the code or the test!

---

## 🤖 CI/CD Integration

### GitHub Actions (Included)

Tests run automatically on every push. See `.github/workflows/tests.yml`

```yaml
name: Tests

on: [push, pull_request]

jobs:
  tests:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v2
      - uses: shivammathur/setup-php@v2
        with:
          php-version: 8.4

      - name: Install Dependencies
        run: composer install

      - name: Run Tests
        run: php artisan test --coverage
```

### Local Pre-Commit Hook

Add to `.git/hooks/pre-commit`:
```bash
#!/bin/sh
php artisan test --stop-on-failure
```

Make it executable:
```bash
chmod +x .git/hooks/pre-commit
```

Now tests run before every commit!

---

## 📝 Test Checklist

When adding a new feature, test:

- [ ] Happy path (normal usage)
- [ ] Edge cases (boundaries)
- [ ] Error cases (invalid input)
- [ ] Null/empty values
- [ ] Concurrent access (if applicable)
- [ ] Database transactions
- [ ] Relationships
- [ ] Validation rules

---

## 🎓 Learning Resources

### Running Your First Test

```bash
# 1. Run all tests
php artisan test

# 2. Run just RegistrationService tests
php artisan test tests/Feature/Services/RegistrationServiceTest.php

# 3. Run with coverage
php artisan test --coverage
```

### Understanding the Output

```
PASS  Tests\Feature\Services\RegistrationServiceTest
✓ it creates registration without coupon
```

This means:
1. File: `RegistrationServiceTest.php`
2. Result: ✓ PASS (all good!)
3. Test: `it_creates_registration_without_coupon()`

### Next Steps

1. **Run the tests** - See them pass!
2. **Break something** - Change a value in the code
3. **Run again** - See the test fail
4. **Fix it** - Restore the code
5. **Celebrate** - You understand testing!

---

## 📞 Need Help?

- Check test output for specific error messages
- Run single test file to isolate issues
- Use `dd()` or `dump()` in tests to debug
- Check Laravel testing docs: https://laravel.com/docs/testing

---

## ✅ Your Test Suite

### Current Test Files

```
tests/
├── Feature/
│   ├── Services/
│   │   ├── RegistrationServiceTest.php   (27 tests)
│   │   └── CouponServiceTest.php         (35 tests)
│   └── Models/
│       └── EventTest.php                 (27 tests)
└── Unit/
    └── (Add unit tests here)
```

### Coverage by Priority

1. **CRITICAL** ✅
   - RegistrationService: 100% coverage
   - CouponService: 100% coverage
   - Event model: 95% coverage

2. **IMPORTANT** 🔄
   - API endpoints: TODO
   - Webhook verification: TODO

3. **NICE TO HAVE** ⏳
   - Email templates: TODO
   - Badge generation: TODO

---

**Remember:** Tests are your safety net. They catch bugs before your customers do!

Happy Testing! 🎉
