var InlineDonation = function(){
	this.clientToken = document.getElementById('client_token').value;
	this.setupEvents();
}

InlineDonation.prototype.setupEvents = function(){
	jQuery(document.body).on('keyup', '#donation-form input', this.clearInvalidEntries);
	this.setup();
}

InlineDonation.prototype.setup = function(){
	braintree.setup(this.clientToken, 'dropin',{
		container: 'dropin-container',
		form: 'donation-form',
		onReady: function(integration){
			inlineDonation.integration = integration;
		},
		onPaymentMethodReceived: function(response){
			inlineDonation.onPaymentMethodReceived(response);
		}
	})
}

InlineDonation.prototype.onPaymentMethodReceived = function(response){
	inlineDonation.paymentMethodReceived = true;
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
		jQuery('#donation-form').append(element);
	}
	inlineDonation.validateInputFields();
}

InlineDonation.prototype.validateInputFields = function(){
	var hasFailures = false;
	jQuery('#donation-form input').each(function(){
		if(jQuery(this).val() === ""){
			jQuery(this).parent().find('div.invalid-input-field').show().addClass('active');
			hasFailures = true;
		}
	});
	if(! hasFailures){
		inlineDonation.submitPayment();
	}
}

InlineDonation.prototype.submitPayment = function(){
	var data = jQuery('#donation-form').serialize();
	var url = jQuery('#ajax_url').val();
	jQuery('.overlay-payment-processing').addClass('active');
	jQuery.ajax({
			type:'POST',
			url: url,
			dataType: 'json',
			data: data
	}).done(function(response){
		jQuery('.overlay-payment-processing').removeClass('active');
		if(response.result === 'success'){
			inlineDonation.redirect(response.url);
		}
		else{
			inlineDonation.showErrorMessage(response.message);
		}
	}).fail(function(response){
		jQuery('.overlay-payment-processing').removeClass('active');
		inlineDonation.showErrorMessage(response.message);
	});
}

InlineDonation.prototype.redirect = function(url){
	window.location.replace(url);
}

InlineDonation.prototype.showErrorMessage = function(message){
	jQuery('#error_messages').html(message);
}

InlineDonation.prototype.clearInvalidEntries = function(){
	jQuery(this).parent().find('div.invalid-input-field').hide().removeClass('active');
}

InlineDonation.prototype.clearErrorMessages = function(){
	jQuery('#error_messages').empty();
}

InlineDonation.prototype.clearInvalidEntries = function(){
	jQuery(this).parent().find('div.invalid-input-field').hide().removeClass('active');
}

InlineDonation.prototype.displayOverlay = function(callback){
	jQuery('#donation_overlay').fadeIn(400, callback);
}

InlineDonation.prototype.hideOverlay = function(callback){
	jQuery('#donation_overlay').fadeOut(400, callback);
}

var inlineDonation = new InlineDonation();