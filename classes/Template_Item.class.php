<?php
/*
 * class providing common functionality for displaying
 * database item objects (records, fields and groups) in templates
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2011 xnau webdesign
 * @license    GPL2
 * @version    0.1
 * @link       http://xnau.com/wordpress-plugins/
 *
 */
class Template_Item {
  
  // properties
  
  // the unique string identifier for the object
  var $name;
	
  // the printable title of the object
  var $title;
	
	// the id of the current record
	var $record_id;
	
	// the plugin options
	var $options;
	
	// the instantiating module
	var $module;
  
  // methods
  
  /**
   * constructs a Template Item object
   *
   * @param object $properties an object with the item's properties
   */
  public function __construct( $properties ) {
		
		$this->options = Participants_Db::$plugin_options;
    
  }
  
  /**
   * tests a value for emptiness, includinf arrays with empty elements
   * 
   * @param mixed $value the value to test
   * @return bool
   */
  public function is_empty($value) {
    
    if (is_array($value)) $value = implode('', $value);
    
    return empty($value);
  }

  /**
   * displays an object value with deslashing and entity encoding
   *
   * @param string $string the value to be printed
   */
  protected function print_value() {
    
    echo prepare_display_value($this->value);
    
  }
  
  /**
   * prepare a field for display
   *
   * primarily to deal with encoded characters, quotes and slashes
   * 
   * @param string $string the value to be prepared
   */
  protected function prepare_display_value( $string ) {
    
    return stripslashes( $string );//htmlspecialchars( stripslashes( $string ), ENT_QUOTES, "UTF-8", false );
  
  }
  
  /**
   * prints a CSS classname
   *
   * @param string $name    string identifier for the class, defaults to the
   *                        name of the object
   */
  protected function print_CSS_class( $name = false, $prefix = true ) {
    
    $name = false === $name? $this->name : $name;
    
    echo $this->prepare_CSS_class( $name, $prefix );
    
  }
  
  /**
   * prepares a CSS classname
   *
   * attempts to make sure the classname is valid since this can be set in the
   * shortcode and you never know what people will try to do
   *
   * this will also add the global CSS prefix to maintain namespace
   *
   * @param string $name    string identifier for the class, defaults to the
   *                        name of the object
   * @param bool   $prefix  true to add global prefix, defaults to true
   *
   * @return string the prepared class name
   *
   */
  protected function prepare_CSS_class( $name = false, $prefix = true ) {
    
    if ( false === $name ) $name = $this->name;
    
    // make sure it does not begin with a numeral
    $classname = preg_replace( '/^([0-9])/','n$1', $name );
    // clean out any non-valid CSS name characters
    $classname = preg_replace( '/[^_a-zA-Z0-9-]/','', $classname );
    
    return $prefix ? Participants_Db::$css_prefix.$classname : $classname;
    
  }
  
  /**
   * assigns the object properties that match properties in the slupplied object
   * 
   * @param object $item the supplied object
   * @param string $class the classname of the instantiating class
   */
  protected function assign_props( $item, $class = __CLASS__ ) {
    
    // grab and assign the class properties from the provided object
    foreach( get_class_vars( $class ) as $property => $value ) {
      
      // skip any properties not needed in the subclass object
      if ( ! in_array( $property, array( 'options' ) ) && isset( $item->$property ) ) {
        
        $this->$property = $item->$property;
      
      }
      
    }
    
  }
  
}