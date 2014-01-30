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
 * @version    Release: 1.5.4.1
 * @link       http://wordpress.org/extend/plugins/participants-database/
 */

class PDb_FormValidation extends xnau_FormValidation {
  

  /*
   * instantiates the form validation object
   * this is meant to be instantiated once per form submission
   *
   */

  public function __construct()
  {

    parent::__construct();
    /*
     * get our error messages from the plugin options
     * 
     */
    foreach (array('invalid', 'empty', 'nonmatching', 'duplicate', 'captcha', 'identifier') as $error_type) {
      $this->error_messages[$error_type] = Participants_Db::$plugin_options[$error_type . '_field_message'];
    }
    /*
     * this filter provides an opportunity to add or modify validation error messages
     * 
     * for example, if there is a custom validation that generates an error type of 
     * "custom" an error message with a key of "custom" will be shown if it fails.
     */
    $this->error_messages = Participants_Db::set_filter('validation_error_messages', $this->error_messages);

    $this->error_style = Participants_Db::$plugin_options['field_error_style'];
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
     * this filter sends the $field object through a filter to allow a custom 
     * validation to be inserted
     * 
     * if a custom validation is implemented, the $field->error_type must be set 
     * to a validation method key string. If the key string is a defined validation 
     * method, that method will be applied. If $field->validation is set to false 
     * by the filter callback, no further processing will be applied.
     * 
     */
    Participants_Db::set_filter('before_validate_field', $field);
    
    /*
     * if there is no validation method defined, exit here
     */
    if (empty($field->validation) || $field->validation === NULL || $field->validation == 'no' || $field->validation === FALSE) {
      return;
    }
    
    
    /*
     * if the validation method is set and the field has not already been
     * validated (error_type == false) we test the submitted field for empty using
     * a defined method that allows 0, but no whitespace characters.
     * 
     * a field that has any validation method and is empty will validate as empty first
     */
    if ($field->error_type === false) {
      // we can validate each form element differently here if needed
      switch ($field->form_element) {
        case 'file-upload':
        case 'image-upload':
          
          /*
           * only "required" validation is allowed on this. Restricting file types 
           * is done in the "values" field or in the settings
           */
          $field->validation = 'yes';
          if ($this->is_empty($field->value)) {
            $field->error_type = 'empty';
          }
          break;
          
        case 'link':
          // a "link" field only needs the first element to be filled in
          if ($this->is_empty($field->value[0])) {
            $field->error_type = 'empty';
          }
          break;
        default:
          if ($this->is_empty($field->value)) {
            $field->error_type = 'empty';
          }
      }
      // if the field validation is 'yes' we're done
      if ($field->error_type === false && $field->validation == 'yes') {
        $field->error_type = 'valid';
      }
    }
    /*
     * if the field has still not been validated, we process it with the remaining validation methods
     */
    if ($field->error_type === false) {

      $regex = '';
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

        case ( 'captcha' == strtolower($field->validation) ) :

          $field->value = isset($field->value[1]) ? $field->value[1] : '';

          // grab the value and the validation key
          list($info, $value) = (isset($this->post_array[$field->name][1]) ? $this->post_array[$field->name] : array($this->post_array[$field->name][0], $field->value));
          $info = json_decode(urldecode($info));

          /* 
           * if we don't have a regex, assume it's a plugin CAPTCHA and decode it's validation regex
           */
          if (empty($regex))
            $regex = $this->xcrypt($info->nonce, PDb_CAPTCHA::get_key());
          
          //error_log(__METHOD__.' $info:'.print_r($info,1).' $field->value:'.$field->value.' regex:'.$regex);

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

        default:
      }

      if ($test_value !== false) {
        
        if ($field->value != $test_value) $field->error_type = 'nonmatching';
        
      } elseif ($regex !== false) {
        
        $test_result = preg_match($regex, $field->value);
        
        if ($test_result === 0) {
        $field->error_type = $field->validation == 'captcha' ? 'captcha' : 'invalid';
        } elseif ($test_result === false) {
          error_log(__METHOD__.' captcha regex error with regex: "' . $regex . '"');
        }
        
      }
    }

    if ($field->error_type) {
      if ($field->error_type != 'valid') $this->_add_error($name, $field->error_type, false);
      $valid = $field->error_type;
    } else {
      $valid = 'valid';
    }
    /*
     * the result of a captcha validation are stored in a session variable
     */
    if (strtolower($validation) === 'captcha' || $field->form_element == 'captcha')
      Participants_Db::$session->set('captcha_result', $valid);

    if (false) {
      error_log(__METHOD__ . '
  field: ' . $name . '
  element: ' . $field->form_element . '
  value: ' . (is_array($field->value) ? print_r($field->value, 1) : $field->value) . '
  validation: ' . (is_bool($field->validation) ? ($field->validation ? 'true' : 'false') : $field->validation) . '
  submitted? ' . ($this->not_submitted($name) ? 'no' : 'yes') . '
  empty? ' . ($this->is_empty($field->value) ? 'yes' : 'no') . '
  error type: ' . $field->error_type);
    }
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

      $field_atts = Participants_Db::get_field_atts($field);

      switch ($field_atts->form_element) {

        case 'rich-text':
        case 'text-area':
        case 'textarea':
          $element = 'textarea';
          break;

        case 'link':
        case 'captcha':
          $field_atts->name .= '[]';
        case 'text':
        case 'text-line':
        case 'date':
          $element = 'input';
          break;

        case 'image-upload':
        case 'file-upload':
          $element = 'input';
          break;

        default:
          $element = false;
      }

      $this->error_CSS[] = '[class*="'. Participants_Db::$prefix . '"] [name="' . $field_atts->name . '"]';

      if (isset($this->error_messages[$error])) {
        $error_messages[] = $error == 'nonmatching' ? sprintf($this->error_messages[$error], $field_atts->title, Participants_Db::column_title($field_atts->validation)) : sprintf($this->error_messages[$error], $field_atts->title);
        $this->error_class = Participants_Db::$prefix . 'error';
      } else {
        $error_messages[] = $error;
        $this->error_class = empty($field) ? Participants_Db::$prefix . 'message' : Participants_Db::$prefix . 'error';
      }

    endforeach; // $this->errors 

    return $error_messages;
  }
  /**
   * encodes or decodes a string using a simple XOR algorithm
   * 
   * @param string $string the tring to be encoded/decoded
   * @param string $key the key to use
   * @return string
   */
  public static function xcrypt($string, $key)
  {

    for ($i = 0; $i < strlen($string); $i++) {
      $pos = $i % strlen($key);
      $replace = ord($string[$i]) ^ ord($key[$pos]);
      $string[$i] = chr($replace);
    }

    return $string;
  }
}

?>
