<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

include(INFUSEDWOO_PRO_DIR . 'modules/savedcarts.php');

add_action('woocommerce_add_to_cart', 'ia_woocommerce_add_to_cart', 10, 6);
add_action('woocommerce_before_checkout_form', 'ia_woocommerce_reached_checkout', 10, 0);
add_action('woocommerce_checkout_process' , 'ia_woocommerce_pressed_orderbtn',10, 0);
add_action('woocommerce_cart_is_empty' , 'ia_emptied_cart',10, 0);
add_action('ia_post_send_email', 'ia_renderAndSendISEmail',10,0);

function ia_woocommerce_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
	global $iwpro;
	global $woocommerce;
	$fast_add_to_cart_tracking = (isset($iwpro->settings['advancedTracking']) && $iwpro->settings['advancedTracking'] == "yes");

	if($fast_add_to_cart_tracking && is_user_logged_in()) {
		$psku = get_post_meta($product_id, '_sku', true);
		if(!empty($variation_id)) $vsku = get_post_meta($variation_id, '_sku', true);

		$user = wp_get_current_user();
		$email = get_user_meta($user->ID, 'billing_email', true);
		if(empty($email)) $email = $user->user_email;

		$cart_contents = $woocommerce->session->get('cart');
		ia_save_cart($email, $cart_contents);

		$iw_events_atc = $woocommerce->session->get('iw_events_atc');

		if(!empty($iw_events_atc) && in_array($psku, $iw_events_atc)) {
			return;  
		}		

		if((!empty($psku) && preg_match("/^[A-Za-z0-9]+$/", $psku)) || (!empty($vsku) && preg_match("/^[A-Za-z0-9]+$/", $vsku))) {
			if(!$iwpro->ia_app_connect()) return;

			

			$contact = $iwpro->app->dsFind('Contact',5,0,'Email',$email,array('Id')); 
			$contact = $contact[0];

			// GET IFS CONTACT ID:
			if ($contact['Id'] != null && $contact['Id'] != 0 && $contact != false){
			   $contactId = (int) $contact['Id']; 
			} else {
				$contactinfo['Email'] = $email;
				$contactId  = $iwpro->app->addCon($contactinfo);
			}
			$addtocart_goal = apply_filters( 'infusedwoo_cpn_goal', 'wooaddtocart' );

			if(!empty($psku)) {
				$iwpro->app->achieveGoal($addtocart_goal, $psku, $contactId);
				if(!empty($iw_events_atc)) $iw_events_atc[] = $psku;
				else $iw_events_atc = array($psku);
			}
			if(!empty($vsku)) {
				$iwpro->app->achieveGoal($addtocart_goal, $vsku, $contactId);
				if(!empty($iw_events_atc)) $iw_events_atc[] = $vsku;
				else $iw_events_atc = array($vsku);
			}
			$iwpro->app->achieveGoal($addtocart_goal, "any", $contactId);

			$woocommerce->session->set('iw_events_atc', $iw_events_atc);
		}	
	}
}

function ia_woocommerce_reached_checkout() {
	global $woocommerce;

	if(is_user_logged_in()) {
		$user = wp_get_current_user();

		$email = get_user_meta($user->ID, 'billing_email', true);
		if(empty($email)) $email = $user->user_email;

		$iw_events_checkout = $woocommerce->session->get('iw_events_checkout');

		$cart_contents = $woocommerce->session->get('cart');
		ia_save_cart($email, $cart_contents);

		if(!empty($iw_events_checkout) && in_array($email, $iw_events_checkout)) {
			return;  
		}

		iw_track_checkout($email);
	}
}

function ia_woocommerce_pressed_orderbtn() {
	global $woocommerce;

	$email = $_POST['billing_email'];

	if(!empty($email)) {
		if(isset($iwpro->settings['advancedTracking']) && $iwpro->settings['advancedTracking'] == 'yes') {
			$cart_contents = $woocommerce->session->get('cart');
			ia_save_cart($email, $cart_contents);
		}

		$iw_events_checkout = $woocommerce->session->get('iw_events_checkout');

		if(!empty($iw_events_checkout) && in_array($email, $iw_events_checkout)) {
			return;  
		}

		iw_track_checkout($email);
	}
}

function iw_track_checkout($email) {
	global $iwpro;
	global $woocommerce;

	// INFUSIONSOFT
	if(strpos($email, '@') === false || strpos($email, '.') === false) return;
	if(!$iwpro->ia_app_connect()) return;

	$contact = $iwpro->app->dsFind('Contact',5,0,'Email',$email,array('Id')); 
	$contact = isset($contact[0]) ? $contact[0] : array();

	// GET IFS CONTACT ID:
	if (isset($contact['Id']) && $contact['Id'] != 0){
	   $contactId = (int) $contact['Id']; 
	} else {
		$contactinfo['Email'] = $email;
		$contactId  = $iwpro->app->addCon($contactinfo);
	}

	if(isset($iwpro->settings['advancedTracking']) && $iwpro->settings['advancedTracking'] == 'yes') {
		// Check if add to cart events were processed:
		$cart = $woocommerce->session->get('cart');
		$iw_events_atc = $woocommerce->session->get('iw_events_atc');
		$iw_events_checkout = $woocommerce->session->get('iw_events_checkout');

		if(empty($iw_events_atc) || !isset($iw_events_atc) || !is_array($iw_events_atc)) $iw_events_atc = array();
		if(empty($iw_events_checkout) || !isset($iw_events_checkout) || !is_array($iw_events_checkout)) $iw_events_checkout = array();

		$track_atc_sku = array();

		foreach($cart as $item) {
			$psku = get_post_meta($item['product_id'], '_sku', true);
			if(!empty($variation_id)) $vsku = get_post_meta($item['variation_id'], '_sku', true);

			if(!empty($psku) && !in_array($psku, $iw_events_atc) && preg_match("/^[A-Za-z0-9]+$/", $psku)) $track_atc_sku[] = $psku;
			if(!empty($vsku) && !in_array($vsku, $iw_events_atc) && preg_match("/^[A-Za-z0-9]+$/", $vsku)) $track_atc_sku[] = $vsku;
		}

		$addtocart_goal = apply_filters( 'infusedwoo_cpn_goal', 'wooaddtocart' );
		foreach($track_atc_sku as $sku) {
			$iwpro->app->achieveGoal($addtocart_goal, $sku, $contactId);
			$iw_events_atc[] = $sku;	
		}
		$iwpro->app->achieveGoal($addtocart_goal, "any", $contactId);

		$woocommerce->session->set('iw_events_atc', $iw_events_atc);

		$iw_events_checkout[] = $email;
		$woocommerce->session->set('iw_events_checkout', $iw_events_checkout);
	}

	$cpn_goal = apply_filters( 'infusedwoo_cpn_goal', 'wooevent' );
	$iwpro->app->achieveGoal($cpn_goal, "reachedcheckout", $contactId);
}

function ia_emptied_cart() {
	// Identify Customer First
	global $wpdb;
	$ia_savedcarts = $wpdb->prefix . "ia_savedcarts";
	global $iwpro;

	if(isset($iwpro->settings['advancedTracking']) && $iwpro->settings['advancedTracking'] == 'yes') {
		$email = '';

		if(isset($_GET['saved_cart_loaded']) && !empty($_GET['saved_cart_loaded'])) { 
			$hash = $_GET['saved_cart_loaded'];

			$saved_cart_email = $wpdb->get_var( $wpdb->prepare(
			    	"SELECT email FROM `$ia_savedcarts` WHERE `hash` = %s", $hash
			  	));

			$email = $saved_cart_email;
		}

		if(is_user_logged_in() && empty($email)) {
			$user = wp_get_current_user();

			$email = get_user_meta($user->ID, 'billing_email', true);
			if(empty($email)) $email = $user->user_email;
		}
		
		if(!$iwpro->ia_app_connect()) return;

		$contact = $iwpro->app->dsFind('Contact',5,0,'Email',$email,array('Id')); 
		$contact = $contact[0];

		// GET IFS CONTACT ID:
		if ($contact['Id'] != null && $contact['Id'] != 0 && $contact != false){
		   $contactId = (int) $contact['Id']; 
		}

		if($contactId > 0) {
			ia_save_cart($email, "");
			$cpn_goal = apply_filters( 'infusedwoo_cpn_goal', 'wooevent' );
			$iwpro->app->achieveGoal($cpn_goal, "emptiedcart", $contactId);
		}
	}
}


function ia_renderLastCartHTML($email, $plain = false, $withprice = false) {
	$cart_info = ia_retrieve_cart($email);
	$saved_cart = unserialize($cart_info['cartcontent']);
	$html = '';

	if(is_array($saved_cart) && count($saved_cart) > 0) {
		if(!$plain) {
			$html .= '<div style="border-top: 1px solid #a59f92; width: 95%; max-width: 620px; margin: 0px auto;">';
			
			foreach ( $saved_cart as $cart_item_key => $values ){ 
					$title = get_the_title($values['product_id']);
					$thumbid = get_post_thumbnail_id( $values['product_id']);
					$thumb = wp_get_attachment_thumb_url($thumbid);
					if(empty($thumb)) $thumb = woocommerce_placeholder_img_src();

			$html .= '<div style="border-bottom: 1px solid #a59f92; width: 95%; padding: 10px 2.5%; font-size: 11pt; ">';
			$html .= '	<div style="width: 50px; float: right; text-align: right; vertical-align: top; ';
			$html .= !empty($thumb) ? "margin-top: 20px;" : ""; 
			$html .= "\">x {$values['quantity']}</div>";
			
			if(!empty($thumb)) {
				$html .= '<div style="display: inline-block; width: 90px; ">';
				$html .= '<img src="'.$thumb.'" style="width: 80px; height: 80px;" /></div>'; 
			}
			$html .= '<div style="display:inline-block; vertical-align: top; ';
			$html .= !empty($thumb) ? "margin-top: 20px; max-width: 70%" : "";
			$html .= '"><b>'.$title.'</b>';
			$html .= $withprice ? "<div style=\"margin-top: 5px;\">" . __("Price","woocommerce") . ": " . woocommerce_price($values['line_subtotal']) . "</div>": "";
			$html .= '	</div>';
			$html .= '</div>';	
			}
			$html .= '</div>';

		} else {
			foreach ( $saved_cart as $cart_item_key => $values ){ 
					$title = get_the_title($values['product_id']);
					$html .= $values['quantity'] . 'x '. "<b>{$title}</b><br>";
					$html .= $withprice ? __("Price","woocommerce") . ": " . woocommerce_price($values['line_subtotal']) . "<br><br>" : "";
			}
		}
	}

	return $html;
}

function ia_getSavedCartURI($email, $ahref, $openingonly = false) {
	$cart_uri = WC_Cart::get_cart_url();
	$cart_info = ia_retrieve_cart($email);

	if(is_array($cart_info) && count($cart_info) > 0) {
		$cart_token = $cart_info['hash'];

		$savedCartURI = strpos($cart_uri, "?") ? $cart_uri . "&ia_saved_cart=" . $cart_token :  $cart_uri . "?ia_saved_cart=" . $cart_token;
		if($openingonly) {
			return '<a href="'.$savedCartURI.'">';
		} else if($ahref) {
			return '<a href="'.$savedCartURI.'">'.$savedCartURI.'</a>';;
		} else {
			return $savedCartURI;
		}
	} else {
		return '';
	}
}

function ia_renderAndSendISEmail() {
	$contactid = $_GET['contactId'];
	$email = $_GET['Email'];
	$templateid = $_GET['templateId'];

	if(empty($email) || empty($contactid) || empty($templateid)) {
		die("Missing 1 or more parameter: Email, ContactId, TemplateId");
	} else {
		global $iwpro;
		if(!$iwpro->ia_app_connect()) return;

		$con = $iwpro->app->loadCon($contactid, array('Email'));

		if(is_array($con)) {
			if($email == $con['Email']) {
				$template = $iwpro->app->getEmailTemplate($templateid);
				if(!empty($template['htmlBody'])) {
					$newHtml = str_replace("~InfusedWoo.LastCart~", ia_renderLastCartHTML($email), $template['htmlBody']);
					$newHtml = str_replace("~InfusedWoo.LastCart.PlainText~", ia_renderLastCartHTML($email, true), $newHtml);
					$newHtml = str_replace("~InfusedWoo.LastCart.withPrice~", ia_renderLastCartHTML($email, false,true), $newHtml);
					$newHtml = str_replace("~InfusedWoo.LastCart.PlainText.withPrice~", ia_renderLastCartHTML($email, true,true), $newHtml);
					$newHtml = str_replace("~InfusedWoo.LastCartLink~", ia_getSavedCartURI($email, true), $newHtml);
					$newHtml = str_replace("~InfusedWoo.LastCartLinkOpen~", ia_getSavedCartURI($email, true,true), $newHtml);
					$newHtml = str_replace("~InfusedWoo.LastCartLinkClose~", "</a>", $newHtml);
				}
				$clist = array($contactid);
				$status = $iwpro->app->sendEmail($clist,$template['fromAddress'],$template['toAddress'], "","","HTML",$template['subject'],"",$newHtml);
				echo $newHtml;
			}

		} else {
			die('');
		}

	}


}

?>