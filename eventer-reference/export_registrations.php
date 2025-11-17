<?php
$path = preg_replace('/wp-content.*$/','',__DIR__);
include($path.'wp-load.php'); 

if(!current_user_can( 'edit_posts' )) {
  echo "Sorry. You do not have permission to view this page.";
  exit;
}

 global $wpdb;
 $table = $wpdb->prefix . 'registrations';
 $result = $wpdb->get_results ( "SELECT * FROM $table", ARRAY_A );

 
$clear_result = array();
foreach($result as $key => $val) {
	foreach($val as $arr => $subval) {
		$x = htmlspecialchars_decode($subval);
		$clear_result[$key][] = $x;
	}
}

 $headers = array(
 		'id',
   		'title',
		'name',
		'surname',
		'email',
		'company',
		'role',
		'mobile_phone',
		'office_phone',
		'website',
		'street_address',
		'postcode',
		'city',
		'my_company_is',
		'country',
		't_and_c',
		'interests',
		'paid',
		'payment_status',
		'coupon_code',
		'sign_up_date',
		'printed',
		'hs_synched', 
 );
 
 print_to_csv($clear_result, $headers);
