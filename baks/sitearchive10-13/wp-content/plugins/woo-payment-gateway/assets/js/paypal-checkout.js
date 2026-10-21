var PayPalCheckout = function(){
	this.form = jQuery('#braintree_paypal_button').closest('form')[0];
	this.setupEvents();
	this.clientToken = jQuery('#client_token').val();
	this.is_order_recieved_page = braintree_paypal_checkout.order_received_page === 'true' ? true : false;
}

PayPalCheckout.instance = function(){
	window.paypalCheckout = new PayPalCheckout();
	paypalCheckout.initializePayPal();
}

PayPalCheckout.prototype.setupEvents = function(){
	jQuery(this.form).on('checkout_place_order', this.checkoutPlaceOrder);
	jQuery(document.body).on('click', '#cancel_paypal_payment', this.cancelPayPalPayment);
}

PayPalCheckout.prototype.initializePayPal = function(){
	var paypalButton = document.getElementById('braintree_paypal_button');
	braintree.client.create({
		authorization: paypalCheckout.clientToken
	}, function(clientErr, clientInstance){
		if(clientErr){
			paypalCheckout.submit_error(clientErr.message);
			return;
		}
		braintree.paypal.create({
			client: clientInstance
		}, function(paypalErr, paypalInstance){
			if(paypalErr){
				paypalCheckout.submit_error(paypalErr.message);
				return;
			}
		paypalButton.addEventListener('click', function(event){
			event.preventDefault();
			if(paypalCheckout.isGatewaySelected()){
				if(paypalCheckout.tokenizationStarted){
					return;
				}
				paypalCheckout.tokenizationStarted = true;
				paypalInstance.tokenize(paypalCheckout.createPayPalTokenizationObject(), function(tokenizeErr, payload){
					if(tokenizeErr){
						paypalCheckout.tokenizationStarted = false;
						if(tokenizeErr.type !== 'CUSTOMER'){
							paypalCheckout.submit_error(tokenizeErr.message);
						}
						return;
					}
				paypalCheckout.onPaymentMethodReceived(payload);
				});
			}
		});
		});
	});
}

PayPalCheckout.prototype.createPayPalTokenizationObject = function(){
	var object = {
			'flow': braintree_paypal_checkout.checkout_flow,
			'displayName': braintree_paypal_checkout.display_name
	};
	if(object.flow === 'checkout'){
		object.amount = braintree_paypal_checkout.order_total;
		object.currency = braintree_paypal_checkout.currency;
	}
	return object;
}

PayPalCheckout.prototype.isGatewaySelected = function() {
	return document.getElementById('payment_method_braintree_paypal_payments') ? 
			document.getElementById('payment_method_braintree_paypal_payments').checked : false;
}

PayPalCheckout.prototype.onPaymentMethodReceived = function(response){
	paypalCheckout.paymentMethodReceived = true;
	var element = document.getElementById('payment_method_nonce');
	if(element != null){
		element.value = response.nonce;
	}
	else{
		element = document.createElement('input');
		element.type = 'hidden';
		element.name = 'payment_method_nonce';
		element.id = 'payment_method_nonce';
		element.value = response.nonce;
		jQuery(paypalCheckout.form).append(element);
	}
	var html = braintree_paypal_checkout.paypal_html;
	var email = response.details.email;
	html = html.replace('%s', email);
	jQuery('#paypal_vaulted_method').html(html).slideDown(200);
	jQuery('#paypal_container').slideUp(200);
}

PayPalCheckout.prototype.submit_error = function(error_message){
	jQuery( '.woocommerce-error, .woocommerce-message' ).remove();
	jQuery(paypalCheckout.form).prepend( '<div class="woocommerce-error">'+error_message+'</div>' );
	jQuery(paypalCheckout.form).removeClass( 'processing' ).unblock();
	jQuery(paypalCheckout.form).find( '.input-text, select, input:checkbox' ).blur();
	jQuery( 'html, body' ).animate({
		scrollTop: ( jQuery( 'form.checkout' ).offset().top - 100 )
	}, 1000 );
}

PayPalCheckout.prototype.cancelPayPalPayment = function(e){
	paypalCheckout.paymentMethodReceived = false;
	paypalCheckout.tokenizationStarted = false;
	jQuery('#paypal_vaulted_method').html("").slideUp();
	jQuery('#paypal_container').slideDown();
	jQuery('#payment_method_nonce').val("");
}

PayPalCheckout.prototype.checkoutPlaceOrder = function(e){
	if(paypalCheckout.isGatewaySelected()){
		if(paypalCheckout.paymentMethodReceived){
			return true;
		}else{
			return false;
		}
	}
}

PayPalCheckout.verifyFormInitialized = function(){
	if(typeof paypalCheckout === 'undefined'){
		jQuery(document.body).trigger('updated_checkout');
	}
}

jQuery(document.body).on('updated_checkout', PayPalCheckout.instance);

setTimeout(PayPalCheckout.verifyFormInitialized, 3000);