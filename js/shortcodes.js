/*
 * Participants Database Plugin
 * 
 * version: 0.7
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
  // prevent double submissions
  var pdbform = $('input.pdb-submit').closest("form");
  pdbform.submit(function(e) {
    if ($(this).hasClass('pdb-disabled')) {
      e.preventDefault();
      return false;
    }
    $(this).addClass('pdb-disabled');
    return true;
  });
  // place email obfuscation
  $('a.obfuscate[rel]').each(function() {
    xnau_email_obfuscate($(this));
  });
  // dropdown-other controls
  var
          othergroup = $('div.dropdown-other-control-group');
  othergroup.on('change', 'select.otherselect', function() {
    var 
            thisGroup = $(this).closest('.dropdown-other-control-group'),
            thisName = thisGroup.attr('name'),
            otherLabel = thisGroup.attr('rel');
    if ($(this).val() == 'other') {
      thisGroup.find('.otherselect').attr('name','temp');
      thisGroup.find('.otherfield').attr('name', thisName).select();
    } else {
      thisGroup.find('.otherselect').attr('name', thisName);
      thisGroup.find('.otherfield')
              .attr('name','temp')
              .val(thisGroup.find('.otherselect').find('option:selected').text()==""?"("+otherLabel+")":"");
    }
  });
  othergroup.on('click', 'input.otherfield', function() {
    var 
            thisGroup = $(this).closest('.dropdown-other-control-group'),
            thisName = thisGroup.attr('name'),
            otherLabel = thisGroup.attr('rel');
    thisGroup.find('.otherfield').attr('name',thisName);
    thisGroup
            .find('.otherselect option:selected').removeAttr('selected')
            .end()
            .find('.otherselect option[value=other]').prop('selected', true)
            .end()
            .find('.otherselect').attr('name','temp');
    return true;
  });
  othergroup.find('.otherselect').trigger('change');
  });
/**
 * converts a text-obfuscated email address to a clickable mailto link
 * 
 * @param object el
 * @returns null
 */
function xnau_email_obfuscate(el) {
  var address;
  try {
    address = jQuery.parseJSON(el.attr('rel'));
  } catch (e) {
    return;
  }
  var link = ''.concat(address.name, '@', address.domain);
  el.attr('href', 'mailto:' + link).html(link).addClass('obfuscated');
}