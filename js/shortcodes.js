// frontend scrips for PDB plugin
jQuery(document).ready( function($) {
				$('input.pdb-submit').click(function(e){
																								 $(this).attr("disabled", true);
																								 $(this).css("opacity",0.5);
																								 });
				
			});