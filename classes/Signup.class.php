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
 * @version    0.1
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    FormElement class
 */
class Signup {

	// holds the column attributes for columns assigned to the signup
	private $signup_columns;

	// holds the recipient values after a form submission
	private $recipient;
	
	// boolean to send the reciept email
	private $send_reciept;

	// the receipt subject line
	private $receipt_subject;
	
	// holds the body of the signup receipt email
	private $receipt_body;
	
	// boolean to send the notification email
	private $send_notification;

	// holds the notify recipient emails
	private $notify_recipients;

	// the notification subject line
	private $notify_subject;

	// holds the body of the notification email
	private $notify_body;
	
	// the text of the thank you note
	private $thanks_message;

	// header added to receipts and notifications
	private $email_header;

	// the selected captcha type
	private $captcha_type;

	// holds the submission values
	private $post = array();

	// error messages
	private $errors = array();
	
	// holds the output for the shortcode
	private $output;

	// methods
	//

	/**
	 * instantiates the signup form object
	 *
	 * this class is designed to be called by a WP shortcode
	 *
	 * @param array $params this array supplies the display parameters for the instance
	 *                title    string displays a title for the form (default none)
	 *                captcha  string type of captcha to include: none (default), math, image, word
	 *
	 * @return prints HTML
	 */
	public function __construct( $params ) {

		$this->signup_columns = Participants_db::get_column_atts( 'signup' );

		$options = get_option( Participants_Db::$participants_db_options );

		$this->send_reciept = $options['send_signup_receipt_email'];
		$this->send_notification = $options['send_signup_notify_email'];
		$this->notify_recipients = $options['email_signup_notify_addresses'];
		$this->notify_subject = $options['email_signup_notify_subject'];
		$this->notify_body = $options['email_signup_notify_body'];
		$this->receipt_subject = $options['signup_receipt_email_subject'];
		$this->receipt_body = $options['signup_receipt_email_body'];
		$this->thanks_message = $options['signup_thanks'];
		$this->email_header = sprintf(
		                               'From: %2$s <%1$s>%3$s',
		                               $options['receipt_from_address'],
		                               $options['receipt_from_name'],
		                               "\r\n"
		                               );


		$atts = shortcode_atts( array(
																			'title'   => '',
																			'captcha' => 'none',
																			'class' => 'signup',
																			),
														$params );
														
		$this->captcha_type = $atts['captcha'];

		$submission_id = $this->process_submit();
		
		// do we have a submission?
		if ( false === $submission_id ) {

				// no submission
				// if the signup and edit record shortcodes are on the same page, we check to see which one we will show:
				// if no private id is included in the URI, we show the signup form; also
				// if there is a private id in the URI, we check to see if it's valid; if not, show the signup form
				if (
						! isset( $_GET['pid'] )
						||
						( isset( $_GET['pid'] ) && false === Participants_Db::get_participant_id( $_GET['pid'] ) ) 
					 )
				{
	
			// no submission; output the form
			$this->_form( $atts );
			
				}
			
		} else {

			// load the values from the newly-submitted record
			$this->_load_participant( $submission_id );
			
			$this->registration_page = get_bloginfo('url').'/'.( isset( $options['registration_page'] ) ? $options['registration_page'] : '' ).'?pid='.$this->participant['private_id'];
			
			// print the thank you note
			$this->_thanks();

			// send the email receipt and notification
			$this->_send_email();
			
		}
		
	}
	
	public function print_form( $params ) {
		
		$signup = new Signup( $params );
		
		return $signup->output;
		
	}
	
	// prints a signup form
	private function _form( $atts ) {
		ob_start();
		?>
		<div class="<?php echo $atts['class']?>" >
		<?php

			if ( is_object( Participants_Db::$validation_errors ) ) echo Participants_Db::$validation_errors->get_error_html();

			?>
			<form method="post" action="<?php echo $_SERVER['REQUEST_URI']?>">
				<?php
				FormElement::print_hidden_fields( array( 'action'=>'signup' ) );
				?>
				<table class="form-table pdb-signup">
			<?php

			// get the columns and output form
			foreach ( $this->signup_columns as $column ) :

				// skip the ID column
				if ( in_array( $column->name, array( 'id', 'private_id' ) ) ) continue;
				?>
					<tr id="<?php echo $column->name?>" class="<?php echo $column->form_element?>">
						<th><?php echo $column->title?></th>
						<td>
						<?php

						$value = isset( $participant_values[ $column->name ] ) ? Participants_Db::unserialize_array( $participant_values[ $column->name ] ) : '';

						FormElement::print_element( array(
																							'type'       => $column->form_element,
																							'value'      => ( isset( $_POST[ $column->name ] ) ? $_POST[ $column->name ] : $value ),
																							'name'       => $column->name,
																							'options'    => $column->values,
																							) );
						if ( ! empty( $column->help_text ) ) :
							?>
							<span class="helptext"><?php echo trim( $column->help_text )?></span>
							<?php
						endif;
						?>
						</td>
					</tr>
					<?php
				endforeach;
				if ( $captcha = $this->_add_captcha( $this->captcha_type ) ) :
					?>
					<tr>
						<td colspan="2"><?php echo $captcha?></td>
					</tr>
				<?php endif ?>
					<tr>
						<td colspan="2" class="submit-buttons">
							<input class="button-primary" type="submit" value="<?php $options = get_option(Participants_Db::$participants_db_options); echo $options['signup_button_text']?>" name="submit">
						</td>
					</tr>
				</table>
			</form>
		</div>
		<?php
		$this->output = ob_get_clean();
	}
	
	/**
	 * prints a thank you note
	 */
	private function _thanks() {
		ob_start();
		?>
		<div class="signup">
		<?php
		echo $this->_proc_tags( $this->thanks_message );
		?>
		</div>
		<?php
		$this->output = ob_get_clean();
		
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

	// sends an email
	private function _send_email() {

		if ( $this->send_notification ) $this->_do_notify();
		if ( $this->send_reciept ) $this->_do_receipt();
	
	}

	// sends a receipt email
	private function _do_receipt() {
		
		if ( ! isset( $this->participant['email'] ) || empty( $this->participant['email'] ) ) return NULL;

		$body = $this->_proc_tags( $this->receipt_body );

		$this->_mail( $this->participant['email'], $this->receipt_subject, $body );
		
	}

	// sends a notification email
	private function _do_notify() {

		$body = $this->_proc_tags( $this->notify_body );
		
		$this->_mail( $this->notify_recipients, $this->notify_subject, $body );
		
		
	}

	// send a mesage to a mail handler function
	private function _mail( $recipients, $subject, $body ) {

		// error_log(__METHOD__.' with: '.$recipients.' '.$subject.' '.$body );

		$sent = wp_mail( $recipients, $subject, $body, $this->email_header );

		if ( false === $sent ) error_log( __METHOD__.' sending returned false' );

	}

	/**
	 * processes the submit
	 *
	 * this is going to take the relevant items from the $_POST array, validate
	 * them, store them in an object property and call the appropriate display
	 * method
	 *
	 * @return false or ID of new successful registration
	 */
	public function process_submit() {
		
		if ( !isset( $_POST['submit'] ) || $_POST['action'] != 'signup' ) return false;
		
		// instantiate the validation object
		Participants_Db::$validation_errors = new FormValidation();

		/* if someone signs up with an email that already exists, we update that
		 * record rather than let them create a new record. This gives us a method
		 * for dealing with people who have lost their access link, they just sign
		 * up again with the same email, and their access link will be emailed to
		 * them. This is handled by the Participants_Db::process_form method.
		 */

		$_POST['private_id'] = Participants_Db::generate_pid();
		
		return Participants_Db::process_form( $_POST, 'insert' ) ;
		
	}

	// replace the tags in text messages
	// returns the text with the values replacing the tags
	// all tags use the column name as the key string
	// also includes and processes the [record_link] tag
	private function _proc_tags( $text, $values = array(), $tags = array() ) {

		if ( empty( $values ) ) {

			foreach( $this->signup_columns as $column ) {

				$tags[] = '['.$column->name.']';

				$values[] = $this->participant[$column->name];

			}

		}

		// add the "record_link" tag
		$tags[] = '[record_link]';
		$values[] = $this->registration_page;
				

		$placeholders = array();
		
		for ( $i = 1; $i <= count( $tags ); $i++ ) {

			$placeholders[] = '%'.$i.'$s';

		}

		// replace the tags with variables
		$pattern = str_replace( $tags, $placeholders, $text );
		
		// replace the variables with strings
		return vsprintf( $pattern, $values );

	}

	// sets up the participant property with only the the signup values
	private function _load_participant( $id ) {

		$p = Participants_Db::get_participant( $id );

		foreach( $this->signup_columns as $column ) {

			$this->participant[ $column->name ] = $p[ $column->name ];

		}

	}

}