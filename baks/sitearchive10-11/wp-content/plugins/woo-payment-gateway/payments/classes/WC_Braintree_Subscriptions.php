<?php
use Braintree\Exception;
if (! defined ( 'ABSPATH' )) {
	exit (); // Exit if accessed directly
}
/**
 * Braintree class that handles all subscription payment functionality.
 *
 * @author Clayton Rogers
 *        
 */
class WC_Braintree_Subscriptions extends WC_Braintree_Payments {
	public function __construct() {
		parent::__construct ();
	}
	
	/**
	 * Assign all necessary actions and filters.
	 */
	public static function init() {
		/* Cancel the subscription */
		add_action ( 'woocommerce_subscription_pending-cancel_' . self::gatewayName, __CLASS__ . '::cancelSubscription' );
		add_action ( 'woocommerce_subscription_pending-cancel_' . self::paypal_gateway_name, __CLASS__ . '::cancelSubscription' );
		
		/* Cancel the subscription. */
		add_action ( 'woocommerce_subscription_cancelled_' . self::gatewayName, __CLASS__ . '::cancelSubscription', 10, 1 );
		add_action ( 'woocommerce_subscription_cancelled_' . self::paypal_gateway_name, __CLASS__ . '::cancelSubscription', 10, 1 );
		
		/* Recurring payment charge. */
		add_action ( 'woocommerce_scheduled_subscription_payment_' . self::gatewayName, __CLASS__ . '::processRecurringPayment', 10, 2 );
		add_action ( 'woocommerce_scheduled_subscription_payment_' . self::paypal_gateway_name, __CLASS__ . '::processRecurringPayment', 10, 2 );
		
		/* Payment method change. */
		add_action ( 'woocommerce_subscription_payment_method_updated_to_' . self::gatewayName, __CLASS__ . '::updatePaymentMethod', 10, 2 );
		add_action ( 'woocommerce_subscription_payment_method_updated_to_' . self::paypal_gateway_name, __CLASS__ . '::updatePaymentMethod', 10, 2 );
		
		/* Cancel subscription if new gateway is used. */
		add_action ( 'woocommerce_subscription_payment_method_updated_from_' . self::gatewayName, __CLASS__ . '::cancelSubscriptionForOldPaymentMethod', 10, 2 );
		add_action ( 'woocommerce_subscription_payment_method_updated_from_' . self::paypal_gateway_name, __CLASS__ . '::cancelSubscriptionForOldPaymentMethod', 10, 2 );
		
		/* Display payment method on account page. */
		add_action ( 'woocommerce_my_subscriptions_payment_method', __CLASS__ . '::displayPaymentMethod', 10, 2 );
		
		add_filter ( 'woocommerce_subscription_payment_gateway_supports', __CLASS__ . '::addSupportIfBraintreeSubscription', 10, 3 );
		
		/* Save the subscription meta if the Braintree Subscriptions option is enabled. */
		if (BT_Manager ()->isActive ( 'braintree_subscriptions' )) {
			
			add_action ( 'save_post', __CLASS__ . '::saveAdminSubscriptionMeta', 99 );
			
			add_action ( 'wp_ajax_add_braintree_plan', __CLASS__ . '::addBraintreeSubscriptionPlan' );
			
			add_action ( 'wp_ajax_remove_braintree_plan', __CLASS__ . '::removeBraintreeSubscriptionPlan' );
			
			if (! BT_Manager ()->subscriptionsActive ()) {
				
				add_filter ( 'woocommerce_get_price_html', __CLASS__ . '::getSubscriptionPriceHTML', 10, 2 );
				
				add_filter ( 'woocommerce_order_formatted_line_subtotal', __CLASS__ . '::formatLineSubtotal', 10, 3 );
				
				add_filter ( 'woocommerce_cart_product_price', __CLASS__ . '::cartSubscriptionPrice', 99, 2 );
				
				add_filter ( 'woocommerce_cart_product_subtotal', __CLASS__ . '::getProductSubtotal', 99, 4 );
			}
			
			add_filter ( 'woocommerce_subscription_lengths', __CLASS__ . '::addSubscriptionRanges', 10, 2 );
			
			if (self::subscriptionsActive ()) {
				
				add_action ( 'woocommerce_ajax_save_product_variations', __CLASS__ . '::saveVariableSubscriptionMeta', 99, 1 );
				
				add_filter ( 'woocommerce_add_to_cart_validation', __CLASS__ . '::validateAddToCart', 10, 3 );
				
				add_filter ( 'woocommerce_subscriptions_product_period_interval', __CLASS__ . '::getProductInterval', 10, 2 );
				
				add_filter ( 'woocommerce_subscriptions_product_length', __CLASS__ . '::getProductLength', 10, 2 );
				
				add_filter ( 'woocommerce_subscriptions_product_period', __CLASS__ . '::getProductPeriod', 10, 2 );
			} else {
				
				add_filter ( 'woocommerce_add_to_cart_validation', __CLASS__ . '::validateCartEntries', 10, 3 );
			}
		}
	}
	
	/**
	 * Save the subscription meta for the given post_id.
	 * This method is used for changes made by the admin to the subscription
	 * product.
	 *
	 * @param int $post_id        	
	 */
	public static function saveAdminSubscriptionMeta($post_id) {
		if (! isset ( $_POST ['product-type'] )) {
			return;
		}
		
		$braintree_subscription = BT_Manager ()->getRequestParameter ( 'braintree_subscription' );
		
		update_post_meta ( $post_id, 'braintree_subscription', $braintree_subscription );
		
		if (BT_Manager ()->subscriptionsActive ()) {
			if ($braintree_subscription === 'yes') {
				$plan = BT_Manager ()->getBraintreePlan ( BT_Manager ()->getProductPlanId ( $post_id ) );
				
				if ($plan) {
					update_post_meta ( $post_id, '_subscription_period_interval', $plan->billingFrequency );
					update_post_meta ( $post_id, '_subscription_period', 'month' );
					if ($plan->numberOfBillingCycles == null) {
						update_post_meta ( $post_id, '_subscription_length', 0 );
					} else {
						update_post_meta ( $post_id, '_subscription_length', $plan->numberOfBillingCycles * $plan->billingFrequency );
					}
				}
			}
		}
	}
	public static function addSubscriptionRanges($subscription_ranges, $period) {
		$count = count ( $subscription_ranges ['month'] );
		$subscription_length = 60;
		for($i = $count; $i <= $subscription_length; $i ++) {
			$subscription_ranges ['month'] [] = wcs_get_subscription_period_strings ( $i, 'month' );
		}
		return $subscription_ranges;
	}
	public static function getProductInterval($subscription_period_interval, WC_Product $product) {
		if ($product instanceof WC_Product_Variable_Subscription) {
			if (self::isProductSubscription ( $product->children ['visible'] [0] )) {
				$plan = BT_Manager ()->getBraintreePlan ( BT_Manager ()->getProductPlanId ( $product->children ['visible'] [0] ) );
				if ($plan) {
					
					$subscription_period_interval = $plan->billingFrequency;
				}
			}
		}
		if ($product instanceof WC_Product_Subscription_Variation && self::isProductSubscription ( $product->variation_id )) {
			$plan = BT_Manager ()->getBraintreePlan ( BT_Manager ()->getProductPlanId ( $product->variation_id ) );
			if ($plan) {
				
				$subscription_period_interval = $plan->billingFrequency;
			}
		}
		return $subscription_period_interval;
	}
	public static function getProductLength($subscription_length, WC_Product $product) {
		if ($product instanceof WC_Product_Variable_Subscription) {
			if (self::isProductSubscription ( $product->children ['visible'] [0] )) {
				$plan = BT_Manager ()->getBraintreePlan ( BT_Manager ()->getProductPlanId ( $product->children ['visible'] [0] ) );
				if ($plan) {
					
					$subscription_length = $plan->billingFrequency * $plan->numberOfBillingCycles;
				}
			}
		}
		
		if ($product instanceof WC_Product_Subscription_Variation && self::isProductSubscription ( $product->variation_id )) {
			$plan = BT_Manager ()->getBraintreePlan ( BT_Manager ()->getProductPlanId ( $product->variation_id ) );
			if ($plan) {
				
				$subscription_length = $plan->billingFrequency * $plan->numberOfBillingCycles;
			}
		}
		return $subscription_length;
	}
	public static function getProductPeriod($period, WC_Product $product) {
		return __ ( 'month', 'braintree' );
	}
	
	/**
	 * Return true if the order contains a subscription item.
	 *
	 * @param WC_Order|int $order        	
	 */
	public static function orderContainsSubscription($order) {
		$isSubscription = false;
		if (! is_object ( $order )) {
			$order = wc_get_order ( $order );
		}
		$items = $order->get_items ();
		foreach ( $items as $item ) {
			$product_id = $item ['product_id'];
			if (WC_Subscriptions_Product::is_subscription ( $product_id )) {
				$isSubscription = true;
			}
		}
		return $isSubscription;
	}
	
	/**
	 * Method that is called during subscription cancellation.
	 * If the subscription exists in Braintree's system,
	 * it will be deleted.
	 *
	 * @param WC_Subscription $subscription        	
	 */
	public static function cancelSubscription(WC_Subscription $subscription) {
		if (self::isPaymentChangeRequest ()) {
			return;
		}
		if (self::isBraintreeSubscription ( $subscription )) {
			return BT_Manager ()->cancelBraintreeSubscription ( $subscription );
		} else {
			return BT_Manager ()->cancelWooCommerceSubscription ( $subscription );
		}
	}
	
	/**
	 * Return a list of configured Braintree Plans.
	 *
	 * @return Braintree_Plan[]
	 */
	public static function getBraintreePlans() {
		$result = array ();
		$plans = BT_Manager ()->getBraintreePlans ();
		return $plans;
	}
	
	/**
	 * Add the braintree subscription plan to the subscription.
	 */
	public static function addBraintreeSubscriptionPlan() {
		$post_id = $_POST ['post_id'];
		$braintree_plan = $_POST ['braintree_plan'];
		$currency = $_POST ['currency_code'];
		$saved_plans = get_post_meta ( $post_id, 'braintree_plans', true );
		if (! is_array ( $saved_plans )) {
			$saved_plans = array ();
		}
		$saved_plans [$currency] = $braintree_plan;
		update_post_meta ( $post_id, 'braintree_plans', $saved_plans );
		
		ob_start ();
		WCS_BT_Meta_Box::doBraintreeSavedPlansOutput ( $post_id );
		$html = ob_get_clean ();
		
		$response = array (
				'result' => 'success',
				'html' => $html 
		);
		
		wp_send_json ( $response );
	}
	public static function removeBraintreeSubscriptionPlan() {
		$post_id = $_POST ['post_id'];
		$currency = $_POST ['currency_code'];
		$saved_plans = get_post_meta ( $post_id, 'braintree_plans', true );
		unset ( $saved_plans [$currency] );
		update_post_meta ( $post_id, 'braintree_plans', $saved_plans );
		
		ob_start ();
		WCS_BT_Meta_Box::doBraintreeSavedPlansOutput ( $post_id );
		$html = ob_get_clean ();
		
		$response = array (
				'result' => 'success',
				'html' => $html 
		);
		
		wp_send_json ( $response );
	}
	
	/**
	 * Save the variable data associated with the variable subscription product.
	 */
	public static function saveVariableSubscriptionMeta($post_id) {
		$variation_ids = BT_Manager ()->getRequestParameter ( 'variable_post_id' );
		
		$braintree_subscription = BT_Manager ()->getRequestParameter ( 'braintree_subscription' );
		
		foreach ( $variation_ids as $i => $variation_id ) {
			
			$braintree_subscription = $braintree_subscription [$i];
			
			update_post_meta ( $variation_id, 'braintree_subscription', $braintree_subscription );
			
			if (BT_Manager ()->subscriptionsActive ()) {
				if ($braintree_subscription === 'yes') {
					$plan = BT_Manager ()->getBraintreePlan ( BT_Manager ()->getProductPlanId ( $variation_id ) );
					
					if ($plan) {
						update_post_meta ( $variation_id, '_subscription_period_interval', $plan->billingFrequency );
						update_post_meta ( $variation_id, '_subscription_period', 'month' );
						if ($plan->numberOfBillingCycles == null) {
							update_post_meta ( $variation_id, '_subscription_length', 0 );
						} else {
							update_post_meta ( $variation_id, '_subscription_length', $plan->numberOfBillingCycles * $plan->billingFrequency );
						}
					}
				}
			}
		}
	}
	
	/**
	 * If Braintree subscriptions is enabled and WooCommerce subscriptions is enabled, validate that the cart doesn't already contain the same product.
	 * If it does,
	 * do not allow the customer to add the product.
	 *
	 * @param bool $is_valid        	
	 * @param WC_Product $product        	
	 * @param int $quantity        	
	 */
	public static function validateAddToCart($is_valid, $product_id, $quantity) {
		if (WC_Subscriptions_Product::is_subscription ( $product_id )) {
			
			$cartCount = WC ()->cart->get_cart_contents_count ();
			
			if ($quantity > 1) { // Cant' add the same subscription to the cart.
				$is_valid = false;
				wc_add_notice ( __ ( 'The same subscription cannot be added twice to your cart.', 'braintree' ), 'error' );
			} else if ($cartCount > 0 && ! WC_Subscriptions_Cart::cart_contains_subscription ()) { // Cart only has products and products and subscriptions cannot be mixed.
				$is_valid = false;
				wc_add_notice ( __ ( 'You cannot mix subscriptions and products in your shopping cart.', 'braintree' ), 'error' );
			} else {
				foreach ( WC ()->cart->get_cart () as $cart => $values ) { // Check all the items in the cart and make sure the same subscription isn't already in the cart.
					$_product = $values ['data'];
					if ($_product->id === $product_id) {
						wc_add_notice ( __ ( 'You cannot add the same subscription product to your cart.', 'braintree' ), 'error' );
						$is_valid = false;
					}
				}
			}
		} else {
			if (WC_Subscriptions_Cart::cart_contains_subscription ()) {
				$is_valid = false;
				wc_add_notice ( __ ( 'Products and subscriptions cannot be mixed in your shopping cart.', 'brintree' ), 'error' );
			}
		}
		return $is_valid;
	}
	
	/**
	 * Validate that the product being added does not conflict with other products.
	 * Braintree Subscriptions can not be
	 * added with Products but they can be added together.
	 *
	 * @param bool $is_valid        	
	 * @param int $product_id        	
	 * @param int $quantity        	
	 */
	public static function validateCartEntries($is_valid, $product_id, $quantity) {
		if (self::isProductSubscription ( $product_id )) {
			if ($quantity > 1) { // Can't add the same subscription twice.
				$is_valid = false;
				wc_add_notice ( __ ( 'You cannot add more than one subscription to your cart at a time.', 'braintree' ), 'error' );
				return $is_valid;
			}
			if (WC ()->cart->get_cart_contents_count () >= 1) { // Cart cannot contain multiple items.
				$is_valid = false;
				wc_add_notice ( __ ( 'You cannot have more than one item in your cart when it\'s a subscription.', 'braintree' ), 'error' );
				return $is_valid;
			}
		} else {
			if (self::cartContainsSubscriptions ()) {
				$is_valid = false;
				wc_add_notice ( __ ( 'Products and subscriptions cannot be mixed in your shopping cart.', 'braintree' ), 'error' );
			}
		}
		return $is_valid;
	}
	
	/**
	 * Return true if the subscription is a Braintree subscription, false otherwise.
	 *
	 * @param WC_Subscription $subscription        	
	 * @return bool $isBraintree;
	 */
	public static function isBraintreeSubscription(WC_Order $subscription) {
		$isBraintreeSubscription = false;
		$subscriptionType = get_post_meta ( $subscription->id, '_subscription_type', true );
		if ($subscriptionType === 'braintree') {
			$isBraintreeSubscription = true;
		}
		return $isBraintreeSubscription;
	}
	
	/**
	 * If the subscription is of type "braintree" then there is no need to process the payment since recurring payment occurs automatically.
	 * If however, the subscription is not of type "braintree", process the payment.
	 *
	 * @param int $amount        	
	 * @param WC_Order $order        	
	 */
	public static function processRecurringPayment($amount, WC_Order $order) {
		if (self::isBraintreeSubscription ( $order )) {
			return true;
		}
		$subscription = wcs_get_subscription ( $order->subscription_renewal );
		$attribs = array (
				'amount' => $amount,
				'options' => array (
						'submitForSettlement' => BT_Manager()->isActive('authorize_transaction') ? false : true 
				),
				'paymentMethodToken' => $subscription != null ? get_post_meta ( $subscription->id, '_payment_method_token', true ) : get_post_meta ( $order->id, '_payment_method_token', true ) 
		);
		self::addBillingAddress ( $attribs, $order );
		self::addShippingAddress ( $attribs, $order );
		self::addSubscriptionMerchantAccountId ( $attribs, $order );
		$result = BT_Manager ()->sale ( $attribs );
		if ($result instanceof Exception) {
			BT_Manager ()->log->writeErrorToLog ( sprintf ( 'Subscription Payment Error. Exception: %s', print_r ( $e, true ) ) );
			$order->add_order_note ( sprintf ( 'Recurring payment for subscription failed. Message: ', $result->getMessage () ), 0, false );
			if ($subscription != null) {
				$subscription->update_status ( BT_Manager ()->get_option ( 'subscriptions_payment_failed_status' ) );
			}
		} else {
			if ($result->success) {
				$order->add_order_note ( sprintf ( 'Recurring payment charged for subscription. Transaction ID: %s', $result->transaction->id ), 0, false );
				update_post_meta ( $order->id, '_transaction_id', $result->transaction->id );
				$order->update_status ( BT_Manager ()->get_option ( 'order_status' ) );
				$order->payment_complete();
				BT_Manager ()->log->writeToLog ( sprintf ( 'Subscription Payment Success. Message: %s', print_r ( $result, true ) ) );
				if ($subscription != null) {
					$subscription->payment_complete();
				}
				BT_Manager ()->saveTransactionMeta ( $result->transaction, $order );
			} else {
				$order->add_order_note ( sprintf ( 'Recurring payment for order failed. Message: %s', $result->message ), 0, false );
				BT_Manager ()->log->writeToLog ( sprintf ( 'Subscription Payment Error. Message: %s', print_r ( $result, true ) ) );
				if ($subscription != null) {
					$subscription->update_status ( BT_Manager ()->get_option ( 'subscriptions_payment_failed_status' ) );
				}
			}
		}
	}
	
	/**
	 * Method that is called when a payment method is being updated for the subscription.
	 *
	 * @param string $old_payment_method        	
	 */
	public static function updatePaymentMethod(WC_Subscription $subscription, $old_payment_method) {
		$user_id = wp_get_current_user ()->ID;
		$result = array ();
		if (! $paymentMethod = self::getPaymentMethodFromChangeRequest ( $subscription )) {
			BT_Manager ()->log->writeToLog ( sprintf ( 'There was an error updating the payment method for user %s', $user_id ) );
			$result = BT_Manager ()->handleWCError ( __ ( 'There was an error updating the payment method.', 'braintree' ) );
			return false;
		}
		/* Update the Braintree Subscription */
		if (self::isBraintreeSubscription ( $subscription )) {
			if (self::updateBraintreeSubscription ( $subscription, $paymentMethod ['token'] )) {
				update_post_meta ( $subscription->id, '_payment_method_token', $paymentMethod ['token'] );
				update_post_meta ( $subscription->id, '_payment_method_title', $paymentMethod ['description'] );
				wc_add_notice ( __ ( 'Your payment method was updated successfully.', 'braintree' ), 'success' );
			} else {
				wc_add_notice ( __ ( 'Your payment method could not be updated at this time.', 'braintree' ), 'error' );
			}
		} else { /* If not a Braintree subscription, then simply update the meta data. */
			update_post_meta ( $subscription->id, '_payment_method_token', $paymentMethod ['token'] );
			update_post_meta ( $subscription->id, '_payment_method_title', $paymentMethod ['description'] );
			wc_add_notice ( __ ( 'Your payment method was updated successfully.', 'braintree' ), 'success' );
		}
		return $result;
	}
	
	/**
	 * Return the Braintree Subscription ID saved to the subscription meta.
	 *
	 * @param unknown $user_id        	
	 */
	public static function getBraintreeSubscriptionId($subscription_id) {
		return get_post_meta ( $subscription_id, '_subscription_id', true );
	}
	public static function getPaymentMethodFromChangeRequest(WC_Order $order) {
		$paymentMethod = array ();
		if ($_REQUEST ['payment_method'] === self::gatewayName &&
				$token = self::getRequestParameter ( 'selected_payment_method' )) {
			$paymentMethods = get_user_meta ( wp_get_current_user ()->ID, 'braintree_payment_methods', true );
			$paymentMethod = $paymentMethods [$token];
		} else {
			$response = BT_Manager ()->createBraintreePaymentMethod ( array (
					'paymentMethodNonce' => self::getRequestParameter ( 'payment_method_nonce' ),
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
				$newMethod = $response->paymentMethod;
				if ($newMethod instanceof Braintree_CreditCard) {
					$paymentMethod ['type'] = $newMethod->cardType;
					$paymentMethod ['description'] = $newMethod->cardType . ' ' . $newMethod->maskedNumber;
					$paymentMethod ['token'] = $newMethod->token;
				}
				if ($newMethod instanceof Braintree_PayPalAccount) {
					$paymentMethod ['type'] = 'paypal';
					$paymentMethod ['description'] = 'PayPal - ' . $newMethod->email;
					$paymentMethod ['token'] = $newMethod->token;
				}
			} else {
				BT_Manager ()->log->writeToLog ( sprintf ( 'The payment method for userId %s could not be created. Payment method
							creation failed.', $user_id ) );
				wc_add_notice ( $response->message, 'error' );
			}
		}
		return $paymentMethod;
	}
	
	/**
	 * Create the Braintree Subscription using the WC_Subscription object.
	 *
	 * @param WC_Subscription $subscription        	
	 */
	public static function createBraintreeSubscription(WC_Subscription $subscription, $token) {
		if (! $planId = BT_Manager ()->getSubscriptionPlanId ( $subscription )) {
			BT_Manager ()->handleWCError ( __ ( 'You cannot use this payment gateway to change the subscription. There is not a valid
					Plan Id configured for the subscription.', 'braintree' ) );
			BT_Manager ()->log->writeToLog ( 'Method: WC_Braintree_Subscriptions::createBraintreeSubscription(). There are no planId configured for the subscription.' );
			return false;
		}
		$attribs = array (
				'paymentMethodToken' => $token,
				'planId' => $planId,
				'price' => BT_Manager ()->getSubscriptionPrice ( $subscription ),
				'firstBillingDate' => BT_Manager ()->getSubscriptionDate ( $subscription, 'next_payment' ) 
		);
		self::addMerchantAccountId ( $attribs );
		self::getCustomerObject ( $attribs, $subscription );
		try {
			$response = Braintree_Subscription::create ( $attribs );
			if ($response->success) {
				BT_Manager ()->saveBraintreeSubscriptionMeta ( $subscription, $response->subscription );
				$result = true;
			} else {
				throw new Exception ( $response->message );
			}
		} catch ( Exception $e ) {
			BT_Manager ()->log->writeErrorToLog ( $e->getMessage () );
			$result = false;
		}
		return $result;
	}
	
	/**
	 * Update the Braintree subscription.
	 *
	 * @param WC_Subscription $subscription        	
	 * @param string $token        	
	 */
	public static function updateBraintreeSubscription(WC_Subscription $subscription, $token) {
		$attribs = array (
				'paymentMethodToken' => $token 
		);
		return BT_Manager ()->updateBraintreeSubscription ( self::getBraintreeSubscriptionId ( $subscription->id ), $attribs );
	}
	
	/**
	 * Cancel the Braintree subscription, if it exists.
	 *
	 * @param WC_Subscription $subscription        	
	 * @param unknown $new_payment_method        	
	 */
	public static function cancelSubscriptionForOldPaymentMethod(WC_Subscription $subscription, $new_payment_method) {
		if (self::gatewayName === $new_payment_method) {
			return;
		}
		if (self::isBraintreeSubscription ( $subscription )) {
			if (BT_Manager ()->cancelBraintreeSubscription ( $subscription, false )) {
				update_post_meta ( $subscription->id, '_subscription_type', 'woocommerce' );
			}
		}
	}
	public static function displayPaymentMethod($payment_method_to_display, WC_Subscription $subscription) {
		if ($paymentMethod = get_post_meta ( $subscription->id, '_payment_method_title', true )) {
			$payment_method_to_display = $paymentMethod;
		}
		return $payment_method_to_display;
	}
	
	/**
	 * Method that determines if the post is a Braintree Subscription.
	 *
	 * @param int $post_id        	
	 */
	public static function isProductSubscription($post_id) {
		return get_post_meta ( $post_id, 'braintree_subscription', true ) === 'yes';
	}
	public static function orderContainsBraintreeSubscription($order_id) {
		$order = wc_get_order ( $order_id );
		$items = $order->get_items ();
		$isSubscription = false;
		foreach ( $items as $item ) {
			$product_id = $item ['product_id'];
			if (self::isProductSubscription ( $product_id )) {
				$isSubscription = true;
			}
		}
		return $isSubscription;
	}
	
	/**
	 * Create the Braintree Subscription using the WC_Order.
	 * There is a transaction charge if the order total is not zero. If WooCommerce Subscriptions is enabled, create a subscription for each Subscription object.
	 * When WooCommerce subscriptions is not enabled, there can only be one Braintree Subscription per order because the subscription name is created using the order. This
	 * makes webhooks much more efficient because the post_status can be updated directly using the subscription Id from Braintree, rather then having to search
	 * for the subscription id in the post_meta table.
	 *
	 * @param WC_Order $order        	
	 */
	public static function createBraintreeOnlySubscriptions(WC_Order $order) {
		if (! wp_get_current_user ()->ID) {
			return self::wooCoomerceError ( __ ( 'In order to purchase a subscription, you must first create an account.', 'braintree' ) );
		}
		
		$attribs = array ();
		
		if (self::getRequestParameter ( 'payment_method_nonce' )) {
			$response = BT_Manager ()->createBraintreePaymentMethod ( array (
					'paymentMethodNonce' => self::getRequestParameter ( 'payment_method_nonce' ),
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
			if (! $response->success) {
				return self::wooCoomerceError ( $response->message );
			}
			$attribs ['paymentMethodToken'] = $response->paymentMethod->token;
		} else {
			$attribs = BT_Manager ()->getPaymentMethodFromRequest ( $attribs );
		}
		
		// Process the transaction before creating the subscriptions. Only applicable if WooCommerce Subscriptions is enabled.
		if (BT_Manager ()->subscriptionsActive () && self::OrderNeedsPayment ( $order ) && ($total = self::getOrderTotal ( $order ))) {
			$attribs = self::createOrderAttributes ( $order );
			$attribs ['amount'] = $total;
			$attribs ['orderId'] = BT_Manager ()->get_option ( 'woocommerce_subscriptions_prefix' ) . $order->id;
			try {
				$response = Braintree_Transaction::sale ( $attribs );
				if (! $response->success) {
					wc_add_notice ( $response->message, 'error' );
					return BT_Manager ()->handleResponseError ( $response );
				} else {
					BT_Manager ()->saveTransactionMeta ( $response->transaction, $order );
					$attribs = array ();
					$attribs ['paymentMethodToken'] = BT_Manager ()->getPaymentMethodToken ( $response->transaction );
				}
			} catch ( Exception $e ) {
				return BT_Manager ()->handleResponseError ( $response );
			}
		}
		
		// Create the subscriptions
		if (self::subscriptionsActive ()) {
			
			$subscriptions = wcs_get_subscriptions_for_order ( $order->id );
			
			foreach ( $subscriptions as $subscription ) {
				$result = self::createBraintreeOnlySubscription ( $subscription, $attribs );
				if ($result ['result'] === 'failure') {
					return $result;
				}
			}
			$result ['redirect'] = $order->get_checkout_order_received_url ();
			WC ()->cart->empty_cart ();
			$order->payment_complete ();
			return $result;
		} else {
			$result = self::createBraintreeOnlySubscription ( $order, $attribs );
			if ($result ['result'] === 'success') {
				WC ()->cart->empty_cart ();
			}
			return $result;
		}
	}
	public static function createBraintreeOnlySubscription(WC_Order $order, $attribs) {
		$planId = BT_Manager ()->getSubscriptionPlanId ( $order );
		if (! $planId) {
			BT_Manager ()->log->writeToLog ( 'An attempt was made by a customer to add a subscription product but the product does not have a Braintree PlanId assigned yet.' );
			return self::wooCoomerceError ( __ ( 'A Plan Id has not been assigned to this product. Please contact support so they can correct the problem.', 'braintree' ), 'error' );
		}
		try {
			
			$attribs = self::populateBraintreeSubscriptionAttributes ( $order, $attribs, $planId );
			BT_Manager ()->log->writeToLog ( sprintf ( 'Braintree subscription being created. Attributes: %s', print_r ( $attribs, true ) ) );
			$response = Braintree_Subscription::create ( $attribs );
			
			if ($response->success) {
				$order->payment_complete ();
				BT_Manager ()->saveBraintreeSubscriptionMeta ( $order, $response->subscription );
				update_post_meta ( $order->id, 'braintree_subscription', 'yes' );
				BT_Manager ()->log->writeToLog ( sprintf ( 'Braintree Subscription Success: %s', print_r ( $response, true ) ) );
				return BT_Manager ()->handleResponseSuccess ( $order );
			} else {
				BT_Manager ()->log->writeErrorToLog ( sprintf ( 'Braintree Subscription Error: %s', print_r ( $response, true ) ) );
				return BT_Manager ()->handleResponseError ( $response );
			}
		} catch ( Exception $e ) {
			return self::wooCoomerceError ( __ ( 'There was an error processing your subscription payment.', 'error' ) );
			BT_Manager ()->log->writeErrorToLog ( $e->getMessage () );
		}
	}
	public static function cartContainsProducts() {
		$hasProducts = false;
		foreach ( WC ()->cart->get_cart () as $cart => $values ) {
			$_product = $values ['data'];
			if (! self::isProductSubscription ( $_product->id )) {
				$hasProducts = true;
			}
		}
		return $hasProducts;
	}
	public static function cartContainsSubscriptions() {
		$hasSubscriptions = false;
		foreach ( WC ()->cart->get_cart () as $cart => $values ) {
			$_product = $values ['data'];
			if (self::isProductSubscription ( $_product->id )) {
				$hasSubscriptions = true;
			}
		}
		return $hasSubscriptions;
	}
	public static function getNextPaymentDate($date, WC_Sub $subscription, $timezone = null) {
		if (self::isBraintreeSubscription ( $subscription )) {
			try {
				$response = Braintree_Subscription::find ( $subscription->id );
				if ($response instanceof Braintree_Subscription) {
					$date = $response->nextBillingDate->date;
				}
			} catch ( Exception $e ) {
				BT_Manager ()->log->writeToLog ( sprintf ( 'Subscription %s could not be found in Braintree\'s system', $subscription->id ) );
			}
		}
		return $date;
	}
	
	/**
	 * Method that calculates the order total to be charged for a subscription order.
	 *
	 * @param WC_Order $order        	
	 */
	public static function getOrderTotal(WC_Order $order) {
		$total = 0;
		if (BT_Manager ()->subscriptionsActive ()) {
			$subscriptions = wcs_get_subscriptions_for_order ( $order->id );
			foreach ( $subscriptions as $subscription ) {
				$total = $total + $subscription->get_sign_up_fee (); // Only the signup fee should be charged since the shipping, taxes, and the product price are included in the subscription price.
			}
		}
		return $total;
	}
	
	/**
	 * If Braintree subscriptions is enabled and the product is a Braintree Subscription, render the html for the product.
	 *
	 * @param unknown $html        	
	 * @param WC_Product $product        	
	 */
	public static function getSubscriptionPriceHTML($html, WC_Product $product) {
		if (self::isProductSubscription ( $product->id )) {
			if (! $plan_id = BT_Manager ()->getProductPlanId ( $product->id )) {
				if (function_exists ( 'wc_add_notice' )) {
					wc_add_notice ( sprintf ( __ ( 'The Subscription Plan has not been assigned for currency %s.', 'braintree' ), get_woocommerce_currency () ), 'error' );
				}
				return $html;
			}
			if (! $plan = BT_Manager ()->getBraintreePlan ( $plan_id )) {
				return $html;
			}
			$html = sprintf ( '<span class="amount">%s%s %s %s</span>', get_woocommerce_currency_symbol ( get_woocommerce_currency () ), $product->get_price (), wc_braintree_get_billing_cycle ( $plan->billingFrequency ), wc_braintree_get_subscription_length ( $plan->billingFrequency, $plan->numberOfBillingCycles ) );
		}
		return $html;
	}
	public static function cartSubscriptionPrice($price, WC_Product $product) {
		if (self::isProductSubscription ( $product->id )) {
			$plan = BT_Manager ()->getBraintreePlan ( BT_Manager ()->getProductPlanId ( $product->id ) );
			if ($plan) {
				$price = sprintf ( '%s %s %s', $price, wc_braintree_get_billing_cycle ( $plan->billingFrequency ), wc_braintree_get_subscription_length ( $plan->billingFrequency, $plan->numberOfBillingCycles ) );
			}
		}
		return $price;
	}
	public static function getProductSubtotal($product_subtotal, $product, $quantity, $cart) {
		if (self::isProductSubscription ( $product->id )) {
			$plan = BT_Manager ()->getBraintreePlan ( BT_Manager ()->getProductPlanId ( $product->id ) );
			if ($plan) {
				$product_subtotal = sprintf ( '%s %s %s', $product_subtotal, wc_braintree_get_billing_cycle ( $plan->billingFrequency ), wc_braintree_get_subscription_length ( $plan->billingFrequency, $plan->numberOfBillingCycles ) );
			}
		}
		return $product_subtotal;
	}
	
	/**
	 *
	 * @param string $subtotal        	
	 * @param array $item        	
	 * @param WC_Order $order        	
	 */
	public static function formatLineSubtotal($subtotal, $item, $order) {
		if (self::isProductSubscription ( $item ['product_id'] )) {
			$plan = BT_Manager ()->getBraintreePlan ( BT_Manager ()->getProductPlanId ( $item ['product_id'] ) );
			if ($plan) {
				$subtotal = sprintf ( '%s%s', get_woocommerce_currency_symbol ( $order->get_order_currency () ), $order->get_line_subtotal ( $item, true ) );
				$subtotal = sprintf ( '<span class="amount">%s %s %s</span>', $subtotal, wc_braintree_get_billing_cycle ( $plan->billingFrequency ), wc_braintree_get_subscription_length ( $plan->billingFrequency, $plan->numberOfBillingCycles ) );
			}
		}
		return $subtotal;
	}
	
	/**
	 *
	 * @param WC_Order $order        	
	 * @param array $attribs        	
	 * @param string $planId        	
	 */
	public static function populateBraintreeSubscriptionAttributes(WC_Order $order, $attribs, $planId) {
		$attribs ['id'] = $order->id;
		$attribs ['planId'] = $planId;
		$attribs ['price'] = $order->get_total (); // This will return the total which includes shipping, taxes, etc.
		if (BT_Manager ()->subscriptionsActive ()) { // Only firstBillingDate if WooCommerce Subscriptions is active.
			$attribs ['firstBillingDate'] = BT_Manager ()->getSubscriptionBillingDate ( $order );
		}
		self::addMerchantAccountId ( $attribs );
		if (BT_Manager ()->isActive ( 'dynamic_descriptors' )) {
			$name = BT_Manager ()->getProductDynamicDescriptor ( $order );
			$number = BT_Manager ()->get_option ( 'dynamic_descriptor_phone' );
			$url = BT_Manager ()->get_option ( 'dynamic_descriptor_url' );
			$attribs ['descriptor'] = array ();
			if ($name) {
				$attribs ['descriptor'] ['name'] = $name;
			}
			if (! empty ( $number ) && preg_match ( '/^US/i', get_option ( 'woocommerce_default_country' ) )) { // number only valid for US.
				$attribs ['descriptor'] ['phone'] = $number;
			}
			if (! empty ( $url )) {
				$attribs ['descriptor'] ['url'] = $url;
			}
		}
		
		return $attribs;
	}
	public static function OrderNeedsPayment(WC_Order $order) {
		return ! $order->get_transaction_id ();
	}
	
	/**
	 * Get the merchant account id associated with the order.
	 * First, the order is checked for an existing merchant_account_id. If there is none, then
	 * the merchant_account_id is found be using the order currency to search through the configuration settings.
	 *
	 * @param array $attribs        	
	 * @param WC_Order $order        	
	 */
	public static function addSubscriptionMerchantAccountId(&$attribs, WC_Order $order) {
		$merchant_account_id = $order->merchant_account_id;
		if (empty ( $merchant_account_id )) {
			$merchant_account_id = BT_Manager ()->get_option ( 'woocommerce_braintree_' . BT_Manager ()->getEnvironment () . '_merchant_account_id[' . $order->order_currency . ']' );
		}
		if (! empty ( $merchant_account_id )) {
			$attribs ['merchantAccountId'] = $merchant_account_id;
		}
		return $attribs;
	}
	
	/**
	 * If the subscription object is a Braintree Subscription, then add support for gateway_scheduled_payments.
	 *
	 * @param bool $is_supported        	
	 * @param string $feature        	
	 * @param int $subscription        	
	 */
	public static function addSupportIfBraintreeSubscription($is_supported, $feature, $subscription) {
		if (self::isBraintreeSubscription ( $subscription )) {
			if ($feature === 'gateway_scheduled_payments') {
				$is_supported = true;
			}
		}
		return $is_supported;
	}
}
WC_Braintree_Subscriptions::init ();