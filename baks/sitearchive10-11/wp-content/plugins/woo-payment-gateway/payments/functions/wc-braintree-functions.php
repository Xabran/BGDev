<?php
/**
 * Functions for use within Braintree For WooCommerce.
 */

/**
 * Return an array of subscription length options.
 * @return string[]
 */
function wc_braintree_get_subscription_lengths(){
	$array = array(
			1=>__('for 1 Month', 'braintree')
	);
	$range = range(2, 36);
	
	foreach( $range as $number ){
		$array[$number] = sprintf( __('%s months', 'braintree'), $number );
	}
	return $array;
}

/**
 * Returns the subscription length of the provided Braintree_Plan length.
 * @param $length
 */
function wc_braintree_get_subscription_length( $frequency, $numOfCycles ){
	$value = null;
	$length = $frequency * $numOfCycles;
	if( !$length ){ //never expires
		$value = '';
	}
	else{
		if( $length == 1 ){
			$value = __( 'for 1 month', 'braintree' );
		}
		else{
			$value = sprintf( __( 'for %s months', 'braintree' ), $length );
		}
	}
	return $value;
}

/**
 * Return a formatted string representing the billing cycle.
 * <strong>Example: </strong> "every 4 months."
 * @param unknown $cycle
 */
function wc_braintree_get_billing_cycle( $cycle ){
	if( $cycle == 1 ){
		return __( '/ month', 'braintree' );
	}
	else{
		return sprintf( __( 'every %s months', 'braintree' ), $cycle);
	}
}
?>