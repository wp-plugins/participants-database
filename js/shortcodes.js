// frontend scrips for PDB plugin
//
// functionality added here:
//   disable submit after submit to prevent multiple submits
//   perform email obfuscation if enabled
//   form element placeholders
//
jQuery(document).ready( function($) {
		var pdbform = $('input.pdb-submit').parents("form");
		pdbform.submit(function(e) {
    if ($(this).hasClass('pdb-disabled')) {
      e.preventDefault(); return false;
    }
    $('input[placeholder], textarea[placeholder]').each( function() {
      clear_placeholder($(this));
    });
        				$(this).addClass('pdb-disabled');
        			return true;
        		});	
        $('a.obfuscate[rel]').each( function() {
          var address = $.parseJSON( $(this).attr('rel') );
          var link = ''.concat( address.name,'@',address.domain );
          $(this).attr('href', 'mailto:'+link ).html( link ).attr('class','obfuscated');
        });
  $('input[placeholder], textarea[placeholder]').each( function() {
    var el = $(this);
    if (''==el.val()) el.val(el.attr('placeholder'));
    el.focus(function(){
      console.log('selected:'+$(this).context);
      clear_placeholder($(this));
    });
  });
  function clear_placeholder(el){
    if (el.val()==el.attr('placeholder')) {
      el.val('');
    }
  }
			});