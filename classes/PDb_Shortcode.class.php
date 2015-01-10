<?php

/*
 * Shortcode class
 *
 * provides basic functionality for rendering a shortcode's output
 *
 * common functionality we will handle here:
 *  choosing a template
 *  capturing the output of the template
 *  loading the plugin settings
 *  defining the default shortcode attributes array
 *  setting up the shortcode attributes array
 *  maintaining loop pointers
 *  instantiating Field_Group and Field objects for the display loop
 *  converting dynamic value notation to the value it represents
 *  perfoming a field key replace on blocks of text for emails and user feedback
 * 
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2013 xnau webdesign
 * @license    GPL2
 * @version    1.5.5
 * @link       http://xnau.com/wordpress-plugins/
 *
 */

abstract class PDb_Shortcode {

  /**
   * @var string name stem of the shortcode
   */
  public $module;
  /**
   * @var object the instance of the class for singleton pattern
   */
  public static $instance;
  /**
   *
   * @var string a namespacing prefix
   */
  public $prefix;
  /**
   * @var string holds the name of the template
   */
  protected $template_name;
  /**
   * @var string holds the template file path
   */
  protected $template;
  /**
   * @var string holds the output for the shortcode
   */
  protected $output = '';
  /**
   * @var array default values for standard shortcode attributes
   */
  protected $shortcode_defaults;
  /**
   * @var array holds the current shorcode attributes
   */
  protected $shortcode_atts;
  /**
   * @var array a selected array of fields to display
   */
  public $display_columns = false;
	
  /**
	 * holds the field groups array which will contain all the groups info and their fields
	 *
	 * this will be the main object the template iterates through
	 * @var array
	 */
  var $record;
  
	/**
	 * an array of all the hidden fields in a record, name=>value pairs
   * 
	 * @var array of defined hidden field names=>value pairs
	 */
	var $hidden_fields = array();
	
  /**
	 * holds the current record ID
	 * @var int
	 */
  var $participant_id;
	
  /**
	 * the array of current record fields; false if the ID is invalid
	 * @var array
	 */
  var $participant_values;
  /**
   *
   * @var string|bool permalink to the page the form submits to
   */
  var $submission_page = false;
  /**
   * @var string holds any validation error html generated by the validation class
   */
  protected $error_html = '';
  /**
   * @var string holds the module wrap class name
   */
  var $wrap_class;
  /**
   * @var string the class name to apply to empty fields
   */
  protected $emptyclass = 'blank-field';
  /**
   * @var object the pagination object
   */
  var $pagination;
  /**
   * @var array groups to be displayed
   */
  var $display_groups;
  /**
   * @var object the current Field_Group object
   */
  var $group;
  /**
   * @var int the number of displayable groups in the record
   */
  var $group_count;
  /**
   * @var int group iteration pointer
   */
  var $current_group_pointer = 1;
  /**
   * @var object the current Field object
   */
  var $field;
  /**
   * @var int field iteration pointer
   */
  var $current_field_pointer = 1;
  /**
   * @var array all the records are held in this array
   */
  var $records;
  /**
   * @var int the number of records after filtering
   */
  var $num_records;
  /**
   * @var int record iteration pointer
   */
  var $current_record_pointer = 1;
  /**
   * @var array all field objects used by the shortcode
   */
  var $fields = array();

  /**
   * instantiates the shortcode object
   *
   * @param array  $shortcode_atts              the raw parameters passed in from the shortcode
   * @param array  $subclass_shortcode_defaults additional shortcode attributes to use as defined
   *                                            in the instantiating subclass
   *
   */
  public function __construct($shortcode_atts, $subclass_shortcode_defaults = array()) {
    
    // increment the index each time this class is instantiated
    Participants_Db::$instance_index++;
    
    // set the global shortcode flag and trigger the action on the first instantiation of this class
    $this->plugin_shortcode_action();

    Participants_Db::include_stylesheets();
    Participants_Db::add_scripts();

    $this->prefix = Participants_Db::$prefix;

    global $post;

    $this->shortcode_defaults = array(
        'title' => '',
        'class' => '',
        'template' => 'default',
        'fields' => '',
        'groups' => '',
        'action' => '',
        'target_instance' => Participants_Db::$instance_index,
        'target_page' => '',
        'record_id' => false,
        'filtering' => 0, // this is set to '1' if we're coming here from an AJAX call
        'autocomplete' => 'off',
        'submit_button' => Participants_Db::plugin_setting('signup_button_text'),
        'post_id' => $post->ID,
    );
    
    // error_log(__METHOD__.' incoming shorcode atts:'.print_r($shortcode_atts,1));

    // set up the shortcode_atts property
    $this->_setup_shortcode_atts($shortcode_atts, $subclass_shortcode_defaults);
    
    $this->module = $this->shortcode_atts['module'];
    
    $this->_setup_fields();
    
    /* 
     * save the shotcode attributes to the session array
     * 
     * skip this if doing AJAX because it would just store the default values, not 
     * the actual values from the shortcode 
     */
    if ($this->shortcode_atts['filtering'] != 1) {
      static $clear = true;
      if (is_null($clear) && filter_input(INPUT_GET,'shortcode_clear') === 'true') {
        $clear = true;
      }
      if ($clear) {
        Participants_Db::$session->clear('shortcode_atts');
        $clear = false;
      }
      Participants_Db::$session->update('shortcode_atts', $this->shortcode_session());
    }

    $this->wrap_class = $this->prefix . $this->module . ' ' . $this->prefix . 'instance-' . Participants_Db::$instance_index;

    $this->_set_display_groups();

    $this->_set_display_columns();

    $this->wrap_class = trim($this->wrap_class) . ' ' . trim($this->shortcode_atts['class']);
    // set the template to use
    $this->set_template($this->shortcode_atts['template']);
  }

  /**
   * dumps the output of the template into the output property
   *
   */
  protected function _print_from_template() {

    if (false === $this->template) {

      $this->output = '<p class="alert alert-error">' . sprintf(_x('<%1$s>The template %2$s was not found.</%1$s> Please make sure the name is correct and the template file is in the correct location.', 'message to show if the plugin cannot find the template', 'participants-database'), 'strong', $this->template) . '</p>';

      return false;
    }
    /*
     * don't show the signup form if cookies are disabled.
     */
    if ($this->module === 'signup' && Participants_Db::$session->no_user_cookie) {
      $message = Participants_Db::plugin_setting('no_cookie_message');
      if (!empty($message)) {
        $this->output = '<p class="alert alert-error">' . $message . '</p>';
        return false;
      }
    }

    ob_start();
    
    if (WP_DEBUG && in_array($this->module, array('signup','single','record','list','search'))) {
    echo '<!-- template: ' . $this->template_basename($this->template) . ' -->';
    }

    // this will be included in the subclass context
    $this->_include_template();
    
    if (WP_DEBUG && in_array($this->module, array('signup','single','record','list','search'))) {
    echo '<!-- end template: ' . $this->template_basename($this->template) . ' -->';
    }

    $this->output = $this->strip_linebreaks(ob_get_clean());
  }
  
  /**
   * conditionally removes line breaks from a buffered output
   * 
   * @param string $input the buffer input
   * @return string processed string
   */
  protected function strip_linebreaks($input) {
    
    if (Participants_Db::plugin_setting_is_true('strip_linebreaks')) {
      
      $input = str_replace(PHP_EOL, '', $input);
    }
    return $input;
  }

  /**
   * sets up the template
   *
   * sets the template properties of the object
   *
   * @param string $name the name stem of the specified template
   * 
   */
  protected function set_template($name) {

    $this->template_name = $name;
    $this->_find_template();
  }

  /**
   * selects the template to use
   *
   */
  private function _find_template() {

    $template = false;

    $template = get_stylesheet_directory() . '/templates/pdb-' . $this->module . '-' . $this->template_name . '.php';

    if (!file_exists($template)) {
      /*
       * we do this here so that the callback can choose to provide a default template 
       * or override the custom template.
       */
      $template = Participants_Db::set_filter('template_select', $template);
    }

    if (!file_exists($template)) {
      
      $template = Participants_Db::$plugin_path . 'templates/pdb-' . $this->module . '-' . $this->template_name . '.php';
    }

    if (!file_exists($template)) {

      $template = Participants_Db::$plugin_path . 'templates/pdb-' . $this->module . '-default.php';
    }

    if (!file_exists($template)) {

      error_log(__METHOD__ . ' template not found: ' . $template);
    }

    // pass the found template value through a filter so it can be changed by a plugin
		$this->template = $template;
  }

  /**
   * includes the shortcode template
   *
   * this is a dummy function that must be defined in the subclass because the
   * template has to be included in the subclass context
   */
  abstract protected function _include_template();

  /**
   * sets up the shortcode attributes array
   *
   * @param array $shortcode_atts raw parameters passed in from the shortcode
   * @param array $add_atts an array of subclass-specific attributes to add
   */
  private function _setup_shortcode_atts($shortcode_atts, $add_atts) {

    $defaults = array_merge($this->shortcode_defaults, $add_atts);

    $this->shortcode_atts = shortcode_atts($defaults, $shortcode_atts, 'pdb_' . $defaults['module']);
  }

  /**
   * outputs a "record not found" message
   *
   * the message is defined int he plugin settings
   */
  protected function _not_found() {

    $this->output = empty(Participants_Db::$plugin_options['no_record_error_message']) ? '' : '<p class="alert alert-error">' . Participants_Db::plugin_setting('no_record_error_message') . '</p>';
  }

  /**
   * collects any validation errors from the last submission
   *
   */
  protected function _get_validation_errors() {

    if (is_object(Participants_Db::$validation_errors)) {

      $this->error_html = Participants_Db::$validation_errors->get_error_html();
    }
  }

  /**
   * prints the error messages html
   *
   * @param string $container wraps the whole error message element, must include
   *                          2 %s placeholders: first for a class name, then one for the content
   * @param string $wrap      wraps each error message, must have %s placeholders for the content.
   *
   */
  public function print_errors($container = false, $wrap = false) {

    if (is_object(Participants_Db::$validation_errors)) {

      if ($container)
        Participants_Db::$validation_errors->set_error_html($container, $wrap);

      echo Participants_Db::$validation_errors->get_error_html();
    }

    //echo $this->error_html;
  }

  /**
   * gets the current errors
   * 
   * @return mixed an array of error messages, or bool false if no errors
   */
  public function get_errors() {

    if (is_object(Participants_Db::$validation_errors)) {

      $errors = Participants_Db::$validation_errors->get_validation_errors();
      if ($this->_empty($errors))
        return false;
      else
        return $errors;
    }
  }

  /*   * **************
   * ITERATION CONTROL

    /**
   * checks if there is still another group of fields to show
   *
   */

  public function have_groups() {

    return $this->current_group_pointer <= $this->group_count;
  }

  /**
   * gets the next group
   *
   * increments the group pointer
   *
   */
  public function the_group() {

    // the first time through, use current()
    if ($this->current_group_pointer == 1)
      $this->group = new PDb_Field_Group_Item(current($this->record), $this->module);
    else
      $this->group = new PDb_Field_Group_Item(next($this->record), $this->module);

    $this->reset_field_counter();

    $this->current_group_pointer++;
  }

  /**
   * checks if there is still another field to show
   *
   * @param object $group the current group out of the iterator
   */
  public function have_fields() {

    $field_count = is_object($this->group) ? $this->group->_field_count : count($this->display_columns);

    return $this->current_field_pointer <= $field_count;
  }

  /**
   * gets the next field; advances the count
   *
   */
  public function the_field() {

    // the first time through, use current()
    if ($this->current_field_pointer == 1) {
      if (is_object($this->group))
        $this->field = new PDb_Field_Item(current($this->group->fields));
      else
        $this->field = new PDb_Field_Item(current($this->record->fields), $this->record->record_id);
    } else {
      if (is_object($this->group))
        $this->field = new PDb_Field_Item(next($this->group->fields));
      else
        $this->field = new PDb_Field_Item(next($this->record->fields), $this->record->record_id);
    }
    
    $this->field->module = $this->module;

    /*
     * if pre-fill values for the signup form are present in the GET array, set them
     */
    $get_field_name = filter_input(INPUT_GET, $this->field->name, FILTER_SANITIZE_STRING);
    if (in_array($this->module, array('signup','retrieve')) and !empty($get_field_name)) {
      $this->field->value = $get_field_name;
    }

    $this->current_field_pointer++;
  }

  /**
   * resets the field counter
   */
  protected function reset_field_counter() {

    $this->current_field_pointer = 1;
  }

  /**
   * checks for additional records to show
   */
  public function have_records() {

    $remaining = $this->num_records - ( ( $this->pagination->page - 1 ) * $this->shortcode_atts['list_limit'] );

    $records_this_page = $remaining < $this->shortcode_atts['list_limit'] ? $remaining : $this->shortcode_atts['list_limit'];

    return $this->current_record_pointer <= $records_this_page;
  }

  /**
   * gets the next group
   *
   * increments the group pointer
   *
   */
  public function the_record() {

    // the first time through, use current()
    if ($this->current_record_pointer == 1) {

      $the_record = current($this->records);
    } else {

      $the_record = next($this->records);
    }

    $this->record = new PDb_Record_Item($the_record, key($this->records), $this->module);

    $this->reset_field_counter();

    $this->current_record_pointer++;
  }

  /**
   * sets up the template iteration object
   *
   * this takes all the fields that are going to be displayed and organizes them
   * under their group so we can easily run through them in the template
   */
  protected function _setup_iteration() {

    $this->record = new stdClass;

    $groups = Participants_Db::get_groups();

    foreach ($this->display_groups as $group_name) {

      $group_fields = $this->_get_group_fields($group_name);

      $this->record->$group_name = (object) $groups[$group_name];
      $this->record->$group_name->fields = new stdClass();

      $field_count = 0;
      $all_empty_fields = true; 

      foreach ($group_fields as $field) {

          // set the current value of the field
          $this->_set_field_value($field);

          /*
           * hidden fields are stored separately for modules that use them as
           * hidden input fields
           */
        if ($field->form_element == 'hidden' && in_array($field->name, $this->display_columns)) { // and in_array($this->module, array('signup', 'record'))
            $this->hidden_fields[$field->name] = $field->value;
        } else {

          $this->_set_field_link($field);

            /*
             * add the field object to the record object
             */
          if (in_array($field->name, $this->display_columns)) {
            $field_count++;
            if (!$this->_empty($field->value)) $all_empty_fields = false;
            $this->record->$group_name->fields->{$field->name} = $field;
          }
          }
        }
      if ($field_count === 0) {
        // remove the empty group from the iterator
        unset($this->record->$group_name);
      } elseif ($all_empty_fields) {
        $this->record->$group_name->class[] = 'empty-field-group';
      }
    }

    // save the number of groups
    $this->group_count = count((array) $this->record);
  }

  /*   * **************
   * RECORD FIELDS
   */

  /**
   *  gets the field attribues for named field
   * 
   * if given an array, returns an array of field objects
   * 
   * TODO: remove, looks unused
   * 
   * @param string|array $fields
   * @global object $wpdb
   * @return single object or array of objects, indexed by field name
   */
  protected function _get_record_field($fields) {

    global $wpdb;
    $columns = array('name', 'title', 'default', 'help_text', 'form_element', 'validation', 'readonly', 'values', 'persistent');
    $field_objects = array();

    $sql = 'SELECT v.*, "' . $this->module . '" AS "module" 
            FROM ' . Participants_Db::$fields_table . ' v 
            WHERE v.name IN ("' . implode('","',(array)$fields) . '") 
            ORDER BY v.order';
    $result = $wpdb->get_results($sql, OBJECT_K);

    return is_array($fields) ? $result : current($result);
  }

  /*   * **************
   * FIELD GROUPS
   */

  /**
   * gets the field attribues for all fields in a specified group
   * 
   * @var string $group the name of the group of fields to get
   * @return array of field objects
   */
  private function _get_group_fields($group)
  {

    $return = array();
    foreach ($this->fields as $field) {
      if ($field->group == $group) {
    switch ($this->module) {

      case 'signup':
      case 'thanks':
          case 'retrieve':

            if ($field->form_element !== 'placeholder') {
              $return[$field->name] = clone $field;
            }
        break;
        
      case 'record':

            if ($field->form_element !== 'placeholder') {
              $return[$field->name] = clone $field;
            }
        break;

      default:

            if (!in_array($field->form_element, array('placeholder', 'captcha'))) {
              $return[$field->name] = clone $field;
            }
        }
    }
    }

    return $return;
  }

  /**
   * determines if a group has fields to display in the module context
   *
   * @param string $group name of the group to check
   * @return bool
   */
  private function _has_group_fields($group) {

    foreach ($this->fields as $field) {
      if ($field->group == $group) {
    switch ($this->module) {
      case 'signup':
      case 'thanks':
            if ($field->signup > 0) {
              return true;
            }
        break;
          default:
            return true;  
    }
      }
    }
    return false;

  }

  /**
   * gets only display-enabled groups
   * 
   * first, attempts to get the list from the shortcode, then uses the defined as 
   * visible list from the database
   *
   * if the "groups" attribute is used, it overrides the "display" group setting
   *
   * @global object $wpdb
   * @param  bool $logic true to get display-enabled groups, false to get non-enabled groups
   * @return null
   */
  protected function _set_display_groups($logic = true)
  {

    global $wpdb;
    $groups = array();
    if (!empty($this->shortcode_atts['fields']) && $this->display_columns === false) {
      
      $this->_set_display_columns();
      
      foreach ($this->display_columns as $column) {
        $column = $this->fields[$column];
        $groups[$column->group] = true;
      }
      
      $groups = array_keys($groups);
      
    } elseif (!empty($this->shortcode_atts['groups'])) {

      /*
       * process the shortcode groups attribute and get the list of groups defined
       */
      $list = array();
      $groups_attribute = explode(',', str_replace(array(' '), '', $this->shortcode_atts['groups']));
      foreach ($groups_attribute as $item) {
        if (Participants_Db::is_group($item))
          $list[] = trim($item);
      }
      if (count($list) !== 0) {
      /*
       * get a list of all defined groups
       */
      $sql = 'SELECT g.name 
              FROM ' . Participants_Db::$groups_table . ' g';

      $result = $wpdb->get_results($sql, ARRAY_N);
      foreach ($result as $group) {
        if (in_array(current($group), $list) === $logic) {
            $groups[] = current($group);
        }
      }
    }
    }
    if (count($groups) === 0) {

      $sql = 'SELECT g.name 
              FROM ' . Participants_Db::$groups_table . ' g
              WHERE g.display = "' . ( $logic ? '1' : '0' ) . '" ORDER BY g.order ASC';

      $result = $wpdb->get_results($sql, ARRAY_N);

      foreach ($result as $group) {
        $groups[] = current($group);
      }
    }
    $this->display_groups = $groups;
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
   * @return string the value of the field
   */
  protected function _set_field_value($field) {

    /*
     * get the value from the record; if it is empty, use the default value if the 
     * "persistent" flag is set.
     */
    $record_value = isset($this->participant_values[$field->name]) ? $this->participant_values[$field->name] : '';

    $value = $this->_empty($record_value) ? ($this->_empty($field->default) ? '' : $field->default) : $record_value;
    // replace it with the new value if provided, escaping the input
    if (in_array($this->module, array('record','signup','retrieve')) && isset($_POST[$field->name])) {

      $value = $this->_esc_submitted_value($_POST[$field->name]);
    }

    /*
     * make sure id and private_id fields are read only
     */
    if (in_array($field->name, array('id', 'private_id'))) {
      $this->display_as_readonly($field);
    }
    if ($field->form_element === 'hidden') {

      $value = $this->_empty($record_value) ? '' : $record_value;
      /*
       * use the dynamic value if no value has been set
         */
      if (in_array($this->module, array('signup', 'record', 'retrieve'))) {
        if ($value === '') {
          $value = $this->get_dynamic_value($field->default);
        }
        /*
         * add to the display columns if not already present so it will be processed 
         * in the form submission
         */
        $this->display_columns += array($field->name);
        } else {
        // show this one as a readonly field
          $this->display_as_readonly($field);
        }
    }

    $field->value = $value;
  }

  /**
   * determines if the field should be wrapped in a link and sets the link property of the field object
   * 
   * sets the link property of the field, right now only for the single record link
   * 
   * @param object $field field data object
   */
  protected function _set_field_link($field) {

    $link = '';

    //check for single record link
    if (
            !in_array($this->module, array('single', 'signup')) &&
            Participants_Db::is_single_record_link($field) &&
            isset($this->participant_values['id'])
    ) {
      $url = get_permalink(Participants_Db::plugin_setting('single_record_page'));
      $link = Participants_Db::add_uri_conjunction($url) . 'pdb=' . $this->participant_values['id'];
    }

    $field->link = $link;
  }

  /**
   * builds a validated array of selected fields
   * 
   * this looks for the 'field' attribute in the shortcode and if it finds it, goes 
   * through the list of selected fields and sets up an array of valid fields that 
   * can be used in a database query 
   */
  protected function _set_display_columns() {

    // if this has already been set, we're done
    if (is_array($this->display_columns)) return;

    $this->display_columns = array();

    if (isset($this->shortcode_atts['fields'])) {

      $raw_list = explode(',', str_replace(array("'", '"', ' ', "\r", "\n"), '', $this->shortcode_atts['fields']));

      if (is_array($raw_list)) :

        foreach ($raw_list as $column) {

          if (Participants_Db::is_column($column)) {

            $this->display_columns[] = $column;
          }
        }

      endif;
    }

    /*
     * if the field list has not been defined in the shortcode, get it from the global settings
     */
    if (count($this->display_columns) == 0) {
      $this->_set_shortcode_display_columns();
    }
  }

  /**
   * sets up the array of display columns
   * 
   *
   *
   * @global object $wpdb
   */
  protected function _set_shortcode_display_columns() {
    
    if (empty($this->display_groups)) {
      $this->_set_display_groups();
    }
    
    $groups = 'field.group IN ("' . implode('","',$this->display_groups) . '")';

    global $wpdb;
    
    $where = '';
    switch($this->module) {
      
        case 'signup':
        $where .= 'WHERE field.signup = 1 AND ' . $groups . ' AND field.form_element NOT IN ("placeholder")';
        break;
      
      case 'retrieve':
        $where .= 'WHERE field.name = "' . Participants_Db::plugin_setting('retrieve_link_identifier') . '"';
        break;
        
      case 'record':
        $where .= 'WHERE ' . $groups . ' AND field.form_element NOT IN ("captcha","placeholder","hidden")';
        break;
        
      case 'list':
      default:
        $where .= 'WHERE ' . $groups . ' AND field.form_element NOT IN ("captcha","placeholder")';
    }

    $sql = '
      SELECT field.name
      FROM ' . Participants_Db::$fields_table . ' field
      JOIN ' . Participants_Db::$groups_table . ' fieldgroup ON field.group = fieldgroup.name 
      ' . $where . ' ORDER BY  fieldgroup.order, field.order';

    $this->display_columns = $wpdb->get_col($sql);
  }

  /**
   * gets the column and column order for participant listing
   * returns a sorted array, omitting any non-displyed columns
   *
   * @param string $set selects the set of columns to get:
   *                    admin or display (frontend)
   * @global object $wpdb
   * @return array of column names, ordered and indexed by the set order
   */
  public static function get_list_display_columns($set = 'admin_column') {

    global $wpdb;

    $column_set = array();
    // enforce the default value
    $set = $set === 'display_column' ? $set : 'admin_column';

    $sql = '
      SELECT f.name, f.' . $set . '
      FROM ' . Participants_Db::$fields_table . ' f 
      WHERE f.' . $set . ' > 0';

    $columns = $wpdb->get_results($sql, ARRAY_A);

    if (is_array($columns) && !empty($columns)) {
			foreach ($columns as $column) {

				$column_set[$column[$set]] = $column['name'];
			}

			ksort($column_set);
    }

    return $column_set;
  }
  
  /**
   * get a single column object
   * 
   * @param string $name Name of the field to get
   * @return object|bool the result set object or bool false
   */
  public static function get_column_atts($name) {
    $result = clone Participants_Db::$fields[$name];
    return is_object($result) ? $result : false;
  } 

  /**
   * escape a value from a form submission
   *
   * can handle both single values and arrays
   */
  protected function _esc_submitted_value($value) {

    $value = maybe_unserialize($value);

    if (is_array($value)) {

      $return = array();
      foreach ($value as $k => $v)
        $return[$k] = $this->_esc_value($v);
    } else {

      $return = $this->_esc_value($value);
    }

    return $return;
  }

  /**
   * escape a value from a form submission
   */
  private function _esc_value($value) {

    return esc_html(stripslashes($value));
  }

  /*
   * temporarily sets a field to a read-only text line field 
   */
  protected function display_as_readonly(&$field)
  {
    $field->form_element = 'text-line';
    $field->readonly = 1;
  }

  /**
   * parses the value string and obtains the corresponding dynamic value
   *
   * the object property pattern is 'object->property' (for example 'curent_user->name'),
   * and the presence of the  '->'string identifies it.
   * 
   * the superglobal pattern is 'global_label:value_name' (for example 'SERVER:HTTP_HOST')
   *  and the presence of the ':' identifies it.
   *
   * if there is no indicator, the field is treated as a constant
   * 
   * @version 1.6 moved to Base class
   *
   * @param string $value the current value of the field as read from the
   *                      database or in the $_POST array
   *
   */
  public function get_dynamic_value($value) {

    return Participants_Db::get_dynamic_value($value);
  }

  /**
   * prints the form open tag and all hidden fields
   * 
   * The incoming hidden fields are merged with the default fields
   * 
   * @param array $hidden array of hidden fields to print
   * @return null
   */
  protected function _print_form_head($hidden = '') {
    
    $uri_components = parse_url($_SERVER['REQUEST_URI']);

    echo '<form method="post" enctype="multipart/form-data"  autocomplete="' . $this->shortcode_atts['autocomplete'] . '" action="' . $_SERVER['REQUEST_URI'] . '" >';
    $default_hidden_fields = array(
        'action' => $this->module,
        'subsource' => Participants_Db::PLUGIN_NAME,
        'shortcode_page' => $uri_components['path'],
        'thanks_page' => $this->submission_page,
            'instance_index' => Participants_Db::$instance_index,
        'pdb_data_keys' => $this->_form_data_keys(),
    );
    if (WP_DEBUG) echo '<!-- DEBUG: process fields: ' . implode(', ',PDb_Base::get_data_key_columns($this->_form_data_keys())) . ' -->';
    
    $hidden = is_array($hidden) ? $hidden : array();
    
    $hidden_fields = $hidden + $this->hidden_fields + $default_hidden_fields;
    
    PDb_FormElement::print_hidden_fields($hidden_fields);
  }

  /**
   * supplies a template file name and path from the content root
   * 
   * this is for labeling the template file used in the HTML comments
   * 
   * @return string the template filename with a partial path
   */
  public function template_basename() {
    if (WP_DEBUG) {
      $path = $this->template;
    } else {
      $path = '';
      $paths = explode('/', $this->template);
      for($i = 3;$i>0;$i--) {
        $path = '/' . array_pop($paths) . $path;
      }
    }
    return ltrim($path, '/');
  }

  /**
   * sets up the fields property, which contains all field objects
   * 
   */
  protected function _setup_fields() {
    
    $this->fields = array();
    foreach (Participants_Db::$fields as $column) {
      $this->fields[$column->name] = clone $column;
      $this->fields[$column->name]->module = $this->module;
    }
    
  }

  /**
   * replace the tags in text messages
   *
   * a tag contains the column name for the value to use: [column_name]
   *
   * also processes the [record_link] tag
   *
   * @param string $text   the unprocessed text with tags
   *
   * @return string the text with the replacements made
   *
   */
  protected function _proc_tags($text) {
    
    return Participants_Db::replace_tags($text, $this->participant_values, $this->fields);

    /*
    if (empty($values)) {

      foreach ($this->fields as $column) {

        $tags[] = '[' . $column->name . ']';

        $column->value = $this->participant_values[$column->name];

        $values[] = PDb_FormElement::get_field_value_display($column, false);
      }
    }

    // add some extra tags
    foreach (array('id', 'private_id') as $v) {

      $tags[] = '[' . $v . ']';
      $values[] = $this->participant_values[$v];
    }
    $tags[] = '[record_link]';
    $values[] = $this->registration_page;

    $tags[] = '[admin_record_link]';
    $values[] = Participants_Db::get_admin_record_link($this->participant_values['id']);

    $placeholders = array();

    for ($i = 1; $i <= count($tags); $i++) {

      $placeholders[] = '%' . $i . '$s';
    }

    // replace the tags with variables
    $pattern = str_replace($tags, $placeholders, $text);

    // replace the variables with strings
    return vsprintf($pattern, $values);
     */
  }

  

  /**
   * prints the submit button
   *
   * @param string $class a classname for the submit button, defaults to 'button-primary'
   * @param string $button_value submit button text
   * 
   * @return null
   */
  public function print_submit_button($class = 'button-primary', $button_value = '') {

    $pattern = '<input class="%s pdb-submit" type="submit" value="%s" name="save" >';

    printf($pattern, $class, $button_value);
  }

  /**
   * closes the form tag
   */
  protected function print_form_close() {

    echo '</form>';
  }

  /**
   * sets the form submission page
   */
  protected function _set_submission_page()
  {

    $form_status = 'normal';
    if (!empty($this->shortcode_atts['action'])) {
      $this->submission_page = Participants_Db::find_permalink($this->shortcode_atts['action']);
      if ($this->submission_page !== false) $form_status = 'multipage';
    }
    if (!$this->submission_page) {
      $this->submission_page = $_SERVER['REQUEST_URI'];
    }
    $this->set_form_status($form_status);
  }
  
  /**
   * sets the current form status
   * 
   * this is used to determine the submission status of a form; primarily to determine 
   * if a nulti-page form in ins process
   * 
   * @param string $status the new status string or null
   */
  public function set_form_status($status = 'normal') {
    Participants_Db::$session->set('form_status', $status);
  }
  
  /**
   * gets the current form status
   * 
   * @return string the status string: normal, multipage, or complete
   */
  public function get_form_status() {
    return Participants_Db::$session->get('form_status', 'normal');
  }

  /**
   * sets up the shortcode atts session save
   * 
   * @return array the shortcode atts session save
   */
  protected function shortcode_session() {
    return array(
        $this->shortcode_atts['post_id'] => array( 
            $this->module => array( 
                Participants_Db::$instance_index => $this->shortcode_atts
                    )
                )
            );
  }
  
  /**
   * sets up the pdb_data_keys value
   * 
   * the purpose of this value is to tell the submission processor which fields 
   * to process. This is a security measure so that trying to spoof the submission 
   * by adding extra fields, editing readonly fields or deleting fields in the 
   * browser HTML won't work.
   * 
   * readonly fields and hidden fields that have values set are not included in the 
   * set because they are not processed in this context
   * 
   * @return string the value for the pdb_data_keys field
   */
  protected function _form_data_keys() {
    
    $displayed = array();
    foreach ($this->display_columns as $column) {
      $field = $this->fields[$column];
      if ((!in_array($field->form_element, array('hidden')) && $field->readonly === '0') || $field->form_element === 'captcha') {
        $displayed[] = $field->name;
      }
    }
    
    return implode('.', PDb_Base::get_field_indices(array_unique(array_merge($displayed, array_keys($this->hidden_fields)))));
//    return PDb_Base::xcrypt(implode('.', PDb_Base::get_field_indices(array_unique(array_merge($displayed, array_keys($this->hidden_fields))))));
    
  }

  /**
   * prints an empty class designator
   *
   * @param object Field object
   * @return string the class name
   */
  public function get_empty_class($Field) {

    $emptyclass = 'image-upload' == $Field->form_element ? 'image-' . $this->emptyclass : $this->emptyclass;

    return ( $this->_empty($Field->value) ? $emptyclass : '' );
  }

  /**
   * tests a value for emptiness
   *
   * returns true for empty strings, array, objects or null value...everything else 
   * is considered not empty.
   *
   * @param mixed $value the value to test
   * @return bool
   */
  protected function _empty($value) {

    if (!isset($value))
    	return true;

    // if it is an array or object, collapse it
    if (is_object($value))
    	$value = get_object_vars($value);
    
    if (is_array($value))
      $value = implode('', $value);

    return $value === '';
  }
  /**
   * triggers the shortcode present action the first time a plugin shortcode is instantiated
   * 
   */
  public function plugin_shortcode_action() {
    if (!Participants_Db::$shortcode_present) {
      Participants_Db::$shortcode_present = true;
      do_action(Participants_Db::$prefix . 'shortcode_active');
    }
  }

}