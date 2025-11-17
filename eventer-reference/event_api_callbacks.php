<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function all_event_options() {
    $all_options = array(
        "event_name",
        "event_payoff",
        "event_date",
        "event_start",
        "event_end",
        "alt_stripe_key",
        "stripe_webhook",
        "event_tag",
        "hubspot_list",
        "hubspot_key",
        "venue_name",
        "venue_address",
        "venue_city",
        "venue_country",
        "max_attendees",
        "badge_template",
        "ticket_price",
        "badge_x",
        "badge_y",
        "badge_x_p2",
        "badge_y_p2",
        "badge_company_format",
        "badge_job_format",
        "badge_name_format",
	    "badge_namebreak",
    );
    $return_array = array();

    foreach($all_options as $single_opt) {
        $existing_option = get_option($single_opt);
        if($single_opt === 'badge_company_format' ||
        $single_opt === 'badge_job_format' ||
        $single_opt === 'badge_name_format') {
            $existing_option = unserialize($existing_option);
        }
        if(!empty($existing_option) && $existing_option !== FALSE) {
            $return_array[] = array($single_opt, $existing_option);
        }
    }

    echo json_encode($return_array);
    die();
}


function set_the_options($args) {
    $data = $args->get_json_params();

    $permitted = array(
            "event_name",
            "event_payoff",
            "event_date",
            "event_start",
            "event_end",
            "alt_stripe_key",
            "stripe_webhook",
            "event_tag",
            "hubspot_list",
            "hubspot_key",
            "venue_name",
            "venue_address",
            "venue_city",
            "venue_country",
            "max_attendees",
            "badge_template",
            "ticket_price",
            "badge_x",
            "badge_y",
            "badge_x_p2",
            "badge_y_p2",
            "badge_company_format",
            "badge_job_format",
            "badge_name_format",
	        "badge_namebreak",
    );
    $errors = array();

    update_option('badge_namebreak', NULL);

    foreach($data as $key => $val) {
        $new_option = strval($key);
        if(is_array($val)) {
            $arrval = array();
            foreach($val as $subkey => $subval) {
                if(null == $subval) continue;
                $subkey = stripslashes(strip_tags(trim($subkey)));
                $subval = stripslashes(strip_tags(trim($subval)));
                $arrval[$subkey] = $subval;
            }
            $new_value = serialize($arrval);
        } else {
            $new_value = stripslashes(strip_tags(trim($val)));
        }

        // Make sure options are legit
        if(!in_array($new_option, $permitted)) return array("Error", "Sorry, you submitted a forbidden option");

        // Catch any empty fileds in form submission
        if($new_value == null || empty($new_value)) continue;

        update_option($new_option, $new_value);
        $option_check = get_option($new_option);

        if($option_check !== $new_value) {
            $errors[] = $new_option;
        }
    }

    if(empty($errors)) {
        $return_status = "Success";
        $return_string = ("All options saved correctly.");
    }
    else {
        $return_status = "Watch out";
        $return_string = "Options saved but we found errors in the following fields: ";
        foreach($errors as $err) {
            $return_string .= " " . $err;
        }
        $return_string .= ". Please review and try again";
    }

    echo json_encode(array($return_status, $return_string));
    die();
}


function all_event_registrations() {
    global $wpdb;
    $table = $wpdb->prefix . 'registrations';
    $result = $wpdb->get_results ( "SELECT * FROM $table" );

    echo json_encode($result);
    die();
}


function edit_registration($data) {
    $x = $data->get_json_params();
    $cleaned_data = array_map('sanitize_it', $x);
    if(isset($cleaned_data['id'])) $reg_id = $cleaned_data['id'];
    $reg_action = $cleaned_data['command'];

    // Based on command given, we either channel the request into one of 3 different actions: to create, edit, or delete a registration.
    switch ($reg_action) {
    case 'create':
        $res = create_new_registration($cleaned_data);
        break;
    case 'edit':
        $res = edit_existing_registration($cleaned_data);
        break;
    case 'delete':
        $res = delete_existing_registration($cleaned_data);
        break;
    default: 
        //If we can't figure it out, bail and return error message.
        echo json_encode(array('Sorry!', 'Could not identify the correct registration record to update.'));
        die();
    }

    //Res will be a two-part array with STATUS and a user-friendly message.
    echo json_encode($res);
    die();
}

function create_new_registration($data) {
    // Ghetto fix for first name
    $data['mkt'] = true;
    $data['postcode'] = "";
    if(isset($data['name'])) $data['fname'] = $data['name'];
    if(isset($data['surname'])) $data['lname'] = $data['surname'];
    if(isset($data['street_address'])) $data['address'] = $data['street_address'];
    if(isset($data['office_phone'])) $data['office'] = $data['office_phone'];
    if(isset($data['mobile_phone'])) $data['mobile'] = $data['mobile_phone'];

    require plugin_dir_path( __FILE__ ) . '/checkout-scripts/EventerRegistrations.php';
    $create = new EventerRegistration($data);
    
    if($create->registration_id !== false) {
        $create->confirmFreeUser(true);
		$val = array("Success", "New registration successfully created");
		return $val;
	}
	$val = array("Uh oh", "Registration was not created. We don't know any more than that, sorry.");
    return $val;
}

function edit_existing_registration($data) {
    global $wpdb;
	$my_table = $wpdb->prefix . 'registrations';
	$id = intval($data['id']);

	$email = $data['email'];
	$name = $data['name'];
	$surname = $data['surname'];
	$company = $data['company'];
    $company_is = $data['my_company_is'];
	$role = $data['role'];
    $city = $data['city'];
    $country = $data['country'];
    $mobile_phone = $data['mobile_phone'];
    $office_phone = $data['office_phone'];
    $postcode = $data['postcode'];
    $street_address = $data['street_address'];
    $website = $data['website'];
		
	$chk = $wpdb->query( $wpdb->prepare( 
		"
			UPDATE $my_table 
			SET name = %s, 
            surname = %s, 
            email = %s, 
            company = %s,
            my_company_is = %s,
            role = %s, 
            city = %s, 
            country = %s, 
            mobile_phone = %s, 
            office_phone = %s, 
            postcode = %s,
            street_address = %s,
			hs_synched = 0,
            website = %s
			WHERE id = %d
		",
	$name, $surname, $email, $company, $company_is, $role, $city, $country, $mobile_phone, $office_phone, $postcode, $street_address, $website, $id
	) );

    if($chk !== false) {
		$val = array("Success", "Registration details updated correctly");
		return $val;
	}
	$val = array("Uh oh", "Registration update failed. We don't know any more than that, sorry.");
    return $val;
}

function delete_existing_registration($data) {
    $id = intval($data['id']);
    $coupon = $data['coupon_code'];
	global $wpdb;
	$reg_table = $wpdb->prefix . 'registrations';
    
    // Also remove from hubspot list
    $vid = $wpdb->get_var( $wpdb->prepare( "SELECT hubspot_id from $reg_table where id = %d", $id ) );
    if($vid !== 'Error') {
        require_once plugin_dir_path( __DIR__ ) . 'eventer/checkout-scripts/HubspotTool.php';
        $res = HubspotTool::removeRegistrantFromHubspotList($vid);
    }

	$res = $wpdb->delete( $reg_table, array( 'id' => $id ) );
    if($res !== false) {
        $coupon_post = get_page_by_title($coupon, OBJECT, 'invitation');
        if(!empty($coupon_post) && $coupon_post != false) {
            $min = intval(get_post_meta($coupon_post->ID, 'actual_uses', true));
            $min--;
            update_post_meta($coupon_post->ID, 'actual_uses', $min);
        }
        
        return array("Success", "Registration deleted successfully.");
    }
    return array("Uh oh", "Registration could not be deleted, sorry.");
}

function sanitize_it($el) {
    $sanitized_el = htmlspecialchars(strip_tags(trim($el)));
    return $sanitized_el;
}

function all_event_coupons() {
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

        $headliners_ids = get_post_meta($sing_inv, 'custom_headliners', false);
        
        $invitation_array[$n]['with_headliners'] = "standard";
        $headliners = array();

        if(!empty($headliners_ids)) {
            $invitation_array[$n]['with_headliners'] = "custom";
            foreach($headliners_ids as $sing_id) {
                $headliners[] = intval($sing_id);
                //$headliners[] = array('id' => $sing_id, 'name' => get_the_title($sing_id));
            }
        } 


        $invitation_array[$n]['invitation_post_id'] = $sing_inv;
        $invitation_array[$n]['coupon_title'] = get_the_title($sing_inv);
        $invitation_array[$n]['invitation_type'] = get_post_meta($sing_inv, 'invitation_type', true);
        $invitation_array[$n]['headliners'] = $headliners;
        $invitation_array[$n]['recipient_name'] = $related_name;
        $invitation_array[$n]['recipient_id'] = $related_id;
        $invitation_array[$n]['max_uses'] = get_post_meta($sing_inv, 'max_uses', true);
        $invitation_array[$n]['actual_uses'] = get_post_meta($sing_inv, 'actual_uses', true);
        $invitation_array[$n]['discount'] = get_post_meta($sing_inv, 'percentage_value', true);
        $invitation_array[$n]['guest_status'] = get_post_meta($sing_inv, 'for_guests', true);
        $invitation_array[$n]['permalink'] = get_permalink($sing_inv);
        $n++;
    }
    
    echo json_encode($invitation_array);
    die();
}

function all_people_and_orgs() {
    $args = array(
        'post_type' => array('speaker', 'company'),
        'numberposts' => -1,
    );
    $res = get_posts($args);

    $n = 0;
    $res_array = array();
    foreach($res as $peep) {
        $res_array[$n]['id'] = $peep->ID;
        $res_array[$n]['name'] = $peep->post_title;
        $n++;
    }
    echo json_encode($res_array);
    die();
}

function just_the_people() {
    $args = array(
        'post_type' => 'speaker',
        'numberposts' => -1,
    );
    $res = get_posts($args);

    $n = 0;
    $res_array = array();
    foreach($res as $peep) {
        $res_array[$n]['id'] = $peep->ID;
        $res_array[$n]['name'] = $peep->post_title;
        $n++;
    }
    echo json_encode($res_array);
    die();
}

function edit_coupon_or_invitation($data) {
    $x = $data->get_json_params();
    $y = $x;
    unset($y['headliners']);
    $cleaned_data = array_map('sanitize_it', $y); 
    $cleaned_headliners = array();

    //Sanitize, validate headliners separately then return to cleaned array as they get killed by 'sanitize_it' function.
    if(!empty($x['headliners'])) {
        foreach($x['headliners'] as $sing_headliner) {
            if('publish' === get_post_status( $sing_headliner )) {
                $cleaned_headliners[] = $sing_headliner;
            }
        }
    }
    $cleaned_data['headliners'] = $cleaned_headliners;
    if(!empty($cleaned_data['invitation_post_id'])) {
        $reg_id = $cleaned_data['invitation_post_id'];
    }
    $reg_action = $cleaned_data['command'];
    
    switch ($reg_action) {
        case 'create':
            $res = create_new_coupon($cleaned_data);
            break;
        case 'edit':
            $res = edit_existing_coupon($cleaned_data);
            break;
        case 'delete':
            $res = delete_existing_coupon($reg_id);
            break;
        default: 
            //If we can't figure it out, bail and return error message.
            echo json_encode(array('Sorry!', 'Could not identify the correct coupon record to update.'));
            die();
        }

    print_r(json_encode($res));
    die();
}

function create_new_coupon($data) {
    if(isset($data['guest_status'])) {
    	$for_guests = $data['guest_status'];
	} else {
		$for_guests = false;
	}

    require_once( ABSPATH . 'wp-admin/includes/post.php' );
    $exists_already_check = post_exists($data['coupon_title']);
    if(0 !== $exists_already_check) {
        return array('Uh oh.', 'A coupon/invitation with this title already exists. Please try using a different code.');
    }

    //If non-named coupon/invitation...
    if($data['recipient_id'] === "other") {
        $related_post = null;
        $given_recipient = $data['recipient_name'];
    } else {
    //But if we have a related post instead, then...
        $related_post = $data['recipient_id'];
        $given_recipient = null;
    }

    if(!empty($data['headliners']) && $data['with_headliners'] == "custom") {
        $new_headliners = $data['headliners'];
    } else {
        $new_headliners = null;
    }

    $args = array(
        'title' => $data['coupon_title'],
        'invitation_type' => $data['invitation_type'],
        'custom_headliners' => $new_headliners,
        'for_guests' => $for_guests,
        'related_post' => $related_post,
        'recipient' => $given_recipient,
        'percentage_value' => $data['discount'],
        'max_uses' => $data['max_uses'],
    );

    $pod = pods('invitation');
    $new_invitation = $pod->add( $args );
    if('publish' === get_post_status( $new_invitation )) {
        return array('Great!', 'New coupon successfully created.');
    }
    return array('Uh oh.', 'Something went wrong and we could not save this coupon/invitation. Sorry.');
}
function edit_existing_coupon($data) {
    $id = intval($data['invitation_post_id']);
    $for_guests = $data['guest_status'];

    //If non-named coupon/invitation...
    if($data['recipient_id'] === "other") {
        $related_post = null;
        $given_recipient = $data['recipient_name'];
    } else {
    //But if we have a related post instead, then...
        $related_post = $data['recipient_id'];
        $given_recipient = null;
    }

    if(!empty($data['headliners']) && $data['with_headliners'] == "custom") {
        $new_headliners = $data['headliners'];
    } else {
        $new_headliners = null;
    }

    $args = array(
        'title' => $data['coupon_title'],
        'invitation_type' => $data['invitation_type'],
        'custom_headliners' => $new_headliners,
        'for_guests' => $for_guests,
        'related_post' => $related_post,
        'recipient' => $given_recipient,
        'percentage_value' => $data['discount'],
        'max_uses' => $data['max_uses'],
    );

    $pod = pods('invitation', $id);
    $new_invitation = $pod->save( $args );
    if('publish' === get_post_status( $new_invitation )) {
        return array('Great!', 'Coupon/invitation ' . $data['coupon_title'] . ' (ID ' . $id . ') was successfully updated.');
    }
    return array('Uh oh.', 'Something went wrong and we could not update this coupon/invitation. Sorry.');
}

function delete_existing_coupon($id_to_delete) {
    if('publish' === get_post_status( $id_to_delete )) {
        wp_delete_post($id_to_delete);
        return array("Success", "Invitation/coupon " . $id_to_delete . " was successfully deleted from the system.");
    }
    return array("Hmm...", "Something went wrong and we couldn't find the invitation/coupon you tried to delete.");
}

function hubspot_sync($new_data) {
    $data = $new_data->get_json_params();
	
	// Ghetto fix again. Should probably be its own function...
	$data['mkt'] = true;
    if(!isset($data['postcode'])) $data['postcode'] = "";
    if(isset($data['name'])) $data['fname'] = $data['name'];
    if(isset($data['surname'])) $data['lname'] = $data['surname'];
    if(isset($data['street_address'])) $data['address'] = $data['street_address'];
    if(isset($data['office_phone'])) $data['office'] = $data['office_phone'];
    if(isset($data['mobile_phone'])) $data['mobile'] = $data['mobile_phone'];
	

    require_once plugin_dir_path( __DIR__ ) . 'eventer/checkout-scripts/HubspotTool.php';
    $res = HubspotTool::createNewHubspotPerson($data);

    if($res !== null) {
        global $wpdb;
	    $my_table = $wpdb->prefix . 'registrations';
	    $id = intval($data['id']);

        $email = $data['email'];
        $name = $data['name'];
        $surname = $data['surname'];
        $company = $data['company'];
        $company_is = $data['my_company_is'];
        $role = $data['role'];
        $city = $data['city'];
        $country = $data['country'];
        $mobile_phone = $data['mobile_phone'];
        $office_phone = $data['office_phone'];
        $postcode = $data['postcode'];
        $street_address = $data['street_address'];
        $website = $data['website'];
		
        $chk = $wpdb->query( $wpdb->prepare( 
            "
                UPDATE $my_table 
                SET name = %s, 
                surname = %s, 
                email = %s, 
                company = %s,
                my_company_is = %s,
                role = %s, 
                city = %s, 
                country = %s, 
                mobile_phone = %s, 
                office_phone = %s, 
                postcode = %s,
                street_address = %s,
                website = %s,
                hs_synched = %s
                WHERE id = %d
            ",
        $name, $surname, $email, $company, $company_is, $role, $city, $country, $mobile_phone, $office_phone, $postcode, $street_address, $website, "1", $id
        ) );
    }
    
    if($chk !== false) {
		$val = array("Success", "User successfully synced with Hubspot.");
		return $val;
	}
	$val = array("Uh oh", "Sync with Hubspot failed. We don't know any more than that, sorry.");
    return $val;
    die();
}

function all_sync() {
    require_once plugin_dir_path( __DIR__ ) . 'eventer/checkout-scripts/RegistrationsSync.php';
    $res = new RegistrationsSync;
    return array("Success", "Hubspot sync successfully carried out.");
    die();
}

function do_speaker_codes() {
        //Get all speaker coupons
        $args = array(
            'post_type' => 'invitation',
            'numberposts' => -1,
            'fields' => 'ids'
        );
        $coupons = get_posts($args);
    
        foreach($coupons as $single) {
            $related = get_post_meta($single, 'related_post', true);
            if(!empty($related) && 'publish' == get_post_status( $related ) && has_category('2023', $related)) {
                $mail = get_post_meta($related, 'email-add', true);
                if (strpos($mail, '@') !== false) {
                    $speaker_coupons[] = array(
                            'email' => $mail,
                            'coupon' => get_the_title($single)
                    );
                }
            }
        }
        
        require_once plugin_dir_path( __DIR__ ) . 'eventer/registrations/add_registrant_to_hubspot.php';
        $results = array(
            'good' => 0,
            'bad' => 0
        );
        foreach($speaker_coupons as $speaker_data) {
            $fields = array(
                'properties' => array(
                    array(
                        'property' => 'email',
                        'value'	=> $speaker_data['email']
                    ),
                    array(
                        'property' => 'event_coupon_code',
                        'value'	=> $speaker_data['coupon']
                    ),
                )
            );

            $url = "https://api.hubapi.com/contacts/v1/contact/createOrUpdate/email/" . $speaker_data['email'];
            $fields_string = json_encode($fields); 
            $response = trigger_hubspot_curl($url, $fields, $fields_string);

            if(isset($response->vid)) {
                file_put_contents('hubspot_log.txt', "CONTACT ID: " . $response->vid . "\n", FILE_APPEND);
                set_up_and_send_list_add($response->vid);
                $results['good']++;
            } else {
                $results['bad']++;
            }
			$t = time();
            file_put_contents('hubspot_log.txt', "TIME: " . $t . "\n", FILE_APPEND);
	        file_put_contents('hubspot_log.txt', print_r($fields, true . "\n"), FILE_APPEND);
        }
        $num = $results['good'] + $results['bad'];
        return array('Results', 'We attempted to push a total of ' . $num . ' speakers into Hubspot.' . $results['good'] . ' were successful.');
        die();
}

function resend_welcome_mail($new_data) {
    $data = $new_data->get_json_params();
    $mail = $data['email'];
    $name = $data['name'];
    $surname = $data['surname'];
    if(!isset($mail) || empty($mail)) return array("Error", "No email address was provided for this registered user.");
    if(!isset($name) || empty($name)) $name = "Friend";
    if(!isset($surname) || empty($surname)) $surname = "";

    //$welcome_mail = new MailFunction($mail, $name, $surname, "welcome");

    global $wpdb; 
    $my_table = $wpdb->prefix . 'registrations';
    $wpdb->query( $wpdb->prepare( 
        "
            UPDATE $my_table 
            SET welcome_email_sent = 1
            WHERE id = %d
        ",
        intval($data['id'])
    ) );

    return array("Success", "Welcome email correctly sent to user " . $mail);
    die();
}

// NEW VERSION 6
function print_array_of_badges($raw_data) {

    $data = $raw_data->get_json_params();

	if(!is_array($data['ids'])) {
    	$ids[] = $data['ids'];
	} else {
		$ids = $data['ids'];
	}
	
    if(empty($ids)) return array("Error", "No registration IDs were provided.");

    require_once plugin_dir_path( __DIR__ ) . 'eventer/BadgeBuilder.php';

    $output = array();
    $errors = array();

    if(count($ids) > 1) {

        BadgeBuilder::kill_the_old_badges();
        BadgeBuilder::make_the_badge_folder();

        foreach($ids as $single_id) {
            $badge = new BadgeBuilder($single_id, false); //second var denotes whether we're printing a single badge or not
            $output[] = $badge->badge_output; // Single url of file
            if(!empty($badge->errors)) {
                $errors[] = array(
                    'id' => $badge->id, // Single id of badge item (i.e. registration ID) with issues
                    'errors' => implode(' -> ', $badge->errors), // All errors for this badge item
                );
            }
        }

        $return_files = BadgeBuilder::zipOutput();
        $link_string = " <a href='$return_files' target='_blank' download>$return_files</a>";
        $message = "All badges were successfully generated and are available for download here:" . $link_string;

    } else {

        $badge = new BadgeBuilder($ids[0], true);
        $output[] = " <a href='$badge->badge_output' target='_blank' download>$badge->badge_output</a>"; // Single url of file
        if(!empty($badge->errors)) {
            $errors[] = array(
                'registration_id' => $badge->id, // Single id of badge item (i.e. registration ID) with issues
                'errors' => implode(' -> ', $badge->errors), // All errors for this badge item
            );
            return array("Error", "Please try again. There were errors with the following: " . json_encode($errors));
        }

        $return_files = $output[0];
        $message = "Your badge was successfully generated and is available for download here:" . $return_files;    

    }

    if(!empty($errors)) {
        return array("Error", "Please try again. There were errors with the following: " . json_encode($errors));
    }

    return array("Success", $message);

}



function add_ad_hoc_registration($raw_data) {

    $data = $raw_data->get_json_params();

    $data['title'] = null;
    if(isset($data['office'])) $data['office_phone'] = $data['office'];
    $data['website'] = 'https://www.unknown.com';
    $data['address'] = 'Not requested';
    $data['postcode'] = 'Not requested';
    $data['city'] = 'Not requested';
    $data['country'] = 'Not requested';
    $data['mkt'] = true;
    $data['interests'] = 'Not requested';
    $data['paid'] = 0;
    $data['payment_status'] = 'Manual data entry';
    $data['sign_up_date'] = date('Y-m-d');
    $data['printed'] = 0;
    $data['badge_link'] = null;
    $data['hubspot_id'] = null;
    $data['hs_synched'] = 0;
    $data['welcome_email_sent'] = 0;
    $data['checked_in'] = "1";

    // Hacky stuff that'll need to get fixed sooner or later....
    if(isset($data['name'])) $data['fname'] = $data['name'];
    if(isset($data['surname'])) $data['lname'] = $data['surname'];

    require plugin_dir_path( __FILE__ ) . '/checkout-scripts/EventerRegistrations.php';
    $create = new EventerRegistration($data);
    
    // If registration worked, we can confirm user
    if(empty($create->registration_id)) {
        return array("Error", "Registration was not created. We don't know any more than that, sorry.");
    }
    $create->confirmFreeUser(true);

    // If user confirmation worked, we can print badge
    require_once plugin_dir_path( __DIR__ ) . 'eventer/BadgeBuilder.php';
    $badge = new BadgeBuilder($create->registration_id, true);

    if(!empty($badge->errors)) {
        $errors[] = array(
            'registration_id' => $badge->id, // Single id of badge item (i.e. registration ID) with issues
            'errors' => implode(' -> ', $badge->errors), // All errors for this badge item
        );   
        return array("Error", "We had trouble creating user and printing badge. Please check following: " . json_encode($errors));
    }

    $output[] = " <a href='$badge->badge_output' target='_blank' download>$badge->badge_output</a>"; // Single url of file

    $return_files = $output[0];
    $message = "User registered and badge is available for printing: " . $return_files;   

    $val = array("Success", $message);
    return $val;

}


function do_single_check_in($raw_data) {

    $data = $raw_data->get_json_params();

    $id = $data['id'];
    $command = $data['cmd'];

    if($command === 'add') {

        global $wpdb;
        $my_table = $wpdb->prefix . 'registrations';
        $wpdb->query( $wpdb->prepare( 
            "
                UPDATE $my_table 
                SET checked_in = 1
                WHERE id = %d
            ",
            intval($id)
        ) );

        return array("Success", "User " . $id . " was successfully checked in.");
    }

    if($command = 'remove') {
        global $wpdb;
        $my_table = $wpdb->prefix . 'registrations';
        $wpdb->query( $wpdb->prepare( 
            "
                UPDATE $my_table 
                SET checked_in = 0
                WHERE id = %d
            ",
            intval($id)
        ) );

        return array("Success", "User " . $id . " was successfully checked out.");
    }

}


function wipe_badge_printed_list() {

    $user_id = get_current_user_id();
    if(!current_user_can('edit_posts')) return array("error", "You do not have permission to complete this task.");

    global $wpdb;
    $my_table = $wpdb->prefix . 'registrations';
    $wpdb->query( $wpdb->prepare( 
        "
                    UPDATE $my_table 
                    SET printed = 0,
                    badge_link = null
                ",
    ) );

    return array("success", "Badges successfully reset to 'not printed'.");

}
