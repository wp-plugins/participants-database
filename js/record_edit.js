/*
 * Participants Database settings page support
 * 
 * sets up the tab functionality on the plugin settings page
 * 
 * @version 0.2
 * 
 */
PDbSettings = (function ($) {
  var
          tabsetup,
          lastTab = 'pdb-settings-page-tab',
          effect = {
            effect : 'fadeToggle',
            duration : 200
          };
  if ($.versioncompare("1.9", $.ui.version) == 1) {
    tabsetup = {
      fx : {
        opacity : "show",
        duration : "fast"
      },
      cookie : {
        expires : 1
      }
    }
  } else {
    tabsetup = {
      hide : effect,
      show : effect,
      active : $.cookie(lastTab),
      activate : function (event, ui) {
        $.cookie(lastTab, ui.newTab.index(), {
          expires : 365
        });
      }
    }
  }
  return {
    init : function () {
      var wrapped = $(".pdb-admin-edit-participant.wrap .ui-tabs>h3").wrap("<div class=\"ui-tabs-panel\">"),
              wrapclass = $('.pdb-admin-edit-participant.wrap').attr('class');
      wrapped.each(function () {
        $(this).parent().append($(this).parent().nextUntil("div.ui-tabs-panel"));
      });
      $(".ui-tabs-panel").each(function (index) {
        var str = $(this).children("a.pdb-anchor").attr('name').replace(/\s/g, "_");
        $(this).attr("id", str.toLowerCase());
      });
      $(".pdb-admin-edit-participant.wrap").removeClass().addClass(wrapclass + " main");
      $('.pdb-admin-edit-participant .ui-tabs').tabs(tabsetup).bind('tabsselect', function (event, ui) {
        var activeclass = $(ui.tab).attr('href').replace(/^#/, '');
        $(".pdb-admin-edit-participant.wrap").removeClass().addClass(wrapclass + " " + activeclass);
      });
      if ($.browser.mozilla) {
        $("form").attr("autocomplete", "off");
      }
    }
  }
}(jQuery));
jQuery(function () {
  PDbSettings.init();
});