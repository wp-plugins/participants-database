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
        'module' => 'signup'
    );
    
    $sent = true; // start by assuming the notification email has been sent
    /*
     * this is set true if the form is a multi-page form. This is so a multi-page form 
     * can't be completed by skipping back to the signup form, they must go to a page 
     * with a thanks shortcode
     */
    $redirected = false;
    if ($shortcode_atts['module'] != 'thanks' && ((isset($shortcode_atts['action']) && $shortcode_atts['action'] !== ''))) {
      // this is set true if the signup form is supposed to be redirected after the submission
      $redirected = true;
    }

    if ((isset($_GET['m']) && $_GET['m'] == 'r') || $shortcode_atts['module'] == 'retrieve') {
      /*
       * we're proceesing a link retrieve request
       */
      $shortcode_atts['module'] = 'retrieve';
    } elseif ($this->participant_id = Participants_Db::$session->get('pdbid')) {
      
      /*
       * the submission is successful, clear the session
       */
      Participants_Db::$session->clear('pdbid');
      Participants_Db::$session->clear('captcha_vars');
      Participants_Db::$session->clear('captcha_result');
      $this->participant_values = Participants_Db::get_participant($this->participant_id);
      if ($this->participant_values && !$redirected) {
        
        // check the notification sent status of the record
        $sent = $this->check_sent_status($this->participant_id);
        $this->submitted = true;
        $shortcode_atts['module'] = 'thanks';
      }
      $shortcode_atts['id'] = $this->participant_id;
    } elseif ($shortcode_atts['module'] == 'signup') {
      /*
       * we're showing the signup form
       */
      $this->participant_values = Participants_Db::get_default_record();
    } else {
      /*
       * there was no type set
       */
      return;
    }

    // run the parent class initialization to set up the $shortcode_atts property
    parent::__construct($shortcode_atts, $shortcode_defaults);

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
      if (has_filter(Participants_Db::$prefix . 'before_signup_thanks')) {

        $signup_feedback_props = array('recipient', 'receipt_subject', 'receipt_body', 'notify_recipients', 'notify_subject', 'notify_body', 'thanks_message', 'participant_values');
        $signup_feedback = new stdClass();
        foreach ($signup_feedback_props as $prop) {
          $signup_feedback->$prop = &$this->$prop;
        }

        apply_filters(Participants_Db::$prefix . 'before_signup_thanks', $signup_feedback);
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
      if (isset($this->options['signup_thanks_page']) && $this->options['signup_thanks_page'] != 'none') { 
      $this->submission_page = get_permalink($this->options['signup_thanks_page']);
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

  public function print_submit_button($class = 'button-primary', $value = false) {

    PDb_FormElement::print_element(array(
        'type' => 'submit',
        'value' => ($value === false ? $this->options['signup_button_text'] : $value),
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
    
    if ($this->options['show_retrieve_link'] != 0) {
      $retrieve_link = $this->options['link_retrieval_page'] !== 'none' ? get_permalink($this->options['link_retrieval_page']) : $_SERVER['REQUEST_URI'];
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
    
    $email_field = Participants_Db::$plugin_options['primary_email_address_field'];

    if (!isset($this->participant_values[$email_field]) || empty($this->participant_values[$email_field])) {
      error_log(__METHOD__.' no valid email address was found, mail could not be sent.');
      return NULL;
    }

    $this->_mail(
            $this->participant_values[$email_field], 
            $this->_proc_tags($this->receipt_subject), 
            Participants_Db::process_rich_text($this->_proc_tags($this->receipt_body))
    );
  }

  // sends a notification email
  private function _do_notify() {

    $this->_mail(
            $this->notify_recipients, $this->_proc_tags($this->notify_subject), $this->_proc_tags($this->notify_body)
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
            WHERE v.name = "' . $this->options['retrieve_link_identifier'] . '" 
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

    if ($this->options['html_email'])
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
    } else return true;
  }

}