<?php
/**
 * PDb subclass for printing and managing form elements
 * 
 * @category   
 * @package    WordPress
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2013 xnau webdesign
 * @license    GPL2
 * @version    Release: 1.5
 * @link       http://wordpress.org/extend/plugins/participants-database/
 *
 */
class PDb_FormElement extends FormElement {
  
  /**
   * instantiates a FormElement object
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
     * external func is expected to fill the output property
     */
    Participants_Db::set_filter('form_element_build_' . $this->type, $this);

    if (empty($this->output)) {
      parent::build_element();
    }
  }
  
  /**
   * builds the HTML string for display
   *
   * @static
   */
  protected static function _HTML( $parameters ) {

    $Element = new PDb_FormElement( $parameters );
    
    return $Element->_output();
    
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
    
    //error_log(__METHOD__.' field:'.print_r($field,1));
    
    $return = '';
    
    if (has_filter(Participants_Db::$prefix . 'before_display_field'))
            $return = Participants_Db::set_filter('before_display_field', $field->value, $field->form_element);
    
    if (empty($return)) {

      switch ($field->form_element) :

        case 'image-upload' :

          $image = new PDb_Image(array(
              'filename' => $field->value,
              'link' => (isset($field->link) ? $field->link : ''),
              'mode' => 'both',
              'module' => $field->module,
              ));

          if ($html and (!is_admin() or (defined('DOING_AJAX') and DOING_AJAX))) {
            if (isset($field->module) and in_array($field->module, array('single','list'))) {
              $image->display_mode = 'image';
            } elseif (isset($field->module) and $field->module == 'signup') {
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
            $field->link =  Image_Handler::concatenate_directory_path(get_bloginfo('url'), Participants_Db::$plugin_options['image_upload_location']) . $field->value;
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
          
//          if (is_admin()) {
//            $format = get_option('date_format', 'r');
//            if (Participants_Db::$plugin_options['show_time'] == '1' and $field->form_element == 'timestamp') {
//              $format .= ' ' . get_option('time_format');
//            }
//          } else {
//            $format = Participants_Db::$date_format;
//          }
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
        
        $multivalues = Participants_Db::unserialize_array($field->value);
        if ( is_array($multivalues) and empty( $multivalues['other'] ) ) unset($multivalues['other']);

        $return = implode(', ', (array) $multivalues);
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
        
        $return = sprintf('<span class="textarea richtext">%s</span>',(Participants_Db::$plugin_options['enable_wpautop'] ? wpautop($field->value) : $field->value ) );
        break;
      default :

        $return = $field->value;

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
  

    // clean up the provided link string
    $URI = str_replace('mailto:', '', strtolower(trim(strip_tags($field->value))));
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
      if (is_admin())
        return esc_html($field->value);

      if (Participants_Db::$plugin_options['email_protect'] && ! Participants_Db::$sending_email && ! is_admin()) {

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