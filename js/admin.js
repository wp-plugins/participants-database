// participants-database admin support scripts
PDbAdmin = (function($) {
  $.fn.PDb_email_obfuscate = function () {
    var address, link;
    var el = this;
    try {
      address = jQuery.parseJSON(el.attr('data-email-values'));
    } catch (e) {
      return;
    }
    link = ''.concat(address.name, '@', address.domain);
    el.attr('href', 'mailto:' + link).html(link).addClass('obfuscated');
  }
  return {
    init: function() {
      $('input[placeholder], textarea[placeholder]').placeholder();
      $('.participants_db .ui-tabs-nav li').append($('<span class="mask"/>'));
      $('.manage-fields-wrap').on('focus', '[name*=title], [name*=default] ', function() {
        $(this).addClass('focused').closest('td').addClass('focused');
      }).on('blur', '.manage-fields input[type="text"]', function() {
        $(this).removeClass('focused').closest('td').removeClass('focused');
      });
      // place email obfuscation
      $('a.obfuscate[data-email-values]').each(function () {
        $(this).PDb_email_obfuscate();
      });
    }
  }
})(jQuery);
jQuery(function() {
  PDbAdmin.init();
});
/*!
 *  jQuery version compare plugin
 *
 *  Usage:
 *    $.versioncompare(version1[, version2 = jQuery.fn.jquery])
 *
 *  Example:
 *    console.log($.versioncompare("1.4", "1.6.4"));
 *
 *  Return:
 *    0 if two params are equal
 *    1 if the second is lower
 *   -1 if the second is higher
 *
 *  Licensed under the MIT:
 *  http://www.opensource.org/licenses/mit-license.php
 *
 *  Copyright (c) 2011, Nobu Funaki @zuzara
 */
(function($) {
  $.versioncompare = function(version1, version2) {
    if ('undefined' === typeof version1) {
      throw new Error("$.versioncompare needs at least one parameter.");
    }
    version2 = version2 || $.fn.jquery;
    if (version1 === version2) {
      return 0;
    }
    var v1 = normalize(version1);
    var v2 = normalize(version2);
    var len = Math.max(v1.length, v2.length);
    for (var i = 0; i < len; i++) {
      v1[i] = v1[i] || 0;
      v2[i] = v2[i] || 0;
      if (v1[i] == v2[i]) {
        continue;
      }
      return v1[i] > v2[i] ? 1 : -1;
    }
    return 0;
  };
  function normalize(version) {
    return $.map(version.split('.'), function(value) {
      return parseInt(value, 10);
    });
  }
}(jQuery));
