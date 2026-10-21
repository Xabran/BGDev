<?php

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

if(class_exists( 'EDD_Recurring' )) {
	
	class WPF_EDD_Recurring extends WPF_Integrations_Base {

		/**
		 * Get things started
		 *
		 * @access public
		 * @return void
		 */

		public function init() {


			// Add additional meta fields
			add_action('wpf_edd_meta_box', array( $this, 'meta_box_content' ), 10, 2 );

			// Subscription status triggers
			add_action( 'edd_subscription_completed', array( $this, 'subscription_status_change' ), 10, 2 );
			add_action( 'edd_subscription_expired', array( $this, 'subscription_status_change' ), 10, 2 );
			add_action( 'edd_subscription_failing', array( $this, 'subscription_status_change' ), 10, 2 );
			add_action( 'edd_subscription_cancelled', array( $this, 'subscription_status_change' ), 10, 2 );

		}

		/**
		 * Determines if a product or variable price option is a recurring charge
		 *
		 * @access  public
		 * @return  bool
		 */

		private function is_recurring( $download_id ) {

			if(EDD_Recurring()->is_recurring( $download_id ) == true)
				return true;

			if(edd_has_variable_prices( $download_id )) {

				$prices = edd_get_variable_prices( $download_id );

				foreach ( $prices as $price_id => $price ) {
					if ( EDD_Recurring()->is_price_recurring( $download_id, $price_id ) )
						return true;
				}

			}

			return false;

		}

		/**
		 * Triggered when a subscription status changes
		 *
		 * @access  public
		 * @return  void
		 */

		public function subscription_status_change( $subscription_id, $subscription ) {

			if($this->is_recurring($subscription->product_id) == false)
				return;

			$wpf_options = get_post_meta( $subscription->product_id, 'wpf-settings-edd', true );

			// Remove tags if option is selected
			if(isset($wpf_options['remove_tags']))
				wp_fusion()->user->remove_tags($wpf_options['apply_tags'], $subscription->customer->user_id);

			// Apply tags based on status
			$status = $subscription->get_status();

			if(isset($wpf_options['apply_tags_' . $status]))
				wp_fusion()->user->apply_tags($wpf_options['apply_tags_' . $status], $subscription->customer->user_id);

		}

		/**
		 * Outputs fields to EDD meta box
		 *
		 * @access public
		 * @return mixed
		 */

		public function meta_box_content( $post, $settings ) {

			echo '<table class="form-table wpf-edd-recurring-options' . ($this->is_recurring( $post->ID ) == true ? '' : ' hidden') . '"><tbody>';

				// Remove tags

				echo '<tr>';

					echo '<th scope="row"><label for="remove_tags">Remove Tags:</label></th>';
					echo '<td>';
						echo '<input class="checkbox" type="checkbox" id="remove_tags" name="wpf-settings-edd[remove_tags]" value="1" ' . checked($settings['remove_tags'], 1, FALSE) . ' />';
						echo '<span class="description">Remove tags when the subscription is completed, fails to charge, or is cancelled.</span>';
					echo '</td>';

				echo '</tr>';

				// Completed

				echo '<tr>';

					echo '<th scope="row"><label for="apply_tags_completed">Subscription Completed:</label></th>';
					echo '<td>';
						wpf_render_tag_multiselect($settings['apply_tags_completed'], 'wpf-settings-edd', 'apply_tags_completed');
						echo '<span class="description">Apply these when a subscription is complete (number of payments matches the Times field).</span>';
					echo '</td>';

				echo '</tr>';

				// Failing

				echo '<tr>';

					echo '<th scope="row"><label for="apply_tags_expired">Subscription Failing:</label></th>';
					echo '<td>';
						wpf_render_tag_multiselect($settings['apply_tags_failing'], 'wpf-settings-edd', 'apply_tags_failing');
						echo '<span class="description">Apply these when a subscription has a failed payment.</span>';
					echo '</td>';

				echo '</tr>';

				// Expired

				echo '<tr>';

					echo '<th scope="row"><label for="apply_tags_expired">Subscription Expired:</label></th>';
					echo '<td>';
						wpf_render_tag_multiselect($settings['apply_tags_expired'], 'wpf-settings-edd', 'apply_tags_expired');
						echo '<span class="description">Apply these when a subscription has multiple failed payments or is marked Expired.</span>';
					echo '</td>';

				echo '</tr>';

				// Cancelled

				echo '<tr>';

					echo '<th scope="row"><label for="apply_tags_cancelled">Subscription Cancelled:</label></th>';
					echo '<td>';
						wpf_render_tag_multiselect($settings['apply_tags_cancelled'], 'wpf-settings-edd', 'apply_tags_cancelled');
						echo '<span class="description">Apply these when a subscription is cancelled.</span>';
					echo '</td>';

				echo '</tr>';

			echo '</tbody></table>';


		}


	}

	new WPF_EDD_Recurring;

}