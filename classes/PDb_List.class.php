<?php
/**
 * class for handling the listing of participant records
 *
 * static class for managing a set of modules which together out put a listing of 
 * records in various configurations
 *
 * the general plan is that this class's initialization method is called in the
 * admin to generate the page. We also call this method with a shortcode
 * [pdb_list] which will initialize the class and pass in the parameters (if any)
 * to print the list to the website.
 *
 * Requires PHP Version 5.3 or greater
 * 
 * @category   
 * @package    WordPress
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2012 xnau webdesign
 * @license    GPL2
 * @version    Release: 1.10
 * @link       http://wordpress.org/extend/plugins/participants-database/
 */
 
class PDb_List
{
	
	// holds the main query for building the list
	static $list_query;
	 
	// translations strings for buttons
	static $i18n;
	
	// holds the pagination object
	static $pagination;

	// holds the number of list items to show per page
	static $page_list_limit;
	
	// the name of the list page variable
	static $list_page = 'listpage';
	
	// name of the list anchor element
	static $list_anchor = 'participants-list';
	
	// the number of records after filtering
	static $num_records;
	
	// all the records are held in this array
	static $participants;
	
	// true if the class is called from the admin
	static $backend;

	// holds the url of the registrations page
  static $registration_page_url;

	// holds the columns to display in the list
	static $display_columns;

	// holds the parameters for a shortcode-called display of the list
	static $params;
	
	/**
	 * initializes and outputs the list for the backend
	 *
	 * @param array $atts display customization parameters
	 *                    from the shortcode
	 */
	public function initialize( $atts = '' ) {

		// are we showing the backend form or frontend?
		self::$backend = is_admin();
		
		// begin buffering the output of the plugin
		if ( ! self::$backend ) ob_start();
		
		self::_setup_i18n();
    
    $options = get_option( Participants_Db::$participants_db_options );

    self::$page_list_limit = ( ! isset( $_POST['list_limit'] ) or ! is_numeric( $_POST['list_limit'] ) or $_POST['list_limit'] < 1 ) ? $options['list_limit'] : $_POST['list_limit'];
    
    self::$registration_page_url = get_bloginfo('url').'/'.( isset( $options['registration_page'] ) ? $options['registration_page'] : '' );

    self::$display_columns = Participants_Db::get_list_display_columns( self::$backend ? 'admin_column' : 'display_column' );

    // define the default settings for the shortcode
    $shortcode_defaults = array(
                                      'sort'        => 'false',
                                      'search'      => 'false',
                                      'list-limit'  => self::$page_list_limit,
                                      'class'       => 'participants-database',
                                      );

    self::$params = shortcode_atts( $shortcode_defaults, $atts );
		
		// process delete and items-per-page form submissions
		if ( self::$backend ) self::_process_general();
		else echo '<link media="all" type="text/css" href="'.plugins_url( Participants_Db::PLUGIN_NAME.'/pdb-list.css' ).'" rel="stylesheet">';
		
		// process any search/filter terms and build the main query
		$submit = isset( $_POST['submit'] ) ? $_POST['submit'] : '';
		self::_process_search( $submit );
		
		// get the $wpdb object
		global $wpdb;
		
		// get the number of records returned
		self::$num_records = count( $wpdb->get_results( self::$list_query, ARRAY_A ) );
		
		// set the pagination object
		self::$pagination = new Pagination( array(
																	'link'          => self::get_page_link( $_SERVER['REQUEST_URI'] ),
																	'page'          => isset( $_GET[ self::$list_page ] ) ? $_GET[ self::$list_page ] : '1',
																	'size'          => self::$page_list_limit,
																	'total_records' => self::$num_records,
																	'wrap_tag'      => '<div class="pagination"><label>'._x('Page', 'noun; page number indicator', Participants_Db::PLUGIN_NAME ).':</label> ',
																	'wrap_tag_close'=> '</div>',
																	));
		
		// get the records for this page, adding the pagination limit clause
		self::$participants = $wpdb->get_results( self::$list_query.' '.self::$pagination->getLimitSql(), ARRAY_A );
		
		// ok, setup finished, start outputting the form
		
		// add the top part of the page for the admin
		if ( self::$backend ) self::_admin_top();
		else self::_shortcode_top();
		
		// print the sorting/filtering forms
		self::_sort_filter_forms( self::_sort_filter_mode() );

		if ( self::$backend ) self::_general_list_form_top();
		
		// print the main table
		self::_main_table();
		
		// output the pagination controls
		self::$pagination->links();
		
		if ( self::$backend ) {
			
			// print the CSV export form
			self::_print_export_form();
			
			// print the plugin footer
			Participants_Db::plugin_footer();
			
		} else {

      // close the shotcode container div
      echo '</div>';

    }
		
		// if called by a shortcode, return the buffer contents
		if ( ! self::$backend ) return ob_get_clean();
			
	}
	
	/**
	 * strips the page number out of the URI so it can be used as a link to other pages
	 *
	 * @param string $uri the incoming URI, usually $_SERVER['REQUEST_URI']
	 *
	 * @return string the re-constituted URI
	 */
	public function get_page_link( $uri ) {
	
		$URI_parts = explode( '?', $uri );
		
		if ( empty( $URI_parts[1] ) ) return $URI_parts[0].'?'.self::$list_page.'=%s#'.self::$list_anchor;
		
		parse_str( $URI_parts[1], $values );
		
		$get = '';
		
		foreach( $values as $key => $value ) {
			
			// strip out the 'p' value
			if ( $key != self::$list_page ) $get .= $key.'='.$value.'&';
			
		}
		
		return $URI_parts[0].'?'.trim( $get, '&' ).'&'.self::$list_page.'=%s#'.self::$list_anchor;
	
	}
	
	
	/**	
	 * processes all the general list actions
	 * thses are only available in the admin
	 */
	private function _process_general() {
		
		global $wpdb;
		
		if ( isset( $_POST['action'] ) && $_POST['action']=='list_action' ) {
			
			switch ( $_POST['submit'] ) {
				
				case self::$i18n['delete_checked']:
			
					$count = count( $_POST['pid'] );
			
					$pattern = $count > 1 ? 'IN ( '.trim( str_repeat( '%s,', $count ), ',' ).' )' : '= %s';
					$sql = "DELETE FROM ".Participants_Db::$participants_table." WHERE id ".$pattern;
					$wpdb->query( $wpdb->prepare( $sql, $_POST['pid'] ) );
					break;
					
				case self::$i18n['change']:
				
					Participants_Db::$plugin_settings->update_option( 'list_limit', self::$page_list_limit );
					break;
					
				default:
				
			}
			
		}
		
	}
	
	/**
	 * precesses searches and sorts to build the listing query
	 */
	private function _process_search( $submit ) {

    // add this to the query to remove the default record
    $skip_default = ' `id` != '.Participants_Db::$id_base_number;
		
		switch ( $submit ) {
		
			case self::$i18n['sort']:
			case self::$i18n['filter']:
			
				self::$list_query = 'SELECT * FROM '.Participants_Db::$participants_table;
				
				$delimiter = ( false !== stripos( $_POST['operator'], 'LIKE' ) ? '%' : '' );
				
				if ( $_POST['where_clause'] != 'none' ) {
					
					self::$list_query .= ' WHERE `'.mysql_real_escape_string($_POST['where_clause']).'` '.mysql_real_escape_string($_POST['operator'])." '".$delimiter.mysql_real_escape_string($_POST['value']).$delimiter."' ";
					self::$list_query .= ' AND '.$skip_default;
					
				} else {
					
					self::$list_query .= ' WHERE '.$skip_default;
					
				}
				
				self::$list_query .= ' ORDER BY '.mysql_real_escape_string($_POST['sortBy']).' '.mysql_real_escape_string($_POST['ascdesc']);
		
				// go back to the first page to display the newly sorted/filtered list
				$_GET[ self::$list_page ] = 1;
				
				break;
				
			case self::$i18n['clear'] :
			
				unset( $_POST['value'], $_POST['where_clause'] );
		
				// go back to the first page
				$_GET[ self::$list_page ] = 1;
				
			default:
			
				self::$list_query = 'SELECT * FROM '.Participants_Db::$participants_table.' WHERE '.$skip_default.' ORDER BY last_name';
				
		}
		
	}
	
	/**
	 * top section for admin listing
	 */
	private function _admin_top() {
	?>
	<script type="text/javascript" language="javascript">
    var L10n = {"record":"<?php _e("Do you really want to delete the selected record?", Participants_Db::PLUGIN_NAME )?>","records":"<?php _e("Do you really want to delete the selected records?", Participants_Db::PLUGIN_NAME )?>"};
  
    function delete_confirm() {
			var plural = ( document.getElementById('select_count').value > 1 ) ? true : false;
      var x = window.confirm( plural ? L10n.records : L10n.record );
      if (x)
        return true
      else
        return false
    }
		
    check_state=false;
    function checkedAll (form_id) {
      var form = document.getElementById(form_id);
      if (check_state == false) {
               check_state = true
      } else {
               check_state = false;
							 armDelbutton(false);
      }
      for (var i =0; i < form.elements.length; i++) {
				if ( form.elements[i].type == 'checkbox' && form.elements[i].name != 'checkall' ) {
        	form.elements[i].checked = check_state;
					addSelects( check_state );
				}
      }
    }
		
		function addSelects( selected ) {
			var count_element = document.getElementById('select_count');
			var count = count_element.value;
			if ( selected === true ) count++;
			else {
				count--;
				document.getElementById('checkall').checked = false;
			}
			if ( count < 0 ) count = 0;
			armDelbutton(count > 0);
			count_element.value = count;
		}
		
		function armDelbutton( state ) {
			var delbutton = document.getElementById('delete_button');
			delbutton.setAttribute('class',state?'armed':'unarmed');
		}
			
		
		function checkEnter(e){
		 e = e || event;
		 return (e.keyCode || event.which || event.charCode || 0) !== 13;
		}
    
  </script>
  <div class="wrap">
    <h2><?php echo Participants_Db::$plugin_title?></h2>
    <h3><?php printf( _n( 'List Participants: %s record found, sorted by:', 'List Participants: %s records found, sorted by:', self::$num_records ), self::$num_records )?> 
		<?php echo isset( $_POST['sortBy'] ) ? Participants_Db::column_title( $_POST['sortBy'] ) : Participants_Db::column_title( 'last_name' ) ?>.</h3>
    <?php
	}

	/**
	 * prints the top section of the output when called by a shortcode
	 */
  private function _shortcode_top() {
  
    // start with an anchor so pagination can target the list top
    printf('<a name="%1$s" id="%1$s" ></a>',self::$list_anchor);

    ?>
    <div class="<?php echo self::$params['class'] ?>">
    <?php
    

  }
	 
	/**
	 * prints the sorting and filtering forms
	 *
	 * @param string $mode determines whether to print filter, sort, both or 
	 *                     none of the two functions
	 */
	private function _sort_filter_forms( $mode ) {
	
		if ( $mode == 'none' ) return;
	
	?>
	
	<form method="post" id="sort_filter_form" onKeyPress="return checkEnter(event)" >
    <input type="hidden" name="action" value="sort">
    
  	<?php if ( in_array( $mode, array( 'filter','both' ) ) ) : ?>
    
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
      <input name="submit" type="submit" value="<?php echo self::$i18n['filter']?>">
      <input name="submit" type="submit" value="<?php echo self::$i18n['clear']?>">
    </fieldset>
    
    <?php endif;
		if ( in_array( $mode, array( 'sort','both' ) ) ) : ?>
    
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
      <input name="submit" type="submit" value="<?php echo self::$i18n['sort'] ?>">
    </fieldset>
    <?php endif ?>
  </form><?php
	}

	/**
	 * prints the general list form controls for the admin lising
	 */
	private function _general_list_form_top() { ?>

		<form id="list_form"  method="post"  onKeyPress="return checkEnter(event)" >
    	<?php FormElement::print_hidden_fields( array( 'action'=>'list_action' ) )?>
      <input type="hidden" id="select_count" value="0" />
      <div style="margin-top:10px">
  
        <input type="submit" name="submit" value="<?php echo self::$i18n['delete_checked'] ?>" onClick="return delete_confirm();" class="unarmed" id="delete_button" >
        <?php $list_limit = FormElement::get_element( array( 
																														'type'=>'text-line', 
																														'name'=>'list_limit', 
																														'value'=>self::$page_list_limit, 
																														'attributes'=>array( 
																																								'style'=>'width:2.2em',
																																								'maxLength'=>'2'
																																								) 
																														) 
																										 )?>
        <span style="padding-left:20px"><?php printf( __( 'Show %s items per page.', Participants_Db::PLUGIN_NAME ),$list_limit )?>
        <?php FormElement::print_element( array( 'type'=>'submit', 'name'=>'submit','value'=>self::$i18n['change']) )?>
        </span>
      </div>

    <?php
  }

  /**
	 * prints the main body of the list, including headers
	 *
	 * @param string $mode dtermines the print mode: 'noheader' skips headers, (other choices to be determined)
	 */
  private function _main_table( $mode = '' ) { ?>

   <table class="wp-list-table widefat fixed pages" cellspacing="0" >
      <?php
		// template for printing the registration page link in the admin
		$PID_pattern = '<td><a href="'.self::$registration_page_url.'?pid=%1$s">%1$s</a></td>';
			
    // template for printing a header item
		$head_pattern = '<th class="%2$s" scope="col">%1$s</th>';
		
		//template for outputting a column
		$col_pattern = '<td>%s</td>';
		
    if ( count( self::$participants ) > 0 ) :
			
			if ( $mode != 'noheader' ) :
      ?>
      <thead>
        <tr>
          <?php self::_print_header_row( $head_pattern ) ?>
        </tr>
      </thead>
      <?php
			endif; // table header row
			
      // print the table footer row if there is a long list
      if ( $mode != 'noheader' && count( self::$participants ) > 10 ) :
      ?>
      <tfoot>
        <tr>
          <?php self::_print_header_row( $head_pattern ) ?>
        </tr>
      </tfoot>
      <?php
      endif; // table footer row ?>
      <?php
			// output the main list
      foreach ( self::$participants as $value ) {
      ?>
      <tr>
        <?php // print delete check
        if ( self::$backend ) : ?>
        <td>
          <a href="admin.php?page=<?php echo Participants_Db::PLUGIN_NAME ?>-edit_participant&action=edit&id=<?= $value['id']?>"><?php _e( 'Edit', Participants_Db::PLUGIN_NAME )?></a> |
          <input type="checkbox" name="pid[]" value="<?= $value['id']?>" onClick="addSelects( this.checked )">
        </td>
        <?php endif; // delete check

        foreach ( self::$display_columns as $column ) {

					// get the form element value for the field
          $column_atts = Participants_Db::get_field_atts( $column, 'form_element' );

					// this is where we place form-element-specific text transformations for display
          switch ( $column_atts->form_element ) {

            case 'image-upload':

              $display_value = self::$backend ? basename( $value[ $column ] ) : '<img class="PDb-list-image" src="'.$value[ $column ].'" />';
              break;

            default:

              $display_value = $value[ $column ];

          }

          if ( $column == 'private_id' ) printf( $PID_pattern, Participants_Db::prepare_value( $display_value ) );

          else printf( $col_pattern, Participants_Db::prepare_value( $display_value ) );

        } ?>
      </tr>
      <?php }

    else : // if there are no records to show; do this
    	?>
      <tr>
        <td><?php _e('No records found', Participants_Db::PLUGIN_NAME )?></td>
      </tr>
      <?php
     endif; // participants array
     ?>
     </table>
		 <?php
		 if ( self::$backend ) : ?></form><?php endif;
		 
    }
		
		/**
		 * prints the CSV export form
		 */
		private function _print_export_form() { ?>
    
      <fieldset class="widefat">
      	<h3><?php _e('Export CSV', Participants_Db::PLUGIN_NAME )?></h3>
        <form method="post">
          <input type="hidden" name="source" value="<?php echo Participants_Db::PLUGIN_NAME ?>">
          <input type="hidden" name="action" value="output CSV" />
          <input type="hidden" name="CSV type" value="participant list" />
          <input type="hidden" name="query" value="<?php echo rawurlencode( self::$list_query )?>" />
          <?php
          /* translators: date format, (see http://php.net/date) must output valid string for filename  */
          $date_string = __('M-d-Y', Participants_Db::PLUGIN_NAME );
          $suggested_filename = Participants_Db::PLUGIN_NAME.'-'.date($date_string).'.csv';
          $namelength = round( strlen( $suggested_filename ) * 0.9 ) ;
          ?>
          <p>
					  <?php _e( 'File Name', Participants_Db::PLUGIN_NAME  )?>:
            <input type="text" name="filename" value="<?php echo $suggested_filename?>" size="<?php echo $namelength ?>" />
            <input type="submit" name="submit" value="<?php _e( 'Download CSV for this list', Participants_Db::PLUGIN_NAME  )?>" />
          </p>
          <p>
						<?php _e( 'This will download the whole list of participants that match your search terms, and in the order specified by the sort. The export will include records on all list pages.', Participants_Db::PLUGIN_NAME  )?>
          </p>
        </form>
      </fieldset>
  
  	<?php 
		}

    /**
     * prints a table header row
     */
    private function _print_header_row( $head_pattern ) {

      // print the "select all" header
      if ( self::$backend ) : ?>
      <th scope="col" style="width:6em">
        <?php /* translators: uses the check symbol in a phrase that means "check all" */ _e( '&#10004; all', Participants_Db::PLUGIN_NAME )?>
        <input type="checkbox" onClick="checkedAll('list_form');" name="checkall" id="checkall" style="top: 2px; margin-left: 4px;">
      </th>
      <?php endif;// "select all" header

      // print the top header row
      foreach ( self::$display_columns as $column ) {
      printf (
              $head_pattern,
              htmlspecialchars( stripslashes( Participants_Db::column_title( $column ) ),ENT_QUOTES,"UTF-8",false ),
							$column
              );
      }

    }

    /**
     * builds the sort-filter mode setting
     */
    private function _sort_filter_mode() {

      if ( self::$backend ) return 'both';

      // until we get this working:
      else return 'none';

      $mode = self::$params['sort'] == 'true' ? 'sort' : 'none';

      return self::$params['search'] == 'true' ? ( $mode == 'sort' ? 'both' : 'filter' ) : $mode ;

    }
		
		/**
		 * sets up the internationalization strings
		 */
		private function _setup_i18n() {
		
			/* translators: the following 5 strings are used in logic matching, please test after translating in case special characters cause problems */
			self::$i18n = array(
				'delete_checked' => _x( 'Delete Checked', 'submit button label', Participants_Db::PLUGIN_NAME ),
				'change' => _x( 'Change', 'submit button label', Participants_Db::PLUGIN_NAME ),
				'sort' => _x( 'Sort', 'submit button label', Participants_Db::PLUGIN_NAME ),
				'filter' => _x( 'Filter', 'submit button label', Participants_Db::PLUGIN_NAME ),
				'clear' => _x( 'Clear', 'submit button label', Participants_Db::PLUGIN_NAME ),
			);
		
		}
	
}// class ?>