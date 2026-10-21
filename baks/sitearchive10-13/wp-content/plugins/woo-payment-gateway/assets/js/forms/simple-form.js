jQuery(document).ready(function(){
	var SimpleForm = function(){};
	
	SimpleForm.onFocus = function(e, event){
		var field = event.fields[event.emittedBy];
		if(field.isPotentiallyValid){
			jQuery(field.container).addClass('braintree-hosted-fields-focused');
		}
		else{
			jQuery(field.container).removeClass('braintree-hosted-fields-focused');
		}
	}
	
	SimpleForm.validityChange = function(e, event){
		var field = event.fields[event.emittedBy];
		if(!field.isPotentiallyValid){
			jQuery(field.container).removeClass('braintree-hosted-fields-focused');
		}
	}
	
	SimpleForm.onTokenizationError = function(e, err){
		if(err.details && err.details.invalidFieldKeys.length > 0){
			for(var i=0;i<err.details.invalidFieldKeys.length;i++){
				var key = err.details.invalidFieldKeys[i];
				var field = braintreeCheckout.getFields()[key];
				jQuery(field.selector).addClass('braintree-hosted-fields-invalid');
			}
		}
	}
	
	jQuery(document.body).on('braintree_field_focus', SimpleForm.onFocus);
	jQuery(document.body).on('braintree_field_validity_change', SimpleForm.validityChange);
	jQuery(document.body).on('braintree_tokenization_error', SimpleForm.onTokenizationError);
});
