<?php
/*
 * this class is provided for backwards compatibility only since I prefixed all my class names
 * 
 * it won't work in environments where there is both a legacy call to the FormElement 
 * class and a conflicting class definition
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2013 xnau webdesign
 * @license    GPL2
 * @version    0.2
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    PDb_FormElement class
 */

class FormElement {
  /**
   * prints a form element
   * 
   * @param array $params
   */
  public static function print_element($params) {
   PDb_FormElement::print_element($params);
  }
  /**
   * gets a form element html
   * 
   * @param array $params
   * @return string
   */
  public static function get_element($params) {
   return PDb_FormElement::get_element($params);
  }
  /**
   * prints a series of hidden fields
   * 
   * @param array $fields name => value pairs
   * @param bool $print true to print, false to return
   */
  public static function print_hidden_fields($fields, $print = true) {
    $hidden_fields = PDb_FormElement::print_hidden_fields($fields, false);
    if ($print) echo $hidden_fields;
    else return $hidden_fields;
  }
}

?>
