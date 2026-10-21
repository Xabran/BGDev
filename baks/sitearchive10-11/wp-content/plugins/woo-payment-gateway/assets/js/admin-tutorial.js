jQuery(document).ready(function(){
	jQuery(document.body).on('click', '.div--tutorialHeader ul li a', function(e){
		e.preventDefault();
		var id = jQuery(this).attr('tutorial-container');
		jQuery('.braintree-explanation-container').each(function(index){
			jQuery(this).slideUp(400);
		});
		jQuery('#' + id).slideDown(400);
	});
	
	jQuery(document.body).on('click', '.span-subscription_type', function(e){
		e.preventDefault();
		jQuery('.tutorials_subscription_type').slideUp();
		var elementName = jQuery(this).attr('type');
		jQuery(elementName).slideDown();
	});
});