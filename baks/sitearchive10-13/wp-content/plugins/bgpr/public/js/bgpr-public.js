(function( $ ) {
	'use strict';

	$(function() {

		var ajaxurl = '/wp-admin/admin-ajax.php';

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

			var redirectTo = $(this).attr('data-redirect');

			jQuery.post(ajaxurl, data, function(response) {
				var data = JSON.parse(response);
				
				location.href ='/'+redirectTo;
			});

			e.preventDefault();
		});

		$('.woocommerce-remove-coupon').click(function(){
			console.log('redeem reward');
			var data = {
				'action': 'bgpr_claim_reward',
			};

			var redirectTo = $(this).attr('data-redirect');

			jQuery.post(ajaxurl, data, function(response) {
				var data = JSON.parse(response);
				
				location.href ='/cart';
			});

			e.preventDefault();
			/*location.href ='/cart';*/
		});

		
			


		$(document).on( "click",".mfp-wrap .mfp-container .mfp-content #bgpr-club-popup p #btn_popup_addtocart", function(e) {
			/*console.log('button clicked');*/
		
			var upd_cart_btn = $(".woocommerce-cart input[name='update_cart']");
				$(upd_cart_btn).prop('disabled', false);
			var data = {
						'action': 'bgpr_free_shipping_selected',
					};
			jQuery.post(ajaxurl, data, function(response) {
					/*var data = JSON.parse(response);
					console.log(response);*/
					
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

		$(document).on( "click",".woocommerce .cart-collaterals #playersclubimg", function(e){
			
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


		$(document).on( "click",".woocommerce form .shop_table.cart input[name='update_cart'], .woocommerce form .shop_table.cart a.remove", function(e){

			setTimeout( function(){ 
			    var datax = {'action': 'bgpr_update_left_bottom'};

				jQuery.post(ajaxurl, datax, function(myresponse) {	
					console.log('update bottom left cart');	
					
					$('.cart_playersclub').html("");
					$('.cart_playersclub').html(myresponse);

					
						$('.quantity input[type="number"]').before('<input type="button" value="-" class="minus" />');
    					$('.quantity input[type="number"]').after('<input type="button" value="+" class="plus"/>');
    				
				});
				var datax = {'action': 'bgpr_update_amountforfreeshipping'};

				jQuery.post(ajaxurl, datax, function(myresponsex) {	
					$('#addmorebgbox').html("");
					$('#addmorebgbox').html(myresponsex);

				});

			  }  ,2000);
			
		});

		$(document).on( "submit",".woocommerce form.woocommerce-shipping-calculator", function(e){
			setTimeout( function(){ 
			    var datax = {'action': 'bgpr_update_left_bottom'};

				jQuery.post(ajaxurl, datax, function(myresponse) {	
					console.log('update bottom left cart after form shipping');	
					
					$('.cart_playersclub').html("");
					$('.cart_playersclub').html(myresponse);

					
					$('.quantity input[type="number"]').before('<input type="button" value="-" class="minus" />');
					$('.quantity input[type="number"]').after('<input type="button" value="+" class="plus"/>');
    				
				});
			  }  ,2000);
		});

		$(document).ajaxStop(function () {

		 });

		if ($('body.woocommerce-cart a.showlogin').length){
			$('body.woocommerce-cart a.showlogin').click(function(e){
				e.preventDefault();
				$('form.login').slideToggle();

			});
		}

		
		
		
		
	});



})( jQuery );
