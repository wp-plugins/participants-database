<?php
/*
 * class for handling the display of an individual image
 *
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdeign@xnau.com>
 * @copyright  2015 xnau webdesign
 * @license    GPL2
 * @version    0.3
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    Image_Handler class
 */
if ( ! defined( 'ABSPATH' ) ) die;
class PDb_Image extends xnau_Image_Handler {
  
  /**
   * intializes the object with a setup array
   *
   * @param array $config an array of optional parameters:
   *                     'filename' => an image path, filename or URL
   *                     'classname' => a classname for the image
   *                     'wrap_tags' => array of open and close HTML
   *                     'link' URI for a wrapping anchor tag
   *                     'relstring' => string to show in the "rel" attribute of the image
   *                     'mode' => display mode: as an image or a filename or both
   *                     'module' => calling module
   */
  function __construct( $config ) {
    
    parent::__construct($config);
    
    $this->classname .= ' ' . Participants_Db::$prefix . 'image image-field-wrap';
    
    if (empty($this->link)) {
      $this->link = $this->image_defined && Participants_Db::plugin_setting_is_true('image_link') ? $this->image_uri : '';
    }
    
  }
  
  /**
   * sets the default path to the image directory
   *
   */
  public function set_image_directory() {
    $this->image_directory = Participants_Db::set_filter('image_base_path', PDb_Path::files_path(), $this);
    $this->image_directory_uri = Participants_Db::set_filter('image_base_uri', PDb_Path::files_uri(), $this);

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
    
    $default_image = trailingslashit(PDb_Path::base_url()) . ltrim($this->default_image, '/');
    
//    error_log(__METHOD__.' setting: '.$this->default_image.' full: '.$default_image);
    
    if ( !empty($this->default_image) and @getimagesize( $default_image ) ) {
      
      $this->default_image = $default_image;
      
    } else $this->default_image = false; 
    
  }
  
  /**
   * sets up the image wrap html
   * 
   * this gives us a chance to structure the wrap based on the image file
   * 
   * @return null
   */
  protected function _set_image_wrap() {
    
    if($this->link === false || empty($this->link)) {
      
      $this->image_wrap = array(
          '<span class="%1$s">',
          '</span>'
      );
      
    } elseif ( Participants_Db::$plugin_options['image_link'] == 1 and $this->image_defined) {
      
      $this->image_wrap = array(
                        '<span class="%1$s"><a href="%2$s" rel="%4$s" title="%3$s" >',
                        '</a></span>'
                        );

    } elseif (!empty($this->link) and $this->link !== false) {
      
      $this->image_wrap = array(
                        '<span class="%1$s single-record-link"><a href="%2$s" title="%3$s" >',
                        '</a></span>'
                        );
      
    }
    
  }
  
}