function custtype() {
		var type = jQuery('[name=custtype]').val();
		if(type == 'select' || type == 'radio' || type == 'cselect') {
			jQuery('.choiceswrap').show();
		} else {
			jQuery('.choiceswrap').hide();		
		}
}

function custproducts() {
		if(jQuery('[name=custalways]').is(':checked')) {
			jQuery('.prodwrap').hide();
		} else {
			jQuery('.prodwrap').show();		
		}
}

jQuery(document).ready(function() {
	custtype();
	jQuery('[name=custtype]').change( function() {
		custtype();
	});

	custproducts();
	jQuery('[name=custalways]').click( function() {
		custproducts();
	});
});