<?php
/*
 * class providing CSV file import functionality for the Participants Database
 * plugin
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2012 xnau webdesign
 * @license    GPL2
 * @version    0.1
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    Participants_Db class, CSV_Import
 *
 */
class PDb_CSV_Import extends xnau_CSV_Import {
  
  function __construct( $file_field_name ) {
    
    $this->i10n_context = Participants_Db::PLUGIN_NAME;
    
    $this->_set_column_array();
    
    parent::__construct( $file_field_name );
    
  }
  
  function _set_column_array() {
    
    $columns = Participants_Db::get_column_atts('all');

    foreach ( $columns as $column ) {
    
      if ( $column->CSV != '0') $this->column_names[] = $column->name;
    
    }
    
    $this->column_count = count( $this->column_names );
        
    
  }
  
  function _set_upload_dir() {

    $this->upload_directory = Participants_Db::$plugin_options['image_upload_location'];
  
    // check for the target directory; attept to create if it doesn't exist
    return is_dir( $this->root_path.$this->upload_directory ) ? true : Participants_Db::_make_uploads_dir( $this->upload_directory ) ;
    
  }
  
  /**
   * applies conditioning and escaping to the incoming value, also allows for a filter callback
   * 
   * @global object $wpdb
   * @param type $value
   * @return string
   */
  function process_value($value) {
    global $wpdb;
    return Participants_Db::set_filter('csv_import_value', $wpdb->escape($this->_enclosure_trim($value, '', $this->CSV->enclosure)));
  }
  
  function store_record( $post ) {
    
    // add the record data to the database
		$id = Participants_Db::process_form( $post, 'insert' );
		
		// count the insert type for the record
		switch ( Participants_Db::$insert_status ) {
      
      case 'insert' :
      $this->insert_count++;
      break;
    
      case 'update' :
      $this->update_count++;
      break;
    
      case 'skip' :
      $this->skip_count++;
      break;
    
    }
    
  }
  
}