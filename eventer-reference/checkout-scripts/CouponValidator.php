<?php

class CouponValidator {

    /**
     * 
     * Takes in a coupon code and makes available the result: a string containing either:
     * badcoupon
     * couponlimit
     * couponnotexist
     * zerotopay
     * 
     * OR an integer value of the amount owing after that coupon has been applied.
     * 
     * Result is available in the public coupon_result prop.
     * 
     */


    public $coupon_result;
    public $invitation_post_id;
    private $max_uses;
    public $actual_uses;
    private $discount;
    public $coupon_code;
    private $amount_to_pay;


    function __construct($entered_coupon_code) {
       
		$this->coupon_code = strtoupper($entered_coupon_code);
        $this->findCoupon();
        
        $p = get_option('ticket_price');
        $this->amount_to_pay = intval(preg_replace("/[^0-9.]/", "", $p));

        if($this->invitation_post_id === false) return;

        $this->max_uses = get_post_meta($this->invitation_post_id, 'max_uses', true);
	    $this->actual_uses = get_post_meta($this->invitation_post_id, 'actual_uses', true);
	    $this->discount = get_post_meta($this->invitation_post_id, 'percentage_value', true);

        $this->checkCouponValidity();
    }


    private function findCoupon() {
        
        $invitation_object = get_page_by_title( $this->coupon_code, OBJECT, 'invitation' );
        
        if(empty($invitation_object) || !isset($invitation_object->ID)) { 
            $this->coupon_result = "badcoupon";
            $this->invitation_post_id = false;
			return;
        } 

        $this->invitation_post_id = $invitation_object->ID;

    }


    private function checkCouponValidity() {
        
        if( empty($this->max_uses) ) {
            $this->max_uses = 999;
        }
    
        if( empty($this->actual_uses) ) {
            $this->actual_uses = 0;
        }
    
        if( empty($this->discount) ) {
            $this->discount = 0;
        }

        if($this->actual_uses >= $this->max_uses) {
            $this->coupon_result = "couponlimit";
            return;
        }

        if(!isset($this->discount) || $this->discount === false) {
            $this->coupon_result = "couponnotexist";
            return;
        }

        $discount_percentage = $this->amount_to_pay / 100; //Full price as a percentage
	    $amount_to_discount = $discount_percentage * $this->discount; //value to knock off main price
		
		$end_total_owing = $this->amount_to_pay - $amount_to_discount;//Final price to pay on checkout
		if($end_total_owing <= 0) {
			$this->coupon_result = "zerotopay";
		} else {
			$this->coupon_result = $end_total_owing;
		}

    }

}