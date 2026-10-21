<?php

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

if(class_exists( 'Easy_Digital_Downloads' )) {
	
	class WPF_EDD extends WPF_Integrations_Base {

		/**
		 * Get things started
		 *
		 * @access public
		 * @return void
		 */

		public function init() {

			add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
			add_filter( 'wpf_meta_fields', array( $this, 'prepare_meta_fields' ), 20 );
			add_action( 'save_post', array( $this, 'save_meta_box_data' ));
			add_action( 'edd_complete_purchase', array( $this, 'complete_purchase' ), 10 );

			add_filter( 'wpf_user_register', array( $this, 'user_register' ) );

			// Variable price column fields
			add_action( 'edd_download_price_table_head', array( $this, 'download_price_table_head' ) );
			add_action( 'edd_download_price_table_row', array( $this, 'download_table_price_row' ), 10, 3 );

		}

		/**
		 * Sets field labels and types for EDD custom fields
		 *
		 * @access  public
		 * @return  array Meta fields
		 */

		public function prepare_meta_fields( $meta_fields ) {

			$meta_fields['billing_address_1'] 	= array('label' => 'Billing Address 1', 'type' => 'text');
			$meta_fields['billing_address_2'] 	= array('label' => 'Billing Address 2', 'type' => 'text');
			$meta_fields['billing_city'] 		= array('label' => 'Billing City', 'type' => 'text');
			$meta_fields['billing_state'] 		= array('label' => 'Billing State', 'type' => 'text');
			$meta_fields['billing_country'] 	= array('label' => 'Billing Country', 'type' => 'text');
			$meta_fields['billing_postcode'] 	= array('label' => 'Billing Postcode', 'type' => 'text');

			return $meta_fields;

		}

		/**
		 * Triggered at user registration to adapt EDD fields to WP standard
		 *
		 * @access public
		 * @return array Post Data
		 */

		public function user_register( $post_data ) {

			// Trim "edd_" from the beginning of each key
			foreach($post_data as $key => $value) {
				if(substr( $key, 0, 4 ) == 'edd_') {
					$key = substr($key, 4);
					$post_data[$key] = $value;
					unset($post_data['edd_' . $key]);
				}
			}

			if(isset($post_data['email'])) {
				$post_data['user_email'] = $post_data['email'];
				unset($post_data['email']);
			}

			if(isset($post_data['first'])) {
				$post_data['first_name'] = $post_data['first'];
				unset($post_data['first']);
			}

			if(isset($post_data['last'])) {
				$post_data['last_name'] = $post_data['last'];
				unset($post_data['last']);
			}

			return $post_data;

		}

		/**
		 * Triggered when an order is completed. Updates contact record (or creates it) and applies tags
		 *
		 * @access public
		 * @return void
		 */

		public function complete_purchase( $payment_id ) {

			// Adapt meta data to contact data
			$user_meta = $this->edd_get_customer_data($payment_id);

			// See if the user already exists locally
			$user_id = edd_get_payment_user_id($payment_id);

			// Guest checkout
			if($user_id == '-1') {

				// See if contact exists in CRM based on email
				$contact_id = wp_fusion()->crm->get_contact_id( $user_meta['user_email'] );

				// If no contact ID found
				if($contact_id == false) {

					// Create contact and add note
					$contact_id = wp_fusion()->crm->add_contact( $user_meta );
					edd_insert_payment_note($payment_id, wp_fusion()->crm->name . ' contact ID ' . $contact_id . ' created via guest-checkout.');

				} else {

					// Update contact
					wp_fusion()->crm->update_contact( $contact_id, $user_meta );					

				}


			} else {

				// If user is found, update their info
				wp_fusion()->user->push_user_meta( $user_id, $user_meta );
				$contact_id = wp_fusion()->user->get_contact_id( $user_id );

			}

			// Apply tags

			$apply_tags = array();
			$cart_items = edd_get_payment_meta_cart_details( $payment_id );

			foreach( $cart_items as $item ) {

				$wpf_settings = get_post_meta( $item['id'], 'wpf-settings-edd', true );

				if(!empty($wpf_settings)) {

					if(isset($wpf_settings['apply_tags']))
						$apply_tags = array_merge($apply_tags, $wpf_settings['apply_tags']);


					// Variable pricing tags
					if(isset($wpf_settings['apply_tags_price'])) {

						$payment_details = get_post_meta($payment_id, '_edd_payment_meta', true);

						foreach($payment_details['downloads'] as $download) {

							$price_id = edd_get_cart_item_price_id($download);

							if(isset($wpf_settings['apply_tags_price'][$price_id]))
								$apply_tags = array_merge($apply_tags, $wpf_settings['apply_tags_price'][$price_id]);

						}

					}

				}

			}

			// Guest checkout
			if($user_id == '-1') {

				wp_fusion()->crm->apply_tags( $apply_tags, $contact_id );

			} else {

				wp_fusion()->user->apply_tags( $apply_tags, $user_id );

			}

			// Run payment complete action
			do_action('wpf_edd_payment_complete', $payment_id, $contact_id);

		}


		/**
		 * Internal function to map EDD payment meta to user meta fields
		 *
		 * @access public
		 * @return array User Meta
		 */

		private function edd_get_customer_data($payment_id) {

			$payment_details = get_post_meta($payment_id, '_edd_payment_meta', true);

			$user_meta = array();

			$user_meta['user_email'] = $payment_details['user_info']['email'];
			$user_meta['first_name'] = $payment_details['user_info']['first_name'];
			$user_meta['last_name'] = $payment_details['user_info']['last_name'];

			if(is_array($payment_details['user_info']['address'])) {
				$user_meta['billing_address_1'] = $payment_details['user_info']['address']['line1'];
				$user_meta['billing_address_2'] = $payment_details['user_info']['address']['line2'];
				$user_meta['billing_city'] = $payment_details['user_info']['address']['city'];
				$user_meta['billing_state'] = $payment_details['user_info']['address']['state'];
				$user_meta['billing_country'] = $payment_details['user_info']['address']['country'];
				$user_meta['billing_postcode'] = $payment_details['user_info']['address']['zip'];
			}

			return $user_meta;

		}


		/**
		 * Outputs WPF column headers on pricing table
		 *
		 * @access public
		 * @return voic
		 */

		public function download_price_table_head() {

			echo '<th style="width: 300px;">Apply Tags (Variations)</th>';

		}


		/**
		 * Outputs WPF fields to variable price rows
		 *
		 * @access public
		 * @return voic
		 */

		public function download_table_price_row( $post_id, $key, $args ) {

			echo '<td class="wpf-tags-select">';

			$settings = array(
				'apply_tags_price'	=> array()
			);

			if(get_post_meta( $post_id, 'wpf-settings-edd', true )) 
				$settings = array_merge($settings, get_post_meta( $post_id, 'wpf-settings-edd', true ));

			if(empty($settings['apply_tags_price'][$key]))
				$settings['apply_tags_price'][$key] = array();

			wpf_render_tag_multiselect($settings['apply_tags_price'], 'wpf-settings-edd', 'apply_tags_price', $key);

			echo '</td>';

		}


		/**
		 * Registers meta box
		 *
		 * @access public
		 * @return voic
		 */

		public function add_meta_box() {

			add_meta_box('wpf-edd-meta', 'WP Fusion Download Settings', array($this, 'meta_box_callback'), 'download', 'normal', 'default');

		}

		/**
		 * Displays meta box content
		 *
		 * @access public
		 * @return mixed
		 */

		public function meta_box_callback( $post ) {

			// Add an nonce field so we can check for it later.
			wp_nonce_field( 'wpf_meta_box_edd', 'wpf_meta_box_edd_nonce' );

			$settings = array(
				'apply_tags'	=> array(),
			);

			if(get_post_meta( $post->ID, 'wpf-settings-edd', true )) 
				$settings = array_merge($settings, get_post_meta( $post->ID, 'wpf-settings-edd', true ));

			echo '<table class="form-table"><tbody>';

				echo '<tr>';

					echo '<th scope="row"><label for="apply_tags">Apply Tags:</label></th>';
					echo '<td>';
						wpf_render_tag_multiselect($settings['apply_tags'], 'wpf-settings-edd', 'apply_tags');
						echo '<span class="description">Apply these tags in ' . wp_fusion()->crm->name . ' when purchased</span>';
					echo '</td>';

				echo '</tr>';

			echo '</tbody></table>';

			// Allows other plugins to add additional fields to meta box
			do_action('wpf_edd_meta_box', $post, $settings);

		}

		/**
		 * Saves WPF configuration to product
		 *
		 * @access public
		 * @return mixed
		 */

		public function save_meta_box_data($post_id) {

			// Check if our nonce is set.
			if ( ! isset( $_POST['wpf_meta_box_edd_nonce'] ) )
				return;

			// Verify that the nonce is valid.
			if ( ! wp_verify_nonce( $_POST['wpf_meta_box_edd_nonce'], 'wpf_meta_box_edd' ) )
				return;

			// If this is an autosave, our form has not been submitted, so we don't want to do anything.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
				return;

			// Don't update on revisions
			if($_POST['post_type'] == 'revision')
				return;
			

			if(isset($_POST['wpf-settings-edd'])) {
				$data = $_POST['wpf-settings-edd'];

			} else {
				$data = array();
			}

			// Update the meta field in the database.
			update_post_meta( $post_id, 'wpf-settings-edd', $data );
		}

	}

	new WPF_EDD;

}