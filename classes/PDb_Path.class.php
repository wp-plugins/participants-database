<?php

/*
 * manages and makes available various environment paths
 * 
 * the basic purpose here is to allow the plugin to operate in a number of different WordPress directory schemes 
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2015  xnau webdesign
 * @license    GPL2
 * @version    0.2
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

class PDb_Path {
  /**
   * provides the WP installation root absolute path
   * 
   * equivalent to web root + wp install directory (if any)
   * 
   * this uses constants, so it's not filterable, but the constants (if customized) 
   * are defined in the config file, so should be accurate for a particular installation
   * 
   * this works by finding the common path to both ABSPATH and WP_CONTENT_DIR which 
   * we can assume is the base install path of WP, even if the WP application is in 
   * another directory and/or the content directory is in a different place
   * 
   * @return string
   */
  public static function app_base_path() {
    $content_path = explode('/', WP_CONTENT_DIR);
    $wp_app_path = explode('/', ABSPATH);
    $end = min(count($content_path), count($wp_app_path));
    $i = 0;
    $common = array();
    while ($content_path[$i] === $wp_app_path[$i] and $i < $end) {
      $common[] = $content_path[$i];
      $i++;
    }
    return trailingslashit(implode('/', $common));
  }
  /**
   * finds the name of the content directory
   * 
   * @return string the name of the content directory
   */
  public static function app_content_dir() {
    return self::trim_slash(str_replace(self::app_base_path(), '', WP_CONTENT_DIR));
  }
  /**
   * finds the name of the WP includes dir
   * 
   * if the WP includes are in a different directory, this finds it's name
   * 
   * @return string
   */
  public static function app_includes_dir() {
    return self::trim_slash(str_replace(self::app_base_path(), '', ABSPATH));
  }
  /**
   * supplies an image/file upload location
   * 
   * relative to WP root
   * 
   * @return string relative path to the plugin files location
   */
  public static function files_location() {
    /**
     * @version 1.6.0
     * filter: pdb-files_location
     * 
     * allows access to the "image_upload_location" plugin setting value
     */
    return Participants_Db::set_filter('files_location', Participants_Db::plugin_setting('image_upload_location'));
  }
  /**
   * supplies the absolute path to the files location
   * 
   * @return string
   */
  public static function files_path() {
    return self::combine(self::app_base_path(), self::app_content_dir(), str_replace(self::app_content_dir(), '', self::files_location()));
  }
  /**
   * supplies the absolute path to the files location
   * 
   * @return string
   */
  public static function files_uri() {
    return self::combine( self::base_url(), self::files_location());
  }
  /**
   * provides the base URL
   * 
   * the is the base url for the WP install, usually it's the same as what site_url() 
   * provides, but allows for an alternate directory structure where the WP application 
   * files are in a different directory
   * 
   * @return string
   */
  public static function base_url() {
    
    return self::app_includes_dir() === '' ? site_url() : preg_replace('#/'.self::app_includes_dir() . '$#', '/', site_url() );
  }
  /**
   * supplies a complete path to an image or file asset
   * 
   * @param string the name of the file
   */
  public static function asset_path($file) {
    return self::combine(self::files_path(), $file);
  }
  /**
   * supplies a complete uri to an image or file asset
   * 
   * @param string name of the file
   */
  public static function asset_uri($file) {
    return self::combine(self::files_uri(), $file);
  }
  /**
   * removes the leading or trailing slash from a string
   * 
   * @param string $string
   * @return string
   */
  public static function trim_slash($string) {
    return trim($string, '/');
  }
  /**
   * combines a series of terms into a path
   * 
   * accepts any number of arguments
   * 
   * @param string $arg
   * 
   * @return string the combined path with a slash at the end
   */
  public static function combine($arg) {
    $arg_list = func_get_args();
    $path = trailingslashit(array_shift($arg_list));
    foreach ($arg_list as $arg) {
      $path .= trailingslashit(self::trim_slash($arg));
    }
    return $path;
  }
  

  /**
   * attempt to create the uploads directory
   *
   * sets an error if it fails
   * 
   * @param string $dir the name of the new directory
   */
  public static function _make_uploads_dir($dir = '') {

    $dir = empty($dir) ? self::files_location() : $dir;
    $savedmask = umask(0);
    $status = true;
    if (mkdir(self::app_base_path() . $dir, 0755, true) === false) {

      if (is_object(Participants_Db::$validation_errors))
        Participants_Db::$validation_errors->add_error('', sprintf(__('The uploads directory (%s) could not be created.', 'participants-database'), $dir));

      $status = false;
    }
    umask($savedmask);
    return $status;
  }
}

?>
