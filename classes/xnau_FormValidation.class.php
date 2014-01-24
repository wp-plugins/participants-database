<?php

/**
 * Form Validation Class
 *
 * tracks form submission validation and provides user feedback
 * 
 * Requires PHP Version 5.2 or greater
 * 
 * @category   
 * @package    WordPress
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2012 xnau webdesign
 * @license    GPL2
 * @version    Release: 1.5
 * @link       http://wordpress.org/extend/plugins/participants-database/
 */
class xnau_FormValidation {

  // this array collects validation errors for each field
  var $errors;
  // holds the error messages
  var $error_messages = array();
  // holds the CSS for an error indication as defined in the options
  var $error_style;
  var $error_CSS;
  var $error_html_wrap;
  // holds the class name we give the container: error or message
  var $error_class;
  // holdes an array of all the submitted values
  var $post_array;

  /*
   * instantiates the form validation object
   * this is meant to be instantiated once per form submission
   *
   */

  public function __construct()
  {

    $this->post_array = $_POST;

    // clear the array
    $this->errors = array();
    
    // set the error messages
    $this->error_messages = array('invalid', 'empty', 'nonmatching', 'duplicate', 'captcha', 'identifier');

    $this->error_style = 'border: 1px solid red';

    // set the default error wrap HTML for the validation error feedback display
    $this->error_html_wrap = array('<div class="%s">%s</div>', '<p>%s</p>');
  }

  /**
   * validates a field submitted to the main database
   *
   * receives a validation pair and processes it, adding any error to the
   * validation status array
   *
   * @param string $value       the submitted value of the field
   * @param object $column_atts the column atributes object
   *                            validation key can be NULL, 'yes', 'email-regex', a field name, regex
   * @param array  $post        the post array with all submitted values, defaults 
   *                            to $this->post_array as instantiated
   */
  public function validate($value, $column_atts, $post = false)
  {

    $this->_validate_field($value, $column_atts->name, $column_atts->validation, $column_atts->form_element);

    if ($post)
      $this->post_array = $post;
  }

  /**
   * validates a field from a general form submission
   *
   * @param string $value       the submitted value of the field
   * @param string $name        the name of the field
   * @param string $validation  the validation key to use in validating the
   *                            submission. Could be 'yes', 'email-regex', a field name or a regex pattern
   */
  public function validate_field($value, $name, $validation = NULL)
  {

    $this->_validate_field($value, $name, $validation);
  }
  
  /**
   * checks for an error on a given field
   * 
   * @param string $name of the field to check
   * @return bool true if an error has already been set on this field
   */
  public function has_error($name) {
    return isset($this->errors[$name]) && !empty($this->errors[$name]);
  }

  /**
   * gives the error status of the form submission
   */
  public function errors_exist()
  {

    return empty($this->errors) ? false : true;
  }

  /**
   * prepares the error messages and CSS for a main database submission
   *
   * @return array indexed array of error messages
   */
  public function get_validation_errors()
  {

    // check for errors
    if (!$this->errors_exist())
      return array();

    $output = '';
    $error_messages = array();
    $this->error_CSS = array();

    foreach ($this->errors as $field => $error) :

      // set a default element type
      $element = 'input';

      if ($element)
        $this->error_CSS[] = $element . '[name="' . $field_atts->name . '"]';

      if (isset($this->error_messages[$error])) {
        $error_messages[] = sprintf($this->error_messages[$error], $field);
        $this->error_class = 'error';
      } else {
        $error_messages[] = $error;
        $this->error_class = empty($field) ? 'message' : 'error';
      }

    endforeach; // $this->errors 

    return $error_messages;
  }

  /**
   * adds an arbitrary error to the object
   * 
   * @param string $name field name
   * @param string $message message handle or liter message string
   * @param bool $overwrite if true, overwrites an existing error on the same field
   */
  public function add_error($name, $message, $overwrite = true)
  {

    $this->_add_error($name, $message, $overwrite);
  }

  /**
   * returns the error messages and CSS HTML
   *
   *
   */
  protected function _error_html($error_messages)
  {

    if (empty($error_messages))
      return '';

    $output = $this->get_error_CSS();

    $messages = '';

    foreach ($error_messages as $message) {

      $messages .= sprintf($this->error_html_wrap[1], $message);
    }

    $output .= sprintf($this->error_html_wrap[0], $this->error_class, $messages);

    return $output;
  }

  /**
   * returns the error messages HTML
   *
   */
  public function get_error_html()
  {

    return $this->_error_html($this->get_validation_errors());
  }

  public function get_error_CSS()
  {

    if (!empty($this->error_CSS))
      return '<style type="text/css">' . implode(",\r", $this->error_CSS) . '{ ' . $this->error_style . ' }</style>';
    else
      return '';
  }

  public function get_error_class()
  {

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
  public function set_error_html($container, $wrap)
  {

    $this->error_html_wrap = array($container, $wrap);
  }

  /**
   * validates a single field submitted to the main database
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
  protected function _validate_field($value, $name, $validation = NULL, $form_element = false)
  {

    $error_type = false;

    $field = (object) compact('value', 'name', 'validation', 'form_element', 'error_type');
    
    /*
     * if there is no validation method defined, exit here
     */
    if (empty($field->validation) || $field->validation === NULL || $field->validation == 'no' || $field->validation === FALSE) {
      return;
    }
    
    
    /*
     * if the validation method is 'yes' and the field has not already been
     * validated (error_type false) we test the submitted field for empty using
     * a defined method that allows 0, but no whitespace characters.
     */ 
    elseif ($field->validation == 'yes') {

      if ($this->is_empty($field->value)) {
        $field->error_type = 'empty';
      }

      /*
       * here we process the remaining validation methods set for the field if we
       * have not validated the field yet
       */
    } elseif ($field->error_type === false) {

      $regex = false;
      $test_value = false;
      switch (true) {

        /*
         * the validation method key for an email address was formerly 'email' This 
         * has been changed to 'email-regex' but will still come in as 'email' from 
         * legacy databases. We test for that by looking for a field named 'email' 
         * in the incoming values.
         */
        case ( $field->validation == 'email-regex' || ($field->validation ==  'email' && $field->name ==  'email') ) :

          $regex = '#^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$#i';
          break;

        case ( $this->_is_regex($field->validation) ) :

          $regex = $field->validation;
          break;

        /*
         * if it's not a regex, test to see if it's a valid field name for a match test
         */
        case ( isset($this->post_array[strtolower($field->validation)]) ) :

          $test_value = $this->post_array[strtolower($field->validation)];
          break;
        
        case (is_bool($field->validation)) :
          $regex = $field->validation ? '#.*#' : '#\Zx\A#';
          break;

        default:
          // passes validation because the validation method was not properly defined
          $regex = '#.*#';
      }

      if (false !== $test_value && $field->value !== $test_value) {
        $field->error_type = 'nonmatching';
      } elseif (false !== $regex && preg_match($regex, $field->value) == 0) {
        $field->error_type = $validation == 'captcha' ? 'captcha' : 'invalid';
      }
    }

    if ($field->error_type) {
      $this->_add_error($name, $field->error_type);
      $valid = $field->error_type;
    } else {
      $valid = 'valid';
    }
  }

  /**
   * sets the error status for a field
   *
   * @param string $field the name of the field
   * @param string $error the error status of the field
   * @param bool $overwrite if true, overwrites an existing error on the same field
   */
  protected function _add_error($field, $error, $overwrite = false)
  {

    if ($overwrite === true || !isset($this->errors[$field]) || empty($this->errors[$field])) {
      $this->errors[$field] = $error;
    }
  }

  /**
   * get an array of field errors
   *
   * @return array fieldname=>error
   *
   */
  public function get_error_fields()
  {

    return $this->errors;
  }

  /*   * ***********************
   * UTILITIES             *
   * *********************** */

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
    if (is_array($string)) {
      //return self::_is_empty_array($string);
      $string = implode('', $string);
  }
    return $string === '';
  }

  /*
   * tests a string for a regex pattern
   * 
   * since this class uses the regex in a "preg_match" func, we first make sure 
   * it's a string then test the function and look for a boolean false, which is 
   * a strong indicator the regex is invalid
   * 
   * @param string $string the string to test
   * @return bool
   */
  protected function _is_regex($string)
  {

    if (!is_string($string))
      return false;

    return @preg_match($string,'test string') !== false;
  }

  /**
   * makes a display title out of a slug string
   * 
   * @param string
   * @return string the "title-ized" input string
   */
  protected function _make_title($string)
  {

    return ucwords(str_replace(array('_', '-'), ' ', $string));
  }

  /**
   * test a field for presence in the POST array
   * 
   * @param string $fieldname the name of the field to test
   * @return bool
   */
  public function not_submitted($fieldname)
  {

    return @$_POST[$fieldname] === NULL;
  }


}