<?php  
/*  
Plugin Name: Participants Database 
Plugin URI: http://xnau.com/wordpress-plugins/participants-database
Description: Plugin for managing a database of participants, members or volunteers
Author: Roland Barker
Version: 1.3.7
Author URI: http://xnau.com 
License: GPL2
Text Domain: participants-database
*/
	
/*  Copyright 2011, 2012 Roland Barker xnau webdesign  (email : webdesign@xnau.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
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
	
	// record ID numbers begin counting up from this number
	public static $id_base_number = 1325;
	
	// name of the transient record used to hold the last record
	public static $last_record = 'pdb_last_record';
	
	// these columns are not manually edited
	public static $internal_columns;
	
	// header to include with plugin emails
	public static $email_headers;
	
	public function initialize() {

		// register the class autoloading
		spl_autoload_extensions( '.php' );
		spl_autoload_register( 'PDb_class_loader' );

		// set the table names
		global $wpdb;
		self::$participants_table = $wpdb->prefix . str_replace( '-', '_', self::PLUGIN_NAME );
		self::$fields_table = self::$participants_table . '_fields';
		self::$groups_table = self::$participants_table . '_groups';

		// name of the WP option where the current db version is stored
		self::$db_version = 'PDb_Db_version';
		
		// set the plugin version
		self::$plugin_version = self::_get_plugin_data('Version');

		// define some locations
		self::$participants_db_options = self::PLUGIN_NAME.'_options';
		self::$plugin_page = self::PLUGIN_NAME;
		self::$plugin_url = WP_PLUGIN_URL.'/'.self::PLUGIN_NAME;
		self::$plugin_path = dirname(__FILE__);
		// this is relative to the WP install
		self::$uploads_path = 'wp-content/uploads/'.self::PLUGIN_NAME.'/';

		//self::$plugin_settings = new PDb_Settings();
		//self::$plugin_options = get_option( self::$participants_db_options );
		
		// hard-code some image file extensions
		self::$allowed_extensions = array( 'jpg','jpeg','gif','png' );
		
		// define an array of all available form element types
    self::$element_types = array(
                                 'text-line',
                                 'textarea',
                                 'checkbox',
                                 'radio',
                                 'dropdown',
                                 'date',
                                 'dropdown-other',
                                 'multi-checkbox',
                                 'select-other',
                                 'multi-select-other',
																 'link',
																 'image-upload',
																 'hidden',
                                 );
		

    // install/deactivate and uninstall methods are handled by the PDB_Init class
    register_activation_hook( __FILE__, array( 'PDb_Init', 'on_activate' ) );
    register_deactivation_hook( __FILE__, array( 'PDb_Init', 'on_deactivate' ) );
    register_uninstall_hook( __FILE__, array( 'PDb_Init', 'on_uninstall' ) );


    // define the list of internal columns
    if ($wpdb->get_var('show tables like "'.Participants_Db::$groups_table.'"') == Participants_Db::$groups_table) {
			
      self::$internal_columns = array();
			
      $internal_columns = $wpdb->get_results( 'SELECT f.name 
																						 	 FROM '.self::$fields_table.' f 
																							 INNER JOIN '.self::$groups_table.' g 
																							 ON f.group = g.name 
																							 WHERE g.display = 0'
																						, ARRAY_N );
			
      foreach ( $internal_columns as $column ) self::$internal_columns[] = $column[0];
			
    }
		
		// set the last record value to the initial id
		// this is used to keep persistent fields as an aid to data entry
		set_transient( self::$last_record, self::$id_base_number, (1*60*60*24) );
								
		add_filter('query_vars', array( __CLASS__, 'register_queryvars') );

		// set the WP hooks to finish setting up the plugin
		add_action( 'init', array( __CLASS__, 'init') );
		add_action( 'admin_menu', array( __CLASS__, 'plugin_menu') );
		add_action( 'admin_init', array( __CLASS__, 'admin_init') );

		// define our shortcodes
		add_shortcode( 'pdb_record', array( __CLASS__, 'frontend_edit') );
		add_shortcode( 'pdb_signup', array( __CLASS__, 'print_signup_form' ) );
		add_shortcode( 'pdb_signup_thanks', array( __CLASS__, 'print_signup_thanks_form' ) );
		add_shortcode( 'pdb_list', array( 'PDb_List','initialize' ) );
		add_shortcode( 'pdb_single', array( __CLASS__, 'show_record' ) );
		
	
		if ($wpdb->get_var('SHOW TABLES LIKE "'.Participants_Db::$participants_table.'"') == Participants_Db::$participants_table) :
		// db integrity check and fix
		$query = 'SELECT * FROM '.self::$fields_table;
		$fields = $wpdb->get_results( $query, ARRAY_A );
		$columns_raw = self::get_columns();
		$columns = array();
		foreach( $columns_raw as $col ) $columns[] = $col['Field'];
		foreach ( $fields as $field ) {
			
			if ( ! in_array( $field['name'], $columns ) ) {
				
				//error_log( 'adding column:'.print_r( $field, true ));
				
				self::_add_db_column( $field );
				
			}
			
		}
		endif;// end integrity check and fix
	
	}
	
	function admin_init() {
		
		// if the setting was made in previous versions and is a slug, convert it to a post ID
		if ( isset( self::$plugin_options['registration_page'] ) && ! is_numeric( self::$plugin_options['registration_page'] ) ) {
			
			self::$plugin_options['registration_page'] = self::_get_ID_by_slug( self::$plugin_options['registration_page'] );
			
			update_option( self::$participants_db_options, self::$plugin_options );
			
		}
		
	}
	
	public function init() {

    load_plugin_textdomain( self::PLUGIN_NAME, false, dirname( plugin_basename( __FILE__ ) ).'/languages/' );

    self::$plugin_title = __('Participants Database', self::PLUGIN_NAME );

    // set the email content headers
    if ( ! isset( self::$plugin_options ) ) {

      self::$plugin_options = get_option( self::$participants_db_options );

    } 
		
		$options = self::$plugin_options;
		
    if ( 0 != $options['html_email'] ) {
      $type = 'text/html; charset="'.get_option('blog_charset').'"';
    } else {
      $type = 'text/plain; charset=us-ascii';
    }
    self::$email_headers= "MIME-Version: 1.0\n" .
                          "From: ".$options['receipt_from_name']." <".$options['receipt_from_address'].">\n" .
                          "Content-Type: ".$type."\n";
		
		self::include_scripts();

		// this processes form submits before any output so that redirects can be used
		self::process_page_request();

	}
	
	public function plugin_menu() {
		
		// intialize the plugin settings
		// we do this here because we need the object for the plugin menus
		//self::$plugin_settings = new PDb_Settings();

    self::$plugin_settings = new PDb_Settings();
    self::$plugin_options = get_option( self::$participants_db_options );
		
		// define the plugin admin menu pages
	  add_menu_page(
			self::$plugin_title, 
			self::$plugin_title, 
			'', 
			self::PLUGIN_NAME, 
			array( __CLASS__, 'include_admin_file' ) 
			);
		
		$listpage = add_submenu_page(
			self::PLUGIN_NAME, 
			__('List Participants', self::PLUGIN_NAME ),
			__('List Participants', self::PLUGIN_NAME ), 
			self::$plugin_options['record_edit_capability'],
			self::$plugin_page.'-list_participants',
			array( 'PDb_List','initialize' )
			/*array( __CLASS__, 'include_admin_file' )*/ 
			);
		
		$addpage = add_submenu_page(
			self::PLUGIN_NAME,  
			__('Add Participant', self::PLUGIN_NAME ), 
			__('Add Participant', self::PLUGIN_NAME ),
			self::$plugin_options['record_edit_capability'],
			self::$plugin_page.'-edit_participant', 
			array( __CLASS__, 'include_admin_file' ) 
			);
		
		$managepage = add_submenu_page(
			self::PLUGIN_NAME, 
			__('Manage Database Fields', self::PLUGIN_NAME ), 
			__('Manage Database Fields', self::PLUGIN_NAME ), 
			'manage_options', 
			self::$plugin_page.'-manage_fields', 
			array( __CLASS__, 'include_admin_file' ) 
			);
		
		$uploadpage = add_submenu_page(
			self::PLUGIN_NAME, 
			__('Import CSV File', self::PLUGIN_NAME ), 
			__('Import CSV File', self::PLUGIN_NAME ), 
			'manage_options', 
			self::$plugin_page.'-upload_csv', 
			array( __CLASS__, 'include_admin_file' ) 
			);
		
		$settingspage = add_submenu_page(
			self::PLUGIN_NAME, 
			__('Settings', self::PLUGIN_NAME ),
			__('Settings', self::PLUGIN_NAME ), 
			'manage_options', 
			self::$plugin_page.'_settings_page', 
			array( self::$plugin_settings, 'show_settings_form' ) 
			);
		
		$editpage = add_submenu_page(
			'', 
			__('Edit Record', self::PLUGIN_NAME ), 
			__('Edit Record', self::PLUGIN_NAME ), 
			self::$plugin_options['record_edit_capability'],
			self::$plugin_page.'_edit_participant'
			);

		// add the CSS for the admin pages such that it will only be loaded when
		// those pages are called. There is probably a better way to do this, but
		// this works
    add_action( 'admin_print_styles-' . $settingspage , array( __CLASS__, 'admin_style' ) );
    add_action( 'admin_print_styles-' . $listpage , array( __CLASS__, 'admin_style' ) );
    add_action( 'admin_print_styles-' . $addpage , array( __CLASS__, 'admin_style' ) );
    add_action( 'admin_print_styles-' . $managepage , array( __CLASS__, 'admin_style' ) );
    add_action( 'admin_print_styles-' . $uploadpage , array( __CLASS__, 'admin_style' ) );

    // add a global stylesheet to use while the plugin is active
    add_action( 'admin_print_styles', array( __CLASS__, 'global_admin_style' ) );

		// add any js scripts needed for the admin pages
    add_action( 'admin_print_scripts-' . $managepage, array( __CLASS__, 'manage_fields_scripts' ));
    add_action( 'admin_print_scripts-' . $settingspage, array( __CLASS__, 'settings_scripts' ));
    //add_action( 'admin_enqueue_scripts', array( __CLASS__, 'edit_scripts' ));
		
	}
	
	public function admin_style() {
		wp_enqueue_style( 'participants_db_admin_stylesheet', '/wp-content/plugins/'.self::PLUGIN_NAME.'/participants-db.css' );// plugins_url('participants-db.css', __FILE__)
	}

	public function global_admin_style() {
		wp_enqueue_style( 'participants_db_global_admin_stylesheet','/wp-content/plugins/'.self::PLUGIN_NAME.'/PDb-admin.css', false, false  );// plugins_url('PDb-admin.css', __FILE__)
	}

	public function manage_fields_scripts() {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'jquery-ui-tabs' );
		wp_enqueue_script( 'jquery-ui-dialog' );

		wp_register_script( 'cookie', plugins_url( 'js/jquery.cookie.js', __FILE__ ) );
		wp_register_script( 'manage_fields', plugins_url( 'js/manage_fields.js', __FILE__ ) );
		wp_enqueue_script( 'cookie' );
		wp_enqueue_script( 'manage_fields' );
		/* translators: don't translate the words in brackets {} */
		wp_localize_script( 'manage_fields', 'L10n', array(
			'must_remove' => '<h4>You must remove all fields from the {name} group before deleting it.</h4>',
			'delete_confirm' => '<h4>Delete the "{name}" {thing}?</h4>',
		) );
	}

	public function settings_scripts() {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-tabs' );
		wp_register_script( 'cookie', plugins_url( 'js/jquery.cookie.js', __FILE__ ) );
		wp_enqueue_script( 'cookie' );
		wp_register_script( 'settings_script', plugins_url( 'js/settings.js', __FILE__ ) );
		wp_enqueue_script( 'settings_script' );
	}
	
	

	public function edit_scripts() {
		
		wp_register_script( 'datepicker', plugins_url( 'js/jquery.datepicker.js', __FILE__ ) );
		wp_register_script( 'edit_record', plugins_url( 'js/edit.js', __FILE__ ) );
		
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'datepicker' );
		//wp_enqueue_script( 'edit_record' );
		
	}
	
	// include any JS needed for the front-end
	public function include_scripts() {
		
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'frontend', plugins_url( 'js/shortcodes.js', __FILE__ ) );
		
	}
		

	// callback for plugin admin subpages
	// grabs the name from the request and includes the file to display the page
	public function include_admin_file() {
		
		$parts = explode( '-', $_GET['page'] );
		
		$file = array_pop( $parts );

		// we'll need this in the included file
		global $wpdb;
		
		include $file.'.php';
		
	}
	
	// register all the GET variables we want to use
	public function register_queryvars( $qvars ) {
		
		// 'pdb' is used bye the pdb_show shortcode
		$qvars[] = 'pdb';
		
		return $qvars;
		
	}
	
	// plays out a record edit screeen for a participant
	// it requires the use of a link with an id number
	// low security on entry becuase we don't require that they establish an
	// account, but we do use an encrypted account number
	public function frontend_edit( $atts ) {

		// at present, there are no attributes
		$vars = shortcode_atts( array(
                                  'class' => 'PDb-record'
                                  ), $atts );
		
		if ( isset( $_GET['pid'] ) ) {

      $participant_id = self::get_participant_id( $_GET['pid'] );

      if ( $participant_id ) {
				
				ob_start();
        ?>
        <style type="text/css"><?php include 'PDb-record.css' ?></style>
        <div class="<?php echo $vars['class']?>">
        <?php

        include 'edit_participant.php';

        ?></div><?php
				
				return ob_get_clean();
			
      } else return '<p>'.__('There is no record for this ID.', Participants_Db::PLUGIN_NAME ).'</p>';
    }
		
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
  public function show_record( $atts ) {

    /*
     * the template attribut selects the template file
     * the term attribute selects the term to use to look up the record
     * this should be a unique identifier, but if it's not it will show a series of records
     */
    $vars = shortcode_atts( array(
                                  'template' => 'default',
                                  'term'     => 'id',
                                  'class'    => 'PDb-single'
                                  ), $atts );

    if ( isset( $_GET['pdb'] ) ) {

      $ids = self::_get_participant_id_by_term( $vars['term'], $_GET['pdb'] );
			
			$template = get_stylesheet_directory().'/templates/pdb-single-'.$vars['template'].'.php';
			
			if ( ! file_exists( $template ) ) {

      	$template = self::$plugin_path.'/templates/pdb-single-default.php';

			} elseif ( ! file_exists( $template ) ) {
				
        error_log( __METHOD__.' template not found: '.$template );
        return '<p>'._x('Missing Template', 'message to show if the plugin cannot find the template', self::PLUGIN_NAME ).'</p>';
				
      }

      ob_start();

      ?><style type="text/css"><?php include 'PDb-record.css' ?></style><?php

      foreach( (array) $ids as $id ) :

        if ( self::_id_exists( $id ) ) {
          ?>
          <div class="<?php echo $vars['class']?>">
          
            <?php include $template ?>
            
          </div><?php

        } else echo '<p>'.sprintf( __( 'No record found for %s.', Participants_Db::PLUGIN_NAME ), $id ).'</p>';

      endforeach;// cycle through ids

      return ob_get_clean();
      
    }

  }
	
	/**
	 * prints a signup form
	 */
	public function print_signup_form( $params ) {

		return Signup::print_form( $params );
		
	}
	
	/**
	 * prints the signup thanks form
	 */
	public function print_signup_thanks_form( $params ) {
		
		$params['type'] = 'thanks';

		return Signup::print_form( $params );
		
	}
		
	
	// get all the attributes of a field by it's name
	// an attribute or comma-separated list of attributes can be specified
	// if not, a default list of attributes is retrieved
	//
	// returns data object
  public function get_field_atts( $field = false, $atts = '*' ) {
		
		if ( ! $field ) {
			
			$return = new stdClass;
			$return->form_element = '';
			return $return;
			
		}

	 global $wpdb;
	 
	 $sql = "
		SELECT ".$atts." 
		FROM ".self::$fields_table." 
		WHERE `name` = '".$field."'";
		
	 //error_log( __METHOD__.' called with:'. $sql );
	 
	 $field_atts = $wpdb->get_row( $sql );
		
	 return $field_atts;
	 
  }

	// get the groups info
  public function get_groups( $column = '*', $exclude = '' ) {

		global $wpdb;
		
		$where = empty( $exclude ) ? '' : ' WHERE `name` != "'.$exclude.'" ';

		$sql = 'SELECT '.$column.' FROM '.self::$groups_table.$where.' ORDER BY `order`,`name` ASC';
		
		// are we looking for only one column?
		// if so, flatten the array
		if ( $column !== '*' and false === strpos( $column, ',' ) ) {
			
			$output = array();
			
			foreach ( $wpdb->get_results( $sql, ARRAY_A ) as $row ) $output[] = $row[$column];
			
			return $output;
			
		} else {
			
			$group_index = array();
			
			$groups = $wpdb->get_results( $sql, ARRAY_A );
			
			// build an array indexed by the group's name
			foreach( $groups as $group ) $group_index[ $group['name'] ] = $group;
			
			return $group_index;
			
		}

  }

	// get the names of all the persistent fields 
	public function get_persistent() {
		
	 return self::get_subset( 'persistent' );
	 
	}

	// get the names of all the sortable fields
	public function get_sortables() {
		
	 global $wpdb;
	 
	 if ( is_admin() ) $frontend_fields = '';
	 else $frontend_fields = ' AND `display_column` > 0 ';

		$sql = "
			SELECT `name`,`title`
			FROM ".self::$fields_table."
			WHERE `sortable` > 0".$frontend_fields;

		$result = $wpdb->get_results( $sql, ARRAY_N );

		// get the 2nd dimension of the array
		$return = array();
		foreach( $result as $item ) $return[$item[1]] = $item[0];

		return $return;
	 
	}

	// get a subset of field names; this function only works for boolean qualifiers
	private function get_subset( $subset ) {
	
		global $wpdb;

		$sql = "
			SELECT `name`
			FROM ".self::$fields_table."
			WHERE `".$subset."` > 0";

		$result = $wpdb->get_results( $sql, ARRAY_N );

		// get the 2nd dimension of the array
		$return = array();
		foreach( $result as $item ) $return[] = $item[0];

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
	public function get_list_display_columns( $set = 'admin_column' ) {
	
    global $wpdb;

    $sql = "
      SELECT `name`,`".$set."`
      FROM ".self::$fields_table."
      WHERE `".$set."` > 0";
		
	 $columns = $wpdb->get_results( $sql, ARRAY_A );
	 
	 $column_set = array();
	 foreach ( $columns as $column ) {
	 
		$column_set[ $column[ $set ] ] = $column[ 'name' ];
		
	 }
	 
	 if ( $set == 'admin_column' && self::$plugin_settings->get_option( 'show_pid' ) ) $column_set[0] = 'private_id';
	 
	 ksort( $column_set );
	 
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

		$sql = "SHOW COLUMNS FROM ".self::$participants_table;

		return $wpdb->get_results($sql, ARRAY_A);
	 
  }
	
	/**
	 * checks a string against active columns to validate input
	 */
	public function is_column( $string ) {
		
		global $wpdb;
		
		$sql = "SELECT f.name 
		        FROM ".self::$fields_table." f";
		
		$columns_info = $wpdb->get_results( $sql, ARRAY_N );
		
		$columns = array();
		
		foreach ( $columns_info as $column_data ) {
			
			$columns[] = $column_data[0];
			
		}
		
		return in_array( $string, $columns );
		
	}
		
		

	/**
	 * gets the field attributes as filtered by the type of form to display
	 *
	 * @param string $filter sets the context of the display and determines the set of columns to return
	 * @return object the object is ordered first by the order of the group, then by the field order
	 */
	public function get_column_atts( $filter = 'new' ) {
		
		global $wpdb;

		switch ( $filter ) {

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
			
				$where = 'WHERE v.group = "internal"';
				break;

			case 'new':
			case 'backend':
			default:

				$where = 'WHERE v.name NOT IN ( "id" ) ';

		}

		$sql = 'SELECT v.*, g.order FROM '.self::$fields_table.' v INNER JOIN '.self::$groups_table.' g ON v.group = g.name '.$where.' ORDER BY g.order, v.order';
		
		return $wpdb->get_results( $sql, OBJECT_K );
		
	}
	
  /**
   * builds an object of all participant values structured by groups and columns
   */
	public function single_record_fields( $id, $exclude = '' ) {

    global $wpdb;

    // get the groups object
    $sql = '
		        SELECT g.title, g.name, g.description  
		        FROM '.self::$groups_table.' g 
						WHERE g.display = 1 
						ORDER BY `order` ASC
						';

    $groups = $wpdb->get_results( $sql, OBJECT_K );

    if ( is_array( $exclude ) ) {

      $excludes = "AND v.name NOT IN ('".implode( "','", $exclude )."') ";

    } else $excludes = '';

    // add the columns to each group
    foreach( $groups as $group ) {

      $group->fields = $wpdb->get_results( 'SELECT v.name, v.title, v.form_element 
                                            FROM '.self::$fields_table.' v
                                            WHERE v.group = "'.$group->name.'"
                                            '.$excludes.'
																						AND v.form_element != "hidden"  
                                            ORDER BY v.order
                                            ', OBJECT_K );

      // now get the participant value for the field
      foreach( $group->fields as $field ) {

        $field->value = current( $wpdb->get_row( "SELECT `".$field->name."`
                                         FROM ".self::$participants_table."
                                         WHERE `id` = '".$id."'", ARRAY_N ) );

      } // fields

    }// groups

    return $groups;

	}
	
	/**
	 * prepares a field value for display
	 *
	 * @param string $value        the raw value of the field
	 * @param string $form_element the form element type of the field
	 * @return string
	 */
	public function prep_field_for_display( $value, $form_element ) {
		
		switch ( $form_element ) :
              
			case 'image-upload' :
			
				$return = empty( $value ) ? '' : '<img src="'.self::get_image_uri( $value ).'" />';
				break;
				
			case 'date' :
			
				$return = empty( $value ) ? '' : date( get_option( 'date_format' ), self::parse_date( $value ) );
				break;
				
			case 'multi-checkbox' :
			case 'multi-select-other' :
			
				$return = implode( ', ', (array) self::unserialize_array( $value ) );
				break;
				
			case 'link' :
			
				$linkdata = Participants_Db::unserialize_array( $value );
			
				if ( ! is_array( $linkdata ) ) { 
				
					$return = '';
					break;
					
				}
				
				if ( 2 > count( $linkdata ) ) $lindata[1] = $linkdata[0];
			
				$return = vsprintf( ( empty( $linkdata[0] ) ? '%1$s%2$s' : '<a href="%1$s">%2$s</a>' ), $linkdata );
				break;
				
      case 'text-line' :

        if ( self::$plugin_options['make_links'] ) {

          $return = self::make_link( $value );
          break;

        }
				
			default :
			
				$return = $value;
				
		endswitch;
		
		return $return;
		
		
	}
	
	/**
	 * returns a path to the defined image location
	 *
	 * can also deal with a path saved before 1.3.2 which included the whole path
	 */
	public function get_image_uri( $filename ) {
		
		if ( ! file_exists( $filename ) ) {
			
			$fileURI = get_bloginfo('wpurl').'/'.self::$plugin_options['image_upload_location'].basename( $filename );
					
			return $fileURI ;
			
		} else return $filename;
		
	}
	
	// returns the number of participant records
	// can also be used to get the count of any other table
	public function get_num_records( $table = false ) {
		
		global $wpdb;
		
		if ( ! $table ) $table = self::$participants_table;
		
		return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table;" ) );
		
	}
  
	/**
	 * processes a form submit
	 * can either add new record or edit existing record
	 * new record begins with the default values
	 *
	 * @param array  $post           the array of new values
	 * @param string $action         the db action to be performed
	 * @param mixed  $participant_id the id of the record to update. If it is false, it creates
	 *                               a new record, if true, it creates or updates the default record.
	 *
	 * @return int the ID of the record created or updated
	 */
  public function process_form( $post, $action, $participant_id = false ) {
		
		global $wpdb;
		
		// are we doing the default record? This only happens when the DB is set up or updated
		if ( true === $participant_id ) {

			// we're doing the default record, get it's id
			$participant_id = self::$id_base_number;
			
			// does the default record exist?
			// check the first record
			$sql = 'SELECT `id` FROM `'.self::$participants_table;
			
			if ( self::$id_base_number == $wpdb->get_var($sql) ) {
			
				$action = 'update';
			
			} else {
			
				$action = 'insert';
			
			}

		}
		
		if ( ! empty( $_FILES ) && ! isset( $_POST['csv_file_upload' ] ) ) {
			
			foreach ( $_FILES as $fieldname => $attributes ) {
				
				if ( UPLOAD_ERR_NO_FILE == $attributes['error'] ) continue;
				
				// place the path to the file in the field value
				$filepath = self::_handle_file_upload( $fieldname, $attributes );
				
				if ( false !== $filepath ) {
					
					$post[ $fieldname ] = $filepath;
					
					// make sure the old value isn't there to override the new one
					unset( $_POST[ $fieldname ] );
					
				}
				
			}
			
		}
		
		$options = get_option( self::$participants_db_options );

		// check for an existing record with same email so we can at least avoid
		// inserting a duplicate email address into the database
		if ( $options['unique_email'] && isset( $post['email'] ) && ! empty( $post['email'] ) && self::email_exists( $post['email'] ) ) {

      // record with same email exists...get the id and update the existing record
      $participant_id = self::_get_participant_id_by_term( 'email', $post['email'] );
			
			// if there is more than one record with a particular email, return the first one
			if ( is_array( $participant_id ) ) $participant_id = current( $participant_id );
			
			//unset( $post['private_id'] ); 
      $action = 'update';

    }

    switch ($action) {

      case 'update':
        $sql = 'UPDATE ';
        $where = " WHERE id = ".$participant_id;
        break;

      case 'insert':
        $sql = 'INSERT INTO ';
        $where = '';

    }

    $sql .= self::$participants_table." SET date_updated = NOW(), ";

    $new_values = array();
    $new_value = false;
    $columns = array();

    $column_set = ( isset( $post['action'] ) && $post['action'] == 'signup' ? 'signup' : 'new' );

    // gather the submit values and add them to the query
		foreach ( self::get_column_atts( $column_set ) as $column_atts ) :

			// the validation object is only instantiated when this method is called
			// by a form submission
			if ( is_object( self::$validation_errors ) ) {

				self::$validation_errors->validate( ( isset( $post[ $column_atts->name ] ) ? $post[ $column_atts->name ] : '' ), $column_atts );

			}

			// we can process individual submit values here
			switch ( $column_atts->name ) {

				case 'id':
				case 'date_updated':
				case 'date_recorded':
					$new_value = false;
					break;
					
				case 'private_id':
					$new_value = empty( $post['private_id'] ) ? self::generate_pid() : $post['private_id'];
					break;

				default :
				
				if ( !isset( $post[ $column_atts->name ] ) && ! is_null( $post[ $column_atts->name ] ) && $action == 'update' ) {
					
					$new_value = false;
					
				} elseif ( empty( $post[ $column_atts->name ] ) && $action == 'insert' ) {
				
					$new_value = empty( $column_atts->default ) ? NULL : $column_atts->default;
					
				} elseif ( NULL === $post[ $column_atts->name ] ) {
				
					$new_value = false;
					
				} elseif ( is_array( $post[ $column_atts->name ] ) ) {
				
					$new_value = self::_prepare_array_mysql( $post[ $column_atts->name ] );
					
				} elseif ( $column_atts->form_element == 'date' ) {
					
					$date = self::parse_date( $post[ $column_atts->name ], $column_atts );
				
					$new_value = $date ? $date : NULL ;
					
        } elseif ( self::backend_user() && 'textarea' == $column_atts->form_element && $options['rich_text_editor'] ) {

          $new_value = stripslashes($post[ $column_atts->name ]);

				} else {
					
					$new_value = self::_prepare_string_mysql( $post[ $column_atts->name ] );
					
				}

			}
			
			// add the column and value to the sql
			if ( false !== $new_value ) {
			
				// insert a true NULL if the field is NULL
				if ( NULL !== $new_value ) $new_values[] = $new_value;
				$columns[] =  "`".$column_atts->name."` = ".( NULL === $new_value ? "NULL" : "%s" );
				
			}
		
		endforeach;// columns

		// if the validation object exists and there are errors, stop here
		if ( is_object( self::$validation_errors ) && self::$validation_errors->errors_exist() ) {

      // error_log( __METHOD__.' errors exist; returning');
      
      return false;

    }

		// add in the column names
		$sql .= implode( ', ',$columns);

		// add the WHERE clause
		$sql .= $where;
		
		if ( WP_DEBUG ) error_log( __METHOD__.' storing record sql='.$sql.' values:'.print_r( $new_values, true ) );

		$wpdb->query( $wpdb->prepare( $sql, $new_values ) );

		// is it a new record?
		if ( $action == 'insert' ) {

			// get the new record id for the return
			$participant_id = $wpdb->insert_id;

			// hang on to the id of the last record for a day
			set_transient( self::$last_record, $participant_id, (1*60*60*24) );

		}

		return $participant_id;
  
	}
  
  // get a participant record object with its id;
  // the default set is self::$id_base_number
  // returns associative array
  public function get_participant( $id = false ) {
		
		$id = false === $id ? self::$id_base_number :  $id;

    // test that the id exists
		if ( false === self::_id_exists( $id ) ) return false;
    
    global $wpdb;
		
		$sql = "SELECT * FROM ".self::$participants_table." WHERE `id` = '".$id."'";
		
		return $id == self::$id_base_number ? self::set_initial_record( $wpdb->get_row( $sql, ARRAY_A ) ) : $wpdb->get_row( $sql, ARRAY_A );
  
  }

  /**
   * gets a participant id by private ID
   *
   */
  public function get_participant_id( $pid ) {
		
		return self::_get_participant_id_by_term( 'private_id', $pid );

  }
	
	/**
	 * gets a participant record id by term
	 *
	 * given an identifier, returns the id of the record identified. If there is
	 * more than one record with the given term, returns an array of id numbers.
	 *
	 * @param string $term the column to match
	 * @param string $value the value to search for
	 *
	 * @return unknown returns integer if one match, array of integers if multiple matches, false if no match
	 */
	private function _get_participant_id_by_term( $term, $value ) {

    global $wpdb;
		
		$result = $wpdb->get_results( $wpdb->prepare( "SELECT `id` FROM ".self::$participants_table." WHERE `$term` = %s", $value ), ARRAY_A );
		
		if ( ! is_array( $result ) ) return false;
		
		$output = array();
		
		foreach ( $result as $row ) {
			
			$output[] = $row['id'];
			
		}
		
		return count( $output ) > 1 ? $output : current( $output );
		
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
        '1','2','3','4','5','6','7','8','9','0','A','B','C','D','E','F','G','H',
        'I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z' );

    for( $i = 0; $i < 5; $i++ ) {
      
      $pid .= $chr_source[array_rand( $chr_source )];

    }

    // if by chance we've generated a string that has been used before, generate another
    return self::_id_exists( $pid, 'private_id' ) ? self::generate_pid() : $pid;

  }

  /**
   * tests for existence of record in main db
   *
   * @param string $id the identifier to test
   * @param string $field the db field to test the $id value against
   */
  private function _id_exists( $id, $field = 'id' ) {

    global $wpdb;

    $id_exists = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM ".self::$participants_table." WHERE $field = %s", $id ) );

    return $id_exists < 1 ? false : true;

  }

  /**
   * tests for the presence of an email address in the records
   *
   * @param string $email the email address to search for
   * @return boolean true if email is found
   */
  public function email_exists( $email ) {

    return self::_id_exists( $email, 'email' );

  }
	
  /*
   * prepares an array for storage in the database
   *
   * @param array $array
   * @return string prepped array in serialized form
   */
  private function _prepare_array_mysql( $array ) {

    $prepped_array = array();

    foreach( $array as $key => $value ) {

      $prepped_array[ $key ] = self::_prepare_string_mysql( $value );

    }

    return serialize( $prepped_array );

  }

  /**
   * prepares a string for storage
   *
   * gets the string ready by getting rid of slashes and converting quotes and
   * other indesirables to HTML entities
   */
  private function _prepare_string_mysql( $string ) {

    return htmlspecialchars( stripslashes( $string ), ENT_QUOTES, 'utf-8' );

  }
	
	
	
	// unserialize if necessary
	public function unserialize_array( $string ) {
		
		// is_serialized is a WordPress utility function
		return is_serialized( $string ) ? unserialize( $string ) : $string ;
		
	}
	
	
	// prepare a field for display
	// displays an array as a series of comma-separated strings
	public function prepare_value( $string ) {
		
		$value = self::unserialize_array( $string );
		
		return is_array( $value ) ? implode( ', ', $value ) : stripslashes( $value ) ;
		
	}
  
  // this is used to prepare a new record page
  // takes the initial participant record and inserts default convenience values
  // taken from the current user and from the last record entered
  private function set_initial_record( $participant_record ) {
  
	 global $current_user;
	// get_currentuserinfo();
	 
	 $participant_record['by'] = $current_user->display_name;
	 $participant_record['when'] = date( get_option( 'date_format' ) );
	 $participant_record['private_id'] = self::generate_pid();
	 $participant_record['date_recorded'] = date( 'Y-m-d H:i:s' );
	 $participant_record['date_updated'] = date( 'Y-m-d H:i:s' );
	 
	 // get the id of the last record stored
	 $last_record = get_transient( self::$last_record );
	 //delete_transient( self::$last_record );
	 
	 //error_log( __METHOD__.' last record='.$last_record );,

	 // is this an initial entry? i.e. are there no persistent values to carry over
	 if ( $last_record == self::$id_base_number ) return $participant_record;
	 
	 $previous_record = self::get_participant( $last_record );
	 
	 $persistent_fields = self::get_persistent();
	 
	 foreach( $persistent_fields as $persistent_field ) {
	 
		if ( ! empty( $previous_record[ $persistent_field ] ) ) {
				
			$participant_record[ $persistent_field ] = $previous_record[ $persistent_field ];
			
		}
		
	 }
	 
	 return $participant_record;
	 
	}
	
	/**
	 * updates the default record with new field defaults
	 */
	public function update_default_record() {
		
		foreach ( self::get_column_atts( 'all' ) as $column ) {
			
			$post[ $column->name ] = empty( $column->default ) ? NULL : $column->default;
			
		}
		
		self::process_form( $post, 'update', self::$id_base_number );
		
	}
	
	// adds a blank field type record
	public function add_blank_field( $atts ) {
		
		global $wpdb;
		$wpdb->hide_errors();
		
		$defaults = wp_parse_args( $atts, array( 'form_element'=>'text-line' ) );

		$wpdb->insert( self::$fields_table, $defaults );

		if ( $wpdb->last_error ) {

      if ( WP_DEBUG ) error_log( __METHOD__.' failed to add row '.$atts['name'] );
		
      return false;

    }
		
		// if this column does not exist in the DB, add it
		if ( count( $wpdb->get_results( "SHOW COLUMNS FROM `".self::$participants_table."` LIKE '".$defaults['name']."'", ARRAY_A ) ) < 1 ) {
		
		if ( false === ( self::_add_db_column( $defaults ) ) ) {
			
			if ( WP_DEBUG ) error_log( __METHOD__.' failed to add column:'.print_r( $defaults, true ) );

			return false;
			
		}
		
	}
	
	}
	
	private function _add_db_column( $atts ) {
		
		global $wpdb;
		
		$datatype = self::set_datatype( $atts['form_element'] );
		
		$sql = 'ALTER TABLE `'.self::$participants_table.'` ADD `'.$atts['name'].'` '.$datatype.' NULL';
		
		return $wpdb->query( $sql );
		
	}
					
	// returns a MYSQL datatype appropriate to the form element type
	public function set_datatype( $element ) {
		
		switch ( $element ) {

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
			
		$options = get_option( self::$participants_db_options );

		// only process POST arrays from this plugin's pages
		if ( ! isset( $_POST['source'] ) or $_POST['source'] != self::PLUGIN_NAME or ! isset( $_POST['action'] ) ) return NULL;

    // error_log( __METHOD__.' post:'.print_r( $_POST, true ) );

		// instantiate the validation object
		self::$validation_errors = new FormValidation();

		switch ( $_POST['action'] ) :

			case 'update':
			case 'insert':
		
				$participant_id = isset( $_POST['id'] ) ? $_POST['id'] : ( isset( $_GET['id'] ) ? $_GET['id'] : self::$id_base_number );
			
				$participant_id = self::process_form( $_POST, $_POST['action'], $participant_id );
			
				if ( false === $participant_id ) {
			
					// we have errors; go back to form and show errors
					return;
			
				}
			
				// if we are submitting from the frontend, we're done
				if ( ! is_admin() ) {
					
					self::$validation_errors->add_error( '', $options['record_updated_message'] );
					
					if ( $options['send_record_update_notify_email'] ) {
						
						$sent = wp_mail( 
															$options['email_signup_notify_addresses'], 
															$options['record_update_email_subject'], 
															self::proc_tags( $options['record_update_email_body'], $participant_id ),
															self::$email_headers
														);
						
					}
					
					return;
					
				}
			
				// redirect according to which submit button was used
				switch ( $_POST['submit'] ) {
			
					case 'Apply' :
						wp_redirect( get_admin_url().'admin.php?page='.self::PLUGIN_NAME.'-edit_participant&id='.$participant_id );
						break;
			
					case 'Next' :
						wp_redirect( get_admin_url().'admin.php?page='.self::PLUGIN_NAME.'-edit_participant' );
						break;
			
					case 'Submit' :
						wp_redirect( get_admin_url().'admin.php?page='.self::PLUGIN_NAME.'-list_participants&id='.$participant_id);
			
					default :
			
				}
				break;
			
		 case 'output CSV':
				
			 $header_row = array();
			 $data = array();	
			 $filename = isset( $_POST['filename'] ) ? $_POST['filename'] : '';
		
		 	 switch ( $_POST['CSV_type'] ) :
		
				 // create a blank data array
				 case 'blank':
		
					// add the header row
					foreach ( self::get_column_atts( 'CSV' ) as $column ) $header_row[] = $column->name;
					$data[] = $header_row;
		
					$i = 2;// number of blank rows to create
					
					while ( $i > 0 ) {
						$data[] = array_fill_keys( $header_row, '' );
						$i--;
					}
					break;
		
				case 'participant list':
		
					global $wpdb;
		
					$import_columns = '';
		
					foreach ( self::get_column_atts( 'CSV' ) as $column ) {
		
						$import_columns .= sprintf( '`%s`,',$column->name );
						$header_row[] = $column->name;
		
					}
		
					$data['header'] = $header_row;
		
					$query = str_replace( '*', ' '.trim( $import_columns, ',' ).' ', rawurldecode( $_POST['query'] ) );
		
					$data += self::_prepare_CSV_rows( $wpdb->get_results( $query, ARRAY_A ) );
					
					break;
				 
			endswitch;// CSV type
			
			if ( ! empty( $filename ) ) {
				
			 // create a file pointer connected to the output stream
				$output = fopen('php://output', 'w');
				
				header('Content-type: application/csv'); // Content-Type: text/csv; charset=utf-8
				header('Content-Disposition: attachment; filename="'.$filename.'"');
				
				// output the data lines
				foreach( $data as $line ) {
					fputcsv( $output, $line, ',', '"' );
				}
				
				fclose( $output );
				
				// we must terminate the script to prevent additional output being added to the CSV file
				exit;
				
			}
			
			return $data;
			
		 case 'signup' :
		 
			// instantiate the validation object if it doesn't exist
			if ( ! is_object( Participants_Db::$validation_errors ) ) Participants_Db::$validation_errors = new FormValidation();
	
			/* if someone signs up with an email that already exists, we update that
			 * record rather than let them create a new record. This gives us a method
			 * for dealing with people who have lost their access link, they just sign
			 * up again with the same email, and their access link will be emailed to
			 * them. This is handled by the Participants_Db::process_form method.
			 */
	
			$_POST['private_id'] = Participants_Db::generate_pid();
			 
		 	// only go to the thanks page if we have no errors
		 	$_POST['id'] = Participants_Db::process_form( $_POST, 'insert' );
		 
		 	if ( false !== $_POST['id']  ) {
				
				$conj = false !== strpos( $_POST['thanks_page'], '?' ) ? '&' : '?' ;
					
				wp_redirect( $_POST['thanks_page'].$conj.'id='.$_POST['id'] );
				
				exit;
				
			}
			
			return;
			 
		endswitch; // $_POST['action']
	 
  }
  
  // returns boolean to question of whether the user is authorized to see / edit 
  // administrative fields
  public function backend_user() {

	 // contributor and above
	 return current_user_can( 'edit_posts' ) ? true : false;
	 
  }
  
  /**
	 * gets an array of readonly fields
	 *
	 * @return array
	 */
  public function get_readonly() {
		
		$fields = array();
  
		foreach( self::get_column_atts( 'readonly' ) as $column ) 
			
			$fields[] = $column->name;
			
		return $fields;
		
  }
  
	// returns the title attribute of a column
	public function column_title( $column ) {
		
		$field = self::get_field_atts( $column, 'title' );
		
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
	public function set_selected( $element_value, $new_value, $attribute = 'selected', $state = true ) {
		
		$pattern = ' %1$s="%1$s" ';

		if (
				 ( is_array( $element_value ) && ( $state === in_array( $new_value, $element_value ) ) )
				 ||
				 $element_value == $new_value
		   )
		{
			
			return sprintf( $pattern, $attribute );
			
		} else return '';
		
	}
	
	/**
	 * displays an edit field for a field attribute
	 * this is used by the manage_fields script
	 * 
	 * @param string $field name of the field
	 * @return array contains parameters to use in instantiating the FormElement object
	 */
	 public function get_edit_field_type( $field ) {
		
		switch ( $field ) :
			
			// small integer fields
			case 'id':
				return array( 'type'=>'hidden' );
				
			case 'order':
				return array( 'type'=>'drag-sort' );
			
			case 'admin_column':
			case 'display_column':
				return array( 'type'=>'text', 'size'=>'2' );
			
			// all the booleans
			case 'persistent':
			case 'sortable':
			case 'CSV':
			case 'signup':
				return array( 'type'=>'checkbox', 'options'=> array( 1, 0 ) );
			
			// field names can't be edited
			case 'name':
				return array( 'type'=>'text','attributes'=>array('readonly'=>'readonly') );
			
			// all the text-area fields
			case 'values':
			case 'help_text':
				return array( 'type'=>'textarea' );
			
			// drop-down fields
			case 'form_element':
				// populate the dropdown with the available field types from the FormElement class
				return array( 'type' => 'dropdown', 'options'=>self::$element_types );
				
			case 'validation':
				return array( 'type' => 'dropdown-other', 'options'=>array('No'=>'no','Yes'=>'yes'), 'attributes'=> array( 'other'=>'regex' ) );
			
			case 'group':
				// these options are defined on the "settings" page
				return array( 'type' => 'dropdown', 'options'=> self::get_groups('name', 'internal') );
				
			case 'link':
				
			case 'title':
			default:
				return array( 'type'=>'text' );
				
		endswitch;
		
	}
	
	
	/**
	 * prepares a set of rows for CSV output
	 *
	 * @param array $raw_array the raw array output from the query
	 *
	 * @return array of record arrays
	 */
	private function _prepare_CSV_rows( $raw_array ) {
		
		$output = array();
		
		foreach( $raw_array as $key => $value ) {
			
			$output[ $key ] = self::_prepare_CSV_row( $value );
			
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
	private function _prepare_CSV_row( $raw_array ) {
		
		$output = array();
		
		// get the column attributes
		$columns = self::get_column_atts('CSV');
		
		// iterate through the object as we iterate through the array
		$column = current( $columns );
		
		foreach( $raw_array as $key => $value ) {
				
			// process any other value types
			switch ( $column->form_element ) {
				
				case 'date':
				
					if ( ! empty( $value ) && is_numeric( $value ) ) {
				
							$value = date( get_option( 'date_format' ), $value );
							break;
						
					}
					
				default:
				
					// flatten arrays
					if ( is_serialized( $value ) )
		
						$value = implode( ', ', unserialize( $value ) );
				
			}

			// decode HTML entities
			$output[ $key ] = html_entity_decode( $value, ENT_QUOTES, "utf-8" );
			
			$column = next( $columns );
			
		}
		
		return $output;
		
	}
	
	/**
	 * inserts a series of records from a csv file
	 *
	 * @param string $src_file the file to parse
	 *
	 * @return mixed returns integer for number of records successfully added, or string error encountered
	 */
	public function insert_from_csv( $src_file ) {
	
		global $wpdb;
		$wpdb->hide_errors();
		
		$errorMsg = '';
	
		if( empty( $src_file ) || ! is_file( $src_file ) ) {

      /* translators: the %s will be the name of the file */
			return sprintf( __('Input file does not exist or path is incorrect.<br />Attempted to load: %s', self::PLUGIN_NAME), basename($src_file) );
	
		}
		
		$CSV = new parseCSV();
		
		$CSV->enclosure = self::_detect_enclosure( $src_file );
	
		$CSV->auto( $src_file );
	
		// build the column names
		if ( is_array( $CSV->titles ) ) {
			
			$column_names = $CSV->titles;
			
			// remove enclosure characters
			array_walk( $column_names, array( __CLASS__, '_enclosure_trim' ), $CSV->enclosure );
			
		} else { 

			foreach ( self::get_column_atts() as $column ) {
			
				if ( $column->CSV ) $column_names[] = $column->name;
			
			}
			
		}
	
	
		$line_num = 1;
		
		foreach ( $CSV->data as $csv_line ) {
	
			// error_log( __METHOD__.' csv line= '.print_r( $csv_line, true ) );
			
			$values = array();
			
			foreach( $csv_line as $value ) {
				$values[] = $wpdb->escape( trim( $value, $CSV->enclosure ) );
			}
	
			if ( count( $values ) != count( $column_names) ) {
	
				return sprintf( 
											 __('The number of items in line %s is incorrect.<br />There are %s and there should be %s.', self::PLUGIN_NAME ),
											 $line_num, 
											 count( $values ), 
											 count( $column_names ) 
											 );
	
			}
			
			if ( ! $post = array_combine( $column_names, $values ) ) return __('Number of values does not match number of columns', self::PLUGIN_NAME) ;// suspenders and a belt here
			
			// error_log( __METHOD__.' post array='.print_r( $post, true));
			
			// we've got our record data, now add it to the database
			self::process_form( $post, 'insert' );
			
			$line_num++;
			
		}
		return $line_num - 1;
	}
	
	/**
	 * trims enclosure characters from the csv field
	 * @access public because PHP callback uses it
	 */
	public function _enclosure_trim( &$value, $key, $enclosure = "'" ) {

    $value = trim( $value, $enclosure );

	}
	
	/**
	 * detect an enclosure character
	 *
	 * there's no way to do this 100%, so we will look and fall back to a
	 * reasonable assumption if we don't see a clear choice: simply whichever
	 * of the two most common enclosure characters is more numerous is returned
	 *
	 * @param string $csv_file path to a csv file to read and analyze
	 * return string the best guess enclosure character
	 */
	private function _detect_enclosure( $csv_file ) {
		
		$csv_text = file_get_contents( $csv_file );
		
		$single_quotes = substr_count( $csv_text, "'" );
		$double_quotes = substr_count( $csv_text, '"' );
		
		return $single_quotes >= $double_quotes ? "'" : '"';
		
	}
	
	/**
	 * outputs a link in specified format
	 * 
	 * @param string $link the URI
	 * @param string $title the clickable text (optional)
	 * @param string $template the format of the link (optional)
	 * @param array  $get an array of name=>value pairs to include in the get string
	 *
	 * @return string HTML or HTML-escaped string (if it's not a link)
	 */
	public function make_link( $link, $title = '', $template = false, $get = false ) {

    // URL's, emails and the like are case-insensitive
    $lc_link = strtolower( $link );
    
    // if it's not really a link don't wrap it in anchor tag
    if ( ( 0 !== stripos( $lc_link, 'http' ) && false === $get ) && ! filter_var( $lc_link, FILTER_VALIDATE_EMAIL ) ) return esc_html( $link );

    if ( false !== $get && is_array( $get ) ) {

        $link .= false !== strpos( $link, '?' ) ? '&' : '?';

        foreach( $get as $name => $value ) {

          $link .= rawurlencode($name).'='.rawurlencode($value).'&';

        }

        $link = rtrim( $link, '&' );

    }

    if ( filter_var( $lc_link, FILTER_VALIDATE_EMAIL ) && 0 !== strpos( $lc_link, 'mailto:' ) ) {
    
      $title = $link;
      $link = 'mailto:'.$lc_link;

    }
		
		// default template for links
		$linktemplate = $template === false ? '<a href="%1$s" target="_blank" >%2$s</a>' : $template;
		
		$title = empty( $title ) ? str_replace( array('http://', 'https://' ), '', $link ) : $title;
		
		//construct the link
		return sprintf( $linktemplate, $link, $title );
		
	}
	
	
	/**
	 * handles file uploads
	 *
	 * @param array $upload_file the $_FILES array element corresponding to one file
	 *
	 * return string the path to the uploaded file or false if error
	 */ 
	private function _handle_file_upload( $name, $file ) {
		
		$options = get_option( self::$participants_db_options );
		
		if ( ! is_dir( ABSPATH.$options['image_upload_location'] ) ) {
		
			if ( false === self::_make_uploads_dir( $options['image_upload_location'] ) ) return false;
			
		}
		
		if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
			
			self::$validation_errors->add_error( $name, __('There is something wrong with the file you tried to upload. Try another.', self::PLUGIN_NAME ) );
			
			return false;
			
		}
		
		$fileinfo = getimagesize( $file['tmp_name'] );
		
		// check the type of file to make sure it is an image file
		if ( ! in_array( $fileinfo[2], array( IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WBMP ) ) ) {
			
			self::$validation_errors->add_error( $name, __('You may only upload image files like JPEGs, GIFs or PNGs.', self::PLUGIN_NAME ) );
			
			return false;
			
		}
		
		// make sure the filename is OK:
		$new_filename = preg_replace( 
                     array("/\s+/", "/[^-\.\w]+/"), 
                     array("_", ""), 
                     trim( $file['name'] ) ); 
		
		if ( $file['size'] > $options[ 'image_upload_limit' ] * 1024 ) {
			
			self::$validation_errors->add_error( $name, sprintf( __('The image you tried to upload is too large. The file must be smaller than %sK.', self::PLUGIN_NAME ), $options[ 'image_upload_limit' ] ) );
			
			return false;
			
		}
		
    if ( false === move_uploaded_file( $file['tmp_name'], ABSPATH.$options['image_upload_location'].$new_filename ) ) {
			
			self::$validation_errors->add_error( $name, __('The file could not be saved.', self::PLUGIN_NAME ) );
			
			return false;
			
		}
		
		/*
		 * as of 1.3.2 we save the image as filename only; the image is retrieved from 
		 * the directory defined in the plugin setting using the self::get_image function
		 */
		
		else return /*get_bloginfo('wpurl').DIRECTORY_SEPARATOR.$options['image_upload_location'].*/$new_filename;
	
	}
	
	/**
	 * attempt to create the uploads directory
	 *
	 * sets an error if it fails
	 */
	private function _make_uploads_dir( $dir ) {
		
		$savedmask = umask(0);
		
		if ( false === mkdir( ABSPATH.$dir, 0755, true ) ) {
			
			if ( is_object( self::$validation_errors ) ) self::$validation_errors->add_error( $name, sprintf( __('The uploads directory (%s) could not be created.', self::PLUGIN_NAME ), $dir )  );
			
			umask( $savedmask ); 
			
			return false;
			
		}
			
		umask( $savedmask ); 
		
		return true;
		
	}
	
	/**
	 * builds a record edit link
	 *
	 * @param string $PID private id value
	 * @return string private record URI
	 */
	public function get_record_link( $PID ) {
		
		$options = get_option( self::$participants_db_options );
		
		// if the setting is not yet set, don't try to build a link
		if ( ! isset( $options['registration_page'] ) || empty( $options['registration_page'] ) ) return '';
		
		// if the setting was made in previous versions and is a slug, convert it to a post ID
		if ( ! is_numeric( $options['registration_page'] ) ) {
			
			$options['registration_page'] = self::_get_ID_by_slug( $options['registration_page'] );
			
			update_option( self::$participants_db_options, $options );
			
		}
		
		$page_link = get_page_link( $options['registration_page'] );
		
		$delimiter = false !== strpos( $page_link, '?' ) ? '&' : '?';
		
		return $page_link.$delimiter.'pid='.$PID;
		
	}
	
	/**
	 * gets the ID of a page given it's slug
	 *
	 * this is to provide backwards-compatibility with previous versions that used a page-slug to point to the [pdb_record] page.
	 */
	private function _get_ID_by_slug( $page_slug ) {
		
    $page = get_page_by_path( $page_slug );
		
    return is_object( $page ) ? $page->ID : false;
		
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
	public function proc_tags( $text, $participant_id, $mode = 'frontend'  ) {
		
		$participant = self::get_participant( $participant_id );

		$tags = array();
		$values = array();

		foreach( self::get_column_atts( $mode ) as $column ) {

			$tags[] = '['.$column->name.']';
			
			$values[] = self::prep_field_for_display( $participant[$column->name], $column->form_element );

		}
		
		$options = get_option( self::$participants_db_options );

		// add the "record_link" tag
		$tags[] = '[record_link]';
		$values[] = $options['registration_page'];
		
		// add the date tag
		$tags[] = '[date]';
		$values[] = date( get_option( 'date_format' ), self::parse_date() );
				

		$placeholders = array();
		
		for ( $i = 1; $i <= count( $tags ); $i++ ) {

			$placeholders[] = '%'.$i.'$s';

		}

		// replace the tags with variables
		$pattern = str_replace( $tags, $placeholders, $text );
		
		// replace the variables with strings
		return vsprintf( $pattern, $values );

	}
	
	/**
	 * makes a title legal to use in anchor tag
	 */
	public function make_anchor( $title ) {
		
		return str_replace( ' ','', preg_replace( '#^[0-9]*#','',strtolower( $title ) ) );
		
	}
	
	/**
	 * parses a date string into UNIX timestamp
	 *
	 * @param string $string      the string to parse; if not given, defaults to now
	 * @param object $column_atts the column object; used to identify the field for
	 *                            user feedback
	 * @return int  UNIX timestamp or false if parse fails
	 */
	public function parse_date( $string = false, $column = '' ) {

          // return the now() timestamp
          if ( false === $string ) return time();
					
					// it's already a timestamp; or something that looks like a timestamp but wouldn't parse anyway
					if ( preg_match( '#^[0-9-]+$#', $string ) > 0 ) return $string;
		
					if ( self::$plugin_options['strict_dates'] ) {

            $date = date_create_from_format( get_option( 'date_format' ), $string );

            if ( is_array( date_get_last_errors() ) && ! empty( $string ) ) {

              $errors = date_get_last_errors();

              if ( $errors['warning_count'] > 0 || $errors['error_count'] > 0 ) {

                $date = false;

                if ( is_object( self::$validation_errors ) and is_object( $column ) ) {

                  self::$validation_errors->add_error( $column->name, sprintf( __('The date for "%s" was invalid. Please input the date with the exact format shown', self::PLUGIN_NAME ), $column->title ) );

                }

              }

            }

            // if we have a valid date, convert to timestamp
            if ( $date ) $date = date_format( $date, 'U' );

          } else {

            $date = strtotime( $string );

          }
					
					return $date;
	}
	
	/**
	 * translates the current date format option string to a jQuery UI date format string
	 *
	 */
	function get_jqueryUI_date_format( $PHP_date_format = '' ) {
		
			$dateString = empty($PHP_date_format) ? get_option( 'date_format' ) : $PHP_date_format;
		
			$pattern = array(
				
				//day
				'd',		//day of the month
				'j',		//3 letter name of the day
				'l',		//full name of the day
				'z',		//day of the year
				
				//month
				'F',		//Month name full
				'M',		//Month name short
				'n',		//numeric month no leading zeros
				'm',		//numeric month leading zeros
				
				//year
				'Y', 		//full numeric year
				'y'		//numeric year: 2 digit
			);
			$replace = array(
				'dd','d','DD','o',
				'MM','M','m','mm',
				'yy','y'
			);
			foreach($pattern as &$p)
			{
				$p = '/'.$p.'/';
			}
			return preg_replace($pattern,$replace,$dateString);
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
        <h4><?php echo self::$plugin_title, ' ', self::$plugin_version ?><br /><?php _e('WordPress Plugin', self::PLUGIN_NAME )?></h4>
        <p><em><?php _e('Helping organizations manage their volunteers, members and participants.', self::PLUGIN_NAME)?></em></p>
      </div>
      <div class="section">
        <h4><a href="http://xnau.com"><img src="<?php bloginfo( 'wpurl' ) ?>/wp-content/plugins/<?php echo self::PLUGIN_NAME ?>/ui/xnau-square-60.png" style="float:left;width:50px;height:auto;margin-right:10px" /></a><?php _e('Developed by', self::PLUGIN_NAME)?><br /><a href="http://xnau.com">xn&lowast;au webdesign</a></h4>
        <p><?php _e('Suggestions or crticisms of this plugin? I&#39;d love to hear them: email ', self::PLUGIN_NAME)?><a href="mailto:support@xnau.com">support@xnau.com.</a>
      </div>
      <div class="section">
        <p><?php printf( __('Please consider contributing to the continued support and development of this software by visiting %1$sthis plugin&#39;s page,%3$s giving the plugin a %2$srating%3$s or review, or dropping something in the %1$stip jar.%3$s Thanks!', self::PLUGIN_NAME), '<a href="http://xnau.com/wordpress-plugins/participants-database">','<a href="http://wordpress.org/extend/plugins/participants-database/">', '</a>')?></p>
      </div>
    </div>
    <?php
  }
	
	/**
	 * parses the text header to extract plugin info
	 */
	private function _get_plugin_data( $key = 'Name' ) {
		
		if ( ! function_exists( 'get_plugin_data' ) )
			include ABSPATH.'/wp-admin/includes/plugin.php';
		
		$plugin_data = get_plugin_data( __FILE__ );
		
		return $plugin_data[ $key ];
		
	}
	
	
	
} // class



/**
 * performs the class autoload
 *
 * @param string $class the name of the class to be loaded
 */
function PDb_class_loader( $class ) {

	$class_file = plugin_dir_path( __FILE__ ).'classes/' . $class . '.class.php';

	if ( is_file( $class_file ) ) {
	
	//error_log( __FUNCTION__. ' class loaded: '.$class_file );

		require_once $class_file;

	}

}
?>
