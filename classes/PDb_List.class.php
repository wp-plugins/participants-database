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
  var $wrap_class = 'pdb-list';
  /**
   * @var object temporarily holds an instance of the object
   */
  static $instance;
  // holds the main query for building the list
  static $list_query;
  // translations strings for buttons
  var $i18n;
  // holds the number of list items to show per page
  var $page_list_limit;
  // the name of the list page variable
  var $list_page = 'listpage';
  // name of the list anchor element
  var $list_anchor = 'participants-list';
  // holds the url of the registrations page
  var $registration_page_url;
  // holds the url to the single record page
  var $single_record_url = false;
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
  // holdes the search error style statement
  var $search_error_style = '';
  // holds the wrapper HTML for the pagination control
  // the first two elements wrap the whole control, the third wraps the buttons, the fourth wraps each button
  var $pagination_wrap = array(
      'open' => '<div class="pagination"><label>%s:</label> ',
      'close' => '</div>',
      'all_buttons' => 'ul',
      'button' => 'li',
  );

  /**
   * initializes and outputs the list on the frontend as called by the shortcode
   *
   * @param array $atts display customization parameters
   *                    from the shortcode
   */
  public function __construct($params) {

    // set the list limit value; this can be overridden by the shortcode atts later
    $this->page_list_limit = (!isset($_POST['list_limit']) or !is_numeric($_POST['list_limit']) or $_POST['list_limit'] < 1 ) ? Participants_Db::$plugin_options['list_limit'] : $_POST['list_limit'];

    $this->sortables = Participants_Db::get_sortables();

    // define the default settings for the shortcode
    $shortcode_defaults = array(
        'sort' => 'false',
        'search' => 'false',
        'list_limit' => $this->page_list_limit,
        'class' => 'participants-database',
        'filter' => '',
        'orderby' => 'date_updated',
        'order' => 'asc',
        'fields' => '',
        'single_record_link' =>'',
        'display_count' => 'false',
        'template' => 'default',
        'filtering' => 0, // this is set to '1' if we're coming here from an AJAX call
    );

    // run the parent class initialization to set up the parent methods 
    parent::__construct($this, $params, $shortcode_defaults);

    //error_log( __METHOD__.' '.print_r( $this,1 ));

    $this->registration_page_url = get_bloginfo('url') . '/' . ( isset($this->options['registration_page']) ? $this->options['registration_page'] : '' );

    $this->_set_display_columns();

    $this->_setup_i18n();
    
    $this->_set_single_record_url();

    // enqueue the filter/sort AJAX script
    if ($this->_sort_filter_mode() !== 'none' and $this->options['ajax_search'] == 1)
      wp_enqueue_script('list-filter');

    // set up the iteration data
    $this->_setup_iteration();

    $this->_print_from_template();
  }

  /**
   * prints a list of records called by a shortcode
   *
   * this function is called statically to instantiate the PDb_List object,
   * which captures the output and returns it for display
   *
   * @param array $params parameters passed by the shortcode
   * @return string form HTML
   */
  public static function print_record($params) {

    self::$instance = new PDb_List($params);

    return self::$instance->output;
  }

  /**
   * includes the shortcode template
   */
  protected function _include_template() {

    // set some local variables for use in the template
    $filter_mode = $this->_sort_filter_mode();
    $display_count = $this->shortcode_atts['display_count'] == 'true' or $this->shortcode_atts['display_count'] == '1';
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
  protected function _setup_iteration() {

    // process any search/filter/sort terms and build the main query
    $this->_build_shortcode_query();

    // get the $wpdb object
    global $wpdb;

    // get the number of records returned
    $this->num_records = $wpdb->get_var(preg_replace('#^SELECT.+FROM#', 'SELECT COUNT(*) FROM', $this->list_query));

    // set up the pagination object
    $pagination_defaults = array(
        'link' => ( $this->shortcode_atts['filtering'] ? urldecode($_POST['pagelink']) : $this->get_page_link($_SERVER['REQUEST_URI']) ),
        'page' => isset($_GET[$this->list_page]) ? $_GET[$this->list_page] : '1',
        'size' => $this->shortcode_atts['list_limit'],
        'total_records' => $this->num_records,
        'filtering' => $this->shortcode_atts['filtering'],
        'add_variables' => http_build_query($this->filter) . '#' . $this->list_anchor,
    );

    // instantiate the pagination object
    $this->pagination = new Pagination($pagination_defaults);

    /*
     * get the records for this page, adding the pagination limit clause
     *
     * this gives us an array of objects, each one a set of field->value pairs
     */
    $records = $wpdb->get_results($this->list_query . ' ' . $this->pagination->getLimitSql(), OBJECT);

    foreach ($records as $record) {

      $id = $record->id;
      unset($record->id);

      $this->records[$id] = $record;
    }

    if (!empty($this->records)) {

      foreach ($this->records as &$record) {

        $this->participant_values = (array) $record;

        //error_log( __METHOD__.' participant_values:'.print_r( $this->participant_values ,1));

        foreach ($record as $field => $value) {

          $field_object = $this->_get_record_field($field);

          // set the current value of the field
          $this->_set_field_value($field_object);

          //error_log( __METHOD__.' record field:'.print_r( $field_object ,1));
          // add the field to the list of fields
          $this->columns[] = $field;

          // add the field to the record object
          // illegal names will be ignored
          if ( isset( $record->{$field_object->name} ) ) $record->{$field_object->name} = $field_object;
        }
      }
    }

    /*
     * at this point, $this->records has been defined as an array of records,
     * each of which is an object that is a collection of objects: each one of
     * which is the data for a field
     */
    //error_log( __METHOD__.' all records:'.print_r( $this->records,1));
  }

  private function modify_record_iterator($record) {
    
  }

  /**
   * processes shortcode filters and sorts to build the listing query
   *
   */
  private function _build_shortcode_query() {

    // set up the column select string for the queries
    $column_select = "`id`,`" . implode("`,`", $this->display_columns) . "`";

    // set up the basic values; sort values come from the shortcode
    $default_values = array(
        'search_field' => 'none',
        'value' => '',
        'operator' => 'LIKE',
        'sortBy' => $this->shortcode_atts['orderby'],
        'ascdesc' => $this->shortcode_atts['order']
    );

    // merge the defaults with the $_REQUEST array so if there are any new values coming in, they're included
    $this->filter = shortcode_atts($default_values, $_REQUEST);

    // if we've got a valid orderby, use it. Check $_POST first, shortcode second
    $orderby = isset($this->filter['sortBy']) ? $this->filter['sortBy'] : $this->shortcode_atts['orderby'];
    $orderby = Participants_Db::is_column($orderby) ? $orderby : ( $orderby == 'random' ? 'random' : current($this->sortables) );
    $this->filter['sortBy'] = $orderby;

    $order = isset($this->filter['ascdesc']) ? strtoupper($this->filter['ascdesc']) : strtoupper($this->shortcode_atts['order']);
    $order = in_array($order, array('ASC', 'DESC')) ? $order : 'ASC';
    $this->filter['ascdesc'] = strtolower($order);

    $order_clause = $orderby == 'random' ? ' ORDER BY RAND()' : ' ORDER BY `' . $orderby . '` ' . $order;

    $this->list_query = 'SELECT ' . $column_select . ' FROM ' . Participants_Db::$participants_table . $order_clause;

    if (isset($_POST['submit']) && $_POST['submit'] == $this->i18n['clear']) {

      $this->filter['value'] = '';
      $this->filter['search_field'] = 'none';

      // go back to the first page
      $_GET[$this->list_page] = 1;
    }

    if (isset($this->shortcode_atts['filter'])) {

      $statements = explode('&', html_entity_decode($this->shortcode_atts['filter']));

      $clauses = array();

      foreach ($statements as $statement) {

        $operator = preg_match('#^([^\2]+)(\>|\<|=|!|~)(.*)$#', $statement, $matches);

        if ($operator === 0)
          continue; // no valid operator; skip to the next statement

          
// get the parts
        list( $string, $column, $op_char, $target ) = $matches;

        if (!Participants_Db::is_column($column) or (!empty($this->filter['value']) && $column == $this->filter['search_field'] )) {

          /*
           * the column specified was not valid or was used in a user search 
           * query which overrides the shortcode; skip to the next one
           */
          continue;
        }

        $field_atts = Participants_Db::get_field_atts($column);

        $delimiter = array('"', '"');

        /*
         * if we're dealing with a date element, the target value needs to be 
         * conditioned to get a correct comparison
         */
        if ($field_atts->form_element == 'date') {

          $target = Participants_Db::parse_date($target);

          // if we don't get a valid date, skip this statement
          if (false === $target)
            continue;

          // if its a MySQL TIMESTAMP we must make the comparison as a string
          if ($field_atts->group == 'internal') {
            $target = date('Y-m-d H:i:s', $target);
          } else {
            $delimiter = array('CAST(', ' AS SIGNED)');
          }
        }

        // get the proper operator
        switch ($op_char) {

          case '~':
            $operator = 'LIKE';
            $delimiter = array('"%', '%"');
            break;

          case '!':

            if (empty($target)) {
              $operator = '<>';
              $delimiter = array("'", "'");
            } else {
              $operator = 'NOT LIKE';
              $delimiter = array('"%', '%"');
            }
            break;

          default:
            $operator = $op_char;
        }

        /*
         * don't add an 'id = 0' clause if there is a user search. This gives us a 
         * way to create a "search results only" list if the shortcode contains 
         * a filter for 'id=0'
         */
        if (isset($this->filter['value']) and !empty($this->filter['value']) and $column == 'id' and $target == '0')
          break;

        // add the clause
        $clauses[] = sprintf('`%s` %s %s%s%s', $column, $operator, $delimiter[0], $target, $delimiter[1]);
      }// foreach $statements
      
      /*
       * add the user search. These must be made secure as it is direct input from the browser 
       */
      if ( isset($_POST['action']) and $_POST['action'] == 'pdb_list_filter') {
        if (
                isset($this->filter['value']) && 
                !empty($this->filter['value']) && 
                'none' != $this->filter['search_field'] && 
                in_array($this->filter['search_field'],$this->display_columns)
                ) {

          $clauses[] = sprintf(
                  ($this->options['strict_search'] ? '`%s` = "%s"' : '`%s` LIKE "%%%s%%"'), 
                  $this->display_columns[array_search($this->filter['search_field'], $this->display_columns)], 
                  mysql_real_escape_string($this->filter['value'])
                  );
        } elseif ( empty($this->filter['value']) ) 
          $this->search_error('value');
        elseif ( 'none' == $this->filter['search_field'] )
          $this->search_error ('search');
      }

      // assemble there WHERE clause
      $where_clause = empty($clauses) ? '' : ' WHERE ' . implode(' AND ', $clauses);

      $this->list_query = 'SELECT ' . $column_select . ' FROM ' . Participants_Db::$participants_table . $where_clause . $order_clause;
    }

    if (WP_DEBUG)
      error_log(__METHOD__ . ' list query= ' . $this->list_query);
  }

  /**
   * prints the whole search/sort form as a shortcut function
   *
   */
  public function show_search_sort_form() {

    $output = array();

    if ($this->_sort_filter_mode() != 'none' && !$this->shortcode_atts['filtering']) {

      $output[] = $this->search_error_style;
      $output[] = '<div class="pdb-searchform">';
      $output[] = '<div class="pdb-error pdb-search-error" style="display:none">';
      $output[] = sprintf('<p id="search_field_error">%s</p>', __('Please select a column to search in.', 'participants-database'));
      $output[] = sprintf('<p id="value_error">%s</p>', __('Please type in something to search for.', 'participants-database'));
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

      $output[] = '</div>';
    }

    echo $this->output_HTML($output);
  }

  /**
   * prints the top of the search/sort form
   *
   * @param string $target set the action attribute of the search form to another 
   *                       page, giving the ability to have the search on a 
   *                       different page than the list, defaults to the same page
   */
  public function search_sort_form_top($target = false, $class = false, $print = true) {

    global $post;

    $output = array();

    $action = $target ? $target : get_page_link($post->ID) . '#' . $this->list_anchor;
    $ref = $target ? 'remote' : 'update';
    $class_att = $class ? 'class="' . $class . '"' : '';
    $output[] = '<form method="post" id="sort_filter_form" action="' . $action . '"' . $class_att . ' ref="' . $ref . '" >';
    $output[] = '<input type="hidden" name="action" value="pdb_list_filter">';
    $output[] = '<input type="hidden" name="pagelink" value="' . $this->get_page_link($_SERVER['REQUEST_URI']) . '">';

    if ($print)
      echo $this->output_HTML($output);
    else
      return $this->output_HTML($output);
  }

  //build the list of columns available for filtering
  public function column_selector($all = false, $print = true) {

    $all_string = false === $all ? '(' . __('show all', 'participants-database') . ')' : $all;

    $filter_columns = array($all_string => 'none');
    
    foreach($this->display_columns as $column ) {
      
      $filter_columns[Participants_Db::column_title($column)] = $column;
    }

    $element = array(
        'type' => 'dropdown',
        'name' => 'search_field',
        'value' => $this->filter['search_field'],
        'class' => 'search-item',
        'options' => $filter_columns,
    );
    if ($print)
      FormElement::print_element($element);
    else
      return FormElement::get_element($element);
  }

  public function search_form($print = true) {

    $output = array();

    $output[] = '<input name="operator" type="hidden" class="search-item" value="LIKE" />';
    $output[] = '<input id="participant_search_term" type="text" name="value" class="search-item" value="' . $this->filter['value'] . '">';
    $output[] = '<input name="submit" type="submit" value="' . $this->i18n['search'] . '">';
    $output[] = '<input name="submit" type="submit" value="' . $this->i18n['clear'] . '">';

    if ($print)
      echo $this->output_HTML($output);
    else
      return $this->output_HTML($output);
  }

  public function sort_form($print = true) {

    $element = array(
        'type' => 'dropdown',
        'name' => 'sortBy',
        'value' => $this->filter['sortBy'],
        'options' => $this->sortables,
        'class' => 'search-item',
    );
    $output[] = FormElement::get_element($element);

    $element = array(
        'type' => 'radio',
        'name' => 'ascdesc',
        'value' => $this->filter['ascdesc'],
        'class' => 'checkbox inline search-item',
        'options' => array(
            __('Ascending', 'participants-database') => 'asc',
            __('Descending', 'participants-database') => 'desc'
        ),
    );
    $output[] = FormElement::get_element($element);

    $output[] = '<input name="submit" type="submit" value="' . $this->i18n['sort'] . '"';

    if ($print)
      echo $this->output_HTML($output);
    else
      return $this->output_HTML($output);
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
    
    /*
     * add a split point if this is an AJAX call
     */
    $split_point = $this->shortcode_atts['filtering'] ? '%%%' : '';

    // print the control
    echo $split_point.$this->pagination->create_links();
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
  private function _set_display_columns() {

    // allow for an arbitrary fields definition list in the shortcode
    if (!empty($this->shortcode_atts['fields'])) {

      $raw_list = explode(',', str_replace(array("'", '"', ' ', "\r"), '', $this->shortcode_atts['fields']));

      if (is_array($raw_list)) :

        //clear the array
        $this->display_columns = array();

        foreach ($raw_list as $column) {

          if (Participants_Db::is_column($column)) {

            $this->display_columns[] = $column;
          }
        }

      endif;
    } else {

      $this->display_columns = Participants_Db::get_list_display_columns('display_column');
    }
  }

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
            isset($this->options['single_record_link_field'])
            &&
            $column == $this->options['single_record_link_field']
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

    $time = preg_match('#^[0-9-]+$#', $value) > 0 ? (int) $value : strtotime($value);

    $dateformat = $format ? $format : get_option('date_format', 'r');

    if ($print)
      echo date($dateformat, $time);
    else
      return date($dateformat, $time);
  }

  public function show_array($value, $glue = ', ', $print = true) {

    $output = implode($glue, Participants_Db::unserialize_array($value));

    if ($print)
      echo $output;
    else
      return $output;
  }

  public function output_HTML($output = array()) {
    return implode("\r", $output);
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
      printf(
              $head_pattern, htmlspecialchars(stripslashes(Participants_Db::column_title($column)), ENT_QUOTES, "UTF-8", false), $column
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
  public function get_page_link($uri) {

    $URI_parts = explode('?', $uri);

    if (empty($URI_parts[1])) {

      $values = array();
    } else {

      parse_str($URI_parts[1], $values);

      unset($values[$this->list_page]);
    }

    return $URI_parts[0] . '?' . $this->list_page . '=%s';
  }

  /**
   * builds the sort-filter mode setting
   */
  private function _sort_filter_mode() {

    $mode = $this->shortcode_atts['sort'] == 'true' ? 'sort' : 'none';

    return $this->shortcode_atts['search'] == 'true' ? ( $mode == 'sort' ? 'both' : 'filter' ) : $mode;
  }

  /**
   * builds a URI querys string from the filter parameters
   *
   * @param  array  $values the incoming finter values
   * @return string URL-encoded filter parameters, empty string if filter is not active
   */
  private function _filter_query($values) {

    if (!empty($values) and $values['search_field'] != 'none') {

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
    
    if ($type == 'search') $css[] = '#search_field_error';
    if ($type == 'value' ) $css[] = '#value_error';
    
    $this->search_error_style = sprintf('<style>.pdb-search-error p { display:none } %s { display:inline-block !important }</style>', implode( ', ', $css) );
  }
  
  /**
   * sets the single record page url
   * 
   */
  private function _set_single_record_url() {
    
    if (isset($this->options['single_record_page']))
      $this->single_record_url = $this->options['single_record_page'];
    if (!empty($this->shortcode_atts['single_record_link']))
      $this->single_record_url = Participants_Db::get_id_by_slug($this->shortcode_atts['single_record_link']);
    if (isset($_POST['pagelink']))
      $this->single_record_url = Participants_Db::get_id_by_slug($_POST['pagelink']);
    
    $this->single_record_url = get_page_link($this->single_record_url);
    
    error_log(__METHOD__.' set to:'.$this->single_record_url);
  }

  /**
   * sets up the internationalization strings
   */
  private function _setup_i18n() {

    /* translators: the following 5 strings are used in logic matching, please test after translating in case special characters cause problems */
    $this->i18n = array(
        'delete_checked' => _x('Delete Checked', 'submit button label', 'participants-database'),
        'change' => _x('Change', 'submit button label', 'participants-database'),
        'sort' => _x('Sort', 'submit button label', 'participants-database'),
        'filter' => _x('Filter', 'submit button label', 'participants-database'),
        'clear' => _x('Clear', 'submit button label', 'participants-database'),
        'search' => _x('Search', 'search button label', 'participants-database'),
    );
  }

}

// class ?>