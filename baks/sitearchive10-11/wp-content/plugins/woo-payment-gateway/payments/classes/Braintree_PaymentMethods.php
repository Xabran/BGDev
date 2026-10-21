<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class Braintree_PaymentMethods {
	
	public static $url = WC_BRAINTREE_ASSETS;

	public static function paymentMethods(){ 
		return array(	
				'amex' => array(
						'type'=>'img',
						'src'=>WC_BRAINTREE_ASSETS.'images/amex.png',
						'class'=>'payment-method-img',
						'value'=>'American Express'
				),
				'china_union_pay' => array(
						'type'=>'img',
						'src'=>WC_BRAINTREE_ASSETS.'images/china_union_pay.png',
						'class'=>'payment-method-img',
						'value'=>'China UnionPay'
				),
				'diners_club_international' => array(
						'type'=>'img',
						'src'=>WC_BRAINTREE_ASSETS.'images/diners_club_international.png',
						'class'=>'payment-method-img',
						'value'=>'Diner\'s Club'
				),
				'discover' => array(
						'type'=>'img',
						'src'=>WC_BRAINTREE_ASSETS.'images/discover.png',
						'class'=>'payment-method-img',
						'value'=>'Discover'
				),
				'jcb' => array(
						'type'=>'img',
						'src'=>WC_BRAINTREE_ASSETS.'images/jcb.png',
						'class'=>'payment-method-img',
						'value'=>'JCB'
				),
				'maestro' => array(
						'type'=>'img',
						'src'=>WC_BRAINTREE_ASSETS.'images/maestro.png',
						'class'=>'payment-method-img',
						'value'=>'Maestro'
				),
				'master_card' => array(
						'type'=>'img',
						'src'=>WC_BRAINTREE_ASSETS.'images/master_card.png',
						'class'=>'payment-method-img',
						'value'=>'MasterCard'
				),
				'solo' => array(
						'type'=>'img',
						'src'=>WC_BRAINTREE_ASSETS.'images/solo.png',
						'class'=>'payment-method-img',
						'value'=>'Solo'
				),
				'switch_type' => array(
						'type'=>'img',
						'src'=>WC_BRAINTREE_ASSETS.'images/switch_type.png',
						'class'=>'payment-method-img',
						'value'=>'Switch'
				),
				'visa' => array(
						'type'=>'img',
						'src'=>WC_BRAINTREE_ASSETS.'images/visa.png',
						'class'=>'payment-method-img',
						'value'=>'Visa'
				),
				'paypal' => array(
						'type'=>'img',
						'src'=>WC_BRAINTREE_ASSETS.'images/paypal.png',
						'class'=>'payment-method-img',
						'value'=>'PayPal'
				)
		);
	}
}
?>