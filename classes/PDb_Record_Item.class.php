<?php
/*
 * class for handling the display of a participant record
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdeign@xnau.com>
 * @copyright  2011 xnau webdesign
 * @license    GPL2
 * @version    0.1
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    Template_Item class
 */
if ( ! defined( 'ABSPATH' ) ) die;
class PDb_Record_Item extends PDb_Template_Item {
  
  /**
   * instantiates a field group object
   *
   * @param object a object with all the field group's properties
   */
  public function __construct( $record, $id, $module ) {
    
    $this->module = $module;
    
    // set up the common properties (currently does nothing)
    parent::__construct( $record );
    
    // get rid of unneeded properties
    unset( $this->name, $this->title );
    
    // add the record field objects
    // this needs to by typed as array for the iterators to work
    $this->fields = (array) $record;
    
    $this->record_id = $id;
    
    $this->setup_values();
    
    
//    $this->values = Participants_Db::get_participant($id);
    
  }
  
  /**
   * sets up the values property
   */
  private function setup_values() {
    foreach($this->fields as $name => $field) {
    
      // get the field attributes
      $field = (object) array_merge((array)$field, (array)Participants_Db::$fields[$name]);
      
      if (isset($field->value)) $this->values[$name] = $field->value;
    }
    reset($this->fields);
  }
  
}