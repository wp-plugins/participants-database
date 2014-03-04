<?php
/**
 * PHP class for printing HTML form elements
 *
 * This class abstracts the functional elements of a form into units that 
 * are easily defined and edited within the context of a PHP script. This 
 * class is especially useful in cases where the content and elements of a 
 * form are only known at runtime, and facilitates a standardized approach 
 * to displaying forms. This first version of the class is focused on the 
 * form elements themselves; in future versions, methods for organizing 
 * elements and formatting forms will be included.
 *
 * Several non-standard form elements have been implemented, fusing an 
 * interrelated set of HTML tags and javascript into a functional unit that 
 * can be output with the same simplicity as any other form tag. This set 
 * of user-experience-centered form elements can be easily expanded by 
 * extending the class.
 *
 * This class was developed for use within the WordPress environment.
 *
 * USAGE
 * The class operates as a static factory, with each element called as a 
 * static method with the minimum necessary parameters as an associative 
 * array or get-request-like string in the WordPress style. The static 
 * method instantiates the element object, which itself remains protected. 
 * See the constructor method for details.
 *
 * Requires PHP Version 5.3 or greater
 * 
 * @category   
 * @package    WordPress
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2011, 2012, 2013 xnau webdesign
 * @license    GPL2
 * @version    Release: 1.5.4.4
 * @link       http://wordpress.org/extend/plugins/participants-database/
 *
 */
/*  Copyright 2011, 2012 Roland Barker xnau webdesign  (email : webdesign@xnau.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
abstract class xnau_FormElement {
  
  /**
   * defines the type of form element for the object
   *
   * @var string 
   */
  var $type;
  
  /**
   * holds the current value of the element
   *
   * @var string
   */
  var $value;
  
  /**
   * the name attribute of the form data field
   *
   * @var string
   */
  var $name;
  
  /**
   * for elements that have set options such as checkboxes and dropdowns, this 
   * array holds the name=>value pairs
   *
   * @var array
   */
  var $options;
  
  /**
   * holds any other html element attributes in name=>value pairs
   * 
   * @var array 
   */
  var $attributes;
  
  /**
   * space-separated string of class names for the form element
   *
   * @var string
   */
  var $classes;

  /**
   * array holding the text lines of an element to be output
   *
   * @var array
   */
  var $output = array();
  
  /**
   * the size attribute of the input tag
   *
   * @var type 
   */
  var $size;

	/**
   * sets the height and width of the textarea element
   *
   * @var array
   */
  var $textarea_dims = array('rows' => 2, 'cols' => 40);
  
  /**
   * element group status
   * 
   * this pertains to elements which are part of a group of form elements sharing 
   * a common name, such as for multi-selects
   *
   * @var bool
   */
  public $group;
  
  /**
   * holds "inside wrapping tag" status
   * 
   * this is used in constructing complex elements that use wrapping tags such as 
   * optgroups
   * 
   * @var bool
   */
  var $inside = false;
  
  /**
   * @var string the linebreak character
   */
  const BR = PHP_EOL;
  
  /**
   * 
   * @var string the tab character
   */
  const TAB = "\t";
  
  /**
   * holds current indent level
   *
   * @var int
   */
  protected $indent;
  
  /**
   *
   * @var array holds the internationaliztion strings
   */
  protected $i18n;
  
  /**
   *
   * @var array of all available form element types
   */
  var $element_types;
  
  /**
   * a namespacing prefix for CSS classes and such
   */
  var $prefix = 'form-element';
  
  /**
   * 
   * @var string name of the instantiating module
   */
  var $module;
  
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
   *                    size         int    the size of the field (text type fields only)
   *                    container_id string CSS id for the element containter (if any)
   *
   * @return NULL
   */
  public function __construct( $parameters ) {
    
    $this->_set_types();
    
    $defaults = array(
                      'options'      => NULL,
                      'attributes'   => '',
                      'class'        => '',
                      'indent'       => 1,
                      'size'         => false,
                      'container_id' => false,
                      'group'        => false,
                      );
    $params = wp_parse_args( $parameters, $defaults );
    
    $this->type         = $params['type'];
    $this->form_element = $params['type'];
    $this->value        = $params['value'];
    $this->name         = $params['name'];
    $this->classes      = $params['class'];
    $this->size         = $params['size'];
    $this->container_id = $params['container_id'];
    $this->group        = $params['group'];
    $this->module       = isset($params['module']) ? $params['module'] : '';
    
    if ( NULL !== $params['options'] || ! empty( $params['options'] ) ) {
      
      // is_serialized is a utility function in WordPress
      $this->options = is_serialized( $params['options'] ) ? unserialize( $params['options'] ) : $params['options'];
      
    }
    
    $this->i18n = array(
    					'other' => _x('other', 'indicates a write-in choice', 'participants-database' ),
							'linktext' => _x( 'Link Text','indicates the text to be clicked to go to another web page', 'participants-database' )
    					);
    
    $this->attributes = $params['attributes'];
    
    // transfers the class attribute if it's set in the attributes array
    // it's possible for class names to be in either/both places, this collects them onto one place
    if ( isset( $this->attributes['class'] ) ) {
      
      $this->classes .= ' '.$this->attributes['class'];
      unset( $this->attributes['class'] );
      
    }
    
    $this->indent = $params['indent'];

    // clear the output array
    $this->output = array();
    
    $this->build_element();
  }
  
  /**
   * give the child class a chance to insert it's modifications to the build method
   */
  abstract function build_element();


  /**
   * builds the form element by calling it's method
   * 
   * @return null
   */
  protected function call_element_method() {
    
      switch ( $this->type ) :

        case 'date':
          $this->_date_field();
          break;
        
        case 'timestamp':
          $this->_timestamp_field();
          break;

        case 'text-area':
        case 'textarea':
          $this->_text_field();
          break;

        case 'rich-text':
          $this->_rich_text_field();
          break;

        case 'checkbox':
          $this->_checkbox();
          break;

        case 'radio':
          $this->_radio();
          break;

        case 'dropdown':
          $this->_dropdown();
          break;

        case 'dropdown-other':
          $this->_dropdown_other();
          break;

        case 'multi-checkbox':
          $this->_multi_checkbox();
          break;

        case 'text':
        case 'text-line':
          $this->_text_line();
          break;

        case 'password':
          $this->_password();
          break;

        case 'select-other':
          $this->_select_other();
          break;

        case 'multi-select-other':
          $this->_select_other_multi();
          break;

        case 'link':
          $this->_link_field();
          break;

        case 'drag-sort':
          $this->_drag_sort();
          break;

        case 'submit':
          $this->_submit_button();
          break;

        case 'selectbox':
          $this->_selectbox();
          break;

        case 'hidden':
          $this->_hidden();
          break;

        case 'image-upload':
          $this->_upload('image');
          break;

        case 'file':
        case 'file-upload':
          $this->_upload('file');
          break;

        case 'captcha':
          $this->_captcha();
          break;

        default:

      endswitch;
  }
  
  /**
   * builds the HTML string for display
   *
   * @static
   */
  static function _HTML( $parameters ){

  }
  
  /**********************
	 * PUBLIC METHODS
	 */
  
  /** 
   * prints a form element
   *
   * this func is calls the child class so any legacy implementations using the 
   * xnau_FormElement class alone can still work
   *
   * @param array $parameters (same as __construct() )
   * @static
   */
  public static function print_element( $parameters ) {
    PDb_FormElement::print_element($parameters);
  }

  /** 
   * returns a form element
   *
   * @param array $parameters (same as __construct() )
   * @static
   */
  public static function get_element( $parameters ) {
    PDb_FormElement::get_element($parameters);
  }
  
  /**
   * outputs a set of hidden inputs
   *
   * @param array $fields name=>value pairs for each hidden input tag
   */
  public static function print_hidden_fields( $fields, $print = true ) {
    
   PDb_FormElement::print_hidden_fields($fields, $print);
    
  }

  /**
   * returns an element value formatted for display or storage
   * 
   * @param object $field a Field_Item object
   * @param bool   $html  if true, retuns the value wrapped in HTML, false returns 
   *                      the formatted value alone
   * @return string the object's current value, formatted
   */
  public static function get_field_value_display($field, $html = true) {
    
    //error_log(__METHOD__.' field:'.print_r($field,1));

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
          $upload_dir = wp_upload_dir();
          $field->link =  get_bloginfo('url') . '/' . Participants_Db::$plugin_options['image_upload_location'] . $field->value;
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
        
        $date = strtotime($field->value);
        
        $format = get_option('date_format', 'r');
        $return = date_i18n($format, $date);
      }
      break;

    case 'multi-checkbox' :
    case 'multi-select-other' :

      $multivalues = maybe_unserialize($field->value);
      if ( is_array($multivalues) and empty( $multivalues['other'] ) ) unset($multivalues['other']);

      $return = implode(', ', (array) $multivalues);
      break;

    case 'link' :
      
      /*
       * value is indexed array: array( $url, $linktext )
       */

      if (!$linkdata = unserialize_array($field->value)) {

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

      $return = '<span class="textarea richtext">' . $field->value . '</span>';
      break;
    default :

      $return = $field->value;

    endswitch;

    return $return;
  
    
  }

  /************************* 
	 * ELEMENT CONSTRUCTORS
	 */
  
  /**
   * builds a input text element
   */
  protected function _text_line() {
    
    $this->_addline( $this->_input_tag() );
    
  }
  
  /**
   * builds a date field
   */
  protected function _date_field() {
    
    $this->add_class( 'date_field' );
    
    if (!empty($this->value)) {
      $this->value = $this->format_date( $this->value, false );
    }
    
    $this->_addline( $this->_input_tag() );
    
  }
  
  /**
   * builds a timestamp field
   */
  protected function _timestamp_field() {
    
    $this->add_class( 'timestamp_field' );
    
    // test for a timestamp
    if (is_int($this->value) or ((string) (int) $this->value === $this->value)) {
      $this->value = $this->format_date($this->value, true );
    } else {
      $this->value = $this->format_date(strtotime($this->value), true );
    }
    
    $this->_addline( $this->_input_tag() );
    
  }
  
  /**
   * builds a text-field (textarea) element
   */
  protected function _text_field() {
    
    $value = ! empty( $this->value ) ? $this->value : '';
    
    $this->_addline( '<textarea name="' . $this->name . '" rows="' . $this->textarea_dims['rows'] . '" cols="' . $this->textarea_dims['cols'] . '" ' . $this->_attributes() . ' >'.$value.'</textarea>', empty( $this->value ) ? 0 : -1 );
    
  }
  
  /**
   * builds a rich-text editor (textarea) element
   */
  protected function _rich_text_field() {
    
    $value = isset( $this->value ) ? $this->value : '';
    
    wp_editor(
            htmlspecialchars_decode($value),
            preg_replace( '#[0-9_-]#', '', $this->prefix . $this->name ),
            array(
                'media_buttons' => false,
                'textarea_name' => $this->name,
                )
            );
    
  }
  
  /**
   * builds a password text element
   */
  protected function _password() {
    
    $this->_addline( $this->_input_tag('password') );
    
  }
  
  /**
   * builds a checkbox element
   *
   * places a hidden field to supply the value when unchecked
   *
   * if there is no options array supplied, it is assumed to be a "select box",
   * which is a checkbox with no unchecked value, otherwise, there is a hidden
   * input added to supply a value to the field when the box is unchecked. The
   * first value of the array is the checked value, the second is the unchecked
   * value. If a key is provided, the key for the first options array element
   * will be the label for the checkbox. If no key is provided, no label will be
   * printed
   */
  protected function _checkbox() {
    
      // if no options are defined, then it's a selectbox
      if ( false === $this->options or ! is_array( $this->options ) ) {
        $this->_selectbox();
        return NULL;
      } else {
        $title = $this->is_assoc( $this->options ) ? key( $this->options ) : false;
        $checked_value = current($this->options);
        $unchecked_value = next($this->options);
        if ($unchecked_value === false) $unchecked_value = '';
      }

      
      if ( false !== $title ) {
        $this->_addline( '<label for="' . $this->name . '">' );
      }
      
      $this->_addline( $this->_input_tag( 'hidden', $unchecked_value ) );
      
      $this->_addline( $this->_input_tag( 'checkbox', $checked_value, 'checked' ), 1 );
      
      if ( false !== $title ) {
        $this->_addline( $title, 1 );
        $this->_addline( '</label>', -1 );
      }
      
  }
  
  /**
   * builds a radio button element
   */
  protected function _radio() {
    
    $this->_add_radio_series();
       
  }
  
  /**
   * builds a dropdown or dropdown-other element
   */
  protected function _dropdown( $other = false ) {
    
    if ( isset( $this->attributes['other'] ) ) {
      $otherlabel = $this->attributes['other'];
      unset( $this->attributes['other'] );
    } else $otherlabel = $this->i18n['other'];
    
    if (!isset($this->attributes['readonly'])) {
    
    // make a unique prefix for the js function
    $js_prefix = $this->_prep_js_string($this->name);

      if ($other) {
        $this->_addline('<div class="dropdown-other-control-group" name="' . $this->name . '" rel="' . $otherlabel . '">');
        $this->add_class('otherselect');
        $this->_addline('<select name="' . $this->name . '" ' . $this->_attributes() . ' >');
        //$this->_addline('<select id="' . $js_prefix . '_otherselect" onChange="' . $js_prefix . 'SelectOther()" name="' . $this->name . '" ' . $this->_attributes() . ' >');
    } else {
      $id = isset($this->attributes['id']) ? $this->attributes['id'] : $js_prefix . '_select';
        $this->_addline('<select id="' . $id . '" name="' . $this->name . '" ' . $this->_attributes() . ' >');
    }

    $this->indent++;
    
    /*
     * include the "nothing selected" state
     */
    $this->_set_null_select();
    
    $this->_add_option_series($other ? $otherlabel : false);
    
      $this->_addline('</select>', -1);
    
      if ( $other ) {
    
    // build the text input element
        $is_other = $this->_set_selected( $this->options, $this->value, 'selected', false ) !== '';
        $this->_addline('<input type="text" class="otherfield" name="' . $this->name . '" value="' . ( $is_other ? $this->value : '' ) . '" ' . $this->_attributes() . ' >');
        $this->_addline('</div>');
      }
    
    } else {
      // readonly display
      $this->_addline('<input type="text" class="pdb-readonly" name="' . $this->name . '" value="' . $this->value . '" ' . $this->_attributes() . ' >');
    }
  }

  /**
   * builds a dropdown-other element
   *
   * @return string
   */
  protected function _dropdown_other() {

    $this->_dropdown( true );
      
  }

  /**
   * builds a multi-checkbox
   *
   * a set of checkboxes enclosed in a div tag
   */
  protected function _multi_checkbox() {

		$this->value = (array) $this->value;

    if (!isset($this->attributes['readonly'])) {

    $this->_addline( '<div class="multicheckbox"' . ( $this->container_id ? ' id="' . $this->container_id . '"' : '' ) . '>' );
    $this->indent++;

    $this->_add_checkbox_series();

      $this->_addline('</div>', -1);
    } else {
      $this->_readonly_multi();
    }
  }
  
  /**
   * builds a select/other form element
   *
   * a set of checkboxes or radio buttons with an optional text input element activated by selecting "other"
   *
   * @param string $type can be either 'radio' or 'checkbox' (for a multi-select element)
   */
  protected function _select_other( $type = 'radio' ) {
    
    if ( $type == 'radio' ) {
      $this->value = is_array($this->value) ? current($this->value) : $this->value;
    } else {
      $this->value = (array) $this->value;
      if ( ! isset( $this->value['other'] ) ) $this->value['other'] = '';
    }
    
    /*
     * determine the label for the other field: start with the default value, then 
     * in the field definition, the finally the string if set in the template via 
     * the attributes array
     */
    $otherlabel = $this->i18n['other'];
    if ($i = array_search('other', $this->options)) {
      $otherlabel = array_search('other',$this->options);
      unset($this->options[$otherlabel]);
    }
    if ( isset( $this->attributes['other'] ) ) {
      $otherlabel = $this->attributes['other'];
      unset( $this->attributes['other'] );
    }
    
    // make a unique prefix for the function
    $js_prefix = $this->_prep_js_string($this->name)/* .'_' */;
    
    // put it in a conatiner
    $this->_addline('<div class="selectother ' . $type . '-other-control-group"' . ( $this->container_id ? ' id="' . $this->container_id . '"' : '' ) . ' name="' . $this->name . '">');
    $this->indent++;
    
    $type == 'checkbox' ? $this->_add_checkbox_series($otherlabel) : $this->_add_radio_series($otherlabel);
    
    // add the text input element
    $this->attributes['class'] =  'otherfield';
    $value = $type == 'checkbox' ? $this->value['other'] : (!in_array($this->value, $this->options) ? $this->value : '' );
    $name = $value === '' ? 'temp' : $this->name . ($type == 'checkbox' ? '[other]' : '');
    $this->_addline('<input type="text" id="' . $this->name . '_other" name="' . $name . '" value="' . $value . '" ' . $this->_attributes() . ' >');
    
    // close the container
    $this->_addline('</div>', -1);
  }
  
  /**
   * builds a multi-select/other form element
   */
  protected function _select_other_multi()
  {
    if (!isset($this->attributes['readonly'])) {
      $this->_select_other('checkbox');
    } else {
      $this->_readonly_multi();
    }
  }
	
	/**
	 * builds a link form element
	 *
	 * stores an array: first element is the URL the optional second the link text
	 */
	protected function _link_field() {
    
    // this element is stored as an array
    $this->group = true;
		
    $link_placeholder = '(URL)';
    $linktext_placeholder = $this->i18n['linktext'];
		
    $parts = is_serialized( $this->value ) ? unserialize( $this->value ) : (array) $this->value;

    // if the value contains only a URL, the linktext and URL are made the same
    // if the value is not a URL, only the linked text is used
    if ( count( $parts ) < 2 ) {
      $parts[1] = $parts[0];
      if ( ! filter_var( $parts[0], FILTER_VALIDATE_URL ) ) $parts[0] = '';
    }

    list( $url, $title ) = $parts;

    $this->_addline( '<div class="link-element">' );
		
		//$url = empty( $url ) ? $link_placeholder : $url;
    $title = empty( $title ) ? '' : $title;
    
    // previous onClick script: "this.value=this.value=='(URL)'?'':this.value"
		
		$this->attributes['placeholder'] = $link_placeholder;
		
		$this->_addline( $this->_input_tag( 'url', $url, false ) );
		
		$this->attributes['placeholder'] = $linktext_placeholder;
		
		$this->_addline( $this->_input_tag( 'text', $title, false ).'</div>' );
		
	}
																			
  /**
   * produces the output for a read-only multi-select element
   * 
   */
  protected function _readonly_multi() {
    
    $display = array();
    $this->group = true;
    
    $this->_addline('<div class="readonly-value-group">');
    
    foreach((array)$this->value as $value) {
      
      if ($value !== '') {
        
        $display[] = $value;
        $this->_addline( $this->_input_tag( 'hidden', $value ) );
        
      }
    }
    $this->_addline('<span class="pdb-readonly">' . implode(', ',$display) . '</span></div>');
    
  }
																			
		

  /**
   * builds a drag-sort element
   *
   * requires js on page to function; this just supplies a suitable handle
   *
   */
  protected function _drag_sort() {
    
    $name = preg_replace( '#(\[.*\])#','', $this->name );

    $this->_addline( '<a id="' . $name . '" class="dragger" href="#" ><span class="glyphicon glyphicon-sort"></span></a>' ); // &uarr;&darr;


  }
  
  /**
   * builds a submit button
   */
  protected function _submit_button() {
    
    $this->_addline( $this->_input_tag( 'submit' ) );
    
  }
  
  /**
   * builds a selector box
   * special checkbox with no unselected value
   */
  protected function _selectbox() {
    
    $this->_addline( $this->_input_tag( 'checkbox', $this->value, false ) );
    
  }
  
  /**
   * build a hidden field
   */
  protected function _hidden() {
    
    $this->_addline( $this->_input_tag( 'hidden' ) );
    
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
      $this->_addline('<span class="file-delete" ><label><input type="checkbox" value="delete" name="' . $this->name . '-deletefile">' . __('delete', 'participants-database') . '</label></span>');
    }
    
    $this->_addline('</div>');
  }

  /**
   * builds a captcha element
   * 
   */
  protected function _captcha() {
    
    $captcha = new PDb_CAPTCHA($this);
    $this->_addline($captcha->get_html());
  }

  /************************ 
	* SUB-ELEMENTS
	*/

  /**
   * builds an input tag
   *
   * @param string $type   the type of input tag to return, defaults to 'text'
   * @param string $value  the value of the tag; if not given, uses object value
   *                       property
   * @param string $select the selected attribute string for the element. If
   *                       given, performs a match test and sets the flag if met
   * @param bool   $group  if set, adds brackets to name for group elements
   * @return string
   *
   */
  protected function _input_tag( $type='text', $value = false, $select = false ) {

    if ( $value === false ) $value = $this->value;
    $size = $this->size ? ' size="'.$this->size.'" ' : '';

    if ($type == 'text' && isset($this->attributes['type'])) {
      $this->attributes = array_merge(array('type'=>$type), (array)$this->attributes);
    } else {
      $this->attributes['type'] = $type;
    }

    $html = '<input name="' . $this->name . ( $this->group ? '[]' : '' ) . '"' . $size . ( false !== $select ? $this->_set_selected( $value, $this->value, $select ) : '' ) . ' ' . $this->_attributes() . '  value="' . $value . '" />';
    // unset the type attribute so it doesn't carry over to the next element
    unset($this->attributes['type']);
    return $html;

  }
  
  /**
   * builds a checkbox or radio input series
   *
   * @param string $type sets the type of input series, defaults to checkbox
   * @param string|bool if string, add an "other" option with this label
   */
  protected function _add_input_series($type = 'checkbox', $otherlabel = false)
  {
    
    // checkboxes are grouped, radios are not
    $this->group = $type == 'checkbox' ? true : false;
		
		$class = '';
		if ( ! empty( $this->classes ) ) {
			$class = 'class="'.$this->classes.'"';
			$this->classes = '';
			}
    
    $null_select = isset($this->options['null_select']) || $type == 'checkbox' ? $this->options['null_select'] : false;
    if ($null_select !== false) {
      $this->_addline($this->_input_tag('hidden', (is_string($null_select)?$null_select:''), false), 1);
    }
    unset($this->options['null_select']);
    
    $this->_addline('<span class="' . $type . '-group" >');
       
    foreach ($this->_make_assoc($this->options) as $option_key => $option_value) {
      
      if (($option_value === false or $option_value === 'false' or $option_value === 'optgroup') and !empty($option_key)) {
        /*
         * this is where we would implement some kind of grouping for display purposes
         */
      } else {
        $this->attributes['id'] = $this->legal_name($this->name . '-' . trim(strtolower($option_value)));
      $this->_addline('<label ' . $class . ' for="' . $this->attributes['id'] . '">');
      $this->_addline($this->_input_tag($type, $option_value, 'checked'), 1);
      $this->_addline($option_key . '</label>');
    	}
  	}
    if ($otherlabel) {
      
      $value = $type == 'checkbox' ? (isset($this->value['other']) ? $this->value['other'] : '') : $this->value;
      $this->attributes['class'] =  'otherselect';
      $this->_addline('<label ' . $class . ' for="' . $this->name . '_otherselect">');
      $this->_addline(sprintf('<input type="%s" id="%s_otherselect" name="%s"  value="%s" %s %s />', 
              $type, 
              $this->name, 
              $type == 'checkbox' ? 'temp' : $this->name, 
              $otherlabel, 
              $this->_set_selected($this->options, $value, 'checked', $value === ''), 
              $this->_attributes()
              ), 1);
      //$this->_addline('<input type="' . $type . '" id="' . $this->name . '_otherselect" name="' . ($type == 'checkbox' ? 'temp' : $this->name) . '"  value="' . $otherlabel . '" ' . $this->_set_selected($this->options, ( $type == 'checkbox' ? $this->value['other'] : $this->value), 'checked', false) . ' ' . $this->_attributes() . ' />', 1);
      $this->_addline($otherlabel . ':');
      $this->_addline('</label>', -1);
    }
    
    $this->_addline('</span>');
  }
  
  /**
   * adds a series of radio buttons
   * 
   * @param string|bool if string, add an "other" option with this label
   */
  protected function _add_checkbox_series($otherlabel = false)
  {
    
    $this->_add_input_series('checkbox', $otherlabel);
  }
    
  /**
   * adds a series of radio buttons
   * 
   * @param string|bool if string, add an "other" option with this label
   */
  protected function _add_radio_series($otherlabel = false)
  {

    $this->_add_input_series('radio', $otherlabel);
  }
  
  /**
   * builds an option series
   * 
   * if an element in the options array has a value of bool false, it will open an 
   * optgroup using the key as the group 
   * 
   * @var string|bool label of the "other" option if any
   */
  protected function _add_option_series($otherlabel = false)
  {
    
    foreach ($this->_make_assoc($this->options) as $key => $value) {
      
      if (($value === false or $value === 'false' or $value === 'optgroup') and !empty($key)) {
        $this->_add_options_divider($key);
      } elseif($value === 'other') {
        $otherlabel = $key;
      } elseif (!empty($value) or $value === 0) {
        $this->_addline('<option value="' . $value . '" ' . $this->_set_selected($value, $this->value, 'selected') . ' >' . stripslashes($key) . '</option>', -1);
      }

    }
    // add the "other" option
    if ( $otherlabel !== false ) $this->_addline( '<option ' . ( $this->value !== '' ? $this->_set_selected( $this->options, $this->value, 'selected', false ) : '' ) . ' value="other" >'.$otherlabel.'</option>' );
    
    if ($this->inside) {
       $this->_addline ('</optgroup>');
       $this->inside = false;
    }
    
  }

  /******************  
	 * OUTPUT FUNCTIONS
	 */

  /**
   * builds an output string
   */
  protected function _output() {

    return implode( self::BR, $this->output ).self::BR;

  }

  /**
   * add a line to the output property
   *
   * places the proper number of tabs at the beginning of each line, then adds
   * the line to the output array
   *
   * @param string  $line          the line to be added
   * @param int     $tab_increment change to the current tab level ( +/- 1 ); false
   *                               for no indent
   */
  protected function _addline( $line, $tab_increment = 0 ) {

    $indent = '';

    if ( false !== $tab_increment ) {

    if ( $tab_increment > 0 ) $this->indent++;
    elseif ( $tab_increment < 0 ) $this->indent--;

    }

    $this->output[] = $indent . $line;

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
    } elseif (filter_var($URI, FILTER_VALIDATE_URL)) {

      // convert the get array to a get string and add it to the URI
      if (is_array($get)) {

        $URI .= false !== strpos($URI, '?') ? '&' : '?';

        $URI .= http_build_query($get);
      }
    } elseif (filter_var($URI, FILTER_VALIDATE_EMAIL)) {

      // in admin, emails are plaintext
      if (is_admin())
        return esc_html($field->value);

      $URI = 'mailto:' . $URI;
    } else {
      return $field->value; // if it is neither URL nor email address nor defined link
    }
      
    // default template for links
    $linktemplate = $template === false ? '<a href="%1$s" >%2$s</a>' : $template;

    $linktext = empty($linktext) ? str_replace(array('http://', 'https://'), '', $URI) : $linktext;

    //construct the link
    return sprintf($linktemplate, $URI, esc_html($linktext));
  }
  
  
  /**
   * adds a class name to the class property
   */
  public function add_class( $classname ) {
    
    $this->classes .= ' ' . $classname;
    
  }
  
  /**
   * builds a string of attributes for inclusion in an HTML element
   *
   * @return string
   */
  protected function _attributes() {
    
    $attributes_array = array();
    $output = '';
    
    if ( ! empty( $this->classes ) ) {
      
      $attributes_array['class'] = $this->classes;
      
    }
    
    if (! empty( $this->attributes ) ) {
      
      $attributes_array = array_merge( $attributes_array, $this->attributes );
      
    }

    if ( empty( $attributes_array ) ) return '';
        
    $pattern = '%1$s="%2$s" ';
      
    foreach ( $attributes_array as $name => $value ) {
          
      $output .= sprintf( $pattern, $name, $value );
          
    }
    
    return $output;
    
  }
  
  /**
   * returns a select state for a form field
   *
   * @param mixed  $element_value  the set value of the element that we compare against
   * @param string $new_value      the selected value of the field
   * 
   * @param string $attribute      the keyword for the select state of the form element
   * @param bool   $state          inverts the logic of the array value match:
   *                               true = looking for a match;
   *                               false = looking for no match
   *
   * @return string selection state string for HTML element
   */
  protected function _set_selected($element_value, $new_value, $attribute = 'selected', $state = true)
  {

    if (is_array($new_value)) { 
      return $this->_set_multi_selected($element_value, $new_value, $attribute, $state);
    }

    $add_attribute = false;
    $new_value = $this->_prep_comp_string($new_value);
    $element_value = $this->_prep_comp_array($element_value);
    
    switch (true) {
      case ($element_value === true and $new_value === true):
      case (is_array($element_value) and ($state === in_array($new_value, $element_value))):
      case ($element_value == $new_value):
          $add_attribute = true;
        break;
      default:
      }
    
    return $add_attribute ? sprintf(' %1$s="%1$s" ', $attribute) : '';
  }

  /**
   * prepares a string for a comparison
   *
   * converts HTML entities to UTF-8 characters
   * 
   * if the argument is not a string, returns it unchanged
   * 
   * @param mixed $string
   * @return mixed converted string or unchanged input
   */
  protected function _prep_comp_string( $string ) {

    return is_string($string) ? trim(html_entity_decode( $string, ENT_QUOTES, 'UTF-8')) : $string;

  }

  /**
   * prepares an array for string comparison
   * 
   * @param array $array the array to prepare for comparison
   * @return array an indexed array of prepared strings
   */
  protected function _prep_comp_array( $array ) {

    if (!is_array($array)) return $this->_prep_comp_string( $array );

    $output = array();

    foreach ( $array as $item ) $output[] = $this->_prep_comp_string( $item );

    return $output;

  }

  /**
   * sets up the "nothing selected" option element
   *      
   * include the null state if it is not overridden. This adds a blank option which 
   * will be selected if there is no value property set for the element. 
   * 
   * If $this->options['null_select'] has a string value, it will be used as the 
   * display value for the null option. 
   * If $this->options['null_select'] is blank, a blank unselected null option will 
   * be added. 
   * If $this->options['null_select'] is false no null state option will be added.
   * If the value is empty and no null select is defined, a blank one will be added
   * 
   * @return null
   */
  protected function _set_null_select() {
    
    $null_select = isset($this->options['null_select']) ? $this->options['null_select'] : false;
    // remove the null_select from the options array
    if (isset($this->options['null_select']))
      unset($this->options['null_select']);
    /*
     * the null select option is added if it is not canceled by a null select value 
     * of false or 'false'
     */
    $null_select_label = is_string($null_select) ? $null_select : '';
    if (($null_select !== false and $null_select !== 'false') || $this->value === '') {
      $selected = $this->value === '' ? $this->_set_selected(true, true, 'selected') : '';
      $this->_addline('<option value="" ' . $selected . '  >' . $null_select_label . '</option>');
    }
    
  }
  
  /**
   * adds a divider element to an option series
   * 
   * @param string $title of the option divider
   * @return null
   */
  protected function _add_options_divider($title) {
    $divider = '';
    if ($this->inside) {
      $divider = '</optgroup>' . self::BR;
      $this->inside = false;
    }
    $divider .= sprintf('<optgroup label="%s">', $title);
    $this->inside = true;
    $this->_addline($divider);
  }

  /**
	 * sets the select states for a multi-select element
	 *
	 * cycles through the available selects or chackboxes and sets the selected
	 * attribute if there is a match to an element of the array of stored values
	 * for the field
	 *
	 * @param string  $element_value   the value of one select of a multi-select
	 * @param array   $new_value_array the array of stored or inputted values
	 * @param string  $attribute       the name of the "selected" attribute for the element
   * @param bool    $state           true to check for a match or false for a non-match
	 * @return string                  the attribute string for the element
	 */
  protected function _set_multi_selected($element_value, $new_value_array, $attribute = 'selected', $state = true) {
		
    $prepped_new_value_array = $this->_prep_comp_array($new_value_array);
		
		$prepped_string = $this->_prep_comp_string($element_value);
		
    if ($state === in_array($prepped_string, $prepped_new_value_array))
      return sprintf(' %1$s="%1$s" ', $attribute);
    else
      return '';
	}

  /**
   * tests the type of an array, returns true if associative
   */
  public static function is_assoc($array) {
		
    return ( is_array($array) && ( count($array)==0 || 0 !== count( array_diff_key( $array, array_keys( array_keys( $array ) ) ) ) ) );
		
  }
  
  /**
   * makes a string OK to use in javascript as a variable or function name
   */
  protected function _prep_js_string( $string ) {
		
    return str_replace( array( '[',']','{','}','-','.','(',')' ), '', $string );
		
  }
  
  /**
   * makes an associative array out of an indexed array by copying the values into the keys
   *
   * given an associative array, it returns the array unaltered
   *
   * @param array the array to be processed
   * @return array an associative array
   */
  protected function _make_assoc( $array ) {
    
    if ( $this->is_assoc( $array ) ) return $array;
    
    return array_combine( array_values( $array ), $array );
    
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
    if (!preg_match('#^[0-9-]+$#', $timestamp)) $timestamp = strtotime($timestamp);

    if (preg_match('#^[0-9-]+$#', $timestamp)) {
      $format = get_option('date_format');
      
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
   * builds a legal CSS classname or ID
   * 
   * @param string $string
   * @return string the legalized name
   */
  public static function legal_name($string) {
    // make sure it doens't start with a numeral
    if (preg_match('/^[0-9]/',$string)) $string = '_' . $string;
    // eliminate any non-legal characters
    $string = preg_replace('/[^_a-zA-Z0-9- ]/', '', $string);
    // replace spaces with a dash
    return strtolower(str_replace(array(' '),array('-'),$string));
  }
  
  /**
   * unambiguously test for all the flavors of emptiness
   * 
   * @var unknown $test the value to test
   * @return bool true if the value is the equivalent of empty, zero or undefined
   */
  public static function is_empty($test) {
    // collapse an array
    if (is_array($test)) $test = implode('', $test);
    switch (true) {
      case $test === '0000-00-00 00:00:00':
      case $test == '0':
      case $test === 0:
      case $test === '':
      case $test === null:
        return true;
        break;
      case $test === false:
      case $test === true:
      default:
        return false;
    }
  }
  
  /**
   * returns a MYSQL datatype appropriate to the form element type
   * 
   * @param string $element the name of the element type
   * @return string the name of the MySQL datatype
   */
  public static function get_datatype($element) {

    switch ($element) {
      
      case 'timestamp':
        $datatype = 'TIMESTAMP';
        break;
      
      case 'date':
        $datatype = 'BIGINT';
        break;
      
      case 'text-line':
        $datatype = 'TINYTEXT';
        break;

      case 'checkbox':
      case 'radio':
      case 'multi-select':
      case 'multi-checkbox':
      case 'text-area':
      case 'rich-text':
      case 'dropdown':
      default :
        $datatype = 'TEXT';

    }

    return $datatype;
  }
  
  /**
   * sets the array of available form element types
   * 
   * merges in an array in the config file, this allowing new types to be registered, 
   * also a language translation of type titles is possible by overwriting an existing 
   * entry
   */
  protected function _set_types() {
     $this->element_types = self::get_types();
  }
  /*
   * static function for assembling the types array
   */
  public static function get_types() {
     $types = array ( 
         'text-line'          => 'Text-line', 
         'text-area'          => 'Text Area', 
         'rich-text'          => 'Rich Text', 
         'checkbox'           => 'Checkbox', 
         'radio'              => 'Radio Buttons', 
         'dropdown'           => 'Dropdown List', 
         'date'               => 'Date Field', 
         'dropdown-other'     => 'Dropdown/Other', 
         'multi-checkbox'     => 'Multiselect Checkbox', 
         'select-other'       => 'Radio Buttons/Other', 
         'multi-select-other' => 'Multiselect/Other', 
         'link'               => 'Link Field', 
         'image-upload'       => 'Image Upload Field', 
         'file-upload'        => 'File Upload Field', 
         'hidden'             => 'Hidden Field', 
         'password'           => 'Password Field', 
         'captcha'            => 'CAPTCHA',
         'timestamp'          => 'Timestamp',
         );
    /*
     * this gives access to the list of form element types for alteration before
     * it is set
     */
    return $types;
  }
  
} //class
?>