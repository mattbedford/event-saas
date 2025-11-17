<?php

class EventerRegistration {

    public $data;
    public $hubspot_id;
    public $hubspot_sync_status;
    public $event_status;
    public $registration_id;


    function __construct($form_data) { 
        $this->event_status = "pending";
        $this->registration_id = false;
        $this->data = $form_data;

        $hubspot_values = $this->checkHubspotId();

        $this->hubspot_id = $hubspot_values[0];
        $this->hubspot_sync_status = $hubspot_values[1];
        $this->addUserToDb();
    }


    private function checkHubspotId() {
        require_once "HubspotTool.php";
        // Return two-part array: hubspot ID and whether-synced
        $res = HubspotTool::hubspotExistsHandler($this->data);
        return $res;
    
    }


    private function addUserToDb() {
        global $wpdb;
	
        $presumed_payment = 0;
        $table_name = $wpdb->prefix . 'registrations';
        $sign_up_date = date("Y-m-d H:i:s");

        if(!isset($this->data['coupon']) || empty($this->data['coupon'])) {
            $p = get_option('ticket_price');
            $presumed_payment = intval(preg_replace("/[^0-9.]/", "", $p));
            $this->data['coupon'] = 'none';
        }

        if(!isset($this->data['title']) || empty($this->data['title'])) {
            $this->data['title'] = null;
        }

        if(!isset($this->data['interests']) || empty($this->data['interests'])) {
            $this->data['interests'] = null;
        }

        if(!isset($this->data['mobile']) || empty($this->data['mobile'])) {
            $this->data['mobile'] = null;
        }

        if(!isset($this->data['checked_in']) || empty($this->data['checked_in'])) {
            $checked_in = '0';
        } else {
            $checked_in = '1';
        }
        
        $wpdb->insert( 
            $table_name, 
            array( 
                'title' => $this->data['title'],
                'name' => $this->data['fname'], 
                'surname' => $this->data['lname'],
                'email' => $this->data['email'], 
                'company' => $this->data['company'],
                'my_company_is' => $this->data['my_company_is'],
                'street_address' => $this->data['address'],
                'city' => $this->data['city'],
                'country' => $this->data['country'],
                't_and_c' => $this->data['mkt'],
                'interests' => $this->data['interests'],
                'mobile_phone' => $this->data['mobile'],
                'office_phone' => $this->data['office'],
                'website' => $this->data['website'],
                'postcode' => $this->data['postcode'],
                'role' => $this->data['role'],
                'paid' => $presumed_payment,
                'hubspot_id' => $this->hubspot_id,
                'payment_status' => $this->event_status,
                'coupon_code' => $this->data['coupon'],
                'sign_up_date' => $sign_up_date,
                'printed' => '0',
                'hs_synched' => $this->hubspot_sync_status,
                'checked_in' => $checked_in
            ) 
        );
        
        if($wpdb->insert_id !== false) {
           $this->registration_id = $wpdb->insert_id;
        } 
    }




    // Two ways into the same update/confirm function.
    // First one is only called on valid coupon entry or manual data entry.
    // Second method is called direct from success template after someone goes through Stripe
    public function confirmFreeUser($block_mail = false) {
		$this->updateCouponUsageCount();
        if($block_mail !== false) {
            self::confirmAnyUser(0, "Manual data entry", $this->registration_id, true);
        } else {
            self::confirmAnyUser(0, "Free entry", $this->registration_id);
        }
    }

  // Only for registering speakers
    public function confirmSpeakerRegistration() {
		$this->updateCouponUsageCount();
            self::confirmAnyUser(0, "Speaker sign-up form", $this->registration_id, true);
    }

    public static function confirmAnyUser($price_paid, $payment_status, $registration_id, $block_mail = false) {
        // - Updates user payment status in database
        // - Sends a welcome mail and updates in database TO DO: Merge these DB queries into one.
        // - Adds user to confirmed list in HS.

        global $wpdb;
        $my_table = $wpdb->prefix . 'registrations';
         
        $query = $wpdb->get_results( $wpdb->prepare( 
            "
                SELECT * 
                FROM $my_table 
                WHERE id = %d
            ",
            intval($registration_id)
        ));

		$user_data = $query[0];
		
        $welcome = 0;

        if($block_mail === false) {
            //$welcome_mail = new MailFunction($user_data->email, $user_data->name, $user_data->surname, "welcome");
            //$welcome = 1;
        }

        $wpdb->query( $wpdb->prepare( 
         "
             UPDATE $my_table 
             SET paid = %d,
             payment_status = %s,
             welcome_email_sent = %d
             WHERE id = %d
         ",
            $price_paid, $payment_status, $welcome, intval($registration_id)
        ) );

		require_once "HubspotTool.php";
        HubspotTool::addRegistrantToHubspotList($user_data->hubspot_id);
		
    }
	
	private function updateCouponUsageCount() {
		
		if(!isset($this->data['coupon']) || empty($this->data['coupon'])) return; 

        require_once "CouponValidator.php";
        $coupon = new CouponValidator($this->data['coupon']);

		if($coupon->coupon_result === 'zerotopay' || is_numeric($coupon->coupon_result)) {
			$new_coupon_usage_count = intval($coupon->actual_uses);
			$new_coupon_usage_count++;
			update_post_meta($coupon->invitation_post_id, 'actual_uses', $new_coupon_usage_count);
		}
		
	}
	

}
