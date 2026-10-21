jQuery(document).ready(function(){
	
	var _3dCardForm = function(){};
	
	_3dCardForm.onTokenizationError = function(e, err){
		if(err.details && err.details.invalidFieldKeys.length > 0){
			for(var i=0;i<err.details.invalidFieldKeys.length;i++){
				var key = err.details.invalidFieldKeys[i];
				var field = braintreeCheckout.getFields()[key];
				jQuery(field.selector).addClass('braintree-hosted-fields-invalid');
			}
		}
	}
	
	jQuery(document.body).on('braintree_tokenization_error', _3dCardForm.onTokenizationError);
});