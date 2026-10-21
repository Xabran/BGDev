<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// ORDER COMPLETE HOOKS

add_action('woocommerce_order_status_on-hold_to_completed', 'ia_woocommerce_payment_complete', 10, 1);
add_action('woocommerce_order_status_pending_to_completed', 'ia_woocommerce_payment_complete', 10, 1);
add_action('woocommerce_order_status_failed_to_completed', 'ia_woocommerce_payment_complete', 10, 1);					
add_action('woocommerce_order_status_on-hold_to_processing', 'ia_woocommerce_payment_complete', 10, 1);
add_action('woocommerce_order_status_pending_to_processing', 'ia_woocommerce_payment_complete', 10, 1);
add_action('woocommerce_order_status_failed_to_processing', 'ia_woocommerce_payment_complete', 10, 1);

add_action('woocommerce_checkout_update_order_meta', 'ia_woocommerce_new_order', 10, 1);



function ia_woocommerce_payment_complete_force($order_id) {
	ia_woocommerce_payment_complete( $order_id, true); 
}

function ia_woocommerce_new_order($order_id) {
	$create_early_invoice = apply_filters( 'infusedwoo_create_early_invoice', false );

	if($create_early_invoice) {
		$order = new WC_Order($order_id);
		$order_status = $order->get_status();

		if($order_status != 'processing' && $order_status != 'completed') {
			ia_woocommerce_payment_complete( $order_id, false, true);
		}
	}
}


function ia_woocommerce_payment_complete( $order_id, $force = false, $mark_unpaid = false) {
	global $woocommerce;
	global $iwpro;

	$purchase_goal = apply_filters( 'infusedwoo_cpn_goal', 'woopurchase' );
	$coupon_goal = apply_filters( 'infusedwoo_cpn_goal', 'woocoupon' );
	$action_log = array();

	$order = new WC_Order( $order_id );	
	if(!$iwpro->ia_app_connect()) {
		$apperrormsg = $iwpro->settings['apperrormsg'];
		$order->add_order_note("CRITICAL: Not sent to infusionsoft due to {$apperrormsg}");	
		return false;
	}		

	$email = $order->billing_email;

	$contact = $iwpro->app->dsFind('Contact',5,0,'Email',$email,array('Id'));
	$contact = $contact[0];	
	
	if ($contact['Id'] != null && $contact['Id'] != 0 && $contact != false){
		$contactId = (int) $contact['Id']; 
	} else {				
		$contactinfo	= array();	
		$contactinfo['Email'] = $email;
		$contactId  = $iwpro->app->addCon($contactinfo);
	}

	$contactId = apply_filters( 'infusedwoo_get_contactid', $contactId, $order);			
	
	$products = $order->get_items(); 
	
	update_post_meta( $order_id, 'infusionsoft_contact_id', $contactId );

	$as = (int) $iwpro->success_as;
	if($as > 0) {
		$iwpro->app->runAS($contactId, $as);
		$action_log[] = "Ran Action # $as";
	}

	$iwpro->app->achieveGoal($purchase_goal, "any", $contactId);		
	$action_log[] = "Triggered Goal: $purchase_goal, any";
	
	$saveOrders = $iwpro->settings['saveOrders'];
	
	$payment_method = $order->payment_method;
	$used_coup = $order->get_used_coupons();
	
	$pending_payment = get_post_meta($order_id, 'infusionsoft_order_pending', true );

	if($saveOrders == "yes" && $payment_method != "infusionsoft" && !$pending_payment) {

		// MAKE SURE BILLING AND SHIPPING ADDRESS IS CORRECT
		// Company Selector
		$compId = 0;
		$b_company = stripslashes($order->billing_company);
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

		$contactinfo = array();
		if(!empty($order->billing_first_name)) $contactinfo['FirstName'] = $order->billing_first_name;
		if(!empty($order->billing_last_name)) $contactinfo['LastName'] = $order->billing_last_name;
		if(!empty($order->billing_phone)) $contactinfo['Phone1'] = $order->billing_phone;
		if(!empty($order->billing_address_1)) $contactinfo['StreetAddress1'] = $order->billing_address_1;
		if(!empty($order->billing_address_2)) $contactinfo['StreetAddress2'] = $order->billing_address_2;
		if(!empty($order->billing_city)) $contactinfo['City'] = $order->billing_city;
		if(!empty($order->billing_state)) $contactinfo['State'] = $order->billing_state;
		if(!empty($order->billing_country)) $contactinfo['Country'] = iw_to_country($order->billing_country);
		if(!empty($order->billing_postcode)) $contactinfo['PostalCode'] = $order->billing_postcode;
		if(!empty($order->shipping_address_1)) $contactinfo['Address2Street1'] = $order->shipping_address_1;
		if(!empty($order->shipping_address_2)) $contactinfo['Address2Street2'] = $order->shipping_address_2;
		if(!empty($order->shipping_city)) $contactinfo['City2'] = $order->shipping_city;
		if(!empty($order->shipping_state)) $contactinfo['State2'] = $order->shipping_state;
		if(!empty($order->shipping_country)) $contactinfo['Country2'] = iw_to_country($order->shipping_country);
		if(!empty($order->shipping_postcode)) $contactinfo['PostalCode2'] = $order->shipping_postcode;
		if(!empty($b_company)) $contactinfo['Company'] = $b_company;
		if(!empty($compId)) $contactinfo['CompanyID'] = $compId;
		$contactinfo['ContactType'] = 'Customer';

		$infusedwoo_contact_update = apply_filters( 'infusedwoo_contact_update', true );
		$contactinfo = apply_filters( 'infusedwoo_contact_updateinfo', $contactinfo, $order);	
		if($iwpro->overwriteBD != "yes" && $infusedwoo_contact_update) $iwpro->app->dsUpdate("Contact",$contactId,$contactinfo);
		
		if($payment_method != "infusionsoft") {
			// CHECK AFFILIATE			
					
			$returnFields = array('AffiliateId');
			$referrals = $iwpro->app->dsFind('Referral',1000,0,'ContactId',(int) $contactId,$returnFields);
			$num = count($referrals);
			if($num > 0  && is_array($referrals)) $is_aff = $referrals[$num-1]['AffiliateId'];
			else $is_aff = 0;	

			$is_aff = apply_filters( 'infusedwoo_get_affiliateid', $is_aff, $order);	

			// BREAK IF INVOICE ALREADY CREATED
			$ifs_inv = get_post_meta($order_id, 'infusionsoft_invoice_id', true );
			if($ifs_inv > 0 && !$force) {
				return true;
			}
			
			// CREATE INVOICE
			
			$orderDate = date('Ymd\TH:i:s', current_time('timestamp'));

			$order_title = apply_filters( 'infusedwoo_infusion_order_title', "Woocommerce Order # {$order->id}", $order );
			$inv_id = (int) $iwpro->app->blankOrder($contactId,$order_title,$orderDate,0,$is_aff);
			update_post_meta($order_id, 'infusionsoft_invoice_id', $inv_id);
			$calc_totals = 0;
			
			$products = $order->get_items(); 
			// PRODUCT LINE


			foreach($products as $product) {
				$sku = "";
				$id  =  (int) $product['product_id'];
				$vid =  (int) $product['variation_id'];				
				
				$pid     = (int) get_post_meta($id, 'infusionsoft_product', true);
				
				if($vid != 0)   $sku = get_post_meta($vid, '_sku', true);
				if(empty($sku)) $sku = get_post_meta($id, '_sku', true);
				$sdesc = '';


				if( empty($pid) ) {
					if(!empty($sku)) {
						$ifsproduct = $iwpro->app->dsFind('Product',1,0,'Sku',$sku, array('Id'));
						$ifsproduct = $ifsproduct[0];
						if(!empty($ifsproduct)) $pid = (int) $ifsproduct['Id'];
						else if($iwpro->settings['addsku'] == "yes") {
							$productname  = get_the_title($product['product_id']);
							$productprice = $product['line_total'];								
							$newproduct = array('ProductName' 	=> $productname,
												'ProductPrice'  => $productprice,
												'Sku'     		=> $sku);
							$pid = (int) $iwpro->app->dsAdd("Product", $newproduct);
						} else $pid = 0;
					} else $pid = 0;						
				} 
				// set product description
				$pdesc = ''; 
				if($pid == 0) $pdesc .= $product['name'] . " ";

				if($vid != 0) {
					$variation = new WC_Product_Variation($vid);

					$attribs = $variation->get_variation_attributes();
					$all_attribs = $variation->get_attributes();

					$attribs_txt = array();

					foreach($attribs as $k => $v) {
						$key = str_replace('attribute_', '', $k);
						if(isset($all_attribs[$key])) {
							$label = $all_attribs[$key]['name'];
							$attribs_txt[] = "$label: $v";
						}
					}

					$var_sku = get_post_meta($vid, '_sku', true);
					if(!empty($var_sku)) $attribs_txt[] = "SKU: $var_sku";
					
					$pdesc .= implode(", ", $attribs_txt);
				} 

				$pdesc = apply_filters( 'infusedwoo_product_item_desc', $pdesc, $product['name'], $pid );
		
				$qty 	= (int) $product['qty'];
				$price 	= ((float) $product['line_subtotal']) / ((float) $product['qty']);
				
				if(!$mark_unpaid) {
					$action_log = iw_run_success_actions($product, $contactId, $order_id, $action_log);		
				}

				$iwpro->app->addOrderItem($inv_id, $pid, 4, round($price,2), $qty, $pdesc, $sdesc);
				$calc_totals += $qty * $price;		
			}	
			
			// SHIPPING LINE
			$s_method = (string) $order->get_shipping_method();  
			$s_total  = (float)  $order->get_total_shipping();
			if($s_total > 0.0) {
				$iwpro->app->addOrderItem($inv_id, 0, 14, round($s_total,2), 1, $s_method,$s_method);
				$calc_totals += $s_total;
			}

			// Custom Fees
			$fees = $order->get_fees();
			if(count($fees) > 0) foreach($fees as $fee) {
				$fee_amount = round((float) $fee['line_subtotal'],2);
				$iwpro->app->addOrderItem($inv_id, 0, 13, $fee_amount, 1, $fee['name'], $fee['name']);
				$calc_totals += $fee_amount;
			}

			// TAX LINE
			$tax = (float) $order->get_total_tax();
			if($tax > 0.0) {
				$iwpro->app->addOrderItem($inv_id, 0, 2, round($tax,2), 1, 'Tax','');
				$calc_totals += $tax;
			}
			
			//coupon line
			$discount = (float) ($calc_totals - $order->get_total());
			if ( round($discount,2) > 0.00  ) {
			  $coupon_desc = "Discount";
			  if(is_array($used_coup)) $coupon_desc = implode(",", $used_coup);
			  $iwpro->app->addOrderItem($inv_id, 0, 7, -round($discount,2), 1, $coupon_desc, 'Woocommerce Coupon Code');
			  $calc_totals -= $discount;		  
			} 

			
			if(!$mark_unpaid) {
				$method = $order->payment_method_title;
				
				$totals = (float) $iwpro->app->amtOwed($inv_id);
				$iwpro->app->manualPmt($inv_id, $totals, $orderDate, $method, "Woocommerce Checkout",false);
			} else {
				update_post_meta($order_id, 'infusionsoft_order_pending', 1);
			}

			//Add Order Notes				
			$jobid  = $iwpro->app->dsLoad("Invoice",$inv_id, array("JobId"));
			$jobid  = (int) $jobid['JobId'];
			$modify_order = array("JobNotes" => $order->customer_note, 'OrderType' => 'Online');

			if(!empty($order->shipping_first_name) && $order->shipping_first_name != $order->billing_first_name)
				$modify_order['ShipFirstName'] = $order->shipping_first_name;

			if(!empty($order->shipping_last_name) && $order->shipping_last_name != $order->billing_last_name)
				$modify_order['ShipLastName'] = $order->shipping_last_name;

			if(!empty($order->shipping_company) && $order->shipping_company != $order->billing_company)
				$modify_order['ShipCompany'] = $order->shipping_company;

			$modify_order = apply_filters( 'infusedwoo_modify_order', $modify_order, $order_id );
			$iwpro->app->dsUpdate("Job",$jobid, $modify_order);
			update_post_meta($order_id, 'infusionsoft_order_id', $jobid);
			$appname = isset($iwpro->machine_name) ? $iwpro->machine_name : "";
			update_post_meta($order_id, 'infusionsoft_view_order', "https://$appname.infusionsoft.com/Job/manageJob.jsp?view=edit&ID=$jobid");
				
		}				
	} else {
		if($pending_payment) {
			delete_post_meta( $order_id, 'infusionsoft_order_pending' );
			$method = $order->payment_method_title;
			$inv_id = get_post_meta($order_id, 'infusionsoft_invoice_id', true );
				
			$totals = (float) $iwpro->app->amtOwed($inv_id);
			$iwpro->app->manualPmt($inv_id, $totals, date('Ymd\TH:i:s', current_time('timestamp')), $method, "Woocommerce Checkout",false);
		}

		foreach($products as $product) {
			$action_log = iw_run_success_actions($product, $contactId, $order_id, $action_log);	
		}		
	}

	
	// TRIGGER GOAL IF COUPON CODE IS USED:

	if (function_exists('ia_save_cart')) ia_save_cart($email, "");

	if(is_array($used_coup)) { 
		foreach($used_coup as $c) {
			$iwpro->app->achieveGoal($coupon_goal, $c, $contactId);	
			$action_log[] = "Triggered Goal: $coupon_goal, $c";
		}
	}

	$order->add_order_note("Infusionsoft Automation\n" . implode("\n", $action_log));

	do_action( 'infusedwoo_payment_complete', $order_id, $contactId );
}


function iw_run_success_actions($product, $contactId, $order_id, $action_log = array()) {
	global $iwpro;
	$purchase_goal = apply_filters( 'infusedwoo_cpn_goal', 'woopurchase' );

	$sku = "";
	$id  =  (int) $product['product_id'];
	$vid =  (int) $product['variation_id'];

	if($vid != 0)   $sku = get_post_meta($vid, '_sku', true);
	if(empty($sku)) $sku = get_post_meta($id, '_sku', true);

	$tag    = get_post_meta($id, 'infusionsoft_tag', 	true);
	$email  = get_post_meta($id, 'infusionsoft_email', 	true);
	$action = get_post_meta($id, 'infusionsoft_action', true);				

	if(is_array($action)) {
		foreach($action as $a) {
			$iwpro->app->runAS($contactId, $a);
			$action_log[] = "Ran Action # $a";
		}
	} else if(!empty($action)) {
		$iwpro->app->runAS($contactId, (int) $action);
		$action_log[] = "Ran Action # $action";
	}

	if(is_array($tag)) {
		foreach($tag as $t) {
			$iwpro->app->grpAssign($contactId, $t);
			$action_log[] = "Applied Tag # $t";
		}
	} else if(!empty($tag)) {
		$iwpro->app->grpAssign($contactId, (int) $tag);
		$action_log[] = "Applied Tag # $tag";
	}

	if(is_array($email)) {
		foreach($email as $e) {
			$iwpro->app->sendTemplate(array($contactId), $e);
			$action_log[] = "Sent Email # $e";
		}
	} else if(!empty($email)) {
		$iwpro->app->sendTemplate(array($contactId), (int) $email);
		$action_log[] = "Sent Email # $email";
	}

	if(!empty($sku) && preg_match("/^[A-Za-z0-9]+$/", $sku)) {
		$iwpro->app->achieveGoal($purchase_goal, $sku, $contactId);	
		$action_log[] = "Triggered Goal: $purchase_goal, $sku";
	}

	do_action( 'infusedwoo_product_purchase', $product, $contactId, $order_id );

	return $action_log;
	
}





?>