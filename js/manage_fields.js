/*
 * Participants Database Plugin
 * 
 * version: 0.7
 * 
 * xnau webdesign xnau.com
 * 
 * js support for manage database fields page
 */
jQuery(document).ready(function($){

  // flag the row as changed
  $('#fields-tabs table.manage-fields input, #fields-tabs table.manage-fields textarea').bind('keyup',function(el) {
    set_changed_flag(this);
  });
	// flag the row as changed
  $('#fields-tabs table.manage-fields select, #fields-tabs table.manage-fields input[type=checkbox]').change(function(el) {
    set_changed_flag(this);
  });
  function set_changed_flag(el) {
    var matches = $(el).closest('tr').attr('id').match(/(\d+)/);
    $('#status_'+matches[1]).attr('value','changed');
  }

  // defeat return key submit behavior
  $("#fields-tabs form").bind("keypress", function(e) {
    if (e.keyCode == 13) return false;
  });

  // disable autocomplete
  if ($.browser.mozilla)
    $("#fields-tabs form").attr("autocomplete", "off");

  // set up the UI tabs
  var lastTab = 'pdb-manage-fields-tab',
  effect = {
    effect: 'fadeToggle', 
    duration: 100
  };
  if ( $.versioncompare("1.9",$.ui.version) == 1 ) {
    var tabsettings = {
      fx: {
        opacity: "show", 
        duration: "fast"
      },
      cookie: {
        expires:1
      }
    };
  } else {
    var tabsettings = {
      hide:effect,
      show:effect,
      active:$.cookie(lastTab),
      activate: function(event, ui){
        $.cookie(lastTab, ui.newTab.index(), {
          expires: 365
        });
      }
    };
  }
  $("#fields-tabs").tabs(tabsettings);

  // pre-set CAPTCHA settings
  $('.manage-fields-wrap').on('change', '.manage-fields tbody td.form_element select', function(){
    if ($(this).val() == 'captcha') {
      var row = $(this).closest('tr');
      row.find('td.validation select').val('captcha');
      row.find('td.readonly input[type=checkbox], td.signup input[type=checkbox]').prop('checked', true);
      row.find('td.sortable input[type=checkbox], td.CSV input[type=checkbox], , td.persistent input[type=checkbox]').prop('checked', false);
    }
  });


  // set up the delete functionality
  // set up the click function
  $('#fields-tabs .manage-fields a.delete').click(function (e) { // if a user clicks on the "delete" image

    //prevent the default browser behavior when clicking
    e.preventDefault();

    var row_id = $(this).attr('name').replace( /^delete_/,'');
    var parent = $(this).parent().parent('tr');
    var name = parent.find( 'td.title input' ).attr('value');
    var thing = $(this).attr('ref');
    // set the group ID and get the field count for the group
    var group = parent.children('td.group').children('select').val();
    var group_id;
    if ( typeof group != "undefined" ) group_id = group;
    else group_id = row_id;
    var countDisplay = $('#field_count_'+group_id);
    var count = countDisplay.html();
    // test to see if the group we're deleting has fields in it
    var not_empty_group = ( /[0-9]+/.test( count ) && count > 0 ) ? true : false ;

    // set the dialog text
    if ( not_empty_group && thing == 'group' ) {

      $('#confirmation-dialog').html(L10n.must_remove.replace('{name}',name));

      // initialize the dialog action
      $('#confirmation-dialog').dialog({
        autoOpen: false,
        height: 'auto',
        minHeight: '20',
        buttons: {
          "Ok": function () { //If the user choose to click on "OK" Button
            $(this).dialog('close'); // Close the Confirmation Box
          }// ok
        } // buttons
      });// dialog

    } else {

      $('#confirmation-dialog').html(L10n.delete_confirm.replace('{name}',name).replace('{thing}',thing));

      // initialize the dialog action
      $('#confirmation-dialog').dialog({
        autoOpen: false,
        height: 'auto',
        minHeight: '20',
        buttons: {
          "Ok": function () { //If the user choose to click on "OK" Button
              $(this).dialog('close'); // Close the Confirmation Box
              $.ajax({ //make the Ajax Request
                type: 'post',
                url: window.location.pathname + window.location.search,
                data: 'delete=' + row_id + '&action=delete_' + thing,
                beforeSend: function () {
                  parent.animate({
                    'backgroundColor': 'yellow'
                  }, 200);
                },
                success: function (response) {
                  parent.slideUp(600, function () { //remove the Table row .
                      parent.remove();
                  });
                  countDisplay.html( count - 1 );// update the group field count
                  $('#tab_'+row_id).fadeOut();
                }
              });// ajax
          },// ok
          "Cancel": function () { //if the User Clicks the button "cancel"
            $(this).dialog('close');
          } // cancel
        } // buttons
      });// dialog
    }
    $('#confirmation-dialog').dialog('open'); //Display confirmation dialog when user clicks on "delete Image"
    return false;
  }); // click function
}); // document ready

// prevents collapse of table row while dragging
var fixHelper = function(e, ui) {
    ui.children().each(function() {
        jQuery(this).width(jQuery(this).width());
    });
    return ui;
};
// grabs the id's of the anchor tags and puts them in
// a string for the ajax reorder functionality
function serializeList(container)
{
  var str = '';
  var n = 0;
  var els = container.find('a');
  for (var i = 0; i < els.length; ++i) {
    var el = els[i];
    var p = el.id.lastIndexOf('_');
    if (p != -1) {
      if (str != '') str = str + '&';
			str = str + el.id + '=' + n;
      ++n;
    }
  }
  return str;
}