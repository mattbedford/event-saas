# Event SaaS - API Documentation

API documentation for integrating WordPress event sites with the centralized Laravel SaaS platform.

## Base URL

```
https://your-saas-domain.com/api
```

## Authentication

Currently, the API uses event slugs for routing. Future versions will include API keys for additional security.

## Endpoints

### 1. Get Event Details

Retrieve information about an event.

**Endpoint:** `GET /events/{eventSlug}`

**Parameters:**
- `eventSlug` (path) - Event slug identifier

**Response:**
```json
{
  "success": true,
  "event": {
    "id": 1,
    "name": "Tech Conference 2024",
    "slug": "tech-conf-2024",
    "ticket_price": 299.00,
    "event_date": "2024-06-15T09:00:00Z",
    "is_active": true
  }
}
```

---

### 2. Create Registration

Create a new registration and initiate Stripe checkout.

**Endpoint:** `POST /events/{eventSlug}/registrations`

**Parameters:**
- `eventSlug` (path) - Event slug identifier

**Request Body:**
```json
{
  "email": "john@example.com",
  "name": "John",
  "surname": "Doe",
  "company": "Acme Corp",
  "phone": "+1234567890",
  "coupon_code": "EARLY25",
  "success_url": "https://your-wp-site.com/success?session_id={CHECKOUT_SESSION_ID}",
  "cancel_url": "https://your-wp-site.com/registration-cancelled"
}
```

**Response:**
```json
{
  "success": true,
  "registration": {
    "id": 123,
    "email": "john@example.com",
    "name": "John Doe",
    "expected_amount": 224.25,
    "discount_amount": 74.75
  },
  "checkout": {
    "session_id": "cs_test_...",
    "url": "https://checkout.stripe.com/..."
  }
}
```

**Usage:**
Redirect the user to the `checkout.url` to complete payment.

---

### 3. Get Registration

Retrieve registration details by email.

**Endpoint:** `GET /events/{eventSlug}/registrations/{email}`

**Parameters:**
- `eventSlug` (path) - Event slug identifier
- `email` (path) - Registrant email address

**Response:**
```json
{
  "success": true,
  "registration": {
    "id": 123,
    "email": "john@example.com",
    "name": "John Doe",
    "company": "Acme Corp",
    "payment_status": "paid",
    "paid_amount": 224.25,
    "expected_amount": 224.25,
    "badge_generated": true,
    "created_at": "2024-01-15T10:30:00Z"
  }
}
```

---

### 4. Validate Coupon

Validate a coupon code and calculate pricing.

**Endpoint:** `POST /events/{eventSlug}/validate-coupon`

**Parameters:**
- `eventSlug` (path) - Event slug identifier

**Request Body:**
```json
{
  "coupon_code": "EARLY25"
}
```

**Response (Valid):**
```json
{
  "success": true,
  "pricing": {
    "base_price": 299.00,
    "discount_amount": 74.75,
    "final_price": 224.25,
    "coupon_code": "EARLY25"
  }
}
```

**Response (Invalid):**
```json
{
  "success": false,
  "message": "This coupon has expired"
}
```

---

### 5. Stripe Webhook

Handle Stripe webhook events (payment confirmations, etc.).

**Endpoint:** `POST /webhooks/stripe/{eventSlug}`

**Parameters:**
- `eventSlug` (path) - Event slug identifier

**Headers:**
- `Stripe-Signature` - Stripe webhook signature for verification

**Request Body:**
Raw Stripe webhook payload

**Response:**
```json
{
  "success": true,
  "result": {
    "handled": true,
    "registration_id": 123,
    "amount_paid": 224.25,
    "status": "paid"
  }
}
```

**Configuration:**
Add this webhook URL to your Stripe dashboard:
```
https://your-saas-domain.com/api/webhooks/stripe/{your-event-slug}
```

Select these events:
- `checkout.session.completed`
- `payment_intent.succeeded`
- `payment_intent.payment_failed`
- `payment_intent.partially_funded`

---

## WordPress Integration Example

### JavaScript (Frontend)

```javascript
// Validate coupon
async function validateCoupon(eventSlug, couponCode) {
  const response = await fetch(`/api/events/${eventSlug}/validate-coupon`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ coupon_code: couponCode }),
  });

  return response.json();
}

// Create registration
async function createRegistration(eventSlug, formData) {
  const response = await fetch(`/api/events/${eventSlug}/registrations`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      ...formData,
      success_url: `${window.location.origin}/registration-success?session_id={CHECKOUT_SESSION_ID}`,
      cancel_url: `${window.location.origin}/registration-cancelled`,
    }),
  });

  const data = await response.json();

  if (data.success) {
    // Redirect to Stripe Checkout
    window.location.href = data.checkout.url;
  }

  return data;
}
```

### PHP (Backend)

```php
<?php
// Get registration status after successful payment
function get_registration_status($event_slug, $email) {
    $url = "https://your-saas-domain.com/api/events/{$event_slug}/registrations/" . urlencode($email);

    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    return $body['success'] ? $body['registration'] : false;
}
```

---

## Error Handling

All endpoints return consistent error responses:

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

**Common HTTP Status Codes:**
- `200` - Success
- `400` - Bad Request (validation errors)
- `404` - Resource Not Found
- `409` - Conflict (e.g., email already registered)
- `422` - Unprocessable Entity (validation failed)
- `500` - Internal Server Error

---

## Payment Status Values

- `pending` - Registration created, payment not yet completed
- `partial` - Partial payment received
- `paid` - Full payment received
- `failed` - Payment failed
- `refunded` - Payment refunded

---

## Important Notes

1. **Partial Payment Bug Fix**: This system properly handles partial payments. The payment status is always updated based on the actual amount received from Stripe.

2. **Webhook Security**: Always verify Stripe webhook signatures to prevent fraud.

3. **Coupon Usage**: Coupons are reserved when a registration is created and released if the registration is cancelled or refunded.

4. **Badge Generation**: Badges are automatically generated after successful payment and attached to confirmation emails.

5. **Hubspot Sync**: Registrations are automatically synced to Hubspot after successful payment (if configured).

---

## Support

For technical support or questions about the API, please contact the development team.
