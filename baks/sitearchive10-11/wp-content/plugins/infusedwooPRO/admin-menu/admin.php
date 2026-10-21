<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


include_once INFUSEDWOO_PRO_DIR . "admin-menu/admin_ajax.php";

add_action('admin_menu', 'infusedwoo_add_menu2');

function infusedwoo_add_menu2() {
	add_menu_page('InfusedWoo', __('InfusedWoo','woocommerce'), 'manage_woocommerce', 'infusedwoo-menu-2', 'infusedwoo_menu2', 'dashicons-cloud', '2.17');
}

function infusedwoo_sub_menu_link($submenu,$label) {
	$uri = admin_url("admin.php?page=infusedwoo-menu-2&submenu=$submenu");

	if(isset($_GET['submenu']) && $submenu == $_GET['submenu']) $class = "iw-submenu active";
	else $class = "iw-submenu";

	echo '<a href="'.$uri.'" class="'.$class.'">'.$label.'</a>';
}

function infusedwoo_menu2() {
	global $iwpro;
	global $woocommerce;
	if(version_compare( WOOCOMMERCE_VERSION, '2.1.0', '>=' )) 
		$wcs = 'wc-settings';
	else
		$wcs = 'woocommerce_settings';

	// add css / js
	wp_enqueue_script( 'ia_searchable', (INFUSEDWOO_PRO_URL . 'assets/chosen.jquery.min.js'), array('jquery') ); 
	wp_enqueue_style( 'ia_searchable_css', (INFUSEDWOO_PRO_URL . 'assets/chosen.css') ); 
	wp_enqueue_style( "infusedwoo-admin-css", INFUSEDWOO_PRO_URL . "admin-menu/assets/admin.css", array());
	wp_enqueue_script( "infusedwoo-admin-js", INFUSEDWOO_PRO_URL . "admin-menu/assets/admin.js", array('jquery','jquery-ui-sortable','ia_searchable','jquery-ui-autocomplete'));
	wp_enqueue_style( "infusedwoo-admin-fonts", "//fonts.googleapis.com/css?family=Lato|Arvo", array());
	wp_enqueue_style( 'iwpro-fontawesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css');
	wp_register_style( 'jquery-ui-styles','//ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/themes/smoothness/jquery-ui.css' );
	?>
		<div class="infusedwoo-admin-wrapper">
			<div class="infusedwoo-admin-top-bar">
				<a href="<?php echo admin_url("admin.php?page=infusedwoo-menu-2"); ?>" class="iw-submenu"><img class="infusedwoo-logo" src="<?php echo INFUSEDWOO_PRO_URL . "images/infusedwoo.png" ?>" /></a>

				<span style="float: right; margin-right: 20px; margin-top: 10px; font-size: 12pt; color:white;">Version: <?php echo INFUSEDWOO_PRO_VER; ?>
					<?php 
						$plugin_current_version = INFUSEDWOO_PRO_VER;  
						$remote_ver = get_transient('infusedwoo_remote_ver');
						$lic_validate = get_transient('infusedwoo_lic_validate');

						if(isset($remote_ver) && version_compare( $remote_ver, INFUSEDWOO_PRO_VER, '>=' ) && !empty($lic_validate)) {
							$new_upd = true;
							 	if($lic_validate == "invalid") {
						            infusedwoo_sub_menu_link("update",'<span class="alert">Invalid License Key! Click to Update.</span>');
						        } else if($lic_validate == "exceed") {
						        	infusedwoo_sub_menu_link("update",'<span class="alert">License Key Limit Reached!</span>');    
						        }  else if($lic_validate == "empty") {
						            infusedwoo_sub_menu_link("update",'<span class="alert">No License Key! Click to Update</span>');
						        }  else if($lic_validate == "valid") {
						            infusedwoo_sub_menu_link("update",'<span class="alert">New Update is Available!</span>');
						        } 
						} else {
							$new_upd = false;
						}
					?>
					<span class="loader"><img src="<?php echo INFUSEDWOO_PRO_URL . "admin-menu/images/ajax-loader.gif" ?>" /></span>
				</span>
			</div>

			<div class="infusedwoo-admin-left-menu">
				<div class="infusedwoo-admin-menu">
					<ul><li><a href="#" class="menu-list-head">Getting Started</a>
							<ul>
								<li><?php infusedwoo_sub_menu_link("quick_install","Guided Setup") ?></li>
								<li><?php infusedwoo_sub_menu_link("update","Updating InfusedWoo") ?></li>
							</ul>
						</li></ul>

					<ul><li><a href="#" class="menu-list-head">Import / Export</a>
							<ul>
								<li><?php infusedwoo_sub_menu_link("product_import","Products") ?></li>
								<li><?php infusedwoo_sub_menu_link("order_import","Orders") ?></li>
							</ul>
						</li></ul>

					<ul><li><a href="#" class="menu-list-head">Receiving Payments</a>
							<ul>
								<li><?php infusedwoo_sub_menu_link("is_gateway","Infusionsoft Payment Gateway") ?></li>
								<li><?php infusedwoo_sub_menu_link("other_gateways","Integrating Other Payment Gateways") ?></li>
							</ul>
						</li></ul>

					<ul><li><a href="#" class="menu-list-head">Automation</a>
							<ul>
								<li><?php infusedwoo_sub_menu_link("action_sets","Using Action Sets") ?></li>
								<li><?php infusedwoo_sub_menu_link("campaign_builder","Using Campaign Builder") ?></li>
								<li><?php infusedwoo_sub_menu_link("campaign_goals","Available Campaign API Goals") ?></li>
								<li><?php infusedwoo_sub_menu_link("cart_abandon","Cart Abandon Campaign") ?></li>
							</ul>
						</li></ul>

					<ul><li><a href="#" class="menu-list-head">Subscriptions</a>
							<ul>
								<li><?php infusedwoo_sub_menu_link("iw_subs","Via InfusedWoo Subscription Module") ?></li>
								<li><?php infusedwoo_sub_menu_link("woo_subs","Via Woocommerce Subscriptions") ?></li>
							</ul>
						</li></ul>

					<ul><li><a href="#" class="menu-list-head">More Integration Options</a>
							<ul>
								<li><?php infusedwoo_sub_menu_link("ty_page_control","Thank You Page Control") ?></li>
								<li><?php infusedwoo_sub_menu_link("custom_fields","Checkout Custom Fields") ?></li>
								<li><?php infusedwoo_sub_menu_link("leadsources","Leadsources") ?></li>
								<li><?php infusedwoo_sub_menu_link("ref_partners","Referral Partners") ?></li>
								<li><?php infusedwoo_sub_menu_link("auto_order_import","Order Auto Import") ?></li>
								<li><?php infusedwoo_sub_menu_link("user_reg","User Registration") ?></li>
								<li><?php infusedwoo_sub_menu_link("one-click","One-Click Upsells") ?></li>
							</ul>
						</li></ul>

					<ul><li><a href="#" class="menu-list-head">Others</a>
							<ul>
								<?php
									if(version_compare( WOOCOMMERCE_VERSION, '2.1.0', '>=' )) 
										$wcs = 'wc-settings';
									else
										$wcs = 'woocommerce_settings';
								?>
								<li><a href="<?php echo admin_url('admin.php?page='.$wcs.'&tab=integration&section=infusionsoft'); ?>" class="" target="_blank">Integration Settings</a></li>
								<li><a href="http://infusedaddons.com/support" class="" target="_blank">Support</a></li>
							</ul>
						</li></ul>
				</div>
			</div>

			<div class="infusedwoo-admin-content">
				<?php if(isset($_GET['submenu'])) {
						if((isset($iwpro->enabled) && $iwpro->enabled == "yes" && $iwpro->ia_app_connect()) || in_array($_GET['submenu'], array('quick_install','gen_settings','support','update')))
							include INFUSEDWOO_PRO_DIR . "admin-menu/{$_GET['submenu']}.php";
						else {
							?>
							<br><br><br>
							<center>
							<img src="<?php echo INFUSEDWOO_PRO_URL . "images/broken_link.jpg" ?>" style="opacity: 0.7; width: 80%;"/>
							<br><br>
							<p style="font-size: 14pt; line-height: 16pt">
								Connection to Infusionsoft is currently disabled.<br><br>
								To access this feature, please <a href="<?php echo admin_url("admin.php?page=infusedwoo-menu-2&submenu=quick_install"); ?>">
								enable Infusionsoft<br> Integration first.
								</a>

							</p>
							
							<?php
						} 

					} else {
					?>
						<center style="font-size: 13pt; line-height: 18pt;">
							<br><br>
							<div style="background-color: #293D67; padding: 10px 20px; border-radius: 10px; width: auto; display: inline-block; ">
							<img src="<?php echo INFUSEDWOO_PRO_URL . "images/infusedwoo.png" ?>" />
							</div>
							<br><br><br>
							You are currently using InfusedWoo <?php echo INFUSEDWOO_PRO_VER; ?>
							<br><br>
							Welcome to the InfusedWoo admin panel. <br>Please use the menu on the left side to access all the features of InfusedWoo. 

						</center>

					<?php } ?>
			</div>
		</div>
	<?php
}