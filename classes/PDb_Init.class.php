<?php
/*
 * plugin initialization class
 *
 * version 1.1
 *
 * The way db updates will work is we will first set the "fresh intall" db
 * initialization to the latest version's structure. Then, we add the "delta"
 * queries to the series of upgrade steps that follow. Whichever version the
 * plugin comes in with when activated, it will jump into the series at that
 * point and complete the series to bring the database up to date.
 *
 * we're not using WP's dbDelta for updates because it's too fussy
 */

class PDb_Init
{
    // this is the current db version
    const VERSION = '0.5';

    // arrays for building default field set
    public static $internal_fields;
    public static $main_fields;
    public static $admin_fields;
    public static $personal_fields;
    public static $source_fields;
    public static $field_groups;

    function __construct( $mode = false )
    {
        if ( ! $mode )
            wp_die( 'class must be be called on the activation hooks', 'object not correctly instantiated' );

        // error_log( __METHOD__.' called with '.$mode );

        switch( $mode )
        {
            case 'activate' :
                $this->_activate();
                break;

            case 'deactivate' :
                $this->_deactivate();
                break;

            case 'uninstall' :
                $this->_uninstall();
                break;
        }
    }

    /**
     * Set up tables, add options, etc. - All preparation that only needs to be done once
     */
    public function on_activate()
    {
        new PDb_Init( 'activate' );
    }

    /**
     * Do nothing like removing settings, etc.
     * The user could reactivate the plugin and wants everything in the state before activation.
     * Take a constant to remove everything, so you can develop & test easier.
     */
    public function on_deactivate()
    {
        new PDb_Init( 'deactivate' );
    }

    /**
     * Remove/Delete everything - If the user wants to uninstall, then he wants the state of origin.
     */
    public function on_uninstall()
    {
        new PDb_Init( 'uninstall' );
    }

    private function _activate()
    {

      global $wpdb;
			
			

      // fresh install: install the tables if they don't exist
      if ( $wpdb->get_var('show tables like "'.Participants_Db::$participants_table.'"') != Participants_Db::$participants_table ) :
      
      // define the arrays for loading the initial db records
      $this->_define_init_arrays();

        // create the field values table
        $sql = 'CREATE TABLE '.Participants_Db::$fields_table.' (
          `id` INT(3) NOT NULL AUTO_INCREMENT,
          `order` INT(3) NOT NULL DEFAULT 0,
          `name` VARCHAR(30) NOT NULL,
          `title` TINYTEXT NOT NULL,
          `default` TINYTEXT NULL,
          `group` VARCHAR(30) NOT NULL,
          `help_text` TEXT NULL,
          `form_element` TINYTEXT NULL,
          `values` LONGTEXT NULL,
          `validation` TINYTEXT NULL,
          `display_column` INT(3) DEFAULT 0,
          `admin_column` INT(3) DEFAULT 0,
          `sortable` BOOLEAN DEFAULT 0,
          `CSV` BOOLEAN DEFAULT 0,
          `persistent` BOOLEAN DEFAULT 0,
          `signup` BOOLEAN DEFAULT 0,
          UNIQUE KEY  ( `name` ),
          INDEX  ( `order` ),
          INDEX  ( `group` ),
          PRIMARY KEY  ( `id` )
          )
          DEFAULT CHARACTER SET utf8
          ';
        $wpdb->query($sql);

        // create the groups table
        $sql = 'CREATE TABLE '.Participants_Db::$groups_table.' (
          `id` INT(3) NOT NULL AUTO_INCREMENT,
          `order` INT(3) NOT NULL DEFAULT 0,
          `display` BOOLEAN DEFAULT 1,
          `title` TINYTEXT NOT NULL,
          `name` VARCHAR(30) NOT NULL,
          `description` TEXT NULL,
          UNIQUE KEY ( `name` ),
          PRIMARY KEY ( `id` )
          )
          DEFAULT CHARACTER SET utf8
          AUTO_INCREMENT = 1
          ';
        $wpdb->query($sql);

        // create the main data table
        $sql = 'CREATE TABLE ' . Participants_Db::$participants_table . ' (
          `id` int(6) NOT NULL AUTO_INCREMENT,
          `private_id` VARCHAR(6) NOT NULL,
          ';
        foreach( array_keys( self::$field_groups ) as $group ) {

        // these are not added to the sql in the loop
        if ( $group == 'internal' ) continue;

        foreach( self::${$group.'_fields'} as $name => &$defaults ) {

          if ( ! isset( $defaults['form_element'] ) ) $defaults['form_element'] = 'text-line';

            $datatype = Participants_Db::set_datatype( $defaults['form_element'] );

            $sql .= '`'.$name.'` '.$datatype.' NULL, ';

          }

        }

        $sql .= '`date_recorded` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          `date_updated` TIMESTAMP NOT NULL,
          PRIMARY KEY  (`id`)
          )
          DEFAULT CHARACTER SET utf8
          AUTO_INCREMENT = '.Participants_Db::$id_base_number.'
          ;';

        $wpdb->query($sql);

        // save the db version
        add_option( Participants_Db::$db_version );
        update_option( Participants_Db::$db_version, self::VERSION );

        // now load the default values into the database
        $i = 0;
        unset( $defaults );
        foreach( array_keys( self::$field_groups ) as $group ) {

          foreach( self::${$group.'_fields'} as $name => $defaults ) {

            $defaults['name'] = $name;
            $defaults['group'] = $group;
            $defaults['CSV'] = 'main' == $group ? 1 : 0;
            $defaults['order'] = $i;
            $defaults['validation'] = isset( $defaults['validation'] ) ? $defaults['validation'] : 'no';

            if ( isset( $defaults['values'] ) && is_array( $defaults['values'] ) ) {

              $defaults['values'] = serialize( $defaults['values'] );

            }

            $wpdb->insert( Participants_Db::$fields_table, $defaults );

            $i++;

          }

        }

        // put in the default groups
        $i = 1;
        $defaults = array();
        foreach( self::$field_groups as $group=>$title ) {
          $defaults['name'] = $group;
          $defaults['title'] = $title;
          $defaults['display'] = ( in_array( $group, array( 'internal', 'admin', 'source' ) ) ? 0 : 1 );
          $defaults['order'] = $i;

          $wpdb->insert( Participants_Db::$groups_table, $defaults );

          $i++;

        }

        // create the default record
        $this->set_default_record();

      endif;// end of the fresh install

      if ( false === get_option( Participants_Db::$db_version ) || '0.1' == get_option( Participants_Db::$db_version ) ) {

        /*
         * updates version 0.1 database to 0.2
         *
         * adding a new column "display_column" and renaming "column" to
         * "admin_column" to accommodate the new frontend display shortcode
         */

        $sql = "ALTER TABLE ".Participants_Db::$fields_table." ADD COLUMN `display_column` INT(3) DEFAULT 0 AFTER `validation`,";

        $sql .= "CHANGE COLUMN `column` `admin_column` INT(3)";

        if ( false !== $wpdb->query( $sql ) ) {

          // in case the option doesn't exist
          add_option( Participants_Db::$db_version );

          // set the version number this step brings the db to
          update_option( Participants_Db::$db_version, '0.2' );

        }
				
				// load some preset values into new column
				$values = array( 
												'first_name' => 1,
												'last_name'  => 2,
												'city'       => 3,
												'state'      => 4 
												);
				foreach( $values as $field => $value ) {
					$wpdb->update( 
												Participants_Db::$fields_table,
												array('display_column' => $value ),
												array( 'name' => $field )
												);
				}

      }

      if ( '0.2' == get_option( Participants_Db::$db_version ) ) {

        /*
         * updates version 0.2 database to 0.3
         *
         * modifying the 'values' column of the fields table to allow for larger
         * select option lists
         */

        $sql = "ALTER TABLE ".Participants_Db::$fields_table." MODIFY COLUMN `values` LONGTEXT NULL DEFAULT NULL";

        if ( false !== $wpdb->query( $sql ) ) {

          // set the version number this step brings the db to
          update_option( Participants_Db::$db_version, '0.3' );

        }

      }

      if ( '0.3' == get_option( Participants_Db::$db_version ) ) {

        /*
         * updates version 0.3 database to 0.4
				 *
         * changing the 'when' field to a date field
         * exchanging all the PHP string date values to UNIX timestamps in all form_element = 'date' fields
				 *
         */
				
				// change the 'when' field to a date field
				$wpdb->update( Participants_Db::$fields_table, array( 'form_element' => 'date' ), array( 'name' => 'when', 'form_element' => 'text-line' ) );
				 
				//
				$date_fields = $wpdb->get_results( 'SELECT f.name FROM '.Participants_Db::$fields_table.' f WHERE f.form_element = "date"', ARRAY_A );
         		
         		$df_string = '';
         		
         		foreach( $date_fields as $date_field ) {
         		
         			if ( ! in_array( $date_field['name'], array( 'date_recorded', 'date_updated' ) ) ) 
         				$df_string .= ',`'.$date_field['name'].'` ';
         		}
         			
				// skip updating the Db if there's nothing to update
        if ( ! empty( $df_string ) ) :
         			
					$query = '
						
						SELECT `id`'.$df_string.'
						FROM '.Participants_Db::$participants_table;
					
					$fields = $wpdb->get_results( $query, ARRAY_A );
					
					
					// now that we have all the date field values, convert them to N=UNIX timestamps
					foreach( $fields as $row ) {
						
						$id = $row['id'];
						unset( $row['id'] );
						
						$update_row = array();
						
						foreach ( $row as $field => $original_value ) {
							
							if ( empty( $original_value ) ) continue 2;
							
							// if it's already a timestamp, we don't try to convert
							$value = preg_match('#^[0-9]+$#',$original_value) > 0 ? $original_value : strtotime( $original_value );
							
							// if strtotime fails, revert to original value
							$update_row[ $field ] = ( false === $value ? $original_value : $value );
							
						}
						
						$wpdb->update( 
														Participants_Db::$participants_table, 
														$update_row, 
														array( 'id' => $id ) 
													);
						
					}
				
				endif;
				
				// set the version number this step brings the db to
				update_option( Participants_Db::$db_version, '0.4' );

      }

      if ( '0.4' == get_option( Participants_Db::$db_version ) ) {

        /*
         * updates version 0.4 database to 0.5
         *
         * modifying the "import" column to be named more appropriately "CSV"
         */

        $sql = "ALTER TABLE ".Participants_Db::$fields_table." CHANGE COLUMN `import` `CSV` TINYINT(1)";

        if ( false !== $wpdb->query( $sql ) ) {

          // set the version number this step brings the db to
          update_option( Participants_Db::$db_version, '0.5' );

        }

      }
			
			/* this fixes an error I made in the 0.5 DB update
			*/
			if ( '0.5' == get_option( Participants_Db::$db_version ) && false === Participants_Db::get_participant() ) {
				
				// define the arrays for loading the initial db records
      	$this->_define_init_arrays();
				
				// load the default values into the database
        $i = 0;
        unset( $defaults );
        foreach( array_keys( self::$field_groups ) as $group ) {

          foreach( self::${$group.'_fields'} as $name => $defaults ) {

            $defaults['name'] = $name;
            $defaults['group'] = $group;
            $defaults['CSV'] = 'main' == $group ? 1 : 0;
            $defaults['order'] = $i;
            $defaults['validation'] = isset( $defaults['validation'] ) ? $defaults['validation'] : 'no';

            if ( isset( $defaults['values'] ) && is_array( $defaults['values'] ) ) {

              $defaults['values'] = serialize( $defaults['values'] );

            }

            $wpdb->insert( Participants_Db::$fields_table, $defaults );

            $i++;

          }

        }
				
				// create the default record
        $this->set_default_record();
				
			}

      /*
       * this is to fix a problem with the timestamp having it's datatype
       * changed when the field attributes are edited
       */
			if ( '0.5' == get_option( Participants_Db::$db_version ) ) {

        $sql = "SHOW FIELDS FROM ".Participants_Db::$participants_table." WHERE `field` IN ('date_recorded','date_updated')";
        $field_info = $wpdb->get_results( $sql );

        foreach ( $field_info as $field ) {

          if ( $field->Type !== 'TIMESTAMP' ) {

            switch ( $field->Field ) {

              case 'date_recorded':

                $column_definition = '`date_recorded` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
                break;

              case 'date_updated':

                $column_definition = '`date_updated` TIMESTAMP NOT NULL DEFAULT 0';
                break;

              default:

                $column_definition = false;

            }

            if ( false !== $column_definition ) {

              $sql = "ALTER TABLE ".Participants_Db::$participants_table." MODIFY COLUMN ".$column_definition;

              $result = $wpdb->get_results( $sql );

            }

          }

        }

			}
				
      error_log( Participants_Db::PLUGIN_NAME.' plugin activated' );
      
    }

    private function _deactivate()
    {
				
				error_log( Participants_Db::PLUGIN_NAME.' plugin deactivated' );
    }

    private function _uninstall()
    {

        global $wpdb;

        // delete tables
        $sql = 'DROP TABLE `'.Participants_Db::$fields_table.'`, `'.Participants_Db::$participants_table.'`, `'.Participants_Db::$groups_table.'`;';
        $wpdb->query( $sql );

        // remove options
        delete_option( Participants_Db::$participants_db_options );
				delete_option( Participants_Db::$db_version );

				// clear transients
				delete_transient( 'pdb_last_record' );
				
        error_log( Participants_Db::PLUGIN_NAME.' plugin uninstalled' );
        
    }

    /**
     * defines arrays containg a starting set of fields, groups, etc.
     *
     * @return void
     */
    private function _define_init_arrays() {

      // define the default field groups
      self::$field_groups = array(
                                  'main'      => 'Participant Info',
                                  'admin'     => 'Administrative Info',
                                  'personal'  => 'Personal Info',
                                  'source'    => 'Source of the Record',
                                  'internal'  => 'Record Info',
                                  );

      // fields for keeping track of records; not manually edited, but they can be displayed
      self::$internal_fields = array(
                            'id'             => array(
                                                    'title' => 'Record ID',
                                                    'signup' => 1,
                                                    'form_element'=>'text-line',
                                                    ),
                            'private_id'     => array(
                                                    'title' => 'Private ID',
                                                    'signup' => 1,
                                                    'form_element' => 'text',
                                                    'default' => 'RPNE2',
                                                    ),
                            'date_recorded'  => array(
                                                    'title' => 'Date Recorded',
                                                    'form_element'=>'date',
																										'admin_column'=>'100',
																										'sortable'=>1,
                                                    ),
                            'date_updated'   => array(
                                                    'title' => 'Date Updated',
                                                    'form_element'=>'date',
																										'sortable'=>1,
                                                    ),
                            );

      // these are some fields just to get things started
      // in the released plugin, these will be defined by the user
      //
      // the key is the id slug of the field
      // the fields in the array are:
      //  title - a display title
      //  help_text - help text to appear on the form
      //   default - a default value
      //   sortable - a listing can be sorted by this value if set
      //   column - column in the list view and order (missing or 0 for not used)
      //   persistent - is the field persistent from one entry to the next (for
      //                convenience while entering multiple records)
      //   CSV - is the field one to be imported or exported
      //   validation - if the field needs to be validated, use this regex or just
      //               yes for a value that must be filled in
      //   form_element - the element to use in the form--defaults to
      //                 input, Could be text-line (input), text-field (textarea),
      //                 radio, dropdown (option) or checkbox, also select-other
      //                 multi-checkbox and asmselect.(http://www.ryancramer.com/journal/entries/select_multiple/)
      //                 The mysql data type is determined by this.
      //   values array title=>value pairs for checkboxes, radio buttons, dropdowns
      //               for checkbox, first item is visible option, if value
      //               matches 'default' value then it defaults checked
      self::$main_fields = array(
                                  'first_name'   => array(
                                                        'title' => 'First Name',
                                                        'form_element' => 'text-line',
                                                        'validation' => 'yes',
                                                        'sortable' => 1,
                                                        'admin_column' => 1,
                                                        'display_column' => 1,
                                                        'signup' => 1
                                                        ),
                                  'last_name'    => array(
                                                        'title' => 'Last Name',
                                                        'form_element' => 'text-line',
                                                        'validation' => 'yes',
                                                        'sortable' => 1,
                                                        'admin_column' => 2,
                                                        'display_column' => 2,
                                                        'signup' => 1
                                                        ),
                                  'address'      => array(
                                                        'title' => 'Address',
                                                        'form_element' => 'text-line',
                                                        ),
                                  'city'         => array(
                                                        'title' => 'City',
                                                        'sortable' => 1,
                                                        'persistent' => 1,
                                                        'form_element' => 'text-line',
                                                        'admin_column' => 3,
                                                        'display_column' => 3,
                                                      ),
                                  'state'        => array(
                                                        'title' => 'State',
                                                        'form_element' => 'text-line',
                                                        'display_column' => 4,
                                                      ),
                                  'country'      => array(
                                                        'title' => 'Country',
                                                        'form_element' => 'text-line',
                                                      ),
                                  'zip'          => array(
                                                        'title' => 'Zip Code',
                                                        'form_element' => 'text-line',
                                                      ),
                                  'phone'        => array(
                                                        'title' => 'Phone',
                                                        'help_text' => 'primary contact number',
                                                        'form_element' => 'text-line',
                                                      ),
                                  'email'        => array(
                                                        'title' => 'Email',
                                                        'form_element' => 'text-line',
                                                        'validation' => '#^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$#i',
                                                        'signup' => 1,
                                                      ),
                                  'mailing_list' => array(
                                                        'title' => 'Mailing List',
                                                        'help_text' => 'do you want to receive our newsletter and occasional announcements?',
                                                        'sortable' => 1,
                                                        'signup' => 1,
                                                        'form_element' => 'checkbox',
                                                        'default' => 'Yes',
                                                        'values'  => array(
                                                                          'Yes',
                                                                          'No',
                                                                          ),
                                                        ),
                                  );
      self::$admin_fields = array(
                                  'donations'   => array(
                                                        'title' => 'Donations Made',
                                                        'form_element' => 'text-field',
                                                      ),
                                  'volunteered' => array(
                                                        'title' => 'Time Volunteered',
                                                        'form_element' => 'text-field',
                                                        'help_text' => 'how much time they have volunteered',
                                                      ),

                                  );
      self::$personal_fields = array(
                                  'contact_permission' => array(
                                                              'title' => 'Contact Permission',
                                                              'help_text' => 'may we contact you? If so, what is the best way?',
                                                              'form_element' => 'text-line',
                                                              ),
                                  'resources'         => array(
                                                              'title' => 'Resources Offered',
                                                              'form_element' => 'text-field',
                                                              'help_text' => 'how are you willing to help?',
                                                              ),
                                  );
      self::$source_fields = array(
                                  'where'             => array(
                                                              'title' => 'Location or Event of Signup',
                                                              'form_element' => 'text-line',
                                                              'persistent' => 1,
                                                            ),
                                  'when'              => array(
                                                              'title' => 'Signup Date',
                                                              'form_element' => 'date',
                                                              'persistent' => 1,
                                                            ),
                                  'by'                => array(
                                                              'title' => 'Signup Gathered By',
                                                              'form_element' => 'text-line',
                                                              'persistent' => 1,
                                                            ),
                                  );



    }

    // create the default record
    public function set_default_record() {
			
			if ( ! is_array( self::$field_groups ) ) self::_define_init_arrays();

      $default_values = array();
      $fields = array();

      // append all the field groups into one array ($fields) for the insert
      foreach( array_keys( self::$field_groups ) as $group ) {

        $fields = array_merge( $fields, self::${$group.'_fields'} );

      }

      // now build an array of default values to put into the default record
      foreach ( $fields as $name => $field ) {

        $default_values[ $name ] = isset( $field['default'] ) ? $field['default'] : '';

      }

      // insert the record; no id value for the record id forces it to use the default record id
      return Participants_Db::process_form( $default_values, 'insert', true );

    }

}