/*
 * Participants Database Plugin
 * 
 * version: 0.5
 * 
 * xnau webdesign xnau.com
 * 
 * handles form submissions
 * 
 *  functionality added here:
 *    disable submit after submit to prevent multiple submits
 *    perform email obfuscation if enabled
 *    form element placeholders
 */
jQuery(document).ready(function($) {
  var pdbform = $('input.pdb-submit').closest("form");
  pdbform.submit(function(e) {
    if ($(this).hasClass('pdb-disabled')) {
      e.preventDefault();
      return false;
    }
    $(this).addClass('pdb-disabled');
    return true;
  });
  $('a.obfuscate[rel]').each(function() {
    var address = $.parseJSON($(this).attr('rel'));
    var link = ''.concat(address.name, '@', address.domain);
    $(this).attr('href', 'mailto:' + link).html(link).attr('class', 'obfuscated');
  });
});