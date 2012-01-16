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

$section = '';
?>
<div class="wrap edit-participant">
<h2><?php echo $page_title?></h2>
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
        $id_line = '<tr><th>ID</th><td>'.($participant_id == Participants_Db::$id_base_number ? '(new record)' : $participant_id ).'</td></tr>';
		  }
		  $section = $column->group
?>
<h3><?php echo $groups[$section]['title']?></h3>
<table class="form-table">
<?php
		  
		}
    echo $id_line;
?>

	<tr class="<?php echo $column->form_element?>">
		<th><?php echo htmlspecialchars(stripslashes($column->title),ENT_QUOTES,"UTF-8",false)?></th>
		<td id="<?php echo $column->name?>">
		<?php
		
		$readonly = in_array( $column->name, $readonly_columns )  ? array( 'readonly' => 'readonly' ) : NULL;

		$value = isset( $participant_values[ $column->name ] ) ? Participants_Db::unserialize_array( $participant_values[ $column->name ] ) : '';
		$value = ( isset( $_POST[ $column->name ] ) ? $_POST[ $column->name ] : $value );
		$value = ( 'date' == $column->form_element ? date( get_option( 'date_format' ).' '.get_option( 'time_format' ), strtotime( $value ) ) : $value );
		// 
		FormElement::print_element( array(
																			'type'       => $column->form_element,
																			'value'      => $value,
																			'name'       => $column->name,
																			'options'    => $column->values,
																			'attributes' => $readonly,
																			) );
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
      <th><h3>Save the Record</h3></th>
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
      <th><h3><?php _e('Save Your Changes', Participants_Db::PLUGIN_NAME )?></h3></th>
      <td class="submit-buttons">
        <input class="button-primary" type="submit" value="Save" name="save">
        <input name="submit" type="hidden" value="Apply">
      </td>
    </tr>
    <?php endif; ?>
  </table>
</form>
</div>
<?php endif?>