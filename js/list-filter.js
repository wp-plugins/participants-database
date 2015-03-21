/*
 * Participants Database Plugin
 * 
 * version: 0.8
 * 
 * xnau webdesign xnau.com
 * 
 * handles AJAX list filtering, paging and sorting
 */
PDbListFilter = (function($) {
  "use strict";
  var
          isError = false,
          errormsg = $('.pdb-searchform .pdb-error'),
          filterform = $('.sort_filter_form[data-ref="update"]'),
          remoteform = $('.sort_filter_form[data-ref="remote"]'),
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
                    $pageButton = get_page_button(event.target),
                    $submitButton = $(event.target),
                    search_field_error = $submitButton.closest('.' + PDb_ajax.prefix + 'searchform').find('.search_field_error'),
                    value_error = $submitButton.closest('.' + PDb_ajax.prefix + 'searchform').find('.value_error');
            
            submission.submit = $submitButton.data('submit');

            switch (submission.submit) {

              case 'search':
                submission.listpage = '1';
                if ($('[name^="search_field"]').PDb_checkInputs('none')) {
                  search_field_error.show();
                  isError = true;
                }
                if ($('[name^="value"]').PDb_checkInputs('')) {
                  value_error.show();
                  isError = true;
                }
                if (isError) {
                  errormsg.show();
                  return;
                }
                if (remote) {
                  $submitButton.closest('form').submit();
                  return
                }
                break;
                
              case 'clear':
                clear_search($submitButton);
                submission.listpage = '1';
                break;
              
              case 'page':
                submission.listpage = $pageButton.data('page');
                break;
                
              case 'sort':
                break;
                
              default:
                return;
            }
            $submitButton.PDb_processSubmission();
            // trigger a general-purpose event
            $('html').trigger('pdbListFilterComplete');
          },
          get_page_button = function (target) {
            var $button = $(target);
            if ($button.is('a')) return $button;
            return $button.closest('a');
          },
          submit_remote_search = function(event) {
            submit_search(event, true);
          },
          get_page = function(event) {
            $(event.target).data('submit', 'page');
            submit_search(event);
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
          },
          add_value_to_submission = function(el,submission) {
    var
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
          },
          post_submission = function (button) {
    var
            target_instance = $('.pdb-list.pdb-instance-' + submission.instance_index),
            container = target_instance.length ? target_instance : $('.pdb-list').first(),
						pagination = container.find('.pagination'),
						buttonParent = button.parent('fieldset, div'),
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
								replacePagination = newContent.find('.pagination'),
                replaceContent = newContent.find('.list-container').length ? newContent.find('.list-container') : newContent;
        newContent.PDb_idFix();
        replaceContent.find('a.obfuscate[data-email-values]').each(function() {
          $(this).PDb_email_obfuscate();
        });
        container.find('.list-container').replaceWith(replaceContent);
				 if (replacePagination.length) {
					 if (pagination.length) {
						 pagination.each( function(i) { 
							 $(this).replaceWith(replacePagination.get(i));
						 } );
					} else {
						container.find('.list-container').after(replacePagination);
					}
        }
        spinner.remove();
      },
      error: function(jqXHR, status, errorThrown) {
        console.log('Participants Database JS error status:' + status + ' error:' + errorThrown);
      }
    });
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
  $.fn.PDb_processSubmission = function() {
    // collect the form values and add them to the submission
    filterform.find('input:not(input[type="submit"],input[type="radio"]), select').each(function() {
      add_value_to_submission($(this),submission);
    });
    filterform.find('input[type="radio"]:checked').each(function() {
      add_value_to_submission($(this),submission);
    });
    post_submission(this);
  };
  return {
    run: function() {

      compatibility_fix();

      clear_error_messages();

      filterform.on('click', '[type="submit"]', submit_search);
      remoteform.on('click', '[type="submit"]', submit_remote_search);
      $('.pdb-list').on('click', '.pdb-pagination a', get_page);
    }
  };
}(jQuery));
jQuery(function() {
  "use strict";
  PDbListFilter.run();
});