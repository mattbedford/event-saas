<?php

// Include classes
require_once('EventerRegistrations.php');
require_once('HubspotTool.php');
require_once('CouponValidator.php');

// Include dependencies
require_once plugin_dir_path( __DIR__ ) . "vendor/autoload.php";
require_once('form_validation.php');



// TURNSTILE CHECK ONLY IF LOGIN-LIMITER PLUGIN IS ACTIVE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$login_limiter_path = trailingslashit(ABSPATH) . 'wp-content/plugins/login-limiter/src/TurnstileHandler.php';
	if (!class_exists('\LAL\src\TurnstileHandler') && file_exists($login_limiter_path)) {
		require_once $login_limiter_path;
		
		$token = $_POST['cf-turnstile-response'] ?? '';
	
		if (empty($token) || !\LAL\src\TurnstileHandler::verify_token($token)) {
			wp_die('Please complete the human verification challenge.');
		}
	}
    // Continue
}
// End turnstile integration

// Validate form
$form_result = validate_the_form($_POST);

if ($form_result['status'] === 'error') {
    send_back_with_errors($form_result);
}

$form_data = $form_result['old'];

// Register user
$registration = new EventerRegistration($form_data);

// Set default ticket price
$p = get_option('ticket_price');
$amount_to_pay = intval(preg_replace("/[^0-9.]/", "", $p));

// Handle coupon if present
if (isset($form_data['coupon']) && !empty($form_data['coupon'])) {

    $coupon_instance = new CouponValidator($form_data['coupon']);
    $coupon_result = $coupon_instance->coupon_result;

    switch ($coupon_result) {
        case 'badcoupon':
        case 'couponlimit':
        case 'couponnotexist':
            wp_redirect(site_url() . "/checkout/?status=error&msg=" . $coupon_result . "&errs=coupon");
            exit;

        case 'zerotopay':
            $registration->confirmFreeUser();
            wp_redirect(site_url() . "/success?coupon=" . $coupon_instance->coupon_code . "&session_id=" . $registration->registration_id);
            exit;

        default:
            $amount_to_pay = $coupon_result;
            break;
    }

    do_stripe_routine($registration, $coupon_instance);

} else {
    do_stripe_routine($registration);
}



// ------------------------------
// Stripe checkout redirect logic
// ------------------------------
function do_stripe_routine($reg_obj, $coupon_obj = null) {

    $stripe_api_access = get_option('alt_stripe_key');
    \Stripe\Stripe::setApiKey($stripe_api_access);

    header('Content-Type: application/json');
    $domain = site_url();
    $event_name = get_option('event_name');
    $email = $reg_obj->data['email'];

    if ($coupon_obj !== null) {
        $amount = intval($coupon_obj->coupon_result * 100);
        $the_code = $coupon_obj->coupon_code;
    } else {
        $p = get_option('ticket_price');
        $amount = intval(preg_replace("/[^0-9.]/", "", $p)) * 100;
        $the_code = "no-code";
    }

    $checkout_session = \Stripe\Checkout\Session::create([
        'customer_email' => $email,
        'invoice_creation' => ['enabled' => true],
        'client_reference_id' => $reg_obj->registration_id,
        'line_items' => [[
            'price_data' => [
                'currency' => 'chf',
                'product_data' => [
                    'name' => "Entrance to Dagorà " . $event_name,
                ],
                'unit_amount' => $amount,
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => $domain . '/success?coupon=' . $the_code . '&session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => $domain . '/checkout',
    ]);

    header("HTTP/1.1 303 See Other");
    header("Location: " . $checkout_session->url);
    exit;
}



// --------------------------------------
// Send errors and old form data back
// --------------------------------------
function send_back_with_errors($form_result) {
    $key = 'eventer_' . md5($_SERVER['REMOTE_ADDR'] . time());

    set_transient($key . '_errors', $form_result['errors'], 60);
    set_transient($key . '_old', $form_result['old'], 60);

    $fail_url = add_query_arg([
        'status' => 'error',
        'formref' => $key
    ], site_url() . '/checkout/');

    wp_redirect($fail_url);
    exit;
}
