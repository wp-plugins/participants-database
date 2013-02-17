/*
 * for jQuery 1.8.3, jQuery UI 1.9.2
 * 
 */
jQuery(document).ready(function($) {
  var wrapped = $(".participants_db.wrap .ui-tabs>h3").wrap("<div class=\"ui-tabs-panel\">");
  wrapped.each(function() {
    $(this).parent().append($(this).parent().nextUntil("div.ui-tabs-panel"));
  });
  $(".ui-tabs-panel").each(function(index) {
    var str = $(this).children("a.pdb-anchor").attr('name').replace(/\s/g, "_");
    $(this).attr("id", str.toLowerCase());
  });
  var wrapclass = $('.participants_db.wrap').attr('class');
  $(".participants_db.wrap").removeClass().addClass( wrapclass+" main" );
  var lastTab = 'pdb-settings-page-tab',
  effect = {
    effect: 'fadeToggle', 
    duration: 200
  };
  if ( $.versioncompare("1.9",$.ui.version) == 1 ) {
    var tabsetup = { 
      fx: {
        opacity: "show",  
        duration: "fast"
      },
      cookie: {
        expires:1
      } 
    }
  } else {
    var tabsetup = {
      hide:effect,
      show:effect,
      active:$.cookie(lastTab),
      activate: function(event, ui){
        $.cookie(lastTab, ui.newTab.index(), {
          expires: 365
        });
      }
    }
}
$('.participants_db .ui-tabs').tabs(tabsetup).bind( 'tabsselect', function( event,ui) {
  var activeclass = $(ui.tab).attr('href').replace( /^#/, '');
  $(".participants_db.wrap").removeClass().addClass( wrapclass+" "+activeclass );
});
if ($.browser.mozilla)
  $("form").attr("autocomplete", "off");
});