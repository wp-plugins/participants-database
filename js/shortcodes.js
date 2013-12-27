/*
 * Participants Database Plugin
 * 
 * version: 0.6
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
  $('.list-container a.obfuscate[rel]').each(function() {
    xnau_email_obfuscate($(this));
  });
  });
/**
 * converts a text-obfuscated email address to a clickable mailto link
 * 
 * @param object el
 * @returns null
 */
function xnau_email_obfuscate(el) {
  var address = jQuery.parseJSON(el.attr('rel'));
  var link = ''.concat(address.name, '@', address.domain);
  el.attr('href', 'mailto:' + link).html(link).addClass('obfuscated');
}