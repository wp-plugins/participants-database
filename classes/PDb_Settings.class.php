<?php

/**
 * plugin settings class for participants-database plugin
 *
 * this uses the generic plugin settings class to build the settings specific to
 * the plugin
 */
class PDb_Settings extends xnau_Plugin_Settings {

  function __construct() {

    $this->WP_setting = Participants_Db::$participants_db_options;

    // define the settings sections
    // no need to worry about the namespace, it will be prefixed
    $this->sections = array(
        'pdb-main' => __('General Settings', 'participants-database'),
        'pdb-signup' => __('Signup Form Settings', 'participants-database'),
        'pdb-record' => __('Record Form Settings', 'participants-database'),
        'pdb-list' => __('List Display Settings', 'participants-database'),
        'pdb-resend' => __('Resend Link Settings', 'participants-database'),
        'pdb-advanced' => __('Advanced Settings', 'participants-database'),
        'pdb-css'  => __('Custom CSS', 'participants-database'),
    );

    self::$section_description = array(
        'pdb-record' => __('Settings for the [pdb_record] shortcode, which is used to show a user-editable form on the website.', 'participants-database'),
        'pdb-list' => __('Settings for the [pdb_list] shortcode, which is used to show a list of records from the database.', 'participants-database'),
        'pdb-signup' => __('Settings for the [pdb_signup] shortcode, which is used to show a signup or registration form on the website.', 'participants-database'),
        'pdb-resend' => __('Settings for the lost private link resend function.', 'participants-database'),
        'pdb-advanced' => __('Settings for special configurations.', 'participants-database'),
        'pdb-css' => __('User CSS rules for styling plugin displays.</h4><p>If you\'re new to CSS, try this tutorial to help you get started: <a href="http://www.ostraining.com/blog/wordpress/firebug-wordpress-css/">Use Firebug for Editing WordPress Themes.</a></p>' , 'participants-database'),
    );
    // determine the type of text-area elements to use for email body settings
    $this->textarea_type = Participants_Db::$plugin_options['html_email'] == '1' ? 'rich-text' : 'text-area';


    // run the parent class initialization to finish setting up the class 
    parent::__construct(__CLASS__, $this->WP_setting, $this->sections);

    $this->submit_button = __('Save Plugin Settings', 'participants-database');

    // define the individual settings
    $this->_define_settings();

    // now that the settings have been defined, finish setting
    // up the plugin settings
    $this->initialize();
  }

  /**
   * get all options w/defaults
   * 
   * this is used to overload the options
   * 
   * @return array name => defualt
   */
  public function get_default_options() {
    $defaults = array();
    foreach($this->plugin_settings as $setting) {
      $defaults[$setting['name']] = isset($setting['options']['value']) ? $setting['options']['value'] : '';
    }
    return $defaults;
  }

  /**
   * defines the individual settings for the plugin
   *
   * @return null
   */
  private function _define_settings() {

    /******************************************************
     *
     *   general settings
     *
     * **************************************************** */

    $this->plugin_settings[] = array(
        'name' => 'image_upload_location',
        'title' => __('File Upload Location', 'participants-database'),
        'group' => 'pdb-main',
        'options' => array(
            'type' => 'text',
            'help_text' => __("This defines where the uploaded files will go, relative to the WordPress root. The default location is '/wp-content/uploads/participants-database'<br />Don't put it in the plugin folder, the images and files could get deleted when the plugin is updated.", 'participants-database'),
            'value' => Participants_Db::$uploads_path,
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'image_upload_limit',
        'title' => __('File Upload Limit', 'participants-database'),
        'group' => 'pdb-main',
        'options' => array(
            'type' => 'text',
            'help_text' => __('the maximum allowed file size (in kilobytes) for an uploaded file', 'participants-database'),
            'value' => '100',
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'allowed_file_types',
        'title' => __('Allowed File Types', 'participants-database'),
        'group' => 'pdb-main',
        'options' => array(
            'type' => 'text',
            'help_text' => __('list of allowed file types by file extension. Please be aware that there are security risks in allowing file uploads.', 'participants-database'),
            'value' => 'txt,pdf,mp3,mp4a,ogg,doc,docx,odt,rtf,zip,jpg,jpeg,gif,png',
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'default_image',
        'title' => __('Default Image', 'participants-database'),
        'group' => 'pdb-main',
        'options' => array(
            'type' => 'text',
            'help_text' => __("Path (relative to WP root) of an image file to show if no image has been defined for an image field. Leave blank for no default image.", 'participants-database'),
            'value' => '/wp-content/plugins/participants-database/ui/no-image.png',
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'image_link',
        'title' => __('Link Image to Fullsize', 'participants-database'),
        'group' => 'pdb-main',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __('place a link to the full-size image on images. This link will work with most "lightbox" plugins.', 'participants-database'),
            'value' => 0,
            'options' => array(1, 0),
        ),
    );

    $this->plugin_settings[] = array(
        'name' => 'file_delete',
        'title' => __('Allow File Delete', 'participants-database'),
        'group' => 'pdb-main',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __('if checked, allows uploaded files and images to be deleted from storage when deleted from a record by a user', 'participants-database'),
            'value' => 0,
            'options' => array(1, 0),
        ),
    );

    $this->plugin_settings[] = array(
        'name' => 'make_links',
        'title' => __('Make Links Clickable', 'participants-database'),
        'group' => 'pdb-main',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __('if a "text-line" field looks like a link (begins with "http" or is an email address) make it clickable', 'participants-database'),
            'value' => 0,
            'options' => array(1, 0),
        ),
    );

    $this->plugin_settings[] = array(
        'name' => 'email_protect',
        'title' => __('Protect Email Addresses', 'participants-database'),
        'group' => 'pdb-main',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __('protect email addresses in text-line fields with Javascript', 'participants-database'),
            'value' => 0,
            'options' => array(1, 0),
        ),
    );

    $this->plugin_settings[] = array(
        'name' => 'empty_field_message',
        'title' => __('Missing Field Error Message', 'participants-database'),
        'group' => 'pdb-main',
        'options' => array(
            'type' => 'text',
            'help_text' => __('the message shown when a field is required, but left empty (the %s is replaced by the name of the field)', 'participants-database'),
            'value' => __('The %s field is required.', 'participants-database'),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'invalid_field_message',
        'title' => __('Invalid Field Error Message', 'participants-database'),
        'group' => 'pdb-main',
        'options' => array(
            'type' => 'text',
            'help_text' => __("the message shown when a field's value does not pass the validation test", 'participants-database'),
            'value' => __('The %s field appears to be incorrect.', 'participants-database'),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'nonmatching_field_message',
        'title' => __('Non-Matching Field Error Message', 'participants-database'),
        'group' => 'pdb-main',
        'options' => array(
            'type' => 'text',
            'help_text' => __("the message shown when a field's value does match the value of another field. The first %s will show the name of the field, the second will show the name of the field it must match.", 'participants-database'),
            'value' => __('The %s field must match the %s field.', 'participants-database'),
        )
    );


    $this->plugin_settings[] = array(
        'name' => 'captcha_field_message',
        'title' => __('Failed CAPTCHA Message', 'participants-database'),
        'group' => 'pdb-main',
        'options' => array(
            'type' => 'text',
            'help_text' => __("the message shown when the CAPTCHA (verify human) test failed", 'participants-database'),
            'value' => __('Pleast try the %s question again.', 'participants-database'),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'field_error_style',
        'title' => __('Field Error Style', 'participants-database'),
        'group' => 'pdb-main',
        'options' => array(
            'type' => 'text',
            'help_text' => __('the CSS style applied to an input or text field that is missing or has not passed validation. This must be a valid CSS rule', 'participants-database'),
            'value' => 'border: 1px solid red',
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'mark_required_fields',
        'title' => __('Mark Required Fields', 'participants-database'),
        'group' => 'pdb-main',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __('mark the title of required fields?', 'participants-database'),
            'value' => 0,
            'options' => array(1, 0),
        ),
    );

    $this->plugin_settings[] = array(
        'name' => 'required_field_marker',
        'title' => __('Required Field Marker', 'participants-database'),
        'group' => 'pdb-main',
        'options' => array(
            'type' => 'text-area',
            'help_text' => __('html added to field title for required fields if selected above (the %s is replaced by the title of the field)', 'participants-database'),
            'value' => '%s<span class="reqd">*</span>',
        )
    );

//    $this->plugin_settings[] = array(
//        'name' => 'search_collation',
//        'title' => __('Search Collation', 'participants-database'),
//        'group' => 'pdb-main',
//        'options' => array(
//            'type' => 'dropdown',
//            'help_text' => sprintf(__('sets the database collation map <a href="%s">(more info)</a> to use for list searches. Set this to your language if your non-English searches are not working correctly. You may have to experiment with different settings. The default is "UTF-8 Unicode."', 'participants-database'), 'http://dev.mysql.com/doc/refman/5.0/en/charset-general.html'),
//            'value' => $this->get_collation(),
//            'options' => $this->get_collation_list(),
//        )
//    );

    /******************************************************
     *
     *   signup form settings
     *
     ******************************************************/

    $this->plugin_settings[] = array(
        'name' => 'signup_button_text',
        'title' => __('Signup Button Text', 'participants-database'),
        'group' => 'pdb-signup',
        'options' => array(
            'type' => 'text',
            'help_text' => __('text shown on the button to sign up', 'participants-database'),
            'value' => _x('Sign Up', 'the text on a button to submit a signup form', 'participants-database'),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'signup_thanks_page',
        'title' => __('Signup Thanks Page', 'participants-database'),
        'group' => 'pdb-signup',
        'options' => array(
            'type' => 'dropdown-other',
            'help_text' => __('after they register, send them to this page for a thank you message. This page is where you put the [pdb_signup_thanks] shortcode, but you don&#39;t have to do that if you have them go back to the same page. You can also use a Post ID for posts and custom post types.', 'participants-database'),
            'options' => $this->_get_pagelist(true),
            'attributes' => array('other' => 'Post ID'),
            'value' => 'none',
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'signup_show_group_descriptions',
        'title' => __('Show Field Groups', 'participants-database'),
        'group' => 'pdb-signup',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __('Show groups and group descriptions in the signup form.', 'participants-database'),
            'value' => 0,
            'options' => array(1, 0),
        )
    );
    
     $this->plugin_settings[] = array(
        'name' => 'unique_field',
        'title' => __('Duplicate Record Check Field', 'participants-database'),
        'group' => 'pdb-signup',
        'options' => array
            (
            'type' => 'dropdown',
            'help_text' => __('when a signup is submitted or CSV record is imported, this field is checked for a duplicate', 'participants-database'),
            'options' => array_merge($this->_get_display_columns(), array('Record ID' => 'id')),
            'value' => 'email',
        )
    );
    $this->plugin_settings[] = array(
        'name' => 'unique_email',
        'title' => __('Duplicate Record Preference', 'participants-database'),
        'group' => 'pdb-signup',
        'options' => array
            (
            'type' => 'dropdown',
            'help_text' => __('when the submission matches the Duplicate Record Check Field of an existing record. This also applies to importing records from a CSV file.', 'participants-database'),
            'value' => 1,
            'options' => array(
                __('Create a new record with the submission', 'participants-database') => 0,
                __('Overwrite matching record with new data', 'participants-database') => 1,
                __('Show a validation error message', 'participants-database') => 2,
                'null_select' => false,
            ),
        ),
    );

    $this->plugin_settings[] = array(
        'name' => 'duplicate_field_message',
        'title' => __('Duplicate Record Error Message', 'participants-database'),
        'group' => 'pdb-signup',
        'options' => array(
            'type' => 'text-area',
            'help_text' => __('If "Show a validation error message" is selected above, this message will be shown if a signup is made with a "check field" that matches an existing record.', 'participants-database'),
            'value' => __('A record with that %s already exists. Please choose another.', 'participants-database'),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'send_signup_receipt_email',
        'title' => __('Send Signup Response Email', 'participants-database'),
        'group' => 'pdb-signup',
        'options' => array(
            'type' => 'checkbox',
            'help_text' => __('Send a receipt email to people who sign up', 'participants-database'),
            'value' => 1,
            'options' => array(1, 0),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'receipt_from_address',
        'title' => __('Signup Email From Address', 'participants-database'),
        'group' => 'pdb-signup',
        'options' => array(
            'type' => 'text',
            'help_text' => __('the "From" address on signup receipt emails. If the recipient hits "reply", their reply will go to this address. It is a good idea to use an email address from the same domain as this website.', 'participants-database'),
            'value' => get_bloginfo('admin_email'),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'receipt_from_name',
        'title' => __('Signup Email From Name', 'participants-database'),
        'group' => 'pdb-signup',
        'options' => array(
            'type' => 'text',
            'help_text' => __('the "From" name on signup receipt emails.', 'participants-database'),
            'value' => get_bloginfo('name'),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'signup_receipt_email_subject',
        'title' => __('Signup Response Email Subject', 'participants-database'),
        'group' => 'pdb-signup',
        'options' => array(
            'type' => 'text',
            'help_text' => __('subject line for the signup response email; placeholder tags can be used (see below)', 'participants-database'),
            'value' => sprintf(__("You've just signed up on %s", 'participants-database'), get_bloginfo('name')),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'signup_receipt_email_body',
        'title' => __('Signup Response Email', 'participants-database'),
        'group' => 'pdb-signup',
        'options' => array(
            'type' => $this->textarea_type,
            'help_text' => __('Body of the email a visitor gets when they sign up. It includes a link ([record_link]) back to their record so they can fill it out. Can include HTML, placeholders:[first_name],[last_name],[email],[record_link]. You can only use placeholders for fields that are present in the signup form, including hidden fields.', 'participants-database'),
            /* translators: the %s will be the name of the website */
            'value' => sprintf(__('<p>Thank you, [first_name], for signing up with %s.</p><p>You may complete your registration with additional information or update your information by visiting this private link at any time: <a href="[record_link]">[record_link]</a>.</p>', 'participants-database'), get_bloginfo('name')),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'signup_thanks',
        'title' => __('Signup Thanks Message', 'participants-database'),
        'group' => 'pdb-signup',
        'options' => array(
            'type' => 'rich-text',
            'help_text' => __('Note to display on the web page after someone has submitted a signup form. Can include HTML and placeholders (see above)', 'participants-database'),
            'value' => __('<p>Thank you, [first_name] for signing up!</p><p>You will receive an email acknowledgement shortly. You may complete your registration with additional information or update your information by visiting the link provided in the email.</p>', 'participants-database'),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'send_signup_notify_email',
        'title' => __('Send Signup Notification Email', 'participants-database'),
        'group' => 'pdb-signup',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __('Send an email notification that a signup has occurred.', 'participants-database'),
            'value' => 1,
            'options' => array(1, 0),
        )
    );


    $this->plugin_settings[] = array(
        'name' => 'email_signup_notify_addresses',
        'title' => __('Signup Notification Recipients', 'participants-database'),
        'group' => 'pdb-signup',
        'options' => array(
            'type' => 'text',
            'help_text' => __('comma-separated list of email addresses to send signup notifications to', 'participants-database'),
            'value' => get_bloginfo('admin_email'),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'email_signup_notify_subject',
        'title' => __('Signup Notification Email Subject', 'participants-database'),
        'group' => 'pdb-signup',
        'options' => array(
            'type' => 'text',
            'help_text' => __('subject of the notification email; placeholder tags can be used (see above)', 'participants-database'),
            /* translators: the %s will be the name of the website */
            'value' => sprintf(__('New signup on %s', 'participants-database'), get_bloginfo('name')),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'email_signup_notify_body',
        'title' => __('Signup Notification Email', 'participants-database'),
        'group' => 'pdb-signup',
        'options' => array(
            'type' => $this->textarea_type,
            'help_text' => __('notification email body. The [admin_record_link] tag will supply the URL for editing the record in the admin.', 'participants-database'),
            'value' => __('<p>A new signup has been submitted</p><ul><li>Name: [first_name] [last_name]</li><li>Email: [email]</li></ul><p>Edit this new record here: <a href="[admin_record_link]">[admin_record_link]</a></p>', 'participants-database'),
        )
    );
    
    /*******************************************************
     * 
     * link retrieval settings
     * 
     *******************************************************/

    $this->plugin_settings[] = array(
        'name' => 'show_retrieve_link',
        'title' => __('Enable Lost Private Link', 'participants-database'),
        'group' => 'pdb-resend',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __('Show a link on the signup form allowing users to have their private link emailed to them.', 'participants-database'),
            'value' => 0,
            'options' => array(1, 0),
        )
    );
   
    $this->plugin_settings[] = array(
        'name' => 'retrieve_link_text',
        'title' => __('Lost Private Link Text', 'participants-database'),
        'group' => 'pdb-resend',
        'options' => array(
            'type' => 'text',
            'help_text' => __('clickable text shown in the signup form', 'participants-database'),
            'value' => __('Forget your private link? Click here to have it emailed to you.','participants-database'),
        )
    );
    $this->plugin_settings[] = array(
        'name' => 'link_retrieval_page',
        'title' => __('Lost Private Link Page', 'participants-database'),
        'group' => 'pdb-resend',
        'options' => array
            (
            'type' => 'dropdown-other',
            'help_text' => __('send people to this page to request their private link.', 'participants-database'),
            'options' => $this->_get_pagelist(true),
            'attributes' => array('other' => 'Post ID'),
            'value' => 'none',
        )
    );
   
    $this->plugin_settings[] = array(
        'name' => 'retrieve_link_identifier',
        'title' => __('Lost Private Link ID Field', 'participants-database'),
        'group' => 'pdb-resend',
        'options' => array(
            'type' => 'dropdown',
            'help_text' => __('The field used to identify the user&#39;s account. This must be a unique identifier for the record', 'participants-database'),
            'options' => $this->_get_identifier_columns(false),
            'value' => 'email',
        )
    );
   
    $this->plugin_settings[] = array(
        'name' => 'retrieve_link_email_subject',
        'title' => __('Lost Private Link Email Subject', 'participants-database'),
        'group' => 'pdb-resend',
        'options' => array(
            'type' => 'text',
            'help_text' => __('subject line for the lost private link email', 'participants-database'),
            'value' => sprintf(__("Here is your private link on %s", 'participants-database'), get_bloginfo('name')),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'retrieve_link_email_body',
        'title' => __('Lost Private Link Email', 'participants-database'),
        'group' => 'pdb-resend',
        'options' => array(
            'type' => $this->textarea_type,
            'help_text' => __('Body of the email sent when a lost private link is requested.', 'participants-database'),
            /* translators: the %s will be the name of the website */
            'value' => '<p>' . sprintf(__('Here is the private link you requested from %s:', 'participants-database'), get_bloginfo('name')) . '</p><p><a href="[record_link]">[record_link]</a>.</p>',
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'send_retrieve_link_notify_email',
        'title' => __('Send Lost Private Link Notification Email', 'participants-database'),
        'group' => 'pdb-resend',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __('Send an email notification that a lost private link has been requested. This email will go to the "Signup Notification Recipients."', 'participants-database'),
            'value' => 0,
            'options' => array(1, 0),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'retrieve_link_notify_subject',
        'title' => __('Lost Private Link Notification Email Subject', 'participants-database'),
        'group' => 'pdb-resend',
        'options' => array(
            'type' => 'text',
            'help_text' => __('subject of the notification email; placeholder tags can be used (see above)', 'participants-database'),
            /* translators: the %s will be the name of the website */
            'value' => sprintf(__('A Lost Private Link has been requested on %s', 'participants-database'), get_bloginfo('name')),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'retrieve_link_notify_body',
        'title' => __('Lost Private Link Notification Email', 'participants-database'),
        'group' => 'pdb-resend',
        'options' => array(
            'type' => $this->textarea_type,
            'help_text' => __('notification email body', 'participants-database'),
            'value' => __('<p>A lost private link has been requested by:</p><ul><li>Name: [first_name] [last_name]</li><li>Email: [email]</li></ul>', 'participants-database'),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'identifier_field_message',
        'title' => __('Retrieve Private Link Error Message', 'participants-database'),
        'group' => 'pdb-resend',
        'options' => array(
            'type' => 'text-area',
            'help_text' => __('Message shown when a record matching the retrieve link idenifier cannot be found', 'participants-database'),
            'value' => __('A record matching that %s cannot be found.', 'participants-database'),
        )
    );

    /*     * ****************************************************
     *
     *   record form settings
     *
     * **************************************************** */

    $this->plugin_settings[] = array(
        'name' => 'registration_page',
        'title' => __('Participant Record Page', 'participants-database'),
        'group' => 'pdb-record',
        'options' => array
            (
            'type' => 'dropdown-other',
            'help_text' => __('The page where your participant record ([pdb_record] shortcode) is displayed. You can use a Post ID for posts and custom post types.', 'participants-database'),
            'options' => $this->_get_pagelist(false,true),  
            'attributes' => array('other' => 'Post ID'),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'show_group_descriptions',
        'title' => __('Show Group Descriptions', 'participants-database'),
        'group' => 'pdb-record',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __('Show the group description under each group title in the record form.', 'participants-database'),
            'value' => 0,
            'options' => array(1, 0),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'save_changes_label',
        'title' => __('Save Changes Label', 'participants-database'),
        'group' => 'pdb-record',
        'options' => array
            (
            'type' => 'text',
            'help_text' => __('label for the save changes button on the record form', 'participants-database'),
            'value' => __('Save Your Changes', 'participants-database'),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'save_changes_button',
        'title' => __('Save Button Text', 'participants-database'),
        'group' => 'pdb-record',
        'options' => array
            (
            'type' => 'text',
            'help_text' => __('text on the "save" button', 'participants-database'),
            'value' => _x('Save', 'a label for a button to save a form', 'participants-database'),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'record_updated_message',
        'title' => __('Record Updated Message', 'participants-database'),
        'group' => 'pdb-record',
        'options' => array(
            'type' => 'text',
            'help_text' => __("the message shown when a record form has been successfully submitted", 'participants-database'),
            'value' => __('Your information has been updated', 'participants-database'),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'no_record_error_message',
        'title' => __('Record Not Found Error Message', 'participants-database'),
        'group' => 'pdb-record',
        'options' => array(
            'type' => 'text',
            'help_text' => __('message to show if the record page was accessed without a valid identifier. Leave this empty if you want nothing at all to show.', 'participants-database'),
            'value' => sprintf(__("No record was found.", 'participants-database'), get_bloginfo('name')),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'send_record_update_notify_email',
        'title' => __('Send Record Form Update Notification Email', 'participants-database'),
        'group' => 'pdb-record',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __('Send an email notification that a record has been updated. These will be sent to the email addresses listed in the "Signup Notification Recipients" setting.', 'participants-database'),
            'value' => 0,
            'options' => array(1, 0),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'record_update_email_subject',
        'title' => __('Record Update Email Subject', 'participants-database'),
        'group' => 'pdb-record',
        'options' => array(
            'type' => 'text',
            'help_text' => __('subject line for the record update notification email; placeholders can be used.', 'participants-database'),
            'value' => sprintf(__("A record has just been updated on %s", 'participants-database'), get_bloginfo('name')),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'record_update_email_body',
        'title' => __('Record Update Notification Email', 'participants-database'),
        'group' => 'pdb-record',
        'options' => array(
            'type' => $this->textarea_type,
            /* translators: [date] and [admin_record_link] must not be translated, they must be used literally. The rest of the words enclosed in brackets can be defined by the user, they are used here only as examples. */
            'help_text' => __('Body of the the email sent when a user updates their record. Any field from the form can be included by using a replacement code of the form: [field_name]. For instance: [last_name],[address],[email] etc. (The field name is under the "name" column on the "Manage Database Fields" page.)  Also available is [date] which will show the date and time of the update and [admin_record_link] tag for a link to edit the record in the admin.', 'participants-database'),
            'value' => __('<p>The following record was updated on [date]:</p><ul><li>Name: [first_name] [last_name]</li><li>Address: [address]</li><li>[city], [state], [country]</li><li>Phone: [phone]</li><li>Email: [email]</li></ul><p>Edit this record <a href="[admin_record_link]">here.</a></p>', 'participants-database'),
        )
    );


    /******************************************************
     *
     *   list display settings
     *
     * *****************************************************/

    $this->plugin_settings[] = array(
        'name' => 'list_limit',
        'title' => __('Records per Page', 'participants-database'),
        'group' => 'pdb-list',
        'options' => array(
            'type' => 'text',
            'help_text' => __('the number of records to show on each page', 'participants-database'),
            'attributes' => array('style' => 'width:40px'),
            'value' => 10,
        ),
    );

    $this->plugin_settings[] = array(
        'name' => 'single_record_link_field',
        'title' => __('Single Record Link Field', 'participants-database'),
        'group' => 'pdb-list',
        'options' => array
            (
            'type' => 'dropdown',
            'help_text' => __('select the field on which to put a link to the [pdb_single] shortcode. Leave blank or set to "none" for no link.', 'participants-database'),
            'options' => $this->_get_display_columns(),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'single_record_page',
        'title' => __('Single Record Page', 'participants-database'),
        'group' => 'pdb-list',
        'options' => array
            (
            'attributes' => array('other' => 'Post ID'),
            'type' => 'dropdown-other',
            'help_text' => __('this is the page where the [pdb_single] shortcode is located. If you want to assign a post or custom post type, select "Post ID" and enter the post ID in the "other" box.', 'participants-database'),
            'options' => $this->_get_pagelist(false,true),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'no_records_message',
        'title' => __('No Records Message', 'participants-database'),
        'group' => 'pdb-list',
        'options' => array(
            'type' => 'text',
            'help_text' => __('Message shown when no records are found.', 'participants-database'),
            'value' => __('No Records Found', 'participants-database'),
        )
    );
    $this->plugin_settings[] = array(
        'name' => 'show_count',
        'title' => __('Show Count', 'participants-database'),
        'group' => 'pdb-list',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __("Show the list count on list displays. Can also be set in the shortcode.", 'participants-database'),
            'value' => 0,
            'options' => array(1, 0),
        ),
    );
    
    $this->plugin_settings[] = array(
        'name' => 'count_template',
        'title' => __('List Count Template', 'participants-database'),
        'group' => 'pdb-list',
        'options' => array(
            'type' => 'text-area',
            'help_text' => sprintf(__('template for displaying the list count. %1$s - total number of records found, %2$s - number of records shown per page, %3$s - starting record number, %4$s - ending record number, %5$s - the current page number', 'participants-database'),'<br /><strong>%1$s</strong>','<strong>%2$s</strong>','<strong>%3$s</strong>','<strong>%4$s</strong>','<strong>%5$s</strong>'),
            'value' => __('Total Records Found: %1$s, showing %2$s per page', 'participants-database'),
            'attributes' => array(
                'style' => 'height: 4em;'
            ),
        ),
    );


    $this->plugin_settings[] = array(
        'name' => 'list_default_sort',
        'title' => __('List Default Sort', 'participants-database'),
        'group' => 'pdb-list',
        'options' => array
            (
            'type' => 'dropdown',
            'value' => 'date_updated',
            'help_text' => __('The record list shown by the list shortcode will be sorted by this field by default. (Field must be checked "sortable.")', 'participants-database'),
            'options' => $this->_get_sort_columns(),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'list_default_sort_order',
        'title' => __('List Default Sort Order', 'participants-database'),
        'group' => 'pdb-list',
        'options' => array(
            'type' => 'dropdown',
            'help_text' => __('Sets the default order of the records shown by the list shortcode.', 'participants-database'),
            'value' => 'desc',
            'options' => array(__('Ascending', 'participants-database') => 'asc', __('Descending', 'participants-database') => 'desc', 'null_select' => false),
        )
    );


    $this->plugin_settings[] = array(
        'name' => 'admin_default_sort',
        'title' => __('Admin List Default Sort', 'participants-database'),
        'group' => 'pdb-list',
        'options' => array
            (
            'type' => 'dropdown',
            'value' => 'date_updated',
            'help_text' => __('The record list shown in the admin section will be sorted by this field by default. (Field must be checked "sortable.")', 'participants-database'),
            'options' => $this->_get_sort_columns(),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'admin_default_sort_order',
        'title' => __('Admin List Default Sort Order', 'participants-database'),
        'group' => 'pdb-list',
        'options' => array(
            'type' => 'dropdown',
            'help_text' => __('Sets the default order of the record list in the admin.', 'participants-database'),
            'value' => 'desc',
            'options' => array(__('Ascending', 'participants-database') => 'asc', __('Descending', 'participants-database') => 'desc', 'null_select' => false),
        )
    );
    
    $this->plugin_settings[] = array(
        'name' => 'admin_thumbnails',
        'title' => __('Show Image Thumbnails in Admin List', 'participants-database'),
        'group' => 'pdb-list',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => '',
            'value' => 0,
            'options' => array(1, 0),
        ),
    );
    
    /******************************************************
     *
     *   advanced settings
     *
     * *****************************************************/

    $this->plugin_settings[] = array(
        'name' => 'use_plugin_css',
        'title' => __('Use the Plugin CSS', 'participants-database'),
        'group' => 'pdb-advanced',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __('use the plugin\'s CSS to style the output of shortcodes', 'participants-database'),
            'value' => 1,
            'options' => array(1, 0),
        ),
    );

    $this->plugin_settings[] = array(
        'name' => 'rich_text_editor',
        'title' => __('Use Rich Text Editor', 'participants-database'),
        'group' => 'pdb-advanced',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __('enable the rich text editor on "rich-text" fields for front-end users. If deselected, "rich-text" fields will appear as text-area fields. This does not affect admin users, who always have the use of the rich-text editor.', 'participants-database'),
            'value' => 1,
            'options' => array(1, 0),
        ),
    );

    $this->plugin_settings[] = array(
        'name' => 'enable_wpautop',
        'title' => __('Use WordPress Auto Formatting', 'participants-database'),
        'group' => 'pdb-advanced',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __('Use WordPress&#39; auto formatting on rich text fields.', 'participants-database'),
            'value' => 1,
            'options' => array(1, 0),
        ),
    );

    $this->plugin_settings[] = array(
        'name' => 'primary_email_address_field',
        'title' => __('Primary Email Address Field', 'participants-database'),
        'group' => 'pdb-advanced',
        'options' => array
            (
            'type' => 'dropdown',
            'help_text' => __('this field is the primary email address for the record', 'participants-database'),
            'value' => 'email',
            'options' => $this->_get_identifier_columns(),
        ),
    );

    $this->plugin_settings[] = array(
        'name' => 'html_email',
        'title' => __('Send HTML Email', 'participants-database'),
        'group' => 'pdb-advanced',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __('use rich text in plugin emails? If you turn this off, be sure to remove all HTML tags from the email body settings for the plugin.', 'participants-database'),
            'value' => 1,
            'options' => array(1, 0),
        ),
    );

    $this->plugin_settings[] = array(
        'name' => 'strict_dates',
        'title' => __('Strict Date Format', 'participants-database'),
        'group' => 'pdb-advanced',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => sprintf(__('This forces date inputs to be interpreted strictly according to the "Input Date Format" setting. You should tell your users what format you are expecting them to use. This also applies to date values used in [pdb_list] shortcode filters. The date with your current setting looks like this: <strong>%s</strong> %s', 'participants-database'), date(isset(Participants_Db::$plugin_options['input_date_format']) ? Participants_Db::$plugin_options['input_date_format'] : get_option('date_format')), (function_exists('date_create') ? '' : '<strong>(' . __('Your current PHP installation does not support this setting.', 'participants-database') . ' )</strong>')),
            'value' => 0,
            'options' => array(1, 0),
        ),
    );

    $this->plugin_settings[] = array(
        'name' => 'input_date_format',
        'title' => __('Input Date Format', 'participants-database'),
        'group' => 'pdb-advanced',
        'options' => array
            (
            'type' => 'text-line',
            'help_text' => __('date formatting string for all plugin date inputs when "Strict Date Format" is enabled. You should use this for all localized (non-American English) date formats.', 'participants-database'),
            'value' => get_option('date_format'),
        ),
    );

    $this->plugin_settings[] = array(
        'name' => 'show_time',
        'title' => __('Show Timestamp Time', 'participants-database'),
        'group' => 'pdb-advanced',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __('Show time with timestamp dates', 'participants-database'),
            'value' => 0,
            'options' => array(1, 0),
        ),
    );

    $this->plugin_settings[] = array(
        'name' => 'record_edit_capability',
        'title' => __('Record Edit Access Level', 'participants-database'),
        'group' => 'pdb-advanced',
        'options' => array(
            'type' => 'dropdown',
            'help_text' => __('sets the user access level for adding, editing and listing records.', 'participants-database'),
            'value' => 'edit_others_posts',
            'options' => $this->get_role_select(),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'plugin_admin_capability',
        'title' => __('Plugin Admin Access Level', 'participants-database'),
        'group' => 'pdb-advanced',
        'options' => array(
            'type' => 'dropdown',
            'help_text' => __('sets the user access level for fields management, plugin settings, deleting records and CSV operations.', 'participants-database'),
            'value' => 'manage_options',
            'options' => $this->get_role_select(),
        )
    );

    $this->plugin_settings[] = array(
        'name' => 'strict_search',
        'title' => __('Strict User Searching', 'participants-database'),
        'group' => 'pdb-advanced',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __('When checked, the frontend list search must match the whole field exactly. If unchecked, the search will match if the search term is found in part of the field. Searches are not case-sensitive either way.', 'participants-database'),
            'value' => 0,
            'options' => array(1, 0),
        ),
    );
    $this->plugin_settings[] = array(
        'name' => 'ajax_search',
        'title' => __('Enable AJAX Search Functionality', 'participants-database'),
        'group' => 'pdb-advanced',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __("This enables list search results that are updated without reloading the page. It requires Javascript, but search will still work if Javascript is disabled in the user's browser.", 'participants-database'),
            'value' => 1,
            'options' => array(1, 0),
        ),
    );

    $this->plugin_settings[] = array(
        'name' => 'use_php_sessions',
        'title' => __('Use PHP Sessions', 'participants-database'),
        'group' => 'pdb-advanced',
        'options' => array
            (
            'type' => 'checkbox',
            'help_text' => __('uncheck this if PHP sessions are not working.', 'participants-database'),
            'value' => 1,
            'options' => array(1, 0),
        ),
    );


    /******************************************************
     *
     *   custom CSS setting
     *
     * *****************************************************/
    $this->plugin_settings[] = array(
        'name' => 'custom_css',
        'title' => __('Custom Plugin Stylesheet', 'participants-database'),
        'group' => 'pdb-css',
        'options' => array(
            'type' => 'text-area',
            'value' => '',
            'help_text' => __('use this to add your own styles or override styles applied to the output of all plugin shortcodes', 'participants-database'),
            'attributes' => array(
              'style'=>"height:20em;width:90%;max-width:400px;",
              'class' => 'code',
              'lang' => 'CSS'
              ),
        )
    );
    $this->plugin_settings[] = array(
        'name' => 'print_css',
        'title' => __('Plugin Print Stylesheet', 'participants-database'),
        'group' => 'pdb-css',
        'options' => array(
            'type' => 'text-area',
            'value' => "/* this prevents the search controls from printing */\r.pdb-searchform {\r\tdisplay:none;\r}",
            'help_text' => __('use this to format the printed output of all plugin shortcodes', 'participants-database'),
            'attributes' => array(
              'style'=>"height:20em;width:90%;max-width:400px;",
              'class' => 'code',
              'lang' => 'CSS'
              ),
        )
    );
  }

  private function _get_pagelist($with_none = false, $with_blank = false) {

    $pagelist = array();
    
    if ($with_blank) $pagelist['null_select'] = '';

    if ($with_none)
      $pagelist[__('Same Page', 'participants-database')] = 'none';

    $pages = get_pages(array());

    foreach ($pages as $page) {

      $pagelist[$page->post_title] = $page->ID;
    }

    /*
     * if you wish to include posts in the list of pages where the shortcode can be found, uncomment this block of code
     */
    /*

      $posts = get_posts( array( 'numberposts' => -1 ) );

      foreach( $posts as $post ) {

      $pagelist[ $post->post_title ] = $post->ID;

      }
     */

    return $pagelist;
  }

  private function _get_display_columns() {

    $columnlist = array(__('None', 'participants-database') => 'none', 'null_select' => false);

    $columns = Participants_Db::get_column_atts('all');

    foreach ($columns as $column) {

      if (in_array($column->form_element, array('text-line', 'image-upload', 'checkbox')))
        $columnlist[$column->title] = $column->name;
    }

    return $columnlist;
  }

  /**
   * this provides a set of fields to select from
   * 
   * @param bool $null if true include a null value
   * @global object $wpdb
   * @return array of fields as $title => $value
   */
  private function _get_identifier_columns($null = true) {

    global $wpdb;

    $columnlist = $null ? array() : array('null_select' => false);

    $sql = 'SELECT v.*, g.order FROM ' . Participants_Db::$fields_table . ' v INNER JOIN ' . Participants_Db::$groups_table . ' g ON v.group = g.name AND v.form_element IN ("text-line","hidden") ORDER BY g.order, v.order';

    $columns = $wpdb->get_results($sql, OBJECT_K);

    foreach ($columns as $column) {
        $columnlist[$column->title] = $column->name;
    }

    return $columnlist;
  }

  private function _get_sort_columns() {

    $columnlist = array('null_select' => false);

    $columns = Participants_Db::get_column_atts('sortable');

    foreach ($columns as $column) {

      $columnlist[$column->title] = $column->name;
    }

    return $columnlist;
  }
  
  /**
   * get the user roles in a form suitable for populating a dropdown
   * 
   * for custom roles, this looks for a unique capability assigned to the custom 
   * role. If there is no unique capability assigned to the custom role, it will 
   * not be offered as a choice in the setting
   * 
   * @return array all defined roles with a key capability
   */
  public static function get_role_select()
  {
    // these are the standard roles we will include
    $role_select = array(
        __('Contributor', 'participants-database') => 'edit_posts', 
        __('Author', 'participants-database') => 'edit_published_posts', 
        __('Editor', 'participants-database') => 'edit_others_posts', 
        __('Admin', 'participants-database') => 'manage_options',
        'null_select' => false,
        );
    global $wp_roles;
    $roles = $wp_roles->roles;
    //error_log(__METHOD__.' roles:'.print_r($roles,1));
    $caps = array();
    // collect all standard capabilities and remove standard roles
    foreach(array('administrator','editor','author','contributor','subscriber') as $role) {
      if ($role !== 'administrator' && is_array($roles[$role]['capabilities'])) $caps += array_keys($roles[$role]['capabilities']);
      unset($roles[$role]);
    }
    // add any custom roles
    if (count($roles) > 0) {
      foreach($roles as $role) {
        $new_caps = '';
        if (is_array($role['capabilities'])) {
        $new_caps = array_diff(array_keys($role['capabilities']), $caps);
        }
        if (!empty($new_caps)) {
          // we grab the first unique capability and use it to typify the role
          $role_select[$role['name']] = current($new_caps);
        }
      }
    }
    return $role_select;
  }
  
  
   
   /**
    * grabs a list of available collations
    */
  public static function get_collation_list() {
    global $wpdb;
    $character_sets = $wpdb->get_results('SHOW CHARACTER SET');
    
    $return = array('null_select' => false);
    foreach($character_sets as $set) {
      $return[$set->Description] = $set->{'Default collation'};
    }
    return $return;
    
  }
  
  /**
    * gets the collation of the database
    */
  public static function get_collation() {
    global $wpdb;
    $character_set = $wpdb->get_row('SHOW TABLE STATUS WHERE `name` = "' . $wpdb->prefix . 'participants_database"');

    return $character_set->Collation;
    
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
      <?php Participants_Db::admin_page_heading(Participants_Db::$plugin_title . ' ' . __('Settings', 'participants-database')) ?>
      <?php settings_errors(); ?>
      <form action="options.php" method="post" >
        <div class="ui-tabs">
          <?php /* ?>
          <h2 class="nav-tab-wrapper">
            <?php foreach ($this->sections as $id => $title)
              printf('<a class="nav-tab" href="#%s">%s</a>', Participants_Db::make_anchor($id), $title);
            ?>
          </h2>
          <?php */ ?>
          <ul class="ui-tabs-nav">
            <?php foreach ($this->sections as $id => $title)
              printf('<li><a href="#%s">%s</a></li>', Participants_Db::make_anchor($id), $title);
            ?>
          </ul>
          <?php
          settings_fields($this->WP_setting);

          do_settings_sections($this->settings_page);
          ?>
        </div>

        <?php
        $args = array(
            'type' => 'submit',
            'class' => $this->submit_class,
            'value' => $this->submit_button,
            'name' => 'submit',
        );

        printf($this->submit_wrap, PDb_FormElement::get_element($args));
        ?>
      </form>

    </div>
    <?php
  }

  /**
   * displays a section subheader
   *
   * note: the header is displayed by WP; this is only what would go under that
   */
  public function options_section($section) {

    $parts = explode('_', $section['id']);
    $name = Participants_db::make_anchor(end($parts));  

    printf('<a id="%1$s" name="%1$s" class="%2$s" ></a>', $name, Participants_Db::$prefix . 'anchor');

    if (isset(self::$section_description[$name]))
      printf('<div class="section-description" ><h4>%s</h4></div>', self::$section_description[$name]);
  }

  /**
   * validate settings
   * 
   * we define a number of validation tests for submitted settings
   * 
   * @param array $settings an array of all the settings andtheir submitted values
   * @return array the sanitized array
   */
  public function validate($settings) {

    foreach ($settings as $name => $value) {

      switch ($name) {

        case 'empty_field_message':
        case 'invalid_field_message':
        case 'nonmatching_field_message':

          $settings[$name] = strip_tags($value);
          break;

        case 'field_error_style':

          // test for CSS rule consistency
          $value = rtrim($value, ';') . ';';
          $num_matches = preg_match_all("%[ ]?([^:<> ]+\:[^:;<>]+;)%", $value, $matches);
          if ($num_matches > 0) {
            $settings[$name] = implode('', $matches[1]);
          }
          else
            $settings[$name] = '';
          break;
        case 'list_limit':
        case 'image_upload_limit':
          if (intval($value) < 1) {
            add_settings_error( $name, $name, sprintf(__('Only numeric values can be used for the "%s" setting.', 'participants-database'), $this->get_option_title($name)), 'error' );
            $settings[$name] = $this->get_default_value($name);
          }
      }
    }
    return $settings;
  }

}
?>