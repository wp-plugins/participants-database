<?php
/**
 * PDb subclass for printing and managing form elements
 * 
 * @category   
 * @package    WordPress
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2013 xnau webdesign
 * @license    GPL2
 * @version    Release: 1.5.4
 * @link       http://wordpress.org/extend/plugins/participants-database/
 *
 */
class PDb_FormElement extends xnau_FormElement {
  
  /**
   * instantiates a xnau_FormElement object
	 * 
   *
   * @param array $parameters carries the parameters to build a form element
   *                    type         string sets the type of element to print
   *                    value        string the current value of the element
   *                    name         string the name attribute of the element
   *                    options      mixed  an optional array of values for checkboxes, selects, etc. Can also
   *                                        be serialized array. A special element in this array has the key 
   *                                        "null_select" which if bool false prevents the selected null case of 
   *                                        dropdown elements from being added. If it has another value, the null 
   *                                        case (which has a blank label) will hold this value and be selected 
   *                                        if no value property is provided to the instance
   *                    attributes   array  an optional array of name=>value set of HTML attributes to include
   *                                        (can include a class attribute)
   *                    class        string a class name for the element; more than one class name must be
   *                                        space-separated string
   *                    indent       int    starting indent value
   *                    size         int    the size of the field
   *                    container_id string CSS id for the element containter (if any)
   *
   * @return NULL
   */
  public function __construct( $parameters ) {
    $this->prefix = Participants_Db::$prefix;
    parent::__construct($parameters);
  }
  
  /**
   * builds the HTML string for display
   *
   * @var array parameters as per __construct()
   * @static
   */
  public static function _HTML( $parameters ) {

    $Element = new PDb_FormElement( $parameters );
    
    return $Element->_output();
    
  }
  /**********************
	 * PUBLIC METHODS
	 */
  
  /** 
   * prints a form element
   *
   * @param array $parameters (same as __construct() )
   * @static
   */
  public static function print_element( $parameters ) {

    $Element = new PDb_FormElement( $parameters );
    
    echo $Element->_output();
    
  }

  /** 
   * returns a form element
   *
   * @param array $parameters (same as __construct() )
   * @static
   */
  public static function get_element( $parameters ) {

    $Element = new PDb_FormElement( $parameters );
    
    return $Element->_output();

  }
  
  /**
   * outputs a set of hidden inputs
   *
   * @param array $fields name=>value pairs for each hidden input tag
   */
  public static function print_hidden_fields( $fields, $print = true ) {
    
    $output = array();
    
    $atts = array( 'type' => 'hidden');
    
    foreach ( $fields as $k => $v ) {
      
      $atts['name'] = $k;
      $atts['value'] = $v;
      
      $output[] = self::_HTML( $atts );
      
    }
    
    if ( $print ) echo implode(PHP_EOL, $output);
		else return implode(PHP_EOL, $output);
    
  }
  
  /**
   * builds the form element
   * 
   * allows an external func to build the element. If that doens't happen, uses 
   * the parent method to build it
   * 
   * @return null
   */
  function build_element() {
    /*
     * if the filter is defined, we pass the object to an external function with 
     * a filter handle that includes the name of the custom form element. The 
     * filter callback is expected to fill the output property
     */
    Participants_Db::set_filter('form_element_build_' . $this->type, $this);

    if (empty($this->output)) {
      $this->call_element_method();
    }
  }
  
  /**
   * returns an element value formatted for display or storage
   * 
   * this supplants the function Participants_Db::prep_field_for_display
   * 
   * @param object $field a Field_Item object
   * @param bool   $html  if true, retuns the value wrapped in HTML, false returns 
   *                      the formatted value alone
   * @return string the object's current value, formatted
   */
  public static function get_field_value_display($field, $html = true) {
    
    $return = '';
    
    if (has_filter(Participants_Db::$prefix . 'before_display_field'))
            $return = Participants_Db::set_filter('before_display_field', $return, $field->value, $field->form_element);
    
    if (empty($return)) {

      switch ($field->form_element) :

        case 'image-upload' :

          $image = new PDb_Image(array(
              'filename' => $field->value,
              'link' => (isset($field->link) ? $field->link : ''),
              'mode' => 'both',
              'module' => $field->module,
              ));

          if ($html) {
            if (isset($field->module) and in_array($field->module, array('single','list'))) {
              $image->display_mode = 'image';
            } elseif (isset($field->module) and in_array($field->module, array('signup','admin-edit'))) {
              $image->display_mode = $image->image_defined ? 'both' : 'none';
              $image->link = false;
            }
            $image->set_image_wrap();
            $return = $image->get_image_html();
          } elseif ($image->file_exists) {
            $return = $image->get_image_file();
          } else {
            $return = $field->value;
          }
          
          

        break;
        
      case 'file-upload' :

        if ($html and ! empty($field->value)) {

          if ($field->module == 'signup') {
            $field->link = false;
            $return = $field->value;
          } else {
            $field->link =  xnau_Image_Handler::concatenate_directory_path(get_bloginfo('url'), Participants_Db::$plugin_options['image_upload_location']) . $field->value;
            $return = self::make_link($field);
          }
          break;
        } else {

          $return = $field->value;
          break;
        }

      case 'date' :
      case 'timestamp' :
        
        $return = '';
        if (self::is_empty($field->value) === false) {
          $date = Participants_Db::parse_date($field->value, $field);
          $format = Participants_Db::$date_format;
          if (Participants_Db::$plugin_options['show_time'] == '1' and $field->form_element == 'timestamp') {
            $format .= ' ' . get_option('time_format');
          }
          $return = date_i18n($format, $date);
        } else {
          $return = '';
        }
        
        break;

      case 'multi-checkbox' :
      case 'multi-select-other' :
        
        /*
         * these elements are stored as serialized arrays of values, the data is displayed 
         * a comma-separated string of the values, using the value titles if defined
         */
        $multivalues = Participants_Db::unserialize_array($field->value);
        // remove empty elements and convert to string for display
        $multivalues = array_filter((array)$multivalues, array( __CLASS__, 'is_displayable'));
        $titles = array();
        foreach($multivalues as $value) {
          $titles[] = self::get_value_title($value, $field->name);
        }
        $return = implode(', ', $titles);
        break;

      case 'link' :

        $linkdata = Participants_Db::unserialize_array($field->value);

        if (!is_array($linkdata)) {

          $return = '';
          break;
        }

        if (empty($linkdata[1]))
          $linkdata[1] = str_replace('http://', '', $linkdata[0]);

        if ($html)
          $return = vsprintf(( empty($linkdata[0]) ? '%1$s%2$s' : '<a href="%1$s">%2$s</a>'), $linkdata);
        else
          $return = $linkdata[0];
        break;

      case 'text-line' :

        if ($html) {

          $field->value = self::get_value_title($field->value, $field->name);
          $return = self::make_link($field);
          break;
        } else {

          $return = $field->value;
          break;
        }
        
      case 'text-area':
      case 'textarea':
        
        $return = sprintf('<span class="textarea">%s</span>',$field->value );
        break;
      case 'rich-text':
        
        $return = sprintf('<span class="textarea richtext">%s</span>', Participants_Db::process_rich_text($field->value));
        break;
      default :

        $field->value = self::get_value_title($field->value, $field->name);
        $return = self::make_link($field);

      endswitch;
    }

    return $return;
  
    
  }

  /************************* 
	 * ELEMENT CONSTRUCTORS
	 */
  
  /**
   * builds a rich-text editor (textarea) element
   */
  protected function _rich_text_field() {
    
    if ( !is_admin() and !Participants_Db::$plugin_options['rich_text_editor'] ) $this->_text_field();
    
    else parent::_rich_text_field();
    
  }

  /**
   * builds a file upload element
   * 
   * @param string $type the upload type: file or image
   */
  protected function _upload($type) {

    $this->_addline('<div class="' . $this->prefix . 'upload">');
   // if a file is already defined, show it
    if (!empty($this->value)) {

      $this->_addline(self::get_field_value_display($this));
    }

    // add the MAX_FILE_SIZE field
    // this is really just for guidance, not a valid safeguard; this must be checked on submission
    if (isset($this->options['max_file_size']))
      $max_size = $this->options['max_file_size'];
    else
      $max_size = ( ini_get('post_max_size') / 2 ) * 1048576; // half it to give a cushion

    $this->_addline($this->print_hidden_fields(array('MAX_FILE_SIZE' => $max_size, $this->name => $this->value)));

    if (!isset($this->attributes['readonly'])) {
    
    $this->_addline($this->_input_tag('file'));

    // add the delete checkbox if there is a file defined
    if (!empty($this->value))
      $this->_addline('<span class="file-delete" ><label><input type="checkbox" value="delete" name="' . $this->name . '-deletefile" ' . $this->_attributes () . '>' . __('delete', 'participants-database') . '</label></span>');
    
    }
    
    $this->_addline('</div>');
  }

  /*************************** 
	 * UTILITY FUNCTIONS
	 */ 
  
  /**
   * outputs a link (HTML anchor tag) in specified format if enabled by "make_links"
   * option
   * 
   * this func validates the link as being either an email addres or URI, then
   * (if enabled) builds the HTML and returns it
   * 
   * @param object $field the field object
   * @param string $linktext the clickable text (optional)
   * @param string $template the format of the link (optional)
   * @param array  $get an array of name=>value pairs to include in the get string
   *
   * @return string HTML or HTML-escaped string (if it's not a link)
   */
  public static function make_link($field, $template = false, $get = false) {
  
    // AJAX requests are not really from the admin
    $in_admin = is_admin() && (!defined('DOING_AJAX') || !DOING_AJAX);

    // clean up the provided string
    $URI = str_replace('mailto:', '', strtolower(trim(strip_tags($field->value))));
    // use the default value if present for the link text
    $linktext = empty($field->value) ? $field->default : $field->value;

    if (isset($field->link) and !empty($field->link)) {
      // if the field is a single record link or other kind of defined link field
      $URI = $field->link;
    } elseif (filter_var($URI, FILTER_VALIDATE_URL) && Participants_Db::$plugin_options['make_links']) {

      // convert the get array to a get string and add it to the URI
      if (is_array($get)) {

        $URI .= false !== strpos($URI, '?') ? '&' : '?';

        $URI .= http_build_query($get);
      }
    } elseif (filter_var($URI, FILTER_VALIDATE_EMAIL) && Participants_Db::$plugin_options['make_links']) {

      // in admin, emails are plaintext
      if ($in_admin)
        return esc_html($field->value);

      if (Participants_Db::$plugin_options['email_protect'] && ! Participants_Db::$sending_email && ! $in_admin) {

        // the email gets displayed in plaintext if javascript is disabled; a clickable link if enabled
        list( $URI, $linktext ) = explode('@', $URI, 2);
        $template = '<a class="obfuscate" rel=\'{"name":"%1$s","domain":"%2$s"}\'>%1$s AT %2$s</a>';
      } else {
        $URI = 'mailto:' . $URI;
      }
    } elseif (filter_var($URI, FILTER_VALIDATE_EMAIL) && ! Participants_Db::$plugin_options['make_links'] && Participants_Db::$plugin_options['email_protect'] && ! Participants_Db::$sending_email) {
      
      // only obfuscating, not making links
      return vsprintf('%1$s AT %2$s', explode('@', $URI, 2));
    } else {
      return $field->value; // if it is neither URL nor email address and we're not formatting it as html
    }
      
    // default template for links
    $linktemplate = $template === false ? '<a href="%1$s" >%2$s</a>' : $template;

    $linktext = empty($linktext) ? str_replace(array('http://', 'https://'), '', $URI) : $linktext;

    //construct the link
    return sprintf($linktemplate, $URI, esc_html($linktext));
  }
  
  /**
   * returns an internationalized date string from a UNIX timestamp
   * 
   * @param int $timestamp a UNIX timestamp
   * @param bool $time if true, adds the time of day to the format
   * @return string a formatted date or input string if invalid
   */
  public static function format_date($timestamp, $time = false) {
    // if it's not a timestamp, we attempt to convert it to one
    if (!Participants_Db::is_valid_timestamp($timestamp)) $timestamp = Participants_Db::parse_date($timestamp);

    if (Participants_Db::is_valid_timestamp($timestamp)) {
      
      $format = Participants_Db::$date_format;
      
      if ($time) {
        $format .= ' ' . get_option('time_format');
      }
      
      return date_i18n( $format, $timestamp );
    
    } else {
      // not a timestamp: return unchanged
      return $timestamp;
    }
  
  }
 
  /**
   * get the title that corresponds to a value from a value series
   * 
   * this func grabs the value and matches it to a title from a list of values set 
   * for a particular field
   * 
   * if there is no title defined, or if the values is stored as a simple string, 
   * the value is returned unchanged
   * 
   * @global object $wpdb
   * @param array $values
   * @param string $fieldname
   * @return array of value=>title pairs
   */
  public static function get_value_titles($values, $fieldname)
  {
    global $wpdb;
    $sql = 'SELECT f.values FROM ' . Participants_Db::$fields_table . ' AS f WHERE f.name = "%s"';
    $options_array = maybe_unserialize($wpdb->get_var($wpdb->prepare($sql, $fieldname)));
    $return = array();
    if (is_array($options_array)) {
      $i = 0;
      foreach ($options_array as $index => $option_value) {
        if (!is_string($index) or $index == 'other') {
          // we use the stored value
          $return[$option_value] = $option_value;
        } elseif ($option_value == $values[$i]) {
          // grab the option title
          $return[$option_value] = $index;
        }
        $i++;
      }
    }
    return $return;
  }
 
  /**
   * get the title that corresponds to a value from a value series
   * 
   * this func grabs the value and matches it to a title from a list of values set 
   * for a particular field
   * 
   * if there is no title defined, or if the values is stored as a simple string, 
   * the value is returned unchanged
   * 
   * @global object $wpdb
   * @param string $value
   * @param string $fieldname
   * @return string the title matching the value
   */
  public static function get_value_title($value, $fieldname)
  {
    global $wpdb;
    $sql = 'SELECT f.values FROM ' . Participants_Db::$fields_table . ' AS f WHERE f.name = "%s"';
    $options_array = maybe_unserialize($wpdb->get_var($wpdb->prepare($sql, $fieldname)));

    if (is_array($options_array)) {
      foreach ($options_array as $index => $option_value) {
        if (!is_string($index) or $index == 'other') {
          // we use the stored value
        } elseif ($option_value == $value) {
          // grab the option title
          return $index;
        }
      }
    }
    return $value;
  }
  
  /**
   * tests for a displayble value
   * 
   * this is used as a callback for a array_filter function
   * 
   * @param string|int $string the test subject
   * @return bool true if is non-empty string or integer
   */
  public static function is_displayable($string) {
    
    return is_string($string) && $string !== '';
  }
 
  /*
   * static function for assembling the types array
   */
  public static function get_types() {
     $types = array ( 
         'text-line'          => __('Text-line', 'participants-database'), 
         'text-area'          => __('Text Area', 'participants-database'), 
         'rich-text'          => __('Rich Text', 'participants-database'), 
         'checkbox'           => __('Checkbox', 'participants-database'), 
         'radio'              => __('Radio Buttons', 'participants-database'), 
         'dropdown'           => __('Dropdown List', 'participants-database'), 
         'date'               => __('Date Field', 'participants-database'), 
         'dropdown-other'     => __('Dropdown/Other', 'participants-database'), 
         'multi-checkbox'     => __('Multiselect Checkbox', 'participants-database'), 
         'select-other'       => __('Radio Buttons/Other', 'participants-database'), 
         'multi-select-other' => __('Multiselect/Other', 'participants-database'), 
         'link'               => __('Link Field', 'participants-database'), 
         'image-upload'       => __('Image Upload Field', 'participants-database'), 
         'file-upload'        => __('File Upload Field', 'participants-database'), 
         'hidden'             => __('Hidden Field', 'participants-database'), 
         'password'           => __('Password Field', 'participants-database'), 
         'captcha'            => __('CAPTCHA', 'participants-database'),
//         'timestamp'          => __('Timestamp', 'participants-database'),
         );
    /*
     * this gives access to the list of form element types for alteration before
     * it is set
     */
    return Participants_Db::set_filter('set_form_element_types', $types);
  }
  
} //class
?>