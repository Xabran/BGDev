<?php
        if ( ! defined( 'ABSPATH' ) ) {
                exit; // Exit if accessed directly
        }
 
        if (class_exists('WC_Subscriptions_Manager')) {
                add_action('woocommerce_subscription_payment_complete', 'iw_handle_subscription_payment', 10, 1);
                add_action('woocommerce_subscription_status_active', 'iw_handle_activated_subscription', 10, 1);
                add_action('woocommerce_subscription_status_cancelled', 'iw_handle_cancelled_subscription', 10, 1);
                add_action('woocommerce_subscription_status_on-hold', 'iw_handle_subscription_put_onhold', 10, 1);
                add_action('woocommerce_subscription_status_expired', 'iw_handle_subscription_expired', 10, 1);
        }
       
        function iw_handle_subscription_payment($subscription) {
                iw_handle_subscription('completed', $subscription);
        }
 
        function iw_handle_activated_subscription($subscription) {
                iw_handle_subscription('activated', $subscription);
        }
 
        function iw_handle_cancelled_subscription($subscription) {
                iw_handle_subscription('cancelled', $subscription);
        }
 
        function iw_handle_subscription_put_onhold($subscription) {
                iw_handle_subscription('on-hold', $subscription);
        }
 
        function iw_handle_subscription_expired($subscription) {
                iw_handle_subscription('expired', $subscription);
        }
       
        function iw_handle_subscription($action = '', $subscription){
                global $iwpro;
                
                // CHECKS
                if(empty($action)) return;
                if(!$iwpro->ia_app_connect()) return;
               
                // ARRAY OF ACTIONS
                $activated_goal = apply_filters( 'infusedwoo_cpn_goal', 'woosubactivated' );
                $completed_goal = apply_filters( 'infusedwoo_cpn_goal', 'woosubpayment' );
                $cancelled_goal = apply_filters( 'infusedwoo_cpn_goal', 'woosubcancelled' );
                $on_hold_goal = apply_filters( 'infusedwoo_cpn_goal', 'woosubsuspended' );
                $expired_goal = apply_filters( 'infusedwoo_cpn_goal', 'woosubexpired' );


                $action_array = array(
                        'activated' => array('action_set' => 'infusionsoft_sub_activated', 'api_goal' => $activated_goal),
                        'completed' => array('action_set' => 'infusionsoft_sub_activated', 'api_goal' => $completed_goal),
                        'cancelled' => array('action_set' => 'infusionsoft_sub_cancelled', 'api_goal' => $cancelled_goal),
                        'on-hold' => array('action_set' => 'infusionsoft_sub_on-hold', 'api_goal' => $on_hold_goal),
                        'expired' => array('action_set' => 'infusionsoft_sub_expired', 'api_goal' =>  $expired_goal)
                );
               
                // GET ORDER
                $order = new WC_Order( $subscription->order->id );
               
                //GET LINE ITEMS FROM ORDER
                $items = $order->get_items();
 
                // GET CONTACT INFO
                $email = $order->billing_email;
                $contact = $iwpro->app->dsFind('Contact',5,0,'Email',$email,array('Id'));
                $contact = $contact[0];
                                               
                if ($contact['Id'] != null && $contact['Id'] != 0 && $contact != false){
                        $contactId = (int) $contact['Id'];
                } else {                               
                        $contactinfo    = array();     
                        $contactinfo['Email'] = $email;
                        $contactId  = $iwpro->app->addCon($contactinfo);
                }      
                // LOOP THROUGH EACH LINE ITEM
                foreach($items as $item)
                {
                        // GET PRODUCT ID
                        $product_id = $item['product_id'];
                        $find_id = ($item['variation_id'] > 0) ? $item['variation_id'] : $item['product_id'];
       
                        // ACTION SET & CAMPAIGN TRIGGER
                        $as     = (int) get_post_meta($product_id, $action_array[$action]['action_set'], true);
                        $cpgoal = get_post_meta($find_id, '_sku', true);
       
                        // RUN ACTIONS
                        if(!empty($as)) $iwpro->app->runAS($contactId, $as);
                        if(!empty($cpgoal)) $iwpro->app->achieveGoal($action_array[$action]['api_goal'], $cpgoal, $contactId); 
                        $iwpro->app->achieveGoal($action_array[$action]['api_goal'], "any", $contactId);

                }
               
        }
 
?>