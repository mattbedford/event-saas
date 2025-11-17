<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// NEW VERSION 6 (just line 40)


function run_database_set_up() {
    
	//Badges & registrations table
	global $wpdb;

	$table_name = $wpdb->prefix . 'registrations';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		title text,
		name text NOT NULL,
		surname text NOT NULL,
		email text NOT NULL,
		company text NOT NULL,
		role text,
		mobile_phone text,
		office_phone text,
		website text,
		street_address text,
		postcode text,
		city text,
		my_company_is text,
		country text,
		t_and_c tinyint(1) DEFAULT '1' NOT NULL,
		interests text,
		paid int(9),
		payment_status text,
		coupon_code text,
		sign_up_date DATE NOT NULL,
		printed tinyint(1) DEFAULT '0' NOT NULL,
		badge_link text, 
		hubspot_id text,
		hs_synched tinyint(1) DEFAULT '0' NOT NULL,
		welcome_email_sent tinyint(1) DEFAULT '0' NOT NULL,
		checked_in tinyint(1) DEFAULT '0' NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}