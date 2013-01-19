/*
 * Participants Database Plugin
 * 
 * handles AJAX list filtering and sorting
 */
jQuery(document).ready(function ($) {
  "use strict";
  var isError,
  errormsg = $('.pdb-searchform .pdb-error'),
  filterform = $('#sort_filter_form'),
  submission = {};
  
  clear_error_messages();
  
  submission.filterNonce = PDb_ajax.filterNonce;
  submission.postID = PDb_ajax.postID;
  
  if (filterform.attr('ref')=='update'){
    filterform.find('input[type="submit"]').live('click',function(event) {
      if (event.preventDefault) {
        event.preventDefault();
      } else {
        event.returnValue = false;
      }
      clear_error_messages();
      // validate and process form here
      var submitButton = event.target;
      submission.submit = submitButton.value;
      if (submitButton.value === PDb_ajax.i18n.search) {
        if ($('select[name="search_field"]').val() === "none") {
          $("#search_field_error").show();
          isError = true;
        }
        if (! $('input[name="value"]').val()) {
          $("#value_error").show();
          isError = true;
        }
        if(isError){
          errormsg.show();
        }
      } else if (submitButton.value === PDb_ajax.i18n.clear) {
        $('select[name="search_field"]').val('none');
        $('input[name="value"]').val('');
        clear_error_messages();
      }
      if (!isError){
        filterform.find('input:not(input[type="submit"],input[type="radio"]), select').each( function() {
          submission[this.name]=escape($(this).val());
        });
        filterform.find('input[type="radio"]:checked').each( function() {
          submission[this.name]=escape($(this).val());
        });
        $.ajax({
          type: "POST",
          url: PDb_ajax.ajaxurl,
          data: submission,
          success: function(html,status) {
            var parts = html.split("%%%");
            $('#pdb-list').replaceWith($(parts[0]).find('#pdb-list'));
            if ($('.pagination').length){
              $('.pagination').replaceWith($(parts[1]));
            }else{
              $('#pdb-list').after($(parts[1]));
            }
          },
          error:function(jqXHR,status,errorThrown){
            console.log('Participants Database JS error status:'+status+' error:'+errorThrown);
          }
        });
      }
    });
  }else{
    filterform.find('input[type="submit"]').live('click',function(event) {
      // process the 'clear' submit only'
      var submitButton = event.target;
      if (submitButton.value === PDb_ajax.i18n.clear) {
        if (event.preventDefault) {
          event.preventDefault();
        } else {
          event.returnValue = false;
        }
        $('select[name="search_field"]').val('none');
        $('input[name="value"]').val('');
        clear_error_messages();
      }
    });
  }
  function clear_error_messages() { 
    errormsg.hide();
    errormsg.children('p').hide();
    isError = false;
  }
});