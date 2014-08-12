/**
 * general scripts for manage database fields page
 * 
 * Participants Database plugin
 * 
 * @version 0.8
 * @author Roland Barker <webdesign@xnau.com>
 */
PDbManageFields = (function ($) {
  "use strict";
  var
          dialogOptions = {
            autoOpen : false,
            height : 'auto',
            minHeight : '20'
          },
          tabEffect = {
            effect : 'fadeToggle',
            duration : 100
          },
          lastTab = 'pdb-manage-fields-tab';
  var deleteField = function (event) {
    event.preventDefault();
    var
            el = $(this),
            row_id = el.attr('name').replace(/^delete_/, ''),
            parent = el.closest('tr'),
            name = parent.find('td.title input').val(),
            thing = el.attr('ref'),
            group = parent.find('td.group select').val(), // set the group ID and get the field count for the group
            group_id = group ? group : row_id,
            countDisplay = $('#field_count_' + group_id),
            count = countDisplay.html(),
            not_empty_group = (/[0-9]+/.test(count) && count > 0) ? true : false, // test to see if the group we're deleting has fields in it
            confirmationBox = $('#confirmation-dialog');

    if (not_empty_group && thing === 'group') {

      confirmationBox.html(PDb_L10n.must_remove.replace('{name}', name));

      // initialize the dialog action
      confirmationBox.dialog(dialogOptions, {
        buttons : {
          "Ok" : function () {
            $(this).dialog('close');
          }
        }
      });

    } else {

      confirmationBox.html(PDb_L10n.delete_confirm.replace('{name}', name).replace('{thing}', thing));

      // initialize the dialog action
      confirmationBox.dialog(dialogOptions, {
        buttons : {
          "Ok" : function () { //If the user choose to click on "OK" Button
            parent.css('opacity', '0.3');
            $(this).dialog('close'); // Close the Confirmation Box
            $.ajax({//make the Ajax Request
              type : 'post',
              url : window.location.pathname + window.location.search,
              data : 'delete=' + row_id + '&action=delete_' + thing,
              beforeSend : function () {
              },
              success : function (response) {
                parent.slideUp(600, function () { //remove the Table row .
                  parent.remove();
                });
                countDisplay.html(count - 1);// update the group field count
                $('#tab_' + row_id).fadeOut();
              }
            });// ajax
          }, // ok
          "Cancel" : function () { //if the User Clicks the button "cancel"
            $(this).dialog('close');
          } // cancel
        } // buttons
      });// dialog
    }
    confirmationBox.dialog('open'); //Display confirmation dialog when user clicks on "delete Image"
    return false;
  },
          captchaPreset = function () {
            var el = $(this),
                    row = el.closest('tr');
            if (el.val() === 'captcha') {
              row.find('td.validation select').val('captcha');
              row.find('td.readonly input[type=checkbox], td.signup input[type=checkbox]').prop('checked', true);
              row.find('td.sortable input[type=checkbox], td.CSV input[type=checkbox], td.persistent input[type=checkbox]').prop('checked', false);
            }
          },
          saveState = function () {
            var el = $(this);
            if (el.is('[type=checkbox]')) {
              el.data('initState', el.is(':checked'));
            } else {
              el.data('initState', el.attr('value'));
            }
          },
          setChangedFlag = function () {
            var
                    el = $(this),
                    initState = el.data('initState'),
                    matches = el.closest('tr').attr('id').match(/(\d+)/),
                    flag = $('#status_' + matches[1]),
                    check = el.is('[type=checkbox]') ? el.is(':checked') : el.attr('value');
            if (check !== initState) {
              flag.attr('value', 'changed');
              setUnsavedChangesFlag(1);
            } else {
              setUnsavedChangesFlag(-1);
            }
          },
          setUnsavedChangesFlag = function (op) {
            var body = $('body'),
                    unsavedChangesCount = body.data('unsavedChanges') || 0;
            if (op === 1) {
              unsavedChangesCount++;
            } else if (op === -1) {
              unsavedChangesCount--;
            }
            body.data('unsavedChanges', unsavedChangesCount);
            if (unsavedChangesCount <= 0) {
              clearUnsavedChangesWarning;
            } else {
            window.onbeforeunload = confirmOnPageExit; // set up the unsaved changes warning
            }
          },
          clearUnsavedChangesWarning = function () {
              window.onbeforeunload = null;
          },
          fixHelper = function (e, ui) {
            ui.children().each(function () {
              jQuery(this).width(jQuery(this).width());
            });
            return ui;
          },
          enableNew = function () {
            $(this).prev('input.button').removeClass('disabled').addClass('enabled').prop('disabled', false);
          },
          serializeList = function (container) {
            /*
             * grabs the id's of the anchor tags and puts them in a string for the 
             * ajax reorder functionality
             */
            var str = '';
            var n = 0;
            var els = container.find('a');
            for (var i = 0; i < els.length; ++i) {
              var el = els[i];
              var p = el.id.lastIndexOf('_');
              if (p != -1) {
                if (str != '')
                  str = str + '&';
                str = str + el.id + '=' + n;
                ++n;
              }
            }
            return str;
          },
          cancelReturn = function (event) {
            // disable autocomplete
            if ($.browser.mozilla) {
              $(this).attr("autocomplete", "off");
            }
            if (event.keyCode === 13)
              return false;
          },
          getTabSettings = function () {
            if ($.versioncompare("1.9", $.ui.version) === 1) {
              return {
                fx : {
                  opacity : "show",
                  duration : "fast"
                },
                cookie : {
                  expires : 1
                }
              };
            } else {
              return {
                hide : tabEffect,
                show : tabEffect,
                active : $.cookie(lastTab),
                activate : function (event, ui) {
                  $.cookie(lastTab, ui.newTab.index(), {
                    expires : 365
                  });
                }
              };
            }
          },
          confirmOnPageExit = function (e) {
            e = e || window.event;
            var message = PDb_L10n.unsaved_changes;
            // For IE6-8 and Firefox prior to version 4
            if (e) {
                e.returnValue = message;
            }
            // For Chrome, Safari, IE8+ and Opera 12+
            return message;
        };
  return {
    fixHelper : fixHelper,
    serializeList : serializeList,
    init : function () {
      var
              tabcontrols = $("#fields-tabs"),
              sortFields = {
                helper : PDbManageFields.fixHelper,
                update : function (event, ui) {
                  var order = PDbManageFields.serializeList($(this));
                  $.ajax({
                    url : manageFields.uri,
                    type : "POST",
                    data : order + '&action=reorder_fields'
                  });
                }
              },
              sortGroups = {
                helper : PDbManageFields.fixHelper,
                update : function (event, ui) {
                  var order = PDbManageFields.serializeList($(this));
                  $.ajax({
                    url : manageFields.uri,
                    type : "POST",
                    data : order + '&action=reorder_groups'
                  });
                }
              };
      clearUnsavedChangesWarning();
      // set up tabs
      tabcontrols.tabs(getTabSettings());
      // save the initial state of all inputs
      tabcontrols.find('table.manage-fields input[type=text], table.manage-fields textarea, table.manage-fields select, table.manage-fields input[type=checkbox]').each(saveState);
      // flag the row as changed for text inputs
      tabcontrols.find('table.manage-fields input, table.manage-fields textarea').on('input', setChangedFlag);
      // flag the row as changed for dropdowns, checkboxes
      tabcontrols.find('table.manage-fields select, table.manage-fields input[type=checkbox]').on('change', setChangedFlag);
      // defeat return key submit behavior
      tabcontrols.on("keypress", 'form', cancelReturn);
      // pre-set CAPTCHA settings
      $('.manage-fields-wrap').on('change', '.manage-fields tbody td.form_element select', captchaPreset);
      // set up the delete functionality
      tabcontrols.find('.manage-fields a.delete').click(deleteField);
      // prevent empty submission
      tabcontrols.find('input.add_field').on('input', enableNew);
      // set up the field sorting
      $("#field_groups tbody").sortable(sortGroups);
      $('[id$="_fields"]').sortable(sortFields);
      $('input.manage-fields-update').on('click', clearUnsavedChangesWarning);
    }
  };
}(jQuery));
jQuery(function () {
  PDbManageFields.init();
});
