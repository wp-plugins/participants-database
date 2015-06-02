<?php
/**
 * handles the presentation of the editable frontend record
 * 
 *  * 
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2015 xnau webdesign
 * @license    GPL2
 * @version    1.6
 * @link       http://xnau.com/wordpress-plugins/
 * 
 */
if ( ! defined( 'ABSPATH' ) ) die;
/*
 * class for displaying an editable record on the frontend with the [pdb_record] shortcode
 *
 */
class PDb_Record extends PDb_Shortcode {

  /**
   * @var string class for the wrapper
   */
  var $wrap_class = 'participant-record';
  /**
   * @var string the originating page in a multipage form
   */
  var $previous_multipage;

  // methods

  /**
   * initializes the record edit object
   */
  public function __construct($shortcode_atts) {

    // define shortcode-specific attributes to use
    $add_atts = array(
        'module' => 'record',
        'class' => 'edit-participant ' . $this->wrap_class,
        'submit_button' => Participants_Db::plugin_setting('save_changes_button'),
        /*
         * @version 1.6
         * if true, "no record" message is shown using the template
         */
        'no_record_template' => '',
    );
    // run the parent class initialization to set up the parent methods 
    parent::__construct($shortcode_atts, $add_atts);

    $this->_setup_multipage();

    // set the action URI for the form
    $this->_set_submission_page();

    if (false === $this->shortcode_atts['record_id']) {

      $this->_not_found();
    } else {

      $this->participant_id = $this->shortcode_atts['record_id'];

      $this->participant_values = Participants_Db::get_participant($this->participant_id);

      if ($this->participant_values === false) {

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
   * @param string $button_value submit button text
   * 
   */
  public function print_submit_button($class = 'button-primary', $button_value = false) {
    
    if (!empty($this->participant_id)) {
    
			$button_value = $button_value ? $button_value : $this->shortcode_atts['submit_button'];

			$pattern = '<input class="%s pdb-submit" type="submit" value="%s" name="save" >';

			printf($pattern, $class, $button_value);
		}
  }

  
  /**
   * prints a "next" button for multi-page forms
   * 
   * this is simply an anchor to the thanks page
   * 
   * @return string
   */
  public function print_back_button() {
    if (strlen($this->previous_multipage) > 0) {
      printf('<a type="button" class="button button-secondary" href="%s" >%s</a>', $this->previous_multipage, __('back', 'participants-database'));
    }
  }

  /**
   * prints a 'save changes' label according to the plugin setting
   */
  private function print_save_changes_label() {

    echo Participants_Db::plugin_setting('save_changes_label');
  }


  /**
   * outputs a "record not found" message
   *
   * the message is defined in the plugin settings
   */
  protected function _not_found() {
    
    if (Participants_Db::plugin_setting_is_true('no_record_use_template')) {

        $this->_print_from_template();
      
    } else {
      $this->output = empty(Participants_Db::$plugin_options['no_record_error_message']) ? '' : '<p class="alert alert-error">' . Participants_Db::plugin_setting('no_record_error_message') . '</p>';
    }
  }
  
  

  /**
   * sets the form submission page
   */
  protected function _set_submission_page()
  {

    $form_status = 'normal';
    if (!empty($this->shortcode_atts['action'])) {
      $this->submission_page = Participants_Db::find_permalink($this->shortcode_atts['action']);
      if ($this->submission_page !== false) {
        $form_status = 'multipage';
      }
    }
    if (!$this->submission_page) {
      $this->submission_page = $_SERVER['REQUEST_URI'];
    }
    $this->set_form_status($form_status);
  }
  
  /**
   * sets up the multipage referral
   * 
   * @retrun null
   */
  protected function _setup_multipage() {
    $this->previous_multipage = Participants_Db::$session->get('previous_multipage', '');
    if (strlen($this->previous_multipage) === 0) {
      Participants_Db::$session->clear('pdbid');
    }
  }
}