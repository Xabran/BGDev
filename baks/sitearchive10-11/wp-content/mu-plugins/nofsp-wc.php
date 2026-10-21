<?php
        /**
         *Reduce the strength requirement on the woocommerce password.
         *
         * Strength Settings
         * 3 = Strong (default)
         * 2 = Medium
         * 1 = Weak
         * 0 = Very Weak / Anything
         */
        function reduce_woocommerce_min_strength_requirement( $strength ) {
            return 1;
        }
        //add_filter( 'woocommerce_min_password_strength', 'reduce_woocommerce_min_strength_requirement' );
        
        //Removes str entirely including the meter
        function wc_remove_password_strength() {
                if ( wp_script_is( 'wc-password-strength-meter', 'enqueued' ) ) {
                        wp_dequeue_script( 'wc-password-strength-meter' );
                }
        }
        add_action( 'wp_print_scripts', 'wc_remove_password_strength', 100 );
            
        function change_pw_str_hint_txt( $hint )
        {
          return 'Use upper and lower case letters, numbers, and symbols like ! " ? $ % ^ &amp';
        }
        add_filter( 'password_hint', 'change_pw_str_hint_txt');
?>
