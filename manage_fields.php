<?php
/*
 * add / edit / delete fields and field groups and their attributes 
 */
// translations strings for buttons
/* translators: these strings are used in logic matching, please test after translating in case special characters cause problems */
global $PDb_i18n;
$PDb_i18n = array(
  'update fields'   => __( 'Update Fields', Participants_Db::PLUGIN_NAME ),
  'update groups'   => __( 'Update Groups', Participants_Db::PLUGIN_NAME ),
  'add field'       => __( 'Add Field',     Participants_Db::PLUGIN_NAME ),
  'add group'       => __( 'Add Group',     Participants_Db::PLUGIN_NAME ),
	'new field title' => __( 'new field title',     Participants_Db::PLUGIN_NAME ),
	'new group title' => __( 'new group title',     Participants_Db::PLUGIN_NAME ),
  );
// process form submission
$error_msgs = array();
if ( isset( $_POST['action'] ) ) {
	
	switch ( $_POST['action'] ) {
		
		case 'reorder_fields':
			unset( $_POST['action'], $_POST['submit'] );
			foreach( $_POST as $key => $value ) {
				$wpdb->update( Participants_Db::$fields_table, array( 'order' => $value ), array( 'id' => str_replace( 'row_','',$key ) ) );
			}
			break;

    case 'reorder_groups':
      unset( $_POST['action'], $_POST['submit'] );
      foreach( $_POST as $key => $value ) {
        $wpdb->update( Participants_Db::$groups_table, array( 'order' => $value ), array( 'name' => str_replace( 'order_','',$key ) ) );
      }
      break;
			
		case $PDb_i18n['update fields']:

			// dispose of these now unneeded fields
			unset( $_POST['action'], $_POST['submit'] );
			
			foreach( $_POST as $name => $row ) {
				
				// skip all non-row elements
				if ( false === strpos( $name, 'row_' ) ) continue;

				if ( $row['status'] == 'changed' ) {
					
					$id = $row['id'];
					
					if ( ! empty( $row['values'] ) ) {
						
						$row['values'] = serialize( PDb_prep_values_array( explode( ',', $row['values'] ) ) );
						
					}
					
					if ( ! empty( $row['validation'] ) && ! in_array( $row['validation'], array( 'yes','no' ) ) ) {
						
						$row['validation'] = str_replace( '\\\\', '\\', $row['validation'] );
						
					}
					
					// modify the datatype if necessary
					if ( isset( $row['group'] ) && $row['group'] != 'internal' ) {
            $sql = "SHOW FIELDS FROM ".Participants_Db::$participants_table." WHERE field = '".$row['name']."'";
            $field_info = $wpdb->get_results( $sql );
            $new_type = strtolower( Participants_Db::set_datatype( $row['form_element'] ) );
            if ( $new_type != current($field_info)->Type ) {

              $sql = "ALTER TABLE ".Participants_Db::$participants_table." MODIFY COLUMN `".$row['name']."` ".$new_type;

              $result = $wpdb->get_results( $sql );

            }
          }
					
					
					// remove the fields we won't be updating
					unset( $row['status'],$row['id'],$row['name'] );
					$wpdb->update( Participants_Db::$fields_table, $row, array( 'id'=> $id ) );
					
				}

			}
		
			// load the default record with the default values
			Participants_Db::update_default_record();
			
			break;

    case $PDb_i18n['update groups']:

      // dispose of these now unneeded fields
      unset( $_POST['action'], $_POST['submit'], $_POST['group_title'], $_POST['group_order'] );

      foreach( $_POST as $name => $row ) {

					// make sure name is legal
					$row['name'] = PDb_make_name( $row['name'] );

          $wpdb->update( Participants_Db::$groups_table, $row, array( 'name'=> $name ) );

      }
      break;

		// add a new blank field
		case $PDb_i18n['add field']:

			// use the wp function to clear out any irrelevant POST values
			$atts = shortcode_atts( array( 
																		'name'  => PDb_make_name( stripslashes($_POST['title']) ),
																		'title' => htmlspecialchars( stripslashes($_POST['title']),ENT_QUOTES, "UTF-8", false ),//PDb_prep_title($_POST['title']),  
																		'group' => $_POST['group'],
																		'order' => $_POST['order'],
																		'validation' => 'no',
																		),
														 $_POST
														 );
			$result = Participants_Db::add_blank_field( $atts );
			if ( false === $result ) $error_msgs[] = PDb_parse_db_error( $wpdb->last_error, $_POST['action'] );
			break;

		// add a new blank field
		case $PDb_i18n['add group']:

			global $wpdb;
			$wpdb->hide_errors();
			
			$atts = array(
										'name'  => PDb_make_name( $_POST['group_title'] ),
										'title' => htmlspecialchars( stripslashes($_POST['group_title']),ENT_QUOTES, "UTF-8", false ),
										'order' => $_POST['group_order'],
			);
														 
			$wpdb->insert( Participants_Db::$groups_table, $atts );

			if ( $wpdb->last_error ) $error_msgs[] = PDb_parse_db_error( $wpdb->last_error, $_POST['action'] );
			break;
			
		case 'delete_field':

			global $wpdb;
			$wpdb->hide_errors();
	
			$result = $wpdb->query('
				DELETE FROM '.Participants_Db::$fields_table.'
				WHERE id = '.$wpdb->escape($_POST['delete'])
			);
			
			break;

		case 'delete_group':

			global $wpdb;
			//$wpdb->hide_errors();

			$group_count = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM '.Participants_Db::$fields_table.' WHERE `group` = "' . $_POST['delete'].'"' ) );

			if ( $group_count == 0 ) $result = $wpdb->query('
																												DELETE FROM '.Participants_Db::$groups_table.'
																												WHERE `name` = "'.$wpdb->escape($_POST['delete']).'"'
																											);

			break;
			
		default:
		
	}
	
}

// get the defined groups
$groups = Participants_Db::get_groups( 'name' );
// remove the internal group
//unset( $groups[ array_search( 'internal', $groups ) ] );
$attribute_columns = array();

// get an array with all the defined fields
foreach( $groups as $group ) {
	
	// only display these columns for internal group
	$select_columns = ( $group === 'internal' ? '`id`,`order`,`name`,`title`,`admin_column`,`sortable`,`CSV`' : '*' );
	
	$sql = "SELECT $select_columns FROM " . Participants_Db::$fields_table . ' WHERE `group` = "'.$group.'" ORDER BY `order` ';
	$database_rows[$group] =  $wpdb->get_results( $sql, ARRAY_A );
	
	// get an array of the field attributes
	$attribute_columns[$group] = $wpdb->get_col_info( 'name' );
	
	// remove read-only fields
	foreach( array( 'id'/*,'name'*/ ) as $item ) {
		unset( $attribute_columns[$group][ array_search( $item, $attribute_columns ) ] );
	}
	
}
// this script updates a hidden field when a row is edited so we don't have to update the whole database on submit
// second bit disables the submit on return behavior
?>
<div class="wrap">
<h2><?php echo Participants_Db::$plugin_title?></h2>
<h3><?php _e('Manage Database Fields',Participants_Db::PLUGIN_NAME )?></h3>
<?php
if ( ! empty( $error_msgs ) ) :
?>
<div class="error settings-error">
<?php 
error_log( __FILE__.' errors registered:'.print_r( $error_msgs,true ));
foreach ( $error_msgs as $error ) echo '<p>'.$error.'</p>'; ?>
</div>
<?php endif?>
<h4><?php _e('Field Groups',Participants_Db::PLUGIN_NAME )?>:</h4>
<div id="fields-tabs">
	<ul>
		<?php
		foreach ( $groups as $group ) {
			echo '<li><a href="#'.$group.'" id="tab_'.$group.'">'.ucwords( str_replace( '_',' ',$group ) ).'</a></li>';
		}
		echo '<li><a href="#field_groups">'.__('Field Groups',Participants_Db::PLUGIN_NAME ).'</a></li>';
    echo '<li><a href="#help">'.__('Help',Participants_Db::PLUGIN_NAME ).'</a></li>';
		?>
	</ul>
	<?php
	foreach ( $groups as $group ) :
	?>
	<div id="<?php echo $group?>" class="manage-fields-wrap" >
		<form id="manage_<?php echo $group?>_fields" method="post">
		<h3><?php echo ucwords( str_replace( '_',' ',$group ) ),' ', __('Fields',Participants_Db::PLUGIN_NAME )?></h3>
		<p>
		<?php
		if ( 'internal' !== $group ) :
		// "add field" functionality
		FormElement::print_element( array( 'type'=>'submit','value'=>$PDb_i18n['add field'],'name'=>'action', 'attributes'=>array( 'class'=>'add_field_button', 'onclick'=>'return false;') ) );
		FormElement::print_element( array( 'type'=>'text', 'name'=>'title','value'=>$PDb_i18n['new field title'].'&hellip;','attributes'=>array('onfocus'=>"this.value='';jQuery(this).prev('input').removeAttr( 'onclick' )",'class'=>'add_field') ) );
		
		endif; // skip internal groups
		
		// number of rows in the group
		$num_group_rows = count( $database_rows[ $group ] );
		
		$last_order = $num_group_rows > 1 ? $database_rows[ $group ][ $num_group_rows -1 ]['order']+1 : 1;
		
		FormElement::print_hidden_fields( array( 'group'=>$group, 'order'=>$last_order ) );
		
		?>
		</p>
		<table class="wp-list-table widefat fixed manage-fields" cellspacing="0" >
		<thead>
			<tr>
      <?php if ( 'internal' !== $group ) : ?>
				<th scope="col" class="delete vertical-title"><span><?php echo PDb_header( 'delete' ) ?></span></th>
			<?php
			endif; // internal group test
			
			foreach( $attribute_columns[ $group ] as $attribute_column ) {
				
				if ( 'internal' == $group && in_array( $attribute_column, array( 'order' ) ) ) continue;
				
				$column_class = in_array( $attribute_column, array( 'order', 'persistent', 'sortable', 'admin_column', 'display_column', 'CSV', 'signup', 'display' ) ) ? $attribute_column.' vertical-title' : $attribute_column;
				
				?>
				<th scope="col" class="<?php echo $column_class?>"><span><?php echo PDb_header( $attribute_column ) ?></span></th>
				<?php
			}
			?>
			</tr>
		</thead>
		<tbody id="<?php echo $group?>_fields">
			<?php
			if ( $num_group_rows < 1 ) { // there are no rows in this group to show
			?>
			<tr><td colspan="<?php echo count( $attribute_columns[ $group ] ) + 1 ?>"><?php _e('No fields in this group',Participants_Db::PLUGIN_NAME )?></td></tr>
			<?php
			} else {
				// add the rows of the group
				foreach( $database_rows[$group] as $database_row ) :
				?>
				<tr id="db_row_<?php echo $database_row[ 'id' ]?>">
        	<?php if ( 'internal' !== $group ) : ?>
					<td>
					<?php
					endif; // hidden field test
					// add the hidden fields
					foreach( array( 'id'/*,'name'*/ ) as $attribute_column ) {
	
						$value = Participants_Db::prepare_value( $database_row[ $attribute_column ] );
	
						$element_atts = array_merge( Participants_Db::get_edit_field_type( $attribute_column ),
																					array(
																								'name'=>'row_'.$database_row[ 'id' ].'['.$attribute_column.']',
																								'value'=> $value,
																								) );
						FormElement::print_element( $element_atts );
	
					}
					FormElement::print_element( array(
																'type' => 'hidden',
																'value' => '',
																'name'=>'row_'.$database_row[ 'id' ].'[status]',
																'attributes' => array( 'id'=>'status_'.$database_row[ 'id' ] ),
																) );
					if ( 'internal' !== $group ) : ?>
						<a href="#" name="delete_<?php echo $database_row[ 'id' ]?>" class="delete" ref="field"></a>
					</td>
					<?php
					endif;// internal group test
	
					// list the fields for editing
					foreach( $attribute_columns[$group] as $attribute_column ) :
					
						if ( 'internal' == $group && in_array( $attribute_column, array( 'order' ) ) ) continue;
						
						// preserve backslashes in regex expressions
						if ( $attribute_column == 'validation' ) $database_row[ $attribute_column ] = str_replace( '\\','&#92;',$database_row[ $attribute_column ] );
	
						$value = Participants_Db::prepare_value( $database_row[ $attribute_column ] );
	
						$element_atts = array_merge( Participants_Db::get_edit_field_type( $attribute_column ),
																					array(
																								'name'=>'row_'.$database_row[ 'id' ].'['.$attribute_column.']',
																								'value'=> PDb_prep_value( $value, (bool) ( $attribute_column == 'validation' ) ),
																								) );
					?>
					<td class="<?php echo $attribute_column?>"><?php FormElement::print_element( $element_atts )  ?></td>
					<?php
					endforeach; // columns
					?>
				</tr>
				<?php
				endforeach; // rows
			} // num group rows ?>
		</tbody>
		</table>
		<?php // this javascript handles the drag-reordering of fields ?>
		<script type="text/javascript">
		jQuery(document).ready(function($){
			$("#<?php echo $group?>_fields").sortable({
				helper: fixHelper,
				update: function(event, ui){
					var order = serializeList( $(this) );
					$.ajax({
						url: "<?php echo $_SERVER['REQUEST_URI']?>",
						type: "POST",
						data: order + '&action=reorder_fields'
						});
					}
				});
		});
		</script>
		<p class="submit">
			<?php
			FormElement::print_element( array('type'=>'submit', 'name'=>'action','value'=>$PDb_i18n['update fields'], 'class'=>'button-primary') );
			?>
		</p>
		</form>
	</div><!-- tab content container -->
	<?php
	endforeach; // groups
	
	// build the groups edit panel
	$groups = Participants_Db::get_groups( '`order`,`display`,`name`,`title`,`description`' );
	?>
	<div id="field_groups" class="manage-fields-wrap">
		<form id="manage_field_groups" method="post">
		<input type="hidden" name="action" value="<?php echo $PDb_i18n['update groups']?>" />
		<h3><?php _e('Edit / Add / Remove Field Groups',Participants_Db::PLUGIN_NAME )?></h3>
		<p>
		<?php
		
		// "add group" functionality
		FormElement::print_element( array( 'type'=>'submit','value'=>$PDb_i18n['add group'],'name'=>'action', 'attributes'=>array( 'class'=>'add_field_button', 'onclick'=>'return false;' ) ) );
		FormElement::print_element( array( 'type'=>'text', 'name'=>'group_title','value'=>$PDb_i18n['new group title'].'&hellip;','attributes'=>array('onfocus'=>"this.value='';jQuery(this).prev('input').removeAttr( 'onclick' )",'class'=>'add_field') ) );
		$next_order = count( $groups ) + 1;
		FormElement::print_hidden_fields( array( 'group_order'=>$next_order ) );
		
		?>
		</p>
		<table class="wp-list-table widefat fixed manage-fields manage-field-groups" cellspacing="0" >
		<thead>
			<tr>
				<th scope="col" class="fields vertical-title"><span><?php echo PDb_header( __('fields',Participants_Db::PLUGIN_NAME ) ) ?></span></th>
        <th scope="col" class="delete vertical-title"><span><?php echo PDb_header( __('delete',Participants_Db::PLUGIN_NAME ) ) ?></span></th>
			<?php
			foreach ( current( $groups ) as $column => $value ) {

				$column_class = in_array( $column, array( 'order', 'display' ) ) ? $column.' vertical-title' : $column;
				
				?>
				<th scope="col" class="<?php echo $column_class?>"><span><?php echo PDb_header( $column ) ?></span></th>
				<?php
				
			}
			?>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach ( $groups as $group => $group_values ) {
			if ( $group == 'internal' ) continue;

			$group_count = $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM '.Participants_Db::$fields_table.' WHERE `group` = "' . $group.'"' ) );
			
			?>
			<tr>
      <td id="field_count_<?php echo $group?>"><?php echo $group_count?></td>
			<td><a href="<?php echo $group_count?>" name="delete_<?php echo $group?>" class="delete" ref="group"></a></td>
			<?php
			foreach( $group_values as $column => $value  ) {
				
				$attributes = array();
				$options = array();
				$name = '';
				
				switch ( $column ) {
					
					case 'display':
						$type = 'checkbox';
						$options = array( 1, 0 );
						break;
						
					case 'description':
						$type = 'text-field';
						break;
						
					case 'order':
						$attributes = array( 'style'=>'width:30px' );
						$name = 'order_'.$group;
						$type = 'drag-sort';
						break;
						
					case 'name':
						$type = 'text';
						$attributes = array('readonly'=>'readonly');
						
					default:
						$type = 'text';
						
				}
				$element_atts = array(
														'name'=> ( empty( $name ) ? $group.'['.$column.']' : $name ),
														'value'=> $value,
														'type'=> $type,
														);
				if ( ! empty( $attributes ) ) $element_atts['attributes'] = $attributes;
				if ( ! empty( $options ) ) $element_atts['options'] = $options;
				?>
				<td class="<?php echo $column?>"><?php FormElement::print_element( $element_atts );?></td>
				<?php
				}
				?>
			</tr>
		<?php
		}
		?>
		</tbody>
	</table>
		<script type="text/javascript">
		jQuery(document).ready(function($){
			$("#field_groups tbody").sortable({
				helper: fixHelper,
				update: function(event, ui){
					var order = serializeList( $(this) );
					$.ajax({
						url: "<?php echo $_SERVER['REQUEST_URI']?>",
						type: "POST",
						data: order + '&action=reorder_groups'
						});
					}
				});
		});
		</script>
		<p class="submit">
			<?php
			FormElement::print_element( array('type'=>'submit', 'name'=>'submit','value'=>$PDb_i18n['update groups'], 'class'=>'button-primary') );
			?>
		</p>
		</form>
	</div><!-- groups tab panel -->
	<div id="help">
    <?php include 'manage_fields_help.php' ?>
	</div>
</div><!-- ui-tabs container -->
<div id="dialog-overlay"></div>
<div id="confirmation-dialog">
</div>
<?php
//
function PDb_header( $string ) {

	return ucwords( str_replace( array( '_' ), array( "<br />\r" ), $string ) );

}
function PDb_make_name( $string ) {

	return strtolower(str_replace( array( ' ','-',"'",'"','%','\\','#','$','.'),array('_','_','','','pct','','','',''), stripslashes( $string ) ) );
	
}
function PDb_trim_array( $array ) {

	$return = array();

	foreach ( $array as $element ) {

		$return[] = trim( $element );

	}

	return $return;

}
function PDb_prep_values_array( $array ) {

  $return = array();

  foreach ( $array as $element ) {

    $return[] = PDb_prep_value( $element );

  }

  return $return;
  
}
function PDb_prep_value( $value, $slashes = false ) {

	if ( $slashes ) return htmlentities( trim( $value ), ENT_QUOTES, "UTF-8", false  );
		
	else return htmlentities( trim( stripslashes( $value ) ), ENT_QUOTES, "UTF-8", false  );
	
}
	
	
// this rather kludgy function will do for now
function PDb_parse_db_error( $error, $context ) {

  global $PDb_i18n;

	// unless we find a custom message, use the class error message
	$message = $error;

	if ( false !== strpos( $error, 'Duplicate entry' ) ) {

    $item = false;

		switch ( $context ) {

			case $PDb_i18n['add group']:

        $item = 'group';
				break;

      case $PDb_i18n['add field']:

        $item = 'field';
        break;

		}

		if ( $item && false !== stripos( $message, 'duplicate'  ) ) {

        $message = sprintf(__('The %1$s was not added. Your new %1$s must have a unique name.', Participants_Db::PLUGIN_NAME ), $item );

		}

		return $message;

	}

}

function PDb_prep_title( $title ) {
	
	$needle = array( '"',"'",'\\' );
	$replace = array( '&quot;','&#039;','');
	
	return str_replace( $needle, $replace, $title );
	
}
?>