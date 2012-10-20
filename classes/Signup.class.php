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
 * @version    0.2
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    FormElement class
 */
class Signup {

	// holds the column attributes for columns assigned to the signup
	private $signup_columns;

	// groups titles and descriptions
	private $groups;
	
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
	
	// holds an index to the current field
	private $field_index;
	
	// holds the current shorcode attributes
	private $shortcode_atts;

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

		$this->shortcode_atts = shortcode_atts( array(
																	'title'   => '',
																	'captcha' => 'none',
																	'class' => 'signup',
																	'type' => 'signup',
																	'template' => 'default',
																	),
												$params );
														
		$this->captcha_type = $this->shortcode_atts['captcha'];
		
		
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
						$this->shortcode_atts['type'] != 'thanks'
					 )
				{
					// no submission; output the form
					$this->_print_from_template();
					//$this->_form( $atts );
			
				}
			
		} elseif ( $submission_id && 'sent' != get_transient( 'signup-'.$submission_id ) ) {

			// load the values from the newly-submitted record
			$this->_load_participant( $submission_id );
			
			
			$this->registration_page = Participants_Db::get_record_link( $this->participant['private_id'] );
			
			// print the thank you note
			$this->_thanks();

			// send the email receipt and notification
			$this->_send_email();
			
			// mark the record as sent to prevent duplicate emails
			set_transient( 'signup-'.$submission_id, 'sent', 86400 );
			
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
	
	private function _print_from_template() {
		
		$template = Participants_Db::get_template( 'signup', $this->shortcode_atts['template'] );
			
    if ( false === $template ) {
			
			$this->output = '<p class="alert alert-error">'.sprintf(_x('%sThe template %s was not found.%s Please make sure the name is correct and the template file is in the correct location.', 'message to show if the plugin cannot find the template', Participants_Db::PLUGIN_NAME ), '<strong>', 'pdb-signup-'.$this->shortcode_atts['template'].'.php', '</strong>' ) .'</p>';
      
    	return false;


    }
			
    ob_start();
    
    include $template;

    $this->output = ob_get_clean();
			
	}
		

	public function get_signup_form_fields() {

    global $wpdb;

    if ( $this->options['signup_show_group_descriptions'] ) {

      // get the groups object
      $sql = "
              SELECT REPLACE( g.title, '\\\', '' ) as title, g.name, REPLACE( g.description, '\\\', '' ) as description 
              FROM ".Participants_Db::$groups_table." g
              WHERE g.display = 1
              AND g.name IN (
                SELECT f.group
                FROM ".Participants_Db::$fields_table." f
                WHERE f.signup = 1
                )
              ORDER BY `order` ASC
              ";

      $groups = $wpdb->get_results( $sql, OBJECT_K );

    } else {

      $groups = array();
      $groups[] = new stdClass;

    }

    // define which field columns are needed, un-escaping the display fields
    $field_select = "f.name, REPLACE(f.title,'\\\','') as title, REPLACE(f.help_text,'\\\','') as help_text, f.form_element, f.values, f.validation, f.default";

    foreach( $groups as $group ) {

      $where = isset( $group->name ) ? 'f.group = "'.$group->name.'"' : 'g.display = 1';

      $sql = '
              SELECT '.$field_select.', g.display, g.order
              FROM '.Participants_Db::$fields_table.' f
              JOIN '.Participants_Db::$groups_table.' g
							ON f.group = g.name
              WHERE '.$where.' 
              AND f.signup = 1
              ORDER BY g.order, f.order
							';

      $group->fields = $wpdb->get_results( $sql, OBJECT_K );

    }
		
		$this->field_index = -1;

    return $groups;

  }

  // prints a signup form top
  public function form_top() {
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
	
	public function errors() {
		
		if ( is_object( Participants_Db::$validation_errors ) ) {
			
			return Participants_Db::$validation_errors->get_validation_errors();
			
		}
		
	}
	
	public function error_class() {
		
		if ( is_object( Participants_Db::$validation_errors ) ) {
			
			echo Participants_Db::$validation_errors->get_error_class();
			
		}
		
	}
	
	public function error_CSS() {
		
		if ( is_object( Participants_Db::$validation_errors ) ) {
			
			echo Participants_Db::$validation_errors->get_error_CSS();
			
		}
		
	}

	private function have_fields( $group ) {

    $field = $this->field_index < 0 ? current( $group->fields ) : next( $group->fields );
		
		$this->field_index ++;

    if ( is_object( $field ) && $field->form_element == 'hidden' ) {

      $this->_print_hidden_field( $field, $field->default );

      $field = next( $group->fields );

    }
		
		$have_fields = is_object( $field );
		
		if ( ! $have_fields ) $this->field_index = -1;

    return $have_fields;

  }

  private function current_field( $group ) {

    return current( $group->fields );

  }
	
	public function field_title( $field ) {
		
		if ( $this->options['mark_required_fields'] && $field->validation != 'no' ) {
			
			printf( $this->options['required_field_marker'], $field->title );
      
		} else echo $field->title;
		
	}
	
	public function print_field( $field ) {
		
		FormElement::print_element( array(
                                        'type'       => $field->form_element,
                                        'value'      => Participants_Db::prepare_field_value( $field->name, $field->default, $_POST ),
                                        'name'       => $field->name,
                                        'options'    => $field->values,
                                        'class'      => ( $field->validation != 'no' ? "required-field" : '' ),
                                        ) );
		
	}
	
	public function submit_button( $class = 'button-primary pdb-submit' ) {
		
		FormElement::print_element( array(
                                      'type'       => 'submit',
                                      'value'      => $this->options['signup_button_text'],
                                      'name'       => 'submit',
                                      'class'      => $class,
                                      ) );
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

		$this->_mail(
								 $this->participant['email'],
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

		if ( false !== $p ) {

		foreach( $this->signup_columns as $column ) {

			$this->participant[ $column->name ] = $p[ $column->name ];

		}

	}
	
	}
	
	private function _print_hidden_field( $column, $value = false ) {
		
		if ( $value ) {
		
			global $post, $current_user;
			
			if ( false !== strpos( html_entity_decode($value), '->' ) ) {
				
				list( $object, $property ) = explode( '->', html_entity_decode($value) );
				
				$object = ltrim( $object, '$' );
				
				$value = isset( $$object->$property ) ? is_array( $$object->$property ) ? current( $$object->$property ) : $$object->$property : $value;
				
			}
			
			FormElement::print_element( array(
																				'type'       => 'hidden',
																				'value'      => $value,
																				'name'       => $column->name,
																				)
																 );

			
		}
		
	}

}