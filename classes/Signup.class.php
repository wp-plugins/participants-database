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

	// groups titles and descriptions
	private $groups;
	
	// the currently active group
	private $current_group;
	
	// holds the target page for the submission
	private $submission_page;

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
	private $output = '';
	
	// plugin options array
	private $options;

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

		$this->options = get_option( Participants_Db::$participants_db_options );

		$this->send_reciept = $this->options['send_signup_receipt_email'];
		$this->send_notification = $this->options['send_signup_notify_email'];
		$this->notify_recipients = $this->options['email_signup_notify_addresses'];
		$this->notify_subject = $this->options['email_signup_notify_subject'];
		$this->notify_body = $this->options['email_signup_notify_body'];
		$this->receipt_subject = $this->options['signup_receipt_email_subject'];
		$this->receipt_body = $this->options['signup_receipt_email_body'];
		$this->thanks_message = $this->options['signup_thanks'];
		$this->email_header = Participants_Db::$email_headers;
		
		if ( ! isset( $this->options['signup_thanks_page'] ) ) {
			
			// the signup thanks page is not set up
			
			$this->submission_page = $_SERVER['REQUEST_URI'];
			
		} else {
			
			$this->submission_page = get_page_link( $this->options['signup_thanks_page'] );
			
		}

		if ( $this->options['signup_show_group_descriptions'] ) {

      $this->groups = Participants_Db::get_groups( '`name`,`title`,`description`' );

    } else $this->groups = false;

		$atts = shortcode_atts( array(
																	'title'   => '',
																	'captcha' => 'none',
																	'class' => 'signup',
																	'type' => 'signup',
																	),
												$params );
														
		$this->captcha_type = $atts['captcha'];
		
		
		// if we're coming back from a successful form submission, the id of the new record will be present
		$submission_id = isset( $_GET['id'] ) ? $_GET['id'] : false ;
	
		if ( false === $submission_id ) {

				/*
				 * no submission: do we show the signup form?
				 *
				 * yes if there's no valid PID in the GET string
				 * but not if we're actually showing a [pdb_signup_thanks] shortcode 
				 * and there's no submission
				 *
				 * this is because we use the same code for both shortcodes
				 *
				 * we will get a no-show if we end up here with a valid PID, but 
				 * there's no [pdb_record] shortcode there.
				 */
				if (
						( 
						 ! isset( $_GET['pid'] )
						 ||
						 ( isset( $_GET['pid'] ) && false === Participants_Db::get_participant_id( $_GET['pid'] ) ) 
						)
						&&
						$atts['type'] != 'thanks'
					 )
				{
	
					// no submission; output the form
					$this->_form( $atts );
			
				}
			
		} elseif ( $submission_id && false === get_transient( 'signup-'.$submission_id ) ) {

			// load the values from the newly-submitted record
			$this->_load_participant( $submission_id );
			
			
			$this->registration_page = Participants_Db::get_record_link( $this->participant['private_id'] );
			
			// print the thank you note
			$this->_thanks();

			// send the email receipt and notification
			$this->_send_email();
			
			set_transient( 'signup-'.$submission_id, 'sent', 30 );
			
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
	public function print_form( $params ) {
		
		$signup = new Signup( $params );
		
		return $signup->output;
		
	}
	
	// prints a signup form
	private function _form( $atts ) {
		ob_start();
		?>
		<div class="<?php echo $atts['class']?> pdb-signup" >
		<?php
		
		$participant_values = Participants_Db::get_participant( Participants_Db::$id_base_number );
		
		$action = 'action="'.$this->submission_page.'"';

		if ( is_object( Participants_Db::$validation_errors ) ) {
			
			echo Participants_Db::$validation_errors->get_error_html();
			
			$action = '';
			
		}

			?>
			<form method="post" enctype="multipart/form-data" >
				<?php
				FormElement::print_hidden_fields( array( 'action'=>'signup', 'source'=>Participants_Db::PLUGIN_NAME, 'shortcode_page' => basename( $_SERVER['REQUEST_URI'] ), 'thanks_page' => $this->submission_page ) );
				?>
				<table class="form-table pdb-signup">
			<?php

			// get the columns and output form
			foreach ( $this->signup_columns as $column ) :

				// skip the ID column
				if ( in_array( $column->name, array( 'id', 'private_id' ) ) ) continue;
				
				if ( in_array( $column->form_element, array( 'hidden' ) ) ) {
					
					$this->_print_hidden_field( $column, $participant_values );
					
					continue;
					
				}
				
				
				// if we're showing groups, is the group of the next field a new one? If so, show it
        if ( false !== $this->groups && true === ( $column->group != $this->current_group['name'] ) ) {
					
					$this->_print_group_row( $column->group );
					
					$this->current_group = $this->groups[ $column->group ];
					
				}
				
				?>
					<tr id="pdb_<?php echo $column->name?>" class="<?php echo $column->form_element?>">
						<?php
            $column_title = $column->title;
            if ( $this->options['mark_required_fields'] && $column->validation != 'no' ) {
              $column_title = sprintf( $this->options['required_field_marker'], $column_title );
            }
            ?>
						<th><?php echo $column_title?></th>
						<td>
						<?php

						// unserialize it if the default is an array
						$value = isset( $participant_values[ $column->name ] ) ? Participants_Db::unserialize_array( $participant_values[ $column->name ] ) : '';
						
						// now get the inputted value and scrub it if it's a string
						if ( isset( $_POST[ $column->name ] ) ) {
							
							if ( is_array( $_POST[ $column->name ] ) ) $value = $_POST[ $column->name ];
							
							else $value = esc_html(stripslashes($_POST[ $column->name ]));
							
						}

						FormElement::print_element( array(
																							'type'       => $column->form_element,
																							'value'      => $value,
																							'name'       => $column->name,
																							'options'    => $column->values,
                                      				'class'      => ( $column->validation != 'no' ? "required-field" : '' ),
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
            <?php 
						FormElement::print_element( array(
																							'type'       => 'submit',
																							'value'      => $this->options['signup_button_text'],
																							'name'       => 'submit',
																							'class'      => 'button-primary pdb-submit',
																							) );
						?>
						</td>
					</tr>
				</table>
			</form>
		</div>
		<?php
		$this->output .= ob_get_clean();
	}

	/**
	 * prints a group row
	 *
	 * we use the array index to keep track of which group is current
	 */
  private function _print_group_row( $group ) {

    // get the group's info
    $new_group = $this->groups[ $group ];

    ?>
    <tr class="signup-group">
      <td colspan="2">
      	<?php printf( ( empty( $new_group['description'] ) ? '<h3>%1$s</h3>' : '<h3>%1$s</h3><p>%2$s</p>' ),$new_group['title'], $new_group['description'] )?>
      </td>
    </tr>
    <?php
  }
	
	/**
	 * prints a thank you note
	 */
	private function _thanks() {
		ob_start();
		?>
		<div class="signup signup-thanks">
		<?php
		echo $this->_proc_tags( $this->thanks_message );
		?>
		</div>
		<?php
		
		$this->output = ob_get_clean();
		
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

	// replace the tags in text messages
	// returns the text with the values replacing the tags
	// all tags use the column name as the key string
	// also includes and processes the [record_link] tag
	private function _proc_tags( $text, $values = array(), $tags = array() ) {

		if ( empty( $values ) ) {

			foreach( $this->signup_columns as $column ) {

				$tags[] = '['.$column->name.']';

				$values[] = Participants_Db::prep_field_for_display( $this->participant[$column->name], $column->form_element );

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
	
	private function _print_hidden_field( $column, $participant_values ) {
		
		// unserialize it if the default is an array
		$value = isset( $participant_values[ $column->name ] ) ? $participant_values[ $column->name ] : false  ;
		
		if ( $value ) {
		
			global $post, $current_user;
			
			if ( false !== strpos( html_entity_decode($value), '->' ) ) {
				
				list( $object, $property ) = explode( '->', html_entity_decode($value) );
				
				$object = ltrim( $object, '$' );
				
				$value = isset( $$object->$property )? $$object->$property : $value;
				
			}
			
			FormElement::print_element( array(
																				'type'       => $column->form_element,
																				'value'      => $value,
																				'name'       => $column->name,
																				)
																 );

			
		}
		
	}

}