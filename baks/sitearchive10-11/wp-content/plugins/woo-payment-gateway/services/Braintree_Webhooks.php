<?php

use Braintree\WebhookNotification;
use Braintree\WebhookTesting;
/**
 * 
 * @author Clayton Rogers
 *
 */
class Braintree_Webhooks{
	
	private static $pattern = '/\/?braintreegateway\/webhooks\/notifications\/?$/';
	
	public static function init(){
		
		add_action('init', __CLASS__.'::braintreeHookAction');
		
		add_action('braintree_hook_'.WebhookNotification::CHECK, __CLASS__.'::processTestNotification');
		
		add_filter( 'braintree_hook_match_url', __CLASS__.'::checkRequestParameters', 10, 2 );
		
		add_filter( 'braintree_hook_match_url', __CLASS__.'::checkRestfulUrl', 10, 2 );
		
		if(BT_Manager()->isActive('webhook_subscription_charged_successfully')){
			add_action('braintree_hook_'.WebhookNotification::SUBSCRIPTION_CHARGED_SUCCESSFULLY, __CLASS__.'::subscriptionChargedSuccessfully');
		}
		if(BT_Manager()->isActive('webhook_subscription_charged_unsuccessfully')){
			add_action('braintree_hook_'.WebhookNotification::SUBSCRIPTION_CHARGED_UNSUCCESSFULLY, __CLASS__.'::subscriptChargedUnsuccessfully');
		}
		if(BT_Manager()->isActive('webhook_subscription_went_active')){
			add_action('braintree_hook_'.WebhookNotification::SUBSCRIPTION_WENT_ACTIVE, __CLASS__.'::subscriptionWentActive');
		}
		if(BT_Manager()->isActive('webhook_subscription_past_due')){
			add_action('braintree_hook_'.WebhookNotification::SUBSCRIPTION_WENT_PAST_DUE, __CLASS__.'::subscriptionPastDue');
		}
		if(BT_Manager()->isActive('webhook_subscription_expired')){
			add_action('braintree_hook_'.WebhookNotification::SUBSCRIPTION_EXPIRED, __CLASS__.'::subscriptionExpired');
		}
		if(BT_Manager()->isActive('webhook_subscription_cancelled')){
			add_action('braintree_hook_'.WebhookNotification::SUBSCRIPTION_CANCELED, __CLASS__.'::subscriptionCancelled');
		}
	}
	
	/**
	 * Method that intercepts the Braintree hooks.
	 */
	public static function braintreeHookAction(){
		if( self::isWebhookUrl() ){
			if(BT_Manager()->isActive('enable_webhooks')){
				self::processSubscriptionHook();
			}
			else{
				BT_Manager()->log->writeToLog((__('Webhooks have not been enabled in the configuration. 
						Please enable webhooks and test again.', 'braintree')));
			}
		}
	}
	
	public static function isWebhookUrl(){
		return apply_filters( 'braintree_hook_match_url', false, $_SERVER['REQUEST_URI'] );
	}
	
	public static function checkRequestParameters( $result, $uri ){
		if( isset($_REQUEST['woocommerce_braintree_subscription_hook'] ) ){
			$result = true;
		}
		return $result;
	}
	
	public static function checkRestfulUrl( $result, $uri ){
		if( preg_match( self::$pattern, $uri ) ){
			$result = true;
		}
		return $result;
	}
	
	public static function processSubscriptionHook(){
		$bt_signature = isset($_POST['bt_signature']) ? $_POST['bt_signature'] : '';
		$bt_payload = isset($_POST['bt_payload']) ? str_replace('\r\n', '', $_POST['bt_payload']) : '';
		try{
			$notification = WebhookNotification::parse($bt_signature, $bt_payload);
			self::processSubscriptionKind($notification);
			http_response_code( 200 );
			exit();
		}catch(Exception $e){
			BT_Manager()->log->writeToLog(sprintf('There was an error parsing the webhook payload sent 
					from Braintree. Messages: %s. bt_signature: %s. bt_payload: %s', $e->getMessage(), $bt_signature, $bt_payload));
			http_response_code(400);
			exit();
		}
	}
	
	public static function processSubscriptionKind(WebhookNotification $notification){
		return do_action('braintree_hook_'.$notification->kind, $notification);
	}
	
	/**
	 * Method that processes the Braintree notification when a subscription charge is successful. 
	 * @param WebhookNotification $notification
	 */
	public static function subscriptionChargedSuccessfully(WebhookNotification $notification){
		$bt_sub = $notification->subscription;
		$subscription = wc_get_order($bt_sub->id);
		$subscription->add_order_note(sprintf(__('Subscription %s was charged successfully. Transaction ID: %s.', 'braintree'), $subscription->id, $bt_sub->transactions[0]->id));
		try{
			if( BT_Manager()->subscriptionsActive() ){
				$subscription->update_status(BT_Manager()->get_option('braintree_subscriptions_charge_success'));
			}
			else{
				$subscription->update_status(BT_Manager()->get_option('braintree_only_subscriptions_charge_success'));
			}
		}catch(Exception $e){
			$subscription->add_order_note($e->getMessage());
			BT_Manager()->log->writeToLog(sprintf('Braintree Webhook - %s', $e->getMessage()));
			return;
		}
		BT_Manager()->log->writeToLog(sprintf('Braintree Webhook - Subscription %s was charged successfully. Transcation ID: %s', $subscription->id, $bt_sub->transactions[0]->id));
	}
	
	/**
	 * Update the Subscription meta based on the subscription object located in the WebhookNotification.
	 * @param WebhookNotification $notification
	 */
	public static function subscriptChargedUnsuccessfully(WebhookNotification $notification){
		$subscription = wc_get_order($notification->subscription->id);
		try{
			if( BT_Manager()->subscriptionsActive() ){
				$subscription->update_status(BT_Manager()->get_option('braintree_subscriptions_charge_failed'));
			}
			else{
				$subscription->update_status(BT_Manager()->get_option('braintree_only_subscriptions_charge_failed'));
			}
			BT_Manager()->log->writeToLog(sprintf('Braintree Webhook - Payment failed for subscription %s.', $subscription->id));
		}catch(Exception $e){
			BT_Manager()->log->writeToLog(sprintf('Braintree Webhook - %s', $e->getMessage()));
		}
	}
	
	/**
	 * Method that processes the Braintree notification for when a subscription has gone active.
	 * @param WebhookNotification $notification
	 */
	public static function subscriptionWentActive(WebhookNotification $notification){
		$subscription = wc_get_order($notification->subscription->id);
		$subscription->add_order_note(sprintf(__('Subscription %s is now active.', 'braintree')));
		try{
			if( BT_Manager()->subscriptionsActive() ){
				$subscription->update_status(BT_Manager()->get_option('braintree_subscriptions_active'));
			}
			else{
				$subscription->update_status(BT_Manager()->get_option('braintree_only_subscriptions_active'));
			}
		}catch(Exception $e){
			$subscription->add_order_note($e->getMessage());
			BT_Manager()->log->writeToLog(sprintf('Braintree Webhook - %s', $e->getMessage()));
			return;
		}
		BT_Manager()->log->writeToLog(sprintf('Braintree Webhook - Subscription %s is now active.', $subscription->id));
	}
	
	/**
	 * Method that processes the Braintree notification for when a subscription has gone past due.
	 * @param WebhookNotification $notification
	 */
	public static function subscriptionPastDue(WebhookNotification $notification){
		$subscription = wc_get_order($notification->subscription->id);
		$subscription->add_order_note(sprintf(__('Subscription %s has gone past due.', 'braintree'), $subscription->id));
		try{
			if( BT_Manager()->subscriptionsActive() ){
				$subscription->update_status(BT_Manager()->get_option('braintree_subscriptions_past_due'));
			}
			else{
				$subscription->update_status(BT_Manager()->get_option('braintree_only_subscriptions_past_due'));
			}
		}catch(Exception $e){
			$subscription->add_order_note($e->getMessage());
			BT_Manager()->log->writeToLog(sprintf('Braintree Webhook - %s', $e->getMessage()));
			return;
		}
		BT_Manager()->log->writeToLog(sprintf('Braintree Webhook - Subscription %s is past due.', $subscription->id));
	}
	
	/**
	 * Method that processes the Braintree notification for when a subscription has expired.
	 * @param WebhookNotification $notification
	 */
	public static function subscriptionExpired(WebhookNotification $notification){
		$subscription = wc_get_order($notification->subscription->id);
		$subscription->add_order_note(sprintf(__('Subscription %s has expired.', 'braintree'), $subscription->id));
		try{
			if( BT_Manager()->subscriptionsActive() ){
				$subscription->update_status(BT_Manager()->get_option('braintree_subscriptions_expired'));
			}
			else{
				$subscription->update_status(BT_Manager()->get_option('braintree_only_subscriptions_expired'));
			}
		}catch(Exception $e){
			$subscription->add_order_note($e->getMessage());
			BT_Manager()->log->writeToLog(sprintf('Braintree Webhook - %s', $e->getMessage()));
			return;
			
		}
		BT_Manager()->log->writeToLog(sprintf('Braintree Webhook - Subscription %s is expired.', $subscription->id));
	}
	
	public static function subscriptionCancelled(WebhookNotification $notification){
		$subscription = wc_get_order($notification->subscription->id);
		$subscription->add_order_note(sprintf(__('Subscription %s has expired.', 'braintree'), $subscription->id));
		try{
			if( BT_Manager()->subscriptionsActive() ){
				$subscription->update_status(BT_Manager()->get_option('braintree_subscriptions_cancelled'));
			}
			else{
				$subscription->update_status(BT_Manager()->get_option('braintree_only_subscriptions_cancelled'));
			}
		}catch(Exception $e){
			$subscription->add_order_note($e->getMessage());
			BT_Manager()->log->writeToLog(sprintf('Braintree Webhook - %s', $e->getMessage()));
			return;
		}
		BT_Manager()->log->writeToLog(sprintf('Braintree Webhook - Subscription %s is expired.', $subscription->id));
	}
	
	/**
	 * Method that processes the test notification. 
	 * @param WebhookNotification $notification
	 */
	public static function processTestNotification(WebhookNotification $notification){
		BT_Manager()->log->writeToLog(sprintf(__('Webhook test was successful. Payload received from Braintree at %s', 'braintree'), $notification->timestamp->format('m/d/Y H:i:s')));
		http_response_code(200);
		exit();
	}
}
Braintree_Webhooks::init();
?>