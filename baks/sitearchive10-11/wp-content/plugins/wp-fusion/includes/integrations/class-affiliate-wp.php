<?php

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

if(class_exists( 'Affiliate_WP' )) {

	class WPF_AffiliateWP extends WPF_Integrations_Base {

		/**
		 * Gets things started
		 *
		 * @access  public
		 * @since   1.0
		 * @return  void
		 */

		public function init() {

			add_filter( 'wpf_meta_fields', array( $this, 'prepare_meta_fields' ) );
			add_action( 'affwp_insert_affiliate', array( $this, 'add_affiliate' ), 5 );
			add_action( 'affwp_update_affiliate', array( $this, 'update_affiliate' ), 5 );

		}

		/**
		 * Adds AWP meta fields to WPF contact fields list
		 *
		 * @access  public
		 * @return  array Meta Fields
		 */

		public function prepare_meta_fields( $meta_fields ) {

			$meta_fields['awp_affiliate_id'] 		= array( 'label' => 'AffiliateWP Affiliate ID', 'type' => 'text' );
			$meta_fields['awp_referral_rate'] 		= array( 'label' => 'AffiliateWP Referral Rate', 'type' => 'text' );
			$meta_fields['awp_payment_email'] 		= array( 'label' => 'AffiliateWP Payment Email', 'type' => 'text' );

			return $meta_fields;

		}

		/**
		 * Triggered when affiliate updated
		 *
		 * @access  public
		 * @return  void
		 */

		public function update_affiliate( $data ) {

			$affiliate = affwp_get_affiliate( $data['affiliate_id'] );

			if(empty($data['rate']))
				$data['rate'] = affiliate_wp()->settings->get( 'referral_rate', 20 );

			$affiliate_data = array(
				'awp_affiliate_id'	=> $data['affiliate_id'],
				'awp_referral_rate' => $data['rate'],
				'awp_payment_email' => $data['payment_email']
				);

			wp_fusion()->user->push_user_meta( $affiliate->user_id, $affiliate_data );

		}

		/**
		 * Triggered when affiliate added
		 *
		 * @access  public
		 * @return  void
		 */

		public function add_affiliate( $affiliate_id ) {

			$affiliate = affwp_get_affiliate( $affiliate_id );

			$rate = isset( $affiliate->rate ) ? $affiliate->rate : null;

			if(empty($rate))
				$rate = affiliate_wp()->settings->get( 'referral_rate', 20 );

			$affiliate_data = array(
				'awp_affiliate_id'	=> $affiliate_id,
				'awp_referral_rate' => $rate,
				'awp_payment_email' => $affiliate->payment_email
				);
			
			wp_fusion()->user->push_user_meta( $affiliate->user_id, $affiliate_data );

		}


	}

	new WPF_AffiliateWP;

}