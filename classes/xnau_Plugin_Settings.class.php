<?php
/**
 * plugin settings handler class
 *
 * uses the WP Settings API to manage settings, interactions with settings,
 * display and editing of settings for a plugin. It would normally be extended
 * by a plugin-specific subclass where the specific settings would be defined.
 * This class is designed to be a simple implementation, not suitable for
 * plugins requiring a more complex settings scheme, such as multiple pages. It
 * will work well with Javascript tabs, however.
 *
 * @depends xnau_FormElement class
 */
class xnau_Plugin_Settings {

  // class name of the plugin-specific subclass
  private $plugin_class;
  
  // WP settings label
  protected $WP_setting;

  // all registered sections
  protected $sections;
  // descriptions for each section as needed;
  static $section_description;

  // all individual settings
  protected $plugin_settings;

  // settings page slug
  protected $settings_page;

  // help text wrap HTML
  protected $help_text_wrap;

  // wrapper HTML for the settings page submit button
  protected $submit_wrap;

  // classname for the submit button
  protected $submit_class;

  // label for the submit button
  protected $submit_button;

  /**
   * @var string type to use for text area settings; rich or plain text
   */
  var $textarea_type = 'text-area';

  /**
   * constructor
   *
   * @param string $class    the classname of the extending subclass (required)
   * @param string $label    a unique string label for the set of settings for
   *                         the plugin (required)
   * @param array  $sections an array of name/title pairs defining the settings
   *                         sections (optional)
   */
  public function __construct( $class = false, $label = false, $sections = false ) {

    if ( false === $class ) die( __CLASS__.' class must be instantiated by a plugin-specific subclass' );
    if ( false === $label ) die( 'you must supply a settings label string');

    $this->plugin_class = $class;

    $this->WP_setting = $label;

    $this->settings_page = $this->WP_setting.'_settings_page';

    // set up the HTML for the built-in display functions
    // these are generic settings to be modified by the subclass
    $this->help_text_wrap = '<span class="helptext">%s</span>';
    $this->submit_wrap = '<p class="submit">%s</p>';
    $this->submit_class = 'button-primary';
    $this->submit_button = 'Save Settings';

    // define a default settings section so that setting up sections is optional
    $this->sections = false === $sections ? array( 'main' => 'General Settings' ) : (array) $sections;

    // register the plugin setting with WP
    // this will store an array of all the individual settings for the plugin
    register_setting(
        $this->WP_setting,
        $this->WP_setting,
        array( $this, 'validate'));

  }
	
	/**
	 * registers the individual plugin options
	 */
  public function initialize() {
		
		// register the individual settings
    if (function_exists('add_settings_field')) {
		$this->_register_options();
      // register the sections
      $this->_register_sections();
	}
  }
		

  /*************************
   * PUBLIC METHODS
   */

  /**
   * updates or adds an option value
   *
   * @param string  $option_name name of the option to update
   * @param string  $value value to use
   * @param bool    $overwrite if true, overwrite the value, false to write vlaue only if not present
   */
  public function update_option( $option_name, $value, $overwrite = true ) {

    if ( ! isset( $option_name ) ) return false;

    $options = get_option( $this->WP_setting );

    if ( false === $overwrite && isset( $options[ $option_name ] ) ) {
      return true;
    } else {
      $options[ $option_name ] = $value;
    }

    return update_option( $this->WP_setting, $options );

  }

  /**
	 * retrieves an option value
	 *
	 * yes, there is a WP function of the same name, but this is the beauty of
	 * using classes: the class name tacked onto the front will nicely distinguish
	 * the function from its WP counterpart
	 *
	 * @param string $option_name
	 * @return the value of the option or false
	 */
  public function get_option( $option_name ) {

    $options = get_option( $this->WP_setting );

    //error_log( __METHOD__.' options='.print_r( $options, true ) );

    return isset( $options[ $option_name ] ) ? $options[ $option_name ] : false;

  }

  /*******************
   * METHODS CALLED BY PLUGIN SUBCLASS
   */

  /**
   * registers the options
   *
   * this function is called by the plugin subclass to set up the options
   *
   * @param array $settings an array of all the individual settings for the plugin
   *                name unique string identifier for the setting
   *                title display title for the setting
   *                group the settings group the setting is assigned to
   *                options an array of extended options for the setting
   *
   * @return null
   */
  private function _register_options() {

    foreach ( $this->plugin_settings as $setting_params ) {

      $this->_register_option(
                              $setting_params['name'],
                              $setting_params['title'],
                              $setting_params['group'],
                              $setting_params['options']
                              );

    }

  }

  /**
   * displays a settings page form using the WP Settings API
   *
   * this just displays the core (form element) of the page; the complete 
	 * page display should be defined by the plugin subclass. 
   *
   * @return null
   */
  protected function show_settings_form() {
    
    settings_errors();
    ?>
      <form action="options.php" method="post">
        <?php

        settings_fields( $this->WP_setting );

        do_settings_sections( $this->settings_page );

        $args = array(
                      'type'  => 'submit',
                      'class' => $this->submit_class,
                      'value' => $this->submit_button,
                      'name'  => 'submit_button',
                      );

        printf( $this->submit_wrap, PDb_FormElement::get_element( $args ) );

        ?>
      </form>
    <?php

  }

  /**
   * customizes the settings display HTML
   *
   * @param array $display_settings
   *                help_text_wrap
   *                submit_wrap
   *                submit_class
   *                submit_button
   */
  public function define_settings_display( $display_settings ) {
  
    foreach( array( 'help_text_wrap', 'submit_wrap', 'submit_class', 'submit_button' ) as $setting ) {

      if ( isset( $display_settings[ $setting ] ) )
        $this->$setting = $display_settings[ $setting ];

    }

  }

  /**
   * gets a setting default value
   * 
   * @param string $name name of the setting default to get
   * @return unknown the default value
   */
  public function get_default_value($name) {
    foreach($this->plugin_settings as $setting) {
      if ($setting['name'] == $name and isset($setting['options']['value'])) {
        return $setting['options']['value'];
      }
    }
    return null;
  }

  /**
   * gets an option title
   * 
   * @param string $name name of the setting
   * @return string|null the title if defined
   */
  public function get_option_title($name) {
    foreach($this->plugin_settings as $setting) {
      if ($setting['name'] == $name) {
        return $setting['title'];
      }
    }
    return null;
  }
  
  /**
   * gets all options names
   * 
   * @return array of all defined option names
   */
  public function get_option_names() {
    
    $names = array();
    foreach($this->plugin_settings as $setting) {
      $names[] = $setting['name'];
    }
    return $names;
    
  }

  /********************
   * METHODS USED BY SETTINGS API
   */

  /**
   * validates settings fields
   *
   * the plugin subclass will supply any validation if needed
   */
  public function validate( $input ) {

    return $input;

  }

   /**
   * prints a settings form element
   *
   * @access public because it's called by WP
   *
   * @param array $input
   *    name - setting slug (required)
   *    type - the type of form element to use
   *    value - the current value of the field
   *    help_text - extra text for the setting page
   *    options - if an array type setting, the values of the settings, array
   *    attributes - any additional attributes to add
   *    class - a CSS class name to add
   */
  public function print_settings_field( $input ) {
    
    //error_log(__METHOD__.' name:'.$input['name'].' type:'.$input['type'].' title:'.$input['title']);

    if ( ! isset( $input['name'] ) ) return NULL;
    
    if ($input['type'] == 'header') {
      //echo '<h3>' . $input['title'] . '</h3>';
    } else {

      $options = get_option( $this->WP_setting );

      $args = wp_parse_args( $input, array(
        'options' => false,
        'attributes' => '',
        'value' => ''
        ) );

      // supply the value of the field from the saved option or the default as defined in the settings init
      $args['value'] = isset( $options[ $input['name'] ] ) ? $options[ $input['name'] ] : $args['value'];

      $args['name'] = $this->WP_setting.'['.$input['name'].']';

      PDb_FormElement::print_element( $args );

      if ( ! empty( $args['help_text'] ) ) {

        printf( $this->help_text_wrap, trim( $args['help_text'] ) );

      }
    }

  }
	
	/**
	 * displays a section subheader
	 *
	 * note: the header is displayed by WP; this is only what would go under that
	 */
	public function options_section( $section ) {
	
	}

  /***********
   * PRIVATE CALLS TO WP SETTINGS API
   */

  /**
   * registers settings sections with the WP Settings API
   *
   */
  private function _register_sections() {

    foreach ( $this->sections as $name => $title ) {

      add_settings_section(
        $this->WP_setting.'_'.$name,
        $title,
        array( $this, 'options_section' ),
        $this->settings_page
      );

    }

  }

  /**
   * registers an option setting with the WP Settings API
   *
   * @param string $name     name of the setting (unique string ID)
   * @param string $title    display title for the setting
   * @param string $group    group for the setting
   * @param array  $options  the various options for the setting
   *                  type the form element type to use
   *                  help_text any explanatory text to include
   *                  value a default value for the setting
   */
   private function _register_option( $name, $title, $group, $options ) {

    if ( ! isset( $options['type'] ) ) $options['type'] = 'text';
    $options['name'] = $name;
    $options['title'] = $title;

    add_settings_field(
        $name,
        $title,
        array( $this, 'print_settings_field' ),
        $this->settings_page,
        $this->WP_setting.'_'.$group,
        $options
        );

    // drop in the default value (if any)
    if ( isset( $options['value'] ) ) self::update_option( $name, $options['value'], false );

   }

}