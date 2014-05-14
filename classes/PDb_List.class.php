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
 * @version    Release: 1.5
 * @link       http://wordpress.org/extend/plugins/participants-database/
 */
class PDb_List extends PDb_Shortcode {
  
  /**
   * @var object temporarily holds an instance of the object
   */
  static $instance;
  /**
   *
   * @var string holds the main query for building the list
   */
  var $list_query;
  /**
   *
   * @var array translations strings for buttons
   */
  var $i18n;
  /**
   *
   * @var int holds the number of list items to show per page
   */
  var $page_list_limit;
  /**
   *
   * @var string the name of the list page variable
   */
  var $list_page = 'listpage';
  /**
   *
   * @var string name of the list anchor element
   */
  var $list_anchor = 'participants-list';
  /**
   *
   * @var string holds the url of the registrations page
   */
  var $registration_page_url;
  /**
   *
   * @var string holds the url to the single record page
   */
  var $single_record_url = false;
  /**
   *
   * @var array holds the list of sortable columns
   */
  var $sortables;
  /**
   *
   * @var array holds the settings for the list filtering and sorting
   */
  var $filter;
  /**
   *
   * @var string holds the search error style statement
   */
  var $search_error_style = '';
  /**
   * the wrapper HTML for the pagination control
   * 
   * the first two elements wrap the whole control, the third wraps the buttons, 
   * the fourth wraps each button
   * 
   * @var array wrapper HTML elements
   */
  var $pagination_wrap = array(
      'open' => '<div class="pagination"><label>%s:</label> ',
      'close' => '</div>',
      'all_buttons' => 'ul',
      'button' => 'li',
  );
  /**
   * this is set as the filters and search parameters are assembled
   *    
   * @var bool the suppression state: true suppresses list output
   */
  var $suppress = false;
  /**
   * set to true if list is the result of a search
   * 
   * @var bool
   */
  var $is_search_result = false;

  /**
   * initializes and outputs the list on the frontend as called by the shortcode
   *
   * @param array $shortcode_atts display customization parameters
   *                              from the shortcode
   */
  public function __construct($shortcode_atts) {

    // set the list limit value; this can be overridden by the shortcode atts later
    $this->page_list_limit = intval((!isset($_POST['list_limit']) or !is_numeric($_POST['list_limit']) or $_POST['list_limit'] < 1 ) ? Participants_Db::$plugin_options['list_limit'] : $_POST['list_limit']);

    // define the default settings for the shortcode
    $shortcode_defaults = array(
        'sort' => 'false',
        'search' => 'false',
        'list_limit' => $this->page_list_limit,
        'class' => 'participants-database',
        'filter' => '',
        'orderby' => Participants_Db::$plugin_options['list_default_sort'],
        'order' => Participants_Db::$plugin_options['list_default_sort_order'],
        'fields' => '',
        'single_record_link' =>'',
        'display_count' => Participants_Db::$plugin_options['show_count'],
        'template' => 'default',
        'module' => 'list',
        'action' => '',
        'suppress' => '',
    );

    // run the parent class initialization to set up the parent methods 
    parent::__construct($shortcode_atts, $shortcode_defaults);

//    error_log( __METHOD__.' $this->shortcode_atts:'.print_r( $this->shortcode_atts,1 ));

    $this->registration_page_url = get_bloginfo('url') . '/' . ( isset(Participants_Db::$plugin_options['registration_page']) ? Participants_Db::$plugin_options['registration_page'] : '' );

    /*
     * set the initial sortable field list; this is the set of all fields that are 
     * both marked "sortable" and currently displayed in the list
     */
    $this->sortables = Participants_Db::get_sortables();
//    $this->sortables = Participants_Db::get_sortables(Participants_Db::get_sortables() + $this->display_columns);

    $this->_setup_i18n();
    
    $this->_set_single_record_url();
    
    /*
     * if the 'suppress' shortcode attribute is set
     */
    if (!empty($this->shortcode_atts['suppress'])) $this->suppress = true;

    // enqueue the filter/sort AJAX script
    if ($this->_sort_filter_mode() !== 'none' and Participants_Db::$plugin_options['ajax_search'] == 1) {

      global $wp_query;

      $ajax_params = array(
          'ajaxurl' => admin_url('admin-ajax.php'),
          'filterNonce' => wp_create_nonce(Participants_Db::$prefix . 'list-filter-nonce'),
          'postID' => ( isset($wp_query->post) ? $wp_query->post->ID : '' ),
          'prefix' => Participants_Db::$prefix,
          'loading_indicator' => Participants_Db::get_loading_spinner(),
          'i18n' => $this->i18n
      );
      
      wp_localize_script(Participants_Db::$prefix.'list-filter', 'PDb_ajax', $ajax_params);
      
      wp_enqueue_script(Participants_Db::$prefix.'list-filter');
    }

    // set up the iteration data
    $this->_setup_iteration();

    $this->_print_from_template();
  }

  /**
   * returns the list
   * 
   * @deprecated since version 1.5
   * @var array $atts the shortcode attributes array
   * @return string the HTML
   */
  public static function print_record($atts) {
    return self::get_list($atts);
  }

  /**
   * prints a list of records called by a shortcode
   *
   * this function is called statically to instantiate the PDb_List object,
   * which captures the output and returns it for display
   *
   * @param array $shortcode_atts parameters passed by the shortcode
   * @return string form HTML
   */
  public static function get_list($shortcode_atts) {

    self::$instance = new PDb_List($shortcode_atts);

    return self::$instance->output;
  }

  /**
   * includes the shortcode template
   */
  public function _include_template() {

    // set some local variables for use in the template
    $filter_mode = $this->_sort_filter_mode();
    $display_count = isset($this->shortcode_atts['display_count']) ? ($this->shortcode_atts['display_count'] == 'true' or $this->shortcode_atts['display_count'] == '1') : false;
    $record_count = $this->num_records;
    $records = $this->records;
    $fields = $this->display_columns;
    $single_record_link = $this->single_record_url;
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
  public function _setup_iteration() {

    // process any search/filter/sort terms and build the main query
    $this->_build_shortcode_query();

    // allow the query to be altered before the records are retrieved
    $this->list_query = Participants_Db::set_filter('list_query',$this->list_query);

    // get the $wpdb object
    global $wpdb;

    // get the number of records returned
    $this->num_records = $wpdb->get_var(preg_replace('#^SELECT.+FROM #', 'SELECT COUNT(*) FROM ', $this->list_query));
    
    if ($this->shortcode_atts['list_limit'] < 1 || $this->shortcode_atts['list_limit'] > $this->num_records) {
      $this->page_list_limit = $this->shortcode_atts['list_limit'] = $this->num_records;
    }
    
    // set up the pagination object
    $pagination_defaults = array(
        'link' => $this->prepare_page_link( $this->shortcode_atts['filtering'] ? urldecode($_POST['pagelink']) : $_SERVER['REQUEST_URI'] ),
        'page' => isset($_GET[$this->list_page]) ? $_GET[$this->list_page] : '1',
        'size' => $this->shortcode_atts['list_limit'],
        'total_records' => $this->num_records,
        'filtering' => $this->shortcode_atts['filtering'],
        'add_variables' => http_build_query($this->filter) . '#' . $this->list_anchor,
    );

    // instantiate the pagination object
    $this->pagination = new PDb_Pagination($pagination_defaults);
    /*
     * get the records for this page, adding the pagination limit clause
     *
     * this gives us an array of objects, each one a set of field->value pairs
     */
    $records = $wpdb->get_results($this->list_query . ' ' . $this->pagination->getLimitSql(), OBJECT);

    /*
     * build an array of record objects, indexed by ID
     */
    foreach ($records as $record) {

      $id = $record->id;
      if (!in_array('id',$this->display_columns)) unset($record->id);

      $this->records[$id] = $record;
    }

    if (!empty($this->records)) {

      foreach ($this->records as $id => &$record) {

        $this->participant_values = (array) $record;
        $this->participant_values['id'] = $id;

        //error_log( __METHOD__.' participant_values:'.print_r( $this->participant_values ,1));
          
        foreach ($record as $field => $value) {
          
          

          $field_object = $this->_get_record_field($field);
          // set the current value of the field
          $this->_set_field_value($field_object);

          $this->_set_field_link($field_object);

          // add the field to the list of fields
          $this->columns[$field_object->name] = $field_object;

          // add the field to the record object
          $record->{$field_object->name} = $field_object;
        }
      }
    }

    /*
     * at this point, $this->records has been defined as an array of records,
     * each of which is an object that is a collection of objects: each one of
     * which is the data for a field
     */
    // error_log( __METHOD__.' all records:'.print_r( $this->records,1));
  }

  private function modify_record_iterator($record) {
    
  }

  /**
   * processes shortcode filters and sorts to build the listing query
   * 
   *
   */
  private function  _build_shortcode_query() {

    /*
     * set up the column select string for the queries
     */
    $column_select = 'p.id';
    if (!empty($this->display_columns)) {
      if (in_array('id', $this->display_columns)) {
        $column_select = ' p.' . implode(", p.", $this->display_columns);
      } else {
        $column_select .= ', p.' . implode(", p.", $this->display_columns);
      }
    }
    
    /*
     * set up the basic values; sort values come from the shortcode
     */
    $default_values = array(
        'search_field' => 'none',
        'value'        => '',
        'operator'     => 'LIKE',
        'sortBy'       => $this->shortcode_atts['orderby'],
        'ascdesc'      => $this->shortcode_atts['order'],
        'submit'       => '',
        'sortstring'   => $this->shortcode_atts['orderby'],
        'orderstring'  => $this->_get_orderstring(),
    );
    
    /*
     * translate the "submit_button" value into the "submit" value of the filter
     */
    if (isset($_GET['submit_button'])) $_GET['submit'] = $_GET['submit_button'];
    if (isset($_POST['submit_button'])) $_POST['submit'] = $_POST['submit_button'];

    /* filtering parameters can come from three sources: the shortcode, $_POST (AJAX) 
     * and $_GET (pagination and non-AJAX search form submissions) We merge the $_POST 
     * and $_GET values with the defaults to get our initial set of filtering parameters. 
     * Then we process the shortcode filter, skipping any specific column filters that 
     * were brought in from the $_POST or $_GET. The processed values are kept in the 
     * filter property
     */
    if (isset($_POST['action']) && $_POST['action'] == 'pdb_list_filter') {
      $this->filter = shortcode_atts($default_values, array_map('urldecode',$_POST));
    } elseif (isset($_GET['operator']) && !empty($_GET['operator'])) {
      $this->filter = shortcode_atts($default_values, array_map('urldecode',$_GET));
    } else {
      $this->filter = $default_values;
    }
    
    // prevent list page value from carrying over to next query
    unset($this->filter[$this->list_page]);

    // get the ORDER BY clause
    $order_clause = $this->_build_order_clause();

    /* at this point, we have our base query, now we need to add any WHERE clauses
     */
//    error_log(__METHOD__.' filter property:'.print_r($this->filter,1));
//    error_log(__METHOD__.' shortcode_atts:'.print_r($this->shortcode_atts,1));

    // add the shortcode filtering statements
    $clauses = $this->_process_shortcode_filter();
    
//    error_log(__METHOD__.' clauses from the shortcode:'.print_r($clauses,1));

    /*
     * process the user search, which has been placed in the filter property. These 
     * values must be made secure as it is direct input from the browser 
     */
    
    if (  empty($this->filter['submit']) ) {
      // do nothing
    } elseif ($this->filter['submit'] == $this->i18n['clear']) {

      // process the "clear" submission
      $this->filter['value'] = '';
      $this->filter['search_field'] = 'none';

      // a "clear" will take us back to the first page
      $_GET[$this->list_page] = 1;
      $this->filter['submit'] = '';
      $this->is_search_result = false;
    } elseif (
            !empty($this->filter['value']) &&
            'none' != $this->filter['search_field']
    ) {
      
      $this->is_search_result = true;
      // grab the attributes of the search field
      $search_field = Participants_Db::get_field_atts($this->filter['search_field']);
      
      if (!is_object($search_field)) {
        
        break; // not a valid field
        
      } elseif ($search_field->form_element == 'date') {
        /*
         * process date and timestamp searches
         */
        $filter_value = Participants_Db::parse_date($this->to_utf8($this->filter['value']), $search_field, true); // $this->to_utf8($this->filter['value'])
        
        if ($filter_value) {
          /*
           * regular date fields are stored as signed integers (UNIX timestamp) 
           * and are simply compared as such
           */
          $clause_pattern = " CAST(p.%s AS SIGNED) = CAST(%s AS SIGNED) ";
        }
          
      } elseif ($search_field->form_element == 'timestamp') {
        
        /*
         * process date and timestamp searches
         */
        $filter_value = date('Y-m-d H:i:s',Participants_Db::parse_date($this->to_utf8($this->filter['value']), $search_field, false)); // $this->to_utf8($this->filter['value'])
        if ($filter_value) {
          /*
           * if the field is a date, the value is stored as a Unix TS, so we must 
           * convert it. If the field is a timestamp, there is no need to convert 
           * the value
           */
          $clause_pattern = "DATE(p.%s) = DATE('%s') ";
        } else break;// date could not be parsed
        
      } else {
        
        /*
         * process regular text searches
         */
        $this->filter['value'] = $this->to_utf8($this->filter['value']);
        /*
         * if the search term contains a '*' wildcard, use it, otherwise, we assume the 
         * search string can be anywhere in the target string (except in strict mode)
         */
        $wildcard = '%%';
        $operator =  'LIKE';
        $filter_value = str_replace(array('_', '%'), array('\_', '\%'), $this->filter['value']);
        if (strpos($this->filter['value'], '*') !== false) {
          $wildcard =  '';
          $filter_value = str_replace('*', '%', $filter_value);
          $this->filter['operator'] = 'LIKE';
        } elseif ($this->filter['operator'] === 'EQ') {
          $operator = '=';
          $wildcard = '';
        }
        $filter_value = esc_sql($filter_value);

        if (Participants_Db::$plugin_options['strict_search']) {
          if (in_array($search_field->form_element, array('multi-checkbox', 'multi-select-other'))) {
            $clause_pattern = ' p.%s LIKE \'' . $wildcard . '"%s"' . $wildcard . '\'';
          } else {
            $clause_pattern = 'p.%s = "%s"';
          }
        } else {
          $clause_pattern = 'p.%s ' . $operator . ' "' . $wildcard . '%s' . $wildcard . '"';
        }
        
      }
      
      // we are adding search clauses, don't suppress list
      $this->suppress = false;
      // we have a valid search, add it to the where clauses
      $clauses[] = sprintf(
              $clause_pattern, 
              $search_field->name, 
              $filter_value
      );
    } elseif ( $this->filter['submit'] != $this->i18n['sort'] && $this->_empty($this->filter['value']))
      $this->search_error('value');
    elseif ( $this->filter['submit'] != $this->i18n['sort'] && 'none' == $this->filter['search_field'])
      $this->search_error('search');

    /*
     * add the blocking 'id' = 0 clause if no search clauses are present and the list is 
     * set to be suppressed
     */
    if ($this->suppress === true) {
      $clauses[] = 'p.id = 0';
    }
    // assemble there WHERE clause
    if (empty($clauses)) {
      $where_clause = '';
    } else {
      $where_clause = ' WHERE ' . implode(' AND ', $clauses);
    }

    $this->list_query = 'SELECT ' . $column_select . ' FROM ' . Participants_Db::$participants_table . ' p' . $where_clause . $order_clause;

    if (WP_DEBUG)
      error_log(__METHOD__ . ' list query= ' . $this->list_query);
  }
  
  /**
   * processes the ordering attributes to build an order clause for the query
   * 
   * this starts with the two order attributes of the shortcode, both of which can have multiple fields, and then add in the user sort to yield a combined order clause
   * 
   * @return string an order clause for a database query
   */
  private function _build_order_clause() {
    
    $order_statement = array();

    if ($this->filter['sortBy'] == 'random') {

      $order_statement[] = 'RAND()';
    } elseif (isset($this->filter['sortBy']) and !empty($this->filter['sortBy'])) {
      
      /*
       * the idea here is we take the two strings, sortstring and orderstring (which 
       * correspond to the shortcode-supplied sorting parameters) and convert them 
       * into arrays. We then build an array of sorting paramters, starting with the 
       * user sort. Then we add the shortcode sort, discarding any that match sort 
       * fields already in the sorting array. Finally, we use the sorting array to 
       * construct our MySQL clause;
       */
      $sort_arrays = array();
      $sort_fields = explode(',',$this->filter['sortstring']);
      $sort_orders = explode(',',$this->filter['orderstring']);
      $sort_by = explode(',',$this->filter['sortBy']);
      $sort_by_order = explode(',',$this->filter['ascdesc']);
      for ($i = 0; $i < count($sort_by); $i++) {
        if(Participants_Db::is_column($sort_by[$i])) {
          $sort_arrays[] = array(
              'field' => $sort_by[$i],
              'order' => (empty($sort_by_order[$i]) ? 'asc' : $sort_by_order[$i]),
          );
        }
      }
      for ($i = 0; $i < count($sort_fields); $i++) {
        if(Participants_Db::is_column($sort_fields[$i])) {
          foreach ($sort_arrays as $sort_array) {
            if ($sort_fields[$i] == $sort_array['field']) continue 2;
          }
          $sort_arrays[] = array(
              'field' => $sort_fields[$i],
              'order' => $sort_orders[$i],
          );
        }
      }
      if (empty($sort_arrays)) {
        $order_statement = false;
      } else {
        foreach ($sort_arrays as $sort_array) {
          $order_statement[] = 'p.' . $sort_array['field'] . ' ' . strtoupper($sort_array['order']);
        }
      }
    } else {

      $order_statement = false;
    }

    // assemble the ORDER BY clause
    return is_array($order_statement) ? ' ORDER BY ' . implode( ', ', $order_statement) : '';
    
  }
  
  /**
   * builds an order definition string and adds it to the shortcode atts array
   * 
   * this create a string of ASC/DESC statements to pair with the sort fields defined in a shortcode
   * 
   * @return string the orderstring
   */
  private function _get_orderstring() {
    
    $sort_fields = explode(',', $this->shortcode_atts['orderby']);
    $sort_orders = explode(',', $this->shortcode_atts['order']);
    if ( count($sort_fields) > count($sort_orders)) {
      $sort_orders = $sort_orders + array_fill(count($sort_orders),count($sort_fields)-count($sort_orders),'asc');
    }
    
    return implode(',',$sort_orders);
  }
  
  /**
   * processes the shortcode filter string
   * 
   * @return array of where clauses
   */
  private function _process_shortcode_filter() {

    $clauses = array();
    
    $this->shortcode_atts['filter'] = Participants_Db::set_filter('list_filter', $this->shortcode_atts['filter']);
    
    if (isset($this->shortcode_atts['filter'])) {

      $statements = explode('&', html_entity_decode($this->shortcode_atts['filter']));

      foreach ($statements as $statement) {
        
        if ( false !== strpos($statement, '|') ) {                              // check for OR clause
          
          $or_statements = explode('|', $statement);
          
          $or_clause = array();
          
          foreach ( $or_statements as $or_statement ) {
            
            $or_clause[] = $this->_make_single_statement($or_statement);
            
            if ( end($or_clause) === false ) {                                  // parse failed, abort
              
              $clause = false;
              continue 2;
            }
            
          }
          
          $clause = '(' . implode( ' OR ', $or_clause ) . ')';
          
        } else {
          
          $clause = $this->_make_single_statement($statement);
          
        }
        
        if ($clause === false) continue;

        // add the clause
        $clauses[] = $clause;
        
      }// each $statement
      
    }// done processing shortcode filter statements
    
    return $clauses;
  }
  
  /**
   * builds a where clause from a single statement
   * 
   * @param string $statement a single statement drawn from the shortcode filter string
   * @return string a MYSQL statement
   */
  private function _make_single_statement($statement) {

    $operator = preg_match('#^([^\2]+)(\>|\<|=|!|~)(.*)$#', $statement, $matches);

    if ($operator === 0)
      return false; // no valid operator; skip to the next statement
    
    // get the parts
    list( $string, $column, $op_char, $target ) = $matches;

    /*
     * don't add an 'id = 0' clause if there is a user search. This gives us a 
     * way to create a "search results only" list if the shortcode contains 
     * a filter for 'id=0'
     * 
     * we flag it for suppression. Later, if there is no other clause for the ID 
     * column, the list display will be suppressed
     */
    if ($column == 'id' and $target == '0') {
      $this->suppress = true;
      return false;
    }

    /*
     * if the column is not valid or if the column is being searched in by 
     * an overriding filter, skip this statement
     */
    if (!Participants_Db::is_column($column) or (!empty($this->filter['value']) && $column == $this->filter['search_field'] ))
      return false;

    $field_atts = Participants_Db::get_field_atts($column);
    
    $statement = false;

    /*
     * set up special-case field types
     */
    if (in_array($field_atts->form_element, array('date', 'timestamp')) and !empty($target)) {

      /*
       * if we're dealing with a date element, the target value needs to be 
       * conditioned to get a correct comparison
       */
      $target = Participants_Db::parse_date($target, $field_atts);
      $operator = in_array($op_char, array('>','<','=')) ? $op_char : '=';

      // if we don't get a valid date, skip this statement
      if ($target === false)
        return false;

      if ($field_atts->form_element == 'timestamp') {
        $statement = 'DATE(p.' . $column . ') ' . $operator . ' CONVERT_TZ(FROM_UNIXTIME(' . $target . '), @@session.time_zone, "+00:00") ';
      } else {
        $statement = 'p.' . $column . ' ' . $operator . ' CAST(' . $target . ' AS SIGNED)';
      }
      
    } else {
    
      $delimiter = array('"', '"');
      $wildcard = (strpos($target, '*') !== false or strpos($target, '?') !== false) ? true : false;
      $target = str_replace(
              array('%', '_', '*', '?'), 
              array('\%', '\_', '%', '_'), 
              $target);

      // get the proper operator
      switch ($op_char) {

        case '~':

          $operator = 'LIKE';
          $delimiter = $wildcard ? array('"','"') : array('"%', '%"');
          break;

        case '!':

          if (empty($target)) {
            $operator = '<>';
            $delimiter = array("'", "'");
          } else {
            $operator = 'NOT LIKE';
            $delimiter = $wildcard ? array('"','"') : array('"%', '%"');
          }
          break;

        case '=':

          /*
           * if the field's exact value will be found in an array (actually a 
           * serialized array), we must prepare a special statement to search 
           * for the double quotes surrounding the value in the serialization
           */
          if (in_array($field_atts->form_element, array('multi-checkbox', 'multi-select-other'))) {

            $delimiter = array('\'%"', '"%\'');
            $operator = 'LIKE';
          } else
            $operator = $wildcard ? 'LIKE' : '=';
          break;

        default:
          $operator = $op_char;
      }
      $statement = sprintf('p.%s %s %s%s%s', $column, $operator, $delimiter[0], $target, $delimiter[1]);
    }
    
    return $statement;
    
  }

  /**
   * prints the whole search/sort form as a shortcut function
   *
   */
  public function show_search_sort_form() {

    if (Participants_Db::$search_set === true) return;
    
    Participants_Db::$search_set = true;

    $output = array();

    if ($this->_sort_filter_mode() != 'none' && !$this->shortcode_atts['filtering']) {

      $output[] = $this->search_error_style;
      $output[] = '<div class="pdb-searchform">';
      $output[] = '<div class="pdb-error pdb-search-error" style="display:none">';
      $output[] = sprintf('<p class="search_field_error">%s</p>', __('Please select a column to search in.', 'participants-database'));
      $output[] = sprintf('<p class="value_error">%s</p>', __('Please type in something to search for.', 'participants-database'));
      $output[] = '</div>';
      $output[] = $this->search_sort_form_top(false, false, false);

      if ($this->_sort_filter_mode() == 'filter' || $this->_sort_filter_mode() == 'both') {

        $output[] = '<fieldset class="widefat">';

        $output[] = sprintf('<legend>%s:</legend>', __('Search', 'participants-database'));

        $output[] = $this->column_selector(false, false);
        $output[] = $this->search_form(false);

        $output[] = '</fieldset>';
      }

      if (
              ($this->_sort_filter_mode() == 'sort' || $this->_sort_filter_mode() == 'both') and 
              ( ! empty( $this->sortables ) and is_array( $this->sortables ) ) 
              ) {

        $output[] = '<fieldset class="widefat">';

        $output[] = sprintf('<legend>%s:</legend>', __('Sort by', 'participants-database'));

        $output[] = $this->sort_form(false);

        $output[] = '</fieldset>';
      }

      $output[] = '</form></div>';
    }

    echo $this->output_HTML($output);
  }

  /**
   * prints the top of the search/sort form
   *
   * @param string $target set the action attribute of the search form to another 
   *                       page, giving the ability to have the search on a 
   *                       different page than the list, defaults to the same page
   * @global object $post
   */
  public function search_sort_form_top($target = false, $class = false, $print = true) {

    $this->shortcode_atts['target_page'] = trim($this->shortcode_atts['target_page']);

    if (!empty($this->shortcode_atts['action']) && empty($this->shorcode_atts['target_page']) ) $this->shorcode_atts['target_page'] = $this->shortcode_atts['action'];

    global $post;

    $output = array();
    
    $ref = 'update';
    if ($target === false && !empty($this->shortcode_atts['target_page']) && $this->module == 'search') {
      $target = Participants_Db::find_permalink($this->shortcode_atts['target_page']);
      }
    if ($target) {
      $ref = 'remote';
    }
    
    $action = $target !== false ? $target : get_permalink($post->ID)  . '#' . $this->list_anchor;
    
    $class_att = $class ? 'class="' . $class . '"' : '';
    
    $output[] = '<form method="post" class="sort_filter_form" action="' . $action . '"' . $class_att . ' ref="' . $ref . '" >';
    $hidden_fields = array(
        'action' => 'pdb_list_filter',
        'instance_index' => $this->shortcode_atts['target_instance'],
        'pagelink' => $this->prepare_page_link($_SERVER['REQUEST_URI']),
        'sortstring' => $this->filter['sortstring'],
        'orderstring' => $this->filter['orderstring'],
    );
    $output[] = PDb_FormElement::print_hidden_fields($hidden_fields, false);

    if ($print)
      echo $this->output_HTML($output);
    else
      return $this->output_HTML($output);
  }

  /**
   * builds a dropdown element with a list of columns available for filtering
   *
   * @param string $all     sets the "all fields" or no search string, defaults to "show all"
   * @param bool   $print   if true prints the dropdown element
   * @param array  $columns array of columns to show in the dropdown, defaults to displayed columns
   * @param string $sort    sort method to apply to selector list
   *
   * @return NULL or HTML string if $print == false
   */
  public function column_selector($all = false, $print = true, $columns = false, $sort = 'column') {

    $all_string = false === $all ? '(' . __('select', 'participants-database') . ')' : $all;

    $element = array(
        'type' => 'dropdown',
        'name' => 'search_field',
        'value' => $this->filter['search_field'],
        'class' => 'search-item',
        'options' => array($all_string => 'none', 'null_select' => false) + $this->searchable_columns($columns),
    );
    if ($print)
      PDb_FormElement::print_element($element);
    else
      return PDb_FormElement::get_element($element);
  }
  
  /**
   * supplies an array of searchable columns
   * 
   * this is needed because the shorcode can define which fields to show, so the 
   * total set of potentially searchable fields would be the shortcode defined 
   * fields plus the fields given a display column in the database
   * 
   * $this->display_columns only contains the columns currently defined as shown in the list
   * 
   * @param array $columns array of column names
   * @return array $title => $name
   */
  function searchable_columns($columns = false) {
    
    $return = array();
    $search_columns = is_array($columns) ? $columns : $this->display_columns;
    foreach ($search_columns as $col) {
      $column = $this->get_column_atts($col);
      $return[$column->title] = $column->name;
    }
    return $return;
    
  }

  public function search_form($print = true) {

    $output = array();

    $output[] = '<input name="operator" type="hidden" class="search-item" value="LIKE" />';
    $output[] = '<input id="participant_search_term" type="text" name="value" class="search-item" value="' . $this->filter['value'] . '">';
    $output[] = '<input name="submit_button" type="submit" value="' . $this->i18n['search'] . '">';
    $output[] = '<input name="submit_button" type="submit" value="' . $this->i18n['clear'] . '">';

    if ($print)
      echo $this->output_HTML($output);
    else
      return $this->output_HTML($output);
  }

  public function sort_form($print = true) {

    $element = array(
        'type' => 'dropdown',
        'name' => 'sortBy',
        'value' => $this->get_first_in_list($this->filter['sortBy']),
        'options' => array('null_select' => true,) + $this->sortables,
        'class' => 'search-item',
    );
    $output[] = PDb_FormElement::get_element($element);

    $element = array(
        'type' => 'radio',
        'name' => 'ascdesc',
        'value' => strtoupper($this->get_first_in_list($this->filter['ascdesc'])),
        'class' => 'checkbox inline search-item',
        'options' => array(
            __('Ascending', 'participants-database') => 'ASC',
            __('Descending', 'participants-database') => 'DESC'
        ),
    );
    $output[] = PDb_FormElement::get_element($element);

    $output[] = '<input name="submit_button" type="submit" value="' . $this->i18n['sort'] . '" />';

    if ($print)
      echo $this->output_HTML($output);
    else
      return $this->output_HTML($output);
  }
  
  /**
   * prints the list count if enabled in the shortcode
   * 
   * this can be optionally given an open tag to wrap the output in. Only the open 
   * tag is given: the close tag is derived from it. By default, the pattern is 
   * wrapped in a '<caption>' tag.
   * 
   * @var string $wrap_tag the HTML to wrap the count statement in
   * @var bool $print echo ouput if true
   */
  public function print_list_count($wrap_tag = false, $print = true) {
    
    $display_count_shortcode = ($this->shortcode_atts['display_count'] == '1' or $this->shortcode_atts['display_count'] == 'true');
    
    if ($display_count_shortcode) {
      if (!$wrap_tag) $wrap_tag = '<caption>';
      $wrap_tag_close = '';
      // create the close tag by reversing the order of the open tags
      $tag_count = preg_match_all('#<([^ >]*)#',$wrap_tag,$matches);
      if ($tag_count) {
        $tags = $matches[1];
        $tags = array_reverse($tags);
        $wrap_tag_close = '</' . implode('></', $tags) . '>';
      }
      $output = $wrap_tag . sprintf(
              Participants_Db::$plugin_options['count_template'],
              $this->num_records, // total number of records found
              $this->shortcode_atts['list_limit'], // number of records to show each page
              (($this->pagination->page - 1) * $this->shortcode_atts['list_limit']) + ($this->num_records > 1 ? 1 : 0), // starting record number
              ($this->num_records - (($this->pagination->page - 1) * $this->shortcode_atts['list_limit']) > $this->shortcode_atts['list_limit'] ? 
                      $this->pagination->page * $this->shortcode_atts['list_limit'] : 
                      (($this->pagination->page - 1) * $this->shortcode_atts['list_limit']) + ($this->num_records - (($this->pagination->page - 1) * $this->shortcode_atts['list_limit']))), // ending record number
              $this->pagination->page // current page
              ) . $wrap_tag_close;
      
      if ($print) echo $output;
      else return $output;
    }
  }
  
  /**
   * sets the sortables list
   * 
   * @param array  $columns supplies a list of columns to use, defaults to sortable 
   *                        displayed columns
   * @param string $sort    'column' sorts by the display column order, 'order' uses 
   *                        the defined group/fields order, 'alpha' sorts the list 
   *                        alphabetically
   * @return NULL just sets the sortables property
   */
  public function set_sortables($columns = false, $sort = 'column') {
    if($columns !== false or $sort != 'column') {
    	$this->sortables = Participants_Db::get_sortables($columns,$sort);
    }
  }

  /**
   * prints the pagination controls to the template
   *
   * this does nothing if filtering is taking place
   *
   */
  public function show_pagination_control() {

    // set the wrapper HTML parameters
    $this->pagination->set_wrappers($this->pagination_wrap);

    // print the control
    if (!Participants_Db::$pagination_set) {
      Participants_Db::$pagination_set = true;
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
  protected function set_pagination_wrap($open = '', $close = '', $all_buttons = '', $button = '') {

    foreach (array('open', 'close', 'all_buttons', 'button') as $tag) {

      if (isset($$e) and !empty($$e))
        $this->pagination_wrap[$e] = $$e;
    }
  }

  /**
   * sets the columns to display in the list
   *
   */
//  private function _set_display_columns() {
//
//    // allow for an arbitrary fields definition list in the shortcode
//    if (!empty($this->shortcode_atts['fields'])) {
//
//      $raw_list = explode(',', str_replace(array("'", '"', ' ', "\r"), '', $this->shortcode_atts['fields']));
//
//      if (is_array($raw_list)) :
//
//        //clear the array
//        $this->display_columns = array();
//
//        foreach ($raw_list as $column) {
//
//          if (Participants_Db::is_column($column)) {
//
//            $this->display_columns[] = $column;
//          }
//        }
//
//      endif;
//    } else {
//
//      $this->display_columns = Participants_Db::get_list_display_columns('display_column');
//    }
//  }

  /**
   * get the column form element type
   *
   */
  public function get_field_type($column) {

    $column_atts = Participants_Db::get_field_atts($column, '`form_element`,`default`');

    return $column_atts->form_element;
  }

  /**
   * are we setting the single record link?
   * returns boolean
   */
  public function is_single_record_link($column) {

    return (
            isset(Participants_Db::$plugin_options['single_record_link_field'])
            &&
            $column == Participants_Db::$plugin_options['single_record_link_field']
            &&
            false !== $this->single_record_url
            &&
            !in_array($this->get_field_type($column), array('rich-text', 'link'))
            );
  }

  /**
   * create a date/time string
   */
  public function show_date($value, $format = false, $print = true) {

    $time = Participants_Db::is_valid_timestamp($value) ? $value : Participants_Db::parse_date($value);

    $dateformat = $format ? $format : Participants_Db::$date_format;

    if ($print)
      echo date_i18n($dateformat, $time);
    else
      return date_i18n($dateformat, $time);
  }

  public function show_array($value, $glue = ', ', $print = true) {

    $output = implode($glue, Participants_Db::unserialize_array($value));

    if ($print)
      echo $output;
    else
      return $output;
  }

  public function output_HTML($output = array()) {
    return implode('', $output);
  }

  public function show_link($value, $template = false, $print = false) {

    if (is_serialized($value)) {

      $params = unserialize($value);

      if (count($params) < 2)
        $params[1] = $params[0];
    } elseif ( is_array($value)) {
      
      list($params[0],$params[1]) = $value;
    } else {

      // in case we got old unserialized data in there
      $params = array_fill(0, 2, $value);
    }

    $output = Participants_Db::make_link($params[0], $params[1], $template);

    if ($print)
      echo $output;
    else
      return $output;
  }

  /* BUILT-IN OUTPUT METHODS */

  /**
   * prints a table header row
   */
  public function print_header_row($head_pattern) {

    // print the top header row
    foreach ($this->display_columns as $column) {
      $title = stripslashes(Participants_Db::column_title($column));
      printf(
              $head_pattern, str_replace(array('"',"'"), array('&quot;','&#39;'), $title), $column
      );
    }
  }

  /**
   * strips the page number out of the URI so it can be used as a link to other pages
   * 
   * we also strip out the request string for filtering values as they are added 
   * from the 'add_variables' element of the pagination config array
   *
   * @param string $uri the incoming URI, usually $_SERVER['REQUEST_URI']
   *
   * @return string the re-constituted URI
   */
  public function prepare_page_link($uri) {

    $URI_parts = explode('?', $uri);

    if (empty($URI_parts[1])) {

      $values = array();
    } else {

      parse_str($URI_parts[1], $values);
      
      /* clear out our filter variables so that all that's left in the URI are 
       * variables from WP or any other source-- this is mainly so query string 
       * page id can work with the pagination links
       */
      $filter_atts = array(
          $this->list_page,
          'search_field',
          'value',
          'operator',
          'sortBy',
          'ascdesc',
          'submit',
          'pagelink',
          'sortstring',
          'orderstring',
          'postID',
          'action',
          'filterNonce',
      );
      foreach( $filter_atts as $att ) unset($values[$att]);
    }

    return $URI_parts[0] . '?' . (count($values)>0 ? http_build_query($values) . '&' : '') . $this->list_page . '=%1$s';
  }

  /**
   * builds the sort-filter mode setting
   */
  private function _sort_filter_mode() {

    $mode = $this->shortcode_atts['sort'] == 'true' ? 'sort' : 'none';

    return $this->shortcode_atts['search'] == 'true' ? ( $mode == 'sort' ? 'both' : 'filter' ) : $mode;
  }

  /**
   * builds a URI query string from the filter parameters
   *
   * @param  array  $values the incoming finter values
   * @return string URL-encoded filter parameters, empty string if filter is not active
   */
  private function _filter_query($values) {

    if (!empty($values)) {

      return http_build_query(array_merge($values, $this->filter)) . '&';
    } else
      return '';
  }

  /**
   * takes the $_POST array and constructs a filter statement to add to the list shortcode filter
   */
  private function _make_filter_statement($post) {

    if (!Participants_Db::is_column($post['search_field']))
      return '';

    $this->filter['search_field'] = $post['search_field'];


    switch ($post['operator']) {

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

    if (empty($post['value']))
      return '';

    $this->filter['value'] = $post['value'];

    return $this->filter['search_field'] . $this->filter['operator'] . $this->filter['value'];
  }
  
  /**
   * sets the search error so it will be shown to the user
   * 
   * @param string $type sets the error type
   * @return string the CSS style rule to add
   */
  public function search_error( $type ) {
    
    $css = array('.pdb-search-error');
    
    if ($type == 'search') $css[] = '.search_field_error';
    if ($type == 'value' ) $css[] = '.value_error';
    
    $this->search_error_style = sprintf('<style>.pdb-search-error p { display:none } %s { display:inline-block !important }</style>', implode( ', ', $css) );
  }
  
  /**
   * gets the first value of a comma-separated list
   */
  public function get_first_in_list($list) {
    $listitems = explode(',',$list);
    return trim($listitems[0]);
  }
  
  /**
   * sets the single record page url
   * 
   */
  private function _set_single_record_url() {
    
    if (!empty($this->shortcode_atts['single_record_link']))
      $page_id = Participants_Db::get_id_by_slug($this->shortcode_atts['single_record_link']);
    elseif (isset(Participants_Db::$plugin_options['single_record_page']))
      $page_id = Participants_Db::$plugin_options['single_record_page'];
    else $page_id = false;
    
    $this->single_record_url = get_permalink($page_id);
  }
  
  /**
   * merges two indexed arrays such that each element is unique in the resulting indexed array
   * 
   * @param array $array1 this array will take priority, it's elements will precede 
   *                      elements from the second array
   * @param array $array2
   * @return array an indexed array of unique values
   */
  public static function array_merge_unique($array1,$array2)
  {
    $array1 = array_combine( array_values( $array1 ), $array1 );
    $array2 = array_combine( array_values( $array2 ), $array2 );

    return array_values(array_merge($array1, $array2));
  }
  
  /**
   * converts a URL-encoded character to the correct utf-8 form
   *
   * @param string $string the string to convert to UTF-8
   * @return string the converted string
   */
  function to_utf8( $string ) {
    
    $value = preg_match('/%[0-9A-F]{2}/i',$string) ? rawurldecode($string) : $string;
    if (!function_exists('mb_detect_encoding')) {
      error_log( __METHOD__ . ': Participants Database Plugin unable to process multibyte strings because "mbstring" module is not present');
      return $value;
    }
    $encoding = mb_detect_encoding($value.'a',array('utf-8', 'windows-1251','windows-1252','ISO-8859-1'));
    return ($encoding == 'UTF-8' ? $value : mb_convert_encoding($value,'utf-8',$encoding));
  }

  /**
   * sets up the internationalization strings
   */
  private function _setup_i18n() {

    /* translators: the following 5 strings are used in logic matching, please test after translating in case special characters cause problems */
    $this->i18n = array(
        'delete_checked' => _x('Delete Checked', 'submit button label', 'participants-database'),
        'change'         => _x('Change', 'submit button label', 'participants-database'),
        'sort'           => _x('Sort', 'submit button label', 'participants-database'),
        'filter'         => _x('Filter', 'submit button label', 'participants-database'),
        'clear'          => _x('Clear', 'submit button label', 'participants-database'),
        'search'         => _x('Search', 'search button label', 'participants-database'),
    );
  }

}

// class ?>