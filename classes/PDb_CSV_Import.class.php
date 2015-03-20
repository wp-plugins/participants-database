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
if ( ! defined( 'ABSPATH' ) ) die;
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
  
  /**
   * takes a raw title row from the CSV and sets the column names array with it
   * if the imported row is different from the plugin's defined CSV columns
   *
   */
  protected function import_columns() {

    // build the column names from the CSV if it's there
    if (!$this->CSV->error and is_array($this->CSV->titles) and $this->column_names != $this->CSV->titles) {

      $this->column_names = $this->CSV->titles;

      $this->errors[] = __('New columns imported from the CSV file.', 'participants-database');

      // remove enclosure characters
      array_walk($this->column_names, array($this, '_enclosure_trim'), $this->CSV->enclosure);

      $this->column_count = count($this->column_names);
    }
  }
  
  function _set_upload_dir() {

    $this->upload_directory = Participants_Db::$plugin_options['image_upload_location'];
  
    // check for the target directory; attept to create if it doesn't exist
    return is_dir( $this->root_path.$this->upload_directory ) ? true : Participants_Db::_make_uploads_dir( $this->upload_directory ) ;
    
  }
  
  /**
   * applies conditioning and escaping to the incoming value, also allows for a filter callback
   * 
   * @param type $value
   * @return string
   */
  function process_value($value) {
    return Participants_Db::set_filter('csv_import_value', esc_sql($this->_enclosure_trim($value, '', $this->CSV->enclosure)));
  }
  
  function store_record( $post ) {
    
    $post['csv_file_upload'] = 'true';
    $post['subsource'] = Participants_Db::PLUGIN_NAME;
    
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
  
  /**
   * detect an enclosure character
   *
   * @todo experiment with doing this with a regex using a backreference 
   *       counting repetitions of first and last character matches
   *
   * @param string $csv_file path to a csv file to read and analyze
   * @return string the best guess enclosure character
   */
  protected function _detect_enclosure($csv_file) {
    $post_enclosure = filter_input(INPUT_POST, 'enclosure_character', FILTER_SANITIZE_STRING);
    if (empty($post_enclosure) && $post_enclosure !== 'auto' ) {
      return $post_enclosure;
    } else {
      return parent::_detect_enclosure($csv_file);
    }
  }

  /**
   * determines the delimiter character in the CSV file
   * 
   * @param string $csv_file the CSV file to scan for a delimiter
   * @return string the delimiter
   */
  protected function _detect_delimiter($csv_file) {
    $post_delimiter = filter_input(INPUT_POST, 'delimiter_character', FILTER_SANITIZE_STRING);
    if (empty($post_delimiter) && $post_delimiter !== 'auto' ) {
      return $post_delimiter;
    } else {
      return parent::_detect_delimiter($csv_file);
    }
  }
  
  /*
   * provides the current matching record policy
   * 
   * @return string
   */
  
  
}