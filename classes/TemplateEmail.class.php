<?php
/*
 * Template Email class
 *
 * Handles the sending of email messages with the use of templates
 *
 * @package    WordPress
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2013, xnau webdesign
 * @license    GPL2
 * @version    0.5
 * 
 */
class TemplateEmail {
  
  /**
   * an array of arrays, each element defining a destination email address, name and possibly more
   *
   * each element:
   *    'name' => name of the recipient
   *    'email' => recipient's email address
   * 
   * @var array
   */
  var $send_to;
  
  /**
   * an array of setup values
   *  headers   string the email header string
   *  mail_func string the mail function to call; defaults to wp_mail
   *  send_to   mixed  the recipient or recipients- could be string, array or comma-separated addresses
   *  id        string id of the record to access for tag replacments
   *  tags      array  associative array of tag replacements
   *  subject   string email subject with tags
   *  body      string the body of the email with tags
   *     
   * @var array
   */
  var $setup;
  
  /**
   * the subject of the email
   *
   * this string may contain replacement tags
   *
   * @var string
   */
  var $subject;
  
  /**
   * the body of the email message
   *
   * can include replacement tags
   *
   * @var string
   */
  var $body;
  
  /**
   * the tag values indexed by name
   *
   * @var array
   */
  var $tags = false;
  
  /**
   * initializes the TemplateEmail object
   *
   * sets up the parent object with values for the current message
   *
   * @param array $setup the setup array
   *  headers   string the email header string
   *  mail_func string the mail function to call; defaults to wp_mail
   * 
   */
  function __construct($setup) {
    
    $setup_defaults = array(
        'headers'   => "MIME-Version:1.0\nContent-Type:text/html; charset=utf-8\n",
        'mail_func' => 'wp_mail',// defaults to WordPress' mail function
    );
    
    $this->setup = array_merge($setup, $setup_defaults);
    
  }
  
  public function get_tags() {
    
    return $this->tags;
  }
  
  /**
   * sets the list of tags names
   *
   * @var array $tags an associative array of strings defining the tag names and values to replace
   */
  function set_tags($tags) {
    
    $this->tags = $tags;
  }
  
  /**
   * sets the array of recipients
   *
   * this gets an array of arrays or a single associative array
   *
   * @var array $recipients
   */
  function set_send_to($recipients) {
    
    if (empty($recipients) and $this->tags)
      $recipients = $this->tags['pdb-user-email-address'];
    
    if (is_string($recipients) ) $recipients = explode(',',str_replace(' ','',$recipients));
    
    foreach( $recipients as $recipient ) {

      if ( is_array($recipient)) {
        $this->send_to[] = $recipient;
      } else {
        $this->send_to[] = array(
          'email' => $recipient,
        );
      }
    }
  }
  
  /**
   * sets the subject of the email
   *
   * this string may contain replacement tags
   *
   * @var string $subject the subject
   */
  function set_subject($subject) {
    
    $this->subject = $subject;
  }
  
  /**
   * sets the body of the email
   *
   * this string may contain replacement tags
   *
   * @var string $body the body
   */
  function set_body($body) {
    
    $this->body = $body;
  }
  
  /**
   * sets the email headers
   * 
   * @param string $headers the header string
   */
  function set_headers($headers) {
    
    if (! isset($this->setup['headers'])) $this->setup['headers'] = $headers;
  }
  
  /**
   * sends the email
   * 
   * 
   */
  protected function _send_email() {
    
    // array of recipients
    $to = array();
    
    foreach( $this->send_to as $recipient) {
      $to[] = isset($recipient['name']) ? $recipient['name'] . ' <' . $recipient['email'] . '>' : $recipient['email'];
    }
    
    $headers = array();
    
    if ( ! empty($this->setup['headers']) )
      $headers = $this->setup['headers'];
    else
      $headers = NULL;
    
    // if HTML email, add a text body for better deliverability
    if ( $this->setup['mail_func'] == 'wp_mail' && false !== stripos($this->setup['headers'],'html' ) )
            add_action( 'phpmailer_init', array( $this, 'set_alt_body') );
    
    call_user_func($this->setup['mail_func'], $to, $this->_proc_tags($this->subject), $this->_proc_tags($this->body), $headers);
    
  }

  /**
   * replace the tags in text messages
   * 
   * this expects the property $this->tags to have been set up, but it will simply 
   * return the unprocessed input if it has not, such as if an email without tags 
   * is to be sent.
   *
   * @param string $text   the unprocessed text with tags
   *
   * @return string
   *
   */
	protected function _proc_tags( $text ) {
    
    if (is_array($this->tags)) {
    
      return self::proc_tags($text, $this->tags);
      
    } else return $text;

	}

  /**
   * replace the tags in text messages with the supplied replacement values
   *
   * @param string $text   the unprocessed text with tags
   * @param array  $tags   associative array if replacement tags/values
   *
   * @return string the text with the replacements made
   *
   */
	public static function proc_tags( $text, $tags ) {
    
    $tag_search = array();
    $tag_values = array();
    $placeholders = array();
    $i = 1;

    foreach ($tags as $tag => $value ) {

      $tag_search[] = '[' . $tag . ']';
      $tag_values[] = $value;
      $placeholders[] = '%'.$i.'$s';

      $i++;

    }

    // replace the tags with variables
    $pattern = str_replace( $tag_search, $placeholders, $text );

    // replace the variables with strings
    return vsprintf( $pattern, $tag_values );

	}
  
  /**
   * set the PHPMailer AltBody property with the text body of the email
   * 
   * @param object $phpmailer an object of type PHPMailer
   * @return null
   */
  public function set_alt_body( &$phpmailer ) {
    
    if ( is_object( $phpmailer )) $phpmailer->AltBody = $this->_make_text_body ($this->_proc_tags($this->body));
  }
  
  /**
   * strips the HTML out of an HTML email message body to provide the text body
   * 
   * this is a fairly crude conversion here. I should include some kind of 
   * library to do this properly. 
   * 
   * 
   * @param string $HTML the HTML body of the email
   * @return string
   */
  private function _make_text_body( $HTML ) {
    
    return strip_tags( preg_replace('#(</[p|h1|h2|h3|h4|h5|h6|div|tr|li]{1,3} *>)#i', "\r", $HTML) );
  }
}