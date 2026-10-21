<?php
if (! defined ( 'ABSPATH' )) {
	exit (); // Exit if accessed directly
}

use Braintree\Exception;
use Braintree\Exception\NotFound;
use Braintree\PaymentMethod;

/**
 * Manager class that controls access to data stored in the database.
 *
 * @author Clayton Rogers
 * @since 3/12/16
 */
class Braintree_Manager {
	public static $_instance = null;
	private $required_settings = null;
	public $paypal_design_options;
	public $debug;
	public $log;
	public $braintreeSubscriptions;
	public $paypalOnly;
	public $version = '2.5.0';
	public $settings;
	const Settings = 'braintree_payment_settings';
	
	/**
	 * Creates and instance of the Braintree_Manager class and loads all necessary data.
	 */
	public function __construct() {
		$this->log = new Braintree_DebugLog ();
		$this->init_settings ();
		// $this->required_settings = include_once WC_BRAINTREE_PLUGIN . 'admin/includes/braintree-settings.php';
		$this->debug = $this->get_option ( 'enable_debug' ) === 'yes' ? true : false;
		$this->initializeVariables ();
		$this->initializeBraintree ();
	}
	public static function instance() {
		if (self::$_instance === null) {
			Braintree_Manager::$_instance = new Braintree_Manager ();
		}
		return self::$_instance;
	}
	
	/**
	 * Set values for any class variables that are needed.
	 */
	private function initializeVariables() {
		$this->braintreeSubscriptions = $this->get_option ( 'braintree_subscriptions' ) === 'yes' ? true : false;
		$this->cancelOnFail = $this->get_option ( 'braintree_subscriptions_cancel_on_fail' ) === 'yes' ? true : false;
	}
	
	/**
	 * Load the settings values using the wordpress function get_options.
	 */
	private function init_settings() {
		$this->settings = $this->get_payments_config ( self::Settings );
	}
	public function isActive($option) {
		return $this->get_option ( $option ) === 'yes' ? true : false;
	}
	public function get_required_settings() {
		if ($this->required_settings == null) {
			$settings = include_once WC_BRAINTREE_PLUGIN . 'admin/includes/braintree-settings.php';
			$this->required_settings = $settings + $this->get_custom_form_options () + $this->get_paypal_options ();
		}
		return $this->required_settings;
	}
	public function get_option($key) {
		if ($this->settings == null) {
			$this->init_settings ();
		}
		if (! isset ( $this->settings [$key] )) {
			$this->settings [$key] = isset ( $this->get_required_settings () [$key] ['default'] ) ? $this->get_required_settings () [$key] ['default'] : '';
		}
		return $this->settings [$key];
	}
	public function set_option($key, $value = '') {
		$this->settings [$key] = $value;
	}
	public function update_settings($settings = null) {
		if ($this->settings == null) {
			$this->init_settings ();
		}
		if ($settings != null) {
			$this->settings = $settings;
		}
		$this->update_payments_config ( self::Settings, $this->settings );
	}
	private function update_payments_config($option, $value) {
		update_option ( base64_encode ( $option ), base64_encode ( maybe_serialize ( $value ) ) );
	}
	public function get_payments_config($option) {
		return maybe_unserialize ( base64_decode ( get_option ( base64_encode ( $option ) ) ) );
	}
	public function delete_payments_config($option) {
		delete_option ( base64_encode ( $option ) );
	}
	public function display_debugLog() {
		$log = new Braintree_DebugLog ();
		return $log->display_debugLog ();
	}
	
	/**
	 * Return the array containg the PayPal button design information.
	 */
	public function get_paypal_design() {
		$design = $this->get_option ( 'paypal_button_design' );
		$options = $this->get_paypal_design_options ();
		return apply_filters ( 'braintree_get_paypal_design_option', $options [$design] );
	}
	public function get_paypal_design_options() {
		return include WC_BRAINTREE_PLUGIN . 'admin/includes/paypal-design-options.php';
	}
	public function get_custom_form_options() {
		return include WC_BRAINTREE_PLUGIN . 'admin/includes/custom-form-options.php';
	}
	public function get_paypal_options() {
		return include WC_BRAINTREE_PLUGIN . 'admin/includes/paypal-settings.php';
	}
	public function get_custom_forms() {
		return include WC_BRAINTREE_PLUGIN . 'admin/includes/custom-forms.php';
	}
	public function activateLicense($license_key) {
		$attempt = 0;
		$args = array (
				'timeout' => 60 
		);
		$url_args = array (
				'slm_action' => 'slm_activate',
				'secret_key' => BRAINTREE_LICENSE_VERIFICATION_KEY,
				'license_key' => $license_key 
		);
		$url = add_query_arg ( $url_args, BRAINTREE_LICENSE_ACTIVATION_URL );
		$headers = array (
				'Content-type: text/html' 
		);
		$options = array (
				CURLOPT_URL => $url,
				CURLOPT_CONNECTTIMEOUT => 60,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSL_VERIFYPEER => true,
				CURLOPT_CAINFO => WC_BRAINTREE_PLUGIN . 'ssl/wordpress_paymentplugins_com.crt',
				CURLOPT_HTTPHEADER => $headers 
		);
		$response = $this->executeCurl ( $options );
		if ($response ['result'] === 'error') {
			if ($response ['code'] == 60 || $response ['code'] == 51) {
				$options [CURLOPT_SSL_VERIFYPEER] = false;
				$options [CURLOPT_SSL_VERIFYHOST] = false;
				$response = $this->executeCurl ( $options );
				if ($response ['result'] === 'error') {
					$this->licenseActivationFailure ( $response, $license_key );
				} elseif ($response ['result'] === 'success') {
					$this->licenseActivationSuccess ( $response, $license_key );
				}
			} else {
				$this->licenseActivationFailure ( $response, $license_key );
			}
		} else {
			if ($response ['result'] === 'success') {
				$this->licenseActivationSuccess ( $response, $license_key );
			}
		}
	}
	private function licenseActivationSuccess($response = array(), $license_key = '') {
		$this->set_option ( 'license_status', 'active' );
		$this->set_option ( 'license', $license_key );
		$this->update_settings ();
		$this->addAdminNotice ( array (
				'type' => 'success',
				'text' => sprintf ( 'Your license has been activated.' ) 
		) );
	}
	private function licenseActivationFailure($response = array(), $license_key = '') {
		$this->log->writeErrorToLog ( sprintf ( 'There was an error activating your license. Reason: %s', isset ( $response ['message'] ) ? $response ['message'] : 'curl response code: ' . $errNo ) );
		$this->addAdminNotice ( array (
				'type' => 'error',
				'text' => sprintf ( 'There was an error activating your license. Reason: %s', isset ( $response ['message'] ) ? $response ['message'] : 'curl response code: ' . $errNo ) 
		) );
	}
	private function executeCurl($options = array()) {
		$ch = curl_init ();
		curl_setopt_array ( $ch, $options );
		$response = curl_exec ( $ch );
		$errNo = curl_errno ( $ch );
		curl_close ( $ch );
		if ($errNo) {
			$response = array (
					'result' => 'error',
					'code' => $errNo,
					'message' => sprintf ( 'There was an error activating your license. Reason: %s', isset ( $response ['message'] ) ? $response ['message'] : 'curl response code: ' . $errNo ) 
			);
		} else {
			$response = json_decode ( $response, true );
		}
		return $response;
	}
	
	/**
	 * Deletes the debug log entries.
	 */
	public function deleteDebugLog() {
		$log = new Braintree_DebugLog ();
		$log->delete_log ();
	}
	
	/**
	 * Initialize all of the Braintree settings.
	 */
	private function initializeBraintree() {
		Braintree_Configuration::environment ( $this->getEnvironment () );
		Braintree_Configuration::publicKey ( $this->get_option ( $this->getEnvironment () . '_public_key' ) );
		Braintree_Configuration::privateKey ( $this->get_option ( $this->getEnvironment () . '_private_key' ) );
		Braintree_Configuration::merchantId ( $this->get_option ( $this->getEnvironment () . '_merchant_id' ) );
	}
	
	/**
	 * Generate a client token using the Braintree SDK.
	 * If a user has a customerId saved in the database,
	 * then the client token will be generated using the vault customerId.
	 *
	 * @param array $args        	
	 */
	public function getClientToken($attribs = []) {
		$clientToken = null;
		try {
			$clientToken = Braintree_ClientToken::generate ( $attribs );
		} catch ( InvalidArgumentException $e ) {
			$this->log->writeErrorToLog ( $e->getMessage () );
			if ($customerId) {
				delete_user_meta ( $user_id, 'braintree_' . $this->getEnvironment () . '_vault_id' );
				$clientToken = $this->getClientToken ();
			}
		} catch ( Exception $e ) {
			$this->log->writeErrorToLog ( 'There was an error generating the client token. Message - ' . $e->getMessage () );
			return $clientToken;
		}
		return $clientToken;
	}
	
	/**
	 * Perform the Braintree_Transaction::sale() for the given $attribs and $order.
	 * If the sale is successful, the transaction data is
	 * saved to the order_meta data in the database.
	 *
	 * @param array $args        	
	 * @param int $order_id        	
	 */
	public function woocommerceSale($attribs = array(), WC_Order $order) {
		if (! isset ( $attribs ['paymentMethodToken'] ) && ! isset ( $attribs ['paymentMethodNonce'] )) {
			if ($this->getRequestParameter ( 'payment_method_nonce' )) {
				$attribs ['paymentMethodNonce'] = $this->getRequestParameter ( 'payment_method_nonce' );
			} else {
				$attribs = $this->getPaymentMethodFromRequest ( $attribs );
			}
		}
		$result = null;
		if ($order->get_total () == 0) { // Order total is zero, may be a synchronized subscription.
			$this->maybeSaveSynchronizedSubscription ( $order, $attribs );
			$this->log->writeToLog ( sprintf ( 'Synchronized subscription %s saved. Attributes: %s', $order->id, print_r ( $attribs, true ) ) );
			$result = $this->handleResponseSuccess ( $order );
		} else {
			try {
				BT_Manager ()->log->writeToLog ( sprintf ( 'Transaction being processed by Braintree. Attributes: %s', print_r ( $attribs, true ) ) );
				$response = Braintree_Transaction::sale ( $attribs );
				if ($response->success) {
					// update transaction meta in database.
					$this->saveTransactionMeta ( $response->transaction, $order );
					if ($this->subscriptionsActive () && wcs_order_contains_subscription ( $order )) {
						$subscriptions = wcs_get_subscriptions_for_order ( $order );
						foreach ( $subscriptions as $subscription ) {
							$this->saveTransactionMeta ( $response->transaction, $subscription );
						}
					}
					$order->payment_complete ($response->transaction->id);
					$this->log->writeToLog ( sprintf ( 'Transaction %s successfully charged in the amount of %s for orderId %s', $response->transaction->id, $order->get_total (), $order->id ) );
					$result = $this->handleResponseSuccess ( $order );
				} else {
					$result = $this->handleResponseError ( $response );
				}
			} catch ( Braintree_Exception $e ) {
				if ($e instanceof Braintree_Exception_Authorization) {
					$this->log->writeErrorToLog ( 'There was an Authorization Exception thrown during the transaction. Please check your 
							API key settings. Also, if you have configured a Merchant Account ID for settlement currency, verify you have entered the correct
							Merchant Account ID.' );
					$e = new Exception ( __ ( 'There was an error processing your payment.', 'braintree' ) );
				} else {
					$this->log->writeErrorToLog ( $e->getMessage () );
				}
				$this->handleResponseError ( $e );
				return array (
						'result' => 'failure',
						'redirect' => '' 
				);
			}
		}
		return $result;
	}
	
	/**
	 * Process the Braintree_Transaction::sale().
	 *
	 * @param array $attribs        	
	 */
	public function sale(array $attribs) {
		$response = null;
		try {
			$response = Braintree_Transaction::sale ( $attribs );
			if (! $response->success) {
				$this->log->writeToLog ( $response->message );
			}
		} catch ( Exception $e ) {
			$this->log->writeErrorToLog ( $e->getMessage );
			$response = $e;
		}
		return $response;
	}
	
	/**
	 * Update the order/subscriptions status.
	 * @deprecated version 2.4.8
	 * @param WC_Order $order        	
	 */
	public function updateOrderStatus(WC_Order $order) {
		if ($this->orderIsSubscription ( $order )) {
			$order->update_status ( $this->get_option ( 'subscriptions_payment_success_status' ) );
		} else {
			$order->update_status ( $this->get_option ( 'order_status' ) );
		}
	}
	
	/**
	 * Record the error message in the Braintree Log and return an array containg the result and redirect.
	 *
	 * @param Braintree $response        	
	 */
	public function handleResponseError($response) {
		if ($response instanceof Exception) {
			if (function_exists ( 'wc_add_notice' )) {
				wc_add_notice ( $response->getMessage (), 'error' );
			}
			$this->log->writeErrorToLog ( $response->getMessage () );
			return array (
					'result' => 'failure',
					'redirect' => '' 
			);
		} else {
			if ($response->errors->deepSize () > 0) {
				foreach ( $response->errors->deepAll () as $error ) {
					if (function_exists ( 'wc_add_notice' )) {
						wc_add_notice ( $error->message, 'error' );
					}
					$this->log->writeErrorToLog ( sprintf ( 'Message: %s Error Code: %s', $error->message, $error->code ) );
				}
			} else {
				if (function_exists ( 'wc_add_notice' )) {
					wc_add_notice ( $response->message, 'error' );
				}
				$this->log->writeErrorToLog ( $response->message );
			}
			return array (
					'result' => 'failure',
					'redirect' => '' 
			);
		}
	}
	public function handleSubscriptionResponseError($response) {
		if ($response instanceof Exception) {
			if (current_user_can ( 'manager_options' )) {
				wcs_add_admin_notice ( sprintf ( 'The subscription could not be cancelled. 
					Message: %s', $response->getMessage () ), 'error' );
			}
			$this->log->writeErrorToLog ( sprintf ( 'The subscription could not be cancelled. 
					Message: %s', $response->getMessage () ) );
		} else {
			if ($response->errors->deepSize () > 0) {
				foreach ( $response->errors->deepAll () as $error ) {
					if (current_user_can ( 'manager_options' )) {
						wcs_add_admin_notice ( $error->message, 'error' );
					}
					$this->log->writeErrorToLog ( $error->message );
				}
			} else {
				if (current_user_can ( 'manager_options' )) {
					wcs_add_admin_notice ( $response->message, 'error' );
				}
				$this->log->writeErrorToLog ( $response->message );
			}
			return array (
					'result' => 'failure',
					'redirect' => '' 
			);
		}
	}
	public function handleSubscriptionCancellation($subscription) {
		if (current_user_can ( 'manage_options' )) {
			if (function_exists ( 'wcs_add_admin_notice' )) {
				wcs_add_admin_notice ( __ ( 'Subscription ' . $subscription->id . ' was successfully cancelled.', 'braintree' ), 'success' );
			}
		}
		$this->log->writeToLog ( sprintf ( 'Subscription %s was successfully cancelled.', $subscription->id ) );
	}
	
	/**
	 * Process the refund for the given order_id.
	 *
	 * @param int $order_id        	
	 * @param float $amount        	
	 * @param string $reason        	
	 */
	public function refund($order_id, $amount, $reason) {
		$order = wc_get_order ( $order_id );
		try {
			$response = Braintree_Transaction::refund ( $order->get_transaction_id (), $amount );
			if ($response->success) {
				$this->log->writeToLog ( sprintf ( 'Order %s was refunded in the amount of %s', $order_id, $amount ) );
				$order->add_order_note ( sprintf ( __ ( 'Order has been refunded in the amount of %s', 'braintree' ), $amount ) );
				return true;
			} else {
				$this->handleResponseError ( $response );
				throw new Exception ( sprintf ( 'A refund could not be processed for Order %s. Message: %s', $order_id, $response->message ) );
			}
		} catch ( Exception\NotFound $e ) {
			$this->log->writeErrorToLog ( sprintf ( 'Refund could not be processed for Order %s because it was not found
					in the Braintree system. Message: %s.', $order_id, $e->getMessage () ) );
			throw new Exception ( sprintf ( 'Order %s could not be refunded because transaction ID %s could not be found in the Braintree Environment.', $order_id, $order->get_transaction_id () ) );
		}
	}
	
	/**
	 * Save the transaction data to the order meta in the database.
	 *
	 * @param Braintree_Transaction $transaction        	
	 * @param WC_Order $order_id        	
	 */
	public function saveTransactionMeta($transaction, $order) {
		update_post_meta ( $order->id, '_transaction_id', $transaction->id );
		update_post_meta ( $order->id, '_payment_method_title', $this->getPaymentMethodTitle ( $transaction ) );
		update_post_meta ( $order->id, '_payment_method_token', $this->getPaymentMethodToken ( $transaction ) );
		update_post_meta ( $order->id, '_merchant_account_id', $transaction->merchantAccountId );
		if ($this->orderIsSubscription ( $order )) {
			update_post_meta ( $order->id, '_subscription_type', 'woocommerce' );
			update_post_meta ( $order->order->id, '_subscription_type', 'woocommerce' );
			update_post_meta ( $order->order->id, '_merchant_account_id', $transaction->merchantAccountId );
			/* Save the transaction data to the parent order. */
			$this->saveTransactionMeta ( $transaction, $order->order );
		}
	}
	
	/**
	 * Return the payment method title for the given transaction.
	 *
	 * @param
	 *        	Braintree_Transaction | Braintree_PaymentMethod $object
	 */
	public function getPaymentMethodTitle($object) {
		$paymentTitle = '';
		if ($object instanceof Braintree_Transaction) {
			if ($object->paymentInstrumentType === 'credit_card') {
				$paymentTitle = $object->creditCardDetails->cardType . ' - ' . $object->creditCardDetails->maskedNumber;
			} elseif ($object->paymentInstrumentType === 'paypal_account') {
				$paymentTitle = 'PayPal - ' . $object->paypalDetails->payerEmail;
			}
		} elseif ($object instanceof Braintree_CreditCard) {
			$paymentTitle = $object->cardType . ' - ' . $object->maskedNumber;
		} elseif ($object instanceof Braintree_PayPalAccount) {
			$paymentTitle = 'PayPal - ' . $object->email;
		}
		return $paymentTitle;
	}
	
	/**
	 * Return the token representing the payment method.
	 *
	 * @param Braintree_Transaction $transaction        	
	 */
	public function getPaymentMethodToken(Braintree_Transaction $transaction) {
		if ($transaction->paymentInstrumentType === 'credit_card') {
			$token = $transaction->creditCardDetails->token;
		} elseif ($transaction->paymentInstrumentType === 'paypal_account') {
			$token = $transaction->paypalDetails->token;
		}
		return $token;
	}
	
	/**
	 * Retrieve the parameter from the $_POST, $_GET, or $_REQUEST
	 *
	 * @param string $string        	
	 */
	public function getRequestParameter($string) {
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
	 * Create the Braintree Customer in the vault.
	 *
	 * @param number $userId        	
	 */
	public function createBraintreeCustomer($userId = 0, $attribs) {
		$response = null;
		try {
			$response = Braintree_Customer::create ( $attribs );
			if ($response->success) {
				update_user_meta ( $userId, 'braintree_' . $this->getEnvironment () . '_vault_id', $response->customer->id );
				$this->log->writeToLog ( sprintf ( 'Braintree customerId %s created successfully for userId %s', $response->customer->id, $userId ) );
			} else {
				if ($response->errors->deepSize () > 0) {
					foreach ( $response->errors->deepAll () as $error ) {
						$this->log->writeErrorToLog ( $error->message );
					}
				} else {
					$this->log->writeErrorToLog ( $response->message );
				}
			}
		} catch ( Exception $e ) {
			$this->log->writeErrorToLog ( sprintf ( 'There was an error while creating the braintree customer for user 
					%s. Message: %s', $userId, $e->getMessage () ) );
			$response = $e;
		}
		return $response;
	}
	
	/**
	 *
	 * @param int $user_id        	
	 * @param array $attribs        	
	 */
	public function updateBraintreeCustomer($user_id, $attribs = array()) {
		$customerId = $this->getBraintreeCustomerId ( $user_id );
		try {
			Braintree_Customer::update ( $customerId, $attribs );
		} catch ( Exception $e ) {
			if ($e instanceof Braintree_Exception_NotFound) {
				$this->log->writeErrorToLog ( sprintf ( 'There was an error while updating Braintree customer %s. The customer
						could not be found.', $customerId ) );
			} else
				$this->log->writeErrorToLog ( $e->getMessage () );
		}
	}
	public function getBraintreeCustomerId($user_id) {
		return get_user_meta ( $user_id, 'braintree_' . $this->getEnvironment () . '_vault_id', true );
	}
	
	/**
	 * Process the WooCommerce Subscription using the order_id provided.
	 * The order may contain multiple subscriptions and each
	 * one will be processed.
	 *
	 * @param WC_Order $order_id        	
	 */
	public function processWooCommerceSubscriptions(WC_Order $order) {
		$result = null;
		$subscriptions = wcs_get_subscriptions_for_order ( $order );
		$result = $this->createWooCommerceSubscription ( $order );
		if ($result ['result'] === 'failure') {
			return $result;
		} else {
			WC ()->cart->empty_cart ();
			$order->payment_complete ();
		}
		return $result;
	}
	
	/**
	 * Update the Braintree_Subscription
	 *
	 * @param array $attribs        	
	 */
	public function updateBraintreeSubscription($id, $attribs) {
		$result = null;
		try {
			$response = Braintree_Subscription::update ( $id, $attribs );
			$result = $response->success;
		} catch ( Braintree_Exception_NotFound $e ) {
			$this->log->writeErrorToLog ( sprintf ( 'Braintree subscription %s could not be found.', $id ) );
			$result = false;
		} catch ( Exception $e ) {
			$this->log->writeErrorToLog ( $e->getMessage () );
			$result = false;
		}
		return $result;
	}
	
	/**
	 * Create the WooCommerce subscription.
	 *
	 * @param WC_Subscription $subscription        	
	 */
	public function createWooCommerceSubscription(WC_Order $subscription) {
		$attribs = WC_Braintree_Payments::createOrderAttributes ( $subscription );
		if (self::getRequestParameter ( 'payment_method_nonce' )) {
			$response = BT_Manager ()->createBraintreePaymentMethod ( array (
					'paymentMethodNonce' => $this->getRequestParameter ( 'payment_method_nonce' ),
					'customerId' => BT_Manager ()->getBraintreeCustomerId ( wp_get_current_user ()->ID ),
					'billingAddress' => array (
							'countryCodeAlpha2' => $subscription->billing_country,
							'firstName' => $subscription->billing_first_name,
							'lastName' => $subscription->billing_last_name,
							'postalCode' => $subscription->billing_postcode,
							'streetAddress' => $subscription->billing_address_1 
					),
					'options' => array (
							'failOnDuplicatePaymentMethod' => BT_Manager ()->isActive ( 'fail_on_duplicate' ) ? true : false,
							'makeDefault' => true 
					) 
			) );
			if (! $response->success) {
				return BT_Manager ()->handleResponseError ( $response );
			} else {
				$attribs ['paymentMethodToken'] = $response->paymentMethod->token;
			}
		} else {
			$attribs = $this->getPaymentMethodFromRequest ( $attribs );
		}
		return $this->woocommerceSale ( $attribs, $subscription );
	}
	
	/**
	 * Cancel the Braintree subscription.
	 *
	 * @param WC_Subscription $subscription        	
	 * @param bool $update_status
	 *        	default is false.
	 */
	public function cancelBraintreeSubscription(WC_Subscription $subscription, $update_status = false) {
		$id = get_post_meta ( $subscription->id, '_subscription_id', true );
		try {
			$response = Braintree_Subscription::cancel ( $id );
			if ($response->success) {
				if ($update_status) {
					$subscription->update_status ( 'cancelled' );
				}
				$this->handleSubscriptionCancellation ( $response->subscription );
			} else {
				$this->handleSubscriptionResponseError ( $response );
			}
		} catch ( Exception $e ) {
			$this->handleSubscriptionResponseError ( $e );
		}
		return $response->success;
	}
	
	/**
	 * Cancel the WooCommmerce Subscription.
	 *
	 * @param WC_Subscription $subscription        	
	 */
	public function cancelWooCommerceSubscription(WC_Subscription $subscription) {
		$subscription->update_status ( 'cancelled' );
		do_action('braintree_wc_subscription_cancelled', $subscription->id);
		return true;
	}
	
	/**
	 * Fetch the planId for the given subscription object.
	 * The planId will be fetched for the Simple Subscription or Variable Subscription.
	 *
	 * @param WC_Subscription $subscription        	
	 * @return string
	 */
	public function getSubscriptionPlanId(WC_Order $subscription) {
		$product_id = $this->getProductIdFromOrder ( $subscription );
		$plans = get_post_meta ( $product_id, 'braintree_plans', true );
		
		$currency = get_woocommerce_currency ();
		
		return $plans [$currency];
	}
	
	/**
	 * Return the product ID from the WC_Order.
	 * Only to be used for Braintree Subscriptions.
	 *
	 * @param WC_Order $order        	
	 */
	public function getProductIdFromOrder(WC_Order $order) {
		$product_id = null;
		$items = $order->get_items ();
		foreach ( $items as $item ) {
			$product = wc_get_product ( $item ['product_id'] );
			if ($product->is_type ( 'variable' )) {
				$product_id = $item ['variation_id'];
			} else
				$product_id = $item ['product_id'];
		}
		return $product_id;
	}
	
	/**
	 * Returns the Braintree Plan Id for the given product id based on the WooCommerce currency.
	 *
	 * @param int $post_id        	
	 */
	public function getProductPlanId($post_id) {
		$plans = get_post_meta ( $post_id, 'braintree_plans', true );
		return isset ( $plans [get_woocommerce_currency ()] ) ? $plans [get_woocommerce_currency ()] : null;
	}
	
	/**
	 * Save the transaction data for the subscription to the database.
	 * The transcation data is saved to the subscription
	 * and the parent order. If there is not transaction, then a call is made to Braintree to fetch the payment method details.
	 *
	 * @param WC_Subscription $subscription        	
	 * @param Braintree_Subscription $braintree_subscription        	
	 */
	public function saveBraintreeSubscriptionMeta($subscription, $braintree_subscription) {
		$transaction = isset ( $braintree_subscription->transactions [0] ) ? $braintree_subscription->transactions [0] : null;
		update_post_meta ( $subscription->id, '_subscription_id', $braintree_subscription->id );
		update_post_meta ( $subscription->id, 'braintree_plan_id', $braintree_subscription->planId );
		update_post_meta ( $subscription->id, '_payment_method_token', $braintree_subscription->paymentMethodToken );
		update_post_meta ( $subscription->id, '_subscription_type', 'braintree' );
		update_post_meta ( $subscription->order->id, '_subscription_type', 'braintree' );
		if ($transaction) {
			update_post_meta ( $subscription->id, '_transaction_id', $transaction->id );
			if ($subscription->order) {
				$this->saveTransactionMeta ( $transaction, $subscription->order );
			}
			update_post_meta ( $subscription->id, '_payment_method_title', $this->getPaymentMethodTitle ( $transaction ) );
		}  /* If there is not transaction, then fetch the payment method using the token and use it to save the payment method title. */
else {
			$paymentMethod = $this->getBraintreePaymentMethod ( $braintree_subscription->paymentMethodToken );
			update_post_meta ( $subscription->id, '_payment_method_title', $this->getPaymentMethodTitle ( $paymentMethod ) );
			update_post_meta ( $subscription->order->id, '_payment_method_title', $this->getPaymentMethodTitle ( $paymentMethod ) );
		}
	}
	
	/**
	 * Save the transaction data for the subscription to the database.
	 *
	 * @param WC_Subscription $subscription        	
	 * @param Braintree_Subscription $braintree_subscription        	
	 */
	public function saveWooCommerceSubscriptionMeta($subscription, $transaction) {
		update_post_meta ( $subscription->id, '_transaction_id', $transaction->id );
		update_post_meta ( $subscription->id, '_payment_method_token', $braintree_subscription->paymentMethodToken );
		update_post_meta ( $subscription->id, '_payment_method_title', $this->getPaymentMethodTitle ( $transaction ) );
	}
	
	/**
	 *
	 * @param string $message        	
	 */
	public function handleWCError($message) {
		wc_add_notice ( $message, 'error' );
		return array (
				'result' => 'failure',
				'redirect' => '' 
		);
	}
	
	/**
	 * Returns an array o consiting of a result and redirect index.
	 * This method is to be used only for
	 * order and subscription creation.
	 *
	 * @param WC_Order $order        	
	 */
	public function handleResponseSuccess($order) {
		return array (
				'result' => 'success',
				'redirect' => $order->get_checkout_order_received_url () 
		);
	}
	public function getEnvironment() {
		$environment = $this->get_option ( 'production_environment' ) === 'yes' ? 'production' : 'sandbox';
		if (strtolower ( $this->get_option ( 'license_status' ) ) === 'inactive') {
			$environment = 'sandbox';
		}
		return $environment;
	}
	public function isEnabled() {
		return $this->get_option ( 'enabled' ) === 'yes' ? 'yes' : 'no';
	}
	
	/**
	 * Method that gets all of the configured plan Id's in the Braintree environment.
	 */
	public function getBraintreePlans() {
		$plans = array ();
		try {
			$plans = Braintree_Plan::all ();
		} catch ( Exception $e ) {
			$this->log->writeErrorToLog ( $e->getMessage () );
		}
		return $plans;
	}
	
	/**
	 * Returns the Braintree_Plan object from the array.
	 *
	 * @param string $plan_id        	
	 * @param Braintree_Plan[] $plans        	
	 * @return Braintree_Plan|null
	 */
	public function getBraintreePlan($plan_id, $plans = null) {
		if ($plans == null) {
			
			$plans = $this->getBraintreePlans ();
		}
		$plan = null;
		foreach ( $plans as $plan ) {
			if ($plan->id === $plan_id) {
				return $plan;
			}
		}
		return null;
	}
	
	/**
	 * Retrieve the payment method from Braintree using the specified token.
	 *
	 * @param string $token        	
	 * @return
	 *
	 */
	public function getBraintreePaymentMethod($token) {
		$paymentMethod = null;
		try {
			$paymentMethod = Braintree_PaymentMethod::find ( $token );
			if (! $paymentMethod) {
				throw new Exception ( $response->message );
			}
		} catch ( Exception $e ) {
			$this->log->writeErrorToLog ( 'Method getBraintreePaymentMethod(). Failed to retrieve customer 
					payment method. Message: ' . $e->getMessage );
		}
		return $paymentMethod;
	}
	
	/**
	 * Retrieve the Braintree Customer from Braintree.
	 *
	 * @param int $user_id        	
	 * @param string $customerId        	
	 * @return null | Braintree_Customer $customer
	 */
	public function getBraintreeCustomer($user_id, $customerId) {
		$customer = null;
		try {
			$customer = Braintree_Customer::find ( $customerId );
		} catch ( Braintree_Exception_NotFound $e ) {
			$this->log->writeErrorToLog ( sprintf ( 'Braintree customer %s could not be found.
					Message: %s', $customerId, $e->getMessage () ) );
			delete_user_meta ( $user_id, 'braintree_' . $this->getEnvironment () . '_vault_id' );
			delete_user_meta ( $user_id, 'braintree_payment_methods' );
			WC_Braintree_Payments::maybeCreateBraintreeCustomer ();
			return $customer;
		} catch ( Exception $e ) {
			$this->log->writeErrorToLog ( sprintf ( 'Braintree customer %s could not be found. 
					Message: %s', $customerId, $e->getMessage () ) );
			return $customer;
		}
		return $customer;
	}
	
	/**
	 * Return the first billing date for the subscription.
	 *
	 * @param WC_Subscription $subscription        	
	 */
	public function getSubscriptionBillingDate($subscription) {
		if (! $timeStamp = $subscription->get_time ( 'trial_end' )) {
			$timeStamp = $subscription->get_time ( 'start' );
		}
		$startDate = new DateTime ();
		$startDate->setTimestamp ( $timeStamp );
		return $startDate->format ( 'm/d/Y' ); // ex. 01/01/2016
	}
	
	/**
	 * Get the formatted date mm/dd/yyyy for the specified date type
	 *
	 * @param WC_Subscription $subscription        	
	 * @param string $dateType        	
	 * @return string date
	 */
	public function getSubscriptionDate(WC_Subscription $subscription, $dateType) {
		$timestamp = $subscription->get_time ( 'next_payment' );
		$date = new DateTime ();
		$date->setTimestamp ( $date );
		return $date->format ( 'm/d/Y' );
	}
	
	/**
	 * Return the subscription length for the item contained within the WC_Order.
	 *
	 * @param WC_Order $order        	
	 */
	public function getBraintreeSubscriptionLength(WC_Order $order) {
		$items = $subscription->get_items ();
		$product_id;
		foreach ( $items as $item ) {
			$product_id = wc_get_product ( $item ['product_id'] );
			break;
		}
		return get_post_meta ( $product_id, '_subscription_length', true );
	}
	
	/**
	 * Return the subscription price.
	 * The method looks for the product_id associated with the subscription and finds the price using
	 * the _subscription_price meta field.
	 *
	 * @param WC_Order $subscription        	
	 */
	public function getSubscriptionPrice($subscription) {
		$product_id = $this->getProductIdFromOrder ( $subscription );
		return get_post_meta ( $product_id, '_subscription_price', true );
	}
	public function populateSubscriptionPrice($attribs, $subscription) {
		$price = $this->getSubscriptionPrice ( $subscription );
		if (! empty ( $price )) {
			$attribs ['price'] = $price;
		}
		return $attribs;
	}
	
	/**
	 * Remove the subscription from the shopping cart.
	 *
	 * @param WC_Subscription $subscription        	
	 */
	public function updateCart($subscription) {
		foreach ( WC ()->cart->get_cart () as $cart_key => $item ) {
			$product_id = $item ['product_id'];
			foreach ( $subscription->get_items () as $item ) {
				if ($item ['product_id'] == $product_id) {
					WC ()->cart->remove_cart_item ( $cart_key );
				}
			}
		}
	}
	public function orderIsSubscription(WC_Order $order) {
		$isSubscription = false;
		if ($this->subscriptionsActive ()) {
			if ($order instanceof WC_Subscription) {
				$isSubscription = true;
			}
		}
		return $isSubscription;
	}
	
	/**
	 * Check if WooCommerce Subscriptions plugin is active.
	 */
	public function subscriptionsActive() {
		$array = $this->getActivePlugins ();
		return (in_array ( 'woocommerce-subscriptions/woocommerce-subscriptions.php', $array ) || array_key_exists ( 'woocommerce-subscriptions/woocommerce-subscriptions.php', $array ) || preg_grep ( '/woocommerce.*subscriptions.*/', $array ));
	}
	
	/**
	 * Return true if the WooCommerce plugin is active.
	 */
	public function woocommerceActive() {
		$array = $this->getActivePlugins ();
		return (in_array ( 'woocommerce/woocommerce.php', $array ) || array_key_exists ( 'woocommerce/woocommerce.php', $array ));
	}
	public function getActivePlugins() {
		return get_option ( 'active_plugins' );
	}
	
	/**
	 * Retrieve the Merchant Account for the shop.
	 *
	 * @param array $attribs        	
	 * @return unknown
	 */
	public function getMerchantAccountId(&$attribs = array()) {
		$currencyCode = get_woocommerce_currency ();
		$merchantId = BT_Manager ()->get_option ( 'woocommerce_braintree_' . BT_Manager ()->getEnvironment () . '_merchant_account_id[' . $currencyCode . ']' );
		if (! empty ( $merchantId )) {
			$attribs ['merchantAccountId'] = $merchantId;
		}
		return $attribs;
	}
	public function getBraintreeCustomerObject(&$attribs = array(), WC_Order $order) {
		$attribs ['customer'] = array (
				'email' => $order->billing_email,
				'firstName' => $order->billing_first_name,
				'lastName' => $order->billing_last_name,
				'phone' => $order->billing_phone 
		);
		$customerId = $this->getBraintreeCustomerId ( wp_get_current_user ()->ID );
		if ($customerId) {
			$attribs ['customerId'] = $customerId;
		}
		return $attribs;
	}
	public function getDropinContainer() {
		include WC_BRAINTREE_PLUGIN . 'payments/forms/dropin-container.php';
	}
	public function getDonationDropinContainer() {
		include WC_BRAINTREE_PLUGIN . 'payments/forms/donation-form.php';
	}
	public function getPayPalContainer() {
		echo '<div id="dropin-container"></div>';
	}
	public static function getOrderStatuses() {
		return array (
				'wc-pending' => _x ( 'Pending Payment', 'Order status', 'woocommerce' ),
				'wc-processing' => _x ( 'Processing', 'Order status', 'woocommerce' ),
				'wc-on-hold' => _x ( 'On Hold', 'Order status', 'woocommerce' ),
				'wc-completed' => _x ( 'Completed', 'Order status', 'woocommerce' ),
				'wc-cancelled' => _x ( 'Cancelled', 'Order status', 'woocommerce' ),
				'wc-refunded' => _x ( 'Refunded', 'Order status', 'woocommerce' ),
				'wc-failed' => _x ( 'Failed', 'Order status', 'woocommerce' ) 
		);
	}
	public static function getSubscriptionStatuses() {
		$subscription_statuses = array (
				'wc-pending' => _x ( 'Pending', 'Subscription status', 'woocommerce-subscriptions' ),
				'wc-active' => _x ( 'Active', 'Subscription status', 'woocommerce-subscriptions' ),
				'wc-on-hold' => _x ( 'On hold', 'Subscription status', 'woocommerce-subscriptions' ),
				'wc-cancelled' => _x ( 'Cancelled', 'Subscription status', 'woocommerce-subscriptions' ),
				'wc-switched' => _x ( 'Switched', 'Subscription status', 'woocommerce-subscriptions' ),
				'wc-expired' => _x ( 'Expired', 'Subscription status', 'woocommerce-subscriptions' ),
				'wc-pending-cancel' => _x ( 'Pending Cancellation', 'Subscription status', 'woocommerce-subscriptions' ) 
		);
		return $subscription_statuses;
	}
	
	/**
	 * Load the Braintree customer's payment methods if there are any.
	 */
	public function loadCustomerPaymentMethods() {
		if (! $user_id = wp_get_current_user ()->ID) {
			return;
		}
		$customerId = BT_Manager ()->getBraintreeCustomerId ( $user_id );
		if ($customerId) {
			$customer = BT_Manager ()->getBraintreeCustomer ( $user_id, $customerId );
			if ($customer) {
				$paymentMethods = $customer->paymentMethods;
				$customerMethods = array ();
				if ($paymentMethods) {
					foreach ( $paymentMethods as $index => $paymentMethod ) {
						$method = array ();
						if ($paymentMethod instanceof Braintree_CreditCard) {
							$method ['type'] = $paymentMethod->cardType;
							$method ['description'] = $paymentMethod->cardType . ' ' . $paymentMethod->maskedNumber;
							$method ['token'] = $paymentMethod->token;
							$method ['default'] = $paymentMethod->default;
							$method ['last4'] = $paymentMethod->last4;
							$method ['expires'] = $paymentMethod->expirationDate;
						}
						if ($paymentMethod instanceof Braintree_PayPalAccount) {
							$method ['type'] = 'paypal';
							$method ['description'] = 'PayPal - ' . $paymentMethod->email;
							$method ['token'] = $paymentMethod->token;
							$method ['default'] = $paymentMethod->default;
							$method ['expires'] = __ ( 'N/A', 'braintree' );
						}
						$customerMethods ['payment_method_' . $index] = $method;
					}
				}
				update_user_meta ( $user_id, 'braintree_payment_methods', $customerMethods );
			}
		}
	}
	public function customerHasPaymentMethods() {
		$bool = false;
		$user_id = wp_get_current_user ()->ID;
		$paymentMethods = get_user_meta ( $user_id, 'braintree_payment_methods', true );
		if ($paymentMethods) {
			$bool = true;
		}
		return $bool;
	}
	public function getPaymentMethodFromRequest($attribs = array()) {
		$paymentMethod = $this->getRequestParameter ( 'selected_payment_method' );
		if (! empty ( $paymentMethod )) {
			$paymentMethods = get_user_meta ( wp_get_current_user ()->ID, 'braintree_payment_methods', true );
			$attribs ['paymentMethodToken'] = $paymentMethods [$paymentMethod] ['token'];
		} else {
			$attribs ['paymentMethodNonce'] = $this->getRequestParameter ( 'payment_method_nonce' );
		}
		return $attribs;
	}
	
	/**
	 * Create the Payment Method in the Braintree environment.
	 *
	 * @param array $attribs        	
	 */
	public function createBraintreePaymentMethod($attribs) {
		$response = null;
		try {
			$response = Braintree_PaymentMethod::create ( $attribs );
		} catch ( Exception $e ) {
			$this->log->writeErrorToLog ( $e->getMessage () );
		}
		return $response;
	}
	
	/**
	 *
	 * @param Braintree_MerchantAccount $merchant_account        	
	 */
	public function getBraintreeMerchantAccount($merchant_account) {
		$merchantAccount = false;
		try {
			$merchantAccount = Braintree_MerchantAccount::find ( $merchant_account );
		} catch ( Exception $e ) {
			BT_Manager ()->log->writeErrorToLog ( sprintf ( 'There was an error fetching the 
					merchant account %s. Message: %s', $merchant_account, $e->getMessage () ) );
		}
		return $merchantAccount;
	}
	
	/**
	 * Method that determines if the post is a Braintree Subscription.
	 *
	 * @param int $post_id        	
	 */
	public function isProductBraintreeOnlySubscription($post_id) {
		return get_post_meta ( $post_id, 'braintree_subscription', true ) === 'yes';
	}
	
	/**
	 * Save the subscription meta for a synchronized subscription.
	 *
	 * @param WC_Order $order        	
	 * @param array $attribs        	
	 */
	public function maybeSaveSynchronizedSubscription(WC_Order $order, $attribs = array()) {
		if ($this->subscriptionsActive () && wcs_order_contains_subscription ( $order )) {
			$paymentMethod = $this->getBraintreePaymentMethod ( $attribs ['paymentMethodToken'] );
			$paymentMethodTitle = $this->getPaymentMethodTitle ( $paymentMethod );
			$subscriptions = wcs_get_subscriptions_for_order ( $order );
			foreach ( $subscriptions as $subscription ) {
				update_post_meta ( $subscription->id, '_payment_method_token', $attribs ['paymentMethodToken'] );
				update_post_meta ( $subscription->id, '_merchant_account_id', $attribs ['merchantAccountId'] );
				update_post_meta ( $subscription->id, '_payment_method_title', $paymentMethodTitle );
			}
			update_post_meta ( $order->id, '_payment_method_token', $attribs ['paymentMethodToken'] );
			update_post_meta ( $order->id, '_merchant_account_id', $attribs ['merchantAccountId'] );
			update_post_meta ( $order->id, '_payment_method_title', $paymentMethodTitle );
		}
	}
	
	/**
	 * Test the API key by requesting a client token from Braintree for the given environment.
	 *
	 * @param unknown $environment        	
	 */
	public function testBraintreeConnection($environment) {
		Braintree_Configuration::environment ( $environment );
		Braintree_Configuration::privateKey ( $this->get_option ( $environment . '_private_key' ) );
		Braintree_Configuration::publicKey ( $this->get_option ( $environment . '_public_key' ) );
		Braintree_Configuration::merchantId ( $this->get_option ( $environment . '_merchant_id' ) );
		try {
			$clientToken = Braintree_ClientToken::generate ();
			$this->addAdminNotice ( array (
					'type' => 'success',
					'text' => sprintf ( __ ( 'The connection test for your Braintree %s environment was successful. ', 'braintree' ), $environment ) 
			) );
		} catch ( Exception $e ) {
			$this->addAdminNotice ( array (
					'type' => 'error',
					'text' => sprintf ( __ ( 'The connection test for your Braintree %s environment was unsuccessful. Please check your API key entries and try 
				  		again.', 'braintree' ), $environment ) 
			) );
		}
	}
	
	/**
	 * Return the dynamic descriptor for the product.
	 * It does not matter if there is a variation_id, the product_id
	 * will always be fetched since the descriptor is set on the general product data tab.
	 *
	 * @param WC_Order $order        	
	 */
	public function getProductDynamicDescriptor($order) {
		$items = $order->get_items ();
		foreach ( $items as $item ) {
			$product_id = $item ['product_id'];
			break;
		}
		return get_post_meta ( $product_id, 'dynamic_descriptor_name', true );
	}
	
	/**
	 * Add the message to the transient value.
	 *
	 * @param unknown $message        	
	 */
	public function addAdminNotice($message) {
		$messages = get_transient ( 'braintree_for_woocommerce_admin_notices' );
		if (! $messages) {
			$messages = array ();
		}
		$messages [] = $message;
		set_transient ( 'braintree_for_woocommerce_admin_notices', $messages );
	}
	
	/**
	 * Return the admin notices if any.
	 */
	public function getAdminNotices() {
		return get_transient ( 'braintree_for_woocommerce_admin_notices' );
	}
	public function deleteAdminNotices() {
		delete_transient ( 'braintree_for_woocommerce_admin_notices' );
	}
	
	/**
	 * Return the Braintree_Transaction object using the given transaction_id.
	 *
	 * @param string $transaction_id        	
	 * @return Braintree_Transaction
	 */
	public function getBraintreeTransaction($transaction_id) {
		$transaction = null;
		try {
			$transaction = Braintree_Transaction::find ( $transaction_id );
		} catch ( Braintree_Exception_NotFound $e ) {
			$this->log->writeErrorToLog ( sprintf ( 'Transaction %s could not be found in the Braintree environment.', $transaction_id ) );
		} catch ( Exception $e ) {
			$this->log->writeErrorToLog ( sprintf ( 'Error Retrieving Transaction. Error: %s', print_r ( $e, true ) ) );
		}
		return $transaction;
	}
	
	/**
	 * Return an array of payment methods saved to the usermeta table.
	 *
	 * @param int $user_id        	
	 */
	public function getSavedPaymentMethods($user_id) {
		return get_user_meta ( $user_id, 'braintree_payment_methods', true );
	}
	
	/**
	 * Delete the Braintree payment method.
	 *
	 * @param int $user_id        	
	 * @param string $token        	
	 */
	public function deletePaymentMethod($user_id, $token) {
		try {
			PaymentMethod::delete ( $token );
			$this->removePaymentMethodFromMeta ( $user_id, $token );
		} catch ( Braintree_Exception $e ) {
			$this->log->writeErrorToLog ( sprintf ( 'Payment method %s could not be deleted for user %s. Reason: %s', $token, $user_id, $e->getMessage () ) );
		}
	}
	public function removePaymentMethodFromMeta($user_id, $token) {
		$methods = $this->getSavedPaymentMethods ( $user_id );
		foreach ( $methods as $index => $method ) {
			if ($method ['token'] === $token) {
				unset ( $methods [$index] );
			}
		}
		update_user_meta ( $user_id, 'braintree_payment_methods', $methods );
	}
	
	/**
	 * Retrieve the Braintree transaction.
	 *
	 * @param string $id        	
	 * @return Braintree_Transaction
	 */
	public function get_braintree_transaction($id) {
		$transaction = null;
		try {
			$transaction = Braintree_Transaction::find ( $id );
		} catch ( Braintree_Exception_NotFound $e ) {
			$this->log->writeErrorToLog ( sprintf ( 'Transaction %s was not found in the Braintree environment.', $id ) );
		} catch ( Exception $e ) {
			$this->log->writeErrorToLog ( sprintf ( 'Transaction %s was not found in the Braintree environment. Reason: %s', $id, $e->getMessage () ) );
		}
		return $transaction;
	}
}

/**
 * Function that returns an instance of the Braintree_Manager class.
 * If there is no instance, then a new instance is
 * instantiated and asigned to the static variable $_instance of class Braintree_Manager.
 *
 * @return Braintree_Manager
 */
function BT_Manager() {
	return Braintree_Manager::instance ();
}