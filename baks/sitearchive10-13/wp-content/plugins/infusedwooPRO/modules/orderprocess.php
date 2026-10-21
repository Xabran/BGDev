<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action('woocommerce_checkout_process' , 'ia_woocommerce_checkout_process',10, 1);

function ia_woocommerce_checkout_process() {
	global $iwpro;

	$infusedwoo_contact_update = apply_filters( 'infusedwoo_contact_update', true );

	if($_POST['payment_method'] != 'infusionsoft') {
		if(!$iwpro->ia_app_connect()) return;


		$returnFields 	= array('Id');	
		$shiptobilling 	= (int) ia_get_post('shiptobilling');
		
		// GET COUNTRY
		$email			= ia_get_post('billing_email');
		if(strpos($email, '@') === false || strpos($email, '.') === false) return;

		$contact 		= $iwpro->app->dsFind('Contact',5,0,'Email',$email,$returnFields); 
		$contact 		= $contact[0];
		
		$firstName		= ia_get_post('billing_first_name');
		$lastName		= ia_get_post('billing_last_name');
		$phone			= ia_get_post('billing_phone');
		
		$b_address1		= ia_get_post('billing_address_1');
		$b_address2		= ia_get_post('billing_address_2');
		$b_city			= ia_get_post('billing_city');
		$b_state		= ia_get_post('billing_state');
		$b_country		= iw_to_country(ia_get_post('billing_country'));
		$b_zip			= ia_get_post('billing_postcode');
		$b_company		= ia_get_post('billing_company');
		
		$s_address1		= $shiptobilling ?	$b_address1 : ia_get_post('shipping_address_1');
		$s_address2		= $shiptobilling ? 	$b_address2	: ia_get_post('shipping_address_2');
		$s_city			= $shiptobilling ? 	$b_city		: ia_get_post('shipping_city');
		$s_state		= $shiptobilling ? 	$b_state	: ia_get_post('shipping_state');
		$s_country		= $shiptobilling ? 	$b_country	: iw_to_country(ia_get_post('shipping_country'));
		$s_zip			= $shiptobilling ? 	$b_zip		: ia_get_post('shipping_postcode');
		
		// Company Selector
		$compId = 0;
		if(!empty($b_company)) {
			$company 		= $iwpro->app->dsFind('Company',5,0,'Company',$b_company,array('Id')); 
			$company 		= $company[0];
			
			if ($company['Id'] != null && $company['Id'] != 0 && $company != false){							
				$compId = $company['Id'];						
			} else {
				$companyinfo = array('Company' => $b_company);
				$compId = $iwpro->app->dsAdd("Company", $companyinfo);
			}
		}
		
		// CONTACT INFO
		$contactinfo = array();
		if(!empty($firstName)) $contactinfo['FirstName'] = stripslashes($firstName);
		if(!empty($lastName)) $contactinfo['LastName'] = stripslashes($lastName);
		if(!empty($phone)) $contactinfo['Phone1'] = stripslashes($phone);
		if(!empty($b_address1)) $contactinfo['StreetAddress1'] = stripslashes($b_address1);
		if(!empty($b_address2)) $contactinfo['StreetAddress2'] = stripslashes($b_address2);
		if(!empty($b_city)) $contactinfo['City'] = stripslashes($b_city);
		if(!empty($b_state)) $contactinfo['State'] = stripslashes($b_state);
		if(!empty($b_country)) $contactinfo['Country'] = stripslashes($b_country);
		if(!empty($b_zip)) $contactinfo['PostalCode'] = stripslashes($b_zip);
		if(!empty($s_address1)) $contactinfo['Address2Street1'] = stripslashes($s_address1);
		if(!empty($s_address2)) $contactinfo['Address2Street2'] = stripslashes($s_address2);
		if(!empty($s_city)) $contactinfo['City2'] = stripslashes($s_city);
		if(!empty($s_state)) $contactinfo['State2'] = stripslashes($s_state);
		if(!empty($s_country)) $contactinfo['Country2'] = stripslashes($s_country);
		if(!empty($s_zip)) $contactinfo['PostalCode2'] = $s_zip;
		if(!empty($b_company)) $contactinfo['Company'] = 	stripslashes($b_company);
		if(!empty($compId)) $contactinfo['CompanyID'] = $compId;
		$contactinfo['ContactType'] = 'Customer';
			
		if(isset($_SESSION['leadsource']) && !empty($_SESSION['leadsource'])) $contactinfo['Leadsource'] = 	$_SESSION['leadsource'];
	
		// GET CONTACT ID
		if ($contact['Id'] != null && $contact['Id'] != 0 && $contact != false){
			   $contactId = (int) $contact['Id']; 
			   if($iwpro->overwriteBD != "yes" && $infusedwoo_contact_update) $contactId = $iwpro->app->updateCon($contactId, $contactinfo);
		} else {
			$contactinfo['Email'] = $email;
			if($infusedwoo_contact_update) $contactId  = $iwpro->app->addCon($contactinfo);
			$iwpro->app->optIn($email,"API: User Purchased from Shop");
		}

		// CREATE REFERRAL: CHECK AFFILIATE													
		$is_aff = isset($_COOKIE['is_aff']) ? (int) $_COOKIE['is_aff'] : "";				
		if( empty($is_aff) ) {					
			if(!empty( $_COOKIE['is_affcode'])) {						
				$returnFields 	= array('Id');						
				$affiliate 		= $iwpro->app->dsFind('Affiliate',1,0,'AffCode', $_COOKIE['is_affcode'], $returnFields);								
				$affiliate		= $affiliate[0];						
				$is_aff 		= (int) $affiliate['Id'];									
			}							
		}							

		if( !empty($is_aff) ) {
			$iwpro->app->dsAdd('Referral', array(			
				'ContactId'   => $contactId,				
				'AffiliateId' => $is_aff,				
				'IPAddress'   => $_SERVER['REMOTE_ADDR'],		
				'Type'	  	  => 0,
				'DateSet'	  => date("Y-m-d")
				)					
			);								
		}				
	}		
	
	if(isset($contactId)) $_SESSION['ia_contactId']  = $contactId;	
}

function ia_get_post($name) {
	if(isset($_POST[$name])) {
		return $_POST[$name];
	}
	return NULL;
}		

?>