(function( $ ) {
	'use strict';

	$(function() {

		$( '.woocommerce form .shop_table.cart' ).find( 'input[name="update_cart"]' ).removeProp( 'disabled');
 

		/*magnific popup*/
  		$('.open-magnific').magnificPopup({
		  type:'inline',
		  midClick: true, // Allow opening popup on middle mouse click. Always set it to true if you don't provide alternative source in href.
		  closeBtnInside:false
		});

		$('#coupon_link').click(function(e){
			e.preventDefault();
			$('#couponbox').slideDown();
		});

		$('.bgreward_redeem').click(function(e){
			console.log('redeem reward');
			var data = {
				'action': 'bgpr_claim_reward',
			};
			jQuery.post(ajaxurl, data, function(response) {
				var data = JSON.parse(response);
				
				location.href ='/cart';
			});

			e.preventDefault();
		});

		$('.woocommerce-remove-coupon').click(function(){
			location.href ='/cart';
		});

		
			/*$(document).on( "click",".woocommerce .cart-collaterals .cart_totals table.shop_table tr.shipping td .shipping_method", function() {
				console.log($(this).val());
				if ($(this).val()=='bgpr-free-shipping'){
					var el = $('.woocommerce .cart-collaterals .cart_totals #bgpr-club-popup');
					if (el.length) {
					    $.magnificPopup.open({
					        items: {
					            src: el
					        },
					        type: 'inline'
					    });
					}

					var data = {
						'action': 'bgpr_free_shipping_selected',
					};

				} else {
					var data = {
						'action': 'bgpr_free_shipping_deselected',
					};
					jQuery.post(ajaxurl, data, function(response) {
						/*var data = JSON.parse(response);*/
						/*location.href ='/cart';*/
						/*$(upd_cart_btn).trigger("click");*/
						location.href ='/cart';
					});
				}

				var upd_cart_btn = $(".woocommerce-cart input[name='update_cart']");
				$(upd_cart_btn).prop('disabled', false);
				
	           
			});*/


		$(document).on( "click",".mfp-wrap .mfp-container .mfp-content #bgpr-club-popup p #btn_popup_addtocart", function(e) {

		
			var upd_cart_btn = $(".woocommerce-cart input[name='update_cart']");
				$(upd_cart_btn).prop('disabled', false);
			var data = {
						'action': 'bgpr_free_shipping_selected',
					};
			jQuery.post(ajaxurl, data, function(response) {
					var data = JSON.parse(response);
					
					$.magnificPopup.close()
					/*$(upd_cart_btn).trigger("click");*/
					location.href ='/cart';
				});
			
			e.preventDefault();
		});

		$(document).on( "click",".mfp-wrap .mfp-container .mfp-content #bgpr-club-popup p #btn_popup_nothanks", function(e){
			var upd_cart_btn = $(".woocommerce-cart input[name='update_cart']");
				$(upd_cart_btn).prop('disabled', false);
			var data = {
						'action': 'bgpr_free_shipping_deselected',
					};
			jQuery.post(ajaxurl, data, function(response) {
					/*var data = JSON.parse(response);*/
					/*location.href ='/cart';*/
					$.magnificPopup.close()
					/*$(upd_cart_btn).trigger("click");*/
					location.href ='/cart';
				});
			$.magnificPopup.close()
			e.preventDefault();
		});

		

		$('#playersclubimg').click(function (e){
			
			$(".woocommerce .cart-collaterals .cart_totals table.shop_table tr.shipping td #shipping_method_0_bgpr-free-shipping").click();
			var el = $('.woocommerce .cart-collaterals .cart_totals #bgpr-club-popup');
			if (el.length) {
			    $.magnificPopup.open({
			        items: {
			            src: el
			        },
			        type: 'inline'
			    });
			}
			
			e.preventDefault();
		});
		
	});



})( jQuery );
