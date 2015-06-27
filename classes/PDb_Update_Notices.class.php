<?php

/*
 * class description
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2015  xnau webdesign
 * @license    GPL2
 * @version    0.1
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

class PDb_Update_Notices {
  /**
   * @var string holds the root plugin file path
   */
  private $plugin_file_path;
  /**
   * @var object the response object
   */
  private $response;
  /**
   * @var string latest version
   */
  static $latest_version = '1.6';
  /**
   * @var string minimum WP version
   */
  static $min_version = '4.1';
  /**
   * @var string tested version
   */
  static $tested_version = '4.2.2';
  /**
   * @var testing switch
   */
  private $testmode = false;
  /**
   * @var string url to the readme
   */
  private $readme_url;
  /**
   * 
   * @param string $plugin_file_path
   */
  public function __construct($plugin_file_path)
  {
    $this->plugin_file_path = $plugin_file_path;
    $this->readme_url = $this->testmode ? 'http://wp.xnau.dev/content/plugins/participants-database/readme.txt' : 'http://plugins.svn.wordpress.org/participants-database/trunk/readme.txt?format=txt';
    // checks for a plugin update
    add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_plugin_update'));
    /*
     * custom upgrade details window
     */
    add_filter('plugins_api', array($this, 'plugin_update_info'), 10, 3);
    /*
     * this adds a custom update message to the plugin list 
     */
    global $pagenow;
    if ( 'plugins.php' === $pagenow )
    {
      $plugin_path = plugin_basename( $this->plugin_file_path );
      $hook = "in_plugin_update_message-" . $plugin_path;
      add_action( $hook, array($this, 'plugin_update_message'), 20, 2 );
    }
  }
  /**
   * prints a plugin update message
   * 
   * this is seen in the plugins list
   * 
   * @param array $plugin_data
   * @param object $response
   * @return string $output
   */
  public function plugin_update_message($plugin_data, $response)
  {

    $upgrade_notice = $this->upgrade_notice();

    $upgrade_notice = preg_replace('#(==?[^=]+==?)#', '', $upgrade_notice);

    $upgrade_notice = preg_replace('#(\*\*([^*]+)\*\*)#', '<span style="color:#BC0B0B">\2</span>', $upgrade_notice);
    
    // we got all that info, but really we just need to print the message we got from the readme
    
    echo wpautop(self::format_markdown($upgrade_notice));
  }
  /**
   * creates the update notice for this version
   * 
   * @param object $response
   * @return object
   */
  public function check_for_plugin_update($checkdata)
  {

    if (empty($checkdata->checked)) {
      return $checkdata;
    }

    $response = $this->response();
    
    $upgrade_notice = $this->upgrade_notice();
    
    $response->upgrade_notice = preg_replace('#(==?[^=]+==?)#', '', $upgrade_notice);
    
    $checkdata->response[plugin_basename($this->plugin_file_path)] = $response;

    //error_log(__METHOD__ . ' data returned:' . print_r($checkdata->response, 1));

    return $checkdata;
  }
  /**
   * creates the update notice for this version
   * 
   * @param boolean $false
   * @param array $action
   * @param object $arg
   * @return bool|object
   */
  public function plugin_update_info($false, $action, $arg)
  {
    
    if ($arg->slug !== Participants_Db::PLUGIN_NAME) return false;

    $plugin_data = get_plugin_data($this->plugin_file_path);
    $response = $this->response();
    $response->version = $plugin_data['Version'];
    $response->homepage = $plugin_data['PluginURI'];
    $response->sections = array(
        'description' => wpautop(self::format_markdown('This WordPress plugin is for the purpose of creating a simple database for use on a WordPress site. It is primarily intended as a way to manage information pertaining to people such as the members of a club or team, volunteers, students, anything like that. It gives you the ability to allow people to create and edit their own record while additional information can be managed by administrators or managers. The plugin may also be used as the basis for an index, directory or catalog.

The database is made up of fields, and each field may be one of several types that are uniquely suited to store a particular kind of information. These fields can also be divided into groups to help organize the information. Fields can also be provided with help text to assist users in providing the information.

[How Does Participants Database Work?](http://xnau.com/work/wordpress-plugins/participants-database/how-does-participants-database-work/)
')),
        'changelog' => wpautop(self::format_markdown($this->upgrade_notice())),
    );

    //error_log(__METHOD__ . ' data returned:' . print_r($response, 1));

    return $response;
  }
  /**
   * defines the response object
   * 
   * this is used to generate update notices
   */
  private function response()
  {
    return (object) array(
                'slug' => Participants_Db::PLUGIN_NAME,
                'name' => Participants_Db::$plugin_title,
                'new_version' => self::$latest_version, 
                'requires' => self::$min_version,  
                'tested' => self::$tested_version,
                'upgrade_notice' => $this->upgrade_notice(),
                'package' => 'https://downloads.wordpress.org/plugin/participants-database.' . self::$latest_version . '.zip',
                'url' => 'http://wordpress.org/plugins/participants-database/',
    );
  }
  /**
   * gets the upgrade notice from the trunk readme
   * 
   * @param bool $html whether to format the response in html or markup
   * 
   * @return string the upgrade notice text
   */
  private function upgrade_notice($html = false)
  {
    
    // readme contents
    $data = file_get_contents( $this->readme_url );
    
    $changelog = stristr($data, '== Upgrade Notice ==');
    $changelog = stristr($changelog, '== Using the Plugin ==', true);
    
    return $changelog;
  }
  /**
   * super simple markdown to HTML converter
   * 
   * only supports two headings and linked text
   * 
   * @param string $markdown the markdown text
   * 
   * @return string html
   */
  public static function format_markdown($markdown)
  {
    return preg_replace(
            array(
                '/(==\s([^=]*)\s==)/', 
                '/(=\s([^=]*)\s=)/', 
                '/\[([^\]]*)\]\s?\(([^)]*)\)/'
                ), 
            array(
                '<h2>$2</h2>', 
                '<h3>$2</h3>', 
                '<a href="$2">$1</a>'
            ), $markdown);
  }
}

?>
