<?php
/*
 * class for handling the display of fields in a template
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
class Field extends Template_Item {
  
  // properties
  
  // the field's value string
  var $value;
  
  // the fields option values
  var $values;
  
  // the validation method for the field
  var $validation;
  
  // the field's form element
  var $form_element;
  
  // the field's defualt value
  var $default_value;
  
  // the help text
  var $help_text;
  
  // the readonly status of the field
  var $readonly;
  
  // the element class name
  var $field_class;
  
  // methods
  
  public function __construct( $field, $id = false ) {
    
    parent::__construct( $field );
    
    $this->assign_props( $field, __CLASS__ );
    
    $this->record_id = $id;
    
  }
  
  // template methods
  
  public function print_label() {
    
    echo $this->_label();
    
  }
  
  public function print_value() {
    
    if ( $this->_is_single_record_link() ) {
      
      $template = '<a class="single-record-link" href="%1$s" title="%2$s" >%2$s</a>';
      
      $output = Participants_Db::make_link(
        get_page_link( $this->options['single_record_page'] ),  // URL of the single record page
        $this->value,                                           // field value
        $template,                                              // template for building the link
        array( 'pdb'=>$this->record_id )                        // add the record id to the GET string
      );
      
    } else {
      
      $output = Participants_Db::prep_field_for_display( $this->value, $this->form_element );
      
    }
    
    echo $output;
    
  }
	
	/**
	 * is this the single record link?
	 * returns boolean
	 */
  private function _is_single_record_link() {

    return (
            isset( $this->options['single_record_link_field'] )
            &&
            $this->name == $this->options['single_record_link_field']
            &&
            ! empty( $this->options['single_record_page'] )
            &&
            ! in_array( $this->form_element, array('textarea', 'link' ) )
            &&
            $this->record_id
            );

  }
  
  /**
   * adds the required marker to a field label as needed
   *
   */
  private function _label() {
    
    $label = $this->prepare_display_value( $this->title );
    
    if ( $this->options['mark_required_fields'] && $this->validation != 'no' ) {
      
      $label = sprintf( $this->options['required_field_marker'], $label );
      
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
  
  public function print_element_id() {
    
    $this->print_CSS_class( $this->name, false  );
    
  }
  
  /**
   * prints the field element
   *
   */
  public function print_element() {
    
    $this->field_class = ( $this->validation != 'no' ? "required-field" : '' ) . ( in_array( $this->form_element, array( 'text-line','date' ) ) ? ' regular-text' : '' );

		if ( $this->readonly ) {
      
      echo '<span class="pdb-readonly '.$this->field_class.'" >'.$this->value.'</span>';
      
    } else {
    
      if ( $this->form_element == 'dynamic-value' ) {
        
        echo '<span class="pdb-readonly '.$this->field_class.'" >'.$this->value.'</span>';
      
      }
    
      $this->_print();

    }
    
  }
  
  /**
   * prints the element
   */
  public function _print() {
    
    FormElement::print_element( array(
                                        'type'       => $this->form_element,
                                        'value'      => $this->value,
                                        'name'       => $this->name,
                                        'options'    => $this->values,
                                        'class'      => $this->field_class,
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