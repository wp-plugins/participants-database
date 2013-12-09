<?php

/*
 * class for displaying an editable record on the frontend with the [pdb_record] shortcode
 *
 */

class PDb_Record extends PDb_Shortcode {

  // class for the wrapper
  var $wrap_class = 'participant-record';

  // methods

  /**
   * initializes the record edit object
   */
  public function __construct($shortcode_atts) {

    // define shortcode-specific attributes to use
    $add_atts = array(
        'module' => 'record',
        'class' => 'edit-participant ' . $this->wrap_class,
    );

    // run the parent class initialization to set up the parent methods 
    parent::__construct($shortcode_atts, $add_atts);

    // set the action URI for the form
    $this->_set_submission_page();

    if (false === $this->shortcode_atts['record_id']) {

      $this->_not_found();
    } else {

      $this->participant_id = $this->shortcode_atts['record_id'];

      $this->participant_values = Participants_Db::get_participant($this->participant_id);

      if (false === $this->participant_values) {

        $this->_not_found();
      } else {

        // update the access timestamp
        Participants_Db::set_record_access($this->participant_id);

        $this->_get_validation_errors();

        $this->_setup_iteration();

        $this->_print_from_template();
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
  public static function print_form($params) {

    $record = new PDB_Record($params);

    return $record->output;
  }

  /**
   * includes the shortcode template
   */
  protected function _include_template() {

    include $this->template;
  }

  /**
   * prints the form header and hidden fields
   */
  public function print_form_head() {

    $hidden = array(
        'action' => 'update',
        'id' => $this->participant_id,
        'pid' => $this->participant_values['private_id'],
    );

    $this->_print_form_head($hidden);
  }

  /**
   * prints the submit button
   *
   * @param string $class a classname for the submit button, defaults to 'button-primary'
   * 
   */
  private function print_submit_button($class = 'button-primary') {

    $pattern = '<input class="%s pdb-submit" type="submit" value="%s" name="save" >';

    printf($pattern, $class, $this->options['save_changes_button']);
  }

  /**
   * prints a 'save changes' label according to the plugin setting
   */
  private function print_save_changes_label() {

    echo $this->options['save_changes_label'];
  }

}

?>