<?php

/*
 * class for handling the display of images
 *
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdeign@xnau.com>
 * @copyright  2011 xnau webdesign
 * @license    GPL2
 * @version    0.2
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

abstract class Image_Handler {

  // holds the setup array
  protected $setup;
  // holds the "file found" status
  var $file_exists = false;
  // holds the image filename
  protected $image_file;
  // holds the URI to the image
  protected $image_uri;
  // holds the pixel dimensions of the image
  var $width = 0;
  var $height = 0;
  // holds the CSS classname for the image
  protected $classname;
  // hold the wrap tags for the image HTML
  protected $open = '';
  protected $close = '';
  // holds the default wrap tags
  protected $default_wrap;
  // holds the path to the default image
  protected $default_image = false;
  // holds the path to the image directory
  protected $image_directory;
  // holds the URL for the image directory
  protected $image_directory_uri;
  // class name for an undefined image, default image
  var $emptyclass = 'no-image';
  var $defaultclass = 'default-image';

  /**
   * intializes the object with a setup array
   *
   * @param array $setup
   *                     'filename' => an image path, filename or URL
   *                     'classname' => a classname for the image
   *                     'wrap_tags' => array of open and close HTML
   */
  function __construct($config, $subclass_atts) {

    $this->set_image_directory();

    $this->_set_default_wrap($config);

    $defaults = array(
        'filename' => '',
        'classname' => 'image-field-wrap',
        'wrap_tags' => $this->default_wrap,
    );

    $this->setup = shortcode_atts(array_merge($defaults, $subclass_atts), $config);

    $this->_set_props();

    $this->set_default_image();

    $this->_file_setup();
  }

  /**
   * prints the HTML
   *
   */
  public function print_image() {

    echo $this->get_image_html();
  }

  /**
   * returns the HTML for the image
   */
  public function get_image_html() {

    $pattern = $this->file_exists ?
            (
             $this->in_admin() ?
                    '%1$s<img src="%2$s" class="PDb-list-image" /><span class="image-filename">%4$s</span>%3$s' :
                    '%1$s<img src="%2$s" class="PDb-list-image" />%3$s'
            ) :
            '%1$s%2$s%3$s';

    return sprintf($pattern,
            sprintf($this->open, 
                    $this->classname, 
                    $this->image_uri, 
                    basename($this->image_uri)
                    ), 
            $this->image_uri, 
            $this->close,
            $this->image_file
            );
  }

  /**
   * returns the name of the image file
   *
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
   * sets the default image path
   *
   * @param string $image absolute path to the default image file
   */
  abstract function set_default_image($image = false);

  /**
   * process the filename to test it's validity, set it's path and find its properties
   *
   * sets the path to the file, sets dimensions, sets file_exists flag
   */
  protected function _file_setup($filename = false) {

    if (false === $filename)
      $filename = $this->setup['filename'];
    
    $status = 'untested';
    
    switch (true) {
      
      case (empty($filename)):
        
        $status = $this->_showing_default_image();
        break;
      
      case ($this->test_absolute_path_image($filename)) :
        $status = 'absolute';
        $this->image_file = basename($filename);
        $this->image_uri = $filename;
        $this->file_exists = true;
        $this->_set_dimensions();
        break;
      
      default:

        /*
         * set the image file path with the full system path to the image
         * directory as defined in the plugin settings
         */
        $filename = $this->concatenate_directory_path( $this->image_directory, basename($filename), false );
  
        $status = 'basename';
        $this->_testfile($filename);
  
        // if we still have no valid image, drop in the default
        if (!$this->file_exists) {
         $status = $this->_showing_default_image($filename);
        } else {
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
    
    if (empty($filepath)) return false;

    // first use the standard function
    if (is_file($filepath)){
      return true;
    }

    /*
     * if we're testing an absolute pate
     */
    if (function_exists('curl_exec')) {

      // check the header with cURL
      return $this->url_exists($this->concatenate_directory_path($this->image_directory_uri,basename($filepath),false));
    }

    // we give up, can't find the file
    return false;
  }

  /**
   * uses cURL to test if a file exists
   *
   * This must be used as a last resort, it can take a long tome to get the
   * server's response in some cases
   *
   * @param string $url the absolute url of the file to test
   * @return bool
   */
  function url_exists($url) {

    $handle = curl_init($url);
    if (false === $handle)
      return false;
    curl_setopt($handle, CURLOPT_HEADER, false);
    curl_setopt($handle, CURLOPT_FAILONERROR, true);
    curl_setopt($handle, CURLOPT_HTTPHEADER, Array("User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.15) Gecko/20080623 Firefox/2.0.0.15")); // request as if Firefox    
    curl_setopt($handle, CURLOPT_NOBODY, true);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, false);
    $connectable = curl_exec($handle);
    curl_close($handle);
    return $connectable;
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
   * @param string $open  the HTML to place before the image tag; %s is replaced
   *                      with the classname
   * @param string $close the HTML to put after the image tag
   */
  public function set_image_wrap($open, $close) {

    $this->open = $open;
    $this->close = $close;
  }

  /**
   * sets up the object properties from the construct parameters
   *
   */
  private function _set_props() {

    $this->classname = $this->setup['classname'];
    $this->set_image_wrap($this->setup['wrap_tags'][0], $this->setup['wrap_tags'][1]);
  }

  /**
   * sets up the default wrap tags
   */
  protected function _set_default_wrap($config) {

    $this->default_wrap = array(
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