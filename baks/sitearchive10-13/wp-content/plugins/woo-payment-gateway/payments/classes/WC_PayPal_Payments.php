<?php
if (! defined ( 'ABSPATH' )) {
	exit (); // Exit if accessed directly
}
/**
 * PayPal specific class to be used when merchants are using a custom payment form.
 * PayPal
 * will be treated as a separate gateway.
 *
 * @author Clayton Rogers
 * @since 2.4.3
 * @version 2.4.3
 *         
 */
class WC_PayPal_Payments extends WC_Braintree_Payments {
	public $supports = array (
			'subscriptions',
			'products',
			'subscription_cancellation',
			'multiple_subscriptions',
			'subscription_amount_changes',
			'subscription_date_changes',
			'default_credit_card_form',
			'refunds',
			'pre-orders',
			'subscription_payment_method_change_admin',
			'subscription_reactivation',
			'subscription_suspension',
			'subscription_payment_method_change_customer' 
	);
	public static function init() {
		add_filter ( 'woocommerce_payment_gateways', __CLASS__ . '::add_gateway' );
		parent::init ();
	}
	public function __construct() {
		$this->enabled = self::is_gateway_active () ? 'yes' : 'no';
		$this->id = self::paypal_gateway_name;
		$this->title = BT_Manager ()->get_option ( 'paypal_gateway_title' );
		$this->has_fields = true;
		$this->method_title = __ ( 'PayPal Braintree Payment Gateway', 'braintree' );
		add_action ( 'wp_enqueue_scripts', __CLASS__ . '::load_scripts' );
	}
	public static function is_gateway_active() {
		return (BT_Manager()->isActive('enabled') && BT_Manager ()->isActive ( 'custom_form' ) && BT_Manager ()->isActive ( 'enable_paypal' )) || BT_Manager ()->isActive ( 'paypal_only' );
	}
	public static function load_scripts() {
		if (! is_order_received_page ()) {
			if (BT_Manager ()->isActive ( 'enable_paypal' ) || BT_Manager ()->isActive ( 'paypal_only' )) {
				wp_enqueue_script ( 'paypal-component', BRAINTREE_PAYPAL_JS, array (
						'wc-checkout' 
				), BT_Manager ()->version, true );
				wp_enqueue_script ( 'paypal-braintree-checkout', WC_BRAINTREE_ASSETS . 'js/paypal-checkout.js', array (
						'wc-checkout' 
				), BT_Manager ()->version, true );
				$paypal_design = BT_Manager ()->get_paypal_design ();
				wp_enqueue_style ( 'paypal-design-css', $paypal_design ['css'], array (), BT_Manager ()->version );
				wp_localize_script ( 'paypal-braintree-checkout', 'braintree_paypal_checkout', self::get_checkout_params () );
				if (BT_Manager ()->isActive ( 'paypal_only' )) {
					wp_enqueue_script ( 'braintree-V3-component', BRAINTREE_V3_JS, array (
							is_checkout () ? 'wc-checkout' : 'jquery' 
					), BT_Manager ()->version, true );
					wp_enqueue_style ( 'braintree-payments-style', WC_BRAINTREE_ASSETS . 'css/braintree-payments.css', array (), BT_Manager ()->version );
				}
			}
		}
	}
	public static function get_checkout_params() {
		$params = array (
				'paypal_html' => '<div class="paypal-payment-method"><span class="payment-method-type paypal"></span><span class="description">%s</span><span id="cancel_paypal_payment">' . __ ( 'Cancel', 'braintree' ) . '</span></div>',
				'order_total' => WC ()->cart->total,
				'checkout_flow' => BT_Manager ()->get_option ( 'paypal_checkout_flow' ),
				'currency' => get_woocommerce_currency (),
				'display_name' => BT_Manager ()->get_option ( 'paypal_display_name' ),
				'checkout_page' => is_checkout () && ! is_order_received_page () ? 'true' : 'false',
				'order_received_page' => is_order_received_page () ? 'true' : 'false' 
		);
		if (BT_Manager ()->subscriptionsActive ()) {
			$params ['cart_contains_subscription'] = WC_Subscriptions_Cart::cart_contains_subscription () ? 'true' : 'false';
			$params ['checkout_flow'] = WC_Subscriptions_Cart::cart_contains_subscription () || self::isPaymentChangeRequest () ? 'vault' : $params ['checkout_flow'];
		}
		if (BT_Manager ()->isActive ( 'paypal_only' )) {
			$params ['client_token'] = BT_Manager ()->getClientToken ();
		}
		return $params;
	}
	
	/**
	 * Display the PayPal specific payment fields.
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Braintree_Payments::payment_fields()
	 */
	public function payment_fields() {
		if (is_checkout ()) {
			include WC_BRAINTREE_PLUGIN . 'payments/forms/paypal/paypal-form.php';
		}
	}
	
	/**
	 * Add the PayPal payment gateway to the array of methods.
	 *
	 * @param array $methods        	
	 */
	public static function add_gateway($methods = array()) {
		if ((BT_Manager ()->isActive ( 'custom_form' ) && BT_Manager ()->isActive ( 'enable_paypal' )) || BT_Manager ()->isActive ( 'paypal_only' )) {
			$methods [] = 'WC_PayPal_Payments';
		}
		return $methods;
	}
}
WC_PayPal_Payments::init ();