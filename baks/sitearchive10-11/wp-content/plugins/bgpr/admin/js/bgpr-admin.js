(function( $ ) {
	'use strict';

	$(function() {
		$('.btn_edit').click(function(e){
			e.preventDefault();

			var p_id = $(this).attr('data');
			$('#edit_box').slideDown();

			var data = {
				'action': 'bgpr_point_actions',
				'event': 'edit',
				'p_id': p_id
			};

			
			jQuery.post(ajaxurl, data, function(response) {
				var data = JSON.parse(response);
				$('#action_name').val(data.action_name);
				$('#action_type').val(data.action_type);
				$('#p_id').val(data.id);
				$('#action_value').val(data.action_value);
			});

		});

		$('.btn_edit_member').click(function(e){
			e.preventDefault();

			var user_id = $(this).attr('data');
			$('#edit_box').slideDown();

			var data = {
				'action': 'bgpr_edit_member_meta',
				'event': 'edit',
				'user_id': user_id
			};

			
			jQuery.post(ajaxurl, data, function(response) {
				var data = JSON.parse(response);

				$('#user_name').val(data.user_nicename);
				$('#bgpr_current_point_total').val(data.total_points);
				$('#orig_current_point_total').val(data.total_points);

				$('#bgpr_total_bucks').val(data.total_bucks);
				$('#orig_total_bucks').val(data.total_bucks);
				$('#user_id').val(data.user_id);
			});

		});

		$('.btn_delete').click(function(e){
			e.preventDefault();
			var p_id = $(this).attr('data');


			$('#tbl_action_points tr.row_'+ p_id).remove();
		});

		$('.btn_closebox').click(function (e){
			e.preventDefault();
			$('#edit_box').slideUp();
			$('#action_name').val('');
			$('#action_type').val('');
			$('#p_id').val('');
			$('#action_value').val('');
		});


		$('#form_point_edit').submit(function(e){
			e.preventDefault();
			var p_id = $('#p_id').val();
			var data = {
				'action': 'bgpr_point_actions',
				'event': 'update',
				'p_id': p_id,
				'action_name':$('#action_name').val(),
				'action_type':$('#action_type').val(),
				'action_value':$('#action_value').val()
			};

			jQuery.post(ajaxurl, data, function(response) {
				var data = JSON.parse(response);
				if (data.success==true){
					showMessage('success','Successfully updated data!');
				}
				$('#edit_box').slideUp();
				$('#action_name').val('');
				$('#action_type').val('');
				$('#p_id').val('');
				$('#action_value').val('');
				location.reload();
			});
		});


		$('#form_member_edit').submit(function(e){
			e.preventDefault();
			var user_id = $('#user_id').val();
			var reason = $('#reason').val();
			if (reason ==''){
				alert('Please enter the reason for changing the Member data.');
				return false;
			}
			var data = {
				'action': 'bgpr_edit_member_meta',
				'event': 'update',
				'user_id': user_id,
				'bgpr_current_point_total':$('#bgpr_current_point_total').val(),
				'bgpr_total_bucks':$('#bgpr_total_bucks').val(),
				'orig_current_point_total':$('#orig_current_point_total').val(),
				'orig_total_bucks':$('#orig_total_bucks').val(),
				'reason':$('#reason').val(),
				
			};

			jQuery.post(ajaxurl, data, function(response) {
				var data = JSON.parse(response);
				if (data.success==true){
					showMessage('success','Successfully updated data!');
				}
				$('#edit_box').slideUp();
				$('#bgpr_current_point_total').val('');
				$('#bgpr_total_bucks').val('');
				$('#orig_current_point_total').val('');
				$('#orig_total_bucks').val('');
				$('#user_id').val('');
				$('#reason').val('');
				
				location.reload();
			});
		});

		$('#flashMessage #message .notice-dismiss').on( "click", "#message", function() {
			$('#flashMessage').empty();
		});
	 
	});

	function showMessage(type, message)
	{
		close ='<button class="notice-dismiss" type="button"><span class="screen-reader-text">Dismiss this notice.</span></button>';
		$('#flashMessage').html('<div id="message" class="updated notice notice-'+type+' is-dismissible"><p>'+message+'</p>' +close + '</div>');
	}

	

})( jQuery );
