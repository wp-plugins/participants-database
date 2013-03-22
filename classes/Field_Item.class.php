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
class Field_Item extends Template_Item {
  
  // properties
  
  // the field's value string
  var $value;
  
  // the fields option values
  var $values;
  
  // the validation method for the field
  var $validation;
  
  // the field's form element
  var $form_element;
  // the field's id
  var $record_id;
  
  // the field's defualt value
  var $default;
  
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
  
  /**
   * prints a field value, wrapping with a link as needed
   * 
   */
  public function print_value() {
    
    if ( $this->is_single_record_link() ) {
			
      $output = $this->output_single_record_link();
      
    } else {
      
      $output = Participants_Db::prep_field_for_display( $this->value, $this->form_element );
      
    }
    
    echo $output;
    
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
            isset( $this->options['single_record_link_field'] )
            &&
            $this->name == $this->options['single_record_link_field']
            &&
            ! empty( $this->options['single_record_page'] )
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
		$url = get_permalink($this->options['single_record_page']);
		$url = Participants_Db::add_uri_conjunction($url) . 'pdb='.$this->record_id;
		
		return sprintf($template, $url, (empty($this->value)?$this->default:$this->value));
		
	}
  
  /**
   * adds the required marker to a field label as needed
   *
   */
  private function _label() {
    
    $label = $this->prepare_display_value( $this->title );
    
    if ( $this->options['mark_required_fields'] && $this->validation != 'no' && $this->module != 'single' ) {
      
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
    
    $this->field_class = ( $this->validation != 'no' ? "required-field" : '' ) . ( in_array( $this->form_element, array( 'text-line','date' ) ) ? ' regular-text' : '' );

		if ( $this->readonly ) {
      
      if ( $this->form_element == 'date' ) $this->value = FormElement::format_date($this->value);
      
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