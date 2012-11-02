<?php
/*
 * default template for displaying a single record
 *
 * each group with the "visible" attribute checked will display its fields in the order set 
 * in the manage database fields page.
 *
 *
 * if there are specific fields you wish to exclude from display, you can include the "name" value of 
 * the field in the $exclude array like this: $exclude = array( 'city','state','country' ); or whatever 
 * you want. Leave it empty (like it is here) if you don't want to exclude any fields.
 *
 * this template is a simple demonstration of what is possible
 *
 * for those unfamiliar with PHP, just remember that something like <?php echo $group->name ?> just prints out 
 * the group name. You can move it around, but leave all the parts between the <> brackets as they are.
 *
 */

// define an array of fields to exclude here
$exclude = array();

?>

<div class="wrap <?php echo $this->wrap_class ?>">

	
  <?php while ( $this->have_groups() ) : $this->the_group(); ?>
  
  <div class="section" id="<?php echo $this->group->name ?>">
  
    <?php $this->group->print_title( '<h2>', '</h2>' ) ?>
    
    <?php $this->group->print_description( '<p>', '</p>' ) ?>
    
    <dl>
    
      <?php while ( $this->have_fields() ) : $this->the_field();
      
          // skip any field found in the exclude array
          if ( in_array( $this->field->name, $exclude ) ) continue;
					
          // CSS class for empty fields
					$empty_class = empty( $this->field->value ) ? 'blank-field' : '';
      
      ?>
      
      <dt class="<?php echo $this->field->name.' '.$empty_class?>"><?php $this->field->print_label() ?></dt>
      
      <dd class="<?php echo $this->field->name.' '.$empty_class?>"><?php $this->field->print_value() ?></dd>
  
    	<?php endwhile; // end of the fields loop ?>
    
    </dl>
    
  </div>
  
  <?php endwhile; // end of the groups loop ?>
  
</div>