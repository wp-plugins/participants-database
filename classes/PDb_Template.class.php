<?php

/*
 * utility class for accessing a record from a template
 * 
 * this class facilitates building a template that is not based on a loop, but needs 
 * to access and print any of the fields of a record by name
 * 
 * use the class by instantiating it with the "$this" variable, then use the resuting 
 * object methods in your template: $record = new PDb_Template($this);
 * 
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2015 xnau webdesign
 * @license    GPL2
 * @version    1.0
 * @link       http://xnau.com/wordpress-plugins/
 */

if ( ! defined( 'ABSPATH' ) ) die;
class PDb_Template {
  
  /**
   * @var object holds the instantiating object
   */
  /**
   * holds the record object
   * @var object $record
   */
  var $record;
  /**
   * holds all the fields data
   * 
   * not organized by groups
   * 
   * @var object $fields
   */
  var $fields;
  /**
   * holds the currently displayed groups
   * 
   * @var obect $groups
   */
  var $groups;
  /**
   * holds the current module name
   * 
   * @var string module name
   */
  var $module;
  /**
   * the type of object used to instantiate the class
   * 
   * @var string $base_type
   */
  var $base_type;
  /**
   * this is an indexed array of raw (as stored) field values
   * 
   * @var array $values
   */
  var $values;
  /**
   * permalink to the record edit page
   * 
   * @var string 
   */
  var $edit_page;
  /**
   * permalink to the single record page
   *
   * @var string
   */
  var $detail_page;
  /**
   * @var int the record ID
   */
  var $id;
  /**
   * @var string the link to the detail page
   */
  var $detail_link;
  /**
   * @var string link to the record edit page
   */
  var $edit_link;
  /**
   * this class is instantiated with the module class
   * 
   * @var type $object
   */
  function __construct($object)
  {
    $this->list_object = $object;
    $this->_setup_fields();
    unset($this->list_object);
  }
  
  /**
   * 
   * prints a formatted field value
   * 
   * public alias for _print()
   * 
   * @param string $name name of the field to print
   */
  public function print_field($name) {
    $this->_print($name);
  }
  
  /**
   * 
   * prints a formatted field value
   * 
   * alias for print_field()
   * 
   * @param string $name name of the field to print
   */
  public function print_value($name) {
    $this->_print($name);
  }
  
  
  /**
   * gets an individual value from the raw values array
   * 
   * @param string $name the name of the value to get
   * @return mixed the value
   */
  public function get_value($name) {
  	return $this->_value($name);
  }
  
  
  /**
   * sets a field value
   * 
   * this does no field format checking, you must use a compatible value, for instance 
   * if the field stores it's value as an array, you must store an array
   *
   * @param string $name of the field
   * @param int|string $value the value to set the field to
   */
  public function set_value($name, $value) {
    if (Participants_Db::is_column($name)) {
      $this->_setvalue($name, $value);
    }
  }
  
  /**
   * prints a field title
   * 
   * @param string $name
   */
  public function print_title($name) {
    
    echo $this->get_field_prop($name, 'title');
      
  }
  
  /**
   * prints a field property
   * 
   * @param string $name the fields name
   * @param string $prop the field property to get
   * @return string
   */
  public function print_field_prop($name, $prop) {
    echo $this->get_field_prop($name, $prop);
  }
  
  /**
   * prints a group title given it's name
   * 
   * @param string $name
   * @return string
   */
  public function print_group_title($name) {
    echo $this->get_group_prop($name, 'title');
  }
  /**
   * determines if the named group has a defined description
   * 
   * @param string $name the groups name
   * 
   * @return bool true if the group description is non-empty
   */
  public function has_group_description($name) {
  	$description = $this->get_group_prop($name, 'description');
  	return !empty($description);
  }
  
  /**
   * prints a group title given it's name
   * 
   * @param string $name
   * @return string
   */
  public function print_group_description($name) {
    echo $this->get_group_prop($name, 'description');
  }
  
  /**
   * prints a value wrapped in an anchor tag with an href value
   * 
   * @param string $name of the field
   * @param string $href the href value
   * @return null
   */
  public function print_with_link($name, $href) {
    if (is_object($this->fields->{$name}) && !empty($href)) {
      $this->_set_link($name, $href);
      $this->_print($name);
    }
  }
  
  /**
   * gets a field property
   * 
   * @param string $name the fields name
   * @param string $prop the field property to get
   * @return string|array
   */
  public function get_field_prop($name, $prop) {
    return (isset($this->fields->{$name}->{$prop}) ? maybe_unserialize($this->fields->{$name}->{$prop}) : '');
  }
  
  /**
   * gets a group property
   * 
   * @param string $name
   * @param string $prop
   * @return string
   */
  public function get_group_prop($name, $prop) {
    return isset($this->groups[$name]->{$prop}) ? $this->groups[$name]->{$prop} : '';
  }
  
  /**
   * provides a URL for a record edit link
   * 
   * @return string the URL
   */
  public function get_edit_link($page = '') {
    $edit_page = empty($page) ? $this->edit_page : Participants_Db::find_permalink($page);
    return $this->_cat_url_var($edit_page, 'pid', $this->_value('private_id'));
  }
  
  /**
   * provides the URL for a record detail page
   * 
   * @return string the URL
   */
  public function get_detail_link($page = '') {
    $detail_page = empty($page) ? $this->detail_page : Participants_Db::find_permalink($page);
    return $this->_cat_url_var($detail_page, 'pdb', $this->_value('id'));
  }
  
  /**
   * sets the edit page property
   * 
   * it is assumed the [pdb_record] shortcode is on that page
   * 
   * @param string|int $page the page slug, path or ID
   */
  public function set_edit_page($page = '') {
    if (empty($page)) {
      $page = Participants_Db::$plugin_options['registration_page'];
    }
    $this->edit_page = Participants_Db::find_permalink($page);
    }
  /**
   * sets the detail page property
   * 
   * it is assumed the [pdb_single] shortcode is on that page
   * 
   * @param string|int $page the page slug, path or ID
   * 
   */
  public function set_detail_page($page = '') {
    if (empty($page)) {
      $page = !empty($this->list_object->shortcode_atts['single_record_link']) ? $this->list_object->shortcode_atts['single_record_link'] : Participants_Db::$plugin_options['single_record_page'];
    }
    $this->detail_page = Participants_Db::find_permalink($page);
  }
  
  /**
   * checks a field for a value to show
   * 
   * @param string $name names of the field to check

   * @return bool true if field value is non-empty
   */
  public function has_content($name) {
    
  	$value = $this->fields->{$name}->value;
    return strlen($value) !== 0;
  }
  /**
   * determines if a group has any fields with non-empty content
   * 
   * typically, this is used to determine if a group should be shown or not
   * 
   * @param string $group name of the group
   * @return bool true if at least one of the group's fields have content
   */
  public function group_has_content($group) {
  	if ($this->base_type === 'PDb_List') {
  		return true;
  	} else {
  		if (is_array($this->groups[$group]->fields)) {
  			foreach ($this->groups[$group]->fields as $field_name) {
  				if ($this->has_content($field_name)) {
  					return true;
  				}
  			}
  		}
  		return false;
  	}
  }
  /**
   * provides the field's form element
   * 
   * @var string $name the field name
   * @return string HTML
   */
  public function get_form_element($name) {
    if (property_exists($this->fields, $name)) {
      $field = $this->fields->{$name};
      if (!property_exists($this->fields, 'attributes') ) {
        $field->attributes = array();
      }
      $element = array(
          'type' => $field->form_element,
          'name' => $field->name,
          'value' => $field->value,
          'options' => $field->values,
          'class' => Participants_Db::$prefix . $field->form_element,
          'attributes' => $field->attributes,
      );
      return PDb_FormElement::get_element($element);
    }
  }
  
  /**
   * prints a field form element
   * 
   * @var string $name
   * @return null
   */
  public function print_form_element($name) {
    echo $this->get_form_element($name);
  }
  /**
   * returns the named value
   * 
   * @param string $name of the property
   * 
   * @return mixed
   */
  protected function _value($name) {
    switch($this->base_type) {
      case 'Record_Item':
        return isset($this->record->values[$name]) ? $this->record->values[$name] : '';
      case 'PDb_Single':
      default:
        return isset($this->values[$name]) ? $this->values[$name] : '';
    }
  }
  /**
   * sets the field value
   * 
   * @param string $name of the property
   * @param string|array $value the value to set
   * @return mixed
   */
  protected function _setvalue($name, $value) {
    switch($this->base_type) {
      case 'Record_Item':
        $this->record->values[$name] = $value;
      case 'PDb_Single':
      default:
        $this->values[$name] = is_array($value) ? serialize($value) : $value;
        if (!isset($this->fields->{$name})) {
          $this->fields->{$name} = new stdClass();
        }
        $this->fields->{$name}->value = $value;
    }
  }
  /**
   * 
   * prints a formatted field value
   * 
   * @param string $name name of the field to print
   */
  protected function _print($name) {
    if (isset($this->fields->{$name})) {
      echo PDb_FormElement::get_field_value_display($this->fields->{$name});
    }
  }
  /**
   * adds a link value to a field object
   * 
   * @param string $name
   * @param string $href
   */
  private function _set_link($name, $href) {
    if (PDb_FormElement::field_is_linkable($this->fields->{$name})) {
    switch ($this->base_type) {
      case 'PDb_List':
        $this->fields->{$name}->link = $href;
        break;
      case 'PDb_Signup':
      case 'PDb_Single':
      case 'PDb_Record':
      default:
        $group = $this->fields->{$name}->group;
        $field = $this->record->{$group}->fields->{$name}->link = $href;
    }
  }
  }
  

  /**
   * sets up the fields object
   * 
   * this will use a different method for each type of object used to instantiate the class
   */
  private function _setup_fields() {
    $this->base_type = get_class($this->list_object);
    $this->values = $this->list_object->participant_values;
    $this->set_edit_page();
    $this->set_detail_page();
    $this->module = $this->list_object->module;
    $this->id = $this->values['id'];
    $this->edit_link = $this->_cat_url_var($this->edit_page, 'pid', $this->values['private_id']);
    $this->detail_link = $this->_cat_url_var($this->detail_page, 'pdb', $this->id);
    $this->fields = new stdClass();
    $this->groups = array();
    switch ($this->base_type) {
      case 'PDb_List':
        foreach($this->list_object->record->fields as $field_object) {
          $name = $field_object->name;
          $value = isset($field_object->value) ? $field_object->value : '';
          $this->fields->{$name} = Participants_Db::get_column($name);
          $this->fields->{$name}->module = $this->list_object->module;
          $this->fields->{$name}->value = $value;
        }
        reset($this->list_object->record->fields);
        $this->_setup_list_record_object();
        break;
      case 'PDb_Signup':
      case 'PDb_Single':
      case 'PDb_Record':
      default:
        if (!isset($this->list_object->record)) {
          error_log(__METHOD__.' cannot instantiate ' . __CLASS__ . ' object. Class must be instantiated with full module object.');
          break;
        }
        $this->record = clone $this->list_object->record;
        foreach($this->list_object->fields as $name => $field) {
          $this->fields->{$name} = $field;
          $this->fields->{$name}->module = $this->list_object->module;
          $this->fields->{$name}->value = $this->values[$name];
          }
        foreach($this->record as $name => $group) {
          $this->groups[$name] = $this_group = new stdClass();
          $this_group->name = $name;
          $this_group->title = $group->title;
          $this_group->description = $group->description;
          $this_group->fields = array();
          foreach ($group->fields as $group_field) {
          	$this_group->fields[] = $group_field->name;
          }
          reset($group->fields);
        }
        reset($this->record);
        break;
    }
    //unset($this->record->options);
  }
  /**
   * builds a record object for the list module
   * 
   * @return null
   */
  private function _setup_list_record_object() {
    $this->record = new stdClass();
    $this->_setup_record_groups();
    foreach ($this->fields as $name => $field) {
      $this->groups[$field->group]->fields[$field->order] = $name;
      $this->record->{$field->group}->fields->{$name} = clone $field;
    }
      }
  private function _setup_record_groups() {
    foreach ($this->list_object->display_groups as $group_name) {
      $this->record->{$group_name} = new PDb_Template_Field_Group(Participants_Db::get_group($group_name));
    }
  }
  /**
   * adds a value to an url
   * 
   * @param string $url
   * @param string $name of the variable
   * @param string $value
   * 
   * @return string the concatenated url
   */
  private function _cat_url_var($url, $name, $value) {
  	$op = strpos($url, '?') === false ? '?' : '&';
  	return $url . $op . $name . '=' . urlencode($value);
  }
}
/**
 * class that forms a container for field groups
 */
class PDb_Template_Field_Group {
  
  var $id;
  var $title;
  var $order;
  var $display;
  var $name;
  var $admin;
  var $description;
  /**
   *
   * @var object
   */
  var $fields;
  /**
   * instantates the object
   */
  public function __construct($group)
  {
    $this->fields = new stdClass;
    $this->_import($group);
  }
  /**
   * imports the incoming object's properties
   */
  private function _import($object)
  {   
      foreach (get_object_vars($object) as $key => $value) {
          $this->$key = $value;
      }
  } 
}
?>
