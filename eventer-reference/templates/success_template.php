<?php
/**
 * Template Name: Success page template
 */

get_header();

// Check if we have a Stripe session first (indicates payment was processed)
if(isset($_GET['session_id']) && !empty($_GET['session_id'])) {
	// User went through Stripe - could be full or partial payment
	run_stripe_checkout_session_check();
	exit;
}

// No Stripe session - check for free coupon entry
if(isset($_GET['coupon']) && !empty($_GET['coupon'])) {
	$coupon_code = strtoupper(stripslashes(strip_tags(trim($_GET['coupon']))));
	if($coupon_code != "NO-CODE" && $coupon_code != "none" && !empty($coupon_code)) {
		run_free_coupon_script($coupon_code);
		exit;
	}
}

// Fallback if neither condition met
bail_we_have_a_problem("Invalid success page access.");

$badge_link = null;

function run_stripe_checkout_session_check() {
	if( !class_exists( 'Stripe' ) ) {
		require_once(plugin_dir_path( __DIR__ ) . "vendor/autoload.php" );
	}

	$stripe = new \Stripe\StripeClient(
	  get_option('alt_stripe_key')
	);
	$stripe_sesh = $stripe->checkout->sessions->retrieve(
	  $_GET['session_id'],
	  []
	);

	if(empty($stripe_sesh) || !isset($stripe_sesh)) {
		//Could not find a stripe checkout session with this identifier
		bail_we_have_a_problem("No record of this transaction");
	}

	// Retrieve DB record of this particular user
	$database_record_id = $stripe_sesh->client_reference_id;
	
	$amount = $stripe_sesh->amount_total / 100;
	

	if(empty($database_record_id)) {
		//DB record of this sign-up was not passed properly to Stripe, so now we dunno who we've got.
		//Using email could be a workaround for this issue...
		bail_we_have_a_problem("No record of this customer exists.");
	}
	
	global $wpdb;
	$row = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}registrations WHERE id = '%s'", $database_record_id) );
	$result = $row[0]->payment_status;
	
	if($result && strtolower($result) === "pending" && $stripe_sesh->payment_status == "paid") {
		// We have a user from whom we expected payment and it has been paid.
		// First update in DB, then add to HS list using new OOP methods.
		
		require_once plugin_dir_path( __DIR__ ) . "checkout-scripts/EventerRegistrations.php";
		EventerRegistration::confirmAnyUser($amount, "Paid", $database_record_id);
		
		require_once plugin_dir_path( __DIR__ ) . "checkout-scripts/HubspotTool.php";
		$hs_id = $wpdb->get_var($wpdb->prepare("SELECT hubspot_id FROM {$wpdb->prefix}registrations WHERE id = '%s'", $database_record_id) );
		if(!empty($hs_id)) {
			HubspotTool::addRegistrantToHubspotList($hs_id);
			if(!empty($row[0]->hubspot_id) && $row->hubspot_id !== "Error" && !empty($row[0]->email)) {
				$badge_link = "<a class='btn' href='/get-badge?token=" . $row[0]->hubspot_id . "&email=" . $row[0]->email . "'>Get your event badge here</a>";
			}
		}
		
	} elseif(!$result) {
		//We have a user who was not found in our registrations DB table
		bail_we_have_a_problem("Your sign up has not been successful. :( ");
	} elseif($result !== "Pending") {
		// We have a user who was found in the registrations table, but who is not "pending"
		//No need to do anything.
	} elseif($stripe_sesh->payment_status != "paid") {
		// We have a checkout session that did not return "paid" correctly. Maybe something went wrong.
		bail_we_have_a_problem("It looks like your payment was unsuccessful.");
	}

	run_success_message();
	
}

function run_free_coupon_script($coupon_code) {
	?>
	<style>
	#page {
		max-width:unset;
		width:100vw;
		margin:0;
	}
	.site-content {
		display:flex;
		min-height:90vh;
		align-items:center;
		justify-content:center;
		background: rgb(0,132,203);
		background: linear-gradient(149deg, rgba(0,132,203,1) 19%, rgba(127,196,28,1) 57%);
	}
	.success {
		background:white;
		padding:100px;
		margin:100px;
	}
	.success svg {
		width:60px;
		color:#7fc41c;
		margin-bottom:-14px;
	}
	.success h1 {
		display:inline;
		margin-left:20px;
		color:#7fc41c;
		font-weight:400;
	}
	.success p {
		margin:10px 0;
	}
	.success .code {
		font-weight:600 !important;
	}

	@media screen and (max-width:767px) {
		.success {
			text-align:center;
			padding:40px 20px;
			min-width:300px;
		}
	}
	</style>

	<?php

	$badge_link = grab_badge_for_coupon_user();

	echo "<div class='success'>";
	echo '<svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path fill="none" stroke="currentColor" stroke-miterlimit="10" stroke-width="32" d="M448 256c0-106-86-192-192-192S64 150 64 256s86 192 192 192 192-86 192-192z"/><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="M352 176 217.6 336 160 272"/></svg>';
	echo "<h1>Congrats!</h1></br>";
	echo "<p>You have successfully signed up for " . get_option('event_name');  
	echo " using coupon code <span class='code'>" . $coupon_code . "</span>";
	echo " and we can't wait to see you there!</p>";
	echo "<h3>Important: Within 24 hours you'll receive your email confirmation.</h3>";
	if($badge_link && $badge_link !== null) {
		echo $badge_link;
	} else {
		echo "<p>Click <a href='/'>here</a> to continue exploring the themes and speakers of the big day!</p>";
	}
}


function grab_badge_for_coupon_user() {
	if(isset($_GET['session_id']) && !empty($_GET['session_id'])) {
		$session_id = htmlspecialchars($_GET['session_id']);

		global $wpdb;
		$row = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}registrations WHERE id = '%s'", $session_id) );
	} 

	if(!$row || empty($row)) {
		return null;
		return null;
	}

	$hs_id = $row[0]->hubspot_id;
	$email = $row[0]->email;

	if(empty($hs_id) || $hs_id == "Error" || empty($email)) {
		return null;
		return null;
	}
	return "<a class='btn' href='/get-badge?token=" . $hs_id . "&email=" . $email . "'>Get your event badge here</a>";

}


function bail_we_have_a_problem($message) {
	?>
	<style>
	#page {
		max-width:unset;
		width:100vw;
		margin:0;
	}
	.site-content {
		display:flex;
		min-height:90vh;
		align-items:center;
		justify-content:center;
		background: rgb(0,132,203);
		background: linear-gradient(149deg, rgba(0,132,203,1) 19%, rgba(127,196,28,1) 57%);
	}
	.success {
		background:white;
		padding:100px;
		margin:100px;
	}
	.success svg {
		width:60px;
		color:#c74747;
		margin-bottom:-14px;
	}
	.success h1 {
		display:inline;
		margin-left:20px;
		color:#c74747;
		font-weight:400;
	}
	.success p {
		margin:10px 0;
	}
	.success .code {
		font-weight:600 !important;
	}

	@media screen and (max-width:767px) {
		.success {
			text-align:center;
			padding:40px 20px;
			min-width:300px;
		}
	}
	</style>

	<?php
	echo "<div class='success'>";
	echo '<svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path fill="none" stroke="currentColor" stroke-miterlimit="10" stroke-width="32" d="M448 256c0-106-86-192-192-192S64 150 64 256s86 192 192 192 192-86 192-192z"/><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="M320 320 192 192m0 128 128-128"/></svg>';
	echo "<h1>Uh oh. There's been a problem.</h1></br>";
	echo "<p>" . $message . "</p>";
	echo "If the problem persists, please mail us at <a href='mailto:info@dagora.ch'>info@dagora.ch</a> and we'll try to set everything right.";
	die;
}



function run_success_message() {
	?>
	<style>
	#page {
		max-width:unset;
		width:100vw;
		margin:0;
	}
	.site-content {
		display:flex;
		min-height:90vh;
		align-items:center;
		justify-content:center;
		background: rgb(0,132,203);
		background: linear-gradient(149deg, rgba(0,132,203,1) 19%, rgba(127,196,28,1) 57%);
	}
	.success {
		background:white;
		padding:100px;
		margin:100px;
	}
	.success svg {
		width:60px;
		color:#7fc41c;
		margin-bottom:-14px;
	}
	.success h1 {
		display:inline;
		margin-left:20px;
		color:#7fc41c;
		font-weight:400;
	}
	.success p {
		margin:10px 0;
	}
	.success .code {
		font-weight:600 !important;
	}

	@media screen and (max-width:767px) {
		.success {
			text-align:center;
			padding:40px 20px;
			min-width:300px;
		}
	}
	</style>

	<?php
	$badge_link = grab_badge_for_coupon_user();
	echo "<div class='success'>";
	echo '<svg xmlns="http://www.w3.org/2000/svg" class="ionicon" viewBox="0 0 512 512"><path fill="none" stroke="currentColor" stroke-miterlimit="10" stroke-width="32" d="M448 256c0-106-86-192-192-192S64 150 64 256s86 192 192 192 192-86 192-192z"/><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="M352 176 217.6 336 160 272"/></svg>';
	echo "<h1>Congrats!</h1></br>";
	echo "<p>You have successfully purchased your ticket for " . get_option('event_name');  
	echo ". We can't wait to see you at our event!</p>";
	echo "<h3>Important: Within 24 hours you'll receive your email confirmation.</h3>";
	if($badge_link && $badge_link !== null) {
		echo $badge_link;
	} else {
		echo "<p>Click <a href='/'>here</a> to continue exploring the themes and speakers of the big day!</p>";
	}
}
