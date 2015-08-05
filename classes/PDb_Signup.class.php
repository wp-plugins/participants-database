<?php
/*
 * prints a signup form
 * provides user feedback
 * emails a receipt and a notification
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdeign@xnau.com>
 * @copyright  2015 xnau webdesign
 * @license    GPL2
 * @version    0.9
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    xnau_FormElement class, Shortcode class
 */
if ( ! defined( 'ABSPATH' ) ) die;
class PDb_Signup extends PDb_Shortcode {
  /**
   *
   * @var bool holds the submission status: false if the form has not been submitted
   */
  var $submitted = false;
  /**
   *
   * @var string the user's email address
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
   * @param array $shortcode_atts   this array supplies the display parameters for the instance
   *
   */
  public function __construct($shortcode_atts) {

    // define shortcode-specific attributes to use
    $shortcode_defaults = array(
        'module' => 'signup',
        'submit_button' => Participants_Db::plugin_setting('signup_button_text'),
        'edit_record_page' => Participants_Db::plugin_setting('registration_page'),
    );
    /*
     * status values: normal (signup form submission) or multipage
     */
    $form_status = $this->get_form_status();
    
    /*
     * get the record ID from the last submission or current multiform
     */
			$this->participant_id = Participants_Db::$session->get('pdbid');
    
      /*
       * if we've opened a regular signup form while in a multipage session, treat it 
       * as a normal signup form and terminate the multipage session
       */
    if ($shortcode_atts['module'] === 'signup' && $this->participant_id !== false && !isset($shortcode_atts['action']) && Participants_Db::is_multipage_form()) {
      $this->participant_id = false;
      $this->_clear_multipage_session();
    }
    /*
     * if no ID is set, no submission has been received
     */
    if ($this->participant_id === false) {
    	if (filter_input(INPUT_GET, 'm') === 'r' || $shortcode_atts['module'] == 'retrieve') {
	      /*
	       * we're proceesing a link retrieve request
	       */
	      $shortcode_atts['module'] = 'retrieve';
        add_filter('pdb-before_field_added_to_iterator', array($this, 'allow_readonly_fields_in_form'));
      }
      if ($shortcode_atts['module'] == 'signup') {
        /*
         * we're showing the signup form
         */
        $this->participant_values = Participants_Db::get_default_record();
      }
    } else {
      
      /*
       * if we arrive here, the form has been submitted and is complete or is a multipage 
       * form and we've come back to the signup shortcode before the form was completed: 
       * in which case we show the saved values from the record
       */
      $this->participant_values = Participants_Db::get_participant($this->participant_id);
      
      if ($this->participant_values && ($form_status === 'normal' || ($shortcode_atts['module'] == 'thanks' && Participants_Db::is_multipage_form()))) {
        /*
         * the submission is successful, clear the session and set the submitted flag
       */
        $this->_clear_multipage_session();
        $this->submitted = true;
        $shortcode_atts['module'] = 'thanks';
      }
      $shortcode_atts['id'] = $this->participant_id;
    }

    // run the parent class initialization to set up the $shortcode_atts property
    parent::__construct($shortcode_atts, $shortcode_defaults);

    // set up the signup form email preferences
    $this->_set_email_prefs();

    // set the action URI for the form
    $this->_set_submission_page();

    // set up the template iteration object
    $this->_setup_iteration();

    if ($this->submitted) {

      /*
       * filter provides access to the freshly-stored record and the email and 
       * thanks message properties so user feedback can be altered.
       * 
       * filter: pdb-before_signup_thanks
       */
      if (has_filter(Participants_Db::$prefix . 'before_signup_thanks')) {

        $signup_feedback_props = array('recipient', 'receipt_subject', 'receipt_body', 'notify_recipients', 'notify_subject', 'notify_body', 'thanks_message', 'participant_values');
        $signup_feedback = new stdClass();
        foreach ($signup_feedback_props as $prop) {
          $signup_feedback->$prop = &$this->$prop;
        }

        apply_filters(Participants_Db::$prefix . 'before_signup_thanks', $signup_feedback, $this->get_form_status());
      }

      $this->_send_email();

      // form has been submitted, close it
      Participants_Db::$session->clear('form_status');
      
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
   * sets up the hidden fields array
   * 
   * in this class, this simply adds all defined hidden fields
   * 
   * @return null
   */
  protected function _setup_hidden_fields() {
    foreach (Participants_Db::$fields as $field) {
      if ($field->form_element === 'hidden' && $field->signup) {
        $this->_set_field_value($field);
        $this->hidden_fields[$field->name] = $field->value;
      }
    }
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
    $this->recipient = @$this->participant_values[Participants_Db::plugin_setting('primary_email_address_field')];

  }

  /**
   * sets the form submission page
   * 
   * if the "action" attribute is not set in the shortcode, use the "thanks page" 
   * setting if set
   */
  protected function _set_submission_page()
  {

    $form_status = $this->get_form_status();
    $this->submission_page = false;
    if (!empty($this->shortcode_atts['action'])) {
      $this->submission_page = Participants_Db::find_permalink($this->shortcode_atts['action']);
      if ($this->submission_page !== false) {
        $form_status = 'multipage-signup';
      }
    }
    if (!$this->submission_page) {
      if (Participants_Db::plugin_setting('signup_thanks_page', 'none') != 'none') { 
      	$this->submission_page = get_permalink(Participants_Db::plugin_setting('signup_thanks_page'));
      }
    }
    if ($this->submission_page === false) {
      // the signup thanks page is not set up, so we submit to the page the form is on
      $this->submission_page = $_SERVER['REQUEST_URI'];
    }
    $this->set_form_status($form_status);
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
      echo $open_tag . '<a href="' . Participants_Db::add_uri_conjunction($retrieve_link) . 'm=r">' . Participants_Db::set_filter('translate_string', $linktext) . '</a>' . $close_tag;
    }
  }

  /**
   * prints a thank you note
   */
  private function get_thanks_message() {

    $this->output = empty($this->participant_values) ? '' : $this->_proc_tags($this->thanks_message);
    unset($_POST);
    return $this->output;
  }

  /**
   * sends the notification and receipt emails
   * 
   * this handles both signups and updates using multi-page forms
   *
   */
  private function _send_email() {
  	$action = $this->get_form_status();
  	
		switch ($action) {
      case 'multipage-update':
      	// this is a multipage record form
	      if ($this->send_notification) {
	        $this->_do_update_notify();
	      }
	      break;
      case 'multipage-signup':
				// this is a multipage signup form
	      if ($this->send_notification) {
					$this->_do_notify();
	      }
	      if ($this->send_reciept) {
					$this->_do_receipt();
				}
				break;
			case 'normal':
			default:
				if (filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING) === 'update') {
					// this is a record form using a thanks page
		      if ($this->send_notification) {
		        $this->_do_update_notify();
		      }
				} else {
					// this is a normal signup form
		      if ($this->send_notification) {
						$this->_do_notify();
		      }
		      if ($this->send_reciept) {
						$this->_do_receipt();
					}
				}
	      break;
		}
  }

  /**
   * sends a user receipt email
   */
  private function _do_receipt() {
    
    if (filter_var($this->recipient, FILTER_VALIDATE_EMAIL) === false) {
      error_log(Participants_Db::$plugin_title.': no valid email address was found for the user receipt email, mail could not be sent.');
      return NULL;
    }

    /**
     * filter
     * 
     * pdb-receipt_email_template 
     * pdb-receipt_email_subject
     * 
     * @param string email template
     * @param array of current record values
     * 
     * @return string template
     */
    $this->_mail(
            $this->recipient, 
            $this->_proc_tags(Participants_Db::set_filter('receipt_email_subject', $this->receipt_subject, $this->participant_values)), 
            Participants_Db::process_rich_text($this->_proc_tags(Participants_Db::set_filter('receipt_email_template', $this->receipt_body, $this->participant_values)))
    );
  }

  /**
   * sends a new signup notification email to the admin
   */
  private function _do_notify() {

    $this->_mail(
            $this->notify_recipients, $this->_proc_tags($this->notify_subject), Participants_Db::process_rich_text($this->_proc_tags($this->notify_body))
    );
  }

  /**
   * sends an update notification email to the admin
   */
  private function _do_update_notify() {

    $this->_mail(
            $this->notify_recipients, 
            $this->_proc_tags(Participants_Db::$plugin_options['record_update_email_subject']), 
            Participants_Db::process_rich_text($this->_proc_tags(Participants_Db::$plugin_options['record_update_email_body']))
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
   * changes the readonly status of internal fields used in the retrieve form
   * 
   * @param $field a PDb_Field_Item object
   */
  public function allow_readonly_fields_in_form($field) {
    if ($field->group !== 'internal') return $field;
    $field->readonly = 0;
    return $field;
  }
  
  /**
   * clears the multipage form session values
   */
  function _clear_multipage_session() {
    Participants_Db::$session->clear('pdbid');
    Participants_Db::$session->clear('captcha_vars');
    Participants_Db::$session->clear('captcha_result');
  }

}