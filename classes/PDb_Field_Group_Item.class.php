<?php
/*
 * class for handling the display of field groups in a template
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
if ( ! defined( 'ABSPATH' ) ) die;
class PDb_Field_Group_Item extends PDb_Template_Item {
  
  // properties
  
  // the group description string
  var $description;
  
  // count of fields in the group
  var $_field_count;
  
  // holds the group's fields
  var $fields;
  
  // methods
  
  /**
   * instantiates a field group object
   *
   * @param object a object with all the field group's properties
   */
  public function __construct( $group, $module ) {
    
    // set up the common porperties
    parent::__construct( $group );
    
    // load the object properties
    $this->assign_props( $group, __CLASS__ );
    
    // set the field count for the group
    $this->_field_count = count( (array) $group->fields );
    
    $this->module = $module;
    
  }
  
  /**
   * prints the title of the group
   *
   * @param string $start_tag the opening tag for the title wrapper
   * @param string $end_tag   the closing tag for the title wrapper
   */
  public function print_title( $start_tag = '<h3 class="pdb-group-title">', $end_tag = '</h3>', $echo = true ) {
    
    if ( $this->printing_title() ) {
      $output = $start_tag.$this->title.$end_tag;
      if ( $echo ) echo $output;
      else return $output;
    }
    
  }
  
  /**
   * prints a group description
   *
   * @param array  $wrap  tags to wrap the description in; first element is
   *                      opening tag, second is closing tag (optional)
   * @param bool   $echo  if true, echo the description (defaults to true)
   *
   */
  public function print_description(  $start_tag = '<p class="pdb-group-description">', $end_tag = '</p>', $echo = true ) {
    
    if ( $this->printing_groups() and ! empty( $this->description ) ) {
      
      $output = $start_tag.$this->prepare_display_value( $this->description ).$end_tag;
      
      if ( $echo ) echo $output;
      else return $output;
      
    }
    
  }
  
  /**
   * indicates whether group titles should be shown
   * 
   * @return bool 
   */
  public function printing_title() {
    
    return (bool) $this->printing_groups() and ! empty( $this->title );
  }
  
  /**
   * indicates whether groups are to be printed in the form
   *
   * signup and record forms print group titles/descriptions only if the setting for that form is true
   * all other shortcodes always print groups, but they're really only seen in single record displays
   * 
   * @return bool true if groups are to be printed
   */
  public function printing_groups() {
    
    switch ($this->module) {
      case 'signup':
        $optionname = 'signup_show_group_descriptions';
        break;
      case 'record':
        $optionname = 'show_group_descriptions';
        break;
      default:
        return true;
    }
    
    return Participants_Db::plugin_setting_is_true($optionname);
  }
  
}