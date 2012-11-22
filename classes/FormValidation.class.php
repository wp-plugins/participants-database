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
	private $error_messages;
  
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

		// this needs to be changed to eliminate dependency
		$options = get_option( Participants_Db::$participants_db_options );

		// clear the array
		$this->errors = array();

    /*
     * get our error messages from the plugin options
     * 
     */
    foreach ( array( 'invalid','empty','nonmatching','duplicate' ) as $error_type ) {
      $this->error_messages[$error_type] = $options[$error_type.'_field_message'];
    }
		$this->error_style = $options['field_error_style'];

    // set the defualt error wrap HTML for the validation error feedback display
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
	 * @param string $column_atts the column atributes object
	 *                            validation key can be NULL, 'yes', 'email', regex
	 * @param array  $post        the post array with all submitted values
	 */
	public function validate( $value, $column_atts, $post = NULL ) {

		$this->_validate_field( $value, $column_atts->name, $column_atts->validation, $column_atts->title );

    if ( is_array( $post ) ) $this->post_array = $post;

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
					$element = 'textarea';
					break;

				case 'text':
				case 'text-line':
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
				$this->error_class = 'pdb-error';
      } else {
      	$error_messages[] = $error;
				$this->error_class = empty( $field ) ? 'pdb-message' :'pdb-error' ;
			}

		endforeach;// $this->errors 
		
		return $error_messages;

	}

	/**
	 * adds an arbitrary error to the object
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
	 * receives a validation pair and preocesses it, adding any error to the
	 * validation status array
	 *
	 * @param string $value       the submitted value of the field
	 * @param string $name        the name of the field
	 * @param string $validation  key can be NULL (or absent), 'yes', 'email', regex
	 */
	private function _validate_field( $value, $name, $validation = NULL ) {

		// error_log( __METHOD__.' validating field '.$name.' of value '.$value.' with '.$validation );

    $error_type = false;

    /*
     * set as empty any fields requiring validation
     * second condition is to allow match validation fields to be empty; they'll be tested again
     */
		if ( empty( $validation ) || NULL === $validation || 'no' == strtolower( $validation ) ) return;

		elseif ( empty( $value )  && ! isset( $this->post_array[$validation] ) ) {
    
    //error_log( __METHOD__.' test empty' );
    
      $error_type = 'empty';
    
    } else {
    
    //error_log( __METHOD__.' '.$name.' not empty; other validation:'.$validation.' '.print_r( $this->post_array,1 ) );

      /*
       * perform the specific type of validation with our field
       */
      $regex = false;
      $test_value = false;
      switch (true) {
        
        /*
         * if it's not a regex, test to see if it's a valid field name for a match test
         */
        case ( isset( $this->post_array[$validation] ) ) :
          
          $test_value = $this->post_array[$validation];
          break;
  
        case ( 'email'== strtolower( $validation ) ) :
  
          $regex = '#^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$#i';
          break;
  
        case ( self::_is_regex( $validation ) ) :
  
          $regex = $validation;
          break;
        
        default:
  
      }
  
      if ( false !== $regex && preg_match( $regex, $value ) == 0 ) {
        $error_type = 'invalid';
      } elseif ( false !== $test_value && $value !== $test_value ) {
        $error_type = 'nonmatching';
      }
      
    }
    
    if ( $error_type ) $this->_add_error( $name, $error_type );

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

}