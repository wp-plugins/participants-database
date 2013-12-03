<?php
/*
 * class for handling the display of a participant record
 *
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
class PDb_Record_Item extends PDb_Template_Item {
  
  /**
   * instantiates a field group object
   *
   * @param object a object with all the field group's properties
   */
  public function __construct( $fields, $id, $module = 'none' ) {
    
    $this->module = $module;
    
    // set up the common properties
    parent::__construct( $fields );
    
    // get rid of unneeded properties
    unset( $this->name, $this->title );
    
    // add the record field objects
    // this needs to by typed as array for the iterators to work
    $this->fields = (array) $fields;
    
    $this->record_id = $id;
    
    $this->values = Participants_Db::get_participant($id);
    
  }
  
}