# Add stateful checkout flow with coupon reservations and comprehensive admin UI

## Summary

Implements a complete stateful checkout flow with coupon reservation system to handle all edge cases in event registration. Designed specifically for low-tech users with intuitive admin UI, comprehensive search, and automated cleanup.

### Key Features

#### 1. **3-Step Checkout API**
- `POST /checkout/validate` - Pre-validate coupon availability
- `POST /checkout/initiate` - Create draft registration, soft-reserve coupon, send to Hubspot as prospect
- `POST /checkout/complete` - Confirm if free, or create Stripe checkout session

#### 2. **Coupon Soft Reservation System**
- Reserves coupons for 30 minutes without consuming inventory
- Prevents race conditions on popular coupons
- Auto-expires if checkout abandoned
- Only confirmed on successful payment

#### 3. **Registration State Machine**
- **draft** - User filled form, not submitted yet
- **pending_payment** - Stripe session created, waiting for user
- **payment_processing** - User completed Stripe, awaiting confirmation
- **confirmed** - Fully confirmed and paid
- **abandoned** - User gave up during checkout
- **payment_failed** - Payment failed, can retry with same email

#### 4. **Admin UI Enhancements**
- Color-coded status badges with tooltips
- Quick-filter tabs: Confirmed, Needs Attention, Drafts, Abandoned
- Comprehensive search across all fields (name, email, company, event, coupon, Stripe IDs)
- Global search integration (Cmd+K)
- Bulk email checker ("Did these 5 people register?")
- Admin actions: Reset for Retry, Force Confirm
- Coupon reservation visibility with expiry tracking

#### 5. **Enhanced Stripe Webhook Handler**
- `checkout.session.completed` → payment_processing
- `checkout.session.expired` → abandoned (releases coupon)
- `payment_intent.succeeded` → confirmed (confirms coupon reservation)
- `payment_intent.payment_failed` → payment_failed (releases coupon for retry)
- Comprehensive logging for all state transitions

#### 6. **Automated Cleanup**
- Command: `php artisan registrations:cleanup-abandoned`
- Expires reservations older than 30 minutes
- Marks drafts older than 24 hours as abandoned
- Releases reservations for failed/abandoned registrations
- Designed for scheduled execution (every 30 minutes)

## Edge Cases Handled

### Case 1: 100% Discount Coupon
✅ No Stripe needed, confirms immediately
✅ Coupon reservation confirmed on completion
✅ Hubspot updated as confirmed

### Case 2: User Bails at Checkout
✅ Sent to Hubspot at draft stage (prospect tracking)
✅ Coupon soft-reserved (doesn't count against limits)
✅ Reservation auto-expires after 30 minutes
✅ Status: "Abandoned" in admin

### Case 3: Card Fails, User Retries
✅ Can retry with same email (no duplicate blocking)
✅ Updates existing draft registration
✅ Can use different coupon (old released, new reserved)
✅ Status transitions: "Payment Failed" → "Draft" → "Confirmed"

### Case 4: Slow Stripe Webhook
✅ checkout.session.completed → "Processing..."
✅ payment_intent.succeeded → "Confirmed"
✅ Clear status visibility throughout entire flow

## Database Changes

### Migrations
1. `add_registration_status_to_registrations_table`
   - Adds `registration_status` enum field
   - Adds `confirmed_at` timestamp

2. `create_coupon_reservations_table`
   - New table for soft reservation tracking
   - Status: reserved, confirmed, released, expired
   - 30-minute expiry window
   - Foreign keys to coupons, registrations, events

### Backward Compatibility
✅ All migrations are backward compatible
✅ Existing registrations work without changes
✅ Legacy API endpoints maintained
✅ No breaking changes

## Files Changed

### New Files
- `app/Http/Controllers/Api/CheckoutController.php` - 3-step checkout flow
- `app/Models/CouponReservation.php` - Reservation lifecycle management
- `app/Console/Commands/CleanupAbandonedRegistrations.php` - Automated cleanup
- `app/Filament/Widgets/RegistrationSearchTips.php` - Search help widget
- `resources/views/filament/widgets/registration-search-tips.blade.php` - Widget view
- `CHECKOUT_FLOW_README.md` - Comprehensive quick-start guide

### Modified Files
- `app/Services/CouponService.php` - Added reservation methods
- `app/Services/StripeCheckoutService.php` - Enhanced webhook handling with state management
- `app/Models/Registration.php` - Added state checking methods
- `app/Filament/Resources/RegistrationResource.php` - Full admin UI enhancements
- `app/Filament/Resources/RegistrationResource/Pages/ListRegistrations.php` - Tabs and widgets
- `routes/api.php` - New checkout endpoints

## Testing Checklist

### Admin UI
- [ ] New "Status" column visible with color badges
- [ ] Tabs working: Confirmed, Needs Attention, Drafts, Abandoned
- [ ] Search works across all fields
- [ ] Global search (Cmd+K) finds registrations
- [ ] Bulk email checker functions
- [ ] View registration shows coupon reservation details
- [ ] Admin actions appear conditionally

### API Endpoints
- [ ] `/checkout/validate` pre-validates coupons
- [ ] `/checkout/initiate` creates draft and reserves coupon
- [ ] `/checkout/complete` confirms free or creates Stripe session
- [ ] Email retry works (same email, different registration state)
- [ ] Coupon change on retry works (releases old, reserves new)

### Stripe Webhooks
- [ ] checkout.session.completed sets payment_processing
- [ ] checkout.session.expired sets abandoned
- [ ] payment_intent.succeeded sets confirmed and confirms coupon
- [ ] payment_intent.payment_failed sets payment_failed and releases coupon

### Cleanup Command
- [ ] Dry-run shows what would be cleaned
- [ ] Actually expires old reservations
- [ ] Marks old drafts as abandoned
- [ ] Releases reservations for failed registrations

## Deployment Steps

1. **Pull and migrate**
   ```bash
   git pull
   composer install
   php artisan migrate
   php artisan filament:cache-components
   php artisan optimize:clear
   ```

2. **Configure Stripe webhook**
   - Dashboard → Developers → Webhooks
   - Add endpoint: `https://yourdomain.com/api/webhooks/stripe`
   - Events: `checkout.session.*`, `payment_intent.*`
   - Update `STRIPE_WEBHOOK_SECRET` in `.env`

3. **Schedule cleanup** (optional but recommended)
   ```php
   // app/Console/Kernel.php
   $schedule->command('registrations:cleanup-abandoned')->everyThirtyMinutes();
   ```

## Documentation

See `CHECKOUT_FLOW_README.md` for:
- Quick setup guide
- Testing scenarios with curl examples
- Admin UI walkthrough
- Troubleshooting guide
- Frontend integration examples

## Performance Considerations

- Tab badge counts may cause N+1 queries on high-traffic sites (consider caching)
- Global search indexes all specified fields (existing indexes sufficient)
- Reservation expiry handled by scheduled command, not on-demand
- Stripe webhook processing is idempotent and safe to retry

## Security

- ✅ Webhook signature verification enforced
- ✅ Registration ownership verified before operations
- ✅ Coupon reservations prevent inventory overselling
- ✅ Email uniqueness only for confirmed registrations
- ✅ All state transitions logged for audit trail

## Breaking Changes

None - all changes are backward compatible.

## Notes for Low-Tech Users

All UI elements designed with clear labels, tooltips, and visual indicators:
- Status badges use colors and emoji
- Tooltips explain what each status means
- Search works with partial information
- Bulk checker provides instant results
- Admin actions have clear descriptions
