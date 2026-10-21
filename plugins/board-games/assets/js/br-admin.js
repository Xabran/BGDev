;(function($) {
	'use strict';
        var ajaxurl = '/wp-admin/admin-ajax.php';
	var spin = '<div class="ajax-loader"><span class="spinner">';
	$('body').on('click', '.add_video_button_ajax button', function() {
            alert('testtest');
            e.preventDefault();
            //var post_id = jQuery(this).data('id');
            alert('check!');
            var valid = true;
            if($('._brgm_video_embedurl').text === ''){
                alert("Embed URL is required!");
                valid = false;
            }
            if(valid){
                jQuery.ajax({
                        url : ajax_url,
                        type : 'post',
                        data : {
                                action : 'product_videosadd_ajax'
                        },
                        success : function( response ) {
                                alert(response);
                        },
                        error: function(errorThrown) {
					console.log( errorThrown );
			}
                });
            }
	});
	
})(jQuery);