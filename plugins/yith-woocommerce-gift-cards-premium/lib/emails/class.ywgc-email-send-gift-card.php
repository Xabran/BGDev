<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( "WC_Email" ) ) {
	require_once( WC()->plugin_path() . '/includes/emails/class-wc-email.php' );
}

if ( ! class_exists( "YWGC_Email_Send_Gift_Card" ) ) {
	/**
	 * Create and send a digital gift card to the specific recipient
	 *
	 * @since 0.1
	 * @extends \WC_Email
	 */
	class YWGC_Email_Send_Gift_Card extends WC_Email {
		/**
		 * An introductional message from the shop owner
		 */
		public $introductory_text;

		/**
		 * Set email defaults
		 *
		 * @since 0.1
		 */
		public function __construct() {
			// set ID, this simply needs to be a unique name
			$this->id = 'ywgc-email-send-gift-card';

			// this is the title in WooCommerce Email settings
			$this->title = __( "YITH Gift Cards - Dispatch of the coupon", 'yith-woocommerce-gift-cards' );

			// this is the description in WooCommerce email settings
			$this->description = __( 'Send the digital gift card to the email address selected during the purchase', 'yith-woocommerce-gift-cards' );

			// these are the default heading and subject lines that can be overridden using the settings
			$this->heading = __( 'Your gift card', 'yith-woocommerce-gift-cards' );
			$this->subject = __( '[{site_title}] You have received a gift card', 'yith-woocommerce-gift-cards' );

			// these define the locations of the templates that this email should use, we'll just use the new order template since this email is similar
			$this->template_html  = 'emails/send-gift-card.php';
			$this->template_plain = 'emails/plain/send-gift-card.php';

			$this->introductory_text = __( 'You have received this gift card from <b>{sender}</b>, use it on our online shop.', 'yith-woocommerce-gift-cards' );

			// Trigger on specific action call
			add_action( 'ywgc-email-send-gift-card_notification',
				array(
					$this,
					'trigger'
				) );

			parent::__construct();
			$this->email_type = "html";
		}

		/**
		 * Send the digital gift card to the recipient
		 *
		 * @param int|YWGC_Gift_Card_Premium|YWGC_Gift_Card $object it's the order id or the gift card id or the gift card instance to be sent
		 *
		 * @return bool|void
		 */
		public function trigger( $object ) {

			if ( is_numeric( $object ) ) {
				$post_type = get_post_type( $object );

				if ( 'shop_order' == $post_type ) {

					$gift_ids = array();
					//  Check for order item that belong to a gift card and send them individually
					$the_order = wc_get_order( $object );

					foreach ( $the_order->get_items( 'line_item' ) as $order_item_id => $order_item_data ) {

						$product_id = $order_item_data["product_id"];
						$product    = wc_get_product( $product_id );

						//  skip all item that belong to product other than the gift card type
						if ( ! $product instanceof WC_Product_Gift_Card ) {

							continue;
						}

						//  Check if current product, of type gift card, has a discount code associated to it
						//array_push ( $gift_ids , ywgc_get_order_item_giftcards ( $order_item_id ) );
						$gift_ids = array_merge( $gift_ids, ywgc_get_order_item_giftcards( $order_item_id ) );
					}

					//  How much gift cards associated to this order item?

					if ( $gift_ids ) {
						// Trigger an email for every gift card associated
						foreach ( $gift_ids as $gift_id ) {
							$this->trigger( $gift_id );
						}

						//  Done! Stop here.
						return true;
					}

				} else if ( YWGC_CUSTOM_POST_TYPE_NAME == $post_type ) {
					$args   = array( 'ID' => $object );
					$object = new YWGC_Gift_Card_Premium( $args );
				} else {
					return false;
				}
			}

			if ( ! ( $object instanceof YWGC_Gift_Card_Premium ) ) {
				return false;
			}

			if ( ! $object->exists() ) {
				return false;
			}

			$this->object    = $object;
			$this->recipient = $object->recipient;

			$this->introductory_text = $this->get_option( 'introductory_text', __( 'You have received this gift card from <b>{sender}</b>, use it on our online shop.', 'yith-woocommerce-gift-cards' ) );

			$this->introductory_text = str_replace(
				array( "{sender}" ),
				array( $this->object->sender ),
				$this->introductory_text
			);

			$result = $this->send( $this->get_recipient(),
				$this->get_subject(),
				$this->get_content(),
				apply_filters( 'ywgc_gift_card_code_email_bcc', $this->get_headers() ),
				$this->get_attachments() );

			if ( $result ) {
				//  Set the gift card as sent
				$object->set_as_sent();
			}

			return $result;
		}

		/**
		 * get_content_html function.
		 *
		 * @since 0.1
		 * @return string
		 */
		public function get_content_html() {
			ob_start();
			wc_get_template( $this->template_html, array(
				'gift_card'         => $this->object,
				'introductory_text' => $this->introductory_text,
				'email_heading'     => $this->get_heading(),
				'email_type'        => $this->email_type,
				'sent_to_admin'     => false,
				'plain_text'        => false,
				'email'             => $this,
			),
				'',
				YITH_YWGC_TEMPLATES_DIR );

			return ob_get_clean();
		}


		/**
		 * Initialize Settings Form Fields
		 *
		 * @since 0.1
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled'           => array(
					'title'   => __( 'Enable/Disable', 'woocommerce' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable this email notification', 'woocommerce' ),
					'default' => 'yes',
				),
				'subject'           => array(
					'title'       => __( 'Subject', 'woocommerce' ),
					'type'        => 'text',
					'description' => sprintf( __( 'Defaults to <code>%s</code>', 'woocommerce' ), $this->subject ),
					'placeholder' => '',
					'default'     => '',
				),
				'heading'           => array(
					'title'       => __( 'Email Heading', 'woocommerce' ),
					'type'        => 'text',
					'description' => sprintf( __( 'Defaults to <code>%s</code>', 'woocommerce' ), $this->heading ),
					'placeholder' => '',
					'default'     => '',
				),
				'introductory_text' => array(
					'title'       => __( 'Introductive message', 'yith-woocommerce-gift-cards' ),
					'type'        => 'textarea',
					'description' => sprintf( __( 'Defaults to <code>%s</code>', 'woocommerce' ), $this->introductory_text ),
					'placeholder' => '',
					'default'     => '',
				),
			);
		}
	} // end \YWGC_Email_Send_Gift_Card class
}

return new YWGC_Email_Send_Gift_Card();