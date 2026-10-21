jQuery(document).ready(
		function() {
			var ClassicForm = function() {

			}
			ClassicForm.notEmpty = function(e, event) {
				for ( var key in event.fields) {
					var field = event.fields[key];
					if (field.isEmpty) {
						jQuery(field.container).prev().removeClass('active');
					} else {
						jQuery(field.container).prev().addClass('active');
					}
				}
			};

			ClassicForm.empty = function(e, event) {
				var field = event.fields[event.emittedBy];
				jQuery(field.container).prev().removeClass('active');
			}

			ClassicForm.cardTypeChange = function(e, event) {
				var field = event.fields[event.emittedBy];
				if (event.cards.length === 1) {
					jQuery(field.container).next('.card-type').addClass(
							event.cards[0].type);
				} else {
					jQuery(field.container).next('.card-type').attr('class',
							'card-type');
				}
			}

			ClassicForm.onFocus = function(e, event) {
				var field = event.fields[event.emittedBy];
				if (event.emittedBy === 'cvv') {
					if (event.cards.length === 1) {
						if (event.cards[0].code.size === 3) {
							jQuery(field.container).next('span').addClass(
									'cvv-image active');
						} else {
							jQuery(field.container).next('span').addClass(
									'cvv-image-ax active');
						}
					} else {
						jQuery(field.container).next('span').addClass(
								'cvv-image active');
					}

				} else {
					jQuery('#cvv').next('span').attr('class', 'cvv-image');
				}
				jQuery(field.container).parent('div').addClass('active');
			}

			ClassicForm.onBlur = function(e, event){
				var field = event.fields[event.emittedBy];
				jQuery(field.container).parent('div').removeClass('active');
			}
			
			ClassicForm.validityChange = function(e, event) {
				var field = event.fields[event.emittedBy];
				if (field.isValid) {
					jQuery(field.container).parent().removeClass('has-warning')
							.addClass('has-success');
				} else if (field.isPotentiallyValid) {
					jQuery(field.container).parent().removeClass(
							'has-warning has-success');
				} else {
					jQuery(field.container).parent().addClass('has-warning');
				}
			}

			ClassicForm.beforeFormInitialize = function(e){
				if(braintreeCheckout.cart_contains_subscription || braintreeCheckout.add_paymentmethod_page
						|| !braintreeCheckout.save_payment_method_enabled){
					jQuery('.save-payment-method-label').hide();
				}
			}
			
			ClassicForm.onTokenizationError = function(e, err){
				if(err.details && err.details.invalidFieldKeys.length > 0){
					for(var i=0;i<err.details.invalidFieldKeys.length;i++){
						var key = err.details.invalidFieldKeys[i];
						var field = braintreeCheckout.getFields()[key];
						jQuery(field.selector).parent('div').addClass('has-warning');
					}
				}
			}
			
			ClassicForm.updateContainerCSS = function(e){
				if(jQuery('#payment').width() < 545){
					jQuery('#braintree_custom_form').addClass('small-container');
				}
			}
			
			jQuery(document.body).on('braintree_field_not_empty',
					ClassicForm.notEmpty);
			jQuery(document.body)
					.on('braintree_field_empty', ClassicForm.empty);
			jQuery(document.body).on('braintree_card_type_change',
					ClassicForm.cardTypeChange);
			jQuery(document.body).on('braintree_field_focus',
					ClassicForm.onFocus);
			jQuery(document.body).on('braintree_field_validity_change',
					ClassicForm.validityChange);
			jQuery(document.body).on('braintree_field_blur', ClassicForm.onBlur);
			jQuery(document.body).on('braintree_custom_form_loaded', ClassicForm.updateContainerCSS);
			jQuery(document.body).on('braintree_before_form_initialize', ClassicForm.beforeFormInitialize);
			jQuery(document.body).on('braintree_tokenization_error', ClassicForm.onTokenizationError);
		});