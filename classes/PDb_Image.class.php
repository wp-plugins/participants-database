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
 * @depends    
 */
class PDb_Image {
  
  // properties
  
  // holds the setup array
  private $setup;
  
  // holds the "file found" status
  public $file_exists = false;
  
  // holds the image filename
  private $image_file;
  
  // holds the URI to the image
  private $image_uri;
  
  // holds the pixel dimensions of the image
  // index 0: width; index 1: height
  private $dimensions;
  
  // holds the CSS classname for the image
  private $classname;
  
  // hold the wrap tags for the image HTML
  private $open = '';
  private $close = '';
  
  // holds the path to the default image
  private $default_image = false;
  
  // class name for an undefined image
  var $emptyclass = 'empty';
  
  // methods
  
  /**
   * intializes the object with a setup array
   *
   * @param array $setup
   *                     'filename' => an image path, filename or URL
   *                     'classname' => a classname for the image
   *                     'wrap_tags' => array of open and close HTML
   */
  function __construct( $config ) {
    
    $defaults = array(
                      'filename' => '',
                      'classname' => 'pdb-image image-field-wrap',
                      'wrap_tags' => array(
                        '<span class="%s">',
                        '</span>'
                        ),
                      );
    
    $this->setup = shortcode_atts( $defaults, $config );
    
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
    
    return sprintf( $pattern, sprintf( $this->open, $this->classname ), $this->image_uri, $this->close );
    
  }
  
  /**
   * returns the name of the image file
   *
   */
  public function get_image_file() {
    
    return $this->image_file;
  
  }
  
  /**
   * process the filename to test it's validity, set it's path and find its properties
   *
   * sets the path to the file, sets dimensions, sets file_exists flag
   */
  private function _file_setup( $filename = false ) {
    
    if ( ! $filename ) $filename = $this->setup['filename'];
      
    // test the filename as-is
    if ( ! $this->_testfile( $filename ) ) {
      
      // test the default image path as defined in the sttings
      $filename = get_bloginfo('wpurl').'/'.Participants_Db::$plugin_options['image_upload_location'].basename( $filename );
    
    }
    
    // if we still have no valid image, drop in the default
    if ( ! $this->file_exists and ! $this->_testfile( $filename ) ) {
      
      $filename = $this->default_image;
      
    }
    
    // no valid filename found
    if ( ! $this->file_exists and ! $this->_testfile( $filename ) ) {
        
        $this->image_uri = '';
        $this->image_file = '';
      
    }
    
    if ( $this->file_exists ) {
      
      error_log( __METHOD__.'  file exists');
      
      $this->_set_dimensions();
      
    } else {
      
      // add the empty class
      $this->classname = $this->classname.' '.$this->emptyclass;
      
    }
    
  }
  
  /**
   * tests a file and sets properties if extant
   *
   * @param sting $filename an absolute path to a file
   */
  private function _testfile( $filename ) {
      
      if ( $this->_file_exists( $filename ) ) {
        
        $this->image_uri = $filename;
        $this->image_file = basename( $filename );
        $this->file_exists = true;
        
        return true;
        
      }
      
      return false;
    
  }
  
  /**
   * seta the dimension properties
   *
   */
  private function _set_dimensions() {
    
    $getimagesize = @getimagesize( $this->image_uri );
    
    if ( false !== $getimagesize ) {
    
      foreach ( array(0,1) as $i ) {
        
        $this->dimensions[$i] = $getimagesize[$i];
        
      }
      
    }
    
  }
  
  /**
   * sets the default image path
   *
   * @param string $image absolute path to the default image file
   */
  public function set_default_image( $image = false ) {
    
    if ( ! $image ) {
      
      $this->default_image = Participants_Db::$plugin_options['default_image'];
      
    } else {
      
      $this->default_image = $image;
    }
    
    if ( ! file_exists( $this->default_image ) ) $this->default_image = false;
    
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
   * sets up the object properties from the constrict parameters
   *
   */
  private function _set_props() {
    
    $this->classname = $this->setup['classname'];
    $this->set_image_wrap( $this->setup['wrap_tags'][0], $this->setup['wrap_tags'][1] );
    
  }
  
  /**
   * does an image file exist
   *
   * this is needed because on some systems file_exists() gives a false negative
   *
   */
  private function _file_exists( $filename ) {
    
    // first use the standard function
    if ( file_exists( $filename ) ) return true;
    
    $basename = basename( $filename );
    
    if ( ! empty( $basename ) and function_exists('curl_exec') ) {
      
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
    
    $handle   = curl_init($url);
    if (false === $handle)
    {
        return false;
    }
    curl_setopt($handle, CURLOPT_HEADER, false);
    curl_setopt($handle, CURLOPT_FAILONERROR, true);
    curl_setopt($handle, CURLOPT_HTTPHEADER, Array("User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.15) Gecko/20080623 Firefox/2.0.0.15") ); // request as if Firefox    
    curl_setopt($handle, CURLOPT_NOBODY, true);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, false);
    $connectable = curl_exec($handle);
    curl_close($handle);   
    return $connectable;
  
  }
  
  
}