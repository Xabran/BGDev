<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action('init', 'ia_post_functions', 10, 2);

function ia_post_functions() {	
	global $iwpro;

	// FIRST FUNCTION: ## reg ## :: FOR AUTO REGISTRATION OF USERS
	if(isset($_GET['ia_fcn'])) {
		if($_GET['ia_fcn'] == 'reg') {
			if(!$iwpro->ia_app_connect()) die('Cannot connect to infusionsoft.');
			
			$passfield = $_GET['passfield'];
			
			$passpresent = false;
			if(!empty($passfield)) {
				//check if passfield is present.
				$ifs_fields = $iwpro->app->dsQuery('DataFormField',1,0,array('Name' => $passfield, 'FormId' => -1), array('Id'));

				if(is_array($ifs_fields)) {
					$passpresent = true;
					$passfield = "_" . $passfield;
				}
				else echo "NOTE: The custom field for password you used is invalid. WP passwords will not be saved in Infusionsoft.\n<br>";
			} else {
				$passpresent = true;
				$passfield = "Password";
			}
			
			if(!empty($_POST['contactId'])) {
				$cid = (int) $_POST['contactId'];
				if($passpresent) $contact = $iwpro->app->loadCon($cid, array('Id','Email',"{$passfield}"));
				else $contact = $iwpro->app->loadCon($cid, array('Id','Email'));
				$email = $contact['Email'];
			} else {
				if(!empty($_POST['Email'])) {
					$email = $_POST['Email'];
				} else if(!empty($_POST['email'])) {
					$email = $_POST['email'];
				} else {
					die('Contact ID / Email is empty.');
				}
				
				if($passpresent) $contact = $iwpro->app->dsFind('Contact',5,0,'Email',$email, array('Id','Email',"{$passfield}"));
				else $contact = $iwpro->app->dsFind('Contact',5,0,'Email',$email, array('Id','Email'));
				$contact = $contact[0];
				
				if ($contact['Id'] != null && $contact['Id'] != 0 && $contact != false){
					$cid = $contact['Id'];
				} else {					
					die('Contact was not found!');
				}					
			}
			
			// NOW REGISTER THE USER
			if($passpresent) {
				$pass = $contact["{$passfield}"];
				if(empty($pass)) {
					$pass = wp_generate_password( $length=8, $include_standard_special_chars=false );
				}
			} else {
				$pass = wp_generate_password( $length=8, $include_standard_special_chars=false );
			}
			
			$user_id = username_exists( $email );
			if ( !$user_id and email_exists($email) == false ) {
				$user_id = wp_create_user( $email, $pass, $email );
				
				if($passpresent) {
					$contactpass = array("{$passfield}" => $pass);
					$iwpro->app->dsUpdate('Contact',$cid,$contactpass);
				}
				
				$ras = (int) $iwpro->reg_as;
				echo "User successfully registered. ";
				if($passpresent) echo "User password saved in infusionsoft";
				$iwpro->app->runAS($cid, $ras);
			} else {
				echo 'This user has already been registered to wordpress.'; 
			}
			
			exit;
		} else if($_GET['ia_fcn'] == 'cpn') {
			$source = $_POST;

			if((!empty($source['contactId']) || !empty($source['Email'])) && !empty($_GET['call'])) {
				if(!$iwpro->ia_app_connect()) die('Cannot connect to infusionsoft.');
				$cid   = (int) $source['contactId'];
				
				if(empty($cid)) {
					if(!empty($source['Email'])) {
						$contact = $iwpro->app->dsFind('Contact',1,0,'Email',$source['Email'],array('Id'));
						$contact = $contact[0];
						
						if(!empty($contact)) {
							$cid = $contact['Id'];
						} else { echo "Cannot identify contact?"; exit; }
					} else {
						echo "Cannot identify contact?";
						exit;
					}
				}
				
				$call  = $_GET['call'];
				$int   = $_GET['int'];
				
				if(empty($int)) $int = $iwpro->machine_name;			

				$result = $iwpro->app->achieveGoal($int, $call, $cid);

				echo "{$cid} ::: {$call} ::: {$int} ::: " . print_r($result, 1);
				exit;
			} else {
				echo "Call name / contactId / Email is missing.";
				exit;
			}
		} else {
			$customfcn = $_GET['ia_fcn'];
			if(!empty($customfcn)) do_action("ia_post_$customfcn");
			exit;
		}
	}
}

?>