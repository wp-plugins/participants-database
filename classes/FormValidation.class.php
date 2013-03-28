<?php
/**
 * Form Validation Class
 *
 * tracks form submission validation and provides user feedback
 */
class FormValidation {

	// this array collects validation errors for each field
	private $errors;

  // holds the error messages
	public $error_messages = array();
  
  // holds the CSS for an error indication as defined in the options
	private $error_style;
	public	$error_CSS;
  private $error_html_wrap;
	
	// holds the class name we give the container: error or message
	private $error_class;

  // holdes an array of all the submitted values
  private $post_array;

	/*
	 * instantiates the form validation object
	 * this is meant to be instantiated once per form submission
	 *
	 */
	public function __construct() {
    
    $this->post_array = $_POST;

		// clear the array
		$this->errors = array();

    /*
     * get our error messages from the plugin options
     * 
     */
    foreach ( array( 'invalid','empty','nonmatching','duplicate' ) as $error_type ) {
      $this->error_messages[$error_type] = Participants_Db::$plugin_options[$error_type.'_field_message'];
    }
    /*
     * this filter provides an opportunity to add or modify validation error messages
     * 
     * for example, if there is a custom validation that generates an error type of 
     * "custom" an error message with a key of "custom" will be shown if it fails.
     */
		if (has_filter(Participants_Db::$css_prefix . 'validation_error_messages')) {
			$this->error_messages = apply_filters(Participants_Db::$css_prefix . 'validation_error_messages', $this->error_messages);
		}
    
		$this->error_style = Participants_Db::$plugin_options['field_error_style'];

    // set the default error wrap HTML for the validation error feedback display
    $this->error_html_wrap = array( '<div class="%s">%s</div>','<p>%s</p>' );

	}
	
	function __destruct() {
  }

	/**
	 * validates a field submitted to the main database
	 *
	 * receives a validation pair and processes it, adding any error to the
	 * validation status array
	 *
	 * @param string $value       the submitted value of the field
	 * @param object $column_atts the column atributes object
	 *                            validation key can be NULL, 'yes', 'email', regex
	 * @param array  $post        the post array with all submitted values, defaults 
   *                            to $this->post_array as instantiated
	 */
	public function validate( $value, $column_atts, $post = false ) {

		$this->_validate_field( $value, $column_atts->name, $column_atts->validation, $column_atts->form_element );

    if ( $post ) $this->post_array = $post;

	}

	/**
	 * validates a field from a general form submission
	 *
	 * @param string $value       the submitted value of the field
	 * @param string $name        the name of the field
	 * @param string $validation  the validation key to use in validating the
	 *                            submission. Could be 'yes', 'email' or a regex pattern
	 */
	public function validate_field( $value, $name, $validation = NULL ) {

		$this->_validate_field( $value, $name, $validation );

	}

	/**
	 * gives the error status of the form submission
	 */
	public function errors_exist() {

		return empty( $this->errors ) ? false : true;

	}

	/**
	 * prepares the error messages and CSS for a main database submission
	 *
	 * @return array indexed array of error messages
	 */
	public function get_validation_errors() {

		// check for errors
		if ( ! $this->errors_exist() ) return array();

		$output = '';
		$error_messages = array();
		$this->error_CSS = array();

		foreach ( $this->errors as $field => $error ) :

			$field_atts = Participants_Db::get_field_atts( $field );

			switch ( $field_atts->form_element ) {

				case 'rich-text':
				case 'text-area':
				case 'textarea':
					$element = 'textarea';
					break;
        
        case 'link':
          $field_atts->name .= '[]';
				case 'text':
				case 'text-line':
        case 'date':
					$element = 'input';
					break;
					
				case 'image-upload':
					$element = 'input';
					break;

				default:
					$element = false;

			}

			if ( $element ) $this->error_CSS[] = $element.'[name="'.$field_atts->name.'"]';

			if ( isset( $this->error_messages[$error] ) ) {
        $error_messages[] = sprintf( $this->error_messages[$error], $field_atts->title );
				$this->error_class = Participants_Db::$css_prefix . 'error';
      } else {
      	$error_messages[] = $error;
				$this->error_class = empty( $field ) ? Participants_Db::$css_prefix . 'message' :Participants_Db::$css_prefix . 'error' ;
			}

		endforeach;// $this->errors 
		
		return $error_messages;

	}

	/**
	 * adds an arbitrary error to the object
	 * 
	 * @param string $name field name
	 * @param string $message message handle or liter message string
	 */
	public function add_error( $name, $message ) {
		
		$this->_add_error( $name, $message );
		
	}

	/**
	 * returns the error messages and CSS HTML
	 *
	 *
	 */
	private function _error_html( $error_messages ) {
		
		if ( empty ( $error_messages ) ) return '';
		
		$output = $this->get_error_CSS();

    $messages = '';
    
    foreach( $error_messages as $message ) {
      
      $messages .= sprintf($this->error_html_wrap[1],$message );
      
    }

		$output .= sprintf( $this->error_html_wrap[0], $this->error_class, $messages );

		return $output;

	}
	
	/**
	 * returns the error messages HTML
	 *
	 */
	public function get_error_html( ) {

		return $this->_error_html( $this->get_validation_errors() );

	}
	
	public function get_error_CSS() {

		if ( ! empty( $this->error_CSS ) ) return '<style type="text/css">'.implode( ', ',$this->error_CSS ).'{ '.$this->error_style.' }</style>';
		
		else return '';
		
	}
	
	public function get_error_class() {
		
		return $this->error_class;
		
	}
		
  /**
   * sets the html wrapper for the error message display
   *
   * @param string $container wraps the whole error message element, must include
   *                          2 %s placeholders: first for a class name, then one for the content
   * @param string $wrap      wraps each error message, must have %s placeholders for the content.
   *
   */
	public function set_error_html( $container, $wrap ) {
    
    $this->error_html_wrap = array( $container, $wrap );
		
  }


	/**
	 * validates a field submitted to the main database
	 *
	 * receives a validation pair and processes it, adding any error to the
	 * validation status array
	 *
	 * @param string $value        the submitted value of the field
	 * @param string $name         the name of the field
	 * @param string $validation   validation method to use: can be NULL (or absent),
	 *                             'no', 'yes', 'email', 'other' (for regex or match
	 *                             another field value)
	 * @param string $form_element the form element type of the field
	 * @return NULL
	 */
	private function _validate_field( $value, $name, $validation = NULL, $form_element = false ) {

    $error_type = false;
    
    $field = (object) compact('value','name','validation','form_element','error_type');
    
    /*
     * this filter sends the $field object through a filter to allow a custom 
     * validation to be inserted
     * 
     * if a custom validation is implemented, the $field->error_type must be set 
     * to a validation method key string so the built-in validation won't be 
     * applied. This key string is an arbitrary unique key, so if can be anything 
     * except a string that is already defined. If the field passes validation,
     * $field->validation can be set to false to avoid further validation of the
     * field.
     * 
     */
    if (has_filter(Participants_Db::$css_prefix . 'before_validate_field')) {
			apply_filters(Participants_Db::$css_prefix . 'before_validate_field', $field );
		}
		
		/*
		 * set the validation to FALSE if it is not defined or == 'no'
		 */
		if (empty($field->validation) || $field->validation === NULL || $field->validation == 'no') $field->validation = FALSE;
    
    if (WP_DEBUG) { error_log(__METHOD__.'
  field: '.$name.'
  element: '.$field->form_element . '
  value: '.(is_array($field->value)? print_r($field->value,1):$field->value).'
  validation: '.(is_bool($field->validation)? ($field->validation ? 'true' : 'false') : $field->validation).'
  submitted? '.($this->not_submitted($name)?'no' : 'yes').'
  empty? '.($this->is_empty($field->value)?'yes':'no') . '
  error type: ' . $field->error_type); }

    /*
     * first we check if the field needs to be validated at all. Fields not present
     * in the form are excluded as well as any field with no validation method
     * defined, or a validation method of 'no'.
     */
		if ( $field->validation === FALSE || $this->not_submitted($name) ) return;
		/*
		 * if the validation method is 'yes' and the field has not already been
		 * validated (error_type false) we test the submitted field for empty using
		 * a defined method that allows 0, but no whitespace characters.
		 */
		elseif ( $field->validation == 'yes' and $field->error_type === false ) {
			
			if ( $field->form_element === false and $this->is_empty($field->value)	) {
				
				$field->error_type = 'empty';
				
			} else {
				// we can validate each form element differently here
				switch ($field->form_element) {
					case 'link':
						if ($this->is_empty($field->value[0])) {
							$field->error_type = 'empty';
						}
						break;
					default:
						if ($this->is_empty($field->value)) {
							$field->error_type = 'empty';
						}
				}
			}
    
    /*
		 * here we process the remaining validation methods set for the field if we
		 * have not validated the field yet
		 */
    } elseif ($field->error_type === false) {

      $regex = false;
      $test_value = false;
      switch (true) {
  
        case ( $field->validation == 'email' ) :
  
          $regex = '#^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$#i';
          break;
  
        case ( $this->_is_regex( $field->validation ) ) :
  
          $regex = $field->validation;
          break;
        
        /*
         * if it's not a regex, test to see if it's a valid field name for a match test
         */
        case ( isset( $this->post_array[$field->validation] ) ) :
          
          $test_value = $this->post_array[$field->validation];
          break;
        
        default:
  
      }
  
      if ( false !== $test_value && $field->value !== $test_value ) {
        $field->error_type = 'nonmatching';
      } elseif ( false !== $regex && preg_match( $regex, $field->value ) == 0 ) {
        $field->error_type = 'invalid';
      } 
      
    }
    
    if ( $field->error_type ) $this->_add_error( $name, $field->error_type );

	}

	/**
	 * sets the error status for a field
	 *
	 * @param string $field the name of the field
	 * @param string $error the error status of the field
	 */
	private function _add_error( $field, $error ) {

		$this->errors[ $field ] = $error;

	}
		
  /**
   * get an array of field errors
   *
   * @return array fieldname=>error
   *
   */
  public function get_error_fields() {
    
    return $this->errors;
		
  }
  
	/*************************
	 * UTILITIES             *
	 *************************/
	
	/**
	 * test a submitted field as empty
	 *
	 * we test for a submission that has only invalid characters or nothing. If we
	 * come here with an array, we test each element and return true if one of them
	 * tests true
	 *
	 * @param mixed $input the value of the submitted field
	 * 
	 * @return bool true if empty
	 */
	public static function is_empty($string)
	{
		if (is_array($string)) return self::_is_empty_array($string);
		return $string == '' or 0 !== preg_match('/^(\W+|\s+)$/', $string);
	}
	
	/**
	 * tests each element of an array for empty
	 *
	 * @param array $array the array to test
	 * @return bool true if any element tests true
	 */
	private function _is_empty_array($array)
	{
		foreach ($array as $element) {
			if (!self::is_empty($element)) return false;
		}
		return true;
	}

	// tests a string for a regex pattern by looking for a delimiter
	// not the most robust solution, but will do for most situations
  private function _is_regex( $string ) {

		if ( is_array( $string ) ) return false;

		return (
						substr( $string, 0, 1 ) == substr( $string, -1, 1 )
						||
						substr( $string, 0, 1 ) == substr( $string, -2, 1 )
						)
						? true : false;

	}

	/**
	 * makes a display title out of a slug string
	 */
	private function _make_title( $string ) {

		return ucwords( str_replace( array( '_','-' ), ' ', $string ) );

	}
  
  /**
   * test a field for presence in the POST array
   * 
   * @param string $fieldname the name of the field to test
   * @return bool
   */
  public function not_submitted($fieldname) {
    
    return @$_POST[$fieldname] === NULL;
  }
  /**
   * encodes or decodes a string using a simple XOR algorithm
   * 
   * @param string $string the tring to be encoded/decoded
   * @param string $key the key to use
   * @return string
   */
  public function xcrypt($string,$key){
    
    for( $i = 0; $i < strlen($string); $i++ ) {
      $pos = $i % strlen($key);
      $replace = ord($string[$i]) ^ ord($key[$pos]);
      $string[$i] = chr($replace);
    }
    
    return $string;
  }

}