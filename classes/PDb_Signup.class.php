<?php
/*
 * prints a signup form
 * adds a record to the database
 * emails a receipt and a notification
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdeign@xnau.com>
 * @copyright  2011,2013 xnau webdesign
 * @license    GPL2
 * @version    0.7
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    xnau_FormElement class, Shortcode class
 */

class PDb_Signup extends PDb_Shortcode {
  /**
   *
   * @var bool holds the submission status: false if the form has not been submitted
   */
  var $submitted = false;
  /**
   *
   * @var array holds the recipient values after a form submission
   */
  var $recipient;
  /**
   * @var bool reciept email sent status
   */
  var $send_reciept;
  /**
   *
   * @var string the receipt subject line
   */
  var $receipt_subject;
  /**
   *
   * @var string holds the body of the signup receipt email
   */
  var $receipt_body;
  /**
   * TODO: redundant?
   * @var bool whether to send the notification email
   */
  var $send_notification;
  /**
   *
   * @var array holds the notify recipient emails
   */
  public $notify_recipients;
  /**
   *
   * @var string the notification subject line
   */
  var $notify_subject;
  /**
   *
   * @var string holds the body of the notification email
   */
  var $notify_body;
  /**
   *
   * @var string holds the current email body
   */
  var $current_body;
  /**
   *
   * @var string thank message body
   */
  var $thanks_message;
  /**
   *
   * @var string header added to receipts and notifications
   */
  private $email_header;
  /**
   *
   * @var array holds the submission values
   */
  private $post = array();
  /**
   *
   * @var array error messages
   */
  private $errors = array();

  // methods

  //

	/**
   * instantiates the signup form object
   *
   * this class is called by a WP shortcode
   *
   * @param array $shortcode_atts   this array supplies the display parameters for the instance
   *                 'title'   string displays a title for the form (default none)
   *
   */
  public function __construct($shortcode_atts) {

    // define shortcode-specific attributes to use
    $shortcode_defaults = array(
        'module' => 'signup',
        'submit_button' => Participants_Db::plugin_setting('signup_button_text'),
        'edit_record_page' => Participants_Db::plugin_setting('registration_page'),
    );
    
   //Participants_Db::$session->clear('form_status');
    
    $form_status = Participants_Db::$session->get('form_status') ? Participants_Db::$session->get('form_status') : 'normal';

    $this->participant_id = Participants_Db::$session->get('pdbid');

    if (filter_input(INPUT_GET, 'm') === 'r' || $shortcode_atts['module'] == 'retrieve') {
      /*
       * we're proceesing a link retrieve request
       */
      $shortcode_atts['module'] = 'retrieve';
    } elseif ($this->participant_id !== false) {
      /*
       * if we arrive here, the form has been submitted and is complete or is a multipage 
       * form and we've come back to the signup shortcode before the form was completed: 
       * in which case we show the saved values from the record
       */
      $this->participant_values = Participants_Db::get_participant($this->participant_id);
      if ($this->participant_values && ($form_status === 'normal' || ($shortcode_atts['module'] == 'thanks' && $form_status === 'multipage'))) {
        /*
       * the submission is successful, clear the session
       */
      Participants_Db::$session->clear('pdbid');
      Participants_Db::$session->clear('captcha_vars');
      Participants_Db::$session->clear('captcha_result');
        $this->submitted = true;
        $shortcode_atts['module'] = 'thanks';
      }
      $shortcode_atts['id'] = $this->participant_id;
    } elseif ($shortcode_atts['module'] == 'signup') {
      /*
       * we're showing the signup form
       */
      $this->participant_values = Participants_Db::get_default_record();
      $form_status = (isset($shortcode_atts['action']) && !empty($shortcode_atts['action'])) ? 'multipage' : 'normal';
      Participants_Db::$session->set('form_status', $form_status);
      
    } else {
      /*
       * there was no type set.
       */
      error_log(__METHOD__.' doing nothing');
      return;
    }

    // run the parent class initialization to set up the $shortcode_atts property
    parent::__construct($shortcode_atts, $shortcode_defaults);

    $this->registration_page = Participants_Db::get_record_link($this->participant_values['private_id'], $this->shortcode_atts['edit_record_page']);

    // set up the signup form email preferences
    $this->_set_email_prefs();

    // set the action URI for the form
    $this->_set_submission_page();

    // set up the template iteration object
    $this->_setup_iteration();

    if ($this->submitted) {

      // form has been submitted, close it
      Participants_Db::$session->set('form_status', 'complete');

      /*
       * filter provides access to the freshly-stored record and the email and thanks message properties so user feedback can be altered.
       */
      if (has_filter(Participants_Db::$prefix . 'before_signup_thanks')) {

        $signup_feedback_props = array('recipient', 'receipt_subject', 'receipt_body', 'notify_recipients', 'notify_subject', 'notify_body', 'thanks_message', 'participant_values');
        $signup_feedback = new stdClass();
        foreach ($signup_feedback_props as $prop) {
          $signup_feedback->$prop = &$this->$prop;
        }

        apply_filters(Participants_Db::$prefix . 'before_signup_thanks', $signup_feedback);
      }

        $this->_send_email();
    }
    // print the shortcode output
    $this->_print_from_template();
  }

  /**
   * prints a signup form called by a shortcode
   *
   * this function is called statically to instantiate the Signup object,
   * which captures the processed template output and returns it for display
   *
   * @param array $params parameters passed by the shortcode
   * @return string form HTML
   */
  public static function print_form($params) {

    self::$instance = new PDb_Signup($params);

    return self::$instance->output;
  }

  /**
   * includes the shortcode template
   */
  protected function _include_template() {

    include $this->template;
  }

  /**
   * sets up the signup form email preferences
   */
  private function _set_email_prefs() {

    $this->send_reciept = Participants_Db::plugin_setting('send_signup_receipt_email');
    $this->send_notification = Participants_Db::plugin_setting('send_signup_notify_email');
    $this->notify_recipients = Participants_Db::plugin_setting('email_signup_notify_addresses');
    $this->notify_subject = Participants_Db::plugin_setting('email_signup_notify_subject');
    $this->notify_body = Participants_Db::plugin_setting('email_signup_notify_body');
    $this->receipt_subject = Participants_Db::plugin_setting('signup_receipt_email_subject');
    $this->receipt_body = Participants_Db::plugin_setting('signup_receipt_email_body');
    $this->thanks_message = Participants_Db::plugin_setting('signup_thanks');
    $this->email_header = Participants_Db::$email_headers;
  }

  /**
   * sets the form submission page
   * 
   * if the "action" attribute is not set in the shortcode, use the "thanks page" 
   * setting if set
   */
  protected function _set_submission_page()
  {

    if (!empty($this->shortcode_atts['action'])) {
      $this->submission_page = Participants_Db::find_permalink($this->shortcode_atts['action']);
    }
    if (!$this->submission_page) {
      if (Participants_Db::plugin_setting('signup_thanks_page', 'none') != 'none') { 
      $this->submission_page = get_permalink(Participants_Db::plugin_setting('signup_thanks_page'));
      }
    }
    if (!$this->submission_page) {

      // the signup thanks page is not set up, so we submit to the page the form is on
      $this->submission_page = $_SERVER['REQUEST_URI'];
    }
  }

  /**
   * prints a signup form top
   * 
   * @param array array of hidden fields supplied in the template
   */
  public function print_form_head($hidden = '') {

    echo $this->_print_form_head($hidden);
  }
  /**
   * prints the submit button
   *
   * @param string $class a classname for the submit button, defaults to 'button-primary'
   * @param string $button_value submit button text
   * 
   */
  public function print_submit_button($class = 'button-primary', $button_value = false) {

    $button_value = $button_value ? $button_value : $this->shortcode_atts['submit_button'];

    PDb_FormElement::print_element(array(
        'type' => 'submit',
        'value' => $button_value,
        'name' => 'submit_button',
        'class' => $class . ' pdb-submit',
        'module' => $this->module,
    ));
  }
  
  
  /**
   * prints a private link retrieval link
   * 
   * @param string $linktext
   */
  public function print_retrieve_link($linktext = '', $open_tag = '<span class="pdb-retrieve-link">', $close_tag = '</span>') {
    
    $linktext = empty($linktext) ? Participants_Db::$plugin_options['retrieve_link_text'] : $linktext;
    
    if (Participants_Db::plugin_setting_is_true('show_retrieve_link')) {
      $retrieve_link = Participants_Db::plugin_setting('link_retrieval_page') !== 'none' ? get_permalink(Participants_Db::plugin_setting('link_retrieval_page')) : $_SERVER['REQUEST_URI'];
      echo $open_tag . '<a href="' . Participants_Db::add_uri_conjunction($retrieve_link) . 'm=r">' . $linktext . '</a>' . $close_tag;
    }
  }

  /**
   * prints a thank you note
   */
  private function get_thanks_message() {

    $this->output = $this->_proc_tags($this->thanks_message);
    unset($_POST);
    return $this->output;
  }

  /**
   * sends the notification and receipt emails for a signup submission
   *
   */
  private function _send_email() {

    if ($this->send_notification)
      $this->_do_notify();
    if ($this->send_reciept)
      $this->_do_receipt();
  }

  // sends a receipt email
  private function _do_receipt() {
    
    $recipient = @$this->participant_values[Participants_Db::$plugin_options['primary_email_address_field']];

    if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
      error_log(__METHOD__.' no valid email address was found for the user receipt email, mail could not be sent.');
      return NULL;
    }

    $this->_mail(
            $recipient, 
            $this->_proc_tags($this->receipt_subject), 
            Participants_Db::process_rich_text($this->_proc_tags($this->receipt_body))
    );
  }

  // sends a notification email
  private function _do_notify() {

    $this->_mail(
            $this->notify_recipients, $this->_proc_tags($this->notify_subject), Participants_Db::process_rich_text($this->_proc_tags($this->notify_body))
    );
  }

  /**
   * grab the defined identifier field for display in the retrieve private link form
   * 
   * @global type $wpdb
   * @return string
   */
  function get_retrieve_field() {

    global $wpdb;

    $columns = array('name', 'title', 'form_element');

    $sql = 'SELECT v.' . implode(',v.', $columns) . ' 
            FROM ' . Participants_Db::$fields_table . ' v 
            WHERE v.name = "' . Participants_Db::plugin_setting('retrieve_link_identifier') . '" 
            ';

    return $wpdb->get_results($sql, OBJECT_K);
  }

  /**
   * sends a mesage through the WP mail handler function
   *
   * @todo these email functions should be handled by an email class
   *
   * @param string $recipients comma-separated list of email addresses
   * @param string $subject    the subject of the email
   * @param string $body       the body of the email
   *
   */
  private function _mail($recipients, $subject, $body) {

    if (WP_DEBUG) error_log(__METHOD__.'
      
header:'.$this->email_header.'
to:'.$recipients.' 
subj.:'.$subject.' 
message:
'.$body 
            );

    $this->current_body = $body;

    if (Participants_Db::plugin_setting('html_email'))
      add_action('phpmailer_init', array($this, 'set_alt_body'));

    $sent = wp_mail($recipients, $subject, $body, $this->email_header);

    if (false === $sent)
      error_log(__METHOD__ . ' sending failed for: ' . $recipients);
  }

  /**
   * set the PHPMailer AltBody property with the text body of the email
   *
   * @param object $phpmailer an object of type PHPMailer
   * @return null
   */
  public function set_alt_body(&$phpmailer) {

    if (is_object($phpmailer))
      $phpmailer->AltBody = $this->_make_text_body($this->current_body);
  }

  /**
   * strips the HTML out of an HTML email message body to provide the text body
   *
   * this is a fairly crude conversion here. I should include some kind of library
   * to do this properly.
   *
   * @param string $HTML the HTML body of the email
   * @return string
   */
  private function _make_text_body($HTML) {

    return strip_tags(preg_replace('#(</(p|h1|h2|h3|h4|h5|h6|div|tr|li) *>)#i', "\r", $HTML));
  }
  
  /**
   * updates the signup transient
   * 
   * "true" here indicates that the record signup notification has been sent
   * 
   * @param int $id the record id
   * @param bool $state the state to set the transient value to
   * @return null
   */
  public static function update_sent_status($id, $state) {
    $check_sent[$id] = $state;
    $sent_records = get_transient(Participants_Db::$prefix . 'signup-email-sent');
    if (is_array($sent_records)) $sent_records = $check_sent + $sent_records;
    else $sent_records = $check_sent;
    /* 
     * expires after one year, we need to do this in order to avoid the transient 
     * being needlessly autoloaded
     */
    set_transient(Participants_Db::$prefix . 'signup-email-sent', $sent_records, (365 * 60 * 60 * 12));
  }
  
  /**
   * checks the status of a signup email status transient
   * 
   * @param int $id the id of the record to check
   * @return bool the stored status of the record
   */
  public static function check_sent_status($id)
  {
    $check_sent = get_transient(Participants_Db::$prefix . 'signup-email-sent');
    if ($check_sent === false or !isset($check_sent[$id]) or $check_sent[$id] === false) {
      return false;
    } else {
      return true;
    }
  }

}