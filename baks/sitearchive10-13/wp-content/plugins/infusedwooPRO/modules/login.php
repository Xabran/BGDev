<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action('wp_login', 'ia_woocommerce_refresh_admin', 10, 2);		
add_action('wp_login', 'ia_woocommerce_refresh_customer', 10, 2);

function ia_woocommerce_refresh_admin($user_login, $user) {		
	global $iwpro;				
	if (user_can( $user, 'publish_posts' )) {
		$iwpro->ia_woocommerce_update_product_options();
	} 
}

function ia_woocommerce_refresh_customer($user_login, $user) {
	global $woocommerce;
	global $iwpro;
	
	if($iwpro->ia_app_connect()) {
		$userEmail  = $user->user_email;	
		$countries 	= new WC_Countries();
		$countries 	= array_flip($countries->countries);

		if(!empty($userEmail)) {
			$contactinfo = array(
				'Id'				,
				'FirstName' 		,
				'LastName' 			,
				'Phone1' 			,
				'StreetAddress1' 	,
				'StreetAddress2' 	,
				'City' 				,
				'State' 			,
				'Country' 			,
				'PostalCode' 		,
				'Company'
			);
			
			$billing_fname  = get_user_meta($user->ID, 'billing_first_name', true);
			
			if(empty($billing_fname)) {
				$contact = $iwpro->app->dsFind('Contact',5,0,'Email',$userEmail,$contactinfo);

				if(isset($contact[0])) {
					$contact = $contact[0];
					
					if(isset($contact['Country'])) {
						$countryfull    = $contact['Country'];
						$country 		= $countries[$countryfull];
					}
					
					if(!empty($contact['Id'])) { 
						if(isset($contact['FirstName'])) update_user_meta( $user->ID, 'billing_first_name', 	$contact['FirstName']  );
						if(isset($contact['LastName'])) update_user_meta( $user->ID, 'billing_last_name', 	$contact['LastName']  );
						update_user_meta( $user->ID, 'billing_email', 		$userEmail  );
						if(isset($contact['Phone1'])) update_user_meta( $user->ID, 'billing_phone', 		$contact['Phone1']  );
						if(isset($contact['StreetAddress1'])) update_user_meta( $user->ID, 'billing_address_1', 	$contact['StreetAddress1']  );
						if(isset($contact['StreetAddress2'])) update_user_meta( $user->ID, 'billing_address_2', 	$contact['StreetAddress2']  );
						if(isset($contact['City'])) update_user_meta( $user->ID, 'billing_city', 		$contact['City']  );
						if(isset($contact['State'])) update_user_meta( $user->ID, 'billing_state', 		$contact['State']  );
						if(isset($country)) update_user_meta( $user->ID, 'billing_country', 	$country );
						if(isset($contact['PostalCode'])) update_user_meta( $user->ID, 'billing_postcode', 	$contact['PostalCode']  );
						if(isset($contact['Company'])) update_user_meta( $user->ID, 'billing_company', 	$contact['Company']  );
					}
				}
			}			
		}
	}						
}

