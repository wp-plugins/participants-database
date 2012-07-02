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
 * method instantiates the element object, which itself remains private. 
 * See the constructor method for details.
 *
 * Requires PHP Version 5.3 or greater
 * 
 * @category   
 * @package    WordPress
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2011, 2012 xnau webdesign
 * @license    GPL2
 * @version    Release: 1.4
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
// form elements class
//
class FormElement {
  
  // defines the type of form element for the object
  private $type;
  
  // holds the current value of the element
  private $value;
  
  // the name attribute of the form data field
  private $name;
  
  // for elements that have set options such as checkboxes and dropdowns, this 
  // array holds the name=>value pairs
  private $options;
  
  // this array holds all other html element attributes
  private $attributes;
  
  // space-separated string of class names for the form element
  private $classes;

  // array holding the text lines of an element to be output
  private $output = array();
  
  // the size attribute of the input tag
  public $size;

	// sets the height and width of the textarea element
	private $textarea_dims = array( 'rows'=> 2, 'cols'=> 40 );
  
  // boolean: is the element part of a group?
  public $group;
  
  // linebreak character
  const BR = PHP_EOL;
  
  // tab character
  const TAB = "\t";
  
  // holds current indent level
  private $indent;
  
  // holds the internationaliztion strings
  private $i18n;
  
  /**
   * instantiates a FormElement object
	 * 
   *
   * @param array $parameters carries the parameters to build a form element
   *                    type         string sets the type of element to print
   *                    value        string the current value of the element
   *                    name         string the name attribute of the element
   *                    options      mixed  an optional array of values for checkboxes, selects, etc. Can also
   *                                        be serialized array
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
    $this->value        = $params['value'];
    $this->name         = $params['name'];
    $this->classes      = $params['class'];
    $this->size         = $params['size'];
    $this->container_id = $params['container_id'];
    $this->group        = $params['group'];
    
    if ( NULL !== $params['options'] || ! empty( $params['options'] ) ) {
      
      // is_serialized is a utility function in WordPress
      $this->options = is_serialized( $params['options'] ) ? unserialize( $params['options'] ) : $params['options'];
      
    }
    
    $this->i18n = array(
    					'other' => _x('other', 'indicates a write-in choice', Participants_Db::PLUGIN_NAME ),
							'linktext' => _x( 'Link Text','indicates the text to be clicked to go to another web page', Participants_Db::PLUGIN_NAME )
    					);
    
    $this->attributes = $params['attributes'];
    
    // transfers the class attribute if it's set in the attributes array
    // it's possible for class names to be in either/both places, this collects them onto one place
    if ( isset( $this->attributes['class'] ) ) {
      
      $this->classes .= $this->attributes['class'];
      unset( $this->attributes['class'] );
      
    }
    
    $this->indent = $params['indent'];

    // clear the output array
    $this->output = array();

    // set the proper type for the value property
    //$this->_condition_value_property();

    // build the element by calling the type's method
    switch ( $this->type ) :

      case 'date':
        $this->_date_field();
        break;

      case 'text-field':
      case 'textarea':
        $this->_text_field();
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
			case 'file':
			case 'file-upload':
        $this->_upload();
        break;

      default:

    endswitch;
    
  }
  
  /**
   * builds the HTML string for display
   *
   * @static
   */
  private function _HTML( $parameters ) {

    $Element = new FormElement( $parameters );
    
    return $Element->_output();
    
  }
  
  /**********************
	 * PUBLIC FUNCTIONS
	 */
  
  /** 
   * prints a form element
   *
   * @param array $parameters (same as __construct() )
   * @static
   */
  public function print_element( $parameters ) {
    
    echo self::_HTML( $parameters );
    
  }

  /** 
   * returns a form element
   *
   * @param array $parameters (same as __construct() )
   * @static
   */
  public function get_element( $parameters ) {

    return self::_HTML( $parameters );

  }
  
  /**
   * outputs a set of hidden inputs
   *
   * @param array $fields name=>value pairs for each hidden input tag
   */
  public function print_hidden_fields( $fields, $print = true ) {
    
    $atts = array( 'type' => 'hidden');
    
    foreach ( $fields as $k => $v ) {
      
      $atts['name'] = $k;
      $atts['value'] = $v;
      
      if ( $print ) echo self::_HTML( $atts );
			else return self::_HTML( $atts );
      
    }
    
  }

  /************************* 
	 * ELEMENT CONSTRUCTORS
	 */
  
  /**
   * builds a input text element
   */
  private function _text_line() {
    
    $this->_addline( $this->_input_tag() );
    
  }
  
  /**
   * builds a date field
   */
  private function _date_field() {
    
    $this->add_class( 'date_field' );
    
    $this->_addline( $this->_input_tag() );
    
  }
  
  /**
   * builds a text-field (textarea) element
   */
  private function _text_field() {
    
    $value = ! empty( $this->value ) ? $this->value : '';
    
    $this->_addline( '<textarea name="' . $this->name . '" rows="' . $this->textarea_dims['rows'] . '" cols="' . $this->textarea_dims['cols'] . '" ' . $this->_attributes() . ' >'.$value.'</textarea>', empty( $this->value ) ? 0 : -1 );
    
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
  private function _checkbox() {
    
      // if no options are defined, then it's a selectbox
      if ( false === $this->options or ! is_array( $this->options ) ) {
        $this->_selectbox();
        return NULL;
      } else {
        $title = $this->is_assoc( $this->options ) ? key( $this->options ) : false;
        list( $checked_value, $unchecked_value ) = $this->options;
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
  private function _radio() {
    
    $this->_add_radio_series();
       
  }
  
  /**
   * builds a dropdown or dropdown-other element
   */
  private function _dropdown( $other = false ) {
    
    if ( isset( $this->attributes['other'] ) ) {
      $otherlabel = $this->attributes['other'];
      unset( $this->attributes['other'] );
    } else $otherlabel = $this->i18n['other'];
    
    // make a unique prefix for the js function
    $js_prefix = $this->_prep_js_string($this->name);

    if ( $other ) $this->_addline( '<select id="' . $js_prefix . '_otherselect" onChange="' . $js_prefix . 'SelectOther()" name="' . $this->name . '" ' . $this->_attributes() . ' >' );
    else $this->_addline( '<select id="' . $js_prefix . '_select" name="' . $this->name . '" ' . $this->_attributes() . ' >' );

    $this->indent++;
    
    // start with blank option if there's no value for the field
    if ( empty( $this->value ) )
      $this->_addline( '<option value="" ' . ( empty( $this->value ) ? $this->_set_selected( true, true, 'selected' ) : '' ) . '  ></option>' );
    
    $this->_add_option_series();
    
    // add the "other" option
    if ( $other ) $this->_addline( '<option ' . ( ! empty( $this->value ) ? $this->_set_selected( $this->options, $this->value, 'selected', false ) : '' ) . ' value="other" >'.$otherlabel.'</option>' );
    
    $this->_addline( '</select>', -1 );
    
    if ( $other ) :
    
    // build the text input element
    $this->_addline( '<input type="text" id="' . $js_prefix . '_other" name="' . $this->name . '" value="' . ( empty( $this->value ) ? '('.$otherlabel.')' : $this->value ) . '" onClick="' . $js_prefix . 'SetOther()" >' );
    
    
    // put in the javascript
    $this->_addline( '
<script type="text/javascript">
  function '.$js_prefix.'SelectOther() {
    var otherfield=document.getElementById("' . $js_prefix . '_other");
    var otherselect=document.getElementById("' . $js_prefix . '_otherselect");
    if ( otherselect.options[otherselect.selectedIndex].text=="'.$otherlabel.'" ) {
      otherfield.name="'. $this->name . '";
      otherselect.name="temp";
      otherfield.select();
    } else {
      otherselect.name="'. $this->name . '";
      otherfield.name="temp";
      otherfield.value=otherselect.options[otherselect.selectedIndex].text==""?"('.$otherlabel.')":"";
    }
  }
  function '.$js_prefix.'SetOther() {
    var otherfield=document.getElementById("' . $js_prefix . '_other");
    var otherselect=document.getElementById("'.$js_prefix.'_otherselect");
    otherselect.options[otherselect.selectedIndex].removeAttribute("selected");
    otherselect.selectedIndex=otherselect.length-1;
    otherselect.options[otherselect.length-1].setAttribute("selected","selected");
    otherfield.name="'. $this->name . '";
    otherselect.name="temp";
    return true;
  }
  window.onload='.$js_prefix.'SelectOther();
</script>
');
    
    endif;
    
  }

  /**
   * builds a dropdown-other element
   *
   * @return string
   */
  private function _dropdown_other() {

    $this->_dropdown( true );
      
  }

  /**
   * builds a multi-checkbox
   *
   * a set of checkboxes enclosed in a div tag
   */
  private function _multi_checkbox() {

		$this->value = (array) $this->value;

    $this->_addline( '<div class="multicheckbox"' . ( $this->container_id ? ' id="' . $this->container_id . '"' : '' ) . '>' );
    $this->indent++;

    $this->_add_checkbox_series();

    $this->_addline( '</div>', -1 );

  }
  
  /**
   * builds a select/other form element
   *
   * a set of checkboxes or radio buttons with an optional text input element activated by selecting "other"
   *
   * @param string $type can be either 'radio' or 'checkbox' (for a multi-select element)
   */
  private function _select_other( $type = 'radio' ) {
    
    if ( $type != 'radio' ) {
		$this->value = (array) $this->value;
		if ( ! isset( $this->value['other'] ) ) $this->value['other'] = '';
	}
    
    // determine the label for the other field
    if ( isset( $this->attributes['other'] ) ) {
      $otherlabel = $this->attributes['other'];
      unset( $this->attributes['other'] );
    } else $otherlabel = $this->i18n['other'];
    
    // make a unique prefix for the function
    $js_prefix = $this->_prep_js_string($this->name)/*.'_'*/;
    
    $this->attributes['onChange'] = $js_prefix.'SelectOther(this)';
    
    // put it in a conatiner
    $this->_addline( '<div class="selectother"' . ( $this->container_id ? ' id="' . $this->container_id . '"' : '' ) . '>' );
    $this->indent++;
    
    $type == 'checkbox' ? $this->_add_checkbox_series() : $this->_add_radio_series();
    
    // add the "other" option
    $options = $this->options;
    $options[] = '';
		
		//error_log( 'options:'.print_r( $options, true ).' values:'.print_r( $this->value, true ));
    $this->_addline( '<label for="'.$this->name.'">' );
    $this->_addline( '<input type="'.$type.'" id="' . $this->name . '_otherselect" name="'.$this->name . ( $type == 'checkbox' ? '[]' : '' ) . '"  value="'.$otherlabel.'" ' . $this->_set_selected( $options, ( $type == 'checkbox' ? $this->value['other'] : $this->value ), 'checked', false ).' ' . $this->_attributes() . ' />', 1 );
    $this->_addline( $otherlabel.':' );
    $this->_addline( '</label>', -1 );
    
    // build the text input element

    $this->_addline( '<input type="text" id="' . $this->name . '_other" name="' . $this->name . ( $type == 'checkbox' ? '[other]' : '' ) . '" value="' . ( $type == 'checkbox' ? $this->value['other'] : ( ! in_array( $this->value, $options ) ? $this->value : '' ) ) . '" onClick="' . $js_prefix . 'SetOther()" >' );
    
    
    // close the container
    $this->_addline( '</div>', -1 );
    
    // put in the javascript
    $this->_addline( '
<script type="text/javascript">
  function '.$js_prefix.'SelectOther(el) {
    var otherfield=document.getElementById("'.$js_prefix.'_other");
    var otherselect=document.getElementById("'.$js_prefix.'_otherselect");
    if ( otherselect.checked ) {
      otherfield.name="'. $this->name . ( $type == 'checkbox' ? '[other]' : '' ) . '";
      otherselect.name = "'. ( $type == 'checkbox' ? 'temp' : $this->name ) . '";
      otherfield.select();
    } else {
      otherfield.name="temp";
      otherselect.name = "'. ( $type == 'checkbox' ? 'other' : $this->name ) . '";
      otherfield.value=""; 
    }
  }
  function '.$js_prefix.'SetOther() {
    document.getElementById("'.$js_prefix.'_otherselect").checked=true;
    '.$js_prefix.'SelectOther();
  }
  window.onload='.$js_prefix.'SelectOther();
</script>
');

  }
  
  /**
   * builds a multi-select/other form element
   */
  private function _select_other_multi() {
    
    $this->_select_other( 'checkbox' );
    
  }
	
	/**
	 * builds a link form element
	 *
	 * stores an array: first element is the URL the optional second the link text
	 */
	private function _link_field( $linktext = true ) {
		
		if ( true === $linktext ) {
			
			$parts = is_serialized( $this->value ) ? unserialize( $this->value ) : (array) $this->value;
			
			if ( count( $parts ) < 2 ) $parts[1] = $parts[0];
		
			list( $url, $title ) = $parts;
			
			$this->_addline( '<div class="link-element">' );
			
		} else $url = $this->value;
		
		$url = empty( $url ) ? '(URL)' : $url; 
		
		$this->attributes['onclick'] = "this.value=this.value=='(URL)'?'':this.value";
		
		$this->_addline( $this->_input_tag( 'text', $url, false, $linktext ) );
		
		unset( $this->attributes['onclick'] );
		
		if ( true === $linktext )  {
		
			$this->_addline( '<label for="'.$this->name.'">'.$this->i18n['linktext'].'</label>'.$this->_input_tag( 'text', $title, false, true ).'</div>' );
			
		}
		
	}
																			
		

  /**
   * builds a drag-sort element
   *
   * requires js on page to function; this just supplies a suitable handle
   *
   */
  private function _drag_sort() {
    
    $name = preg_replace( '#(\[.*\])#','', $this->name );

    $this->_addline( '<a id="' . $name . '" class="dragger" href="#" >&uarr;&darr;</a>' );


  }
  
  /**
   * builds a submit button
   */
  private function _submit_button() {
    
    $this->_addline( $this->_input_tag( 'submit' ) );
    
  }
  
  /**
   * builds a selector box
   * special checkbox with no unselected value
   */
  private function _selectbox() {
    
    $this->_addline( $this->_input_tag( 'checkbox', $this->value, false, $this->group ) );
    
  }
  
  /**
   * build a hidden field
   */
  private function _hidden() {
    
    $this->_addline( $this->_input_tag( 'hidden' ) );
    
  }
	
	/**
	 * builds a file upload element
	 */
	private function _upload() {
		
		// if an image is already defined, show it
		if ( ! empty( $this->value ) ) {
			
			$this->_addline( '<img class="uploaded-image" id="image_'.$this->name.'" src="'.$this->value.'" />' );
			
		}
		
		// add the MAX_FILE_SIZE field
		// this is really just for guidance, not a valid safeguard; this must be checked on submission
		if ( isset( $this->options[ 'max_file_size' ] ) ) $max_size = $this->options[ 'max_file_size' ];
		else $max_size = ( ini_get( 'post_max_size' ) / 2 ) * 1048576;// half it to give a cushion
		
		$this->_addline( $this->print_hidden_fields( array( 'MAX_FILE_SIZE'=>$max_size ) ) );
		
		$this->_addline( $this->_input_tag( 'file' ) );
		
		$this->_addline( $this->print_hidden_fields( array( $this->name=>$this->value ) ) );
		
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
  private function _input_tag( $type='text', $value = false, $select = false, $group = false ) {

    if ( $value === false ) $value = $this->value;
    $size = $this->size ? ' size="'.$this->size.'" ' : '';

    return '<input type="' . $type . '" name="' . $this->name . ( $group ? '[]' : '' ) . '"' . $size . ( false !== $select ? $this->_set_selected( $value, $this->value, $select ) : '' ) . ' ' . $this->_attributes() . '  value="' . $value . '" />';

  }
  
  /**
   * builds a checkbox series
   *
   * @param string $type sets the type of input series, defaults to checkbox
   */
  private function _add_checkbox_series( $type = 'checkbox' ) {
    
    // checkboxes are grouped, radios are not
    $group = $type == 'checkbox' ? true : false;
    
    foreach ( $this->_make_assoc( $this->options ) as $key => $value ) {
       
      $this->_addline( '<label for="'.$this->name.'">' );
      $this->_addline( $this->_input_tag( $type, $value, 'checked', $group ), 1 );
      $this->_addline( $key );
      $this->_addline( '</label>', -1 );
      
    }
    
  }
  
  /**
   * adds a series of radio buttons
   */
  private function _add_radio_series() {
    
    $this->_add_checkbox_series( 'radio' );
    
  }
  
  /**
   * builds an option series
   */
  private function _add_option_series() {
    
    foreach ( $this->_make_assoc( $this->options ) as $key => $value ) {
      
      $this->_addline( '<option value="' . $value . '" ' . $this->_set_selected( $value, $this->value, 'selected' ) . ' >' . $key . '</option>', -1 );

    }
    
  }

  /******************  
	 * OUTPUT FUNCTIONS
	 */

  /**
   * builds an output string
   */
  private function _output() {

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
  private function _addline( $line, $tab_increment = 0 ) {

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
  private function _attributes() {
    
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
  private function _set_selected( $element_value, $new_value, $attribute = 'selected', $state = true ) {

		
		if ( is_array( $new_value ) ) return $this->_set_multi_selected( $element_value, $new_value, $attribute, $state );
		
    // error_log( __METHOD__.' checking value:'.$this->_prep_comp_string($new_value).' against:'.$this->_prep_comp_string($element_value) );

    if (
        ( is_array( $element_value ) && ( $state === in_array( $this->_prep_comp_string( $new_value ), $this->_prep_comp_array( $element_value ) ) ) )
          ||
          $this->_prep_comp_string( $element_value ) == $this->_prep_comp_string( $new_value )
         ) {
      
      return sprintf( ' %1$s="%1$s" ', $attribute );
      
    } else return '';
    
  }

  /**
   * prepares a string for a comparison
   *
   * removes all non-A-Z characters to avoid problems with encoded characters
	 *
	 * not at all perfect, but we just need to accomplish a match, so it would 
	 * only fail if the difference between two terms was only within the 
	 * excluded characters. We're not actually changing any stored data here.
   */
  private function _prep_comp_string( $string ) {

    return preg_replace( '/[^a-zA-Z0-9\p{L}&#;!=]/', '' , htmlspecialchars_decode( (string) $string, ENT_QUOTES ) );

  }

  /**
   * prepares an array for string comparison
   */
  private function _prep_comp_array( $array ) {

    $output = array();

    foreach ( $array as $item ) $output[] = $this->_prep_comp_string( $item );

    return $output;

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
	 * @param boolean $state           true to check for a match or false for a non-match
	 * @return string                  the attribute string for the element
	 */
	private function _set_multi_selected( $element_value, $new_value_array, $attribute = 'selected', $state = true ) {
		
		$prepped_new_value_array = $this->_prep_comp_array( $new_value_array );
		
		$prepped_string = $this->_prep_comp_string($element_value);
		
    //if (WP_DEBUG) error_log( __METHOD__.' checking value:'.$prepped_string.'('.$element_value.')'.' against:'.print_r($prepped_new_value_array,true).' state:'.( in_array( $prepped_string, $prepped_new_value_array)?'true':'false').' setting: '.$attribute );
			
		if ( $state === in_array( $prepped_string, $prepped_new_value_array ) ) return  sprintf( ' %1$s="%1$s" ', $attribute );
		
		else return '';
		
	}

  /**
   * tests the type of an array, returns true if associative
   */
  public function is_assoc($array) {
		
    return ( is_array($array) && ( count($array)==0 || 0 !== count( array_diff_key( $array, array_keys( array_keys( $array ) ) ) ) ) );
		
  }
  
  /**
   * makes a string OK to use in javascript as a variable or function name
   */
  private function _prep_js_string( $string ) {
		
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
  private function _make_assoc( $array ) {
    
    if ( $this->is_assoc( $array ) ) return $array;
    
    return array_combine( array_values( $array ), $array );
    
  }
  
} //class
?>