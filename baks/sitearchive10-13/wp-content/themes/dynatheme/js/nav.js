!function(j,e,n){
	"use strict";
	var o={
		label:"Menu",
		action:"PushRight",
		adTo:"before",
		adToEl:"body",
		closeClick:!0,
		custom:!1,
		dataMenu:".genesis-nav-menu"
	};
	
	j.fn.cmknav=function(n){
		var t=j.extend({},o,n),
		c='<a href="#" tabindex="0" class="cmk-nav-toggle" data-cmk-menu="'+t.dataMenu+'">'+t.label+"</a>";
		t.custom===!0?("before"===t.adTo?j(t.adToEl).before(c):j(t.adToEl).after(c),j(t.dataMenu).addClass("cmk-"+t.action)):("before"===t.adTo?this.before(c):this.after(c),
		j(t.dataMenu).addClass("cmk-"+t.action)),
		j(e).on("click","a.cmk-nav-toggle",function(e){
			//console.log("on click");
			var n=j(this).data("cmk-menu");
			e.preventDefault(),
			j(this).toggleClass("active"),
			j(n).toggleClass("menu-opened"),
			j("body").toggleClass(t.action)
		}),
		
		//console.log(t);
		
		t.closeClick === !0 && j(t.dataMenu).find("a").click(function(e){
			//console.log("close click");
			//j("body").removeClass(t.action),
			//j(t.dataMenu).removeClass("menu-opened"),
			//j(".cmk-nav-toggle").removeClass("active")
		})
	}
}(jQuery,document,window);