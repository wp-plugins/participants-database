<?php
/*
  Plugin Name: Participants Database
  Plugin URI: http://xnau.com/wordpress-plugins/participants-database
  Description: Plugin for managing a database of participants, members or volunteers
  Author: Roland Barker
  Version: 1.4
  Author URI: http://xnau.com
  License: GPL2
  Text Domain: participants-database
 */

/*
 * Copyright 2011, 2012 Roland Barker xnau webdesign  (email : webdesign@xnau.com)
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

/*
 * the main plugin class is set up as a static class
 * this function starts it up
 */
Participants_Db::initialize();

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
  public static $db_version;
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
  // a list of all the form element types available
  public static $element_types;
  // this is can be prefixed on CSS classes or id to keep a namespace
  public static $css_prefix = 'pdb_';
  // holds the form validation errors
  public static $validation_errors;
  // name of the transient record used to hold the last record
  public static $last_record = 'pdb_last_record';
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

  public function initialize() {

    // register the class autoloading
    spl_autoload_extensions('.php');
    spl_autoload_register('PDb_class_loader');

    // set the table names
    global $wpdb;
    self::$participants_table = $wpdb->prefix . str_replace('-', '_', self::PLUGIN_NAME);
    self::$fields_table = self::$participants_table . '_fields';
    self::$groups_table = self::$participants_table . '_groups';

    // name of the WP option where the current db version is stored
    self::$db_version = 'PDb_Db_version';

    // set the plugin version
    self::$plugin_version = self::_get_plugin_data('Version');

    // define some locations
    self::$participants_db_options = self::PLUGIN_NAME . '_options';
    self::$plugin_page = self::PLUGIN_NAME;
    self::$plugin_url = WP_PLUGIN_URL . '/' . self::PLUGIN_NAME;
    self::$plugin_path = dirname(__FILE__);
    // this is relative to the WP install
    self::$uploads_path = 'wp-content/uploads/' . self::PLUGIN_NAME . '/';

    //self::$plugin_settings = new PDb_Settings();
    //self::$plugin_options = get_option( self::$participants_db_options );
    // hard-code some image file extensions
    self::$allowed_extensions = array('jpg', 'jpeg', 'gif', 'png');

    // define an array of all available form element types
    self::$element_types = array(
        'Text-line' => 'text-line',
        'Text Area' => 'text-field',
        'Rich Text' => 'textarea',
        'Checkbox' => 'checkbox',
        'Radio Buttons' => 'radio',
        'Dropdown List' => 'dropdown',
        'Date Field' => 'date',
        'Dropdown/Other' => 'dropdown-other',
        'Multiselect Checkbox' => 'multi-checkbox',
        'Radio Buttons/Other' => 'select-other',
        'Multiselect/Other' => 'multi-select-other',
        'Link Field' => 'link',
        'Image Upload Field' => 'image-upload',
        'Hidden Field' => 'hidden',
        'Password Field' => 'password',
    );


    // install/deactivate and uninstall methods are handled by the PDB_Init class
    register_activation_hook(__FILE__, array('PDb_Init', 'on_activate'));
    register_deactivation_hook(__FILE__, array('PDb_Init', 'on_deactivate'));
    register_uninstall_hook(__FILE__, array('PDb_Init', 'on_uninstall'));


    // define the list of internal columns
    if ($wpdb->get_var('show tables like "' . self::$groups_table . '"') == self::$groups_table) {

      self::$internal_columns = array();

      $internal_columns = $wpdb->get_results('SELECT f.name 
																						 	FROM ' . self::$fields_table . ' f 
																							INNER JOIN ' . self::$groups_table . ' g 
																							ON f.group = g.name 
																							WHERE g.display = 0'
              , ARRAY_N);

      foreach ($internal_columns as $column)
        self::$internal_columns[] = $column[0];
    }

    add_filter('query_vars', array(__CLASS__, 'register_queryvars'));

    // set the WP hooks to finish setting up the plugin
    add_action('init', array(__CLASS__, 'init'));
    add_action('admin_menu', array(__CLASS__, 'plugin_menu'));
    add_action('admin_init', array(__CLASS__, 'admin_init'));
    add_action('wp_enqueue_scripts', array(__CLASS__, 'include_scripts'));
    add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_includes'));
    add_action('wp_footer', array(__CLASS__, 'add_scripts'));

    // handles ajax request from list filter
    add_action('wp_ajax_pdb_list_filter', array(__CLASS__, 'pdb_list_filter'));
    add_action('wp_ajax_nopriv_pdb_list_filter', array(__CLASS__, 'pdb_list_filter'));

    // define our shortcodes
    //add_shortcode( 'pdb_record', array( __CLASS__, 'frontend_edit') );
    add_shortcode('pdb_record', array(__CLASS__, 'record_edit'));
    add_shortcode('pdb_signup', array(__CLASS__, 'print_signup_form'));
    add_shortcode('pdb_signup_thanks', array(__CLASS__, 'print_signup_thanks_form'));
    //add_shortcode( 'pdb_list', array( 'PDb_List_Static','initialize' ) );
    add_shortcode('pdb_list', array(__CLASS__, 'print_list'));
    add_shortcode('pdb_single', array(__CLASS__, 'print_single_record'));
    //add_shortcode( 'pdb_single', array( __CLASS__, 'show_record' ) );

    /*
     * this checks to make sure the columns in the main database match the fields
     * defined in the fields database
     */
    if ($wpdb->get_var('SHOW TABLES LIKE "' . self::$participants_table . '"') == self::$participants_table) :
      // db integrity check and fix
      $query = 'SELECT * FROM ' . self::$fields_table;
      $fields = $wpdb->get_results($query, ARRAY_A);
      $columns_raw = self::get_columns();
      $columns = array();
      foreach ($columns_raw as $col)
        $columns[] = $col['Field'];
      foreach ($fields as $field) {

        if (!in_array($field['name'], $columns)) {

          //error_log( 'adding column:'.print_r( $field, true ));

          self::_add_db_column($field);
        }
      }
    endif; // end integrity check and fix
  }

  function admin_init() {

    // if the setting was made in previous versions and is a slug, convert it to a post ID
    if (isset(self::$plugin_options['registration_page']) && !is_numeric(self::$plugin_options['registration_page'])) {

      self::$plugin_options['registration_page'] = self::_get_ID_by_slug(self::$plugin_options['registration_page']);

      update_option(self::$participants_db_options, self::$plugin_options);
    }
  }

  public function init() {

    load_plugin_textdomain(self::PLUGIN_NAME, false, dirname(plugin_basename(__FILE__)) . '/languages/');

    self::$plugin_title = __('Participants Database', self::PLUGIN_NAME);

    // set the email content headers
    if (!isset(self::$plugin_options)) {

      self::$plugin_options = get_option(self::$participants_db_options);
    }

    $options = self::$plugin_options;

    if (0 != $options['html_email']) {
      $type = 'text/html; charset="' . get_option('blog_charset') . '"';
    } else {
      $type = 'text/plain; charset=us-ascii';
    }
    self::$email_headers = "MIME-Version: 1.0\n" .
            "From: " . $options['receipt_from_name'] . " <" . $options['receipt_from_address'] . ">\n" .
            "Content-Type: " . $type . "\n";

    //self::include_scripts();
    // this processes form submits before any output so that redirects can be used
    self::process_page_request();
  }

  public function plugin_menu() {

    // intialize the plugin settings
    // we do this here because we need the object for the plugin menus
    //self::$plugin_settings = new PDb_Settings();

    self::$plugin_settings = new PDb_Settings();
    self::$plugin_options = get_option(self::$participants_db_options);

    // define the plugin admin menu pages
    add_menu_page(
            self::$plugin_title, self::$plugin_title, '', self::PLUGIN_NAME, array(__CLASS__, 'include_admin_file')
    );

    $listpage = add_submenu_page(
            self::PLUGIN_NAME, __('List Participants', self::PLUGIN_NAME), __('List Participants', self::PLUGIN_NAME), self::$plugin_options['record_edit_capability'], self::$plugin_page . '-list_participants', array('PDb_List_Admin', 'initialize')
            /* array( __CLASS__, 'include_admin_file' ) */
    );

    $addpage = add_submenu_page(
            self::PLUGIN_NAME, __('Add Participant', self::PLUGIN_NAME), __('Add Participant', self::PLUGIN_NAME), self::$plugin_options['record_edit_capability'], self::$plugin_page . '-edit_participant', array(__CLASS__, 'include_admin_file')
    );

    $managepage = add_submenu_page(
            self::PLUGIN_NAME, __('Manage Database Fields', self::PLUGIN_NAME), __('Manage Database Fields', self::PLUGIN_NAME), 'manage_options', self::$plugin_page . '-manage_fields', array(__CLASS__, 'include_admin_file')
    );

    $uploadpage = add_submenu_page(
            self::PLUGIN_NAME, __('Import CSV File', self::PLUGIN_NAME), __('Import CSV File', self::PLUGIN_NAME), 'manage_options', self::$plugin_page . '-upload_csv', array(__CLASS__, 'include_admin_file')
    );

    $settingspage = add_submenu_page(
            self::PLUGIN_NAME, __('Settings', self::PLUGIN_NAME), __('Settings', self::PLUGIN_NAME), 'manage_options', self::$plugin_page . '_settings_page', array(self::$plugin_settings, 'show_settings_form')
    );

    $editpage = add_submenu_page(
            '', __('Edit Record', self::PLUGIN_NAME), __('Edit Record', self::PLUGIN_NAME), self::$plugin_options['record_edit_capability'], self::$plugin_page . '_edit_participant'
    );
  }

  public function admin_includes($hook) {

    wp_register_script('cookie', plugins_url('js/jquery_cookie.js', __FILE__));
    wp_register_script('manage_fields', plugins_url('js/manage_fields.js', __FILE__));
    wp_register_script('settings_script', plugins_url('js/settings.js', __FILE__));
    wp_register_script('jq-placeholder', plugins_url('js/jquery.placeholder.min.js', __FILE__), array('jquery'));
    wp_register_script('admin', plugins_url('js/admin.js', __FILE__), array('jquery'));
    //wp_register_script( 'datepicker', plugins_url( 'js/jquery.datepicker.js', __FILE__ ) );
    //wp_register_script( 'edit_record', plugins_url( 'js/edit.js', __FILE__ ) );

    if (false !== stripos($hook, 'participants-database')) {
      wp_enqueue_script('jquery');
      wp_enqueue_script('jquery-ui-core');
      wp_enqueue_script('jquery-ui-tabs');
      wp_enqueue_script('jquery-ui-sortable');
      wp_enqueue_script('jquery-ui-dialog');
      wp_enqueue_script('cookie');
      wp_enqueue_script('jq-placeholder');
      wp_enqueue_script('admin');
    }

    if (false !== stripos($hook, 'settings_page')) {
      wp_enqueue_script('settings_script');
    }

    if (false !== stripos($hook, 'manage_fields')) {
      /* translators: don't translate the words in brackets {} */
      wp_localize_script('manage_fields', 'L10n', array(
          'must_remove' => '<h4>You must remove all fields from the {name} group before deleting it.</h4>',
          'delete_confirm' => '<h4>Delete the "{name}" {thing}?</h4>',
      ));
      wp_enqueue_script('manage_fields');
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

  // include any JS needed for the front-end
  public function include_scripts() {

    // set the global shortcode flag
    self::$shortcode_present = false;

    wp_register_style('pdb-frontend', plugins_url('/css/participants-database.css', __FILE__));

    if (self::$plugin_options['use_plugin_css']) {

      wp_enqueue_style('pdb-frontend');
    }

    wp_register_script('pdb-shortcode', plugins_url('js/shortcodes.js', __FILE__), array('jquery'));
    wp_register_script('list-filter', plugin_dir_url(__FILE__) . 'js/list-filter.js', array('jquery'));
    wp_register_script('jq-placeholder', plugins_url('js/jquery.placeholder.min.js', __FILE__), array('jquery'));

    // this is now handled conditionally in the wp_footer action
    //wp_enqueue_script( 'jquery' );
    //wp_enqueue_script( 'pdb-shortcode' );
    //wp_enqueue_script( 'jq-placeholder');

    global $wp_query;

    $ajax_params = array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'filterNonce' => wp_create_nonce('pdb-list-filter-nonce'),
        'postID' => $wp_query->post->ID,
    );
    wp_localize_script('list-filter', 'PDb_ajax', $ajax_params);
  }

  /**
   * conditionally adds JS to pages that have one of our shortcodes
   */
  public function add_scripts() {

    if (false !== self::$shortcode_present) {

      wp_enqueue_script('pdb-shortcode');
      wp_enqueue_script('jq-placeholder');
    }
  }

  // callback for plugin admin subpages
  // grabs the name from the request and includes the file to display the page
  public function include_admin_file() {

    $parts = explode('-', $_GET['page']);

    $file = array_pop($parts);

    // we'll need this in the included file
    global $wpdb;

    include $file . '.php';
  }

  // register all the GET variables we want to use
  public function register_queryvars($qvars) {

    // 'pdb' is used bye the pdb_show shortcode
    $qvars[] = 'pdb';

    return $qvars;
  }

  /**
   * shows the frontend edit screen called by the [pdb_record] shortcode
   *
   * requires the 'pid' value in the URI
   */
  function record_edit($atts) {

    $atts['id'] = isset($_GET['pid']) ? self::get_participant_id($_GET['pid']) : false;

    return PDb_Record::print_form($atts);
  }

  /**
   * updates the "last_accessed" field in the database
   */
  private function _record_access($id) {

    global $wpdb;

    $sql = 'UPDATE ' . self::$participants_table . ' SET `last_accessed` = NOW() WHERE `id` = ' . $id;

    return $wpdb->query($sql);
  }

  /**
   * sets the last_accessed timestamp
   */
  public function set_record_access($id) {

    self::_record_access($id);
  }

  /**
   * displays a single record using a shortcode called with the record ID
   *
   * the shorcode is looking for a get variable called 'pdb'
   *
   * @param array $atts array of shortcode variables
   *                 template - name of the template file to use: pdb-single-{template}.php
   *                 term     - the term to index the record by, defaults to the ID
   *                 class    - classname of the wrapper div
   */
  public function show_record($atts) {

    /*
     * the template attribut selects the template file
     * the term attribute selects the term to use to look up the record
     * this should be a unique identifier, but if it's not it will show a series of records
     */
    $vars = shortcode_atts(array(
        'template' => 'default',
        'term' => 'id',
        'class' => 'PDb-single'
            ), $atts);

    if (isset($_GET['pdb'])) {

      $ids = self::_get_participant_id_by_term($vars['term'], $_GET['pdb']);

      $template = self::get_template('single', $vars['template']);

      if (false === $template)
        return '<p>' . _x('Missing Template', 'message to show if the plugin cannot find the template', self::PLUGIN_NAME) . '</p>';

      ob_start();

      foreach ((array) $ids as $id) :

        if (self::_id_exists($id)) {
          ?>
          <div class="<?php echo $vars['class'] ?>">

            <?php include $template ?>

          </div><?php
        } else
          echo '<p>' . sprintf(__('No record found for %s.', self::PLUGIN_NAME), $id) . '</p>';

      endforeach; // cycle through ids

      return ob_get_clean();
    }
  }

  /**
   * prints a single record called by [pdb_list] shortcode
   */
  public function print_list($params) {

    return PDb_List::print_record($params);
  }

  /**
   * prints a single record called by [pdb_single] shortcode
   */
  public function print_single_record($params) {

    return PDb_Single::print_record($params);
  }

  /**
   * prints a signup form
   */
  public function print_signup_form($params) {

    $params['post_id'] = get_the_ID();

    return PDb_Signup::print_form($params);
  }

  /**
   * prints the signup thanks form
   */
  public function print_signup_thanks_form($params) {

    $params['post_id'] = get_the_ID();

    $params['type'] = 'thanks';

    return PDb_Signup::print_form($params);
  }

  /**
   * finds and returns a template file URL
   *
   * looks first in the theme directory for the template file; then in the
   * plugin's directory for the named template, then for the default template
   *
   * @param string $module        the module name of the shortcode calling the
   *                              template: single, list, signup
   * @param string $template_name the name of the template to use
   *
   * @return the URL of the template file or false if none found
   */
  public function get_template($module, $template_name = 'default') {

    $template = get_stylesheet_directory() . '/templates/pdb-' . $module . '-' . $template_name . '.php';

    if (!file_exists($template)) {

      $template = self::$plugin_path . '/templates/pdb-' . $module . '-' . $template_name . '.php';
    }

    if (!file_exists($template)) {

      $template = self::$plugin_path . '/templates/pdb-' . $module . '-default.php';
    }

    if (!file_exists($template)) {

      error_log(__METHOD__ . ' template not found: ' . $template);

      $template = false;
    }

    return $template;
  }

  // get all the attributes of a field by it's name
  // an attribute or comma-separated list of attributes can be specified
  // if not, a default list of attributes is retrieved
  //
	// returns data object
  public function get_field_atts($field = false, $atts = '*') {

    if (!$field) {

      $return = new stdClass;
      $return->form_element = '';
      return $return;
    }

    global $wpdb;

    $sql = "
		SELECT " . $atts . " 
		FROM " . self::$fields_table . " 
		WHERE `name` = '" . $field . "'";

    //error_log( __METHOD__.' called with:'. $sql );

    $field_atts = $wpdb->get_row($sql);

    return $field_atts;
  }

  /**
   * get an array of groups
   *
   * @param string $column comma-separated list of columns to get, defualts to all (*)
   * @param mixed $exclude singe group to exclude or array of groups to exclude
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

  // get the names of all the persistent fields 
  public function get_persistent() {

    return self::get_subset('persistent');
  }

  // get the names of all the sortable fields
  public function get_sortables() {

    global $wpdb;

    $frontend_fields =  is_admin() ? '' : ' AND `display_column` > 0 ';

    $sql = "
			SELECT `name`, REPLACE(`title`,'\\\','') as title
			FROM " . self::$fields_table . "
			WHERE `sortable` > 0" . $frontend_fields;

    $result = $wpdb->get_results($sql, ARRAY_N);

    // get the 2nd dimension of the array
    $return = array();
    foreach ($result as $item)
      $return[$item[1]] = $item[0];

    return $return;
  }

  // get a subset of field names; this function only works for boolean qualifiers
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
   * gets the column and column order for participant listing
   * returns a sorted array, omitting any non-displyed columns
   *
   * @param string $set selects the set of columns to get:
   *                    admin or display (frontend)
   *
   * @return array of column names, ordered and indexed by the set order
   */
  public function get_list_display_columns($set = 'admin_column') {

    global $wpdb;

    $sql = "
      SELECT `name`,`" . $set . "`
      FROM " . self::$fields_table . "
      WHERE `" . $set . "` > 0";

    $columns = $wpdb->get_results($sql, ARRAY_A);

    $column_set = array();
    foreach ($columns as $column) {

      $column_set[$column[$set]] = $column['name'];
    }

    //if ( $set == 'admin_column' && self::$plugin_settings->get_option( 'show_pid' ) ) $column_set[0] = 'private_id';

    ksort($column_set);

    //error_log( __METHOD__.' columns='.print_r( $column_set, true));

    return $column_set;
  }

  /**
   * gets an associative array of all main database column names and types
   * 
   * columns in this database remain even after they've been deleted from the fields database, so we 
   * don't use the results of this func for anything that only uses active columns
   */
  public function get_columns() {

    global $wpdb;

    $sql = "SHOW COLUMNS FROM " . self::$participants_table;

    return $wpdb->get_results($sql, ARRAY_A);
  }

  /**
   * checks a string against active columns to validate input
   */
  public function is_column($string) {

    global $wpdb;

    $sql = "SELECT f.name 
		        FROM " . self::$fields_table . " f";

    $columns_info = $wpdb->get_results($sql, ARRAY_N);

    $columns = array();

    foreach ($columns_info as $column_data) {

      $columns[] = $column_data[0];
    }

    return in_array($string, $columns);
  }

  /**
   * gets the field attributes as filtered by the type of form to display
   *
   * @param string $filter sets the context of the display and determines the set of columns to return
   * @return object the object is ordered first by the order of the group, then by the field order
   */
  public function get_column_atts($filter = 'new') {

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

        $where = 'WHERE g.display = 1 ';
        break;

      case 'readonly':

        $where = is_admin() ? 'WHERE v.group = "internal"' : 'WHERE v.group = "internal" OR v.readonly = 1';
        break;

      case 'new':
      case 'backend':
      default:

        $where = 'WHERE v.name NOT IN ( "id" ) ';
    }

    $sql = 'SELECT v.*, g.order FROM ' . self::$fields_table . ' v INNER JOIN ' . self::$groups_table . ' g ON v.group = g.name ' . $where . ' ORDER BY g.order, v.order';

    return $wpdb->get_results($sql, OBJECT_K);
  }

  /**
   * builds an object of all participant values structured by groups and columns
   *
   * this function is DEPRICATED in favor of using the Shortcode class to render
   * shortcode output, but we have to leave it in here for the moment because
   * there may be modified templates using this function still in use
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
   * prepares a field value for display
   *
   * @param string $value        the raw value of the field
   * @param string $form_element the form element type of the field
   * @param bool   $html         if true, format fields with html; false: plain text only
   * @return string
   */
  public function prep_field_for_display($value, $form_element, $html = true) {

    switch ($form_element) :

      case 'image-upload' :

        $image = new PDb_Image(array('filename' => $value));

        if ($html)
          $return = $image->get_image_html();

        elseif ($image->file_exists)
          $return = $image->get_image_file();

        else
          $return = $value;

        break;

      case 'date' :

        $return = empty($value) ? '' : date(get_option('date_format'), self::parse_date($value));
        break;

      case 'multi-checkbox' :
      case 'multi-select-other' :

        $return = implode(', ', (array) self::unserialize_array($value));
        break;

      case 'link' :

        $linkdata = self::unserialize_array($value);

        if (!is_array($linkdata)) {

          $return = '';
          break;
        }

        if (empty($linkdata[1]))
          $linkdata[1] = str_replace('http://', '', $linkdata[0]);

        if ($html)
          $return = vsprintf(( empty($linkdata[0]) ? '%1$s%2$s' : '<a href="%1$s">%2$s</a>'), $linkdata);
        else
          $return = $linkdata[0];
        break;

      case 'text-line' :

        if (self::$plugin_options['make_links'] and $html) {

          $return = self::make_link($value);
          break;
        } else {

          $return = $value;
          break;
        }

      default :

        $return = $value;

    endswitch;

    return $return;
  }

  /**
   * prepares a field value for display after a form has been unsuccessfully submitted
   *
   * @param string $name    the name of the field
   * @param string $default the default value of the field
   * @param array  $post    the post array
   * @return string
   */
  public function prepare_field_value($name, $default, $post) {

    if (isset($post[$name])) {

      if (is_array($post[$name]))
        return $post[$name];

      else
        return esc_html(stripslashes($post[$name]));
    } else
      return $default;
  }

  /**
   * checks for the existence of an image file
   *
   * checks both absolute path and path relative to image location setting
   *
   * DEPRICATED this is handled in the PDb_Image class
   *
   * @return bool true if file exists
   */
  public function image_exists($filename) {



    if (!file_exists($filename)) {

      $filename = get_bloginfo('wpurl') . '/' . self::$plugin_options['image_upload_location'] . basename($filename);
    }



    //error_log( __METHOD__.' '.$filename.' is file:'. (is_file( $filename ) ?'yes':'no').' file_exists:'.(file_exists( $filename ) ? 'yes' : 'no' ).' imagesize:'.(getimagesize( $filename )?'yes':'no') ) ;

    if (file_exists($filename))
      return true;

    else
      return false;
  }

  /**
   * returns a path to the defined image location
   *
   * this func is superceded by the PDb_Image class methods
   *
   * can also deal with a path saved before 1.3.2 which included the whole path
   *
   * @return the file url if valid; if the file can't be found returns the
   *         supplied filename
   */
  public function get_image_uri($filename) {

    if (!file_exists($filename)) {

      $filename = get_bloginfo('wpurl') . '/' . self::$plugin_options['image_upload_location'] . basename($filename);
    }

    return $filename;
  }

  // returns the number of participant records
  // can also be used to get the count of any other table
  public function get_num_records($table = false) {

    global $wpdb;

    if (!$table)
      $table = self::$participants_table;

    return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table;"));
  }

  /**
   * processes a form submit
   *
   * this processes all record form submissions front-end and back-
   * 
   * can either add new record or edit existing record
   * new record begins with the default values
   *
   * @param array  $post           the array of new values
   * @param string $action         the db action to be performed: insert or update
   * @param mixed  $participant_id the id of the record to update. If it is false, it creates
   *                               a new record, if true, it creates or updates the default record.
   *
   * @return int the ID of the record created or updated
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

          // make sure the old value isn't there to override the new one
          unset($_POST[$fieldname]);
        }
      }
    }

    $options = get_option(self::$participants_db_options);

    /*
     * checks for a record with a matching field so we can exercise the
     * duplicate record preference
     */
    if ($action == 'insert' and $options['unique_email'] !== 0) {

      $field = $options['unique_field'];

      if (isset($post[$field]) && !empty($post[$field]) && self::field_value_exists($post[$field], $field)) {

        switch ($options['unique_email']) {

          case 1:

            // record with same field value exists...get the id and update the existing record
            if ('id' == strtolower($field))
              $participant_id = $post[$field];
            else
              $participant_id = self::_get_participant_id_by_term($field, $post[$field]);
            // get the first one
            if (is_array($participant_id))
              $participant_id = current($participant_id);
            // set the update mode
            $action = 'update';

            break;

          case 2:

            // set the error message
            if (is_object(self::$validation_errors))
              self::$validation_errors->add_error($field, 'duplicate');
            $action = 'skip';
            // go on validating the rest of the form
            break;
        }
      }
    }

    // set the insert status value
    self::$insert_status = $action;

    switch ($action) {

      case 'update':
        $sql = 'UPDATE ';
        $where = " WHERE id = " . $participant_id;
        break;

      case 'insert':
        $sql = 'INSERT INTO ';
        $where = '';
        break;

      case 'skip':
        return false;
    }

    $sql .= self::$participants_table . " SET date_updated = NOW(), ";

    $new_values = array();
    $new_value = false;
    $columns = array();

    // determine the set of columns to process 
    $column_set = $action == 'update' ? ( is_admin() ? 'backend' : 'frontend' ) : 'new';

    // gather the submit values and add them to the query
    foreach (self::get_column_atts($column_set) as $column_atts) :

      // the validation object is only instantiated when this method is called
      // by a form submission
      if (is_object(self::$validation_errors)) {

        self::$validation_errors->validate(( isset($post[$column_atts->name]) ? $post[$column_atts->name] : ''), $column_atts, $post);
      }

      // we can process individual submit values here
      switch ($column_atts->name) {

        case 'id':
        case 'date_updated':
        case 'date_recorded':
        case 'last_accessed':
          $new_value = false;
          break;

        case 'private_id':
          $new_value = empty($post['private_id']) ? self::generate_pid() : $post['private_id'];
          break;

        default :

          // replace null value with the default if defined
          if (NULL === @$post[$column_atts->name]) {

            $new_value = empty($column_atts->default) ? false : $column_atts->default;
            $post[$column_atts->name] = $new_value;
          }

          if (in_array($column_atts->form_element, array('multi-checkbox', 'multi-select-other'))) {

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

              $value_array['other'] = implode(',', $value_array['other']);
            }

            $new_value = self::_prepare_array_mysql($value_array);
          } elseif ('link' == $column_atts->form_element) {

            /* translate the link markdown used in CSV files to the array format used in the database
             */

            if (!is_array($post[$column_atts->name])) {

              $new_value = self::_prepare_array_mysql(self::get_link_array($post[$column_atts->name]));
            } else {
              
              $new_value = self::_prepare_array_mysql($post[$column_atts->name]);
            }
          } elseif ('date' == $column_atts->form_element) {

            $date = self::parse_date($post[$column_atts->name], $column_atts);

            $new_value = $date ? $date : NULL;
          } elseif (self::backend_user() && 'textarea' == $column_atts->form_element && $options['rich_text_editor']) {

            $new_value = wpautop(stripslashes($post[$column_atts->name]));
          } elseif ('password' == $column_atts->form_element) {

            if (!empty($post[$column_atts->name]))
              $new_value = wp_hash_password(trim($post[$column_atts->name]));
            else
              $new_value = false;
          } elseif (!self::backend_user() && $column_atts->readonly != '0') {

            $new_value = false;
          } elseif (is_array($post[$column_atts->name])) {

            $new_value = self::_prepare_array_mysql($post[$column_atts->name]);
          } else {

            $new_value = self::_prepare_string_mysql(trim($post[$column_atts->name]));
          }
      }

      // add the column and value to the sql
      if (false !== $new_value) {

        // insert a true NULL if the field is NULL
        if (NULL !== $new_value)
          $new_values[] = $new_value;
        $columns[] = "`" . $column_atts->name . "` = " . ( NULL === $new_value ? "NULL" : "%s" );
      }

    endforeach; // columns
    // if the validation object exists and there are errors, stop here
    if (is_object(self::$validation_errors) && self::$validation_errors->errors_exist()) {

      // error_log( __METHOD__.' errors exist; returning');

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

      // hang on to the id of the last record for a day
      set_transient(self::$last_record, $participant_id, (1 * 60 * 60 * 24));
    }

    return $participant_id;
  }

  /**
   * parses the markdown string used to store the values for a link form element
   *
   * @param string $markdown_string
   * @return array URL, linktext
   */
  public function get_link_array($markdown_string) {

    if (preg_match('#^<([^>]+)>$#', trim($markdown_string), $matches)) {
      return array($matches[1], '');
    } elseif (preg_match('#^\[([^\]]+)\]\(([^\)]+)\)$#', trim($markdown_string), $matches)) {
      return array($matches[2], $matches[1]);
    }
    return array('', '');
  }

  /**
   * gets the default set of values
   *
   * @return associative array
   */
  public function get_default_record() {

    $sql = 'SELECT f.name,f.default 
            FROM ' . self::$fields_table . ' f
            WHERE f.group != "internal"';

    global $wpdb;

    $result = $wpdb->get_results($sql);

    $default_record = array();

    foreach ($result as $column)
      $default_record[$column->name] = $column->default;

    // get the id of the last record stored
    $prev_record_id = get_transient(self::$last_record);

    if ($prev_record_id) {

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

    $default_record['by'] = $current_user->display_name;
    $default_record['when'] = date(get_option('date_format'));
    $default_record['private_id'] = self::generate_pid();
    $default_record['date_recorded'] = date('Y-m-d H:i:s');
    $default_record['date_updated'] = date('Y-m-d H:i:s');

    return $default_record;
  }

  /**
   * gets a participant record object with its id
   *
   *
   * @param  string $id the record ID; returns default record if omitted
   * 
   * @return array associative array of the record; false if no record matching
   *               the ID was found 
   */
  public function get_participant($id = false) {

    if (false === $id)
      return self::get_default_record();

    // test that the id exists
    if (false === self::_id_exists($id))
      return false;

    global $wpdb;

    $sql = "SELECT * FROM " . self::$participants_table . " WHERE `id` = '" . $id . "'";

    return $wpdb->get_row($sql, ARRAY_A);
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

  public function get_record_id_by_term($term, $id) {

    return self::_get_participant_id_by_term($term, $id);
  }

  /**
   * gets a participant record id by term
   *
   * given an identifier, returns the id of the record identified. If there is
   * more than one record with the given term, returns the first one.
   *
   * @param string $term the column to match
   * @param string $value the value to search for
   *
   * @return unknown returns integer if one match, array of integers if multiple matches, false if no match
   */
  private function _get_participant_id_by_term($term, $value) {

    global $wpdb;

    $result = $wpdb->get_results($wpdb->prepare("SELECT `id` FROM " . self::$participants_table . " WHERE `$term` = %s", $value), ARRAY_A);

    if (!is_array($result))
      return false;

    $output = array();

    foreach ($result as $row) {

      $output[] = $row['id'];
    }

    return count($output) > 1 ? $output : current($output);
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
  public function generate_pid() {

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
   * @param string $id the identifier to test
   * @param string $field the db field to test the $id value against
   */
  private function _id_exists($id, $field = 'id') {

    global $wpdb;

    $id_exists = $wpdb->get_var("SELECT COUNT(*) FROM " . self::$participants_table . " WHERE `" . $field . "` = '" . $id . "'");

    if (NULL !== $id_exists)
      return $id_exists < 1 ? false : true;
    else {
      error_log(__METHOD__ . ' called with invalid arguments');
      return false;
    }
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
   * @return string prepped array in serialized form
   */

  private function _prepare_array_mysql($array) {

    if (!is_array($array))
      return self::_prepare_string_mysql($array);

    $prepped_array = array();

    foreach ($array as $key => $value) {

      $prepped_array[$key] = self::_prepare_string_mysql($value);
    }

    return serialize($prepped_array);
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
   * unserialize if necessary
   * 
   * @param string $string the string to unserialize; does nothing if it is not 
   *                       a serialization
   */
  public function unserialize_array($string) {

    // is_serialized is a WordPress utility function
    return is_serialized($string) ? unserialize($string) : $string;
  }

  // prepare a field for display
  // displays an array as a series of comma-separated strings
  public function prepare_value($string) {

    $value = self::unserialize_array($string);

    return is_array($value) ? implode(', ', $value) : stripslashes($value);
  }

  // adds a blank field type record
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

  private function _add_db_column($atts) {

    global $wpdb;

    $datatype = self::set_datatype($atts['form_element']);

    $sql = 'ALTER TABLE `' . self::$participants_table . '` ADD `' . $atts['name'] . '` ' . $datatype . ' NULL';

    return $wpdb->query($sql);
  }

  // returns a MYSQL datatype appropriate to the form element type
  public function set_datatype($element) {

    switch ($element) {

      case 'multi-select':
      case 'multi-checkbox':
      case 'text-field':
      case 'textarea':
        $datatype = 'TEXT';
        break;

      case 'date':
      case 'checkbox':
      case 'radio':
      case 'dropdown':
      case 'text-line':
      default :
        $datatype = 'TINYTEXT';
    }

    return $datatype;
  }

  // processes any POST requests for the submitted edit page
  public function process_page_request() {

    $options = get_option(self::$participants_db_options);

    // only process POST arrays from this plugin's pages
    if (!isset($_POST['subsource']) or $_POST['subsource'] != self::PLUGIN_NAME or !isset($_POST['action']))
      return NULL;

    // error_log( __METHOD__.' post:'.print_r( $_POST, true ) );

    /*
     * instantiate the validation object if we need to. This is necessary
     * because another script can instantiate the object in order to add a
     * feedback message
     */
    if (!is_object(self::$validation_errors))
      self::$validation_errors = new FormValidation();

    switch ($_POST['action']) :

      case 'update':
      case 'insert':

        $id = isset($_POST['id']) ? $_POST['id'] : ( isset($_GET['id']) ? $_GET['id'] : false );

        $participant_id = self::process_form($_POST, $_POST['action'], $id);

        if (false === $participant_id) {

          // we have errors; go back to form and show errors
          return;
        }

        // if we are submitting from the frontend, we're done
        if (!is_admin()) {

          self::$validation_errors->add_error('', $options['record_updated_message']);

          if ($options['send_record_update_notify_email']) {

            $sent = wp_mail(
                    $options['email_signup_notify_addresses'],
                    self::proc_tags($options['record_update_email_subject'], $participant_id),
                    self::proc_tags($options['record_update_email_body'], $participant_id),
                    self::$email_headers
            );
          }

          return;
        }

        // redirect according to which submit button was used
        switch ($_POST['submit']) {

          case 'Apply' :
            wp_redirect(get_admin_url() . 'admin.php?page=' . self::PLUGIN_NAME . '-edit_participant&id=' . $participant_id);
            break;

          case 'Next' :
            wp_redirect(get_admin_url() . 'admin.php?page=' . self::PLUGIN_NAME . '-edit_participant');
            break;

          case 'Submit' :
            wp_redirect(get_admin_url() . 'admin.php?page=' . self::PLUGIN_NAME . '-list_participants&id=' . $participant_id);

          default :
        }
        break;

      case 'output CSV':

        $header_row = array();
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
            }

            $data['header'] = $header_row;

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

        /* if someone signs up with an email that already exists, we update that
         * record rather than let them create a new record. This gives us a method
         * for dealing with people who have lost their access link, they just sign
         * up again with the same email, and their access link will be emailed to
         * them. This is handled by the Participants_Db::process_form method.
         */

        $_POST['private_id'] = self::generate_pid();

        // only go to the thanks page if we have no errors
        $_POST['id'] = self::process_form($_POST, 'insert');

        if (false !== $_POST['id']) {

          $conj = false !== strpos($_POST['thanks_page'], '?') ? '&' : '?';

          wp_redirect($_POST['thanks_page'] . $conj . 'id=' . $_POST['id']);

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

  // returns the title attribute of a column
  public function column_title($column) {

    $field = self::get_field_atts($column, 'title');

    return $field->title;
  }

  /**
   * returns a select state for a form field
   *
   * @param string $element_value the set value of the element_value that we compare against
   * @param string $new_value     the selected value of the field
   *                              if $element_value is an array, we check if $new_value is in it
   * @param string $attribute     the keyword for the select state of the form element
   * @param bool   $state         inverts the logic of the array value match: true = looking for a match; 
   *                              false = looking for no match
   *
   * @return string the select attribute for an option, radio or checkbox field
   */
  public function set_selected($element_value, $new_value, $attribute = 'selected', $state = true) {

    $pattern = ' %1$s="%1$s" ';

    if (
            ( is_array($element_value) && ( $state === in_array($new_value, $element_value) ) )
            ||
            $element_value == $new_value
    ) {

      return sprintf($pattern, $attribute);
    } else
      return '';
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
        return array('type' => 'text-field');

      // drop-down fields
      case 'form_element':
        // populate the dropdown with the available field types from the FormElement class
        return array('type' => 'dropdown', 'options' => self::$element_types);

      case 'validation':
        return array(
            'type' => 'dropdown-other',
            'options' => array(
                __('Not Required', Participants_Db::PLUGIN_NAME) => 'no',
                __('Required', Participants_Db::PLUGIN_NAME) => 'yes',
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

            $value = date(get_option('date_format'), $value);
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

        default:

          // flatten arrays
          if (is_serialized($value))
            $value = implode(', ', unserialize($value));
      }

      // decode HTML entities
      $output[$key] = html_entity_decode($value, ENT_QUOTES, "utf-8");

      $column = next($columns);
    }

    return $output;
  }

  /**
   * outputs a link in specified format
   * 
   * @param string $link the URI
   * @param string $linktext the clickable text (optional)
   * @param string $template the format of the link (optional)
   * @param array  $get an array of name=>value pairs to include in the get string
   *
   * @return string HTML or HTML-escaped string (if it's not a link)
   */
  public function make_link($link, $linktext = '', $template = false, $get = false) {

    // clean up the provided link string
    $URI = str_replace('mailto:', '', strtolower(trim(strip_tags($link))));

    if (filter_var($URI, FILTER_VALIDATE_URL)) {

      // convert the get array to a get string and add it to the URI
      if (is_array($get)) {

        $URI .= false !== strpos($URI, '?') ? '&' : '?';

        $URI .= http_build_query($get);
      }
    } elseif (filter_var($URI, FILTER_VALIDATE_EMAIL)) {

      // in admin, emails are plaintext
      if (is_admin())
        return esc_html($link);

      if (self::$plugin_options['email_protect']) {

        // the email gets displayed in plaintext if javascript is disabled; a clickable link if enabled
        list( $URI, $linktext ) = explode('@', $URI, 2);
        $template = '<a class="obfuscate" rel=\'{"name":"%1$s","domain":"%2$s"}\'>%1$s AT %2$s</a>';
      } else {

        $linktext = empty($linktext) ? $link : $linktext;
        $URI = 'mailto:' . $URI;
      }
    } else
      return esc_html($link); // if it is neither URL nor email address


      
// default template for links
    $linktemplate = $template === false ? '<a href="%1$s" >%2$s</a>' : $template;

    $linktext = empty($linktext) ? str_replace(array('http://', 'https://'), '', $URI) : $linktext;

    //construct the link
    return sprintf($linktemplate, $URI, esc_html($linktext));
  }

  /**
   * handles file uploads
   *
   * @param array $upload_file the $_FILES array element corresponding to one file
   *
   * return string the path to the uploaded file or false if error
   */
  private function _handle_file_upload($name, $file) {

    $options = get_option(self::$participants_db_options);

    if (!is_dir(ABSPATH . $options['image_upload_location'])) {

      if (false === self::_make_uploads_dir($options['image_upload_location']))
        return false;
    }

    if (!is_uploaded_file($file['tmp_name'])) {

      self::$validation_errors->add_error($name, __('There is something wrong with the file you tried to upload. Try another.', self::PLUGIN_NAME));

      return false;
    }

    $fileinfo = getimagesize($file['tmp_name']);

    // check the type of file to make sure it is an image file
    if (!in_array($fileinfo[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WBMP))) {

      self::$validation_errors->add_error($name, __('You may only upload image files like JPEGs, GIFs or PNGs.', self::PLUGIN_NAME));

      return false;
    }

    // make sure the filename is OK:
    $new_filename = preg_replace(
            array("/\s+/", "/[^-\.\w]+/"), array("_", ""), trim($file['name']));

    if ($file['size'] > $options['image_upload_limit'] * 1024) {

      self::$validation_errors->add_error($name, sprintf(__('The image you tried to upload is too large. The file must be smaller than %sK.', self::PLUGIN_NAME), $options['image_upload_limit']));

      return false;
    }

    if (false === move_uploaded_file($file['tmp_name'], ABSPATH . $options['image_upload_location'] . $new_filename)) {

      self::$validation_errors->add_error($name, __('The file could not be saved.', self::PLUGIN_NAME));

      return false;
    }

    /*
     * as of 1.3.2 we save the image as filename only; the image is retrieved from 
     * the directory defined in the plugin setting using the self::get_image function
     */

    else
      return /* get_bloginfo('wpurl').DIRECTORY_SEPARATOR.$options['image_upload_location']. */$new_filename;
  }

  /**
   * attempt to create the uploads directory
   *
   * sets an error if it fails
   */
  private function _make_uploads_dir($dir) {

    $savedmask = umask(0);

    if (false === mkdir(ABSPATH . $dir, 0755, true)) {

      if (is_object(self::$validation_errors))
        self::$validation_errors->add_error('', sprintf(__('The uploads directory (%s) could not be created.', self::PLUGIN_NAME), $dir));

      umask($savedmask);

      return false;
    }

    umask($savedmask);

    return true;
  }

  /**
   * builds a record edit link
   *
   * @param string $PID private id value
   * @return string private record URI
   */
  public function get_record_link($PID) {

    $options = get_option(self::$participants_db_options);

    // if the setting is not yet set, don't try to build a link
    if (!isset($options['registration_page']) || empty($options['registration_page']))
      return '';

    // if the setting was made in previous versions and is a slug, convert it to a post ID
    if (!is_numeric($options['registration_page'])) {

      $options['registration_page'] = self::_get_ID_by_slug($options['registration_page']);

      update_option(self::$participants_db_options, $options);
    }

    $page_link = get_page_link($options['registration_page']);

    $delimiter = false !== strpos($page_link, '?') ? '&' : '?';

    return $page_link . $delimiter . 'pid=' . $PID;
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
  private function _get_ID_by_slug($page_slug) {

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

      $tags[] = '[' . $column->name . ']';

      $values[] = self::prep_field_for_display($participant[$column->name], $column->form_element);
    }

    $options = get_option(self::$participants_db_options);

    // add the "record_link" tag
    $tags[] = '[record_link]';
    $values[] = $options['registration_page'];

    // add the date tag
    $tags[] = '[date]';
    $values[] = date(get_option('date_format'), self::parse_date());


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
   * called by the wp_ajax_nopriv_pdb_list_filter action
   *
   * this happens when a user submits a search or sort on a record list
   *
   */
  public function pdb_list_filter() {

    if (!wp_verify_nonce($_POST['filterNonce'], 'pdb-list-filter-nonce'))
      die('nonce check failed');

    global $post;

    if (!is_object($post))
      $post = get_post($_POST['postID']);
    
    // grab the shortcode out of the page content
    preg_match( '#\[pdb_list([^\]]*)\]#', $post->post_content, $matches );
    // put the attributes array together
    $atts = shortcode_parse_atts(trim($matches[1]));
    $atts['filtering'] = 1;
    // output the filtered shortcode content
    header("Content-Type:	text/html");
    echo PDb_List::print_record( $atts );

    exit;
  }

  /**
   * parses a date string into UNIX timestamp
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

    // it's already a timestamp; or something that looks like a timestamp but wouldn't parse anyway
    if (preg_match('#^[0-9-]+$#', $string) > 0)
      return $string;

    if (self::$plugin_options['strict_dates'] && function_exists('date_create_from_format')) {

      $date = date_create_from_format(get_option('date_format'), $string);

      if (is_array(date_get_last_errors()) && !empty($string)) {

        $errors = date_get_last_errors();

        if ($errors['warning_count'] > 0 || $errors['error_count'] > 0) {

          $date = false;

          if (is_object(self::$validation_errors) and is_object($column)) {

            self::$validation_errors->add_error($column->name, sprintf(__('The date for "%s" was invalid. Please input the date with the exact format shown', self::PLUGIN_NAME), $column->title));
          }
        }
      }

      // if we have a valid date, convert to timestamp
      if ($date)
        $date = date_format($date, 'U');
    } else {

      $date = strtotime($string);
    }

    return $date;
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
   * translates the current date format option string to a jQuery UI date format string
   *
   */
  function get_jqueryUI_date_format($PHP_date_format = '') {

    $dateString = empty($PHP_date_format) ? get_option('date_format') : $PHP_date_format;

    $pattern = array(
        //day
        'd', //day of the month
        'j', //3 letter name of the day
        'l', //full name of the day
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
    $replace = array(
        'dd', 'd', 'DD', 'o',
        'MM', 'M', 'm', 'mm',
        'yy', 'y'
    );
    foreach ($pattern as &$p) {
      $p = '/' . $p . '/';
    }
    return preg_replace($pattern, $replace, $dateString);
  }

  /**
   * displays a credit footer for the plugin
   *
   * @return string HTML
   */
  public function plugin_footer() {
    ?>
    <div id="PDb_footer" class="widefat">
      <div class="section">
        <h4><?php echo self::$plugin_title, ' ', self::$plugin_version ?><br /><?php _e('WordPress Plugin', self::PLUGIN_NAME) ?></h4>
        <p><em><?php _e('Helping organizations manage their volunteers, members and participants.', self::PLUGIN_NAME) ?></em></p>
      </div>
      <div class="section">
        <h4><a href="http://xnau.com"><img src="<?php bloginfo('wpurl') ?>/wp-content/plugins/<?php echo self::PLUGIN_NAME ?>/ui/xnau-square-60.png" style="float:left;width:50px;height:auto;margin-right:10px" /></a><?php _e('Developed by', self::PLUGIN_NAME) ?><br /><a href="http://xnau.com">xn&lowast;au webdesign</a></h4>
        <p><?php _e('Suggestions or crticisms of this plugin? I&#39;d love to hear them: email ', self::PLUGIN_NAME) ?><a href="mailto:support@xnau.com">support@xnau.com.</a>
      </div>
      <div class="section">
        <p><?php printf(__('Please consider contributing to the continued support and development of this software by visiting %1$sthis plugin&#39;s page,%3$s giving the plugin a %2$srating%3$s or review, or dropping something in the %1$stip jar.%3$s Thanks!', self::PLUGIN_NAME), '<a href="http://xnau.com/wordpress-plugins/participants-database">', '<a href="http://wordpress.org/extend/plugins/participants-database/">', '</a>') ?></p>
      </div>
    </div>
    <?php
  }

  /**
   * parses the text header to extract plugin info
   */
  private function _get_plugin_data($key = 'Name') {

    if (!defined('ABSPATH'))
      return '';

    if (!function_exists('get_plugin_data'))
      include ABSPATH . '/wp-admin/includes/plugin.php';

    $plugin_data = get_plugin_data(__FILE__);

    return $plugin_data[$key];
  }

}

// class

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
