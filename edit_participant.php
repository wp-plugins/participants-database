<?php 
// submission processing happens in Participants_Db::process_page_request on the admin_init action
//
// this file is called by the admin, also by the sortcode [edit_record]
//

if ( ! isset( $participant_id ) ) {
  // if there is no id in the request, use the default record
  $participant_id = isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : Participants_Db::$id_base_number;
}

if ( $participant_id == Participants_Db::$id_base_number ) {
	
  $action = 'insert';
  $page_title = __('Add New Participant Record', Participants_Db::PLUGIN_NAME );
	
} else {
	
  $action = 'update';
  $page_title = __('Edit Existing Participant Record', Participants_Db::PLUGIN_NAME );
	
}

// get the participant information
// and run the rest of the script if the id is valid
// if this returns false, we have an invlaid ID; do nothing
if ( $participant_values = Participants_Db::get_participant( $participant_id ) ) :

if ( $participant_id == Participants_Db::$id_base_number ) $participant_values = Participants_Db::set_initial_record($participant_values);

//get the groups info
$groups = Participants_Db::get_groups();

// get the current user's info
get_currentuserinfo();

$options = get_option( self::$participants_db_options );

$section = '';
?>
<div class="wrap edit-participant">
<?php if ( is_admin() ) : ?><h2><?php echo $page_title?></h2><?php endif ?>
<?php
if ( is_object( Participants_Db::$validation_errors ) ) {
	
	echo Participants_Db::$validation_errors->get_error_html();
	
}

?>
<form method="post" action="<?php echo $_SERVER['REQUEST_URI']?>" enctype="multipart/form-data" >
	<?php 
	FormElement::print_hidden_fields( array(
																					'action' => $action, 
																					'id' => ( isset( $participant_values[ 'id' ] ) ? $participant_values[ 'id' ] : $participant_id ),
																					'private_id' => $participant_values[ 'private_id' ],
																					'source' => Participants_Db::PLUGIN_NAME,
																					) );
																					
	// get the columns and output form
	$type = is_admin() ? 'backend' : 'frontend';
	$readonly_columns = Participants_Db::get_readonly();
	foreach ( Participants_db::get_column_atts( $type ) as $column ) :

    $id_line = '';
		
		// set a new section
		if ( $column->group != $section ) {
		  if ( ! empty( $section ) ) {
?>
</table>
<?php
		  } elseif ( Participants_Db::backend_user() ) {
        $id_line = '<tr><th>'._x('ID','abbreviation for "identification"',Participants_Db::PLUGIN_NAME).'</th><td>'.($participant_id == Participants_Db::$id_base_number ? _x('(new record)','indicates a new record is being entered',Participants_Db::PLUGIN_NAME) : $participant_id ).'</td></tr>';
		  }
		  $section = $column->group
?>
<h3><?php echo $groups[$section]['title']?></h3>
<?php if ( $options['show_group_descriptions'] ) echo '<p class="'.Participants_Db::$css_prefix.'group-description">'.$groups[$section]['description'].'</p>' ?>
<table class="form-table">
<?php
		  
		}
    echo $id_line;
?>

	<tr class="<?php echo ( is_admin() && 'hidden' == $column->form_element ) ? 'text-line' : $column->form_element ?>">
    <?php
    $column_title = htmlspecialchars(stripslashes($column->title),ENT_QUOTES,"UTF-8",false);
    if ( $options['mark_required_fields'] && $column->validation != 'no' ) {
      $column_title = sprintf( $options['required_field_marker'], $column_title );
    }
    ?>
		<th><?php echo $column_title . ( ( Participants_Db::backend_user() && 'hidden' == $column->form_element ) ? ' (hidden)' : '' ) ?></th>
		<td id="<?php echo Participants_Db::$css_prefix.$column->name?>" >
		<?php
		
		$readonly = in_array( $column->name, $readonly_columns )  ? array( 'readonly' => 'readonly' ) : NULL;

		// get the existing value if any
		$value = isset( $participant_values[ $column->name ] ) ? Participants_Db::unserialize_array( $participant_values[ $column->name ] ) : '';
		
		// replace it with the new value if provided
		if ( isset( $_POST[ $column->name ] ) ) {
			
			if ( is_array( $_POST[ $column->name ] ) ) $value = $_POST[ $column->name ];
			
			elseif  ( Participants_Db::backend_user() && 'textarea' == $column->form_element && $options['rich_text_editor'] ) $value = $_POST[ $column->name ];

			else $value = esc_html( stripslashes( $_POST[ $column->name ] ) );
			
		}
		
		if ( isset( $value ) ) {
			
			switch ( $column->form_element ) {
			// format the date if it's a date field
				case 'date':
				
					if ( ! empty( $value ) ) {
					
						$value = date( get_option( 'date_format' ), Participants_Db::parse_date( $value ) );
						
					}
					
					break;
				
				case 'image-upload':
				
					$value = empty( $value ) ? '' : Participants_Db::get_image_uri( $value );
					
					break;
					
				case 'multi-select-other':
				case 'multi-checkbox':
				
					$value = is_array( $value ) ? $value : explode( ',', $value );
					
					break;
					
				case 'hidden':
				
					if ( Participants_Db::backend_user() ) {
						
						// for backend user this field is exposed and editable
						$column->form_element = 'text-line';
						
					} else {
				
						global $post, $current_user;
				
						if ( false !== strpos( html_entity_decode($value), '->' ) ) {
							
							list( $object, $property ) = explode( '->', html_entity_decode($value) );
							
							$object = ltrim( $object, '$' );
							
							$value = isset( $$object->$property )? $$object->$property : $value;
							
						}
						
					}
					
			}
			
		}

		if ( Participants_Db::backend_user() && 'textarea' == $column->form_element && $options['rich_text_editor'] ) {

      wp_editor(
                $value,
                preg_replace( '#[0-9_-]#', '', Participants_Db::$css_prefix.$column->name ),
                array(
                      'media_buttons' => false,
                      'textarea_name' => $column->name,
                      'editor_class'  => ( $column->validation != 'no' ? "required-field" : '' ),
                      )
                );
                
    } else {
    
		FormElement::print_element( array(
																			'type'       => $column->form_element,
																			'value'      => $value,
																			'name'       => $column->name,
																			'options'    => $column->values,
                                      'class'      => ( $column->validation != 'no' ? "required-field" : '' ),
																			'attributes' => $readonly,
																			) );

    }
    
		if ( ! empty( $column->help_text ) ) :
			?>
			<span class="helptext"><?php echo trim( $column->help_text )?></span>
			<?php endif; ?>
		</td>
	 </tr>
	 <?php

		  
		
	endforeach;
	?>
	</table>
  <table class="form-table">
    <?php if ( is_admin() ) : ?>
    <tr>
      <th><h3><?php _e('Save the Record', Participants_Db::PLUGIN_NAME )?></h3></th>
      <td class="submit-buttons"><input class="button-primary" type="submit" value="Submit" name="submit">
        <input class="button-primary" type="submit" value="Apply" name="submit">
        <input class="button-primary" type="submit" value="Next" name="submit">
      </td>
    </tr>
    <tr>
      <td colspan="2"><?php _e('<strong>Submit:</strong> save record and return to list<br><strong>Apply:</strong> save record and continue with same record<br><strong>Next:</strong> save record and then start a new one', Participants_Db::PLUGIN_NAME )?> </td>
    </tr>
    <?php else : ?>
    <tr>
      <th><h3><?php echo $options['save_changes_label']?></h3></th>
      <td class="submit-buttons">
        <input class="button-primary pdb-submit" type="submit" value="<?php echo $options['save_changes_button']?>" name="save">
        <input name="submit" type="hidden" value="Apply">
      </td>
    </tr>
    <?php endif; ?>
  </table>
</form>
</div>
<?php endif?>
<?php /* ?>
<script type="text/javascript">
jQuery(document).ready( function($) {
		$.datepicker.setDefaults({
														  dateFormat : '<?php echo Participants_Db::get_jqueryUI_date_format() ?>'
														 });
		$( ".edit-participant input.date_field" ).each( function() {
			var datefield = $(this);
			var fieldname = datefield.attr('name');
			datefield.datepicker({
        changeMonth: true,
        changeYear: true
      });
		});
});
</script>
<?php */ ?>