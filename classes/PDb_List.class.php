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
 
class PDb_List extends PDb_Shortcode {
  
	// a string identifier for the class
  var $module = 'list';
  
  // class for the wrapper
  var $wrap_class = 'pdb_list';
  
  // holds the current instance of the class
  static $instance;
	
	// holds the main query for building the list
	static $list_query;
	
	// translations strings for buttons
	var $i18n;
	
	// holds the pagination object
	var $pagination;

	// holds the number of list items to show per page
	var $page_list_limit;
	
	// the name of the list page variable
	var $list_page = 'listpage';
	
	// name of the list anchor element
	var $list_anchor = 'participants-list';

	// holds the url of the registrations page
  var $registration_page_url;

	// holds the columns to display in the list
	var $display_columns;

	// holds the list of sortable columns
	var $sortables;

	// holds the parameters for a shortcode-called display of the list
	var $shortcode_params;
	
	// holds the settings for the list filtering and sorting
	var $filter;
	
	// holds plugin options array
	var $options;

	// transient for holding the shortcode parameters
	static $shortcode_transient = 'PDb_list_shortcode';
	
  // holds the wrapper HTML for the pagination control
  // the first two elements wrap the whole control, the third wraps the buttons, the fourth wraps each button
  var $pagination_wrap = array(
                               'open'        =>'<div class="pagination"><label>%s:</label> ',
                               'close'       =>'</div>',
                               'all_buttons' =>'ul',
                               'button'      =>'li',
                               );
	
	/**
	 * initializes and outputs the list on the frontend as called by the shortcode
	 *
	 * @param array $atts display customization parameters
	 *                    from the shortcode
	 */
	public function __construct( $params ) {

    // set the list limit value; this can be overridden by the shortcode atts later
    $this->page_list_limit = ( ! isset( $_POST['list_limit'] ) or ! is_numeric( $_POST['list_limit'] ) or $_POST['list_limit'] < 1 ) ? Participants_Db::$plugin_options['list_limit'] : $_POST['list_limit'];

    // define the default settings for the shortcode
    $shortcode_defaults = array(
                                'sort'          => 'false',
                                'search'        => 'false',
                                'list_limit'    => $this->page_list_limit,
                                'class'         => 'participants-database',
                                'filter'        => '',
                                'orderby'			  => 'date_recorded',
                                'order'         => 'desc',
                                'fields'		  	=> '',
                                'display_count' => 'false',
                                'template'      => 'default',
                                'filtering'     => false, // this is set to true if we're coming here from an AJAX call
                                );

    // run the parent class initialization to set up the parent methods 
    parent::__construct( $this, $params, $shortcode_defaults );

    //error_log( __METHOD__.' '.print_r( $this,1 ));
		
    $this->registration_page_url = get_bloginfo('url').'/'.( isset( $this->options['registration_page'] ) ? $this->options['registration_page'] : '' );
		
    $this->_set_display_columns();
		
    $this->sortables = Participants_Db::get_sortables();
		
		$this->_setup_i18n();
			
    set_transient( self::$shortcode_transient, $this->shortcode_atts, 3600);
			
    // enqueue the filter/sort AJAX script
    if ( $this->_sort_filter_mode() !== 'none' ) wp_enqueue_script( 'list-filter' );
			
    // set up the iteration data
    $this->_setup_iteration();
			
    $this->_print_from_template();
					
	}
				
	/**
	 * prints a signup form called by a shortcode
	 *
	 * this function is called statically to instantiate the PDb_List object,
	 * which captures the output and returns it for display
	 *
	 * @param array $params parameters passed by the shortcode
	 * @return string form HTML
	 */
	public static function print_record( $params ) {
						
		if ( ! isset( self::$instance ) ) self::$instance = new PDb_List( $params );
		
		return self::$instance->output;
					
				}
				
  /**
   * includes the shortcode template
   */
  protected function _include_template() {
			
    // set some local variables for use in the template
    $filter_mode = $this->_sort_filter_mode();
    $display_count = $this->shortcode_atts['display_count'];
    $record_count = $this->num_records;
    $records = $this->records;
    $fields = $this->display_columns;
		$single_record_link = get_page_link( $this->options['single_record_page'] );
		$records_per_page = $this->shortcode_atts['list_limit'];
    $filtering = $this->shortcode_atts['filtering'];
		
    include $this->template;
		
  }
		
  /**
   * sets up the template iteration object
   *
   * this takes all the fields that are going to be displayed and organizes them
   * under their group so we can easily run through them in the template
   */
  protected function _setup_iteration() {
		
		// process any search/filter/sort terms and build the main query
		$this->_build_shortcode_query();
		
		// get the $wpdb object
		global $wpdb;
		
		// get the number of records returned
		$this->num_records = $wpdb->get_var( preg_replace( '#^SELECT.+FROM#', 'SELECT COUNT(*) FROM', $this->list_query ) );
		
    // set up the pagination object
    $pagination_defaults = array(
          'link'                => $this->get_page_link( $_SERVER['REQUEST_URI'] ),
          'page'                => isset( $_GET[ $this->list_page ] ) ? $_GET[ $this->list_page ] : '1',
          'size'                => $this->shortcode_atts['list_limit'],
          'total_records'       => $this->num_records,
					);
		
    // instantiate the pagination object
    $this->pagination = new Pagination( $pagination_defaults );
		
		/*
     * get the records for this page, adding the pagination limit clause
     *
     * this gives us an array of objects, each one a set of field->value pairs
     */
		$records = $wpdb->get_results( $this->list_query.' '.$this->pagination->getLimitSql(), OBJECT );

    foreach ( $records as $record ) {
      
      $id = $record->id;
      unset( $record->id );
      
      $this->records[ $id ] = $record;
      
    }
      
    //error_log( __METHOD__.' all records:'.print_r( $this->records,1));
    
    foreach ( $this->records as &$record ) {
    
      $this->participant_values = (array) $record;
      
      //error_log( __METHOD__.' participant_values:'.print_r( $this->participant_values ,1));
      
      foreach( $record as $field => $value ) {
      
        $field_object = $this->_get_record_field( $field );
          
        // set the current value of the field
        $this->_set_field_value( $field_object );
        
        //error_log( __METHOD__.' record field:'.print_r( $field_object ,1));
        
        // add the field to the list of fields
        $this->columns[] = $field;
        
        // add the field to the record object
        $record->{$field_object->name} = $field_object;
        
      }
      
    }
    
    /*
     * at this point, $this->records has been defined as an array of records,
     * each of which is an object that is a collection of objects: each one of
     * which is the data for a field
     */
    //error_log( __METHOD__.' all records:'.print_r( $this->records,1));
    
  }
  
  private function modify_record_iterator( $record ) {
			
	}
  
	/**
	 * processes shortcode filters and sorts to build the listing query
	 *
	 */
	private function _build_shortcode_query() {
    
    // set up the column select string for the queries
    $column_select = "`id`,`".implode( "`,`", $this->display_columns )."`";
		
		// set up the basic values; sort values come from the shortcode
		$default_values = array(
														'where_clause' => 'none',
														'value'        => '',
														'operator'     => 'LIKE',
														'sortBy'       => $this->shortcode_atts['orderby'],
														'ascdesc'      => $this->shortcode_atts['order']
														);
		
		// merge the defaults with the $_REQUEST array so if there are any new values coming in, they're included
		$this->filter = shortcode_atts( $default_values, $_REQUEST );

    // add this to the query to remove the default record
    $skip_default = ' `id` != '.Participants_Db::$id_base_number;
		
		// if we've got a valid orderby, use it. Check $_POST first, shortcode second
		$orderby = isset( $this->filter['sortBy'] ) ? $this->filter['sortBy'] : $this->shortcode_atts['orderby'];
		$orderby = Participants_Db::is_column( $orderby ) ? $orderby : ( $orderby == 'random' ? 'random' : current( $this->sortables ) );
		$this->filter['sortBy'] = $orderby;
			
		$order = isset( $this->filter['ascdesc'] ) ? strtoupper( $this->filter['ascdesc'] ) : strtoupper( $this->shortcode_atts['order'] );
		$order = in_array( $order, array( 'ASC', 'DESC' ) ) ? $order : 'ASC';
		$this->filter['ascdesc'] = strtolower($order);
		
		if ( $orderby == 'random' ) {
			$this->list_query = 'SELECT '.$column_select.' FROM '.Participants_Db::$participants_table.' WHERE '.$skip_default.' ORDER BY RAND()';
		} else {
		$this->list_query = 'SELECT '.$column_select.' FROM '.Participants_Db::$participants_table.' WHERE '.$skip_default.' ORDER BY `'.$orderby.'` '.$order;
		}
		
		if  ( isset( $_POST['submit'] ) && $_POST['submit'] == $this->i18n['clear'] ) {
			
				$this->filter['value'] = '';
				$this->filter['where_clause'] = 'none';
				
				// go back to the first page
				$_GET[ $this->list_page ] = 1;
				
		}
		
		$where_clause = '';
				
		if ( isset( $this->shortcode_atts['filter'] ) ) {
				
			$statements = explode( '&', html_entity_decode($this->shortcode_atts['filter']) );
			
			foreach ( $statements as $statement ) {
				
				$operator = preg_match( '#(\S+)(\>|\<|=|!|~)(\S+)#', str_replace(' ','', $statement ), $matches );
				
				if ( $operator === 0 ) continue;// no valid operator; skip to the next statement
				
				// get the parts
				list( $string, $column, $op_char, $target ) = $matches;
				
				if ( ! Participants_Db::is_column( $column ) or ( ! empty( $this->filter['value'] ) && $column == $this->filter['where_clause'] ) ) {

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
			if ( isset( $this->filter['value'] ) && ! empty( $this->filter['value'] ) && 'none' != $this->filter['where_clause'] ) {

        $pattern = $this->options['strict_search'] ? '`%s` = "%s" AND ' : '`%s` LIKE "%%%s%%" AND ';

        $where_clause .= sprintf( $pattern, $this->filter['where_clause'],$this->filter['value'] );

      }
			
			if ( $orderby == 'random' ) {
				$this->list_query = 'SELECT '.$column_select.' FROM '.Participants_Db::$participants_table.' WHERE '.$where_clause.$skip_default.' ORDER BY RAND()';
			} else {
			$this->list_query = 'SELECT '.$column_select.' FROM '.Participants_Db::$participants_table.' WHERE '.$where_clause.$skip_default.' ORDER BY `'.$orderby.'` '.$order;
			}
		}
		
		if ( WP_DEBUG ) error_log( __METHOD__.' list query= '.$this->list_query );
		
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
    
    if ( $this->_sort_filter_mode() != 'none' && ! $this->shortcode_atts['filtering'] ) {
      
      $output[] = '<div class="pdb-searchform">';
      $output[] = '<div class="pdb-error pdb-search-error" style="display:none">';
      $output[] = sprintf( '<p id="where_clause_error">%s</p>',__( 'Please select a column to search in.', Participants_Db::PLUGIN_NAME ) );
      $output[] = sprintf( '<p id="value_error">%s</p>', __( 'Please type in something to search for.', Participants_Db::PLUGIN_NAME ) );
      $output[] = '</div>';
      $output[] = $this->search_sort_form_top(false,false,false);
        
      if ( $this->_sort_filter_mode() == 'filter' || $this->_sort_filter_mode() == 'both' ) {
  
        $output[] = '<fieldset class="widefat">';
        
          $output[] = sprintf ( '<legend>%s:</legend>', __('Search', Participants_Db::PLUGIN_NAME ) );
      
          $output[] = $this->column_selector( false, false );
          $output[] = $this->search_form( false );
        
        $output[] = '</fieldset>';
        
      }
      
      if ( $this->_sort_filter_mode() == 'sort' || $this->_sort_filter_mode() == 'both' ) {
      
        $output[] = '<fieldset class="widefat">';
        
          $output[] = sprintf( '<legend>%s:</legend>', __('Sort by', Participants_Db::PLUGIN_NAME ) );
          
          $output[] = $this->sort_form( false );
        
        $output[] = '</fieldset>';
      
      }
  
      $output[] = '</div>';
      
    }
    
    echo $this->output_HTML( $output );
    
  }
  
  /**
   * displays the field value
   *
   * DEPRICATED
   *
   */
  public function display_field( $record, $field ) {
    
    error_log( __METHOD__.' DEPRICATED func got called');
    
    $value = $record[$field];
    
    if ( empty( $value ) ) return;

    $output = array();

    // wrap the item in a link if it's enabled for this field
    if ( $this->is_single_record_link( $field ) ) {
      $output[] = Participants_Db::make_link(
                    get_page_link( $this->options['single_record_page'] ), // URL of the single record page
        $value,                          // field value
        '<a href="%1$s" title="%2$s" >', // template for building the link
        array( 'pdb'=>$record['id'] )    // record ID to get the record
      );
    }
    /*
     * here is where we determine how each field value is presented,
     * depending on what kind of field it is
     */
    switch ( $this->get_field_type( $field ) ) :
    
      case 'image-upload':
     
        $output[] = '<img class="PDb-list-image" src="'.Participants_Db::get_image_uri( $value ).'" />';
        break;
        
      case 'date':
      
        /*
         * if you want to specify a format, include it as the second 
         * argument in this function; otherwise, it will default to 
         * the site setting
         */
        $output[] = $this->show_date( $value, false, false );
        break;
        
    case 'multi-select-other':
    case 'multi-checkbox':
    
      /*
       * this function shows the values as a comma separated list
       * you can customize the glue that joins the array elements
       */
      $output[] = $this->show_array( $value, $glue = ', ', false );
      break;
      
    case 'link' :
      
      /*
       * prints a link (anchor tag with link text)
       * for the template:
       * %1$s is the URL
       * %2$s is the linked text
       */
      $output[] = $this->show_link( $value, $template = '<a href="%1$s" >%2$s</a>', false );
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
      if ( $this->options['make_links'] && ! $this->is_single_record_link( $field ) ) {
        
        $output[] = Participants_Db::make_link( $value );
        
      } else {
        
        $output[] = esc_html( $value );
        
      }
    
    endswitch; // switch by field type
    
    // close the anchor tag if it's a link 
		if ( $this->is_single_record_link( $field ) ) $output[] = '</a>';

    echo $this->output_HTML( $output );

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

    $anchor = $target ? '#'.$this->list_anchor : '';
		$class_att = $class ? 'class="'.$class.'"' : ''; 
    $output[] = '<form method="post" id="sort_filter_form" action="'. get_page_link( $post->ID ). $anchor .'"'. $class_att.' >';
    $output[] = '<input type="hidden" name="action" value="pdb_list_filter">';
    
    if ( $print ) echo $this->output_HTML( $output );
    else return $this->output_HTML( $output );

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
                      'value'  =>  $this->filter['where_clause'],
                      'class'  => 'search-item',
                      'options'  => $filter_columns,
                      );
    if ( $print ) FormElement::print_element( $element );
    else return FormElement::get_element( $element );

  }

  public function search_form( $print = true ) {

    $output = array();

    $output[] = '<input name="operator" type="hidden" class="search-item" value="LIKE" />';
    $output[] = '<input id="participant_search_term" type="text" name="value" class="search-item" value="'. @this->filter['value'] .'">';
    $output[] = '<input name="submit" type="submit" value="'.$this->i18n['search'].'">';
    $output[] = '<input name="submit" type="submit" value="'.$this->i18n['clear'].'">';

    if ( $print ) echo $this->output_HTML($output);
    else return $this->output_HTML($output);

  }

  public function sort_form( $print = true ) {
  
    $element = array(
                      'type'   => 'dropdown',
                      'name'   => 'sortBy',
                      'value'  =>  $this->filter['sortBy'],
                      'options'  => $this->sortables,
                      'class'  => 'search-item',
                      );
    $output[] = FormElement::get_element( $element );

    $element = array(
                      'type'   => 'radio',
                      'name'   => 'ascdesc',
                      'value'  =>  $this->filter['ascdesc'],
											'class'  => 'checkbox inline search-item',
                      'options'  => array(
                                        __('Ascending', Participants_Db::PLUGIN_NAME )  => 'asc',
                                        __('Descending', Participants_Db::PLUGIN_NAME ) => 'desc'
                                        ),
                      );
    $output[] = FormElement::get_element( $element );

    $output[] = '<input name="submit" type="submit" value="'.$this->i18n['sort'].'"';

    if ( $print ) echo $this->output_HTML($output);
    else return $this->output_HTML($output);

  }
  
  /**
   * prints the pagination controls to the template
   *
   * this does nothing if filtering is taking place
   *
   */
  public function show_pagination_control() {
    
    // this only happens if we're not processing an AJAX call
    if ( ! $this->shortcode_atts['filtering'] )  {
      
      // set the wrapper HTML parameters
      $this->pagination->set_all_wrappers( $this->pagination_wrap );
      
      // print the control
      echo $this->pagination->create_links();
      
    }
    
  }
  
  /**
   * sets the pagination control HTML
   *
   * @param string $open the opening HTML for the whole control
   * @param string $close the close HTML for the whole control
   * @param string $all_buttons the wrap tag for the buttons
   * @param string $button the tag that wraps each button (which is an 'a' tag)
   */
  protected function set_pagination_wrap( $open='', $close='', $all_buttons='', $button='' ) {
    
    foreach ( array('open','close','all_buttons','button') as $tag ) {
      
      if ( isset( $$e ) and ! empty( $$e ) ) $this->pagination_wrap[$e] = $$e;
    
    }
    
  }
  
  /**
   * sets the columns to display in the list
   *
   */
  private function _set_display_columns() {
		
		// allow for an arbitrary fields definition list in the shortcode
		if ( ! empty( $this->shortcode_atts['fields'] ) ) {
			
			$raw_list = explode( ',', str_replace( array( "'",'"',' ',"\r" ), '', $this->shortcode_atts['fields'] ) );
			
			if ( is_array( $raw_list ) ) :
			
				//clear the array
				$this->display_columns = array();
			
				foreach( $raw_list as $column ) {
					
					if ( Participants_Db::is_column( $column ) ) {

						$this->display_columns[] = $column;
						
					}
					
				}
				
			endif;
			
		} else {
      
      $this->display_columns = Participants_Db::get_list_display_columns( 'display_column' );
    
    }

  }

  /**
   * get the column form element type
   *
   * DEPRICATED
   */
  public function get_field_type( $column ) {

    error_log( __METHOD__.' DEPRICATED func got called');

    $column_atts = Participants_Db::get_field_atts( $column, '`form_element`,`default`' );

    return $column_atts->form_element;

  }
	
	/**
	 * are we setting the single record link?
	 * returns boolean
	 */
  public function is_single_record_link( $column ) {

    error_log( __METHOD__.' DEPRICATED func got called');

    return (
            isset( $this->options['single_record_link_field'] )
            &&
            $column == $this->options['single_record_link_field']
            &&
            ! empty( $this->options['single_record_page'] )
            &&
            ! in_array( $this->get_field_type( $column ), array('textarea', 'link' ) )
            );

  }
	
	/**
	 * create a date/time string
	 */
	public function show_date( $value, $format = false, $print = true ) {
		
    error_log( __METHOD__.' DEPRICATED func got called');
		
		$time = preg_match( '#^[0-9-]+$#', $value ) > 0 ? (int) $value : strtotime( $value );
		
		$dateformat = $format ? $format :get_option('date_format','r'); 
		
		if ( $print ) echo date( $dateformat, $time );
    else return date( $dateformat, $time );
		
	}
	
	public function show_array( $value, $glue = ', ', $print = true ) {
		
    error_log( __METHOD__.' DEPRICATED func got called');
		
		$output = is_serialized( $value[ $column ] ) ? implode( $glue, unserialize( $value ) ) : $value;
		
    if ( $print ) echo $output;
    else return $output;
		
	}
  
  public function output_HTML( $output = array() ) {
    return implode( "\r", $output );
	}
	
	public function show_link( $value, $template = false, $print = false ) {
		
    error_log( __METHOD__.' DEPRICATED func got called');
		
		if ( is_serialized( $value ) ) {
			
			$params = unserialize( $value );
			
			if ( count( $params ) < 2 ) $params[1] = $params[0];
			
		} else {
			
			// in case we got old unserialized data in there
			$params = array_fill( 0, 2, $value );
			
		}
		
		$output = Participants_Db::make_link( $params[0], $params[1], $template );
    
    if ( $print ) echo $output;
    else return $output;
							
	}
		

  /* BUILT-IN OUTPUT METHODS */

  
    
    
    /**
     * prints a table header row
     */
    private function _print_header_row( $head_pattern ) {

      // print the top header row
      foreach ( $this->display_columns as $column ) {
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

        unset( $values[ $this->list_page ] );

      }

      return $URI_parts[0].'?'.$this->_filter_query( $values ).$this->list_page.'=%s#'.$this->list_anchor;

    }

    /**
     * builds the sort-filter mode setting
     */
    private function _sort_filter_mode() {

      $mode = $this->shortcode_atts['sort'] == 'true' ? 'sort' : 'none';

      return $this->shortcode_atts['search'] == 'true' ? ( $mode == 'sort' ? 'both' : 'filter' ) : $mode ;

    }
    
    /**
     * builds a URI querys string from the filter parameters
     *
     * @param  array  $values the incoming finter values
     * @return string URL-encoded filter parameters, empty string if filter is not active
     */
    private function _filter_query( $values ) {
      
      if ( ! empty( $values ) and $values['where_clause'] != 'none' ) {
        
        return http_build_query( array_merge( $values, $this->filter ) ).'&';

      } else return '';

    }		
		
		/**
		 * takes the $_POST array and constructs a filter statement to add to the list shortcode filter
		 */
		private function _make_filter_statement( $post ) {
			 
			 if ( ! Participants_Db::is_column( $post['where_clause'] ) ) return '';
			 
			 $this->filter['where_clause'] = $post['where_clause'];
			 
			 
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
			 
			$this->filter['operator'] = $operator;
			 
			if ( empty( $post['value'] ) ) return '';
			
			$this->filter['value'] = $post['value'];
			
			return $this->filter['where_clause'].$this->filter['operator'].$this->filter['value'];
			
		}
		
		
		/**
		 * sets up the internationalization strings
		 */
		private function _setup_i18n() {
		
			/* translators: the following 5 strings are used in logic matching, please test after translating in case special characters cause problems */
			$this->i18n = array(
				'delete_checked' => _x( 'Delete Checked', 'submit button label', Participants_Db::PLUGIN_NAME ),
				'change' => _x( 'Change', 'submit button label', Participants_Db::PLUGIN_NAME ),
				'sort' => _x( 'Sort', 'submit button label', Participants_Db::PLUGIN_NAME ),
				'filter' => _x( 'Filter', 'submit button label', Participants_Db::PLUGIN_NAME ),
				'clear' => _x( 'Clear', 'submit button label', Participants_Db::PLUGIN_NAME ),
        'search' => _x( 'Search', 'search button label', Participants_Db::PLUGIN_NAME ),
			);
		
		}
	
}// class ?>