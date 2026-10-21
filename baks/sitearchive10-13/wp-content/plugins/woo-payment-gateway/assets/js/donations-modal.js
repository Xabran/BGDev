/*Script for modal functionality.*/
var DonationModal = function() {
	this.clientToken = document.getElementById('client_token').value;
	this.setupEvents();
};

DonationModal.prototype.setupEvents = function() {
	// jQuery(document.body).on('setup_braintree', this.setup);
	jQuery(document.body).on('click', '#modal_button', this.modalButtonClicked);
	jQuery(document.body).on('click', '#cancel_donation',
			this.cancelDonationClicked);
	jQuery(document.body).on('keyup', '#donation-form input',
			this.clearInvalidEntries)
}

/* Setup the Braintree Dropin */
DonationModal.prototype.setup = function() {
	if (donationModal.integration) {
		return;
	}
	braintree.setup(donationModal.clientToken, 'dropin', {
		container : 'dropin-container',
		form : 'donation-form',
		onReady : function(integration) {
			donationModal.onReady(integration);
		},
		onPaymentMethodReceived : function(response) {
			donationModal.onPaymentMethodReceived(response);
		}
	});
}

/* Handle the onReady callback */
DonationModal.prototype.onReady = function(integration) {
	donationModal.integration = integration;
}

/* Handle the resposne from the onPaymentMethodReceived callback. */
DonationModal.prototype.onPaymentMethodReceived = function(response) {
	donationModal.paymentMethodReceived = true;
	var element = document.getElementById('payment_method_nonce');
	if (element != null) {
		element.value = response.nonce;
	} else {
		element = document.createElement('input');
		element.type = 'hidden';
		element.name = 'payment_method_nonce';
		element.id = 'payment_method_nonce';
		element.value = response.nonce;
		jQuery('#donation-form').append(element);
	}
	donationModal.validateInputFields();
}

DonationModal.prototype.teardown = function() {
	if (donationModal.integration) {
		donationModal.integration.teardown();
		donationModal.integration = null;
	}
}

DonationModal.prototype.submitPayment = function() {
	var data = jQuery('#donation-form').serialize();
	var url = jQuery('#ajax_url').val();
	jQuery('.overlay-payment-processing').addClass('active');
	jQuery.ajax({
		type : 'POST',
		url : url,
		dataType : 'json',
		data : data
	}).done(function(response) {
		jQuery('.overlay-payment-processing').removeClass('active');
		if (response.result === 'success') {
			donationModal.redirect(response.url);
		} else {
			donationModal.showErrorMessage(response.message);
		}
	}).fail(function(response) {
		jQuery('.overlay-payment-processing').removeClass('active');
		donationModal.showErrorMessage(response.message);
	});
}

DonationModal.prototype.redirect = function(url) {
	window.location.replace(url);
}

DonationModal.prototype.showErrorMessage = function(message) {
	jQuery('#error_messages').html(message);
}

DonationModal.prototype.modalButtonClicked = function() {
	if (!donationModal.integration) {
		donationModal.setup();
	}
	donationModal.displayDonationForm();
}

DonationModal.prototype.displayDonationForm = function(callback) {
	// jQuery('#donation_container').fadeIn(400, callback);
	donationModal.displayOverlay(callback);
}

DonationModal.prototype.displayOverlay = function(callback) {
	jQuery('#donation_overlay').fadeIn(400, callback);
}

DonationModal.prototype.hideOverlay = function(callback) {
	jQuery('#donation_overlay').fadeOut(400, callback);
}

DonationModal.prototype.cancelDonationClicked = function() {
	donationModal.teardown();
	donationModal.hideOverlay();
	donationModal.clearErrorMessages();
}

/* Validate the inputs */
DonationModal.prototype.validateInputFields = function() {
	var hasFailures = false;
	jQuery('#donation-form input').each(
			function() {
				if (jQuery(this).val() === "") {
					jQuery(this).parent().find('div.invalid-input-field')
							.show().addClass('active');
					hasFailures = true;
				}
			});
	if (!hasFailures) {
		donationModal.submitPayment();
	}
}

DonationModal.prototype.clearInvalidEntries = function() {
	jQuery(this).parent().find('div.invalid-input-field').hide().removeClass(
			'active');
}

DonationModal.prototype.clearErrorMessages = function() {
	jQuery('#error_messages').empty();
}

var jQuery = jQuery;
var donationModal = new DonationModal();