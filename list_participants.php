<?php
/**
 * displays a listing of records in the WP admin
 */
// translations strings for buttons
/* translators: the following 5 strings are used in logic matching, please test after translating in case special characters cause problems */
$PDb_i18n = array(
  'delete_checked' => _x( 'Delete Checked', 'submit button label', Participants_Db::PLUGIN_NAME ),
  'change' => _x( 'Change', 'submit button label', Participants_Db::PLUGIN_NAME ),
  'sort' => _x( 'Sort', 'submit button label', Participants_Db::PLUGIN_NAME ),
  'filter' => _x( 'Filter', 'submit button label', Participants_Db::PLUGIN_NAME ),
  'clear' => _x( 'Clear', 'submit button label', Participants_Db::PLUGIN_NAME ),
  );
// process all the general list actions first
if ( isset( $_POST['action'] ) && $_POST['action']=='list_action' ) {
	
	switch ( $_POST['submit'] ) {
		
		case $PDb_i18n['delete_checked']:
	
			$count = count( $_POST['pid'] );
	
			$pattern = $count > 1 ? 'IN ( '.trim( str_repeat( '%s,', $count ), ',' ).' )' : '= %s';
			$sql = "DELETE FROM ".Participants_Db::$participants_table." WHERE id ".$pattern;
			$wpdb->query( $wpdb->prepare( $sql, $_POST['pid'] ) );
			break;
			
		case $PDb_i18n['change']:
		
			$value = ( ! is_numeric($_POST['list_limit']) or $_POST['list_limit'] < 1 ) ? Participants_Db::$plugin_settings->get_option( 'list_limit' ) : $_POST['list_limit'];
		
			Participants_Db::$plugin_settings->update_option( 'list_limit', $value );
			break;
			
		default:
		
	}
  
}

// add this to the query to remove the default record
$skip_default = ' `id` != '.Participants_Db::$id_base_number;

// set up the query to display the list
switch ( @$_POST['submit'] ) {

  case $PDb_i18n['sort']:
  case $PDb_i18n['filter']:
  
		$list_query = 'SELECT * FROM '.Participants_Db::$participants_table;
		$delimiter = ( false !== stripos( $_POST['operator'], 'LIKE' ) ? '%' : '' );
		if ($_POST['where_clause'] != 'none'){
		  $list_query .= ' WHERE `'.mysql_real_escape_string($_POST['where_clause']).'` '.mysql_real_escape_string($_POST['operator'])." '".$delimiter.mysql_real_escape_string($_POST['value']).$delimiter."' ";
			$list_query .= ' AND '.$skip_default;
		} else {
			$list_query .= ' WHERE '.$skip_default;
		}
		$list_query .= ' ORDER BY '.mysql_real_escape_string($_POST['sortBy']).' '.mysql_real_escape_string($_POST['ascdesc']);

		// go back to the first page
		$_GET['p'] = 1;
		
		break;
		
  case $PDb_i18n['clear'] :
		unset( $_POST['value'], $_POST['where_clause'] );

		// go back to the first page
		$_GET['p'] = 1;
		
  default:
		$list_query = 'SELECT * FROM '.Participants_Db::$participants_table.' WHERE '.$skip_default.' ORDER BY last_name';
		
}

$options = get_option( Participants_Db::$participants_db_options );

// set the pagination object
$pagination = new Pagination( array(
																		'link'          => PDb_get_page_URI().'&p=%s',
																		'page'          => isset( $_GET['p'] ) ? $_GET['p'] : '1',
																		'size'          => $options['list_limit'],
																		'total_records' => Participants_Db::get_num_records(),
																		'wrap_tag'      => '<div class="pagination"><label>'._x('Page', 'page number indicator', Participants_Db::PLUGIN_NAME ).':</label> ',
																		'wrap_tag_close'=> '</div>',
																		));

// get the number of records returned
$num_records = count( $wpdb->get_results( $list_query, ARRAY_A ) );

// get the records for this page, adding the pagination limit clause
$participants = $wpdb->get_results( $list_query.' '.$pagination->getLimitSql(), ARRAY_A );

?>
<script type="text/javascript" language="javascript">  
	function delete_confirm() {
		var x=window.confirm("<?php _e('Do you really want to delete these participants?', Participants_Db::PLUGIN_NAME )?>")
		if (x)
			return true
		else
			return false
	}
	checked=false;
	function checkedAll (form_id) {
		var form = document.getElementById(form_id);
		if (checked == false) {
	           checked = true
		}
        else {
			checked = false
		}
		for (var i =0; i < form.elements.length; i++) {
			form.elements[i].checked = checked;
		}
	}
	
</script>

<div class="wrap">
  <h2><?php echo Participants_Db::$plugin_title?></h2>
  <h3><?php printf( _n( 'List Participants: %s record found, sorted by:', 'List Participants: %s records found, sorted by:', $num_records ), $num_records )?> <?php echo isset( $_POST['sortBy'] ) ? Participants_Db::column_title( $_POST['sortBy'] ) : Participants_Db::column_title( 'last_name' ) ?>.</h3>
  <form method="post">
    <input type="hidden" name="action" value="sort">
    <fieldset class="widefat">
    <legend><?php _e('Show only records with', Participants_Db::PLUGIN_NAME )?>:</legend>
    <?php
			//build the list of columns available for filtering
			$filter_columns = array( '('.__('show all', Participants_Db::PLUGIN_NAME ).')' => 'none' );
			foreach ( Participants_db::get_column_atts() as $column ) {
				
				if ( in_array( $column->name, Participants_Db::$internal_columns ) ) continue;
				
				$filter_columns[ $column->title ] = $column->name;
				
			}
			
			$element = array(
											 'type'		=> 'dropdown',
											 'name'		=> 'where_clause',
											 'value'	=>	isset( $_POST['where_clause'] ) ? $_POST['where_clause'] : 'none',
											 'options'	=> $filter_columns,
											 );
      FormElement::print_element( $element );
			?>
       that
      <?php
       $element = array(
                        'type'     => 'dropdown',
                        'name'     => 'operator',
                        'value'    => isset( $_POST['operator'] ) ? $_POST['operator'] : 'LIKE',
                        'options'  => array(
                                          __('is', Participants_Db::PLUGIN_NAME )         => '=',
                                          __('is not', Participants_Db::PLUGIN_NAME )     => '!=',
                                          __('contains', Participants_Db::PLUGIN_NAME )   => 'LIKE',
                                          __('doesn&#39;t contain', Participants_Db::PLUGIN_NAME )  => 'NOT LIKE',
                                          ),
                        );
      FormElement::print_element( $element );
      ?>
      <input id="participant_search_term" type="text" name="value" value="<?php echo @$_POST['value'] ?>">
      <input name="submit" type="submit" value="<?php echo $PDb_i18n['filter']?>">
      <input name="submit" type="submit" value="<?php echo $PDb_i18n['clear']?>">
    </fieldset>
    <fieldset class="widefat">
    <legend><?php _e('Sort by', Participants_Db::PLUGIN_NAME )?>:</legend>
    	<?php
		
			$element = array(
											 'type'		=> 'dropdown',
											 'name'		=> 'sortBy',
											 'value'	=>	isset( $_POST['sortBy'] ) ? $_POST['sortBy'] : 'last_name',
											 'options'	=> Participants_Db::get_sortables(),
											 );
      FormElement::print_element( $element );
			
			$element = array(
											 'type'		=> 'radio',
											 'name'		=> 'ascdesc',
											 'value'	=>	isset( $_POST['ascdesc'] ) ? $_POST['ascdesc'] : 'asc',
											 'options'	=> array(
											                    __('Ascending', Participants_Db::PLUGIN_NAME )  => 'asc',
											                    __('Descending', Participants_Db::PLUGIN_NAME ) => 'desc'
											                    ),
											 );
      FormElement::print_element( $element );
			
			?>
      <input name="submit" type="submit" value="<?php echo $PDb_i18n['sort'] ?>">
    </fieldset>
  </form>
  <form id="list_form"  method="post">
    <input type="hidden" name="action" value="list_action">
    <div style="margin-top:10px">
      
      <input type="submit" name="submit" value="<?php echo $PDb_i18n['delete_checked'] ?>" onClick="return delete_confirm();" class="delete-button">
      <?php $list_limit = FormElement::get_element( array( 'type'=>'text-line', 'name'=>'list_limit', 'value'=>$options['list_limit'], 'size'=>'1' ) )?>
      <span style="padding-left:20px"><?php printf( __( 'Show %s items per page.', Participants_Db::PLUGIN_NAME ),$list_limit )?>
      <?php FormElement::print_element( array( 'type'=>'submit', 'name'=>'submit','value'=>$PDb_i18n['change']) )?>
      </span>
    </div>
    <table class="wp-list-table widefat fixed pages" cellspacing="0" >
      <?php
		if ( count( $participants ) > 0 ) :
		
		  // get the columns to display
		  $display_columns = Participants_Db::get_list_display_columns();
		
			//now output the table of participants
			$col_pattern = '<td>%s</td>';
			$head_pattern = '<th>%s</th>';
			
			$PID_pattern = '<td><a href="'.get_bloginfo('url').'/'.( isset( $options['registration_page'] ) ? $options['registration_page'] : '' ).'?pid=%1$s">%1$s</a></td>';
			
			
			?>
      <thead>
        <tr>
          <th scope="col" style="width:6em"><?php /* translators: uses the check symbol in a phrase that means "check all" */ _e( '&#10004; all', Participants_Db::PLUGIN_NAME )?><input type="checkbox" onClick="checkedAll('list_form');" name="checkall" style="top: 2px; margin-left: 4px;"></th>
          <?php
				 foreach ( $display_columns as $column ) {
					printf ( $head_pattern, Participants_Db::column_title( $column ) );
				 } ?>
        </tr>
      </thead>
      <?php
			if ( count( $participants ) > 5 ) :
			?>
      <tfoot>
        <tr>
          <th></th>
          <?php
				 foreach ( $display_columns as $column ) {
					printf ($head_pattern, Participants_Db::column_title( $column ) );
				 } ?>
        </tr>
      </tfoot>
      <?php endif ?>
      <?php
			foreach ( $participants as $value ) {
			?>
      <tr>
        <td><a href="admin.php?page=<?php echo Participants_Db::PLUGIN_NAME ?>-edit_participant&action=edit&id=<?= $value['id']?>"><?php _e( 'Edit', Participants_Db::PLUGIN_NAME )?></a> |
          <input type="checkbox" name="pid[]" value="<?= $value['id']?>"></td>
        <?php foreach ( $display_columns as $column ) {
					
					$column_atts = Participants_Db::get_field_atts( $column, 'form_element' );
					
					switch ( $column_atts->form_element ) {
						
						case 'file-upload':
						
							$display_value = basename( $value[ $column ] );
							break;
							
						default:
						
							$display_value = $value[ $column ];
							
					}
					
					if ( $column == 'private_id' ) printf ($PID_pattern, Participants_Db::prepare_value( $display_value ) );
						
					else printf ($col_pattern, Participants_Db::prepare_value( $display_value ) );
					
				} ?>
      </tr>
      <?php }
			
		else :
		?>
      <tr>
        <td><?php _e('No participants found', Participants_Db::PLUGIN_NAME )?></td>
      </tr>
      <?php
		endif; // participants array 
		?>
    </table>
  </form>
    <?php
		// output the pagination controls
		$pagination->links();
		?>
  <fieldset class="widefat">
  <h3><?php _e('Export CSV', Participants_Db::PLUGIN_NAME )?></h3>
    <form method="post">
      <input type="hidden" name="source" value="<?php echo Participants_Db::PLUGIN_NAME ?>">
      <input type="hidden" name="action" value="output CSV" />
      <input type="hidden" name="CSV type" value="participant list" />
      <input type="hidden" name="query" value="<?php echo rawurlencode( $list_query )?>" />
      <?php
      /* translators: date format, (see http://php.net/date) must output valid string for filename  */
      $date_string = __('M-d-Y', Participants_Db::PLUGIN_NAME );
      $suggested_filename = Participants_Db::PLUGIN_NAME.'-'.date($date_string).'.csv';
      $namelength = round( strlen( $suggested_filename ) * 0.9 ) ;
      ?>
      <p><?php _e( 'File Name', Participants_Db::PLUGIN_NAME  )?>:
        <input type="text" name="filename" value="<?php echo $suggested_filename?>" size="<?php echo $namelength ?>" />
        <input type="submit" name="submit" value="<?php _e( 'Download CSV for this list', Participants_Db::PLUGIN_NAME  )?>" />
      </p>
      <p><?php _e( 'This will download the whole list of participants that match your search terms, and in the order specified by the sort. The export will include records on all list pages.', Participants_Db::PLUGIN_NAME  )?></p>
    </form>
  </fieldset>
  <?php Participants_Db::plugin_footer(); ?>
</div>
<?php
function PDb_get_page_URI() {
	
	$URI_parts = explode('?',$_SERVER['REQUEST_URI']);
	
	if ( empty( $URI_parts[1] ) ) return $URI_parts[0];
	
	parse_str( $URI_parts[1], $values );
	
	$get = '';
	
	foreach( $values as $key => $value ) {
		
		// strip out the 'p' value
		if ( $key != 'p' ) $get .= $key.'='.$value.'&';
		
	}
	
	return $URI_parts[0].'?'.trim( $get, '&' );
	
}