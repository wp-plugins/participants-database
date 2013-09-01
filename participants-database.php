<?php
/*
  Plugin Name: Participants Database
  Plugin URI: http://xnau.com/wordpress-plugins/participants-database
  Description: Plugin for managing a database of participants, members or volunteers
  Author: Roland Barker
  Version: 1.5
  Author URI: http://xnau.com
  License: GPL2
  Text Domain: participants-database
 */

/*
 * Copyright 2011, 2012, 2013 Roland Barker xnau webdesign  (email : webdesign@xnau.com)
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
/**
 * main static class for running the plugin
 * 
 * @category   WordPress Plugins
 * @package    wordPress
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2011 - 2013 7th Veil, LLC
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL2
 * @version    Release: 1.5
 * 
 */
class Participants_Db {
  
  // this is same as the plugin directory name
  const PLUGIN_NAME = 'participants-database';

  // display title
  public static $plugin_title;
  // main participants index table
  public static $participants_table;
  // table for all associated values
  public static $fields_table;
  // table for groups defninitions
  public static $groups_table;
  // current Db version
  public static $db_version = '0.6';
  // name of the WP option where the current db version is stored
  public static $db_version_option = 'PDb_Db_version';
  // current version of plugin
  public static $plugin_version;
  // plugin options name
  public static $participants_db_options;
  public static $plugin_options;
  // holds the plugin settings object
  public static $plugin_settings;
  // locations
  public static $plugin_page;
  public static $plugin_path;
  public static $plugin_url;
  // file uploads
  public static $uploads_path;
  public static $allowed_extensions;
  // arrays for building default field set
  public static $internal_fields;
  public static $main_fields;
  public static $admin_fields;
  public static $personal_fields;
  public static $source_fields;
  public static $field_groups;
  
  // this is a general-use prefix to set a namespace
  public static $css_prefix = 'pdb-';
  // holds the form validation errors
  public static $validation_errors;
  // holds an admin status or error message
  static $admin_message = '';
  // holds the type of admin message
  static $admin_message_type;
  // name of the transient record used to hold the last record
  public static $last_record;
  // this gets set if a shortcode is called on a page
  public static $shortcode_present;
  // status code for the last record processed
  public static $insert_status;
  // these columns are not manually edited
  public static $internal_columns;
  // header to include with plugin emails
  public static $email_headers;
  // enclosure character to use
  static $CSV_enclosure = '"';
  // list of reserved field names
  public static $reserved_names = array('source', 'subsource', 'id', 'private_id', 'record_link', 'action', 'submit', 'name', 'day', 'month', 'year', 'hour', 'date', 'minute');
  // true while sending an email
  public static $sending_email = false;
  // set of internationalized words
  public static $i18n = array();
  // the date format
  public static $date_format;
  // index for tracking multiple instances of a shortcode
  public static $instance_index = 0;

  /**
   * initializes the static class
   * 
   * sets up the class autoloading, configuration values, hooks, filters and shortcodes
   * 
   * @global object $wpdb
   */
  public static function initialize() {

    // register the class autoloading
    spl_autoload_register('PDb_class_loader');

    // set the table names
    global $wpdb;
    self::$participants_table = $wpdb->prefix . str_replace('-', '_', self::PLUGIN_NAME);
    self::$fields_table = self::$participants_table . '_fields';
    self::$groups_table = self::$participants_table . '_groups';

    // set the plugin version
    self::$plugin_version = self::_get_plugin_data('Version');

    // define some locations
    self::$participants_db_options = self::PLUGIN_NAME . '_options';
    self::$plugin_page = self::PLUGIN_NAME;
    self::$plugin_path = dirname(__FILE__);
    self::$plugin_url = WP_PLUGIN_URL . '/' . self::PLUGIN_NAME;
    // this is relative to the WP install
    self::$uploads_path = 'wp-content/uploads/' . self::PLUGIN_NAME . '/';

    self::$last_record = self::$css_prefix . 'last_record';

    // install/deactivate and uninstall methods are handled by the PDB_Init class
    register_activation_hook(__FILE__, array('PDb_Init', 'on_activate'));
    register_deactivation_hook(__FILE__, array('PDb_Init', 'on_deactivate'));
    register_uninstall_hook(__FILE__, array('PDb_Init', 'on_uninstall'));

    add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array(__CLASS__, 'add_plugin_action_links') );
    add_filter( 'plugin_row_meta', array(__CLASS__, 'add_plugin_meta_links'), 10, 2 );

    // set the WP hooks to finish setting up the plugin
    add_action('init',                  array(__CLASS__, 'init'));
    add_action('admin_menu',            array(__CLASS__, 'plugin_menu'));
    add_action('admin_init',            array(__CLASS__, 'admin_init'));
    add_action('wp_enqueue_scripts',    array(__CLASS__, 'include_scripts'));
    add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_includes'));
    add_action('wp_footer',             array(__CLASS__, 'add_scripts'));

    // handles ajax request from list filter
    add_action('wp_ajax_pdb_list_filter',        array(__CLASS__, 'pdb_list_filter'));
    add_action('wp_ajax_nopriv_pdb_list_filter', array(__CLASS__, 'pdb_list_filter'));

    // define our shortcodes
    add_shortcode('pdb_record',        array(__CLASS__, 'record_edit'));
    add_shortcode('pdb_signup',        array(__CLASS__, 'print_signup_form'));
    add_shortcode('pdb_signup_thanks', array(__CLASS__, 'print_signup_thanks_form'));
    add_shortcode('pdb_list',          array(__CLASS__, 'print_list'));
    add_shortcode('pdb_single',        array(__CLASS__, 'print_single_record'));

    //PDb_Init::db_integrity_check();
        }

  /**
   * performs a fix for some older versions of the plugin; does nothing with current plugins
   */
  public static function admin_init() {

    // if the setting was made in previous versions and is a slug, convert it to a post ID
    $regpage = self::$plugin_options['registration_page'];
    if (isset($regpage) && !is_numeric($regpage)) {

      self::$plugin_options['registration_page'] = self::get_id_by_slug($regpage);

      update_option(self::$participants_db_options, self::$plugin_options);
    }
  }
  /**
   * initializes the plugin in the WP environment
   * @return null
   */
  public static function init() {
    
    session_start();

    load_plugin_textdomain('participants-database', false, dirname(plugin_basename(__FILE__)) . '/languages/');

    self::$plugin_title = __('Participants Database', 'participants-database');
    
    self::_set_i18n();
    
    /*
     * checks for the need to update the DB
     * 
     * this is to allow for updates to occur in many different ways
     */
    if ( false === get_option( Participants_Db::$db_version_option ) || get_option( Participants_Db::$db_version_option ) != Participants_Db::$db_version )
      PDb_Init::on_update();

    // get the plugin options array
    if (!isset(self::$plugin_options)) {

      self::$plugin_options = get_option(self::$participants_db_options);
    }
    
    self::$date_format = get_option('date_format');
    
    if (0 != self::$plugin_options['html_email']) {
      $type = 'text/html; charset="' . get_option('blog_charset') . '"';
      //add_filter('wp_mail_content_type', array( __CLASS__, 'set_content_type'));
    } else {
      $type = 'text/plain; charset=us-ascii';
    }
    self::$email_headers = "MIME-Version: 1.0\n" .
            "From: " . self::$plugin_options['receipt_from_name'] . " <" . self::$plugin_options['receipt_from_address'] . ">\n" .
            "Content-Type: " . $type . "\n";

    /*
     * instantiate the settings class; this only sets up the settings definitions, 
     * the WP Settings API may not be available at this point, so we register the 
     * settings on the 'admin_menu' hook
     */
    self::$plugin_settings = new PDb_Settings();

    // this processes form submits before any output so that redirects can be used
    self::process_page_request();
  }
  
  /**
   * sets up the plugin admin menus
   * 
   * @return null
   */
  public static function plugin_menu() {

    /*
     * intialize the plugin settings for the plugin settings pages
     */
    if (is_object(self::$plugin_settings)) {
      self::$plugin_settings->initialize();
    } else {
    self::$plugin_settings = new PDb_Settings();
    }

    // define the plugin admin menu pages
    add_menu_page(
            self::$plugin_title,
            self::$plugin_title, 
            '', 
            self::PLUGIN_NAME, array(__CLASS__, 'include_admin_file')
    );
    
    add_submenu_page(
            self::PLUGIN_NAME, 
            __('List Participants', 'participants-database'), 
            __('List Participants', 'participants-database'), 
            self::$plugin_options['record_edit_capability'], 
            self::$plugin_page . '-list_participants', 
            array('PDb_List_Admin', 'initialize')
    );

    add_submenu_page(
            self::PLUGIN_NAME, 
            __('Add Participant', 'participants-database'), 
            __('Add Participant', 'participants-database'), 
            self::$plugin_options['record_edit_capability'], 
            self::$plugin_page . '-edit_participant', 
            array(__CLASS__, 'include_admin_file')
    );

    add_submenu_page(
            self::PLUGIN_NAME, 
            __('Manage Database Fields', 'participants-database'), 
            __('Manage Database Fields', 'participants-database'), 
            'manage_options', 
            self::$plugin_page . '-manage_fields', 
            array(__CLASS__, 'include_admin_file')
    );

    add_submenu_page(
            self::PLUGIN_NAME, 
            __('Import CSV File', 'participants-database'), 
            __('Import CSV File', 'participants-database'), 
            'manage_options', 
            self::$plugin_page . '-upload_csv', 
            array(__CLASS__, 'include_admin_file')
    );

    add_submenu_page(
            self::PLUGIN_NAME, 
            __('Settings', 'participants-database'), 
            __('Settings', 'participants-database'), 
            'manage_options', 
            self::$plugin_page . '_settings_page', 
            array(self::$plugin_settings, 'show_settings_form')
    );

    add_submenu_page(
            'participants-database', 
            __('Setup Guide', 'participants-database'), 
            __('Setup Guide', 'participants-database'), 
            'manage_options', 
            self::$plugin_page . '-setup_guide', 
            array(__CLASS__, 'include_admin_file')
    );

    add_submenu_page(
            '', 
            __('Edit Record', 'participants-database'), 
            __('Edit Record', 'participants-database'), 
            self::$plugin_options['record_edit_capability'], 
            self::$plugin_page . '_edit_participant'
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

    wp_register_script(self::$css_prefix.'cookie', plugins_url('js/jquery_cookie.js', __FILE__));
    wp_register_script(self::$css_prefix.'manage_fields', plugins_url('js/manage_fields.js', __FILE__));
    wp_register_script(self::$css_prefix.'settings_script', plugins_url('js/settings.js', __FILE__));
    wp_register_script(self::$css_prefix.'jq-placeholder', plugins_url('js/jquery.placeholder.min.js', __FILE__), array('jquery'));
    wp_register_script(self::$css_prefix.'admin', plugins_url('js/admin.js', __FILE__), array('jquery'));
    //wp_register_script( 'datepicker', plugins_url( 'js/jquery.datepicker.js', __FILE__ ) );
    //wp_register_script( 'edit_record', plugins_url( 'js/edit.js', __FILE__ ) );

    if (false !== stripos($hook, 'participants-database')) {
      wp_enqueue_script('jquery');
      wp_enqueue_script('jquery-ui-core');
      wp_enqueue_script('jquery-ui-tabs');
      wp_enqueue_script('jquery-ui-sortable');
      wp_enqueue_script('jquery-ui-dialog');
      wp_enqueue_script(self::$css_prefix.'cookie');
      wp_enqueue_script(self::$css_prefix.'jq-placeholder');
      wp_enqueue_script(self::$css_prefix.'admin');
    }

    if (false !== stripos($hook, 'participants-database-list_participants')) {
      wp_localize_script(self::$css_prefix.'admin-list', 'L10n', array(
          "record" => __("Do you really want to delete the selected record?", 'participants-database' ),
          "records" => __("Do you really want to delete the selected records?", 'participants-database' ),
      ));
    }

    if (false !== stripos($hook, 'participants-database_settings_page')) {
      wp_enqueue_script(self::$css_prefix.'settings_script');
    }

    if (false !== stripos($hook, 'participants-database-manage_fields')) {
      wp_localize_script(self::$css_prefix.'manage_fields', 'L10n', array(
      /* translators: don't translate the words in brackets {} */
          'must_remove' => '<h4>' . __('You must remove all fields from the {name} group before deleting it.', 'participants-database') . '</h4>',
          'delete_confirm' => '<h4>' . __('Delete the "{name}" {thing}?', 'participants-database') . '</h4>',
      ));
      wp_enqueue_script(self::$css_prefix.'manage_fields');
    }

    wp_register_style('pdb-global-admin', plugins_url('/css/PDb-admin-global.css', __FILE__), false, false);
    wp_register_style('pdb-admin', plugins_url('/css/PDb-admin.css', __FILE__));
    wp_register_style('pdb-frontend', plugins_url('/css/participants-database.css', __FILE__));

    wp_enqueue_style('pdb-global-admin');

    // only incude these stylesheets on the plugin admin pages
    if (false !== stripos($hook, 'participants-database')) {
      wp_enqueue_style('pdb-admin');
      wp_enqueue_style('pdb-frontend');
    }
  }

  /**
   * includes any javascript needed for the front end
   * 
   * fired on WP hook 'wp_enqueue_scripts'
   * 
   * @return null
   */
  public function include_scripts() {

    // set the global shortcode flag
    self::$shortcode_present = false;

    wp_register_style('pdb-frontend', plugins_url('/css/participants-database.css', __FILE__));
    wp_register_style('custom_plugin_css', plugins_url('/css/custom_css.php', __FILE__));

    if (self::$plugin_options['use_plugin_css']) {

      wp_enqueue_style('pdb-frontend');
      wp_enqueue_style('custom_plugin_css');
    }

    wp_register_script(self::$css_prefix.'shortcode', plugins_url('js/shortcodes.js', __FILE__), array('jquery'));
    wp_register_script(self::$css_prefix.'list-filter', plugin_dir_url(__FILE__) . 'js/list-filter.js', array('jquery'));
    wp_register_script(self::$css_prefix.'jq-placeholder', plugins_url('js/jquery.placeholder.min.js', __FILE__), array('jquery'));

    // this is now handled conditionally in the wp_footer action
    //wp_enqueue_script( 'jquery' );
    //wp_enqueue_script( 'pdb-shortcode' );
    //wp_enqueue_script( 'jq-placeholder');
  }

  /**
   * conditionally adds JS to pages that have one of our shortcodes
   * 
   * fired on 'wp_footer' hook
   * 
   * @return null
   */
  public function add_scripts() {

    if (false !== self::$shortcode_present) {
      wp_enqueue_script('jquery');
      wp_enqueue_script(self::$css_prefix.'shortcode');
      wp_enqueue_script(self::$css_prefix.'jq-placeholder');
    }
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
  public function include_admin_file()
  {

    $parts = explode('-', $_GET['page']);

    $file = array_pop($parts);

    if (is_file(plugin_dir_path(__FILE__) . $file . '.php')) {

      // we'll need this in the included file
      global $wpdb;

      include $file . '.php';
    }
  }

  /**
   * shows the frontend edit screen called by the [pdb_record] shortcode
   *
   * requires the 'pid' value in the URI or 'id' attribute in the shortcode
   * 
   * @param array $atts array of attributes drawn from the shortcode
   * @return string the HTML of the record edit form
   */
  function record_edit($atts) {

    $atts['id'] = isset($_GET['pid']) ? self::get_participant_id($_GET['pid']) : ( isset($atts['id']) ? $atts['id'] : false );

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
  private function _record_access($id) {

    global $wpdb;

    $sql = 'UPDATE ' . self::$participants_table . ' SET `last_accessed` = NOW() WHERE `id` = %s';

    return $wpdb->query($wpdb->prepare($sql, $id));
  }

  /**
   * sets the last_accessed timestamp
   * 
   * @param int $id id of the record to update
   */
  public function set_record_access($id) {

    self::_record_access($id);
  }


  /**
   * prints a single record called by [pdb_list] shortcode
   * 
   * @param array $params the parameters passed in by the shortcode
   * @return string the output HTML
   */
  public function print_list($params) {

    return PDb_List::get_list($params);
  }

  /**
   * prints a single record called by [pdb_single] shortcode
   * 
   * @param array $params the parameters passed in by the shortcode
   * @return string the output HTML
   */
  public function print_single_record($params) {

    return PDb_Single::print_record($params);
  }

  /**
   * prints a form from the Signup class
   * 
   * @param array $params the parameters from the shortcode
   * @return string the output HTML
   */
  public function print_signup_class_form($params) {

    $params['post_id'] = get_the_ID();

    return PDb_Signup::print_form($params);
  }

  /**
   * prints a signup form
   * 
   * @param array $params the parameters passed in by the shortcode
   * @return string the output HTML
   */
  public function print_signup_form($params) {

    $params['type'] = 'signup';

    return self::print_signup_class_form($params);  
  }

  /**
   * prints the signup thanks form
   * 
   * @param array $params the parameters passed in by the shortcode
   * @return string the output HTML
   */
  public function print_signup_thanks_form($params) {
  
    $params['type'] = 'thanks';

    return self::print_signup_class_form($params);
  }

  /**
   * get all the attributes of a field by it's name
   * 
   * an attribute or comma-separated list of attributes can be specified if not, 
   * a default list of attributes is retrieved
   * 
   * @global object $wpdb
   * @param string $field the name of the field to get
   * @param string $atts
   * @return stdClass 
   */
  public function get_field_atts($field = false, $atts = '*') {

    global $wpdb;
    
    if ($field) {
      $exists = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . self::$fields_table . ' f WHERE f.name = %s', $field));
    }

    if (!$field or $exists == 0) {

      $return = new stdClass;
      $return->form_element = '';
      return $return;
    }

    $sql = "
		SELECT " . $atts . " 
		FROM " . self::$fields_table . " f 
		WHERE f.name = %s";

    //error_log( __METHOD__.' called with:'. $sql );

    $field_atts = $wpdb->get_row($wpdb->prepare($sql, $field));

    return $field_atts;
  }

  /**
   * get an array of groups
   *
   * @param string $column comma-separated list of columns to get, defaults to all (*)
   * @param mixed $exclude single group to exclude or array of groups to exclude
   * @return indexed array
   */
  public function get_groups($column = '*', $exclude = false) {

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
  public function get_persistent() {

    return self::get_subset('persistent');
  }

  /**
   * gets a list of field names/titles
   * 
   * assembles a list of columns from those columns set to display. Optionally, 
   * a list of fields can be supplied with an array. This allows fields that are 
   * not displayed to be included.
   *
   * @param string $type   if 'sortable' will only select fields flagged as sortable  
   * @param array  $fields array of field names defining the fields listed for the 
   *                       purpose of overriding the default selection
   * @param string $sort   sorting method to use, can be 'order' which uses the
   *                       defined group/field order, 'column' which uses the
   *                       current display column order or 'alpha' which sorts the
   *                       list alphabetially; defaults to 'column'
   * @return array
   */
  public function get_field_list($type = false, $fields = false, $sort = 'column') {

    global $wpdb;
    
    $where_clauses = array();
    if ($type == 'sortable') {
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

    // construct an array of this form: name => title
    $return = array();
    foreach ($result as $item)
      $return[$item[1]] = $item[0];

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
  public function get_sortables($fields = false, $sort = 'column') {

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
  private function get_subset($subset) {

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
   * @return object
   */
  public function get_column($name) {
    
    global $wpdb;
    
    $sql = 'SELECT * 
		        FROM ' . self::$fields_table . ' f
            WHERE f.name = %s
            LIMIT 1';
    
    return $wpdb->get_row($wpdb->prepare($sql, $name));
  }

  /**
   * checks a string against active columns to validate input
   */
  public function is_column($string) {

    global $wpdb;

    $sql = 'SELECT COUNT(*)
		        FROM ' . self::$fields_table . ' f
            WHERE f.name = %s';

    $count = $wpdb->get_var($wpdb->prepare($sql,$string));

    return $count > 0;
  }

  /**
   * gets the field attributes as filtered by the type of form to display
   *
   * @param string $filter sets the context of the display and determines the set of columns to return
   * @return object the object is ordered first by the order of the group, then by the field order
   */
  public static function get_column_atts($filter = 'new') {

    global $wpdb;

    switch ($filter) {

      case 'signup':

        $where = 'WHERE v.signup = 1 ';
        break;

      case 'sortable':

        $where = 'WHERE v.sortable = 1 ';
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

      case 'frontend':

        $where = 'WHERE g.display = 1 AND v.readonly = 0';
        break;

      case 'readonly':

        $where = is_admin() ? 'WHERE v.group = "internal"' : 'WHERE v.group = "internal" OR v.readonly = 1';
        break;

      case 'new':
      case 'backend':
      default:

        $where = 'WHERE v.name NOT IN ( "id", "captcha" ) ';
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
  public function single_record_fields($id, $exclude = '') {

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
   * @param array  $post           the array of new values (typically the $_POST array)
   * @param string $action         the db action to be performed: insert or update
   * @param mixed  $participant_id the id of the record to update. If it is false, it creates
   *                               a new record, if true, it creates or updates the default record.
   *
   * @return unknown int ID of the record created or updated, bool false if 
   *                 submission does not validate
   */
  public function process_form($post, $action, $participant_id = false) {

    global $wpdb;

    if (!empty($_FILES) && !isset($_POST['csv_file_upload'])) {

      foreach ($_FILES as $fieldname => $attributes) {

        if (UPLOAD_ERR_NO_FILE == $attributes['error'])
          continue;

        // place the path to the file in the field value
        $filepath = self::_handle_file_upload($fieldname, $attributes);

        if (false !== $filepath) {

          $post[$fieldname] = $filepath;
          
          $_POST[$fieldname] = basename($filepath);
          
        }
      }
    }

    /*
     * checks for a record with a matching field so we can exercise the
     * duplicate record preference
     */
    if ($action == 'insert' and self::$plugin_options['unique_email'] !== 0) {

      $match_field = self::$plugin_options['unique_field'];

      if (isset($post[$match_field]) && !empty($post[$match_field]) && self::field_value_exists($post[$match_field], $match_field)) {

        switch (self::$plugin_options['unique_email']) {

          case 1:

            // record with same field value exists...get the id and update the existing record
            if ('id' == strtolower($match_field))
              $participant_id = $post[$match_field];
            else
              $participant_id = self::_get_participant_id_by_term($match_field, $post[$match_field]);
            // get the first one
            if (is_array($participant_id))
              $participant_id = current($participant_id);
            
            // mark the record as not sent so the private link will be resent
            PDb_Signup::update_sent_status($participant_id, false);
            // set the update mode
            $action = 'update';

            break;

          case 2:

            // set the error message
            if (is_object(self::$validation_errors))
              self::$validation_errors->add_error($match_field, 'duplicate');
            $action = 'skip';
            // go on validating the rest of the form
            break;
        }
      } elseif ( self::$plugin_options['unique_email'] == 1 and 'id' == strtolower($match_field) and isset($post[$match_field]) ) {
        /*
         * if the "OVERWRITE" option is set to "id" and the record contains an id, use it to create the record
         */
        $participant_id = intval($post[$match_field]);
        if ( 0 !== $participant_id ) {
          $action = 'insert';
          // mark the record as not sent so the private link will be resent
          PDb_Signup::update_sent_status($participant_id, false);
        } else $participant_id = false;
      }
    }
    // set the insert status value
    self::$insert_status = $action;

    switch ($action) {

      case 'update':
        $sql = 'UPDATE ' . self::$participants_table . ' SET date_updated = NOW(), ';
        $where = " WHERE id = " . $participant_id;
        break;

      case 'insert':
        $sql = 'INSERT INTO ' . self::$participants_table . ' SET date_recorded = NOW(), date_updated = NOW(), ';
        $where = '';
        break;

      case 'skip':
        return false;
    }

    $new_values = array();
    $columns = array();

    // determine the set of columns to process
    if ( isset($_POST['action']) && $_POST['action'] == 'signup') {
      
      $column_set = 'signup';
    } else {
      
      $column_set = $action == 'update' ? ( is_admin() ? 'backend' : 'frontend' ) : ( $participant_id ? 'all' : 'new' );
    }

    // gather the submit values and add them to the query
    foreach (self::get_column_atts($column_set) as $column_atts) :
      
      //error_log(__METHOD__.' testing:'.print_r($column_atts,1));
    
      // the validation object is only instantiated when this method is called
      // by a form submission
      if (is_object(self::$validation_errors)) {

        self::$validation_errors->validate(( isset($post[$column_atts->name]) ? $post[$column_atts->name] : ''), $column_atts, $post);
      }
      $new_value = false;
      // we can process individual submit values here
      switch ($column_atts->name) {

        case 'id':
          $new_value = $participant_id;
          break;
        
        case 'date_recorded':
          
          if ($action == 'insert' ) {
            $new_value = false;
          } else {
            $new_value = isset($post['date_recorded']) ? $post['date_recorded'] : false ;
          }
          break;

        case 'date_updated':
        case 'last_accessed':
          $new_value = false;
          break;

        case 'private_id':
          $new_value = empty($post['private_id']) ? self::generate_pid() : $post['private_id'];
          break;

        default :

          // replace unsubmitted fields with the default if defined
          if (!empty($column_atts->default)) {
              if (
                      !isset($post[$column_atts->name]) or
                      (@empty($post[$column_atts->name]) and $column_atts->form_element != 'hidden' and $action == 'insert')
              ) {
            $new_value = $column_atts->default;
            $post[$column_atts->name] = $new_value;
              }
          } elseif (!isset($post[$column_atts->name])) {
            continue;
          }
          
          switch ($column_atts->form_element) {

            case 'multi-checkbox':
            case 'multi-select-other':
              /* match the items in the comma-separated list against the preset
               * values of the multi-select. Any extra values are placed in an
               * 'other' array element
               */
              if (is_array($post[$column_atts->name])) {

                $value_array = $post[$column_atts->name];
              } else {

                // build the value array from the string form used in CSV files
                $value_array = array();
                $incoming_value = preg_split('#([ ]*,[ ]*)#', trim($post[$column_atts->name]));
                $field_values = self::unserialize_array($column_atts->values);

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

              $new_value = self::_prepare_array_mysql($value_array);
              break;

            case 'link':
              /* translate the link markdown used in CSV files to the array format used in the database
               */

              if (!is_array($post[$column_atts->name])) {

                $new_value = self::_prepare_array_mysql(self::get_link_array($post[$column_atts->name]));
              } else {

                $new_value = self::_prepare_array_mysql($post[$column_atts->name]);
              }
              break;

            case 'rich-text':
              global $allowedposttags;
              $new_value = wp_kses(stripslashes($post[$column_atts->name]), $allowedposttags);
              break;

            case 'date':
              $date = false;
              if (isset($post[$column_atts->name]))
                $date = self::parse_date($post[$column_atts->name], $column_atts);

              $new_value = $date ? $date : false;
              break;

            case 'captcha':
              $new_value = false;
              break;

            case 'password':
              if (!empty($post[$column_atts->name])) {
                $new_value = wp_hash_password(trim($post[$column_atts->name]));
              } else {
                $new_value = false;
              }
              break;
              
            case 'image-upload':
            case 'file-upload':
              
              if (isset($_POST[$column_atts->name . '-deletefile']) and $_POST[$column_atts->name . '-deletefile'] === 'delete') {
                if (self::$plugin_options['file_delete'] == 1 or is_admin() ) {
                  error_log(__METHOD__.' attempting to delete:'.$post[$column_atts->name]);
                  self::delete_file($post[$column_atts->name]);
                }
                unset($_POST[$column_atts->name]);
                $post[$column_atts->name] = '';
              }
              $new_value = self::_prepare_string_mysql(trim($post[$column_atts->name]));
              break;

            default:
              if (!self::backend_user() && $column_atts->readonly != '0') {

                $new_value = false;
              } elseif (is_array($post[$column_atts->name])) {

                $new_value = self::_prepare_array_mysql($post[$column_atts->name]);
              } else {

                $new_value = self::_prepare_string_mysql(trim($post[$column_atts->name]));
              }
          } // switch column_atts->form_element

//          if (in_array($column_atts->form_element, array('multi-checkbox', 'multi-select-other'))) {
//
//            /* match the items in the comma-separated list against the preset
//             * values of the multi-select. Any extra values are placed in an
//             * 'other' array element
//             */
//            if (is_array($post[$column_atts->name])) {
//
//              $value_array = $post[$column_atts->name];
//            } else {
//
//              // build the value array from the string form used in CSV files
//              $value_array = array();
//              $incoming_value = preg_split('#([ ]*,[ ]*)#', trim($post[$column_atts->name]));
//              $field_values = self::unserialize_array($column_atts->values);
//
//              foreach ($incoming_value as $v) {
//
//                if (in_array($v, $field_values)) {
//
//                  $value_array[] = $v;
//                } else {
//
//                  $value_array['other'][] = $v;
//                }
//              }
//
//              if (isset($value_array['other']) && is_array($value_array['other'])) $value_array['other'] = implode(',', $value_array['other']);
//            }
//
//            $new_value = self::_prepare_array_mysql($value_array);
//          } elseif ('link' == $column_atts->form_element) {
//
//            /* translate the link markdown used in CSV files to the array format used in the database
//             */
//
//            if (!is_array($post[$column_atts->name])) {
//
//              $new_value = self::_prepare_array_mysql(self::get_link_array($post[$column_atts->name]));
//            } else {
//              
//              $new_value = self::_prepare_array_mysql($post[$column_atts->name]);
//            }
//          } elseif ('rich-text' == $column_atts->form_element) {
//            
//            global $allowedposttags;
//            $new_value = wp_kses(stripslashes($post[$column_atts->name]), $allowedposttags);
//          } elseif ('date' == $column_atts->form_element) {
//
//            $date = false;
//            if (isset($post[$column_atts->name])) $date = self::parse_date($post[$column_atts->name], $column_atts);
//
//            $new_value = $date ? $date : false;
//          } elseif ('captcha' == $column_atts->form_element) {
//            
//            $new_value = false;
//          } elseif ('password' == $column_atts->form_element) {
//
//            if (!empty($post[$column_atts->name]))
//              $new_value = wp_hash_password(trim($post[$column_atts->name]));
//            else
//              $new_value = false;
//          } elseif (!self::backend_user() && $column_atts->readonly != '0') {
//
//            $new_value = false;
//          } elseif (is_array($post[$column_atts->name])) {
//
//            $new_value = self::_prepare_array_mysql($post[$column_atts->name]);
//          } else {
//
//            $new_value = self::_prepare_string_mysql(trim($post[$column_atts->name]));
//          }
      }  // swtich column_atts->name 

      // add the column and value to the sql
      if (false !== $new_value) {

        // insert a true NULL if the field is NULL
        if (NULL !== $new_value) {
          $new_values[] = $new_value;
        }
        $columns[] = "`" . $column_atts->name . "` = " . ( NULL === $new_value ? "NULL" : "%s" );
      }

    endforeach; // columns
    // if the validation object exists and there are errors, stop here
    if (is_object(self::$validation_errors) && self::$validation_errors->errors_exist()) {

      // error_log( __METHOD__.' errors exist; returning');

      return false;
    } elseif ( !empty( self::$admin_message ) and 'error' == self::$admin_message_type ) {
      return false;
    }

    // add in the column names
    $sql .= implode(', ', $columns);

    // add the WHERE clause
    $sql .= $where;

    if (WP_DEBUG)
      error_log(__METHOD__ . ' storing record sql=' . $sql . ' values:' . print_r($new_values, true));

    $wpdb->query($wpdb->prepare($sql, $new_values));

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
        // set the "email sent" flag for this id
        set_transient(self::$css_prefix . 'signup-email-sent', array($participant_id => true));
    }
    }

    return $participant_id;
  }

  /**
   * parses the markdown string used to store the values for a link form element
   *
   * will also accept a bare URL
   *
   * @param string $markdown_string
   * @return array URL, linktext
   */
  public function get_link_array($markdown_string) {

    if (preg_match('#^<([^>]+)>$#', trim($markdown_string), $matches)) {
      return array($matches[1], '');
    } elseif (preg_match('#^\[([^\]]+)\]\(([^\)]+)\)$#', trim($markdown_string), $matches)) {
      return array($matches[2], $matches[1]);
    } else return array((filter_var($markdown_string, FILTER_VALIDATE_URL) ? $markdown_string : ''), '');
    }

  /**
   * gets the default set of values
   *
   * @global object $wpdb
   * @return associative array
   */
  public function get_default_record() {

    $sql = 'SELECT f.name,f.default,f.form_element 
            FROM ' . self::$fields_table . ' f
            WHERE f.group != "internal"';

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

    if ( is_object( $current_user ) ) $default_record['by'] = $current_user->display_name;
    $default_record['when'] = date_i18n(self::$date_format);
    $default_record['private_id'] = self::generate_pid();
    $default_record['date_recorded'] = date('Y-m-d H:i:s');
    $default_record['date_updated'] = date('Y-m-d H:i:s');
    
    error_log(__METHOD__.' '.print_r($default_record,1));

    return $default_record;
  }

  /**
   * gets a participant record object with its id
   *
   * @ver 1.5 added $wpdb->prepare
   *
   * @global object $wpdb
   * @param  string $id the record ID; returns default record if omitted
   * @return unknown associative array of the record; false if no record matching
   *                 the ID was found 
   */
  public function get_participant($id = false) {

    if (false === $id)
      return self::get_default_record();

    // test that the id exists
    if (false === self::_id_exists($id))
      return false;

    global $wpdb;

    $sql = "SELECT * FROM " . self::$participants_table . " p WHERE p.id = %s";

    $result = $wpdb->get_row($wpdb->prepare($sql, $id), ARRAY_A);
    
    return array_merge($result, array('id' => $id));
  }

  /**
   * gets a participant id by private ID
   *
   * @param string $pid the private ID for a record
   * 
   * @return int the record ID
   *
   */
  public function get_participant_id($pid) {

    return self::_get_participant_id_by_term('private_id', $pid);
  }

  /**
   * finds the ID of a record given the value of one of it's fields. 
   * 
   * Returns the first of multiple matches
   * 
   * @param string $term
   * @param mixed $id
   * @return int
   */
  public function get_record_id_by_term($term, $id) {

    return self::_get_participant_id_by_term($term, $id);
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
   * @return unknown returns integer if one match, array of integers if multiple matches, false if no match
   */
  private function _get_participant_id_by_term($term, $value, $single = false) {

    global $wpdb;

    $sql = 'SELECT p.id FROM ' . self::$participants_table . ' p WHERE p.' . $term . ' = %s';
    $result = $wpdb->get_results($wpdb->prepare($sql, $value), ARRAY_A);

    if (!is_array($result))
      return false;

    $output = array();

    foreach ($result as $row) {

      $output[] = $row['id'];
    }

    return count($output) > 1 && ! $single ? $output : current($output);
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

    for ($i = 0; $i < 5; $i++) {

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
   */
  private function _id_exists($id, $field = 'id') {

    global $wpdb;

    $id_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . self::$participants_table . " p WHERE p." . $field . " = %s", $id));

    if (NULL !== $id_exists)
      return $id_exists < 1 ? false : true;
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
  public function next_id($id, $increment = true) {
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
  public function email_exists($email) {

    return self::_id_exists($email, 'email');
  }

  /**
   * returns true if a record has a value matching the checked field
   *
   * @param string $value the value of the field to test
   * @param string $field the field to test
   * @return bool true if match exists (only checks for the first one)
   */
  public function field_value_exists($value, $field) {

    return self::_id_exists($value, $field);
  }

  /*
   * prepares an array for storage in the database
   *
   * @param array $array
   * @return string prepped array in serialized form or empty if no data
   */

  private function _prepare_array_mysql($array) {

    if (!is_array($array))
      return self::_prepare_string_mysql($array);

    $prepped_array = array();
    
    $empty = true;

    foreach ($array as $key => $value) {

      if ( ! empty($value) ) $empty = false;
      $prepped_array[$key] = self::_prepare_string_mysql($value);
    }

    return $empty ? '' : serialize($prepped_array);
  }

  /**
   * prepares a string for storage
   *
   * gets the string ready by getting rid of slashes and converting quotes and
   * other undesirables to HTML entities
   * 
   * @param string $string the string to prepare
   */
  private function _prepare_string_mysql($string) {

    return htmlspecialchars(stripslashes($string), ENT_QUOTES, 'utf-8');
  }

  /**
   * unserializes a string if necessary
   * 
   * @param string $string the string to unserialize; does nothing if it is not 
   *                       a serialization
   * @return array or string if not a serialization
   */
  public static function unserialize_array($string) {

    // is_serialized is a WordPress utility function
    return is_serialized($string) ? unserialize($string) : $string;
  }

  /**
   * prepares a field for display 
   * 
   * displays an array as a series of comma-separated strings
   * 
   * @param string $string
   * @return string the prepared string
   */
  public function prepare_value($string) {

    $value = self::unserialize_array($string);

    return is_array($value) ? implode(', ', $value) : stripslashes($value);
  }

  /**
   * adds a blank field type record
   * 
   * @global object $wpdb
   * @param array $atts
   * @return boolean 
   */
  public function add_blank_field($atts) {

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
  private function _add_db_column($atts) {

    global $wpdb;

    $datatype = self::set_datatype($atts['form_element']);

    $sql = 'ALTER TABLE `' . self::$participants_table . '` ADD `' . $atts['name'] . '` ' . $datatype . ' NULL';

    return $wpdb->query($sql);
  }

  /**
   * returns a MYSQL datatype appropriate to the form element type
   * 
   * @param string $element the name of the element type
   * @return string the name of the MySQL datatype
   */
  public function set_datatype($element) {

    switch ($element) {
      case 'date':
      case 'text-line':
        $datatype = 'TINYTEXT';
        break;

      case 'checkbox':
      case 'radio':
      case 'multi-select':
      case 'multi-checkbox':
      case 'text-area':
      case 'rich-text':
      case 'dropdown':
      default :
        $datatype = 'TEXT';

    }

    return $datatype;
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

    // only process POST arrays from this plugin's pages
    if (!isset($_POST['subsource']) or $_POST['subsource'] != self::PLUGIN_NAME or !isset($_POST['action']))
      return;

    // add a filter to check the submission before anything is done with it
    $check = true;
    if (has_filter(self::$css_prefix . 'check_submission')) {
      $check = apply_filters(self::$css_prefix . 'check_submission', $_POST);
    }
    if ($check === false) return;

    // error_log( __METHOD__.' post:'.print_r( $_POST, true ) );

    /*
     * instantiate the validation object if we need to. This is necessary
     * because another script can instantiate the object in order to add a
     * feedback message
     * 
     * we don't validate in the admin, they will be allowed to leave required field unfilled
     */
    if (!is_object(self::$validation_errors) and ! is_admin())
      self::$validation_errors = new FormValidation();

    switch ($_POST['action']) :

      case 'update':
      case 'insert':
    
        /*
         * set the raw post array filters. We use a copy of the POST array so that
         * certain values will remain untouched
         */
        $post_data = $_POST;
        $wp_filter = self::$css_prefix . 'before_submit_' . ($_POST['action'] == 'insert' ? 'add' : 'update');
        if (has_filter($wp_filter)) {
          $post_data = apply_filters($wp_filter,$post_data);
        }

        $id = isset($_POST['id']) ? $_POST['id'] : ( isset($_GET['id']) ? $_GET['id'] : false );

        $participant_id = self::process_form($post_data, $_POST['action'], $id);

        if (false === $participant_id) {

          // we have errors; go back to form and show errors
          return;
        }
        
        /*
         * set the stored record hook.
         */
        $wp_hook = self::$css_prefix . 'after_submit_' . ($_POST['action'] == 'insert' ? 'add' : 'update');
        do_action($wp_hook,self::get_participant($participant_id));

        // if we are submitting from the frontend, we're done
        if (!is_admin()) {

          if ( is_object(self::$validation_errors) ) self::$validation_errors->add_error('', self::$plugin_options['record_updated_message']);

          if (self::$plugin_options['send_record_update_notify_email']) {

            $sent = wp_mail(
                    self::$plugin_options['email_signup_notify_addresses'],
                    self::proc_tags(self::$plugin_options['record_update_email_subject'], $participant_id, 'all'),
                    self::proc_tags(self::$plugin_options['record_update_email_body'], $participant_id, 'all'),
                    self::$email_headers
            );
          }

          return;
        }

        // redirect according to which submit button was used
        switch ($_POST['submit_button']) {

          case self::$i18n['apply'] :
            wp_redirect(get_admin_url() . 'admin.php?page=' . self::PLUGIN_NAME . '-edit_participant&id=' . $participant_id);
            exit;

          case self::$i18n['next'] :
            $get_id = $_POST['action'] == 'update' ? '&id=' . self::next_id($participant_id) : '';
            wp_redirect(get_admin_url() . 'admin.php?page=' . self::PLUGIN_NAME . '-edit_participant' . $get_id);
            exit;

          case self::$i18n['submit'] :
          default :
            wp_redirect(get_admin_url() . 'admin.php?page=' . self::PLUGIN_NAME . '-list_participants&id=' . $participant_id);
            exit;

        }
        break;

      case 'output CSV':

        $header_row = array();
        $title_row = array();
        $data = array();
        $filename = isset($_POST['filename']) ? $_POST['filename'] : '';

        switch ($_POST['CSV_type']) :

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

            if ($_POST['include_csv_titles']) $data[] = $title_row;

            $query = str_replace('*', ' ' . trim($import_columns, ',') . ' ', rawurldecode($_POST['query']));

            $data += self::_prepare_CSV_rows($wpdb->get_results($query, ARRAY_A));

            break;

        endswitch; // CSV type

        if (!empty($filename)) {

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

      case 'signup' :

        $_POST['private_id'] = self::generate_pid();

        $post_data = $_POST;
        
        $wp_filter = self::$css_prefix . 'before_submit_signup';
        if (has_filter($wp_filter)) {
          $post_data = apply_filters($wp_filter,$post_data);
        }

        // only go to the thanks page if we have no errors
        $post_data['id'] = self::process_form($post_data, 'insert');

        if (false !== $post_data['id']) {
          
          $_SESSION['pdbid'] = $post_data['id'];

          wp_redirect($post_data['thanks_page']); // self::add_uri_conjunction($post_data['thanks_page']) . 'pdbid=' . $post_data['id']

          exit;
        }

        return;

    endswitch; // $_POST['action']
  }

  // returns boolean to question of whether the user is authorized to see / edit 
  // administrative fields
  public function backend_user() {

    // contributor and above
    return current_user_can('edit_posts') ? true : false;
  }

  /**
   * gets an array of readonly fields
   *
   * @return array
   */
  public function get_readonly() {

    $fields = array();

    foreach (self::get_column_atts('readonly') as $column)
      $fields[] = $column->name;

    return $fields;
  }

  /**
   * returns the title attribute of a column
   * 
   * @param string $column
   * @return string
   */
  public function column_title($column) {

    $field = self::get_field_atts($column, 'title');

    return $field->title;
  }

  /**
   * displays an edit field for a field attribute
   * this is used by the manage_fields script
   * 
   * @param string $field name of the field
   * @return array contains parameters to use in instantiating the FormElement object
   */
  public function get_edit_field_type($field) {

    switch ($field) :

      // small integer fields
      case 'id':
        return array('type' => 'hidden');

      case 'order':
        return array('type' => 'drag-sort');

      case 'admin_column':
      case 'display_column':
        return array('type' => 'text', 'size' => '2');

      // all the booleans
      case 'persistent':
      case 'sortable':
      case 'CSV':
      case 'signup':
      case 'readonly':
        return array('type' => 'checkbox', 'options' => array(1, 0));

      // field names can't be edited
      case 'name':
        return array('type' => 'text', 'attributes' => array('readonly' => 'readonly'));

      // all the text-area fields
      case 'values':
      case 'help_text':
        return array('type' => 'text-area');

      // drop-down fields
      case 'form_element':
        // populate the dropdown with the available field types from the FormElement class
        return array('type' => 'dropdown', 'options' => array_flip(FormElement::get_types()));

      case 'validation':
        return array(
            'type' => 'dropdown-other',
            'options' => array(
                __('Not Required', 'participants-database') => 'no',
                __('Required', 'participants-database') => 'yes',
                __('Email','participants-database') => 'email',
                'CAPTCHA' => 'captcha',
            ),
            'attributes' => array('other' => 'regex/match'),
        );

      case 'group':
        // these options are defined on the "settings" page
        return array('type' => 'dropdown', 'options' => self::get_groups('name', 'internal'));

      case 'link':

      case 'title':
      default:
        return array('type' => 'text');

    endswitch;
  }

  /**
   * prepares a set of rows for CSV output
   *
   * @param array $raw_array the raw array output from the query
   *
   * @return array of record arrays
   */
  private function _prepare_CSV_rows($raw_array) {

    $output = array();

    foreach ($raw_array as $key => $value) {

      $output[$key] = self::_prepare_CSV_row($value);
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
  private function _prepare_CSV_row($raw_array) {

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

            $value = date(self::$date_format, $value);
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
          if (is_serialized($value))
            $value = implode(', ', unserialize($value));
      }

      // decode HTML entities and convert line breaks to <br>
      $output[$key] = html_entity_decode(str_replace(array("\n","\r"), '<br />', $value), ENT_QUOTES, "utf-8");

      $column = next($columns);
    }

    return $output;
  }

  /**
   * creates an anchor element with clickable link and href
   *
   * this is simply an interface to the FormElement function of the same name
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

    $field->value = $link;
    $field->default = $linktext;

    return FormElement::make_link($field, $template, $get);

      }
      
  /**
   * provides an AJAX loading spinner element
   */
  public static function get_loading_spinner() {
    return '<span class="ajax-loading"><img src="' . plugins_url( 'ui/ajax-loader.gif' , __FILE__ ) . '" /></span>';
  }

  /**
   * adds the URL conjunction to a GET string
   *
   * @param string $URI the URI to which a get string is to be added
   *
   * @return string the URL with the conjunction character appended
   */
  public static function add_uri_conjunction($URI) {
    
    return $URI . ( false !== strpos($URI, '?') ? '&' : '?');
  
  }

  /**
   * handles a file upload
   *
   * @param string $name the name of the current field
   * @param array  $file the $_FILES array element corresponding to one file
   *
   * @return string the path to the uploaded file or false if error
   */
  private function _handle_file_upload($name, $file)
  {

    $field_atts = self::get_field_atts($name);
    $type = 'image-upload' == $field_atts->form_element ? 'image' : 'file';
    $delete = (bool)(isset($_REQUEST[$name . '-deletefile']) and $_REQUEST[$name . '-deletefile'] == 'delete');

//    ob_start();
//    var_dump($delete);
//    error_log(__METHOD__.' type:'.$type.' name:'.$name.' delete:'.  ob_get_clean());

    // attempt to create the target directory if it does not exist
    if (!is_dir(Image_Handler::concatenate_directory_path(ABSPATH, self::$plugin_options['image_upload_location']))) {

      if (false === self::_make_uploads_dir(self::$plugin_options['image_upload_location'])) {
        return false;
      }
    }

    if (!is_uploaded_file(realpath($file['tmp_name']))) {

      self::_show_validation_error(__('There is something wrong with the file you tried to upload. Try another.', 'participants-database'), $name);

      return false;
    }

    /* get the allowed file types and test the uploaded file for an allowed file 
     * extension
     */
    $extensions = empty($field_atts->values) ? self::$plugin_options['allowed_file_types'] : implode(',', self::unserialize_array($field_atts->values));
    $test = preg_match('#^(.+)\.(' . implode('|', array_map('trim', explode(',', $extensions))) . ')$#', $file['name'], $matches);

    //error_log(__METHOD__.' ext:'.$extensions.' test:'. $test.' matches:'.print_r($matches,1));

    if (0 === $test) {

      if ($type == 'image')
        self::_show_validation_error(sprintf(__('For "%s", you may only upload image files like JPEGs, GIFs or PNGs.', 'participants-database'), $field_atts->title), $name);
      else
        self::_show_validation_error(sprintf(__('The file selected for "%s" must be one of these types: %s. ', 'participants-database'), $field_atts->title, $extensions), $name);

      return false;
    } else {

      // validate and construct the new filename using only the allowed file extension
      $new_filename = preg_replace(array('#\.#', "/\s+/", "/[^-\.\w]+/"), array("-", "_", ""), $matches[1]) . '.' . $matches[2];
      // now make sure the name is unique by adding an index if needed
      $index = 1;
      while (file_exists(Image_Handler::concatenate_directory_path(ABSPATH, self::$plugin_options['image_upload_location']) . $new_filename)) {
        $filename_parts = pathinfo($new_filename);
        $new_filename = preg_replace(array('#_[0-9]+$#'), array(''), $filename_parts['filename']) . '_' . $index . '.' . $filename_parts['extension'];
        $index++;
      }
    }

    /*
     * we perform a validity check on the image files, this also makes sure only 
     * images are uploaded in image upload fields and only non-images are uploaded 
     * in file upload fields
     */
    $fileinfo = getimagesize($file['tmp_name']);
    $valid_image = in_array($fileinfo[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WBMP));

    if ( $type == 'image' and !$valid_image ) {

      self::_show_validation_error( sprintf(__('For "%s", you may only upload image files like JPEGs, GIFs or PNGs.', 'participants-database'), $field_atts->title ), $name);
      return false;
    } elseif ( $type == 'file' and $valid_image ) {
      
      self::_show_validation_error(__('The file you tried to upload is not of an allowed type.', 'participants-database'),$name);
      return false;
    }

    if ($file['size'] > self::$plugin_options['image_upload_limit'] * 1024) {

      self::_show_validation_error(sprintf(__('The file you tried to upload is too large. The file must be smaller than %sK.', 'participants-database'), self::$plugin_options['image_upload_limit']),$name);

      return false;
    }

    if (false === move_uploaded_file($file['tmp_name'], Image_Handler::concatenate_directory_path( ABSPATH, self::$plugin_options['image_upload_location'] ) . $new_filename)) {

      self::_show_validation_error(__('The file could not be saved.', 'participants-database'));

      return false;
    }

    /*
     * as of 1.3.2 we save the image as filename only; the image is retrieved from 
     * the directory defined in the plugin setting using the self::get_image function
     */

    else
      return $new_filename;
  }

  /**
   * attempt to create the uploads directory
   *
   * sets an error if it fails
   * 
   * @param string $dir the name of the new directory
   */
  public function _make_uploads_dir($dir) {

    $savedmask = umask(0);

    if (false === mkdir(ABSPATH . $dir, 0755, true)) {

      if (is_object(self::$validation_errors))
        self::$validation_errors->add_error('', sprintf(__('The uploads directory (%s) could not be created.', 'participants-database'), $dir));

      umask($savedmask);

      return false;
    }

    umask($savedmask);

    return true;
  }
  
  /**
   * deletes a file
   * 
   * this looks in the fie upload directory and deletes $filename if found
   * 
   * @param string $filename
   * @return bool success
   */
  public function delete_file($filename) {
    chdir(ABSPATH . self::$plugin_options['image_upload_location']);
    return unlink(basename($filename));
  }

  /**
   * builds a record edit link
   *
   * @param string $PID private id value
   * @return string private record URI
   */
  public function get_record_link($PID) {

    // if the setting is not yet set, don't try to build a link
    if (!isset(self::$plugin_options['registration_page']) || empty(self::$plugin_options['registration_page']))
      return '';

    // if the setting was made in previous versions and is a slug, convert it to a post ID
    if (!is_numeric(self::$plugin_options['registration_page'])) {

      self::$plugin_options['registration_page'] = self::get_id_by_slug(self::$plugin_options['registration_page']);

      update_option(self::$participants_db_options, self::$plugin_options);
    }

    $page_link = get_permalink(self::$plugin_options['registration_page']);

    return self::add_uri_conjunction($page_link) . 'pid=' . $PID;
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
  public function get_admin_record_link($id) {
    
    $path = 'admin.php?page=participants-database-edit_participant&action=edit&id='.$id;
    
    return get_admin_url( NULL, $path );
  }

  /**
   * gets the ID of a page given it's slug
   *
   * this is to provide backwards-compatibility with previous versions that used a page-slug to point to the [pdb_record] page.
   */
  public function get_id_by_slug($page_slug) {

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
  public function proc_tags($text, $participant_id, $mode = 'frontend') {

    $participant = self::get_participant($participant_id);

    $tags = array();
    $values = array();

    foreach (self::get_column_atts($mode) as $column) {
      
      $column->value = $participant[$column->name];

      $tags[] = '[' . $column->name . ']';

      $values[] = FormElement::get_field_value_display($column, false);
    }

    // add the "record_link" tag
    $tags[] = '[record_link]';
    $values[] = self::$plugin_options['registration_page'];

    // add the date tag
    $tags[] = '[date]';
    $values[] = date_i18n(self::$date_format, self::parse_date());
    
    // add the admin record link tag
    $tags[] = '[admin_record_link]';
    $values[] = self::get_admin_record_link($participant_id);

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
   * makes a title legal to use in anchor tag
   */
  public function make_anchor($title) {

    return str_replace(' ', '', preg_replace('#^[0-9]*#', '', strtolower($title)));
  }

  /**
   * prints the list with filtering parameters applied 
   *
   * called by the wp_ajax_nopriv_pdb_list_filter action: this happens when a 
   * user submits a search or sort on a record list
   *
   * @return null
   */
  public function pdb_list_filter() {

    if (!wp_verify_nonce($_POST['filterNonce'], self::$css_prefix . 'list-filter-nonce'))
      die('nonce check failed');

    global $post;

    if (!is_object($post))
      $post = get_post($_POST['postID']);
    
    // grab the shortcode out of the page content
    preg_match( '#(?<!\[)\[pdb_list([^\]]*)\]#', $post->post_content, $matches );
    // put the attributes array together
    $atts = shortcode_parse_atts(trim($matches[1]));
    // add the AJAX filtering flag
    $atts['filtering'] = 1;
    // output the filtered shortcode content
    header("Content-Type:	text/html");
    echo PDb_List::get_list( $atts );

    exit;
  }

  /**
   * parses a date string into UNIX timestamp
   *
   * if "strict dates" is set, this function uses the DateTime class to parse the
   * string according to a specific format. If it is not, we use the conventional
   * strtotime() function, with the enhancement that if the non-American style
   * format is used with slashes "d/m/Y" the string is prepared so strtotime can
   * parse it correctly  
   *
   * @param string $string      the string to parse; if not given, defaults to now
   * @param object $column_atts the column object; used to identify the field for
   *                            user feedback
   * @return int  UNIX timestamp or false if parse fails
   */
  public function parse_date($string = false, $column = '') {

    // return the now() timestamp
    if (false === $string)
      return time();

    // it's already a timestamp
    if (self::is_valid_timestamp($string)) {
      //if (WP_DEBUG and is_object($column)) error_log(__METHOD__.' tried to parse timestamp from '. $column->name);
      return $string;
    }
    
    $date = false;

    /*
     * we have two options to parse a date string into a timestamp: the 
     * IntlDateFormatter class or the DateTime class. The IntlDateFormatter 
     * class can parse localized text dates, but it seems commonly unavailable, 
     * at least on English-speaking servers. The DateTime class is widely 
     * available, but can't parse non-English text dates. It can parse numeric 
     * date representations, so if the intl module is not available, we try to 
     * use DateTime. If that is not available, we use strtotime with the added trick 
     * of swapping out the separators if they are slashes so slashed European 
     * notation can be correctly parsed
     */
    if (self::$plugin_options['strict_dates'] == 1 and is_object($column) and $column->group != 'internal' ) {

      $input_date_format = self::$plugin_options['input_date_format'];
      
      $mode = 'none';
      
      if (class_exists('IntlDateFormatter')) {
        
        $mode = 'Intl';

        $dateformat = empty($input_date_format) ? get_option('date_format') : $input_date_format;

        $fmt = new IntlDateFormatter( WPLANG, IntlDateFormatter::LONG, IntlDateFormatter::NONE, NULL, NULL, Participants_Db::get_ICU_date_format($dateformat) );

        //error_log(__METHOD__.' format object:'.print_r($fmt,1));
        $timestamp = $fmt->parse($string);

        $date_obj = new DateTime();
        $date_obj->setTimestamp($timestamp);
        
      } else if (class_exists('DateTime')) {
        
        $mode = 'DateTime';

        $date_obj = DateTime::createFromFormat($input_date_format, $string);
        
      }
      

      //error_log(__METHOD__.' date:'.print_r($date_obj,1));

      if (is_array(date_get_last_errors()) && !empty($string)) {

        $errors = date_get_last_errors();

        if ($errors['warning_count'] > 0 || $errors['error_count'] > 0) {

          $date_obj = false;

          if (is_object(self::$validation_errors) and is_object($column)) {

            self::$validation_errors->add_error($column->name, sprintf(__('The date for "%s" was invalid. Please input the date with the exact format shown', 'participants-database'), $column->title));
          }
        }
      }

      /*
       * if we have a valid date, convert to timestamp
       */
      if ($date_obj) {
        /*
         * zero the time so date equality comparisons can be made
         */
        $date_obj->setTime(0,0);
        $date = $date_obj->format('U');
      }
    }
      
//      ob_start();
//      var_dump($date);
//      error_log(__METHOD__.' date value:'.ob_get_clean());
    
    /*
     * if we haven't got a timestamp, parse the date the regular way
     */
    if ( $date === false or ! self::is_valid_timestamp($date) ){
      
      $mode= 'strtotime';
      
      /*
       * deal with the common special case of non-American-style numeric date with slashes
       */
      $dateformat = get_option('date_format');
      if (false !== strpos($string,'/') ) {
        $date_parts = explode('/',$dateformat);
        $day_index = array_search('d',$date_parts) !== false ? array_search('d',$date_parts) : array_search('j',$date_parts);
        $month_index = array_search('m',$date_parts) !== false ? array_search('m',$date_parts) : array_search('n',$date_parts);
        if ( $day_index !== false && $month_index !== false && $day_index < $month_index ) {
          $string = str_replace('/','-',$string);
        }
      };
    
      $date = strtotime($string);
    }
    
    //if (WP_DEBUG) error_log(__METHOD__.' mode: ' . $mode . ' timestamp:' . $date);

    return $date;
  }
  
  /**
   * validates a time stamp
   *
   * @param mixed $timestamp the string to test
   * @return bool true if valid timestamp
   */
  public static function is_valid_timestamp($timestamp) {
    return is_int($timestamp) or ((string) (int) $timestamp === $timestamp);
  }

  /**
   * returns the PHP version as a float
   *
   */
  function php_version() {

    $numbers = explode('.', phpversion());

    return (float) ( $numbers[0] + ( $numbers[1] / 10 ) );
  }
  /** 
   * Convert a date format to a strftime format 
   * 
   * Timezone conversion is done for unix. Windows users must exchange %z and %Z. 
   * 
   * Unsupported date formats : S, n, t, L, B, G, u, e, I, P, Z, c, r 
   * Unsupported strftime formats : %U, %W, %C, %g, %r, %R, %T, %X, %c, %D, %F, %x 
   * 
   * @param string $dateFormat a date format 
   * @return string 
   */ 
  public static function dateFormatToStrftime($dateFormat) { 

      $caracs = array( 
          // Day - no strf eq : S 
          'd' => '%d', 'D' => '%a', 'j' => '%e', 'l' => '%A', 'N' => '%u', 'w' => '%w', 'z' => '%j', 
          // Week - no date eq : %U, %W 
          'W' => '%V',  
          // Month - no strf eq : n, t 
          'F' => '%B', 'm' => '%m', 'M' => '%b', 
          // Year - no strf eq : L; no date eq : %C, %g 
          'o' => '%G', 'Y' => '%Y', 'y' => '%y', 
          // Time - no strf eq : B, G, u; no date eq : %r, %R, %T, %X 
          'a' => '%P', 'A' => '%p', 'g' => '%l', 'h' => '%I', 'H' => '%H', 'i' => '%M', 's' => '%S', 
          // Timezone - no strf eq : e, I, P, Z 
          'O' => '%z', 'T' => '%Z', 
          // Full Date / Time - no strf eq : c, r; no date eq : %c, %D, %F, %x  
          'U' => '%s' 
      ); 

      return strtr((string)$dateFormat, $caracs); 
  }
  
  /**
   * translates date format strings from PHP to other formats
   *
   * @param string $dateformat the PHP-style date format string
   * @param string $format_type selected the format type to translate to: 'ICU', 'jQuery'
   * @return string the translated format string
   */
  function translate_date_format($dateformat, $format_type) {

    // these are the PHP date codes
    $pattern = array(
        //day
        'd', //day of the month
        'j', //1 or 2 digit day of month
        'l', //full name of the day of the week
        'D', // abbreviated day of the week
        'z', //day of the year
        //month
        'F', //Month name full
        'M', //Month name short
        'n', //numeric month no leading zeros
        'm', //numeric month leading zeros
        //year
        'Y', //full numeric year
        'y'  //numeric year: 2 digit
    );
    switch ($format_type) {
      case 'ICU':
    $replace = array(
            'dd', 'd', 'EEEE', 'EEEE', 'D',
            'MMMM', 'MMM', 'M', 'MM',
            'yyyy', 'yy'
        );
        break;
      case 'jQuery':
        $replace = array(
            'dd', 'd', 'DD', 'D', 'o',
        'MM', 'M', 'm', 'mm',
        'yy', 'y'
    );
        break;
    }
    $i = 1;
    foreach ($pattern as $p) {
      $dateformat = str_replace($p, '%' . $i . '$s', $dateformat);
      $i++;
  }
    return vsprintf($dateformat, $replace);
  }
  
  /**
   * translates a PHP date() format string to an ICU format string
   * 
   * @param string $PHP_date_format the date format string
   *
   */
  function get_ICU_date_format($PHP_date_format = '') {

    $dateformat = empty($PHP_date_format) ? self::$date_format : $PHP_date_format;

    return self::translate_date_format($dateformat, 'ICU');
  }

  /**
   * translates a PHP date() format string to a jQuery format string
   * 
   * @param string $PHP_date_format the date format string
   *
   */
  function get_jQuery_date_format($PHP_date_format = '') {

    $dateformat = empty($PHP_date_format) ? self::$date_format : $PHP_date_format;

    return self::translate_date_format($dateformat, 'jQuery');
  }
  
  /**
   * sets an admin area error message
   * 
   * @param string $message the message to be dislayed
   * @param string $type the type of message: 'updated' (yellow) or 'error' (red)
   */
  public function set_admin_message($message, $type='error'){
    self::$admin_message = $message;
    self::$admin_message_type = $type;
  }
  
  /**
   * sets the admin message
   */
  public function admin_message() {
    if (!empty(self::$admin_message)){
      printf ('<div class="%s">
       <p>%s</p>
    </div>', self::$admin_message_type, self::$admin_message);
      //self::$admin_message = '';
    }
  }
  
  /**
   * shows a validation error message
   * 
   * @param string $error the message to show
   * @param string $name the field on which the error was called
   */
  private function _show_validation_error( $error, $name = '' ) {
    if ( is_object(self::$validation_errors) ) self::$validation_errors->add_error($name, $error );
    else self::set_admin_message($error);
  }
  
  /**
   * recursively merges two arrays, overwriting matching keys
   *
   * if any of the array elements are an array, they will be merged with an array
   * with the same key in the base array
   *
   * @param array $array    the base array
   * @param array $override the array to merge
   * @return array
   */
  public function array_merge2 ($array, $override) {
    $x = array();
    foreach ($array as $k => $v) {
      if (isset($override[$k])) {
        if (is_array($v)) {
          $v = self::array_merge2($v,(array)$override[$k]);
        } else $v = $override[$k];
        unset($override[$k]);
      }
      $x[$k] = $v;
    }
    // add in the remaining unmatched elements
    return $x += $override;
  }
  
  /**
   * sets up a few internationalization words
   */
  private function _set_i18n() {
    
    self::$i18n = array(
        'submit' => __('Submit','participants-database'),
        'apply' => __('Apply','participants-database'),
        'next' => __('Next','participants-database'),
    );
  }

  /**
   * prints an admin page heading
   *
   * @param text $text text to show if not the title of the plugin
   */
  public function admin_page_heading($text = false) {
    
    $text = $text ? $text : self::$plugin_title;
    ?>
    <div class="icon32" id="icon-users"></div><h2><?php echo $text ?></h2>
    <?php
  }

  /**
   * prints a credit footer for the plugin
   *
   * @return null
   */
  public function plugin_footer() {
    ?>
    <div id="PDb_footer" class="widefat redfade">
      <div class="section">
        <h4><?php echo self::$plugin_title, ' ', self::$plugin_version ?><br /><?php _e('WordPress Plugin', 'participants-database') ?></h4>
        <p><em><?php _e('Helping organizations manage their volunteers, members and participants.', 'participants-database') ?></em></p>
      </div>
      <div class="section">
        <h4><a href="http://xnau.com"><img src="<?php bloginfo('wpurl') ?>/wp-content/plugins/<?php echo self::PLUGIN_NAME ?>/ui/xnau-square-60.png" style="float:left;width:50px;height:auto;margin-right:10px" /></a><?php _e('Developed by', 'participants-database') ?><br /><a href="http://xnau.com">xn&lowast;au webdesign</a></h4>
        <p><?php _e('Suggestions or crticisms of this plugin? I&#39;d love to hear them: email ', 'participants-database') ?><a href="mailto:support@xnau.com">support@xnau.com.</a>
      </div>
      <div class="section">
        <p><?php printf(__('Please consider contributing to the continued support and development of this software by visiting %1$sthis plugin&#39;s page,%3$s giving the plugin a %2$srating%3$s or review, or dropping something in the %1$stip jar.%3$s Thanks!', 'participants-database'), '<a href="http://xnau.com/wordpress-plugins/participants-database">', '<a href="http://wordpress.org/extend/plugins/participants-database/">', '</a>') ?></p>
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
    return array_merge($links, array('settings' => '<a href="' . admin_url('admin.php?page=participants-database_settings_page') . '">Settings</a>'));
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
      
      $links[1] = str_replace('Roland Barker','xn&lowast;au webdesign',$links[1]);
      $links[] = '<a href="http://wordpress.org/support/view/plugin-reviews/participants-database">Submit a rating or review</a>';
      $links[] = '<span style="color:#6B4001;">Free tech support and continued development relies on your support: <a class="button" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=P5TNMURN5JBZA">contribute</a></span>';
    }
    return $links;
  }
    
  }

// class

Participants_Db::initialize();

/**
 * performs the class autoload
 * 
 * @param string $class the name of the class to be loaded
 */
function PDb_class_loader($class) {
  
  $class_file = plugin_dir_path(__FILE__) . 'classes/' . $class . '.class.php';

  if (is_file($class_file)) {

    //error_log( __FUNCTION__. ' class loaded: '.$class_file );

    require_once $class_file;
  }
}
?>
