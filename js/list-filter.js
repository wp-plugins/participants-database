jQuery(document).ready(function ($) {
  "use strict";
  var isError,
  errormsg = $('.pdb-searchform .pdb-error'),
  filterform = $('#sort_filter_form'),
  submission = {};
  
  clear_error_messages();
  
  submission.filterNonce = PDb_ajax.filterNonce;
  submission.postID = PDb_ajax.postID;
  
  //console.log( 'submission JSON='+submission );
  
  filterform.find('input[type="submit"]').live('click',function(event) {
    if (event.preventDefault) {
      event.preventDefault();
    } else {
      event.returnValue = false;
    }
    clear_error_messages();
    // validate and process form here
    var submitButton = event.target;
    if (submitButton.value === "Search") {
      if ($('select[name="where_clause"]').val() === "none") {
        $("#where_clause_error").show();
        isError = true;
      }
      if (! $('input[name="value"]').val()) {
        $("#value_error").show();
        isError = true;
      }
      if(isError){
        errormsg.show();
      }
    } else if (submitButton.value === "Clear") {
      $('select[name="where_clause"]').val('none');
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
          $('.pdb_list').replaceWith(html);
        },
        error:function(jqXHR,status,errorThrown){
          console.log('Participants Database JS error status:'+status+' error:'+errorThrown);
        }
      });
    }
  });
  function clear_error_messages() { 
    errormsg.hide();
    errormsg.children('p').hide();
    isError = false;
  }
});