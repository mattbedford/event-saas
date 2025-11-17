<?php

/**
 * HubspotTool Class. Manages interactions with Hubspot.
 * 
 * Static methods include:
 * hubspotExistsHandler -> to return HS id (or create new HS contact and then return id)
 * addRegistrantToHubspotList -> takes a VID and pushes it into HS event list
 * 
 */

class HubspotTool {

    public static function hubspotExistsHandler($data) {

        // Return array of HS user id and sync status.

        $existing_user = self::checkEmailInHubspot($data['email']);

        if($existing_user->total == 1) {
            
            $hs_contact_id = $existing_user->results[0]->id;
            return array($hs_contact_id, false);

        } else {

            $hs_contact_id = self::createNewHubspotPerson($data);

            if($hs_contact_id === null) {
                return array('Error', false);
            }

            return array($hs_contact_id, true);
        }

    }

    public static function addRegistrantToHubspotList($vid) {
        $list_id = get_option('hubspot_list');
	    $url = "https://api.hubapi.com/contacts/v1/lists/" . $list_id . "/add";

        $fields = array(
            'vids' => array(
                $vid
            )
        );

	    $fields_string = json_encode($fields);
	
	    $response = self::triggerHubspotCurl($url, $fields_string);

    }

    public static function removeRegistrantFromHubspotList($vid) {
        $list_id = get_option('hubspot_list');
	    $url = "https://api.hubapi.com/contacts/v1/lists/" . $list_id . "/remove";

        $fields = array(
            'vids' => array(
                $vid
            )
        );

	    $fields_string = json_encode($fields);
	
	    $response = self::triggerHubspotCurl($url, $fields_string);

    }


    
    public static function checkEmailInHubspot($email) {

        $url = "https://api.hubapi.com/crm/v3/objects/contacts/search";
        $data = array(
            "limit" => 1,
            "filterGroups" => array(
                array( 
                    "filters" => array(
                        array(
                            "value" => $email,
                            "propertyName" => "email",
                            "operator" => "EQ"
                        )
                    )
                )
            ),
        );
    
        $existing = self::triggerHubspotCurl($url, json_encode($data));

        return $existing;

    }
    

    public static function createNewHubspotPerson($data) {
        $url = "https://api.hubapi.com/contacts/v1/contact/createOrUpdate/email/" . $data['email'];
       
        $fields = array(
            'properties' => array(
                array(
                    'property' => 'email',
                    'value'	=> $data['email']
                ),
                array(
                    'property' => 'company',
                    'value'	=> $data['company']
                ),
                array(
                    'property' => 'jobtitle',
                    'value'	=> $data['role']
                ),
                array(
                    'property' => 'firstname',
                    'value'	=> $data['fname']
                ),
                array(
                    'property' => 'lastname',
                    'value'	=> $data['lname']
                ),
                array(
                    'property' => 'phone',
                    'value'	=> $data['office']
                ),
                array(
                    'property' => 'website',
                    'value'	=> $data['website']
                ),
                array(
                    'property' => 'address',
                    'value'	=> $data['address']
                ),
                array(
                    'property' => 'zip',
                    'value'	=> $data['postcode']
                ),
                array(
                    'property' => 'city',
                    'value'	=> $data['city']
                ),
                array(
                    'property' => 'country',
                    'value'	=> $data['country']
                ),
            ),
        );

        //Pushing optional fields into submitted data
        if(!empty($data['title'])) {
            $fields['properties'][] = array(
                'property' => 'gender',
                'value'	=> $data['title']
            );
        } 
        
        if(!empty($data['mobile'])) {
            $fields['properties'][] = array(
                'property' => 'mobilephone',
                    'value'	=> $data['mobile']
                );
        }
        
        if(!empty($data['tags'])) {
            $fields['properties'][] = array(
                    'property' => 'description',
                    'value'	=> $data['tags']
                );
        }
        
        
        $fields_string = json_encode($fields);

        $response = self::triggerHubspotCurl($url, $fields_string);

        if(isset($response->vid)) {
            return $response->vid;
        } else {
            return null;
        }
    }


    public static function triggerHubspotCurl($url, $fields_string) {
        $hubspot_token = get_option('hubspot_key');
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type:application/json',
            'authorization: Bearer ' . $hubspot_token
        ));
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $fields_string);

        $data = curl_exec($curl);
        curl_close($curl);
        return json_decode($data);
    }


}