<?php

/*
 * class for handling the display of images
 *
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdeign@xnau.com>
 * @copyright  2011, 2013 xnau webdesign
 * @license    GPL2
 * @version    0.3
 * @link       http://xnau.com/wordpress-plugins/
 *
 * functionality provided here:
 *   determine if file exists
 *   get image dimensions
 *   define a default image
 *   define an HTML wrapper and class
 *   provide a default image if image doesn't exist
 *   print image HTML with wrapper
 *
 * future development:
 *   cropping
 *   resizing
 *   watermarking
 *   
 */

abstract class xnau_Image_Handler {

  /**
   * true if the image file has been located and verified
   * 
   * @var bool
   */
  var $file_exists = false;
  /**
   * the the image defined and not a default image?
   *
   * @var bool true if the image file is defined and is not a default image
   */
  var $image_defined = false;
  /**
   * holds the image filename
   * 
   * @var string
   */
  var $image_file;
  /**
   * holds the URI to the image
   * @var string
   */
  var $image_uri;
  /**
   * holds the pixel width of the image
   * @var int
   */
  var $width = 0;
  /**
   * holds the pixel height of the image
   * @var int
   */
  var $height = 0;
  /**
   * the CSS classname for the image
   * @var string
   */
  var $classname;
  /**
   * the image wrap HTML
   * @var array first element is open tag, second is close tag
   */
  var $image_wrap;
  /**
   *
   * @var string holds the path to the default image
   */
  var $default_image = false;
  /**
   * @var string the path to the image directory
   */
  var $image_directory;
  /**
   * @var string the URL for the image directory
   */
  var $image_directory_uri;
  /**
   * @var string class name for an undefined image
   */
  var $emptyclass = 'no-image';
  /**
   * @var string class name for a default image
   */
  var $defaultclass = 'default-image';
  /**
   * determines the display mode for the returned HTML:
   *    image - shows the image (default)
   *    filename - shows the filename
   *    both - shows the image and the filename
   * 
   * @var string the current display mode
   */
  var $display_mode;

  /**
   * the href value for the link
   * 
   * if bool false, no link is shown, if empty, the file URL is used, otherwise, 
   * the string value is used
   * @var mixed
   */
  var $link;
  /**
   * the calling module
   * 
   * @var string
   */
  var $module;

  /**
   * intializes the object with a setup array
   *
   * @param array $config
   *                     'filename' => an image path, filename or URL
   *                     'classname' => a classname for the image
   *                     'wrap_tags' => array of open and close HTML
   *                     'link' => URI for a wrapping anchor tag
   *                     'mode' => display mode: as an image or a filename or both
   *                     'module' => calling module
   */
  function __construct($config) {

    $this->set_image_directory();

    $this->set_default_image();
    
    $this->image_file = isset($config['filename']) ? $config['filename'] : '';
    $this->link = isset($config['link']) ? $config['link'] : '';
    $this->classname = isset($config['classname']) ? $config['classname'] : 'image-field-wrap';
    $this->display_mode = isset($config['mode']) ? $config['mode'] : 'image';
    $this->module = isset($config['module']) ? $config['module'] : '';

    $this->_file_setup();
    $this->set_image_wrap(isset($config['wrap_tags']) and is_array($config['wrap_tags']) ? $config['wrap_tags'] : '');

  }

  /**
   * prints the HTML
   *
   */
  public function print_image()
  {
    $this->set_image_wrap();

    echo $this->get_image_html();
  }

  /**
   * supplies the HTML for the image
   * 
   * @return string HTML
   */
  public function get_image_html() {

    if ($this->file_exists) {
      switch ($this->display_mode) {
        case 'both':
          $pattern = '%1$s<img src="%2$s" /><span class="image-filename">%4$s</span>%3$s';
          break;
        case 'filename':
          $pattern = '%1$s%4$s%3$s';
          break;
        case 'none':
          $pattern = '';
          break;
        case 'image':
        default:
          $pattern = '%1$s<img src="%2$s" />%3$s';
      }
    } else {
      $pattern = '%1$s%2$s%3$s';
      if ($this->in_admin()) $this->image_uri = $this->image_file;
    }

    return sprintf($pattern,
            sprintf($this->image_wrap[0], 
                    $this->classname, 
                    $this->link, 
                    basename($this->image_uri)
                    ), 
            $this->image_uri, 
            $this->image_wrap[1],
            $this->image_file
            );
  }

  /**
   * supplies the name of the image file
   *
   * @return string the image filename
   */
  public function get_image_file() {

    return $this->image_file;
  }

  /**
   * sets the default image path
   *
   */
  abstract function set_image_directory();

  /**
   * sets the default image source path
   *
   * @param string $image absolute path to the default image file
   */
  abstract function set_default_image($image = false);

  /**
   * process the filename to test it's validity, set it's path and find its properties
   *
   * sets the path to the file, sets dimensions, sets file_exists flag, sets the HTML 
   * class to indicate the type of filename supplied
   * 
   * @param unknown $filename if set, string image file name, possibly including a path
   */
  protected function _file_setup() {

    $status = 'untested';
    
    switch (true) {
      
      case (empty($this->image_file)):
        
        if (!$this->in_admin()) $status = $this->_showing_default_image();
        else $status = 'admin';
        break;
      
      case ($this->test_absolute_path_image($this->image_file)) :
        $status = 'absolute';
        $this->image_uri = $this->image_file;
        $this->image_file = basename($this->image_file);
        $this->file_exists = true;
        $this->image_defined = true;
        $this->_set_dimensions();
        break;
      
      default:

        /*
         * set the image file path with the full system path to the image
         * directory as defined in the plugin settings
         */
        $status = 'basename';
        $this->_testfile($this->concatenate_directory_path( $this->image_directory, basename($this->image_file), false ));
  
        // if we still have no valid image, drop in the default
        if (!$this->file_exists) {
         if (!$this->in_admin()) $status = $this->_showing_default_image($this->image_file);
         else $status = 'file-notfound';
        } else {
         $this->image_defined = true;
         $this->_set_dimensions();
        }
        
      
    }
    
    $this->classname .= ' ' . $status;
  }
  
  /**
   * sets up the image display if no image file is found
   *
   * @param string $filename the name of the file which wasn't found for the purpose
   *                         of showing what the db contains
   * @return string status
   */
  protected function _showing_default_image($filename = false){
        
        if (!empty($this->default_image)) {

          if ($filename) $this->image_file = basename($filename);
          else $this->image_file = '';
          $this->image_uri = $this->default_image;
          $this->_set_dimensions();
          $status = $this->defaultclass;
          $this->file_exists = true;
          
        } else {
          
          $this->image_uri = '';
          $this->image_file = '';
          $status = $this->emptyclass;
        }
        
        return $status;
    
  }

  /**
   * tests a file and sets properties if extant
   *
   * @param string $filename a path to a file, relative to the WP root
   *
   * sets the file_exists flag to true if the file exists
   */
  protected function _testfile($filename) {
    
    //error_log(__METHOD__.' testing:'.$filename.' getting:'.($this->_file_exists($filename)?'yes':'no'));

    if ($this->_file_exists($filename)) {
      
      $this->image_file = basename($filename);
      $this->image_uri = $this->image_directory_uri.$this->image_file;
      $this->file_exists = true;
    }
  }

  /**
   * does an image file exist?
   *
   * this is needed because on some systems file_exists() gives a false negative
   *
   * @param string $filepath a full system filepath to an image file or just a file name
   * @return bool true if the file exists
   *
   */
  protected function _file_exists($filepath) {
      
    //error_log(__METHOD__.' checking path:'.$filepath.' is_file:'.(is_file($filepath)?'yes':'no'));
    
    if (empty($filepath)) return false;

    // first use the standard function
    if (is_file($filepath)){
      return true;
    }

    /*
     * if we're testing the file using http
     * 
     * this is pretty slow and probably isn't needed, so we'll keep it commented out
     */
//    if (function_exists('stream_context_create')) {
//      
//      error_log(__METHOD__.' checking URL:'.$this->concatenate_directory_path($this->image_directory_uri,basename($filepath),false));
//
//      return $this->url_exists($this->concatenate_directory_path($this->image_directory_uri,basename($filepath),false));
//    }

    // we give up, can't find the file
    return false;
  }

  /**
   * uses file_get_contents to test if a file exists
   *
   * This must be used as a last resort, it can take a long time to get the
   * server's response in some cases
   *
   * @param string $url the absolute url of the file to test
   * @return bool
   */
  function url_exists($url) {
    
    $code = $this->get_http_response_code($url);
    
    return $code == 200;
  }
  
  /**
   * gets an HTTP response header
   * 
   * @param string $url the URI to test
   * @return int the final http response code
   */
  function get_http_response_code($url) {
    $options['http'] = array(
        'method' => "HEAD",
        'ignore_errors' => 1,
    );

    $context = stream_context_create($options);
    $body = file_get_contents($url, NULL, $context);
    $responses = $this->parse_http_response_header($http_response_header);
    
    $last = array_pop($responses);
    
    return $last['status']['code']; // last status code
  }
  /**
   * parse_http_response_header
   *
   * @param array $headers as in $http_response_header
   * @return array status and headers grouped by response, last first
   */
  function parse_http_response_header(array $headers) {
    $responses = array();
    $buffer = NULL;
    foreach ($headers as $header) {
      if ('HTTP/' === substr($header, 0, 5)) {
        // add buffer on top of all responses
        if ($buffer)
          array_unshift($responses, $buffer);
        $buffer = array();

        list($version, $code, $phrase) = explode(' ', $header, 3) + array('', FALSE, '');

        $buffer['status'] = array(
            'line' => $header,
            'version' => $version,
            'code' => (int) $code,
            'phrase' => $phrase
        );
        $fields = &$buffer['fields'];
        $fields = array();
        continue;
      }
      list($name, $value) = explode(': ', $header, 2) + array('', '');
      // header-names are case insensitive
      $name = strtoupper($name);
      // values of multiple fields with the same name are normalized into
      // a comma separated list (HTTP/1.0+1.1)
      if (isset($fields[$name])) {
        $value = $fields[$name] . ',' . $value;
      }
      $fields[$name] = $value;
    }
    unset($fields); // remove reference
    array_unshift($responses, $buffer);

    return $responses;
  }
  
  /**
   * tests an image at an absolute address
   * 
   * @param string $src absolute path to an image file to test
   * 
   * sets $file_exists to true if found
   */
  function test_absolute_path_image($src) {
    
    if ($this->test_url_validity($src) and false !== @getimagesize($src)) {
      return $this->file_exists = true;
    }
  }
  
  /**
   * test an absolute path for validity; must have both the http protocol and a filename
   *
   * @param string $url the path to test
   * @return bool
   */
  public function test_url_validity($url) {
    return 0 !== preg_match("#^https?://.+/.+\..{2,4}$#",$url);
  }

  /**
   * sets the dimension properties
   *
   */
  private function _set_dimensions() {

    $getimagesize = @getimagesize($this->image_uri);

    if (false !== $getimagesize) {

      $this->width = $getimagesize[0];
      $this->height = $getimagesize[1];
    }
  }

  /**
   * sets the image wrap HTML
   *
   * @param array $wrap_tags  the HTML to place before and after the image tag; 
   * %s is replaced with the classname
   */
  public function set_image_wrap($wrap_tags = array()) {

    if (!empty($wrap_tags)) {
      $this->image_wrap = array(
          $wrap_tags[0],
          $wrap_tags[1],
          );
    } else $this->_set_image_wrap (); 
  }

  /**
   * sets up the default wrap tags
   * 
   * @return null
   */
  protected function _set_image_wrap() {

    $this->image_wrap = array(
        '<span class="%s">',
        '</span>'
    );
  }
  
  /**
   * adds a final slash to a directory name if there is none
   * 
   * @param string $path the path to test for an end slash
   * @return string the $path with a slash at the end
   */
  public function end_slash( $path ) {
    
    return rtrim($path,'/').'/';
  }
  /**
   * makes sure there is one and only one slash between directory names in a
   * concatenated path, and it ends in a slash
   * 
   * @param string $path1    first part of the path
   * @param string $path2    second part of the path
   * @param bool   $endslash determines whether to end the path with a slash or not
   */
  public static function concatenate_directory_path( $path1, $path2, $endslash = true ) {
    
    return rtrim( $path1, '/' ) . '/' . ltrim( rtrim( $path2, '/' ), '/' ) . ( $endslash ? '/' : '' );
  }
  /**
   * indicates whether the user is in the admin section, taking into account that
   * AJAX requests look like they are in the admin, but they're not
   *
   * @return bool
   */
  public function in_admin() {
    
    return is_admin() && ! defined('DOING_AJAX');
  }

}