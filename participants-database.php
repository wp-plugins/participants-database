<?php  
/*  
Plugin Name: Participants Database 
Plugin URI: http://xnau.com/wordpress-plugins/participants-database
Description: Plugin for managing a database of participants, members or volunteers
Author: Roland Barker
Version: 1.0
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
	public static $participants_db_db_version;

	// plugin options name
	public static $participants_db_options;
	
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

	// holds the form validation errors
	public static $validation_errors;
	
	// record ID numbers begin counting up from this number
	public static $id_base_number = 1325;
	
	// name of the transient record used to hold the last record
	public static $last_record = 'pdb_last_record';
	
	// these columns are not manually edited
	public static $internal_columns;
	
	public function initialize() {

		// register the class autoloading
		spl_autoload_extensions( '.php' );
		spl_autoload_register( 'PDb_class_loader' );

		// set the table names
		global $wpdb;
		self::$participants_table = $wpdb->prefix . str_replace( '-', '_', self::PLUGIN_NAME );
		self::$fields_table = self::$participants_table . '_fields';
		self::$groups_table = self::$participants_table . '_groups';

		// change this when there is a change to the databases to trigger an update
		self::$participants_db_db_version = '0.1';

		// define some locations
		self::$participants_db_options = self::PLUGIN_NAME.'_options';
		self::$plugin_page = self::PLUGIN_NAME;
		self::$plugin_url = WP_PLUGIN_URL.'/'.self::PLUGIN_NAME;
		self::$plugin_path = dirname(__FILE__);
		// this is relative to the plugin
		self::$uploads_path = 'wp-content/plugins/'.self::PLUGIN_NAME.'/uploads/';
		
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
																 'image-upload'
                                 );
		

    // install/deactivate and uninstall methods are handled by the PDB_Init class
    register_activation_hook( __FILE__, array( 'PDb_Init', 'on_activate' ) );
    register_deactivation_hook( __FILE__, array( 'PDb_Init', 'on_deactivate' ) );
    register_uninstall_hook( __FILE__, array( 'PDb_Init', 'on_uninstall' ) );


    // define the list of read only columns
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

		// set the email content type to HTML
		add_filter('wp_mail_content_type',create_function('', 'return "text/html";'));

		// set the WP hooks to finish setting up the plugin
		add_action( 'init', array( __CLASS__, 'init') );
		add_action( 'admin_menu', array( __CLASS__, 'plugin_menu') );
		add_action( 'admin_init', array( __CLASS__, 'admin_init') );

		// define our shortcodes
		add_shortcode( 'pdb_record', array( __CLASS__, 'frontend_edit') );
		add_shortcode( 'pdb_signup', array( __CLASS__, 'print_signup_form' ) );
	
		if ($wpdb->get_var('show tables like "'.Participants_Db::$participants_table.'"') == Participants_Db::$participants_table) :
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
		
		// set the db version as a WP option
		if ( is_object( self::$plugin_settings ) ) self::$plugin_settings->update_option('db_version', self::$participants_db_db_version);
		
	}
	
	public function init() {

    load_plugin_textdomain( self::PLUGIN_NAME, false, basename(dirname(__FILE__) ) );

    self::$plugin_title = __('Participants Database', self::PLUGIN_NAME );

		// this processes form submits before any output so that redirects can be used
		self::process_page_request();

	}
	
	public function plugin_menu() {
		
		// intialize the plugin settings
		// we do this here because we need the object for the plugin menus
		self::$plugin_settings = new PDb_Settings( self::$participants_db_options );

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
			'edit_pages',
			self::$plugin_page.'-list_participants', 
			array( __CLASS__, 'include_admin_file' ) 
			);
		
		$addpage = add_submenu_page(
			self::PLUGIN_NAME,  
			__('Add Participant', self::PLUGIN_NAME ), 
			__('Add Participant', self::PLUGIN_NAME ),
			'edit_pages', 
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
			'edit_pages', 
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
    add_action( 'admin_print_styles-' . $editpage , array( __CLASS__, 'admin_style' ) );

    // add a global stylesheet to use while the plugin is active
    add_action( 'admin_print_styles', array( __CLASS__, 'global_admin_style' ) );

		// add any js scripts needed for the admin pages
    add_action( 'admin_print_scripts-' . $managepage, array( __CLASS__, 'manage_fields_scripts' ));
		
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

	// callback for plugin admin subpages
	// grabs the name from the request and includes the file to display the page
	public function include_admin_file() {
		
		$parts = explode( '-', $_GET['page'] );
		
		$file = array_pop( $parts );

		// we'll need this in the included file
		global $wpdb;
		
		include $file.'.php';
		
	}
	
	// plays out a record edit screeen for a participant
	// it requires the use of a link with an id number
	// low security on entry becuase we don't require that they establish an
	// account, but we do use an encrypted account number
	public function frontend_edit( $atts ) {

		// at present, there are no attributes
		$vars = shortcode_atts( array(
		), $atts );
		
		if ( isset( $_GET['pid'] ) ) {

      $participant_id = self::get_participant_id( $_GET['pid'] );

      if ( $participant_id ) {
        ?>
        <style type="text/css"><?php include 'participants-db.css' ?></style>
        <?php

        include 'edit_participant.php';
			
      }
    }
		
	}
	
	/**
	 * prints a signup form
	 */
	public function print_signup_form( $params ) {

		// the form is generated by the Signup object
		new Signup( $params );
		
	}
		
	
	// get all the attributes of a field by it's name
	// an attribute or comma-separated list of attributes can be specified
	// if not, a default list of attributes is retrieved
	//
	// returns data object
  public function get_field_atts( $field, $atts = '*' ) {

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
  public function get_groups( $column = '*' ) {

		global $wpdb;

		$sql = 'SELECT '.$column.' FROM '.self::$groups_table.' ORDER BY `order`,`name` ASC';
		
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
		
	 return self::get_subset( 'sortable' );
	 
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
	
	// gets the column and column order for participant listing
	// returns a sorted array, omitting any non-displyed columns
	public function get_list_display_columns() {
	
    global $wpdb;

    $sql = "
      SELECT `name`,`column`
      FROM ".self::$fields_table."
      WHERE `column` > 0";
		
	 $columns = $wpdb->get_results( $sql, ARRAY_A );
	 
	 $column_set = array();
	 foreach ( $columns as $column ) {
	 
		$column_set[ $column[ 'column' ] ] = $column[ 'name' ];
		
	 }
	 
	 if ( self::$plugin_settings->get_option( 'show_pid' ) ) $column_set[0] = 'private_id';
	 
	 ksort( $column_set );
	 
	 //error_log( __METHOD__.' columns='.print_r( $column_set, true));
	 
	 return $column_set;
	 
	}
  
  // gets an associative array of all column names and types
  public function get_columns() {
  
		global $wpdb;

		$sql = "SHOW COLUMNS FROM ".self::$participants_table;

		return $wpdb->get_results($sql, ARRAY_A);
	 
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

			case 'import':

				$where = 'WHERE v.import = 1 ';
				break;

			case 'all':

				$where = '';
				break;

			case 'frontend':

				$where = 'WHERE g.display = 1 ';
				break;

			case 'new':
			case 'backend':
			default:

				$where = 'WHERE v.name NOT IN ( "id" ) ';

		}

		$sql = 'SELECT v.*, g.order FROM '.self::$fields_table.' v INNER JOIN '.self::$groups_table.' g ON v.group = g.name '.$where.' ORDER BY g.order, v.order';
		
		return $wpdb->get_results( $sql, OBJECT_K );
		
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
			
			// error_log( __METHOD__.' default record: '.$action );

		}
		
		if ( ! empty( $_FILES ) ) {
			
			foreach ( $_FILES as $fieldname => $attributes ) {
				
				if ( UPLOAD_ERR_NO_FILE == $attributes['error'] ) continue;
				
				// place the path to the file in the field value
				$filepath = self::_handle_file_upload( $fieldname, $attributes );
				
				if ( false !== $filepath ) $post[ $fieldname ] = $filepath;
				
			}
			
		}
		
		$options = get_option( self::$plugin_settings );

		// check for an existing record with same email so we can at least avoid
		// inserting a duplicate email address into the database
		if ( $options['unique_email'] && isset( $post['email'] ) && self::email_exists( $post['email'] ) ) {

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

    // gather the submit values and add them to the query
		foreach ( self::get_column_atts() as $column_atts ) :

			// skip any fields not included in the post array and have no default value
			if ( ! isset( $post[ $column_atts->name ] ) && NULL === $column_atts->default ) continue;

			// the validation object is only instantiated when this method is called
			// by a form submission
			if ( is_object( self::$validation_errors ) && isset( $post[ $column_atts->name ] ) ) {

				self::$validation_errors->validate( $post[ $column_atts->name ], $column_atts );

			}

			// we can process individual submit values here
			switch ( $column_atts->name ) {

				case 'id':
				case 'date_updated':
				case 'date_recorded':
					$new_value = false;
					break;

				default :
				if ( empty( $post[ $column_atts->name ] ) ) {
				
					$new_value = $column_atts->default;
					
				} else {
				
					$new_value = is_array( $post[ $column_atts->name ] ) ? serialize( $post[ $column_atts->name ] ) : $post[ $column_atts->name ];
					
				}

			}
			
			// add the column and value to the sql
			if ( false !== $new_value && ! empty( $new_value ) ) {
			
				$new_values[] = $new_value;
				$columns[] =  "`".$column_atts->name."` = %s";
				
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
	 
	 // get the id of the last record stored
	 $last_record = get_transient( self::$last_record );
	 //delete_transient( self::$last_record );
	 
	 //error_log( __METHOD__.' last record='.$last_record );

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
	
	// adds a blank field type record
	public function add_blank_field( $atts ) {
		
		global $wpdb;
		
		$defaults = wp_parse_args( $atts, array( 'form_element'=>'text-line' ) );

		$wpdb->insert( self::$fields_table, $defaults );
		
		// if this column does not exist in the DB, add it
		if ( count( $wpdb->get_results( "SHOW COLUMNS FROM `".self::$participants_table."` LIKE '".$defaults['name']."'", ARRAY_A ) ) < 1 ) {
		
		if ( false === ( self::_add_db_column( $defaults ) ) ) {
			
			error_log( __METHOD__.' failed to add column:'.print_r( $defaults, true ) );
			
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
            $datatype = 'TEXT';
            break;

            case 'date':
            $datatype = 'DATE';
            break;

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
    if ( ! is_admin() ) return;

		// redirect according to which submit button was used
		switch ( $_POST['submit'] ) {

			case 'Apply' :
				wp_redirect( get_admin_url().'admin.php?page='.self::PLUGIN_NAME.'-edit_participant&id='.$participant_id );
				break;

			case 'Next' :
				wp_redirect( get_admin_url().'admin.php?page='.self::PLUGIN_NAME.'-edit_participant' );
				break;

			case 'Submit' :
				wp_redirect( get_admin_url().'/admin.php?page='.self::PLUGIN_NAME.'-list_participants&id='.$participant_id);

			default :

		}
		break;
		
	 case 'output CSV':
			
		 $header_row = array();
		 $data = array();	

		 switch ( $_POST['CSV_type'] ) :

			 // create a blank data array
			 case 'blank':

				// add the header row
				foreach ( self::get_column_atts( 'import' ) as $column ) $header_row[] = $column->name;
				$data[] = $header_row;

				$i = 2;// number of blank rows to create
				
				while ( $i > 0 ) {
					$data[] = array_fill_keys( $fields, '' );
					$i--;
				}
				break;

			case 'participant list':
			 
				// build the import header row
				foreach ( self::get_columns() as $column ) {
					$header_row[] = $column['Field'];
				}
				$data['header'] = $header_row;

				global $wpdb;

				$data += self::_prepare_CSV_rows( $wpdb->get_results( rawurldecode( $_POST['query'] ), ARRAY_A ) );
				
				break;
			 
		 endswitch;// CSV type
		 
		// error_log( __METHOD__.' filename '.$filename.' data '.print_r( $data, true ).' fields '.print_r( $fields, true ) );
		 

		$filename = isset( $_POST['filename'] ) ? $_POST['filename'] : '';
		
		if ( ! empty( $filename ) ) {
			
		 // create a file pointer connected to the output stream
			$output = fopen('php://output', 'w');
			
			header('Content-type: application/csv'); // Content-Type: text/csv; charset=utf-8
			header('Content-Disposition: attachment; filename="'.$filename.'"');
			
			// output the data lines
			foreach( $data as $line ) {
				fputcsv( $output, $line );
			}
			
			fclose( $output );
			
			// we must terminate the script to prevent additional output being added to the CSV file
			exit;
			
		}
		
		return $data;
		 
	 endswitch; // $_POST['action']
	 
  }
  
  // returns boolean to question of whether the user is authorized to see / edit 
  // administrative fields
  public function backend_user() {

	 // contributor and above
	 return current_user_can( 'edit_posts' ) ? true : false;
	 
  }
  
  // sets any read-only fields
  public function get_readonly( $column ) {
  
	 $readonly = false;
	 
	 if (
		  in_array( $column, self::$internal_columns )
		  or 
		  ( ! self::backend_user() && in_array( $column, array( ) ) ) // place any read-only fields for the frontend user in this array
		  ) {
		  
		$readonly = true;// ' readonly="readonly" ';
		
	 }
		
	 return $readonly;
		
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
			
			case 'column':
				return array( 'type'=>'text', 'size'=>'2' );
			
			// all the booleans
			case 'persistent':
			case 'sortable':
			case 'import':
			case 'signup':
				return array( 'type'=>'checkbox', 'options'=> array( 1, 0 ) );
			
			// all the text fields
			case 'name':
				return array( 'type'=>'hidden' );
				
			case 'title':
			case 'default':
				return array( 'type'=>'text' );
			
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
				return array( 'type' => 'dropdown', 'options'=> self::get_groups('name') );
				
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
		
		foreach( $raw_array as $key => $value ) {
			
			$output[ $key ] = is_serialized( $value ) ? implode( ', ', unserialize( $value ) ) : $value;
			
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
		
		$CSV->enclosure = "'";
	
		$CSV->parse( $src_file );
	
		// build the column names
		if ( is_array( $CSV->titles ) ) {
			
			$column_names = $CSV->titles;
			
			// remove enclosure characters
			array_walk( $column_names, array( __CLASS__, '_enclosure_trim' ), $CSV->enclosure );
			
		} else { 

			foreach ( self::get_column_atts() as $column ) {
			
				if ( $column->import ) $column_names[] = $column->name;
			
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
	
				return sprintf( __('The number of items in line %s is incorrect.<br />There are %s and there should be %s.', self::PLUGIN_NAME), $line_num, count( $values ), count( $column_names ) );
	
			}
			
			if ( ! $post = array_combine( $column_names, $values ) ) return __('Number of values does not match number of columns', self::PLUGIN_NAME) ;// suspenders and a belt here
			
			// error_log( __METHOD__.' post array='.print_r( $post, true));
			
			// we've got our record data, now add it to the database
			self::process_form( $post, 'insert' );
			
			$line_num++;
			
		}
		return $line_num;
	}
	
	/**
	 * trims enclosure characters from the csv field
	 * @access public because PHP callback uses it
	 */
	public function _enclosure_trim( &$value, $key, $enclosure = "'" ) {

    $value = trim( $value, $enclosure );

	}
	
	/**
	 * handles file uploads
	 *
	 * @param array $upload_file the $_FILES array element corresponding to one file
	 *
	 * return string the path to the uploaded file or false if error
	 */ 
	private function _handle_file_upload( $name, $file ) {
		
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
		
		$options = get_option( self::$participants_db_options );
		
		if ( $file['size'] > $options[ 'image_upload_limit' ] * 1024 ) {
			
			self::$validation_errors->add_error( $name, sprintf( __('The image you tried to upload is too large. The file must be smaller than %sK.'), self::PLUGIN_NAME ), $options[ 'image_upload_limit' ] );
			
			return false;
			
		}
		
    if ( false === move_uploaded_file( $file['tmp_name'], ABSPATH.$options['image_upload_location'].$new_filename ) ) {
			
			self::$validation_errors->add_error( $name, __('The file could not be saved.', self::PLUGIN_NAME ) );
			
			return false;
			
		}
		
		else return get_bloginfo('wpurl').DIRECTORY_SEPARATOR.$options['image_upload_location'].$new_filename;
	
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
        <h4><?php echo self::$plugin_title?><br /><?php _e('WordPress Plugin', self::PLUGIN_NAME )?></h4>
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
