<?php

    add_action( 'gform_after_submission_4', 'mha_s2s_salsa_signup', 10, 2 );
    function mha_s2s_salsa_signup( $entry, $form ) {
    
        $body = array(
            'first_name' => rgar( $entry, '1' ),
            'last_name' => rgar( $entry, '6' ),
            'email' => rgar( $entry, '2' ),
            'phone' => rgar( $entry, '4' ),
        );

        // Salsa token
        $api_token = SALSA_API_KEY;

        // Gather the data to send
        $post_data = [];
        $post_data['id'] = null;
        $post_data['payload'] = array();
        $post_data['payload']['supporters'][0] = [
            "readOnly"                  => false,			
            "sourceTrackingCode"        => 'mha_newsletter_s2s_signup',
            "updateSourceTrackingCode"  => true,
            "firstName"                 => $body['first_name'],
            "lastName"                  => $body['last_name']
        ];

        // Email
        $post_data['payload']['supporters'][0]['contacts'][] = array(
            "type"      => "EMAIL",
            "value"     => $body['email'],
            "status"    => "OPT_IN",
        );
        
        // Phone number
        if($body['phone'] != ''){
            $post_data['payload']['supporters'][0]['contacts'][] = [
                "type"      => "CELL_PHONE",
                "value"     => $body['phone'],
                "status"    => "OPT_IN",
                "smsStatus" => "OPT_IN"
            ];
            // $post_data['payload']['supporters'][0]['cellPhoneOnly'] = true;
        }

        // Salsa data wrap up
        $salsa_data = json_encode($post_data);

        // Send it to Salsa    
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.salsalabs.org/api/integration/ext/v1/supporters",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => $salsa_data,
            CURLOPT_HTTPHEADER => [
                'authToken: '.$api_token,
                'content-type: application/json'
            ]
        ]);	
        $response = curl_exec($curl);
        $error = curl_error($curl);	
        curl_close($curl);	

        // GFCommon::log_debug( 'Salsa Response: body => ' . print_r( $response, true ) );
        // GFCommon::log_debug( 'Salsa Error: body => ' . print_r( $error, true ) );
        
    }