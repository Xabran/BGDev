<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Trigger a abandoned cart Deletion
 *
 * @since 2.4.7
 * @param $abandoned_cart_id Arguments passed
 * @return void
 */

class wcap_delete_bulk_action_handler{

    function wcap_delete_bulk_action_handler_function( $abandoned_cart_id ) {
        global $wpdb;
        
        $get_user_id         = "SELECT user_id FROM `" . $wpdb->prefix . "ac_abandoned_cart_history` WHERE id = '$abandoned_cart_id' ";
        $results_get_user_id = $wpdb->get_results( $get_user_id );
        $user_id_of_guest    = $results_get_user_id[0]->user_id;
        
        $query_delete        = "DELETE FROM `" . $wpdb->prefix . "ac_abandoned_cart_history` WHERE id = '$abandoned_cart_id' ";
        $results_delete      = $wpdb->get_results( $query_delete );
        
        if ( $user_id_of_guest >= '63000000' ) {
            $guest_query_delete   = "DELETE FROM `" . $wpdb->prefix . "ac_guest_abandoned_cart_history` WHERE id = '" . $user_id_of_guest . "'";
            $results_guest = $wpdb->get_results( $guest_query_delete );
            //guest user
        }
        wp_safe_redirect( admin_url( '/admin.php?page=woocommerce_ac_page&action=listcart&wcap_deleted=YES' ) );
    }
    
    function wcap_delete_template_bulk_action_handler_function( $template_id ) {
        global $wpdb;
    
        $id_remove    = $template_id;
        $query_remove = "DELETE FROM `" . $wpdb->prefix . "ac_email_templates` WHERE id='" . $id_remove . "' ";
        $wpdb->query( $query_remove );
         
        wp_safe_redirect( admin_url( '/admin.php?page=woocommerce_ac_page&action=emailtemplates&wcap_template_deleted=YES' ) );
    }
    
    public static function wcap_delete_abandoned_carts_after_x_days() {
        global $wpdb;
        $query = "SELECT * FROM `" . $wpdb->prefix . "ac_abandoned_cart_history` WHERE recovered_cart = '0' ";
        $carts = $wpdb->get_results ( $query );
        foreach( $carts as $cart_key => $cart_value ) {
            $cart_update_time = $cart_value->abandoned_cart_time;
            wcap_delete_bulk_action_handler ::delete_ac_carts( $cart_value, $cart_update_time );
        }
    }
    
    public static function delete_ac_carts( $value, $cart_update_time ) {
        global $wpdb;
        $delete_ac_after_days      = get_option( 'ac_delete_abandoned_order_days' );
        $delete_ac_after_days_time = $delete_ac_after_days * 86400;
        $current_time              = current_time( 'timestamp' );
        $check_time                = $current_time - $cart_update_time;
    
        if ( $check_time > $delete_ac_after_days_time && $delete_ac_after_days_time != 0 && $delete_ac_after_days_time != "" ) {
            $user_id           = $value->user_id;
            $query             = "DELETE FROM `" . $wpdb->prefix . "ac_abandoned_cart_history` WHERE user_id = '$user_id' AND abandoned_cart_time = '$cart_update_time'";
            $results2          = $wpdb->get_results ( $query );
    
            $query_delete_cart = "DELETE FROM `" . $wpdb->prefix."usermeta` WHERE user_id = '$user_id' AND meta_key = '_woocommerce_persistent_cart' ";
            $results_delete    = $wpdb->get_results ( $query_delete_cart );
    
            if ( $user_id >= '63000000' ) {
                $guest_query   = "DELETE FROM `" . $wpdb->prefix . "ac_guest_abandoned_cart_history` WHERE id = '" . $user_id . "'";
                $results_guest = $wpdb->get_results ( $guest_query );
            }
        }
    }

}