/*
 * for jQuery 1.8.3, jQuery UI 1.9.2
 * 
 */
jQuery(document).ready(function($) {
  var wrapped = $(".wrap .ui-tabs>h3").wrap("<div class=\"ui-tabs-panel\">");
  wrapped.each(function() {
    $(this).parent().append($(this).parent().nextUntil("div.ui-tabs-panel"));
  });
  $(".ui-tabs-panel").each(function(index) {
    var str = $(this).children("a.pdb-anchor").attr('name').replace(/\s/g, "_");
    $(this).attr("id", str.toLowerCase());
    if (index > 0)
      $(this).addClass("ui-tabs-hide");
  });
  var wrapclass = $('.wrap').attr('class');
  $(".wrap").removeClass().addClass( wrapclass+" main" );
  var lastTab = 'pdb-settings-page-tab',
  effect = { effect: 'fadeToggle', duration: 200 };
  $('.ui-tabs').tabs({
    hide:effect,
    show:effect,
    active:$.cookie(lastTab),
    activate: function(event, ui){
        $.cookie(lastTab, ui.newTab.index(), { expires: 365 });
    }
  }).bind( 'tabsselect', function( event,ui) {
    var activeclass = $(ui.tab).attr('href').replace( /^#/, '');
    $(".wrap").removeClass().addClass( wrapclass+" "+activeclass );
  });
  if ($.browser.mozilla)
    $("form").attr("autocomplete", "off");
});