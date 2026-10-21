
//
// Select4 Fields
//

// Tags select

function initializeTagsSelect(target) {

	if( jQuery( target + " select.select4-wpf-tags").length ) {

		if(jQuery.inArray('add_tags', wpf.crm_supports) > -1) {

			// For CRMs that support adding new tags via API

			jQuery( target + " select.select4-wpf-tags").each(function(index, el) {

				var limit = jQuery(this).attr('data-limit');

				if(!limit || limit.length == 0)
					limit = -1;

				jQuery(this).select4({
					multiple : true,
					minimumResultsForSearch: -1,
					tags : true,
					maximumSelectionLength: limit,
					insertTag: function(data, tag){
					    tag.text = tag.text + " (add new)"
					    data.push(tag);
					}
				});
			});

		} else {

			// From CRMs that need to resync

			jQuery( target + " select.select4-wpf-tags").each(function(index, el) {

				var limit = jQuery(this).attr('data-limit');

				if(!limit || limit.length == 0)
					limit = -1;

				var jQueryelement = jQuery( target + " select.select4-wpf-tags").select4({
					multiple : true,
					minimumResultsForSearch: -1,
					maximumSelectionLength: limit,
					language: {
						noResults: function() {
							var jQueryresync = jQuery("<a id='wpf-select4-tags-resync'>No results found: click to resynchronize</a>");

							jQueryresync.on('mouseup', function (evt) {
								evt.stopPropagation();
								jQuery(this).hide();
								jQuery(this).after('<span id="wpf-select4-tags-loading"><i class="fa fa-spinner fa-spin"></i> Loading tags, please wait...</span>');

								var data = {
									'action'	: 'sync_tags'
								}

								jQuery.post(ajaxurl, data, function(response) {

									jQuery('#wpf-select4-tags-loading').remove();
									jQueryresync.after('<span>Resync complete. Please try searching again.</span>');

									jQueryelement.append(response);
									jQueryelement.trigger('change');

								});

							});

							return jQueryresync;
						}
					},
					escapeMarkup: function (markup) {
						return markup;
					},
				});

			});

		}
		
	}

}

jQuery(document).ready(function($){

	initializeTagsSelect('#wpbody');

	// Standard select

	if( $("select.select4").length ) {

		$("select.select4").select4({
			minimumResultsForSearch: -1,
			allowClear: true
		});
		
	}

	// Search select

	if( $("select.select4-search").length ) {

		$("select.select4-search").select4({
			allowClear: true
		});
		
	}

	// CRM field select

	function initializeCRMFieldSelect() {

		if( $("select.select4-crm-field").length ) {

			function matcher (params, data) {
				// Always return the object if there is nothing to compare
				if ($.trim(params.term) === '') {
					return data;
				}

				var original = data.text.toUpperCase();
				var term = params.term.toUpperCase();

				// Check if the text contains the term
				if (original.indexOf(term) > -1) {
					return data;
				}

				// Do a recursive check for options with children
				if (data.children && data.children.length > 0) {
					// Clone the data object if there are children
					// This is required as we modify the object to remove any non-matches
					var match = $.extend(true, {}, data);

					// Check each child of the option
					for (var c = data.children.length - 1; c >= 0; c--) {
						var child = data.children[c];

						var matches = matcher(params, child);

						// If there wasn't a match, remove the object in the array
						if (matches == null) {
							match.children.splice(c, 1);
						}
					}

					// If any children matched, return the new object
					if (match.children.length > 0) {
						return match;
					}

					// If there were no matching children, check just the plain object
					return matcher(params, match);
				}

				// If it doesn't contain the term, don't return anything
				return null;
			}


			if($.inArray('add_fields', wpf.crm_supports) > -1) {

				// For CRMs that support adding new custom fields via API

				$("select.select4-crm-field").select4({
					allowClear: true,
					tags: true,
					multiple: false,
					matcher: matcher,
						createTag: function(params) {

							var term = $.trim(params.term);

							if(term === "") { return null; }

							var optionsMatch = false;

							this.$element.find("option").each(function() {
									if(this.label.toLowerCase().indexOf(term.toLowerCase()) > -1) {
										optionsMatch = true;
									}
							});

							if(optionsMatch) {
									return null;
							}

							return {id: term, text: term + ' (new)'};
						}
				});

			} else {

				// For CRMs that need to resync

				var $element = $("select.select4-crm-field").select4({
					allowClear: true,
					multiple: false,
					language: {
						noResults: function() {
							var $resync = $("<a id='wpf-select4-tags-resync'>No results found: click to resynchronize</a>");

							$resync.on('mouseup', function (evt) {
								evt.stopPropagation();
								$(this).hide();
								$(this).after('<span id="wpf-select4-tags-loading"><i class="fa fa-spinner fa-spin"></i> Loading fields, please wait...</span>');

								var data = {
									'action'	: 'sync_custom_fields'
								}

								$.post(ajaxurl, data, function(response) {

									$('#wpf-select4-tags-loading').remove();
									$resync.after('<span>Resync complete. Please try searching again.</span>');

									$element.append(response);
									$element.trigger('change');

								});

							});

							return $resync;
						}
					},
					escapeMarkup: function (markup) {
						return markup;
					},
				});

			}

		}

	}

	initializeCRMFieldSelect();


	//
	// Meta boxes
	//

	$('#wpf-meta [data-unlock]').click(function() {

		var targets = $(this).data('unlock').split(" ");
		var ischecked = $(this).prop('checked');

		$.each(targets, function( index, target ) {
			$('label[for="' + target + '"]').toggleClass('disabled');
			$('#' + target).prop('disabled', !ischecked); 
		});

	});

	// Remove "disabled" on select so the data still gets posted
	$('form').bind('submit', function() {
		$(this).find('#wpf-meta :input').removeAttr('disabled');
	});
	
	
	/*
	 * onchange wp-fusion cf7
	 */
	
	var cf7change = false;
	
	$('textarea#wpcf7-form').change(function(event){		
		cf7change = true;
	});	
	
	$('#wp-fusion-tab-tab').click(function(event) {

		var er = /\[.*\]/g;	
		var currentcf7 = $('textarea#wpcf7-form').val();
		if (cf7change == true){

			var newinputs = [];
			var ematcheds = currentcf7.match(er);
			ematcheds.forEach(function(items){
				var str1 = items.replace('[', '');
				var str2 = str1.replace(']', '');
				var str3 = str2.replace('*', '');
				var elements = str3.split(' ');

				newinputs[elements[1]] = elements[0];
			});

			/*
			newinputs.forEach(function(nitem, typex){
				$.post(ajaxurl, newinputs, function(response) {
					$('#input-row').remove();
					//$element.trigger('change');

				});
			});
			
			debugger;


			var data = currentcf7.toString().split('\n');
			

			data.forEach(function(item){
				newinputs.push(item);
			});
			
			//var finputs = $('tbody#wp-fusion-inputs');
			*/

		}
	});
	
	

 
	//
	// WooCommerce functions
	//

	if($('body').hasClass('post-type-product')) {

		// Update select4's when a new variation is added

		$(document).on('DOMNodeInserted', function(e) {

			if ($(e.target).hasClass('woocommerce_variation')) {

				initializeTagsSelect('#wpbody #variable_product_options');

			}
		});

		// Show / hide subscription fields

		function showHideSubscriptionFields() {

			if($( "select#product-type option:selected" ).val() == 'subscription' || $( "select#product-type option:selected" ).val() == 'variable-subscription') {
				$('.wpf_show_if_subscription').show();
			} else {
				$('.wpf_show_if_subscription').hide();
			}

		}

		showHideSubscriptionFields();

		// Trigger on product type change

		$('select#product-type').change(function(event) {
			showHideSubscriptionFields();
		});

	}

	//
	// User profile page
	//

	// Resync Contact

	if($( "#resync-contact" ).length) {

		$( "#resync-contact" ).on( "click", function() {

			$(this).html('Syncing'); 
	        $(this).attr('disabled', 'disabled');

	        var button = $(this);

	        var data = {
				'action'	: 'resync_contact',
				'user_id' 	: $(this).data('user_id')
			};

			$.post(ajaxurl, data, function(response) {

				if(response.success == false) {

					$('td#contact-id').html('No contact record found.');
					$('#wpf-tags-row').remove();

				} else {

					response = $.parseJSON(response);

					// Set contact ID
					$('td#contact-id').html(response.contact_id);

					// If no tags
					if(response.user_tags == false) {
						$('td#wpf-tags-td').html('No tags applied.');
					} else {
						$('td#wpf-tags-td').html('Reload page to see tags.');
					}

				}

				button.html('Resync Contact');
				button.removeAttr('disabled');

			});

		});

	}

	// EDD

	if($('body').hasClass('post-type-download')) {


		// Variable pricing

		$( document.body ).on( 'change', '#edd_variable_pricing', function(e) {
			initializeTagsSelect('#wpbody');
		});

		$( document.body ).on( 'click', '#edd_price_fields a.edd_add_repeatable', function(e) {
			$('#edd_price_fields tbody tr.edd_repeatable_row').last().find('td.wpf-tags-select span.select4').remove();
			initializeTagsSelect('#wpbody');
			$('#edd_price_fields tbody tr.edd_repeatable_row').last().find('td.wpf-tags-select span.select4').css('width', '100%');
			$('#edd_price_fields tbody tr.edd_repeatable_row').last().find('td.wpf-tags-select span.select4 input.select4-search__field').css('width', '120px');
		});


		// Recurring payments

		if($( ".edd-recurring-enabled" ).length) {

			$('.edd-recurring-enabled select, select#edd_recurring').change(function(event) {
				
				var recurring = false;

				$('.edd-recurring-enabled select, select#edd_recurring').each(function(index, el) {
					if($(this).val() == 'yes') {
						recurring = true;
					}
				});

				if(recurring == true) {
					$('table.wpf-edd-recurring-options').slideDown();
				} else {
					$('table.wpf-edd-recurring-options').slideUp();
				}

			});

		}
	}

});