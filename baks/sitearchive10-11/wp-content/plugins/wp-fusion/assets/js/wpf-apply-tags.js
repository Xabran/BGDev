jQuery(document).ready(function($){

	setTimeout(function() {

		var data = {
			'action'	 : 'apply_tags',
			'user_id'	 : wpf_ajax.user_id,
			'tags'		 : wpf_ajax.tags
		};

		$.post(wpf_ajax.ajaxurl, data, function(response) {

			// Nothing to see here

		});

	}, wpf_ajax.delay);

});