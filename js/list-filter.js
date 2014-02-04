/*
 * Participants Database Plugin
 * 
 * version: 0.5
 * 
 * xnau webdesign xnau.com
 * 
 * handles AJAX list filtering and sorting
 */
jQuery(document).ready(function($) {
  "use strict";

  // this is for backwards compatibility, but it certainly won't work if there are two lists on a page using the old HTML
  idFix($('.wrap.pdb-list'));

  if (typeof PDb_ajax.prefix === 'undefined')
    PDb_ajax.prefix = 'pdb-';
  var
          isError,
          errormsg = $('.pdb-searchform .pdb-error'),
          filterform = $('.sort_filter_form'),
          submission = {};

  clear_error_messages();

  submission.filterNonce = PDb_ajax.filterNonce;
  submission.postID = PDb_ajax.postID;

  if (filterform.attr('ref') === 'update') {
    filterform.on('click', 'input[type="submit"]', function(event) {
      if (event.preventDefault) {
        event.preventDefault();
      } else {
        event.returnValue = false;
      }
      clear_error_messages();
      // validate and process form here
      var
              submitButton = event.target,
              search_field_error = $(this).closest('.' + PDb_ajax.prefix + 'searchform').find('.search_field_error'),
              value_error = $(this).closest('.' + PDb_ajax.prefix + 'searchform').find('.value_error');
      //container.data('target', 'container').find('.list-container');
      submission.submit = submitButton.value;
      if (submitButton.value === PDb_ajax.i18n.search) {
        if ($('select[name="search_field"]').val() === "none") {
          search_field_error.show();
          isError = true;
        }
        if (!$('input[name="value"]').val()) {
          value_error.show();
          isError = true;
        }
        if (isError) {
          errormsg.show();
        }
      } else if (submitButton.value === PDb_ajax.i18n.clear) {
        $('select[name="search_field"]').val('none');
        $('input[name="value"]').val('');
        clear_error_messages();
      }
      if (!isError) {
        filterform.find('input:not(input[type="submit"],input[type="radio"]), select').each(function() {
          submission[this.name] = encodeURI($(this).val());
        });
        filterform.find('input[type="radio"]:checked').each(function() {
          submission[this.name] = encodeURI($(this).val());
        });
        //console.log(submission);
        var
                target_instance = $('.pdb-list.pdb-instance-' + submission.instance_index),
                container = target_instance.length ? target_instance : $('.pdb-list').first(),
                spinner = $(PDb_ajax.loading_indicator);
        $.ajax({
          type: "POST",
          url: PDb_ajax.ajaxurl,
          data: submission,
          beforeSend: function() {
            $(submitButton).parent().append(spinner.clone());
          },
          success: function(html, status) {
            var newContent = $(html);
            idFix(newContent);
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
              $(submitButton).parent().find('.ajax-loading').remove();
          },
          error: function(jqXHR, status, errorThrown) {
            console.log('Participants Database JS error status:' + status + ' error:' + errorThrown);
          }
        });
      }
    });
  } else {
    filterform.on('click', 'input[type="submit"]', function(event) {
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
    errormsg.children().hide();
    isError = false;
  }
  function idFix(content) {
    content.find('#pdb-list').addClass('list-container').removeAttr('id');
    content.find('#sort_filter_form').addClass('sort_filter_form').removeAttr('id');
  }
});