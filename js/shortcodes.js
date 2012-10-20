// frontend scrips for PDB plugin
jQuery(document).ready( function($) {
				var pdbform = $('input.pdb-submit').parents("form");
				pdbform.submit(function(e) {
        			if ($(this).hasClass('pdb-disabled')) { e.preventDefault(); return false; }
        				$(this).addClass('pdb-disabled');
        			return true;
        		});	
        $('a.obfuscate[rel]').each( function() {
          var address = $.parseJSON( $(this).attr('rel') );
          var link = ''.concat( address.name,'@',address.domain );
          $(this).attr('href', 'mailto:'+link ).html( link ).attr('class','obfuscated');
        });
			});