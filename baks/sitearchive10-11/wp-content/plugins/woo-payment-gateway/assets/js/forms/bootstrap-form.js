jQuery(document).ready(function(){
	var BootstrapForm = function(){}
	
	BootstrapForm.validityChange = function(e, event){
		var field = event.fields[event.emittedBy];
		if (field.isValid) {
			jQuery(field.container).parents('.form-group').removeClass('has-warning').addClass(
					'has-success');
			jQuery(field.container).removeClass('braintree-hosted-fields-focused');
		} else if (field.isPotentiallyValid) {
			jQuery(field.container).parents('.form-group').removeClass(
					'has-success');
			jQuery(field.container).parents('.form-group').removeClass(
					'has-warning');
		} else {
			jQuery(field.container).parents('.form-group').removeClass('has-success').addClass(
					'has-warning');
			jQuery(field.container).removeClass('braintree-hosted-fields-focused');
		}
	}
	
	BootstrapForm.onTokenizationError = function(e, err){
		if(err.details && err.details.invalidFieldKeys.length > 0){
			for(var i=0;i<err.details.invalidFieldKeys.length;i++){
				var key = err.details.invalidFieldKeys[i];
				var field = braintreeCheckout.getFields()[key];
				jQuery(field.selector).parents('.form-group').addClass('has-warning');
			}
		}
	}
	
	BootstrapForm.updateContainerCSS = function(e){
		if(jQuery('#payment').width() < 450){
			jQuery('#braintree_custom_form').addClass('small-container');
		}
	}
	
	jQuery(document.body).on('braintree_custom_form_loaded', BootstrapForm.updateContainerCSS);
	jQuery(document.body).on('braintree_field_validity_change', BootstrapForm.validityChange);
	jQuery(document.body).on('braintree_tokenization_error', BootstrapForm.onTokenizationError);
	
});