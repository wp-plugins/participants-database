/*
 * Participants Database Plugin
 * 
 * @version 0.8
 * 
 * xnau webdesign xnau.com
 * 
 * handles form submissions
 * 
 *  functionality added here:
 *    disable submit after submit to prevent multiple submits
 *    perform email obfuscation if enabled
 */
PDbShortcodes = (function ($) {
  var submitOnce = function (e) {
    if ($(this).hasClass('pdb-disabled')) {
      e.preventDefault();
      return false;
    }
    $(this).addClass('pdb-disabled');
    return true;
  }
  $.fn.PDb_email_obfuscate = function () {
    var address, link,
            el = this;
    try {
      address = jQuery.parseJSON(el.attr('data-email-values'));
    } catch (e) {
      return;
    }
    link = ''.concat(address.name, '@', address.domain);
    el.attr('href', 'mailto:' + link).html(link).addClass('obfuscated');
  }
  return {
    init : function () {
      // prevent double submissions
      var pdbform = $('input.pdb-submit').closest("form");
      pdbform.submit(submitOnce);
      // test for cookies, then set a page class if not available
      if (!navigator.cookieEnabled) {
        $('html').addClass('cookies-disabled');
      }
      // place email obfuscation
      $('a.obfuscate[data-email-values]').each(function () {
        $(this).PDb_email_obfuscate();
      });
    }
  }
}(jQuery));
jQuery(function () {
  PDbShortcodes.init();
});