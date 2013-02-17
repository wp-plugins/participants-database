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
  
  // class for the wrapper
  var $wrap_class = 'pdb-signup';
	
	// holds the target page for the submission
	private $submission_page;
  
  // holds the submission status: false if the form has not been submitted
  private $submitted = false;

	// holds the current email object
	public $email_message;
	
	// boolean to send the reciept email
	private $send_reciept;
	
	// boolean to send the notification email
	private $send_notification;
  
  // holds the current email body
  var $current_body;

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
		$add_atts = array( 'type' => 'signup' );
    
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
    
    if ( isset($_GET['retrieve']) ) $this->module = 'retrieve';
		
    // run the parent class initialization to set up the parent methods 
    parent::__construct( $this, $params, $add_atts );
		
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
						$this->shortcode_atts['type'] != 'thanks'
					 )
				{
					// no submission; output the form
					$this->_print_from_template();
			
				}
			
		} elseif ( $this->submitted ) {
      
      /*
       * filter provides access to the freshly-stored record-- actually the whole 
       * object so things like the email parameters can be altered. The properties 
       * would need to be public, or we create methods to alter them
       */
      apply_filters('pdb_after_submit_signup', $this);

			/*
       * check to see if the thanks email has been sent and send it if it has not
       */
			if ( 'sent' != get_transient( 'signup-'.$this->participant_id ) ) {
        
        $this->_send_email();
			
        // mark the record as sent to prevent duplicate emails
        set_transient( 'signup-'.$this->participant_id, 'sent', 120 );
        
      }
			
			// print the thank you note
			if ( is_object($this->email_message) ) $this->_thanks();
			
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
                                                'action'         => $this->module,
                                                'subsource'      => Participants_Db::PLUGIN_NAME,
                                                'shortcode_page' => $_SERVER['REQUEST_URI'],
                                                'thanks_page'    => $this->submission_page
                                                ) );

  }
	
	public function print_submit_button( $class = 'button-primary', $text = false ) {
		
		FormElement::print_element( array(
                                      'type'       => 'submit',
                                      'value'      => ($text === false ? $this->options['signup_button_text'] : $text),
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
      <?php echo TemplateEmail::proc_tags( $this->options['signup_thanks'], $this->email_message->get_tags() ); ?>
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

		if ( $this->options['send_signup_notify_email'] ) $this->_do_notify();
		if ( $this->options['send_signup_receipt_email'] ) $this->_do_receipt();
	
	}

	// sends a receipt email
	private function _do_receipt() {
		
		if ( ! isset( $this->participant_values['email'] ) || empty( $this->participant_values['email'] ) ) return NULL;
    
    $this->email_message = new PDb_TemplateEmail(
                    array(
                        'recipients' => $this->participant_values['email'],
                        'subject' => $this->options['signup_receipt_email_subject'],
                        'body' => $this->options['signup_receipt_email_body'],
                        'id' => $this->participant_id,
                    )
    );
    $this->email_message->send();
		
	}

	// sends a notification email
	private function _do_notify() {
    
    $this->email_message = new PDb_TemplateEmail(
                    array(
                        'recipients' => $this->options['email_signup_notify_addresses'],
                        'subject' => $this->options['email_signup_notify_subject'],
                        'body' => $this->options['email_signup_notify_body'],
                        'id' => $this->participant_id,
                    )
    );
    $this->email_message->send();
		
	}
  
  /**
   * sends a private link given an email address or other record identifier
   * 
   * sends an email to the querent and optionally to the admin
   * 
   * checks the querent's IP and allows three tries before access is blocked for a day
   * 
   * returns a string indicating the result of the operation so a suitable feedback 
   * message can be shown to the user: 'not found', 'IP blocked', 'email sent'
   *
   * @param string $identifier the information used to identify an account
   * @return string
   */
  public function send_private_link($identifier) {
    
    $request_ip = str_replace('.','',$_SERVER['REMOTE_ADDR']);
    $transient = Participants_Db::$css_prefix . 'lost-private-link-timeout-' . $request_ip;
    // check the timeout: they get three tries and then the IP is blocked for a day
    $check = get_transient($transient);
    if ( $check === false ) {
      set_transient($transient, 1, (60 * 60 * 24) ); 
    } else {
      if ($check <= 3) {
        set_transient($transient, $check++, (60 * 60 * 24) );
      } else {
        error_log('Participants Database Plugin: blocked private link request from IP:'. $_SERVER['REMOTE_ADDR']);
        return 'IP blocked';
      }
    }
    
    $record_id = get_record_id_by_term($this->options['retrieve_link_identifier'], $identifier);
    if (!Participants_Db::field_value_exists($record_id,'id')) return 'not found'; // no record was found
    
    $this->email_message = new PDb_TemplateEmail(
                    array(
                        'recipients' => '',
                        'subject' => $this->options['retrieve_link_email_subject'],
                        'body' => $this->options['retrieve_link_email_body'],
                        'id' => $record_id,
                    )
    );
    $this->email_message->send();
    
    if ( 0 != $this->options['send_retrieve_link_notify_email'] ) {
      $this->email_message = new PDb_TemplateEmail(
                      array(
                          'recipients' => $this->options['email_signup_notify_addresses'],
                          'subject' => $this->options['retrieve_link_notify_subject'],
                          'body' => $this->options['retrieve_link_notify_body'],
                          'id' => $record_id,
                      )
      );
      $this->email_message->send();
    }
    
    return 'email sent';
    
  }
  /**
   * grab the defined identifier field for display in the retrieve private link form
   * 
   * @global type $wpdb
   * @return string
   */
  function get_retrieve_field() {
    
    global $wpdb;
    
    $columns = array( 'name','title','form_element');
    
    $sql = 'SELECT v.'. implode( ',v.',$columns ) . ' 
            FROM '.Participants_Db::$fields_table.' v 
            WHERE v.name = "'.$this->options['retrieve_link_identifier'].'" 
            ';
            
    return $wpdb->get_results( $sql, OBJECT_K );
    
  }

}