<?php
/*
 * class for handling the display of fields in a template
 *
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdeign@xnau.com>
 * @copyright  2013 xnau webdesign
 * @license    GPL2
 * @version    0.2
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    Template_Item class
 */
class PDb_Field_Item extends PDb_Template_Item {
  
  /**
   * @var string the field's value
   */
  var $value;
  
  /**
   * @var array option values
   */
  var $values;
  
  /**
   * @var array additional attributes
   */
  var $attributes = array();
  
  /**
   *
   * @var string the validation method for the field
   */
  var $validation;
  
  /**
   *
   * @var string the field's form element
   */
  var $form_element;
  
  /**
   *
   * @var int id of the current record
   */
  var $record_id;
  
  /**
   *
   * @var string the field's defualt value
   */
  var $default;
  
  /**
   *
   * @var string the help text
   */
  var $help_text;
  
  /**
   *
   * @var bool the readonly status of the field
   */
  var $readonly;
  
  /**
   *
   * @var string the element class name
   */
  var $field_class;
  
  /**
   *
   * @var string the link href for elements wrapped in an anchor tag
   */
  var $link = false;
  
  /**
   * 
   * @param array $field the field attributes
   * @param mixed $id the id of the source record if available
   */
  public function __construct( $field, $id = false ) {
    
    parent::__construct( $field );
    
    $this->assign_props( $field, __CLASS__ );
    
    $this->record_id = $id;
    
    //error_log(__METHOD__.' instantiated:'.print_r($this,1));
    
  }
  
  // template methods
  
  /**
   * prints a field label
   */
  public function print_label() {
    
    echo $this->_label();
    
  }
  
  /**
   * prints a field value, wrapping with a link as needed
   * 
   */
  public function print_value($print = true) {
    
    if ($print) echo $this->get_value();
    else return $this->get_value();
    
  }
  
  /**
   * returns a field value, wrapping with a link as needed
   * 
   * @return string the field's value, prepped for display
   * 
   */
  public function get_value() {
    
    return PDb_FormElement::get_field_value_display($this);
    
  }
  
  /**
   * provides public access to the single record link test func
   */
  public function is_single_record_link() {
    return $this->_is_single_record_link();
  }
	
	/**
	 * is this the single record link?
	 * returns boolean
	 */
  private function _is_single_record_link() {

    return (
            isset( Participants_Db::$plugin_options['single_record_link_field'] )
            &&
            $this->name == Participants_Db::$plugin_options['single_record_link_field']
            &&
            ! empty( Participants_Db::$plugin_options['single_record_page'] )
            &&
            ! in_array( $this->form_element, array('rich-text', 'link' ) )
            &&
            $this->record_id
            );

  }
  
  /**
   * outputs a single record link
   *
   * @param string $template an optional template for showing the link
   *
   * @return string the HTML for the single record link
   *
   */
  public function output_single_record_link($template = false) {

    $template = $template ? $template : '<a class="single-record-link" href="%1$s" title="%2$s" >%2$s</a>';
    $url = get_permalink(Participants_Db::$plugin_options['single_record_page']);
    $url = Participants_Db::add_uri_conjunction($url) . 'pdb=' . $this->record_id;

    return sprintf($template, $url, (empty($this->value) ? $this->default : $this->value));
  }
  
  /**
   * adds the required marker to a field label as needed
   *
   */
  private function _label() {
    
    $label = $this->prepare_display_value( $this->title );
    
    if ( Participants_Db::$plugin_options['mark_required_fields'] && $this->validation != 'no' && in_array($this->module,array('signup','record'))) {
      
      $label = sprintf( Participants_Db::$plugin_options['required_field_marker'], $label );
      
    }
    
    return $label;
    
  }
  
  /**
   * prints a CSS class name based on the form_element
   */
  public function print_element_class() {
    
    // for compatibility we are not prefixing the form element class name
    $this->print_CSS_class( $this->form_element, false );
    
  }
  /**
   * prints a CSS classname based on the field name
   */
  public function print_element_id() {
    
    $this->print_CSS_class( $this->name, true  );
    
  }
  
  /**
   * prints the field element
   *
   */
  public function print_element() {

    $this->field_class = ( $this->validation != 'no' ? "required-field" : '' ) . ( in_array($this->form_element, array('text-line', 'date', 'timestamp')) ? ' regular-text' : '' );

    if ($this->readonly && !in_array($this->form_element, array('captcha'))) {

      if (!in_array($this->form_element, array('rich-text'))) {
        
        $this->attributes['readonly'] = 'readonly';
        $this->_print();
      } else {

        $this->value = PDb_FormElement::get_field_value_display($this);
      echo '<span class="pdb-readonly ' . $this->field_class . '" >' . $this->value . '</span>';
      }
    } else {

      $this->_print();
    }
  }
  
  /**
   * prints the element
   */
  public function _print() {
    
    PDb_FormElement::print_element( array(
                                        'type'       => $this->form_element,
                                        'value'      => $this->value,
                                        'name'       => $this->name,
                                        'options'    => $this->values,
                                        'class'      => $this->field_class,
                                        'attributes' => $this->attributes,
                                        'module'     => $this->module,
                                        )
                                 );
    
  }
  
  /**
   * tells if the help_text is defined
   */
  public function has_help_text() {
    
    return ! empty( $this->help_text );
  
  }
  
  /**
   * prints the field's help text
   */
  public function print_help_text( ) {
    
    if ( ! empty( $this->help_text ) ) {
      
      echo $this->prepare_display_value( $this->help_text );  
      
    }
    
  }
  
  /**
   * returns a field's error status
   *
   * @return mixed bool false if no error, string error type if validation error is set
   *
   */
  public function has_error() {
    
    $error_array = array('no error');
    
    if ( is_object( Participants_Db::$validation_errors ) )
      $error_array = Participants_Db::$validation_errors->get_error_fields();
      
    if ( $error_array and isset( $error_array[$this->name] ) ) return $error_array[$this->name];
    
    else return false;
    
  }
  
}