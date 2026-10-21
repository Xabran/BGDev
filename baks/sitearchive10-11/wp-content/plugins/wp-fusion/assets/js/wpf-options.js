jQuery(document).ready(function($){

	// Settings page specific functions

	if($('body').hasClass('settings_page_wpf-settings')) {

		$('[data-toggle="tooltip"]').tooltip()

		//
		// Import Users
		//

		$( "#import-users-btn" ).on( "click", function() {

	       	if($('[name^="wpf_options[import_users]"] option:selected').length == 0) {
	       		$('[name^="wpf_options[import_users]"]').next().addClass('error');
				setTimeout( function() { $('[name^="wpf_options[import_users]"]').next().removeClass('error'); }, 1000 );
	       		return;
	       	}

			$(this).html('<i class="fa-li fa fa-spinner fa-spin"></i> Importing'); 
	       	$(this).attr('disabled', 'disabled');

	    	var button = $(this);

			var params = {};
			$('[name^="wpf_options[import_users]"]').each(function() {
			    params[$(this).attr('id')] = $(this).val();
			});

	        var data = {
				'action'	: 'import_users',
				'params'	: JSON.stringify(params),
				'role'		: $('#import_role').val(),
				'notify'	: $('#email_notifications').is(':checked')
			};

			$.post(ajaxurl, data, function(response) {

				button.html('Import');
				button.removeAttr('disabled');

				if(response > 0) {
					$('#import-output').html('<div class="updated"><p><strong>Success:</strong> ' + response + ' new contacts imported.</p></div>');
				} else {
					$('#import-output').html('<div class="error"><p><strong>Error:</strong> No new contacts found.</p></div>');
				}

			});

		});

		$( ".delete-import-group" ).on( "click", function() {

			var button = $(this);

			button.attr('disabled', 'disabled');

			button.closest('tr').children('.import-date').children('.progress-bar').width('100');

			if(confirm("WARNING: All users from this import will be deleted, and any user content will be reassigned to your account.") == true) {

				button.closest('tr').children('.import-date').children('.progress-bar').width($('#import-groups').width());

		        var data = {
					'action'	: 'delete_import_group',
					'group_id'	: button.data('delete')
				};

				$.post(ajaxurl, data, function(response) {

					if(response.success == true) {
						button.closest('tr').remove();
					} else {
						button.closest('tr').children('.import-date').children('.progress-bar').width('0');
					}

				});

			} else {
				button.closest('tr').children('.import-date').children('.progress-bar').width('0');
				button.removeAttr('disabled');
			}

		});

		//
		// Export
		//

		// Process export step

		var processExportStep = function(button, actions, index, offset, max) {

			// If current objective is complete, move to next stage
			if(offset >= max) {
				startExport(button, actions, index + 1);
				return;
			}

			//console.log('export step for ' + actions[index].action + ' with offset ' + offset);

			var data = {
				'action'	: 'wpf_batch_step',
				'hook'		: actions[index].action,
				'offset'	: offset,
				'number'	: actions[index].batchSize
			};

			offset = parseInt(offset) + parseInt(actions[index].batchSize);

			// Don't show "processing" counts for more than the maximum
			if(offset > max) {
				offset = max;
			}

			button.find('span').html('Processing ' + offset + ' of ' + max + ' ' + actions[index].title);

			$.post(ajaxurl, data, function(result) {

				processExportStep(button, actions, index, offset, max );

			});

		}

		// Start export stage

		var startExport = function(button, actions, index) {

			if(index >= actions.length) {
				completeExport( button );
				return;
			}

			button.html('<i class="fa-li fa fa-spinner fa-spin"></i><span>Beginning ' + actions[index].title + ' Processing</span>');
			 
			var data = {
				'action'	: 'wpf_batch_init',
				'hook'		: actions[index].action
			};

			$.post(ajaxurl, data, function(total) {

				if(actions[index].batchSize > total) {
					actions[index].batchSize = parseFloat(total);
				}

				processExportStep(button, actions, index, 0, total);

			});

		}

		// Complete export

		var completeExport = function(button) {

			button.html('Processing Complete');
			window.onbeforeunload = null;

		}

		// Export button handlers

		$( "#export-btn" ).on( "click", function() {

			if($('input.export-options:checked').length == 0) {
				return
			}

			if (confirm('Export will now run. Do not close this page until export is complete.') != true) {
				return;
			}

			// Set up warning if they try and leave the page
			window.onbeforeunload = function() {
				return 'WARNING: Currently processing. Leaving the page will abort the batch process.';
			};

	        $(this).attr('disabled', 'disabled');
	        var button = $(this);
	        var actions = [];

			$('input.export-options:checked').each(function(i){
				actions[i] = { 'action': $(this).val(), 'title': $(this).attr('data-title'), 'batchSize': parseFloat($(this).attr('data-batch-size')) }
			});

			startExport(button, actions, 0);

		});




		//
		// Test Connection and perform initial sync
		//

		// Syncs users in batches to avoid timeouts on sites with many users

		var processStep = function(offset, number, max, crmContainer) {

			var currentSyncNumber = offset + number;
			if(currentSyncNumber >= max) {
				currentSyncNumber = max;
			}

			$( "a#test-connection" ).html('<i class="fa-li fa fa-spinner fa-spin"></i><span>Syncing ' + currentSyncNumber + ' of ' + max + ' users</span>');

			var data = {
				'action'	: 'wpf_sync_users',
				'offset'	: offset,
				'number'	: number
			};

			$.post(ajaxurl, data, function(response) {

				if(response.success == true) {

					if(offset + number >= max) {

						// Update button to indicate we're done
						$( "a#test-connection" ).html('Complete');
						$('#connection_configured').val(true);
						$(crmContainer).find('#connection-output').html('<div class="updated"><p><strong>Congratulations:</strong> you\'ve successfully established a connection to ' + $(crmContainer).attr('data-name') + ' and your data has been imported. Press "Save Changes" below to finalize.</p></div>');

					} else {
						processStep(number + offset, number, max, crmContainer);
					}

				}

			});

		}

		// Button handler for test connection / resync

		$( "a#test-connection" ).on( "click", function() {

			$(this).html('<i class="fa-li fa fa-spinner fa-spin"></i> Connecting');
	        $(this).attr('disabled', 'disabled');

	        var button = $(this);
	        var crmContainer = $(this).parents('div.crm-config');
	        var crm = $(crmContainer).attr('data-crm');

	        var data = {
				'action'	: 'wpf_test_connection_' + crm
			};

			// Add the submitted data
			postFields = $(this).attr('data-post-fields').split(',');

			$(postFields).each(function(index, el) {
				data[el] = $('input#' + el).val();
			});

			// Test the CRM connection

			$.post(ajaxurl, data, function(response) {
				
				if(response.success != true) {

					$(crmContainer).find('#connection-output').html('<div class="error"><p><strong>Error: </strong>' + response.data + '</p></div>');
					button.html('Retry').removeClass('btn-success').addClass('btn-danger').removeAttr('disabled');

				} else {

					// Sync tags and custom fields

					button.html('<i class="fa-li fa fa-spinner fa-spin"></i><span>Syncing Tags &amp; Fields</span>').addClass('btn-success');

					data['action'] = 'wpf_sync';

					$.post(ajaxurl, data, function(response) {

						if(response.success == true) {

							// Process user sync in batches
							processStep(0, parseFloat(button.attr('data-batch-size')), parseFloat(button.attr('data-total-users')), $(crmContainer));

						}

					});

				}

			});

		});


		//
		// Change CRM
		//

		$('#wpf-settings select#crm').change(function(event) {
			
			$('#wpf-settings').find('div.crm-active').slideUp().removeClass('crm-active').addClass('hidden');
			$('#wpf-settings').find('div#' + $(this).val()).slideDown().addClass('crm-active').removeClass('hidden');

		});

		//
		// Activate / deactivate license
		//

		$( "#edd-license" ).on( "click", function() {
			$(this).html('<i class="fa-li fa fa-spinner fa-spin"></i> Connecting'); 
	        $(this).attr('disabled', 'disabled');

	        var button = $(this);

	        var data = {
				'action'	: $(this).attr('data-action'),
				'key'		: $('#license_key').val()
			};

			$.post(ajaxurl, data, function(response) {

				console.dir(response);
				
				if(response.success == true && response.data == 'activated') {

					button.html('Deactivate License').removeAttr('disabled').attr('data-action', 'edd_deactivate');
					$('#license_key').attr('disabled', 'disabled');
					$('#license_status').val('valid');

				} else if(response.success == true && response.data == 'deactivated') {

					button.html('Activate License').removeAttr('disabled').attr('data-action', 'edd_activate');
					$('#license_key').removeAttr('disabled');
					$('#license_status').val('invalid');

				} else {

					button.html('Retry').addClass('btn-danger').removeAttr('disabled');
					$('#connection-output-edd').html('<div class="error validation-error"><p>Error processing request. Debugging info below:</p></div><br/>' + response.data);


				}

			});

		});


		$('table#contact-fields select.select4-crm-field').change(function(event) {
			
			if(!$(this).val()) {

				$(this).closest('td').siblings().find('input.contact-fields-checkbox').attr('disabled');
				$(this).closest('tr').find('input.contact-fields-checkbox').prop('checked', false);
				$(this).closest('tr').removeClass('success');

			} else {

				$(this).closest('td').siblings().find('input.contact-fields-checkbox').removeAttr('disabled');
				$(this).closest('tr').find('input.contact-fields-checkbox').prop('checked', true);
				$(this).closest('tr').addClass('success');

			}

		});

		// When a checkbox is configured to unlock other options
		$('[data-unlock]').click(function() {

			var targets = $(this).data('unlock').split(" ");
			var ischecked = $(this).prop('checked');

			$.each(targets, function( index, target ) {

				$('#' + target).closest( 'tr' ).toggleClass('disabled');
			    $('#' + target).prop('disabled', !ischecked); 

			});

		});

		$( ".contact-fields-checkbox" ).on( "click", function() {
			$(this).closest( 'tr' ).toggleClass('success');
		});

		$('form').bind('submit', function() {
	    	$(this).find(':input').removeAttr('disabled');
	    });

	} // end WPF settings page listeners



});