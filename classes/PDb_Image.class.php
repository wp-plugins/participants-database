<?php
/*
 * class for handling the display of images for the participants database plugin
 *
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdeign@xnau.com>
 * @copyright  2011 xnau webdesign
 * @license    GPL2
 * @version    0.1
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    Image_Handler class
 */
class PDb_Image extends Image_Handler {
  
  /**
   * intializes the object with a setup array
   *
   * @param array $config an array of optional parameters:
   *                     'filename' => an image path, filename or URL
   *                     'classname' => a classname for the image
   *                     'wrap_tags' => array of open and close HTML
   */
  function __construct( $config ) {
    
    $subclass_atts = array(
                      'classname' => Participants_Db::$css_prefix . 'image image-field-wrap',
                      );
    
    parent::__construct( $config, $subclass_atts );
    
  }
  
  /**
   * sets the default path to the image directory
   *
   */
  public function set_image_directory() {

    $this->image_directory = $this->concatenate_directory_path( ABSPATH, Participants_Db::$plugin_options['image_upload_location'] );
    $this->image_directory_uri = $this->concatenate_directory_path( site_url(), Participants_Db::$plugin_options['image_upload_location'] );
  }
  
  /**
   * defines the default image
   *
   * @param string $image path to the default image file, relative to the WP root
   */
  public function set_default_image( $image = false ) {
    
    if ( ! $image ) {
      
      $this->default_image = Participants_Db::$plugin_options['default_image'];
      
    } else {
      
      $this->default_image = $image;
      
    }
    
    // check that the file exists, then set the absolute path
    if ( !empty($this->default_image) and is_file( $this->concatenate_directory_path( ABSPATH, $this->default_image, false ) ) ) {
      
      $this->default_image = $this->concatenate_directory_path( site_url(), $this->default_image, false );
      
    } else $this->default_image = false; 
    
  }
  
  /**
   * sets up the default wrap tags
   */
  protected function _set_default_wrap($config) {
    
    if ( Participants_Db::$plugin_options['image_link'] == 1 and $this->_file_exists($config['filename']) ) {
      
      $this->default_wrap = array(
                        '<span class="%s"><a href="%s" rel="lightbox" title="%s" >',
                        '</a></span>'
                        );

    } else {
      
      $this->default_wrap = array(
                        '<span class="%s">',
                        '</span>'
                        );
    }
    
  }
  
}