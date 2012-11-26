jQuery(document).ready(function($) {
		var wrapped = $(".wrap .ui-tabs>h3").wrap("<div class=\"ui-tabs-panel\">");
		wrapped.each(function() {
			$(this).parent().append($(this).parent().nextUntil("div.ui-tabs-panel"));
		});
		$(".ui-tabs-panel").each(function(index) {
			var str = $(this).children("a.pdb_anchor").attr('name').replace(/\s/g, "_");
			$(this).attr("id", str.toLowerCase());
			if (index > 0)
				$(this).addClass("ui-tabs-hide");
		});
		var wrapclass = $('.wrap').attr('class');
		$(".ui-tabs").tabs({ 
											 fx: { opacity: "show",  duration: "fast" },
											 cookie: { expires:1 } 
											 }).bind( 'tabsselect', function( event,ui) {
																							var activeclass = $(ui.tab).attr('href').replace( /^#/, '');
																							$(".wrap").removeClass().addClass( wrapclass+" "+activeclass );
																							});
		//$(".wrap h3, .wrap table").show();
		if ($.browser.mozilla)
			$("form").attr("autocomplete", "off");
});