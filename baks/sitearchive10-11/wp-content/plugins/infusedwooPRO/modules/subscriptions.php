<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

///// PRODUCT PAGE OVERRIDE

add_filter('woocommerce_get_price_html', 'ia_woocommerce_sub_filter', 10, 2 );

///// CART OVERRIDES
add_filter('woocommerce_get_order_item_totals',  'ia_order_get_item_totals', 10, 2 );
add_filter('woocommerce_cart_item_price_html', 'ia_woocommerce_sub_cart_filter', 10, 2 );
add_filter('woocommerce_cart_item_subtotal', 'ia_woocommerce_sub_total_filter', 10, 2 );
add_action('woocommerce_after_cart_totals', 'ia_woocommerce_after_cart', 10, 2 );	
add_filter('woocommerce_cart_total', 'ia_woocommerce_cart_total', 10, 2 );
add_filter('woocommerce_cart_subtotal', 'ia_woocommerce_cart_subtotal', 10, 2 );
add_filter('woocommerce_cart_formatted_taxes', 'ia_woocommerce_cart_formatted_tax', 10, 2 );
add_filter('woocommerce_get_cart_tax', 'ia_woocommerce_cart_tax', 10, 1 );
add_filter('woocommerce_cart_formatted_taxes', 'ia_woocommerce_cart_formatted_tax', 10, 2 );
add_filter('woocommerce_cart_tax_totals', 'ia_woocommerce_cart_tax_totals',10,2);

///// CHECKOUT OVERRIDES
add_filter('woocommerce_checkout_item_subtotal', 'ia_woocommerce_sub_total_filter', 10, 2 );
add_filter('woocommerce_available_payment_gateways', 'ia_woocommerce_pg_filter', 10, 2);
add_action('woocommerce_after_checkout_form', 'ia_woocommerce_after_checkout', 10, 2);	
add_action('woocommerce_review_order_after_cart_contents', 'ia_woocommerce_before_order_total', 10, 2 );		

////// SHIPPING OVERRIDE
add_filter('woocommerce_cart_shipping_packages', 'ia_cart_packages_filter', 10, 1 );		
add_filter('woocommerce_available_shipping_methods', 'ia_get_available_shipping_methods', 10, 1 );	

///// ORDER OVERRIDES
add_filter('woocommerce_order_formatted_line_subtotal', 'ia_woocommerce_order_subtotal_filter', 10, 2 );		
add_action('woocommerce_order_items_table', 'ia_woocommerce_order_items_table', 10, 1 );			
add_filter('woocommerce_order_subtotal_to_display', 'ia_woocommerce_order_subtotals', 10, 3 );						
add_filter('woocommerce_get_formatted_order_total', 'ia_woocommerce_order_totals', 10, 2 );

/**
 * PRODUCT PAGE PRICE OVERRIDE FOR SUBSCRIPTIONS
 **/		

function ia_woocommerce_sub_filter( $price, $product ){		
	global $iwpro;
	$ifstype  = get_post_meta($product->id, 'infusionsoft_type', true);			
	
	if($ifstype == 'Subscription') {
		if(!$iwpro->ia_app_connect()) return;
		
		$sid = (int) get_post_meta($product->id, 'infusionsoft_sub', 	true);			
		$trial = (int) get_post_meta($product->id, 'infusionsoft_trial', 	true);	
		
		$returnFields = array('Id','ProgramName','DefaultPrice','DefaultCycle','DefaultFrequency');
		$sub = $iwpro->app->dsLoad('CProgram',$sid,$returnFields);
		
		$stringCycle = '';
		switch($sub['DefaultCycle']) {						
				case 1: $stringCycle = 'yr'; break;						
				case 2: $stringCycle = 'mo'; break;						
				case 3: $stringCycle = 'wk'; break;						
				case 6: $stringCycle = 'day'; break;					
			}	
		$addS = '';					
		if($sub['DefaultFrequency'] > 1) $addS = 's';
		
		if($sub['DefaultFrequency'] == 1) $freq = '';
		else $freq = "{$sub['DefaultFrequency']} ";
		
		$sub_price = $iwpro->ia_get_sub_price($sid, $sub['DefaultPrice']);
		$tax_display_mode      = get_option( 'woocommerce_tax_display_shop' );

		$display_price         = $tax_display_mode == 'incl' ? $product->get_price_including_tax( 1, $sub_price ) : $product->get_price_excluding_tax( 1, $sub_price );
		$display_sale_price    = $tax_display_mode == 'incl' ? $product->get_price_including_tax( 1, $product->get_sale_price() ) : $product->get_price_excluding_tax( 1, $product->get_sale_price() );

		if($product->is_on_sale() && $display_price > $display_sale_price) {
			return $product->get_price_html_from_to( $display_price, $display_sale_price ) . " / {$freq}{$stringCycle}{$addS}"; 
		} else {
			return  woocommerce_price($display_price) . " / {$freq}{$stringCycle}{$addS}"; 
		}
		
	} else return $price;
}

function ia_order_get_item_totals($rows, $order) {
			$subs = get_post_meta( $order->id, 'ia_subscriptions', true );
			if(!empty($subs)) {
				$ct = 0;
				foreach($rows as $k => $row) {
					$label = strtolower($row['label']);				
					
					if(($label == 'tax') || ($k != 'cart_subtotal' && $k != 'shipping' && $k != 'order_total' && $k != 'fee' && $k != 'order_discount'  && $k != 'cart_discount')) {
						$ct++;
						if($ct == 1) {						
							$tax_new = $order->get_total_tax();
										
							foreach($order->get_items() as $item) {
								$ifstype  = get_post_meta($item['product_id'], 'infusionsoft_type', true);
								if($ifstype == 'Subscription') {
									$trial = (int) get_post_meta($item['product_id'], 'infusionsoft_trial', true);	
									
									if($trial > 0) {
										$tax_new -= $item['line_subtotal_tax'];
										if($tax_new < 0) $tax_new = 0; 
									}						
								}
							}							
						
							$rows[$k] = array('label' => 'Tax:', 'value' => woocommerce_price($tax_new));
						} else unset($rows[$k]);						
					}
				}
				
				return $rows;
			} else return $rows;
		}

function ia_woocommerce_sub_cart_filter($price, $product) {	
	global $iwpro;

	if(!isset($product['product_id'])) return $price;				
	$ifstype  = get_post_meta($product['product_id'], 'infusionsoft_type', true);			
	
	if($ifstype == 'Subscription') {
		if(!$iwpro->ia_app_connect()) return;
		
		$sid = (int) get_post_meta($product['product_id'], 'infusionsoft_sub', 	true);			
		$trial = (int) get_post_meta($product['product_id'], 'infusionsoft_trial', 	true);	
		
		$returnFields = array('Id','ProgramName','DefaultPrice','DefaultCycle','DefaultFrequency');
		$sub = $iwpro->app->dsLoad('CProgram',$sid,$returnFields);
		
		$stringCycle = '';
		switch($sub['DefaultCycle']) {						
				case 1: $stringCycle = 'yr'; break;						
				case 2: $stringCycle = 'mo'; break;						
				case 3: $stringCycle = 'wk'; break;						
				case 6: $stringCycle = 'day'; break;					
			}	
		$addS = '';					
		if($sub['DefaultFrequency'] > 1) $addS = 's';
		
		if($sub['DefaultFrequency'] == 1) $freq = '';
		else $freq = "{$sub['DefaultFrequency']} ";
		
		$sub_price = $iwpro->ia_get_sub_price($sid, $sub['DefaultPrice']);
		return woocommerce_price($sub_price) . " / {$freq}{$stringCycle}{$addS}";				
	} else return $price;
}

function ia_woocommerce_sub_total_filter($price, $product) {		
	global $iwpro;
	if(!isset($product['product_id'])) return $price;				
	$ifstype  = get_post_meta($product['product_id'], 'infusionsoft_type', true);			
	
	if($ifstype == 'Subscription') {
		if(!$iwpro->ia_app_connect()) return;
		
		$sid = (int) get_post_meta($product['product_id'], 'infusionsoft_sub', 	true);			
		$trial = (int) get_post_meta($product['product_id'], 'infusionsoft_trial', 	true);	
		
		$returnFields = array('Id','ProgramName','DefaultPrice','DefaultCycle','DefaultFrequency');
		$sub = $iwpro->app->dsLoad('CProgram',$sid,$returnFields);
		
		$stringCycle = '';
		switch($sub['DefaultCycle']) {						
				case 1: $stringCycle = 'yr'; break;						
				case 2: $stringCycle = 'mo'; break;						
				case 3: $stringCycle = 'wk'; break;						
				case 6: $stringCycle = 'day'; break;					
			}	
		$addS = '';					
		if($sub['DefaultFrequency'] > 1) $addS = 's';
		
		if($sub['DefaultFrequency'] == 1) $freq = '';
		else $freq = "{$sub['DefaultFrequency']} ";
		
		$sub_price = $iwpro->ia_get_sub_price($sid, $sub['DefaultPrice']);
		$subtotal = $product['quantity'] * $sub_price;
		
		return woocommerce_price($subtotal) . " / {$freq}{$stringCycle}{$addS}";				
	} else return $price;
}

function ia_woocommerce_after_cart() {
	global $iwpro;
	if($iwpro->has_sub()) {
	?>			
		<script>
			jQuery('.cart_totals > h2').text('<?php _e('Total Amount You Pay Right Now', 'woocommerce'); ?>');			
		</script>			
	<?php
	}
}

function ia_woocommerce_cart_total($total) {		
	global $iwpro;			
	if($iwpro->has_sub()) {
		global $woocommerce;
		$cart = $woocommerce->cart;

		$cart_total = $cart->total;

		if(!$iwpro->ia_app_connect()) return;
		$sub_notes = array();
	
		foreach($cart->cart_contents as $item) {
			$ifstype  = get_post_meta($item['product_id'], 'infusionsoft_type', true);
			if($ifstype == 'Subscription') {
				$sid = (int) get_post_meta($item['product_id'], 'infusionsoft_sub', 	true);			
				$trial = (int) get_post_meta($item['product_id'], 'infusionsoft_trial', 	true);	
				
				if($trial > 0) {
					$returnFields = array('DefaultPrice');
					$sub = $iwpro->app->dsLoad('CProgram',$sid,$returnFields);
					
					$sub_price = $iwpro->ia_get_sub_price($sid, $sub['DefaultPrice']);
					$deduction = $item['quantity'] * $sub_price + $item['line_total_tax'];
					$cart_total -= $deduction;
					if($cart_total < 0) $cart_total = 0;
				}						
			}
		}
		
		
		return woocommerce_price($cart_total);
	} else return $total;
	
}		

function ia_woocommerce_cart_subtotal($subtotal, $compound) {		
	global $iwpro;			
	if($iwpro->has_sub()) {
		global $woocommerce;
		$cart = $woocommerce->cart;

		if(version_compare( WOOCOMMERCE_VERSION, '2.1.7', '>=' )) 
			$cart_subtotal = $cart->subtotal;
		else 
			$cart_subtotal = $cart->subtotal_ex_tax;
		
		if(!$iwpro->ia_app_connect()) return;
		$sub_notes = array();
	
		foreach($cart->cart_contents as $item) {
			$ifstype  = get_post_meta($item['product_id'], 'infusionsoft_type', true);
			if($ifstype == 'Subscription') {
				$sid = (int) get_post_meta($item['product_id'], 'infusionsoft_sub', 	true);			
				$trial = (int) get_post_meta($item['product_id'], 'infusionsoft_trial', 	true);	
				
				if($trial > 0) {
					$returnFields = array('DefaultPrice');
					$sub = $iwpro->app->dsLoad('CProgram',$sid,$returnFields);
					
					$sub_price = $iwpro->ia_get_sub_price($sid, $sub['DefaultPrice']);
					$deduction = $item['quantity'] * $sub_price;
					$cart_subtotal -= $deduction;
					if($cart_subtotal < 0) $cart_subtotal = 0;
				}						
			}
		}
		
		
		return woocommerce_price($cart_subtotal);
	} else return $subtotal;
	
}

function ia_woocommerce_cart_formatted_tax($taxes, $wootax) {	
	global $iwpro;

	if($iwpro->has_sub()) {
		global $woocommerce;
		
		$taxes = $wootax->get_taxes();
		
		
		$deductibles = 0;
		
		foreach($woocommerce->cart->cart_contents as $item) {
			$ifstype  = get_post_meta($item['product_id'], 'infusionsoft_type', true);
			if($ifstype == 'Subscription') {
				$sid = (int) get_post_meta($item['product_id'], 'infusionsoft_sub', 	true);			
				$trial = (int) get_post_meta($item['product_id'], 'infusionsoft_trial', 	true);	
				
				if($trial > 0) {
					$deductibles += $item['line_subtotal_tax'];
				}						
			}
		}	
		

		foreach ( $taxes as $key => $tax ) {
			if ( is_numeric( $tax ) )
				$taxes[ $key ] = woocommerce_price( $tax - $deductibles);
				
			break;
		}

		return $taxes;
	} else return $taxes;
	
}		

function ia_woocommerce_cart_tax($return) {	
	global $iwpro;
	
	if($iwpro->has_sub()) {
		global $woocommerce;
		$cart_tax = $woocommerce->cart->tax_total + $woocommerce->cart->shipping_tax_total;
		
		foreach($woocommerce->cart->cart_contents as $item) {
			$ifstype  = get_post_meta($item['product_id'], 'infusionsoft_type', true);
			if($ifstype == 'Subscription') {
				$sid = (int) get_post_meta($item['product_id'], 'infusionsoft_sub', 	true);			
				$trial = (int) get_post_meta($item['product_id'], 'infusionsoft_trial', 	true);	
				
				if($trial > 0) {
					$cart_tax -= $item['line_subtotal_tax'];
				}						
			}
		}	
	
		return $cart_tax;
	} else return $return;
	
}


function ia_woocommerce_cart_tax_totals($tax_totals, $cart) {
	global $iwpro;

	if($iwpro->has_sub()) {
		global $woocommerce;
		$cart_tax = $woocommerce->cart->tax_total + $woocommerce->cart->shipping_tax_total;
		$deduct   = 0;
		
		foreach($woocommerce->cart->cart_contents as $item) {
			$ifstype  = get_post_meta($item['product_id'], 'infusionsoft_type', true);
			if($ifstype == 'Subscription') {
				$sid = (int) get_post_meta($item['product_id'], 'infusionsoft_sub', 	true);			
				$trial = (int) get_post_meta($item['product_id'], 'infusionsoft_trial', 	true);	
				
				if($trial > 0) {
					$deduct += $item['line_subtotal_tax'];
				}						
			}
		}

		$taxes      = $cart->get_taxes();
		$tax_totals_new = array();

		foreach ( $taxes as $key => $tax ) {

			$code = $cart->tax->get_rate_code( $key );

			if ( ! isset( $tax_totals[ $code ] ) ) {
				$tax_totals_new[ $code ] = new stdClass();
				$tax_totals_new[ $code ]->amount = 0;
			}

			$tax_totals_new[ $code ]->is_compound       = $cart->tax->is_compound( $key );
			$tax_totals_new[ $code ]->label             = $cart->tax->get_rate_label( $key );
			$tax_totals_new[ $code ]->amount           += $tax - $deduct;
			$tax_totals_new[ $code ]->formatted_amount  = woocommerce_price( $tax_totals[ $code ]->amount - $deduct);
			$deduct = 0;
		}

		return $tax_totals_new;
		
	
		return $cart_tax;
	} else return $tax_totals;

}

function ia_woocommerce_after_checkout() {
	global $iwpro;

	if($iwpro->has_sub()) {
	?>			
		<script>
			jQuery('h3#order_review_heading').text('<?php _e('Total Amount You Pay Right Now', 'woocommerce'); ?>');			
		</script>			
	<?php
	}
}

function ia_woocommerce_before_order_total() {		
	global $iwpro;

	if($iwpro->has_sub()) {
		global $woocommerce;
		if(!$iwpro->ia_app_connect()) return;
		$sub_notes = array();
		
		$packages = $woocommerce->shipping->packages;
		$selected_shipping = $woocommerce->session->chosen_shipping_method;
		
		foreach($woocommerce->cart->cart_contents as $item) {
			$ifstype  = get_post_meta($item['product_id'], 'infusionsoft_type', true);
			if($ifstype == 'Subscription') {	
				$shipping_fee = 0;

				foreach($packages as $package) {
					foreach($package['contents'] as $content) {
						if($content['product_id'] == $item['product_id']) {
							if($package['trialdays'] > 0 && !empty($package['rates'][$selected_shipping]->subcost)) {
								$shipping_fee += $package['rates'][$selected_shipping]->subcost;
								foreach($package['rates'][$selected_shipping]->subtaxes as $tax) 
									$shipping_fee += $tax;					
							} else {
								$shipping_fee += $package['rates'][$selected_shipping]->cost;
								
								if(is_array($package['rates'][$selected_shipping]->taxes)) {
									foreach($package['rates'][$selected_shipping]->taxes as $tax) 
										$shipping_fee += $tax;
								}
							}
						}
					}
				}  					

				$sid = (int) get_post_meta($item['product_id'], 'infusionsoft_sub', 	true);			
				$trial = (int) get_post_meta($item['product_id'], 'infusionsoft_trial', 	true);	
				
				$returnFields = array('Id','ProgramName','DefaultPrice','DefaultCycle','DefaultFrequency');
				$sub = $iwpro->app->dsLoad('CProgram',$sid,$returnFields);
				
				$stringCycle = '';
				
				switch($sub['DefaultCycle']) {						
						case 1: $stringCycle = 'year'; 	$nextbill = $sub['DefaultFrequency']*366; break;						
						case 2: $stringCycle = 'month'; $nextbill = $sub['DefaultFrequency']*30; break;						
						case 3: $stringCycle = 'week'; 	$nextbill = $sub['DefaultFrequency']*7; break;						
						case 6: $stringCycle = 'day';  	$nextbill = $sub['DefaultFrequency']*1; break;					
					}	
				$addS = '';					
				if($sub['DefaultFrequency'] > 1) $addS = 's';						
				if($sub['DefaultFrequency'] == 1) $freq = '';						
				else $freq = "{$sub['DefaultFrequency']} ";			
				
				$sub_price = $iwpro->ia_get_sub_price($sid, $sub['DefaultPrice']);
				$tot_price = $item['quantity'] * $sub_price;
				
				$subtotal = $tot_price + $shipping_fee + $item['line_subtotal_tax'];

				
				if($trial == 0)		$nextbilldate = date('M j, Y', (time() + $nextbill*24*60*60));
				else 				$nextbilldate = date('M j, Y', (time() + $trial*24*60*60));
				
				$sub_note  = " every {$freq}{$stringCycle}{$addS}{$remark}";	
				$sub_notes[] = array('sub' 		=> "{$sub['ProgramName']} x {$item['quantity']}", 
									 'price' 	=> $tot_price,
									 'ship'	 	=> $shipping_fee,
									 'tax'	 	=> $item['line_subtotal_tax'],
									 'total' 	=> $subtotal,
									 'nextbill' => $nextbilldate,
									 'cycle' 	=> $sub_note); 
				
			}			
		}
	?>
	</tbody>
	</table>
	<h3>Take note of the following recurring charges</h3>
	
	<table class="shop_table">
	<thead>
			<tr>
			<th>Subscription</th>
			<th>Price</th>
			<th>Billing Cycle</th>
			<th>Next Bill Date</th>
			</tr>			
	<thead>
	
	<tbody>
		<?php foreach($sub_notes as $sub_note) { ?>
			<tr>
			<td><?php echo $sub_note['sub']; ?></td>
			<td><b><?php echo woocommerce_price($sub_note['total']); ?></b></td>
			<td><b><?php echo $sub_note['cycle']; ?></b></td>
			<td><b><?php echo $sub_note['nextbill']; ?></b></td>
			</tr>
				
		<?php } ?>
	</table>
	</td>
	<?php
	
	}
}

function ia_woocommerce_pg_filter($value) {	
	global $iwpro;

	if($iwpro->has_sub()) {
		$newpg = array();
		foreach($value as $k => $pg) {
			if($k == 'infusionsoft') { 
				$newpg[$k] = $pg;
				break;
			}
		}
		
		return $newpg;
	} else {
		return $value;
	}
	
}


/**
 * SHIPPING OVERRIDES
**/		
	
function ia_cart_packages_filter($packages) {
	global $iwpro;

	if($iwpro->has_sub()) {
		global $woocommerce;
		$newpackages = $packages;
		
		
		foreach($packages as $k => $package) {
		
			$package_count = count($package['contents']);

			// REMOVE NON SHIPABBLE ITEMS
			foreach($package['contents'] as $tok => $content) {
				if(!$content['data']->needs_shipping()) {
					unset($package['contents'][$tok]);
					$package_count--;
				}
			}

			foreach($package['contents'] as $tok => $content) {		
				$pid = (int) $content['product_id'];
				if(empty($pid)) { 
					$pid = (int) $content['data']['id'];							
				}
				
				$ifstype  = get_post_meta($pid, 'infusionsoft_type', true);

				$trialdays = get_post_meta($pid, 'infusionsoft_trial', true);
				if($ifstype == 'Subscription' && ($package_count > 1 || ($trialdays > 0 && count($newpackages) > 1))) {							
						$newpackages[$k]['contents_cost'] -= $content['line_total'];
						unset($newpackages[$k]['contents'][$tok]);
						
						$totalpack = count($newpackages);
						$newpackages[$totalpack] = $newpackages[0];					
						
						$newpackages[$totalpack]['contents'] = array();
						$newpackages[$totalpack]['contents'][$tok] = $content;
						$newpackages[$totalpack]['contents_cost'] = $content['line_total'];
						$newpackages[$totalpack]['trialdays'] = (int) $trialdays;
						$package_count--;
				} else if($ifstype == 'Subscription') {		
					$newpackages[$k]['trialdays'] = (int) $trialdays;	
					break;
				}
			}
		}
		
		return $newpackages;
	} else return $packages;

}

function ia_get_available_shipping_methods($default_methods) {
	global $iwpro;

	if($iwpro->has_sub()) {	
		global $woocommerce;
		$available_methods = $default_methods;
		foreach($default_methods as $k => $method) {				
			if( empty($method->orig) ) 		$method->orig = $method->cost;
			else 							$available_methods[$k]->cost = $method->orig;
			
			if( empty($method->origtax) ) 	$method->origtax = $method->taxes;
			else							$available_methods[$k]->taxes   = $method->origtax;

			// CHECK FOR TRIALS:
			$packages = $woocommerce->shipping->packages;
			foreach($packages as $package) {
				if($package['trialdays'] > 0) {
					$rates = $package['rates'];

					$available_methods[$k]->subcost  = $rates[$method->id]->cost;							
					$available_methods[$k]->cost  	 -= $rates[$method->id]->cost;

					$available_methods[$k]->subtaxes = array();
					foreach($available_methods[$k]->taxes as $t => $tax) {
						$available_methods[$k]->subtaxes[$t]  = $rates[$method->id]->taxes[$t];
						$available_methods[$k]->taxes[$t]	 -= $rates[$method->id]->taxes[$t];
					}
					
				}
			}
		}

		return $available_methods;				
	} return $default_methods;
}


/**
 * ORDER OVERRIDES
**/		

function ia_woocommerce_order_subtotal_filter($subtotal, $item) {		
	global $iwpro;
	if(!isset($item['product_id'])) return $subtotal;	
	$ifstype  = get_post_meta($item['product_id'], 'infusionsoft_type', true);			
	
	if($ifstype == 'Subscription') {
		if(!$iwpro->ia_app_connect()) return;
		
		$sid   = (int) get_post_meta($item['product_id'], 'infusionsoft_sub', true);			
		$trial = (int) get_post_meta($item['product_id'], 'infusionsoft_trial', true);	
		
		$returnFields = array('Id','ProgramName','DefaultPrice','DefaultCycle','DefaultFrequency');
		$sub = $iwpro->app->dsLoad('CProgram',$sid,$returnFields);
		
		$stringCycle = '';
		switch($sub['DefaultCycle']) {						
				case 1: $stringCycle = 'yr'; break;						
				case 2: $stringCycle = 'mo'; break;						
				case 3: $stringCycle = 'wk'; break;						
				case 6: $stringCycle = 'day'; break;					
			}	
		$addS = '';					
		if($sub['DefaultFrequency'] > 1) $addS = 's';
		
		if($sub['DefaultFrequency'] == 1) $freq = '';
		else $freq = "{$sub['DefaultFrequency']} ";
		
		$sub_price = $iwpro->ia_get_sub_price($sid, $sub['DefaultPrice']);				
		$subtotal = $item['qty'] * $sub_price;
		
		if($trial == 0) $remark = ' starting immediately.';
		else $remark = ' starting on ' . date('M j, Y', (time() + $trial*24*60*60));
		
		return woocommerce_price($subtotal) . " every {$freq}{$stringCycle}{$addS}{$remark}";			
	} else return $subtotal;
}


function ia_woocommerce_order_items_table($order) {
	$subs = get_post_meta( $order->id, 'ia_subscriptions', true );
	
	if(!empty($subs)) {

	?>
		</tbody>
		</table>
		
		<h2>Recurring Orders</h2>
		<table class="shop_table order_details">
		<thead>
		<tr>
		<th>Subscription</th>
		<th>Price</th>
		<th>Billing Cycle</th>
		<th>Next Bill Date</th>
		</tr>
		</thead>
		<tbody>
		<?php 
		
		foreach($subs as $sub) {
			switch($sub['cycle']) {						
				case 1: $stringCycle = 'year'; break;						
				case 2: $stringCycle = 'month'; break;						
				case 3: $stringCycle = 'week'; break;						
				case 6: $stringCycle = 'day'; break;					
			}		
			
			$addS = '';					
			if($sub['freq'] > 1) $addS = 's';	
				
			$sub_cycle = "every {$sub['freq']} {$stringCycle}{$addS}";	
			$sub_next  = date('M j, Y', $sub['nextbilldate']);	
		
		?>
		<tr>
			<td><?php echo $sub['program']; ?></td>
			<td><?php echo woocommerce_price($sub['price']*$sub['qty']); ?></td>
			<td><?php echo $sub_cycle; ?></td>
			<td><?php echo $sub_next; ?></td>
		</tr>
	<?php 
		}
	} 
	
}

function ia_woocommerce_order_subtotals($subtotal, $item, $order) {						
	global $woocommerce;

	$newst = 0;
	$subfound = false;
	
	foreach ( $order->get_items() as $item ) {		
						
		$ifstype  = get_post_meta($item['product_id'], 'infusionsoft_type', true);			
	
		if($ifstype == 'Subscription') {
			$subfound = true;
			$trial = (int) get_post_meta($item['product_id'], 'infusionsoft_trial', true);					
			if($trial == 0)  $newst += $order->get_line_subtotal( $item );				
		} else {
			$newst += $order->get_line_subtotal( $item );
		}			
	}
	
	if($subfound) return woocommerce_price($newst);	
	else return $subtotal;
}


function ia_woocommerce_order_totals($total,$order) {						
	global $woocommerce;

	$ordertotal = $order->order_total;
	$subfound = false;
	
	foreach ( $order->get_items() as $item ) {		
						
		$ifstype  = get_post_meta($item['product_id'], 'infusionsoft_type', true);			
	
		if($ifstype == 'Subscription') {
			$subfound = true;
			$trial = (int) get_post_meta($item['product_id'], 'infusionsoft_trial', true);					
			if($trial > 0)  $ordertotal -= ($order->get_line_subtotal( $item ) + $item['line_subtotal_tax']);		
		} 			
	}
	
	if($subfound) return woocommerce_price($ordertotal);	
	else return $total;
}