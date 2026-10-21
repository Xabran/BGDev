<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'woocommerce_after_order_notes', 'iw_extra_checkout_fields' );
add_action( 'woocommerce_before_checkout_form', 'iw_date_field_ui');
add_action('woocommerce_checkout_process', 'iw_cf_checkrequired');
add_action( 'woocommerce_checkout_update_order_meta', 'iw_cf_save_to_infusionsoft' );
add_action('infusedwoo_payment_complete','iw_check_for_unsaved_fields', 10, 1);


function iw_extra_checkout_fields($checkout) {
	$iw_cf_groups = get_option('iw_cf_groups');
	$iw_cf_fields = get_option('iw_cf_fields');

	// group fields
	$grouped_fields = array();
	if(is_array($iw_cf_fields) && count($iw_cf_fields) > 0) {
		foreach($iw_cf_fields as $k => $f) {
			$grouped_fields[$f['grpid']][$f['order']] = $f;
		} 
	}

	if(empty($iw_cf_groups)) return;
	$sorted_cf_groups = array();

	foreach($iw_cf_groups as $k => $v) {
		$sorted_cf_groups[$iw_cf_groups[$k]['order']] = $v;
	}
	ksort($sorted_cf_groups);


	foreach($sorted_cf_groups as $k => $grp) {
	    $grp_fields = isset($grouped_fields[$grp['id']]) ? $grouped_fields[$grp['id']] : array();

		if(count($grp_fields) > 0) {
			$group_head_shown = false;
			$needs_closing = false;

			ksort($grp_fields); 
			foreach($grp_fields as $field) {
				if($field['disp'] == 'inherit') $usetest = $grp;
				else $usetest = $field;

				$further = isset($usetest['further']) ? $usetest['further'] : "";
				$showthis = iw_show_field($usetest['disp'], $further);
				if(!$showthis) continue;

				if(!$group_head_shown) {
					$show_group_name = apply_filters( 'iw_show_custom_field_group', true, $grp['name'] );
					echo '<div>';
					if($show_group_name) {
						echo '<h3>' . $grp['name'] . '</h3>';
					}
					$group_head_shown = true;
					$needs_closing = true;
				}

				$class = "";
				$custom_attr = array();
				$options = array("" => "");
				$type = 'text';
				$default = '';

				switch($field['type']) {
					case 'date':
						$class = 'iw-date';
						$class2 = 'form-row-wide';
					case 'text': 
						$type = 'text'; 
						$class2 = 'form-row-wide';
						break;
					case 'textarea': 
						$type = 'textarea';
						$class2 = 'form-row-wide';
						break;
					case 'multidropdown':
						$custom_attr = array('multiple' => 1);
					case 'dropdown':
						$type = 'select';
						$class2 = 'form-row-wide';
						foreach($field['options'] as $k => $v)
							$options[$v] = $v;
						break;
					case 'checkbox':
						$type = 'checkbox';
						$class2 = 'form-row-wide';
						$default = $field['default'];
						break;
					case 'radio':
						$type = 'radio';
						$class2 = 'form-row-wide';
						$options = array();
						foreach($field['options'] as $k => $v)
							$options[$v] = $v;
						break;

				}
				$fieldid = $field['type'] == 'multidropdown' ? $field['id'] . "[]" : $field['id'];

				if($type == 'checkbox' || $type == 'radio') {
					echo '<style>.'.'iw-extra-fields-' . $fieldid .' input { width: auto; }</style>';
				}

				$checkval = $checkout->get_value( 'iw-extra-fields-' . $field['id'] );

				$type = apply_filters( 'infusedwoo_custom_field_type', $type, $field );
				$default = apply_filters( 'infusedwoo_custom_field_default', $default, $field );

				if($field['type'] == 'checkbox') {
					echo '<input type="hidden" value="0" name="'.'iw-extra-fields-' . $fieldid.'"/>';
				}


				 woocommerce_form_field( 'iw-extra-fields-' . $fieldid, array(
			        'type'          => $type,
			        'class'         => array('iw-extra-fields-' . $field['id'], $class, $class2),
			        'label'         => $field['name'],
			        'custom_attributes' => $custom_attr,
			        'options'	=> $options,
			        'required'  => ($field['required'] == 'yes')
			     ), ($checkval ? $checkval : $default)); 

			} 
			
			if($needs_closing) {
		    	echo '</div>';
		    	$needs_closing = false;
		    }

		}
	}
}

function iw_show_field($type, $checks) {
	global $woocommerce;
	$show = false;
	$cart = $woocommerce->cart;

	if($type == 'always') {
		$show = true;
	} else if($type == 'product') {
		foreach($cart->cart_contents as $item) {
			if(in_array($item['product_id'], $checks)) {
				$show = true;
				break;
			}
		}
	} else if($type == 'categ') {
		foreach($cart->cart_contents as $item) {
			$cats = get_the_terms($item['product_id'], 'product_cat');

			if(is_array($cats)) {
				foreach($cats as $cat) {
					if(in_array($cat->term_id, $checks)) {
						$show = true;
						break;
					}
				}
			}

			if($show) break;
		}
	} else if($type == 'morevalue') {
		$show = ($cart->cart_contents_total > $checks);
	} else if($type == 'lessvalue') {
		$show = ($cart->cart_contents_total < $checks);
	} else if($type == 'moreitem') {
		$show = ($cart->cart_contents_count > $checks);
	} else if($type == 'lessitem') {
		$show = ($cart->cart_contents_count < $checks);
	} else if($type == 'coupon') {
		if(empty($checks)) {
			$show = count($cart->applied_coupons);
		} else {
			foreach($cart->applied_coupons as $coupon) {
				if(strpos($checks, $coupon) !== false) {
					$show = true;
					break;
				}
			}
		}
	}

	return $show;
}

function iw_date_field_ui() {
	wp_enqueue_style('jquery-ui-css',
                'https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.1/themes/smoothness/jquery-ui.css');
	wp_enqueue_script( 'iw-date-field-ui', INFUSEDWOO_PRO_URL . "assets/datepicker.js", array('jquery-ui-datepicker','jquery'));
}

function iw_cf_checkrequired() {
	$iw_cf_fields = get_option('iw_cf_fields');

	if(is_array($iw_cf_fields)) {
		foreach($iw_cf_fields as $field) {
			if(isset($_POST['iw-extra-fields-' . $field['id']])) {
				if($field['required'] == 'yes' && $field['type'] == 'checkbox') {
					$val = $_POST['iw-extra-fields-' . $field['id']];

					if($val != 1 && $val != 'yes') {
						wc_add_notice( __( 'You must check the "'. $field['name'] .'" checkbox.' ), 'error' );
					}
				}
				else if($field['required'] == 'yes' && empty($_POST['iw-extra-fields-' . $field['id']])) {
					wc_add_notice( __( 'Please enter something into the "'. $field['name'] .'" field' ), 'error' );
				} 		
			}
		}
	}
}

function iw_cf_save_to_infusionsoft( $order_id ) {
	$iw_cf_fields = get_option('iw_cf_fields');
	$contact_custom = array();
	$order_custom = array();
	global $iwpro;

	if(is_array($iw_cf_fields)) {
		foreach($iw_cf_fields as $field) {
			if(isset($_POST['iw-extra-fields-' . $field['id']])) {
				if(is_array($_POST['iw-extra-fields-' . $field['id']])) {
					$value = array_map("stripslashes", $_POST['iw-extra-fields-' . $field['id']]);
					$value = implode(",", $value);
				} else if($field['type'] == 'date') {
					$timestamp = strtotime($_POST['iw-extra-fields-' . $field['id']]);
					if($timestamp > 0) {
						$value = date("Y-m-d", $timestamp);
					} else {
						$value = "";
					}
				} else {
					$value = stripslashes($_POST['iw-extra-fields-' . $field['id']]);
				}

				update_post_meta( $order_id, iw_slugify($field['name']), $value);
				
				if(isset($field['infusionsoft']) && !empty($field['infusionsoft'])) {
					if(strpos($field['infusionsoft'], "Order:") !== false) {
						$fieldsplit = explode(":", $field['infusionsoft']);
						$fieldkey = $fieldsplit[1];
						$order_custom[$fieldkey] = $value;
					} else {
						$contact_custom[$field['infusionsoft']] = $value;
					}
				}
			}
		}
	}

	if(count($contact_custom) > 0) {
		$order = new WC_Order( $order_id );	
		$email = $order->billing_email;
		$contactinfo = $contact_custom;
		
		if($iwpro->ia_app_connect()) {
			// GET CONTACT ID
			$contact = $iwpro->app->dsFind('Contact',5,0,'Email',$email,array('Id'));
			$contact = isset($contact[0]) ? $contact[0] : false;	
		
			if ($contact['Id'] != null && $contact['Id'] != 0 && $contact != false){
				$contactId = (int) $contact['Id']; 
				$iwpro->app->dsUpdate('Contact',$contactId, $contactinfo);
			} else {				
				$contactinfo	= array();	
				$contactinfo['Email'] = $email;
				$contactId  = $iwpro->app->addCon($contactinfo);
			}
		}
	}

	if(count($order_custom) > 0) {
		$job_id = (int) get_post_meta($order_id, 'infusionsoft_order_id', true); 

		if($job_id > 0) {
			if($iwpro->ia_app_connect()) $iwpro->app->dsUpdate('Job',$job_id, $order_custom);
		} else {
			// save later
			update_post_meta($order_id, 'iw_save_custom_order_fields', $order_custom);
		}
	} 
}

function iw_check_for_unsaved_fields($order_id) {
	global $iwpro;
	$order_custom = get_post_meta($order_id, 'iw_save_custom_order_fields',true);

	if(is_array($order_custom) && count($order_custom) > 0) {
		$job_id = (int) get_post_meta($order_id, 'infusionsoft_order_id', true); 

		if($job_id > 0) {
			if($iwpro->ia_app_connect()) {
				$iwpro->app->dsUpdate('Job',$job_id, $order_custom);
				delete_post_meta($order_id, 'iw_save_custom_order_fields');
			}
		} 
	} 
}


function iw_slugify($text)
{ 
  // replace non letter or digits by -
  $text = preg_replace('~[^\\pL\d]+~u', '-', $text);

  // trim
  $text = trim($text, '-');

  // transliterate
  if(function_exists('iconv')) $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

  // lowercase
  $text = strtolower($text);

  // remove unwanted characters
  $text = preg_replace('~[^-\w]+~', '', $text);

  if (empty($text))
  {
    return 'n-a';
  }

  return $text;
}