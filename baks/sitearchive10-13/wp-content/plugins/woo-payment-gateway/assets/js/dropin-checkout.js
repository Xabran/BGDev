var BraintreeUtils = function() {
	this.constructor();
};

var createBraintreeUtils = function() {
	braintreeUtils = new BraintreeUtils();
	if (!braintreeUtils.hasSavedPaymentMethods()) {
		braintreeUtils.setup();
	} else {
		braintreeUtils.showPaymentMethods();
		braintreeUtils.hideDropinContainer();
		braintreeUtils.selectDefaultMethod();
	}
}

BraintreeUtils.prototype.constructor = function() {
	this.clientToken = jQuery('#client_token').val();
	this._3ds_enabled = braintree_dropin_form_options._3ds_enabled === 'true' ? true : false;
	this.setForm();
	this.save_payments_enabled = braintree_dropin_form_options.save_payment_methods === 'true' ? true : false;
	this.setupEvents();
	this.hideAndDisplayFields();
}

BraintreeUtils.prototype.setForm = function() {
	this.form = jQuery('#dropin-container').closest('form')[0];
}

BraintreeUtils.prototype.setupEvents = function() {
	jQuery(document.body).on('click', '#add_new_method', this.addNewMethod);
	jQuery(document.body).on('click', '#cancel_add_new', this.cancelAddNew);
	jQuery(document.body).on('click', '.payment-method-item',
			this.paymentMethodSelected);
	jQuery(document.body).on('change', 'input[name="payment_method"]',
			this.removeExtraForms, this.maybeTearDownMaybeSetup);
	jQuery('form.checkout').on('checkout_place_order', this.checkoutPlaceOrder);
	jQuery(document.body).on('checkout_error', this.checkoutError);
}

BraintreeUtils.prototype.hideAndDisplayFields = function(){
	if(!this.save_payments_enabled){
		jQuery('.save-payment-method-label').hide();
	}
}

BraintreeUtils.prototype.setup = function() {
	if (braintreeUtils.integration) {
		return false;
	}
	if (braintreeUtils.setupCalled) {
		return false;
	}
	if(braintreeUtils.clientToken == null){
		braintreeUtils.submit_error('A client token is required to initialize the payment form. Please check your API key configuration.');
	}
	braintree.setup(braintreeUtils.clientToken, 'dropin',
			{
				container : 'dropin-container',
				form : jQuery(this.form).attr('id'),
				onReady : function(integration) {
					if (!braintreeUtils.integration) {
						braintreeUtils.integration = integration;
					}
					braintreeUtils.removeExtraForms();
				},
				onPaymentMethodReceived : function(response) {
					if(braintreeUtils._3ds_enabled && response.type !== 'PayPalAccount'){
						braintreeUtils.verify3DS(response);
					}else{
						braintreeUtils.onPaymentMethodReceived(response);
					}
				}
			});
	braintreeUtils.setupCalled = true;
	braintreeUtils.removeExtraForms();
	
	if(braintreeUtils._3ds_enabled){
		braintreeUtils.client = new braintree.api.Client({
			clientToken: braintreeUtils.clientToken
		});
	}
}

BraintreeUtils.prototype.removeExtraForms = function() {
	jQuery.each(jQuery('#dropin-container').children('iFrame'),
			function(index) {
				if (index > 0) {
					jQuery(this).remove();
				}
			});
}

BraintreeUtils.prototype.isGatewaySelected = function() {
	return document.getElementById('payment_method_braintree_payment_gateway') ? 
			document.getElementById('payment_method_braintree_payment_gateway').checked : false;
}

BraintreeUtils.prototype.isPaymentMethodSelected = function() {
	if (jQuery('#selected_payment_method').length > 0
			&& jQuery('#selected_payment_method').val() !== "") {
		return true;
	}
	return false;
}

BraintreeUtils.prototype.selectDefaultMethod = function() {
	jQuery('.payment-method-item').each(
			function() {
				if (jQuery(this).hasClass('selected')) {
					jQuery('#selected_payment_method').val(
							jQuery(this).attr('payment-token'));
				}
			});
}

BraintreeUtils.prototype.checkoutPlaceOrder = function() {
	if (braintreeUtils.isGatewaySelected()) {
		if (braintreeUtils.isPaymentMethodSelected()) {
			return true;
		} else {
			if (braintreeUtils.paymentMethodReceived) {
				return true;
			}
			return false;
		}
	}
}

BraintreeUtils.prototype.onPaymentMethodReceived = function(response) {
	braintreeUtils.paymentMethodReceived = true;
	var element = document.getElementById('payment_method_nonce');
	if (element != null) {
		element.value = response.nonce;
	} else {
		element = document.createElement('input');
		element.type = 'hidden';
		element.name = 'payment_method_nonce';
		element.id = 'payment_method_nonce';
		element.value = response.nonce;
		jQuery(braintreeUtils.form).append(element);
	}
	jQuery(braintreeUtils.form).submit();
}

BraintreeUtils.prototype.verify3DS = function(response){
	braintreeUtils.client.verify3DS({
		amount: braintree_dropin_form_options.order_total,
		creditCard: response.nonce,
		onUserClose: function(){
			braintreeUtils.teardown();
			jQuery(document.body).trigger('updated_checkout');
		}
	}, function(error, response){
		if(error){
			braintreeUtils.submit_error(error.message);
			return;
		}
		braintreeUtils.onPaymentMethodReceived(response);
	});
}

BraintreeUtils.prototype.submit_error = function(error_message){
	jQuery( '.woocommerce-error, .woocommerce-message' ).remove();
	jQuery(braintreeUtils.form).prepend( '<div class="woocommerce-error">'+error_message+'</div>' );
	jQuery(braintreeUtils.form).removeClass( 'processing' ).unblock();
	jQuery(braintreeUtils.form).find( '.input-text, select, input:checkbox' ).blur();
	jQuery( 'html, body' ).animate({
		scrollTop: ( jQuery( 'form.checkout' ).offset().top - 100 )
	}, 1000 );
}

BraintreeUtils.prototype.checkoutError = function() {
	braintreeUtils.teardown();
	braintreeUtils.setup();
	braintreeUtils.paymentMethodReceived = false;
}

BraintreeUtils.prototype.teardown = function() {
	if (braintreeUtils.integration) {
		braintreeUtils.integration.teardown();
		braintreeUtils.integration = null;
		braintreeUtils.setupCalled = false;
	}
}

BraintreeUtils.prototype.maybeTearDownMaybeSetup = function(){
	if(jQuery('#payment_method_braintree_payment_gateway').is(':checked')){
		braintreeUtils.setup();
	}else{
		braintreeUtils.teardown();
	}
}

BraintreeUtils.prototype.addNewMethod = function() {
	braintreeUtils.removeSelectedPaymentMethod();
	braintreeUtils.hidePaymentMethods();
	braintreeUtils.setup();
	braintreeUtils.showDropinContainer();
}

BraintreeUtils.prototype.cancelAddNew = function() {
	braintreeUtils.teardown();
	braintreeUtils.hideDropinContainer();
	braintreeUtils.selectDefaultMethod();
	braintreeUtils.showPaymentMethods();
}

BraintreeUtils.prototype.showDropinContainer = function() {
	jQuery('#braintree_dropin_container').slideDown(400);
}

BraintreeUtils.prototype.hideDropinContainer = function() {
	jQuery('#braintree_dropin_container').slideUp(400);
}

BraintreeUtils.prototype.hidePaymentMethods = function() {
	jQuery('#saved_payment_methods').slideUp(400);
}

BraintreeUtils.prototype.showPaymentMethods = function() {
	jQuery('#saved_payment_methods').slideDown(400);
}

BraintreeUtils.prototype.removeSelectedPaymentMethod = function() {
	jQuery('#selected_payment_method').val("");
}

BraintreeUtils.prototype.paymentMethodSelected = function() {
	jQuery('.payment-method-item').each(function() {
		jQuery(this).removeClass('selected');
	});
	jQuery('#selected_payment_method').val(jQuery(this).attr('payment-token'));
	jQuery(this).addClass('selected');
}

BraintreeUtils.verifyFormInitialized = function(){
	if(braintreeUtils == null){
		jQuery(document.body).trigger('updated_checkout');
	}
}

BraintreeUtils.prototype.hasSavedPaymentMethods = function() {
	return jQuery('#saved_payment_methods').length > 0;
}

BraintreeUtils.checkSetup = function(){
	var iFrame = jQuery('#dropin-container').children('iFrame');
	if(iFrame.length == 0){
		createBraintreeUtils();
	}
}

var braintreeUtils = null;

jQuery(document.body).on('updated_checkout', createBraintreeUtils);

setInterval(BraintreeUtils.checkSetup, 2000);