<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'YWGC_Gift_Card' ) ) {

	/**
	 *
	 * @class   YWGC_Gift_Card
	 * @package Yithemes
	 * @since   1.0.0
	 * @author  Your Inspiration Themes
	 */
	class YWGC_Gift_Card {
		/**
		 * @var int the id of the gift card post type
		 */
		public $ID = 0;

		/**
		 * @var int the product id for the product of type WC_Product_Gift_Card
		 */
		public $product_id = 0;

		/**
		 * @var int the order id for the gift card
		 */
		public $order_id = 0;

		/**
		 * @var string the code that let the customer apply the discuount
		 */
		public $gift_card_number = '';

		/**
		 * @var float the gift card amount
		 */
		public $amount = 0.00;

		/**
		 * @var float
		 */
		public $amount_tax = 0.00;

		/**
		 * @var float the gift card current balance
		 */
		public $balance = 0.00;

		/**
		 * @var float
		 */
		public $balance_tax = 0.00;

		/**
		 * @var string the gift card post status
		 */
		public $status = 'publish';

		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @param  array $args the arguments
		 *
		 * @since  1.0
		 * @author Lorenzo Giuffrida
		 */
		public function __construct( $args = array() ) {

			$defaults = array(
				'ID'               => 0,
				'gift_card_number' => '',
				'product_id'       => 0,
				'order_id'         => 0,
				'amount'           => 0.00,
				'amount_tax'       => 0.00,
				'balance'          => 0.00,
				'balance_tax'      => 0.00,
				'status'           => GIFT_CARD_STATUS_ENABLED,
			);

			$args = wp_parse_args( $args, $defaults );

			/**
			 *  if $args['ID'] is set, retrieve the post with the same ID
			 *  if $args['gift_card_number'] is set, retrieve the post with the same post_title
			 */
			if ( $args['ID'] ) {
				$post = get_post( $args['ID'] );
			} elseif ( ! empty( $args['gift_card_number'] ) ) {
				$post = get_page_by_title( $args['gift_card_number'], OBJECT, YWGC_CUSTOM_POST_TYPE_NAME );
			}

			if ( isset( $post ) ) {

				$args = array(
					'ID'               => $post->ID,
					'gift_card_number' => $post->post_title,
					'product_id'       => $post->post_parent,
					'order_id'         => get_post_meta( $post->ID, YWGC_META_GIFT_CARD_ORDER_ID, true ),
					'amount'           => get_post_meta( $post->ID, YWGC_META_GIFT_CARD_AMOUNT, true ),
					'amount_tax'       => get_post_meta( $post->ID, YWGC_META_GIFT_CARD_AMOUNT_TAX, true ),
					'balance'          => get_post_meta( $post->ID, YWGC_META_GIFT_CARD_AMOUNT_BALANCE, true ),
					'balance_tax'      => get_post_meta( $post->ID, YWGC_META_GIFT_CARD_AMOUNT_BALANCE_TAX, true ),
					'status'           => $post->post_status,
				);
			}

			//  Set the object fields
			$this->ID               = $args['ID'];
			$this->gift_card_number = $args['gift_card_number'];
			$this->product_id       = $args['product_id'];
			$this->order_id         = $args['order_id'];
			$this->amount           = $args['amount'];
			$this->amount_tax       = $args['amount_tax'];
			$this->balance          = $args['balance'];
			$this->balance_tax      = $args['balance_tax'];
			$this->status           = $args['status'];
		}

		/**
		 * Give a mixed arguments, extract an array with the arguments to be used for creating a gift card
		 *
		 * @param int|string|array $args
		 *
		 * @return array
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		private function get_array_args( $args ) {


		}

		/**
		 * Set the initial amount for the current gift card. Can't be updated if previously set.
		 *
		 * @param float $amount         The gift card amount
		 * @param float $tax_amount     The gift card amount
		 * @param bool  $update_balance update the balance when the amount is set
		 *
		 */
		public function set_amount( $amount, $tax_amount = 0.00, $update_balance = true ) {

			$this->amount     = $amount;
			$this->amount_tax = $tax_amount;
			$this->save_amount();

			if ( $update_balance ) {
				$this->set_balance( $amount, $tax_amount );
			}
		}

		/**
		 * Retrieve the gift card original amount
		 *
		 * @param bool $incl_tax
		 *
		 * @return float the current gift card amount
		 */
		public function get_amount( $incl_tax = false ) {

			$_amount = 0.00;

			if ( $this->ID ) {
				$_amount = get_post_meta( $this->ID, YWGC_META_GIFT_CARD_AMOUNT, true );
				$_amount = empty( $_amount ) ? 0.00 : $_amount;

				if ( $incl_tax ) {
					$_tax = get_post_meta( $this->ID, YWGC_META_GIFT_CARD_AMOUNT_TAX, true );
					$_amount += $_tax;
				}
			}

			return $_amount;
		}

		/**
		 * Save the gift card amount to the database
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		private function save_amount() {

			if ( $this->ID ) {
				update_post_meta( $this->ID, YWGC_META_GIFT_CARD_AMOUNT, $this->amount );
				update_post_meta( $this->ID, YWGC_META_GIFT_CARD_AMOUNT_TAX, $this->amount_tax );
			}
		}

		/**
		 * Set the balance for the current gift card
		 *
		 * @param float $amount
		 * @param float $tax_amount
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function set_balance( $amount, $tax_amount = 0.00 ) {
			$this->balance     = $amount;
			$this->balance_tax = $tax_amount;
			$this->save_balance();
		}

		/**
		 * Persist the current balance on the database
		 *
		 * @param float      $new_balance_tax
		 * @param float|null $new_balance The new balance for the gift card or false to use the current amount
		 */
		private function update_balance( $new_balance = null, $new_balance_tax = 0.00 ) {
			if ( $new_balance !== null ) {
				$this->balance     = $new_balance;
				$this->balance_tax = $new_balance_tax;
				$this->save_balance();
			}
		}

		private function save_balance() {
			if ( $this->ID ) {
				update_post_meta( $this->ID, YWGC_META_GIFT_CARD_AMOUNT_BALANCE, $this->balance );
				update_post_meta( $this->ID, YWGC_META_GIFT_CARD_AMOUNT_BALANCE_TAX, $this->balance_tax );
			}
		}


		/**
		 * Retrieve the gift card current balance
		 *
		 * @param bool $incl_tax give inclusive of taxes
		 *
		 * @return float the current gift card balance
		 */
		public function get_balance( $incl_tax = false ) {

			$_balance = 0.00;

			if ( $this->ID ) {
				$_balance = get_post_meta( $this->ID, YWGC_META_GIFT_CARD_AMOUNT_BALANCE, true );
				$_balance = empty( $_balance ) ? 0.00 : $_balance;

				if ( $incl_tax ) {
					$_tax = get_post_meta( $this->ID, YWGC_META_GIFT_CARD_AMOUNT_BALANCE_TAX, true );
					$_tax = empty( $_tax ) ? 0.00 : $_tax;
					$_balance += $_tax;
				}
			}

			return $_balance;
		}

		/**
		 * Deduct an amount from the gift card
		 *
		 * @param float $deduct_amount     the amount to be deducted from current gift card balance
		 * @param float $deduct_amount_tax the tax amount to be deducted from current gift card balance
		 */
		public function deduct_amount( $deduct_amount, $deduct_amount_tax = 0.00 ) {

			$new_amount     = $this->get_balance() - $deduct_amount;
			$new_amount_tax = $this->get_balance( true ) - $this->get_balance() - $deduct_amount_tax;

			if ( $new_amount < 0 ) {
				$new_amount     = 0.00;
				$new_amount_tax = 0.00;
			}

			$this->update_balance( $new_amount, $new_amount_tax );
		}

		/**
		 * Register the order in the list of orders where the gift card was used
		 *
		 * @param int $order_id
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function register_order( $order_id ) {
			if ( $this->ID ) {
				//  assign the order to this gift cards...
				$orders   = $this->get_registered_orders();
				$orders[] = $order_id;
				update_post_meta( $this->ID, YWGC_META_GIFT_CARD_ORDERS, $orders );

				//  assign the customer to this gift cards...
				$order = wc_get_order( $order_id );
				$this->register_user( $order->customer_user );
			}
		}

		/**
		 * Check if the user is registered as the gift card owner
		 *
		 * @param int $user_id
		 *
		 * @return bool
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function is_registered_user( $user_id ) {
			$customer_users = get_post_meta( $this->ID, YWGC_META_GIFT_CARD_CUSTOMER_USER );

			return in_array( $user_id, $customer_users );
		}

		/**
		 * Register an user as the gift card owner(may be one or more)
		 *
		 * @param int $user_id
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function register_user( $user_id ) {
			if ( $user_id == 0 ) {
				return;
			}

			if ( $this->is_registered_user( $user_id ) ) {
				//  the user is a register user
				return;
			}

			add_post_meta( $this->ID, YWGC_META_GIFT_CARD_CUSTOMER_USER, $user_id );
		}

		/**
		 * Retrieve the list of orders where the gift cards was used
		 *
		 * @return array|mixed
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function get_registered_orders() {
			$orders = array();

			if ( $this->ID ) {
				$orders = get_post_meta( $this->ID, YWGC_META_GIFT_CARD_ORDERS, true );
				if ( ! $orders ) {
					$orders = array();
				}
			}

			return array_unique( $orders );
		}

		/**
		 * Check if the gift card has enough balance to cover the amount requested
		 *
		 * @param $amount int the amount to be deducted from current gift card balance
		 *
		 * @return bool the gift card has enough credit
		 */
		public function has_sufficient_credit( $amount ) {
			return $this->get_balance( true ) >= $amount;
		}

		/**
		 * retrieve the gift card code
		 *
		 * @return string
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function get_code() {
			return $this->gift_card_number;
		}

		/**
		 * The gift card exists
		 *
		 * @return bool
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function exists() {
			return $this->ID > 0;
		}

		/**
		 * Save the current object
		 */
		public function save() {
			// Create post object
			$args = array(
				'post_title'  => $this->gift_card_number,
				'post_status' => $this->status,
				'post_type'   => YWGC_CUSTOM_POST_TYPE_NAME,
				'post_parent' => $this->product_id,
			);

			if ( $this->ID == 0 ) {
				// Insert the post into the database
				$this->ID = wp_insert_post( $args );

			} else {
				$args["ID"] = $this->ID;
				$this->ID   = wp_update_post( $args );
			}

			//  Save Gift Card postmeta
			$this->save_amount();
			$this->save_balance();

			update_post_meta( $this->ID, YWGC_META_GIFT_CARD_ORDER_ID, $this->order_id );

			return $this->ID;
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

			$new_gift             = new YWGC_Gift_Card_Premium();
			$new_gift->product_id = $this->product_id;
			$new_gift->order_id   = $this->order_id;

			//  Set the amount of the cloned gift card equal to the balance of the old one
			$new_gift->set_amount( $this->balance, $this->balance_tax );
			$new_gift->gift_card_number = $new_code;

			return $new_gift;
		}
	}
}