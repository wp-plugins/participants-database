<?php
/*
 * class for displaying an single record on the frontend with the [pdb_single] shortcode
 *
 */

 class PDb_Single extends PDb_Shortcode {
  
  /**
   * initializes the record edit object
   */
  public function __construct( $shortcode_atts ) {
    
		
		// define shortcode-specific attributes to use
		$add_atts = array(
        'module' => 'single',
        'class' => $this->wrap_class,
        'term' => 'id',
    );
    
    // run the parent class initialization to set up the parent methods 
    parent::__construct($shortcode_atts, $add_atts);
    
    /*
     * determine the ID of the record to show
     *
     * 'pdb' is a generic $_GET variable that indexes the record according to
     * the 'term' value, which defaults to 'id'
     *
     */
    if ( $this->shortcode_atts['record_id'] !== false ) {
      $id = $this->shortcode_atts['record_id'];
    } else $id = 0;
		// override the shortcode att if the value is in the URI
    if ( isset( $_GET['pdb'] ) ) {
      $id = $_GET['pdb'];
    }
    $record_id = Participants_Db::get_record_id_by_term( $this->shortcode_atts['term'], $id );
    
    if ( false === $record_id ) {
      
      $this->_not_found();
      
    } else {
      
      $this->participant_values = Participants_Db::get_participant( $record_id );
      $this->participant_id = $record_id;
          $this->_setup_iteration();
          $this->_print_from_template();
          
    
    }
    
  }

	/**
	 * prints a signup form called by a shortcode
	 *
	 * this function is called statically to instantiate the Signup object,
	 * which captures the output and returns it for display
	 *
	 * @param array $params parameters passed by the shortcode
	 * @return string form HTML
	 */
	public static function print_record( $params ) {
		
		self::$instance = new PDb_Single( $params );
		
		return self::$instance->output;
		
	}
  
  /**
   * includes the shortcode template
   */
  protected function _include_template() {
    
    // set some template variables
    $id = $this->participant_id;
    
    include $this->template;
		
  }
  
 }
 ?>