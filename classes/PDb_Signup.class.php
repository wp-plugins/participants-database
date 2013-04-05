<?php
/*
 * prints a signup form
 * adds a record to the database
 * emails a receipt and a notification
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdeign@xnau.com>
 * @copyright  2011 xnau webdesign
 * @license    GPL2
 * @version    0.3
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
	public function __construct( $params ) {
    
		// define shortcode-specific attributes to use
		$add_atts = array(
                      'type'   => 'signup',
                      );
    
		/*
     * if we're coming back from a successful form submission, the id of the new
     * record will be present, otherwise, the id is set to the default record
     */
    if ( isset( $_GET['id'] ) ) {
      
      $this->participant_id = $_GET['id'];
      $this->submitted = true;
      $this->participant_values = Participants_Db::get_participant( $this->participant_id );
      $add_atts['id'] = $this->participant_id;
      
    } else {
      
      $this->participant_values = Participants_Db::get_default_record();
      
    }
		
    // run the parent class initialization to set up the parent methods 
    parent::__construct( $this, $params, $add_atts );
    
    $this->module = $this->shortcode_atts['type'];
    
    $this->registration_page = Participants_Db::get_record_link( $this->participant_values['private_id'] );
    
    // set up the signup form email preferences
    $this->_set_email_prefs();
		
    // set the action URI for the form
		$this->_set_submission_page();

    // set up the template iteration object
    $this->_setup_iteration();
	
		if ( ! $this->submitted ) {

				/*
				 * no submission has been made: do we show the signup form?
				 *
				 * yes if there's no valid PID in the GET string
				 * but not if we're actually showing a [pdb_signup_thanks] shortcode 
				 * and there's no submission
				 *
				 * this is because we use the same code for both shortcodes
				 *
				 * we will get a no-show if we end up here with a valid ID, but 
				 * there's no [pdb_record] shortcode there.
				 */
				if (
						( 
						 ! isset( $_GET['pid'] )
						 ||
						 ( isset( $_GET['pid'] ) && false === Participants_Db::get_participant_id( $_GET['pid'] ) ) 
						)
						&&
						$this->module == 'signup'
					 )
				{
					// no submission; output the form
					$this->_print_from_template();
			
				}
			
		} elseif ( $this->submitted ) {
      
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
			
			// print the thank you note
			$this->_thanks();

			/*
       * check to see if the thanks email has been sent and send it if it has not
       */
			if ( 'sent' != get_transient( 'signup-'.$this->participant_id ) ) {
        
        $this->_send_email();
			
        // mark the record as sent to prevent duplicate emails
        set_transient( 'signup-'.$this->participant_id, 'sent', 120 );
        
      }
			
		}
		
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
	public function print_form( $params ) {
		
		if ( ! isset( self::$instance ) ) self::$instance = new PDb_Signup( $params );
		
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
  private function _set_submission_page() {
		
		if ( isset( $this->options['signup_thanks_page'] ) and $this->options['signup_thanks_page'] != 'none' ) {
			
			$this->submission_page = get_permalink( $this->options['signup_thanks_page'] );
			
		} else {
			
			// the signup thanks page is not set up, so we submit to the page the form is on
			
			$this->submission_page = $_SERVER['REQUEST_URI'];
			
		}
    
  }

  

  // prints a signup form top
  public function print_form_head() {
    ?>
    <form method="post" enctype="multipart/form-data" >
        <?php
        FormElement::print_hidden_fields( array(
                                                'action'=>'signup',
                                                'subsource'=>Participants_Db::PLUGIN_NAME,
                                                'shortcode_page' => basename( $_SERVER['REQUEST_URI'] ),
                                                'thanks_page' => $this->submission_page
                                                ) );

  }
	
	public function print_submit_button( $class = 'button-primary' ) {
		
		FormElement::print_element( array(
                                      'type'       => 'submit',
                                      'value'      => $this->options['signup_button_text'],
                                      'name'       => 'submit',
                                      'class'      => $class.' pdb-submit',
                                      ) );
	}
	
	/**
	 * prints a thank you note
	 */
	private function _thanks() {
		ob_start(); ?>
    
		<div class="<?php echo $this->wrap_class ?> signup-thanks">
      <?php echo $this->_proc_tags( $this->thanks_message ); ?>
		</div>
    
		<?php $this->output = ob_get_clean();
		
		unset( $_POST );
		
	}
	
	/**
	 * adds a captcha field to the signup form
	 *
	 * @param string $type selects the type of CAPTCHA to employ: none, math, color, reCaptcha
	 */
	private function _add_captcha() {
		
		switch ( $this->captcha_type ) {
			
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

		if ( $this->send_notification ) $this->_do_notify();
		if ( $this->send_reciept ) $this->_do_receipt();
	
	}

	// sends a receipt email
	private function _do_receipt() {
		
		if ( ! isset( $this->participant_values['email'] ) || empty( $this->participant_values['email'] ) ) return NULL;

		$this->_mail(
								 $this->participant_values['email'],
								 $this->_proc_tags( $this->receipt_subject ),
								 $this->_proc_tags( $this->receipt_body )
								 );
		
	}

	// sends a notification email
	private function _do_notify() {
		
		$this->_mail(
								 $this->notify_recipients, 
								 $this->_proc_tags( $this->notify_subject ), 
								 $this->_proc_tags( $this->notify_body ) 
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
	private function _mail( $recipients, $subject, $body ) {

		// error_log(__METHOD__.' with: '.$recipients.' '.$subject.' '.$body );
    
    $this->current_body = $body;
    
    if ( $this->options['html_email'] ) add_action( 'phpmailer_init', array( $this, 'set_alt_body') );

		$sent = wp_mail( $recipients, $subject, $body, $this->email_header );

		if ( false === $sent ) error_log( __METHOD__.' sending returned false' );

	}
  
  /**
   * set the PHPMailer AltBody property with the text body of the email
   * 
   * @param object $phpmailer an object of type PHPMailer
   * @return null
   */
  public function set_alt_body( &$phpmailer ) {
    
    if ( is_object( $phpmailer )) $phpmailer->AltBody = $this->_make_text_body ($this->_proc_tags( $this->current_body ));
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
  private function _make_text_body( $HTML ) {
    
    return strip_tags( preg_replace('#(</[p|h1|h2|h3|h4|h5|h6|div|tr|li]{1,3} *>)#i', "\r", $HTML) );
  }

}