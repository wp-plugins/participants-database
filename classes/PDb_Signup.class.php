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
 * @version    0.4
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    FormElement class, Shortcode class
 */

class PDb_Signup extends PDb_Shortcode {

  // a string identifier for the class
  var $module = 'signup';
  // holds the target page for the submission
  var $submission_page;
  // holds the submission status: false if the form has not been submitted
  var $submitted = false;
  // holds the recipient values after a form submission
  var $recipient;
  // boolean to send the reciept email
  var $send_reciept;
  // the receipt subject line
  var $receipt_subject;
  // holds the body of the signup receipt email
  var $receipt_body;
  // boolean to send the notification email
  var $send_notification;
  // holds the notify recipient emails
  public $notify_recipients;
  // the notification subject line
  var $notify_subject;
  // holds the body of the notification email
  var $notify_body;
  // holds the current email body
  var $current_body;
  var $thanks_message;
  // header added to receipts and notifications
  private $email_header;
  // holds the submission values
  private $post = array();
  // error messages
  private $errors = array();

  // methods

  //

	/**
   * instantiates the signup form object
   *
   * this class is called by a WP shortcode
   *
   * @param array $params   this array supplies the display parameters for the instance
   *              'title'   string displays a title for the form (default none)
   *              'captcha' string type of captcha to include: none (default), math, image, word
   *
   */
  public function __construct($params) {

    // define shortcode-specific attributes to use
    $add_atts = array();

    /*
     * if we're coming back from a successful form submission, the id of the new
     * record will be present, otherwise, the id is set to the default record
     */
    if (isset($_SESSION['pdbid'])) {

      $this->participant_id = $_SESSION['pdbid'];
      unset($_SESSION['pdbid']); // clear the ID from the SESSION array
      $this->participant_values = Participants_Db::get_participant($this->participant_id);
      // check the notification sent status of the record
      $sent = $this->check_sent_status($this->participant_id);
      if ($this->participant_values) {
        $this->submitted = true;
        $params['type'] = 'thanks';
      }
      $add_atts['id'] = $this->participant_id;
    } elseif ($params['type'] == 'signup') {

      $this->participant_values = Participants_Db::get_default_record();
    }
    else
      return; // no type set, nothing to show.

    $this->module = $params['type'];

    // run the parent class initialization to set up the parent methods
    parent::__construct($this, $params, $add_atts);

    $this->registration_page = Participants_Db::get_record_link($this->participant_values['private_id']);

    // set up the signup form email preferences
    $this->_set_email_prefs();

    // set the action URI for the form
    $this->_set_submission_page();

    // set up the template iteration object
    $this->_setup_iteration();

    if ($this->submitted) {

      /*
       * filter provides access to the freshly-stored record and the email and thanks message properties so user feedback can be altered.
       */
      if (has_filter(Participants_Db::$css_prefix . 'before_signup_thanks')) {

        $signup_feedback_props = array('recipient', 'receipt_subject', 'receipt_body', 'notify_recipients', 'notify_subject', 'notify_body', 'thanks_message', 'participant_values');
        $signup_feedback = new stdClass();
        foreach ($signup_feedback_props as $prop) {
          $signup_feedback->$prop = &$this->$prop;
        }

        apply_filters(Participants_Db::$css_prefix . 'before_signup_thanks', $signup_feedback);
      }

      /*
       * check to see if the thanks email has been sent and send it if it has not
       */
      if ($sent === false) {

        $this->_send_email();

        // mark the record as sent
        $this->update_sent_status($this->participant_id, true);
      }
      else
        return false; // the thanks message and email have already been sent for this ID
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
  public function print_form($params) {

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

    $this->send_reciept = $this->options['send_signup_receipt_email'];
    $this->send_notification = $this->options['send_signup_notify_email'];
    $this->notify_recipients = $this->options['email_signup_notify_addresses'];
    $this->notify_subject = $this->options['email_signup_notify_subject'];
    $this->notify_body = $this->options['email_signup_notify_body'];
    $this->receipt_subject = $this->options['signup_receipt_email_subject'];
    $this->receipt_body = $this->options['signup_receipt_email_body'];
    $this->thanks_message = $this->options['signup_thanks'];
    $this->email_header = Participants_Db::$email_headers;
  }

  /**
   * sets the form submission page
   */
  protected function _set_submission_page() {

    if (isset($this->options['signup_thanks_page']) and $this->options['signup_thanks_page'] != 'none') {

      $this->submission_page = get_permalink($this->options['signup_thanks_page']);
    } else {

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

    echo $this->_print_form_head((array)$hidden);
  }

  public function print_submit_button($class = 'button-primary', $value = false) {

    FormElement::print_element(array(
        'type' => 'submit',
        'value' => ($value === false ? $this->options['signup_button_text'] : $value),
        'name' => 'submit_button',
        'class' => $class . ' pdb-submit',
        'module' => $this->module,
    ));
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
   * adds a captcha field to the signup form
   *
   * @param string $type selects the type of CAPTCHA to employ: none, math, color, reCaptcha
   */
  private function _add_captcha() {

    switch ($this->captcha_type) {

      case 'none';
      default;
        return false;
    }
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

    if (!isset($this->participant_values['email']) || empty($this->participant_values['email']))
      return NULL;

    $this->_mail(
            $this->participant_values['email'], $this->_proc_tags($this->receipt_subject), $this->_proc_tags($this->receipt_body)
    );
  }

  // sends a notification email
  private function _do_notify() {

    $this->_mail(
            $this->notify_recipients, $this->_proc_tags($this->notify_subject), $this->_proc_tags($this->notify_body)
    );
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

    error_log(__METHOD__.'
      
header:'.$this->email_header.'
to:'.$recipients.' 
subj.:'.$subject.' 
message:
'.$body 
            );

    $this->current_body = $body;

    if ($this->options['html_email'])
      //add_action('phpmailer_init', array($this, 'set_alt_body'));

    $sent = wp_mail($recipients, $subject, $body, $this->email_header);

    if (false === $sent)
      error_log(__METHOD__ . ' sending returned false');
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

    return strip_tags(preg_replace('#(</[p|h1|h2|h3|h4|h5|h6|div|tr|li]{1,3} *>)#i', "\r", $HTML));
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
    $sent_records = get_transient(Participants_Db::$css_prefix . 'signup-email-sent');
    if (is_array($sent_records)) $sent_records = $check_sent + $sent_records;
    else $sent_records = $check_sent;
    /* 
     * expires after one year...effectively never, but we need to do this in order 
     * to avoid the transient being needlessly autoloaded
     */
    set_transient(Participants_Db::$css_prefix . 'signup-email-sent', $sent_records, (365 * 60 * 60 * 12));
  }
  
  /**
   * checks the status of a signup transient
   * 
   * @param int $id the id of the record to check
   * @return bool the stored status of the record
   */
  public static function check_sent_status($id)
  {

    $check_sent = get_transient(Participants_Db::$css_prefix . 'signup-email-sent');
    if ($check_sent === false or !isset($check_sent[$id]) or $check_sent[$id] === false) {
      return false;
    } else return true;
  }

}