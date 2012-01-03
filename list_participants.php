<?php
// process all the general list actions first
if ( isset( $_POST['action'] ) && $_POST['action']=='list_action' ) {
	
	switch ( $_POST['submit'] ) {
		
		case 'Delete checked':
	
			$pattern = count( $_POST['pid'] ) > 1 ? 'in ( %s )' : '= %s';
			$value = implode( ',',$_POST['pid'] );
			$sql = "DELETE FROM ".Participants_Db::$participants_table." WHERE id ".$pattern;
			$wpdb->query( $wpdb->prepare( $sql, $value ) );
			break;
			
		case 'Change':
		
			$value = ( ! is_numeric($_POST['list_limit']) or $_POST['list_limit'] < 1 ) ? self::get_option( 'list_limit' ) : $_POST['list_limit'];
		
			self::update_option( 'list_limit', $value );
			break;
			
		default:
		
	}
  
}

// add this to the query to remove the default record
$skip_default = ' `id` != '.Participants_Db::$id_base_number;

// set up the query to display the list
switch ( @$_POST['submit'] ) {

  case 'Sort':
  case 'Filter':
  
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
		
  case 'Clear' :
		unset( $_POST['value'], $_POST['where_clause'] );

		// go back to the first page
		$_GET['p'] = 1;
		
  default:
		$list_query = 'SELECT * FROM '.Participants_Db::$participants_table.' WHERE '.$skip_default.' ORDER BY last_name';
		
}

$options = get_option( Participants_Db::$participants_db_options );

// set the pagination object
$pagination = new Pagination( array(
																		'link'          => $_SERVER['REQUEST_URI'].'&p=%s',
																		'page'          => isset( $_GET['p'] ) ? $_GET['p'] : '1',
																		'size'          => $options['list_limit'],
																		'total_records' => Participants_Db::get_num_records(),
																		'wrap_tag'      => '',
																		'wrap_tag_close'=> '',
																		));

// get the number of records returned
$num_records = count( $wpdb->get_results( $list_query, ARRAY_A ) );

// get the records for this page, adding the pagination limit clause
$participants = $wpdb->get_results( $list_query.' '.$pagination->getLimitSql(), ARRAY_A );

?>
<script type="text/javascript" language="javascript">  
	function delete_confirm() {
		var x=window.confirm("Do you really want to delete these participants?")
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
  <h2><?php echo Participants_Db::PLUGIN_TITLE?></h2>
  <h3>List Participants: <?php echo $num_records?> records found, sorted by: <?php echo isset( $_POST['sortBy'] ) ? Participants_Db::column_title( $_POST['sortBy'] ) : Participants_Db::column_title( 'last_name' ) ?>.</h3>
  <form method="post">
    <input type="hidden" name="action" value="sort">
    <fieldset class="widefat">
    <legend>Show only records with:</legend>
    <?php
			//build the list of columns available for filtering
			$filter_columns = array( '(show all)' => 'none' );
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
                                          'is'               => '=',
                                          'is not'           => '!=',
                                          'contains'         => 'LIKE',
                                          "doesn't contain"  => 'NOT LIKE',
                                          ),
                        );
      FormElement::print_element( $element );
      ?>
      <input id="participant_search_term" type="text" name="value" value="<?php echo @$_POST['value'] ?>">
      <input name="submit" type="submit" value="Filter">
      <input name="submit" type="submit" value="Clear">
    </fieldset>
    <fieldset class="widefat">
    <legend>Sort by:</legend>
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
											 'options'	=> array( 'Ascending' => 'asc', 'Descending' => 'desc' ),
											 );
      FormElement::print_element( $element );
			
			?>
      <input name="submit" type="submit" value="Sort">
    </fieldset>
  </form>
  <form id="list_form"  method="post">
    <input type="hidden" name="action" value="list_action">
    <div style="margin-top:10px">
      
      <input type="submit" name="submit" value="Delete checked" onClick="return delete_confirm();" class="delete-button">
      <span style="padding-left:20px">Show
      <?php FormElement::print_element( array( 'type'=>'text-line', 'name'=>'list_limit', 'value'=>$options['list_limit'], 'size'=>'1' ) )?>
      items per page.
      <?php FormElement::print_element( array( 'type'=>'submit', 'name'=>'submit','value'=>'Change') )?>
      </span>
    </div>
    <table class="wp-list-table widefat fixed pages" cellspacing="0" >
      <?php
		if ( count( $participants ) > 0 ) :
		
		  // get the columns to display
		  $display_columns = Participants_Db::get_list_display_columns();
		
			//now output the table of participants
			$col_pattern = "<td>%s</td>";
			$head_pattern = "<th>%s</th>";?>
      <thead>
        <tr>
          <th scope="col" style="width:6em">&#10004; all<input type="checkbox" onclick="checkedAll('list_form');" name="checkall" style="top: 2px; margin-left: 4px;"></th>
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
        <td><a href="admin.php?page=<?php echo Participants_Db::PLUGIN_NAME ?>-edit_participant&action=edit&id=<?= $value['id']?>">Edit</a> |
          <input type="checkbox" name="pid[]" value="<?= $value['id']?>"></td>
        <?php foreach ( $display_columns as $column ) {
				 printf ($col_pattern, Participants_Db::prepare_value( $value[ $column ] ) );
				} ?>
      </tr>
      <?php }
			
		else :
		?>
      <tr>
        <td>No participants found</td>
      </tr>
      <?php
		endif; // participants array 
		?>
    </table>
  </form>
  <div class="pagination"><label>Page:</label> 
    <?php
		$pagination->links();
		?>
  </div>
  <fieldset class="widefat">
  <h3>Export CSV</h3>
    <form method="post">
      <input type="hidden" name="source" value="<?php echo Participants_Db::PLUGIN_NAME ?>">
      <input type="hidden" name="action" value="output CSV" />
      <input type="hidden" name="CSV type" value="participant list" />
      <input type="hidden" name="query" value="<?php echo rawurlencode( $list_query )?>" />
      <!-- query: <?php echo $list_query ?> -->
      <?php 
      $suggested_filename = Participants_Db::PLUGIN_NAME.'-'.date('M-d-Y').'.csv';
      $namelength = round( strlen( $suggested_filename ) * 0.9 ) ;
      ?>
      <p>File Name:
        <input type="text" name="filename" value="<?php echo $suggested_filename?>" size="<?php echo $namelength ?>" />
        <input type="submit" name="submit" value="Download CSV for this list" />
      </p>
      <p>This will download the whole list of participants that match your search terms, and in the order specified by the sort. The export will include records on all list pages.</p>
    </form>
  </fieldset>
  <?php Participants_Db::plugin_footer(); ?>
</div>
