<?php
/**
 * class for handling the listing of participant records when called by the [pdb_list] shortcode
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
 * @version    Release: 1.4
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
	
	// holds the pagination defulat settings
	static $pagination_defaults;

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
                                'sort'          => 'false',
                                'search'        => 'false',
                                'list_limit'    => self::$page_list_limit,
                                'class'         => 'participants-database',
                                'filter'        => '',
                                'orderby'			  => 'date_recorded',
                                'order'         => 'desc',
                                'fields'		  	=> '',
                                'display_count' => 'false',
                                'template'      => 'default',
                                'filtering'     => false, // this is so the AJAX call can update the list only
                                );

    self::$shortcode_params = shortcode_atts( $shortcode_defaults, $atts );

    set_transient( self::$shortcode_transient, self::$shortcode_params, 3600); 
		
    // enqueue the filter/sort AJAX script
    if ( self::_sort_filter_mode() !== 'none' ) wp_enqueue_script( 'list-filter' );
		
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
		$count = $wpdb->get_results( str_replace( 'SELECT * FROM', 'SELECT COUNT(*) FROM', self::$list_query ), ARRAY_A );
		self::$num_records = current( $count[0] );
		
		// set the pagination object default parameters
		self::$pagination_defaults = array(
					'link'          => self::get_page_link( $_SERVER['REQUEST_URI'] ),
					'page'          => isset( $_GET[ self::$list_page ] ) ? $_GET[ self::$list_page ] : '1',
					'size'          => self::$shortcode_params['list_limit'],
					'total_records' => self::$num_records,
					'wrap_tag'      => '<div class="pagination"><label>'._x('Page', 'noun; page number label', Participants_Db::PLUGIN_NAME ).':</label> ',
					'wrap_tag_close'=> '</div>',
					);
		
		// set the pagination object
		self::$pagination = new Pagination( self::$pagination_defaults );
		
		// get the records for this page, adding the pagination limit clause
		self::$participants = $wpdb->get_results( self::$list_query.' '.self::$pagination->getLimitSql(), ARRAY_A );

    // set some local variables for use in the template
    $filter_mode = self::_sort_filter_mode();
    $display_count = self::$shortcode_params['display_count'];
    $record_count = self::$num_records;
    $records = self::$participants;
    $fields = self::$display_columns;
		$single_record_link = get_page_link( self::$options['single_record_page'] );
		$records_per_page = self::$shortcode_params['list_limit'];
    $list_anchor = self::$list_anchor;
    $filtering = self::$shortcode_params['filtering'];
		
		// ok, setup finished, start outputting the form

    // begin buffering the output of the plugin
    ob_start();
    ?>
    <a name="<?php echo $list_anchor?>" id="<?php echo $list_anchor?>"></a>
    <div id="pdb-show-records" class="<?php self::wrap_class(); ?>">
    
      <?php include Participants_Db::get_template( 'list', self::$shortcode_params['template'] ); ?>
    
    </div>
		
    <?php
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
		$orderby = Participants_Db::is_column( $orderby ) ? $orderby : ( $orderby == 'random' ? 'random' : current( self::$sortables ) );
		self::$filter['sortBy'] = $orderby;
			
		$order = isset( self::$filter['ascdesc'] ) ? strtoupper( self::$filter['ascdesc'] ) : strtoupper( self::$shortcode_params['order'] );
		$order = in_array( $order, array( 'ASC', 'DESC' ) ) ? $order : 'ASC';
		self::$filter['ascdesc'] = strtolower($order);
		
		if ( $orderby == 'random' ) {
			self::$list_query = 'SELECT * FROM '.Participants_Db::$participants_table.' WHERE '.$skip_default.' ORDER BY RAND()';
		} else {
		self::$list_query = 'SELECT * FROM '.Participants_Db::$participants_table.' WHERE '.$skip_default.' ORDER BY `'.$orderby.'` '.$order;
		}
		
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
          
          // if its a MySQL TIMESTAMP we must make the comparison as a string
          if ( $field_atts->group == 'internal' ) {
            $target = date( 'Y-m-d H:i:s', $target );
          } else {
            $delimiter = array( 'CAST(',' AS SIGNED)' );
          }
					
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
			
			if ( $orderby == 'random' ) {
				self::$list_query = 'SELECT * FROM '.Participants_Db::$participants_table.' WHERE '.$where_clause.$skip_default.' ORDER BY RAND()';
			} else {
			self::$list_query = 'SELECT * FROM '.Participants_Db::$participants_table.' WHERE '.$where_clause.$skip_default.' ORDER BY `'.$orderby.'` '.$order;
			}
		}
		
	}

  /* TEMPLATE METHODS */

  /**
   * prints a stylesheet link
   *
   * @param string $sheet name of the stylesheet file; expected to be in the
   *                      theme directory; defaults to the plugin's default
   *                      stylesheet
   */
  public function add_stylesheet( $sheet = false ) {

    if ( false === $sheet ) $ss_url = plugins_url( Participants_Db::PLUGIN_NAME.'/css/PDb-list.css' );

    else $ss_url = get_stylesheet_directory_uri() . '/' . $sheet;

    wp_enqueue_style( 'pdb_list', $ss_url );

  }
  
  /**
   * prints the whole search/sort form as a shortcut function
   *
   */
  public function show_search_sort_form() {
    
    $output = array();
    
    if ( self::_sort_filter_mode() != 'none' && ! self::$shortcode_params['filtering'] ) {
      
      $output[] = '<div class="pdb-searchform">';
      $output[] = '<div class="pdb-error pdb-search-error" style="display:none">';
      $output[] = sprintf( '<p id="where_clause_error">%s</p>',__( 'Please select a column to search in.', Participants_Db::PLUGIN_NAME ) );
      $output[] = sprintf( '<p id="value_error">%s</p>', __( 'Please type in something to search for.', Participants_Db::PLUGIN_NAME ) );
      $output[] = '</div>';
      $output[] = self::search_sort_form_top(false,false,false);
        
      if ( self::_sort_filter_mode() == 'filter' || self::_sort_filter_mode() == 'both' ) {
  
        $output[] = '<fieldset class="widefat">';
        
          $output[] = sprintf ( '<legend>%s:</legend>', __('Search', Participants_Db::PLUGIN_NAME ) );
      
          $output[] = self::column_selector( false, false );
          $output[] = self::search_form( false );
        
        $output[] = '</fieldset>';
        
      }
      
      if ( self::_sort_filter_mode() == 'sort' || self::_sort_filter_mode() == 'both' ) {
      
        $output[] = '<fieldset class="widefat">';
        
          $output[] = sprintf( '<legend>%s:</legend>', __('Sort by', Participants_Db::PLUGIN_NAME ) );
          
          $output[] = self::sort_form( false );
        
        $output[] = '</fieldset>';
      
      }
  
      $output[] = '</div>';
      
    }
    
    echo self::output_HTML( $output );
    
  }
  
  /**
   * displays the field value
   *
   */
  public function display_field( $record, $field ) {
    
    $value = $record[$field];
    
    if ( empty( $value ) ) return;

    $output = array();

    // wrap the item in a link if it's enabled for this field
    if ( self::is_single_record_link( $field ) ) {
      $output[] = Participants_Db::make_link(
                    get_page_link( self::$options['single_record_page'] ), // URL of the single record page
        $value,                          // field value
        '<a href="%1$s" title="%2$s" >', // template for building the link
        array( 'pdb'=>$record['id'] )    // record ID to get the record
      );
    }
    /*
     * here is where we determine how each field value is presented,
     * depending on what kind of field it is
     */
    switch ( self::get_field_type( $field ) ) :
    
      case 'image-upload':
     
        $output[] = '<img class="PDb-list-image" src="'.Participants_Db::get_image_uri( $value ).'" />';
        break;
        
      case 'date':
      
        /*
         * if you want to specify a format, include it as the second 
         * argument in this function; otherwise, it will default to 
         * the site setting
         */
        $output[] = self::show_date( $value, false, false );
        break;
        
    case 'multi-select-other':
    case 'multi-checkbox':
    
      /*
       * this function shows the values as a comma separated list
       * you can customize the glue that joins the array elements
       */
      $output[] = self::show_array( $value, $glue = ', ', false );
      break;
      
    case 'link' :
      
      /*
       * prints a link (anchor tag with link text)
       * for the template:
       * %1$s is the URL
       * %2$s is the linked text
       */
      $output[] = self::show_link( $value, $template = '<a href="%1$s" >%2$s</a>', false );
      break;
      
    case 'textarea':
      
      /*
       * if you are displaying rich text you may want to process the 
       * output through wpautop like this: echo wpautop( $value ) see 
       * http://codex.wordpress.org/Function_Reference/wpautop
       */
      $output[] = '<span class="textarea">'.$value.'</span>';
      break;
      
    case 'text-line':
    default:
    
      /*
       * if the make links setting is enabled, try to make a link out of the field
       */
      if ( self::$options['make_links'] && ! self::is_single_record_link( $field ) ) {
        
        $output[] = Participants_Db::make_link( $value );
        
      } else {
        
        $output[] = esc_html( $value );
        
      }
    
    endswitch; // switch by field type
    
    // close the anchor tag if it's a link 
		if ( self::is_single_record_link( $field ) ) $output[] = '</a>';
    
    echo self::output_HTML( $output );
    
  }

  // generate a wrap class for the record list
  public function wrap_class( $add_class = '', $print = true ) {

    if ( $print ) echo 'pdb_list '.self::$shortcode_params['class'].' '.$add_class;
    else return 'pdb_list '.self::$shortcode_params['class'].' '.$add_class;

  }

  /**
   * prints the top of the search/sort form
   *
   * @param bool $target determines whether the form submits to the top of the
   *                     form or to the top of the page.
   */
  public function search_sort_form_top( $target = true, $class = false, $print = true ) {

    global $post;

    $output = array();

    $anchor = $target ? '#'.self::$list_anchor : '';
		$class_att = $class ? 'class="'.$class.'"' : ''; 
    $output[] = '<form method="post" id="sort_filter_form" action="'. get_page_link( $post->ID ). $anchor .'"'. $class_att.' >';
    $output[] = '<input type="hidden" name="action" value="pdb_list_filter">';
    
    if ( $print ) echo self::output_HTML( $output );
    else return self::output_HTML( $output );

  }

  //build the list of columns available for filtering
  public function column_selector( $all = false, $print = true ) {

    $all_string = false === $all ? '('.__('show all', Participants_Db::PLUGIN_NAME ).')' : $all;

    $filter_columns = array( $all_string => 'none' );

    foreach ( Participants_db::get_column_atts( 'frontend_list' ) as $column ) {

      if ( in_array( $column->name, array( 'id', 'private_id' ) ) ) continue;

      $filter_columns[ stripslashes($column->title) ] = $column->name;

    }

    $element = array(
                      'type'   => 'dropdown',
                      'name'   => 'where_clause',
                      'value'  =>  self::$filter['where_clause'],
                      'class'  => 'search-item',
                      'options'  => $filter_columns,
                      );
    if ( $print ) FormElement::print_element( $element );
    else return FormElement::get_element( $element );

  }

  public function search_form( $print = true ) {

    $output = array();

    $output[] = '<input name="operator" type="hidden" class="search-item" value="LIKE" />';
    $output[] = '<input id="participant_search_term" type="text" name="value" class="search-item" value="'. @self::$filter['value'] .'">';
    $output[] = '<input name="submit" type="submit" value="'.self::$i18n['search'].'">';
    $output[] = '<input name="submit" type="submit" value="'.self::$i18n['clear'].'">';

    if ( $print ) echo self::output_HTML($output);
    else return self::output_HTML($output);

  }

  public function sort_form( $print = true ) {
  
    $element = array(
                      'type'   => 'dropdown',
                      'name'   => 'sortBy',
                      'value'  =>  self::$filter['sortBy'],
                      'options'  => self::$sortables,
                      'class'  => 'search-item',
                      );
    $output[] = FormElement::get_element( $element );

    $element = array(
                      'type'   => 'radio',
                      'name'   => 'ascdesc',
                      'value'  =>  self::$filter['ascdesc'],
											'class'  => 'checkbox inline search-item',
                      'options'  => array(
                                        __('Ascending', Participants_Db::PLUGIN_NAME )  => 'asc',
                                        __('Descending', Participants_Db::PLUGIN_NAME ) => 'desc'
                                        ),
                      );
    $output[] = FormElement::get_element( $element );

    $output[] = '<input name="submit" type="submit" value="'.self::$i18n['sort'].'"';

    if ( $print ) echo self::output_HTML($output);
    else return self::output_HTML($output);

  }

  /**
   * get the column form element type
   */
  public function get_field_type( $column ) {

    $column_atts = Participants_Db::get_field_atts( $column, '`form_element`,`default`' );

    return $column_atts->form_element;

  }
	
	/**
	 * are we setting the single record link?
	 * returns boolean
	 */
  public function is_single_record_link( $column ) {

    return (
            isset( self::$options['single_record_link_field'] )
            &&
            $column == self::$options['single_record_link_field']
            &&
            ! empty( self::$options['single_record_page'] )
            &&
            ! in_array( self::get_field_type( $column ), array('textarea', 'link' ) )
            );

  }
	
	/**
	 * create a date/time string
	 */
	public function show_date( $value, $format = false, $print = true ) {
		
		$time = preg_match( '#^[0-9-]+$#', $value ) > 0 ? (int) $value : strtotime( $value );
		
		$dateformat = $format ? $format :get_option('date_format','r'); 
		
		if ( $print ) echo date( $dateformat, $time );
    else return date( $dateformat, $time );
		
	}
	
	public function show_array( $value, $glue = ', ', $print = true ) {
		
		$output = is_serialized( $value[ $column ] ) ? implode( $glue, unserialize( $value ) ) : $value;
		
    if ( $print ) echo $output;
    else return $output;
		
	}
  
  public function output_HTML( $output = array() ) {
    return implode( "\r", $output );
	}
	
	public function show_link( $value, $template = false, $print = false ) {
		
		if ( is_serialized( $value ) ) {
			
			$params = unserialize( $value[ $column ] );
			
			if ( count( $params ) < 2 ) $params[1] = $params[0];
			
		} else {
			
			// in case we got old unserialized data in there
			$params = array_fill( 0, 2, $value[ $column ] );
			
		}
		
		$oputput = Participants_Db::make_link( $params[0], $params[1], $template );
    
    if ( $print ) echo $output;
    else return $output;
							
	}
		

  /* BUILT-IN OUTPUT METHODS */

  
    
    
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