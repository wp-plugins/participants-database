<?php

/*
 * send an email using a template with placeholder tags 
 * 
 * the placeholder tags are mainly intending to be replaced with data from a PDB 
 * record, but it can also be supplied an associative array
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2015  xnau webdesign
 * @license    GPL2
 * @version    0.3
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    
 */

class PDb_Template_Email {
  /**
   * @var string holds the "to" email field
   */
  private $email_to;
  /**
   * @var string holds the "from" email field
   */
  private $email_from;
  /**
   * @var string holds the email subject
   */
  private $email_subject;
  /**
   * @var string holds the raw email body template
   */
  private $email_template;
  /**
   * @var array and associative array of values for use by the template
   */
  private $data;
  /**
   * instantiates the class instance
   * 
   * @param array $config array of values to use in the email
   *                'to' => $email_to
   *                'from' => $email_from
   *                'subject' => $email_subject
   *                'template' -> $email_template
   * @param int|array $data if an integer, gets the PDB record with that ID, if 
   *                        array, uses it as the data source; must be associative 
   *                        array with fields labeled
   */
  function __construct($config, $data = false)
  {
    $this->setup_email_configuration($config);
    $this->data = $this->setup_data($data);
  }
  /**
   * sends the email
   * 
   * @return bool true if successful
   */
  public function send_email() {
    return $this->_mail($this->email_to, $this->email_subject, $this->process_template());
  }
  /**
   * sends a templated email
   * 
   * this function allwos for the simple sending of an email using a static function
   * 
   * @param array $config array of values to use in the email
   *                'to' => $email_to
   *                'from' => $email_from
   *                'subject' => $email_subject
   *                'template' -> $email_template
   * @param int|array $data if an integer, gets the PDB record with that ID, is 
   *                        array, uses it as the data source; must be associative 
   *                        array with fields labeled
   */
  public static function send($config, $data) {
    $instance = new self($config, $data);
    return $instance->send_email();
  }
  /**
   * processes the placeholder tags in the template
   * 
   * @return string the processed template
   */
  private function process_template() {
    if (preg_match('/\[.+\]/', $this->email_template) > 0) {
      return $this->replace_tags($this->email_template, $this->data);
    }
  }
  
  /**
   * sends a mesage through the WP mail handler function
   *
   * @param string $recipients comma-separated list of email addresses
   * @param string $subject    the subject of the email
   * @param string $body       the body of the email
   *
   * @return bool success
   */
  private function _mail($recipients, $subject, $body) {

    if (WP_DEBUG) error_log(__METHOD__.'
      
header:'.$this->email_header().'
to:'.$recipients.' 
subj.:'.$subject.' 
message:
'.$body 
            );

    $sent = wp_mail($recipients, $subject, $body, $this->email_header());

    if (false === $sent)
      error_log(__METHOD__ . ' sending failed for: ' . $recipients);
    return $sent;
  }
  /**
   * supplies an email header
   * 
   * @return string
   */
  private function email_header() {
    return 'From: ' . $this->email_from . "\n" .
    'Content-Type: text/html; charset="' . get_option('blog_charset') . '"' . "\n";
  }

  /**
   * maps a sets of values to "tags"in a template, replacing the tags with the values
   * 
   * @param string $text the tag-containing template string
   * @param array  $data array of record values: $name => $value
   * 
   * @return string template with all matching tags replaced with values
   */
  private function replace_tags($text, array$data) {

    $values = $tags = array();

    foreach ($data as $name => $value) {

      $tags[] = '[' . $name . ']';

      $values[] = $value;
    }

    // add the "record_link" tag
    if (isset($data['private_id'])) {
      $tags[] = '[record_link]';
      $values[] = Participants_Db::get_record_link($data['private_id']);
    }

    // add the date tag
    $tags[] = '[date]';
    $values[] = date_i18n(Participants_Db::$date_format, Participants_Db::parse_date());

    // add the time tag
    $tags[] = '[time]';
    $values[] = date_i18n(get_option('time_format'), Participants_Db::parse_date());

    $placeholders = array();

    for ($i = 1; $i <= count($tags); $i++) {

      $placeholders[] = '%' . $i . '$s';
    }

    // replace the tags with variables
    $pattern = str_replace($tags, $placeholders, $text);

    // replace the variables with strings
    return vsprintf($pattern, $values);
    
  }
  /**
   * sets up the email parameters
   * 
   * @param array $config the config array
   * 
   * @return null
   */
  private function setup_email_configuration($config) {
    $this->email_to = $config['to'];
    $this->email_from = $config['from'];
    $this->email_subject = $config['subject'];
    $this->email_template = $config['template'];
  }
  /**
   * sets up the data source
   * 
   * @param array|int $data
   * 
   * @return array
   */
  private function setup_data($data = false) {
    if (is_array($data)) {
      return $data;
    }
    if (is_numeric($data) && $record = Participants_Db::get_participant($data)) {
      return $record;
    }
    return array();
  }
}

?>
