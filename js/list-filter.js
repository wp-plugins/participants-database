/*
 * Participants Database Plugin
 * 
 * version: 0.6
 * 
 * xnau webdesign xnau.com
 * 
 * handles AJAX list filtering and sorting
 */
PDbListFilter = (function($) {
  "use strict";
  var
          isError = false,
          errormsg = $('.pdb-searchform .pdb-error'),
          filterform = $('.sort_filter_form[ref="update"]'),
          remoteform = $('.sort_filter_form[ref="remote"]'),
          submission = {
            filterNonce: PDb_ajax.filterNonce,
            postID: PDb_ajax.postID
          },
          submit_search = function(event, remote) {
            remote = remote || false;
            if (event.preventDefault) {
              event.preventDefault();
            } else {
              event.returnValue = false;
            }
            clear_error_messages();
            // validate and process form here
            var
                    this_button = $(event.target),
                    submitButton = event.target,
                    search_field_error = this_button.closest('.' + PDb_ajax.prefix + 'searchform').find('.search_field_error'),
                    value_error = this_button.closest('.' + PDb_ajax.prefix + 'searchform').find('.value_error');
            //container.data('target', 'container').find('.list-container');
            submission.submit = submitButton.value;
            
            switch (submitButton.value) {

              case PDb_ajax.i18n.search:
                if ($('select[name^="search_field"]').PDb_checkInputs('none')) {
                  search_field_error.show();
                  isError = true;
                }
                if ($('input[name^="value"]').PDb_checkInputs('')) {
                  value_error.show();
                  isError = true;
                }
                if (isError) {
                  errormsg.show();
                } else if (remote) {
                  this_button.closest('form').submit();
                } else {
                  this_button.PDb_processSubmission();
                }
                break;
                
              case PDb_ajax.i18n.clear:
                clear_search();
            }
          },
          submit_remote_search = function(event) {
            submit_search(event, true);
          },
          clear_error_messages = function() {
            errormsg.hide();
            errormsg.children().hide();
            isError = false;
          },
          clear_search = function() {
            $('select[name^="search_field"]').PDb_clearInputs('none');
            $('input[name^="value"]').PDb_clearInputs('');
            clear_error_messages();
          },
          compatibility_fix = function() {
            // for backward compatibility
            if (typeof PDb_ajax.prefix === "undefined") {
              PDb_ajax.prefix = 'pdb-';
            }
            $('.wrap.pdb-list').PDb_idFix();
          };
  $.fn.PDb_idFix = function() {
    var el = this;
    el.find('#pdb-list').addClass('list-container').removeAttr('id');
    el.find('#sort_filter_form').addClass('sort_filter_form').removeAttr('id');
  };
  $.fn.PDb_checkInputs = function(check) {
    var el = this,
            number = el.length,
            count = 0;
    el.each(function() {
      if ($(this).val() === check) {
        count++;
      }
    });
    return count === number;
  };
  $.fn.PDb_clearInputs = function(value) {
    this.each(function() {
      $(this).val(value);
    });
  };
  $.fn.PDb_addValue = function(submission) {
    var
            el = this,
            value = encodeURI(el.val()),
            fieldname = el.attr('name'),
            multiple = fieldname.match(/\[\]$/);
    fieldname = fieldname.replace('[]', ''); // now we can remove the brackets
    if (multiple && typeof submission[fieldname] === 'string') {
      submission[fieldname] = [submission[fieldname]];
    }
    if (typeof submission[fieldname] === 'object') {
      submission[fieldname][submission[fieldname].length] = value;
    } else {
      submission[fieldname] = value;
    }
  };
  $.fn.PDb_processSubmission = function() {
    filterform.find('input:not(input[type="submit"],input[type="radio"]), select').each(function() {
      $(this).PDb_addValue(submission);
    });
    filterform.find('input[type="radio"]:checked').each(function() {
      $(this).PDb_addValue(submission);
    });
    // console.log(submission);
    var
            target_instance = $('.pdb-list.pdb-instance-' + submission.instance_index),
            container = target_instance.length ? target_instance : $('.pdb-list').first(),
            buttonParent = this.parent(),
            spinner = $(PDb_ajax.loading_indicator).clone();
    $.ajax({
      type: "POST",
      url: PDb_ajax.ajaxurl,
      data: submission,
      beforeSend: function() {
        buttonParent.append(spinner);
      },
      success: function(html, status) {
        var
                newContent = $(html),
                pagination = newContent.find('.pagination').first(),
                replaceContent = newContent.find('.list-container').length ? newContent.find('.list-container') : newContent;
        newContent.PDb_idFix();
        replaceContent.find('a.obfuscate[data-email-values]').each(function() {
          $(this).PDb_email_obfuscate();
        });
        container.find('.list-container').replaceWith(replaceContent);
        if (container.find('.pagination').first().length) {
          container.find('.pagination').first().replaceWith(pagination);
        } else {
          container.find('.list-container').after(pagination);
        }
        spinner.remove();
      },
      error: function(jqXHR, status, errorThrown) {
        console.log('Participants Database JS error status:' + status + ' error:' + errorThrown);
      }
    });
  };
  return {
    run: function() {

      compatibility_fix();

      clear_error_messages();

      filterform.on('click', 'input[type="submit"]', submit_search);
      remoteform.on('click', 'input[type="submit"]', submit_remote_search);
    }
  };
}(jQuery));
jQuery(function() {
  "use strict";
  PDbListFilter.run();
});