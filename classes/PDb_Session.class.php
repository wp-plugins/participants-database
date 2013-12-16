<?php

/*
 * class description
 *
 * @package    WordPress
 * @subpackage Participants Database Plugin
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2013 xnau webdesign
 * @license    GPL2
 * @version    0.3
 * @link       http://xnau.com/wordpress-plugins/
 * @depends    FormElement class, Shortcode class
 * 
 * based on EDD_Session class by Pippin Williamson
 * https://github.com/easydigitaldownloads/Easy-Digital-Downloads/blob/master/includes/class-edd-session.php
 */

class PDb_Session {

	/**
	 * Holds the session data
	 *
	 * @var array
	 */
	private $session = array();


	/**
	 * Whether to use PHP $_SESSION or WP_Session
	 *
	 * PHP $_SESSION is opt-in only by defining the PDB_USE_PHP_SESSIONS constant
	 *
	 * @var bool
	 */
	private $use_php_sessions = false;
  
  /**
   * a name for our session array
   * 
   * @var string
   */
  public $session_name;


	/**
	 * construct the class
   * 
   * the global PDB_USE_PHP_SESSIONS must be set to true to use PHP sessions, it's 
   * not automatic because prior to PHP 5.4 it's difficult to determine if sessions 
   * are working without a page load...and by then it's too late.
   * 
   * we are just using this alternate form of session mnagement instead of PHP 
   * sessions for now
	 */
	public function __construct() {

		$this->use_php_sessions = defined( 'PDB_USE_PHP_SESSIONS' ) && PDB_USE_PHP_SESSIONS;
    
    $this->session_name = Participants_Db::$prefix . 'session';

		if( $this->use_php_sessions ) {

			// Use PHP SESSION (must be enabled via the PDB_USE_PHP_SESSIONS constant)

			if( ! session_id() )
				add_action( 'init', 'session_start', -2 );

		} else {

			// Use WP_Session (default)

			if ( ! defined( 'WP_SESSION_COOKIE' ) )
				define( 'WP_SESSION_COOKIE', Participants_Db::$prefix . 'wp_session' );
				
      require_once plugin_dir_path(__FILE__) . 'wp-session.inc.php';

		}

		if ( empty( $this->session ) && ! $this->use_php_sessions ) {
			add_action( 'plugins_loaded', array( $this, 'init' ), -1 );
		} else {
			add_action( 'init', array( $this, 'init' ), -1 );
		}
	}


	/**
	 * Setup the WP_Session instance
   * 
	 * @return void
	 */
	public function init() {

		if( $this->use_php_sessions )
			$this->session = isset( $_SESSION[$this->session_name] ) && is_array( $_SESSION[$this->session_name] ) ? $_SESSION[$this->session_name] : array();
		else
			$this->session = WP_Session::get_instance();

		return $this->session;
	}


	/**
	 * get the session ID
   * 
	 * @return string Session ID
	 */
	public function get_id() {
		return $this->session->session_id;
	}


	/**
	 * get a session variable
   * 
	 * @param string $key Session key
	 * @return string Session variable
	 */
	public function get( $key ) {
		$key = sanitize_key( $key );
		return isset( $this->session[ $key ] ) ? maybe_unserialize( $this->session[ $key ] ) : false;
	}

	/**
	 * Set a session variable
	 *
	 * @param $key Session key
	 * @param $value Session variable
	 * @return mixed Session variable
	 */
	public function set( $key, $value ) {
		$key = sanitize_key( $key );

		if ( is_array( $value ) )
			$this->session[ $key ] = serialize( $value );
		else
			$this->session[ $key ] = $value;

		if( $this->use_php_sessions )
			$_SESSION[$this->session_name] = $this->session;

		return $this->session[ $key ];
	}
  /**
   * clears a session variable
   * 
   * @param string $name the name of the variable to get
   * @return null
   */
  public function clear($name) {
    
    $this->set($name, false);

	}
}


?>
