jQuery(document).ready(function(){
	var GoogleMaterialForm = function(){}

	GoogleMaterialForm.onTokenizationError = function(e, err){
		if(err.details && err.details.invalidFieldKeys.length > 0){
			for(var i=0;i<err.details.invalidFieldKeys.length;i++){
				var key = err.details.invalidFieldKeys[i];
				var field = braintreeCheckout.getFields()[key];
				jQuery(field.selector).addClass('braintree-hosted-fields-invalid');
			}
		}
	}

	GoogleMaterialForm.updateContainerCSS = function(e){
		if(jQuery('#payment').width() < 600){
			jQuery('#braintree_custom_form').addClass('small-container');
		}
	}
	
	jQuery(document.body).on('braintree_custom_form_loaded', GoogleMaterialForm.updateContainerCSS);
	jQuery(document.body).on('braintree_tokenization_error', GoogleMaterialForm.onTokenizationError);
});