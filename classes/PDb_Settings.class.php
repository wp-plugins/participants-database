<?php
/**
 * plugin settings class for participants-database plugin
 *
 * this uses the generic plugin settings class to build the settings specific to
 * the plugin
 */
class PDb_Settings extends Plugin_Settings {

  function __construct( $setting_label = false ) {

    $this->WP_setting = false === $setting_label ? 'participants-database_settings' : $setting_label;

    // define the settings sections
    // no need to worry about the namespace, it will be prefixed
    $this->sections = array(
                            'main' => 'General Settings',
                            'signup' => 'Signup Form Settings',
                            );

    // run the parent class initialization to finish setting up the class 
    parent::__construct( __CLASS__, $this->WP_setting, $this->sections );

    // define the individual settings
    $this->_define_settings();

    // now that the settings have been defined, finish setting
    // up the plugin settings
    $this->initialize();

  }

  /**
   * defines the individual settings for the plugin
   *
   * @return null
   */
  private function _define_settings() {

    // general settings

    $this->plugin_settings[] = array(
        'name'=>'list_limit',
        'title'=>'Records per Page',
        'group'=>'main',
        'options'=>array(
          'type'=>'text',
          'help_text'=> 'the number of records to show on each page',
          'attributes'=>array( 'style'=>'width:40px' ),
          'value'=>10,
          ),
        );

    $this->plugin_settings[] = array(
        'name'=>'registration_page',
        'title'=>'Participant Record Page',
        'group'=>'main',
        'options'=>array(
          'type'=>'text',
          'help_text'=> 'the slug of the page where your participant record is displayed',
          )
        );

    $this->plugin_settings[] = array(
        'name'=>'empty_field_message',
        'title'=>'Missing Field Error Message',
        'group'=>'main',
        'options'=>array(
          'type'=>'text',
          'help_text'=> 'the message shown when a field is required, but left empty (the %s is replaced by the name of the field)',
          'value' => 'The %s field is required.',
          )
        );

    $this->plugin_settings[] = array(
        'name'=>'invalid_field_message',
        'title'=>'Invalid Field Error Message',
        'group'=>'main',
        'options'=>array(
          'type'=>'text',
          'help_text'=> "the message shown when a field's value does not pass the validation test",
          'value' => 'The %s field appears to be incorrect.',
          )
        );

    $this->plugin_settings[] = array(
        'name'=>'field_error_style',
        'title'=>'Field Error Style',
        'group'=> 'main',
        'options'=>array(
          'type'=>'text',
          'help_text'=> 'the style applied to an input or text field that is missing or has not passed validation',
          'value' => 'border: 1px solid red',
          )
        );

    // signup form settings

    $this->plugin_settings[] = array(
        'name'=>'signup_button_text',
        'title'=>'Signup Button Text',
        'group'=> 'signup',
        'options'=> array(
          'type'=>'text',
          'help_text'=> 'text shown on the button to sign up',
          'value' => 'Sign Up',
          )
        );

    $this->plugin_settings[] = array(
        'name'       => 'send_signup_receipt_email',
        'title'      => 'Send Signup Response Email',
        'group'      => 'signup',
        'options'    => array
          (
          'type'        => 'checkbox',
          'help_text'   => 'Send a receipt email to people who sign up',
          'value'       => 1,
          'options'     => array( 1, 0 ),
          )
        );

    $this->plugin_settings[] = array(
        'name'=>'receipt_from_address',
        'title'=>'Sugnup Email From Address',
        'group'=> 'signup',
        'options'=> array(
          'type'=>'text',
          'help_text'=> 'the "From" address on signup receipt emails. If the recipient hits "reply", their reply will go to this address',
          'value' => get_bloginfo( 'admin_email' ),
          )
        );

    $this->plugin_settings[] = array(
        'name'=>'receipt_from_name',
        'title'=>'Signup Email From Name',
        'group'=> 'signup',
        'options'=> array(
          'type'=>'text',
          'help_text'=> 'the "From" name on signup receipt emails.',
          'value' => get_bloginfo( 'name' ),
          )
        );

    $this->plugin_settings[] = array(
        'name'=>'signup_receipt_email_subject',
        'title'=>'Signup Response Email Subject',
        'group'=> 'signup',
        'options'=> array(
          'type'=>'text',
          'help_text'=> 'subject line for the signup response email',
          'value' => "You've just signed up on ".get_bloginfo('name'),
          )
        );

    $this->plugin_settings[] = array(
        'name'=>'signup_receipt_email_body',
        'title'=>'Signup Response Email',
        'group'=> 'signup',
        'options'=> array(
          'type'=>'text-field',
          'help_text'=> 'body of the email a visitor gets when they sign up. It includes a link ([record_link]) back to their record so they can fill it out. Can include HTML, placeholders:[first_name],[last_name],[email],[record_link].',
          'value'=>'<p>Thank you, [first_name] for signing up with '.get_bloginfo('name').'.</p><p>You may complete your registration with additional information or update your information by visiting this link at any time: <a href="[record_link]">[record_link]</a>.</p>',
          )
        );

    $this->plugin_settings[] = array(
        'name'=>'signup_thanks',
        'title'=>'Signup Thanks Message',
        'group'=> 'signup',
        'options'=> array(
          'type'=>'text-field',
          'help_text'=> 'Note to display on the web page after someone has submitted a signup form. Can include HTML, placeholders:[first_name],[last_name],[email],[record_link].',
          'value'=>'<p>Thank you, [first_name] for signing up!</p><p>You will receive an email acknowledgement shortly. You may complete your registration with additional information or update your information by visiting the link provided in the email.</p>',
          )
        );

    $this->plugin_settings[] = array(
        'name'       => 'send_signup_notify_email',
        'title'      => 'Send Signup Notification Email',
        'group'      => 'signup',
        'options'    => array
          (
          'type'        => 'checkbox',
          'help_text'   => 'Send an email notification that a signup has occurred.',
          'value'       => 1,
          'options'     => array( 1, 0 ),
          )
        );


    $this->plugin_settings[] = array(
        'name'=>'email_signup_notify_addresses',
        'title'=>'Signup Notification Recipients',
        'group'=> 'signup',
        'options'=> array(
          'type'=>'text',
          'help_text'=> 'comma-separated list of email addresses to send signup notifications to',
          'value' => get_bloginfo( 'admin_email' ),
          )
        );

    $this->plugin_settings[] = array(
        'name'=>'email_signup_notify_subject',
        'title'=>'Signup Notification Email Subject',
        'group'=> 'signup',
        'options'=> array(
          'type'=>'text',
          'help_text'=> 'subject of the notification email',
          'value' => 'New signup on '.get_bloginfo('name'),
          )
        );

    $this->plugin_settings[] = array(
        'name'=>'email_signup_notify_body',
        'title'=>'Signup Notification Email',
        'group'=> 'signup',
        'options'=> array(
          'type'=>'text-field',
          'help_text'=> 'notification email body',
          'value' => '<p>A new signup has been submitted</p><ul><li>Name: [first_name] [last_name]</li><li>Email: [email]</li></ul>',
          )
        );

  }

  /**
   * displays a settings page form using the WP Settings API
   *
   * this function is called by the plugin on it's settings page
   *
   * @return null
   */
  public function show_settings_form() {
    ?>
    <div class="wrap participants_db settings-class">
      <h2><?php echo Participants_Db::PLUGIN_TITLE?> Settings</h2>

      <?php parent::show_settings_form() ?>

    </div>
    <?php

  }

}