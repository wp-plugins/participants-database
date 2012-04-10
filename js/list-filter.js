jQuery(document).ready( function($) {
																	 
	var errormsg = $('.pdb-searchform .pdb-error');
	
	errormsg.hide();
	errormsg.children('p').hide();
	
	var filterform = $('#sort_filter_form');
	
	filterform.children('input[name="action"]').val('pdb_list_filter');
	
  var submission = {};
  submission.filterNonce=PDb_ajax.filterNonce;
  submission.postID=PDb_ajax.postID;
	
	filterform.find('input[name!="submit"], select').each( function() {
		
		submission[this.name]=escape($(this).val());
		
	});
	
	//console.log( 'submission JSON='+submission );
	
	filterform.find('input[name="submit"]').click(function(e) {

    e.preventDefault();
		// validate and process form here
		var errormsg=$('.pdb-error');

		errormsg.hide();
		errormsg.children('p').hide();
		if ( $(this).val() == "Search" ) {
			if ($('select[name="where_clause"]').val() == "none") {
				$("#where_clause_error").show();
				errormsg.show();
				$("#where_clause").focus();
				return false;
			}
			if ($('input[name="value"]').val() == "") {
				$("#value_error").show();
				errormsg.show();
				$("#value").focus();
				return false;
			}
		}

		$.ajax({
			type: "POST",
			url: PDb_ajax.ajaxurl,
			data: submission,
			success: function(html,status) {
				console.log('status '+status+': printing response');
				$('#pdb-show-records').html(html);
			},
			error:function(jqXHR,status,errorThrown){
        console.log('error status:'+status+' error:'+errorThrown);
      }
		});

	});
	return false;

});