jQuery(document).ready(function($){

	$.each(wpf_async.hooks, function(index, val) {
		 
		var data = {
			'action'	 : 'wpf_async',
			'data'		 : val
		};

		$.post(wpf_async.ajaxurl, data);

	});

});