var BraintreeCheckout = function() {
	this.updated_checkout_count = 0;
	this.form = jQuery('#braintree_custom_form').closest('form')[0];
	this.clientToken = jQuery('#client_token').val();
	this._3ds_enabled = braintree_custom_form_options._3ds_enabled === 'true' ? true : false;
	this.loader_enabled = braintree_custom_form_options.enable_loader === 'yes' ? true : false;
	this.options = braintree_custom_form_options;
	this.is_checkout_page = braintree_custom_form_options.checkout_page === 'true' ? true : false;
	this.is_order_recieved_page = braintree_custom_form_options.order_received_page === 'true' ? true : false;
	this.add_paymentmethod_page = braintree_custom_form_options.add_paymentmethod_page === 'true' ? true : false;
	this.cart_contains_subscription = braintree_custom_form_options.cart_contains_subscription === 'true' ? true : false;
	this.save_payment_method_enabled = braintree_custom_form_options.save_payment_methods === 'true' ? true : false;
	this.change_payment_method_page = braintree_custom_form_options.change_payment_method === 'true' ? true : false;
	this.setupEvents();
	this.formLoaded();
}

var braintreeCheckout = null;

BraintreeCheckout.instance = function() {
	if(braintreeCheckout == null){
		braintreeCheckout = new BraintreeCheckout();
	}
	if (!braintreeCheckout.hasSavedPaymentMethods() || braintreeCheckout.use_new_payment) {
			braintreeCheckout.initializeForm();
	} else {
		braintreeCheckout.selectDefaultMethod();
		jQuery(document.body).trigger('braintree_saved_payment_form_setup');
	}
}

BraintreeCheckout.prototype.setupEvents = function() {
	jQuery(this.form).on('checkout_place_order', this.checkoutPlaceOrder);
	jQuery(document.body).on('checkout_error', this.checkoutError);
	jQuery(document.body).on('click', '#cancel_add_new', this.cancelAddNew);
	jQuery(document.body).on('click', '#add_new_method', this.addNewMethod);
	jQuery(document.body).on('click', '.payment-method-item',
			this.paymentMethodSelected);
	jQuery(document.body).on('braintree_saved_payment_form_setup', BraintreeCheckout.setupSavedPaymentForm);
	jQuery(document.body).on('braintree_checkout_initiated', this.displayCheckoutLoader);
	jQuery(document.body).on('braintree_checkout_error', this.hideCheckoutLoader);
	jQuery(document.body).on('braintree_tokenization_error', this.hideCheckoutLoader);
	jQuery(document.body).on('checkout_error', this.hideCheckoutLoader);
	
}

BraintreeCheckout.prototype.initializeForm = function() {
	BraintreeCheckout.checkCustomFormWidth();
	jQuery(document.body).trigger('braintree_before_form_initialize');
	braintreeCheckout.removeExtraFrames();
	braintreeCheckout.initializeCalled = true;
	braintreeCheckout.updated_checkout_count++;
	braintree.client.create({
		authorization : braintreeCheckout.clientToken
	}, function(err, clientInstance) {
		if (err) {
			braintreeCheckout.submit_error(err.message);
			return;
		}
		braintree.hostedFields.create({
			client : clientInstance,
			styles : braintreeCheckout.getStyles(),
			fields : braintreeCheckout.getFields(),
		}, function(err, hostedFieldsInstance) {
			if (err) {
				if(braintreeCheckout.updated_checkout_count > 1){
					return;
				}
				braintreeCheckout.submit_error(err.message);
				return;
			}
			braintreeCheckout.initializeCalled = false;
			var submitButton = jQuery(braintreeCheckout.form).find('#place_order')[0];
			braintreeCheckout.removeExtraFrames();
			braintreeCheckout.hostedFieldsInstance = hostedFieldsInstance;
			hostedFieldsInstance.on('validityChange', braintreeCheckout.validityChange);
			hostedFieldsInstance.on('cardTypeChange', braintreeCheckout.cardTypeChange);
			hostedFieldsInstance.on('empty', braintreeCheckout.fieldEmpty);
			hostedFieldsInstance.on('notEmpty', braintreeCheckout.fieldNotEmpty);
			hostedFieldsInstance.on('blur', braintreeCheckout.fieldBlur);
			hostedFieldsInstance.on('focus', braintreeCheckout.fieldFocus);
			hostedFieldsInstance.on('inputSubmitRequest', braintreeCheckout.inputSubmitRequest);
			submitButton.addEventListener('click', function(e) {
				if(braintreeCheckout.isGatewaySelected() && ! braintreeCheckout.isPaymentMethodSelected()){
					jQuery(document.body).trigger('braintree_checkout_initiated');
					e.preventDefault();
					try{
						hostedFieldsInstance.tokenize(function(err, payload) {
							if (err) {
								braintreeCheckout.tokenizationStarted = false;
								jQuery(document.body).trigger('braintree_tokenization_error', err);
								return;
							}
							if(braintreeCheckout._3ds_enabled && braintreeCheckout.is_checkout_page){
								braintreeCheckout.verify3DSecure(payload);
							}else{
								braintreeCheckout.onPaymentMethodReceived(payload);
							}
						});
					}catch(err){
						jQuery(document.body).trigger('braintree_checkout_error');
					}
				}
			});
		});
		if(braintreeCheckout._3ds_enabled && braintreeCheckout.is_checkout_page){
			braintree.threeDSecure.create({
				client: clientInstance
			}, function(error, threeDSecureInstance){
				if(error){
					braintreeCheckout._3ds_enabled = false;
					braintreeCheckout.submit_error(error.message);
					return;
				}
				braintreeCheckout.threeDSecure = threeDSecureInstance;
			})
		}
	});
	if(braintreeCheckout.use_new_payment){
		braintreeCheckout.hidePaymentMethods();
		braintreeCheckout.showCustomForm();
	}
	braintreeCheckout.removeExtraFrames();
}

BraintreeCheckout.prototype.onPaymentMethodReceived = function(response) {
	var element = document.getElementById('payment_method_nonce');
	if (element != null) {
		element.value = response.nonce;
	} else {
		element = document.createElement('input');
		element.type = 'hidden';
		element.name = 'payment_method_nonce';
		element.id = 'payment_method_nonce';
		element.value = response.nonce;
		jQuery(braintreeCheckout.form).append(element);
	}
	braintreeCheckout.paymentMethodReceived = true;
	jQuery(braintreeCheckout.form).submit();
}

BraintreeCheckout.prototype.verify3DSecure = function(response){
	braintreeCheckout.loadThreeDSSpinner();
	braintreeCheckout.threeDSecure.verifyCard({
		amount: braintree_custom_form_options.order_total,
		nonce: response.nonce,
		addFrame: braintreeCheckout.addFrame,
		removeFrame: braintreeCheckout.removeFrame
	}, function(error, response){
		if(error){
			jQuery('#braintree_3ds_container').empty().fadeOut();
			jQuery(braintreeCheckout.form).closest('body').removeClass('threeDS-body');
			braintreeCheckout.submit_error(error.message);
			return;
		}
		jQuery('#braintree_3ds_container').empty().fadeOut();
		jQuery(braintreeCheckout.form).closest('body').removeClass('threeDS-body');
		braintreeCheckout.onPaymentMethodReceived(response);
	})
}

BraintreeCheckout.prototype.submit_error = function(error_message){
	jQuery( '.woocommerce-error, .woocommerce-message' ).remove();
	jQuery(braintreeCheckout.form).prepend( '<div class="woocommerce-error">'+error_message+'</div>' );
	jQuery(braintreeCheckout.form).removeClass( 'processing' ).unblock();
	jQuery(braintreeCheckout.form).find( '.input-text, select, input:checkbox' ).blur();
	jQuery( 'html, body' ).animate({
		scrollTop: ( jQuery( 'form.checkout' ).offset().top - 100 )
	}, 1000 );
}

BraintreeCheckout.prototype.addFrame = function(error, iFrame){
	if(error){
		jQuery('#braintree_3ds_container').empty().fadeOut();
		jQuery(braintreeCheckout.form).closest('body').removeClass('threeDS-body');
		braintreeCheckout.submit_error(error.message);
		return;
	}
	jQuery('.threeDS-spinner').fadeOut(400, function(){
		braintreeCheckout.spinner.stop();
		jQuery('.threeDS-spinner').remove();
		jQuery('#braintree_3ds_container').prepend(braintree_custom_form_options._3ds_modal_html);
		jQuery('.threeDS-frame-body').prepend(iFrame);
		jQuery('.threeDS-cancel').on('click', braintreeCheckout.removeFrame);
	});
}

BraintreeCheckout.prototype.removeFrame = function(){
	braintreeCheckout.threeDSecure.cancelVerifyCard(function(){
		jQuery('#braintree_3ds_container').empty().fadeOut();
		jQuery(braintreeCheckout.form).closest('body').removeClass('threeDS-body');
	})
}

BraintreeCheckout.prototype.loadThreeDSSpinner = function(){
	jQuery('#braintree_3ds_container').prepend(braintree_custom_form_options._3ds_spinner_html).fadeIn(400, function(){
		jQuery(braintreeCheckout.form).closest('body').addClass('threeDS-body');
	});
	jQuery(braintreeCheckout.form).closest('body').addClass('threeDS-body');
	var target = jQuery('.threeDS-spinner')[0];
	var opts = {
			  lines: 13 // The number of lines to draw
			, length: 28 // The length of each line
			, width: 20 // The line thickness
			, radius: 42 // The radius of the inner circle
			, scale: 1 // Scales overall size of the spinner
			, corners: 1 // Corner roundness (0..1)
			, color: '#fff' // #rgb or #rrggbb or array of colors
			, opacity: 0.25 // Opacity of the lines
			, rotate: 0 // The rotation offset
			, direction: 1 // 1: clockwise, -1: counterclockwise
			, speed: 1 // Rounds per second
			, trail: 60 // Afterglow percentage
			, fps: 20 // Frames per second when using setTimeout() as a fallback for CSS
			, zIndex: 2e9 // The z-index (defaults to 2000000000)
			, className: 'spinner' // The CSS class to assign to the spinner
			, top: '50%' // Top position relative to parent
			, left: '50%' // Left position relative to parent
			, shadow: false // Whether to render a shadow
			, hwaccel: false // Whether to use hardware acceleration
			, position: 'absolute' // Element positioning
			}
	braintreeCheckout.spinner = new Spinner(opts).spin(target);
}

BraintreeCheckout.prototype.fieldEmpty = function(event){
	jQuery(document.body).trigger('braintree_field_empty', event);
}

BraintreeCheckout.prototype.fieldNotEmpty = function(event){
	jQuery(document.body).trigger('braintree_field_not_empty', event);
}

BraintreeCheckout.prototype.fieldBlur = function(event){
	jQuery(document.body).trigger('braintree_field_blur', event);
}

BraintreeCheckout.prototype.fieldFocus = function(event){
	jQuery(document.body).trigger('braintree_field_focus', event);
}

BraintreeCheckout.prototype.validityChange = function(event) {
	jQuery(document.body).trigger('braintree_field_validity_change', event);
}

BraintreeCheckout.prototype.cardTypeChange = function(event){
	jQuery(document.body).trigger('braintree_card_type_change', event);
}

BraintreeCheckout.prototype.inputSubmitRequest = function(event){
	jQuery(document.body).trigger('braintree_card_input_submit_request', event);
}

BraintreeCheckout.prototype.displayCheckoutLoader = function(){
	if(braintreeCheckout._3ds_enabled){
		return;
	}
	if(! braintreeCheckout.loader_enabled){
		return;
	}
	jQuery('#braintree_custom_form').block({
		message: braintree_custom_form_options.custom_form_loader_message,
		css: braintreeCheckout.getLoaderCSS()
	});
}

BraintreeCheckout.prototype.getLoaderCSS = function(){
	var styles = braintree_custom_form_options.custom_form_loader_css;
	if(styles.length > 0){
		var css = JSON.parse(styles);
	}else{
		var css = {};
	}
	return css;
}

BraintreeCheckout.prototype.hideCheckoutLoader = function(){
	jQuery('#braintree_custom_form').unblock();
}

BraintreeCheckout.prototype.checkoutPlaceOrder = function() {
	if (braintreeCheckout.isGatewaySelected()) {
		if (braintreeCheckout.isPaymentMethodSelected()) {
			return true;
		} else {
			if (braintreeCheckout.paymentMethodReceived) {
				return true;
			} else {
				return false;
			}
		}
	}
}

BraintreeCheckout.prototype.isGatewaySelected = function() {
	return document.getElementById('payment_method_braintree_payment_gateway') ? 
			document.getElementById('payment_method_braintree_payment_gateway').checked : false;
}

BraintreeCheckout.prototype.getStyles = function() {
	var styles = braintree_custom_form_options.custom_form_styles;
	if(styles.length > 0){
		styles = JSON.parse(styles);
	}else{
		styles = {};
	}
	if(typeof styles.input != 'undefined'){
		if(! styles.input['font-size']){
			styles.input['font-size'] = '16px';
		}
	}else{
		styles.input = {
				'font-size': '16px'
		}
	}
	jQuery(document.body).trigger('braintree_custom_fields_styles', styles);
	return styles;
}

BraintreeCheckout.prototype.getFieldContainerClass = function() {
	return braintree_custom_form_options.custom_form_field_container_class;
}

BraintreeCheckout.prototype.getFields = function() {
	if (!braintreeCheckout.fields) {
		var fields = {
			number : {
				selector : '#card-number',
				placeholder : braintree_custom_form_options.card_number_placeholder
			},
		}
		if(jQuery('#cvv').length){
			fields.cvv = {
				selector : '#cvv',
				placeholder : braintree_custom_form_options.card_cvv_placeholder
			}
		}
		if(jQuery('#postal-code').length){
			fields.postalCode = {
				selector : '#postal-code',
				placeholder : braintree_custom_form_options.card_postal_placeholder
			}
		}
		if (jQuery('#expiration-date').length) {
			fields.expirationDate = {
				selector : '#expiration-date',
				placeholder : braintree_custom_form_options.card_expiration_date_placeholder
			}
		} else if(jQuery('#expiration-month').length) {
			fields.expirationMonth = {
				selector : '#expiration-month',
				placeholder : braintree_custom_form_options.card_expiration_month_placeholder
			};
			fields.expirationYear = {
				selector : '#expiration-year',
				placeholder : braintree_custom_form_options.card_expiration_year_placeholder
			}
		}
		braintreeCheckout.fields = fields;
	}
	return braintreeCheckout.fields;
}

BraintreeCheckout.prototype.teardown = function(callback){
	try {
		if(braintreeCheckout.hostedFieldsInstance){
			var callback = callback;
			braintreeCheckout.hostedFieldsInstance.teardown(function() {
				braintreeCheckout.initializeCalled = false;
				if(callback){
					callback();
				}
			});
		}
	} catch (err) {
		
	}
}

BraintreeCheckout.prototype.cancelAddNew = function() {
	braintreeCheckout.teardown();
	braintreeCheckout.hideCustomForm();
	braintreeCheckout.use_new_payment = false;
	braintreeCheckout.selectDefaultMethod();
	braintreeCheckout.showPaymentMethods();
}

BraintreeCheckout.prototype.addNewMethod = function() {
	braintreeCheckout.removeSelectedPaymentMethod();
	braintreeCheckout.hidePaymentMethods();
	braintreeCheckout.use_new_payment = true;
	braintreeCheckout.initializeForm();
	braintreeCheckout.showCustomForm();
}

BraintreeCheckout.prototype.showCustomForm = function() {
	jQuery('#braintree_custom_form').slideDown(400);
}

BraintreeCheckout.prototype.hideCustomForm = function() {
	jQuery('#braintree_custom_form').slideUp(400);
}

BraintreeCheckout.prototype.hidePaymentMethods = function() {
	jQuery('#saved_payment_methods').slideUp(400);
}

BraintreeCheckout.prototype.showPaymentMethods = function() {
	jQuery('#saved_payment_methods').slideDown(400);
}

BraintreeCheckout.prototype.removeSelectedPaymentMethod = function() {
	jQuery('#selected_payment_method').val("");
}

BraintreeCheckout.prototype.paymentMethodSelected = function() {
	jQuery('.payment-method-item').each(function() {
		jQuery(this).removeClass('selected');
	});
	jQuery('#selected_payment_method').val(jQuery(this).attr('payment-token'));
	jQuery(this).addClass('selected');
}

BraintreeCheckout.prototype.hasSavedPaymentMethods = function() {
	return jQuery('#saved_payment_methods').length > 0;
}

BraintreeCheckout.prototype.selectDefaultMethod = function() {
	jQuery('.payment-method-item').each(
			function() {
				if (jQuery(this).hasClass('selected')) {
					jQuery('#selected_payment_method').val(
							jQuery(this).attr('payment-token'));
				}
			});
}

BraintreeCheckout.prototype.isPaymentMethodSelected = function() {
	if (jQuery('#selected_payment_method').length > 0
			&& jQuery('#selected_payment_method').val() !== "") {
		return true;
	}
	return false;
}

BraintreeCheckout.prototype.hideSavedPaymentMethodCheckbox = function(){
	jQuery('.save-card-info').hide();
}

BraintreeCheckout.prototype.removeExtraFrames = function() {
	var fields = braintreeCheckout.getFields();
	for ( var key in fields) {
		var field = fields[key];
		jQuery.each(jQuery(field.selector).children('iFrame'), function(index) {
			if (index > 0) {
				jQuery(this).remove();
			}
		})
	}
}

BraintreeCheckout.prototype.fadeInThreeDSModal = function(){
	jQuery('#braintree_3ds_container').fadeIn();
}

BraintreeCheckout.prototype.fadeOutThreeDSModal = function(){
	jQuery('#braintree_3ds_container').fadeOut();
}

BraintreeCheckout.prototype.formLoaded = function(){
	jQuery(document.body).trigger('braintree_custom_form_loaded');
}

BraintreeCheckout.verifyFormInitialized = function(){
	var length = jQuery('#braintree_custom_form').find('iframe').length;
	if(length == 0){
		BraintreeCheckout.instance();
	}
}

BraintreeCheckout.checkCustomFormWidth = function(){
	if(jQuery('.payment_methods').width() < 375){
		jQuery('#braintree_custom_form').addClass('small-container');
	}
}

BraintreeCheckout.setupSavedPaymentForm = function(){
	if(jQuery('.payment_methods').width() < 375){
		jQuery('#saved_payment_methods').addClass('small-container');
	}
}

BraintreeCheckout.prototype.checkoutError = function() {
	braintreeCheckout.paymentMethodReceived = false;
}

jQuery(document.body).on('updated_checkout', BraintreeCheckout.instance);
jQuery(document.body).on('init_add_payment_method', BraintreeCheckout.instance);
setInterval(BraintreeCheckout.verifyFormInitialized, 3000);