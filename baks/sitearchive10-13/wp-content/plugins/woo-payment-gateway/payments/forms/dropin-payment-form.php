<input type="hidden" id="client_token" value="<?php echo BT_Manager ()->getClientToken ( BT_Manager()->isActive('3ds_enabled') ? BT_Manager ()->getMerchantAccountId () : [] )?>"/>
<?php
BT_Manager ()->getDropinContainer ();
echo WC_Braintree_Payments::getSavedPaymentMethodForm ( wp_get_current_user ()->ID );
