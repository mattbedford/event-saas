<?php
$path = preg_replace('/wp-content.*$/','',__DIR__);
include($path.'wp-load.php'); 

if(!current_user_can( 'edit_posts' )) {
  echo "Sorry. You do not have permission to view this page.";
  exit;
}

    $args = array(
        'post_type' => 'invitation',
        'numberposts' => -1,
        'fields' => 'ids'
    );
    $invitations = get_posts($args);
    $invitation_array = array();
    $n = 0;

    foreach($invitations as $sing_inv) {
        //Use $related to check if we have a registered post of this recipient or if it's a random person
        $related = get_post_meta($sing_inv, 'related_post', true);
        if(empty($related) || $related == false) {
            //If no related and registered user...
            $related_name = get_post_meta($sing_inv, 'recipient', true);
            $related_id = "other";
        } else {
            //But if we do have one...
            $related_name = get_the_title($related);
            $related_id = $related;
        }

        $invitation_array[$n]['invitation_post_id'] = $sing_inv;
        $invitation_array[$n]['coupon_title'] = get_the_title($sing_inv);
        $invitation_array[$n]['invitation_type'] = get_post_meta($sing_inv, 'invitation_type', true);
        $invitation_array[$n]['recipient_name'] = $related_name;
        $invitation_array[$n]['max_uses'] = get_post_meta($sing_inv, 'max_uses', true);
        $invitation_array[$n]['actual_uses'] = get_post_meta($sing_inv, 'actual_uses', true);
        $invitation_array[$n]['discount'] = get_post_meta($sing_inv, 'percentage_value', true);
        $invitation_array[$n]['guest_status'] = get_post_meta($sing_inv, 'for_guests', true);
        $invitation_array[$n]['permalink'] = get_permalink($sing_inv);
        $n++;
    }

 

 $headers = array(
 		'id',
   		'coupon_code',
		'type',
		'recipient_name',
		'max_uses',
		'actual_uses',
		'%_discount',
		'for_guests',
		'link_to_pdf_invite'
 );
 

print_to_csv($invitation_array, $headers);
