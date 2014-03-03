<?php
/*
 * plugin initialization class
 *
 * version 1.5.4.6
 *
 * The way db updates will work is we will first set the "fresh install" db
 * initialization to the latest version's structure. Then, we add the "delta"
 * queries to the series of upgrade steps that follow. Whichever version the
 * plugin comes in with when activated, it will jump into the series at that
 * point and complete the series to bring the database up to date.
 *
 * we're not using WP's dbDelta for updates because it's too fussy
 */

class PDb_Init
{

    // arrays for building default field set
    public static $internal_fields;
    public static $main_fields;
    public static $admin_fields;
    public static $personal_fields;
    public static $field_groups;

    function __construct( $mode = false )
    {
        if ( ! $mode )
            wp_die( 'class must be called on the activation hooks', 'object not correctly instantiated' );

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
          `name` VARCHAR(64) NOT NULL,
          `title` TINYTEXT NOT NULL,
          `default` TINYTEXT NULL,
          `group` VARCHAR(64) NOT NULL,
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
					`readonly` BOOLEAN DEFAULT 0,
          UNIQUE KEY  ( `name` ),
          INDEX  ( `order` ),
          INDEX  ( `group` ),
          PRIMARY KEY  ( `id` )
          )
          DEFAULT CHARACTER SET utf8
          COLLATE utf8_unicode_ci
          AUTO_INCREMENT = 0
          ';
        $wpdb->query($sql);

        // create the groups table
        $sql = 'CREATE TABLE '.Participants_Db::$groups_table.' (
          `id` INT(3) NOT NULL AUTO_INCREMENT,
          `order` INT(3) NOT NULL DEFAULT 0,
          `display` BOOLEAN DEFAULT 1,
          `admin` BOOLEAN NOT NULL DEFAULT 0,
          `title` TINYTEXT NOT NULL,
          `name` VARCHAR(64) NOT NULL,
          `description` TEXT NULL,
          UNIQUE KEY ( `name` ),
          PRIMARY KEY ( `id` )
          )
          DEFAULT CHARACTER SET utf8
          COLLATE utf8_unicode_ci
          AUTO_INCREMENT = 1
          ';
        $wpdb->query($sql);

        // create the main data table
        $sql = 'CREATE TABLE ' . Participants_Db::$participants_table . ' (
          `id` int(6) NOT NULL AUTO_INCREMENT,
          `private_id` VARCHAR(6) NULL,
          ';
        foreach( array_keys( self::$field_groups ) as $group ) {

        // these are not added to the sql in the loop
        if ( $group == 'internal' ) continue;

        foreach( self::${$group.'_fields'} as $name => &$defaults ) {

          if ( ! isset( $defaults['form_element'] ) ) $defaults['form_element'] = 'text-line';

            $datatype = PDb_FormElement::get_datatype( $defaults['form_element'] );

            $sql .= '`'.$name.'` '.$datatype.' NULL, ';

          }

        }

        $sql .= '`date_recorded` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          `date_updated` TIMESTAMP NULL DEFAULT NULL,
          `last_accessed` TIMESTAMP NULL DEFAULT NULL,
          PRIMARY KEY  (`id`)
          )
          DEFAULT CHARACTER SET utf8
          COLLATE utf8_unicode_ci
          ;';

        $wpdb->query($sql);

        // save the db version
        add_option( Participants_Db::$db_version_option );
        update_option( Participants_Db::$db_version_option, Participants_Db::$db_version );

        // now load the default values into the database
        $i = 0;
        unset( $defaults );
        foreach( array_keys( self::$field_groups ) as $group ) {

          foreach( self::${$group.'_fields'} as $name => $defaults ) {

            $defaults['name'] = $name;
            $defaults['group'] = $group;
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

      endif;// end of the fresh install

      
				
      error_log( Participants_Db::PLUGIN_NAME.' plugin activated' );
      
    }

    private function _deactivate()
    {
				
				error_log( Participants_Db::PLUGIN_NAME.' plugin deactivated' );
    }

    /**
     * deletes all plugin tables, options and transients
     * 
     * @global object $wpdb
     */
    private function _uninstall()
    {

        global $wpdb;

        // delete tables
        $sql = 'DROP TABLE `'.Participants_Db::$fields_table.'`, `'.Participants_Db::$participants_table.'`, `'.Participants_Db::$groups_table.'`;';
        $wpdb->query( $sql );

        // remove options
        delete_option( Participants_Db::$participants_db_options );
				delete_option( Participants_Db::$db_version_option );
        delete_option( Participants_Db::$default_options );

				// clear transients
        delete_transient(Participants_Db::$last_record);
        $sql = 'SELECT `option_name` FROM ' . $wpdb->prefix . 'options WHERE `option_name` LIKE "%' . Participants_Db::$prefix . 'retrieve-count-%" OR `option_name` LIKE "%' . PDb_List_Admin::$user_settings . '%" OR `option_name` LIKE "%' . Participants_Db::$prefix . 'captcha_key" OR `option_name` LIKE "%' . Participants_Db::$prefix . 'signup-email-sent" ';
        $transients = $wpdb->get_col($sql);
        foreach($transients as $name) {
          delete_transient($name);
        }
        
        error_log( Participants_Db::PLUGIN_NAME.' plugin uninstalled' );
        
    }
    
    /**
     * performs an update to the database if needed
     */
    public static function on_update() {
      
      global $wpdb;
      
      // determine the actual version status of the database
      self::set_database_real_version();
      
      if (WP_DEBUG) error_log('participants database db version determined to be: '.get_option( Participants_Db::$db_version_option ) );
      
      if ( false === get_option( Participants_Db::$db_version_option ) || '0.1' == get_option( Participants_Db::$db_version_option ) ) {

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
          add_option( Participants_Db::$db_version_option );

          // set the version number this step brings the db to
          update_option( Participants_Db::$db_version_option, '0.2' );

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

      if ( '0.2' == get_option( Participants_Db::$db_version_option ) ) {

        /*
         * updates version 0.2 database to 0.3
         *
         * modifying the 'values' column of the fields table to allow for larger
         * select option lists
         */

        $sql = "ALTER TABLE ".Participants_Db::$fields_table." MODIFY COLUMN `values` LONGTEXT NULL DEFAULT NULL";

        if ( false !== $wpdb->query( $sql ) ) {

          // set the version number this step brings the db to
          update_option( Participants_Db::$db_version_option, '0.3' );

        }

      }

      if ( '0.3' == get_option( Participants_Db::$db_version_option ) ) {

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
				update_option( Participants_Db::$db_version_option, '0.4' );

      }

      if ( '0.4' == get_option( Participants_Db::$db_version_option ) ) {

        /*
         * updates version 0.4 database to 0.5
         *
         * modifying the "import" column to be named more appropriately "CSV"
         */

        $sql = "ALTER TABLE ".Participants_Db::$fields_table." CHANGE COLUMN `import` `CSV` TINYINT(1)";

        if ( false !== $wpdb->query( $sql ) ) {

          // set the version number this step brings the db to
          update_option( Participants_Db::$db_version_option, '0.5' );

        }

      }
			
			/* this fixes an error I made in the 0.5 DB update
			*/
			if ( '0.5' == get_option( Participants_Db::$db_version_option ) && false === Participants_Db::get_participant() ) {
				
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
        // set the version number this step brings the db to
        update_option( Participants_Db::$db_version_option, '0.5.1' );
				
			}

      /*
       * this is to fix a problem with the timestamp having it's datatype
       * changed when the field attributes are edited
       */
			if ( '0.51' == get_option( Participants_Db::$db_version_option ) ) {

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

        // delete the default record
        $wpdb->query( $wpdb->prepare( "DELETE FROM ".Participants_Db::$participants_table." WHERE private_id = '%s'", 'RPNE2'));
				
				// add the new private ID admin column setting because we eliminated the redundant special setting
				$options = get_option( Participants_Db::$participants_db_options );
				if ( $options['show_pid'] ) {
						$wpdb->update( Participants_Db::$fields_table, array( 'admin_column' => 90 ), array( 'name' => 'private_id') );
				}
			
				/*
				 * add the "read-only" column
				 */
				$sql = "ALTER TABLE ".Participants_Db::$fields_table." ADD COLUMN `readonly` BOOLEAN DEFAULT 0 AFTER `signup`";
        
        $wpdb->query( $sql );
        
        /*
         * change the old 'textarea' field type to the new 'text-area'
         */
        $sql = "
          UPDATE ".Participants_Db::$fields_table."
          SET `form_element` = replace(`form_element`, \"textarea\", \"text-area\")";
        $wpdb->query( $sql ); 
        $sql = "
          UPDATE ".Participants_Db::$fields_table."
          SET `form_element` = replace(`form_element`, \"text-field\", \"text-line\") ";
        

        if ( false !== $wpdb->query( $sql ) ) {

          // update the stored DB version number
          update_option( Participants_Db::$db_version_option, '0.55' );

        }
			
			}
			
			/*
			 * this database version adds the "last_accessed" column to the main database
			 * 
			 */
			if ( '0.55' == get_option( Participants_Db::$db_version_option ) ) { 
			
				/*
				 * add the "last_accessed" column
				 */
				$sql = "ALTER TABLE ".Participants_Db::$participants_table." ADD COLUMN `last_accessed` TIMESTAMP NOT NULL AFTER `date_updated`";
        
        $wpdb->query( $sql );
        
        /*
         * add the new field to the fields table
         */
        $data = array(
                      'order' => '20',
                      'name' => 'last_accessed',
                      'title' => 'Last Accessed',
                      'group' => 'internal',
                      'sortable' => '1',
                      'form_element' => 'date',
                      );

        if ( false !== $wpdb->insert( Participants_Db::$fields_table, $data ) ) {

          // update the stored DB version number
          update_option( Participants_Db::$db_version_option, '0.6' );

        }
			
			}
      if ( '0.6' == get_option( Participants_Db::$db_version_option ) ) {
        /*
         * this database version changes the internal timestamp fields from "date" 
         * type to "timestamp" type fields, also sets the 'readonly' flag of internal 
         * fields so we don't have to treat them as a special case any more.
         * 
         * set the field type of internal timestamp fields to 'timestamp'
         */
        $sql = "UPDATE ".Participants_Db::$fields_table." p SET p.form_element = 'timestamp', p.readonly = '1' WHERE p.name IN ('date_recorded','last_accessed','date_updated')";
        if ($wpdb->query( $sql ) !== false) {
          // update the stored DB version number
          update_option( Participants_Db::$db_version_option, '0.65' );
        }
      }
      if ( '0.65' == get_option( Participants_Db::$db_version_option ) ) {
        /*
         * adds a new column to the goups database so a group cna be designated as a "admin" group
         */
        $sql = "ALTER TABLE ".Participants_Db::$groups_table." ADD COLUMN `admin` BOOLEAN NOT NULL DEFAULT 0 AFTER `order`";
        
          if ($wpdb->query( $sql ) !== false) {
          // update the stored DB version number
          update_option( Participants_Db::$db_version_option, '0.7' );
        }
        $sql = "UPDATE ".Participants_Db::$groups_table." g SET g.admin = '1' WHERE g.name ='internal'";
        $wpdb->query( $sql );
      }
      if ('0.7' == get_option(Participants_Db::$db_version_option)) {
        /*
         * changes all date fields' datatype to BIGINT unless the user has modified the datatype
         */
        $sql = 'SELECT f.name FROM ' . Participants_Db::$fields_table . ' f INNER JOIN INFORMATION_SCHEMA.COLUMNS AS c ON TABLE_NAME = "' . Participants_Db::$participants_table . '" AND c.column_name = f.name COLLATE utf8_general_ci AND data_type = "TINYTEXT" WHERE f.form_element = "date"';

        $results = $wpdb->get_results($sql, ARRAY_A);
        $fields = array();
        foreach ($results as $result) {
          $fields[] = $result['name'];
        }

        if (count($fields) === 0) {
          
          // nothing to change, update the version number
          update_option(Participants_Db::$db_version_option, '0.8');
          
        } else {
          
          $results = $wpdb->get_results("SHOW COLUMNS FROM `" . Participants_Db::$participants_table . "`");
          $columns = array();
          foreach ($results as $result) {
            $columns[] = $result->Field;
          }
          $fields = array_intersect($columns,$fields);
					$sql = 'ALTER TABLE ' . Participants_Db::$participants_table . ' MODIFY COLUMN `' . implode('` BIGINT NULL, MODIFY COLUMN `', $fields) . '` BIGINT NULL';

					if (false !== $wpdb->query($sql)) {
						// set the version number this step brings the db to
						update_option(Participants_Db::$db_version_option, '0.8');
					}
				}
      }
      if ('0.8' == get_option(Participants_Db::$db_version_option)) {
        /*
         * all field and group names need more staorage space, changing the datatype to VARCHAR(64)
         */
        $sql = "ALTER TABLE ".Participants_Db::$fields_table." MODIFY `name` VARCHAR(64) NOT NULL, MODIFY `group` VARCHAR(64) NOT NULL";
      
        if ( false !== $wpdb->query( $sql ) ) {

          $sql = "ALTER TABLE ".Participants_Db::$groups_table." MODIFY `name` VARCHAR(64) NOT NULL";

          if ( false !== $wpdb->query( $sql ) ) {
            // set the version number this step brings the db to
            update_option( Participants_Db::$db_version_option, '0.9' );
          }
        }
      }
      
      if ('0.9' == get_option(Participants_Db::$db_version_option)) {
        /*
         * set TIMESTAMP fields to allow NULL and set the default to NULL
         */
        $success = $wpdb->query("ALTER TABLE `" . Participants_Db::$participants_table . "` MODIFY COLUMN `date_updated` TIMESTAMP NULL DEFAULT NULL, MODIFY COLUMN `last_accessed` TIMESTAMP NULL DEFAULT NULL");
        /*
         * set other "not null" columns to NULL so the empty default value won't trigger an error
         */
        if ($success !== false)
          $success = $wpdb->query("ALTER TABLE `" . Participants_Db::$participants_table . "` MODIFY COLUMN `private_id` VARCHAR(6) NULL");
        if ($success !== false)
          $success = $wpdb->query("ALTER TABLE `" . Participants_Db::$fields_table . "` MODIFY COLUMN `name` VARCHAR(64) NULL, MODIFY COLUMN `title` TINYTEXT NULL, MODIFY COLUMN `group` VARCHAR(64) NULL");
        if ($success !== false)
          $success = $wpdb->query("ALTER TABLE `" . Participants_Db::$groups_table . "` MODIFY COLUMN `name` VARCHAR(64) NULL, MODIFY COLUMN `title` TINYTEXT NULL");
        //
        if ($success !== false) {
          $table_status = $wpdb->get_results("SHOW TABLE STATUS WHERE `name` = '" . Participants_Db::$participants_table . "'");
          if (current($table_status)->Collation == 'utf8_general_ci') {
            if ($success !== false)
              $success = $wpdb->query("alter table `" . Participants_Db::$participants_table . "` convert to character set utf8 collate utf8_unicode_ci");
            if ($success !== false)
              $success = $wpdb->query("alter table `" . Participants_Db::$fields_table . "` convert to character set utf8 collate utf8_unicode_ci");
            if ($success !== false)
              $success = $wpdb->query("alter table `" . Participants_Db::$groups_table . "` convert to character set utf8 collate utf8_unicode_ci");
          }
        }

        if ($success === false) {
          error_log(__METHOD__ . ' database could not be updated: ' . $wpdb->last_error);
        } else {
          update_option(Participants_Db::$db_version_option, '1.0');
        }
      }
      
      if (WP_DEBUG) error_log( Participants_Db::PLUGIN_NAME.' plugin updated to Db version '.get_option( Participants_Db::$db_version_option ) );
      
    }
    
    /**
     * performs a series of tests on the database to determine it's actual version
     * 
     * this is becuse it is apparently possible for the database version option 
     * to be incorrect or missing. This way, we know with some certainty which version 
     * the database really is. Every time we create a new database version, we add 
     * a test for it here.
     * 
     * @global object $wpdb
     * @return null
     */
    private static function set_database_real_version() {

      /* don't bother if the value is set, we only do this if the db version can't 
       * be determined
       */
      if (get_option(Participants_Db::$db_version_option)) {
        return;
      }

      global $wpdb;

      // set up the option starting with the first version
      add_option(Participants_Db::$db_version_option);
      update_option(Participants_Db::$db_version_option, '0.1');

      // check to see if the update to 0.2 has been performed
      $column_test = $wpdb->get_results('SHOW COLUMNS FROM ' . Participants_Db::$fields_table . ' LIKE "column"');
      if (empty($column_test))
        update_option(Participants_Db::$db_version_option, '0.2');
      else return;

      // check for version 0.4
      $column_test = $wpdb->get_results('SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = "' . Participants_Db::$fields_table . '" AND COLUMN_NAME = "values"');
      if ( strtolower($column_test[0]->DATA_TYPE) == 'longtext')
        // we're skipping update 3 because all it does is insert default values
        update_option(Participants_Db::$db_version_option, '0.4');
      else return;

      // check for version 0.51
      $column_test = $wpdb->get_results('SHOW COLUMNS FROM ' . Participants_Db::$fields_table . ' LIKE "import"');
      if (empty($column_test))
        update_option(Participants_Db::$db_version_option, '0.51');
      else return;
      
      // check for version 0.55
      $column_test = $wpdb->get_results('SHOW COLUMNS FROM ' . Participants_Db::$fields_table . ' LIKE "readonly"');
      if (!empty($column_test))
        update_option(Participants_Db::$db_version_option, '0.55');
      else return;
      
      // check for version 0.6
      $column_test = $wpdb->get_results('SHOW COLUMNS FROM ' . Participants_Db::$participants_table . ' LIKE "last_accessed"');
      if (!empty($column_test))
        update_option(Participants_Db::$db_version_option, '0.6');
      else return;
      
      // check for version 0.65
      $value_test = $wpdb->get_var('SELECT `form_element` FROM ' . Participants_Db::$fields_table . ' WHERE `name` = "date_recorded"');
      
      
      if ($value_test == 'timestamp')
        update_option(Participants_Db::$db_version_option, '0.65');
      else return;
      
      // check for version 0.7
      $column_test = $wpdb->get_results('SHOW COLUMNS FROM ' . Participants_Db::$groups_table . ' LIKE "admin"');
      if (!empty($column_test))
        update_option(Participants_Db::$db_version_option, '0.7');
      else return;
      
      // check for version 0.9
      $column_test = $wpdb->get_results('SELECT CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = "' . Participants_Db::$fields_table . '" AND COLUMN_NAME = "name"');
      if ( $column_test[0]->CHARACTER_MAXIMUM_LENGTH == '64')
        update_option(Participants_Db::$db_version_option, '0.9');
      else return;
      
  }

    /**
     * defines arrays containg a starting set of fields, groups, etc.
     *
     * @return void
     */
    private function _define_init_arrays() {

      // define the default field groups
      self::$field_groups = array(
                                  'main'      => __('Participant Info', 'participants-database'),
                                  'personal'  => __('Personal Info', 'participants-database'),
                                  'admin'     => __('Administrative Info', 'participants-database'),
                                  'internal'  => __('Record Info', 'participants-database'),
                                  );

      // fields for keeping track of records; not manually edited, but they can be displayed
      self::$internal_fields = array(
                            'id'             => array(
                                                    'title' => 'Record ID',
                                                    'signup' => 1,
                                                    'form_element'=>'text-line',
                                                    'CSV' => 1,
                                                    'readonly' => 1,
                                                    ),
                            'private_id'     => array(
                                                    'title' => 'Private ID',
                                                    'signup' => 1,
                                                    'form_element' => 'text',
																										'admin_column' => 90,
                                                    'default' => 'RPNE2',
                                                    'readonly' => 1,
                                                    ),
                            'date_recorded'  => array(
                                                    'title' => 'Date Recorded',
                                                    'form_element'=>'timestamp',
																										'admin_column'=>100,
																										'sortable'=>1,
                                                    'readonly' => 1,
                                                    ),
                            'date_updated'   => array(
                                                    'title' => 'Date Updated',
                                                    'form_element'=>'timestamp',
																										'sortable'=>1,
                                                    'readonly' => 1,
                                                    ),
                            'last_accessed'   => array(
                                                    'title' => 'Last Accessed',
                                                    'form_element'=>'timestamp',
																										'sortable'=>1,
                                                    'readonly' => 1,
                                                    ),
                            );

      
      /*
       * these are some fields just to get things started
       * in the released plugin, these will be defined by the user
       *
       * the key is the id slug of the field
       * the fields in the array are:
       *  title - a display title
       *  help_text - help text to appear on the form
       *   default - a default value
       *   sortable - a listing can be sorted by this value if set
       *   column - column in the list view and order (missing or 0 for not used)
       *   persistent - is the field persistent from one entry to the next (for
       *                convenience while entering multiple records)
       *   CSV - is the field one to be imported or exported
       *   validation - if the field needs to be validated, use this regex or just
       *               yes for a value that must be filled in
       *   form_element - the element to use in the form--defaults to
       *                 input, Could be text-line (input), text-field (textarea),
       *                 radio, dropdown (option) or checkbox, also select-other
       *                 multi-checkbox and asmselect.(http: *www.ryancramer.com/journal/entries/select_multiple/)
       *                 The mysql data type is determined by this.
       *   values array title=>value pairs for checkboxes, radio buttons, dropdowns
       *               for checkbox, first item is visible option, if value
       *               matches 'default' value then it defaults checked
       */
      self::$main_fields = array(
                                  'first_name'   => array(
                                                        'title' => 'First Name',
                                                        'form_element' => 'text-line',
                                                        'validation' => 'yes',
                                                        'sortable' => 1,
                                                        'admin_column' => 2,
                                                        'display_column' => 1,
                                                        'signup' => 1,
                                                        'CSV' => 1,
                                                        ),
                                  'last_name'    => array(
                                                        'title' => 'Last Name',
                                                        'form_element' => 'text-line',
                                                        'validation' => 'yes',
                                                        'sortable' => 1,
                                                        'admin_column' => 3,
                                                        'display_column' => 2,
                                                        'signup' => 1,
                                                        'CSV' => 1,
                                                        ),
                                  'address'      => array(
                                                        'title' => 'Address',
                                                        'form_element' => 'text-line',
                                                        'CSV' => 1,
                                                        ),
                                  'city'         => array(
                                                        'title' => 'City',
                                                        'sortable' => 1,
                                                        'persistent' => 1,
                                                        'form_element' => 'text-line',
                                                        'admin_column' => 0,
                                                        'display_column' => 3,
                                                        'CSV' => 1,
                                                      ),
                                  'state'        => array(
                                                        'title' => 'State',
                                                        'sortable' => 1,
                                                        'persistent' => 1,
                                                        'form_element' => 'text-line',
                                                        'display_column' => 4,
                                                        'CSV' => 1,
                                                      ),
                                  'country'      => array(
                                                        'title' => 'Country',
                                                        'sortable' => 1,
                                                        'persistent' => 1,
                                                        'form_element' => 'text-line',
                                                        'CSV' => 1,
                                                      ),
                                  'zip'          => array(
                                                        'title' => 'Zip Code',
                                                        'sortable' => 1,
                                                        'persistent' => 1,
                                                        'form_element' => 'text-line',
                                                        'CSV' => 1,
                                                      ),
                                  'phone'        => array(
                                                        'title' => 'Phone',
                                                        'help_text' => 'Your primary contact number',
                                                        'form_element' => 'text-line',
                                                        'CSV' => 1,
                                                      ),
                                  'email'        => array(
                                                        'title' => 'Email',
                                                        'form_element' => 'text-line',
																												'admin_column' => 4,
                                                        'validation' => '#^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$#i',
                                                        'signup' => 1,
                                                        'CSV' => 1,
                                                      ),
                                  'mailing_list' => array(
                                                        'title' => 'Mailing List',
                                                        'help_text' => 'do you want to receive our newsletter and occasional announcements?',
                                                        'sortable' => 1,
                                                        'signup' => 1,
                                                        'form_element' => 'checkbox',
                                                        'CSV' => 1,
                                                        'default' => 'Yes',
                                                        'values'  => array(
                                                                          'Yes',
                                                                          'No',
                                                                          ),
                                                        ),
                                  );
      self::$personal_fields = array(
                                  'photo'       => array(
                                                        'title' => 'Photo',
                                                        'help_text' => 'Upload a photo of yourself. 300 pixels maximum width or height.',
                                                        'form_element' => 'image-upload',
                                                        ),
                                  'website'     => array(
                                                        'title' => 'Website, Blog or Social Media Link',
                                                        'form_element' => 'link',
                                                        'help_text' => 'Put the URL in the left box and the link text that will be shown on the right',
                                                        ),
                                  'interests'   => array(
                                                        'title' => 'Interests or Hobbies',
                                                        'form_element' => 'multi-select-other',
                                                        'values' => array(
                                                                          'Sports' => 'sports',
                                                                          'Photography' => 'photography',
                                                                          'Art/Crafts' => 'crafts',
                                                                          'Outdoors' => 'outdoors',
                                                                          'Yoga' => 'yoga',
                                                                          'Music' => 'music',
                                                                          'Cuisine' => 'cuisine',
                                                                          ),
                                                        ),
                                  );
      self::$admin_fields = array(
                                  'approved' => array(
                                                        'title' => 'Approved',
                                                        'sortable' => 1,
                                                        'form_element' => 'checkbox',
                                                        'default' => 'no',
                                                        'values'  => array(
                                                                          'yes',
                                                                          'no',
                                                                          ),
                                                        ),

                                  );



    }

}