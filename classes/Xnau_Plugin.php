<?php
/**
 * xnau generic WordPress plugin class
 *
 * lays out a set of basic functionalities useful to a WordPress plug-in
 * set up as master class to be called statically
 * 
 *
 * PHP version 5
 *
 * @package    WordPress
 * @author     Roland Barker <webdeign@xnau.com>
 * @copyright  2011 xnau webdesign
 * @license    GPL2
 * @version    0.1
 * @link       http://xnau.com/wordpress-plugins/
 *
 */
 class Xnau_Plugin {

	// plugin options name
	public static $options;
	
	// locations
	public static $plugin_page;
	public static $plugin_path;
	public static $uploads_url;
	public static $uploads_path;
	
	public function initialize() {
	}
	
	/**
	 * sets up the settings for the plugin using the WP Settings API
	 *
	 * @param string $name name of the WP option to save the settings in
	 * @param mixed  $validation (optional) defines a method for validating options
	 * @param array  $sections array of all the settings sections to define
	 *                  name unique string identifier for the section
	 *                  title display title for the section
	 *                  callback (optional) method to use to display the section
	 *                  page (optional) handle for the settings page
	 */
	protected function _init_settings( $name, $validation = false, $sections = false ) {
		
		$validation_method = false === $validation ? array(__CLASS__, 'options_validate') : $validation;
		
		register_setting(
				$name, 
				$name, 
				$validation_method
				);
		
		// supply a default section
		if ( false === $sections || ! is_array( $sections ) ) {
			
			$section = array(
											 'name'=> $name.'_general_settings',
											 'title' => 'General Settings',
											 'callback' => array(__CLASS__, 'options_section' ),
											 'page'     => $name.'_settings_page',
											 );
			$sections[] = $section;
			
		}
		
		// register the sections
		foreach ( $sections as $section ) {
	
			add_settings_section(
					$section['name'], 
					$section['title'],
					$section['callback'], 
					$settings_page
					);
		}
		
	}
	
	/**
	 * registers each setting for the plugin using the WP Settings API
	 *
	 * @param array $settings contains all the settings for the plugin
	 *            for each array element:
	 *                     section the section to place the stting in
	 *                     id string unique string identifier
	 *                     title string a display title for the setting
	 *                     page string page id to show the setting
	 *                     options array an array containing any additional properties to add to the setting
	 * 
	 */
	protected function _register_settings( $settings ) {
	}