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
 * @version    0.1
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
  function __construct( $config, $subclass_atts ) {
    
    $this->_set_default_wrap();
    
    $defaults = array(
                      'filename' => '',
                      'classname' => 'image-field-wrap',
                      'wrap_tags' => $this->default_wrap,
                      );
    
    $this->setup = shortcode_atts( array_merge( $defaults, $subclass_atts ), $config );
    
    $this->set_image_directory();
    
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
    
    $pattern = $this->file_exists ? '%s<img src="%s" />%s' : '%s%s%s';
    
    return sprintf( $pattern, sprintf( $this->open, $this->classname, $this->image_uri, basename($this->image_uri) ), $this->image_uri, $this->close );
    
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
  abstract function set_default_image( $image );
  
  /**
   * process the filename to test it's validity, set it's path and find its properties
   *
   * sets the path to the file, sets dimensions, sets file_exists flag
   */
  protected function _file_setup( $filename = false ) {
    
    if ( ! $filename ) $filename = $this->setup['filename'];
    
    $status = 'absolute';
      
    // test the filename as-is in case it's an absolute path
    if ( ! $this->_testfile( $filename ) ) {
      
      // test the default image path as defined in the sttings
      $filename = $this->image_directory.basename( $filename );
      
      $status = 'basename';
    
    }
    
    // if we still have no valid image, drop in the default
    if ( ! $this->file_exists and ! $this->_testfile( $filename ) and $this->default_image ) {
      
      $filename = $this->default_image;
      
      $status = $this->defaultclass;
      
    }
    
    // no valid filename found
    if ( ! $this->file_exists and ! $this->_testfile( $filename ) ) {
        
      $this->image_uri = '';
      $this->image_file = '';
      
      $status = $this->emptyclass;
      
    }
    
    $this->classname .= ' '.$status;
    
    if ( $this->file_exists ) {
      
      $this->_set_dimensions();
      
    }
    
  }
  
  /**
   * tests a file and sets properties if extant
   *
   * @param string $filename a path to a file, relative to the WP root
   */
  protected function _testfile( $filename ) {
      
      if ( $this->_file_exists( $filename ) ) {
        
        $this->image_uri = $filename;
        $this->image_file = basename( $filename );
        $this->file_exists = true;
        
        return true;
        
      }
      
      return false;
    
  }
  
  /**
   * does an image file exist
   *
   * this is needed because on some systems file_exists() gives a false negative
   *
   */
  protected function _file_exists( $filename ) {
    
    // first use the standard function
    if ( is_file( $filename ) ) return true;
    
    if ( function_exists('curl_exec') ) {
      
      // check the header with cURL
      return $this->url_exists( $filename );
      
    }
    
    // we give up, can't find the file
    return false;
    
  }
  
  /**
   * uses cURL to test if a file exists
   */
  function url_exists($url) {
    
    $handle = curl_init($url);
    if (false === $handle) return false;
    curl_setopt($handle, CURLOPT_HEADER, false);
    curl_setopt($handle, CURLOPT_FAILONERROR, true);
    curl_setopt($handle, CURLOPT_HTTPHEADER, Array("User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.15) Gecko/20080623 Firefox/2.0.0.15") ); // request as if Firefox    
    curl_setopt($handle, CURLOPT_NOBODY, true);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, false);
    $connectable = curl_exec($handle);
    curl_close($handle);   
    return $connectable;
  
  }
  
  /**
   * seta the dimension properties
   *
   */
  private function _set_dimensions() {
    
    $getimagesize = @getimagesize( $this->image_uri );
    
    if ( false !== $getimagesize ) {
    
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
  public function set_image_wrap( $open, $close ) {
    
    $this->open = $open;
    $this->close = $close;
    
  }
  
  /**
   * sets up the object properties from the construct parameters
   *
   */
  private function _set_props() {
    
    $this->classname = $this->setup['classname'];
    $this->set_image_wrap( $this->setup['wrap_tags'][0], $this->setup['wrap_tags'][1] );
    
  }
  
  /**
   * sets up the default wrap tags
   */
  protected function _set_default_wrap() {
    
    $this->default_wrap = array(
                        '<span class="%s">',
                        '</span>'
                        );
    
  }
  
  
}