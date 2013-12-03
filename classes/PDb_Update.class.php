<?php

/*
 * class for handling update notices
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2011 xnau webdesign
 * @license    GPL2
 * @version    0.3
 * @link       http://xnau.com/wordpress-plugins/
 * 
 * adapted from: http://wp.tutsplus.com/tutorials/plugins/a-guide-to-the-wordpress-http-api-automatic-plugin-updates/
 */

class PDb_Update {

  /**
   * The plugin current version
   * @var string
   */
  public $current_version;

  /**
   * The plugin remote update path
   * @var string
   */
  public $update_path;

  /**
   * Plugin Slug (plugin_directory/plugin_file.php)
   * @var string
   */
  public $plugin_file;

  /**
   * Plugin name (plugin_file)
   * @var string
   */
  public $slug;

  /**
   * Initialize a new instance of the PDb_Update class
   * 
   * this would normally be instantiated on the 'init' hook:
   * $updater = new PDb_Update('participants-database/participants-database.php', '1.5', 'http://xnau.com/plugin_updates.php');
   * 
   * this will be handled in the PDb_Aux_Plugin class
   * 
   * @param string $plugin_file the directory and file of the plugin to be updated
   * @param string $current_version of the plugin
   * @param string $update_path
   */
  function __construct($plugin_file, $current_version, $update_path = 'http://xnau.com/plugin_updates.php')
  {
    // Set the class public variables
    $this->current_version = $current_version;
    $this->update_path = $update_path;
    $this->plugin_file = $plugin_file;
    list ($directory, $filename) = explode('/', $plugin_file);  
    $this->slug = str_replace('.php', '', $filename);

    // define the alternative API for updating checking
    add_filter('pre_set_site_transient_update_plugins', array(&$this, 'check_update'));

    // Define the alternative response for information checking
    add_filter('plugins_api', array(&$this, 'check_info'), 10, 3);
    
    // set the filter for getting an update message for display in the plugins list
    $hook = "in_plugin_update_message-" . $this->plugin_file;
    add_action( $hook, array(&$this, 'getRemote_update_message'), 20, 2 );
  }

  /**
   * Add our self-hosted autoupdate plugin to the filter transient
   *
   * @param $transient
   * @return object $ transient
   */
  public function check_update($transient)
  {
    if (empty($transient->checked)) {
      return $transient;
    }

    // Get the remote version
    $remote_version = $this->getRemote_version();

    // If a newer version is available, add the update
    if (version_compare($this->current_version, $remote_version, '<')) {
      $obj = new stdClass();
      $obj->slug = $this->slug;
      $obj->new_version = $remote_version;
      $obj->url = $this->update_path;
      $obj->package = $this->update_path;
      $transient->response[$this->plugin_file] = $obj;
    }
    //var_dump($transient);
    return $transient;
  }

  /**
   * Add our self-hosted description to the filter
   *
   * @param boolean $false
   * @param array $action
   * @param object $arg
   * @return bool|object
   */
  public function check_info($false, $action, $arg)
  {
    if ($arg->slug === $this->slug) {
      $information = $this->getRemote_information();
      return $information;
    }
    return false;
  }

  /**
   * Return the remote version
   * @return string $remote_version
   */
  public function getRemote_version()
  {
    $request = wp_remote_post($this->update_path, array('body' => array('action' => 'version')));
    if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {
      return $request['body'];
    }
    return false;
  }

  /**
   * Get information about the remote version
   * @return bool|object
   */
  public function getRemote_information()
  {
    $request = wp_remote_post($this->update_path, array('body' => array('action' => 'info')));
    if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {
      return unserialize($request['body']);
    }
    return false;
  }

  /**
   * Return the status of the plugin licensing
   * @return boolean $remote_license
   */
  public function getRemote_license()
  {
    $request = wp_remote_post($this->update_path, array('body' => array('action' => 'license')));
    if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {
      return $request['body'];
    }
    return false;
  }
  
  /**
   * gets the plugin update message
   * @return bool|object
   */
  public function getRemote_update_message($data, $response)
  {
    $request = wp_remote_post($this->update_path, array('body' => array('action' => 'message')));
    if (!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {
      echo $request['body'];
    }
    echo '';
  }

}

?>
