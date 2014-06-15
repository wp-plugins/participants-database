/*
 * Participants Database Plugin
 * 
 * version: 0.6
 * 
 * xnau webdesign xnau.com
 * 
 * handles AJAX list filtering and sorting
 */
(function( $ ) {
  $.fn.pdb = function() {
    var el = this;
    return {
      idFix: function() {
        el.find('#pdb-list').addClass('list-container').removeAttr('id');
        el.find('#sort_filter_form').addClass('sort_filter_form').removeAttr('id');
      },
      checkInputs: function(check) {
        var
                number = el.length,
                count = 0;
        el.each(function() {
          if ($(this).val() === check) {
            count++;
          }
        });
        return count === number;
      },
      clearInputs: function(value) {
        el.each(function() {
          $(this).val(value);
        });
      },
      addValue: function(submission) {
        var
                value = encodeURI(el.val()),
                fieldname = el.attr('name'),
                multiple = fieldname.match(/\[\]$/);
        fieldname = fieldname.replace('[]', '');
        if (multiple && typeof submission[fieldname] === 'string') {
          submission[fieldname] = [submission[fieldname]];
        }
        if (typeof submission[fieldname] === 'object') {
          submission[fieldname][submission[fieldname].length] = value;
        } else {
          submission[fieldname] = value;
        }
      }
    };
  };
}( jQuery ));
jQuery(document).ready(function($) {
  "use strict";

  // this is for backwards compatibility, but it certainly won't work if there are two lists on a page using the old HTML
  $('.wrap.pdb-list').pdb().idFix();

  if (typeof PDb_ajax.prefix === 'undefined')
    PDb_ajax.prefix = 'pdb-';
  var
          isError = false,
          errormsg = $('.pdb-searchform .pdb-error'),
          filterform = $('.sort_filter_form[ref="update"]'),
          remoteform = $('.sort_filter_form[ref="remote"]'),
          submission = {};

  clear_error_messages();

  submission.filterNonce = PDb_ajax.filterNonce;
  submission.postID = PDb_ajax.postID;

    filterform.on('click', 'input[type="submit"]', function(event) {
      if (event.preventDefault) {
        event.preventDefault();
      } else {
        event.returnValue = false;
      }
      clear_error_messages();
      // validate and process form here
      var
            this_button = $(this),
              submitButton = event.target,
            search_field_error = this_button.closest('.' + PDb_ajax.prefix + 'searchform').find('.search_field_error'),
            value_error = this_button.closest('.' + PDb_ajax.prefix + 'searchform').find('.value_error');
      //container.data('target', 'container').find('.list-container');
      submission.submit = submitButton.value;

      if (submitButton.value === PDb_ajax.i18n.search) {
      if ($('select[name^="search_field"]').pdb().checkInputs('none')) {
          search_field_error.show();
          isError = true;
        }
      if ($('input[name^="value"]').pdb().checkInputs('')) {
          value_error.show();
          isError = true;
        }
        if (isError) {
          errormsg.show();
      } else {
        processSubmission(submitButton);
        }
      } else if (submitButton.value === PDb_ajax.i18n.clear) {
      $('select[name^="search_field"]').pdb().clearInputs('none');
      $('input[name^="value"]').pdb().clearInputs('');
        clear_error_messages();
      }
  });
  remoteform.on('click', 'input[type="submit"]', function(event) {
    // process the 'clear' submit only'
    var submitButton = event.target;
    if (submitButton.value === PDb_ajax.i18n.clear) {
      if (event.preventDefault) {
        event.preventDefault();
      } else {
        event.returnValue = false;
      }
      $('select[name^="search_field"]').pdb().clearInputs('none');
      $('input[name^="value"]').pdb().clearInputs('');
      clear_error_messages();
    }
  });
  function processSubmission(submitButton) {
        filterform.find('input:not(input[type="submit"],input[type="radio"]), select').each(function() {
      $(this).pdb().addValue(submission);
        });
        filterform.find('input[type="radio"]:checked').each(function() {
      $(this).pdb().addValue(submission);
        });
    // console.log(submission);
        var
                target_instance = $('.pdb-list.pdb-instance-' + submission.instance_index),
                container = target_instance.length ? target_instance : $('.pdb-list').first(),
            buttonParent = $(submitButton).parent(),
                spinner = $(PDb_ajax.loading_indicator);
        $.ajax({
          type: "POST",
          url: PDb_ajax.ajaxurl,
          data: submission,
          beforeSend: function() {
        buttonParent.append(spinner.clone());
          },
          success: function(html, status) {
            var newContent = $(html);
        newContent.pdb().idFix();
            var
                    pagination = newContent.find('.pagination').first(),
                    replaceContent = newContent.find('.list-container').length ? newContent.find('.list-container') : newContent;
            replaceContent.find('a.obfuscate[rel]').each(function() {xnau_email_obfuscate($(this));});
            container.find('.list-container').replaceWith(replaceContent)
            if (container.find('.pagination').first().length) {
              container.find('.pagination').first().replaceWith(pagination);
            } else {
              container.find('.list-container').after(pagination);
            }
        buttonParent.find('.ajax-loading').remove();
       
        submission = {
          filterNonce: PDb_ajax.filterNonce,
          postID: PDb_ajax.postID
        };
          },
          error: function(jqXHR, status, errorThrown) {
            console.log('Participants Database JS error status:' + status + ' error:' + errorThrown);
          }
        });
      }
  function clear_error_messages() {
    errormsg.hide();
    errormsg.children().hide();
    isError = false;
  }
});