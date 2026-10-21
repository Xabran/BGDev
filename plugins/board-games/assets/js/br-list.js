;(function($) {
	'use strict';
        var ajaxurl = '/wp-admin/admin-ajax.php';
	var spin = '<div class="ajax-loader"><span class="spinner">';
	$('body').on('click', '.br-woo-list button', function() {

		var security	= $(this).parent().parent().data('nonce'),
			datas		= {
				'action'	: '_brgm_wclist',
				'security'	: security,
				'productID'	: $(this).data('id'),
				'listID'	: $(this).data('list'),
				'type'		: $(this).data('action'),
			}
		if (  security && security !== '0' ) {
			_list_action( datas, $(this) );
		} else {
			$('.bglog').trigger('click');
		}
	});
	$('.brgm-list').on('click', '.remove-item', function(e) {
        var list = $(this).closest('.brgm-list').data('list'),
        	me = $(this),
        	id = $(this).data('id'),
        	wrapper = $(this).closest('.brgm-list'),
        	cofirm = confirm("Are you sure you want to delete this?"),
        	security = $(this).closest('.brgm-list').data('nonce'),
        	datas = {
				'action'	: br_list.action,
				'security'	: security,
				'productID'	: id,
				'listID'	: list,
				'type'		: 'removefrom',
			};
        if ( cofirm && security && security !== '0' ) {
        	me.prop('disabled', true);
        	wrapper.find('#list-'+ id).addClass('removing');
        	$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: datas,
				success: function(data) { 
					if ( data.status ) {
						wrapper.find('#list-'+ id).fadeOut();
					}
				},
				error: function(errorThrown) {
					me.prop('disabled', true);
					console.log( errorThrown );
				},
			});
        }
    });

    $('body').on('submit', '#brgm-filter', function(e) {
    	e.preventDefault();
    	$('.woocommerce ul.products').prepend( spin );
    	$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: $(this).serialize(),
			success: function(data) { 
				$('.ajax-loader').remove();
                                
				if ( data.status ) {
					$('.woocommerce ul.products').html( data.result );
					$('#paging').val( data.current );
				} else {
                                    console.log( data );
					$('.woocommerce ul.products').html( 'No Result Found!' );
				}
				if ( data.current >= data.paged ) {
					$('.q-load-more').prop('disabled', true);
				} else {
					$('.q-load-more').prop('disabled', false);
				}
				console.log( data );
			},
			error: function(errorThrown) {
				$('.ajax-loader').remove();
				console.log( errorThrown );
			},
		});
    });

    $('body').on('click', '.q-load-more', function(e) {
    	e.preventDefault();
    	var me   = $(this),
    		data = $('#brgm-filter').serialize();
        var columns = '4';
        if($(this).data("columns") !== "undefined"){columns = $(this).data("columns"); }
    	$('.woocommerce ul.products').prepend( spin );
    	me.prop('disabled', true);
		me.addClass('loading');
    	$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: data + '&reqtype=paging&columns=' + columns,
			success: function(data) { 
				$('.ajax-loader').remove();
				me.prop('disabled', false);
				me.removeClass('loading');
				if ( data.status ) {
					$('.woocommerce ul.products').append( data.result );
					$('#paging').val( data.current );
					if ( data.current >= data.paged ) $('.q-load-more').prop('disabled', true);
				}
				//console.log( data )
			},
			error: function(errorThrown) {
				me.prop('disabled', false);
				me.removeClass('loading');
				$('.ajax-loader').remove();
				console.log( errorThrown );
			},
		});
    });
	
	$('body').on('click', '#view-all', function(e) {
    	e.preventDefault();
    	var me   = $(this),
    		data = $('#brgm-filter').serialize();
    	if ( me.hasClass('viewed') ) return;
    	$('.woocommerce ul.products').prepend( spin );
    	$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: data + '&viewall=yes',
			success: function(data) { 
				$('.ajax-loader').remove();
				me.addClass('viewed');
				$('.q-load-more').prop('disabled', true);
				if ( data.status ) {
					$('.woocommerce ul.products').append( data.result );
					$('#paging').val( data.current );
				}
			},
			error: function(errorThrown) {
				me.prop('disabled', false);
				$('.ajax-loader').remove();
				console.log( errorThrown );
			},
		});
    });

    $('.brgm-oc').on('change', '.brgm-select', function(e) {
    	if ( $(this).is('#brgm-count') ) $('#ppg').val( $(this).val() );
    	if ( $(this).is('#brgm-order') ) $('#ordby').val( $(this).val() );
    	//console.log( $('#brgm-filter').serialize() );
    });
    
	function _list_action( datas, elem ) {
		elem.addClass('loading');
		elem.prop('disabled', true);
		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: datas,
			success: function(data) { 
				elem.removeClass('loading');
				elem.prop('disabled', false);
				if ( data.action == 'removefrom' ) {
					elem.text( elem.data('text') );
					elem.data('action', 'addto');
				} else {
					elem.text( 'Added to '+ elem.data('text') + ' List');
					elem.data('action', 'removefrom');
				}
				console.log(data);
			},
			error: function(errorThrown) {
				elem.prop('disabled', false);
				elem.removeClass('loading');
				console.log( errorThrown );
			},
		});
	}
	if ( !$('body').hasClass('logged-in') ) {
		document.cookie='bg_redirect='+ window.location.href +'; expires=; path=/';
	}
})(jQuery);