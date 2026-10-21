<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'YWGC_Gift_Card_Premium' ) ) {
	/**
	 *
	 * @class   YWGC_Gift_Card_Premium
	 * @package Yithemes
	 * @since   1.0.0
	 * @author  Your Inspiration Themes
	 */
	class YWGC_Gift_Card_Premium extends YWGC_Gift_Card {

		/**
		 * @var bool the gift card has a postdated delivery date
		 */
		public $postdated_delivery = false;

		/**
		 * @var string the delivery date
		 */
		public $delivery_date;

		/**
		 * @var string the recipient for digital gift cards
		 */
		public $recipient = '';

		/**
		 * @var string the sender for digital gift cards
		 */
		public $sender = '';

		/**
		 * @var string the message for digital gift cards
		 */
		public $message = '';

		/**
		 * @var bool the digital gift cards use the default image
		 */
		public $use_default_image = true;

		/**
		 * @var string the custom image for digital gift cards
		 */
		public $custom_image = null;

		/**
		 * @var bool the product is set as a present
		 */
		public $product_as_present = false;

		/**
		 * @var int the product variation id when the product is used as a present
		 */
		public $present_variation_id = 0;

		/**
		 * @var int the product id used as a present
		 */
		public $present_product_id = 0;

		/**
		 * @var string the currency used when the gift card is created
		 */
		public $currency = '';


		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @param $args int|array|WP_Post
		 *
		 * @since  1.0
		 * @author Lorenzo Giuffrida
		 */
		public function __construct( $args = array() ) {

			parent::__construct( $args );

			$defaults = array(
				'postdated_delivery'   => false,
				'delivery_date'        => '',
				'recipient'            => '',
				'sender'               => '',
				'message'              => '',
				'use_default_image'    => true,
				'custom_image'         => '',
				'product_as_present'   => false,
				'present_variation_id' => 0,
				'present_product_id'   => 0,
				'currency'             => get_woocommerce_currency(),
			);

			//  if $args is a numeric value, retrieve the post with the same ID
			//  if $args is a string value, retrieve the post with the same post_title
			if ( $this->ID ) {
				$args = get_post_meta( $this->ID, YWGC_META_GIFT_CARD_USER_DATA, true );
			}

			$args = wp_parse_args( $args, $defaults );

			$this->postdated_delivery   = $args["postdated_delivery"];
			$this->delivery_date        = $args["delivery_date"];
			$this->recipient            = $args["recipient"];
			$this->sender               = $args["sender"];
			$this->message              = $args["message"];
			$this->use_default_image    = $args["use_default_image"];
			$this->custom_image         = $args["custom_image"];
			$this->product_as_present   = $args["product_as_present"];
			$this->present_variation_id = $args["present_variation_id"];
			$this->present_variation_id = $args["present_product_id"];
			$this->currency             = $args["currency"];
		}

		/**
		 * The gift card product is virtual
		 */
		public function is_virtual() {

			$is_virtual = false;

			$product = wc_get_product( $this->product_id );
			if ( $product ) {
				$is_virtual = $product->is_virtual();
			}

			return $is_virtual;
		}

		/**
		 * Check if the gift card has been sent
		 */
		public function has_been_sent() {
			return get_post_meta( $this->ID, YWGC_META_GIFT_CARD_SENT, true );
		}

		/**
		 * Set the gift card as sent
		 */
		public function set_as_sent() {
			update_post_meta( $this->ID, YWGC_META_GIFT_CARD_SENT, current_time( 'Y-m-d', 0 ) );
		}

		/**
		 * Set the gift card as pre-printed i.e. the code is manually entered instead of being auto generated
		 */
		public function set_as_pre_printed() {
			$this->set_status( GIFT_CARD_STATUS_PRE_PRINTED );
		}

		/**
		 * Check if the gift card is pre-printed
		 */
		public function is_pre_printed() {

			return GIFT_CARD_STATUS_PRE_PRINTED == $this->status;
		}

		/**
		 * Retrieve if a gift card is enabled
		 *
		 * @return bool
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function is_enabled() {

			return GIFT_CARD_STATUS_ENABLED == $this->status;
		}

		/**
		 * Retrieve if a gift card can be enabled
		 *
		 * @return bool
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function can_be_enabled() {

			return ! $this->is_enabled() && ! $this->is_dismissed();
		}

		/**
		 * Retrieve if a gift card can be disabled
		 *
		 * @return bool
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function can_be_disabled() {

			return $this->is_enabled() && ! $this->is_dismissed();
		}


		/**
		 * Set the gift card enabled status
		 *
		 * @param bool|false $enabled
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function set_enabled_status( $enabled = false ) {

			$current_status = $this->is_enabled();

			if ( $current_status == $enabled ) {
				return;
			}

			//  If the gift card is dismissed, stop now
			if ( $this->is_dismissed() ) {
				return;
			}

			$this->set_status( $enabled ? 'publish' : GIFT_CARD_STATUS_DISABLED );
		}

		/**
		 * Set the gift card status
		 *
		 * @param string $status
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function set_status( $status ) {

			$this->status = $status;

			if ( $this->ID ) {
				$args = array(
					'ID'          => $this->ID,
					'post_status' => $status,
				);

				wp_update_post( $args );
			}
		}

		/**
		 * Save the current object
		 */
		public function save() {
			parent::save();

			/**
			 * Save user content as a serialized array if a gift card object is created and
			 * valid.
			 */
			if ( $this->ID ) {
				update_post_meta( $this->ID, YWGC_META_GIFT_CARD_USER_DATA, (array) $this );
			}
		}

		/**
		 * The gift card is nulled and no more usable
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function set_dismissed_status() {
			$this->set_status( GIFT_CARD_STATUS_DISMISSED );
		}

		/**
		 * Check if the gift card is dismissed
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function is_dismissed() {

			return GIFT_CARD_STATUS_DISMISSED == $this->status;
		}

		/**
		 * Clone the current gift card using the remaining balance as new amount
		 *
		 * @param string $new_code the code to be used for the new gift card
		 *
		 * @return YWGC_Gift_Card_Premium
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function clone_gift_card( $new_code = '' ) {

			$new_gift                       = new YWGC_Gift_Card_Premium();
			$new_gift->product_id           = $this->product_id;
			$new_gift->order_id             = $this->order_id;
			$new_gift->sender               = $this->sender;
			$new_gift->recipient            = $this->recipient;
			$new_gift->message              = $this->message;
			$new_gift->postdated_delivery   = $this->postdated_delivery;
			$new_gift->delivery_date        = $this->delivery_date;
			$new_gift->use_default_image    = $this->use_default_image;
			$new_gift->custom_image         = $this->custom_image;
			$new_gift->product_as_present   = $this->product_as_present;
			$new_gift->present_variation_id = $this->present_variation_id;
			$new_gift->present_product_id   = $this->present_product_id;
			$new_gift->currency             = $this->currency;
			$new_gift->status               = $this->status;

			$new_gift->gift_card_number = $new_code;

			//  Set the amount of the cloned gift card equal to the balance of the old one
			$new_gift->set_amount( $this->balance, $this->balance_tax );

			return $new_gift;
		}
	}
}