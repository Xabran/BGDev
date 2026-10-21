<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action('user_register', 'ia_new_user_reg',10,1);

function ia_new_user_reg($user_id) {
	global $iwpro;

	if($iwpro->regtoifs == 'yes') {
		$iwpro->ia_app_connect();
		
		$ia_user_info = array();

		$user_info = get_userdata($user_id);
		
		// Determine Email

      	if(!empty($user_info->user_email))		$user_email = $user_info->user_email;
		else if(isset($_POST['user_email'])) 	$user_email = $_POST['user_email'];
		else if(isset($_POST['email'])) 		$user_email = $_POST['email'];
		else if(isset($_POST['Email'])) 		$user_email = $_POST['Email'];
		else if(isset($_POST['email2']))		$user_email = $_POST['Email'];
		else if(isset($_POST['billing_email']))	$user_email = $_POST['billing_email'];
		
		if(strpos($user_email, '@') === false || strpos($user_email, '.') === false) return;
		if(!empty($user_email)) $ia_user_info['Email'] = $user_email;
		
		// Determine First Name
		if(!empty($user_info->first_name))		$user_fname = $user_info->first_name;
		else if (isset( $_POST['first_name']))	$user_fname = $_POST['first_name'];
		else if (isset( $_POST['firstname']))	$user_fname = $_POST['firstname'];
		else if (isset( $_POST['FirstName']))	$user_fname = $_POST['FirstName'];
		else if (isset( $_POST['Firstname']))	$user_fname = $_POST['Firstname'];
		else if (isset( $_POST['billing_first_name']))	$user_fname = $_POST['billing_first_name'];


		if(!empty($user_fname)) $ia_user_info['FirstName'] =  $user_fname;
		
		// Determine Last Name
		if(!empty($user_info->last_name))		$user_lname = $user_info->last_name;
		else if (isset( $_POST['last_name']))	$user_lname = $_POST['last_name'];
		else if (isset( $_POST['lastname']))	$user_lname = $_POST['lastname'];
		else if (isset( $_POST['LastName']))	$user_lname = $_POST['LastName'];
		else if (isset( $_POST['Lastname']))	$user_lname = $_POST['Lastname'];
		else if (isset( $_POST['billing_last_name']))	$user_lname = $_POST['billing_last_name'];

		if(!empty($user_lname)) $ia_user_info['LastName'] =  $user_lname;
		if(isset($_SESSION['leadsource']) && !empty($_SESSION['leadsource'])) $ia_user_info['Leadsource'] = 	$_SESSION['leadsource'];

		// Determine Username and Password:
		$user_pass = $user_info->user_pass;
		$username = $user_info->user_login;
		
		$contact = $iwpro->app->dsFind('Contact',5,0,'Email',$user_email, array('Id')); 
		
		if (isset($contact[0]['Id'])) {
			   if(!empty($ia_user_info)) {
				   $contactId = (int) $contact[0]['Id']; 
				   $contactId = $iwpro->app->updateCon($contactId, $ia_user_info);
				}
				$iwpro->app->optIn($user_email,"API: User registered to Shop");						
		} else {					
			$contactId  = $iwpro->app->addCon($ia_user_info);
			$iwpro->app->optIn($user_email,"API: User registered to Shop");
		}
		
		$ras = (int) $iwpro->reg_as;
		$iwpro->app->runAS($contactId, $ras);
		$iwpro->app->achieveGoal("wooevent", "register", $contactId);	
	}			
}

?>