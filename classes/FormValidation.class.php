<?php
/**
 * Form Validation Class
 *
 * tracks form submission validation and provides user feedback
 */
class FormValidation {

	// this array collects validation errors for each field
	private $errors;

	private $invalid_message;
	private $empty_message;
	private $error_style;

	/**
	 * instantiates the form validation object
	 * this is meant to be instantiated once per form submission
	 * called with the plugin options array
	 *
	 * @param array $options
	 *                empty_field_message
	 *                invalid_field_message
	 *                field_error_style
	 */
	public function __construct() {


		// this needs to be changed to eliminate dependency
		$options = get_option( Participants_Db::$participants_db_options );

		// clear the array
		$this->errors = array();

		$this->invalid_message = $options['invalid_field_message'];
		$this->empty_message = $options['empty_field_message'];
		$this->error_style = $options['field_error_style'];

	}

	/**
	 * validates a field submitted to the main database
	 *
	 * receives a validation pair and preocesses it, adding any error to the
	 * validation status array
	 *
	 * @param string $value       the submitted value of the field
	 * @param string $column_atts the column atributes object
	 *                            validation key can be NULL, 'yes', 'email', regex
	 */
	public function validate( $value, $column_atts ) {

		if ( isset( $column_atts->validation ) ) :

			$this->_validate_field( $value, $column_atts->name, $column_atts->validation );

		endif;

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
	 * prepares the error messages and CSS for a general form submission
	 *
	 * @return array indexed array of error messages
	 */
	public function get_validation_errors() {

		// check for errors
		if ( ! $this->errors_exist() ) return '';

		$output = '';

		$error_CSS = array();
		$error_messages = array();

		foreach ( $this->errors as $field => $error ) :

			$error_CSS[] = '#'.$field.' input[type="text"], #'.$field.' textarea';

			switch ( $error ) {

				case 'empty':

					$error_messages[] = sprintf( $this->empty_message, $this->_make_title( $field ) );
					break;

				case 'invalid':

					$error_messages[] = sprintf( $this->invalid_message, $this->_make_title( $field ) );
					break;

				default:

			}

    endforeach;

    return $error_messages;

	}

	/**
	 * prepares the error messages and CSS for a main database submission
	 *
	 */
	public function get_error_html() {

		// check for errors
		if ( ! $this->errors_exist() ) return '';

		$output = '';

		$error_CSS = array();
		$error_messages = array();

		foreach ( $this->errors as $field => $error ) :

			$field_atts = Participants_Db::get_field_atts( $field );

			switch ( $field_atts->form_element ) {

				case 'textarea':
				case 'text-field':
					$element = 'textarea';
					break;

				case 'text':
				case 'text-line':
					$element = 'input';
					break;

				default:
					$element = false;

			}

			$error_CSS[] = '#'.$field.' '.$element;

			switch ( $error ) {

				case 'empty':

					$error_messages[] = sprintf( $this->empty_message, $field_atts->title );
					break;

				case 'invalid':

					$error_messages[] = sprintf( $this->invalid_message, $field_atts->title );
					break;

				default:

			}

		endforeach;// $this->errors

		return $this->_error_html( $error_CSS, $error_messages );

	}

	/**
	 * returns the error messages and CSS HTML
	 *
	 *
	 */
	private function _error_html( $error_CSS, $error_messages ) {

		$output = '<style type="text/css">'.implode( ', ',$error_CSS ).'{ '.$this->error_style.' }</style>';

		$output .= '<div class="error"><p>'.implode( '</p><p>', $error_messages ).'</p></div>';

		return $output;

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

		if ( NULL === $validation || 'no' == strtolower( $validation ) ) return;

		if ( 'yes' == strtolower( $validation ) || self::_is_regex( $validation ) ) {

			if ( empty( $value ) ) $this->_add_error( $name, 'empty' );

		}

		if ( ! empty( $value ) ) {

			$regex = false;

			if ( 'email'== strtolower( $validation ) ) {

				$regex = '#^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$#i';

			} elseif ( self::_is_regex( $validation ) ) {

				$regex = $validation;

			}

			if ( false !== $regex && preg_match( $regex, $value ) == 0 )
				$this->_add_error( $name, 'invalid' );

		}

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