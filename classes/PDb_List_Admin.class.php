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
 * @version    Release: 1.5.4.1
 * @link       http://wordpress.org/extend/plugins/participants-database/
 */
class PDb_List_Admin {

  // holds the main query for building the list
  static $list_query;
  // translations strings for buttons
  static $i18n;
  // holds the pagination object
  static $pagination;
  // holds the number of list items to show per page
  static $page_list_limit;
  // the name of the list page variable
  static $list_page = 'listpage';
  // name of the list anchor element
  static $list_anchor = 'participants-list';
  // name of the list limit transient
  static $user_settings = 'admin-user-settings';
  // the number of records after filtering
  static $num_records;
  // all the records are held in this array
  static $participants;
  // holds the url of the registrations page
  static $registration_page_url;
  /**
   * holds the columns to display in the list
   * 
   * @var array of field objects
   */
  static $display_columns;
  // holds th list of sortable columns
  static $sortables;
  // holds the settings for the list filtering and sorting
  static $filter;
  // holds plugin options array
  static $options;

  /**
   * initializes and outputs the list for the backend
   */
  public static function initialize() {

    self::_setup_i18n();

    self::$options = Participants_Db::$plugin_options;

    get_currentuserinfo();

    // set up the user settings transient
    global $user_ID;
    self::$user_settings = Participants_Db::$prefix . self::$user_settings . '-' . $user_ID;
    
    self::set_list_limit();
  
    self::$registration_page_url = get_bloginfo('url') . '/' . ( isset(self::$options['registration_page']) ? self::$options['registration_page'] : '' );

    self::setup_display_columns();

    self::$sortables = Participants_Db::get_sortables();

    // set up the basic values
    $default_values = array(
        'search_field' => self::get_admin_user_setting('search_field', 'none'),
        'value' => '',
        'operator' => self::get_admin_user_setting('search_op', 'LIKE'),
        'sortBy' => self::get_admin_user_setting('sort_by', self::$options['admin_default_sort']),
        'ascdesc' => self::get_admin_user_setting('sort_order', self::$options['admin_default_sort_order']),
        'submit-button' => '',
    );

    // merge the defaults with the $_REQUEST array so if there are any new values coming in, they're included
    self::$filter = shortcode_atts($default_values, $_REQUEST);
    
    self::set_admin_user_setting('search_field', self::$filter['search_field']);
    self::set_admin_user_setting('search_op', self::$filter['operator']);
    self::set_admin_user_setting('sort_by', self::$filter['sortBy']);
    self::set_admin_user_setting('sort_order', self::$filter['ascdesc']);
    
    //error_log(__METHOD__.' request:'.print_r($_REQUEST,1).' filter:'.print_r(self::$filter,1));

    // process delete and items-per-page form submissions
    self::_process_general();

    self::_process_search(self::$filter['submit-button']);

    if (WP_DEBUG)
      error_log(__METHOD__ . ' list query= ' . self::$list_query);

    if (current_user_can(Participants_Db::$plugin_options['plugin_admin_capability'])) {
      global $current_user;
      set_transient(Participants_Db::$prefix . 'admin_list_query' . $current_user->ID, self::$list_query, 3600 * 24);
    }

    // get the $wpdb object
    global $wpdb;

    // get the number of records returned
    self::$num_records = $wpdb->get_var(str_replace('*', 'COUNT(*)', self::$list_query));

    // set the pagination object
    self::$pagination = new PDb_Pagination(array(
        'link' => self::prepare_page_link($_SERVER['REQUEST_URI']),
        'page' => isset($_GET[self::$list_page]) ? $_GET[self::$list_page] : '1',
        'size' => self::$page_list_limit,
        'total_records' => self::$num_records,
//        'wrap_tag' => '<div class="pdb-list"><div class="pagination"><label>' . _x('Page', 'noun; page number indicator', 'participants-database') . ':</label> ',
//        'wrap_tag_close' => '</div></div>',
        'add_variables' => http_build_query(self::$filter) . '#pdb-list-admin',
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

    // print the CSV export form (admin users only)
    if (current_user_can(Participants_Db::$plugin_options['plugin_admin_capability']))
      self::_print_export_form();

    // print the plugin footer
    Participants_Db::plugin_footer();
  }

  /**
   * strips the page number out of the URI so it can be used as a link to other pages
   *
   * @param string $uri the incoming URI, usually $_SERVER['REQUEST_URI']
   *
   * @return string the re-constituted URI
   */
  public static function prepare_page_link($uri) {

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
          'search_field',
          'value',
          'operator',
          'sortBy',
          'ascdesc',
          'submit_button',
      );
      foreach ($filter_atts as $att)
        unset($values[$att]);
    }

    return $URI_parts[0] . '?' . http_build_query($values) . '&' . self::$list_page . '=%1$s';
  }

  /** 	
   * processes all the general list actions: delete and  set items-per-page
   */
  private static function _process_general() {

    global $wpdb;

    if (isset($_POST['action']) && $_POST['action'] == 'list_action') {

      switch ($_POST['submit-button']) {

        case self::$i18n['delete_checked']:

          $count = count($_POST['pid']);

          $pattern = $count > 1 ? 'IN ( ' . trim(str_repeat('%s,', $count), ',') . ' )' : '= %s';
          $sql = "DELETE FROM " . Participants_Db::$participants_table . " WHERE id " . $pattern;
          $wpdb->query($wpdb->prepare($sql, $_POST['pid']));
          Participants_Db::set_admin_message(__('Record delete successful.', 'participants-database'), 'updated');
          break;

        case self::$i18n['change']:

          if (floatval($_POST['list_limit']) > 0) self::set_admin_user_setting('list_limit', $_POST['list_limit']);
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
  private static function _process_search($submit) {

    switch ($submit) {

      case self::$i18n['sort']:
      case self::$i18n['filter']:
      case self::$i18n['search']:

        self::$list_query = 'SELECT * FROM ' . Participants_Db::$participants_table . ' p ';

        $delimiter = array("'", "'");

        switch (self::$filter['operator']) {

          case 'LIKE':

            $operator = 'LIKE';
            $delimiter = array('"%', '%"');
            break;

          case 'gt':

            $operator = '>';
            break;

          case 'lt':

            $operator = '<';
            break;

          default:

            $operator = esc_sql(self::$filter['operator']);
        }

        if (self::$filter['value'] !== '') {

          // if the field searched is a "date" field, convert the search string to a date
          $field_atts = Participants_Db::get_field_atts(self::$filter['search_field']);

          $value = self::$filter['value'];

          if ($field_atts->form_element == 'timestamp') {
            
            $value = self::$filter['value'];
            $value2 = false;
            if (strpos(self::$filter['value'], ' to ')) {
              list($value, $value2) = explode('to', self::$filter['value']);
            }
            
            $value = Participants_Db::parse_date($value, $field_atts, false);
            if ($value2)
              $value2 = Participants_Db::parse_date($value2, $field_atts, $field_atts->form_element == 'date');
            
            if ($value !== false) {
            
              $stored_date = "DATE(p." . esc_sql(self::$filter['search_field']) . ")";
            
              if ($value2 !== false and !empty($value2)) {
                
                  self::$list_query .= " WHERE " . $stored_date . " > DATE_ADD(FROM_UNIXTIME(0), interval " . esc_sql($value) . " second) AND " . $stored_date . " < DATE_ADD(FROM_UNIXTIME(0), interval " . esc_sql($value2) . " second)";
                  
              } else {

                if ($operator == 'LIKE')
                  $operator = '=';

                self::$list_query .= " WHERE " . $stored_date . " " . $operator . " DATE_ADD(FROM_UNIXTIME(0), interval " . esc_sql($value) . " second) ";
              }
            }
              } elseif ($field_atts->form_element == 'date') { 

            $value = self::$filter['value'];
            $value2 = false;
            if (strpos(self::$filter['value'], ' to ')) {
              list($value, $value2) = explode('to', self::$filter['value']);
                }

            $value = Participants_Db::parse_date($value, $field_atts, true);
            if ($value2)
              $value2 = Participants_Db::parse_date($value2, $field_atts, $field_atts->form_element == 'date');

            if ($value !== false) {
              
              $stored_date = "CAST(p." . esc_sql(self::$filter['search_field']) . " AS SIGNED)";
            
              if ($value2 !== false and !empty($value2)) {
                
                  self::$list_query .= " WHERE " . $stored_date . " > CAST(" . esc_sql($value) . " AS SIGNED) AND " . $stored_date . " < CAST(" . esc_sql($value2) . "  AS SIGNED)";
                  
              } else {

                if ($operator == 'LIKE') $operator = '=';

                self::$list_query .= " WHERE " . $stored_date . " " . $operator . " CAST(" . esc_sql($value) . " AS SIGNED)";
              }
                
            }
          } else {
            
            self::$list_query .= ' WHERE p.' . esc_sql(self::$filter['search_field']) . ' ' . $operator . " " . $delimiter[0] . esc_sql($value) . $delimiter[1] . " ";
            
          }

          
        }

        // add the sorting
        self::$list_query .= ' ORDER BY p.' . esc_sql(self::$filter['sortBy']) . ' ' . esc_sql(self::$filter['ascdesc']);

        // go back to the first page to display the newly sorted/filtered list
        if (isset($_POST['submit-button']))
          $_GET[self::$list_page] = 1;

        break;

      case self::$i18n['clear'] :

        self::$filter['value'] = '';
        self::$filter['search_field'] = 'none';

        // go back to the first page if the search has just been submitted
        $_GET[self::$list_page] = 1;
        self::$filter['submit-button'] = '';

      default:

        self::$list_query = 'SELECT * FROM ' . Participants_Db::$participants_table . ' ORDER BY `' . esc_sql(self::$filter['sortBy']) . '` ' . esc_sql(self::$filter['ascdesc']);
    }
  }

  /**
   * top section for admin listing
   */
  private static function _admin_top() {
    ?>
    <script type="text/javascript" language="javascript">

      var L10n = {
        "record": "<?php _e("Do you really want to delete the selected record?", 'participants-database') ?>",
        "records": "<?php _e("Do you really want to delete the selected records?", 'participants-database') ?>"
      },
      check_state = false;

      window.onload = function() {
        armDelbutton(false)
      };

      function delete_confirm() {
        var plural = (document.getElementById('select_count').value > 1) ? true : false;
        var x = window.confirm(plural ? L10n.records : L10n.record);
        armDelbutton(x);
        check_state = !x;
        return x;
      }

      function checkedAll() {
        var form = document.getElementById('list_form');
        if (check_state == false) {
          check_state = true
        } else {
          check_state = false;
          armDelbutton(false);
        }
        for (var i = 0; i < form.elements.length; i++) {
          if (form.elements[i].type == 'checkbox' && form.elements[i].name != 'checkall' && form.elements[i].checked != check_state) {
            form.elements[i].checked = check_state;
            addSelects(check_state);
          }
        }
      }

      function addSelects(selected) {
        var count_element = document.getElementById('select_count');
        var count = count_element.value;
        if (selected === true)
          count++;
        else {
          count--;
          document.getElementById('checkall').checked = false;
        }
        if (count < 0)
          count = 0;
        armDelbutton(count > 0);
        count_element.value = count;
      }

      function armDelbutton(state) {
        var delbutton = document.getElementById('delete_button');
        delbutton.setAttribute('class', state ? delbutton.getAttribute('class').replace('unarmed', 'armed') : delbutton.getAttribute('class').replace('armed', 'unarmed'));
        delbutton.disabled = state ? false : true;
      }


      function checkEnter(e) {
        e = e || event;
        return (e.keyCode || event.which || event.charCode || 0) !== 13;
      }

    </script>
    <div  class="wrap participants_db">
    <a id="pdb-list-admin" name="pdb-list-admin"></a>
      <?php Participants_Db::admin_page_heading() ?>
    <div id="poststuff">
      <div class="post-body">
          <h2><?php _e('List Participants', 'participants-database') ?></h2>
          <h3><?php printf(_n('%s record found, sorted by:', '%s records found, sorted by:', self::$num_records, 'participants-database'), "\n" . self::$num_records) ?> 
          <?php echo Participants_Db::column_title(self::$filter['sortBy']) ?>.</h3>
    <?php
  }

  /**
   * prints the sorting and filtering forms
   *
   * @param string $mode determines whether to print filter, sort, both or 
   *                     none of the two functions
   */
  private static function _sort_filter_forms() {

    global $post;
    ?>
      <div class="pdb-searchform">
        <form method="post" id="sort_filter_form" >
          <input type="hidden" name="action" value="sort">
                  <table class="form-table"><tbody><tr><td>
          <fieldset class="widefat inline-controls">
            <legend><?php _e('Show only records with', 'participants-database') ?>:</legend>
        <?php
        //build the list of columns available for filtering
        $filter_columns = array();
        foreach (Participants_db::get_column_atts('backend') as $column) {

          if (in_array($column->name, array('id', 'private_id')))
            continue;

          $filter_columns[$column->title] = $column->name;
        }
        // alphabetize the dropdown
        asort($filter_columns);

        $element = array(
            'type' => 'dropdown',
            'name' => 'search_field',
            'value' => self::$filter['search_field'],
            'options' => array('(' . __('show all', 'participants-database') . ')' => 'none') + $filter_columns,
        );
        PDb_FormElement::print_element($element);
        ?>
            that
    <?php
    $element = array(
        'type' => 'dropdown',
        'name' => 'operator',
        'value' => self::$filter['operator'],
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
            <input id="participant_search_term" type="text" name="value" value="<?php echo @self::$filter['value'] ?>">
            <input class="button button-default" name="submit-button" type="submit" value="<?php echo self::$i18n['filter'] ?>">
            <input class="button button-default" name="submit-button" type="submit" value="<?php echo self::$i18n['clear'] ?>">
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
                'value' => self::$filter['ascdesc'],
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
      </div><?php
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
          <?php if (current_user_can(Participants_Db::$plugin_options['plugin_admin_capability'])) : ?>
          <span style="padding-right:20px" ><input type="submit" name="submit-button" class="button button-default" value="<?php echo self::$i18n['delete_checked'] ?>" onClick="return delete_confirm();" id="delete_button"  ></span>
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
      private static function _main_table($mode = '') {
        ?>

        <table class="wp-list-table widefat fixed pages pdb-list stuffbox" cellspacing="0" >
          <?php
          // template for printing the registration page link in the admin
          $PID_pattern = '<td><a href="%2$s">%1$s</a></td>';
          $head_pattern = '
<th class="%2$s" scope="col">
  <span>%1$s</span>
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
                    <?php if (current_user_can(Participants_Db::$plugin_options['plugin_admin_capability'])) : ?>
                      <input type="checkbox" name="pid[]" value="<?php echo $value['id'] ?>" onClick="addSelects(this.checked)">
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
                        'mode' => (self::$options['admin_thumbnails'] ? 'image':'filename'),
                      );

                    if (
                            isset(self::$options['single_record_link_field']) &&
                            $column->name == self::$options['single_record_link_field'] &&
                            !empty(self::$options['single_record_page'])
                    ) {

                      $page_link = get_permalink(self::$options['single_record_page']);
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
                      if (Participants_Db::$plugin_options['show_time'] == 1 and $column->form_element == 'timestamp' ) {
                        // replace spaces with &nbsp; so the time value stays together on a broken line
                        $format .= ' ' . str_replace(' ', '&\\nb\\sp;', get_option('time_format'));
                      }
                      $time = Participants_Db::is_valid_timestamp($value[$column->name]) ? (int) $value[$column->name] : Participants_Db::parse_date($value[$column->name],$column->name, $column->form_element == 'date');
                      $display_value = $value[$column->name] == '0000-00-00 00:00:00' ? '' : date_i18n($format, $time);
                      //$display_value = date_i18n($format, $time);
                    }
                    else
                      $display_value = '';

                    break;

                  case 'multi-select-other':
                  case 'multi-checkbox':
                    // multi selects are displayed as comma separated lists

                    $display_value = is_serialized($value[$column->name]) ? implode(', ', unserialize($value[$column->name])) : $value[$column->name];
                    break;

                  case 'link':

                    if (is_serialized($value[$column->name])) {

                      $params = unserialize($value[$column->name]);

                      if (empty($params))
                        $page_link = array('', '');

                      if (count($params) == 1)
                        $params[1] = $params[0];
                    } else {

                      // in case we got old unserialized data in there
                      $params = array_fill(0, 2, $value[$column->name]);
                    }

                    $display_value = Participants_Db::make_link($params[0], $params[1]);

                    break;

                  case 'rich-text':

                    if (!empty($value[$column->name]))
                      $display_value = '<span class="textarea">' . $value[$column->name] . '</span>';
                    else
                      $display_value = '';
                    break;

                  case 'text-line':

                    if (
                            isset(self::$options['single_record_link_field']) &&
                            $column->name == self::$options['single_record_link_field'] &&
                            !empty(self::$options['single_record_page'])
                    ) {

                      $url = get_permalink(self::$options['single_record_page']);
                      $template = '<a href="%1$s" >%2$s</a>';
                      $delimiter = false !== strpos($url, '?') ? '&' : '?';
                      $url = $url . $delimiter . 'pdb=' . $value['id'];

                      $display_value = sprintf($template, $url, $value[$column->name]);
                    } elseif (self::$options['make_links']) {

                      $display_value = Participants_Db::make_link($value[$column->name]);
                    } else {

                      $display_value = NULL === $value[$column->name] ? $column->default : esc_html($value[$column->name]);
                    }

                    break;

                  case 'hidden':

                    $display_value = NULL === $value[$column->name] ? '' : esc_html($value[$column->name]);

                    break;

                  default:

                    $display_value = NULL === $value[$column->name] ? $column->default : esc_html($value[$column->name]);
                }

                if ($column->name == 'private_id')
                  printf(
                          $PID_pattern, $display_value, Participants_Db::get_record_link($display_value)
                  );
                else
                  printf($col_pattern, $display_value);
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
            private static function _print_export_form() {
              ?>

      <div class="postbox">
        <h3><?php _e('Export CSV', 'participants-database') ?></h3>
        <div class="inside">
        <form method="post" class="csv-export">
          <input type="hidden" name="subsource" value="<?php echo Participants_Db::PLUGIN_NAME ?>">
          <input type="hidden" name="action" value="output CSV" />
          <input type="hidden" name="CSV type" value="participant list" />
          <?php
          $date_string = str_replace(array('/', '#', '.', '\\', ', ', ',', ' '), '-', date_i18n(Participants_Db::$date_format));
          $suggested_filename = Participants_Db::PLUGIN_NAME . '-' . $date_string . '.csv';
          $namelength = round(strlen($suggested_filename) * 0.9);
          ?>
          <fieldset class="inline-controls">
    <?php _e('File Name', 'participants-database') ?>:
            <input type="text" name="filename" value="<?php echo $suggested_filename ?>" size="<?php echo $namelength ?>" />
            <input type="submit" name="submit-button" value="<?php _e('Download CSV for this list', 'participants-database') ?>" />
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
    private static function _print_header_row($head_pattern) {

      
      // print the "select all" header 
      ?>
    <th scope="col" style="width:3em">
        <?php if (current_user_can(Participants_Db::$plugin_options['plugin_admin_capability'])) : ?>
      <?php /* translators: uses the check symbol in a phrase that means "check all"  printf('<span class="checkmark" >&#10004;</span>%s', __('all', 'participants-database'))s*/ ?>
        <input type="checkbox" onClick="checkedAll('list_form');" name="checkall" id="checkall" ><span class="glyphicon glyphicon-edit" style="opacity: 0"></span>
        <?php endif ?>
      </th>
          <?php
          // print the top header row
          foreach (self::$display_columns as $column) {
            $title = strip_tags(stripslashes($column->title));
            printf(
                    $head_pattern, str_replace(array('"',"'"), array('&quot;','&#39;'), $title), $column->name
            );
          }
        }

        /**
         * sets up the main list columns
         */
        private static function setup_display_columns() {
          
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
        private static function set_list_limit() {

          $limit_value = self::get_admin_user_setting('list_limit', self::$options['list_limit']);
          if (isset($_REQUEST['list_limit']) && floatval($_REQUEST['list_limit']) > 0) {
            $limit_value = $_REQUEST['list_limit'];
          }
          self::$page_list_limit = $limit_value;
          self::set_admin_user_setting('list_limit', $limit_value);
        }
        /**
         * sets the admin list limit value
         */
        private static function set_list_sort() {

          $sort_by = self::get_admin_user_setting('sort_by', self::$options['admin_default_sort']);
          $sort_order = self::get_admin_user_setting('sort_order', self::$options['admin_default_sort_order']);
          
          $sort_order = isset($_POST['ascdesc']) ? $_POST['ascdesc'] : $sort_order;
          $sort_by = isset($_POST['sortBy']) ? $_POST['sortBy'] : $sort_by;
                    
          //error_log(__METHOD__.' sort: '.self::$options['admin_default_sort'].' order: '.self::$options['admin_default_sort_order']);
          
          self::set_admin_user_setting('sort_by', $sort_by);
          self::set_admin_user_setting('sort_order', $sort_order);
        }
        /**
         * gets a user setting
         * 
         * @param string $name name of the setting to get
         * @param string|bool $setting if there is no setting, supply this value instead
         * @return string|bool the setting value or false if not found
         */
        public static function get_admin_user_setting($name, $setting = FALSE) {
          
          if ($settings = get_transient(self::$user_settings)) {
            $setting = $settings[$name] ? $settings[$name] : $setting;
          }
          return $setting;
        }
        /**
         * sets a user setting
         * 
         * @param string $name
         * @param string|int $value the setting value
         * @return null
         */
        public static function set_admin_user_setting($name, $value) {
          
          $settings = array();
          $saved_settings = get_transient(self::$user_settings);
          if (is_array($saved_settings)) {
            $settings = $saved_settings;
          }
          $settings[$name] = $value;
          set_transient(self::$user_settings, $settings);
        }

        /**
         * sets up the internationalization strings
         */
        private static function _setup_i18n() {

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