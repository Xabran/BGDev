<?php
	if ( ! defined( 'ABSPATH' ) ) {
		exit; // Exit if accessed directly
	}

	add_action('template_redirect', 'iw_typagecontrol');

	function iw_typagecontrol() {
		global $iwpro;
		if(is_order_received_page()) {
			$overrides = get_option('iw_ty_ovs' );

			if(!is_array($overrides) || count($overrides) == 0) return false;

			$sorted_overrides = array();
			foreach($overrides as $k => $v) {
				$sorted_overrides[$overrides[$k]['order']] = $v;
			}
			ksort($sorted_overrides);

			// URL Vars:
			if(isset($_GET['view-order'])) {
				$orderid = $_GET['view-order'];
			}
			//check if on view order-received page and get parameter is available
			else if(isset($_GET['order-received'])) {
			    $orderid = $_GET['order-received'];
			}
			//no more get parameters in the url
			else {
			    $url = $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
			    $template_name = strpos($url,'/order-received/') === false ? '/view-order/' : '/order-received/';
			    if (strpos($url,$template_name) !== false) {
			        $start = strpos($url,$template_name);
			        $first_part = substr($url, $start+strlen($template_name));
			        $orderid = substr($first_part, 0, strpos($first_part, '/'));
			    }
			}



			if($orderid) {
				$order = new WC_Order($orderid);
				if(!$order->key_is_valid($_GET['key'])) {
					return false;
				}
			} else {
				return false;
			}

			$passvars = array(
					'FirstName'			=> stripslashes($order->billing_first_name),
					'LastName'			=> stripslashes($order->billing_last_name),
					'Email'				=> stripslashes($order->billing_email),
					'StreetAddress1' 	=> stripslashes($order->billing_address_1),
					'StreetAddress2' 	=> stripslashes($order->billing_address_2),
					'City' 				=> stripslashes($order->billing_city),
					'State' 			=> stripslashes($order->billing_state),
					'Country' 			=> stripslashes(iw_to_country($order->billing_country)),
					'PostalCode' 		=> stripslashes($order->billing_postcode),
					'Address2Street1' 	=> stripslashes($order->shipping_address_1),
					'Address2Street2' 	=> stripslashes($order->shipping_address_2),
					'City2' 			=> stripslashes($order->shipping_city),
					'State2' 			=> stripslashes($order->shipping_state),
					'Country2' 			=> stripslashes(iw_to_country($order->shipping_country)),
					'WooOrderId'		=> $orderid,
				);

			// override custom $passvars_key 
			foreach($passvars as $k => $v) {
				$new_key = apply_filters( 'iw_ty_variable_key', $k );
				if($new_key != $k) {
					$passvars[$new_key] = $v;
					unset($passvars[$k]); 
				}
			}

			$is_order_id = get_post_meta($orderid, 'infusionsoft_order_id', true );

			if($is_order_id) {
				$passvars['InfusionOrderId'] = $is_order_id;

				// get contact id
				if($iwpro->ia_app_connect()) {
					$job = $iwpro->app->dsLoad('Job', $is_order_id, array('ContactId'));
					if(isset($job['ContactId']))
						$passvars['contactId'] = $job['ContactId'];
				}
			}




			$getvars = array();
			foreach($_GET as $k => $v) {
				if($k != 'page_id') {
					$passvars[$k] = $v;
					$getvars[$k] = $v;
				}
			}

			$items = $order->get_items();
			$totals = $order->get_total();
			$count = $order->get_item_count();
			$usedcoups = $order->get_used_coupons();

			foreach($sorted_overrides as $o) {
				$redir = false;

				$redir = iw_check_ov_conds($o, $items, $totals, $count, $usedcoups, $order);


				if($redir) {
					$ty_uri = $o['url'];
					if($o['passvars'] == 'true') {
						if(strpos($ty_uri, "?") !== false)
							$ty_uri .= "&" . http_build_query($passvars);
						else 
							$ty_uri .= "?" . http_build_query($passvars);
					} else if(count($getvars) > 0) {
						if(strpos($ty_uri, "?") !== false)
							$ty_uri .= "&" . http_build_query($getvars);
						else 
							$ty_uri .= "?" . http_build_query($getvars);
					}

					header("Location: $ty_uri");
					exit();
					break;
				}
			}
		}
	}

	function iw_check_ov_conds($ov, $items, $totals, $count, $usedcoups, $order) {
		if(isset($ov['cond'])) {
			$conds = array(array(
					'type' => $ov['cond'],
					'further' => $ov['further']
				));
		} else {
			$conds = $ov['conds'];
		}


		foreach($conds as $c) {
			$sub_check = false;
			$type = $c['type'];
			$checks = $c['further'];

			if($type == 'always') {
				return true;
			} else if($type == 'product') {
				foreach($items as $item) {
					if(in_array($item['product_id'], $checks)) {
						$sub_check = true;
					}
				}
			} else if($type == 'categ') {
				foreach($items as $item) {
					$cats = get_the_terms($item['product_id'], 'product_cat');
					if(is_array($cats)) {
						foreach($cats as $cat) {
							if(in_array($cat->term_id, $checks)) {
								$sub_check = true;
							}
						}
					}

					if($sub_check) break;
				}
			} else if($type == 'morevalue') {
				$sub_check = ($totals > $checks);
			} else if($type == 'lessvalue') {
				$sub_check = ($totals < $checks);
			} else if($type == 'moreitem') {
				$sub_check = ($count > $checks);
			} else if($type == 'lessitem') {
				$sub_check = ($count < $checks);
			} else if($type == 'coupon') {
				if(empty($checks)) {
					$sub_check = count($usedcoups) > 0;
				} else {
					foreach($usedcoups as $coupon) {
						if(strpos($checks, $coupon) !== false) {
							$sub_check = true;
						}
					}
				}
			} else if($type == 'pg') {
				$sub_check = ($order->payment_method == $checks);
			}

			if(!$sub_check) {
				return false;
			}

		}

		return true;



	}
?>