/**
 * js for handling general list management functions
 * 
 * @author Roland Barker, xnau webdesign
 * @version 0.2
 */
var PDbListAdmin = (function ($) {
  "use strict";
  var
          checkState = false,
          listform = $('#list_form'),
          count_element = $('#select_count'),
          delete_button = $('#delete_button').prop('disabled', true).addClass('unarmed'),
          checkall = $('#checkall'),
          submitElement = $('<input type="hidden" name="submit-button" />'),
          armDeleteButton = function (state) {
            delete_button
                    .attr('class', state ? delete_button.attr('class').replace('unarmed', 'armed') : delete_button.attr('class').replace('armed', 'unarmed'))
                    .prop('disabled', state ? false : true);
          },
          addSelects = function (selected) {
            var count = count_element.val();
            if (selected === true) {
              checkall.prop("checked", listform.find('.delete-check:not(:checked)').length ? false : true);
              count++;
            } else {
              checkall.prop("checked", false);
              count--;
            }
            if (count < 0) {
              count = 0;
            }
            armDeleteButton(count > 0);
            count_element.val(count);
          },
          checkAll = function () {
            if (checkState === false) {
              checkState = true;
            } else {
              checkState = false;
              armDeleteButton(false);
            }
            listform.find('.delete-check').each(function () {
              $(this).prop('checked', checkState);
              addSelects(checkState);
            });
          },
          confirmDialog = $('<div/>').dialog({
            dialogClass : 'confirmation-dialog',
            modal : true,
            zIndex : 10000,
            autoOpen : false,
            width : 'auto',
            resizable : false,
            buttons : [{
                icons : {
                  primary : "dashicons dashicons-yes"
                },
                click : function () {
                  listform.prepend(submitElement.clone().val(list_adminL10n.delete));
                  armDeleteButton(true);
                  checkState = false;
                  $(this).dialog("destroy");
                  listform.submit();
                }
              }, {
                icons : {
                  primary : "dashicons dashicons-no-alt"
                },
                click : function () {
                  checkState = true;
                  $(this).dialog("close");
                }
              }]
          });
  return {
    init : function () {
      delete_button.on('click', function (e) {
        e.preventDefault();
        var plural = (parseInt(count_element.val(), 10) > 1) ? true : false;
        confirmDialog.html($('<h3/>').text(plural ? list_adminL10n.records : list_adminL10n.record)).dialog('open');
      });
      checkall.click(checkAll);
      $('.delete-check').on('click', function () {
        addSelects($(this).prop('checked'));
      });
      $('#list_filter_count').change($.debounce(500, function () {
        $(this).closest('form').submit();
      }));
    }
  };
}(jQuery));
jQuery(function () {
  PDbListAdmin.init();
});