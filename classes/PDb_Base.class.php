<?php

/*
 * this static class provides a set of utility functions used throughout the plugin
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2014 xnau webdesign
 * @license    GPL2
 * @version    0.8
 * @link       http://xnau.com/wordpress-plugins/
 */
if ( ! defined( 'ABSPATH' ) ) die;
class PDb_Base {
  /**
   * set if a shortcode is called on a page
   * @var bool
   */
  public static $shortcode_present = false;
  /**
   * parses a list shortcode filter string into an array
   * 
   * this creates an array that makes it easy to manipulate and interact with the 
   * filter string. The returned array is of format:
   *    'fieldname' => array(
   *       'column' => 'fieldname',
   *       'operator' => '=', (<, >, =, !, ~)
   *       'search_term' => 'string',
   *       'relation' => '&', (optional)
   *       ),
   * 
   * @param string $filter the filter string
   * @return array the string parsed into an array of statement arrays
   */
  public static function parse_filter_string($filter) {
    $return = array();
    $statements = preg_split('/(&|\|)/', html_entity_decode($filter), null, PREG_SPLIT_DELIM_CAPTURE);
    foreach($statements as $s) {
      $statement = self::_filter_statement($s);
      if ($statement) $return[] = $statement;
    }
    return $return;
  }
  /**
   * builds a filter string from an array of filter statement objects or arrays
   * 
   * @param array $filter_array
   */
  public static function build_filter_string($filter_array) {
    $filter_string = '';
    foreach($filter_array as $statement) {
      $filter_string .= $statement['column'] . $statement['operator'] . $statement['search_term'] . $statement['relation'];
    }
    return rtrim($filter_string,'&|');
  }
  /**
   * merges two filter statement arrays
   * 
   * if a given target field is present in both arrays, all statements for that 
   * field will be eliminated from the first array, and the statements from the 
   * second array will be used. All other elements in the second array will follow the elements from the first array
   * 
   * @param array $array1
   * @param array $array2 the overriding array
   * @return array the combined array
   */
  public static function merge_filter_arrays($array1, $array2) {
    $return = array();
    foreach($array1 as $statement) {
      $index = self::search_array_column($array2, $statement['column']);
      if ($index === false) {
        $return[] = $statement;
      }
    }
    return array_merge($return, $array2);
  }
  /**
   * searches for a matching column in an array
   * 
   * this function searches for a matching term of a given key in the second dimension 
   * of the array and returns the index of the matching array
   * 
   * @param array $array the array to search
   * @param string $term the term to search for
   * @param string the key of the element to search in
   * @return mixed the int index of the matching array or bool false if no match
   */
  private static function search_array_column($array, $term, $key = 'column') {
    for($i = 0;$i < count($array);$i++) {
      if ($array[$i][$key] == $term) return $i;
    }
    return false;
  }
  /**
   * supplies an object comprised of the componenets of a filter statement
   * 
   * @param type $statement
   * @return array
   */
  private static function _filter_statement($statement,$relation = '&') {
    
    $operator = preg_match('#^([^\2]+)(\>|\<|=|!|~)(.*)$#', $statement, $matches);

    if ($operator === 0)
      return false; // no valid operator; skip to the next statement
    
    list( $string, $column, $operator, $search_term ) = $matches;
    
    $return = array();
    
    // get the parts
    $return = compact('column', 'operator', 'search_term');
    
    $return['relation'] = $relation;
    
    return $return;
  }
  
  /**
   * determines if an incoming set of data matches an existing record
   * 
   * @param array|string $columns column name, comma-separated series, or array of 
   *                              column names to check for matching data
   * @param array $data the incoming data to test: name => value
   * @global object $wpdb
   * @return int|bool record ID if the incoming data matches an existing record, 
   *                  bool false if no match
   */
  public static function find_record_match($columns, $data) {
    global $wpdb;
    $values = array();
    $where = array();
    $columns = strpos($columns,',') !== false ? explode(',',  str_replace(' ', '', $columns)) : (array)$columns;
    foreach($columns as $column) {
      if (isset($data[$column])) {
        $values[] = $data[$column];
        $where[] = ' r.' . $column . ' = %s';
      } else {
        $where[] = ' (r.' . $column . ' IS NULL OR r.' . $column . ' = "")';
      }
    }
    $sql = 'SELECT r.id FROM ' . Participants_Db::$participants_table . ' r WHERE ' . implode(', ', $where);
    $match = $wpdb->get_var($wpdb->prepare($sql, $values));
    return is_numeric($match) ? (int)$match : false;
  }
  
  /**
   * provides a permalink given a page name, path or ID
   * 
   * this allows a permalink to be found for a page name, relative path or post ID. 
   * If an absolute path is provided, the path is returned unchanged.
   * 
   * @param string|int $page the term indicating the page to get a permalink for
   * @global object $wpdb
   * @return string|bool the permalink or false if it fails
   */
  public static function find_permalink($page) {
    $permalink = false;
    $id = false;
    if (filter_var($page, FILTER_VALIDATE_URL)) {
      $permalink = $page;
    } elseif (preg_match('#^[0-9]+$#', $page)) {
      $id = $page;
    } elseif ($post = get_page_by_path($page)) {
      $id = $post->ID;
    } else {
      global $wpdb;
      $id = $wpdb->get_var( $wpdb->prepare( "SELECT p.ID FROM $wpdb->posts p WHERE p.post_name = '%s' AND p.post_status = 'publish'", trim($page, '/ ') ) );
    }
    if ($id) $permalink = get_permalink($id);
    return $permalink;
  }
  
  /**
   * determines if the field is the designated single record field
   * 
   * also checks that the single record page has been defined
   * 
   * @param object $field
   * @return bool
   */
  public static function is_single_record_link($field) {
    $name = is_object($field) ? $field->name : $field;
    $page = Participants_Db::plugin_setting('single_record_page');
    return $name === Participants_Db::plugin_setting('single_record_link_field') && !empty($page);
  }
  
  /*
   * prepares an array for storage in the database
   *
   * @param array $array
   * @return string prepped array in serialized form or empty if no data
   */

  public static function _prepare_array_mysql($array)
  {

    if (!is_array($array))
      return Participants_Db::_prepare_string_mysql($array);

    $prepped_array = array();

    $empty = true;

    foreach ($array as $key => $value) {

      if ($value !== '')
        $empty = false;
      $prepped_array[$key] = Participants_Db::_prepare_string_mysql((string)$value);
    }

    return $empty ? '' : serialize($prepped_array);
  }

  /**
   * prepares a string for storage
   *
   * gets the string ready by getting rid of slashes and converting quotes and
   * other undesirables to HTML entities
   * 
   * @param string $string the string to prepare
   */
  public static function _prepare_string_mysql($string)
  {
    
    return stripslashes($string);
  }

  /**
   * unserializes an array if necessary
   * 
   * @param string $string the string to unserialize; does nothing if it is not 
   *                       a serialization
   * @return array or string if not a serialization
   */
  public static function unserialize_array($string)
  {

    return maybe_unserialize($string);
  }

  /**
   * adds the URL conjunction to a GET string
   *
   * @param string $URI the URI to which a get string is to be added
   *
   * @return string the URL with the conjunction character appended
   */
  public static function add_uri_conjunction($URI)
  {

    return $URI . ( false !== strpos($URI, '?') ? '&' : '?');
  }
  /**
   * returns a path to the defined image location
   *
   * this func is superceded by the PDb_Image class methods
   *
   * can also deal with a path saved before 1.3.2 which included the whole path
   *
   * @return the file url if valid; if the file can't be found returns the
   *         supplied filename
   */
  public static function get_image_uri($filename) {

    if (!file_exists($filename)) {

      $filename = get_bloginfo('wpurl') . '/' . Participants_Db::plugin_setting('image_upload_location') . basename($filename);
    }

    return $filename;
  }

  /**
   * parses the value string and obtains the corresponding dynamic value
   *
   * the object property pattern is 'object->property' (for example 'curent_user->name'),
   * and the presence of the  '->'string identifies it.
   * 
   * the superglobal pattern is 'global_label:value_name' (for example 'SERVER:HTTP_HOST')
   *  and the presence of the ':' identifies it.
   *
   * if there is no indicator, the field is treated as a constant
   *
   * @param string $value the current value of the field as read from the
   *                      database or in the $_POST array
   *
   */
  public static function get_dynamic_value($value) {

    // this value serves as a key for the dynamic value to get
    $dynamic_key = html_entity_decode($value);
    /**
     * @version 1.6 added 'pdb-dynamic_value' filter
     */
    $dynamic_value = Participants_Db::set_filter('dynamic_value', '', $dynamic_key);
    // return the value if it was set in the filter
    if (!empty($dynamic_value)) return $dynamic_value;

    if (strpos($dynamic_key, '->') > 0) {
      
      /*
       * here, we can get values from one of several WP objects
       * 
       * so far, that is only $post amd $current_user
       */
      global $post, $current_user;

      list( $object, $property ) = explode('->', $dynamic_key);

      $object = ltrim($object, '$');

      if (is_object($$object) && isset($$object->$property)) {

        $dynamic_value = $$object->$property;
      }
    } elseif (strpos($dynamic_key, ':') > 0) {
      
      /*
       * here, we are attempting to access a value from a PHP superglobal
       */

      list( $global, $name ) = explode(':', $dynamic_key);
      
      /*
       * if the value refers to an array element by including [index_name] or 
       * ['index_name'] we extract the indices
       */
      $indexes = array();
      if (strpos($name, '[') !== false) {
        $count = preg_match("#^([^]]+)(?:\['?([^]']+)'?\])?(?:\['?([^]']+)'?\])?$#", stripslashes($name), $matches);
        $match = array_shift($matches); // discarded
        $name = array_shift($matches);
        $indexes = count($matches) > 0 ? $matches : array();
      }

      // clean this up in case someone puts $_SERVER instead of just SERVER
      $global = preg_replace('#^[$_]{1,2}#', '', $global);

      /*
       * for some reason getting the superglobal array directly with the string
       * is unreliable, but this bascially works as a whitelist, so that's
       * probably not a bad idea.
       */
      switch (strtoupper($global)) {

        case 'SERVER':
          $global = $_SERVER;
          break;
        case 'SESSION':
          $global = $_SESSION;
          break;
        case 'REQUEST':
          $global = $_REQUEST;
          break;
        case 'COOKIE':
          $global = $_COOKIE;
          break;
        case 'POST':
          $global = $_POST;
          break;
        case 'GET':
          $global = $_GET;
      }

      /*
       * we attempt to evaluate the named value from the superglobal, which includes 
       * the possiblity that it will be referring to an array element. We take that 
       * to two dimensions only. the only way that I know of to do this open-ended 
       * is to use eval, which I won't do
       */
      if (isset($global[$name])) {
        if (is_string($global[$name])) {
          $dynamic_value = $global[$name];
        } elseif (is_array($global[$name]) || is_object($global[$name])) {
        
          $array = is_object($global[$name]) ? get_object_vars($global[$name]) : $global[$name];
	        switch (count($indexes)) {
		        case 1:
		              $dynamic_value = isset($array[$indexes[0]]) ? $array[$indexes[0]] : '';
		          break;
		        case 2:
		              $dynamic_value = isset($array[$indexes[0]][$indexes[1]]) ? $array[$indexes[0]][$indexes[1]] : '';
		          break;
		        default:
		            // if we don't have an index, grab the first value
		              $dynamic_value = is_array($array) ? current($array) : '';
	        }
	      }
	    }
    }

    /*
     * note: we need to sanitize the value, but we don't know what kind of value 
     * it will be so we're just going to treat them all as strings. It shouldn't 
     * be object or array anyway, so if a number is represented as a string, it's 
     * not a big deal.
     */
    return filter_var($dynamic_value, FILTER_SANITIZE_STRING);
  }
  
  /**
   * determines if the field default value string is a dynamic value
   * 
   * @param string $value the value to test
   * @return bool true if the value is to be parsed as dynamic
   */
  public static function is_dynamic_value($value) {
    $test_value = html_entity_decode($value);
  	return strpos($test_value, '->') > 0 || strpos($test_value, ':') > 0;
  }

  /**
   * processes an incoming timestamp
   * 
   * timestamps are usually handled by the plugin automatically, but when records 
   * are being imported via CSV, they should be imported
   * 
   * @var string $timestamp a timestamp value, could be unix timestamp or text date
   * @return bool|string if the timestamp is valid, a MySQL timestamp is returns; 
   *                     bool false otherwise
   */
  public static function import_timestamp($timestamp) {
    
    $post_date = $timestamp !== null ? Participants_Db::parse_date($timestamp) : false;
    $new_value = $post_date !== false ? date('Y-m-d H:i:s', $post_date) : false;
    
    return $new_value;
    
  }

  /**
   * supplies a group object for the named group
   * 
   * @param string $name group name
   * @return object the group parameters as a stdClass object
   */
  public static function get_group($name) {
    global $wpdb;
    $sql = 'SELECT * FROM ' . Participants_Db::$groups_table  . ' WHERE `name` = "%s"';
    return current($wpdb->get_results($wpdb->prepare($sql, $name)));
  }
  
  
  /**
   * check the current users plugin role
   * 
   * the plugin has two roles: editor and admin; it is assumed an admin has the editor 
   * capability
   * 
   * @param string $role optional string to test a specific role. If omitted, tests 
   *                     for either role
   * @param string $context the function or action being tested for
   * 
   * @return bool true if current user has the role tested
   */
  public static function current_user_has_plugin_role($role = 'editor', $context = '') {
    
    $role = $role === 'admin' ? 'plugin_admin_capability' : 'record_edit_capability';
    
    return current_user_can(self::plugin_capability(self::plugin_setting($role), $context));
  }
  
  /**
   * checks a plugin permission level and passes it through a filter
   * 
   * this allows for all plugin functions that are permission-controlled to be controlled 
   * with a filter callback
   * 
   * the context value will contain the name of the function or script that is pretected
   * 
   * see: http://codex.wordpress.org/Roles_and_Capabilities
   * 
   * @param string $cap the plugin capability level (not WP cap) to check for
   * @param string $context provides the context of the request
   * 
   * @return string the name of the WP capability to use
   */
  public static function plugin_capability ($cap, $context = '') {
    
    $capability = 'read'; // assume the lowest cap
    if (in_array($cap, array('plugin_admin_capability','record_edit_capability'))) {
      $capability = self::set_filter('access_capability', self::plugin_setting($cap), $context);
    }
    return $capability;
  }

  /**
   * loads the plugin translation fiels and sets the textdomain
   * 
   * the parameter is for the use of aux plugins
   * 
   * originally from: http://geertdedeckere.be/article/loading-wordpress-language-files-the-right-way
   * 
   * @param string $path of the calling file
   * @param string $textdomain omit to use default plugin textdomain
   * 
   * @return null
   */
  public static function load_plugin_textdomain($path, $textdomain = '') {
    
    $textdomain = empty($textdomain) ? Participants_Db::PLUGIN_NAME : $textdomain;
    // The "plugin_locale" filter is also used in load_plugin_textdomain()
    $locale = apply_filters('plugin_locale', get_locale(), $textdomain);

    load_textdomain( $textdomain, WP_LANG_DIR.'/' . Participants_Db::PLUGIN_NAME . '/' . $textdomain . '-' . $locale . '.mo' );
    load_plugin_textdomain( $textdomain, false, dirname(plugin_basename($path)).'/languages/');

  }

  
  /**
   * provides a plugin setting
   * 
   * @param string $name setting name
   * @param string|int|float $default a default value
   * @return string the plugin setting value or provided default
   */
  public static function plugin_setting($name, $default = false) {
    $setting = isset(Participants_Db::$plugin_options[$name]) ? Participants_Db::$plugin_options[$name] : $default;
    return $setting;
  }
  
  /**
   * checks a plugin setting for a saved value
   * 
   * returns false for empty string, true for 0
   * 
   * @param string $name setting name
   * @return bool false true if the setting has been saved by the user
   */
  public static function plugin_setting_is_set($name) {
    return isset(Participants_Db::$plugin_options[$name]) && strlen(Participants_Db::plugin_setting($name)) > 0;
  }
  /**
   * provides a boolean plugin setting value
   * 
   * @param string $name of the setting
   * @param bool the default value
   * @return bool the setting value
   */
  public static function plugin_setting_is_true($name, $default = false) {
    
    if (isset(Participants_Db::$plugin_options[$name])) {
      return filter_var(Participants_Db::plugin_setting($name), FILTER_VALIDATE_BOOLEAN);
    } else {
      return (bool) $default;
    }
  }
  /**
   * sets up an API filter
   * 
   * determines if a filter has been set for the given tag, then either filters 
   * the term or returns it unaltered
   * 
   * this function also allows for two extra parameters
   * 
   * @param string $slug the base slug of the plugin API filter
   * @param unknown $term the term to filter (passed by reference)
   * @param unknown $var1 extra variable
   * @param unknown $var2 extra variable
   * @return unknown the filtered or unfiltered term
   */
  public static function set_filter($slug, $term, $var1 = NULL, $var2 = NULL)
  {
    if (strpos($slug, Participants_Db::$prefix) === false) {
			$tag = Participants_Db::$prefix . $slug;
    }
    if (!has_filter($tag)) {
      return $term;
    }
    return apply_filters($tag, $term, $var1, $var2);
  }

  /**
   * writes the custom CSS setting to the custom css file
   * 
   * @return bool true if the css file can be written to
   * 
   */
  protected static function _set_custom_css() {
    $css_file = Participants_Db::$plugin_path . '/css/PDb-custom.css';
    if (!is_writable($css_file)) {
      return false;
    }
    $file_contents = file_get_contents($css_file);
    $custom_css = Participants_Db::$plugin_options['custom_css'];
    if ($file_contents === $custom_css) {
      // error_log(__METHOD__.' CSS settings are unchanged; do nothing');
    } else {
      file_put_contents($css_file, $custom_css);
    }
    return true;
  }

  /**
   * deletes a file
   * 
   * this looks in the fie upload directory and deletes $filename if found
   * 
   * @param string $filename
   * @return bool success
   */
  public static function delete_file($filename)
  {
    $current_dir = getcwd(); // save the cirrent dir
    chdir(ABSPATH . Participants_Db::plugin_setting('image_upload_location')); // set the plugin uploads dir
    $result = unlink(basename($filename)); // delete the file
    chdir($current_dir); // change back to the previous directory
    return $result;
  }

  /**
   * makes a title legal to use in anchor tag
   */
  public static function make_anchor($title)
  {

    return str_replace(' ', '', preg_replace('#^[0-9]*#', '', strtolower($title)));
  }

  /**
   * checks if the current user's form submissions are to be validated
   * 
   * @return bool true if the form should be validated 
   */
  public static function is_form_validated() {
    
    if (is_admin()) {
      return self::current_user_has_plugin_role('admin', 'forms not validated') === false;
    } else {
      return true;
    }
    
  }

  /**
   * recursively merges two arrays, overwriting matching keys
   *
   * if any of the array elements are an array, they will be merged with an array
   * with the same key in the base array
   *
   * @param array $array    the base array
   * @param array $override the array to merge
   * @return array
   */
  public static function array_merge2($array, $override)
  {
    $x = array();
    foreach ($array as $k => $v) {
      if (isset($override[$k])) {
        if (is_array($v)) {
          $v = Participants_Db::array_merge2($v, (array) $override[$k]);
        }
        else
          $v = $override[$k];
        unset($override[$k]);
      }
      $x[$k] = $v;
    }
    // add in the remaining unmatched elements
    return $x += $override;
  }

  /**
   * validates a time stamp
   *
   * @param mixed $timestamp the string to test
   * @return bool true if valid timestamp
   */
  public static function is_valid_timestamp($timestamp)
  {
    return is_int($timestamp) or ((string) (int) $timestamp === $timestamp);
  }

  /**
   * returns the PHP version as a float
   *
   */
  function php_version()
  {

    $numbers = explode('.', phpversion());

    return (float) ( $numbers[0] + ( $numbers[1] / 10 ) );
  }

  /**
   * Convert a date format to a strftime format 
   * 
   * Timezone conversion is done for unix. Windows users must exchange %z and %Z. 
   * 
   * Unsupported date formats : S, n, t, L, B, G, u, e, I, P, Z, c, r 
   * Unsupported strftime formats : %U, %W, %C, %g, %r, %R, %T, %X, %c, %D, %F, %x 
   * 
   * Props: http://php.net/manual/en/function.strftime.php#96424
   * 
   * @param string $dateFormat a date format 
   * @return string 
   */
  public static function dateFormatToStrftime($dateFormat)
  {

    $caracs = array(
        // Day - no strf eq : S 
        'd' => '%d', 'D' => '%a', 'j' => '%e', 'l' => '%A', 'N' => '%u', 'w' => '%w', 'z' => '%j',
        // Week - no date eq : %U, %W 
        'W' => '%V',
        // Month - no strf eq : n, t 
        'F' => '%B', 'm' => '%m', 'M' => '%b',
        // Year - no strf eq : L; no date eq : %C, %g 
        'o' => '%G', 'Y' => '%Y', 'y' => '%y',
        // Time - no strf eq : B, G, u; no date eq : %r, %R, %T, %X 
        'a' => '%P', 'A' => '%p', 'g' => '%l', 'h' => '%I', 'H' => '%H', 'i' => '%M', 's' => '%S',
        // Timezone - no strf eq : e, I, P, Z 
        'O' => '%z', 'T' => '%Z',
        // Full Date / Time - no strf eq : c, r; no date eq : %c, %D, %F, %x  
        'U' => '%s'
    );

    return strtr((string) $dateFormat, $caracs);
  }

  /**
   * translates date format strings from PHP to other formats
   *
   * @param string $dateformat the PHP-style date format string
   * @param string $format_type selected the format type to translate to: 'ICU', 'jQuery', 'strftime'
   * @return string the translated format string
   */
  static function translate_date_format($dateformat, $format_type)
  {

    // these are the PHP date codes
    $pattern = array(
        //day
        'd', //day of the month
        'j', //1 or 2 digit day of month
        'l', //full name of the day of the week
        'D', // abbreviated day of the week
        'z', //day of the year
        //month
        'F', //Month name full
        'M', //Month name short
        'n', //numeric month no leading zeros
        'm', //numeric month leading zeros
        //year
        'Y', //full numeric year
        'y'  //numeric year: 2 digit
    );
    switch ($format_type) {
      case 'strftime':
        return self::dateFormatToStrftime($dateformat);
        break;
      case 'ICU':
        $replace = array(
            'dd', 'd', 'EEEE', 'EEEE', 'D',
            'MMMM', 'MMM', 'M', 'MM',
            'yyyy', 'yy'
        );
        break;
      case 'jQuery':
        $replace = array(
            'dd', 'd', 'DD', 'D', 'o',
            'MM', 'M', 'm', 'mm',
            'yy', 'y'
        );
        break;
    }
    $i = 1;
    foreach ($pattern as $p) {
      $dateformat = str_replace($p, '%' . $i . '$s', $dateformat);
      $i++;
    }
    return vsprintf($dateformat, $replace);
  }

  /**
   * translates a PHP date() format string to an ICU format string
   * 
   * @param string $PHP_date_format the date format string
   *
   */
  static function get_ICU_date_format($PHP_date_format = '')
  {

    $dateformat = empty($PHP_date_format) ? Participants_Db::$date_format : $PHP_date_format;

    return Participants_Db::translate_date_format($dateformat, 'ICU');
  }

  /**
   * translates a PHP date() format string to a jQuery format string
   * 
   * @param string $PHP_date_format the date format string
   *
   */
  static function get_jqueryUI_date_format($PHP_date_format = '')
  {

    $dateformat = empty($PHP_date_format) ? Participants_Db::$date_format : $PHP_date_format;

    return Participants_Db::translate_date_format($dateformat, 'jQuery');
  }

  /**
   * sets an admin area error message
   * 
   * @param string $message the message to be dislayed
   * @param string $type the type of message: 'updated' (yellow) or 'error' (red)
   */
  public static function set_admin_message($message, $type = 'error')
  {
    if (is_admin()) {
			Participants_Db::$session->set('admin_message', array($message, $type));
			Participants_Db::$admin_message = $message;
			Participants_Db::$admin_message_type = $type;
		}
  }

  /**
   * prints the admin message
   */
  public static function admin_message()
  {
    if (Participants_Db::$session->get('admin_message')) {
      list(Participants_Db::$admin_message, Participants_Db::$admin_message_type) = Participants_Db::$session->get('admin_message');
      if (!empty(Participants_Db::$admin_message)) {
        printf('<div class="%s"><p>%s</p></div>', Participants_Db::$admin_message_type, Participants_Db::$admin_message);
        Participants_Db::$session->clear('admin_message');
      }
    }
  }

/**
   * gets the timezone
   * 
   * @return string
   */
  public static function get_timezone() {
    $php_timezone = ini_get('date.timezone');
    return empty($php_timezone) ? 'UTC' : $php_timezone;
  } 

/**
 * collect a list of all the plugin shortcodes present in the content
 *
 * @global array $shortcode_tags
 * @param string $content the content to test
 * @param string $tag
 * @return array of plugin shortcode tags
 */
  public static function get_plugin_shortcodes($content = '', $tag = '[pdb_') {
    
    $shortcodes = array();
    // get all shortcodes
    preg_match_all('/' . get_shortcode_regex() . '/s', $content, $matches, PREG_SET_ORDER);
    
    // none found
    if (!empty($matches[0][0]) && false === strpos($matches[0][0], $tag))
      return false;
    // check each one for a plugin shortcode
    foreach ($matches as $shortcode) {
      $shortcodes[] = $shortcode[2] . '-shortcode';
    }
    return $shortcodes;
  }
/**
 * check a string for a shortcode
 *
 * modeled on the WP function of the same name
 * 
 * what's different here is that it will return true on a partial match so it can 
 * be used to detect any of the plugin's shortcode. Generally, we just check for 
 * the common prefix
 *
 * @global array $shortcode_tags
 * @param string $content the content to test
 * @param string $tag
 * @return boolean
 */
  public static function has_shortcode($content = '', $tag = '[pdb_') {
    
    // get all shortcodes
    preg_match_all('/' . get_shortcode_regex() . '/s', $content, $matches, PREG_SET_ORDER);
    // none found
    if (empty($matches))
      return false;
    // check each one for a plugin shortcode
    foreach ($matches as $shortcode) {
      if (false !== strpos($shortcode[0], $tag)) {
        return true;
      }
    }
    return false;
  }
  /**
   * sets the shortcode present flag if a plugin shortcode is found in the post
   * 
   * runs on the 'wp' filter
   * 
   * @global object $post
   * @return array $posts
   */
  public static function remove_rel_link() {
    global $post;
    /*
     * this is needed to prevent Firefox prefetching the next page and firing the damn shortcode
     * 
     * as per: http://www.ebrueggeman.com/blog/wordpress-relnext-and-firefox-prefetching
     */
    if (is_object($post) && $post->post_type === 'page') {
      remove_action('wp_head', 'adjacent_posts_rel_link_wp_head');
    }
  }
  /**
   * provides an array of field indices corresponding, given a list of field names
   * 
   * or vice versa if $indices is false
   * 
   * @param array $fieldnames the array of field names
   * @param bool  $indices if true returns array of indices, if false returns array of fieldnames
   * @return array an array of integers
   */
  public static function get_field_indices($fieldnames, $indices = true) {
    global $wpdb;
    $sql = 'SELECT f.' . ($indices ? 'id' : 'name') . ' FROM ' . Participants_Db::$fields_table . ' f ';
    $sql .= 'WHERE f.' . ($indices ? 'name' : 'id') . ' ';
    if (count($fieldnames) > 1) {
      $sql .= 'IN ("' . implode('","',$fieldnames) . '") ORDER BY FIELD(f.name, "' . implode('","',$fieldnames) . '")';
    } else {
      $sql .= '= "' . current($fieldnames) . '"';
    }
    return $wpdb->get_col($sql);
  }

  /**
   * provides a list of field names, given a list of indices
   * 
   * @param array $ids of integer ids
   * @return array of field names
   * 
   */
  public static function get_indexed_names($ids) {
    return self::get_field_indices($ids, false);
  }
  /**
   * gets a list of column names from a dot-separated string of ids
   * 
   * @param string $ids the string of ids
   * @return array of field names
   */
  public static function get_shortcode_columns($ids) {
    return self::get_field_indices(explode('.',$ids), false);
  }
  
  /**
   * provides a filter array for a search submission
   * 
   * filters a POST submission for displaying a list
   * 
   * @param bool $multi if true, filter a multi-field search submission
   * @return array of filter parameters
   */
  public static function search_post_filter($multi = false) {
    $array_filter = array(
            'filter' => FILTER_SANITIZE_STRING,
            'flags' => FILTER_FORCE_ARRAY
        );
    $multi_validation = $multi ? $array_filter : FILTER_SANITIZE_STRING;
    return array (
        'filterNonce' => FILTER_SANITIZE_STRING,
        'postID' => FILTER_VALIDATE_INT,
        'submit' => FILTER_SANITIZE_STRING,
        'action' => FILTER_SANITIZE_STRING,
        'instance_index' => FILTER_VALIDATE_INT,
        'pagelink' => FILTER_SANITIZE_STRING,
        'sortstring' => FILTER_SANITIZE_STRING,
        'orderstring' => FILTER_SANITIZE_STRING,
        'search_field' => $multi_validation,
        'operator' => $multi_validation,
        'value' => $multi_validation,
        'logic' => $multi_validation,
        'sortBy' => FILTER_SANITIZE_STRING,
        'ascdesc' => FILTER_SANITIZE_STRING,
        'listpage' => FILTER_VALIDATE_INT,
     );
  }
  
  /**
   * attempts to prevent browser back-button caching in the middle of a multipage form
   * 
   * @param array $headers array of http headers
   * @return array altered headers array
   */
  public static function control_caching($headers) {
    $form_status = Participants_Db::$session->get('form_status');
//    $headers['X-xnau-plugin'] = $headers['X-xnau-plugin'] . ' ' . Participants_Db::$plugin_title . '-' . Participants_Db::$plugin_version;
    if ($form_status === 'multipage') {
      $headers['Cache-Control'] = 'no-cache, max-age=0, must-revalidate, no-store';
    }
    return $headers;
  }
  
  /**
   * clears the shortcode session for the current page
   * 
   * TODO: unused method
   * 
   * shortcode sessions are used to provide asynchronous functions with the current 
   * shortcode attributes
   */
  public static function reset_shortcode_session() {
    global $post;
    if (is_object($post)) {
			$current_session = Participants_Db::$session->get('shortcode_atts');
			/*
			 * clear the current page's session
			 */
			$current_session[$post->ID] = array();
			Participants_Db::$session->set('shortcode_atts', $current_session);
		}
  }
  /**
   * Remove slashes from strings, arrays and objects
   * 
   * @param    mixed   input data
   * @return   mixed   cleaned input data
   */
  public static function deep_stripslashes($input)
  {
    if (is_array($input)) {
      $input = array_map(array(__CLASS__,'deep_stripslashes'), $input);
    } elseif (is_object($input)) {
      $vars = get_object_vars($input);
      foreach ($vars as $k => $v) {
        $input->{$k} = deep_stripslashes($v);
      }
    } else {
      $input = stripslashes($input);
    }
    return $input;
  }
  
  /**
   * encodes or decodes a string using a simple XOR algorithm
   * 
   * @param string $string the tring to be encoded/decoded
   * @param string $key the key to use
   * @return string
   */
  public static function xcrypt($string, $key = false)
  {
    if ($key === false) {
      $key = self::get_key();
    }
    $text = $string;
    $output = '';
    for ($i = 0; $i < strlen($text);) {
      for ($j = 0; ($j < strlen($key) && $i < strlen($text)); $j++, $i++) {
        $output .= $text{$i} ^ $key{$j};
      }
    }
    return $output;
  }
  /**
   * supplies a random alphanumeric key
   * 
   * the key is stored in a transient which changes every day
   * 
   * @return null
   */
  public static function get_key() {
    if (!$key = get_transient(Participants_Db::$prefix . 'captcha_key')) {
      set_transient(Participants_Db::$prefix . 'captcha_key', self::generate_key(), (60 * 60 * 24));
    }
    $key = get_transient(Participants_Db::$prefix . 'captcha_key');
    //error_log(__METHOD__.' get new key: '.$key);
    return $key;
  }
  /**
   * returns a random alphanumeric key
   * 
   * @param int $length number of characters in the random string
   * @return string the randomly-generated alphanumeric key
   */
  private static function generate_key($length = 8) {
    
    $alphanum = self::get_alpha_set();
    $key = '';
    while ($length > 0) {
      $key .= $alphanum[array_rand($alphanum)];
      $length--;
    }
    return $key;
  }
  /**
   * supplies an alphanumeric character set for encoding
   * 
   * characters that would mess up HTML are not included
   * 
   * @return array of valid characters
   */
  private static function get_alpha_set() {
    return str_split('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890.{}[]_-=+!@#$%^&*()~`');
  }
  /**
   * decodes the pdb_data_keys value
   * 
   * this provides a security measure by defining which fields to process in a form submission
   * 
   * @param string $datakey the pdb_data_key value
   * 
   * @return array of column names
   */
  public static function get_data_key_columns($datakey) {
    
    return self::get_indexed_names( explode('.', $datakey));
//    return self::get_indexed_names( explode('.', self::xcrypt($datakey)));
  }
  
}

?>
