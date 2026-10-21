<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


add_action( 'wp_ajax_ia_admin_apicreds', 'ia_admin_apicreds' );
add_action( 'wp_ajax_iw_count_orders', 'iw_count_orders' );
add_action( 'wp_ajax_iw_advanced_tracking', 'iw_advanced_tracking' );
add_action( 'wp_ajax_iw_enable_regtoifs', 'iw_enable_regtoifs' );
add_action( 'wp_ajax_iw_save_orders', 'iw_save_orders' );


add_action( 'wp_ajax_iw_cf_save_group', 'iw_cf_save_group');
add_action( 'wp_ajax_iw_cf_load_fields', 'iw_cf_load_fields');
add_action( 'wp_ajax_iw_cf_del_group', 'iw_cf_del_group');
add_action( 'wp_ajax_iw_cf_load_group', 'iw_cf_load_group');
add_action( 'wp_ajax_iw_cf_reposition_groups', 'iw_cf_reposition_groups');
add_action( 'wp_ajax_iw_cf_save_field', 'iw_cf_save_field');
add_action( 'wp_ajax_iw_cf_load_field', 'iw_cf_load_field');
add_action( 'wp_ajax_iw_cf_del_field', 'iw_cf_del_field');
add_action( 'wp_ajax_iw_cf_reposition_fields', 'iw_cf_reposition_fields');

add_action( 'wp_ajax_iw_ty_save_ov', 'iw_ty_save_ov');
add_action( 'wp_ajax_iw_ty_load_ovs', 'iw_ty_load_ovs');
add_action( 'wp_ajax_iw_ty_load_ov', 'iw_ty_load_ov');
add_action( 'wp_ajax_iw_ty_reposition_ovs', 'iw_ty_reposition_ovs');
add_action( 'wp_ajax_iw_ty_del_ov', 'iw_ty_del_ov');

add_action( 'wp_ajax_iw_data_search_wooproduct', 'iw_data_search_wooproduct');
add_filter( 'posts_where', 'iw_post_like_filter', 10, 2 );

// InfusedWoo 2.4
add_action( 'wp_ajax_iw_register_bgprocess', 'iw_register_bgprocess' );
add_action( 'wp_ajax_iw_get_bgprocess_status', 'iw_get_bgprocess_status' );
add_action( 'wp_ajax_iw_start_bgprocess', 'iw_start_bgprocess' );

// sync last order via HTTP Post:
add_action( 'wp_ajax_iw_create_wcorder', 'iw_create_wcorder' );
add_action( 'wp_ajax_nopriv_iw_create_wcorder','iw_create_wcorder');



function iw_post_like_filter( $where, &$wp_query ) {
    global $wpdb;
    if ( $iw_post_like = $wp_query->get( 'iw_post_like' ) ) {
        $where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'%' . esc_sql( $wpdb->esc_like( $iw_post_like ) ) . '%\'';
    }
    return $where;
}

function ia_admin_apicreds() {
	global $iwpro;
	if(!isset($_GET['app']) || !isset($_GET['api'])) {
		die("Empty App Name or API Key");
	}

	if(class_exists('iaSDK')) {
		$testapp = new iaSDK;
		
		$testapp->configCon($_GET['app'], $_GET['api']);
		$checker = $testapp->dsGetSetting('Contact', 'optiontypes');
		
		//VALIDATE CREDENTIALS
		$pos = strrpos($checker, "ERROR");

		if ($pos === false)  {
			if(isset($iwpro->settings)) {
				$settings = $iwpro->settings;
			} else {
				$settings = array();
			}

			$settings['enabled'] = "yes";
			$settings['machinename'] = $_GET['app'];
			$settings['apikey'] = $_GET['api'];


			update_option( $iwpro->plugin_id . $iwpro->id . '_settings', $settings );
			$iwpro->ia_app_connect();
			die("ok");					
		} else {
			die($checker);	
		}
	} else {
		die("API File / Conflict Error.");
	}		
}

function iw_count_orders() {
	global $iwpro;

	$count = 0;

	if($_POST['options']['step1'] == 'import') {
		if(!$iwpro->ia_app_connect()) return false;

		if($_POST['options']['step2'] == 'all') {
			$count = $iwpro->app->dsCount("Invoice",array("Id" => "%"));
		} else if($_POST['options']['step2'] == 'cat') {
			$count_paid = $iwpro->app->dsCount("Invoice",array("PayStatus" => 1));
			$count_unpaid = $iwpro->app->dsCount("Invoice",array("PayStatus" => 0));

			if(in_array("unpaid", $_POST['options']['step2further'])) {
				$count += $count_unpaid;
			} 

			if(in_array("paid", $_POST['options']['step2further'])) {
				$count += $count_paid;
			} 
		} else {
			$orders = array();
			$allowedids =  iw_split_entry($_POST['options']['step2further']);
			$proc_today = 0;

			// check allowed ids
			$max_id = max($allowedids);
			$min_id = min($allowedids);

			$allfound = false;
			$maxpage = false;
			$ifs_iids = array();
			$pg = 0;

			do {
				$invoice_ids = $iwpro->app->dsFind('Invoice',1000,$pg,'Id','%',array('JobId'));
				$maxpage = count($invoice_ids) < 1000;

				foreach($invoice_ids as $p) {
					if(in_array($p['JobId'], $allowedids)) {
						$ifs_iids[] = $p['JobId'];
					}

					if($p['JobId'] > $max_id) {
						$allfound = true;
						break;
					}
				}
				$pg++;
			} while(!$allfound && !$maxpage);


			$count = count($ifs_iids);
		}
	} else {
		if($_POST['options']['step2'] == 'all') {
			$count_info = wp_count_posts('shop_order');
			foreach(array_keys( wc_get_order_statuses() ) as $s ) {
				$count += $count_info->$s;
			}
		} else if($_POST['options']['step2'] == 'cat') {
			$count_info = wp_count_posts('shop_order');
			foreach($_POST['options']['step2further'] as $s ) {
				$count += $count_info->$s;
			}
		} else {
			$args= array(
			  'post_type' => 'shop_order',
			  'post_status' => array_keys( wc_get_order_statuses() ),
			  'posts_per_page' => -1,
			  'orderby'    => 'post_date',
			  'order'      => 'ASC',
			  'post__in'   => iw_split_entry($_POST['options']['step2further']),
			);

			$wc_query = new WP_Query($args);
			$count = $wc_query->post_count;
		}
		
	}

	echo $count;
	die();
}


function iw_split_entry($vals) {
	$split_entry = explode(",", $vals);
	$split_result = array();

	for($i = 0; $i < count($split_entry); $i++) {
		if(strpos($split_entry[$i], "-") !== false) {
			$ranged_input = explode("-", $split_entry[$i]);
			if((int) trim($ranged_input[1]) < (int) trim($ranged_input[0])) return false;

			for($j = (int) trim($ranged_input[0]); $j <= (int) trim($ranged_input[1]); $j++) {
				$split_result[] = $j;
			}
		} else if((int) trim($split_entry[$i]) > 0) {
			$split_result[] = (int) trim($split_entry[$i]);
		} else {
			return false;
		}
	}
	
	return $split_result;
}


function iw_save_orders() {
	global $iwpro;

	if(isset($iwpro->settings)) {
		$settings = $iwpro->settings;
	} else {
		$settings = array();
	}

	$settings['saveOrders'] = $_GET['enable'] == 'true' ? "yes" : "no";


	update_option( $iwpro->plugin_id . $iwpro->id . '_settings', $settings );
}

function iw_enable_regtoifs() {
	global $iwpro;

	if(isset($iwpro->settings)) {
		$settings = $iwpro->settings;
	} else {
		$settings = array();
	}

	$settings['regtoifs'] = $_GET['enable'] == 'true' ? "yes" : "no";


	update_option( $iwpro->plugin_id . $iwpro->id . '_settings', $settings );
}

function iw_advanced_tracking() {
	global $iwpro;

	if(isset($iwpro->settings)) {
		$settings = $iwpro->settings;
	} else {
		$settings = array();
	}

	$settings['advancedTracking'] = $_GET['enable'] == 'true' ? "yes" : "no";


	update_option( $iwpro->plugin_id . $iwpro->id . '_settings', $settings );
}

function iw_cf_save_group() {
	$iw_cf_groups = get_option('iw_cf_groups');
	if(empty($iw_cf_groups)) $iw_cf_groups = array();

	if(isset($_POST['id']) && !empty($_POST['id'])) {
		if(count($iw_cf_groups) > 0) {
			foreach($iw_cf_groups as $k => $grp) {
				if($k == $_POST['id']) {
					$_POST['order'] = isset($iw_cf_groups[$_POST['id']]['order']) ? $iw_cf_groups[$_POST['id']]['order'] : $iw_cf_groups[$_POST['id']]['order'];
					$iw_cf_groups[$k] = $_POST;
				}
			}
		} else {
			$_POST['order'] = isset($iw_cf_groups[$_POST['id']]) ? $iw_cf_groups[$_POST['id']]['order'] : $iw_cf_groups[$_POST['id']]['order'];
			$iw_cf_groups[$_POST['id']] = $_POST; 
		}
	} else {
		if(count($iw_cf_groups) > 0) {
			$new_id = max(array_keys($iw_cf_groups)) + 1;
			$_POST['id'] = $new_id;
			$_POST['order'] = $new_id;
			$iw_cf_groups[$new_id] = $_POST; 
		} else {
			$_POST['id'] = 1;
			$_POST['order'] = 1;
			$iw_cf_groups[1] = $_POST; 
		}
	}

	update_option( 'iw_cf_groups', $iw_cf_groups );
	die("ok");
}

function iw_cf_load_fields() {
	$iw_cf_groups = get_option('iw_cf_groups');
	$iw_cf_fields = get_option('iw_cf_fields');

	// group fields
	$grouped_fields = array();
	if(is_array($iw_cf_fields) && count($iw_cf_fields) > 0) {
		foreach($iw_cf_fields as $k => $f) {
			$grouped_fields[$f['grpid']][$f['order']] = $f;
		} 
	}

	if(empty($iw_cf_groups)) die("");
	$sorted_cf_groups = array();

	foreach($iw_cf_groups as $k => $v) {
		$sorted_cf_groups[$iw_cf_groups[$k]['order']] = $v;
	}
	ksort($sorted_cf_groups);
	foreach($sorted_cf_groups as $k => $grp) {
		?>
		<li class="iw_cf_group" grpid="<?php echo $grp['id']; ?>">
			<span class="iw_cf_group_name"><?php echo strlen($grp['name']) > 35 ? substr($grp['name'], 0, 34) . "..." : $grp['name']; ?>
				<span class="controls">
					<i class="fa fa-plus grp-add" grpid="<?php echo $grp['id']; ?>" title="Add new custom field"></i>
					<i class="fa fa-pencil grp-edit" grpid="<?php echo $grp['id']; ?>" title="Group Settings"></i>
					<i class="fa fa-times grp-delete" grpid="<?php echo $grp['id']; ?>" title="Delete Group"></i>
				</span>
			</span>
			<?php 
				$grp_fields = isset($grouped_fields[$grp['id']]) ? $grouped_fields[$grp['id']] : array();

				if(count($grp_fields) > 0) {
					ksort($grp_fields);
					?>
					<ul class="iw_cf_fields"> 
					<?php foreach($grp_fields as $field) {
					?>
					<li class="iw_cf_field" fieldid="<?php echo $field['id']; ?>"><?php echo strlen($field['name']) > 35 ? substr($field['name'], 0, 34) . "..." : $field['name']; ?>
						<span class="controls">
							<i class="fa fa-pencil field-edit" title="Edit Custom Field" fieldid="<?php echo $field['id']; ?>"></i>
							<i class="fa fa-times field-delete" title="Delete Custom Field" fieldid="<?php echo $field['id']; ?>"></i>
						</span>
					</li>
					<?php } ?>
					</ul>
				<?php } ?>
		</li>
		<?php
	}

	die();
}

function iw_cf_del_group() {
	if(isset($_POST['grpid']) && $_POST['grpid'] >0) {
		$iw_cf_groups = get_option('iw_cf_groups');
		$iw_cf_fields = get_option('iw_cf_fields');

		if(empty($iw_cf_groups)) die("");

		if(in_array($_POST['grpid'], array_keys($iw_cf_groups)))
			unset($iw_cf_groups[$_POST['grpid']]);

		foreach($iw_cf_fields as $k => $v) {
			if($v['grpid'] == $_POST['grpid']) unset($iw_cf_fields[$k]);
		}

		update_option( 'iw_cf_groups', $iw_cf_groups );
		update_option( 'iw_cf_fields', $iw_cf_fields );
		die();
	}
}

function iw_cf_del_field() {
	if(isset($_POST['fieldid']) && $_POST['fieldid'] >0) {
		$iw_cf_fields = get_option('iw_cf_fields');
		if(empty($iw_cf_fields)) die("");

		if(in_array($_POST['fieldid'], array_keys($iw_cf_fields)))
			unset($iw_cf_fields[$_POST['fieldid']]);

		update_option( 'iw_cf_fields', $iw_cf_fields );
		die();
	}
}


function iw_cf_load_group() {
	if(isset($_GET['grpid']) && $_GET['grpid'] >0) {
		$iw_cf_groups = get_option('iw_cf_groups');
		if(empty($iw_cf_groups)) die("");

		if(in_array($_GET['grpid'], array_keys($iw_cf_groups))) {
			$result = $iw_cf_groups[$_GET['grpid']];
			if(!isset($_GET['callback'])) echo json_encode($result);
			else echo "{$_GET['callback']}(" . json_encode($result) . ")";
		}

		die();
	}	
}

function iw_cf_reposition_groups() {
	if(isset($_POST['position'])) {
		$iw_cf_groups = get_option('iw_cf_groups');
		$i = 0;

		foreach($_POST['position'] as $v) {
			$iw_cf_groups[$v]['order'] = $i++;
		}		

		update_option( 'iw_cf_groups', $iw_cf_groups );
	}	

	die();
}

function iw_cf_save_field() {	
	$iw_cf_fields = get_option('iw_cf_fields');
	if(empty($iw_cf_fields)) $iw_cf_fields = array();

	$_POST['name'] = stripslashes($_POST['name']);
	if(isset($_POST['further']) && !is_array($_POST['further'])) $_POST['further'] == stripslashes($_POST['further']);

	if(isset($_POST['options']) && is_array($_POST['options'])) {
		foreach($_POST['options'] as $k => $v) {
			$_POST['options'][$k] = stripslashes($v);
		}
	}

	if(isset($_POST['id']) && !empty($_POST['id'])) {
		if(count($iw_cf_fields) > 0) {
			foreach($iw_cf_fields as $k => $grp) {
				if($k == $_POST['id']) {
					$_POST['order'] = isset($iw_cf_fields[$_POST['id']]['order']) ? $iw_cf_fields[$_POST['id']]['order'] : $iw_cf_fields[$_POST['id']]['order'];
					$iw_cf_fields[$k] = $_POST;
				}
			}
		} else {
			$_POST['order'] = isset($iw_cf_fields[$_POST['id']]) ? $iw_cf_fields[$_POST['id']]['order'] : $iw_cf_fields[$_POST['id']]['order'];
			$iw_cf_fields[$_POST['id']] = $_POST; 
		}
	} else {
		if(count($iw_cf_fields) > 0) {
			$new_id = max(array_keys($iw_cf_fields)) + 1;
			$_POST['id'] = $new_id;
			$_POST['order'] = $new_id;
			$iw_cf_fields[$new_id] = $_POST; 
		} else {
			$_POST['id'] = 1;
			$_POST['order'] = 1;
			$iw_cf_fields[1] = $_POST; 
		}
	}

	update_option( 'iw_cf_fields', $iw_cf_fields );

	die("ok");

}

function iw_cf_load_field() {
	if(isset($_GET['fieldid']) && $_GET['fieldid'] >0) {
		$iw_cf_fields = get_option('iw_cf_fields');
		if(empty($iw_cf_fields)) die("");

		if(in_array($_GET['fieldid'], array_keys($iw_cf_fields))) {
			$result = $iw_cf_fields[$_GET['fieldid']];
			if(!isset($_GET['callback'])) echo json_encode($result);
			else echo "{$_GET['callback']}(" . json_encode($result) . ")";
		}

		die();
	}	
}

function iw_cf_reposition_fields() {
	if(isset($_POST['position'])) {
		$iw_cf_fields = get_option('iw_cf_fields');
		$i = 0;

		foreach($_POST['position'] as $v) {
			$iw_cf_fields[$v]['order'] = $i++;
		}		

		update_option( 'iw_cf_fields', $iw_cf_fields );
	}	

	die();
}




function iw_ty_save_ov() {
	$iw_ty_ovs = get_option('iw_ty_ovs');
	if(empty($iw_ty_ovs)) $iw_ty_ovs = array();

	if(isset($_POST['id']) && !empty($_POST['id'])) {
		if(count($iw_ty_ovs) > 0) {
			foreach($iw_ty_ovs as $k => $ov) {
				if($k == $_POST['id']) {
					$_POST['order'] = isset($iw_ty_ovs[$_POST['id']]['order']) ? $iw_ty_ovs[$_POST['id']]['order'] : $iw_ty_ovs[$_POST['id']]['order'];
					$iw_ty_ovs[$k] = $_POST;
				}
			}
		} else {
			$_POST['order'] = isset($iw_ty_ovs[$_POST['id']]) ? $iw_ty_ovs[$_POST['id']]['order'] : $iw_ty_ovs[$_POST['id']]['order'];
			$iw_ty_ovs[$_POST['id']] = $_POST; 
		}
	} else {
		if(count($iw_ty_ovs) > 0) {
			$new_id = max(array_keys($iw_ty_ovs)) + 1;
			$_POST['id'] = $new_id;
			$_POST['order'] = $new_id;
			$iw_ty_ovs[$new_id] = $_POST; 
		} else {
			$_POST['id'] = 1;
			$_POST['order'] = 1;
			$iw_ty_ovs[1] = $_POST; 
		}
	}

	update_option( 'iw_ty_ovs', $iw_ty_ovs );
	die("ok");
}


function iw_ty_load_ovs() {
	$iw_ty_ovs = get_option('iw_ty_ovs');

	if(empty($iw_ty_ovs)) die("");
	$sorted_ovs = array();

	foreach($iw_ty_ovs as $k => $v) {
		$sorted_ovs[$iw_ty_ovs[$k]['order']] = $v;
	}
	ksort($sorted_ovs);
	foreach($sorted_ovs as $k => $ov) {
		?>
		<li class="iw_ty_ov_li" ovid="<?php echo $ov['id']; ?>">
			<span class="iw_ty_ov_name"><?php echo strlen($ov['name']) > 35 ? substr($ov['name'], 0, 34) . "..." : $ov['name']; ?>
				<span class="controls">
					<i class="fa fa-pencil ov-edit" ovid="<?php echo $ov['id']; ?>" title="Override Settings"></i>
					<i class="fa fa-times ov-delete" ovid="<?php echo $ov['id']; ?>" title="Delete Override"></i>
				</span>
			</span>
		</li>
		<?php
	}

	die();
}

function iw_ty_load_ov() {
	if(isset($_GET['ovid']) && $_GET['ovid'] >0) {
		$iw_ty_ovs = get_option('iw_ty_ovs');
		if(empty($iw_ty_ovs)) die("");

		if(in_array($_GET['ovid'], array_keys($iw_ty_ovs))) {
			$result = $iw_ty_ovs[$_GET['ovid']];
			if(!isset($_GET['callback'])) echo json_encode($result);
			else echo "{$_GET['callback']}(" . json_encode($result) . ")";
		}

		die();
	}	
}

function iw_ty_reposition_ovs() {
	if(isset($_POST['position'])) {
		$iw_ty_ovs = get_option('iw_ty_ovs');
		$i = 0;

		foreach($_POST['position'] as $v) {
			$iw_ty_ovs[$v]['order'] = $i++;
		}		

		update_option( 'iw_ty_ovs', $iw_ty_ovs );
	}	

	die();
}

function iw_ty_del_ov() {
	if(isset($_POST['ovid']) && $_POST['ovid'] >0) {
		$iw_ty_ovs = get_option('iw_ty_ovs');

		if(empty($iw_ty_ovs)) die("");

		if(in_array($_POST['ovid'], array_keys($iw_ty_ovs)))
			unset($iw_ty_ovs[$_POST['ovid']]);

		update_option( 'iw_ty_ovs', $iw_ty_ovs );
		die();
	}
}

function iw_data_search_wooproduct() {
	$args = array(
		'posts_per_page'   => 999999,
		'orderby'          => 'post_date',
		'order'            => 'ASC',
		'post_type'        => 'product',
		'post_status'      => 'publish',
		'iw_post_like'	   => $_GET['term']
	);

	$data = array();

	$query1 = new WP_Query( $args );

	// The Loop
	while ( $query1->have_posts() ) {
		$query1->the_post();

		$data[] = array(
				'label' => get_the_title($query1->post->ID),
				'value' => $query1->post->ID,
				'id'	=> $query1->post->ID
			);
	}

	if(count($data) == 0) {
		$data[] = array(
				"label" => "No Products Found.", 
				'value' => ""
			);
	}

	echo json_encode($data);
	exit();
}

function iw_upload_blob($blob, $product_id) {
	if(is_admin()) {
		$upload_dir = wp_upload_dir();
		file_put_contents(($upload_dir['path'] . "/iw_product" . $product_id . ".jpg"), $blob);
		$img_subdir = $upload_dir['subdir'] . "/iw_product" . $product_id . ".jpg";
		$img_subdir = substr($img_subdir, 1);
		return $img_subdir;
	}
}















// NEW IMPORT / EXPORT PROCESS
function iw_bgp_error_handler($severity, $message, $filename, $lineno) {
  if (error_reporting() == 0) {
    return;
  }
  if (error_reporting() & $severity) {
    throw new ErrorException($message, 0, $severity, $filename, $lineno);
  }
}


function iw_register_bgprocess() {
	error_reporting(E_ALL & ~E_NOTICE);
	if(is_admin()) {
		set_error_handler('iw_bgp_error_handler');

		try {
			global $iwpro;
			if(!$iwpro->ia_app_connect()) {
				echo json_encode(array('success' => 0,'errormsg' => 'Cannot connect to Infusionsoft. Please ensure your integration settings are properly set.'));
				exit();
			}

			$new_process = $_POST;

			$iw_bgprocesses = get_option('iw_bgprocesses', array());
			$iw_bgprocesses = $iw_bgprocesses == '' ? array() : $iw_bgprocesses;
			$iw_bgprocesses[] = array();
			$processes_keys = array_keys($iw_bgprocesses);
			$last_key = $processes_keys[count($processes_keys)-1];

			$new_process['status'] = 'pending';

			$refid = $last_key;
			$new_process['refid'] = $refid;


			if($new_process['process'] == 'product_migrate') {
				// calculate total:
				if($new_process['step1'] == 'import') {
					if($new_process['step2'] == 'all') {
						$total = $iwpro->app->dsCount('Product', array('Id' => '%'));
					} else if($new_process['step2'] == 'cat') {
						$total = 0;
						foreach($new_process['step2further'] as $cat_id) {
							$total += $iwpro->app->dsCount('ProductCategoryAssign', array('ProductCategoryId' => (int) $cat_id));
						}
					} else {
						$allowedids =  iw_split_entry($new_process['step2further']);

						// check allowed ids
						$max_id = max($allowedids);
						$min_id = min($allowedids);

						$allfound = false;
						$maxpage = false;
						$ifs_pids = array();
						$page = 0;
						do {
							$product_ids = $iwpro->app->dsFind('Product',1000,$page,'Id','%',array('Id'));
							$maxpage = count($product_ids) < 1000;

							foreach($product_ids as $p) {
								if(in_array($p['Id'], $allowedids)) {
									$ifs_pids[] = $p['Id'];
								}

								if($p['Id'] > $max_id) {
									$allfound = true;
									break;
								}
							}
							$page++;
						} while(!$allfound && !$maxpage);

						$total = count($ifs_pids);
					}
				} else {
					if($new_process['step2'] == 'cat') {
						$args= array(
						  'post_type' => 'product',
						  'post_status' => 'publish',
						  'posts_per_page' => -1,
						  'orderby'    => 'post_date',
						  'order'      => 'ASC',
						  'tax_query' => array(
								array(
									'taxonomy' => 'product_cat',
									'field'    => 'term_id',
									'terms'    => $new_process['step2further'],
								),
							)
						);
					} else if($new_process['step2'] == 'all') {
						$args= array(
						  'post_type' => 'product',
						  'post_status' => 'publish',
						  'posts_per_page' => -1,
						  'orderby'    => 'post_date',
						  'order'      => 'ASC',
						);
					} else {
						$args= array(
						  'post_type' => 'product',
						  'post_status' => 'publish',
						  'posts_per_page' => -1,
						  'orderby'    => 'post_date',
						  'order'      => 'ASC',
						  'post__in'   => iw_split_entry($new_process['step2further'])
						);
					}
					$wc_query = new WP_Query($args);
					$total = $wc_query->post_count;
				}
				$new_process['total'] = $total;
				
			} else {
				$total = (int) $new_process['order_count'];
				$new_process['total'] = $total;
			}

			$iw_bgprocesses[$last_key] = $new_process;
			update_option('iw_bgprocesses', $iw_bgprocesses);
			update_option("iw_bgp{$refid}_log_lastline", -1 );
			update_option("iw_bgp{$refid}_logs", array() );
			echo json_encode(array('success' => 1,'total' => $total, 'refid' => $refid));
		} catch(Exception $e) {
			$errmsg = $e->getMessage() . " on line " . $e->getLine() . " of " . $e->getFile();
			echo json_encode(array('success' => 0,'errormsg' => $errmsg));
		}

	}

	exit();
}

function iw_start_bgprocess() {
	$iw_bgprocesses = get_option('iw_bgprocesses');
	$process = $_POST;

	ignore_user_abort();
	ini_set('max_execution_time', 0);
	set_time_limit(0);
	session_write_close();
	date_default_timezone_set('GMT');
	
	if(isset($iw_bgprocesses[$process['refid']])) {
		$iw_bgprocesses[$process['refid']]['status'] = 'pending';
		update_option( 'iw_bgprocesses', $iw_bgprocesses  );
	} else {
		$process['status'] = 'pending';
		$iw_bgprocesses[$process['refid']] = $process;
		update_option( 'iw_bgprocesses', $iw_bgprocesses  );
	}

	try{
		if($process['process'] == 'product_migrate') {
			iw_product_migrate($process);
		} else if($process['process'] == 'order_migrate') {
			iw_order_migrate($process);
		}
	} catch(Exception $e) {
		$errmsg = $e->getMessage() . " on line " . $e->getLine() . " of " . $e->getFile();
		$process['status'] = 'failed';
		$process['errormsg'] = $errmsg;
		$iw_bgprocesses[$process['refid']] = $process;
		update_option( 'iw_bgprocesses', $iw_bgprocesses  );
	}

	exit();
}

function iw_get_bgprocess_status() {
	error_reporting(E_ALL & ~E_NOTICE);

	if(is_admin() && isset($_POST['refid'])) {
		$refid = (int) $_POST['refid'];
		wp_cache_flush();
		$iw_bgprocesses = get_option('iw_bgprocesses', array());
		if(isset($iw_bgprocesses[$refid])) {
			$this_process = $iw_bgprocesses[$refid]; 

			$this_process['success'] = 1;
			
			// get logs
			$logs = get_option("iw_bgp{$refid}_logs");
			if($logs === false) $logs = array();
			$this_process['logs'] = $logs;

			if(count($logs) > 0) {
				//get last line
				$line_nos = array_keys($logs);
				$last_line = $line_nos[(int) count($line_nos)-1];
				$this_process['last_log_line'] = $last_line;
				update_option("iw_bgp{$refid}_log_lastline", $last_line, false);
			}


			if($this_process['status'] == 'success') {
				unset($iw_bgprocesses[$refid]);
				update_option('iw_bgprocesses', $iw_bgprocesses);
				delete_option( "iw_bgp{$refid}_log_lastline" );
				delete_option("iw_bgp{$refid}_logs" );
			}

			echo json_encode($this_process); 
			exit();
		}
	}

	update_option('iw_bgprocesses', array());
	echo json_encode(array('success' => 0, 'errormsg' => 'Process no longer exist. Please redo the import / export steps.'));
	exit();
}


function iw_product_migrate($pr) {
	// fetch products
	global $iwpro;
	$iw_bgprocesses = get_option('iw_bgprocesses', array());

	if(!isset($iw_bgprocesses[$pr['refid']])) {
		return false;
	}

	$process = $iw_bgprocesses[$pr['refid']];

	if(!$iwpro->ia_app_connect()) {
		$process['status'] = 'failed';
		$process['errormsg'] = 'Cannot connect to Infusionsoft. Please ensure your integration settings are properly set.';
		$iw_bgprocesses[$pr['refid']] = $process;
		return false;
	}

	if($pr['step1'] == 'import') {
		iw_bgp_import_prods($process);
	} else {
		iw_bgp_export_prods($process);
	}
}



function iw_bgprocess_log($process, $log) {
	// get last line
	wp_cache_flush();
	$refid = (int) $process['refid'];
	$last_line = get_option("iw_bgp{$refid}_log_lastline", -1 );

	// get logs
	$logs = get_option("iw_bgp{$refid}_logs" );
	if($logs === false) $logs = array();

	if($last_line > -1) {
		for($i = 0; $i <= $last_line; $i++) {
			unset($logs[$i]);
		}
	}

	if(count($logs) > 0) {
		$lines = array_keys($logs);
		$k = $lines[count($lines) - 1] + 1;
	} else {
		$k = $last_line+1;
	}
	$logs[$k] = date("Y-m-d H:i:s") . " " . $log;

	update_option( "iw_bgp{$refid}_logs", $logs);
}






function iw_bgp_import_prods($pr) {
	global $iwpro; 
	$iw_bgprocesses = get_option('iw_bgprocesses', array());

	$process = $pr;

	$q = array(
			'Id',
			'Sku',
			'ProductName',
			'ProductPrice',
			'ShortDescription',
			'Taxable',
			'Weight',
			'Description',
			'TopHTML',
			'BottomHTML',
			'InventoryLimit',
			'Shippable'
		);

	if($process['step3']['images'] == 'yes') $q[] = 'LargeImage';

	$page = isset($process['page']) ? $process['page']: 0;
	$index = isset($process['index']) ? $process['index']: 0;
	$fetch = isset($process['fetch']) ? $process['fetch']: 250;

	if(!isset($iw_bgprocesses[$pr['refid']]['processed'])) $iw_bgprocesses[$pr['refid']]['processed'] = 0;

	if($process['step2'] == 'all') {
		$products = $iwpro->app->dsFind('Product', $fetch, $page, 'Id', '%', $q);

		$ind = -1;
		foreach($products as $product) {
			$ind++;
			if($ind < $index) continue;

			try {
				iw_import_prod($product, $pr);

				$iw_bgprocesses[$pr['refid']]['processed']++;
				$iw_bgprocesses[$pr['refid']]['status'] = 'pending';
				$iw_bgprocesses[$pr['refid']]['index'] = $ind;
				update_option('iw_bgprocesses', $iw_bgprocesses);
			} catch(Exception $e) {
				$errmsg = $e->getMessage() . " on line " . $e->getLine() . " of " . $e->getFile();
				$process['status'] = 'failed';
				$process['errormsg'] = $errmsg;
				$iw_bgprocesses[$pr['refid']] = $process;
				iw_bgprocess_log($process, "Processed Infusion Product. Error found: " . $errmsg);	
				return false;
			}

		}
		$page++;

		if($iw_bgprocesses[$pr['refid']]['processed'] >= $iw_bgprocesses[$pr['refid']]['total']) {
			$iw_bgprocesses[$pr['refid']]['status'] = 'success';
		} else {
			$iw_bgprocesses[$pr['refid']]['status'] = 'paused';
			$iw_bgprocesses[$pr['refid']]['page'] = $page;
			$iw_bgprocesses[$pr['refid']]['index'] = 0;
		}
		update_option('iw_bgprocesses', $iw_bgprocesses);
	} else if($process['step2'] == 'cat') {
		$pincats = array();
  		$bucket = array();
  		$i = 0;

  		foreach($process['step2further'] as $cat_id) {
	  		do {
				$bucket = $iwpro->app->dsFind('ProductCategoryAssign',1000,$i,'ProductCategoryId',(int) $cat_id, array('ProductId','ProductCategoryId'));
				if(is_array($bucket)) $pincats = array_merge($pincats, $bucket);
				$i++;
			} while(count($bucket) == 1000);
		}
		$ind = -1;
		$proc_today = 0;

		foreach($pincats as $p) {
			$ind++;
			if($ind < $index) continue;

			try {
				$product = $iwpro->app->dsLoad('Product', (int) $p['ProductId'], $q);
				iw_import_prod($product, $process);
				
				$iw_bgprocesses[$pr['refid']]['status'] = 'pending';
				$proc_today++;
				$iw_bgprocesses[$pr['refid']]['index'] = $ind + 1;
				$iw_bgprocesses[$pr['refid']]['processed']++;


				update_option('iw_bgprocesses', $iw_bgprocesses);
				if($proc_today >= $fetch) break;
			} catch(Exception $e) {
				$errmsg = $e->getMessage() . " on line " . $e->getLine() . " of " . $e->getFile();
				$process['status'] = 'failed';
				$process['errormsg'] = $errmsg;
				iw_bgprocess_log($process, "Processed Infusion Product. Error found: " . $errmsg);	
				$iw_bgprocesses[$pr['refid']] = $process;
				return false;
			}
		}

		if($iw_bgprocesses[$pr['refid']]['processed'] >= $iw_bgprocesses[$pr['refid']]['total']) {
			$iw_bgprocesses[$pr['refid']]['status'] = 'success';
		} else {
			$iw_bgprocesses[$pr['refid']]['status'] = 'paused';
			$iw_bgprocesses[$pr['refid']]['index'] = $ind + 1;
		}
		update_option('iw_bgprocesses', $iw_bgprocesses);
	} else {
		$allowedids =  iw_split_entry($process['step2further']);
		$proc_today = 0;

		// check allowed ids
		$max_id = max($allowedids);
		$min_id = min($allowedids);

		$allfound = false;
		$maxpage = false;
		$ifs_pids = array();
		$page = 0;

		do {
			$product_ids = $iwpro->app->dsFind('Product',1000,$page,'Id','%',array('Id'));
			$maxpage = count($product_ids) < 1000;

			foreach($product_ids as $p) {
				if(in_array($p['Id'], $allowedids)) {
					$ifs_pids[] = $p['Id'];
				}

				if($p['Id'] > $max_id) {
					$allfound = true;
					break;
				}
			}
			$page++;
		} while(!$allfound && !$maxpage);


		foreach($ifs_pids as $k => $v) {
			if($k < $index) continue;

			try {
				$product = $iwpro->app->dsLoad('Product', (int) $v, $q);

				if(is_array($product) && isset($product['Id'])) {
					$proc_today++;
					iw_import_prod($product, $process);
					$iw_bgprocesses[$pr['refid']]['processed']++;
				}
				
				$iw_bgprocesses[$pr['refid']]['status'] = 'pending';
				$iw_bgprocesses[$pr['refid']]['index'] = $k + 1;
				update_option('iw_bgprocesses', $iw_bgprocesses);
				if($proc_today >= $fetch) break;
			} catch(Exception $e) {
				$errmsg = $e->getMessage() . " on line " . $e->getLine() . " of " . $e->getFile();
				$process['status'] = 'failed';
				$process['errormsg'] = $errmsg;
				iw_bgprocess_log($process, "Processed Infusion Product. Error found: " .$errmsg);	
				$iw_bgprocesses[$pr['refid']] = $process;
				return false;
			}

		}

		if($iw_bgprocesses[$pr['refid']]['processed'] >= $iw_bgprocesses[$pr['refid']]['total']) {
			$iw_bgprocesses[$pr['refid']]['status'] = 'success';
		} else {
			$iw_bgprocesses[$pr['refid']]['status'] = 'paused';
			$iw_bgprocesses[$pr['refid']]['index'] = $k + 1;
		}
		update_option('iw_bgprocesses', $iw_bgprocesses);
	}

	$iwpro->ia_woocommerce_update_product_options();
}

function iw_get_infusion_prodcats() {
	$prodcats = get_transient( 'iw_infusion_prodcats' );

	if($prodcats == false){
		global $iwpro;
		$prodcats_t = $iwpro->app->dsFind('ProductCategory',1000,0,'Id','%',array('Id','CategoryDisplayName'));
	  	
		$prodcats = array();
	  	foreach($prodcats_t as $pc) {
	  		$prodcats[$pc['Id']] = $pc['CategoryDisplayName'];
	  	}  

	  	set_transient( 'iw_infusion_prodcats', $prodcats, 60*60*24 );
 	}

 	return $prodcats;
}

function iw_import_prod($product, $process) {
	global $woocommerce;
  	global $iwpro;
  	global $wpdb;
  	error_reporting(0);

  	$product_cat_ids = $iwpro->app->dsFind('ProductCategoryAssign',1000,0,'ProductId', $product['Id'], array('ProductCategoryId'));
  	$prodcats = iw_get_infusion_prodcats();

  	// SEARCH BY SKU:
  	$sku = isset($product['Sku']) ? $product['Sku'] : "";
  	$pid = isset($product['Id']) ? $product['Id'] : "";

  	if(!empty($sku)) {
	  	$product_id = $wpdb->get_var($wpdb->prepare("SELECT posts.ID FROM $wpdb->posts posts
	  		INNER JOIN $wpdb->postmeta postmeta ON posts.ID = postmeta.post_id
	  		WHERE postmeta.meta_key='_sku' AND postmeta.meta_value='%s' AND posts.post_type='product' AND posts.post_status NOT LIKE 'trash' LIMIT 1
	  		", $sku ));

	  	if(!empty($product_id)) iw_bgprocess_log($process, "Processed Infusion Product # $pid. Matched via SKU $sku");
  	} else {
	  	$product_id = $wpdb->get_var($wpdb->prepare("SELECT posts.ID FROM $wpdb->posts posts
	  		INNER JOIN $wpdb->postmeta postmeta ON posts.ID = postmeta.post_id
	  		WHERE postmeta.meta_key='infusionsoft_product' AND postmeta.meta_value='%s' AND posts.post_type='product' AND posts.post_status NOT LIKE 'trash' LIMIT 1
	  		", $pid ));	

	  	if(!empty($product_id)) iw_bgprocess_log($process, "Processed Infusion Product # $pid. Matched via existing product ID setting.");			  		
  	}

  	if(empty($product_id)) {
  		// if not exist, create new product
		$new_product = array(
		  'post_title'    => $product['ProductName'],
		  'post_status'   => 'publish',
		  'post_type' => 'product'
		);


		// Insert the post into the database
		$product_id = wp_insert_post( $new_product );
		
		iw_bgprocess_log($process, "Processed Infusion Product # $pid. No match found. Created new Woo Product # $product_id");	
  	} else {
  		$upd_content = array(
			      'ID'          => $product_id,
			      'post_title' 	=> $product['ProductName']
			  );

  		wp_update_post( $upd_content );
  	}

  	// ------- IMAGE: Still working...
  	if(isset($product['LargeImage']) && !empty($product['LargeImage']) && $process['step3']['images'] == 'yes') {
  		require_once( ABSPATH . 'wp-admin/includes/image.php' );
  		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		$wp_upload_dir = wp_upload_dir();

  		$img_path = iw_upload_blob(base64_decode($product['LargeImage']), $product['Id']);
  		$full_path = $wp_upload_dir['basedir'] . "/" . $img_path;

		$parent_post_id = $product_id;
		$filetype = wp_check_filetype( basename( $full_path ), null );
		

		$attachment = array(
			'guid'           => $wp_upload_dir['url'] . '/' . basename($full_path), 
			'post_mime_type' => $filetype['type'],
			'post_title'     => preg_replace( '/\.[^.]+$/', '', basename($full_path)),
			'post_content'   => '',
			'post_status'    => 'inherit'
		);


		// Insert the attachment.
		$attach_id = wp_insert_attachment( $attachment, $full_path, $parent_post_id );

		// Generate the metadata for the attachment, and update the database record.
		$attach_data = wp_generate_attachment_metadata( $attach_id, $full_path );
		wp_update_attachment_metadata( $attach_id, $attach_data );

		// UPDATE WOO PRODUCT:
		update_post_meta($product_id, '_thumbnail_id', $attach_id);
		update_post_meta($product_id, '_wp_attached_file', $img_path);
  	}

  	// update category
  	$pcats = array();

  	foreach($product_cat_ids as $cat) 
  		$pcats[] = $prodcats[$cat['ProductCategoryId']];
  	
  	if(count($pcats) > 0) {
  		wp_set_object_terms( $product_id, $pcats, 'product_cat' );
  	}

  	// update meta
  	update_post_meta($product_id, '_sku', $sku);
  	if(empty($sku)) update_post_meta($product_id, 'infusionsoft_product', $pid);
  	update_post_meta($product_id, '_regular_price', $product['ProductPrice']);
  	update_post_meta($product_id, '_visibility', 'visible');
  	update_post_meta($product_id, '_price', $product['ProductPrice']);

  	if($process['step3']['content'] && !empty($process['step3']['content'])) {
  		$contentsrc = $process['step3']['content'];
  		if($contentsrc == 'shortdesc') $content = $product['ShortDescription'];
  		else if($contentsrc == 'desc') $content = $product['Description'];
  		else if($contentsrc == 'topbottom') $content = $product['TopHTML'] . $product['BottomHTML'];
  		else if($contentsrc == 'topdescbottom') $content = $product['TopHTML'] . $product['Description'] . $product['BottomHTML'];

  		if(isset($content)) {
	  		$upd_content = array(
			      'ID'           => $product_id,
			      'post_content' => $content
			  );

	  		wp_update_post( $upd_content );
  		}

  	}

  	if(isset($process['step3']['shortdesc']) && !empty($process['step3']['shortdesc'])) {
  		$shortsrc = $process['step3']['shortdesc'];
  		if($shortsrc == 'shortdesc') $short = $product['ShortDescription'];
  		else if($shortsrc == 'desc') $short = $product['Description'];

  		if(isset($short)) {
	  		$upd_content = array(
			      'ID'           => $product_id,
			      'post_excerpt' => $short
			  );

	  		wp_update_post( $upd_content );
  		}
  	}

  	if($process['step3']['virtual'] == 'yes') {
  		 if($product['Shippable']) 
  		 	update_post_meta($product_id, '_virtual', 'no');
  		 else 
  		 	update_post_meta($product_id, '_virtual', 'yes');
  		 
  	}

  	if($process['step3']['tax'] == 'yes') {
  		 if($product['Taxable']) 
  		 	update_post_meta($product_id, '_tax_status', 'taxable');
  		 else 
  		 	update_post_meta($product_id, '_tax_status', '');				  		

  	}
}

function iw_bgp_export_prods($pr) {
	global $iwpro; 
	wp_cache_flush();
	$iw_bgprocesses = get_option('iw_bgprocesses', array());

	$process = $pr;

	$index = isset($iw_bgprocesses[$pr['refid']]['index']) ? $iw_bgprocesses[$pr['refid']]['index']: 0;
	$fetch = isset($iw_bgprocesses[$pr['refid']]['fetch']) ? $iw_bgprocesses[$pr['refid']]['fetch']: 250;


	if(!isset($iw_bgprocesses[$pr['refid']]['processed'])) $iw_bgprocesses[$pr['refid']]['processed'] = 0;
	
	if($process['step2'] == 'cat') {
		$args= array(
		  'post_type' => 'product',
		  'post_status' => 'publish',
		  'posts_per_page' => -1,
		  'orderby'    => 'post_date',
		  'order'      => 'ASC',
		  'tax_query' => array(
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'term_id',
					'terms'    => $process['step2further'],
				),
			)
		);
	} else if($process['step2'] == 'all') {
		$args= array(
		  'post_type' => 'product',
		  'post_status' => 'publish',
		  'posts_per_page' => -1,
		  'orderby'    => 'post_date',
		  'order'      => 'ASC',
		);
	} else {
		$args= array(
		  'post_type' => 'product',
		  'post_status' => 'publish',
		  'posts_per_page' => -1,
		  'orderby'    => 'post_date',
		  'order'      => 'ASC',
		  'post__in'   => iw_split_entry($process['step2further'])
		);
	}

	$wc_query = new WP_Query($args);
	$ind = -1;
	$proc_today = 0;

	while ($wc_query->have_posts()) : $wc_query->the_post();
		$ind++;
		if($ind < $index) continue;
		$pid = get_the_ID();

		try {
			iw_export_prod($process, $pid);

			$iw_bgprocesses[$pr['refid']]['processed']++;
			$proc_today++;
			$iw_bgprocesses[$pr['refid']]['status'] = 'pending';
			$iw_bgprocesses[$pr['refid']]['index'] = $ind+1;
		} catch(Exception $e) {
			$errmsg = $e->getMessage() . " on line " . $e->getLine() . " of " . $e->getFile();
			$process['status'] = 'failed';
			$process['errormsg'] = $errmsg;
			$iw_bgprocesses[$pr['refid']] = $process;
			iw_bgprocess_log($process, "Processed Woo Product # $pid. Error found: " . $errmsg);	
			return false;
		}
		
		update_option('iw_bgprocesses', $iw_bgprocesses);
		if($proc_today >= $fetch) break;

	endwhile;

	if($iw_bgprocesses[$pr['refid']]['processed'] >= $iw_bgprocesses[$pr['refid']]['total']) {
		$iw_bgprocesses[$pr['refid']]['status'] = 'success';
		update_option('iw_bgprocesses', $iw_bgprocesses);
	} else {
		$iw_bgprocesses[$pr['refid']]['status'] = 'paused';
		update_option('iw_bgprocesses', $iw_bgprocesses);
	}

	$iwpro->ia_woocommerce_update_product_options();
}


function iw_export_prod($process, $pid) {
	global $woocommerce;
  	global $iwpro;
  	global $wpdb;

  	// MAP FIELDS:
	$mapped = array();
	$prod = new WC_Product($pid);

	foreach($process['step3'] as $k => $v) {
		$fld = $v['name'];
		$val = $v['value'];

  		$isfld = (strpos($fld, "export-") !== false && strpos($fld, "-meta") === false);

  		if($isfld) {
  			$ifsfld = str_replace("export-", "", $fld);
  			if(!empty($val)) {
  				$mapped[$ifsfld] = $val;
  			}
  		} 
  	}


	$addprod = array();
	$ifsid = 0;
	$force_ifsid = (int) get_post_meta($pid, 'infusionsoft_product', true);

	foreach($mapped as $k => $val) {
		if($val == 'sku') {
			$addprod[$k] = get_post_meta($pid, '_sku', true);
		} else if($val == 'short') { 
			$addprod[$k] = get_the_excerpt();
		} else if($val == 'content') { 
			$addprod[$k] = get_the_content();
		} else if($val == 'regprice') { 
			$addprod[$k] = get_post_meta($pid, '_price', true);
		} else if($val == 'saleprice') { 
			$addprod[$k] = get_post_meta($pid, '_sale_price', true);
		} else if($val == 'title') { 
			$addprod[$k] = get_the_title();
		} else if($val == 'weight') { 
			$addprod[$k] = get_post_meta($pid, '_weight', true);
		} else if($val == 'virtual') { 
			$v = get_post_meta($pid, '_virtual', true);
			if($v == 'yes') $v = 0;
			else $v = 1;

			$addprod[$k] = $v;
		} else if($val == 'stock') { 
			$addprod[$k] = get_post_meta($pid, '_stock', true);
		} else if($val == 'taxstatus') { 
			$v = get_post_meta($pid, '_tax_status', true);
			if($v == 'taxable') $v = 1;
			else $v = 0;

			$addprod[$k] = $v;
		} else if($val == 'meta') {
			// find mkey:
			foreach($process['step3'] as $vv) {
				if($vv['name'] == "export-$k-meta") {
					$mkey = $vv['value'];
					$addprod[$k] = get_post_meta($pid, $mkey, true);
					break;
				}
			}
		} else if($val == 'productimage') {
			$attach_id = get_post_meta($pid, '_thumbnail_id', true);
			if($attach_id > 0) {
				$attachment = wp_get_attachment_image_src( $attach_id, 'medium' );
				//$base64 = base64_encode(file_get_contents($attachment[0])); 
				$base64 = "BASE64:" . file_get_contents($attachment[0]);
			}
		}
	}

	// search existence of product in Infusionsoft
	if(isset($force_ifsid) && !empty($force_ifsid)) {
		$ifsprod = $iwpro->app->dsLoad('Product',$force_ifsid, array('Id'));

		if(is_array($ifsprod) && count($ifsprod) > 0) {
			$ifsid = $force_ifsid;
			$match = "via Product ID settings";
		}
	}

	if(!isset($ifsid) || empty($ifsid)) {
		if(!empty($addprod['Sku'])) { 
			$ifsprod = $iwpro->app->dsFind('Product',1,0,'Sku', $addprod['Sku'], array('Id'));

			if(is_array($ifsprod) && count($ifsprod) > 0) {
				$iprod = $ifsprod[0];
				$ifsid = $iprod['Id'];
				$match = "via SKU " . $addprod['Sku'];
			}
		}
	}

	$addprod['Status'] = 1;


	if(isset($ifsid) && $ifsid > 0) {
		$iwpro->app->dsUpdate('Product', $ifsid, $addprod);
		iw_bgprocess_log($process, "Processed Woo Product # $pid. Match found $match");	
	} else {
		$ifsid = $iwpro->app->dsAdd('Product', $addprod);
		if(empty($addprod['Sku'])) {
			update_post_meta($pid, 'infusionsoft_product', $ifsid);
		}
		iw_bgprocess_log($process, "Processed Woo Product # $pid. No match found. Created new Infusion Product # $ifsid");	
	}

	if(isset($base64) && !empty($base64)) {
		//echo '<img src="data:image/jpeg;base64,'.$base64.'" />';
		$test = $iwpro->app->dsUpdateWithImage('Product', $ifsid, array('LargeImage' => $base64));
		unset($base64);
	}
}




// Order migrate
function iw_order_migrate($pr) {
	// fetch products
	global $iwpro;
	$iw_bgprocesses = get_option('iw_bgprocesses', array());

	if(!isset($iw_bgprocesses[$pr['refid']])) {
		return false;
	}

	$process = $iw_bgprocesses[$pr['refid']];

	if(!$iwpro->ia_app_connect()) {
		$process['status'] = 'failed';
		$process['errormsg'] = 'Cannot connect to Infusionsoft. Please ensure your integration settings are properly set.';
		$iw_bgprocesses[$pr['refid']] = $process;
		return false;
	}

	iw_bgp_process_ords($process);
}


function iw_bgp_process_ords($pr) {
	global $iwpro;
	$options = $pr;
	$iw_bgprocesses = get_option('iw_bgprocesses', array());

	if(!isset($iw_bgprocesses[$pr['refid']]['processed'])) $iw_bgprocesses[$pr['refid']]['processed'] = 0;
	
	$page = isset($pr['page']) ? $pr['page']: 0;
	$index = isset($pr['index']) ? $pr['index']: 0;
	$fetch = isset($pr['fetch']) ? $pr['fetch']: 250;
	$proc_today = 0;

	// Fetch Orders First
	if($options['step1'] == 'import') {
		if($options['step2'] == 'all') {
			$orders = $iwpro->app->dsFind('Invoice', $fetch, $page, "Id", "%", array(
					'Id',
					'ContactId',
					'JobId',
					'PayStatus',
					'DateCreated',
					'TotalDue',
					'TotalPaid'
				));
			foreach($orders as $o) {
				iw_bgp_import_ord($options, $o);
				$iw_bgprocesses[$pr['refid']]['processed']++;
				$iw_bgprocesses[$pr['refid']]['status'] = 'pending';
				update_option('iw_bgprocesses', $iw_bgprocesses);
			}
			$iw_bgprocesses[$pr['refid']]['page'] = $page + 1;
		} else if($options['step2'] == 'cat') {
			if(in_array("unpaid", $options['step2further']) && in_array("paid", $options['step2further'])) {
				$query = "%";
			} else if(in_array("unpaid", $options['step2further'])) {
				$query = 0;
			} else {
				$query = 1;
			}
			$orders = $iwpro->app->dsFind('Invoice', (int) $fetch, (int) $page, "PayStatus", $query, array(
					'Id',
					'ContactId',
					'JobId',
					'PayStatus',
					'DateCreated',
					'TotalDue',
					'TotalPaid'
				));
			foreach($orders as $o) {
				iw_bgp_import_ord($options, $o);
				$iw_bgprocesses[$pr['refid']]['processed']++;
				$iw_bgprocesses[$pr['refid']]['status'] = 'pending';
				update_option('iw_bgprocesses', $iw_bgprocesses);
			}
			$iw_bgprocesses[$pr['refid']]['page'] = $page + 1;
		} else {
			$orders = array();
			$allowedids =  iw_split_entry($options['step2further']);
			$proc_today = 0;

			// check allowed ids
			$max_id = max($allowedids);
			$min_id = min($allowedids);

			$allfound = false;
			$maxpage = false;
			$ifs_iids = array();
			$pg = 0;

			do {
				$invoice_ids = $iwpro->app->dsFind('Invoice',1000,$pg,'Id','%',array('Id','JobId'));
				$maxpage = count($invoice_ids) < 1000;

				foreach($invoice_ids as $p) {
					if(in_array($p['JobId'], $allowedids)) {
						$ifs_iids[$p['Id']] = $p['JobId'];
					}

					if($p['JobId'] > $max_id) {
						$allfound = true;
						break;
					}
				}
				$pg++;
			} while(!$allfound && !$maxpage);



			$ind = -1;

			foreach($ifs_iids as $id => $v) {
				$ind++;
				if($ind < $index) continue;

				$o = $iwpro->app->dsLoad('Invoice', (int) $id,array(
					'Id',
					'ContactId',
					'JobId',
					'PayStatus',
					'DateCreated',
					'TotalDue',
					'TotalPaid'
				));
				iw_bgp_import_ord($options, $o);
				$iw_bgprocesses[$pr['refid']]['processed']++;
				$iw_bgprocesses[$pr['refid']]['status'] = 'pending';
				$iw_bgprocesses[$pr['refid']]['index'] = $ind+1;
				update_option('iw_bgprocesses', $iw_bgprocesses);
				$proc_today++;

				if($proc_today >= $fetch) break;
			}


		}
	} else if($options['step1'] == 'export') {
		if($page == 0) $page = 1;
		if($process['step2'] == 'all') {
			$args= array(
			  'post_type' => 'shop_order',
			  'post_status' => array_keys( wc_get_order_statuses() ),
			  'posts_per_page' => $fetch,
			  'orderby'    => 'post_date',
			  'order'      => 'ASC',
			  'paged'	   => $page
			);
		} else if($process['step2'] == 'cat') {
			$args= array(
			  'post_type' => 'shop_order',
			  'post_status' => $options['step2further'],
			  'posts_per_page' => $fetch,
			  'orderby'    => 'post_date',
			  'order'      => 'ASC',
			  'paged'	   => $page
			);
		} else {
			$args= array(
			  'post_type' => 'shop_order',
			  'post_status' => array_keys( wc_get_order_statuses() ),
			  'posts_per_page' => $fetch,
			  'orderby'    => 'post_date',
			  'order'      => 'ASC',
			  'paged'	   => $page,
			  'post__in'   => iw_split_entry($options['step2further']),
			);
		}

		$wc_query = new WP_Query($args);
		while ($wc_query->have_posts()) : $wc_query->the_post();
			$oid = get_the_ID();

			iw_bgp_export_ord($options, $oid);

			$iw_bgprocesses[$pr['refid']]['processed']++;	
			$iw_bgprocesses[$pr['refid']]['status'] = 'pending';
			update_option('iw_bgprocesses', $iw_bgprocesses);
		endwhile;

		$iw_bgprocesses[$pr['refid']]['page'] = $page + 1;
	}

	if($iw_bgprocesses[$pr['refid']]['processed'] >= $iw_bgprocesses[$pr['refid']]['total']) {
		$iw_bgprocesses[$pr['refid']]['status'] = 'success';
		update_option('iw_bgprocesses', $iw_bgprocesses);
	} else {
		$iw_bgprocesses[$pr['refid']]['status'] = 'paused';
		update_option('iw_bgprocesses', $iw_bgprocesses);
	}
}

function iw_bgp_import_ord($pr=false, $order) {
	global $iwpro;
	$process = $pr;
	$jobid = isset($order['JobId']) ? (int) $order['JobId'] : (int) $order['Id'];
	if($process == false) {
		$status = isset($iwpro->settings['http_order_status']) ? $iwpro->settings['http_order_status'] : 'wc-processing';
		$status = str_replace("wc-", "", $status);
	} else {
		$status = str_replace("wc-", "", $process['step3']);
	}

	// Check first if order already exists
	$args = array(
	    'meta_query' => array(
	        array(
	            'key' => 'infusionsoft_order_id',
	            'value' => $jobid
	        )
	    ),
	    'post_type' => 'shop_order',
	    'posts_per_page' => 1
	);
	$check = get_posts($args);

	if(count($check) > 0 ) {
		if($process != false) {
			iw_bgprocess_log($process, "Processed Infusion Order # " . $order['Id'] . ". Corresponding Woo Order found (# " . $check[0]->ID . "). ");	
			return false;
		} else {
			echo "Processed Infusion Order # " . $order['Id'] . ". Corresponding Woo Order found (# " . $check[0]->ID . ").\n<br>";
			return false;	
		}
	}

	// Create New Order:
	$new_order = wc_create_order(array('status' => $status));

	// Save Billing and Shipping Information
	$ifscontact = $iwpro->app->loadCon($order['ContactId'], array(
		'FirstName',
		'LastName',
		'Email',
		'StreetAddress1',
		'StreetAddress2',
		'City',
		'State',
		'Country',
		'PostalCode',
		'Address2Street1',
		'Address2Street2',
		'City2',
		'State2',
		'Country2',
		'PostalCode2',
		'Phone1',
		'Company'
		));

	$new_order->set_address(array(
		'email'			=> isset($ifscontact['Email']) ? $ifscontact['Email'] : "",
		'first_name' 	=> isset($ifscontact['FirstName']) ? $ifscontact['FirstName'] : "",
		'last_name' 	=> isset($ifscontact['LastName']) ? $ifscontact['LastName'] : "",
		'address_1' 	=> isset($ifscontact['StreetAddress1']) ? $ifscontact['StreetAddress1'] : "",
		'address_2' 	=> isset($ifscontact['StreetAddress2']) ? $ifscontact['StreetAddress2'] : "",
		'city' 			=> isset($ifscontact['City']) ? $ifscontact['City'] : "",
		'state' 		=> isset($ifscontact['State']) ? $ifscontact['State'] : "",
		'country' 		=> isset($ifscontact['Country']) ? iw_to_country_code($ifscontact['Country']) : "",
		'postcode' 		=> isset($ifscontact['PostalCode']) ? $ifscontact['PostalCode'] : "",
		'phone' 		=> isset($ifscontact['Phone1']) ? $ifscontact['Phone1'] : "",
		'company' 		=> isset($ifscontact['Company']) ? $ifscontact['Company'] : ""
		), 'billing');

	$new_order->set_address(array(
		'first_name' 	=> isset($ifscontact['FirstName']) ? $ifscontact['FirstName'] : "",
		'last_name' 	=> isset($ifscontact['LastName']) ? $ifscontact['LastName'] : "",
		'address_1' 	=> isset($ifscontact['Address2Street1']) ? $ifscontact['Address2Street1'] : "",
		'address_2' 	=> isset($ifscontact['Address2Street2']) ? $ifscontact['Address2Street2'] : "",
		'city' 			=> isset($ifscontact['City2']) ? $ifscontact['City2'] : "",
		'state' 		=> isset($ifscontact['State2']) ? $ifscontact['State2'] : "",
		'country' 		=> isset($ifscontact['Country2']) ? iw_to_country_code($ifscontact['Country2']) : "",
		'postcode' 		=> isset($ifscontact['PostalCode2']) ? $ifscontact['PostalCode2'] : ""
		), 'shipping');

	// Add Order Items:
	$items = $iwpro->app->dsFind('OrderItem',1000,0,'OrderId',$jobid, array(
			'ItemName',
			'ItemType',
			'Qty',
			'ProductId',
			'PPU',
			'CPU'
		));
	foreach($items as $item) {
		if($item['ItemType'] == 1 || $item['ItemType'] == 14) {
			$shipping_fee = new WC_Shipping_Rate;
			$shipping_fee->label = $item['ItemName'];
			$shipping_fee->cost = $item['PPU'];
			$shipping_fee->name = $item['ItemName'];
			$shipping_fee->amount = $item['PPU'];

			$new_order->add_shipping($shipping_fee);
		} else if($item['PPU'] < 0) {
			$new_order->add_coupon( $item['ItemName'], -$item['PPU']);
		} else if($item['ItemType'] == 2) {
			$tax = new WC_Shipping_Rate;
			$tax->label = "TAX: " . $item['ItemName'];
			$tax->cost = $item['PPU'];
			$tax->name = "TAX: " . $item['ItemName'];
			$tax->amount = $item['PPU'];
			$tax->taxable = false;

			$new_order->add_fee( $tax );
		} else if(isset($item['ProductId']) && $item['ProductId'] > 0) {
			$wcprodid = 0;

			// search by product id:
			$args = array(
			    'meta_query' => array(
			        array(
			            'key' => 'infusionsoft_product',
			            'value' => $item['ProductId']
			        )
			    ),
			    'post_type' => 'product',
			    'posts_per_page' => 1
			);
			$wcprod = get_posts($args);

			if(count($wcprod) > 0) $wcprodid = $wcprod[0]->ID;
			else {
				$ifsprod = $iwpro->app->dsLoad('Product',$item['ProductId'],array('Sku'));
				if(isset($ifsprod['Sku'])) {
					$args = array(
					    'meta_query' => array(
					        array(
					            'key' => '_sku',
					            'value' => $ifsprod['Sku']
					        )
					    ),
					    'post_type' => 'product',
					    'posts_per_page' => 1
					);
					$wcprod = get_posts($args);
					if(count($wcprod) > 0) $wcprodid = $wcprod[0]->ID;
				}
			}

			if($wcprodid > 0) {
				$args = array('totals' => array(
						'total' => $item['PPU']*$item['Qty'],
						'subtotal' => $item['PPU']*$item['Qty']
					));
				$wcprod_to_add = new WC_Product($wcprodid);
				$new_order->add_product( $wcprod_to_add, $item['Qty'],$args );
			} else {
				$custom = new WC_Shipping_Rate;
				$custom->label =  $item['ItemName'];
				$custom->cost = $item['PPU']*$item['Qty'];
				$custom->name =  $item['ItemName'];
				$custom->amount = $item['PPU']*$item['Qty'];
				$custom->taxable = false;

				$new_order->add_fee( $custom );
			}
		} else {
			$custom = new WC_Shipping_Rate;
			$custom->label =  $item['ItemName'];
			$custom->cost = $item['PPU']*$item['Qty'];
			$custom->name =  $item['ItemName'];
			$custom->amount = $item['PPU']*$item['Qty'];
			$custom->taxable = false;

			$new_order->add_fee( $custom );
		}
	}
	// update customer_user
	$user = get_user_by( 'email',  $ifscontact['Email']);
	if(isset($user->ID) && !empty($user->ID)) {
		update_post_meta( $new_order->id, '_customer_user', $user->ID );
	}

	// update date
  	wp_update_post( array(
      'ID'        =>  $new_order->id,
      'post_date' => date("Y-m-d H:i:s", strtotime($order['DateCreated']))
  	));

	//Add Order Notes	
	$new_order->calculate_totals();			
	$new_order->add_order_note("Imported From Infusionsoft.");
	update_post_meta($new_order->id, 'infusionsoft_order_id', $jobid);
	update_post_meta($new_order->id, 'infusionsoft_invoice_id', $order['Id']);
	$appname = isset($iwpro->machine_name) ? $iwpro->machine_name : "";
	update_post_meta($new_order->id, 'infusionsoft_view_order', "https://$appname.infusionsoft.com/Job/manageJob.jsp?view=edit&ID=$jobid");
	do_action( 'iw_order_imported', $jobid, $new_order->id );

	if($process != false) {
		iw_bgprocess_log($process, "Processed Infusion Order # " . $order['Id'] . " Created new Woo Order (# " . $new_order->id . "). ");	
	} else {
		do_action( 'iw_order_auto_imported', $jobid, $new_order->id );
		echo "Processed Infusion Order # " . $order['Id'] . " Created new Woo Order (# " . $new_order->id . "). \n<br>";
	}
}

function iw_bgp_export_ord($pr=false, $oid) {
	global $iwpro;
	$process = $pr;
	$order = get_post($oid);

	$wcorder = new WC_Order( $order->ID );

	$ord_id = get_post_meta($order->ID, 'infusionsoft_order_id', true );
	if($ord_id > 0 && $process != false) {
		iw_bgprocess_log($process, "Processed Woo Order # " . $oid . ". Corresponding Infusion Order found (# " . $ord_id . "). ");
		return false;
	}
	
	$email = $wcorder->billing_email;
	$payment_method = $wcorder->payment_method;
	$contact = $iwpro->app->dsFind('Contact',5,0,'Email',$email,array('Id'));
		$contact = $contact[0];	
	
	if ($contact['Id'] != null && $contact['Id'] != 0 && $contact != false){
		$contactId = (int) $contact['Id']; 
	} else {				
		$contactinfo = array(
			'FirstName'			=> stripslashes($wcorder->billing_first_name),
			'LastName'			=> stripslashes($wcorder->billing_last_name),
			'StreetAddress1' 	=> stripslashes($wcorder->billing_address_1),
			'StreetAddress2' 	=> stripslashes($wcorder->billing_address_2),
			'City' 				=> stripslashes($wcorder->billing_city),
			'State' 			=> stripslashes($wcorder->billing_state),
			'Country' 			=> stripslashes(iw_to_country($wcorder->billing_country)),
			'PostalCode' 		=> stripslashes($wcorder->billing_postcode),
			'Address2Street1' 	=> stripslashes($wcorder->shipping_address_1),
			'Address2Street2' 	=> stripslashes($wcorder->shipping_address_2),
			'City2' 			=> stripslashes($wcorder->shipping_city),
			'State2' 			=> stripslashes($wcorder->shipping_state),
			'Country2' 			=> stripslashes(iw_to_country($wcorder->shipping_country)),
			'PostalCode2' 		=> $wcorder->shipping_postcode,
			'Phone1'			=> $wcorder->billing_phone,
			'Company'			=> stripslashes($wcorder->billing_company),
			'Email'				=> $email
		);
		$contactId  = $iwpro->app->addCon($contactinfo);
	}

	$products = $wcorder->get_items(); 

	// CREATE INVOICE
	$orderDate = date('Ymd\TH:i:s', strtotime($order->post_date_gmt));
	$inv_id = (int) $iwpro->app->blankOrder($contactId,"Woocommerce Order # {$order->ID}",$orderDate,0,0);
	update_post_meta($order->ID, 'infusionsoft_invoice_id', $inv_id);
	$calc_totals = 0;
	
	$products = $wcorder->get_items(); 

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

		$qty 	= (int) $product['qty'];
		$price 	= ((float) $product['line_total']) / ((float) $product['qty']);

		$iwpro->app->addOrderItem($inv_id, $pid, 4, $price, $qty, $product['name'], $sdesc);
		$calc_totals += $qty * $price;		
	}

	// TAX LINE
	$tax = (float) $wcorder->get_total_tax();
	if($tax > 0.0) {
		$iwpro->app->addOrderItem($inv_id, 0, 2, $tax, 1, 'Tax','');
		$calc_totals += $tax;
	}
	
	// SHIPPING LINE
	$s_method = (string) $wcorder->get_shipping_method();  
	$s_total  = (float)  $wcorder->get_total_shipping();
	if($s_total > 0.0) {
		$iwpro->app->addOrderItem($inv_id, 0, 1, $s_total, 1, $s_method,$s_method);
		$calc_totals += $s_total;
	}

	//coupon line
	$discount = (float) ($calc_totals - $wcorder->get_total());
	if ( round($discount,2) > 0.00  ) {
	  $iwpro->app->addOrderItem($inv_id, 0, 7, -$discount, 1, 'Discount', 'Woocommerce Shop Coupon Code');
	  $calc_totals -= $discount;		  
	} 

	$method = $wcorder->payment_method_title;
	$totals = (float) $iwpro->app->amtOwed($inv_id);
	$status = $wcorder->get_status();

	if($status == 'processing' || $status == 'completed') {
		$iwpro->app->manualPmt($inv_id, $totals, $orderDate, $method, "Woocommerce Checkout",false);
	}

	$jobid  = $iwpro->app->dsLoad("Invoice",$inv_id, array("JobId"));
	$jobid  = (int) $jobid['JobId'];
	$modify_order = array('DueDate' => date('Y-m-d', strtotime($order->post_date_gmt)));

	if(!empty($wcorder->shipping_first_name) && $wcorder->shipping_first_name != $wcorder->billing_first_name)
		$modify_order['ShipFirstName'] = $wcorder->shipping_first_name;

	if(!empty($wcorder->shipping_last_name) && $wcorder->shipping_last_name != $wcorder->billing_last_name)
		$modify_order['ShipLastName'] = $wcorder->shipping_last_name;

	if(!empty($wcorder->shipping_company) && $wcorder->shipping_company != $wcorder->billing_company)
		$modify_order['ShipCompany'] = $wcorder->shipping_company;

	$iwpro->app->dsUpdate('Job',$jobid, $modify_order);
	update_post_meta($order->ID, 'infusionsoft_order_id', $jobid);

	if($process != false) {
		$as = (int) $process['step3'];
		if($as > 0) $iwpro->app->runAS($contactId, $as);
		$wcorder->add_order_note('Exported Order to Infusionsoft via Order Export Wizard.');

		iw_bgprocess_log($process, "Processed Woo Order # " . $order->ID . " Created new Infusion Order (# " . $jobid . "). ");
	}
	do_action( 'iw_order_exported', $wcorder->id, $jobid );
}

function iw_create_wcorder() {
	global $iwpro;
	if(!$iwpro->ia_app_connect()) {
		echo "Error: Cannot connect to Infusionsoft.";
		exit();
	}

	$key = substr($iwpro->apikey, 0,6);
	if(!isset($_GET['key']) || $_GET['key'] != $key) {
		echo 'Invalid key';
		exit();
	}

	$arr = $_POST;

	$email_source = array('Email','inf_field_Email','email','Contact0Email','e-mail','E-mail','emailaddress','EmailAddress');
	$cid_source	  = array('Id','id','contactId','ContactId','cid','CID','inf_field_Id');
	$oid_source = array('orderId','order','orderid');

	foreach($email_source as $param) {
		if(isset($arr[$param]) && !empty($arr[$param])) {
			$contact_email = urldecode($arr[$param]);
			break;
		}
	}

	foreach($cid_source as $param) {
		if(isset($arr[$param]) && !empty($arr[$param])) {
			$contact_id = (int) $arr[$param];
			break;
		}
	}

	foreach($oid_source as $param) {
		if(isset($arr[$param]) && !empty($arr[$param])) {
			$order_id = (int) $arr[$param];
			break;
		}
	}

	if(!isset($order_id) || empty($order_id)) {
		// pull from cid
		if((!isset($contact_id) || empty($contact_id)) && !empty($contact_email)) {
			$contacts = $iwpro->app->dsQuery('Contact', 1, 0, array('Email' => $contact_email), array('Id'));
			if(isset($contacts[0]['Id'])) $contact_id = $contacts[0]['Id'];
		}

		if(empty($contact_id)) {
			echo "Error: Cannot Locate Contact";
			exit();
		}

		$ids = $iwpro->app->dsQueryOrderBy('Job', 10, 0, array('ContactId' => $contact_id), array('Id'), 'Id', false);
		$order_ids = array();
		foreach($ids as $id) {
			$order_ids[] = $id['Id'];
		}
	} else {
		$order_ids = array($order_id);
	}

	foreach($order_ids as $order_id) {
		$order = array('JobId' => $order_id,'Id' => $order_id);

		if(isset($contact_id)) {
			$order['ContactId'] = $contact_id;
		} else {
			$job = $iwpro->app->dsLoad('Job',$order_id,array('ContactId'));
			$order['ContactId'] = $job['ContactId'];
		}

		iw_bgp_import_ord($pr=false, $order);
	}

	exit();
}
