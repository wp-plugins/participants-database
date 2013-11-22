<?php

/**
 * parent class for auxiliary plugins to the Participants Database Plugin
 *
 * the main function here is to establish a connection to the parent plugin and
 * provide some common functionality
 * 
 * Requires PHP Version 5.3 or greater
 * 
 * @category   
 * @package    WordPress
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2012 xnau webdesign
 * @license    GPL2
 * @version    Release: 3.0
 * @link       http://wordpress.org/extend/plugins/participants-database/
 */
if (!class_exists('PDb_Aux_Plugin')) :
class PDb_Aux_Plugin {

  /**
   * boolean true if the Participants Database plugin is found and active
   * @var bool
   */
  var $connected = false;
  /**
   * the directory and path of the main plugin file
   * @var string
   */
  var $plugin_path;
  /**
   * holds the path to the parent plugin
   * @var string
   */
  var $parent_path;
  /**
   * name of the instantiating subclass
   * @var string
   */
  var $subclass;
  /**
   * slug of the aux plugin
   * @var string
   */
  var $aux_plugin_name;
  /**
   * title of the aux plugin
   * @var string
   */
  var $aux_plugin_title;
  /**
   * slug of the aux plugin settings page
   * @var string
   */
  var $settings_page;
  /**
   * name of the WP option used for the plugin settings
   * @var string
   */
  var $aux_plugin_settings;
  /**
   * holds the plugin's options
   * @var array
   */
  var $plugin_options;
  /**
   * holds the plugin info fields as parsed from the main plugin file header
   * @var array
   */
  var $plugin_data;
  /**
   * the updater class instance for this plugin
   * @var object
   */
  var $Updater;
  /**
   * 
   * this is typically instantiated in the child class with: 
   * parent::__construct(__CLASS__, __FILE__);
   * 
   * @param string $subclass name of the instantiating subclass
   * @param string $plugin_file absolute path
   */

  function __construct($subclass, $plugin_file)
  {

    $this->plugin_path = plugin_basename($plugin_file);
    $this->connected = $this->check_connection();
    register_activation_hook($plugin_file, array($this, '_activate_plugin'));

    if($this->connected) {
      $this->plugin_data = get_plugin_data($plugin_file);
      $this->aux_plugin_settings = $this->aux_plugin_name;
      $this->subclass = $subclass;
      $this->set_settings_containers();
      $this->plugin_options = get_option($this->aux_plugin_settings);
      add_action('admin_menu', array($this, 'add_settings_page'));
      add_action('admin_init', array($this, 'settings_api_init'));
      add_action('init', array(&$this, 'initialize_updater'));
    }
  }
  
  /**
   * checks for a valid connection to the parent plugin
   * 
   * @return bool
   */
  function check_connection() {
    // find the path to the parent plugin
    $active_plugins = get_option('active_plugins');
    foreach ($active_plugins as $plugin_file) {
      if (false !== stripos($plugin_file, 'participants-database.php')) {
        return true;
      }
    }
    return false;
  }

  /**
   * initializes the update class
   * 
   */
  function initialize_updater() {
    $this->Updater = new PDb_Update($this->plugin_path, $this->plugin_data['Version']);
  }

  /**
   * sets the slug for the aux plugin settings page
   */
  function set_settings_containers()
  {
    $this->settings_page = Participants_Db::$plugin_page . '-' . $this->aux_plugin_name . '_settings';
    $this->aux_plugin_settings = $this->aux_plugin_shortname . '_settings';
  }

  /**
   * provides a simple activation method
   * 
   * this mainly checks for the existence of the parent plugin and notifies the 
   * user if it cannot be found
   * 
   */
  function _activate_plugin()
  {
    if (WP_DEBUG) error_log(__METHOD__. ' ' . Participants_Db::$plugin_title . ' activating aux plugin: '. $subclass);
    if(!$this->connected) {
      deactivate_plugins($this->plugin_path);
      $this->_trigger_error('The Participants Database plugin must be installed and activated for the ' . $this->aux_plugin_title . ' plugin to be activated.');
    }
  }

  /*********************************
   * plugin options section
   */
  function settings_api_init() {}
  
  /**
   * sets up the plugin settings page
   */
  function add_settings_page() {
    // create the submenu page
    add_submenu_page(
            Participants_Db::PLUGIN_NAME, 
            $this->aux_plugin_title . ' Settings', 
            $this->aux_plugin_title, 
            'manage_options', 
            $this->settings_page, 
            array($this,'render_settings_page')
            );
  }
  
  function _add_settings_sections($sections) {
    
    foreach($sections as $section) {
      // Add the section to reading settings so we can add our
      // fields to it
      add_settings_section(
              $section['slug'],
              $section['title'],
              array($this, 'setting_section_callback_function'),
              $this->aux_plugin_name
              );
    }
  }  
  /**
   * renders the plugin settings page
   * 
   * this generic rendering is expected to be overridden in the subclass
   */
  function render_settings_page() {
    ?>
    <div class="wrap" >
  
        <?php Participants_Db::admin_page_heading() ?>  
        <h2><?php echo $this->aux_plugin_title ?></h2>
  
        <?php settings_errors(); ?>  
  
        <form method="post" action="options.php">  
            <?php 
            settings_fields($this->aux_plugin_name . '_settings');
            do_settings_sections($this->aux_plugin_name);
            submit_button(); 
            ?>  
        </form>  
  
    </div><!-- /.wrap -->  
    <?php
    
    }
  
  /**
   * renders a section heading
   * 
   * this is expected to be overridden in the subclass
   * 
   * @param array $section information about the section
   */
  function setting_section_callback_function($section) {}

  /**
   * shows a setting input field
   * 
   * @param array $atts associative array of attributes (* required)
   *                      name    - name of the setting*
   *                      type    - the element type to use for the setting, defaults to 'text'
   *                      value   - preset value of the setting
   *                      title   - title of the setting
   *                      class   - classname for the settting
   *                      style   - CSS style for the setting element
   *                      help    - help text
   *                      options - an array of options for multiple-option input types (name => title)
   */
  function setting_callback_function($atts)
  {
    $options = get_option($this->aux_plugin_settings);
    $defaults = array(
        'name'    => '',                      // 0
        'type'    => 'text',                  // 1
        'value'   => $options[$atts['name']], // 2
        'title'   => '',                      // 3
        'class'   => '',                      // 4
        'style'   => '',                      // 5
        'help'    => '',                      // 6
        'options' => '',                      // 7
        'select'  => '',                      // 8
    );
    $fields = shortcode_atts($defaults, $atts);
    $fields['value'] = isset($options[$atts['name']]) ? $options[$atts['name']] : $atts['value'];
    // create an array of numeric keys
    $selectstring = $this->set_selectstring($fields['type']);
    for($i = 0;$i<count($defaults);$i++) $keys[] = $i;
    // replace the string keys with numeric keys in the order defined in $defaults
    $values = array_combine($keys,$fields);
    switch ($fields['type']){
      case 'text':
        $pattern = '<input name="' . $this->aux_plugin_settings . '[%1$s]" type="%2$s" value="%3$s" title="%4$s" class="%5$s" style="%6$s"  />';
        if (!empty($fields['help'])) $pattern .= '<p class="description">%7$s</p>';
        vprintf($pattern, $values);
        break;
      case 'textarea':
        $pattern = '<textarea name="' . $this->aux_plugin_settings . '[%1$s]" title="%4$s" class="%5$s" style="%6$s"  />%3$s</textarea>';
        if (!empty($fields['help'])) $pattern .= '<p class="description">%7$s</p>';
        vprintf($pattern, $values);
        break;
      case 'checkbox':
        $values[8] = $fields['value'] == 1 ? $selectstring : '';
        $pattern = '<input name="' . $this->aux_plugin_settings . '[%1$s]" type="hidden" value="0" />
<input name="' . $this->aux_plugin_settings . '[%1$s]" type="%2$s" value="1" title="%4$s" class="%5$s" style="%6$s" %9$s />';
        if (!empty($fields['help'])) $pattern .= '<p class="description">%7$s</p>';
        vprintf($pattern, $values);
        break;
      case 'radio':
        $pattern = '<label title="%4$s"><input type="%2$s" %9$s value="%3$s" name="' . $this->aux_plugin_settings . '[%1$s]"> <span>%4$s</span></label><br />';
        echo '<div class="' . $fields['type'] . ' ' . $fields['class'] . '" >';
        foreach ($fields['options'] as $name => $title) {
          $values[8] = $fields['value'] == $name ? $selectstring : '';
          $values[2] = $name;
          $values[3] = $title;
          vprintf($pattern, $values);
        }
        echo '</div>';
        if (!empty($fields['help'])) echo '<p class="description">' . $fields['help'] . '</p>';
        break;
    }
  }
  
  /**
   * sets the select string
   * 
   * define a select indicator string fro form elements that offer multiple slections
   * 
   * @param string the form element type
   */
  private function set_selectstring($type) {
    switch ($type) {
      case 'radio':
      case 'checkbox':
        return 'checked="checked"';
      case 'dropdown':
        return 'selected="selected"';
      default:
        return '';
    }
  }
  
  /**
   * adds a setting to the Settings API
   * 
   * @param array $atts an array of settings parameters
   * @return null
   * 
   */
  protected function add_setting($atts) {
    
    $default = array(
        'type' => 'text',
        'name' => '',
        'title' => '',
        'default' => '',
        'help' => '',
        'options' => '',
        'style' => '',
        'class' => '',
    );
    $params = shortcode_atts($default, $atts);

    add_settings_field(
            $params['name'], 
            $params['title'],
            array($this, 'setting_callback_function'),
            $this->aux_plugin_name,
            $this->aux_plugin_shortname . '_setting_section',
            array(
                'type'  => $params['type'],
                'name'  => $params['name'],
                'value' => isset($this->plugin_options[$params['name']]) ? $this->plugin_options[$params['name']] : $params['default'],
                'title' => $params['title'],
                'help'  => $params['help'],
                'options' => $params['options'],
                'style' => $params['style'],
                'class' => $params['class'],
                )
            );
  }

  /**
   * shows an error message in the admin
   */
  function _trigger_error($message, $errno = E_USER_ERROR)
  {
    if(isset($_GET['action']) and false !== stripos($_GET['action'], 'error_scrape')) {
      
      error_log('Plugin Activation Failed: ' . $_GET['plugin']);

      echo($message);

      exit;
    } else {

      trigger_error($message, $errno);
    }
  }

}
endif;
?>