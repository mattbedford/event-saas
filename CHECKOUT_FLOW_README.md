# Event Registration Checkout Flow - Quick Start Guide

## 🎯 What's New

This implementation adds a complete stateful checkout flow with coupon reservation system to handle all edge cases in event registration:

- **3-step checkout API** - Validate → Initiate → Complete
- **Coupon soft reservations** - Reserve without consuming until payment succeeds
- **Registration state tracking** - Draft → Pending → Processing → Confirmed
- **Payment retry support** - Failed payments can retry with same email
- **Admin UI enhancements** - Full visibility, search, and management tools
- **Webhook integration** - Automatic state updates from Stripe
- **Cleanup automation** - Auto-expire old drafts and reservations

## 🚀 Quick Setup

### 1. Pull and Install

```bash
git checkout claude/event-saas-migration-014v8aKdHaiNoFZrUHpmb2sa
git pull
composer install
```

### 2. Run Migrations

```bash
php artisan migrate
```

This adds:
- `registration_status` enum field to track checkout flow
- `coupon_reservations` table for soft reservations
- `confirmed_at` timestamp for confirmed registrations

### 3. Clear Caches

```bash
php artisan filament:cache-components
php artisan optimize:clear
```

### 4. Start Server

```bash
php artisan serve
```

Visit: http://localhost:8000/admin

## 📋 What Changed

### Database Schema

**registrations table:**
- Added `registration_status` enum: draft, pending_payment, payment_processing, confirmed, abandoned, payment_failed
- Added `confirmed_at` timestamp

**coupon_reservations table (new):**
- Tracks soft coupon reservations
- 30-minute expiry window
- Status: reserved, confirmed, released, expired

### API Endpoints (New)

**Checkout Flow (3-step):**
```
POST /api/events/{slug}/checkout/validate
POST /api/events/{slug}/checkout/initiate
POST /api/events/{slug}/checkout/complete
```

**Legacy endpoints maintained for backward compatibility:**
```
POST /api/events/{slug}/registrations
POST /api/events/{slug}/validate-coupon
```

### Admin UI Enhancements

- New "Status" column with color-coded badges
- Quick-filter tabs: Confirmed, Needs Attention, Drafts, Abandoned
- Comprehensive search across all fields
- Global search (Cmd+K) integration
- Bulk email checker
- Admin actions: Reset for Retry, Force Confirm
- Coupon reservation visibility

## 🧪 Testing the New Flow

### Test 1: Simple Registration (No Payment)

**100% discount coupon → immediate confirmation**

```bash
# Step 1: Validate coupon (optional, just checks availability)
curl -X POST http://localhost:8000/api/events/my-event/checkout/validate \
  -H "Content-Type: application/json" \
  -d '{"coupon_code": "FREE100"}'

# Step 2: Create draft registration
curl -X POST http://localhost:8000/api/events/my-event/checkout/initiate \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "name": "John",
    "surname": "Doe",
    "company": "Test Corp",
    "phone": "+41123456789",
    "coupon_code": "FREE100"
  }'
# Returns: {"success": true, "registration": {"id": 123, ...}, "pricing": {...}}

# Step 3: Complete registration (no Stripe needed)
curl -X POST http://localhost:8000/api/events/my-event/checkout/complete \
  -H "Content-Type: application/json" \
  -d '{
    "registration_id": 123,
    "success_url": "https://example.com/success",
    "cancel_url": "https://example.com/cancel"
  }'
# Returns: {"success": true, "confirmed": true, "message": "Registration confirmed!"}
```

**Check admin:** Registration status = "Confirmed" ✅

### Test 2: Paid Registration with Stripe

**50% discount coupon → Stripe checkout**

```bash
# Step 1 & 2: Same as above but with "SAVE50" coupon

# Step 3: Complete (creates Stripe session)
curl -X POST http://localhost:8000/api/events/my-event/checkout/complete \
  -H "Content-Type: application/json" \
  -d '{
    "registration_id": 124,
    "success_url": "https://example.com/success",
    "cancel_url": "https://example.com/cancel"
  }'
# Returns: {"success": true, "needs_payment": true, "checkout": {"url": "https://checkout.stripe.com/..."}}
```

**User flow:**
1. Redirect user to `checkout.url`
2. User completes payment
3. Stripe webhook fires → registration_status = "payment_processing"
4. Payment confirms → registration_status = "confirmed"

### Test 3: Payment Retry (Card Decline)

```bash
# Same steps as Test 2, but user's card declines
# Webhook updates: registration_status = "payment_failed"

# User tries again with same email:
curl -X POST http://localhost:8000/api/events/my-event/checkout/initiate \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",  # SAME EMAIL
    "name": "John",
    "surname": "Doe",
    "coupon_code": "DIFFERENT50"  # Can use different coupon
  }'
# Returns: Updates existing draft, releases old coupon, reserves new one
```

## 🔍 Admin UI Testing

### Navigation & Tabs

1. Open http://localhost:8000/admin
2. Click "Registrations" in sidebar
3. Notice badge (red if issues, green otherwise)
4. Click tabs to filter:
   - **Confirmed** - Successful registrations
   - **Needs Attention** - Payment failed or stuck processing
   - **Drafts & Pending** - In-progress checkouts
   - **Abandoned** - Users who gave up

### Search Features

**Table Search:**
- Type partial name: "winkleman"
- Type email: "test@example.com"
- Type company: "Tech Corp"
- Paste Stripe ID: "cs_test_..."

**Global Search (Cmd+K or Ctrl+K):**
- Search from anywhere in admin
- Shows rich preview with event, email, status, company
- Click to jump to registration

**Bulk Email Check:**
1. Click "Check Multiple Emails" button
2. Paste list of emails (comma or newline separated)
3. Optional: filter by specific event
4. Get instant results: "Found: 5 | Not registered: 3"

### Admin Actions

**On any registration:**
- **Reset for Retry** - For payment_failed/abandoned, releases coupon
- **Force Confirm** - Manually confirm stuck registrations
- **Resend Email** - Resend confirmation
- **Cancel** - Cancel and reaccredit coupon

**View registration details:**
- See registration status badge
- View coupon reservation status/expiry
- Check Stripe IDs
- See confirmation timestamp

## 🪝 Stripe Webhook Testing

### Setup Stripe CLI

```bash
# Install
brew install stripe/stripe-cli/stripe

# Login
stripe login

# Forward webhooks to local server
stripe listen --forward-to localhost:8000/api/webhooks/stripe
```

### Test Webhook Events

The webhook handler automatically processes:

**checkout.session.completed:**
- Sets: `registration_status = 'payment_processing'`
- User finished Stripe form, awaiting payment confirmation

**payment_intent.succeeded:**
- Sets: `registration_status = 'confirmed'`
- Confirms coupon reservation (NOW counts against limits)
- Updates Hubspot with confirmed status

**payment_intent.payment_failed:**
- Sets: `registration_status = 'payment_failed'`
- Releases coupon reservation (user can retry)

**checkout.session.expired:**
- Sets: `registration_status = 'abandoned'`
- Releases coupon reservation

### Test with Stripe Test Cards

```bash
# Successful payment
4242 4242 4242 4242

# Card declined
4000 0000 0000 9995

# Requires authentication (3D Secure)
4000 0025 0000 3155
```

## 🧹 Cleanup Command

Automatically maintains registration hygiene:

```bash
# Dry run (see what would be cleaned)
php artisan registrations:cleanup-abandoned --dry-run

# Actually run cleanup
php artisan registrations:cleanup-abandoned
```

**What it does:**
1. Expires coupon reservations older than 30 minutes
2. Marks drafts older than 24 hours as abandoned
3. Releases reservations for abandoned/failed registrations

**Schedule it (add to app/Console/Kernel.php):**
```php
$schedule->command('registrations:cleanup-abandoned')->everyThirtyMinutes();
```

## 📊 Edge Cases Covered

### Case 1: 100% Coupon (Immediate Confirmation)
✅ No Stripe needed, confirms immediately
✅ Coupon reservation confirmed on completion
✅ Hubspot updated as confirmed

### Case 2: User Bails at Checkout (Prospect Tracking)
✅ Sent to Hubspot at draft stage
✅ Coupon soft-reserved (doesn't count yet)
✅ Reservation auto-expires after 30 min
✅ Status: "Abandoned" in admin

### Case 3: Card Fails, User Retries
✅ Can retry with same email
✅ Updates existing draft registration
✅ Can use different coupon (old released, new reserved)
✅ Status: "Payment Failed" → "Draft" → "Confirmed"

### Case 4: Slow Stripe Webhook
✅ checkout.session.completed → "Processing..."
✅ payment_intent.succeeded → "Confirmed"
✅ Clear status visibility in admin

## 🐛 Troubleshooting

### Admin UI not showing new columns
```bash
php artisan filament:cache-components
php artisan optimize:clear
```

### Webhooks not firing
1. Check Stripe CLI is running: `stripe listen --forward-to localhost:8000/api/webhooks/stripe`
2. Verify webhook secret in `.env`: `STRIPE_WEBHOOK_SECRET=whsec_...`
3. Check logs: `tail -f storage/logs/laravel.log`

### Coupon reservations not working
1. Check migrations ran: `php artisan migrate:status`
2. Verify coupon_reservations table exists: `php artisan tinker` → `DB::table('coupon_reservations')->count()`

### Search not working
1. Clear cache: `php artisan optimize:clear`
2. Check database columns exist: `php artisan tinker` → `\App\Models\Registration::first()`

## 📝 Next Steps

### Frontend Integration

Update your registration form to call the 3-step API:

```javascript
// Step 1: Validate coupon (when user enters code)
const validateResponse = await fetch('/api/events/my-event/checkout/validate', {
  method: 'POST',
  body: JSON.stringify({ coupon_code: 'SAVE50' })
});

// Step 2: Create draft (when user submits form)
const initiateResponse = await fetch('/api/events/my-event/checkout/initiate', {
  method: 'POST',
  body: JSON.stringify({
    email, name, surname, company, phone, coupon_code
  })
});
const { registration } = await initiateResponse.json();

// Step 3: Complete (when user confirms)
const completeResponse = await fetch('/api/events/my-event/checkout/complete', {
  method: 'POST',
  body: JSON.stringify({
    registration_id: registration.id,
    success_url: window.location.origin + '/success',
    cancel_url: window.location.origin + '/cancel'
  })
});

const result = await completeResponse.json();

if (result.confirmed) {
  // Free registration, show success
  window.location.href = '/success';
} else if (result.needs_payment) {
  // Redirect to Stripe
  window.location.href = result.checkout.url;
}
```

### Production Deployment

1. **Configure Stripe webhook endpoint:**
   - Dashboard → Developers → Webhooks
   - Add endpoint: `https://yourdomain.com/api/webhooks/stripe`
   - Select events: `checkout.session.*`, `payment_intent.*`
   - Copy webhook secret to `.env`

2. **Schedule cleanup command:**
   ```php
   // app/Console/Kernel.php
   protected function schedule(Schedule $schedule)
   {
       $schedule->command('registrations:cleanup-abandoned')->everyThirtyMinutes();
   }
   ```

3. **Monitor logs:**
   ```bash
   tail -f storage/logs/laravel.log | grep -i "registration\|coupon\|stripe"
   ```

## 📚 Key Files Reference

**Controllers:**
- `app/Http/Controllers/Api/CheckoutController.php` - New 3-step checkout flow
- `app/Http/Controllers/Api/WebhookController.php` - Stripe webhook handler

**Services:**
- `app/Services/CouponService.php` - Coupon validation and reservation
- `app/Services/StripeCheckoutService.php` - Stripe integration with state management

**Models:**
- `app/Models/Registration.php` - Registration state methods
- `app/Models/CouponReservation.php` - Reservation lifecycle

**Admin:**
- `app/Filament/Resources/RegistrationResource.php` - Admin UI config
- `app/Filament/Resources/RegistrationResource/Pages/ListRegistrations.php` - Tabs and widgets

**Commands:**
- `app/Console/Commands/CleanupAbandonedRegistrations.php` - Automated cleanup

**Migrations:**
- `database/migrations/2025_11_17_193042_add_registration_status_to_registrations_table.php`
- `database/migrations/2025_11_17_193106_create_coupon_reservations_table.php`

## 🎉 Summary

You now have a production-ready checkout flow that:
- ✅ Handles all edge cases (free, paid, failed, retry, abandoned)
- ✅ Prevents race conditions on popular coupons
- ✅ Tracks prospects who bail at checkout
- ✅ Allows payment retries without duplicate prevention
- ✅ Provides full admin visibility and control
- ✅ Syncs with Hubspot CRM
- ✅ Auto-cleans stale data

The system is designed for low-tech users with intuitive search, clear status labels, and helpful admin actions.

**Questions?** Check the code comments or Laravel logs for detailed behavior.

**Happy testing!** 🚀
