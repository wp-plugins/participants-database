/**
 * js for handling dorpdown/other and multiselect/other form elements
 * 
 * @author Roland Barker, xnau webdesign
 * @version 0.1
 */
jQuery(document).ready(function($) {
  
  /*
   * dropdown-other controls
   */
  var
        ddGroupClass = '.dropdown-other-control-group',        
        ddOtherGroup = $('div'+ddGroupClass);
  ddOtherGroup.on('change', 'select.otherselect', function() {
    var 
            thisGroup = $(this).closest(ddGroupClass),
            thisName = thisGroup.attr('name'),
            otherfield = thisGroup.find('.otherfield');
    if(otherfield.val() !== '') {
      otherfield.data('fieldvalue',otherfield.val());
    }
    if ($(this).val() == 'other') {
      thisGroup.find('.otherselect').attr('name','temp');
      thisGroup.find('.otherfield').attr('name', thisName).select();
       if (otherfield.data('fieldvalue')) {
        otherfield.attr('value',otherfield.data('fieldvalue'));
      }
    } else {
      thisGroup.find('.otherselect').attr('name', thisName);
      thisGroup.find('.otherfield')
              .attr('name','temp')
              .val("");
    }
  });
  ddOtherGroup.on('click', 'input.otherfield', function() {
    var 
            thisGroup = $(this).closest(ddGroupClass),
            thisName = thisGroup.attr('name');
    thisGroup.find('.otherfield').attr('name',thisName);
    thisGroup
            .find('.otherselect option:selected').removeAttr('selected')
            .end()
            .find('.otherselect option[value=other]').prop('selected', true)
            .end()
            .find('.otherselect').attr('name','temp');
    return true;
  });
  ddOtherGroup.find('.otherselect').trigger('change');
  
  /*
   * multi-select-other controls
   */
  var 
          cbGroupClass = '.checkbox-other-control-group',        
          cbOtherGroup = $('div'+cbGroupClass);
  cbOtherGroup.on('change', 'input.otherselect', function() {
    var 
            thisGroup = $(this).closest(cbGroupClass),
            thisName = thisGroup.attr('name'),
            otherfield = thisGroup.find('.otherfield');
      if(otherfield.val() !== '') {
        otherfield.data('fieldvalue',otherfield.val());
      }
    if ($(this).is(':checked')) {
       if (otherfield.data('fieldvalue')) {
        otherfield.attr('value',otherfield.data('fieldvalue'));
      }
      otherfield.attr('name', thisName+'[other]').select();
    } else {
      otherfield.attr('name', 'temp').val("");
    }
  });
  cbOtherGroup.on('click', 'input.otherfield', function() {
    var 
            thisGroup = $(this).closest(cbGroupClass),
            thisName = thisGroup.attr('name');
    if ($(this).is(':focus')) {
      $(this).attr('name',thisName+'[other]');
      thisGroup.find('.otherselect').attr('checked',true);
    }
    return true;
  });
  cbOtherGroup.find('.otherselect').trigger('change');
  
  /*
   * radio-other controls
   */
  var 
          rbGroupClass = '.radio-other-control-group',        
          rbOtherGroup = $('div'+rbGroupClass);
  rbOtherGroup.on('change', 'input[type=radio]', function() {
    var 
            thisGroup = $(this).closest(rbGroupClass),
            thisName = thisGroup.attr('name'),
            otherfield = thisGroup.find('.otherfield');
      if(otherfield.val() !== '') {
        otherfield.data('fieldvalue',otherfield.val());
      }
    if ($(this).filter('.otherselect').is(':checked')) {
       if (otherfield.data('fieldvalue')) {
        otherfield.attr('value',otherfield.data('fieldvalue'));
      }
      otherfield.attr('name', thisName).select();
    } else {
      otherfield.attr('name', 'temp').val("");
    }
  });
  rbOtherGroup.on('click', 'input.otherfield', function() {
    var 
            thisGroup = $(this).closest(rbGroupClass),
            thisName = thisGroup.attr('name');
    if ($(this).is(':focus')) {
      $(this).attr('name',thisName);
      thisGroup.find('.otherselect').attr('checked',true);
    }
    return true;
  });
  rbOtherGroup.find('.otherselect').trigger('change');
});

