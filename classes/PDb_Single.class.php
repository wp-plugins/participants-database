<?php
/*
 * class for displaying an single record on the frontend with the [pdb_single] shortcode
 *
 */

 class PDb_Single extends PDb_Shortcode {
  
	// a string identifier for the class
  var $module = 'single';
  
  // methods
  
  /**
   * initializes the record edit object
   */
  public function __construct( $params ) {
    
		
		// define shortcode-specific attributes to use
		$add_atts = array(
                      'id' => '',
                      'class' => $this->wrap_class,
                      'term' => 'id' ,
                      );
    
    // run the parent class initialization to set up the parent methods 
    parent::__construct( $this, $params, $add_atts );
    
    /*
     * determine the ID of the record to show
     *
     * 'pdb' is a generic $_GET variable that indexes the record according to
     * the 'term' value, which defaults to 'id'
     *
     */
    if ( isset( $this->shortcode_atts['id'] ) and ! empty( $this->shortcode_atts['id'] ) ) {
      $id = $this->shortcode_atts['id'];
    } else $id = 0;
		// override the shortcode att if the value is in the URI
    if ( isset( $_GET['pdb'] ) ) {
      $id = $_GET['pdb'];
    }
    $matched_ids = Participants_Db::get_record_id_by_term( $this->shortcode_atts['term'], $id );
    
    if ( false === $matched_ids ) {
      
      $this->_not_found();
      
    } else {
      
      /*
       * at this point, we have either a single value or an array of values. We
       * test each one for a valid record and display the record if found
       */
      foreach ( (array) $matched_ids as $id ) {
      
        $this->participant_values = Participants_Db::get_participant( $id );
    
        if ( false === $this->participant_values ) {
          
          $this->_not_found();
          
        } else {
              
          $this->participant_id = $id;
      
          $this->_setup_iteration();
          
          $this->_print_from_template();
          
        }
        
      }
    
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
		
		if ( ! isset( self::$instance ) ) self::$instance = new PDb_Single( $params );
		
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