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
 * @copyright  2012 - 2015 xnau webdesign
 * @license    GPL2
 * @version    Release: 1.6
 * @link       http://wordpress.org/extend/plugins/participants-database/
 */
if ( ! defined( 'ABSPATH' ) ) die;
class PDb_List extends PDb_Shortcode {
  /**
   *
   * @var object the List Query object
   */
  private $list_query;
  /**
   *
   * @var array translations strings for buttons
   */
  public $i18n;
  /**
   *
   * @var int holds the number of list items to show per page
   */
  public $page_list_limit;
  /**
   *
   * @var string the name of the list page variable
   */
  public $list_page;
  /**
   *
   * @var string name of the list anchor element
   */
  public $list_anchor = 'participants-list';
  /**
   *
   * @var string holds the url of the registrations page
   */
  public $registration_page_url;
  /**
   *
   * @var string holds the url to the single record page
   */
  public $single_record_url = false;
  /**
   *
   * @var array holds the list of sortable columns
   */
  public $sortables;
  /**
   *
   * @var array holds the settings for the list filtering and sorting
   */
  private $filter;
  /**
   *
   * @var string holds the search error style statement
   */
  public $search_error_style = '';
  /**
   * the wrapper HTML for the pagination control
   * 
   * the first two elements wrap the whole control, the third wraps the buttons, 
   * the fourth wraps each button
   * 
   * @var array wrapper HTML elements
   */
  public $pagination_wrap = array(
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
  public $suppress = false;
  /**
   * set to true if list is the result of a search
   * 
   * @var bool
   */
  public $is_search_result;
  /**
   * @var int the current page number
   */
  private $current_page = 1;

  /**
   * initializes and outputs the list on the frontend as called by the shortcode
   *
   * @param array $shortcode_atts display customization parameters
   *                              from the shortcode
   */
  public function __construct($shortcode_atts) {

    $this->set_instance_index();

    // define the default settings for the shortcode
    $shortcode_defaults = array(
        'sort' => 'false',
        'search' => 'false',
        'list_limit' => Participants_Db::plugin_setting('list_limit', '10'),
        'class' => 'participants-database',
        'filter' => '',
        'orderby' => Participants_Db::plugin_setting('list_default_sort'),
        'order' => Participants_Db::plugin_setting('list_default_sort_order'),
        'fields' => '',
        'single_record_link' =>'',
        'display_count' => Participants_Db::plugin_setting('show_count'),
        'template' => 'default',
        'module' => 'list',
        'action' => '',
        'suppress' => '',
    );

    // run the parent class initialization to set up the parent methods 
    parent::__construct($shortcode_atts, $shortcode_defaults);

    $this->list_page = Participants_Db::$list_page;

    $this->_set_page_number();

//    error_log( __METHOD__.' $this->shortcode_atts:'.print_r( $this->shortcode_atts,1 ));

    $this->registration_page_url = get_bloginfo('url') . '/' . Participants_Db::plugin_setting('registration_page', '');

    $this->_setup_i18n();
    
    $this->_set_single_record_url();
    
    /*
     * if the 'suppress' shortcode attribute is set
     */
    if (!empty($this->shortcode_atts['suppress'])) $this->suppress = filter_var($this->shortcode_atts['suppress'], FILTER_VALIDATE_BOOLEAN);

    // enqueue the filter/sort AJAX script
    if (Participants_Db::plugin_setting_is_true('ajax_search')) {

      global $wp_query;

      $ajax_params = array(
          'ajaxurl' => admin_url('admin-ajax.php'),
          'filterNonce' => Participants_Db::$list_filter_nonce,
          'postID' => ( isset($wp_query->post) ? $wp_query->post->ID : '' ),
          'prefix' => Participants_Db::$prefix,
          'loading_indicator' => Participants_Db::get_loading_spinner()
      );
      
      wp_localize_script(Participants_Db::$prefix.'list-filter', 'PDb_ajax', $ajax_params);
      
      wp_enqueue_script(Participants_Db::$prefix.'list-filter');
    }
    /*
     * instantiate the List Query object
     */
    $this->set_list_query_object();
    if ($search_error = $this->list_query->get_search_error()) {
      $this->search_error($search_error);
    }

    // set up the iteration data
    $this->_setup_iteration();

    /*
     * set the initial sortable field list; this is the set of all fields that are 
     * both marked "sortable" and currently displayed in the list
     */
    $this->_set_default_sortables();
    
    $this->is_search_result = $this->list_query->is_search_result();

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
    $display_count = isset($this->shortcode_atts['display_count']) ? filter_var($this->shortcode_atts['display_count'], FILTER_VALIDATE_BOOLEAN) : false;
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

    // the list query object can be modified at this point to add a custom search
    do_action(Participants_Db::$prefix . 'list_query_object', $this->list_query);

    // allow the query to be altered before the records are retrieved
    $list_query = Participants_Db::set_filter('list_query',$this->list_query->get_list_query());
    
    if (WP_DEBUG) error_log(__METHOD__.' list query: '. $this->list_query->get_list_query());

    // get the $wpdb object
    global $wpdb;

    // get the number of records returned
    $this->num_records = $wpdb->get_var(preg_replace('#^SELECT.+FROM #', 'SELECT COUNT(*) FROM ', $list_query));
    
    $this->_set_list_limit();
    
    // set up the pagination object
    $pagination_defaults = array(
        'link' => $this->prepare_page_link( $this->shortcode_atts['filtering'] ? filter_input(INPUT_POST, 'pagelink') : $_SERVER['REQUEST_URI'] ),
        'page' => $this->current_page,
        'size' => $this->page_list_limit,
        'total_records' => $this->num_records,
        'filtering' => $this->shortcode_atts['filtering'],
        'add_variables' => 'instance=' . $this->instance_index . '#' . $this->list_anchor,
    );
    // instantiate the pagination object
    $this->pagination = new PDb_Pagination($pagination_defaults);
    /*
     * get the records for this page, adding the pagination limit clause
     *
     * this gives us an array of objects, each one a set of field->value pairs
     */
    $records = $wpdb->get_results($list_query . ' ' . $this->pagination->getLimitSql(), OBJECT);

    /*
     * build an array of record objects, indexed by ID
     */
    $this->records = array();
    foreach ($records as $record) {

      $id = $record->id;
      if (!in_array('id',$this->display_columns)) unset($record->id);

      $this->records[$id] = $record;
    }

    if (!empty($this->records)) {

      foreach ($this->records as $id => $record) {

        /*
         * @version 1.6 
         * 
         * this array now contains all values for the record
         */
        // set the values for the current record
        $this->participant_values = Participants_Db::get_participant($id);

        foreach ($record as $field => $value) {
          
          /*
           * as of 1.5.5, we don't fill up the records property with all the field properties, 
           * just the current props, like value and link. The field properties are added when 
           * the list is displayed to use less memory
           */
          $field_object = new stdClass();
          $field_object->name = $field;
          
          // set the current value of the field
          $this->_set_field_value($field_object);

          $this->_set_field_link($field_object);

          // add the field to the record object
          $this->records[$id]->{$field_object->name} = $field_object;
      }
    }

    }
    reset($this->records);
    /*
     * at this point, $this->records has been defined as an array of records,
     * each of which is an object that is a collection of objects: each one of
     * which is the data for a field
     */
    // error_log( __METHOD__.' all records:'.print_r( $this->records,1));
  }
  /**
   * sets up the array of display columns
   *
   * @global object $wpdb
   */
  protected function _set_shortcode_display_columns() {
    
    if (empty($this->shortcode_atts['groups'])) {
      $this->display_columns = $this->get_list_display_columns('display_column');
    } else {
      parent::_set_shortcode_display_columns();
    }
  }

  /**
   * sets the page number
   * 
   * if the instance in the input array matches the current instance, we set the 
   * page number from the input array
   *
   * @return null
   */
  private function _set_page_number()
  {
    $input = false;
    $this->current_page = 1;
    if (filter_input(INPUT_GET, 'instance', FILTER_VALIDATE_INT) === $this->instance_index) {
      $input = INPUT_GET;
    }
    if (isset($_POST[$this->list_page]) && filter_input(INPUT_POST, 'instance_index', FILTER_VALIDATE_INT) === $this->instance_index) {
      $input = INPUT_POST;
    }
    if ($input !== false) {
      $this->current_page = filter_input($input, $this->list_page, FILTER_VALIDATE_INT, array('options' => array('min_range' => 1, 'default' => 1)));
      }
  }
  
  /**
   * sets the field value; uses the default value if no stored value is present
   * 
   * as of version 1.5.5 we slightly changed how this works: formerly, the default 
   * value was only used in the record module if the "persistent" flag was set, now 
   * the default value is used anyway. Seems more intuitive to let the default value 
   * be used if it's set, and not require the persistent flag. The default value is 
   * always used in the signup module.
   * 
   * 
   * @param object $field the current field object
   * @return null
   */
  protected function _set_field_value($field) {

    $field_obj = $this->fields[$field->name];
    /*
     * get the value from the record; if it is empty, use the default value if the 
     * "persistent" flag is set.
     */
    $record_value = isset($this->participant_values[$field->name]) ? $this->participant_values[$field->name] : '';
    
    // replace it with the new value if provided, escaping the input
    if (in_array($this->module, array('record','signup','retrieve')) && isset($_POST[$field->name])) {

      $value = $this->_esc_submitted_value(filter_input(INPUT_POST,$field->name));
          }
    $value = $this->_empty($record_value) ? ($this->_empty($field_obj->default) ? '' : $field_obj->default) : $record_value;

          /*
					 * make sure id and private_id fields are read only
           */
    if (in_array($field->name, array('id', 'private_id'))) {
      $this->display_as_readonly($field);
      }
    if ($field_obj->form_element === 'hidden') {
      if ($field_obj->default === $record_value) {
        $record_value = '';
    }
      $value = $this->_empty($record_value) ? '' : $record_value;
      // show this one as a readonly field
      $this->display_as_readonly($field);
    }
    $field->value = maybe_unserialize($value);
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

        $output[] = '<fieldset class="widefat inline-controls">';

        $output[] = sprintf('<legend>%s:</legend>', __('Search', 'participants-database'));

        $output[] = $this->column_selector(false, false);
        $output[] = $this->search_form(false);

        $output[] = '</fieldset>';
      }

      if (
              ($this->_sort_filter_mode() == 'sort' || $this->_sort_filter_mode() == 'both') and 
              ( ! empty( $this->sortables ) and is_array( $this->sortables ) ) 
              ) {

        $output[] = '<fieldset class="widefat inline-controls">';

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
    
    $output[] = '<form method="post" class="sort_filter_form" action="' . $action . '"' . $class_att . ' data-ref="' . $ref . '" >';
    $hidden_fields = array(
        'action' => 'pdb_list_filter',
        'target_instance' => $this->shortcode_atts['target_instance'],
        'instance_index' => $this->instance_index,
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
   * @param bool   $multi   if true, field name will include [] so the value is submitted as an array element
   *
   * @return NULL or HTML string if $print == false
   */
  public function column_selector($all = false, $print = true, $columns = false, $sort = 'column', $multi = false) {
    
    static $multifield_count = 0;
    $value = $this->list_query->current_filter('search_field');
    if ($multi) {
      $values = $this->list_query->current_filter('search_fields');
      $value = isset($values[$multifield_count]) ? $values[$multifield_count] : '';
    }

    $all_string = false === $all ? '(' . __('select', 'participants-database') . ')' : $all;

    $search_columns = $this->searchable_columns($columns);

    if (count($search_columns) > 1) {
    $element = array(
        'type' => 'dropdown',
        'name' => 'search_field' . ($multi ? '[]' : ''),
        'value' => $value,
        'class' => 'search-item',
          'options' => array($all_string => 'none', 'null_select' => false) + $search_columns,
      );
    } else {
      $element = array(
          'type' => 'hidden',
          'name' => 'search_field' . ($multi ? '[]' : ''),
          'value' => current($search_columns),
          'class' => 'search-item',
			);
    }
    $multifield_count++;
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
      if ($column) $return[$column->title] = $column->name;
    }
    return $return;
    
  }

  /**
   * print a search form
   * 
   * this is a shortcut to print a preset search form.
   * 
   * @param bool $print
   * @return null|string
   */
  public function search_form($print = true) {

//    error_log(__METHOD__.' target: '.$this->shortcode_atts['target_instance'].' module: '.$this->module);
    
    $search_term = $this->list_query->current_filter('search_term');

    $output = array();

    $output[] = '<input name="operator" type="hidden" class="search-item" value="LIKE" />';
    $output[] = '<input id="participant_search_term" type="text" name="value" class="search-item" value="' . $search_term . '">';
    $output[] = $this->search_submit_buttons();

    if ($print)
      echo $this->output_HTML($output);
    else
      return $this->output_HTML($output);
  }
  /**
   * supplies a search form submit and clear button with optional button text strings
   * 
   * the $values array can supply a locally-defined button text value, for example:
   * $values = array( 'submit' => 'Search Records', 'clear' => 'Clear the Search Parameters' );
   * 
   * @param array $values array of strings to set the "value" attribute
   * @return string the HTML
   */
  public function print_search_submit_buttons($values = '') {
    $submit_text = isset($values['submit']) ? $values['submit'] : $this->i18n['search'];
    $clear_text = isset($values['clear']) ? $values['clear'] : $this->i18n['clear'];
  	$output = array();
  	$output[] = '<input name="submit_button" class="search-form-submit" data-submit="search" type="submit" value="' . $submit_text . '">';
    $output[] = '<input name="submit_button" class="search-form-clear" data-submit="clear" type="submit" value="' . $clear_text . '">';
    print $this->output_HTML($output);
  }
  /**
   * supplies a search form submit and clear button
   * 
   * @return string the HTML
   */
  public function search_submit_buttons() {
  	$output = array();
  	$output[] = '<input name="submit_button" class="search-form-submit" data-submit="search" type="submit" value="' . $this->i18n['search'] . '">';
    $output[] = '<input name="submit_button" class="search-form-clear" data-submit="clear" type="submit" value="' . $this->i18n['clear'] . '">';
    return $this->output_HTML($output);
  }
  /**
   * 
   * @param bool $print
   * @return null|string
   */
  public function sort_form($print = true) {

    $value = $this->list_query->current_filter('sort_field');
    $options = array();
    if (!in_array($value, $this->sortables)) {
      $options = array('null_select' => '');
    }
    $element = array(
        'type' => 'dropdown',
        'name' => 'sortBy',
        'value' => $value,
        'options' => $options + $this->sortables,
        'class' => 'search-item',
    );
    $output[] = PDb_FormElement::get_element($element);

    $element = array(
        'type' => 'radio',
        'name' => 'ascdesc',
        'value' => $this->list_query->current_filter('sort_order'),
        'class' => 'checkbox inline search-item',
        'options' => array(
            __('Ascending', 'participants-database') => 'ASC',
            __('Descending', 'participants-database') => 'DESC'
        ),
    );
    $output[] = PDb_FormElement::get_element($element);

    $output[] = '<input name="submit_button" data-submit="sort" type="submit" value="' . $this->i18n['sort'] . '" />';

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
              Participants_Db::plugin_setting('count_template'),
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
   * this func is only used in templates to set up a custom sort dropdown
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
   * sets the default list of sortable columns
   * 
   */
  private function _set_default_sortables() {
    
    $columns = array();
    foreach ($this->display_columns as $column) {
      if ($this->fields[$column]->sortable > 0) {
        $columns[] = $column;
      }
    }
    // if no columns are set as sortable, use all displayed columns
    if (empty($columns)) {
      $columns = $this->display_columns;
    }
    $this->set_sortables($columns);
    
  }

  /**
   * echoes the pagination controls to the template
   *
   * this does nothing if filtering is taking place
   *
   */
  public function show_pagination_control() {

    // set the wrapper HTML parameters
    $this->pagination->set_wrappers($this->pagination_wrap);

    // print the control
		echo $this->pagination->create_links();
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
   * get the column form element type
   *
   * @return string the form element type
   *
   */
  public function get_field_type($column) {

    $column_atts = $this->fields[$column];

    return $column_atts->form_element;
  }

  /**
   * are we setting the single record link?
   * 
   * @return bool true if the current field is the designated single record link field
   */
  public function is_single_record_link($column) {

    return (
            Participants_Db::is_single_record_link($column)
            &&
            false !== $this->single_record_url
            &&
            !in_array($this->get_field_type($column), array('rich-text', 'link'))
            );
  }

  /**
   * print a date string from a UNIX timestamp
   * 
   * @param int|string $value timestamp or date string
   * @param string $format format to use to override plugin settings
   * @param bool $print if true, echo the output
   * @return string formatted date value
   */
  public function show_date($value, $format = false, $print = true) {

    $time = Participants_Db::is_valid_timestamp($value) ? $value : Participants_Db::parse_date($value);

    $dateformat = $format ? $format : Participants_Db::$date_format;

    if ($print)
      echo date_i18n($dateformat, $time);
    else
      return date_i18n($dateformat, $time);
  }

  /**
   * converts an array value to a readable string
   * 
   * @param array $value
   * @param string $glue string to use for concatenation
   * @param bool $print if true, echo the output
   * @return string HTML
   */
  public function show_array($value, $glue = ', ', $print = true) {

    $array = array_filter((array)Participants_Db::unserialize_array($value), array( 'PDb_FormElement', 'is_displayable'));

    $output = implode($glue, $array);

    if ($print)
      echo $output;
    else
      return $output;
  }

  /**
   * returns a concatenated string from an array of HTML lines
   * 
   * @version 1.6 added option to assemble HTML without linebreaks
   * 
   * @param array $output
   * @return type
   */
  public function output_HTML($output = array()) {
    $glue = Participants_Db::plugin_setting_is_true('strip_linebreaks') ? '' : PHP_EOL;
    return implode($glue, $output);
  }

  /**
   * sets up an anchored value
   * 
   * this uses any relevant plugin settings
   * 
   * @param array|string $value
   * @param string       $template for the link HTML (optional)
   * @param bool         $print    if true, HTML is echoed
   * @return string HTML
   */
  public function show_link($value, $template = false, $print = false) {

    $params = maybe_unserialize($value);

    if ( is_array($params)) {

      if (count($params) < 2)
        $params[1] = $params[0];
      
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
          'instance',
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
    else {
      $page_id = Participants_Db::plugin_setting('single_record_page', false);
    }
    
    $this->single_record_url = get_permalink($page_id);
  }
  /**
   * sets the record edit url
   * 
   * @return null
   */
  private function _set_record_edit_url() {
    $this->registration_page_url = get_bloginfo('url') . '/' . Participants_Db::plugin_setting('registration_page','' );
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
   * sets the list limit value
   * 
   * @return null
   */
  private function _set_list_limit() {
    $limit = filter_input(
            INPUT_POST, 
            'list_limit', 
            FILTER_VALIDATE_INT, 
            array(
                'options' => array(
                    'min_range' => 1, 
                    'default' => $this->shortcode_atts['list_limit']
                )
            )
    );
    if ($limit < 1 || $limit > $this->num_records) {
      $this->page_list_limit = $this->num_records;
    } else {
      $this->page_list_limit = $limit;
    }
    
  }

  /**
   * instantiates the list query object for the list instance
   * 
   * @return null
   */
  private function set_list_query_object() {
    
    $this->list_query = new PDb_List_Query($this);
    $search_term = $this->list_query->current_filter('search_term');
    
//    error_log(__METHOD__.' list query: '.print_r($this->list_query->current_filter(),1));
    
    /*
     * if the current list instance doesn't have a search term, see if there is an 
     * incoming search that targets it
     */
    if (empty($search_term) && $this->list_query->is_search_result()) {
      $this->list_query->set_query_session($this->shortcode_atts['target_instance']);
    }
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