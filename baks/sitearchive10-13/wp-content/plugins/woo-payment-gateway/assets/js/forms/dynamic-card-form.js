jQuery(document).ready(function(){
	var DynamicForm = function(){
	};
	DynamicForm.CardTypeChange = function(e, event){
		if(event.cards.length == 1){
			jQuery('#dynamic-card-form').removeClass().addClass(event.cards[0].type);
			jQuery('.card-container header').addClass('header-slide');
			jQuery('#card-image').removeClass().addClass(event.cards[0].type);
		}else{
			jQuery('#dynamic-card-form').removeClass();
			jQuery('.card-container header').removeClass();
			jQuery('#card-image').removeClass()
		}
	};
	DynamicForm.formInitialization = function(e){
		if(braintreeCheckout.cart_contains_subscription || braintreeCheckout.add_paymentmethod_page
				|| !braintreeCheckout.save_payment_method_enabled){
			jQuery('.save-card-info').hide();
		}
	}
	
	DynamicForm.onTokenizationError = function(e, err){
		if(err.details && err.details.invalidFieldKeys.length > 0){
			for(var i=0;i<err.details.invalidFieldKeys.length;i++){
				var key = err.details.invalidFieldKeys[i];
				var field = braintreeCheckout.getFields()[key];
				jQuery(field.selector).addClass('braintree-hosted-fields-invalid');
			}
		}
	}
	
	DynamicForm.updateContainerCSS = function(e){
		if(jQuery('#payment').width() < 436){
			jQuery('#braintree_custom_form').addClass('small-container');
		}
	}
	
	jQuery(document.body).on('braintree_card_type_change', DynamicForm.CardTypeChange);
	jQuery(document.body).on('braintree_custom_form_loaded', DynamicForm.updateContainerCSS);
	jQuery(document.body).on('braintree_before_form_initialize', DynamicForm.formInitialization);
	jQuery(document.body).on('braintree_tokenization_error', DynamicForm.onTokenizationError);
})