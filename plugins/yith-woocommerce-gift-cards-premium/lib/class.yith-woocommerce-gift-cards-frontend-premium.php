<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


if ( ! class_exists( 'YITH_WooCommerce_Gift_Cards_Frontend_Premium' ) ) {

	/**
	 *
	 * @class   YITH_WooCommerce_Gift_Cards_Frontend_Premium
	 * @package Yithemes
	 * @since   1.0.0
	 * @author  Your Inspiration Themes
	 */
	class YITH_WooCommerce_Gift_Cards_Frontend_Premium extends YITH_WooCommerce_Gift_Cards_Frontend {
		/**
		 * @var YITH_WooCommerce_Gift_Cards_Premium main
		 */
		public $main;

		/**
		 * Single instance of the class
		 *
		 * @since 1.0.0
		 */
		protected static $instance;

		/**
		 * Returns single instance of the class
		 *
		 * @since 1.0.0
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since  1.0
		 * @author Lorenzo Giuffrida
		 */
		protected function __construct() {
			parent::__construct();

			/**
			 * Permit to enter free gift card amount
			 */
			add_action( 'yith_gift_cards_template_after_amounts', array( $this, 'show_free_amount_area' ) );

			/**
			 * Let the user to enter a free amount instead of choosing from the select
			 */
			add_action( 'yith_gift_cards_template_append_amount', array( $this, 'add_manual_amount_item' ) );

			/**
			 * Show a live preview of how the gift card will look like
			 */
			add_action( 'yith_gift_cards_template_after_gift_card_form', array(
				$this,
				'show_gift_card_generator'
			), 1 );

			/**
			 * Let the customer to use a product of type WC_Product_Simple  as source for a gift card
			 */
			add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'show_give_as_present_link_simple' ) );

			/**
			 * Let the customer to use a product of type WC_Product_Variable  as source for a gift card
			 */
			add_action( 'woocommerce_single_variation', array( $this, 'show_give_as_present_link_variable' ), 99 );

			//  Let the user to edit che gift card content
			add_action( 'wp_ajax_edit_gift_card', array( $this, 'edit_gift_card_callback' ) );

			/**
			 * Show the gift cards registered to the current user in my-account page
			 */
			add_action( 'woocommerce_after_my_account', array( $this, 'show_my_gift_cards_table' ) );

			/**
			 * Manage the WooCommerce 2.6.0 changes in the cart page and
			 * change the message sent when in cart page a gift card code is entered.
			 * @since 1.4.0
			 */
			add_filter( 'woocommerce_coupon_message', array( $this, 'change_messsage_in_cart' ), 10, 3 );
			add_filter( 'woocommerce_coupon_error', array( $this, 'change_messsage_in_cart' ), 10, 3 );

			/**
			 * set the price when a gift card product is added to the cart
			 */
			add_filter( 'woocommerce_add_cart_item', array( $this, 'set_price_in_cart' ), 10, 2 );
		}

		public function set_price_in_cart( $args, $cart_item_key ) {

			if ( isset( $args['data'] ) ) {

				if ( $args['data'] instanceof WC_Product_Gift_Card ) {
					$args['data']->price = $args['amount'];
				}
			}

			return $args;

		}


		/**
		 * Change the message sent when in cart page a gift card code is entered.
		 *
		 * @param string    $msg
		 * @param int       $msg_code
		 * @param WC_Coupon $coupon
		 *
		 * @return string
		 */
		public function change_messsage_in_cart( $msg, $msg_code, $coupon ) {
			if ( ! isset( $_REQUEST['is_gift_card'] ) ) {
				return $msg;
			}

			switch ( $msg_code ) {
				case WC_Coupon::E_WC_COUPON_ALREADY_APPLIED :
					$msg = __( 'The gift card code is already applied to the current cart.', 'yith-woocommerce-gift-cards' );
					break;

				case WC_Coupon::WC_COUPON_SUCCESS :
					$msg = __( 'Gift card code applied successfully.', 'yith-woocommerce-gift-cards' );
					break;

				case WC_Coupon::WC_COUPON_REMOVED :
					$msg = __( 'Gift card code removed successfully.', 'yith-woocommerce-gift-cards' );
					break;

				case WC_Coupon::E_WC_COUPON_NOT_EXIST :
					$msg = sprintf( __( 'Gift card code "%s" does not exist!', 'yith-woocommerce-gift-cards' ), $coupon->code );
					break;
			}

			return $msg;
		}

		/**
		 * Show my gift cards status on myaccount page
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function show_my_gift_cards_table() {
			wc_get_template( 'myaccount/my-giftcards.php',
				'',
				'',
				trailingslashit( YITH_YWGC_TEMPLATES_DIR ) );
		}

		/**
		 * Let the user to edit che gift card content
		 */
		public function edit_gift_card_callback() {

			if ( ! $this->main->allow_modification ) {
				return;
			}

			$order_item_id = intval( sanitize_text_field( $_POST['item_id'] ) );
			$gift_card_id  = intval( sanitize_text_field( $_POST['gift_card_id'] ) );
			$sender        = sanitize_text_field( $_POST['sender'] );
			$recipient     = sanitize_email( $_POST['recipient'] );
			$message       = implode( "\n", array_map( 'sanitize_text_field', explode( "\n", $_POST['message'] ) ) );

			/** Retrieve the gift card content.
			 *  If a valid gift card was generated, the content to be edited is a post meta of the gift card.
			 *  In the opposite case all the data are order item meta
			 */
			$item_gift_card_ids = ywgc_get_order_item_giftcards( $order_item_id );

			if ( in_array( $gift_card_id, $item_gift_card_ids ) ) {
				//  The gift card exists, edit it as custom post type
				$args = array( 'ID' => $gift_card_id );

				$curr_card = new YWGC_Gift_Card_Premium( $args );
				if ( $curr_card->exists() ) {

					//  Update current gift card content without saving, this card will be dismissed leaving a new gift card build as a clone from it
					$clone_it             = $recipient != $curr_card->recipient;
					$curr_card->sender    = $sender;
					$curr_card->recipient = $recipient;
					$curr_card->message   = $message;

					//  check if the recipient changes, if so, set_dismissed_status the current gift card and
					//  create a new one
					if ( $clone_it ) {

						//  The gift cards being changed will be closed and a new one will be created
						$new_gift = $this->clone_gift_card( $curr_card );
						$new_gift->save();

						$curr_card->set_dismissed_status();

						//  assign the new gift card to the order item
						$item_gift_card_ids[] = $new_gift->ID;
						ywgc_set_order_item_giftcards( $order_item_id, $item_gift_card_ids );

						wp_send_json( array(
							"code"   => 2,
							"values" => array(
								"new_id" => $new_gift->ID,
							),
						) );
					} else {

						//  update the current gift card
						$curr_card->save();

						wp_send_json( array(
							"code" => 1,
						) );
					}
				}
			} else {
				//  a gift card custom post type object doesn't exists, edit order item meta values
				$meta = wc_get_order_item_meta( $order_item_id, YWGC_ORDER_ITEM_DATA );

				//edit order item meta
				$meta["sender"]    = $sender;
				$meta["recipient"] = $recipient;
				$meta["message"]   = $message;

				wc_update_order_item_meta( $order_item_id, YWGC_ORDER_ITEM_DATA, $meta );

				wp_send_json( array(
					"code" => 1,
				) );
			}

			wp_send_json( array(
				"code" => - 1,
			) );
		}


		/**
		 * Clone a gift card with updated balance
		 *
		 * @param YWGC_Gift_Card_Premium $gift_card the initial gift card
		 *
		 * @return YWGC_Gift_Card_Premium
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function clone_gift_card( $gift_card ) {

			$code = $this->main->generate_gift_card_code();

			return $gift_card->clone_gift_card( $code );
		}


		/**
		 * Let the user to edit the gift card
		 *
		 * @param $order_item_id
		 * @param $item
		 * @param $order
		 */
		public function edit_gift_card( $order_item_id, $item, $order ) {

			if ( ! $this->main->allow_modification ) {
				return;
			}

			//  Allow editing only on checkout or my orders pages
			if ( ! is_checkout() && ! is_account_page() ) {
				return;
			}

			$item_meta_array = $item["item_meta"];
			//  Check if current order item is a gift card
			if ( ! isset( $item_meta_array[ YWGC_ORDER_ITEM_DATA ] ) ) {

				return;
			}

			//  Retrieve the gift card content. If a valid gift card was generated, the content to be edited is a postmeta of the
			//  Gift card post type, else the content is still on the order_item_meta.
			$gift_cards = ywgc_get_order_item_giftcards( $order_item_id );

			if ( $gift_cards ) {
				$_gift_card_id = is_array( $gift_cards ) ? $gift_cards[0] : $gift_cards;
				$args          = array( 'ID' => $_gift_card_id );

				//  edit values from a gift card object stored on the DB
				$gift_card = new YWGC_Gift_Card_Premium( $args );

			} else {
				//  edit the data stored as order item meta because the final gift card is not created yet
				$order_item_meta = $item_meta_array[ YWGC_ORDER_ITEM_DATA ];
				$order_item_meta = $order_item_meta[0];
				$order_item_meta = maybe_unserialize( $order_item_meta );

				$gift_card = new YWGC_Gift_Card_Premium( $order_item_meta );
			}

			//  Check if the gift card still exists
			//todo do not block the editing on gift card that are not generated yet
			if ( ! $gift_card->exists() ) {
				//return;
			}

			//  There is nothing to edit for physical gift card product, only virtual gift cards
			//  can be edited

			if ( ! $gift_card->is_virtual() ) {
				return;
			}

			?>

			<div id="current-gift-card-<?php echo $order_item_id; ?>" class="ywgc-gift-card-content">
				<a href="#"
				   class="edit-details"><?php _e( "See card details", 'yith-woocommerce-gift-cards' ); ?></a>

				<div class="ywgc-gift-card-details ywgc-hide">
					<h3><?php _e( "Gift card details", 'yith-woocommerce-gift-cards' ); ?></h3>
					<fieldset class="ywgc-sender-details" style="border: none">
						<label><?php _e( "Sender: ", 'yith-woocommerce-gift-cards' ); ?></label>
						<span class="ywgc-sender"><?php echo $gift_card->sender; ?></span>
					</fieldset>

					<fieldset class="ywgc-recipient-details" style="border: none">
						<label><?php _e( "Recipient: ", 'yith-woocommerce-gift-cards' ); ?></label>
						<span class="ywgc-recipient"><?php echo $gift_card->recipient; ?></span>
					</fieldset>

					<fieldset class="ywgc-message-details" style="border: none">
						<label><?php _e( "Message: ", 'yith-woocommerce-gift-cards' ); ?></label>
						<span class="ywgc-message"><?php echo $gift_card->message; ?></span>
					</fieldset>
					<button
						class="ywgc-do-edit btn btn-ghost"
						style="display: none;"><?php _e( "Edit", 'yith-woocommerce-gift-cards' ); ?></button>
				</div>

				<div class="ywgc-gift-card-edit-details ywgc-hide" style="display: none">
					<h3><?php _e( "Gift card details", 'yith-woocommerce-gift-cards' ); ?></h3>

					<form name="form-gift-card-<?php echo $gift_card->ID; ?>">
						<input type="hidden" name="ywgc-gift-card-id" value="<?php echo $gift_card->ID; ?>">
						<input type="hidden" name="ywgc-item-id" value="<?php echo $order_item_id; ?>">
						<fieldset>
							<label
								for="ywgc-edit-sender"><?php _e( "Sender: ", 'yith-woocommerce-gift-cards' ); ?></label>
							<input type="text" name="ywgc-edit-sender" id="ywgc-edit-sender"
							       value="<?php echo $gift_card->sender; ?>">
						</fieldset>

						<fieldset>
							<label
								for="ywgc-edit-recipient"><?php _e( "Recipient: ", 'yith-woocommerce-gift-cards' ); ?></label>
							<input type="email" name="ywgc-edit-recipient" id="ywgc-edit-recipient"
							       value="<?php echo $gift_card->recipient; ?>"">
						</fieldset>

						<fieldset>
							<label
								for="ywgc-edit-message"><?php _e( "Message: ", 'yith-woocommerce-gift-cards' ); ?></label>
							<textarea name="ywgc-edit-message" id="ywgc-edit-message"
							          rows="5"><?php echo $gift_card->message; ?></textarea>
						</fieldset>
					</form>

					<button
						class="ywgc-apply-edit btn apply"><?php _e( "Apply", 'yith-woocommerce-gift-cards' ); ?></button>
					<button
						class="ywgc-cancel-edit btn btn-ghost"><?php _e( "Cancel", 'yith-woocommerce-gift-cards' ); ?></button>
				</div>
			</div>
			<?php
		}

		/**
		 * Let the customer to use a product of type WC_Product_Simple  as source for a gift card
		 */
		public function show_give_as_present_link_simple() {
			if ( ! $this->main->allow_product_as_present ) {
				return;
			}

			global $product;
			if ( $product instanceof WC_Product_Simple ) {
				// Load the template
				wc_get_template( 'single-product/add-to-cart/give-product-as-present.php',
					'',
					'',
					trailingslashit( YITH_YWGC_TEMPLATES_DIR ) );
			}
		}

		/**
		 * Let the customer to use a product of type WC_Product_Variable  as source for a gift card
		 */
		public function show_give_as_present_link_variable() {
			if ( ! $this->main->allow_product_as_present ) {
				return;
			}

			global $product;
			if ( $product instanceof WC_Product_Variable ) {
				// Load the template
				wc_get_template( 'single-product/add-to-cart/give-product-as-present.php',
					'',
					'',
					trailingslashit( YITH_YWGC_TEMPLATES_DIR ) );
			}
		}

		/**
		 * Let the user to enter a free amount instead of choosing from the select
		 * */
		public function add_manual_amount_item() {
			global $product;


			//  Check if the current product permit free entered amount...
			if ( ! $this->is_manual_amount_allowed( $product->id ) ) {
				return;
			}

			?>
			<option value="-1"><?php _e( "Manual amount", 'yith-woocommerce-gift-cards' ); ?></option>
			<?php
		}

		/**
		 * Show a live preview of how the gift card will look like
		 */
		public function show_gift_card_generator() {
			global $product;

			if ( ( $product instanceof WC_Product_Gift_Card ) && ! $product->is_virtual() ) {
				return;
			}

			$allow_templates       = $this->can_show_template_design( $product->id );
			$allow_customer_images = $this->main->allow_custom_design;
			$allow_manual_amounts  = $this->is_manual_amount_allowed( $product->id );
			?>
			<div class="ywgc-generator">

				<?php $this->main->preview_digital_gift_cards( $product ); ?>
				<input type="hidden" name="ywgc-recipient-required"
				       value="<?php ywgc_required( $product, 'WC_Product_Gift_Card' ); ?>" />

				<div class="gift-card-content-editor variations_button">
					<?php if ( $allow_templates || $allow_customer_images ) : ?>
						<div class="gift-card-content-editor step-appearance">

							<span class="ywgc-editor-section-title">
								<?php _e( "Gift card design", 'yith-woocommerce-gift-cards' ); ?>
							</span>

							<!-- Let the user to cancel a selection, turning back to the default design -->
							<input type="button"
							       class="ywgc-choose-design ywgc-default-picture"
							       value="<?php _e( "Default image", 'yith-woocommerce-gift-cards' ); ?>" />

							<!-- Let the user to upload a file to be used as gift card main image -->
							<?php if ( $allow_customer_images ) : ?>
								<input type="button"
								       class="ywgc-choose-design ywgc-custom-picture"
								       value="<?php _e( "Customize", 'yith-woocommerce-gift-cards' ); ?>" />
								<input type="file" name="ywgc-upload-picture" id="ywgc-upload-picture"
								       accept="image/*" />
							<?php endif; ?>
							<?php if ( $allow_templates ) : ?>
								<input type="button"
								       class="ywgc-choose-design ywgc-choose-template"
								       href="#ywgc-choose-design"
								       rel="prettyPhoto[ywgc-choose-design]"
								       value="<?php _e( "Choose design", 'yith-woocommerce-gift-cards' ); ?>" />

								<input type="hidden" name="ywgc-design-type" id="ywgc-design-type" value="default" />
								<input type="hidden" name="ywgc-template-design" id="ywgc-template-design" value="-1" />
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<div class="gift-card-content-editor step-content">
						<span class="ywgc-editor-section-title">
							<?php _e( "Gift card details", 'yith-woocommerce-gift-cards' ); ?>
						</span>

						<label
							for="ywgc-recipient-email"><?php _e( "Recipient's email (*)", 'yith-woocommerce-gift-cards' ); ?></label>

						<div class="ywgc-single-recipient">
							<input type="email"
							       name="ywgc-recipient-email[]" <?php ywgc_required( $product, 'WC_Product_Gift_Card' ); ?>
							       class="ywgc-recipient" />
							<a href="#" class="remove-recipient hide-if-alone">x</a>
						</div>

						<?php
						//Only with gift card product type you can use multiplece recipients
						if ( $this->main->allow_multiple_recipients && ( $product instanceof WC_Product_Gift_Card ) ): ?>
							<a href="#" class="add-recipient"
							   id="add_recipient"><?php _e( "Add recipient", 'yith-woocommerce-gift-cards' ); ?></a>
						<?php endif; ?>
						<div class="ywgc-sender-name">
							<label
								for="ywgc-sender-name"><?php _e( "Your name (*)", 'yith-woocommerce-gift-cards' ); ?></label>
							<input type="text" name="ywgc-sender-name" id="ywgc-sender-name">
						</div>
						<div class="ywgc-message">
							<label
								for="ywgc-edit-message"><?php _e( "Message", 'yith-woocommerce-gift-cards' ); ?></label>
							<textarea id="ywgc-edit-message" name="ywgc-edit-message" rows="5"
							          placeholder="<?php _e( "Your message...", 'yith-woocommerce-gift-cards' ); ?>"></textarea>
						</div>

						<?php if ( $this->main->allow_send_later ) : ?>
							<div class="ywgc-postdate">
								<label
									for="ywgc-postdate"><?php _e( "Postpone delivery", 'yith-woocommerce-gift-cards' ); ?></label>
								<input type="checkbox" id="ywgc-postdate" name="ywgc-postdate">
								<input type="text" id="ywgc-delivery-date" name="ywgc-delivery-date"
								       class="datepicker hidden">
							</div>
						<?php endif; ?>

						<?php if ( ! ( $product instanceof WC_Product_Gift_Card ) ): ?>
							<input type="hidden" name="ywgc-as-present" value="1">
						<?php endif; ?>
					</div>

					<?php do_action('yith_ywgc_generator_buttons_before', $product); ?>
					<?php if ( ! ( $product instanceof WC_Product_Gift_Card ) ): ?>
						<button id="ywgc-cancel-gift-card"
						        class="button"><?php _e( "Cancel", 'yith-woocommerce-gift-cards' ); ?></button>
					<?php endif; ?>

				</div>
			</div>
			<?php
			if ( $allow_templates ) {
				$this->show_template_design();
			}
		}

		/**
		 * Permit to enter free gift card amount
		 *
		 * @param WC_Product_Gift_Card $product
		 */
		public function show_free_amount_area( $product ) {
			$amounts  = $product->get_amounts_to_be_shown();
			$hide_css = count( $amounts ) ? 'hidden' : '';

			?>
			<input id="ywgc-manual-amount" name="ywgc-manual-amount"
			       class="ywgc-manual-amount <?php echo $hide_css; ?>" type="text"
			       placeholder="<?php _e( "Enter amount(Only digits)", 'yith-woocommerce-gift-cards' ); ?>">
			<?php
		}

		public function show_cart_message_on_added_product( $product_id, $quantity = 1	) {
			//  From WC 2.6.0 the parameter format in wc_add_to_cart_message changed
			$gt_255 = version_compare(WC()->version, '2.5.5', '>');
			$param = $gt_255 ? array( $product_id => $quantity ) : $product_id;
			wc_add_to_cart_message( $param, true );
		}
		/**
		 * Custom add_to_cart handler for gift card product type
		 */
		public function add_to_cart_handler() {


			//  If it's a digital gift card, check the data submitted, else create a gift card without content
			if ( isset( $_POST["ywgc-sender-name"] ) ) {

				//  Digital gift card
				if ( empty( $_POST["ywgc-sender-name"] ) ) {
					wc_add_notice( __( 'You have to add the sender\'s name', 'yith-woocommerce-gift-cards' ), 'error' );

					return false;
				}

				$recipient_list  = $_POST["ywgc-recipient-email"];
				$recipient_count = count( $recipient_list );

				if ( ! $recipient_count ) {
					wc_add_notice( __( 'Add a valid email for the recipient', 'yith-woocommerce-gift-cards' ), 'error' );

					return false;
				}

				if ( $recipient_count > 1 ) {
					$quantity = $recipient_count;
				} else {
					$quantity = isset( $_REQUEST['quantity'] ) ? intval( $_REQUEST['quantity'] ) : 1;
				}

				$product_id = 0;
				$no_errors  = true;
				for ( $i = 0; $i < $quantity; $i ++ ) {

					$gift_card = $this->main->create_gift_card();

					//  Set the real recipient for the gift card
					if ( $recipient_count > 1 ) {
						$gift_card->recipient = sanitize_email( $recipient_list[ $i ] );
					} else {
						$gift_card->recipient = sanitize_email( $recipient_list[0] );
					}

					$product_id = $gift_card->product_id;
					$product    = wc_get_product( $product_id );

					//if ( $gift_card != null ) {

					if ( false === WC()->cart->add_to_cart( $product_id, 1, 0, array(), (array) $gift_card ) ) {
						$no_errors = false;
						break;
					}
					//}
				}

				if ( ! $product_id ) {
					wc_add_notice( __( 'An error occurred while adding the product to the cart.', 'yith-woocommerce-gift-cards' ), 'error' );

					return false;
				}

				if ( $no_errors ) {
					$this->show_cart_message_on_added_product($product_id, $quantity);

				}
			} else {


				//  Physical gift card
				$product_id = absint( $_POST['add-to-cart'] );
				$product    = wc_get_product( $product_id );
				if ( $product ) {

					$quantity = isset( $_REQUEST['quantity'] ) ? intval( $_REQUEST['quantity'] ) : 1;

					if ( ! isset( $_POST['gift_amounts'] ) && !isset($_POST['ywgc-manual-amount']) ) {
						return false;
					}

					$amount = ! isset( $_POST['gift_amounts'] ) || ( "-1" == $_POST['gift_amounts'] ) ?
						number_format( (float) $_POST['ywgc-manual-amount'], wc_get_price_decimals(), '.', '' ) :
						$_POST['gift_amounts'];

					$no_errors = true;

					$gift_card = new YWGC_Gift_Card_Premium();

					$gift_card->set_amount( $amount, 0 );
					$gift_card->product_id = $product_id;
					$gift_card->order_id   = 0;


					//if ( $gift_card != null ) {
					if ( false === WC()->cart->add_to_cart( $product_id, $quantity, 0, array(), (array) $gift_card ) ) {
						$no_errors = false;
					}
					//}

					if ( ! $product_id ) {

						wc_add_notice( __( 'An error occurred while adding the product to the cart.', 'yith-woocommerce-gift-cards' ), 'error' );

						return false;
					}

					if ( $no_errors ) {
						$this->show_cart_message_on_added_product($product_id, $quantity );
					}
				}
			}

			// If we added the product to the cart we can now optionally do a redirect.
			if ( wc_notice_count( 'error' ) === 0 ) {

				$url = '';
				// If has custom URL redirect there
				if ( $url = apply_filters( 'woocommerce_add_to_cart_redirect', $url ) ) {
					wp_safe_redirect( $url );
					exit;
				} elseif ( get_option( 'woocommerce_cart_redirect_after_add' ) === 'yes' ) {
					if ( function_exists( 'wc_get_cart_url' ) ) {
						wp_safe_redirect( wc_get_cart_url() );
					} else {
						wp_safe_redirect( WC()->cart->get_cart_url() );
					}
					exit;
				}
			}
		}

		/**
		 * Show a dropdown for selecting the amount
		 *
		 * @param WC_Product_Gift_Card $product
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function show_gift_cards_amount_dropdown( $product ) {
			$amounts = $product->get_amounts_to_be_shown();
			if ( $amounts ) :
				?>
				<select id="gift_amounts" name="gift_amounts">
					<!--					<option value="">--><?php //_e( "Choose an amount", 'yith-woocommerce-gift-cards' );
					?><!--</option>-->

					<?php foreach ( $product->get_amounts_to_be_shown() as $value => $price ) : ?>

						<option
							value="<?php echo $value; ?>" <?php echo selected( $price, $value, false ); ?>><?php echo wc_price( $price ); ?></option>
					<?php endforeach; ?>

					<?php do_action( 'yith_gift_cards_template_append_amount' ); ?>

				</select>
				<?php
			endif;

			do_action( 'yith_gift_cards_template_after_amounts', $product );
		}

		/**
		 * Check if a gift card product avoid entering manual amount value
		 *
		 * @param $product_id int the product id to check
		 *
		 * @return bool
		 */
		public function is_manual_amount_allowed( $product_id ) {
			$manual_amount = $this->main->get_manual_amount_allowed_status( $product_id );

			//  if the gift card have specific manual entered amount behavour, return that
			if ( "global" != $manual_amount ) {
				return "accept" == $manual_amount;
			}

			return $this->main->allow_manual_amount;
		}

		/**
		 * Retrieve if the templates design should be shown for the product
		 *
		 * @param int $product_id the product id
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function can_show_template_design( $product_id ) {
			if ( ! $this->is_template_design_allowed( $product_id ) ) {
				return false;
			}

			//  If template design are allowed, show it (if there are at least one!)
			return $this->template_design_count();
		}

		/**
		 * Retrieve the number of templates available
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function template_design_categories_count() {
			$media_terms = get_terms( YWGC_CATEGORY_TAXONOMY, array( 'hide_empty' => 1 ) );

			return count( $media_terms );
		}

		/**
		 * Retrieve the number of templates available
		 *
		 * @author Lorenzo Giuffrida
		 * @since  1.0.0
		 */
		public function template_design_count() {
			$media_terms = get_terms( YWGC_CATEGORY_TAXONOMY, array( 'hide_empty' => 1 ) );
			$ids         = array();
			foreach ( $media_terms as $media_term ) {
				$ids[] = $media_term->term_id;
			}

			$template_ids = array_unique( get_objects_in_term( $ids, YWGC_CATEGORY_TAXONOMY ) );

			return count( $template_ids );
		}

		/**
		 * Check if a gift card product permit to choose from a custom template design
		 *
		 * @param $product_id int the product id to check
		 *
		 * @return bool
		 */
		public function is_template_design_allowed( $product_id ) {
			$show_templates = $this->main->get_template_design_status( $product_id );

			//  If the product have a custom status related to the use of template design, return that settings
			if ( "enabled" == $show_templates ) {
				return true;
			}

			if ( "disabled" == $show_templates ) {
				return false;
			}

			//  If there isn't a custom status, retrieve the global settings

			return $this->main->allow_template_design;
		}

		public function show_template_design() {
			$media_terms     = get_terms( YWGC_CATEGORY_TAXONOMY, array( 'hide_empty' => 1 ) );
			$item_categories = array();
			foreach ( $media_terms as $item ) {
				$object_ids = get_objects_in_term( $item->term_id, YWGC_CATEGORY_TAXONOMY );
				foreach ( $object_ids as $object_id ) {
					$item_categories[ $object_id ] = isset( $item_categories[ $object_id ] ) ? $item_categories[ $object_id ] . ' ywgc-category-' . $item->term_id : 'ywgc-category-' . $item->term_id;
				}
			}
			?>
			<div id="ywgc-choose-design" class="ywgc-template-design" style="display: none">
				<div>
					<?php if ( $this->template_design_categories_count() > 1 ): ?>
						<ul class="ywgc-template-categories">
							<li class="ywgc-template-item ywgc-category-all">
								<a href="#" class="ywgc-show-category ywgc-category-selected"
								   data-category-id="all">
									<?php _e( "Show all design", 'yith-woocommerce-gift-cards' ); ?>
								</a>
							</li>
							<?php foreach ( $media_terms as $item ): ?>
								<li class="ywgc-template-item ywgc-category-<?php echo $item->term_id; ?>">
									<a href="#" class="ywgc-show-category"
									   data-category-id="ywgc-category-<?php echo $item->term_id; ?>">
										<?php echo $item->name; ?>
									</a>
								</li>

							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<div class="ywgc-design-list">

						<?php foreach ( $item_categories as $itemid => $categories ): ?>

							<div class="ywgc-design-item <?php echo $categories; ?> template-<?php echo $itemid; ?>">

								<?php echo wp_get_attachment_image( intval( $itemid ), 'shop_catalog' ); ?>
								<button class="ywgc-choose-template"
								        data-design-id="<?php echo $object_id; ?>"
								        data-design-url="<?php echo wp_get_attachment_image_url( intval( $itemid ), 'full' ); ?>"><?php _e( "Choose design", 'yith-woocommerce-gift-cards' ); ?></button>
							</div>

						<?php endforeach; ?>

					</div>
				</div>
			</div>
			<?php
		}
	}
}