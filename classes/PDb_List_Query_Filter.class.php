<?php

/*
 * class defining methods and properties for a single query filter statement
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2015 xnau webdesign
 * @license    GPL2
 * @version    0.5
 * @link       http://xnau.com/wordpress-plugins/
 */
if ( ! defined( 'ABSPATH' ) ) die;
/*
 * defines the List Query Filter Object
 * 
 * each object is a single filter on a specific field. These objects will be 
 * orgainized into arrays of objects for each field
 */
class PDb_List_Query_Filter {
  /**
   * @var string the name of the field the filter is to be applied to
   */
  var $field;
  /**
   * @var string the filter expressed as a mysql query fragment
   */
  private $sql;
  /**
   * @var bool if true, the filter is logically joined to it's successor with an 
   *           OR, if false, it is an AND
   */
  private $or_statement = false;
  /**
   * @var string the term used in the filter 
   */
  var $term = '';
  /**
   * @var int a unique sequential index for the filter
   */
  private $index;
  /**
   * @var bool true if the filter is a "background" filter
   * 
   * a background filter is one that is set in the shortcode and is therefore a 
   * special case that should be overridden by a non-background filter. It is not 
   * considered a "search" in the sense that it is not user-generated
   */
  private $background = false;
  /**
   * @var bool true if term is used in a "LIKE" statement
   */
  public $like_term = false;
  /**
   * instantiates the filter object
   * 
   * 'field'     => $field_name
   * 'statement' => $statement
   * 'logic'     => $logic
   * 'term'      => $search_term
   * 'shortcode' => $shortcode
   */
  public function __construct($params = array())
  {
    $this->update_parameters($params);
  }
  /**
   * returns the un-sanitized filter term
   * 
   * @return string
   */
  public function get_raw_term() {
    return $this->term;
  }
  /**
   * returns the OR logic status
   * 
   * @return bool true if the filter uses an OR logic
   */
  public function is_or() {
    return $this->or_statement;
  }
  /**
   * returns the logic keyword
   * 
   * @return string
   */
  public function logic() {
    return $this->or_statement ? 'OR' : 'AND';
  }
  /**
   * returns the filter sql statement
   * 
   * @return string
   */
  public function statement() {
    return $this->sql;
  }
  /**
   * returns the background filter status
   * 
   * @return bool
   */
  public function is_shortcode() {
    return $this->background;
  }
  /**
   * returns true of the filter originated as a user search
   * 
   * @return bool
   */
  public function is_search() {
    return !$this->background;
  }
  /**
   * updates the parameters
   * 
   * leaves omitted parameters alone
   * 
   * @param array the parameters array
   */
  public function update_parameters($params) {
    
    if (isset($params['field'])) {
      $this->field = Participants_Db::$fields[$params['field']];
      if (!$this->field) $this->field = ''; // blank it if the field name is invalid
    }
    if (isset($params['statement'])) {
      $this->sql = $this->sanitize_sql($params['statement']);
    }
    if (isset($params['logic'])) {
      $this->or_statement = $params['logic'] === 'OR';
    }
    if (isset($params['shortcode'])) {
      $this->background = filter_var($params['shortcode'], FILTER_VALIDATE_BOOLEAN);
    }
    if (isset($params['term'])) {
      $this->set_search_term($params['term']);
    }
    if (isset($params['index'])) {
      $this->index = $params['index'];
  	}
  }
  /**
   * sets the search term property
   * 
   * @param string $term
   * @return null
   */
  public function set_search_term($term) {
   if ($term === 'null' || $term === ''  || is_null($term)) {
     $this->term = '';
   } else {
     global $wpdb;
     if (method_exists($wpdb, 'esc_like')) {
			 $this->term = $wpdb->esc_like(PDb_FormElement::get_title_value($term, $this->field->name));
     } else {
			 $this->term = PDb_FormElement::get_title_value($term, $this->field->name);
    }
   }
  }
  /**
   * supplies the current empty state logic value
   * 
   * true - empty search allowed
   * false - empty searches not allowed
   * 
   * @return bool
   */
  public function empty_search_allowed() {
    if ($this->is_shortcode() || Participants_Db::plugin_setting_is_true('empty_search', false)) {
      return true;
    } else {
      return false;
    }
  }
  /**
   * is it a search on an empty value?
   * 
   * @return bool true if searches for empty values are allowed and the term is empty
   */
  public function is_empty_search() {
    
    return $this->term === '' && $this->empty_search_allowed();
  }
  /**
   * provides the current search term
   * 
   * this will be escaped as needed
   * 
   * @return string
   */
  public function get_term() {
    if ($this->wildcard_present() || $this->like_term === true) {
    	global $wpdb;
      return str_replace(array('*', '?'), array('%', '_'), $wpdb->esc_like($this->term));
    } else {
      return esc_sql($this->term);
    }
  }
  /**
   * tests a search term for placeholder wildcard characters
   * 
   * in the user search, the * and ? are stand-ins for the MySQL wildcards % and _
   * 
   * @param string $term the term to test
   * @return bool true if wildcard characters are present
   */
  public function wildcard_present() {
    return strpos($this->term, '*') !== false || strpos($this->term, '?') !== false;
  }
  /**
   * is the term defined?
   * 
   * @return bool true if term is a non-empty string
   */
  public function is_string_search() {
    return is_string($this->term) && trim($this->term) !== '';
  }
  /**
   * supplies the current index
   * 
   * @return int the index
   */
  public function index() {
    return $this->index;
  }
  /**
   * sanitizes an SQL statement
   * 
   * @param string $sql the query statement
   * @return string the sanitized statement
   */
  public static function sanitize_sql($sql) {
    return stripslashes(esc_sql($sql));
  }
}