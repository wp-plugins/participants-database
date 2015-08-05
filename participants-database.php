<?php
/**
 * Plugin Name: Participants Database
 * Plugin URI: http://xnau.com/wordpress-plugins/participants-database
 * Description: Plugin for managing a database of participants, members or volunteers
 * Author: Roland Barker
 * Version: 1.6.2.6
 * Author URI: http://xnau.com
 * License: GPL2
 * Text Domain: participants-database
 * Domain Path: /languages
 */

/*
 * Copyright 2011, 2012, 2013, 2014, 2015 Roland Barker xnau webdesign  (email : webdesign@xnau.com)
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 */
if (!defined( 'ABSPATH' ) ) exit;
// register the class autoloading
spl_autoload_register('PDb_class_loader');
/**
 * main static class for running the plugin
 * 
 * @category   WordPress Plugins
 * @package    wordPress
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2011 - 2015 7th Veil, LLC
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL2
 * @version    Release: 1.6
 * 
 */
class Participants_Db extends PDb_Base {
  
  /**
   *
   * unique slug for the plugin; this is same as the plugin directory name
   * 
   * @var string unique slug for the plugin
   */
  const PLUGIN_NAME = 'participants-database';

  /**
   *  display title
   * @var string
   */
  public static $plugin_title;
  /**
   * basename of the main participants index table
   * @var string
   */
  public static $participants_table;
  /**
   *  base name of the table for all associated values
   * @var string
   */
  public static $fields_table;
  /**
   * name of the table for groups defninitions
   * @var string
   */
  public static $groups_table;
  /**
   * to create a new database version, change this value to the new version number. 
   * This will trigger a database update in the PDb_Init class
   * 
   * @var string current Db version
   */
  public static $db_version = '1.0';
  /**
   * name of the WP option where the current db version is stored
   * @var string
   */
  public static $db_version_option = 'PDb_Db_version';
  /**
   *  current version of plugin
   * @var string
   */
  public static $plugin_version;
  /**
   * name of the WP plugin options
   * @var string
   */
  public static $participants_db_options;
  /**
   * name of the default settings option
   * @var string
   */
  public static $default_options;
  /**
   * plugin option values $name => $value
   * @var array
   */
  public static $plugin_options;
  /**
   * plugin settings object
   * @var object
   */
  public static $Settings;
  /**
   * name of the plugin admin page
   * @var string
   */
  public static $plugin_page;
  /**
   * path to the plugin root ditectory
   * @var string
   */
  public static $plugin_path;
  /**
   * absolute path to the uploads directory
   * @var string 
   */
  public static $uploads_path;
  /**
   * a general-use prefix to set a namespace
   *
   * @var string
   */
  public static $prefix = 'pdb-';
  /**
   * duplicate of $prefix for backwards compatibility
   * @var string
   */
  public static $css_prefix;
  /**
   * the PDb_FormValidation object
   * @var object
   */
  public static $validation_errors;
  /**
   * an admin status or error message
   * @var string
   */
  static $admin_message = '';
  /**
   * the type of admin message
   * @var string
   */
  static $admin_message_type;
  /**
   * name of the transient record used to hold the last recor
   * @var string
   */
  public static $last_record;
  /**
   * status code for the last record processed
   * @var string
   */
  public static $insert_status;
  /**
   * header to include with plugin email
   * @var strings
   */
  public static $email_headers;
  /**
   * enclosure character to use
   * @var string
   */
  public static $CSV_enclosure = '"';
  /**
   * list of reserved field names
   * @var array
   */
  public static $reserved_names = array('source', 'subsource', 'id', 'private_id', 'record_link', 'action', 'submit', 'submit-button', 'name', 'day', 'month', 'year', 'hour', 'date', 'minute', 'email-regex');
  /**
   * true while sending an email
   * @var bool
   */
  public static $sending_email = false;
  /**
   * set of internationalized words
   * @var array
   */
  public static $i18n = array();
  /**
   * the date format; defaults to the WP global setting, but can be changed within the plugin
   *
   * @var string
   */
  public static $date_format;
  /**
   * the last method used to parse a date
   * 
   * @var string
   */
  public static $date_mode;
  /**
   * index for tracking multiple instances of a shortcode
   * @var int
   */
  public static $instance_index = 1;
  /**
   * @var string name of the list pagination variable
   */
  public static $list_page = 'listpage';
  /**
   * set when a list search form is shown to prevent more than one form appearing on a page
   * @var bool
   */
  public static $search_set = false;
  /**
   * holds the WP session object
   * 
   * @var object
   */
  public static $session;
  /**
   * this is set once per plugin instantiation, then all instances are expected to use this instead of running their own queries
   * 
   * 
   * 
   * @var array of all field objects, indexed by field name
   */
  public static $fields;
  /**
   * @var string context string for the main submission nonce
   */
  public static $main_submission_nonce_key = 'main_submission';
  /**
   * @var int the number of characters to use in the private ID
   */
  public static $private_id_length = 5;
  
  /**
   * initializes the static class
   * 
   * sets up the class autoloading, configuration values, hooks, filters and shortcodes
   * 
   * @global object $wpdb
   */
  public static function initialize() {

    // set the plugin version
    self::$plugin_version = self::_get_plugin_data('Version');

    // define some locations
    self::$default_options = self::$prefix . 'default_options';
    self::$plugin_page = self::PLUGIN_NAME;
    self::$plugin_path = plugin_dir_path(__FILE__);
    // this is relative to the WP install
    self::$uploads_path = 'wp-content/uploads/' . self::PLUGIN_NAME . '/';

    self::$last_record = self::$prefix . 'last_record';
    self::$css_prefix = self::$prefix;

    self::$session = new PDb_Session();

    /* 
     * set up data source names
     * 
     * these can be modified later with a filter hook
     */
    global $wpdb;
    $table_basename = $wpdb->prefix . str_replace('-', '_', self::PLUGIN_NAME);
    self::$participants_table = $table_basename;
    self::$fields_table = $table_basename . '_fields';
    self::$groups_table = $table_basename . '_groups';
    self::$participants_db_options = self::PLUGIN_NAME . '_options';

    // install/deactivate and uninstall methods are handled by the PDB_Init class
    register_activation_hook(__FILE__, array('PDb_Init', 'on_activate'));
    register_deactivation_hook(__FILE__, array('PDb_Init', 'on_deactivate'));
    register_uninstall_hook(__FILE__, array('PDb_Init', 'on_uninstall'));

    add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array(__CLASS__, 'add_plugin_action_links') );
    add_filter( 'plugin_row_meta', array(__CLASS__, 'add_plugin_meta_links'), 10, 2 );

    // set the WP hooks to finish setting up the plugin
    add_action('init',                  array(__CLASS__, 'init'));
    add_action('wp',                    array(__CLASS__, 'remove_rel_link'));
    add_action('wp',                    array(__CLASS__, 'reset_shortcode_session'));
    add_filter('admin_body_class',      array(__CLASS__, 'add_admin_body_class'));
    add_filter('body_class',            array(__CLASS__, 'add_body_class'));
    add_action('admin_menu',            array(__CLASS__, 'plugin_menu'));
    add_action('admin_init',            array(__CLASS__, 'admin_init'));
    add_action('wp_enqueue_scripts',    array(__CLASS__, 'include_scripts'));
    add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_includes'));
    add_filter('wp_headers',            array(__CLASS__, 'control_caching'));
    add_filter(self::$prefix . 'translate_string',  array(__CLASS__, 'string_static_translation'), 20);

    // handles ajax request from list filter
    add_action('wp_ajax_pdb_list_filter',        array(__CLASS__, 'pdb_list_filter'));
    add_action('wp_ajax_nopriv_pdb_list_filter', array(__CLASS__, 'pdb_list_filter'));

    // define our shortcodes
    add_shortcode('pdb_record',        array(__CLASS__, 'print_shortcode'));
    add_shortcode('pdb_signup',        array(__CLASS__, 'print_shortcode'));
    add_shortcode('pdb_signup_thanks', array(__CLASS__, 'print_shortcode'));
    add_shortcode('pdb_request_link',  array(__CLASS__, 'print_shortcode'));
    add_shortcode('pdb_list',          array(__CLASS__, 'print_shortcode'));
    add_shortcode('pdb_single',        array(__CLASS__, 'print_shortcode'));
    add_shortcode('pdb_search',        array(__CLASS__, 'print_shortcode'));
    add_shortcode('pdb_total',         array(__CLASS__, 'print_shortcode'));

    /*
     * sets up the update notification
     * 
     */
    new PDb_Update_Notices(__FILE__);
}

  /**
   * performs a fix for some older versions of the plugin; does nothing with current plugins
   */
  public static function admin_init() {

    // if the setting was made in previous versions and is a slug, convert it to a post ID
    $regpage = isset(self::$plugin_options['registration_page']) ? self::$plugin_options['registration_page'] : '';
    if (!empty($regpage) && !is_numeric($regpage)) {

      self::$plugin_options['registration_page'] = self::get_id_by_slug($regpage);

      update_option(self::$participants_db_options, self::$plugin_options);
    }
  }
  /**
   * initializes the plugin in the WP environment, fired on the 'init' hook
   * 
   * @return null
   */
  public static function init() {
    
    // set the table names
    global $wpdb;
    /*
     * this filter allows a plugin to determine which db tables to use
     * 
     * this allows things like multilingual field definitions or possibly even multiple databases
     */
    self::$participants_table = self::set_filter('select_database_table', self::$participants_table);
    self::$fields_table = self::set_filter('select_database_table', self::$fields_table);
    self::$groups_table = self::set_filter('select_database_table', self::$groups_table);
    // also filter the name of the settings to use
    self::$participants_db_options = self::set_filter('select_database_table', self::$participants_db_options);
    /*
     * set up the base reference object arrays
     * 
     * this is to reduce the number of db queries
     */
    self::_setup_columns();
    
    self::load_plugin_textdomain(__FILE__);
    
    self::$plugin_title = __('Participants Database', 'participants-database');
    
    self::_set_i18n();
    /**
     * @version 1.6 filter pdb-private_id_length
     */
    self::$private_id_length = self::set_filter('private_id_length', self::$private_id_length);
    
    /*
     * checks for the need to update the DB
     * 
     * this is to allow for updates to occur in many different ways
     */
    if ( false === get_option( self::$db_version_option ) || get_option( self::$db_version_option ) != self::$db_version )
      PDb_Init::on_update();

    /*
     * instantiate the settings class; this only sets up the settings definitions, 
     * the WP Settings API may not be available at this point, so we register the 
     * settings on the 'admin_menu' hook
     */
    self::$Settings = new PDb_Settings();

    // get the plugin options array
    if (!is_array(self::$plugin_options)) {
      
      $default_options = get_option(self::$default_options);
      
      if (!is_array($default_options)) {

        $default_options = self::$Settings->get_default_options();

        add_option(self::$default_options, $default_options, '', false);
      }

      self::$plugin_options = array_merge($default_options, (array) get_option(self::$participants_db_options));
    }
    /*
     * normally, the custom CSS is written to a static css file, but on some systems, 
     * that doesn't work, so the fallback is to load the dynamic CSS file
     */
    if (self::_set_custom_css()) {
      $custom_css_file = 'PDb-custom.css';
    } else {
      $custom_css_file = 'custom_css.php';
    }
    
    /*
     * set the plugin date display format; uses the blog setting, which is localized
     * 
     * since version 1.5.5 we have not used the "strict date format" becuase that 
     * is for input purposes only. This format is used to display dates.
     * 
     * this property can be changed in a template if desired
     */
    self::$date_format = get_option('date_format');
    
    if (self::plugin_setting_is_true('html_email')) {
      $type = 'text/html; charset="' . get_option('blog_charset') . '"';
    } else {
      $type = 'text/plain; charset=us-ascii';
    }
    $email_headers = 
            "From: " . self::$plugin_options['receipt_from_name'] . " <" . self::$plugin_options['receipt_from_address'] . ">\n" .
            "Content-Type: " . $type . "\n";

    self::$email_headers = self::set_filter('email_headers', $email_headers);

    // this processes form submits before any output so that redirects can be used
    self::process_page_request();

//    if (!is_object(self::$Settings)) {
//      self::$Settings = new PDb_Settings();
//    }
//    self::$Settings->initialize();
    
    $option_version = self::$Settings->option_version();

    /*
     * register frontend scripts and stylesheets
     */
    wp_register_style('pdb-frontend', plugins_url('/css/participants-database.css', __FILE__), array('dashicons'));
    wp_register_style('custom_plugin_css', plugins_url('/css/' . $custom_css_file, __FILE__), null, $option_version);

    wp_register_script(self::$prefix.'shortcode', plugins_url('js/shortcodes.js', __FILE__), array('jquery'));
    wp_register_script(self::$prefix.'list-filter', plugins_url('js/list-filter.js', __FILE__), array('jquery'));
    wp_register_script(self::$prefix.'jq-placeholder', plugins_url('js/jquery.placeholder.min.js', __FILE__), array('jquery'));
    wp_register_script(self::$prefix.'otherselect', plugins_url('js/otherselect.js', __FILE__), array('jquery'));
    
    
    /*
     * register admin scripts and stylesheets
     */
    wp_register_script(self::$prefix.'cookie', plugins_url('js/jquery_cookie.js', __FILE__));
    wp_register_script(self::$prefix.'manage_fields',   plugins_url('js/manage_fields.js', __FILE__), array('jquery','jquery-ui-core','jquery-ui-tabs','jquery-ui-sortable','jquery-ui-dialog',self::$prefix.'cookie'), false, true);
    wp_register_script(self::$prefix.'settings_script', plugins_url('js/settings.js', __FILE__), array('jquery','jquery-ui-core','jquery-ui-tabs',self::$prefix.'cookie'), false, true);
    wp_register_script(self::$prefix.'record_edit_script', plugins_url('js/record_edit.js', __FILE__), array('jquery','jquery-ui-core','jquery-ui-tabs',self::$prefix.'cookie'), false, true);
    wp_register_script(self::$prefix.'jq-placeholder', plugins_url('js/jquery.placeholder.min.js', __FILE__), array('jquery'));
    wp_register_script('jq-doublescroll', plugins_url('js/jquery.doubleScroll.js', __FILE__), array('jquery', 'jquery-ui-widget'));
    wp_register_script(self::$prefix.'admin', plugins_url('js/admin.js', __FILE__), array('jquery','jq-doublescroll'));
    wp_register_script(self::$prefix.'otherselect', plugins_url('js/otherselect.js', __FILE__), array('jquery'));
    wp_register_script(self::$prefix.'list-admin', plugins_url('js/list_admin.js', __FILE__), array('jquery'));
    wp_register_script(self::$prefix.'debounce',        plugins_url('js/jq_debounce.js', __FILE__), array('jquery'));
    //wp_register_script( 'datepicker', plugins_url( 'js/jquery.datepicker.js', __FILE__ ) );
    //wp_register_script( 'edit_record', plugins_url( 'js/edit.js', __FILE__ ) );
    
    wp_register_style('pdb-utility', plugins_url('/css/xnau-utility.css', __FILE__));
    wp_register_style('pdb-global-admin', plugins_url('/css/PDb-admin-global.css', __FILE__), false, false);
    wp_register_style('pdb-frontend', plugins_url('/css/participants-database.css', __FILE__));
    wp_register_style('pdb-admin', plugins_url('/css/PDb-admin.css', __FILE__));
    
  }
  
  /**
   * sets up the plugin admin menus
   * 
   * @return null
   */
  public static function plugin_menu() {

    
    global $pagenow;
    if (($pagenow == 'admin.php' && filter_input(INPUT_GET, 'page') === 'participants-database_settings_page') || $pagenow == 'options.php') {
      /*
       * intialize the plugin settings for the plugin settings pages
       */
      self::$Settings->initialize();
    }

    /*
     * this allows the possibility of a child class handling the admin list display
     */
    $list_admin_classname = self::set_filter('admin_list_classname', 'PDb_List_Admin');

    // define the plugin admin menu pages
    add_menu_page(
            self::$plugin_title,
            self::$plugin_title, 
            self::plugin_capability('record_edit_capability', 'main admin menu'),
            self::PLUGIN_NAME, 
            null
    );
    
    add_submenu_page(
            self::PLUGIN_NAME, 
            __('List Participants', 'participants-database'), 
            __('List Participants', 'participants-database'), 
            self::plugin_capability('record_edit_capability', 'list participants'), 
            self::PLUGIN_NAME,//self::$plugin_page . '-list_participants', 
            array($list_admin_classname, 'initialize')
    );
    /**
     * this registers the edit participant page without adding it as a menu item
     */
    add_submenu_page(
            null, 
            null, 
            null, 
            self::plugin_capability('record_edit_capability', 'edit participant'), 
            self::$plugin_page . '-edit_participant', 
            array(__CLASS__, 'include_admin_file')
    );

    add_submenu_page(
            self::PLUGIN_NAME, 
            __('Add Participant', 'participants-database'), 
            __('Add Participant', 'participants-database'), 
            self::plugin_capability('record_edit_capability', 'add participant'), 
            self::$plugin_page . '-add_participant', 
            array(__CLASS__, 'include_admin_file')
    );

    add_submenu_page(
            self::PLUGIN_NAME, 
            __('Manage Database Fields', 'participants-database'), 
            __('Manage Database Fields', 'participants-database'), 
            self::plugin_capability('plugin_admin_capability', 'manage fields'), 
            self::$plugin_page . '-manage_fields', 
            array(__CLASS__, 'include_admin_file')
    );

    add_submenu_page(
            self::PLUGIN_NAME, 
            __('Import CSV File', 'participants-database'), 
            __('Import CSV File', 'participants-database'), 
            self::plugin_capability('plugin_admin_capability', 'upload csv'), 
            self::$plugin_page . '-upload_csv', 
            array(__CLASS__, 'include_admin_file')
    );

    add_submenu_page(
            self::PLUGIN_NAME, 
            __('Settings', 'participants-database'), 
            __('Settings', 'participants-database'), 
            self::plugin_capability('plugin_admin_capability', 'plugin settings'), 
            self::$plugin_page . '_settings_page', 
            array(self::$Settings, 'show_settings_form')
    );

    add_submenu_page(
            self::PLUGIN_NAME, 
            __('Setup Guide', 'participants-database'), 
            __('Setup Guide', 'participants-database'), 
            self::plugin_capability('plugin_admin_capability', 'setup guide'), 
            self::$plugin_page . '-setup_guide', 
            array(__CLASS__, 'include_admin_file')
    );
  }

  /**
   * processes the admin includes
   * 
   * uses WP hook 'admin_enqueue_scripts''
   * 
   * @param string $hook the admin menu hook as provided by the WP filter
   * @return null
   */
  public static function admin_includes($hook) {

    if (false !== stripos($hook, 'participants-database')) {
      wp_enqueue_script('jquery');
      wp_enqueue_script('jquery-ui-core');
      wp_enqueue_script('jquery-ui-tabs');
      wp_enqueue_script('jquery-ui-sortable');
      wp_enqueue_script('jquery-ui-dialog');
      wp_enqueue_script(self::$prefix.'cookie');
      wp_enqueue_script(self::$prefix.'jq-placeholder');
      wp_enqueue_script(self::$prefix.'admin');
      wp_enqueue_script(self::$prefix.'otherselect');
      wp_enqueue_script('jq-doublescroll');
    }

    if (false !== stripos($hook, 'participants-database-list_participants')) {
//      wp_localize_script(self::$prefix.'list-admin', 'list_adminL10n', array(
//          'delete' => PDb_List_Admin::$i18n['delete_checked'],
//          'cancel' => PDb_List_Admin::$i18n['change'],
//          "record" => __("Do you really want to delete the selected record?", 'participants-database' ),
//          "records" => __("Do you really want to delete the selected records?", 'participants-database' ),
//      ));
//      wp_enqueue_script(self::$prefix.'list-admin');
    }

    if (false !== stripos($hook, 'participants-database_settings_page')) {
      wp_enqueue_script(self::$prefix.'settings_script');
    }

    if (false !== stripos($hook, 'participants-database-edit_participant')) {
      //wp_enqueue_script(self::$prefix.'record_edit_script');
    }
    
    if (false !== stripos($hook, 'participants-database-manage_fields')) {
      wp_localize_script(self::$prefix . 'manage_fields', 'manageFields', array('uri' => $_SERVER['REQUEST_URI']));
      wp_localize_script(self::$prefix . 'manage_fields', 'PDb_L10n', array(
          /* translators: don't translate the words in brackets {} */
          'must_remove' => '<h4>' . __('You must remove all fields from the {name} group before deleting it.', 'participants-database') . '</h4>',
          'delete_confirm' => '<h4>' . __('Delete the "{name}" {thing}?', 'participants-database') . '</h4>',
          'unsaved_changes' => __("The changes you made will be lost if you navigate away from this page.")
      ));
      wp_enqueue_script(self::$prefix . 'manage_fields');
    }

    wp_enqueue_style('pdb-global-admin');
    wp_enqueue_style('pdb-utility');

    // only incude these stylesheets on the plugin admin pages
    if (false !== stripos($hook, 'participants-database')) {
      wp_enqueue_style('pdb-frontend');
      wp_enqueue_style('pdb-admin');
    }
  }

  /**
   * register all frontend JS and CSS
   * 
   * fired on WP hook 'wp_enqueue_scripts' the actual enqueueing happens in the PDb_Shortcode class
   * 
   * @return null
   */
  public static function include_scripts() {

  }

  /**
   * enqueues the frontend CSS
   * 
   * @return null
   */
  public static function include_stylesheets() {
    
    if (self::plugin_setting_is_true('use_plugin_css')) {
      wp_enqueue_style('pdb-frontend');
    }
    wp_enqueue_style('custom_plugin_css');
    
  }

  /**
   * enqueues the general plugin frontend scripts
   * 
   * @return null
   */
  public static function add_scripts() {
      wp_enqueue_script(self::$prefix.'shortcode');
      wp_enqueue_script(self::$prefix.'jq-placeholder');
      wp_enqueue_script(self::$prefix.'otherselect');
    }

  /**
   * includes files for generating plugin admin pages  
   * 
   * grabs the name from the request and includes the file to display the page; 
   * this is the admin submenu callback
   * 
   * @static
   * @return null
   */
  public static function include_admin_file()
  {

    $file = str_replace(self::$plugin_page . '-', '', filter_input(INPUT_GET, 'page', FILTER_SANITIZE_STRING)) . '.php';

    if (is_file(plugin_dir_path(__FILE__) . $file)) {

      // we'll need this in the included file
      global $wpdb;

      include $file;
    }
  }

  /**
   * shows the frontend edit screen called by the [pdb_record] shortcode
   *
   *
   * the ID of the record to show for editing can be provided one of three ways: 
   *    $_GET['pid'] (private link) or in the POST array (actively editing a record)
   *    $atts['id'](deprecated) or $atts['record_id'] (in the sortcode), or 
   *    self::$session->get('pdbid') (directly from the signup form)
   * 
   * 
   * @param array $atts array of attributes drawn from the shortcode
   * @return string the HTML of the record edit form
   */
  public static function print_record_edit_form($atts)
  {
    
    $record_id = false;
    // get the pid from the get string if given (for backwards compatibility)
    $get_pid = filter_input(INPUT_GET, 'pid', FILTER_SANITIZE_STRING);
    if (empty($get_pid)) {
      $get_pid = filter_input(INPUT_POST, 'pid', FILTER_SANITIZE_STRING);
    }
    if (!empty($get_pid)) {
      $record_id = self::get_participant_id($get_pid);
    }
    
    // get the id from the SESSION array
    if ($record_id === false && self::$session->get('pdbid')) {
      $record_id = self::get_record_id_by_term('id', self::$session->get('pdbid'));
    }

    if ($record_id === false && (isset($atts['id']) || isset($atts['record_id']))) {
      if (isset($atts['id']) & !isset($atts['record_id'])) {
        $atts['record_id'] = $atts['id'];
        unset($atts['id']);
       }
       $record_id = self::get_record_id_by_term('id', $atts['record_id']);
    }

    $atts['record_id'] = $record_id;

    return PDb_Record::print_form($atts);
  }

  /**
   * updates the "last_accessed" field in the database
   * 
   * @ver 1.5 added $wpdb->prepare()
   * 
   * @param int $id the record to update
   * @global $wpdb
   */
  private static function _record_access($id) {

    global $wpdb;

    $sql = 'UPDATE ' . self::$participants_table . ' SET `last_accessed` = NOW() WHERE `id` = %s';

    return $wpdb->query($wpdb->prepare($sql, $id));
  }

  /**
   * sets the last_accessed timestamp
   * 
   * @param int $id id of the record to update
   */
  public static function set_record_access($id) {

    self::_record_access($id);
  }
  
  /**
   * common function for printing all shortcodes
   * 
   * @param array $params array of paramters passed in from the shortcode
   * @param string $content the content of the enclosure (empty string; we don't use enclosure tags)
   * @param string $tag the shortcode identification string
   * @return null 
   */
  public static function print_shortcode($params, $content, $tag) {
    /**
     * @version 1.6
     * 
     * 'pdb-shortcode_call_{$tag}' filter allows the shortcode atrributes to be 
     * altered before instantiating the shortcode object
     */
    $shortcode_parameters = self::set_filter('shortcode_call_' . $tag, $params);
    
    switch ($tag) {
      case 'pdb_record':
        return self::print_record_edit_form($shortcode_parameters);
        break;
      case 'pdb_signup':
        return self::print_signup_form($shortcode_parameters);
        break;
      case 'pdb_signup_thanks':
        return self::print_signup_thanks_form($shortcode_parameters);
        break;
      case 'pdb_request_link':
        return self::print_retrieval_form($shortcode_parameters);
        break;
      case 'pdb_list':
        return self::print_list($shortcode_parameters);
        break;
      case 'pdb_single':
        return self::print_single_record($shortcode_parameters);
        break;
      case 'pdb_search':
        return self::print_search_form($shortcode_parameters);
        break;
      case 'pdb_total':
        return self::print_total($shortcode_parameters);
        break;
    }
  }


  /**
   * prints a "total" value
   * 
   * called by the "pdb_total" shortcode. this is to print a total number of records, 
   * the number of records passing a filter, or an arithmetic sum of all the data 
   * passing a filter.
   * 
   * @param array $params the parameters passed in by the shortcode
   * @return string the output HTML
   */
  public static function print_total($params) {
    
    $params['module'] = 'total';
    $params['list_limit'] = -1;

    return PDb_List::get_list($params);
  }


  /**
   * prints a single record called by [pdb_list] shortcode
   * 
   * @param array $params the parameters passed in by the shortcode
   * @return string the output HTML
   */
  public static function print_list($params) {
    
    $params['module'] = 'list';

    return PDb_List::get_list($params);
  }
  /**
   * prints a list search form
   * 
   * @param array $params the parameters passed in by the shortcode
   * @return string the output HTML
   */
  public static function print_search_form($params) {
    
    $params = (array) $params + array('module' => 'search', 'search' => true); 

    return PDb_List::get_list($params);
  }

  /**
   * prints a single record called by [pdb_single] shortcode
   * 
   * @param array $params the parameters passed in by the shortcode
   * @return string the output HTML
   */
  public static function print_single_record($params) {

    // alias the 'id' attribute for backwards compatibility
    if (isset($params['id']) & !isset($params['record_id'])) {
      $params['record_id'] = $params['id'];
      unset($params['id']);
    }
    if (isset($params['record_id'])) {
    $params['record_id'] = self::get_record_id_by_term('id', $params['record_id']);
    }

    return PDb_Single::print_record($params);
  }

  /**
   * prints a form from the Signup class
   * 
   * @param array $params the parameters from the shortcode
   * @return string the output HTML
   */
  public static function print_signup_class_form($params) {

    $params['post_id'] = get_the_ID();

    return PDb_Signup::print_form($params);
  }

  /**
   * prints a signup form
   * 
   * @param array $params the parameters passed in by the shortcode
   * @return string the output HTML
   */
  public static function print_signup_form($params) {
    
    $params['module'] = 'signup';

    return self::print_signup_class_form($params);  
  }

  /**
   * prints the signup thanks form
   * 
   * @param array $params the parameters passed in by the shortcode
   * @return string the output HTML
   */
  public static function print_signup_thanks_form($params) {
  
    $params['module'] = 'thanks';

    return self::print_signup_class_form($params);
  }

  /**
   * prints the private ID retrieval form
   * 
   * @param array $params the parameters passed in by the shortcode
   * @return string the output HTML
   */
  public static function print_retrieval_form($params)
  {

    $params['module'] = 'retrieve';

    return self::print_signup_class_form($params);
  }

  /**
   * sets up the $fields array
   * 
   * global $wpdb
   */
  private static function _setup_columns() {
    
    global $wpdb;
    self::$fields = array();
    $sql = 'SELECT v.* 
            FROM ' . self::$fields_table . ' v 
            ORDER BY v.order';
    $result = $wpdb->get_results($sql);
    foreach ($result as $column) {
      self::$fields[$column->name] = $column;
    }
    
  }

  /**
   * get all the attributes of a field by it's name
   * 
   * an attribute or comma-separated list of attributes can be specified if not, 
   * a default list of attributes is retrieved
   * 
   * @global object $wpdb
   * @param string $field the name of the field to get
   * @param string $atts depricated
   * @return stdClass 
   */
  public static function get_field_atts($field = false, $atts = '*') {

    return self::get_column($field);
    
  }

  /**
   * get an array of groups
   *
   * @param string $column comma-separated list of columns to get, defaults to all (*)
   * @param mixed $exclude single group to exclude or array of groups to exclude
   * @return array returns an associative array column => value or indexed array 
   *               if only one column is specified in the $column argument
   */
  public static function get_groups($column = '*', $exclude = false) {

    global $wpdb;

    $where = '';

    if ($exclude) {

      $where = ' WHERE `name` ';

      if (is_array($exclude)) {

        $where .= 'NOT IN ("' . implode('","', $exclude) . '") ';
      } else {

        $where .= '!= "' . $exclude . '" ';
      }
    }

    $sql = 'SELECT ' . $column . ' FROM ' . self::$groups_table . $where . ' ORDER BY `order`,`name` ASC';

    // are we looking for only one column?
    // if so, flatten the array
    if ($column !== '*' and false === strpos($column, ',')) {

      $output = array();

      foreach ($wpdb->get_results($sql, ARRAY_A) as $row)
        $output[] = $row[$column];

      return $output;
    } else {

      $group_index = array();

      $groups = $wpdb->get_results($sql, ARRAY_A);

      // build an array indexed by the group's name
      foreach ($groups as $group)
        $group_index[$group['name']] = $group;

      return $group_index;
    }
  }

  /**
   * gets the names of all the persistent fields
   * 
   * @return array of field names
   */
  public static function get_persistent() {

    return self::get_subset('persistent');
  }

  /**
   * gets a list of field names/titles
   * 
   * assembles a list of columns from those columns set to display. Optionally, 
   * a list of fields can be supplied with an array. This allows fields that are 
   * not displayed to be included.
   * 
   * as of 1.5 fields named in the $fields array don't need to have their 'sortable' 
   * flag set in order to be included.
   *
   * @param string $type   if 'sortable' will only select fields flagged as sortable  
   * @param array  $fields array of field names defining the fields listed for the 
   *                       purpose of overriding the default selection
   * @param string $sort   sorting method to use, can be 'order' which uses the
   *                       defined group/field order, 'column' which uses the
   *                       current display column order or 'alpha' which sorts the
   *                       list alphabetially; defaults to 'column'
   * @return array of form: title => name
   */
  public static function get_field_list($type = false, $fields = false, $sort = 'column') {

    global $wpdb;
    
    $where_clauses = array();
    if ($type == 'sortable' && !is_array($fields)) {
      $where_clauses[] = 'f.sortable > 0';
    }
    if (is_array($fields)) {
      $where_clauses[] = 'f.name IN ("' . implode('","',$fields) . '")';
    } elseif (! is_admin()) {
      $where_clauses[] = 'f.display_column > 0 ';
    }
    
    $where = empty($where_clauses) ? '' : "WHERE " . implode(' AND ', $where_clauses);

    switch ($sort) {
      case 'alpha':
        $sql = "
          SELECT f.name, REPLACE(f.title,'\\\','') as title
          FROM " . self::$fields_table . " f
          " . $where . "
          ORDER BY f.name";
        break;
      case 'order':
        $sql = "
          SELECT f.name, REPLACE(f.title,'\\\',''), g.order as title
          FROM " . self::$fields_table . " f
          INNER JOIN " . self::$groups_table . " g ON f.group = g.name
          " . $where . "
          ORDER BY g.order, f.order";
        break;
      default:
        $sql = "
          SELECT f.name, REPLACE(f.title,'\\\','') as title
          FROM " . self::$fields_table . " f
          " . $where . "
          ORDER BY f." . (is_admin() ? 'admin_column' : 'display_column');
    }

    $result = $wpdb->get_results($sql, ARRAY_N);

    // construct an array of this form: title => name
    $return = array();
    foreach ($result as $item) {
      if (isset($return[$item[1]])) {
        $key = self::title_key($item[1], $item[0]);
      } else {
        $key = self::title_key($item[1]);
      }
      $return[$key] = $item[0];
    }
    return $return;
  }

  /**
   * get the names of all the sortable fields
   * 
   * this checks the "sortable" column and collects the list of sortable columns
   * from those columns set to display. Optionally, a list of fields to include
   * can be supplied with an array. This allows fields that are not displayed to
   * be included.
   * 
   * @param array  $fields array of field names defining the fields listed for the 
   *                       purpose of overriding the default selection
   * @param string $sort   sorting method to use, can be 'order' which uses the
   *                       defined group/field order, 'column' which uses the
   *                       current display column order or 'alpha' which sorts the
   *                       list alphabetially; defaults to 'column'
   * @param return array
   */
  public static function get_sortables($fields = false, $sort = 'column') {

    return self::get_field_list('sortable', $fields, $sort);
  }

  /**
   * gets a subset of field names
   *
   * this function only works for boolean qualifiers or "column order" columns where
   * any number greater than 0 indicates the field is to be displayed in a column
   *
   * @param string the name of the qualifier to use to select a set of field names
   * @return array an indexed array of field names
   */
  private static function get_subset($subset) {

    global $wpdb;

    $sql = "
			SELECT `name`
			FROM " . self::$fields_table . "
			WHERE `" . $subset . "` > 0";

    $result = $wpdb->get_results($sql, ARRAY_N);

    // get the 2nd dimension of the array
    $return = array();
    foreach ($result as $item)
      $return[] = $item[0];

    return $return;
  }

  /**
   * gets a single column object
   * 
   * @param string $name the column name
   * @return object|bool false if no field defined for the given name
   */
  public static function get_column($name) {
    
    return isset(self::$fields[$name]) ? self::$fields[$name] : false;
    
  }

  /**
   * checks a string against active columns to validate input
   * 
   * @var string $string the name to test
   */
  public static function is_column($string) {

    return isset(self::$fields[$string]);

  }

  /**
   * checks a string against defined groups to validate a group name
   * 
   * @var string $string the name to test
   */
  public static function is_group($string) {

    global $wpdb;

    $sql = 'SELECT COUNT(*)
		        FROM ' . self::$groups_table . ' g
            WHERE g.name = %s';

    $count = $wpdb->get_var($wpdb->prepare($sql,trim($string)));

    return $count > 0;
  }

  /**
   * gets a set of field attributes as filtered by context
   *
   * @param string|array $filter sets the context of the display and determines the 
   *                             set of columns to return, also accepts an array of 
   *                             column names
   * @return object the object is ordered first by the order of the group, then 
   *                by the field order
   */
  public static function get_column_atts($filter = 'new') {

    global $wpdb;

    if (is_array($filter)) {
      $where = 'WHERE v.name IN ("' . implode( '","', $filter) . '")';
    } else {
    switch ($filter) {

      case 'signup':

          $where = 'WHERE v.signup = 1 AND v.form_element <> "placeholder"';
        break;

      case 'sortable':

          $where = 'WHERE v.sortable = 1 AND v.form_element <> "placeholder"';
        break;

      case 'CSV':

        $where = 'WHERE v.CSV = 1 ';
        break;

      case 'all':

        $where = '';
        break;

      case 'frontend_list':

        $where = 'WHERE v.display_column > 0 ';
        break;

        case 'frontend': // record and single modules

          $where = 'WHERE g.display = 1 AND v.form_element <> "placeholder"';
        break;

      case 'readonly':

          $where = 'WHERE v.group = "internal" OR v.readonly = 1';
        break;
      
      case 'backend':

          $where = 'WHERE v.name <> "id" AND v.form_element <> "captcha" AND v.form_element <> "placeholder"';
        if (!current_user_can(self::plugin_capability('plugin_admin_capability', 'access admin field groups'))) {
          // don't show non-displaying groups to non-admin users
          $where .= 'AND g.admin = 0';
        }
        break;

      case 'new':
      default:

          $where = 'WHERE v.name <> "id"  AND v.form_element <> "captcha"';
      }
    }

    $sql = 'SELECT v.*, g.order FROM ' . self::$fields_table . ' v INNER JOIN ' . self::$groups_table . ' g ON v.group = g.name ' . $where . ' ORDER BY g.order, v.order';

    return $wpdb->get_results($sql, OBJECT_K);
  }

  /**
   * builds an object of all participant values structured by groups and columns
   *
   * TODO: this function is DEPRICATED in favor of using the Shortcode class to render
   * shortcode output, but we have to leave it in here for the moment because
   * there may be modified templates using this function still in use
   * 
   * @param string $id the id number of the record
   * @param array $exclude an array of fields to ecplude
   * @return object containing all the field and their values, ordered by groups
   */
  public static function single_record_fields($id, $exclude = '') {

    global $wpdb;

    // get the groups object
    $sql = '
		    SELECT g.title, g.name, g.description  
		    FROM ' . self::$groups_table . ' g 
			WHERE g.display = 1 
			ORDER BY `order` ASC
			';

    $groups = $wpdb->get_results($sql, OBJECT_K);

    if (is_array($exclude)) {

      $excludes = "AND v.name NOT IN ('" . implode("','", $exclude) . "') ";
    } else
      $excludes = '';

    // add the columns to each group
    foreach ($groups as $group) {

      $group->fields = $wpdb->get_results('SELECT v.name, v.title, v.form_element 
                                            FROM ' . self::$fields_table . ' v
                                            WHERE v.group = "' . $group->name . '"
                                            ' . $excludes . '
																						AND v.form_element != "hidden"  
                                            ORDER BY v.order
                                            ', OBJECT_K);

      // now get the participant value for the field
      foreach ($group->fields as $field) {

        $field->value = current($wpdb->get_row("SELECT `" . $field->name . "`
                                         FROM " . self::$participants_table . "
                                         WHERE `id` = '" . $id . "'", ARRAY_N));
      } // fields
    }// groups

    return $groups;
  }

  /**
   * processes a form submit
   *
   * this processes all record form submissions front-end and back-
   * 
   * @global object $wpdb
   * 
   * @param array       $post           the array of new values (typically the $_POST array)
   * @param string      $action         the db action to be performed: insert or update
   * @param int|bool    $participant_id the id of the record to update. If it is false 
   *                                    or omitted, it creates a new record, if true, it 
   *                                    creates or updates the default record.
   * @param array|bool  $column_names   array of column names to process from the $post 
   *                                    array, if false, processes a preset set of columns
   *
   * @return int|bool   int ID of the record created or updated, bool false if submission 
   *                    does not validate
   */
  public static function process_form($post, $action, $participant_id = false, $column_names = false) {
    
    if (!isset($action) || !in_array($action, array('insert','update')) || ( isset($post['subsource']) && $post['subsource'] !== Participants_Db::PLUGIN_NAME ) ) return false;

    global $wpdb;

    if (!empty($_FILES) && !isset($_POST['csv_file_upload'])) {

      foreach ($_FILES as $fieldname => $attributes) {

        if (UPLOAD_ERR_NO_FILE == $attributes['error'])
          continue;

        $filepath = self::_handle_file_upload($fieldname, $attributes, $participant_id);

        if (false !== $filepath) {

          // place the path to the file in the field value
          $post[$fieldname] = $filepath;
          
          $_POST[$fieldname] = basename($filepath);
          
        }
      }
    }
    /*
     * checks for a record with a matching field so we can exercise the
     * duplicate record preference
     * 
     * 0 - create new record
     * 1 - update matching record
     * 2 - show validation error
     */
    if (isset($_POST['csv_file_upload'])) {
      // a CSV upload brings in it's own match preference
      $duplicate_record_preference = filter_input(INPUT_POST, 'match_preference', FILTER_SANITIZE_STRING);
      $match_field = filter_input(INPUT_POST, 'match_field', FILTER_SANITIZE_STRING);
    } else {
      $duplicate_record_preference = self::plugin_setting('unique_email', '0');
      $match_field = self::plugin_setting('unique_field','id');
    }
    if (is_admin() && self::current_user_has_plugin_role('admin', 'csv upload') && !isset($_POST['csv_file_upload'])) {
      /*
       * set the preference to 0 if current user is an admin in the admin and not 
       * importing a CSV
       * 
       * this allows administrators to create new records without being affected 
       * by the duplicate record preference
       */
      $duplicate_record_preference = '0';
    }
    
    if ($action == 'insert' and $duplicate_record_preference !== '0') {

      $match_field_value = isset($post[$match_field]) ? filter_var($post[$match_field], FILTER_SANITIZE_STRING) : '';
      
      $record_match = $match_field_value !== '' && self::field_value_exists($match_field_value, $match_field);
      // if true, the incoming record matches an existing record
      /**
       * @version 1.6
       * the $record_match status variable is made available to a filter so a custom 
       * record matching method can be implemented
       */
      $record_match = self::set_filter('incoming_record_match', $record_match, $post);

      if ($record_match) {

        /*
         * this prevents possible exposure of private data when using multipage forms
         * 
         * we don't allow the "update" preference for multipage forms
         */
        if (self::is_multipage_form()) {
          $duplicate_record_preference = '2';
        }
        /*
         * we have found a match
         */
        switch ($duplicate_record_preference) {

          case '1':

            // record with same field value exists...get the id and update the existing record
            if ('id' == strtolower($match_field))
              $participant_id = intval($match_field_value);
            else
              $participant_id = self::_get_participant_id_by_term($match_field, $match_field_value);
            // get the first one
            if (is_array($participant_id))
              $participant_id = current($participant_id);
            
            // set the update mode
            $action = 'update';
            // empty any private ID that signup assigned, the record will already have one
            $post['private_id'] = '';
            
            break;

          case '2':

            /*
             * this is skipped if we're in the middle of an unfinished multi-page form
             */
            if (self::is_multipage_form())  {
              // multipage form incomplete, update the current record with the new post data
              $participant_id = Participants_Db::$session->get('pdbid');
              $action = 'update';
            } else {
            // set the error message
              if (! is_object(self::$validation_errors)) {
                self::$validation_errors = new PDb_FormValidation();
              }
              self::$validation_errors->add_error($match_field, 'duplicate');
							$action = 'skip';
            }
            // go on validating the rest of the form
            break;
        }
      } elseif ( $duplicate_record_preference === '1' and strtolower($match_field) === 'id' and is_numeric($match_field_value) ) {
        /*
         * if the "OVERWRITE" option is set to "id" and the record contains an id, use it to create the record
         */
        $participant_id = intval($match_field_value);
        if ( $participant_id !== 0 ) {
          $action = 'insert';
        } else $participant_id = false;
      }
    } elseif ($action === 'insert' && $duplicate_record_preference === '0') {
      /*
       * if the setting is to add a record, matching or not, we don't allow a Record 
       * ID or a private ID to be stored, we let the plugin assign them so that they 
       * will be certain to be unique
       */
      unset($post['id']);
      $post['private_id'] = '';
    }
    // set the insert status value
    self::$insert_status = $action;

    switch ($action) {

      case 'update':
        $sql = 'UPDATE ' . self::$participants_table . ' SET `date_updated` = NOW(), ';
        $where = " WHERE id = " . $participant_id;
        break;

      case 'insert':
        $sql = 'INSERT INTO ' . self::$participants_table. ' SET ';
        if (self::import_timestamp(isset($post['date_recorded']) ? $post['date_recorded'] : '') === false) $sql .= ' `date_recorded` = NOW(), ';
        if (self::import_timestamp(isset($post['date_updated']) ? $post['date_updated'] : '') === false) $sql .= ' `date_updated` = NOW(), ';
        $where = '';
        break;

      case 'skip':
        return false;
    }

    /*
     * determine the set of columns to process
     * 
     */
    $new_values = array();
    $column_data = array();
    
    if (is_array($column_names)) {
      
      $column_set = $column_names;
    } else {

      if (filter_input(INPUT_POST, 'action') === 'signup') {
      
        $column_set = 'signup';
      } else {

        $column_set = $action == 'update' ? ( is_admin() ? 'backend' : 'frontend' ) : ( $participant_id ? 'all' : 'new' );
      }
    }
    
    $columns = self::get_column_atts($column_set);

    // gather the submit values and add them to the query
    foreach ($columns as $column) {
    
      // the validation object is only instantiated when this method is called
      // by a form submission
      if (is_object(self::$validation_errors)) {
        self::$validation_errors->validate(( isset($post[$column->name]) ? self::deep_stripslashes($post[$column->name]) : ''), $column, $post);
      }
      $new_value = false;
      // we can process individual submit values here
      switch ($column->name) {

        case 'id':
          $new_value = $participant_id;
          break;
        
        case 'date_recorded':
        case 'date_updated':
        case 'last_accessed':
          
          // remove the value from the post array if it is already set in the sql
          if (isset($post[$column->name]) && strpos($sql, $column->name) !== false) {
            unset($post[$column->name]);
          }
          /*
           * this func returns bool false if the timestamp is not present or is invalid, 
           * returns the MySQL timestamp string otherwise
           */
          $new_value = @self::import_timestamp($post[$column->name]);
          
          break;

        case 'private_id':
          if (isset($post['private_id']) && strlen($post['private_id']) == self::$private_id_length) $new_value = $post['private_id'];
          else $new_value = $action == 'insert' ? self::generate_pid() : false;
          
          break;

        default :
          
          if (!isset($post[$column->name])) {
            continue;
          }
          
          switch ($column->form_element) {

            case 'multi-select-other':
            case 'multi-checkbox':
              /* match the items in the comma-separated list against the preset
               * values of the multi-select. Any extra values are placed in an
               * 'other' array element
               */
              if (isset($post[$column->name])) {

                if (is_array($post[$column->name])) {
                
                  if ($column->form_element == 'multi-select-other' && $i = array_search('other', $post[$column->name])) {
                    unset($post[$column->name][$i]);
                  }
                
                  $value_array = $post[$column->name];
                  // delete the empty placeholder value
                  if (isset($value_array[0]) && $value_array[0] === '') unset($value_array[0]);
                  
              } else {

                // build the value array from the string form used in CSV files
                $value_array = array();
                  $incoming_value = preg_split('#([ ]*,[ ]*)#', trim($post[$column->name]));
                  $field_values = self::unserialize_array($column->values);
                
                foreach ($incoming_value as $v) {

                  if (in_array($v, $field_values)) {

                    $value_array[] = $v;
                  } else {

                    $value_array['other'][] = $v;
                  }
                }

                if (isset($value_array['other']) && is_array($value_array['other']))
                  $value_array['other'] = implode(',', $value_array['other']);
              }
              }
              else
                $value_array = array();

              $new_value = self::_prepare_array_mysql($value_array);
              break;

            case 'link':
              /* translate the link markdown used in CSV files to the array format used in the database
               */

              if (!is_array($post[$column->name])) {

                $new_value = self::_prepare_array_mysql(self::get_link_array($post[$column->name]));
              } else {

                $new_value = self::_prepare_array_mysql($post[$column->name]);
              }
              break;

            case 'rich-text':
              global $allowedposttags;
              $new_value = wp_kses(stripslashes($post[$column->name]), $allowedposttags);
              break;

            case 'date':

              if ($post[$column->name] !== '') {
                $date = self::parse_date($post[$column->name], $column, true);
              $new_value = $date ? $date : false;
              } else {
                $new_value = null;
              }
              break;

            case 'captcha':
              $new_value = false;
              break;

            case 'password':
              if (!empty($post[$column->name])) {
                $new_value = wp_hash_password(trim($post[$column->name]));
              } else {
                $new_value = false;
              }
              break;
              
            case 'image-upload':
            case 'file-upload':
              
              if (filter_input(INPUT_POST, $column->name . '-deletefile', FILTER_SANITIZE_STRING) === 'delete') {
                if (self::$plugin_options['file_delete'] == 1 or is_admin() ) {
                  self::delete_file($post[$column->name]);
                }
                unset($_POST[$column->name]);
                $post[$column->name] = '';
              }
              $new_value = self::_prepare_string_mysql(trim($post[$column->name]));
              break;

            default:
              
              if (!self::current_user_has_plugin_role('admin', 'readonly access') && $column->readonly != '0') {
                // this prevents unauthorized users from saving readonly field data
                $new_value = false;
              } elseif (is_array($post[$column->name])) {

                $new_value = self::_prepare_array_mysql($post[$column->name]);
              } else {

                $new_value = self::_prepare_string_mysql(trim($post[$column->name]));
              }
          } // switch column_atts->form_element
      }  // swtich column_atts->name 

      /*
       * add the column and value to the sql; if it is bool false, skip it entirely. 
       * Nulls are added as true nulls
       */
      if ($new_value !== false) {

        if ($new_value !== null) {
          $new_values[] = $new_value;
        }
        $column_data[] = "`" . $column->name . "` = " . ( $new_value === null ? "NULL" : "%s" );
      }

    } // columns

    // if the validation object exists and there are errors, stop here
    if (is_object(self::$validation_errors) && self::$validation_errors->errors_exist()) {

      // error_log( __METHOD__.' errors exist; returning');

      return false;
    } elseif ( !empty( self::$admin_message ) and 'error' == self::$admin_message_type ) {
      return false;
    }
    
    /*
     * @version 1.6
     * 
     * add in any missing default values
     */
    if ($action == 'insert') {
      $all_columns = self::get_default_record();
      unset($all_columns['private_id'],$all_columns['date_recorded'],$all_columns['date_updated']);
      foreach ($all_columns as $name => $value) {
        $find_result = preg_grep('/' . $name . '/', $column_data);
        if (count($find_result) === 0 && $value != '') {
          // if a field with a defined default value is missing from the submission, add it in
          $column_data[] = "`$name` = %s";
          $new_values[] = $value;
        }
      }
    }
    
    

    // add in the column names
    $sql .= implode(', ', $column_data);

    // add the WHERE clause
    $sql .= $where;

    if (WP_DEBUG)
      error_log(__METHOD__ . ' storing record sql=' . $sql . ' values:' . print_r($new_values, true));

    $result = $wpdb->query($wpdb->prepare($sql, $new_values));

    if ($result === 0) {
      $db_error_message = sprintf(self::$i18n['zero_rows_error'], $wpdb->last_query);
    } elseif ($result === false) {
      $db_error_message = sprintf(self::$i18n['database_error'], $wpdb->last_query, $wpdb->last_error);
    } else {
      // is it a new record?
      if ($action == 'insert') {

        // get the new record id for the return
        $participant_id = $wpdb->insert_id;

        /* 
         * is this record a new one created in the admin? This also applies to CSV 
         * imported new records
         */
        if (is_admin()) {
        // if in the admin hang on to the id of the last record for an hour
          set_transient(self::$last_record, $participant_id, (1 * 60 * 60 * 1));
        }
      }
    }
    /*
     * set up user feedback
     */
    if (!isset($_POST['csv_file_upload']) && is_admin()) {
      if ($result) {
    self::set_admin_message(($action == 'insert' ? self::$i18n['added'] : self::$i18n['updated']),'updated');
      } else {
        self::set_admin_message($db_error_message,'error');
      }
    }

    return $participant_id;
  }

  /**
   * parses the markdown string used to store the values for a link form element
   *
   * will also accept a bare URL. If the supplied string or URL does not validate 
   * as an URL, return the string
   *
   * @param string $markdown_string
   * @return array URL, linktext
   */
  public static function get_link_array($markdown_string)
  {

    if (preg_match('#^<([^>]+)>$#', trim($markdown_string), $matches)) {
      return array($matches[1], '');
    } elseif (preg_match('#^\[([^\]]+)\]\(([^\)]+)\)$#', trim($markdown_string), $matches)) {
      $url = filter_var($matches[2], FILTER_VALIDATE_URL) ? $matches[2] : '';
	  return array($url, $matches[1]);
    }
    else
      return filter_var($markdown_string, FILTER_VALIDATE_URL) ? array($markdown_string, '') : array('',$markdown_string);
    }

  /**
   * gets the default set of values
   * 
   * this does not include hidden fields
   * 
   * @version 1.6 placeholder elements are also excluded
   *
   * @global object $wpdb
   * @return array name=>value
   */
  public static function get_default_record() {

    $sql = 'SELECT f.name,f.default,f.form_element 
            FROM ' . self::$fields_table . ' f
            WHERE f.group != "internal" AND f.form_element != "placeholder"';

    global $wpdb;

    $result = $wpdb->get_results($sql);

    $default_record = array();

    foreach ($result as $column) {

      if ($column->form_element != 'hidden') $default_record[$column->name] = $column->default;
      
    }

    // get the id of the last record stored
    $prev_record_id = get_transient(self::$last_record);

    if ( is_admin() and $prev_record_id) {

      $previous_record = self::get_participant($prev_record_id);

      if ($previous_record) {

        $persistent_fields = self::get_persistent();

        foreach ($persistent_fields as $persistent_field) {

          if (!empty($previous_record[$persistent_field])) {

            $default_record[$persistent_field] = $previous_record[$persistent_field];
          }
        }
      }
    }
    
    // fill in some convenience values
    global $current_user;

//    if ( is_object( $current_user ) ) $default_record['by'] = $current_user->display_name;
//    $default_record['when'] = date_i18n(self::$date_format);
    $default_record['private_id'] = self::generate_pid();
    date_default_timezone_set(self::get_timezone());
    $default_record['date_recorded'] = date('Y-m-d H:i:s');
    /*
     * @version 1.6 stop setting date_updated on new record
     */
    // $default_record['date_updated'] = date('Y-m-d H:i:s');

    return $default_record;
  }

  /**
   * gets an array of record values
   *
   * as of 1.5.5 returns only registered columns
   *
   * @ver 1.5 added $wpdb->prepare
   * 
   * TODO: add db result caching
   *
   * @global object $wpdb
   * @param  string|bool $id the record ID; returns default record if omitted or bool false 
   * 
   * @return array|bool associative array of name=>value pairs; false if no record 
   *                    matching the ID was found 
   */
  public static function get_participant($id = false) {

    if (false === $id)
      return self::get_default_record();

    global $wpdb;

    $columns = array();
    foreach (self::$fields as $field) {
      $columns[] = $field->name;
    }

    $sql = 'SELECT p.' . implode(',p.', $columns) . ' FROM ' . self::$participants_table . ' p WHERE p.id = %s';

    $result = $wpdb->get_row($wpdb->prepare($sql, $id), ARRAY_A);
    
    if (is_array($result)) {
      return array_merge($result, array('id' => $id));
    } else {
      return false;
    }
  }

  /**
   * gets a participant id by private ID
   *
   * @param string $pid the private ID for a record
   * 
   * @return int|bool the record ID or false
   *
   */
  public static function get_participant_id($pid) {

    return self::_get_participant_id_by_term('private_id', $pid);
  }

  /**
   * finds the ID of a record given the value of one of it's fields. 
   * 
   * Returns the first of multiple matches
   * 
   * @param string $term
   * @param mixed $id
   * @return int|bool false if no valid id found
   */
  public static function get_record_id_by_term($term, $id, $single = true) {

    return self::_get_participant_id_by_term($term, $id, $single);
  }

  /**
   * gets a participant record id by term
   *
   * given an identifier, returns the id of the record identified. If there is
   * more than one record with the given term, returns the first one.
   *
   * @global object $wpdb
   * @param string $term the column to match
   * @param string $value the value to search for
   * @param bool   $single if true, return only one ID
   *
   * @return int|array|bool returns integer if one match, array of integers if multiple 
   *                        matches (and single is false), false if no match
   */
  private static function _get_participant_id_by_term($term, $value, $single = true) {

    global $wpdb;
    
    if ( ! self::is_column($term)) return false;

    $sql = 'SELECT p.id FROM ' . self::$participants_table . ' p WHERE p.' . $term . ' = %s';
    $result = $wpdb->get_results($wpdb->prepare($sql, $value), ARRAY_N);

    if (!is_array($result))
      return false;

    $output = array();

    foreach($result as $id) {
      $output[] = current($id);
    }

    return $single ? current($output) : $output;
  }

  /**
   * generates a 5-character private ID
   *
   * the purpose here is to create a unique yet managably small and unguessable
   * (within reason) id number that can be included in a link to call up a 
   * specific record by a user.
   *
   * @return string unique alphanumeric ID
   */
  public static function generate_pid() {

    $pid = '';

    $chr_source = array(
        '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H',
        'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');

    for ($i = 0; $i < self::$private_id_length; $i++) {

      $pid .= $chr_source[array_rand($chr_source)];
    }

    // if by chance we've generated a string that has been used before, generate another
    return self::_id_exists($pid, 'private_id') ? self::generate_pid() : $pid;
  }

  /**
   * tests for existence of record in main db
   *
   * @global object $wpdb
   * @param string $id the identifier to test
   * @param string $field the db field to test the $id value against
   * @return bool true if a record mathing the criterion exists
   */
  private static function _id_exists($id, $field = 'id') {

    global $wpdb;

    $id_exists = $wpdb->get_var($wpdb->prepare("SELECT EXISTS( SELECT 1 FROM " . self::$participants_table . " p WHERE p." . $field . " = '%s' LIMIT 1 )", $id));

    if (NULL !== $id_exists)
      return $id_exists === '0' ? false : true;
    else {
      error_log(__METHOD__ . ' called with invalid arguments');
      return false;
    }
  }

  /**
   * returns the next valid record id
   * 
   * the next id can be the next higher or lower. This function will wrap, so it 
   * always returns a valid id.
   * 
   * @global object $wpdb
   * @param string $id the current id
   * @param bool   $increment true for next higher, false for next lower
   * @return string the next valid id
   */
  public static function next_id($id, $increment = true) {
    global $wpdb;
    $max = $wpdb->get_var('SELECT MAX(p.id) FROM ' . self::$participants_table . ' p');
    $id = (int)$id;
    $inc = $increment ? 1 : -1;
    $id = $id + $inc;
    while (!self::_id_exists($id)) {
      $id = $id + $inc;
      if ($id > $max) $id = 1;
      elseif ($id < 1) $id = $max;
    }
    return $id;
  }

  /**
   * tests for the presence of an email address in the records
   *
   * @param string $email the email address to search for
   * @return boolean true if email is found
   */
  public static function email_exists($email) {
    
    if (!empty(Participants_Db::$plugin_options['primary_email_address_field'])) {
      return self::_id_exists($email, Participants_Db::$plugin_options['primary_email_address_field']);
    } else return false;
  }

  /**
   * returns true if a record has a value matching the checked field
   *
   * @param string $value the value of the field to test
   * @param string $field the field to test
   * @return bool true if match exists (only checks for the first one)
   */
  public static function field_value_exists($value, $field) {

    return self::_id_exists($value, $field);
  }

  /**
   * prepares a field for display 
   * 
   * displays an array as a series of comma-separated strings
   * 
   * @param string $string
   * @return string the prepared string
   */
  public static function prepare_value($string) {

    $value = self::unserialize_array($string);
    
    if (is_array($value) && PDb_FormElement::is_assoc($value)) {
      /*
       * here, we create a string representation of an associative array, using 
       * :: to denote a name=>value pair
       */
      $temp = array();
      foreach ($value as $k => $v) {
        if (is_int($k)) {
          $temp[] = $v;
        } else {
          $temp[] = $k . '::' . $v;
        }
      }
      return implode(', ',$temp);
    }

    return is_array($value) ? implode(', ', $value) : stripslashes($value);
  }

  /**
   * adds a blank field type record
   * 
   * @global object $wpdb
   * @param array $atts
   * @return boolean 
   */
  public static function add_blank_field($atts) {

    global $wpdb;
    $wpdb->hide_errors();

    $defaults = wp_parse_args($atts, array('form_element' => 'text-line'));

    $wpdb->insert(self::$fields_table, $defaults);

    if ($wpdb->last_error) {

      if (WP_DEBUG)
        error_log(__METHOD__ . ' failed to add row ' . $atts['name']);

      return false;
    }

    // if this column does not exist in the DB, add it
    if (count($wpdb->get_results("SHOW COLUMNS FROM `" . self::$participants_table . "` LIKE '" . $defaults['name'] . "'", ARRAY_A)) < 1) {

      if (false === ( self::_add_db_column($defaults) )) {

        if (WP_DEBUG)
          error_log(__METHOD__ . ' failed to add column:' . print_r($defaults, true));

        return false;
      }
    }
  }

  /**
   * adds a new column (field) to the databse
   * 
   * @global object $wpdb
   * @param array $atts a set of attributrs to define the new columns
   * @retun bool success of the operation
   */
  private static function _add_db_column($atts) {

    global $wpdb;

    $datatype = PDb_FormElement::get_datatype($atts['form_element']);

    $sql = 'ALTER TABLE `' . self::$participants_table . '` ADD `' . $atts['name'] . '` ' . $datatype . ' NULL';

    return $wpdb->query($sql);
  }

  /**
   * processes any POST requests
   * 
   * this is called on the 'init' hook
   * 
   * @global object $wpdb
   * @return null
   */
  public static function process_page_request() {

    $post_sanitize = array(
        'subsource' => FILTER_SANITIZE_STRING,
        'action' => FILTER_SANITIZE_STRING,
        'pdb_data_keys' => FILTER_SANITIZE_STRING,
        'submit_button' => FILTER_SANITIZE_STRING,
        'filename' => FILTER_SANITIZE_STRING,
        'base_filename' => FILTER_SANITIZE_STRING,
        'CSV_type' => FILTER_SANITIZE_STRING,
        'include_csv_titles' => FILTER_VALIDATE_BOOLEAN,
        'nocookie' => FILTER_VALIDATE_BOOLEAN,
        'previous_multipage' => FILTER_SANITIZE_STRING,
    );
    /*
     * $post_input is used for control functions, not for the dataset
     */
    $post_input = filter_input_array(INPUT_POST, $post_sanitize);
    // only process POST arrays from this plugin's pages
    if (empty($post_input['subsource']) or $post_input['subsource'] != self::PLUGIN_NAME or empty($post_input['action']))
      return;

    // add a filter to check the submission before anything is done with it
    $check = true;
    self::set_filter('check_submission', $check);
    if ($check === false) return;

    // error_log( __METHOD__.' post:'.print_r( $_POST, true ) );
    
    /*
     * the originating page for a multipage form is saved in a session value
     * 
     * if this is an empty string, it is assumed the submission was not part of a multipage form series
     */
    self::$session->set('previous_multipage', $post_input['previous_multipage']);

    /*
     * get the defined columns for the submitting shortcode (if any)
     * 
     * this is needed so that validation will be performed on the expected list 
     * of fields, not just what's found in the POST array
     */
    $columns = false;
    if (!empty($post_input['pdb_data_keys'])) $columns = self::get_data_key_columns($post_input['pdb_data_keys']);

    /*
     * instantiate the validation object if we need to. This is necessary
     * because another script can instantiate the object in order to add a
     * feedback message
     * 
     * we don't validate administrators in the admin
     */
    if (!is_object(self::$validation_errors)) {
      if (Participants_Db::is_form_validated()) {
        self::$validation_errors = new PDb_FormValidation();
      }
    }

    switch ($post_input['action']) :

      case 'update':
      case 'insert':
    
        /*
         * we are here for one of these cases:
         *   a) we're adding a new record in the admin
         *   b) a user is updating their record on the frontend
         *   c) an admin is updating a record
         *
         * signups are processed in the case 'signup' section
         * 
         * set the raw post array filters. We pass in the $_POST array, expecting 
         * a possibly altered copy of it to be returned
         * 
         * filter: pdb-before_submit_update
         * filter: pdb-before_submit_add
         */
        $post_data = self::set_filter('before_submit_' . ($post_input['action'] === 'insert' ? 'add' : 'update'), $_POST);

        if (isset($_POST['id'])) {
          $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)));
        } elseif (isset($_GET['id'])) {
          $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)));
        } else {
          $id = false;
        }

        $participant_id = self::process_form($post_data, $post_input['action'], $id, $columns);

        if (false === $participant_id) {

          // we have errors; go back to form and show errors
          return;
        }
        
        /*
         * set the stored record hook.
         * 
         * hook: pdb-after_submit_update
         * hook: pdb-after_submit_add
         */
        $wp_hook = self::$prefix . 'after_submit_' . ($post_input['action'] == 'insert' ? 'add' : 'update');
        do_action($wp_hook,self::get_participant($participant_id));

        /*
         * if we are submitting from the frontend, set the feedback message and 
         * send the update notification
         */
        if (!is_admin()) {

          /*
           * if the user is an admin, the validation object won't be instantiated, 
           * so we do that here so the feedback message can be shown.
           */
          if (!is_object(self::$validation_errors)) self::$validation_errors = new PDb_FormValidation();
            
          self::$validation_errors->add_error('', self::$plugin_options['record_updated_message']);

          if (self::$plugin_options['send_record_update_notify_email'] && ! self::is_multipage_form()) {

            $sent = wp_mail(
                    self::$plugin_options['email_signup_notify_addresses'],
                    self::proc_tags(self::$plugin_options['record_update_email_subject'], $participant_id, 'all'),
                    self::proc_tags(self::$plugin_options['record_update_email_body'], $participant_id, 'all'),
                    self::$email_headers
            );
          }
          /*
           * if the "thanks page" is defined as another page, save the ID in a session variable and move to that page.
           */
          if (isset($post_data['thanks_page']) && $post_data['thanks_page'] != $_SERVER['REQUEST_URI']) {
          
            self::$session->set('pdbid', $post_data['id']);
            
            $redirect = $post_data['thanks_page'];
            /**
             * this is to handle the sepcial case where the frontend record form uses a separate 
             * thanks page using the [pdb_signup_thanks] shortcode
             */
            if ($post_input['action'] == 'insert' && ! self::is_multipage_form() ) {
            	self::add_uri_conjunction($redirect) . 'action=update';
            }

            wp_redirect($redirect);
            
            exit;
          }

          return;
        }

        // redirect according to which submit button was used
        switch ($post_input['submit_button']) {

          case self::$i18n['apply'] :
            $redirect = get_admin_url() . 'admin.php?page=' . self::PLUGIN_NAME . '-edit_participant&id=' . $participant_id;
            break;

          case self::$i18n['next'] :
            $get_id = $post_input['action'] == 'update' ? '&id=' . self::next_id($participant_id) : '';
            $redirect = get_admin_url() . 'admin.php?page=' . self::PLUGIN_NAME . '-edit_participant' . $get_id;
            break;

          case self::$i18n['previous'] :
            $get_id = $post_input['action'] == 'update' ? '&id=' . self::next_id($participant_id, false) : '';
            $redirect = get_admin_url() . 'admin.php?page=' . self::PLUGIN_NAME . '-edit_participant' . $get_id;
            break;

          case self::$i18n['submit'] :
          default :
            $redirect = get_admin_url() . 'admin.php?page=' . self::PLUGIN_NAME;

        }
        wp_redirect($redirect);
        exit;

      case 'output CSV':

        $csv_role = Participants_Db::plugin_setting_is_true('editor_allowed_csv_export') ? 'editor' : 'admin';
        if (!Participants_Db::current_user_has_plugin_role($csv_role, 'csv export')) {
          die();
        }
        $header_row = array();
        $title_row = array();
        $data = array();
        $filename = !empty($post_input['filename']) ? $post_input['filename'] : '';

        switch ($post_input['CSV_type']) :

          // create a blank data array
          case 'blank':

            // add the header row
            foreach (self::get_column_atts('CSV') as $column)
              $header_row[] = $column->name;
            $data[] = $header_row;

            $i = 2; // number of blank rows to create

            while ($i > 0) {
              $data[] = array_fill_keys($header_row, '');
              $i--;
            }
            break;

          case 'participant list':

            global $wpdb;

            $import_columns = '';

            foreach (self::get_column_atts('CSV') as $column) {

              $import_columns .= sprintf('`%s`,', $column->name);
              $header_row[] = $column->name;
              $title_row[] = $column->title;
            }

            $data['header'] = $header_row;

            if ($post_input['include_csv_titles']) $data['titles'] = $title_row;

            global $current_user;
            $query = get_transient(Participants_Db::$prefix . 'admin_list_query' . $current_user->ID);

            if ($query) {
              $query = str_replace('*', ' ' . trim($import_columns, ',') . ' ', $query);
              $data += self::_prepare_CSV_rows($wpdb->get_results($query, ARRAY_A));
            }

            break;

        endswitch; // CSV type

        if (!empty($filename)) {
          
          $base_filename = substr($filename, 0, strpos($filename, PDb_List_Admin::filename_datestamp() . '.csv'));
          
          /*
           * @version 1.6
           * base filename is now saved as a preference
           */
          global $user_ID;
          PDb_List_Admin::$user_settings = Participants_Db::$prefix . PDb_List_Admin::$user_settings . '-' . $user_ID;
          PDb_List_Admin::set_admin_user_setting('csv_base_filename', $base_filename);

          // create a file pointer connected to the output stream
          $output = fopen('php://output', 'w');

          //header('Content-type: application/csv'); // some sources say it should be this
          header('Content-Type: text/csv; charset=utf-8');
          header("Cache-Control: no-store, no-cache");
          header('Content-Disposition: attachment; filename="' . $filename . '"');

          // output the data lines
          foreach ($data as $line) {
            fputcsv($output, $line, ',', self::$CSV_enclosure);
          }

          fclose($output);

          // we must terminate the script to prevent additional output being added to the CSV file
          exit;
        }

        return $data;

      case 'retrieve' :

        if (self::nonce_check( filter_input( INPUT_POST, 'session_hash', FILTER_SANITIZE_STRING ), self::$main_submission_nonce_key)) {
          self::_process_retrieval();
        }
        return;

      case 'signup' :
        
        if ( ! self::nonce_check( filter_input( INPUT_POST, 'session_hash', FILTER_SANITIZE_STRING ), self::$main_submission_nonce_key))
                return;
        
        $_POST['private_id'] = '';
        $columns[] = 'private_id';
        
        /*
         * route the $_POST data through a callback if defined
         * 
         * filter: pdb-before_submit_signup
         */
        $post_data = self::set_filter('before_submit_signup', $_POST);
        
        
    
        /*
         * the signup form should update the current record if it is revisited during a multipage form session
         */
        $submit_action = 'insert';
        if (self::$session->get('pdbid') !== false) {
          $submit_action = 'update';
        }

        // submit the data
        $post_data['id'] = self::process_form($post_data, $submit_action, self::$session->get('pdbid'), $columns);

        if (false !== $post_data['id']) {
          
          /* 
           * hook: pdb-after_submit_signup
           */
          $wp_hook = self::$prefix . 'after_submit_signup';
          do_action($wp_hook,self::get_participant($post_data['id']));
          
          $redirect = $post_data['thanks_page'];
          self::$session->set('pdbid', $post_data['id']);

          wp_redirect($redirect);

          exit;
        }

        return;

    endswitch; // $_POST['action']
  }
  
  
  /**
   * checks the nonce on a form submission
   * 
   * @uses filter pdb-nonce_verify
   * 
   * @param string $nonce the nonce value
   * @param string $context the context string
   * 
   * @return bool true if nonce passes
   */
  public static function nonce_check ($nonce, $context) {
    return self::set_filter('nonce_verify', wp_verify_nonce($nonce, self::nonce_key($context)), $context);
  }
  /**
   * provides a key string for a nonce
   * 
   * @uses filter pdb-nonce_key
   * 
   * @param string $context a context string for the key string
   * 
   * @return string
   */
  public static function nonce_key($context) {
    return self::set_filter('nonce_key', self::$prefix . $context);
  }
  /**
   * provides a nonce string
   * 
   * @uses filter pdb-nonce
   * 
   * @param string $context an optional context string for the filter
   * 
   * @return string
   */
  public static function nonce($context) {
    return self::set_filter('nonce', wp_create_nonce(self::nonce_key($context)));
  } 
   /**
   * tests a private link retrieval submission and send the link or sets an error
   * 
   * @return null
   */
  private static function _process_retrieval()
  {

    /*
     * we check a transient based on the user's IP; if the user tries more than 3 
     * times per day to get a private ID, they are blocked for 24 hours
     */
    $max_tries = Participants_Db::current_user_has_plugin_role('admin', 'retrieve link') ? 10000 : 3; // give the plugin admin unlimited tries
    $transient = self::$prefix . 'retrieve-count-' . str_replace('.', '', $_SERVER['REMOTE_ADDR']);
    $count = get_transient($transient);
    if ($count === false) {
      set_transient($transient, 1, (60 * 60 * 24));
    }
    if ($count > $max_tries) {

// too many tries, come back tomorrow
      error_log('Participants Database Plugin: IP blocked for too many retrieval attempts from IP ' . $_SERVER['REMOTE_ADDR'] . ' in 24-hour period.');
      return;
    }
    $count++;
    set_transient($transient, $count, (60 * 60 * 24));

    $column = self::plugin_setting('retrieve_link_identifier', 'email');

    if (!isset($_POST[$column]) || empty($_POST[$column])) {
      self::$validation_errors->add_error($column, 'empty');
      return;
    }
    // a value was submitted, try to find a record with it
    //$match_id = self::_get_participant_id_by_term($column, $_POST[$column]);
    $match_id = self::find_record_match($column, $_POST);
    
    if (!is_object(self::$validation_errors)) {
      self::$validation_errors = new PDb_FormValidation();
    }
    
    if ($match_id === false) {
      self::$validation_errors->add_error($column, 'identifier');
      return;
    } else {
      $participant_values = self::get_participant($match_id);
    }
    $retrieve_link_email = new stdClass();
    $retrieve_link_email->body_template = self::set_filter('translate_string', self::plugin_setting('retrieve_link_email_body'));
    $retrieve_link_email->subject = self::set_filter('translate_string', self::plugin_setting('retrieve_link_email_subject'));
    $retrieve_link_email->recipient = $participant_values[self::plugin_setting('primary_email_address_field', 'email')];
    /**
     * @version 1.6
     * 
     * filter pdb-before_send_retrieve_link_email
     */
    self::set_filter('before_send_retrieve_link_email', $retrieve_link_email);
    if (!empty($retrieve_link_email->recipient)) {
      $body = self::proc_tags($retrieve_link_email->body_template, $match_id);
      $sent = wp_mail( 
              $retrieve_link_email->recipient, 
              self::proc_tags($retrieve_link_email->subject, $match_id), 
              (self::plugin_setting('html_email') ? self::process_rich_text($body) : $body), 
              self::$email_headers
              );

      if (false === $sent)
        error_log(__METHOD__ . ' sending returned false');
    } else {
        error_log(__METHOD__ . ' primary email address field undefined');
    }
    
    if (self::plugin_setting_is_true('send_retrieve_link_notify_email')) {
      
      $body = self::proc_tags(self::plugin_setting('retrieve_link_notify_body'), $match_id);
      $sent = wp_mail( 
            self::plugin_setting('email_signup_notify_addresses'), 
            self::proc_tags(self::plugin_setting('retrieve_link_notify_subject'), $match_id, 'all'), 
              (self::plugin_setting('html_email') ? self::process_rich_text($body) : $body),
            self::$email_headers
            );
    }

//self::$validation_errors->add_error('', 'success');
    $_POST['action'] = 'success';
    return;
  }

  /**
   * processes a rich text string
   * 
   * runs it through wpautop if selected in the settings
   * 
   * @param string $input
   * @return string
   */
  public static function process_rich_text($string) {
    return Participants_Db::$plugin_options['enable_wpautop'] ? apply_filters('the_content', $string) : $string; // wpautop($string)
  }

  /**
   * gets an array of readonly fields
   *
   * @return array
   */
  public static function get_readonly_fields() {

    $fields = array();

    foreach (self::get_column_atts('readonly') as $column)
      $fields[] = $column->name;

    return $fields;
  }

  /**
   * returns the title attribute of a column
   * 
   * @param string $column field name
   * @return string
   */
  public static function column_title($column) {

    $field = self::get_field_atts($column, 'title');

    return $field->title;
  }

  /**
   * prepares a set of rows for CSV output
   *
   * @param array $raw_array the raw array output from the query
   *
   * @return array of record arrays
   */
  private static function _prepare_CSV_rows($raw_array) {

    $output = array();

    foreach ($raw_array as $value) {
      $output[] = self::_prepare_CSV_row($value);
    }

    return $output;
  }

  /**
   * prepares a row of data for CSV output
   *
   * @param array $raw_array the raw array output from the query
   *
   * @return array with all the serialized arrays in human-readable form
   */
  private static function _prepare_CSV_row($raw_array) {

    $output = array();

    // get the column attributes
    $columns = self::get_column_atts('CSV');

    // iterate through the object as we iterate through the array
    $column = current($columns);

    foreach ($raw_array as $key => $value) {

      // process any other value types
      switch ($column->form_element) {

        case 'date':

          if (!empty($value) && is_numeric($value)) {

            $value = date_i18n(self::$date_format, $value);
          }
          break;

        case 'link':

          // flatten the array
          if (is_serialized($value)) {

            $link = unserialize($value);
            if (empty($link[0]))
              $value = '';
            else {
              $pattern = empty($link[1]) ? '<%1$s>' : '[%2$s](%1$s)';
              $value = vsprintf($pattern, $link);
            }
          }
          break;
          
        case 'rich-text':
          
          /*
           * what we need to do here is add the missing markup (wpautop does 
           * this) and then remove all line breaks and such so the whole thing 
           * looks like one field
           */
          $value = preg_replace( '/^\s+|\n|\r|\s+$/m', '', wpautop($value, true));
          break;

        default:

          // flatten arrays
          $value = implode(', ', (array) maybe_unserialize($value));
      }

      /*
       * decode HTML entities and convert line breaks to <br>, then pass to a filter 
       * for processing beforebeing added to the output array
       */
      $output_value = Participants_Db::set_filter('csv_export_value', html_entity_decode(str_replace(array("\n","\r"), '<br />', stripslashes($value)), ENT_QUOTES, "UTF-8"), $column);
      $output[$key] = $output_value;

      $column = next($columns);
    }

    return $output;
  }

  /**
   * creates an anchor element with clickable link and href
   *
   * this is simply an interface to the xnau_FormElement function of the same name
   * 
   * @static
   * @param string $link the URI
   * @param string $linktext the clickable text (optional)
   * @param string $template the format of the link (optional)
   * @param array  $get an array of name=>value pairs to include in the get string
   *
   * @return string HTML or HTML-escaped string (if it's not a link)
   */
  public static function make_link($link, $linktext = '', $template = false, $get = false) {

    $field = new stdClass();

    $field->link = $link;
    $field->value = $linktext === '' ? $link : $linktext;

    return PDb_FormElement::make_link($field, $template, $get);
      }
      
  /**
   * provides an AJAX loading spinner element
   */
  public static function get_loading_spinner() {
    return '<span class="ajax-loading"><img src="' . plugins_url( 'ui/ajax-loader.gif' , __FILE__ ) . '" /></span>';
  }

  /**
   * handles a file upload
   *
   * @param string $name the name of the current field
   * @param array  $file the $_FILES array element corresponding to one file
   * @param int|bool record id if the action is an update
   *
   * @return string the path to the uploaded file or false if error
   */
  private static function _handle_file_upload($field_name, $file, $id = false)
  {

    $field_atts = self::get_field_atts($field_name);
    $type = 'image-upload' == $field_atts->form_element ? 'image' : 'file';
    $delete_checked = (bool)(isset($_POST[$field_name . '-deletefile']) and $_POST[$field_name . '-deletefile'] == 'delete');
    $_POST[$field_name . '-deletefile'] = '';

    // attempt to create the target directory if it does not exist
    if (!is_dir(Participants_Db::files_path())) {

      if (false === self::_make_uploads_dir()) {
        return false;
      }
    }

    if (!is_uploaded_file(realpath($file['tmp_name']))) {

      self::_show_validation_error(__('There is something wrong with the file you tried to upload. Try another.', 'participants-database'), $field_name);

      return false;
    }

    /* get the allowed file types and test the uploaded file for an allowed file 
     * extension
     */
    $extensions = empty($field_atts->values) ? self::$plugin_options['allowed_file_types'] : implode(',', self::unserialize_array($field_atts->values));
    $test = preg_match('#^(.+)\.(' . implode('|', array_map('trim', explode(',', str_replace('.', '', strtolower($extensions))))) . ')$#', strtolower($file['name']), $matches);

    //error_log(__METHOD__.' ext:'.$extensions.' test:'. $test.' matches:'.print_r($matches,1));

    if (0 === $test) {

      if ($type == 'image' && empty($field_atts->values))
        self::_show_validation_error(sprintf(__('For "%s", you may only upload image files like JPEGs, GIFs or PNGs.', 'participants-database'), $field_atts->title), $field_name);
      else
        self::_show_validation_error(sprintf(__('The file selected for "%s" must be one of these types: %s. ', 'participants-database'), $field_atts->title, preg_replace('#(,)(?=[^,])#U',', ',$extensions)), $field_name);

      return false;
    } else {

      // validate and construct the new filename using only the allowed file extension
      $new_filename = preg_replace(array('#\.#', "/\s+/", "/[^-\.\w]+/"), array("-", "_", ""), $matches[1]) . '.' . $matches[2];
      // now make sure the name is unique by adding an index if needed
      $index = 1;
      while (file_exists(Participants_Db::files_path() . $new_filename)) {
        $filename_parts = pathinfo($new_filename);
        $new_filename = preg_replace(array('#_[0-9]+$#'), array(''), $filename_parts['filename']) . '_' . $index . '.' . $filename_parts['extension'];
        $index++;
      }
    }

    if ( $type == 'image') {
    /*
     * we perform a validity check on the image files, this also makes sure only 
     * images are uploaded in image upload fields
     */
    $fileinfo = getimagesize($file['tmp_name']);
    $valid_image = in_array($fileinfo[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WBMP));

      if (!$valid_image ) {

        self::_show_validation_error( sprintf(__('For "%s", you may only upload image files like JPEGs, GIFs or PNGs.', 'participants-database'), $field_atts->title ), $field_name);
      return false;
    }
    }

    if ($file['size'] > self::$plugin_options['image_upload_limit'] * 1024) {

      self::_show_validation_error(sprintf(__('The file you tried to upload is too large. The file must be smaller than %sK.', 'participants-database'), self::$plugin_options['image_upload_limit']),$field_name);

      return false;
    }

    if (false === move_uploaded_file($file['tmp_name'], Participants_Db::files_path() . $new_filename)) {

      self::_show_validation_error(__('The file could not be saved.', 'participants-database'));

      return false;
    }

    /*
     * if a previously uploaded file exists and the preference is to allow user deletes, 
     * the previously uploaded file is deleted. If an admin wants to delete a file while 
     * user deletes are not allowed, they must check the delete box.
     * 
     * as of 1.5.5
     */
    if ($id !== false) {
      $record_data = self::get_participant($id);
      if (! empty($record_data[$field_name])) {
        $image_obj = new PDb_Image(array('filename' => $record_data[$field_name]));
        if ($image_obj->image_defined and (self::$plugin_options['file_delete'] == '1' || is_admin() && $delete_checked)) {
          self::delete_file($record_data[$field_name]);
        } 
      }
    }

    /*
     * as of 1.3.2 we save the image as filename only; the image is retrieved from 
     * the directory defined in the plugin setting using the self::get_image function
     */

      return $new_filename;
  }

  /**
   * attempt to create the uploads directory
   *
   * sets an error if it fails
   * 
   * @param string $dir the name of the new directory
   */
  public static function _make_uploads_dir($dir = '') {

    $dir = empty($dir) ? Participants_Db::files_location() : $dir;
    $savedmask = umask(0);
    $status = true;
    if (mkdir(Participants_Db::app_base_path() . $dir, 0755, true) === false) {

      if (is_object(self::$validation_errors))
        self::$validation_errors->add_error('', sprintf(__('The uploads directory (%s) could not be created.', 'participants-database'), $dir));

      $status = false;
    }
    umask($savedmask);
    return $status;
  }

  /**
   * builds a record edit link
   *
   * @param string $PID private id value
   * @return string private record URI
   */
  public static function get_record_link($PID, $target_page = '') {

    $target_page = $target_page === '' ? self::$plugin_options['registration_page'] : $target_page;

    if (false === $registration_page = self::find_permalink($target_page)) {
      error_log('Participants Database: "Participant Record Page" setting is invalid.');
      return '';
    }

    return self::add_uri_conjunction($registration_page) . 'pid=' . $PID;
  }
  
  /**
   * builds an admin record edit link
   * 
   * this is meant to be included in the admin notification for a new signup, 
   * giving them the ability to click the link and edit the new record
   * 
   * @param int $id the id of the new record
   * @return string the HREF for the record edit link
   */
  public static function get_admin_record_link($id) {
    
    $path = 'admin.php?page=participants-database-edit_participant&action=edit&id='.$id;
    
    return get_admin_url( NULL, $path );
  }

  /**
   * gets the ID of a page given it's slug
   *
   * this is to provide backwards-compatibility with previous versions that used a page-slug to point to the [pdb_record] page.
   */
  public static function get_id_by_slug($page_slug) {

    $page = get_page_by_path($page_slug);

    return is_object($page) ? $page->ID : false;
  }

  /**
   * replace the tags in text messages
   *
   * returns the text with the values replacing the tags
   * all tags use the column name as the key string 
   * also includes and processes the [record_link]  and [date] tags
   *
   * @param  string  $text           the text containing tags to be replaced with 
   *                                 values from the db
   * @param  int     $participant_id the record id to use
   * @param  string  $mode           the column subset to use
   * @return string                  text with the tags replaced by the data
   */
  public static function proc_tags($text, $participant_id, $mode = 'frontend') {
    
    return self::replace_tags($text, self::get_participant($participant_id), self::get_column_atts($mode));
  }
  /**
   * maps a sets of values to "tags"in a template, replacing the tags with the values
   * 
   * @param string $text the tag-containing template string
   * @param array  $data array of record values: $name => $value
   * @param array  $columns array of field objects
   * 
   * @return string template with all matching tags replaced with values
   */
  public static function replace_tags($text, array$data, array$columns) {

    $values = $tags = array();

    foreach ($columns as $column) {
      
      $column->value = isset($data[$column->name]) ? $data[$column->name] : '';

      $tags[] = '[' . $column->name . ']';

      $values[] = PDb_FormElement::get_field_value_display($column, false);
    }

    // add the "record_link" tag
    if (isset($data['private_id'])) {
      $tags[] = '[record_link]';
      $values[] = Participants_Db::get_record_link($data['private_id']);
    }

    // add the date tag
    $tags[] = '[date]';
    $values[] = date_i18n(self::$date_format, self::parse_date());

    // add the time tag
    $tags[] = '[time]';
    $values[] = date_i18n(get_option('time_format'), self::parse_date());
    
    if (isset($data['id']) && is_numeric($data['id'])) {
      // add the admin record link tag
      $tags[] = '[admin_record_link]';
      $values[] = self::get_admin_record_link($data['id']);
    }

    $placeholders = array();

    for ($i = 1; $i <= count($tags); $i++) {

      $placeholders[] = '%' . $i . '$s';
    }

    // replace the tags with variables
    $pattern = str_replace($tags, $placeholders, $text);

    // replace the variables with strings
    return vsprintf($pattern, $values);
    
  }

  /**
   * prints the list with filtering parameters applied 
   *
   * called by the wp_ajax_nopriv_pdb_list_filter action: this happens when a 
   * user submits a search or sort on a record list
   *
   * @return null
   */
  public static function pdb_list_filter() {
    
    $multi = is_array($_POST['search_field']);
    $postinput = filter_input_array(INPUT_POST, self::search_post_filter($multi));
    self::$instance_index = empty($postinput['target_instance']) ? $postinput['instance_index'] : $postinput['target_instance'];

    if (!self::nonce_check($postinput['filterNonce'], PDb_List::$list_filter_nonce_key))
      die('nonce check failed');

    global $post;

    if (!is_object($post))
      $post = get_post($postinput['postID']);
    
    self::print_list_search_result($post, self::$instance_index);
    
    do_action(Participants_Db::$prefix . 'list_ajax_complete', $post);
    
    exit;
  }
  
  /**
   * provides the list output from an AJAX search
   * 
   * @param object $post the current post object
   * @param int $instance the instance index of the targeted list
   * @return null
   */
  private static function print_list_search_result($post, $instance) {
    /* 
     * get the attributes array; these values were saved in the session array by 
     * the Shortcode class when it was instantiated
     */
    $session = self::$session->getArray('shortcode_atts');
    $shortcode_atts = $session[$post->ID]['list'][$instance];
    
    if (!is_array($shortcode_atts)) {
      printf( 'session for list instance %s not found', $instance );
      return;
    }
      
    // add the AJAX filtering flag
    $shortcode_atts['filtering'] = 1;
    $shortcode_atts['module'] = 'list';
    
    // output the filtered shortcode content
    header("Content-Type:	text/html");
    echo PDb_List::get_list( $shortcode_atts );
    return;
  } 
  
  /**
   * clears the list search
   * 
   * @return null
   */
  private static function clear_list_search() {
    self::$session->clear('shortcode_atts');
  }
  
  /**
   * supplied for backwards compatibility
   * 
   * the original func has been superceded, but this will allow the old func to be used
   * 
   * @var string $value
   * @var string $form_element
   * @return string
   */
  public static function prep_field_for_display( $value, $form_element ){
    $field = (object) array(
        'value' => $value,
        'form_element' => $form_element,
        'module' => 'single', // probably not correct, but this is the generic option
    );
    return PDb_FormElement::get_field_value_display($field);
  }

  /**
   * parses a date string into UNIX timestamp
   *
   * if "strict dates" is set, this function uses the DateTime or IntlDateFormatter 
   * class to parse the string according to a specific format. If it is not, we 
   * use the conventional strtotime() function, with the enhancement that if the 
   * non-American style format is used with slashes "d/m/Y" the string is prepared 
   * so strtotime can parse it correctly  
   *
   * @param string $string      the string to parse; if not given, defaults to now
   * @param object $column_atts the column object; used to identify the field for
   *                            user feedback
   * @param bool   $zero_time   if set, zero the time portion of the date so it 
   *                            won't interfere with date comparisons
   * @return int|bool UNIX timestamp or false if parse fails
   */
  public static function parse_date($string = false, $column = '', $zero_time = false) {

    if (false === $string)
      return false;

    $string = Participants_Db::set_filter('parse_date', $string, $column);

    // is it already a timestamp?
    if (self::is_valid_timestamp($string)) {
      //if (WP_DEBUG and is_object($column)) error_log(__METHOD__.' tried to parse timestamp from '. $column->name);
      return $string;
    }
    
    $date = false;
    // if it is a default zero timestamp, treat it as "no date"
    if ($string === '0000-00-00 00:00:00') return false;

    /*
     * we have two options to parse a date string into a timestamp: the 
     * IntlDateFormatter class or the DateTime class. The IntlDateFormatter 
     * class can parse localized text dates, but it seems commonly unavailable, 
     * at least on English-speaking servers. The DateTime class is widely 
     * available, but can't parse non-English text dates. It can parse numeric 
     * date representations, so if the intl module is not available, we try to 
     * use DateTime. If that is not available, we use strtotime with the added trick 
     * of swapping the date/month if they are slashes so slashed European notation 
     * can be correctly parsed
     */
    self::$date_mode = 'none';
    $errors = false;
    $the_Date = false;
    // test for MySQL-format timestamp
    $is_MySQL_timestamp = (preg_match('#^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$#', $string) === 1) ? true : false;
    
    //error_log(__METHOD__.' object? '.(is_object($column)?'yes':'no').' strict dates? '.(self::plugin_setting_is_true('strict_dates', false)?'yes':'no').' timestamp? '.($is_MySQL_timestamp?'yes':'no'));
    
    
    if (self::plugin_setting_is_true('strict_dates', false) and is_object($column) and !$is_MySQL_timestamp ) {
    	
    	
    	//error_log(__METHOD__.' intl? '.(class_exists('IntlDateFormatter')?'yes':'no').' datetime? '.(class_exists('DateTime')?'yes':'no'));
      
      if (class_exists('IntlDateFormatter')) {
        
        self::$date_mode = 'Intl';

        $DateFormat = new IntlDateFormatter( get_locale(), IntlDateFormatter::LONG, IntlDateFormatter::NONE, NULL, NULL, Participants_Db::get_ICU_date_format(self::$plugin_options['input_date_format']) );
        $DateFormat->setLenient(false); // we want it strict
        $timestamp = $DateFormat->parse($string);

        if ($DateFormat->getErrorCode() !== 0) {
          $errors = array('code' => $DateFormat->getErrorCode(),'error' => $DateFormat->getErrorMessage());
        }
        
        if (!$errors) {
	        $the_Date = new DateTime();
	        $the_Date->setTimestamp($timestamp);
        } elseif (WP_DEBUG) {
        	error_log(__METHOD__.' IntlDateFormatter error: format string: '. Participants_Db::get_ICU_date_format(self::$plugin_options['input_date_format']).' timestamp: '.$timestamp.' formatter error: '. $DateFormat->getErrorMessage());
        }
        
      }
      
      if (!$the_Date && class_exists('DateTime')) {
        
        self::$date_mode = 'DateTime';

        $the_Date = DateTime::createFromFormat(self::$plugin_options['input_date_format'], $string);
        
      }
      
      if (is_object($the_Date)) {
        $errors = $the_Date->getLastErrors();
        if ($errors['warning_count'] === 0 && $errors['error_count'] === 0) {
          $errors = false;
        }
      }

      if (is_array($errors) && !empty($string)) {

          $the_Date = false;
          if (is_object(self::$validation_errors) and is_object($column)) {

            self::$validation_errors->add_error($column->name, sprintf(__('The date for "%s" was invalid. Please input the date with the exact format shown', 'participants-database'), $column->title));
          }
          
          if (WP_DEBUG) error_log(__METHOD__.' DateTime parse error: '.implode(', ', $errors));
          
      }

      /*
       * if we have a valid date, convert to timestamp
       */
      if ($the_Date) {
        /*
         * zero the time so date equality comparisons can be made
         */
        if ($zero_time) $the_Date->setTime(0,0);
        $date = $the_Date->format('U');
      }
    }
      
    /*
     * if we haven't got a timestamp, parse the date the regular way
     */
    if ( $date === false or ! self::is_valid_timestamp($date) ){
      
      $date = false; // no valid date yet
      
      if (is_object($column) && $column->form_element == 'timestamp') {
        if ($zero_time) {
          /*
           * we need to zero the time, we first try to do it using the DateTime class
           */
          $the_Date = new DateTime($string);
          if (is_object($the_Date)) {
            $the_Date->setTime(0, 0);
            $string = $the_Date->format(self::$date_format);
          } else {
            /*
             * remove the time portion of the timestamp
             */
           $string = preg_replace('# [0-9]{2}:[0-9]{2}:[0-9]{2}$#', '', $string);
           $string .= ' 00:00 -0';
         }
       }
      }
      /*
       * @version 1.6
       * Most of the time, the default PHP timezone is the current setting, but 
       * experience has shown it's necessary to reset it for the conversion to make 
       * sure. We also must assume that the database server and PHP server are on 
       * the same TZ.
       */
      date_default_timezone_set(self::get_timezone());
      setlocale(LC_ALL, get_locale());
      /*
       * @version 1.6
       * added strptime method for locaized dates
       * 
       * this only works for known formats...so timestamps and when "strict dates" is enabled
       */
      if (function_exists('strptime') && (self::plugin_setting_is_true('strict_dates', false) || $is_MySQL_timestamp)) {
        self::$date_mode = 'strptime';
        if ($is_MySQL_timestamp) {
          $format = '%Y-%m-%d';
        } else {
          $format_setting = Participants_Db::plugin_setting_is_set('input_date_format') ? Participants_Db::plugin_setting('input_date_format') : get_bloginfo('date_format');
          $format = Participants_Db::translate_date_format($format_setting, 'strftime');
        }
        $date_array = strptime($string, $format);
        $date = mktime(
                $date_array['tm_hour'], $date_array['tm_min'], $date_array['tm_sec'], $date_array['tm_mon'] + 1, $date_array['tm_mday'], $date_array['tm_year'] + 1900
                );
      }
      if ($date === false) {
        self::$date_mode = 'strtotime';
        $date = strtotime(self::date_order_fix($string));
      }
    }
    return $date;
  }
  
  /**
   * inverts the month and date figures so that strtotime can work on European date strings
   * 
   * this only kicks in if the month/day numeric date order is detected in the general date settings
   * 
   * @param string $string the date string as written
   * 
   * @return string
   */
  public static function date_order_fix($string) {
    // first check if it's a numeric date divided by dashes, dots or slashes
    preg_match('/([.\/-])/', $string, $matches);
    $divider = empty($matches) ? false : $matches[1];
    if ($divider) {
      // now check the order to see if it's a European day/month/year type numeric date
      $date_parts = explode($divider, self::$date_format);
      $day_index = array_search('d',$date_parts) !== false ? array_search('d',$date_parts) : array_search('j',$date_parts);
      $month_index = array_search('m',$date_parts) !== false ? array_search('m',$date_parts) : array_search('n',$date_parts);
      if ( $day_index !== false && $month_index !== false && $day_index < $month_index ) {
        // yes, it's a European-style date
        self::$date_mode .= ' european';
        return self::reorder_date_string($string, $divider);
      }
    }
    return $string;
  }
  /**
   * re-orders the date string so it can be parsed by strtotime
   * 
   * @param string the date string
   * @params string the divider string
   * 
   * @return string the reordered string
   */
  public static function reorder_date_string($string, $divider) {
    list($day, $month, $year) = explode($divider, $string);
    return sprintf("%s$divider%s$divider%s", $month, $day, $year);
  }
  
  /**
   * shows a validation error message
   * 
   * @param string $error the message to show
   * @param string $name the field on which the error was called
   */
  private static function _show_validation_error( $error, $name = '', $overwrite = true ) {
    if (is_object(self::$validation_errors)) self::$validation_errors->add_error($name, $error, $overwrite);
    else self::set_admin_message($error);
  }
  
  /**
   * sets up a few internationalization words
   */
  private static function _set_i18n() {
    
    self::$i18n = array(
        'submit' => __('Submit','participants-database'),
        'apply' => __('Apply','participants-database'),
        'next' => __('Next','participants-database'),
        'previous' => __('Previous','participants-database'),
        'updated' => __('The record has been updated.','participants-database'),
        'added' => __('The new record has been added.','participants-database'),
        'zero_rows_error' => __('No record was added on query: %s','participants-database'),
        'database_error' => __('Database Error: %2$s on query: %1$s','participants-database')
    );
  }

  /**
   * sets some custom body classes in the admin
   * 
   * @param array $classes
   */
  public static function add_admin_body_class($class) {
    if (self::has_dashicons()) {
      $class .= ' has-dashicons ';
    }
    return $class;
  }
  
  /**
   * sets some custom body classes
   * 
   * @param array $classes
   */
  public static function add_body_class($classes) {
    if (self::has_dashicons()) {
      $classes[] = 'has-dashicons';
    }
    global $post;
    $shortcodes = is_object($post) ? self::get_plugin_shortcodes($post->post_content) : '';
    if (!empty($shortcodes)) {
      $classes[] = 'participants-database-shortcode';
      foreach ($shortcodes as $shortcode) {
        $classes[] = $shortcode;
      }
    }
    return $classes;
  }
  /**
   * checks the WP version for the availability of dashicon fonts
   * 
   * @return bool true if the font is available
   */
  public static function has_dashicons() {
    return version_compare(get_bloginfo('version'), '3.8', '>=');
  }
 
/**
   * prints an admin page heading
   *
   * @param text $text text to show if not the title of the plugin
   */
  public static function admin_page_heading($text = false) {
    
    $text = $text ? $text : self::$plugin_title;
    ?>
    <div class="icon32" id="icon-users"></div><h2><?php echo $text ?></h2>
    <?php
    self::admin_message();
  }

  /**
   * prints a credit footer for the plugin
   *
   * @return null
   */
  public static function plugin_footer() {
    ?>
    <div id="PDb_footer" class="widefat redfade postbox">
      <div class="section">
        <h4><?php echo 'Participants Database ', self::$plugin_version ?><br /><?php _e('WordPress Plugin', 'participants-database') ?></h4>
        <p><em><?php _e('Helping organizations manage their volunteers, members and participants.', 'participants-database') ?></em></p>
      </div>
      <div class="section">
        <h4><a class="glyph-link" href="http://xnau.com"><span class="icon-xnau-glyph"></span></a><?php _e('Developed by', 'participants-database') ?><br /><a href="http://xnau.com">xn<span class="lowast">&lowast;</span>au webdesign</a></h4>
        <p><?php _e('Suggestions or criticisms of this plugin? I&#39;d love to hear them: email ', 'participants-database') ?><a href="mailto:support@xnau.com">support@xnau.com.</a>
      </div>
      <div class="section">
        <p><?php printf(__('Please consider contributing to the continued support and development of this software by visiting %1$sthis plugin&#39;s page,%3$s giving the plugin a %2$srating%3$s or review, or dropping something in the %1$stip jar.%3$s Thanks!', 'participants-database'), '<a href="http://xnau.com/wordpress-plugins/participants-database#donation-link">', '<a href="http://wordpress.org/extend/plugins/participants-database/">', '</a>') ?></p>
      </div>
    </div>
    <?php
  }

  /**
   * parses the text header to extract plugin info
   * 
   * @param string $key the name of the field to get
   */
  private static function _get_plugin_data($key = 'Name') {

    if (!defined('ABSPATH'))
      return '';

    if (!function_exists('get_plugin_data'))
      include ABSPATH . '/wp-admin/includes/plugin.php';

    $plugin_data = get_plugin_data(__FILE__);

    return $plugin_data[$key];
  }
  /**
   * filters the plugins action links shown on the plugins page to add a link to 
   * the settings page
   * 
   * @param array $links
   * @return array
   */
  public static function add_plugin_action_links($links) {
    return array_merge($links, array('settings' => '<a href="' . admin_url('admin.php?page=participants-database_settings_page') . '">' . __('Settings', 'participants-database') . '</a>'));
  }

  /**
   * adds links and modifications to plugin list meta row
   * 
   * @param array  $links
   * @param string $file
   * @return array
   */
  public static function add_plugin_meta_links($links, $file) {

    $plugin = plugin_basename(__FILE__);

    // create link
    if ($file == $plugin) {
    
      //error_log( ' meta links: '.print_r( $links,1 ));
      
      $links[1] = str_replace('Roland Barker','<span class="icon-xnau-glyph"></span> xn*au webdesign',$links[1]);
      $links[] = '<a href="http://wordpress.org/support/view/plugin-reviews/participants-database">'.__('Submit a rating or review','participants-database').' </a>';
      $links[] = '<span style="color:#6B4001;">'.__('Free tech support and continued development relies on your support:','participants-database').' <a class="button xnau-contribute" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=6C7FSX2DQFWY4">'.__('contribute','participants-database').'</a></span>';
    }
    return $links;
  }
    
}

// class


/**
 * performs the class autoload
 * 
 * @param string $class the name of the class to be loaded
 */
function PDb_class_loader($class) {

  if (!class_exists($class)) {

    $class_file = plugin_dir_path(__FILE__) . 'classes/' . $class . '.class.php';

    if (is_file($class_file)) {

      require_once $class_file;
    }
  }
}
Participants_Db::initialize();