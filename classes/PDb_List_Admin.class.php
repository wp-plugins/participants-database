<?php

/**
 * class for handling the listing of participant records in the admin
 *
 * static class for managing a set of modules which together out put a listing of 
 * records in various configurations
 *
 * the general plan is that this class's initialization method is called in the
 * admin to generate the page.
 *
 * Requires PHP Version 5.3 or greater
 * 
 * @category   
 * @package    WordPress
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2012 xnau webdesign
 * @license    GPL2
 * @version    Release: 1.5.5
 * @link       http://wordpress.org/extend/plugins/participants-database/
 */
if ( ! defined( 'ABSPATH' ) ) die;
class PDb_List_Admin {

  /**
   * @var string holds the main query for building the list
   */
  static $list_query;

  /**
   * @var string translations strings for buttons
   */
  static $i18n;

  /**
   * @var object holds the pagination object
   */
  static $pagination;

  /**
   * @var int holds the number of list items to show per page
   */
  static $page_list_limit;

  /**
   * @var string the name of the list page variable
   */
  static $list_page = 'listpage';

  /**
   * @var string name of the list anchor element
   */
  static $list_anchor = 'participants-list';

  /**
   *  @var string name of the list limit transient
   */
  static $user_settings = 'admin-user-settings';

  /**
   * @var int the number of records after filtering
   */
  static $num_records;

  /**
   * @var array all the records are held in this array
   */
  static $participants;

  /**
   * @var string holds the url of the registrations page
   */
  static $registration_page_url;

  /**
   * holds the columns to display in the list
   * 
   * @var array of field objects
   */
  static $display_columns;

  /**
   * @var array holds the list of sortable columns
   */
  static $sortables;

  /**
   * @var array holds the settings for the list filtering and sorting
   */
  static $filter;

  /**
   * 
   * @var string name of the filter transient
   */
  public static $filter_transient = 'admin_list_filter';

  /**
   * @var array set of values making up the default list filter
   */
  public static $default_filter;

  /**
   * @var bool holds the current parenthesis status used while building a query where clause
   */
  protected static $inparens = false;

  /**
   * initializes and outputs the list for the backend
   */
  public static function initialize()
  {

    self::_setup_i18n();

    wp_localize_script(Participants_Db::$prefix . 'list-admin', 'list_adminL10n', array(
        'delete' => self::$i18n['delete_checked'],
        'cancel' => self::$i18n['change'],
        "record" => __("Do you really want to delete the selected record?", 'participants-database'),
        "records" => __("Do you really want to delete the selected records?", 'participants-database'),
    ));
    wp_enqueue_script(Participants_Db::$prefix . 'list-admin');
    wp_enqueue_script(Participants_Db::$prefix . 'debounce');

    get_currentuserinfo();

    // set up the user settings transient
    global $user_ID;
    self::$user_settings = Participants_Db::$prefix . self::$user_settings . '-' . $user_ID;
    self::$filter_transient = Participants_Db::$prefix . self::$filter_transient . '-' . $user_ID;
    
    self::set_list_limit();

    self::$registration_page_url = get_bloginfo('url') . '/' . Participants_Db::plugin_setting('registration_page', '');

    self::setup_display_columns();

    self::$sortables = Participants_Db::get_sortables(false, 'alpha');

    // set up the basic values
    self::$default_filter = array(
        'search' => array(
            0 => array(
                'search_field' => 'none',
								'value' => '',
                'operator' => 'LIKE',
                'logic' => 'AND'
            )
        ),
        'sortBy' => Participants_Db::plugin_setting('admin_default_sort'),
        'ascdesc' => Participants_Db::plugin_setting('admin_default_sort_order'),
        'list_filter_count' => 1,
    );

    // merge the defaults with the $_REQUEST array so if there are any new values coming in, they're included
    self::_update_filter();

    // error_log(__METHOD__.' filter:'.print_r(self::$filter,1));
    // process delete and items-per-page form submissions
    self::_process_general();

    self::_process_search();

    if (WP_DEBUG)
      error_log(__METHOD__ . ' list query= ' . self::$list_query);
    /*
     * save the query in a transient so it can be used by the export CSV functionality
     */
    if (Participants_Db::current_user_has_plugin_role('admin', 'csv export')) {
      global $current_user;
      set_transient(Participants_Db::$prefix . 'admin_list_query' . $current_user->ID, self::$list_query, 3600 * 24);
    }

    // get the $wpdb object
    global $wpdb;

    // get the number of records returned
    self::$num_records = $wpdb->get_var(str_replace('*', 'COUNT(*)', self::$list_query));

    // set the pagination object
    $current_page = filter_input(INPUT_GET, self::$list_page, FILTER_VALIDATE_INT, array( 'options' => array('default' => 1, 'min_range' => 1)));
    
    self::$pagination = new PDb_Pagination(array(
        'link' => self::prepare_page_link($_SERVER['REQUEST_URI']) . '&' . self::$list_page . '=%1$s',
        'page' => $current_page,
        'size' => self::$page_list_limit,
        'total_records' => self::$num_records,
//        'wrap_tag' => '<div class="pdb-list"><div class="pagination"><label>' . _x('Page', 'noun; page number indicator', 'participants-database') . ':</label> ',
//        'wrap_tag_close' => '</div></div>',
        'add_variables' => '#pdb-list-admin',
    ));

    // get the records for this page, adding the pagination limit clause
    self::$participants = $wpdb->get_results(self::$list_query . ' ' . self::$pagination->getLimitSql(), ARRAY_A);

    // ok, setup finished, start outputting the form
    // add the top part of the page for the admin
    self::_admin_top();

    // print the sorting/filtering forms
    self::_sort_filter_forms();

    // add the delete and items-per-page controls for the backend
    self::_general_list_form_top();

    // print the main table
    self::_main_table();

    // output the pagination controls
    echo '<div class="pdb-list">' . self::$pagination->links() . '</div>';

    // print the CSV export form (authorized users only)
    $csv_role = Participants_Db::plugin_setting_is_true('editor_allowed_csv_export') ? 'editor' : 'admin';
    if (Participants_Db::current_user_has_plugin_role($csv_role, 'csv export'))
      self::_print_export_form();

    // print the plugin footer
    Participants_Db::plugin_footer();
  }

  /**
   * updates the filter property
   * 
   * gets the incoming filter values from the POST array and updates the filter 
   * property, filling in default values as needed
   * 
   * @return null
   */
  private static function _update_filter()
  {
    self::$filter = self::get_filter();
    if (filter_input(INPUT_POST, 'action') === 'admin_list_filter') {
      unset(self::$filter['search']);
      for ($i = filter_input(INPUT_POST, 'list_filter_count', FILTER_SANITIZE_NUMBER_INT); $i > 0; $i--) {
        self::$filter['search'][] = current(self::$default_filter['search']);
      }
      foreach (array_keys($_POST) as $key) {
        $postval = $_POST[$key];
        if (is_array($postval)) {
          $postval = filter_input(INPUT_POST, $key, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
          foreach ($postval as $index => $value) {
            if ($value !== '') {
              self::$filter['search'][$index][$key] = $value;
            }
          }
        } elseif (isset(self::$filter[$key])) {
          self::$filter[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_STRING);
        }
      }
    } elseif ($column_sort = filter_input(INPUT_GET, 'column_sort', FILTER_SANITIZE_STRING)) {
      if (self::$filter['sortBy'] !== $column_sort) {
        // if we're changing the sort column, set the sort to ASC
        self::$filter['ascdesc'] = 'ASC';
      } else {
        self::$filter['ascdesc'] = self::$filter['ascdesc'] === 'ASC' ? 'DESC' : 'ASC';
      }
      self::$filter['sortBy'] = $column_sort;
    }
    self::save_filter(self::$filter);
  }

  /**
   * strips the page number out of the URI so it can be used as a link to other pages
   *
   * @param string $uri the incoming URI, usually $_SERVER['REQUEST_URI']
   *
   * @return string the re-constituted URI
   */
  public static function prepare_page_link($uri)
  {

    $URI_parts = explode('?', $uri);

    if (empty($URI_parts[1])) {

      $values = array();
    } else {

      parse_str($URI_parts[1], $values);

      // take out the list page number
      unset($values[self::$list_page]);

      /* clear out our filter variables so that all that's left in the URI are 
       * variables from WP or any other source-- this is mainly so query string 
       * page id can work with the pagination links
       */
      $filter_atts = array(
          'search',
          'sortBy',
          'ascdesc',
          'column_sort',
      );
      foreach ($filter_atts as $att)
        unset($values[$att]);
    }

    return $URI_parts[0] . '?' . http_build_query($values);
  }

  /** 	
   * processes all the general list actions: delete and  set items-per-page
   */
  private static function _process_general()
  {

    global $wpdb;

    if (filter_input(INPUT_POST, 'action') == 'list_action') {

      switch (filter_input(INPUT_POST, 'submit-button')) {

        case self::$i18n['delete_checked']:

          $selected_ids = filter_input(INPUT_POST, 'pid', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
          if ($selected_ids) {
            $count = count($selected_ids);

          $pattern = $count > 1 ? 'IN ( ' . trim(str_repeat('%s,', $count), ',') . ' )' : '= %s';
          $sql = "DELETE FROM " . Participants_Db::$participants_table . " WHERE id " . $pattern;
					$wpdb->query($wpdb->prepare($sql, $selected_ids));
          Participants_Db::set_admin_message(__('Record delete successful.', 'participants-database'), 'updated');
          }
          break;

        case self::$i18n['change']:

          $list_limit = filter_input(INPUT_POST, 'list_limit', FILTER_VALIDATE_INT);
          if ($list_limit > 0) {
            self::set_admin_user_setting('list_limit', $list_limit);
          }
          $_GET[self::$list_page] = 1;
          break;

        default:
      }
    }
  }

  /**
   * processes searches and sorts to build the listing query
   *
   * @param string $submit the value of the submit field
   */
  private static function _process_search()
  {

    $submit = filter_input(INPUT_POST, 'submit-button', FILTER_SANITIZE_STRING);

    switch ($submit) {

      case self::$i18n['clear'] :
        for ($i = 0; $i < self::$filter['list_filter_count']; $i++) {
          self::$filter['search'][$i] = self::$default_filter['search'][0];
        }
        self::save_filter(self::$filter);
      case self::$i18n['sort']:
      case self::$i18n['filter']:
      case self::$i18n['search']:
        // go back to the first page to display the newly sorted/filtered list
        $_GET[self::$list_page] = 1;
      default:

        self::$list_query = 'SELECT * FROM ' . Participants_Db::$participants_table . ' p ';

        if (count(self::$filter['search']) === 1 && (self::$filter['search'][0]['search_field'] === 'none' || self::$filter['search'][0]['search_field'] === '')) {
          // do nothing, no search performed
        } else {
          self::$list_query .= 'WHERE ';
          for ($i = 0; $i <= count(self::$filter['search']) - 1; $i++) {
            if (self::$filter['search'][$i]['search_field'] !== 'none' && self::$filter['search'][$i]['search_field'] !== '') {
              self::_add_where_clause(self::$filter['search'][$i]);
            }
            if ($i === count(self::$filter['search']) - 1) {
              if (self::$inparens) {
                self::$list_query .= ') ';
                self::$inparens = false;
              }
            } elseif (self::$filter['search'][$i + 1]['search_field'] !== 'none' && self::$filter['search'][$i + 1]['search_field'] !== '') {
              self::$list_query .= self::$filter['search'][$i]['logic'] . ' ';
            }
          }
          // if no where clauses were added, remove the WHERE operator
          if (preg_match('/WHERE $/', self::$list_query)) {
            self::$list_query = str_replace('WHERE', '', self::$list_query);
          }
        }

        // add the sorting
        self::$list_query .= ' ORDER BY p.' . esc_sql(self::$filter['sortBy']) . ' ' . esc_sql(self::$filter['ascdesc']);
    }
  }

  /**
   * adds a where clause to the query
   * 
   * the filter set has the structure:
   *    'search_field' => name of the field to search on
   *    'value' => search term
   *    'operator' => mysql operator
   *    'logic' => join to next statement (AND or OR)
   * 
   * @param array $filter_set
   * @return null
   */
  protected static function _add_where_clause($filter_set)
  {

    if ($filter_set['logic'] === 'OR' && !self::$inparens) {
      self::$list_query .= ' (';
      self::$inparens = true;
    }
    $filter_set['value'] = str_replace('*', '%', $filter_set['value']);

    $delimiter = array("'", "'");

    switch ($filter_set['operator']) {


          case 'gt':

            $operator = '>';
            break;

          case 'lt':

            $operator = '<';
            break;

      case '=':

        $operator = '=';
        if ($filter_set['value'] === '') {
          $filter_set['value'] = 'null';
        } elseif (strpos($filter_set['value'], '%') !== false) {
          $operator = 'LIKE';
          $delimiter = array("'", "'");
        }
        break;

      case 'NOT LIKE':
      case '!=':
      case 'LIKE':
      default:

        $operator = esc_sql($filter_set['operator']);
        if (stripos($operator, 'LIKE') !== false) {
        	$delimiter = array('"%', '%"');
        }
        if ($filter_set['value'] === '') {
          $filter_set['value'] = 'null';
          $operator = '<>';
        } elseif (strpos($filter_set['value'], '%') !== false) {
          $delimiter = array("'", "'");
        }
    }

    // get the attributes of the field being searched
    $field_atts = Participants_Db::get_field_atts($filter_set['search_field']);

    $value = PDb_FormElement::get_title_value($filter_set['value'], $filter_set['search_field']);

          if ($field_atts->form_element == 'timestamp') {
            
      $value = $filter_set['value'];
            $value2 = false;
      if (strpos($filter_set['value'], ' to ')) {
        list($value, $value2) = explode('to', $filter_set['value']);
            }
            
            $value = Participants_Db::parse_date($value, $field_atts, false);
            if ($value2)
              $value2 = Participants_Db::parse_date($value2, $field_atts, $field_atts->form_element == 'date');
            
            if ($value !== false) {
            
        $stored_date = "DATE(p." . esc_sql($filter_set['search_field']) . ")";
            
              if ($value2 !== false and !empty($value2)) {
                
          self::$list_query .= " " . $stored_date . " > DATE_ADD(FROM_UNIXTIME(0), interval " . esc_sql($value) . " second) AND " . $stored_date . " < DATE_ADD(FROM_UNIXTIME(0), interval " . esc_sql($value2) . " second)";
              } else {

                if ($operator == 'LIKE')
                  $operator = '=';

          self::$list_query .= " " . $stored_date . " " . $operator . " DATE_ADD(FROM_UNIXTIME(0), interval " . esc_sql($value) . " second) ";
              }
            }
              } elseif ($field_atts->form_element == 'date') { 

      $value = $filter_set['value'];
            $value2 = false;
      if (strpos($filter_set['value'], ' to ')) {
        list($value, $value2) = explode('to', $filter_set['value']);
                }

            $value = Participants_Db::parse_date($value, $field_atts, true);
            if ($value2)
              $value2 = Participants_Db::parse_date($value2, $field_atts, $field_atts->form_element == 'date');

            if ($value !== false) {
              
        $stored_date = "CAST(p." . esc_sql($filter_set['search_field']) . " AS SIGNED)";
            
              if ($value2 !== false and !empty($value2)) {
                
          self::$list_query .= " " . $stored_date . " > CAST(" . esc_sql($value) . " AS SIGNED) AND " . $stored_date . " < CAST(" . esc_sql($value2) . "  AS SIGNED)";
          } else {
            
          if ($operator == 'LIKE')
            $operator = '=';
            
          self::$list_query .= " " . $stored_date . " " . $operator . " CAST(" . esc_sql($value) . " AS SIGNED)";
          }
        }
    } elseif ($filter_set['value'] === 'null') {

      switch ($filter_set['operator']) {
        case '<>':
        case '!=':
        case 'NOT LIKE':
          self::$list_query .= ' (p.' . esc_sql($filter_set['search_field']) . ' IS NOT NULL AND p.' . esc_sql($filter_set['search_field']) . ' <> "")';
        break;
        case 'LIKE':
        case '=':
      default:
          self::$list_query .= ' (p.' . esc_sql($filter_set['search_field']) . ' IS NULL OR p.' . esc_sql($filter_set['search_field']) . ' = "")';
          break;
      }
    } else {

      self::$list_query .= ' p.' . esc_sql($filter_set['search_field']) . ' ' . $operator . " " . $delimiter[0] . esc_sql($value) . $delimiter[1];
    }
    if ($filter_set['logic'] === 'AND' && self::$inparens) {
      self::$list_query .= ') ';
      self::$inparens = false;
    }
    self::$list_query .= ' ';
  }

  /**
   * top section for admin listing
   */
  private static function _admin_top()
  {
    ?>
    <div  class="wrap participants_db">
    <a id="pdb-list-admin" name="pdb-list-admin"></a>
      <?php Participants_Db::admin_page_heading() ?>
    <div id="poststuff">
      <div class="post-body">
          <h2><?php _e('List Participants', 'participants-database') ?></h2>
    <?php
  }

  /**
   * prints the sorting and filtering forms
   *
   * @param string $mode determines whether to print filter, sort, both or 
   *                     none of the two functions
   */
        private static function _sort_filter_forms()
        {

    global $post;
          $filter_count = intval(self::$filter['list_filter_count']);
        //build the list of columns available for filtering
        $filter_columns = array();
        foreach (Participants_db::get_column_atts('backend') as $column) {
          // add the field name if a field with the same title is already in the list
            $title = apply_filters( 'pdb-translate_string', $column->title);
            $select_title = ( isset($filter_columns[$column->title]) || strlen($column->title) === 0 ) ? $title . ' (' . $column->name . ')' : $title;
          
          $filter_columns[$select_title] = $column->name;
        }
          $record_id_field = Participants_Db::$fields['id'];
          $filter_columns += array(apply_filters( 'pdb-translate_string', $record_id_field->title) => 'id');
          ?>
          <div class="pdb-searchform">
            <form method="post" id="sort_filter_form" action="<?php echo self::prepare_page_link($_SERVER['REQUEST_URI']) ?>" >
              <input type="hidden" name="action" value="admin_list_filter">
              <table class="form-table">
                <tbody><tr><td>
                        <?php
                        for ($i = 0; $i <= $filter_count - 1; $i++) :
                        $filter_set = self::get_filter_set($i);
                          ?>
                        <fieldset class="widefat inline-controls">
                          <?php if ($i === 0): ?>
                            <legend><?php _e('Show only records with', 'participants-database') ?>:</legend>
                            <?php
                          endif;

        $element = array(
            'type' => 'dropdown',
                              'name' => 'search_field[' . $i . ']',
                              'value' => $filter_set['search_field'],
                              'options' => array('' => 'none') + $filter_columns,
        );
        PDb_FormElement::print_element($element);
                          _ex('that', 'joins two search terms, such as in "Show only records with last name that is Smith"', 'participants-database');
    $element = array(
        'type' => 'dropdown',
                              'name' => 'operator[' . $i . ']',
                              'value' => $filter_set['operator'],
        'options' => array(
            'null_select' => false,
            __('is', 'participants-database') => '=',
            __('is not', 'participants-database') => '!=',
            __('contains', 'participants-database') => 'LIKE',
            __('doesn&#39;t contain', 'participants-database') => 'NOT LIKE',
            __('is greater than', 'participants-database') => 'gt',
            __('is less than', 'participants-database') => 'lt',
        ),
    );
    PDb_FormElement::print_element($element);
    ?>
                            <input id="participant_search_term_<?php echo $i ?>" type="text" name="value[<?php echo $i ?>]" value="<?php echo htmlspecialchars(esc_attr($filter_set['value'])) ?>">
                          <?php
                          if ($i < $filter_count - 1) {
                            echo '<br />';
                            $element = array(
                                'type' => 'radio',
                                'name' => 'logic[' . $i . ']',
                                'value' => $filter_set['logic'],
                                'options' => array(
                                    __('and', 'participants-database') => 'AND',
                                    __('or', 'participants-database') => 'OR',
                                ),
                            );
                          } else {
                            $element = array(
                                'type' => 'hidden',
                                'name' => 'logic[' . $i . ']',
                                'value' => $filter_set['logic'],
                            );
                          }
                          PDb_FormElement::print_element($element);
                          ?>

                        </fieldset>
                        <?php endfor ?>
                      <fieldset class="widefat inline-controls">
            <input class="button button-default" name="submit-button" type="submit" value="<?php echo self::$i18n['filter'] ?>">
            <input class="button button-default" name="submit-button" type="submit" value="<?php echo self::$i18n['clear'] ?>">
                      <div class="widefat inline-controls filter-count">
                        <label for="list_filter_count"><?php _e('Number of filters to use: ', 'participants-database') ?><input id="list_filter_count" name="list_filter_count" class="number-entry single-digit" type="number" max="5" min="1" value="<?php echo $filter_count ?>"  /></label>
                      </div>
          </fieldset>
                        </td></tr><tr><td>
          <fieldset class="widefat inline-controls">
            <legend><?php _e('Sort by', 'participants-database') ?>:</legend>
            <?php
            $element = array(
                'type' => 'dropdown',
                'name' => 'sortBy',
                'value' => self::$filter['sortBy'],
                'options' => self::$sortables,
            );
            PDb_FormElement::print_element($element);

            $element = array(
                'type' => 'radio',
                'name' => 'ascdesc',
                            'value' => strtolower(self::$filter['ascdesc']),
                'options' => array(
                    __('Ascending', 'participants-database') => 'asc',
                    __('Descending', 'participants-database') => 'desc'
                ),
            );
            PDb_FormElement::print_element($element);
            ?>
            <input class="button button-default"  name="submit-button" type="submit" value="<?php echo self::$i18n['sort'] ?>">
          </fieldset>
                        </td></tr></tbody></table>
        </form>
          </div>

          <h3><?php printf(_n('%s record found, sorted by: %s.', '%s records found, sorted by: %s.', self::$num_records, 'participants-database'), self::$num_records, Participants_Db::column_title(self::$filter['sortBy'])) ?></h3>
            <?php
          }

          /**
           * prints the general list form controls for the admin lising: deleting and items-per-page selector
           */
        private static function _general_list_form_top()
  {
            ?>

      <form id="list_form"  method="post">
            <?php PDb_FormElement::print_hidden_fields(array('action' => 'list_action')) ?>
        <input type="hidden" id="select_count" value="0" />
                <table class="form-table"><tbody><tr><td>
        <fieldset class="widefat inline-controls">
                      <?php if (current_user_can(Participants_Db::plugin_capability('plugin_admin_capability', 'delete participants'))) : ?>
                        <span style="padding-right:20px" ><input type="submit" name="submit-button" class="button button-default" value="<?php echo self::$i18n['delete_checked'] ?>" id="delete_button"  ></span>
          <?php endif ?>
            <?php
            $list_limit = PDb_FormElement::get_element(array(
                        'type' => 'text-line',
                        'name' => 'list_limit',
                        'value' => self::$page_list_limit,
                        'attributes' => array(
                            'style' => 'width:2.8em',
                            'maxLength' => '3'
                        )
                            )
                    )
            ?>
          <?php printf(__('Show %s items per page.', 'participants-database'), $list_limit) ?>
      <?php PDb_FormElement::print_element(array('type' => 'submit', 'name' => 'submit-button', 'class' => 'button button-default', 'value' => self::$i18n['change'])) ?>
          
        </fieldset>
              </td></tr></tbody>
        <?php
      }

      /**
       * prints the main body of the list, including headers
       *
       * @param string $mode dtermines the print mode: 'noheader' skips headers, (other choices to be determined)
       */
              private static function _main_table($mode = '')
              {
        ?>

        <table class="wp-list-table widefat fixed pages pdb-list stuffbox" cellspacing="0" >
          <?php
          $PID_pattern = '<td><a href="%2$s">%1$s</a></td>';
          $head_pattern = '
<th class="%2$s" scope="col">
  <span><a href="' . self::sort_link_base_URI() . '&column_sort=%2$s">%1$s%3$s</a></span>
</th>
';
          //template for outputting a column
          $col_pattern = '<td>%s</td>';

          if (count(self::$participants) > 0) :

            if ($mode != 'noheader') :
              ?>
              <thead>
                <tr>
            <?php self::_print_header_row($head_pattern) ?>
                </tr>
              </thead>
              <?php
            endif; // table header row
            // print the table footer row if there is a long list
            if ($mode != 'noheader' && count(self::$participants) > 10) :
              ?>
              <tfoot>
                <tr>
              <?php self::_print_header_row($head_pattern) ?>
                </tr>
              </tfoot>
              <?php endif; // table footer row 
            ?>
            <tbody>
            <?php
            // output the main list
            foreach (self::$participants as $value) {
              ?>
                <tr>
        <?php // print delete check  ?>
                  <td>
                          <?php if (current_user_can(Participants_Db::plugin_capability('plugin_admin_capability', 'delete participants'))) : ?>
                            <input type="checkbox" class="delete-check" name="pid[]" value="<?php echo $value['id'] ?>" />
                    <?php endif ?>
                          <a href="admin.php?page=<?php echo 'participants-database' ?>-edit_participant&action=edit&id=<?php echo $value['id'] ?>" title="<?php _e('Edit', 'participants-database') ?>"><span class="glyphicon glyphicon-edit"></span></a>
                  </td>
              <?php
              foreach (self::$display_columns as $column) {

                // this is where we place form-element-specific text transformations for display
                switch ($column->form_element) {

                  case 'image-upload':

                    $image_params = array(
                        'filename' => basename($value[$column->name]),
                        'link' => '',
                                  'mode' => (Participants_Db::plugin_setting_is_true('admin_thumbnails') ? 'image' : 'filename'),
                      );

                    if (Participants_Db::is_single_record_link($column)) {
                                $page_link = get_permalink(Participants_Db::plugin_setting('single_record_page'));
                      $image_params['link'] = Participants_Db::add_uri_conjunction($page_link) . 'pdb=' . $value['id'];
                    }
                    // this is to display the image as a linked thumbnail
                    $image = new PDb_Image($image_params);
                    $display_value = $image->get_image_html();
                    

                    break;

                  case 'date':
                  case 'timestamp':

                    if (!empty($value[$column->name])) {

                      $format = Participants_Db::$date_format;
                                if (Participants_Db::plugin_setting_is_true('show_time') and $column->form_element == 'timestamp') {
                        // replace spaces with &nbsp; so the time value stays together on a broken line
                        $format .= ' ' . str_replace(' ', '&\\nb\\sp;', get_option('time_format'));
                      }
                                $time = Participants_Db::is_valid_timestamp($value[$column->name]) ? (int) $value[$column->name] : Participants_Db::parse_date($value[$column->name], $column->name, $column->form_element == 'date');
                      $display_value = $value[$column->name] == '0000-00-00 00:00:00' ? '' : date_i18n($format, $time);
                      //$display_value = date_i18n($format, $time);
                              } else {
                      $display_value = '';
                              }

                    break;

                  case 'multi-select-other':
                  case 'multi-checkbox':
                    // multi selects are displayed as comma separated lists

                              $column->value = $value[$column->name];
                              $display_value = PDb_FormElement::get_field_value_display($column, false);

                              //$display_value = is_serialized($value[$column->name]) ? implode(', ', unserialize($value[$column->name])) : $value[$column->name];
                    break;

                  case 'link':

                    $link_value = maybe_unserialize($value[$column->name]);

                    if (count($link_value) === 1) {
                                $link_value = array_fill(0, 2, current((array) $link_value));
                    }

                    $display_value = Participants_Db::make_link($link_value[0], $link_value[1]);

                    break;

                  case 'rich-text':

                    if (!empty($value[$column->name]))
                      $display_value = '<span class="textarea">' . $value[$column->name] . '</span>';
                    else
                      $display_value = '';
                    break;

                  case 'text-line':

                    if (Participants_Db::is_single_record_link($column)) {
                                $url = get_permalink(Participants_Db::plugin_setting('single_record_page'));
                      $template = '<a href="%1$s" >%2$s</a>';
                      $delimiter = false !== strpos($url, '?') ? '&' : '?';
                      $url = $url . $delimiter . 'pdb=' . $value['id'];

                      $display_value = sprintf($template, $url, $value[$column->name]);
                              } elseif (Participants_Db::plugin_setting_is_true('make_links')) {

                                $field = new stdClass();
                                $field->value = $value[$column->name];
                                $display_value = PDb_FormElement::make_link($field);
                    } else {
                                $display_value = $value[$column->name] === '' ? $column->default : esc_html($value[$column->name]);
                    }

                    break;

                  case 'hidden':
                              $display_value = $value[$column->name] === '' ? '' : esc_html($value[$column->name]);
                    break;

                  default:
                              $column->value = $value[$column->name];
                              $display_value = PDb_FormElement::get_field_value_display($column, false);
                }

                          if ($column->name === 'private_id' && Participants_Db::plugin_setting_is_set('registration_page')) {
                  printf($PID_pattern, $display_value, Participants_Db::get_record_link($display_value));
                } else {
                  printf($col_pattern, $display_value);
								}
              }
              ?>
                </tr>
                <?php } ?>
            </tbody>

              <?php else : // if there are no records to show; do this
                ?>
            <tbody>
              <tr>
                <td><?php _e('No records found', 'participants-database') ?></td>
              </tr>
            </tbody>
              <?php
              endif; // participants array
              ?>
        </table>
      </form>
              <?php
            }

            /**
             * prints the CSV export form
             */
                private static function _print_export_form()
                {

          $base_filename = self::get_admin_user_setting('csv_base_filename', Participants_Db::PLUGIN_NAME);
              ?>

      <div class="postbox">
        <h3><?php _e('Export CSV', 'participants-database') ?></h3>
        <div class="inside">
        <form method="post" class="csv-export">
          <input type="hidden" name="subsource" value="<?php echo Participants_Db::PLUGIN_NAME ?>">
          <input type="hidden" name="action" value="output CSV" />
          <input type="hidden" name="CSV type" value="participant list" />
          <input type="hidden" name="query" value="<?php echo rawurlencode(self::$list_query) ?>" />
          <?php
          $date_string = str_replace(array('/', '#', '.', '\\', ', ', ',', ' '), '-', date_i18n(Participants_Db::$date_format));
                $suggested_filename = $base_filename . self::filename_datestamp() . '.csv';
          $namelength = round(strlen($suggested_filename) * 0.9);
          ?>
          <fieldset class="inline-controls">
    <?php _e('File Name', 'participants-database') ?>:
            <input type="text" name="filename" value="<?php echo $suggested_filename ?>" size="<?php echo $namelength ?>" />
                  <input type="submit" name="submit-button" value="<?php _e('Download CSV for this list', 'participants-database') ?>" class="button button-primary" />
            <label for="include_csv_titles"><input type="checkbox" name="include_csv_titles" value="1"><?php _e('Include field titles', 'participants-database') ?></label>
          </fieldset>
          <p>
      <?php _e('This will download the whole list of participants that match your search terms, and in the order specified by the sort. The export will include records on all list pages. The fields included in the export are defined in the "CSV" column on the Manage Database Fields page.', 'participants-database') ?>
          </p>
        </form>
        </div>
      </div>
      </div>
    </div>
    </div>
      <?php
    }

    /**
     * prints a table header row
     */
  private static function _print_header_row($head_pattern)
  {

      
    $sorticon_class = strtolower(self::$filter['ascdesc']) === 'asc' ? 'dashicons-arrow-up' : 'dashicons-arrow-down';
    // template for printing the registration page link in the admin
    $sorticon = '<span class="dashicons ' . $sorticon_class . '"></span>';
      // print the "select all" header 
      ?>
    <th scope="col" style="width:3em">
      <?php if (current_user_can(Participants_Db::plugin_capability('plugin_admin_capability', 'delete participants'))) : ?>
      <?php /* translators: uses the check symbol in a phrase that means "check all"  printf('<span class="checkmark" >&#10004;</span>%s', __('all', 'participants-database'))s */ ?>
        <input type="checkbox" name="checkall" id="checkall" ><span class="glyphicon glyphicon-edit" style="opacity: 0"></span>
        <?php endif ?>
      </th>
          <?php
          // print the top header row
          foreach (self::$display_columns as $column) {
      $title = apply_filters( 'pdb-translate_string', strip_tags(stripslashes($column->title)));
            printf(
              $head_pattern, str_replace(
                      array('"', "'"), array('&quot;', '&#39;'), $title
              ), $column->name, ($column->name === self::$filter['sortBy'] ? $sorticon : '')
            );
          }
        }

  /**
   * builds a column sort link
   * 
   * this just removes the 'column_sort' variable from the URI
   * 
   * @return string the base URI for the sort link
   */
  private static function sort_link_base_URI()
  {
    $uri = parse_url($_SERVER['REQUEST_URI']);
    parse_str($uri['query'], $query);
    unset($query['column_sort']);
    return $uri['path'] . '?' . http_build_query($query);
  }

        /**
         * sets up the main list columns
         */
  private static function setup_display_columns()
  {
          
          global $wpdb;
          $sql = '
          SELECT f.name, f.form_element, f.default, f.group, f.title
          FROM ' . Participants_Db::$fields_table . ' f 
          WHERE f.name IN ("' . implode('","', PDb_Shortcode::get_list_display_columns('admin_column')) . '") 
          ORDER BY f.admin_column ASC';
          
          self::$display_columns = $wpdb->get_results($sql);
        }

        /**
         * sets the admin list limit value
         */
  private static function set_list_limit()
  {

    $limit_value = self::get_admin_user_setting('list_limit', Participants_Db::plugin_setting('list_limit'));
    $input_limit = filter_input(INPUT_GET, 'list_limit', FILTER_VALIDATE_INT, array('min_range' => 1));
    if (empty($input_limit)) {
      $input_limit = filter_input(INPUT_POST, 'list_limit', FILTER_VALIDATE_INT, array('min_range' => 1));
    }
    if (!empty($input_limit)) {
      $limit_value = $input_limit;
          }
          self::$page_list_limit = $limit_value;
          self::set_admin_user_setting('list_limit', $limit_value);
        }

        /**
         * sets the admin list limit value
         */
  private static function set_list_sort()
  {
          
    $sort_order = filter_input(INPUT_POST, 'ascdesc', FILTER_SANITIZE_STRING);
    $sort_by = filter_input(INPUT_POST, 'sortBy', FILTER_SANITIZE_STRING);
          
    $sort_by = empty($sort_by) ? self::get_admin_user_setting('sort_by', Participants_Db::plugin_setting('admin_default_sort')) : $sort_by;
    $sort_order = empty($sort_order) ? self::get_admin_user_setting('sort_order', Participants_Db::plugin_setting('admin_default_sort_order')) : $sort_order;
          
          self::set_admin_user_setting('sort_by', $sort_by);
          self::set_admin_user_setting('sort_order', $sort_order);
        }

        /**
   * saves the filter array
   * 
   * @param array $filter_array
   */
  public static function save_filter($value)
  {

    set_transient(self::$filter_transient, $value);
  }

  /**
   * gets a filter array
   * 
   * this is used for pagination to set the query and the search form values
   * 
   * returns an array of default values if no filter array has been saved
   * 
   * @return array the filter values
   */
  public static function get_filter()
  {

    $filter = get_transient(self::$filter_transient);

    return $filter ? $filter : self::$default_filter;
  }

  /**
   * gets a search array from the filter
   * 
   * provides a blank array if there is no defined filter at the index given
   * 
   * @param int $index filter array index to get
   * 
   * @return array
   */
  public static function get_filter_set($index)
  {
    if (isset(self::$filter['search'][$index]) && is_array(self::$filter['search'][$index])) {
      return self::$filter['search'][$index];
    } else {
      return self::$default_filter['search'][0];
    }
  }

  /**
   * supplies an array of display fields
   * 
   * @return array array of field names
   */
  public static function get_display_columns()
  {
    $display_columns = array();
    foreach (self::$display_columns as $col) {
      $display_columns[] = $col->name;
    }
    return $display_columns;
  }

  /**
   * gets a user preference
         * 
         * @param string $name name of the setting to get
         * @param string|bool $setting if there is no setting, supply this value instead
         * @return string|bool the setting value or false if not found
         */
  public static function get_admin_user_setting($name, $setting = false)
  {
          
    return self::get_user_setting($name, $setting, self::$user_settings);
        }

        /**
   * sets a user preference
         * 
         * @param string $name
         * @param string|int $value the setting value
         * @return null
         */
  public static function set_admin_user_setting($name, $value)
  {

    self::set_user_setting($name, $value, self::$user_settings);
  }

  /**
   * sets a settings transient
   * 
   * @param string $name of the setting value to set
   * @param string|array $value new value of the setting
   * @param string $setting_name of the setting transient
   */
  public static function set_user_setting($name, $value, $setting_name)
  {
          
          $settings = array();
    $saved_settings = get_transient($setting_name);
          if (is_array($saved_settings)) {
            $settings = $saved_settings;
          }
          $settings[$name] = $value;
    set_transient($setting_name, $settings);
  }

  /**
   * gets a user setting
   * 
   * @param string $name name of the setting to get
   * @param string|bool $setting if there is no setting, supply this value instead
   * @param string $setting_name the name of the transient to use
   * @return string|bool the setting value or false if not found
   */
  public static function get_user_setting($name, $setting, $setting_name)
  {

    if ($settings = get_transient($setting_name)) {
      $setting = isset($settings[$name]) ? $settings[$name] : $setting;
    }
    return $setting;
        }

        /**
   * supplies the second part of a download filename
   * 
   * this is usually appended to the end of the base fielname for a plugin-generated file
   * 
   * @return string a filename-compatible datestamp
   */
  public static function filename_datestamp() {
    return '-' . str_replace(array('/', '#', '.', '\\', ', ', ',', ' '), '-', date_i18n(Participants_Db::$date_format));
  } 
  /**
         * sets up the internationalization strings
         */
  private static function _setup_i18n()
  {

          /* translators: the following 5 strings are used in logic matching, please test after translating in case special characters cause problems */
          self::$i18n = array(
              'delete_checked' => _x('Delete Checked', 'submit button label', 'participants-database'),
              'change' => _x('Change', 'submit button label', 'participants-database'),
              'sort' => _x('Sort', 'submit button label', 'participants-database'),
              'filter' => _x('Filter', 'submit button label', 'participants-database'),
              'clear' => _x('Clear', 'submit button label', 'participants-database'),
              'search' => _x('Search', 'search button label', 'participants-database'),
          );
        }

      }

      // class ?>