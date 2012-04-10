<?php
/**
 * class for handling the listing of participant records when called by a shortcode
 *
 * static class for managing a set of modules which together out put a listing of 
 * records in various configurations
 *
 * the general plan is that this class's initialization method is called by the
 * shortcode [pdb_list] which will initialize the class and pass in the parameters
 * (if any) to print the list to the website.
 *
 * Requires PHP Version 5.3 or greater
 * 
 * @category   
 * @package    WordPress
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2012 xnau webdesign
 * @license    GPL2
 * @version    Release: 1.3.7
 * @link       http://wordpress.org/extend/plugins/participants-database/
 */
 
class PDb_Show_Records
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

	// holds the url of the registrations page
  static $registration_page_url;

	// holds the columns to display in the list
	static $display_columns;

	// holds the list of sortable columns
	static $sortables;

	// holds the parameters for a shortcode-called display of the list
	static $shortcode_params;
	
	// holds the settings for the list filtering and sorting
	static $filter;
	
	// holds plugin options array
	static $options;

	// transient for holding the shortcode parameters
	static $shortcode_transient = 'PDb_list_shortcode';
	
	/**
	 * initializes and outputs the list on the frontend as called by the shortcode
	 *
	 * @param array $atts display customization parameters
	 *                    from the shortcode
	 */
	public function initialize( $atts = '' ) {
		
		self::_setup_i18n();
    
    self::$options = get_option( Participants_Db::$participants_db_options );

    // set the list limit value; this can be overridden by the shortcode atts later
    self::$page_list_limit = ( ! isset( $_POST['list_limit'] ) or ! is_numeric( $_POST['list_limit'] ) or $_POST['list_limit'] < 1 ) ? self::$options['list_limit'] : $_POST['list_limit'];
    
    self::$registration_page_url = get_bloginfo('url').'/'.( isset( self::$options['registration_page'] ) ? self::$options['registration_page'] : '' );

    self::$display_columns = Participants_Db::get_list_display_columns( 'display_column' );

    self::$sortables = Participants_Db::get_sortables();

    // define the default settings for the shortcode
    $shortcode_defaults = array(
                                      'sort'        => 'false',
                                      'search'      => 'false',
                                      'list_limit'  => self::$page_list_limit,
                                      'class'       => 'participants-database',
																			'filter'      => '',
																			'orderby'			=> 'date_recorded',
																			'order'       => 'desc',
																			'fields'			=> '',
																			'display_count' => 'false',
                                      );

    self::$shortcode_params = shortcode_atts( $shortcode_defaults, $atts );

    set_transient( self::$shortcode_transient, self::$shortcode_params, 3600); 
		
		// set up the basic values; sort values come from the shortcode
		$default_values = array(
														'where_clause' => 'none',
														'value'        => '',
														'operator'     => 'LIKE',
														'sortBy'       => self::$shortcode_params['orderby'],
														'ascdesc'      => self::$shortcode_params['order']
														);
		
		// merge the defaults with the $_REQUEST array so if there are any new values coming in, they're included
		self::$filter = shortcode_atts( $default_values, $_REQUEST );
		
		// allow for an arbitrary fields definition list in the shortcode
		if ( ! empty( self::$shortcode_params['fields'] ) ) {
			
			$raw_list = explode( ',', str_replace( array( "'",'"',' ',"\r" ), '', self::$shortcode_params['fields'] ) );
			
			if ( is_array( $raw_list ) ) :
			
				//clear the array
				self::$display_columns = array();
			
				foreach( $raw_list as $column ) {
					
					if ( Participants_Db::is_column( $column ) ) {
				
						self::$display_columns[] = $column;
						
					}
					
				}
				
			endif;
			
		}
		
		
		
		// process any search/filter/sort terms and build the main query
		self::_shortcode_query();
		
		if ( WP_DEBUG ) error_log( __METHOD__.' list query= '.self::$list_query );
		
		// get the $wpdb object
		global $wpdb;
		
		// get the number of records returned
		self::$num_records = count( $wpdb->get_results( self::$list_query, ARRAY_A ) );
		
		// set the pagination object
		self::$pagination = new Pagination( array(
																	'link'          => self::get_page_link( $_SERVER['REQUEST_URI'] ),
																	'page'          => isset( $_GET[ self::$list_page ] ) ? $_GET[ self::$list_page ] : '1',
																	'size'          => self::$shortcode_params['list_limit'],
																	'total_records' => self::$num_records,
																	'wrap_tag'      => '<div class="pagination"><label>'._x('Page', 'noun; page number indicator', Participants_Db::PLUGIN_NAME ).':</label> ',
																	'wrap_tag_close'=> '</div>',
																	));
		
		// get the records for this page, adding the pagination limit clause
		self::$participants = $wpdb->get_results( self::$list_query.' '.self::$pagination->getLimitSql(), ARRAY_A );
		
		// ok, setup finished, start outputting the form

    // begin buffering the output of the plugin
    ob_start();
		
		// add the top part of the page
    self::_shortcode_top();
		
		// print the sorting/filtering forms
		self::_sort_filter_forms( self::_sort_filter_mode() );
		
		// print the main table
		self::_main_table();
		
		// output the pagination controls
		self::$pagination->links();

    // close the shotcode container div
    echo '</div>';
		
		// return the buffer contents so WP can display the list
		return ob_get_clean();
			
	}
	
	/**
	 * processes shortcode filters and sorts to build the listing query
	 *
	 */
	private function _shortcode_query() {

    // add this to the query to remove the default record
    $skip_default = ' `id` != '.Participants_Db::$id_base_number;
		
		// if we've got a valid orderby, use it. Check $_POST first, shortcode second
		$orderby = isset( self::$filter['sortBy'] ) ? self::$filter['sortBy'] : self::$shortcode_params['orderby'];
		$orderby = Participants_Db::is_column( $orderby ) ? $orderby : current( self::$sortables );
		self::$filter['sortBy'] = $orderby;
			
		$order = isset( self::$filter['ascdesc'] ) ? strtoupper( self::$filter['ascdesc'] ) : strtoupper( self::$shortcode_params['order'] );
		$order = in_array( $order, array( 'ASC', 'DESC' ) ) ? $order : 'ASC';
		self::$filter['ascdesc'] = strtolower($order);
		
		self::$list_query = 'SELECT * FROM '.Participants_Db::$participants_table.' WHERE '.$skip_default.' ORDER BY `'.$orderby.'` '.$order;
		
		if  ( isset( $_POST['submit'] ) && $_POST['submit'] == self::$i18n['clear'] ) {
			
				self::$filter['value'] = '';
				self::$filter['where_clause'] = 'none';
				
				// go back to the first page
				$_GET[ self::$list_page ] = 1;
				
		}
		
		$where_clause = '';
				
		if ( isset( self::$shortcode_params['filter'] ) ) {
				
			$statements = explode( '&', html_entity_decode(self::$shortcode_params['filter']) );
			
			foreach ( $statements as $statement ) {
				
				$operator = preg_match( '#(\S+)(\>|\<|=|!|~)(\S+)#', str_replace(' ','', $statement ), $matches );
				
				if ( $operator === 0 ) continue;// no valid operator; skip to the next statement
				
				// get the parts
				list( $string, $column, $op_char, $target ) = $matches;
				
				if ( ! Participants_Db::is_column( $column ) or ( ! empty( self::$filter['value'] ) && $column == self::$filter['where_clause'] ) ) {

          // not a valid column or was used in a user search query which overrides
          // the shortcode; skip to the next one
          continue;

        }
				
				$field_atts = Participants_Db::get_field_atts( $column );
				
				$delimiter = array('"','"');
				
				// if we're dealing with a date element, the target value needs to be conditioned to
				// get a correct comparison
				if ( $field_atts->form_element == 'date' ) {
				
					$target = Participants_Db::parse_date( $target );
					
					// if we don't get a valid date, skip this statement
					if ( false === $target ) continue;
					
					$delimiter = array( 'CAST(',' AS SIGNED)' );
					
				}
				
				// get the proper operator
				switch ( $op_char ) {
					
					case '~':
						$operator = 'LIKE';
						$delimiter = array('"%','%"');
						break;
					
					case '!':
						$operator = '!=';
						break;
						
					default:
						$operator = $op_char;
						
				}
				
				// build the where clause
				//$where_clause .= '`'.$column.'` '.$operator.' '.$delimiter[0].$target.$delimiter[1].' AND ';
				$where_clause .= sprintf( '`%s` %s %s%s%s AND ', $column, $operator, $delimiter[0], $target, $delimiter[1] );
				
			}// foreach $statements

			// add the user search
			if ( isset( self::$filter['value'] ) && ! empty( self::$filter['value'] ) && 'none' != self::$filter['where_clause'] ) {

        $pattern = self::$options['strict_search'] ? '`%s` = "%s" AND ' : '`%s` LIKE "%%%s%%" AND ';

        $where_clause .= sprintf( $pattern, self::$filter['where_clause'],self::$filter['value'] );

      }
			
			self::$list_query = 'SELECT * FROM '.Participants_Db::$participants_table.' WHERE '.$where_clause.$skip_default.' ORDER BY `'.$orderby.'` '.$order;
			
		}
		
	}

	/**
	 * prints the top section of the output
	 */
  private function _shortcode_top() {

    ?>
    <link media="all" type="text/css" href="<?php echo plugins_url( Participants_Db::PLUGIN_NAME.'/css/PDb-list.css' )?>" rel="stylesheet">

    <?php
    // start with an anchor so pagination can target the list top
    printf('<a name="%1$s" id="%1$s" ></a>',self::$list_anchor);

    ?>
    <div id="pdb-show-records" class="<?php echo self::$shortcode_params['class'] ?>">
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
		
		global $post;

		if( ! is_object( $post ) ) {

      $post = get_post( $_POST['postID'] );

    }
	
	?>
	<div class="pdb-searchform">
    <div class="pdb-error">
      <p id="where_clause_error"><?php _e( 'Please select a column to search in.', Participants_Db::PLUGIN_NAME )?></p>
      <p id="value_error"><?php _e( 'Please type in something to search for.', Participants_Db::PLUGIN_NAME )?></p>
    </div>
	<form method="post" id="sort_filter_form" <?php /*?>onKeyPress="return checkEnter(event)" <?php */?><?php printf( 'action="%s" ',get_page_link( $post->ID ).'#'.self::$list_anchor ) ?>  >
    <input type="hidden" name="action" value="sort">
    
  	<?php if ( in_array( $mode, array( 'filter','both' ) ) ) : ?>
    
    <fieldset class="widefat">
    <legend><?php _e('Search', Participants_Db::PLUGIN_NAME )?>:</legend>
    <?php
			//build the list of columns available for filtering
			$filter_columns = array( '('.__('show all', Participants_Db::PLUGIN_NAME ).')' => 'none' );
			foreach ( Participants_db::get_column_atts( 'frontend_list' ) as $column ) {
				
				if ( in_array( $column->name, array( 'id','private_id' ) ) ) continue;
				
				$filter_columns[ $column->title ] = $column->name;
				
			}
			
			$element = array(
											 'type'		=> 'dropdown',
											 'name'		=> 'where_clause',
											 'value'	=>	self::$filter['where_clause'],
											 'options'	=> $filter_columns,
											 );
      FormElement::print_element( $element );
			?>
      <input name="operator" type="hidden" value="LIKE" />
      <input id="participant_search_term" type="text" name="value" value="<?php echo @self::$filter['value'] ?>">
      <input name="submit" type="submit" value="<?php echo self::$i18n['search'] ?>">
      <input name="submit" type="submit" value="<?php echo self::$i18n['clear']?>">
    </fieldset>
    
    <?php endif; // show filtering (search)
		if ( in_array( $mode, array( 'sort','both' ) ) ) : ?>
    
    <fieldset class="widefat">
    <legend><?php _e('Sort by', Participants_Db::PLUGIN_NAME )?>:</legend>
    	<?php
		
			$element = array(
											 'type'		=> 'dropdown',
											 'name'		=> 'sortBy',
											 'value'	=>	self::$filter['sortBy'],
											 'options'	=> self::$sortables,
											 );
      FormElement::print_element( $element );
			
			$element = array(
											 'type'		=> 'radio',
											 'name'		=> 'ascdesc',
											 'value'	=>	self::$filter['ascdesc'],
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
  </form>
  </div><?php
	}

  /**
	 * prints the main body of the list, including headers
	 *
	 * @param string $mode dtermines the print mode: 'noheader' skips headers, (other choices to be determined)
	 */
  private function _main_table( $mode = '' ) { ?>

   <table class="wp-list-table widefat fixed pages pdb-list" cellspacing="0" >
   	<?php if ( self::$shortcode_params['display_count'] == 'true' ) : ?>
   	<caption><?php printf(__('Total Records Found',Participants_Db::PLUGIN_NAME ).': %s',self::$num_records )?></caption>
    <?php endif ?>
      <?php
		// template for printing the registration page link in the admin
		$PID_pattern = '<td><a href="%2$s">%1$s</a></td>';
			
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
      <tbody>
      <?php
			// output the main list
      foreach ( self::$participants as $value ) {
      ?>
      <tr>
        <?php

        foreach ( self::$display_columns as $column ) {

					// get the form element value for the field
          $column_atts = Participants_Db::get_field_atts( $column, '`form_element`,`default`' );

					// this is where we place form-element-specific text transformations for display
          switch ( $column_atts->form_element ) {

           case 'image-upload':

              $display_value = empty( $value[ $column ] ) ? '' : '<img class="PDb-list-image" src="'.Participants_Db::get_image_uri( $value[ $column ] ).'" />';
					 
					 		if (
                    isset( self::$options['single_record_link_field'] )
										&&
										$column == self::$options['single_record_link_field']
                    &&
                    ! empty( self::$options['single_record_page'] )
                  ) {
								
								$page_link = get_page_link( self::$options['single_record_page'] );

                $display_value = Participants_Db::make_link( $page_link, $display_value, '<a href="%1$s" >%2$s</a>', array( 'pdb'=>$value['id'] ) );
								
							}
							
              break;
							
					 case 'date':
							
							if ( ! empty( $value[ $column ] ) ) {
							
								$time = preg_match( '#^[0-9-]+$#', $value[ $column ] ) > 0 ? (int) $value[ $column ] : strtotime( $value[ $column ] );
								$display_value = date( get_option('date_format','r'), $time );
								
							} else $display_value = '';
							
							break;
							
					 case 'multi-select-other':
					 case 'multi-checkbox':
					 // multi selects are displayed as comma separated lists
					 
					 		$display_value = is_serialized( $value[ $column ] ) ? implode( ', ', unserialize( $value[ $column ] ) ) : $value[ $column ];
							break;
					 
					 case 'link':
					 
              if ( is_serialized( $value[ $column ] ) ) {

                $params = unserialize( $value[ $column ] );
								
								if ( count( $params ) < 2 ) $params[1] = $params[0];

              } else {

                // in case we got old unserialized data in there
                $params = array_fill( 0, 2, $value[ $column ] );

              }

              $display_value = Participants_Db::make_link( $params[0], $params[1] );

              break;

           case 'textarea':

              if ( ! empty( $value[ $column ] ) ) $display_value = '<span class="textarea">' . $value[ $column ] . '</span>';
							else $display_value = '';
              break;

           case 'text-line':

              if (
                    isset( self::$options['single_record_link_field'] )
										&&
										$column == self::$options['single_record_link_field']
                    &&
                    ! empty( self::$options['single_record_page'] )
                  ) {
								
								$page_link = get_page_link( self::$options['single_record_page'] );

                $display_value = Participants_Db::make_link( $page_link, $value[ $column ], '<a href="%1$s" >%2$s</a>', array( 'pdb'=>$value['id'] ) );
					 
              } elseif ( self::$options['make_links'] ) {
								
								$display_value = Participants_Db::make_link( $value[ $column ] );
								
							} else {

               $display_value = NULL === $value[ $column ] ? $column_atts->default : esc_html($value[ $column ]);
							 
							}

							break;

           default:
					 
					 		$display_value = NULL === $value[ $column ] ? $column_atts->default : esc_html($value[ $column ]);

          }

          if ( $column == 'private_id' ) printf( 
																								$PID_pattern, 
																								$display_value, 
																								Participants_Db::get_record_link( $display_value ) 
																								);

          else printf( $col_pattern, $display_value );

        } ?>
      </tr>
      <?php } ?>
    </tbody>

    <?php else : // if there are no records to show; do this
    	?>
      <tbody>
      <tr>
        <td><?php _e('No records found', Participants_Db::PLUGIN_NAME )?></td>
      </tr>
      </tbody>
      <?php
     endif; // participants array
     ?>
     </table>
		 <?php
		 
    }
    
    /**
     * prints a table header row
     */
    private function _print_header_row( $head_pattern ) {

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
    * strips the page number out of the URI so it can be used as a link to other pages
    *
    * @param string $uri the incoming URI, usually $_SERVER['REQUEST_URI']
    *
    * @return string the re-constituted URI
    */
    public function get_page_link( $uri ) {

      $URI_parts = explode( '?', $uri );

      if ( empty( $URI_parts[1] ) ) {

        $values = array();

      } else {

        parse_str( $URI_parts[1], $values );

        unset( $values[ self::$list_page ] );

      }

      return $URI_parts[0].'?'.http_build_query( array_merge( $values, self::$filter ) ).'&'.self::$list_page.'=%s#'.self::$list_anchor;

    }

    /**
     * builds the sort-filter mode setting
     */
    private function _sort_filter_mode() {

      $mode = self::$shortcode_params['sort'] == 'true' ? 'sort' : 'none';

      return self::$shortcode_params['search'] == 'true' ? ( $mode == 'sort' ? 'both' : 'filter' ) : $mode ;

    }		
		
		/**
		 * takes the $_POST array and constructs a filter statement to add to the list shortcode filter
		 */
		private function _make_filter_statement( $post ) {
			 
			 if ( ! Participants_Db::is_column( $post['where_clause'] ) ) return '';
			 
			 self::$filter['where_clause'] = $post['where_clause'];
			 
			 
			 switch ( $post['operator'] ) {
				 
				 case 'LIKE':
				 
					$operator = '~';
					break;
					
				case 'NOT LIKE':
				case '!=':
				
					$operator = '!';
					break;
				
				case 'gt':
				
					$operator = '>';
					break;
					
				case 'lt':
				
					$operator = '<';
					break;
					
				default:
				
					$operator = '=';
					
			 }
			 
			self::$filter['operator'] = $operator;
			 
			if ( empty( $post['value'] ) ) return '';
			
			self::$filter['value'] = $post['value'];
			
			return self::$filter['where_clause'].self::$filter['operator'].self::$filter['value'];
			
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
        'search' => _x( 'Search', 'search button label', Participants_Db::PLUGIN_NAME ),
			);
		
		}
	
}// class ?>