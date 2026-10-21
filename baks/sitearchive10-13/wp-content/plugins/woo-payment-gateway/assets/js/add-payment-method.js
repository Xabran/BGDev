var BraintreePayments = function(){
	this.setClientToken();
	this.setForm();
	this.setEvents();
	this.initializeDropinForm();
}

BraintreePayments.instance = function(){
	window.braintreePayments = new BraintreePayments();
}

BraintreePayments.prototype.setClientToken = function(){
	this.clientToken = document.getElementById('client_token') ? document.getElementById('client_token').value : '';
}

BraintreePayments.prototype.setEvents = function(){
	jQuery(this.form).on('submit', this.validate);
}

BraintreePayments.prototype.setForm = function(){
	this.form = jQuery('#dropin-container').closest('form')[0];
}

BraintreePayments.prototype.initializeDropinForm = function(){
	braintree.setup(this.clientToken, 'dropin', {
		container: 'dropin-container',
		form: this.form.id,
		onReady: function(integration){
			braintreePayments.integration = integration;
		},
		onPaymentMethodReceived: function(response){
			braintreePayments.onPaymentMethodReceived(response);
		}
	});
}

BraintreePayments.prototype.onPaymentMethodReceived = function(response) {
	braintreePayments.paymentMethodReceived = true;
	var element = document.getElementById('payment_method_nonce');
	if (element != null) {
		element.value = response.nonce;
	} else {
		element = document.createElement('input');
		element.type = 'hidden';
		element.name = 'payment_method_nonce';
		element.id = 'payment_method_nonce';
		element.value = response.nonce;
		jQuery(braintreePayments.form).append(element);
	}
	jQuery(braintreePayments.form).submit();
}

BraintreePayments.prototype.validate = function(e){
	if(braintreePayments.isSelected()){
		if(braintreePayments.paymentMethodReceived){
			return true;
		}else{
			return false;
		}
	}
}

BraintreePayments.prototype.isSelected = function(e){
	return jQuery('#payment_method_braintree_payment_gateway').is(':checked');
}

jQuery(document.body).on('init_add_payment_method', BraintreePayments.instance);