<?php

/*
 * class for handling all plugin email functionality
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdeign@xnau.com>
 * @copyright  2011 xnau webdesign
 * @license    GPL2
 * @version    0.3
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    TemplateEmail class
 */

class PDb_TemplateEmail extends TemplateEmail {
  
  /**
   * creates the TemplateEmail object
   * 
   * if the optional tags or id argument is not supplied, an email message without tag 
   * replacements will be sent
   * 
   * some fields are optional*
   * 
   * @param array $params array of parameters for the email message, several of which are optional*
   *  headers    string the email header string*
   *  mail_func  string the mail function to call; defaults to wp_mail*
   *  recipients mixed  the recipient or recipients (if empty, use email from tags array)
   *  id         string the record id to draw the replacement tag data from*
   *  tags       array  associative array of tag replacements*
   *  subject    string email subject with tags
   *  body       string the body of the email with tags
   *   
   */
  function __construct($params) {
    
    Participants_Db::$sending_email = true;
    $this->set_subject($params['subject']);
    $this->set_body($params['body']);
    $this->set_headers(@$params['headers']);
    
    if ( isset($params['id']) && ! empty($params['id']) ) {
      
      $this->set_tags_from_record($params['id']);
    } else {
      
      $this->set_tags($params['tags']);
    }
    
    error_log(__METHOD__.' tags:'.print_r($this->tags,1));
    
    $this->set_send_to($params['recipients']);
    
    parent::__construct($params);
    
  }
  
  /**
   * sends the email
   * 
   */
  function send() {
    
    $this->_send_email();
    Participants_Db::$sending_email = false;
  }
  
  /**
   * sets up the tags array given a record ID
   * 
   * all values are preprared for display
   * 
   * @param string $id   the id of the record to use for the tag values
   * @param string $mode determines the subset of fields to include in the tags 
   *                     as defined in Participants_Db::get_colun_atts()
   */
  function set_tags_from_record( $id, $mode = 'frontend') {
    
    $participant = Participants_Db::get_participant($id);

    foreach (Participants_Db::get_column_atts($mode) as $column) {
      
      if ($column->name == 'email') $this->tags['pdb-user-email-address'] = $participant['email'];

      $this->tags[$column->name] = Participants_Db::prep_field_for_display($participant[$column->name], $column->form_element);
    }
    
    $this->tags['id'] = $id;
    
    $this->tags['private_id'] = $participant['private_id'];

    // add the "record_link" tag
    $this->tags['record_link'] = Participants_Db::get_record_link( $participant['private_id'] );

    // add the date tag
    $this->tags['date'] = date_i18n(Participants_Db::$date_format, Participants_Db::parse_date());
    
    // add the admin record link tag
    $this->tags['admin_record_link'] = Participants_Db::get_admin_record_link($id);
    
  }
  
  /**
   * sets the email headers
   * 
   * @param string $headers the header string
   */
  function set_headers($headers = false) {
    
    $this->setup['headers'] = $headers ? $headers : Participants_Db::$email_headers;
  }
  
  
}

?>
