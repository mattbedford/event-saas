<?php


class RegistrationsSync {

    public $contacts = array();
    public $vids = array();
    public $emails = array();
    private $list;
    private $key;
    private $flag;
    private $offset;
    private $existing;

    function __construct() {

        $this->list = get_option('hubspot_list');
        $this->key = get_option('hubspot_key');
        $this->offset = 0;
        $this->flag = true;
        $this->runContactsLoop();
        $this->grab_data_from_contacts();
        $this->list_all_existing_registrations();
        $this->find_the_missing();

    }


    private function runContactsLoop() {
    
        while($this->flag === true) {
	
            $data = $this->poll_hubspot_list();
            $json_data = (array)json_decode($data);	
            $this->contacts = array_merge($this->contacts, $json_data['contacts']);
            
            $this->offset = $json_data['vid-offset'];

            if($json_data['has-more'] != 'true') {
                $this->flag = false;
            }
        
        }

    }

    
    private function poll_hubspot_list() {
        
        $url = 'https://api.hubapi.com/contacts/v1/lists/';
        $url .= $this->list;
        $url .= '/contacts/all?count=100&&property=firstname&property=lastname&property=phone&property=website&property=email&property=company_type&property=company&property=address&property=zip&property=country&property=city&property=jobtitle&vidOffset=';
        $url .= $this->offset;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type:application/json',
            'authorization: Bearer ' . $this->key
        ));
    
        $data = curl_exec($curl);
        curl_close($curl);
        return $data;

    }


    private function grab_data_from_contacts() {
        foreach($this->contacts as $single_contact) {
            $this->vids[] = $single_contact->vid;
            $this->emails[] = $single_contact->properties->email->value;
        }
    }


    private function list_all_existing_registrations() {

        global $wpdb;
        $table = $wpdb->prefix . 'registrations';
        $exists = $wpdb->get_results ( "SELECT DISTINCT hubspot_id FROM $table", ARRAY_A );
				
				foreach($exists as $key => $val) {
					$this->existing[] = $val['hubspot_id'];
				}

    }


    private function find_the_missing() {	
			
				$missing = array_diff($this->vids, $this->existing);
							
				if(count($missing) < 0) {					
						return;
				}

        foreach($this->contacts as $item => $data) {

            if(in_array($data->vid, $missing)) {

                $missing_registrant = $this->extract_data($data);
				require_once 'EventerRegistrations.php';
				$res = new EventerRegistration($missing_registrant);
              	EventerRegistration::confirmAnyUser(0, 'Import from Hubspot', $res->registration_id, true);
				$this->add_hubspot_synced_flag($res->registration_id);

            }

        }

    }


    private function extract_data($data) {

        $result = array();

        foreach($data->properties as $key => $val) {
            $keyval_arr[$key] = $this->validate_hubspot_return_vals($key, $val->value);
        }
			
        $result['hubspot_id'] = $data->vid;
        $result['title'] = $this->validate_hubspot_return_vals('title', $keyval_arr['title']);
        $result['fname'] = $this->validate_hubspot_return_vals('name', $keyval_arr['firstname']);
        $result['lname'] = $this->validate_hubspot_return_vals('surname', $keyval_arr['lastname']);
        $result['email'] = $this->validate_hubspot_return_vals('email', $keyval_arr['email']);
        $result['company'] = $this->validate_hubspot_return_vals('company', $keyval_arr['company']);
        $result['my_company_is'] = "Other";
        //$result['my_company_is'] = $this->validate_hubspot_return_vals('company_type', $keyval_arr['company_type']);
        $result['role'] = $this->validate_hubspot_return_vals('role', $keyval_arr['jobtitle']);
        $result['office_phone'] = $this->validate_hubspot_return_vals('office_phone', $keyval_arr['phone']);
        $result['website'] = $this->validate_hubspot_return_vals('website', $keyval_arr['website']);
        $result['street_address'] = $this->validate_hubspot_return_vals('address', $keyval_arr['address']);
        $result['postcode'] = $this->validate_hubspot_return_vals('zip', $keyval_arr['zip']);
        $result['city'] = $this->validate_hubspot_return_vals('city', $keyval_arr['city']);
        $result['country'] = $this->validate_hubspot_return_vals('country', $keyval_arr['country']);
        $result['mobile_phone'] = $this->validate_hubspot_return_vals('mobile', $keyval_arr['mobile']);
        $result['hs_synched'] = 1;
        $result['mkt'] = 1;
        $result['printed'] = 0;
        $result['paid'] = 0;
        $result['payment_status'] = "Hubspot import"; 
      
        return $result;

    }


    private function validate_hubspot_return_vals($key, $val) {
        if(!isset($val) || $val == NULL || empty($val) || $val == "" || $val == " " ) {
            $val = "Unknown";
            if ($key == "company_type") $val = "Other";
            if ($key == "gender" || $key == "title") $val = NULL;
        }
        return $val;
    }
	
	
	private function add_hubspot_synced_flag($registration_id) {
		
		global $wpdb; 
		$my_table = $wpdb->prefix . 'registrations';
		$wpdb->query( $wpdb->prepare( 
         "
             UPDATE $my_table 
             SET hs_synched = 1
             WHERE id = %d
         ",
            intval($registration_id)
        ) );
		
		
	}


}

?>
