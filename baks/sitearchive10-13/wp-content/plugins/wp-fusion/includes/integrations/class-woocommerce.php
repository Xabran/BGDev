<?php

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

if(class_exists( 'WooCommerce' )) {

	class WPF_Woocommerce extends WPF_Integrations_Base {

		/**
		 * Get things started
		 *
		 * @access public
		 * @return void
		 */

		public function init() {

			add_filter( 'wpf_user_register', array( $this, 'filter_create_user' ) );
			add_filter( 'wpf_meta_box_post_types', array( $this, 'unset_wpf_meta_boxes' ) );
			add_filter( 'wpf_meta_fields', array( $this, 'prepare_meta_fields' ), 10 );
			add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 15, 2 );
			add_filter( 'wpf_configure_sections', array( $this, 'configure_sections' ), 10, 2 );

			// Account info update
			add_filter( 'woocommerce_save_account_details', array( $this, 'save_account_details' ) );

			// Order status changes
			add_action( 'woocommerce_payment_complete', array( $this, 'woocommerce_apply_tags_checkout' ), 10, 1 );
			add_action( 'woocommerce_order_status_processing', array( $this, 'woocommerce_apply_tags_checkout' ), 10, 1);
			add_action( 'woocommerce_order_status_completed', array( $this, 'woocommerce_apply_tags_checkout' ), 10, 1);
			add_action( 'woocommerce_order_refunded', array( $this, 'woocommerce_order_refunded' ), 10, 2 );

			// Remove add to cart buttons on Shop pages for restricted products
			add_action( 'woocommerce_loop_add_to_cart_link', array( $this, 'add_to_cart_buttons' ), 10, 2 );

			// Remove restricted products from shop loop
			add_action( 'the_posts', array( $this, 'exclude_restricted_products' ), 10, 2 );

			// Add meta boxes to Woo product editor
			add_action( 'woocommerce_product_write_panels', array( $this, 'woocommerce_write_panels' ) );
			add_action( 'woocommerce_product_write_panel_tabs', array( $this, 'woocommerce_write_panel_tabs' ) );
			add_action( 'wpf_woocommerce_panel', array( $this, 'panel_content' ), 5 );

			// Variations fields
			add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'variable_fields' ), 10, 3 );
			add_action( 'woocommerce_save_product_variation', array( $this, 'save_variable_fields' ), 10, 2 );

			// Save changes to Woo meta box data
			add_action( 'save_post', array( $this, 'save_meta_box_data' ) );

			// Export functions
			add_filter( 'wpf_export_options', array( $this, 'export_options' ) );
			add_action( 'wpf_batch_woocommerce_init', array( $this, 'batch_init' ) );
			add_action( 'wpf_batch_woocommerce', array( $this, 'batch_step' ) );

			// Coupons
			add_filter( 'woocommerce_coupon_data_tabs', array( $this, 'coupon_tabs' ) );
			add_action( 'woocommerce_coupon_data_panels', array( $this, 'coupon_data_panel' ) );
			add_action( 'save_post', array( $this, 'save_meta_box_data_coupon' ) );

		}

		/**
		 * Formats field data updated via the Update Account form
		 *
		 * @access public
		 * @return array User Meta
		 */

		public function save_account_details( $user_id ) {

			$user_meta = $_POST;

			if(isset($user_meta['account_first_name']))
				$user_meta['first_name'] = $user_meta['account_first_name'];

			if(isset($user_meta['account_email']))
				$user_meta['user_email'] = $user_meta['account_email'];

			if(isset($user_meta['account_last_name']))
				$user_meta['last_name'] = $user_meta['account_last_name'];

			if(isset($user_meta['password_1']) && !empty($user_meta['password_1']))
				$user_meta['user_pass'] = $user_meta['password_1'];

			wp_fusion()->user->push_user_meta( $user_id, $user_meta );

		}

		/**
		 * Adds Integrations tab if not already present
		 *
		 * @access public
		 * @return void
		 */

		public function configure_sections($page, $options) {

			if(!isset($page['sections']['integrations']))
				$page['sections'] = wp_fusion()->settings->insert_setting_after('contact-fields', $page['sections'], array('integrations' => __('Integrations', 'wp-fusion' )));

			return $page;

		}

		/**
		 * Registers additional Woocommerce settings
		 *
		 * @access  public
		 * @return  array Settings
		 */

		public function register_settings( $settings, $options ) {

			$settings['woo_header'] = array(
					'title'   => __('WooCommerce Integration', 'wp-fusion' ),
					'std'     => 0,
					'type'    => 'heading',
					'section' => 'integrations'
			);

			$settings['woo_hide'] = array(
					'title'   => __('Hide restricted products', 'wp-fusion' ),
					'desc'    => __('If a user can\'t access a product, hide it from the Shop page', 'wp-fusion' ),
					'std'     => 0,
					'type'    => 'checkbox',
					'section' => 'integrations'
			);

			$settings['woo_async'] = array(
					'title'   => __('Asynchronous Checkout', 'wp-fusion' ),
					'desc'    => __('Runs WP Fusion post-checkout actions asynchronously to speed up load times.', 'wp-fusion' ),
					'std'     => 0,
					'type'    => 'checkbox',
					'section' => 'integrations'
			);

			return $settings;

		}

		/**
		 * Tries to detect a leadsource for the order, if available, and stores a cookie for when the purchase is completed
		 *
		 * @access  public
		 * @return  void
		 */

		function set_lead_source() {
			
			$siteurl = $_SERVER['HTTP_HOST'];
			$siteurl = str_replace("http://","",$siteurl);
			$siteurl = str_replace("https://","",$siteurl);
			$siteurl = str_replace("www.","",$siteurl);

			if(!empty($_GET['leadsource'])) {
				setcookie("ia_leadsource", $_GET['leadsource'], (time()+31*24*3600), "/", $siteurl, 0); 
				$_SESSION['leadsource'] = $_GET['leadsource'];
			} else if(!empty($_GET['utm_source'])) {
				setcookie("ia_leadsource", $_GET['utm_source'], (time()+31*24*3600), "/", $siteurl, 0); 
				$_SESSION['leadsource'] = $_COOKIE['utm_source'];				
			} else if(!empty($_GET['utm_campaign'])) {
				setcookie("ia_leadsource", $_GET['utm_campaign'], (time()+31*24*3600), "/", $siteurl, 0); 
				$_SESSION['leadsource'] = $_COOKIE['utm_campaign'];				
			} else if(!empty($_COOKIE['ia_leadsource'])) {
				$_SESSION['leadsource'] = $_COOKIE['ia_leadsource'];				
			}
			
			if(!empty($_GET['affiliate'])) {
				setcookie("is_aff", $_GET['affiliate'], (time()+365*24*3600), "/", $siteurl, 0); 
			}

			if(!empty($_GET['aff'])) {
				setcookie("is_affcode", $_GET['aff'], (time()+365*24*3600), "/", $siteurl, 0); 
			}
		}

		/**
		 * Sets field labels and types for WooCommerce custom fields
		 *
		 * @access  public
		 * @return  array Meta fields
		 */

		public function prepare_meta_fields( $meta_fields ) {

			$shipping_fields = WC()->countries->get_address_fields( '', 'shipping_' );
			$billing_fields = WC()->countries->get_address_fields( '', 'billing_' );

			$woocommerce_fields = array_merge($shipping_fields, $billing_fields);

			foreach( $woocommerce_fields as $key => $data) {

				if(!isset($meta_fields[$key]))
					$meta_fields[$key] = array();

				if(isset($data['label'])) {
					$woo_field_data = array( 'label' => $data['label'] );
				} else {
					$woo_field_data = array( 'label' => '' );
				}

				if(isset($data['type'])) {
					$woo_field_data['type'] = $data['type'];
				} else {
					$woo_field_data['type'] = 'text';
				}

				$meta_fields[$key] = array_merge( $meta_fields[$key], $woo_field_data );

			}

			return $meta_fields;

		}


		/**
		 * Removes standard WPF meta boxes from Woo admin pages
		 *
		 * @access  public
		 * @return  array Post Types
		 */

		public function unset_wpf_meta_boxes( $post_types ) {

			unset($post_types['shop_order']);
			unset($post_types['shop_coupon']);
			
			return $post_types;

		}


		/**
		 * Removes restricted products from shop archives
		 *
		 * @access  public
		 * @return  string Link
		 */

		public function exclude_restricted_products( $posts, $query ) {

			if(wp_fusion()->settings->get('woo_hide') != true || $query->query_vars['post_type'] != 'product' || is_admin())
				return $posts;

			foreach($posts as $index => $product) {

				if(!wp_fusion()->access->user_can_access($product->ID))
					unset($posts[$index]);

			}

			return array_values($posts);

		}


		/**
		 * Removes Add to Cart buttons in Store page for restricted products
		 *
		 * @access  public
		 * @return  string Link
		 */

		public function add_to_cart_buttons( $link, $product ) {

			if(!wp_fusion()->access->user_can_access($product->id))
				$link = '';

			return $link;

		}

		/**
		 * Adapt WooCommerce checkout fields to IS fields for creating customers at checkout
		 *
		 * @access public
		 * @return array Post Data
		 */

		public function filter_create_user($post_data) {

			if(isset($post_data['account_password'])) {
				$post_data['user_pass'] = $post_data['account_password'];
				unset($post_data['account_password']);
			}

			if(isset($post_data['billing_email'])) {
				$post_data['user_email'] = $post_data['billing_email'];
			}

			if(isset($post_data['account_username'])) {
				$post_data['user_login'] = $post_data['account_username'];
				unset($post_data['account_username']);
			} else {
				// Get the username if autogenerated by Woo
				$user = get_user_by( 'email', $post_data['user_email'] );
				$post_data['user_login'] = $user->user_login;
			}

			if(isset($post_data['billing_first_name'])) {
				$post_data['first_name'] = $post_data['billing_first_name'];
			}

			if(isset($post_data['billing_last_name'])) {
				$post_data['last_name'] = $post_data['billing_last_name'];
			}

			return $post_data;

		}

		/**
		 * Gets customer details from the WooCommerce order when customer isn't a registered user
		 *
		 * @access public
		 * @return array Contact Data
		 */

		public function woocommerce_get_customer_data($order) {

			$order_data = array();

			foreach(wp_fusion()->settings->get('contact_fields') as $field_id => $field_data) {

				$field = $order->__get($field_id);

				if($field_id == 'user_email') {

					// Billing email is technically separate from user_email but we'll simplify that
					$order_data[$field_id] = $order->__get('billing_email');

				} elseif($field_id == 'first_name') {

					$order_data[$field_id] = $order->__get('billing_first_name');

				} elseif($field_id == 'last_name') {

					$order_data[$field_id] = $order->__get('billing_last_name');

				} else if($field_data['active'] == true && !empty($field)) {

					// If the field is enabled for sync and the field exists in the order
					$order_data[$field_id] = $order->__get($field_id);

				}

			}

			// Track lead sources on contacts
			if(isset($_SESSION['leadsource']))
				$order_data['Leadsource'] = $_SESSION['leadsource'];

			return $order_data;

		}

		/*
		 * Apply tags when a product is purchased in WooCommerce
		 *
		 * @access public
		 * @return void
		 */

		public function woocommerce_apply_tags_checkout( $order_id, $doing_async = false, $force = false )  {

			// Defer until next page if async checkout is enabled
			if(!is_admin() && wp_fusion()->settings->get('woo_async') == true && $doing_async == false) {
				wp_fusion()->ajax->async_add($order_id, array('class' => 'WPF_Woocommerce', 'function' => 'woocommerce_apply_tags_checkout', 'args' => array( $order_id )));
				return;
			}

			// Prevents the API calls being sent multiple times for the same order
			$wpf_complete = get_post_meta($order_id, 'wpf_complete', true );

			if(!empty($wpf_complete) && $force == false)
				return true;

			$order = new WC_Order( $order_id );

			// First see if the user already exists locally
			$user = get_user_by( 'email', $order->billing_email );

			if($user != false) {

				// If user is found, lookup the contact ID
				$contact_id = wp_fusion()->user->get_contact_id( $user->ID );

			} else {

				// Try seeing if an existing contact ID exists
				$contact_id = wp_fusion()->crm->get_contact_id( $order->billing_email );

			}

			// Format order data
			$order_data = $this->woocommerce_get_customer_data( $order );


			// If contact doesn't exist in CRM
			if( $contact_id == false ) {

				$contact_id = wp_fusion()->crm->add_contact( $order_data );
				$order->add_order_note( wp_fusion()->crm->name . ' contact ID ' . $contact_id . ' created via guest-checkout.');	

			} else {

				wp_fusion()->crm->update_contact( $contact_id, $order_data );

			}

			// Possibly apply tags for any configured coupons
			$coupons = $order->get_used_coupons();

			if(!empty($coupons)) {

				$coupon_tags = array();

				foreach($coupons as $coupon_code) {

					$coupon = new WC_Coupon( $coupon_code );

					$settings = get_post_meta( $coupon->id, 'wpf-settings-woo', true );

					if(!empty($settings) && !empty($settings['apply_tags']))
						$coupon_tags = array_merge($coupon_tags, $settings['apply_tags']);
				}

				if(!empty($coupon_tags)) {

					if( $user == false) {
						// Guest checkout
						$result = wp_fusion()->crm->apply_tags( $coupon_tags, $contact_id );
					} else {
						// Registered users
						$result = wp_fusion()->user->apply_tags( $coupon_tags, $user->ID );
					}

				}

			}


			$wpf_woocommerce_products = get_option('wpf_woocommerce_products', true);

			if(empty($wpf_woocommerce_products)) {
				do_action('wpf_woocommerce_payment_complete', $order_id, $contact_id);
				return;
			}

			$products = $order->get_items();
			$apply_tags = array();

			foreach( $products as $product) {

				if ( isset($wpf_woocommerce_products[$product['product_id']]) && !empty($wpf_woocommerce_products[$product['product_id']]['apply_tags']) ) {

					// Apply tags for products
					$apply_tags = array_merge( $apply_tags, $wpf_woocommerce_products[$product['product_id']]['apply_tags'] );

				} elseif ( isset($wpf_woocommerce_products[$product['variation_id']]) && !empty($wpf_woocommerce_products[$product['variation_id']]['apply_tags']) ) {

					// Apply the tags for variations
					$apply_tags = array_merge( $apply_tags, $wpf_woocommerce_products[$product['variation_id']]['apply_tags'] );

				}

			}

			// Apply the tags
			if(!empty($apply_tags)) {

				if( $user == false) {
					// Guest checkout
					$result = wp_fusion()->crm->apply_tags( $apply_tags, $contact_id );
				} else {
					// Registered users
					$result = wp_fusion()->user->apply_tags( $apply_tags, $user->ID );
				}
				
				if($result == false)
					$order->add_order_note("Error applying tags for order ID: " . $order_id );

			}

			// Denotes that the WPF actions have already run for this order
			update_post_meta( $order_id, 'wpf_complete', true );

			// Run payment complete action
			do_action('wpf_woocommerce_payment_complete', $order_id, $contact_id);

		}

		/**
		 * Triggered when an order is refunded
		 *
		 * @access public
		 * @return void
		 */

		public function woocommerce_order_refunded( $order_id, $id ) {

			$order = new WC_Order( $order_id );
			$items = $order->get_items();

			foreach ($items as $item) {

				if ( isset($wpf_woocommerce_products[$item['product_id']]) )
					wp_fusion()->user->remove_tags($wpf_woocommerce_products[$item['product_id']]['apply_tags'], $order->user_id);

				if ( isset($wpf_woocommerce_products[$item['variation_id']]) )
					wp_fusion()->user->remove_tags($wpf_woocommerce_products[$item['variation_id']]['apply_tags'], $order->user_id);
				
				if( isset($wpf_woocommerce_products[$item['product_id']['apply_tags_refunded']]) )
					wp_fusion()->user->apply_tags($wpf_woocommerce_products[$item['product_id']]['apply_tags_refunded'], $order->user_id);

			}
		}

		/**
		 * Outputs custom panels to WooCommerce product config screen
		 *
		 * @access public
		 * @return mixed
		 */

		public function woocommerce_write_panels() {

			// Add an nonce field so we can check for it later.
			wp_nonce_field( 'wpf_meta_box_woo', 'wpf_meta_box_woo_nonce' );

			echo '<div id="wp_fusion_tab" class="panel woocommerce_options_panel">';
				echo '<div class="options_group wpf-product" style="margin-bottom: 20px;">';

					// Writes the panel content
					do_action('wpf_woocommerce_panel');

				echo '</div>';
			echo '</div>';

		}

		/**
		 * Displays "apply tags" field on the WPF product configuration panel
		 *
		 * @access public
		 * @return mixed
		 */

		public function panel_content() {

			global $post;
			$settings = array('apply_tags' => array(), 'apply_tags_refunded' => array());

			if(get_post_meta( $post->ID, 'wpf-settings-woo', true )) 
				$settings = array_merge($settings, get_post_meta( $post->ID, 'wpf-settings-woo', true ));

			echo '<p class="form-field"><label for="wpf-apply-tags-woo">Apply tags when purchased</label>';
			wpf_render_tag_multiselect($settings['apply_tags'], 'wpf-settings-woo', 'apply_tags');

			echo '<small class="show_if_variable">Tags for product variations can be configured within the Variations tab.</small>';

			echo '</p>';

			echo '<p class="form-field"><label for="wpf-apply-tags-woo">Apply tags when<br />refunded</label>';

			wpf_render_tag_multiselect($settings['apply_tags_refunded'], 'wpf-settings-woo', 'apply_tags_refunded');

			echo '</p>';

		}


		/**
		 * Adds tabs to left side of Woo product editor panel
		 *
		 * @access public
		 * @return mixed
		 */

		public function woocommerce_write_panel_tabs() {

			echo '<li class="custom_tab linked_product_options"><a href="#wp_fusion_tab">WP Fusion</a></li>';

		}


		/**
		 * Adds tag multiselect to variation fields
		 *
		 * @access public
		 * @return mixed
		 */

		public function variable_fields($loop, $variation_data, $variation) {

			echo '<div><p class="form-row form-row-full"><label for="wpf-apply-tags-woo">Apply these tags in ' . wp_fusion()->crm->name . ' when purchased:</label>';

			global $post;

			$settings = array('apply_tags_variation' => array());

			if(get_post_meta( $post->ID, 'wpf-settings-woo', true )) 
				$settings = array_merge($settings, get_post_meta( $post->ID, 'wpf-settings-woo', true ));

			// Migrate old data format to WC 2.4+ format
			if(!empty($settings['apply_tags_variation'][$loop]) && !isset($settings['apply_tags_variation'][$variation->ID])) {
				$settings['apply_tags_variation'][$variation->ID] = $settings['apply_tags_variation'][$loop];
			} elseif(empty($settings['apply_tags_variation'][$variation->ID])) {
				$settings['apply_tags_variation'][$variation->ID] = array();
			}

			wpf_render_tag_multiselect($settings['apply_tags_variation'], 'wpf-settings-woo', 'apply_tags_variation', $variation->ID );

			echo '</p></div>';

		}


		/**
		 * Saves variable field data to product
		 *
		 * @access public
		 * @return mixed
		 */


		public function save_variable_fields( $variation_id, $i ) {

			if(isset($_POST['wpf-settings-woo'])) {
				$data = $_POST['wpf-settings-woo'];
			} else {
				return;
			}

			$post_id = $_POST['product_id'];

			// Keep an array of all products that trigger tags on purchase
			$wpf_woocommerce_products = get_option('wpf_woocommerce_products');

			if(empty($wpf_woocommerce_products))
				$wpf_woocommerce_products = array();

			// Get current post meta
			$post_meta = get_post_meta( $post_id, 'wpf-settings-woo', true );

			if(empty($post_meta))
				$post_meta = array('apply_tags_variation' => array());

			if(isset($data['apply_tags_variation'])) {

				$post_meta['apply_tags_variation'][$variation_id] = $data['apply_tags_variation'][$variation_id];
				$wpf_woocommerce_products[$variation_id] = array( 'apply_tags' => $data['apply_tags_variation'][$variation_id] );

			}

			// Keep a record of all products with tag rules
			update_option('wpf_woocommerce_products', $wpf_woocommerce_products);

			// Update the meta field in the database.
			update_post_meta( $post_id, 'wpf-settings-woo', $post_meta );

		}


		/**
		 * Saves WPF configuration to product
		 *
		 * @access public
		 * @return mixed
		 */

		public function save_meta_box_data($post_id) {

			/*
			 * We need to verify this came from our screen and with proper authorization,
			 * because the save_post action can be triggered at other times.
			 */

			// Check if our nonce is set.
			if ( ! isset( $_POST['wpf_meta_box_woo_nonce'] ) )
				return;

			// Verify that the nonce is valid.
			if ( ! wp_verify_nonce( $_POST['wpf_meta_box_woo_nonce'], 'wpf_meta_box_woo' ) )
				return;

			// If this is an autosave, our form has not been submitted, so we don't want to do anything.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
				return;


			// Don't update on revisions
			if($_POST['post_type'] == 'revision')
				return;
			

			if(isset($_POST['wpf-settings-woo'])) {
				$data = $_POST['wpf-settings-woo'];

			} else {
				$data = array();
			}

			// Keep an array of all products that trigger tags on purchase
			$wpf_woocommerce_products = get_option('wpf_woocommerce_products');

			if(!$wpf_woocommerce_products)
				$wpf_woocommerce_products = array();

			if(isset($data['apply_tags'])) {

				if(!isset($data['remove_tags']))
					$data['remove_tags'] = false;

				$wpf_woocommerce_products[$post_id] = array(
					'apply_tags' 	=> $data['apply_tags'],
					'remove_tags'	=> $data['remove_tags'],
				);

				if(isset($data['apply_tags_cancelled']))
					$wpf_woocommerce_products[$post_id]['apply_tags_cancelled'] = $data['apply_tags_cancelled'];

				if(isset($data['apply_tags_hold']))
					$wpf_woocommerce_products[$post_id]['apply_tags_hold'] = $data['apply_tags_hold'];

				if(isset($data['apply_tags_expired']))
					$wpf_woocommerce_products[$post_id]['apply_tags_expired'] = $data['apply_tags_expired'];
				
				if(isset($data['apply_tags_refunded']))
					$wpf_woocommerce_products[$post_id]['apply_tags_refunded'] = $data['apply_tags_refunded'];

			} else {
				// If no tags, remove the record
				unset($wpf_woocommerce_products[$post_id]);
			}

			if(isset($data['apply_tags_variation'])) {

				foreach($data['apply_tags_variation'] as $variation => $apply_tags) {

					$variation_id = $_POST['variable_post_id'][$variation];
					$wpf_woocommerce_products[$variation_id] = array( 'apply_tags' => $apply_tags );

				}

			}

			$wpf_woocommerce_products = apply_filters( 'wpf_save_woocommerce_products', $wpf_woocommerce_products, $post_id, $data );

			// Keep a record of all products with tag rules
			update_option('wpf_woocommerce_products', $wpf_woocommerce_products);

			// Update the meta field in the database.
			update_post_meta( $post_id, 'wpf-settings-woo', $data );
		}

		/**
		//
		// Coupons
		//
		**/

		/**
		 * Adds WP Fusion settings tab to coupon config
		 *
		 * @access public
		 * @return array Tabs
		 */

		public function coupon_tabs( $tabs ) {

			$tabs['wp_fusion'] = array(
					'label'		=> 'WP Fusion',
					'target'	=> 'wp_fusion_tab',
					'class'		=> ''
				);

			return $tabs;

		}

		/**
		 * Output for coupon data panel
		 *
		 * @access public
		 * @return mixed
		 */

		public function coupon_data_panel() {

			echo '<div id="wp_fusion_tab" class="panel woocommerce_options_panel">';

				echo '<div class="options_group">';

					wp_nonce_field( 'wpf_meta_box_woo_coupon', 'wpf_meta_box_woo_coupon_nonce' );

					global $post;
					$settings = array('apply_tags' => array());

					if(get_post_meta( $post->ID, 'wpf-settings-woo', true )) 
						$settings = array_merge($settings, get_post_meta( $post->ID, 'wpf-settings-woo', true ));

					echo '<p class="form-field"><label for="wpf-apply-tags-woo">Apply tags when used</label>';
					wpf_render_tag_multiselect($settings['apply_tags'], 'wpf-settings-woo', 'apply_tags');
					echo '</p>';

				echo '</div>';

			echo '</div>';

		}

		/**
		 * Saves WPF configuration to product
		 *
		 * @access public
		 * @return mixed
		 */

		public function save_meta_box_data_coupon($post_id) {

			// Check if our nonce is set.
			if ( ! isset( $_POST['wpf_meta_box_woo_coupon_nonce'] ) )
				return;

			// Verify that the nonce is valid.
			if ( ! wp_verify_nonce( $_POST['wpf_meta_box_woo_coupon_nonce'], 'wpf_meta_box_woo_coupon' ) )
				return;

			// If this is an autosave, our form has not been submitted, so we don't want to do anything.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
				return;


			// Don't update on revisions
			if($_POST['post_type'] == 'revision')
				return;
			

			if(isset($_POST['wpf-settings-woo'])) {
				$data = $_POST['wpf-settings-woo'];

			} else {
				$data = array();
			}

			// Update the meta field in the database.
			update_post_meta( $post_id, 'wpf-settings-woo', $data );
		}


		/**
		//
		// EXPORT TOOLS
		//
		**/

		/**
		 * Adds WooCommerce checkbox to available export options
		 *
		 * @access public
		 * @return array Options
		 */

		public function export_options( $options ) {

			$options['woocommerce'] = array('label' => 'WooCommerce Orders', 'title' => 'Orders', 'batch_size' => 10);

			return $options;

		}

		/**
		 * Counts total number of orders to be processed
		 *
		 * @access public
		 * @return int Count
		 */

		public function batch_init() {

			$args = array(
						'numberposts' => -1,
						'post_type'   => wc_get_order_types(),
						'post_status' => array_keys( wc_get_order_statuses() ),
						'fields'      => 'ids',
						'meta_query' 	=> array( array(
								'key' 		=> 'wpf_complete',
								'compare'	=> 'NOT EXISTS'
							) )
					);

			$orders = get_posts( $args );

			echo count($orders);

		}

		/**
		 * Processes order actions in batches
		 *
		 * @access public
		 * @return void
		 */

		public function batch_step( $args ) {

			$args = array(
				'post_type'   => wc_get_order_types(),
				'post_status' => array_keys( wc_get_order_statuses() ),
				'fields'		=> 'ids',
				'orderby'		=> 'ID',
				'posts_per_page' => $args['number'],
				'meta_query' 	=> array( array(
						'key' 		=> 'wpf_complete',
						'compare'	=> 'NOT EXISTS'
					) )
				);

			$orders = get_posts( $args );

			foreach( $orders as $order_id ) {

				$this->woocommerce_apply_tags_checkout( $order_id, false, true );

			}

		}


	}

	new WPF_Woocommerce;

}