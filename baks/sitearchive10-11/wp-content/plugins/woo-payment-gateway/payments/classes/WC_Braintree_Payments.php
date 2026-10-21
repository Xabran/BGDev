<?php
if (! defined ( 'ABSPATH' )) {
	exit (); // Exit if accessed directly
}
/**
 *
 * @author Clayton Rogers
 * @copyright Payment Plugins
 * @since 3/14/2016
 */
class WC_Braintree_Payments extends WC_Payment_Gateway {
	const gatewayName = 'braintree_payment_gateway';
	const paypal_gateway_name = 'braintree_paypal_payments';
	public $supports = array (
			'subscriptions',
			'products',
			'add_payment_method',
			'subscription_cancellation',
			'multiple_subscriptions',
			'subscription_amount_changes',
			'subscription_date_changes',
			'default_credit_card_form',
			'refunds',
			'pre-orders',
			'subscription_payment_method_change_admin',
			// 'gateway_scheduled_payments',
			'subscription_reactivation',
			'subscription_suspension',
			'subscription_payment_method_change_customer' 
	);
	
	/**
	 * Initialize any data needed for payment processing with Braintree
	 */
	public function __construct() {
		$this->enabled = BT_Manager ()->isEnabled ();
		$this->id = self::gatewayName;
		$this->title = $this->get_option ( 'title_text' );
		$this->has_fields = true;
		$this->method_title = __ ( 'Braintree Payment Gateway', 'braintree' );
		add_action ( 'wp_enqueue_scripts', __CLASS__ . '::loadScripts' );
		add_filter ( 'process_braintree_woocommerce_order', array (
				$this,
				'process_braintree_payment' 
		) );
	}
	public static function init() {
		add_filter ( 'woocommerce_payment_gateways', __CLASS__ . '::addGateway' );
		add_action ( 'woocommerce_before_checkout_form', __CLASS__ . '::maybeCreateBraintreeCustomer', 20 );
		add_action ( 'woocommerce_before_checkout_form', __CLASS__ . '::loadCustomerPaymentMethods', 40 );
		add_action ( 'woocommerce_created_customer', __CLASS__ . '::woocommerce_created_customer', 99, 2 );
		add_filter ( 'woocommerce_checkout_customer_userdata', __CLASS__ . '::updateBraintreeCustomer', 20, 2 );
		add_filter ( 'woocommerce_saved_payment_methods_list', __CLASS__ . '::populatePaymentMethodsArray' );
		add_action ( 'woocommerce_account_payment-methods_endpoint', __CLASS__ . '::maybeDeletePaymentMethod' );
		add_filter ( 'woocommerce_order_actions', __CLASS__ . '::woocommerce_order_actions' );
		add_action ( 'woocommerce_order_action_submit_for_settlement', __CLASS__ . '::submit_order_for_settlement' );
		add_action ( 'woocommerce_order_action_braintree_void_transaction', __CLASS__ . '::void_transaction' );
		add_action ( 'user_register', __CLASS__ . '::user_register', 10, 1 );
		add_action ( 'profile_update', __CLASS__ . '::profile_update', 10, 2 );
	}
	public static function loadScripts() {
		if (BT_Manager ()->isActive ( 'enabled' )) {
			wp_enqueue_style ( 'braintree-payments-style', WC_BRAINTREE_ASSETS . 'css/braintree-payments.css', array (), BT_Manager ()->version );
			if (BT_Manager ()->isActive ( 'custom_form' ) && ! is_order_received_page ()) {
				self::enqueue_custom_form_styles ();
				self::enqueue_custom_form_scripts ();
				wp_enqueue_script ( 'braintree-V3-component', BRAINTREE_V3_JS, array (
						is_checkout () ? 'wc-checkout' : 'jquery' 
				), BT_Manager ()->version, true );
				wp_enqueue_script ( 'braintree-V3-hosted-fields', BRAINTREE_V3_HOSTED_JS, array (
						is_checkout () ? 'wc-checkout' : 'jquery' 
				), BT_Manager ()->version, true );
				wp_enqueue_script ( 'braintree-custom-form-checkout', WC_BRAINTREE_ASSETS . 'js/custom-form-checkout.js', array (
						is_checkout () ? 'wc-checkout' : 'jquery' 
				), BT_Manager ()->version, true );
				wp_localize_script ( 'braintree-custom-form-checkout', 'braintree_custom_form_options', self::localize_custom_form_options () );
				if (is_checkout () && BT_Manager ()->isActive ( '3ds_enabled' )) { // add 3d secure if checkout page and 3ds enabled.
					wp_enqueue_script ( 'braintree-3d-secure', BRAINTREE_3DS_JS, null, true );
					wp_enqueue_script ( '3ds-spin-script', WC_BRAINTREE_ASSETS . 'js/spin.min.js', null, BT_Manager ()->version, true );
				}
			} else {
				if (is_checkout () && ! is_order_received_page ()) {
					wp_enqueue_script ( 'braintree-dropin', BRAINTREE_DROPIN_JS, null, true );
					wp_enqueue_script ( 'braintree-dropin-checkout', WC_BRAINTREE_ASSETS . 'js/dropin-checkout.js', array (
							'wc-checkout' 
					), BT_Manager ()->version, true );
					wp_localize_script ( 'braintree-dropin-checkout', 'braintree_dropin_form_options', self::localize_dropin_form_options () );
				} else if (is_add_payment_method_page ()) {
					wp_enqueue_script ( 'braintree-add-payment-method-script', WC_BRAINTREE_ASSETS . 'js/add-payment-method.js', array (
							'jquery' 
					), BT_Manager ()->version, true );
					wp_enqueue_script ( 'braintree-dropin', BRAINTREE_DROPIN_JS, null, true );
				}
			}
		}
	}
	
	/**
	 * Enqueue all css files related to the custom form.
	 */
	public static function enqueue_custom_form_styles() {
		$custom_form_key = BT_Manager ()->get_option ( 'custom_form_design' );
		$custom_form = BT_Manager ()->get_custom_forms () [$custom_form_key];
		wp_enqueue_style ( 'braintree-custom-form-style', $custom_form ['css'], array (), BT_Manager ()->version );
		if (isset ( $custom_form ['external_css'] ) && ! empty ( $custom_form ['external_css'] )) {
			wp_enqueue_style ( 'braintree-custom-form-external-style', $custom_form ['external_css'], array (
					'braintree-custom-form-style' 
			), BT_Manager ()->version );
		}
	}
	public static function enqueue_custom_form_scripts() {
		$custom_form_key = BT_Manager ()->get_option ( 'custom_form_design' );
		$custom_form = BT_Manager ()->get_custom_forms () [$custom_form_key];
		if (isset ( $custom_form ['javascript'] ) && ! empty ( $custom_form ['javascript'] )) {
			wp_enqueue_script ( 'braintree-custom-form-script', $custom_form ['javascript'], array (
					'braintree-custom-form-checkout' 
			), BT_Manager ()->version, true );
		}
	}
	
	/**
	 * Add the payment gateway to the list of accepted gateways.
	 *
	 * @param array $methods        	
	 */
	public static function addGateway($methods = array()) {
		if (! BT_Manager ()->isActive ( 'paypal_only' )) {
			$methods [] = 'WC_Braintree_Payments';
		}
		return $methods;
	}
	
	/**
	 * Process the payment for the order.
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Payment_Gateway::process_payment()
	 */
	public function process_payment($order_id) {
		return apply_filters('process_braintree_woocommerce_order', $order_id);
	}
	
	public function process_braintree_payment($order_id){
		$order = wc_get_order ( $order_id );
		if (self::isPaymentChangeRequest ()) {
			return array (
					'result' => 'success',
					'redirect' => $order->get_checkout_order_received_url ()
			);
		}
		if (self::subscriptionsActive () && $this->isSubscription ( $order_id )) {
			return $this->processSubscription ( $order_id );
		} elseif (BT_Manager ()->isActive ( 'braintree_subscriptions' ) && WC_Braintree_Subscriptions::orderContainsBraintreeSubscription ( $order_id )) {
			return WC_Braintree_Subscriptions::createBraintreeOnlySubscriptions ( wc_get_order ( $order_id ) );
		} else {
			$attribs = self::createOrderAttributes ( $order_id );
				
			/* Create the payment method in the vault if customer has selected to save the payment method. */
			if (self::getRequestParameter ( 'save_payment_method' )) {
				$response = BT_Manager ()->createBraintreePaymentMethod ( array (
						'paymentMethodNonce' => BT_Manager ()->getRequestParameter ( 'payment_method_nonce' ),
						'customerId' => BT_Manager ()->getBraintreeCustomerId ( wp_get_current_user ()->ID ),
						'billingAddress' => array (
								'countryCodeAlpha2' => $order->billing_country,
								'firstName' => $order->billing_first_name,
								'lastName' => $order->billing_last_name,
								'postalCode' => $order->billing_postcode,
								'streetAddress' => $order->billing_address_1
						),
						'options' => array (
								'failOnDuplicatePaymentMethod' => BT_Manager ()->isActive ( 'fail_on_duplicate' ) ? true : false,
								'makeDefault' => true
						)
				) );
				if ($response->success) {
					$attribs ['paymentMethodToken'] = $response->paymentMethod->token;
				} else {
					return self::wooCoomerceError ( $response->message );
				}
			}
				
			$result = BT_Manager ()->woocommerceSale ( $attribs, wc_get_order ( $order_id ) );
			if ($result ['result'] === 'success') {
				WC ()->cart->empty_cart ();
			}
			return $result;
		}
	}
	
	/**
	 * Retrieve the payment fields to be displayed on the checkout page.
	 * If the request is for a payment method change,
	 * render the payment methods form.
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Payment_Gateway::payment_fields()
	 */
	public function payment_fields() {
		if (is_checkout ()) {
			if (BT_Manager ()->isActive ( 'custom_form' )) {
				include WC_BRAINTREE_PLUGIN . 'payments/forms/custom-payment-form.php';
			} else {
				include WC_BRAINTREE_PLUGIN . 'payments/forms/dropin-payment-form.php';
			}
		} elseif (is_add_payment_method_page ()) {
			if (BT_Manager ()->isActive ( 'custom_form' )) {
				include WC_BRAINTREE_PLUGIN . 'payments/forms/custom-form-add-payment-method.php';
			} else {
				include WC_BRAINTREE_PLUGIN . 'payments/forms/dropin-add-payment-method.php';
			}
		}
	}
	
	/**
	 * Process the refund for the given $order_id.
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Payment_Gateway::process_refund()
	 */
	public function process_refund($order_id, $amount = null, $reason = '') {
		if (! $this->canRefundOrder ( $order_id )) {
			BT_Manager ()->log->writeToLog ( sprintf ( 'Order %s cannot be refunded because there is 
					not a transaction associated with the order. If this order is associated with a subscription
					that has not processed a payment yet, then there will be no transaction available until the subscription payment is processed. .', $order_id ) );
			return new WP_Error ( 404, __ ( 'The order cannot be refunded because there is no transaction ID associated with the order. Check the debug log for more details.', 'braintree' ) );
		}
		try {
			return BT_Manager ()->refund ( $order_id, $amount, $reason );
		} catch ( Exception $e ) {
			return new WP_Error ( 404, $e->getMessage () );
		}
	}
	
	/**
	 * Add the payment method to the Braintree vault.
	 */
	public function add_payment_method() {
		$nonce = BT_Manager ()->getRequestParameter ( 'payment_method_nonce' );
		$user_id = wp_get_current_user ()->ID;
		try {
			$response = Braintree_PaymentMethod::create ( array (
					'customerId' => BT_Manager ()->getBraintreeCustomerId ( $user_id ),
					'paymentMethodNonce' => $nonce,
					'options' => array (
							'failOnDuplicatePaymentMethod' => BT_Manager ()->isActive ( 'fail_on_duplicate' ) ? true : false,
							'makeDefault' => true 
					) 
			) );
			if (! $response->success) {
				throw new Exception ( $response->message );
			}
		} catch ( Exception $e ) {
			BT_Manager ()->log->writeErrorToLog ( $e->getMessage () );
			wc_add_notice ( sprintf ( __ ( 'There was an error adding your payment method. Reason: %s', 'braintree' ), $e->getMessage () ), 'error' );
			return;
		}
		return array (
				'result' => 'success',
				'redirect' => wc_get_endpoint_url ( 'payment-methods' ) 
		);
	}
	
	/**
	 * Create the Braintree Customer if none exists for the user.
	 */
	public static function maybeCreateBraintreeCustomer() {
		$user_id = wp_get_current_user ()->ID;
		if ($user_id) {
			$customerId = BT_Manager ()->getBraintreeCustomerId ( $user_id );
			if (empty ( $customerId )) {
				$attribs = array ();
				$attribs ['firstName'] = get_user_meta ( $user_id, 'first_name', true );
				$attribs ['lastName'] = get_user_meta ( $user_id, 'last_name', true );
				$attribs ['email'] = wp_get_current_user ()->user_email;
				$attribs ['phone'] = get_user_meta ( $user_id, 'billing_phone', true );
				BT_Manager ()->createBraintreeCustomer ( $user_id, $attribs );
			}
		}
	}
	
	/**
	 * Create the Braintree user when the registration is performed for WooCommerce.
	 * If the user already has a Braintree
	 * customer_id then don't perform the create.
	 *
	 * @param int $customer_id        	
	 * @param array $new_customer_data        	
	 */
	public static function woocommerce_created_customer($user_id, $new_customer_data) {
		$customer_id = BT_Manager ()->getBraintreeCustomerId ( $user_id );
		if (! $customer_id) {
			$attribs = array ();
			$attribs ['email'] = $new_customer_data ['user_email'];
			$attribs['firstName'] = get_user_meta ( $user_id, 'first_name', true );
			$attribs['lastName'] = get_user_meta ( $user_id, 'last_name', true );
			BT_Manager ()->createBraintreeCustomer ( $user_id, $attribs );
		}
		return true;
	}
	
	/**
	 * When a user is registered, create them in the database.
	 *
	 * @param unknown $user_id        	
	 */
	public static function user_register($user_id) {
		$customer_id = BT_Manager ()->getBraintreeCustomerId ( $user_id );
		if (! $customer_id) {
			$user = get_user_by ( 'id', $user_id );
			$attribs = array (
					'firstName' => get_user_meta ( $user_id, 'first_name', true ),
					'lastName' => get_user_meta ( $user_id, 'last_name', true ),
					'email' => $user->user_email 
			);
			BT_Manager ()->createBraintreeCustomer ( $user_id, $attribs );
		}
	}
	public static function profile_update($user_id, $old_data) {
		$customer_id = BT_Manager ()->getBraintreeCustomerId ( $user_id );
		if (! $customer_id) {
			self::user_register ( $user_id );
		} else {
			$user = get_user_by ( 'id', $user_id );
			BT_Manager ()->updateBraintreeCustomer ( $user_id, array (
					'firstName' => get_user_meta ( $user_id, 'first_name', true ),
					'lastName' => get_user_meta ( $user_id, 'last_name', true ),
					'email' => $user->user_email 
			) );
		}
	}
	
	/**
	 *
	 * @param array $userdata        	
	 * @param WC_Checkout $wc_checkout        	
	 */
	public static function updateBraintreeCustomer($userdata, WC_Checkout $wc_checkout) {
		$attribs = array (
				'firstName' => $userdata ['first_name'],
				'lastName' => $userdata ['last_name'] 
		);
		if (! empty ( $wc_checkout->posted ['billing_email'] )) {
			$attribs ['email'] = $wc_checkout->posted ['billing_email'];
		}
		BT_Manager ()->updateBraintreeCustomer ( $userdata ['ID'], $attribs );
		return $userdata;
	}
	public function get_option($option, $default = null) {
		return BT_Manager ()->get_option ( $option );
	}
	
	/**
	 * Retrieve the parameter from the $_POST, $_GET, or $_REQUEST
	 *
	 * @param string $string        	
	 */
	public static function getRequestParameter($string) {
		$parameter;
		if (isset ( $_POST [$string] )) {
			$parameter = $_POST [$string];
		} elseif (isset ( $_GET [$string] )) {
			$parameter = $_GET [$string];
		} elseif (isset ( $_REQUEST [$string] )) {
			$parameter = $_REQUEST [$string];
		} else {
			$parameter = null;
		}
		return $parameter;
	}
	
	/**
	 * Create an array of attributes for the WooComerce Order.
	 *
	 * @param WC_Order|int $order_id        	
	 */
	public static function createOrderAttributes($order) {
		$attribs = array ();
		if (! is_object ( $order )) {
			$order = wc_get_order ( $order );
		}
		$attribs ['amount'] = $order->get_total ();
		self::addBillingAddress ( $attribs, $order );
		
		if (get_option ( 'woocommerce_calc_shipping' ) === 'yes') {
			self::addShippingAddress ( $attribs, $order );
		}
		$attribs ['options'] = array (
				'submitForSettlement' => ! BT_Manager ()->isActive ( 'authorize_transaction' ) 
		);
		self::getOrderId ( $attribs, $order );
		/* add Merchant Account if configured. */
		self::addMerchantAccountId ( $attribs );
		/* Add customer object to order */
		self::getCustomerObject ( $attribs, $order );
		
		// Add the descriptor if enabled and the value is not empty.
		self::addDynamicDescriptor ( $attribs );
		
		self::addTaxAmount ( $attribs, $order );
		
		return $attribs;
	}
	
	/**
	 * Process the WC_Subscription.
	 * The subscription will be handled differently, based on whether or not
	 * braintree_subscriptions or woocommerce_subscriptions has been enabled by the admin.
	 *
	 * @param int $order_id        	
	 */
	public function processSubscription($order_id) {
		$result = null;
		$order = wc_get_order ( $order_id );
		if (BT_Manager ()->isActive ( 'braintree_subscriptions' )) {
			$result = WC_Braintree_Subscriptions::createBraintreeOnlySubscriptions ( $order );
		} else {
			$result = BT_Manager ()->processWooCommerceSubscriptions ( $order );
		}
		return $result;
	}
	public static function addMerchantAccountId(&$attribs = array()) {
		return BT_Manager ()->getMerchantAccountId ( $attribs );
	}
	public static function getCustomerObject(&$attribs = array(), $order) {
		return BT_Manager ()->getBraintreeCustomerObject ( $attribs, $order );
	}
	public static function getPaymentMethodFromRequest($attribs = array()) {
		return BT_Manager ()->getPaymentMethodFromRequest ( $attribs );
	}
	public static function wooCoomerceError($message) {
		wc_add_notice ( $message, 'error' );
		return array (
				'result' => 'failure',
				'redirect' => '' 
		);
	}
	public static function isPaymentChangeRequest() {
		return (isset ( $_REQUEST ['woocommerce_change_payment'] ) || isset ( $_REQUEST ['change_payment_method'] ));
	}
	public static function isPayForOrder() {
		return (isset ( $_REQUEST ['pay_for_order'] ) && $_REQUEST ['pay_for_order'] === 'true');
	}
	public static function subscriptionsActive() {
		$array = BT_Manager ()->getActivePlugins ();
		return (in_array ( 'woocommerce-subscriptions/woocommerce-subscriptions.php', $array ) || array_key_exists ( 'woocommerce-subscriptions/woocommerce-subscriptions.php', $array ));
	}
	public function isSubscription($order_id) {
		return WC_Braintree_Subscriptions::orderContainsSubscription ( $order_id );
	}
	
	/**
	 * Return the order prefix if one has been configured.
	 *
	 * @param WC_Order $order        	
	 * @return string $orderId
	 */
	public static function getOrderId(&$attribs, WC_Order $order) {
		$orderId = $order->id;
		if (self::subscriptionsActive () && wcs_order_contains_subscription ( $order )) {
			$orderId = BT_Manager ()->get_option ( 'woocommerce_subscriptions_prefix' ) . $orderId;
		} else {
			$orderId = BT_Manager ()->get_option ( 'order_prefix' ) . $orderId;
		}
		$attribs ['orderId'] = $orderId;
	}
	
	/**
	 * Method that checks if the order has a valid transaction Id.
	 *
	 * @param int $order_id        	
	 */
	public function canRefundOrder($order_id) {
		return wc_get_order ( $order_id )->get_transaction_id ();
	}
	
	/**
	 * Return the saved payment methods for the customer.
	 *
	 * @param int $user_id        	
	 */
	public static function getSavedPaymentMethodForm($user_id) {
		include WC_BRAINTREE_PLUGIN . 'payments/forms/saved-payment-methods.php';
	}
	
	/**
	 * Wrapper for the {@link Braintree_Manager::loadCustomerPaymentMethods} method.
	 */
	public static function loadCustomerPaymentMethods() {
		BT_Manager ()->loadCustomerPaymentMethods ();
	}
	
	public static function addDynamicDescriptor(&$attribs) {
		if (BT_Manager ()->isActive ( 'dynamic_descriptors' )) {
			$name = BT_Manager ()->get_option ( 'dynamic_descriptor_name' );
			$number = BT_Manager ()->get_option ( 'dynamic_descriptor_phone' );
			$url = BT_Manager ()->get_option ( 'dynamic_descriptor_url' );
			$attribs ['descriptor'] = array ();
			if (! empty ( $name )) {
				$attribs ['descriptor'] ['name'] = $name;
			}
			if (! empty ( $number ) && preg_match ( '/^US/i', get_option ( 'woocommerce_default_country' ) )) { // For US only.
				$attribs ['descriptor'] ['phone'] = $number;
			}
			if (! empty ( $url )) {
				$attribs ['descriptor'] ['url'] = $url;
			}
		}
		return $attribs;
	}
	
	/**
	 * Add the billing
	 *
	 * @param unknown $attribs        	
	 * @param WC_Order $order        	
	 */
	public static function addBillingAddress(&$attribs, WC_Order $order) {
		$attribs ['billing'] = array (
				'countryCodeAlpha2' => $order->billing_country,
				'firstName' => $order->billing_first_name,
				'lastName' => $order->billing_last_name,
				'locality' => $order->billing_city,
				'postalCode' => $order->billing_postcode,
				'streetAddress' => $order->billing_address_1 
		);
		if (! empty ( $order->billing_state )) {
			$attribs ['billing'] ['region'] = $order->billing_state;
		}
	}
	
	/**
	 * Populate the attribs array with the shipping address;
	 *
	 * @param unknown $attribs        	
	 * @param WC_Order $order        	
	 */
	public static function addShippingAddress(&$attribs, WC_Order $order) {
		$attribs ['shipping'] = array (
				'countryCodeAlpha2' => $order->shipping_country,
				'firstName' => $order->shipping_first_name,
				'lastName' => $order->shipping_last_name,
				'locality' => $order->shipping_city,
				'postalCode' => $order->shipping_postcode,
				'region' => $order->shipping_state,
				'streetAddress' => $order->shipping_address_1,
				'extendedAddress' => $order->shipping_address_2 
		);
	}
	
	/**
	 * Add the tax amount from the WC_Order to the attribs array.
	 *
	 * @param array $attribs        	
	 * @param WC_Order $order        	
	 */
	public static function addTaxAmount(&$attribs, WC_Order $order) {
		$attribs ['taxAmount'] = $order->get_total_tax ();
	}
	
	/**
	 * Populate the $saved_methods array with saved_payment methods from this gateway.
	 *
	 * @param unknown $saved_methods        	
	 */
	public static function populatePaymentMethodsArray($saved_methods) {
		self::loadCustomerPaymentMethods ();
		$methods = BT_Manager ()->getSavedPaymentMethods ( wp_get_current_user ()->ID );
		if (! empty ( $methods )) {
			$saved_methods [self::gatewayName] = array ();
			foreach ( $methods as $method ) {
				$saved_method = array (
						'method' => array (
								'last4' => $method ['type'] !== 'paypal' ? $method ['last4'] : '',
								'brand' => $method ['type'] !== 'paypal' ? $method ['type'] : $method ['description'] 
						) 
				);
				$saved_method ['expires'] = $method ['expires'];
				$saved_method ['actions'] = array (
						'delete' => array (
								'name' => __ ( 'Delete', 'braintree' ),
								'url' => wp_nonce_url ( wc_get_endpoint_url ( 'payment-methods', '', add_query_arg ( 'braintree-delete-method', $method ['token'], get_permalink ( wc_get_page_id ( 'myaccount' ) ) ) ) ) 
						) 
				);
				$saved_methods [self::gatewayName] [] = $saved_method;
			}
		}
		BT_Manager ()->log->writeToLog ( print_r ( $saved_methods, true ) );
		return $saved_methods;
	}
	
	/**
	 * Delete the payment method.
	 * If deleted, page will be redirected to the payment-methods page.
	 */
	public static function maybeDeletePaymentMethod() {
		if (isset ( $_GET ['braintree-delete-method'] )) {
			$nonce = $_GET ['_wpnonce'];
			if (! wp_verify_nonce ( $nonce )) {
				return;
			}
			$token = $_GET ['braintree-delete-method'];
			BT_Manager ()->deletePaymentMethod ( wp_get_current_user () - ID, $token );
			wc_add_notice ( __ ( 'Your payment method has been deleted.', 'braintree' ) );
			wp_redirect ( wc_get_endpoint_url ( 'payment-methods', '', get_permalink ( wc_get_page_id ( 'myaccount' ) ) ) );
			exit ();
		}
	}
	
	/**
	 * Display the admin options page.
	 *
	 * {@inheritDoc}
	 *
	 * @see WC_Payment_Gateway::admin_options()
	 */
	public function admin_options() {
		include WC_BRAINTREE_PLUGIN . 'payments/forms/admin-options.php';
	}
	
	/**
	 * Localize the custom form options so they can be used client side during creation of custom form.
	 *
	 * @return array
	 */
	public static function localize_custom_form_options() {
		$options = array ();
		foreach ( BT_Manager ()->get_custom_form_options () as $key => $option ) {
			$options [$key] = BT_Manager ()->get_option ( $key );
		}
		$options ['_3ds_enabled'] = BT_Manager ()->isActive ( '3ds_enabled' ) ? 'true' : 'false';
		$options ['order_total'] = WC ()->cart->total;
		$options ['_3ds_modal_html'] = include WC_BRAINTREE_PLUGIN . 'payments/forms/custom/3ds-modal.php';
		$options ['_3ds_spinner_html'] = include WC_BRAINTREE_PLUGIN . 'payments/forms/custom/3ds-spinner.php';
		$styles = BT_Manager ()->get_option ( 'custom_form_' . BT_Manager ()->get_option ( 'custom_form_design' ) . '_styles' );
		$options ['custom_form_styles'] = ! empty ( $styles ) ? $styles : BT_Manager ()->get_custom_forms () [BT_Manager ()->get_option ( 'custom_form_design' )] ['default_styles'];
		$options ['checkout_page'] = is_checkout () && ! is_order_received_page () ? 'true' : 'false';
		$options ['order_received_page'] = is_order_received_page () ? 'true' : 'false';
		$options ['add_paymentmethod_page'] = is_add_payment_method_page () ? 'true' : 'false';
		$options ['save_payment_methods'] = BT_Manager ()->isActive ( 'save_payment_methods' ) ? 'true' : 'false';
		$options ['change_payment_method'] = self::isPaymentChangeRequest () ? 'true' : 'false';
		if (BT_Manager ()->subscriptionsActive ()) {
			$options ['cart_contains_subscription'] = WC_Subscriptions_Cart::cart_contains_subscription () ? 'true' : 'false';
		}
		return $options;
	}
	public static function localize_dropin_form_options() {
		$options = array (
				'save_payment_methods' => BT_Manager ()->isActive ( 'save_payment_methods' ) ? 'true' : 'false',
				'_3ds_enabled' => BT_Manager ()->isActive ( '3ds_enabled' ) ? 'true' : 'false',
				'order_total' => WC ()->cart->total 
		);
		if (BT_Manager ()->subscriptionsActive ()) {
			$options ['cart_contains_subscription'] = WC_Subscriptions_Cart::cart_contains_subscription () ? 'true' : 'false';
		}
		return $options;
	}
	
	/**
	 * Returns an array containing the action and title for
	 * submitting an order for settlement.
	 */
	public static function woocommerce_order_actions($action) {
		if (! isset ( $_REQUEST ['post'] )) {
			return $action;
		}
		$order = wc_get_order ( $_REQUEST ['post'] );
		if ($order->payment_method === self::gatewayName || $order->payment_method === self::paypal_gateway_name) {
			if (! $order->get_transaction_id ()) { // Transaction is empty so exit method.
				return $action;
			}
			$transaction = BT_Manager ()->get_braintree_transaction ( $order->get_transaction_id () );
			if ($transaction->status === 'authorized') {
				$action = array (
						'submit_for_settlement' => __ ( 'Submit For Settlement', 'braintree' ),
						'braintree_void_transaction' => __ ( 'Void Transaction', 'braintree' ) 
				);
			} else if ($transaction->status === 'submitted_for_settlement') {
				$action = array (
						'braintree_void_transaction' => __ ( 'Void Transaction', 'braintree' ) 
				);
			}
		}
		return $action;
	}
	
	/**
	 * Submit the given WC_Order for settlement.
	 *
	 * @param WC_Order $order        	
	 */
	public static function submit_order_for_settlement(WC_Order $order) {
		$response = null;
		$amount = isset ( $_POST ['braintree_order_settlement_amount'] ) && ! empty ( $_POST ['braintree_order_settlement_amount'] ) ? $_POST ['braintree_order_settlement_amount'] : $order->get_total ();
		try {
			$response = Braintree_Transaction::submitForSettlement ( $order->get_transaction_id (), $amount );
			if (! $response->success) {
				BT_Manager ()->log->writeErrorToLog ( sprintf ( 'There was an error submitting order %s for settlement. Reason: %s', $response->message ) );
				$order->add_order_note ( sprintf ( __ ( 'There was an error submitting the order for settlement. Reason: %s', 'braintree' ), $response->message ) );
			} else {
				update_post_meta ( $order->id, '_submitted_for_settlement', 1 );
				$order->add_order_note ( sprintf ( __ ( 'The order was submitted for settlement in the amount of %s %s.', 'braintree' ), $response->transaction->currencyIsoCode, $response->transaction->amount ) );
				BT_Manager ()->log->writeToLog ( sprintf ( 'Order %s was submitted for settlement successfully. Response: %s', $order->id, print_r ( $response->transaction, true ) ) );
			}
		} catch ( Braintree_Exception $e ) {
			BT_Manager ()->log->writeErrorToLog ( sprintf ( 'There was an error submitting order %s for settlement. Reason: %s', $order->id, $e->getMessage () ) );
		}
	}
	
	/**
	 * Void the Braintree transaction.
	 *
	 * @param WC_Order $order        	
	 */
	public static function void_transaction(WC_Order $order) {
		try {
			$response = Braintree_Transaction::void ( $order->get_transaction_id () );
			if ($response->success) {
				$order->add_order_note ( __ ( 'The transaction for the order has been voided.', 'braintree' ) );
			} else {
				$order->add_order_note ( sprintf ( __ ( 'There was an error voiding the transcation. Reason: %s' ), $response->message ) );
				BT_Manager ()->log->writeErrorToLog ( sprintf ( __ ( 'There was an error voiding the transcation. Reason: %s' ), $e->getMessage () ) );
			}
		} catch ( Braintree_Exception $e ) {
			$order->add_order_note ( sprintf ( __ ( 'There was an error voiding the transcation. Reason: %s' ), $e->getMessage () ) );
			BT_Manager ()->log->writeErrorToLog ( sprintf ( __ ( 'There was an error voiding the transcation. Reason: %s' ), $e->getMessage () ) );
		}
	}
}
WC_Braintree_Payments::init ();