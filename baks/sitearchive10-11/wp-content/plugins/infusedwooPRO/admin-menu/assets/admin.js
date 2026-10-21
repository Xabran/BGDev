var iw_options = {};
iw_options.toprocess = 0;
iw_options.processed = 0;
iw_options.speed = 50;
iw_options.threads = 3;
var server_pause_flag = false;


jQuery(document).ready(function(){
	jQuery('.menu-list-head').prepend('<span class="iw-collapser">+</span> ');
	jQuery('.infusedwoo-admin-menu > ul > li > ul').hide();
	jQuery('.infusedwoo-admin-menu > ul > li:first > ul').hide();

	jQuery(".ui-toggle").click(function(e) {
	        e.preventDefault();
	        if(jQuery(this).hasClass('checked')) {
	            jQuery(this).removeClass('checked');
	        } else {
	            jQuery(this).addClass('checked');
	        }
	    }); 

	jQuery(".menu-list-head").click( function() {
		jQuery(this).parent().children('ul').slideToggle();
		var $collapser = jQuery(this).children('.iw-collapser');
		if($collapser.html() == "+") $collapser.html("&ndash;");
		else $collapser.html("+");
		return false;
	});


	$selected = jQuery("li a.active");

	if($selected.length > 0) {
		$selected.parent().parent().parent().children(".menu-list-head").click();
	} else {
		jQuery('.menu-list-head:first').click();
	}

	jQuery(".iw-submenu").click( function() {
		var $gd = jQuery(".guided-setup");
		if($gd.length == 0) {
			jQuery(".loader").show();
		}
	});

	// Custom selection box
	jQuery(".iw-selection").each(function() {
		jQuery(this).click(function() {
			if(jQuery(this).hasClass('selected')) jQuery(this).removeClass('selected');
			else {
				jQuery(this).parent().children(".iw-selection").removeClass('selected');
				jQuery(this).addClass('selected');
			}
		});
	});

	// Custom checkbox
	jQuery(".iw-checkbox").each(function() {
		jQuery(this).prepend('<span class="iw-check"></span>');
		jQuery(this).click(function() {
			if(jQuery(this).hasClass('checked')) jQuery(this).removeClass('checked');
			else jQuery(this).addClass('checked');
		});
	});

	// Step-by-step
	jQuery(".step-by-step").each( function()  {
		var blocks = jQuery(this).children(".steps-wrap").children(".step-block");
		var steps = blocks.length;

		var dots = '<div class="step-guide">';
		
		for(var i = 0; i < steps; i++) {
			if(i == 0) active = " active-step";
			else active = "";
			dots += '<div class="step-dot step-'+(i+1)+active+'"></div>';
		}

		dots += '</div>';

		jQuery(this).append(dots);

		jQuery(this).find("input").keypress(function(e) {
		    if(e.which == 13) {
		       jQuery(this).parent().parent().find(".next-button").click();
		    }
		});

		// Next button
		jQuery(".just-next").click(function() {
			next_step(jQuery(this).closest(".step-by-step"));
		});

		jQuery(".just-back").click(function() {
			prev_step(jQuery(this).closest(".step-by-step"));
			iw_options.goback = false;
		});
		
	});

	

	jQuery(".apicreds").click(function() {
		var $gd = jQuery(".guided-setup");
		var appname = jQuery("[name=iw-app-name]").val();
		var apikey = jQuery("[name=iw-api-key]").val();

		if(appname == "" || apikey == "") {
			show_fail(jQuery(".guided-setup"),"Make sure you entered your Infusionsoft App Name and API Key.");
			return false;
		}

		show_loading($gd, "Testing Connection to Infusionsoft");
		jQuery(".loader").show();

		jQuery.get(ajaxurl,{action: 'ia_admin_apicreds',app: appname, api: apikey}, function(data) {
			if(data.trim() == "ok") {
				show_success(jQuery(".guided-setup"), "Successfully connected to Infusionsoft");
				next_step(jQuery(".guided-setup"));
				jQuery(".loader").hide();
				return true;
			} else {
				show_fail(jQuery(".guided-setup"),data);
				jQuery(".loader").hide();
				return false;
			}
		});
		
	});

	jQuery(".iw-prod-1").click(function() {
		iw_options.step1 = jQuery(this).attr("val");
		next_step(jQuery(".product-import"));

		if(iw_options.step1 == 'import') {
			jQuery(".iw-specify-import").show();
			jQuery(".iw-specify-export").hide();
		} else {
			jQuery(".iw-specify-import").hide();
			jQuery(".iw-specify-export").show();
		}

		jQuery(".prod-step-2-further > div").hide();
	});

	jQuery(".iw-prod-2").click(function() {
		iw_options.step2 = jQuery(this).attr("val");
		if(iw_options.step2 == 'all') {
			next_step(jQuery(".product-import"));
			jQuery(".iw-import-2-next").hide();
			jQuery(".prod-step-2-further > div").hide();
		} else if(iw_options.step2 == 'cat') {
			jQuery(".prod-step-2-further > div").hide();
			jQuery(".iw-import-2-next").show();
			if(iw_options.step1 == 'import') {
				jQuery(".icats").show();
			} else {
				jQuery(".wcats").show();
			}
		} else if(iw_options.step2 == 'id'){
			jQuery(".prod-step-2-further > div").hide();
			jQuery(".prodid").show();
			jQuery(".iw-import-2-next").show();
		}
	});

	jQuery(".iw-import-2-next").click( function() {
		cats = [];

		if(iw_options.step1 == 'import') {
			jQuery(".icat.checked").each( function() {
				cats.push(jQuery(this).attr("value"));
			});
		} else {
			jQuery(".wcat.checked").each( function() {
				cats.push(jQuery(this).attr("value"));
			});
		}

		prodids_input = jQuery('[name="prod_ids"]').val();
		var prodids = iw_split_entry(prodids_input);

		if(iw_options.step2 == 'cat') {
			if(cats.length > 0) {
				iw_options.step2further = cats;
				next_step(jQuery(".product-import"));
			} else {
				show_fail(jQuery(".product-import"), "Please select categories.");
			}
		} else if(iw_options.step2 = 'id') {
			if(prodids.length > 0 && prodids[0] != '' && typeof prodids[0] != 'undefined') {
				iw_options.step2further = prodids_input;
				next_step(jQuery(".product-import"));
			} else {
				show_fail(jQuery(".product-import"), "Please enter product IDs and separate by comma");
			}
		}
	});

	// map fields
	jQuery(".map").change(function() {
		var this_name = jQuery(this).attr('name');
		if(jQuery(this).val() == 'meta') {
			jQuery(this).after('<div class="'+this_name+'-meta" style="margin-left: 25px;">Meta Field Name: &nbsp;<input type="text" name="'+this_name+'-meta" style="width: 178px;" class="meta-field" /></div>');
		} else {
			jQuery('.'+this_name+'-meta').remove();
		}
	});

	// process import
	jQuery(".iw-import-process").click(function() {
		var step3import = {};

		step3import.content = jQuery("[name=import-content]").val();
		step3import.shortdesc = jQuery("[name=import-shortdesc]").val();

		step3import.images = jQuery("[name=import-images]").hasClass("checked") ? "yes" : "no";
		step3import.virtual = jQuery("[name=import-virtual]").hasClass("checked") ? "yes" : "no";
		step3import.tax = jQuery("[name=import-tax]").hasClass("checked") ? "yes" : "no";

		iw_options.step3 = step3import;
		iw_options.process = 'product_migrate';
		iw_options.fetch = 250;
		next_step(jQuery(".product-import"));
		iw_bgprocess(iw_options);
		jQuery(".progress-status").html("Fetching Products from Infusionsoft...");
	});

	jQuery(".iw-export-process").click(function() {
		var step3export = {};

		var $mfields = jQuery(".meta-field");

		for(var i = 0; i < $mfields.length; i++) {
			if(jQuery($mfields[i]).val() == '') {
				show_fail(jQuery(".product-import"), "Please make sure meta field is not empty.");
				return false;
			}
		}
		iw_options.process = 'product_migrate';
		iw_options.fetch = 250;
		iw_options.step3 = jQuery("#iw-specify-export-vals").serializeArray();
		next_step(jQuery(".product-import"));
		iw_bgprocess(iw_options);
		jQuery(".progress-status").html("Fetching Products from Woocommerce...");
	});



	// ORDER import / export:
	jQuery(".iw-order-1").click(function() {
		iw_options.step1 = jQuery(this).attr("val");
		next_step(jQuery(".order-import"));

		if(iw_options.step1 == 'import') {
			jQuery(".iw-specify-import").show();
			jQuery(".iw-specify-export").hide();
		} else {
			jQuery(".iw-specify-import").hide();
			jQuery(".iw-specify-export").show();
		}

		jQuery(".order-step-2-further > div").hide();
	});

	jQuery(".iw-order-2").click(function() {
		iw_options.step2 = jQuery(this).attr("val");
		if(iw_options.step2 == 'all') {
			iw_get_order_count();
			next_step(jQuery(".order-import"));
			jQuery(".iw-order-2-next").hide();
			jQuery(".order-step-2-further > div").hide();
		} else if(iw_options.step2 == 'cat') {
			jQuery(".order-step-2-further > div").hide();
			jQuery(".iw-order-2-next").show();
			if(iw_options.step1 == 'import') {
				jQuery(".istats").show();
			} else {
				jQuery(".wstats").show();
			}
		} else if(iw_options.step2 == 'id'){
			jQuery(".order-step-2-further > div").hide();
			jQuery(".orderid").show();
			jQuery(".iw-order-2-next").show();
		}
	});

	jQuery(".iw-order-2-next").click( function() {
		cats = [];

		if(iw_options.step1 == 'import') {
			jQuery(".istat.checked").each( function() {
				cats.push(jQuery(this).attr("value"));
			});
		} else {
			jQuery(".wstat.checked").each( function() {
				cats.push(jQuery(this).attr("value"));
			});
		}

		orderids_input = jQuery('[name="order_ids"]').val();
		var orderids = iw_split_entry(orderids_input);

		if(iw_options.step2 == 'cat') {
			if(cats.length > 0) {
				iw_options.step2further = cats;
				iw_get_order_count();
				next_step(jQuery(".order-import"));
			} else {
				show_fail(jQuery(".order-import"), "Please specify order status.");
			}
		} else if(iw_options.step2 = 'id') {
			if(orderids.length > 0 && orderids[0] != '' && typeof orderids[0] != 'undefined') {
				iw_options.step2further = orderids_input;
				iw_get_order_count();
				next_step(jQuery(".order-import"));
			} else {
				show_fail(jQuery(".order-import"), "Please enter order IDs and separate by comma");
			}
		}
	});

	jQuery(".iw-order-process").click(function() {
		if(iw_options.step1 == 'export') iw_options.step3 = jQuery("[name=iw_order_step3e]").val();
		else iw_options.step3 = jQuery("[name=iw_order_step3]").val();
		next_step(jQuery(".order-import"));

		iw_options.process = 'order_migrate';
		iw_options.fetch = 100;
		iw_bgprocess(iw_options);
		next_step(jQuery(".product-import"));
	});


	// enable / disable other payment gateway integration:
	jQuery("[name=ia_saveOrders]").click(function() {
		var saveOrders = jQuery(this).hasClass('checked');

		jQuery(".loader").show();
		jQuery.getJSON(ajaxurl,{action: 'iw_save_orders', jsoncallback: 'x',format: 'json', enable: saveOrders}).done(function(data) {
				// assume success..
				jQuery(".loader").hide();
			});
	});

	jQuery("[name=ia_enabled_carttracking]").click(function() {
		var advTracking = jQuery(this).hasClass('checked');

		if(advTracking) jQuery(".carttracking").css("opacity", 1);
		else jQuery(".carttracking").css("opacity", 0.5);

		jQuery(".loader").show();
		jQuery.getJSON(ajaxurl,{action: 'iw_advanced_tracking', jsoncallback: 'x',format: 'json', enable: advTracking}).done(function(data) {
				// assume success..
				jQuery(".loader").hide();
			});
	});


	jQuery("[name=ia_enable_regtoifs]").click(function() {
		var regtoifs = jQuery(this).hasClass('checked');

		jQuery(".loader").show();
		jQuery.getJSON(ajaxurl,{action: 'iw_enable_regtoifs', jsoncallback: 'x',format: 'json', enable: regtoifs}).done(function(data) {
				// assume success..
				jQuery(".loader").hide();
			});
	});

	// CHECKOUT FIELDS
	if(jQuery(".iw_checkoutfields").length) {
		load_grp_fields();
	}
    jQuery( ".iw_checkoutfields" ).sortable({
    	update: function( event, ui ) {
    		reposition_cf_groups();
    	}
    });

    jQuery( ".iw_checkoutfields, .iw_cf_fields" ).disableSelection();



    jQuery( ".iw_cf_group_add .iw_cf_group_name").click( function() {
    	jQuery(".iw-grp-edit").show();
		jQuery(".iw-field-edit").hide();
    	load_grp_form();
    });

 
    jQuery("body").on("change",".iw-grp-display", function() {
    	refresh_grp_form();
    });

    jQuery(".iw-grp-edit").submit(function() {	
    	save_grp_form();
    	return false;
    });

     jQuery("body").on("click",".grp-delete", function() {	
    	var del = confirm("Are you sure you want to delete this group? All fields under this group will be deleted.");
    	var grpid = jQuery(this).attr('grpid');
    	if(del && grpid > 0) {
    		enable_loading_cover(jQuery(".iw_checkoutfields"));
    		jQuery.post(ajaxurl + "?action=iw_cf_del_group&jsoncallback=x", {grpid: grpid}, function(data) {
				jQuery(".loading-cover").remove();
				load_grp_fields();
			});	
    	}
    	return false;
    });

     jQuery("body").on("click",".grp-edit", function() {	
     	jQuery(".iw-grp-edit").show();
		jQuery(".iw-field-edit").hide();
    	var grpid = jQuery(this).attr('grpid');
    	load_grp_form(grpid);
    	return false;
    });

    jQuery("body").on("click",".grp-add", function() {
    	jQuery(".iw-grp-edit").hide();
		jQuery(".iw-field-edit").show();
		var grpid = jQuery(this).attr('grpid');
		jQuery("[name=iw-field-grpid]").val(grpid);
    	load_field_form();
    });  

    jQuery("body").on("click",".field-edit", function() {	
     	jQuery(".iw-grp-edit").hide();
		jQuery(".iw-field-edit").show();
    	var grpid = jQuery(this).parent().parent().parent().parent().attr('grpid');
    	jQuery("[name=iw-field-grpid]").val(grpid);
    	var fieldid = jQuery(this).attr('fieldid');
    	load_field_form(fieldid);
    	return false;
    });

    jQuery("body").on("change",".iw-field-display,.iw-field-type", function() {
    	refresh_field_form();
    });

    jQuery(".iw-field-edit").submit(function() {	
    	save_field_form();
    	return false;
    });

    jQuery("body").on("click",".field-delete", function() {	
    	var del = confirm("Are you sure you want to delete this field?");
    	var fieldid = jQuery(this).attr('fieldid');
    	if(del && fieldid > 0) {
    		enable_loading_cover(jQuery(".iw_checkoutfields"));
    		jQuery.post(ajaxurl + "?action=iw_cf_del_field&jsoncallback=x", {fieldid: fieldid}, function(data) {
				jQuery(".loading-cover").remove();
				load_grp_fields();
			});	
    	}
    	return false;
    });

    // TY PAGE CONTROL
    if(jQuery(".iw_ty_ov").length) {
		load_ty_ovs();
	}
    jQuery( ".iw_ty_ov" ).sortable({
    	update: function( event, ui ) {
    		reposition_ty_ovs();
    	}
    });

    jQuery( ".iw_checkoutfields, .iw_cf_fields, .iw_ty_ov" ).disableSelection();

     jQuery( ".iw_ty_ov_add .iw_ty_ov_name").click( function() {
    	load_ty_ov_form();
    });

      jQuery("body").on("change",".iw-ov-condition", function() {
    	refresh_ov_form();
    });

    jQuery(".iw-ov-edit").submit(function() {	
    	save_ov_form();
    	return false;
    });

     jQuery("body").on("click",".ov-edit", function() {	
    	var ovid = jQuery(this).attr('ovid');
    	load_ty_ov_form(ovid);
    	return false;
    });

    jQuery("body").on("click",".ov-delete", function() {	
    	var del = confirm("Are you sure you want to delete this override?");
    	var ovid = jQuery(this).attr('ovid');
    	if(del && ovid > 0) {
    		enable_loading_cover(jQuery(".iw_ty_ov"));
    		jQuery.post(ajaxurl + "?action=iw_ty_del_ov&jsoncallback=x", {ovid: ovid}, function(data) {
				jQuery(".loading-cover").remove();
				load_ty_ovs();
			});	
    	}
    	return false;
    });


    jQuery(".cond-add").click(function() {
    	ov_new_condition();

    });

    jQuery("body").on("click",".cond-del", function() {
    	var $el = jQuery(this).parent();
    	$el.prev().remove();
    	$el.remove();
    	refresh_ov_form();
    });

    // custom tokenizer
    jQuery("body").on("click", ".iw-token-instance > .fa-times-circle", function() {
    	jQuery(this).parent().remove();
    });

    jQuery("body").on("focus", ".productsearch", function() {
    	if(!jQuery(this).hasClass('multisearch')) {
    		enable_multi_autocomplete(jQuery(this), 'wooproduct', 'iw-ov-further-products');
    	}
    });

    // check for pending process
	if(jQuery(".product-import").length > 0) {
		if(window.localStorage!==undefined){

		} else {
		    alert('Your browser is outdated and cannot be used for doing import / export. Please use most recent browsers.');
		}


		var old_iw_import = localStorage.getItem('iw_processes_product_migrate');
		if(old_iw_import) {
			old_iw_import =  JSON.parse(old_iw_import);
		}

		if(old_iw_import && old_iw_import.status && old_iw_import.status != 'success' && !old_iw_import.fatalreported) {
			jQuery(".pending-prod-process").show();
			jQuery(".product-import").hide();

			var process_info = '';
			if(old_iw_import.step1 == 'import') process_info += 'Process: Product Import - Woocommerce &larr; Infusionsoft<br>';
			else process_info += 'Process: Product Export - Woocommerce &rarr; Infusionsoft<br>';
			
			process_info += 'Products to Process - ' + old_iw_import.total + '<br>';
			process_info += 'Total Processed: ' + old_iw_import.processed + '<br>';
			jQuery(".process-info").html(process_info);
		}

		jQuery(".new-process").click(function() {
			jQuery(".pending-prod-process").hide();
			jQuery(".product-import").show();

		});

		jQuery(".resume-pending-process").click(function() {
			jQuery(".pending-prod-process").hide();
			jQuery(".product-import").show();
			jQuery(".step-block").hide();
			jQuery(".product-process-block").show();

			var last_logs = localStorage.getItem('iw_processes_'+old_iw_import.process+'_logs');
			jQuery(".process-logs").html(last_logs);
			update_process_logs("Resuming recent pending process.");

			iw_options = old_iw_import;
			iw_options.paused = false;
			iw_bgprocess(old_iw_import);
			jQuery(".step-guide").hide();
		});
	}

	if(jQuery(".order-import").length > 0) {
		if(window.localStorage!==undefined){

		} else {
		    alert('Your browser is outdated and cannot be used for doing import / export. Please use most recent browsers.');
		}


		var old_iw_import = localStorage.getItem('iw_processes_order_migrate');
		if(old_iw_import) {
			old_iw_import =  JSON.parse(old_iw_import);
		}

		if(old_iw_import && old_iw_import.status && old_iw_import.status != 'success' && !old_iw_import.fatalreported) {
			jQuery(".pending-ord-process").show();
			jQuery(".order-import").hide();

			var process_info = '';
			if(old_iw_import.step1 == 'import') process_info += 'Process: Order Import - Woocommerce &larr; Infusionsoft<br>';
			else process_info += 'Process: Order Export - Woocommerce &rarr; Infusionsoft<br>';
			
			process_info += 'Orders to Process - ' + old_iw_import.total + '<br>';
			process_info += 'Total Processed: ' + old_iw_import.processed + '<br>';
			jQuery(".process-info").html(process_info);
		}

		jQuery(".new-process").click(function() {
			jQuery(".pending-ord-process").hide();
			jQuery(".order-import").show();

		});

		jQuery(".resume-pending-process").click(function() {
			jQuery(".pending-ord-process").hide();
			jQuery(".order-import").show();
			jQuery(".step-block").hide();
			jQuery(".order-process-block").show();

			var last_logs = localStorage.getItem('iw_processes_'+old_iw_import.process+'_logs');
			jQuery(".process-logs").html(last_logs);
			update_process_logs("Resuming recent pending process.");

			iw_options = old_iw_import;
			iw_options.paused = false;
			iw_bgprocess(old_iw_import);
			jQuery(".step-guide").hide();
		});

	}


	jQuery(".show-logs").click(function() {
		jQuery(this).hide();
		jQuery(".process-logs").show();
		return false;
	});

});


// TY Control
function ov_new_condition() {
	var conds = jQuery(".condition-wrap");
	var cond_wrap = jQuery(conds[0]).html();

	var html = '<div class="cond-connector">';
	html 	+= '<div class="cond-connect"></div>';
	html 	+= '<div class="cond-and">AND</div>';
	html 	+= '<div class="cond-connect"></div></div>';
	html 	+= '<div class="condition-wrap"><div class="cond-del"><i class="fa fa-times"></i></div>'+cond_wrap+'</div></div>';
	jQuery(conds[conds.length-1]).after(html);

	refresh_ov_form();
	cond = jQuery(".condition-wrap:last").children('.iw-ov-further').val('');
	jQuery(".condition-wrap:last").find('.iw-tokens').html('');
}

function load_ty_ovs() {
	goback = iw_options.goback;
	enable_loading_cover(jQuery(".iw_ty_ov"));
	jQuery.post(ajaxurl + "?action=iw_ty_load_ovs&jsoncallback=x", {}, function(data) {
		jQuery(".iw_ty_ov").html(data);
		if(goback) {
			prev_step(jQuery(".iw_ty_control"));
		}
		iw_options.goback = false;
		jQuery(".loading-cover").remove();
	}).fail( function(){
		if(goback) {
			show_fail(jQuery(".iw-ov-edit"),"Unexpected error...");
		}

		jQuery(".iw_ty_control").html('Connection error... Please refresh page.')
	});
}

function reposition_ty_ovs() {
	var $ovs = jQuery(".iw_ty_ov > li");
	var position = [];

	for(var i = 0; i < $ovs.length; i++) {
		position.push(jQuery($ovs[i]).attr("ovid"));
	}

	enable_loading_cover(jQuery(".iw_ty_ov"));
	jQuery.post(ajaxurl + "?action=iw_ty_reposition_ovs&jsoncallback=x", {position: position}, function(data) {
		jQuery(".loading-cover").remove();
	}).fail( function(){
		jQuery(".iw_ty_ov").html('Connection error... Please refresh page.')
	});
}

function load_ty_ov_form(ovid) {
	next_step(jQuery(".iw_ty_control"));
	iw_options.goback = true;

	jQuery(".iw-ov-title").html("Add New Override");
	jQuery("[name=iw-ov-id]").val('');
	jQuery("[name=iw-ov-name]").val('');
	jQuery("[name=iw-ov-condition]").val('always');
	jQuery("[name=iw-ov-url]").val('');
	jQuery("[name=iw-ov-pass]").addClass('checked');
	jQuery('.chzn-select').val('').trigger('liszt:updated');
	jQuery("[name=iw-ov-further-value]").val('');
	jQuery("[name=iw-ov-further-item]").val('');
	jQuery("[name=iw-ov-further-coupon]").val('');
	jQuery("[name=iw-ov-further-pg]").val('');
	jQuery(".iw-alerts").remove();

	jQuery(".condition-wrap:not(:first)").remove();
	jQuery(".cond-and").parent().remove();
	jQuery(".iw-ov-products").html('');

	if(ovid > 0) {
		jQuery(".iw-grp-title").html("Edit Override");
		enable_loading_cover(jQuery(".iw-ov-edit"));
		jQuery("[name=iw-ov-id]").val(ovid);

		jQuery.getJSON(ajaxurl,{action: 'iw_ty_load_ov', jsoncallback: 'x',format: 'json', ovid: ovid}).done(function(data) {
			iw_options.temp = data;

			jQuery("[name=iw-ov-id]").val(data.id);
			jQuery("[name=iw-ov-name]").val(data.name);
			
			jQuery("[name=iw-ov-url]").val(data.url);

			if(data.passvars == "false") {
				jQuery("[name=iw-ov-pass]").removeClass('checked');
			} else {
				jQuery("[name=iw-ov-pass]").addClass('checked');
			}

			if(data.cond) {
			 	jQuery("[name=iw-ov-condition]").val(data.cond);

				switch(data.cond) {
					case 'product':
						var lihtml = '';
						for(var i = 0; i < data.further.length; i++) {
							lihtml += '<div class="iw-ov-products iw-token-instance">Woocommerce Product # ' + data.further[i];
					          lihtml += '<input type="hidden" name="iw-ov-further-products[]" value="'+data.further[i]+'" />';
					          lihtml += '<input type="hidden" name="iw-ov-further-products_label[]" value="Woocommerce Product # '+data.further[i]+'" />';
					          lihtml += ' <i class="fa fa-times-circle del_li"></i>';
					          lihtml += '</div>';

							
						}
						jQuery(".iw-ov-products").html(lihtml);
						break;
					case 'categ':
						jQuery("[name=iw-ov-further-categ]").val(data.further);
						break;
					case 'morevalue':
					case 'lessvalue':
						jQuery("[name=iw-ov-further-value]").val(data.further);
						break;
					case 'moreitem':
					case 'lessitem':
						jQuery("[name=iw-ov-further-item]").val(data.further);
						break;
					case 'coupon':
						jQuery("[name=iw-ov-further-coupon]").val(data.further);
						break;
					case 'pg':
						jQuery("[name=iw-ov-further-pg]").val(data.further);
						break;

				}

			} else {
				var conds = data.conds;

				for(var i = 0; i < conds.length; i++) {
					if(i != 0) {
						ov_new_condition();
					}

					var cond = conds[i];

					jQuery(".condition-wrap:last").find(".iw-ov-condition").val(cond.type);
					switch(cond.type) {
						case 'product':
							var lihtml = '';

							if(cond.further) {
								for(var j = 0; j < cond.further.length; j++) {
									lihtml += '<div class="iw-ov-products iw-token-instance">' + cond['prodlabels'][j];
							          lihtml += '<input type="hidden" name="iw-ov-further-products[]" value="'+cond['further'][j]+'" />';
							          lihtml += '<input type="hidden" name="iw-ov-further-products_label[]" value="'+cond['prodlabels'][j]+'" />';
							          lihtml += ' <i class="fa fa-times-circle del_li"></i>';
							          lihtml += '</div>';

									
								}
								jQuery(".condition-wrap:last").find(".iw-ov-products").html(lihtml);
							}
							break;
						case 'categ':
							if(cond.further) jQuery(".condition-wrap:last").find("[name=iw-ov-further-categ]").val(conds[i].further);
							break;
						case 'morevalue':
						case 'lessvalue':
							if(cond.further) jQuery(".condition-wrap:last").find("[name=iw-ov-further-value]").val(conds[i].further);
							break;
						case 'moreitem':
						case 'lessitem':
							if(cond.further) jQuery(".condition-wrap:last").find("[name=iw-ov-further-item]").val(conds[i].further);
							break;
						case 'coupon':
							if(cond.further) jQuery(".condition-wrap:last").find("[name=iw-ov-further-coupon]").val(conds[i].further);
							break;
						case 'pg':
							if(cond.further) jQuery(".condition-wrap:last").find("[name=iw-ov-further-pg]").val(conds[i].further);
							break;

					}					
				}
			}

			jQuery(".loading-cover").remove();
			refresh_ov_form();

		}).fail( function(){
			jQuery(".iw-ov-edit").html('Connection error... Please refresh page.')
		});
	} else {
		refresh_ov_form();	
	}
}


function refresh_ov_form() {
	jQuery(".iw-ov-further").hide();
	no_more_conds = false;
	var cond_vals = [];

	var conditions = jQuery(".iw-ov-condition");

	
	if(jQuery(conditions[0]).val() == 'always') {
		jQuery(".condition-wrap").hide();
		jQuery(".cond-connector").hide();
		jQuery(conditions[0]).parent().show();
	} else {
		jQuery(".condition-wrap").show();
		jQuery(".cond-connector").show();

		if(conditions.length > 7) {
			jQuery(".cond-add").parent().hide();
		} else {
			jQuery(".cond-add").parent().show();
		}
	}

	for(var i = 0; i < conditions.length; i++) {
		var $parent = jQuery(conditions[i]).parent();

		if(i > 0) {
			$parent.children(".iw-ov-condition").children("option[value=always]").remove();
			var inputs = $parent.children("input,select");
			for(var j = 0; j < inputs.length; j++) {
				var fld_name = jQuery(inputs[j]).attr('name');
				jQuery(inputs[j]).attr('name', fld_name + i);
			}

			for(var j = 0; j < cond_vals.length; j++) {
				$parent.children(".iw-ov-condition").children("option[value="+cond_vals[j]+"]").remove();
			}
		}

		var display_option = jQuery(conditions[i]).val();
		cond_vals.push(display_option);

		switch(display_option) {
			case 'product':
				$parent.children(".iw-ov-further-products").show();
				break;
			case 'categ':
				$parent.children(".iw-ov-further-categ").show();
				break;
			case 'morevalue':
			case 'lessvalue':
				$parent.children(".iw-ov-further-value").show();
				break;
			case 'moreitem':
			case 'lessitem':
				$parent.children(".iw-ov-further-item").show();
				break;
			case 'coupon':
				$parent.children(".iw-ov-further-coupon").show();
				break;
			case 'pg':
				$parent.children(".iw-ov-further-pg").show();
				break;

		}
	}


	jQuery(".chzn-select").chosen({width: "80%"});
}


function save_ov_form() {

	// validation:
	if(jQuery('[name=iw-ov-name]').val() == '') {
		show_fail(jQuery(".iw-ov-edit"),"Please enter an override name.")
		return false;
	}

	if(jQuery('[name=iw-ov-url]').val() == '') {
		show_fail(jQuery(".iw-ov-edit"),"Please enter the thank you page URL.")
		return false;
	}

	show_loading(jQuery(".iw-ov-edit"), "Saving...");

	// prepare values:
	var ov_vals = {};
	ov_vals.id = jQuery("[name=iw-ov-id]").val();
	ov_vals.name = jQuery("[name=iw-ov-name]").val();
	ov_vals.url = jQuery.trim(jQuery("[name=iw-ov-url]").val());
	ov_vals.passvars = jQuery("[name=iw-ov-pass]").hasClass('checked');
	
	var conditions = jQuery(".condition-wrap");

	var conds = [];
	for(var i = 0; i < conditions.length; i++) {
		var cond = {};
		cond.type = jQuery(conditions[i]).find(".iw-ov-condition").val();

		switch(cond.type) {
			case 'product':
				var val = [];
				jQuery(conditions[i]).find("[name='iw-ov-further-products[]']").each(function(j){
		          val[j] = jQuery(this).val();
		        });
				cond.further = val;

				var labels = [];
				jQuery(conditions[i]).find("[name='iw-ov-further-products_label[]']").each(function(j){
		          labels[j] = jQuery(this).val();
		        });

		        cond.prodlabels = labels;

				break;
			case 'categ':
				cond.further = jQuery(conditions[i]).find("[name=iw-ov-further-categ]").val();
				break;
			case 'morevalue':
			case 'lessvalue':
				cond.further = jQuery(conditions[i]).find("[name=iw-ov-further-value]").val();
				break;
			case 'moreitem':
			case 'lessitem':
				cond.further = jQuery(conditions[i]).find("[name=iw-ov-further-item]").val();
				break;
			case 'coupon':
				cond.further = jQuery(conditions[i]).find("[name=iw-ov-further-coupon]").val();
				break;
			case 'pg':
				cond.further = jQuery(conditions[i]).find("[name=iw-ov-further-pg]").val();
				break;

		}

		conds.push(cond);	
	}

	ov_vals.conds = conds;

	jQuery.post(ajaxurl + "?action=iw_ty_save_ov&jsoncallback=x",ov_vals, function(data) {
		if(data.trim() == "ok") {
			load_ty_ovs();
		} else {
			show_fail(jQuery(".iw-ov-edit"),"Error saving: " + data)
		}
	}).fail( function(){
		show_fail(jQuery(".iw-ov-edit"),"Unexpected error...")
	});

	iw_options.ov_vals = ov_vals;
	return false;
}






function next_step($jq_select) {
	var window_width = $jq_select.width();
	var $steps_wrap = $jq_select.children(".steps-wrap");
	var current_pos = -parseInt($steps_wrap.css("left").replace("px",""));
	var current_step = (current_pos / window_width) + 1;
	var next_step = current_step + 1;
	var next_pos = (next_step-1) * window_width;

	$steps_wrap.animate({left: -next_pos}, 1000);
	$jq_select.find('.step-'+current_step).removeClass("active-step");
	$jq_select.find('.step-'+next_step).addClass("active-step");

	// If guided setup, make all a tags target=_blank
	var $gd = jQuery(".guided-setup");
	if($gd.length > 0 && next_step > 1) jQuery("a").attr("target", "_blank");
	
	jQuery(".alert-red").hide();
	
	
}

function prev_step($jq_select) {
	var window_width = $jq_select.width();
	var $steps_wrap = $jq_select.children(".steps-wrap");
	var current_pos = -parseInt($steps_wrap.css("left").replace("px",""));
	var current_step = (current_pos / window_width) + 1;
	var prev_step = current_step - 1;
	var prev_pos = (prev_step-1) * window_width;

	$steps_wrap.animate({left: -prev_pos}, 1000);
	$jq_select.find('.step-'+current_step).removeClass("active-step");
	$jq_select.find('.step-'+prev_step).addClass("active-step");

	
	jQuery(".alert-red").hide();
}

function show_loading($jq_select,msg) {
	$jq_select.find(".iw-alerts").remove();
	$jq_select.find(".next-button").after('<div class="iw-alerts alert-blue">'+msg+'</div>');
}


function show_success($jq_select,msg) {
	$jq_select.find(".iw-alerts").remove();
	$jq_select.find(".next-button").after('<div class="iw-alerts alert-green">'+msg+'</div>');
	setTimeout(function() {
		jQuery(".alert-green").fadeOut();
	},3000);
}


function show_fail($jq_select,msg) {
	$jq_select.find(".iw-alerts").remove();
	$jq_select.find(".next-button").after('<div class="iw-alerts alert-red">'+msg+'</div>');
}

function iw_bgprocess(options) {
	// Create new import session. 
	iw_options = options;
	

	if(!options.status) {
		iw_options.status = 'preparing';
		localStorage.setItem("iw_processes_" + iw_options.process, JSON.stringify(options));
		iw_options = options;
	}

	if(iw_options.fatalreported) return;

	if(iw_options.status == 'preparing') {
		jQuery(".import-progress > .actual-progress").animate({width: "0%"}, 10);
		localStorage.setItem('iw_processes_'+iw_options.process+'_logs','');
		localStorage.setItem('iw_processes_'+iw_options.process+'_recorded_logs', '')
		jQuery(".progress-status").html("Initializing Import Process...");
		update_process_logs("Initializing Import Process...");

		jQuery.post(ajaxurl + '?action=iw_register_bgprocess', iw_options, function(data) {
			if(data.success) {
				iw_options.status = 'registered';
				iw_options.total = data.total;
				iw_options.refid = data.refid;
				localStorage.setItem("iw_processes_" + iw_options.process, JSON.stringify(iw_options));
				if(!iw_options.paused) {
					iw_bgprocess(iw_options);
				}
			} else {
				if(!iw_options.paused) {
					jQuery(".repeat-proc").show();
					jQuery(".pause-proc,.play-proc").hide();
					jQuery(".progress-status").html('<span style="color:red">Process failed. Click on repeat button if you want to try again. ERROR INFO: ' + data.errormsg + '</span>');
					jQuery(".import-progress > .actual-progress").stop();
					jQuery(".import-progress > .actual-progress").css("background-color","red");
				}
			}
		},'json').fail(function(xhr, textStatus, errorThrown){
			if(!iw_options.paused) {
				jQuery(".repeat-proc").show();
				jQuery(".pause-proc,.play-proc").hide();
				var errmsg = "Status: " + textStatus + " Response Text: " + xhr.responseText + " Error Thrown: " + errorThrown;

				jQuery(".progress-status").html('<span style="color:red">Process failed. Click on repeat button if you want to try again. ' + errmsg);
				update_process_logs("Process Failed. Error Info: " + errmsg);

				jQuery(".import-progress > .actual-progress").stop();
				jQuery(".import-progress > .actual-progress").css("background-color","red");
			}
		});
	} else if(iw_options.status == 'registered' || iw_options.status == 'pending' || iw_options.status == 'paused') {
		if(iw_options.status == 'registered') {
			jQuery(".import-progress > .actual-progress").animate({width: "10%"}, 500);
			jQuery(".progress-status").html("Processing...");
			jQuery(".import-progress > .actual-progress").css("background-color","#293D67");
			jQuery.post(ajaxurl + '?action=iw_start_bgprocess', iw_options, function(data) {
				if(data.toLowerCase().indexOf('fatal error') !== -1) {
					jQuery(".pause-proc,.play-proc").hide();
					var errmsg = "PHP Fatal Error Reported: " + data;

					jQuery(".progress-status").html('<span style="color:red">Process failed. ' + errmsg);
					update_process_logs("Process Failed. Error Info: " + errmsg);
					jQuery(".import-progress > .actual-progress").stop();
					jQuery(".import-progress > .actual-progress").css("background-color","red");
					iw_options.fatalreported = true;
					iw_options.status = 'failed';
				}
			}).fail(function(xhr, textStatus, errorThrown){
				jQuery(".pause-proc,.play-proc").hide();
				var errmsg = "Status: " + textStatus + " Response Text: " + xhr.responseText + " Error Thrown: " + errorThrown;

				jQuery(".progress-status").html('<span style="color:red">Process failed. ' + errmsg);
				update_process_logs("Process Failed. Error Info: " + errmsg);
				jQuery(".import-progress > .actual-progress").stop();
				jQuery(".import-progress > .actual-progress").css("background-color","red");
				iw_options.fatalreported = true;
				iw_options.status = 'failed';
			});
			update_process_logs("Successfully Registered Process. Process ID: " + iw_options.refid);
		} else {
			jQuery(".progress-status").html("Processed "+ iw_options.processed +" of " + iw_options.total + " items");
			update_process_logs("Processed "+ iw_options.processed +" of " + iw_options.total + " items");
			var percent = parseInt(90 * (iw_options.processed / iw_options.total) + 10);
			jQuery(".import-progress > .actual-progress").stop();
			jQuery(".import-progress > .actual-progress").animate({width: percent + "%"}, 500);
			jQuery(".import-progress > .actual-progress").css("background-color","#293D67");

			if(iw_options.status == 'paused' && !server_pause_flag) {
				update_process_logs("Server process worker finished, running next worker.");
				jQuery.post(ajaxurl + '?action=iw_start_bgprocess', iw_options, function(data) {
					if(data.toLowerCase().indexOf('fatal error') !== -1) {
						jQuery(".pause-proc,.play-proc").hide();
						var errmsg = "PHP Fatal Error Reported: " + data;

						jQuery(".progress-status").html('<span style="color:red">Process failed. ' + errmsg);
						update_process_logs("Process Failed. Error Info: " + errmsg);
						jQuery(".import-progress > .actual-progress").stop();
						jQuery(".import-progress > .actual-progress").css("background-color","red");

						iw_options.fatalreported = true;
						iw_options.status = 'failed';
					}
				}).fail(function(xhr, textStatus, errorThrown){
					jQuery(".pause-proc,.play-proc").hide();
					var errmsg = "Status: " + textStatus + " Response Text: " + xhr.responseText + " Error Thrown: " + errorThrown;

					jQuery(".progress-status").html('<span style="color:red">Process failed. ' + errmsg);
					update_process_logs("Process Failed. Error Info: " + errmsg);
					jQuery(".import-progress > .actual-progress").stop();
					jQuery(".import-progress > .actual-progress").css("background-color","red");

					iw_options.fatalreported = true;
					iw_options.status = 'failed';
				});

				server_pause_flag = true;
				last_proc = iw_options.processed;
			}

			if(iw_options.status == 'pending' || iw_options.processed > last_proc) {
				server_pause_flag = false;
			}
		}
		update_process_logs("Fetching latest status.");
		jQuery.post(ajaxurl + '?action=iw_get_bgprocess_status', iw_options, function(data) {
			if(data.success) {
				iw_options.status = data.status;
				iw_options.processed = data.processed;
				iw_options.errormsg = data.errormsg;

				update_process_logs(data.logs);
				localStorage.setItem("iw_processes_" + iw_options.process, JSON.stringify(iw_options));

				setTimeout(function() {
					if(!iw_options.paused) {
						iw_bgprocess(iw_options);
					}
				}, 1500);
			} else {
				if(!iw_options.paused) {
					jQuery(".repeat-proc").show();
					jQuery(".pause-proc,.play-proc").hide();

					jQuery(".progress-status").html('<span style="color:red">Process failed. Click on repeat button if you want to try again. ERROR INFO: ' + data.errormsg + '</span>');
					update_process_logs("Process Failed. " + data.errormsg);
					jQuery(".import-progress > .actual-progress").stop();
					jQuery(".import-progress > .actual-progress").css("background-color","red");
				}
			}
		},'json').fail(function(xhr, textStatus, errorThrown){
			jQuery(".repeat-proc").show();
			jQuery(".pause-proc,.play-proc").hide();
			var errmsg = "Status: " + textStatus + " Response Text: " + xhr.responseText + " Error Thrown: " + errorThrown;

			jQuery(".progress-status").html('<span style="color:red">Process failed. Click on repeat button if you want to try again. ' + errmsg);
			update_process_logs("Process Failed. Error Info: " + errmsg);
			jQuery(".import-progress > .actual-progress").stop();
			jQuery(".import-progress > .actual-progress").css("background-color","red");
		});
	} else if(iw_options.status == 'success') {
		jQuery(".play-proc,.pause-proc").hide();

		jQuery(".import-progress > .actual-progress").stop();
		jQuery(".import-progress > .actual-progress").animate({width: "100%"}, 1000);

		if(iw_options.process == 'product_migrate') {
			if(iw_options.step1 == 'import') {
				jQuery(".progress-status").html('<span style="color:green">Import Successful! You can view the imported products <a href="edit.php?post_type=product">here.</a></span>');
			} else {
				jQuery(".progress-status").html('<span style="color:green">Export Successful! You should be able to view the products in Infusionsoft.</span>');
			}
		} else {
			if(iw_options.step1 == 'import') {
				jQuery(".progress-status").html('<span style="color:green">Import Successful! You can view the imported orders <a href="edit.php?post_type=shop_order">here.</a></span>');
			} else {
				jQuery(".progress-status").html('<span style="color:green">Export Successful! You should be able to view the orders in Infusionsoft.</span>');
			}
		}
		update_process_logs("Process Successful");
		jQuery(".import-progress > .actual-progress").css("background-color","green");
	} else {
		jQuery(".repeat-proc").show();
		jQuery(".pause-proc,.play-proc").hide();

		jQuery(".progress-status").html('<span style="color:red">Process failed. Click on repeat button if you want to try again. ERROR INFO: ' + iw_options.errormsg + '</span>');
		jQuery(".import-progress > .actual-progress").stop();
		jQuery(".import-progress > .actual-progress").css("background-color","red");
		update_process_logs("Process Failed. " + data.errormsg);
	}
}

function iw_fetch_products_for_orders() {
	update_process_logs("Initializing.." + data.errormsg);

	jQuery(".import-progress > .actual-progress").animate({width: "20%"}, 15000);
	jQuery.getJSON(ajaxurl,{action: 'iw_fetch_products', jsoncallback: 'x',format: 'json', options: iw_options}).done(function(data) {
		if(typeof data.products == 'undefined' || data.products.length < 1) {
			jQuery(".progress-status").html('<span style="color:red">Process failed. Please check if Infusionsoft connection is still active, otherwise contact support.</span>');
			jQuery(".import-progress > .actual-progress").stop();
			jQuery(".import-progress > .actual-progress").css("background-color","red");
		} else {
			iw_options.fetched = data;
			jQuery(".import-progress > .actual-progress").stop();
			jQuery(".import-progress > .actual-progress").animate({width: "20%"}, 1000);
			
			iw_process_orders();
		}

	}).fail(function(){
		jQuery(".progress-status").html('<span style="color:red">Process failed. Please check if Infusionsoft connection is still active, otherwise contact support.</span>');
		jQuery(".import-progress > .actual-progress").stop();
		jQuery(".import-progress > .actual-progress").css("background-color","red");
	});
}

function iw_import_products() {
	var allprods = iw_options.fetched.products;	
	var count = 0;
	var loopcount = 0;

	var num_groups = Math.ceil(allprods.length / iw_options.speed);
	if(num_groups < iw_options.threads) num_groups = iw_options.threads;

	var groups = arraySplit(allprods,num_groups);

	while(iw_options.toprocess < groups.length && loopcount < iw_options.threads) {
		jQuery.post(ajaxurl + "?action=iw_process_products&jsoncallback=x",{
			options: iw_options.step3, 
			method: iw_options.step1,
			product: groups[iw_options.toprocess],
			prodcats: iw_options.fetched.prodcats,
			pincats: iw_options.fetched.pincats
		}, function(data) {
			if(data.trim() == "ok") {
				iw_options.processed++;
				count++;

				if(count >= iw_options.threads) {
					iw_import_products();
				}

				var num_groups = Math.ceil(allprods.length / iw_options.speed);
				if(num_groups < iw_options.threads) num_groups = iw_options.threads;
				var mult = Math.floor(allprods.length / num_groups);

				jQuery(".progress-status").html("Processed "+ iw_options.processed*mult +" of " + allprods.length + " products");
				var percent = parseInt(70 * (iw_options.processed*mult / allprods.length) + 30);
				jQuery(".import-progress > .actual-progress").stop();
				jQuery(".import-progress > .actual-progress").animate({width: percent + "%"}, 500);

				if(iw_options.processed == groups.length) {
					jQuery(".import-progress > .actual-progress").stop();
					jQuery(".import-progress > .actual-progress").animate({width: "100%"}, 1000);
					if(iw_options.step1 == 'import') {
						jQuery(".progress-status").html('<span style="color:green">Import Successful! You can view the imported products <a href="edit.php?post_type=product">here.</a></span>');
					} else {
						jQuery(".progress-status").html('<span style="color:green">Export Successful! You should be able to view the products in Infusionsoft.</span>');
					}
					jQuery(".import-progress > .actual-progress").css("background-color","green");
				}
			} else {
				jQuery(".progress-status").html('<span style="color:red">Process failed. ERROR: '+data+'</span>');
				jQuery(".import-progress > .actual-progress").stop();
				jQuery(".import-progress > .actual-progress").css("background-color","red");
			}
		}).fail( function(){
			jQuery(".progress-status").html('<span style="color:red">Process failed. Please check if Infusionsoft connection is still active, otherwise contact support.</span>');
			jQuery(".import-progress > .actual-progress").stop();
			jQuery(".import-progress > .actual-progress").css("background-color","red");
		});

		iw_options.toprocess++;
		loopcount++;
	}
	
}

function iw_get_order_count() {
	jQuery(".iw-order-process").hide();
	var data1 = "";
	if(iw_options.step2 == "all") {
		data1 = "All Orders";
	} else if(iw_options.step2 == "cat") {
		data1 = "Orders with some specific order statuses.";
	} else if(iw_options.step2 == "id") {
		data1 = "Orders with some order IDs";
	}

	jQuery(".iw-order-data1").html(data1);
	jQuery(".iw-order-data2").html("Calculating...");
	jQuery(".iw-order-data3").html("Calculating...");

	// get data2 and data3
	jQuery.post(ajaxurl + "?action=iw_count_orders&jsoncallback=x",{options: iw_options}, function(data) {
		if(typeof data == 'undefined') {
			jQuery(".iw-order-data2").html("Unknown");
			jQuery(".iw-order-data3").html("Unknown");
			jQuery(".iw-order-process").hide();
			show_fail(jQuery(".order-import"), "Cannot proceed.. Unexpected Error.");
		} else if(data == 0) {
			jQuery(".iw-order-data2").html("0");
			jQuery(".iw-order-data3").html("0");
			show_fail(jQuery(".order-import"), "Cannot proceed.. There are no orders to process.");
			jQuery(".iw-order-process").hide();
		} else {
			jQuery(".iw-order-data2").html(data);
			iw_options.order_count = parseInt(data);
			// simulate empty array
			var simul = new Array();
			for(var i = 0; i < data; i++) simul.push("");

			var num_groups = Math.ceil(simul.length / iw_options.speed);
			if(num_groups < iw_options.threads) num_groups = iw_options.threads;
			var groups = arraySplit(simul,num_groups);
			var data3 = 0;
			var duration = (groups.length*5 / iw_options.threads) + groups.length * 3 + groups[0].length*120/1000;
			duration = duration*2.2;

			if(duration > 60) {
				data3 = Math.round(duration / 60) + " minutes";
			} else {
				data3 = Math.round(duration) + " seconds";
			}

			iw_options.pergroup = parseInt(groups[0].length);
			iw_options.groups = parseInt(groups.length);

			jQuery(".iw-order-data3").html("~"+data3);
			jQuery(".iw-order-process").show();
		}

	}).fail(function(){
		jQuery(".iw-order-data2").html("Unknown");
		jQuery(".iw-order-data3").html("Unknown");
		jQuery(".iw-order-process").hide();
		show_fail(jQuery(".order-import"), "Cannot proceed.. Unexpected Error.");
	});
}

function inArray(needle, haystack) {
    var length = haystack.length;
    for(var i = 0; i < length; i++) {
        if(haystack[i].trim() == needle.trim()) return true;
    }
    return false;
}

function arraySplit(a, n) {
    var len = a.length,out = [], i = 0;
    while (i < len) {
        var size = Math.ceil((len - i) / n--);
        out.push(a.slice(i, i += size));
    }
    return out;
}

function iw_split_entry(vals) {
	var split_entry = vals.split(",");
	var split_result = new Array();

	for(var i = 0; i < split_entry.length; i++) {
		if(split_entry[i].indexOf("-") > -1) {
			var ranged_input = split_entry[i].split("-");
			if(parseInt(ranged_input[1]) < parseInt(ranged_input[0])) return false;

			for(var j = parseInt(ranged_input[0]); j <= parseInt(ranged_input[1]); j++) {
				split_result.push(j);
			}
		} else if(parseInt(split_entry[i]) > 0) {
			split_result.push(parseInt(split_entry[i]));
		} else {
			return false;
		}
	}

	return split_result;

}

function iw_process_orders() {
	var count = 0;
	var loopcount = 0;
	jQuery(".progress-status").html("Processed 0 of "+iw_options.order_count+" orders");

	var mult = iw_options.pergroup;
	var smt_percent = parseInt(100 * ((iw_options.processed+1)*mult / iw_options.order_count));
	if(smt_percent < 100) jQuery(".import-progress > .actual-progress").animate({width: smt_percent + "%"}, 15000);

	while(iw_options.toprocess < iw_options.groups && loopcount < iw_options.threads) {
		jQuery.post(ajaxurl + "?action=iw_process_orders&jsoncallback=x",{
			options: iw_options		
		}, function(data) {
			if(data.trim() == "ok") {
				iw_options.processed++;
				count++;

				if(count >= iw_options.threads) {
					iw_process_orders();
				}

				var mult = iw_options.pergroup;
				jQuery(".progress-status").html("Processed "+ iw_options.processed*mult +" of " + iw_options.order_count + " orders");
				var percent = parseInt(100 * (iw_options.processed*mult / iw_options.order_count));
				jQuery(".import-progress > .actual-progress").stop();
				jQuery(".import-progress > .actual-progress").animate({width: percent + "%"}, 500, function(){
					var mult = iw_options.pergroup;
					var smt_percent = parseInt(100 * ((iw_options.processed+1)*mult / iw_options.order_count));
					if(smt_percent < 100) jQuery(".import-progress > .actual-progress").animate({width: smt_percent + "%"}, 15000);
				});

				if(iw_options.processed == iw_options.groups) {
					jQuery(".import-progress > .actual-progress").stop();
					jQuery(".import-progress > .actual-progress").animate({width: "100%"}, 500, function() {
						jQuery(".import-progress > .actual-progress").stop();
					});
					if(iw_options.step1 == 'import') {
						jQuery(".progress-status").html('<span style="color:green">Import Successful! You can view the imported orders <a href="edit.php?post_type=shop_order">here.</a></span>');
					} else {
						jQuery(".progress-status").html('<span style="color:green">Export Successful! You should be able to view the orders in Infusionsoft.</span>');
					}
					jQuery(".import-progress > .actual-progress").css("background-color","green");
				}
			} else {
				jQuery(".progress-status").html('<span style="color:red">Process failed. ERROR: '+data+'</span>');
				jQuery(".import-progress > .actual-progress").stop();
				jQuery(".import-progress > .actual-progress").css("background-color","red");
			}
		}).fail( function(){
			jQuery(".progress-status").html('<span style="color:red">Process failed. Please check if Infusionsoft connection is still active, otherwise contact support.</span>');
			jQuery(".import-progress > .actual-progress").stop();
			jQuery(".import-progress > .actual-progress").css("background-color","red");
		});

		iw_options.toprocess++;
		loopcount++;		
	}
}





// CHECKOUT FIELDS
function refresh_grp_form() {
	jQuery(".iw-grp-further").hide();
	var display_option = jQuery("[name=iw-grp-display]").val();

	switch(display_option) {
		case 'product':
			jQuery(".iw-grp-further-products").show();
			break;
		case 'categ':
			jQuery(".iw-grp-further-categ").show();
			break;
		case 'morevalue':
		case 'lessvalue':
			jQuery(".iw-grp-further-value").show();
			break;
		case 'moreitem':
		case 'lessitem':
			jQuery(".iw-grp-further-item").show();
			break;
		case 'coupon':
			jQuery(".iw-grp-further-coupon").show();
			break;

	}

	jQuery(".chzn-select").chosen({width: "80%"});
}

function refresh_field_form() {
	jQuery(".iw-field-further").hide();

	var display_option = jQuery("[name=iw-field-display]").val();
	var datatype = jQuery("[name=iw-field-type]").val();

	if(datatype == 'dropdown' || datatype == 'multidropdown' || datatype == 'radio' || datatype == 'checkbox' ) {
		jQuery(".iw-field-options").show();
		jQuery(".iw-field-options > div").hide();
		jQuery(".options-for-"+datatype).show();
	} else {
		jQuery(".iw-field-options").hide();
	}

	switch(display_option) {
		case 'product':
			jQuery(".iw-field-further-products").show();
			break;
		case 'categ':
			jQuery(".iw-field-further-categ").show();
			break;
		case 'morevalue':
		case 'lessvalue':
			jQuery(".iw-field-further-value").show();
			break;
		case 'moreitem':
		case 'lessitem':
			jQuery(".iw-field-further-item").show();
			break;
		case 'coupon':
			jQuery(".iw-field-further-coupon").show();
			break;

	}

	jQuery(".chzn-select").chosen({width: "80%"});
}

function save_grp_form() {

	// validation:
	if(jQuery('[name=iw-grp-name]').val() == '') {
		show_fail(jQuery(".iw-grp-edit"),"Please enter a group name.")
		return false;
	}

	show_loading(jQuery(".iw-grp-edit"), "Saving...");

	// prepare values:
	var grp_vals = {};
	grp_vals.id = jQuery("[name=iw-grp-id]").val();
	grp_vals.name = jQuery("[name=iw-grp-name]").val();
	grp_vals.disp = jQuery("[name=iw-grp-display]").val();

	switch(grp_vals.disp) {
		case 'product':
			grp_vals.further = jQuery("[name=iw-grp-further-products]").chosen().val();
			break;
		case 'categ':
			grp_vals.further = jQuery("[name=iw-grp-further-categ]").chosen().val();
			break;
		case 'morevalue':
		case 'lessvalue':
			grp_vals.further = jQuery("[name=iw-grp-further-value]").val();
			break;
		case 'moreitem':
		case 'lessitem':
			grp_vals.further = jQuery("[name=iw-grp-further-item]").val();
			break;
		case 'coupon':
			grp_vals.further = jQuery("[name=iw-grp-further-coupon]").val();
			break;

	}

	jQuery.post(ajaxurl + "?action=iw_cf_save_group&jsoncallback=x",grp_vals, function(data) {
		if(data.trim() == "ok") {
			load_grp_fields();
		} else {
			show_fail(jQuery(".iw-grp-edit"),"Error saving: " + data)
		}
	}).fail( function(){
		show_fail(jQuery(".iw-grp-edit"),"Unexpected error...")
	});

	iw_options.grp_vals = grp_vals;
	return false;
}

function load_grp_fields() {
	goback = iw_options.goback;
	enable_loading_cover(jQuery(".iw_checkoutfields"));
	jQuery.post(ajaxurl + "?action=iw_cf_load_fields&jsoncallback=x", {}, function(data) {
		jQuery(".iw_checkoutfields").html(data);
		if(goback) {
			prev_step(jQuery(".checkout-fields"));
		}
		iw_options.goback = false;

		jQuery( ".iw_cf_fields" ).sortable({
	    	update: function( event, ui ) {
	    		var grpid = jQuery(this).parent().attr('grpid');
	    		reposition_cf_fields(grpid);
	    	}
	    });

		jQuery(".loading-cover").remove();
	}).fail( function(){
		if(goback) {
			show_fail(jQuery(".iw-grp-edit"),"Unexpected error...");
		}

		jQuery(".iw_checkoutfields").html('Connection error... Please refresh page.')
	});
}

function enable_loading_cover($el) {
	$el.before('<div class="loading-cover"></div>');
	jQuery(".loading-cover").height($el.height() + 60);
	jQuery(".loading-cover").width($el.width());
	jQuery(".loading-cover").css("background-color", "white");
	jQuery(".loading-cover").css("position", "relative");
	jQuery(".loading-cover").css("z-index", "9999");
	jQuery(".loading-cover").css("margin-bottom", -$el.height()-60 + "px");
	jQuery(".loading-cover").css("opacity", 0.6);
}

function load_grp_form(grpid) {
	next_step(jQuery(".checkout-fields"));
	iw_options.goback = true;

	jQuery(".iw-grp-title").html("Add New Group");
	jQuery("[name=iw-grp-id]").val('');
	jQuery("[name=iw-grp-name]").val('');
	jQuery("[name=iw-grp-display]").val('always');
	jQuery('.chzn-select').val('').trigger('liszt:updated');
	jQuery("[name=iw-grp-further-value]").val('');
	jQuery("[name=iw-grp-further-item]").val('');
	jQuery("[name=iw-grp-further-coupon]").val('');
	jQuery(".iw-alerts").remove();
	
	if(grpid > 0) {
		jQuery(".iw-grp-title").html("Edit Group");
		enable_loading_cover(jQuery(".iw-grp-edit"));
		jQuery("[name=iw-grp-id]").val(grpid);

		jQuery.getJSON(ajaxurl,{action: 'iw_cf_load_group', jsoncallback: 'x',format: 'json', grpid: grpid}).done(function(data) {
			iw_options.temp = data;

			jQuery("[name=iw-grp-name]").val(data.name);
			jQuery("[name=iw-grp-display]").val(data.disp);

			switch(data.disp) {
				case 'product':
					jQuery("[name=iw-grp-further-products]").val(data.further).trigger('liszt:updated');
					break;
				case 'categ':
					jQuery("[name=iw-grp-further-categ]").val(data.further).trigger('liszt:updated');
					break;
				case 'morevalue':
				case 'lessvalue':
					jQuery("[name=iw-grp-further-value]").val(data.further);
					break;
				case 'moreitem':
				case 'lessitem':
					jQuery("[name=iw-grp-further-item]").val(data.further);
					break;
				case 'coupon':
					jQuery("[name=iw-grp-further-coupon]").val(data.further);
					break;

			}

			jQuery(".loading-cover").remove();
			refresh_grp_form();

		}).fail( function(){
			jQuery(".iw-grp-edit").html('Connection error... Please refresh page.')
		});
	}


	refresh_grp_form();
}

function load_field_form(fieldid) {
	next_step(jQuery(".checkout-fields"));
	iw_options.goback = true;

	jQuery(".iw-grp-title").html('Add a new custom field');
	jQuery("[name=iw-field-id]").val('');
	jQuery("[name=iw-field-name]").val('');
	jQuery("[name=iw-field-type]").val('text');
	jQuery("[name=iw-field-required]").val('no');
	jQuery("[name=iw-field-infusionsoft]").val('');
	jQuery("[name=iw-field-display]").val('inherit');
	jQuery('.chzn-select').val('').trigger('liszt:updated');
	jQuery("[name=iw-field-further-value]").val('');
	jQuery("[name=iw-field-further-item]").val('');
	jQuery("[name=iw-field-further-coupon]").val('');
	jQuery("[name=iw-field-options]").val('');
	jQuery(".iw-field-options").hide();
	jQuery(".iw-alerts").remove();
	
	if(fieldid > 0) {
		jQuery(".iw-grp-title").html("Edit Field");
		enable_loading_cover(jQuery(".iw-field-edit"));
		jQuery("[name=iw-field-id]").val(fieldid);

		jQuery.getJSON(ajaxurl,{action: 'iw_cf_load_field', jsoncallback: 'x',format: 'json', fieldid: fieldid}).done(function(data) {
			iw_options.temp = data;

			jQuery("[name=iw-field-name]").val(data.name);
			jQuery("[name=iw-field-type]").val(data.type);
			jQuery("[name=iw-field-required]").val(data.required);
			jQuery("[name=iw-field-infusionsoft]").val(data.infusionsoft).trigger('liszt:updated');
			jQuery("[name=iw-field-display]").val(data.disp);

			if(data.type == 'dropdown' || data.type == 'multidropdown' || data.type == 'radio' || data.type == 'checkbox') {
				jQuery(".iw-field-options").show();
				jQuery(".iw-field-options > div").hide();
				jQuery(".options-for-"+data.type).show();
				jQuery("[name=iw-field-options]").val(data.options.join("\n"));
				jQuery("[name=iw-field-default]").val(data.default);
			}

			switch(data.disp) {
				case 'product':
					jQuery("[name=iw-field-further-products]").val(data.further).trigger('liszt:updated');
					break;
				case 'categ':
					jQuery("[name=iw-field-further-categ]").val(data.further).trigger('liszt:updated');
					break;
				case 'morevalue':
				case 'lessvalue':
					jQuery("[name=iw-field-further-value]").val(data.further);
					break;
				case 'moreitem':
				case 'lessitem':
					jQuery("[name=iw-field-further-item]").val(data.further);
					break;
				case 'coupon':
					jQuery("[name=iw-field-further-coupon]").val(data.further);
					break;

			}

			jQuery(".loading-cover").remove();
			refresh_field_form();

		}).fail( function(){
			jQuery(".iw-field-edit").html('Connection error... Please refresh page.')
		});
	}

	refresh_field_form();
}

function reposition_cf_groups() {
	var $groups = jQuery(".iw_checkoutfields > li");
	var position = [];

	for(var i = 0; i < $groups.length; i++) {
		position.push(jQuery($groups[i]).attr("grpid"));
	}

	enable_loading_cover(jQuery(".iw_checkoutfields"));
	jQuery.post(ajaxurl + "?action=iw_cf_reposition_groups&jsoncallback=x", {position: position}, function(data) {
		jQuery(".loading-cover").remove();
	}).fail( function(){
		jQuery(".iw_checkoutfields").html('Connection error... Please refresh page.')
	});
}

function reposition_cf_fields(grpid) {
	var $fields = jQuery(".iw_checkoutfields > li[grpid='"+grpid+"']").children("ul").children("li");
	var position = [];

	for(var i = 0; i < $fields.length; i++) {
		position.push(jQuery($fields[i]).attr("fieldid"));
	}

	enable_loading_cover(jQuery(".iw_checkoutfields"));
	jQuery.post(ajaxurl + "?action=iw_cf_reposition_fields&jsoncallback=x", {position: position, grpid: grpid}, function(data) {
		jQuery(".loading-cover").remove();
	}).fail( function(){
		jQuery(".iw_checkoutfields").html('Connection error... Please refresh page.')
	});
}



function save_field_form() {

	// validation:
	if(jQuery('[name=iw-field-name]').val() == '') {
		show_fail(jQuery(".iw-field-edit"),"Please enter a field name.")
		return false;
	}

	show_loading(jQuery(".iw-field-edit"), "Saving...");

	// prepare values:
	var field_vals = {};
	field_vals.id = jQuery("[name=iw-field-id]").val();
	field_vals.grpid = jQuery("[name=iw-field-grpid]").val();
	field_vals.type = jQuery("[name=iw-field-type]").val();
	field_vals.required = jQuery("[name=iw-field-required]").val();
	field_vals.infusionsoft = jQuery("[name=iw-field-infusionsoft]").chosen().val();
	field_vals.name = jQuery("[name=iw-field-name]").val();
	field_vals.disp = jQuery("[name=iw-field-display]").val();
	field_vals.options = jQuery("[name=iw-field-options]").val().split("\n");
	field_vals.default = jQuery("[name=iw-field-default]").val();

	switch(field_vals.disp) {
		case 'product':
			field_vals.further = jQuery("[name=iw-field-further-products]").chosen().val();
			break;
		case 'categ':
			field_vals.further = jQuery("[name=iw-field-further-categ]").chosen().val();
			break;
		case 'morevalue':
		case 'lessvalue':
			field_vals.further = jQuery("[name=iw-field-further-value]").val();
			break;
		case 'moreitem':
		case 'lessitem':
			field_vals.further = jQuery("[name=iw-field-further-item]").val();
			break;
		case 'coupon':
			field_vals.further = jQuery("[name=iw-field-further-coupon]").val();
			break;

	}

	jQuery.post(ajaxurl + "?action=iw_cf_save_field&jsoncallback=x",field_vals, function(data) {
		if(data.trim() == "ok") {
			load_grp_fields();
		} else {
			show_fail(jQuery(".iw-field-edit"),"Error saving: " + data)
		}
	}).fail( function(){
		show_fail(jQuery(".iw-field-edit"),"Unexpected error...")
	});

	iw_options.field_vals = field_vals;
	return false;
}

function enable_multi_autocomplete($el, endpoint, fieldname) {
	$el.autocomplete({
      source: function( request, response ) {
      	var term = request.term;

      	if(!isInt(parseInt(term)) && term.length < 3) {
      		response([{label: "Continue typing to search...", value: ""}]);
      		return true;
      	} 

      	if(!((endpoint+'_cache') in iw_options)) {
      		iw_options[endpoint+'_cache'] = {}; 
      	}

      	if ( term in iw_options[endpoint+'_cache'] ) {
          response( iw_options[endpoint+'_cache'][ term ] );
          return true;
        }

        response([{label: "Searching...", value: ""}]);

        if(iw_options.data_search) iw_options.data_search.abort();

      	iw_options.data_search = jQuery.getJSON(ajaxurl, {action: 'iw_data_search_'+endpoint, term: request.term}, function(data) {
      		iw_options[endpoint+'_cache'][ term ] = data;
      		response( data );
      		return true;
      	});
      },
      minLength: 1,
      select: function( event, ui ) {
        if(ui.item.id){
          var field = endpoint;
          if(fieldname) field = fieldname;

          jQuery(this).val('');
          allvals = jQuery(this).next(".iw-tokens").find('[name="'+fieldname+'[]"]');

          if(allvals.length && allvals.length > 0) {
          	for(var i = 0; i < allvals.length; i++) {
          		if(jQuery(allvals[i]).val() == ui.item.id) {
          			return false;
          		}
          	} 
          }


          var lihtml = '<div class="iw-ov-products iw-token-instance">' + ui.item.label;

     
          lihtml += '<input type="hidden" name="'+field+'[]" value="'+ui.item.id+'" />';
          lihtml += '<input type="hidden" name="'+field+'_label[]" value="'+ui.item.label+'" />';
          lihtml += ' <i class="fa fa-times-circle del_li"></i>';
          lihtml += '</div>';
          jQuery(this).next(".iw-tokens").append(lihtml);

          return false;
        }
      }
    }).keyup(function(e) {
      var code = (e.keyCode ? e.keyCode : e.which);
      if (code==13) {
          e.preventDefault();
          return false;
      }
    });
}


// pause process
jQuery(".play-proc").hide();
jQuery(".repeat-proc").hide();

jQuery(".pause-proc").click(function() {
	if(iw_options.status != 'success' && iw_options.status != 'failed') {
		iw_options.paused = true;
		jQuery(".progress-status").html('<span style="color:#FF9900">Process Paused. Click Play to continue.</span>');
		jQuery(".import-progress > .actual-progress").stop();
		jQuery(".import-progress > .actual-progress").css("background-color","#FF9900");
		jQuery(".play-proc").show();
		jQuery(this).hide();
		update_process_logs("Process Paused.");
	}
});

jQuery(".play-proc").click(function() {
	update_process_logs("Process Resumed.");
	iw_options.paused = false;
	iw_bgprocess(iw_options);
	jQuery(".pause-proc").show();
	jQuery(this).hide();
});

jQuery(".repeat-proc").click(function() {
	update_process_logs("Retrying Process.");

	iw_options.fatalreported = false;
	iw_options.paused = false;
	iw_options.status = 'pending';
	iw_bgprocess(iw_options);
	jQuery(".pause-proc").show();

	jQuery(".import-progress > .actual-progress").animate({width: "0%"}, 500);
	jQuery(".progress-status").html("Retrying Process...");
	jQuery.post(ajaxurl + '?action=iw_start_bgprocess', iw_options);

	setTimeout(function() {
		if(!iw_options.paused && iw_options.status != 'failed' ) iw_bgprocess(iw_options);
	}, 2500);

	jQuery(this).hide();
});

function update_process_logs(logs) {
	if(logs) {
		if(typeof logs === 'object') {
			var server_recorded_logs = localStorage.getItem('iw_processes_'+iw_options.process+'_recorded_logs');
			if(server_recorded_logs === false) {
				server_recorded_logs = "";
			}

			for(var i in logs) if(server_recorded_logs.indexOf('-' + i + '-') === -1) {
				jQuery(".process-logs").prepend(logs[i] + "<br>");
				server_recorded_logs += "-" + i + "-";
			}
			// recorded server logs:
			localStorage.setItem('iw_processes_'+iw_options.process+'_recorded_logs', server_recorded_logs);
		} else {
			var new_log = new Date().toISOString().slice(0, 19).replace('T', ' ') + " " + logs;
			jQuery(".process-logs").prepend(new_log + "<br>");
		}

		// save logs
		localStorage.setItem('iw_processes_'+iw_options.process+'_logs', jQuery(".process-logs").html());
	}
}


function isInt(n) {
   return n % 1 === 0;
}