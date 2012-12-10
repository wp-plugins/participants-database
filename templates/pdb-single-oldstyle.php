<?php
/*
 * original-style template for displaying a single record
 * 
 * this template is provided for backwards compatibility, it is best to use the 
 * new style of template for better consistency across upgrades, it will also be 
 * more consistent with the other templates the plugin uses
 *
 * each group with the "visible" attribute checked will display its fields in 
 * the order set in the manage database fields page.
 *
 * each group has three values you can use: 
 *    $group->name         a unique string suitable for classnames and such 
 *    $group->title        the group name to be displayed 
 *    $group->description  contains any extra text you want to add to each group
 *
 * each field has three values:
 *    $field->name         like $group->name it's good for precise control over 
 *                         your display using CSS selectors
 *    $field->title        makes a good display title for the field
 *    $field->form_element tells you what kind of data the field contains so you 
 *                         can display it accordingly.
 *    $field->value        is the value of the field
 *
 * the values should be processed through a function called 
 * "Participants_Db::prep_field_for_display" that will convert the stored raw 
 * values into displayable strings using the form_element info to control how it 
 * alters the raw values. I left the function call in the template so you can 
 * process the values yourself if you want
 *
 * if there are specific fields you wish to exclude from display, you can include 
 * the "name" value of the field in the $exclude array like this: 
 * $exclude = array( 'city','state','country' ); or whatever you want. Leave it 
 * empty (like it is here) if you don't want to exclude any fields.
 *
 * this template is a simple demonstration of what is possible
 *
 * for those unfamiliar with PHP, just remember that something like 
 * <?php echo $group->name ?> just prints out the group name. You can move it 
 * around, but leave all the parts between the <> brackets as they are.
 *
 */

// define an array of fields to exclude here
$exclude = array();

?>

<div class="wrap edit-participant <?php echo $this->wrap_class ?>">

	
  <?php // display each data group
  foreach ( Participants_Db::single_record_fields( $id, $exclude ) as $group ) :
  ?>
  
  <div class="section" id="<?php echo $group->name ?>">
  
    <h2><?php echo $group->title ?></h2>
    
    <?php if ( ! empty( $group->description ) ) : ?>
    
    <h3><?php echo $group->description ?></h3>
     
    <?php endif ?>
    
      <?php // this prints out all the fields in the group
			foreach( $group->fields as $field ) : 
              
          $value = Participants_Db::prep_field_for_display( $field->value, $field->form_element );
					
					$empty_class = empty( $value ) ? 'blank-field' : '';
      
      ?>
    
    <dl>
      
      <dt class="<?php echo $field->name.' '.$empty_class?>"><?php echo $field->title ?></dt>
      
      <dd class="<?php echo $field->name.' '.$empty_class?>"><?php echo $value ?></dd>
      
    </dl>
  
    	<?php endforeach; // end of the fields loop ?>
    
    
  </div>
  
  <?php endforeach; // end of the groups loop ?>
  
</div>