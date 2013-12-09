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
 * for a list template, you must instantiate in the loop with each new record:
 * $record = new PDb_Template($this->record);
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2013 xnau webdesign
 * @license    GPL2
 * @version    0.2
 * @link       http://xnau.com/wordpress-plugins/
 */
class PDb_Template {
  
  /**
   * holds the record object
   * @param object $record
   */
  var $record;
  /**
   * holds all the fields data
   * 
   * not organized by groups
   * 
   * @param object $fields
   */
  var $fields;
  /**
   * they type of object used to instantiate the class
   * 
   * @param string $base_type
   */
  var $base_type;
  /**
   * this is an indexed array of raw field values
   * 
   * @param array $values
   */
  var $values;
  
  function __construct(&$object)
  {
    $this->_setup_fields($object);
  }
  
  /**
   * 
   * prints a formatted field value
   * 
   * @param string $name name of the field to print
   */
  public function _print($name) {
    if (isset($this->fields->{$name})) {
      echo PDb_FormElement::get_field_value_display($this->fields->{$name});
    }
  }
  
  /**
   * 
   * prints a formatted field value
   * 
   * alias for _print()
   * 
   * @param string $name name of the field to print
   */
  public function print_value($name) {
    $this->_print($name);
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
   * checks a field for a value to show
   * 
   * @param string $name name of the field to check
   */
  public function has_content($name) {
    return !empty($this->fields->{$name}->value);
  }
  
  /**
   * gets a field property
   * 
   * @param string $name the fields name
   * @param string $prop the field property to get
   * @return string
   */
  public function get_field_prop($name, $prop) {
    return (isset($this->fields->{$name}->{$prop}) ? $this->fields->{$name}->{$prop} : '');
  }
  
  /**
   * gets a group property
   * 
   * @param string $name
   * @param string $prop
   * @return string
   */
  public function get_group_prop($name, $prop) {
    return (isset($this->record->{$name}->{$prop}) ? $this->record->groups->{$name}->{$prop} : '');
  }
  
  /**
   * provides a URL for a record edit link
   * 
   * @return string the URL
   */
  public function get_edit_link() {
    $edit_page = get_permalink(Participants_Db::$plugin_options['registration_page']);
    return $edit_page . (strpos($edit_page, '?') !== false ? '&' : '?') . 'pid=' . $this->get_value('private_id');
  }
  
  /**
   * provides the URL for a record detail page
   * 
   * @return string the URL
   */
  public function get_detail_link() {
    $detail_page = get_permalink(Participants_Db::$plugin_options['single_record_page']);
    return $detail_page . (strpos($detail_page, '?') !== false ? '&' : '?') . 'pdb=' . $this->get_value('id');
  }
  /**
   * adds a link value to a field object
   * 
   * @param string $name
   * @param string $href
   */
  private function _set_link($name, $href) {
    $linkable_field_types = array(
        'text-line',
        'image-upload',
        'file-upload',
        'dropdown',
        'checkbox',
        'radio',
        );
    if (in_array($this->fields->{$name}->form_element, $linkable_field_types))
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
  

  /**
   * sets up the fields object
   * 
   * this will use a different method for each type of object used to instantiate the class
   *
   * @param object $object the instantiating object
   */
  private function _setup_fields(&$object) {
    $this->base_type = get_class($object);
    $this->fields = new stdClass();
    switch ($this->base_type) {
      case 'PDb_List':
        $this->record = ''; // the list module does not have a record iterator
        $this->values = $object->record->values;
        foreach($object->record->values as $name => $value) {
          $this->fields->{$name} = Participants_Db::get_column($name);
          $this->fields->{$name}->value = $value;
          $this->fields->{$name}->value = PDb_FormElement::get_field_value_display($this->fields->{$name});
        }
        reset($object->record->values);
        break;
      case 'PDb_Signup':
      case 'PDb_Single':
      case 'PDb_Record':
      default:
        $this->record = $object->record;
        $this->values = $object->participant_values;
        foreach($this->values as $name => $value) {
          $this->fields->{$name} = Participants_Db::get_column($name);
          $this->fields->{$name}->value = $value;
          $this->fields->{$name}->value = PDb_FormElement::get_field_value_display($this->fields->{$name});
        }
        break;
    }
    //unset($this->record->options);
  }
  
  /**
   * gets an individual value from the raw values array
   * 
   * @param string $name the name of the value to get
   * @return mixed the value
   */
  public function get_value($name) {
    switch($this->base_type) {
      case 'Record_Item':
        return isset($this->record->values[$name]) ? $this->record->values[$name] : '';
      case 'PDb_Single':
      default:
        return isset($this->values[$name]) ? $this->values[$name] : '';
    }
  }
}
?>
