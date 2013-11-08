<?php

/*
 * this static class provides a set of utility functions used throughout the plugin
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2011 xnau webdesign
 * @license    GPL2
 * @version    0.5
 * @link       http://xnau.com/wordpress-plugins/
 */

class PDb_Base {
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
  public function parse_filter_string($filter) {
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

      if (!empty($value))
        $empty = false;
      $prepped_array[$key] = Participants_Db::_prepare_string_mysql($value);
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

    return htmlspecialchars(stripslashes($string), ENT_QUOTES, 'utf-8');
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

    // is_serialized is a WordPress utility function
    return is_serialized($string) ? unserialize($string) : $string;
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
   * sets up an API filter
   * 
   * determines if a filter has been set for the given tag, then either filters 
   * the term or returns it unaltered
   * 
   * this function also allows for two extra parameters
   * 
   * @param string $tag the slug of the plugin API filter
   * @param unknown $term the term to filter
   * @return unknown the filtered or unfiltered term
   */
  public static function set_filter($tag, $term)
  {
    $tag = Participants_Db::$prefix . $tag;
    if (!has_filter($tag)) {
      return $term;
    }
    $var1 = '';
    $var2 = '';
    $args = func_get_args();
    if (count($args) > 2) {
      list($tag, $term, $var1, $var2) = $args;
    }
    return apply_filters($tag, $term, $var1, $var2);
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
    chdir(ABSPATH . Participants_Db::$plugin_options['image_upload_location']);
    return unlink(basename($filename));
  }

  /**
   * makes a title legal to use in anchor tag
   */
  public static function make_anchor($title)
  {

    return str_replace(' ', '', preg_replace('#^[0-9]*#', '', strtolower($title)));
  }

  // returns boolean to question of whether the user is authorized to see / edit 
  // administrative fields
  public static function backend_user()
  {

    // contributor and above
    return current_user_can('edit_posts') ? true : false;
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
   * @param string $format_type selected the format type to translate to: 'ICU', 'jQuery'
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
  static function get_jQuery_date_format($PHP_date_format = '')
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
			$_SESSION['admin_message'] = array($message, $type);
			Participants_Db::$admin_message = $message;
			Participants_Db::$admin_message_type = $type;
		}
  }

  /**
   * sets the admin message
   */
  public static function admin_message()
  {
    if (isset($_SESSION['admin_message'])) {
      list(Participants_Db::$admin_message, Participants_Db::$admin_message_type) = $_SESSION['admin_message'];
      if (!empty(Participants_Db::$admin_message)) {
        printf('<div class="%s"><p>%s</p></div>', Participants_Db::$admin_message_type, Participants_Db::$admin_message);
        unset($_SESSION['admin_message']);
      }
    }
  }

}

?>
