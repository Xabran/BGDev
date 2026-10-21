jQuery(function($) {

	$(".entry-content .one-fourth > h4").click(function(){
		var b=$(this).parent();
		var p = b.position();
			
		if (p.left==0) {
			b.animate({left:-1250},500);
			$(this).removeClass("active");
		}
		else {
			b.animate({left:0},500);
			$(this).addClass("active");
		}
		
	});
	
	
	$("body.blog .sidebar").click(function(e){
		var b=$(this);
		var p = b.position();
			
		if (p.left==0) {
			b.animate({left:-1000},500);
			$(this).removeClass("active");
		}
		else {
			b.animate({left:0},500);
			$(this).addClass("active");
		}
		
	});

	$("body.archive .sidebar").click(function(e){
		var b=$(this);
		var p = b.position();
			
		if (p.left==0) {
			b.animate({left:-1000},500);
			$(this).removeClass("active");
		}
		else {
			b.animate({left:0},500);
			$(this).addClass("active");
		}
		
	});

	
	
	$("body.single .sidebar").click(function(e){
		var b=$(this);
		var p = b.position();
			
		if (p.left==0) {
			b.animate({left:-1000},500);
			$(this).removeClass("active");
		}
		else {
			b.animate({left:0},500);
			$(this).addClass("active");
		}
		
	});
	
	
	$("body.page .one-fourth").first().click(function(e){
		var b=$(this);
		var p = b.position();
			
		if (p.left==0) {
			b.animate({left:-1000},500);
			$(this).removeClass("active");
		}
		else {
			b.animate({left:0},500);
			$(this).addClass("active");
		}
		
	});
	

	$("body .filter-result").click(function(e){
		var b=$(this);
		var p = b.position();
			
		if (p.left==0) {
			b.animate({left:-1000},500);
			$(this).removeClass("active");
		}
		else {
			b.animate({left:0},500);
			$(this).addClass("active");
		}
		
	});
	
	
	

	$("#menu-footer-menu > li > a").click(function(e){
		e.preventDefault();		
		$(this).parent().toggleClass("active");
		$(this).siblings("ul").toggle();
	});
	
	

	
	// MAIN MENU
	$("body a").click(function(e){
		var li=$(this).parent("li");
		// have siblings
		var sib = $(this).siblings("ul");
		if (li.hasClass("menu-item") && sib.length && $("#menu-main-navigation").hasClass("menu-opened")) {
			//console.log("hasClass(menu-item) blya!");
			e.preventDefault();		
			//e.stopPropagation();
			e.stopImmediatePropagation();
		
			$(this).parent().toggleClass("active");
			$(this).siblings("ul").toggle();
			return false;
		}
		
	});


	$(".conright > div .bord span").click(function(){
		var ol=$(this).parents(".one-third").find("ol");


		if (ol.css("height")=="0px")
		{
			$(this).parents(".one-third").addClass("active");
			ol.css("height","auto");
			ol.css("overflow","visible");
			
	
		}
		else
		{	
			$(this).parents(".one-third").removeClass("active");
			ol.css("height","0");
			ol.css("overflow","hidden");

		}

	});
	

	$("#mobile_search a.search").click(function(e){
        e.stopPropagation();
		//$("#text-5 form").toggle();
		
		$("#text-5").toggleClass("l20");
		$("#text-5 form").toggle();
		$("header .title-area").toggleClass("mt25");
		
		
		
		return false;
	});
	

	$("#order_review .shop_table .shipping td").attr("colspan",2);
	
	$("#order_review").change(function(){
		$("#order_review .shop_table .shipping td").attr("colspan",2);
	});

});
