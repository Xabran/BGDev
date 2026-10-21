<?php

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

if(class_exists( 'WC_Subscriptions' )) {

	class WPF_Woo_Subscriptions extends WPF_Integrations_Base {

		/**
		 * Gets things started
		 *
		 * @access  public
		 * @return  void
		 */

		public function init() {

			add_action( 'cancelled_subscription', array( $this, 'cancel_subscription' ), 10, 2 );
			add_action( 'subscription_put_on-hold', array( $this, 'cancel_subscription' ), 10, 2 );
			add_action( 'subscription_expired', array( $this, 'cancel_subscription'), 10, 2 );
			add_action( 'subscription_trial_end', array( $this, 'cancel_subscription' ), 10, 2 );

			add_action( 'activated_subscription', array( $this, 'activate_subscription' ), 10, 2 );

			add_action( 'wpf_woocommerce_panel', array( $this, 'panel_content' ) );

			// Export functions
			add_filter( 'wpf_export_options', array( $this, 'export_options' ) );
			add_action( 'wpf_batch_woo_subscriptions_init', array( $this, 'batch_init' ) );
			add_action( 'wpf_batch_woo_subscriptions', array( $this, 'batch_step' ) );

		}

		/**
		 * Writes subscriptions options to WPF/Woo panel
		 *
		 * @access public
		 * @return mixed
		 */

		public function panel_content() {

			global $post;
			$settings = array('remove_tags' => 0, 'apply_tags_cancelled' => array(), 'apply_tags_hold' => array(), 'apply_tags_expired' => array());

			if(get_post_meta( $post->ID, 'wpf-settings-woo', true )) 
				$settings = array_merge($settings, get_post_meta( $post->ID, 'wpf-settings-woo', true ));

			echo '<div class="options_group wpf_show_if_subscription">';

				echo '<p class="form-field"><label for="wpf-apply-tags-woo">Remove tags</label>';
				echo '<input class="checkbox" type="checkbox" id="wpf-remove-tags-woo" name="wpf-settings-woo[remove_tags]" value="1" ' . checked($settings['remove_tags'], 1, FALSE) . ' />';
				echo '<span class="description">Remove tags when the subscription is cancelled, put on hold, or expires.</span>';
				echo '</p>';

			echo '</div>';

			echo '<div class="options-group wpf_show_if_subscription">';

				// Cancelled
				echo '<p class="form-field"><label>Cancelled</label>';
				wpf_render_tag_multiselect($settings['apply_tags_cancelled'], 'wpf-settings-woo', 'apply_tags_cancelled');
				echo '<span class="description">Apply these tags when a subscription is cancelled.</span>';
				echo '</p>';

				// Put on hold
				echo '<p class="form-field"><label>Put on hold</label>';
				wpf_render_tag_multiselect($settings['apply_tags_hold'], 'wpf-settings-woo', 'apply_tags_hold');
				echo '<span class="description">Apply these tags when a subscription is put on hold.</span>';
				echo '</p>';

				// Expires
				echo '<p class="form-field"><label>Expired</label>';
				wpf_render_tag_multiselect($settings['apply_tags_expired'], 'wpf-settings-woo', 'apply_tags_expired');
				echo '<span class="description">Apply these tags when a subscription expires.</span>';
				echo '</p>';

			echo '</div>';

		}

		/**
		 * Triggered when a subscription is cancelled, put on hold, or expired
		 *
		 * @access public
		 * @return void
		 */

		public function cancel_subscription( $user_id, $subscription_key ) {

			$subscription = WC_Subscriptions_Manager::get_subscription( $subscription_key );
			$wpf_woocommerce_products = get_option('wpf_woocommerce_products');

			if(isset($wpf_woocommerce_products[$subscription['product_id']])) {

				// Remove tags
				if(isset($wpf_woocommerce_products[$subscription['product_id']]['remove_tags']) && $wpf_woocommerce_products[$subscription['product_id']]['remove_tags'] == true)
					wp_fusion()->user->remove_tags($wpf_woocommerce_products[$subscription['product_id']]['apply_tags'], $user_id);


				// Apply tags based on subscription status
				if($subscription['status'] == 'cancelled' && isset($wpf_woocommerce_products[$subscription['product_id']]['apply_tags_cancelled']))
					wp_fusion()->user->apply_tags($wpf_woocommerce_products[$subscription['product_id']]['apply_tags_cancelled'], $user_id);

				if($subscription['status'] == 'on-hold' && isset($wpf_woocommerce_products[$subscription['product_id']]['apply_tags_hold']))
					wp_fusion()->user->apply_tags($wpf_woocommerce_products[$subscription['product_id']]['apply_tags_hold'], $user_id);

				if($subscription['status'] == 'expired' && isset($wpf_woocommerce_products[$subscription['product_id']]['apply_tags_expired']))
					wp_fusion()->user->apply_tags($wpf_woocommerce_products[$subscription['product_id']]['apply_tags_expired'], $user_id);

			}

		}

		/**
		 * Triggered when a subscription is activated
		 *
		 * @access public
		 * @return void
		 */

		public function activate_subscription( $user_id, $subscription_key ) {

			$subscription = WC_Subscriptions_Manager::get_subscription( $subscription_key );

			$wpf_woocommerce_products = get_option('wpf_woocommerce_products');

			if(isset($wpf_woocommerce_products[$subscription['product_id']])) {

				wp_fusion()->user->apply_tags($wpf_woocommerce_products[$subscription['product_id']]['apply_tags'], $user_id);

			}

		}

		/**
		//
		// EXPORT TOOLS
		//
		**/

		/**
		 * Adds Woo Subscriptions checkbox to available export options
		 *
		 * @access public
		 * @return array Options
		 */

		public function export_options( $options ) {

			$options['woo_subscriptions'] = array('label' => 'WooCommerce Subscription Statuses', 'title' => 'Subscriptions', 'batch_size' => 10);

			return $options;

		}

		/**
		 * Counts total number of subscriptions to be processed
		 *
		 * @access public
		 * @return int Count
		 */

		public function batch_init() {

			$args = array(
						'numberposts' => -1,
						'post_type'   => 'shop_subscription',
						'post_status' => 'any',
						'fields'      => 'ids'
					);

			$subscriptions = get_posts( $args );

			echo count($subscriptions);

		}

		/**
		 * Processes subscription actions in batches
		 *
		 * @access public
		 * @return void
		 */

		public function batch_step( $args ) {

			$wpf_woocommerce_products = get_option('wpf_woocommerce_products');

			$subscriptions = wcs_get_subscriptions(array('subscriptions_per_page' => $args['number'], 'offset' => $args['offset']));

			foreach( $subscriptions as $subscription ) {
				$user_id = $subscription->get_user_id();
				$key = wcs_get_old_subscription_key($subscription);
				$status = $subscription->get_status();
				$order_items  = $subscription->get_items();
				$product_id   = ( empty( $order_items ) ) ? 0 : WC_Subscriptions_Order::get_items_product_id( reset( $order_items ) );

				if($status != 'active' && isset($wpf_woocommerce_products[$product_id]['remove_tags']) && $wpf_woocommerce_products[$product_id]['remove_tags'] == true) {

					$this->cancel_subscription( $user_id, $key );

				} elseif ($status == 'active') {
					
					$this->activate_subscription( $user_id, $key );

				} else {

					$this->activate_subscription( $user_id, $key );
					$this->cancel_subscription( $user_id, $key );

				}

			}

		}
	}

	new WPF_Woo_Subscriptions;

}