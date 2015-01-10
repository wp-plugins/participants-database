<?php
/*
 * add / edit / delete fields and field groups and their attributes
 * 
 * 
 * @category   
 * @package    WordPress
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2014 xnau webdesign
 * @license    GPL2
 * @version    Release: 1.5.5
 * @link       http://wordpress.org/extend/plugins/participants-database/
 */
if (!Participants_Db::current_user_has_plugin_role('admin'))
  exit;

class PDb_Manage_Fields {

  /**
   * @var array translations strings used by this class
   */
  var $i18n;

  /**
   * @var array all defined groups
   */
  var $groups;

  /**
   * @var array of field attribute names
   */
  var $attribute_columns;

  /**
   * @var array of group title strings
   */
  var $group_titles;

  /**
   * @var array of group values
   */
  var $group_values;

  /**
   * @var array of field values from the database
   */
  var $fields_data;
  /**
   * @var array of error messages
   */
  var $error_msgs = array();
  /**
   * instantiate the class
   * 
   * @return null 
   */
  function __construct()
  {
    
    $this->i18n = self::get_i18n();

    $this->process_submit();
    
    $this->set_groups();
    $this->setup_group_edit_values();

    $this->print_header();
    $this->print_group_tabs();
    $this->print_footer();
  }

  /**
   * print the edit form header
   * 
   * @return null
   */
  protected function print_header()
  {
    ?>
    <div class="wrap participants_db">
      <?php Participants_Db::admin_page_heading() ?>
      <h3><?php _e('Manage Database Fields', 'participants-database') ?></h3>
      <?php Participants_Db::admin_message(); ?>
      <h4><?php _e('Field Groups', 'participants-database') ?>:</h4>
      <div id="fields-tabs">
        <ul>
          <?php
          $mask = '<span class="mask"></span>';
          foreach ($this->groups as $group) {
            echo '<li><a href="#' . $group . '" id="tab_' . $group . '">' . $this->group_titles[$group] . '</a>' . $mask . '</li>';
          }
          echo '<li class="utility"><a href="#field_groups">' . __('Field Groups', 'participants-database') . '</a>' . $mask . '</li>';
          echo '<li class="utility"><a href="#help">' . __('Help', 'participants-database') . '</a>' . $mask . '</li>';
          ?>
        </ul>
        <?php
      }

    /**
     * print all groups edit tab contents
     */
    protected function print_group_tabs()
    {
      foreach ($this->groups as $group) {
        $this->print_group_tab_content($group);
      }
      $this->print_group_edit_tab_content();
    }

    /**
     * prints an individual group tab content
     * 
     * @param string $group current group name
     */
    protected function print_group_tab_content($group)
    {
      $internal_group = $group === 'internal';
      ?>
      <div id="<?php echo $group ?>" class="manage-fields-wrap" >
        <form id="manage_<?php echo $group ?>_fields" method="post" autocomplete="off">
          <h3><?php echo $this->group_titles[$group], ' ', $this->i18n['fields'] ?></h3>
          <p>
            <?php
            if (! $internal_group) :
              // "add field" functionality
              PDb_FormElement::print_element(array(
                  'type' => 'submit',
                  'value' => $this->i18n['add field'],
                  'name' => 'action',
                  'attributes' => array(
                      'class' => 'button button-default',
                      'disabled' => 'disabled'
                  )
                      )
              );
              PDb_FormElement::print_element(array(
                  'type' => 'text',
                  'name' => 'title',
                  'value' => '',
                  'attributes' => array(
                      'placeholder' => $this->i18n['new field title'] . '&hellip;',
                      'class' => 'add_field'
                  )
                      )
              );

            endif; // skip internal groups
            // number of rows in the group
            $num_group_rows = count($this->fields_data[$group]);

            $last_order = $num_group_rows > 1 ? $this->fields_data[$group][$num_group_rows - 1]['order'] + 1 : 1;

            PDb_FormElement::print_hidden_fields(array('group' => $group, 'order' => $last_order));
            ?>
          </p>
          <table class="wp-list-table widefat fixed manage-fields" cellspacing="0" >
            <thead>
              <tr>
                <?php if (!$internal_group) : ?>
                  <th scope="col" class="delete vertical-title"><span><?php echo $this->table_header('delete') ?></span></th>
                  <?php
                endif; // internal group test

                foreach ($this->attribute_columns[$group] as $attribute_column) {

                  if ($internal_group && in_array($attribute_column, array('order')))
                    continue;

                  $column_class = $attribute_column;
                  $column_class .= in_array($attribute_column, array('order', 'persistent', 'sortable', 'admin_column', 'display_column', 'CSV', 'signup', 'display', 'readonly')) ? ' vertical-title' : '';
                  $column_class .= in_array($attribute_column, array('admin_column', 'display_column',)) ? ' number-column' : '';
                  ?>
                  <th scope="col" class="<?php echo $column_class ?>"><span><?php echo $this->table_header($attribute_column) ?></span></th>
                <?php
              }
              ?>
              </tr>
            </thead>
            <tbody id="<?php echo $group ?>_fields">
              <?php
              if ($num_group_rows < 1) { // there are no rows in this group to show
                ?>
                <tr><td colspan="<?php echo count($this->attribute_columns[$group]) + 1 ?>"><?php _e('No fields in this group', 'participants-database') ?></td></tr>
                    <?php
                  } else {
                    // add the rows of the group
                    foreach ($this->fields_data[$group] as $database_row) :
                      ?>
                  <tr id="db_row_<?php echo $database_row['id'] ?>">
                      <?php if (!$internal_group) : ?>
                      <td>
                        <?php
                      endif; // hidden field test
                      // add the hidden fields
                      foreach (array('id'/* ,'name' */) as $attribute_column) {

                        $value = Participants_Db::prepare_value($database_row[$attribute_column]);

                        $element_atts = array_merge($this->get_edit_field_type($attribute_column), array(
                            'name' => 'row_' . $database_row['id'] . '[' . $attribute_column . ']',
                            'value' => $value,
                        ));
                        PDb_FormElement::print_element($element_atts);
                      }
                      PDb_FormElement::print_element(array(
                          'type' => 'hidden',
                          'value' => '',
                          'name' => 'row_' . $database_row['id'] . '[status]',
                          'attributes' => array('id' => 'status_' . $database_row['id']),
                      ));
                      if (!$internal_group) :
                        ?>
                        <a href="#" title="<?php echo $database_row['id'] ?>" name="delete_<?php echo $database_row['id'] ?>" class="delete" ref="<?php _e('field', 'participants-database') ?>"><span class="glyphicon glyphicon-remove"></span></a>
                      </td>
                      <?php
                    endif; // internal group test
                    // list the fields for editing
                    foreach ($this->attribute_columns[$group] as $attribute_column) :

                      $edit_field_type = $this->get_edit_field_type($attribute_column);

                      if ($internal_group && in_array($attribute_column, array('order')))
                        continue;

                      // preserve backslashes in regex expressions
                      if ($attribute_column == 'validation') {
                        if ($database_row['name'] == 'email' && $database_row[$attribute_column] == 'email')
                          $database_row[$attribute_column] = 'email-regex';
                      }

                      $value = Participants_Db::prepare_value($database_row[$attribute_column]);

                      $element_atts = array_merge($edit_field_type, array(
                          'name' => 'row_' . $database_row['id'] . '[' . $attribute_column . ']',
                          'value' => $this->prep_value($value, in_array($attribute_column, array('title', 'description'))),
                      ));
                      ?>
                      <td class="<?php echo $attribute_column ?>"><?php PDb_FormElement::print_element($element_atts) ?></td>
                    <?php
                  endforeach; // columns
                  ?>
                  </tr>
                <?php
              endforeach; // rows
            } // num group rows 
            ?>
            </tbody>
          </table>
          <p class="submit">
            <?php
            PDb_FormElement::print_element(array(
                'type' => 'submit',
                'name' => 'action',
                'value' => $this->i18n['update fields'],
                'class' => 'button button-primary manage-fields-update'
                    )
            );
            ?>
          </p>
        </form>
      </div><!-- tab content container -->
  <?php
  }

  /**
   * prints the groups edit tab content
   * 
   * @global object $wpdb
   * @return null 
   */
  protected function print_group_edit_tab_content()
  {
    global $wpdb;
    ?>
        <div id="field_groups" class="manage-fields-wrap">
          <form id="manage_field_groups" method="post">
            <input type="hidden" name="action" value="<?php echo $this->i18n['update groups'] ?>" />
            <h3><?php _e('Edit / Add / Remove Field Groups', 'participants-database') ?></h3>
            <p>
              <?php
              // "add group" functionality
              PDb_FormElement::print_element(array(
                  'type' => 'submit',
                  'value' => $this->i18n['add group'],
                  'name' => 'action',
                  'attributes' => array(
                      'class' => 'button button-default',
                      'disabled' => 'disabled'
                  )
                      )
              );
              PDb_FormElement::print_element(array(
                  'type' => 'text',
                  'name' => 'group_title',
                  'value' => '',
                  'attributes' => array(
                      'placeholder' => $this->i18n['new group title'] . '&hellip;',
                      'class' => 'add_field'
                  )
                      )
              );
              $next_order = count($this->group_values) + 1;
              PDb_FormElement::print_hidden_fields(array('group_order' => $next_order));
              ?>
            </p>
            <table class="wp-list-table widefat fixed manage-fields manage-field-groups" cellspacing="0" >
              <thead>
                <tr>
                  <th scope="col" class="fields vertical-title"><span><?php echo $this->table_header(__('fields', 'participants-database')) ?></span></th>
                  <th scope="col" class="delete vertical-title"><span><?php echo $this->table_header(__('delete', 'participants-database')) ?></span></th>
    <?php
    foreach (current($this->group_values) as $column => $value) {

      $column_class = in_array($column, array('order', 'admin', 'display')) ? $column . ' vertical-title' : $column;
      ?>
                    <th scope="col" class="<?php echo $column_class ?>"><span><?php echo $this->table_header($column) ?></span></th>
                  <?php
                }
                ?>
                </tr>
              </thead>
              <tbody>
                  <?php
                  foreach ($this->group_values as $group => $group_values) {
                    //  if ($group == 'internal')
                    //    continue;

                    $group_count = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . Participants_Db::$fields_table . ' WHERE `group` = "%s"', $group));
                    ?>
                  <tr>
                    <td id="field_count_<?php echo $group ?>"><?php echo $group_count ?></td>
                    <td><a href="<?php echo $group_count ?>" name="delete_<?php echo $group ?>" class="delete" ref="<?php _e('group', 'participants-database') ?>"><span class="glyphicon glyphicon-remove"></span></a></td>
                    <?php
                    foreach ($group_values as $column => $value) {

                      $attributes = array();
                      $options = array();
                      $name = '';

                      switch ($column) {

                        case 'display':
                        case 'admin':
                          $attributes = array('style' => 'width:20px');
                          $type = 'checkbox';
                          $options = array(1, 0);
                          break;

                        case 'description':
                          $type = 'text-area';
                          break;

                        case 'order':
                          $attributes = array('style' => 'width:30px');
                          $name = 'order_' . $group;
                          $type = 'drag-sort';
                          break;

                        case 'name':
                          $type = 'text';
                          $attributes = array('readonly' => 'readonly');

                        default:
                          $type = 'text';
                          
                      }
                      $element_atts = array(
                          'name' => ( empty($name) ? $group . '[' . $column . ']' : $name ),
                          'value' => htmlspecialchars(stripslashes($value)),
                          'type' => $type,
                      );
                      if (!empty($attributes))
                        $element_atts['attributes'] = $attributes;
                      if (!empty($options))
                        $element_atts['options'] = $options;
                      ?>
                      <td class="<?php echo $column ?>"><?php PDb_FormElement::print_element($element_atts); ?></td>
                  <?php
                }
                ?>
                  </tr>
                <?php
              }
              ?>
              </tbody>
            </table>
            <p class="submit">
    <?php
    PDb_FormElement::print_element(array(
        'type' => 'submit',
        'name' => 'submit-button',
        'value' => $this->i18n['update groups'],
        'class' => 'button button-primary'
            )
    );
    ?>
            </p>
          </form>
        </div><!-- groups tab panel -->
          <?php
        }

        /**
         * prints the fields edit page footer
         */
        protected function print_footer()
        {
          ?>
        <div id="help">
          <?php include Participants_Db::$plugin_path . 'manage_fields_help.php' ?>
        </div>
      </div><!-- ui-tabs container -->
      <div id="dialog-overlay"></div>
      <div id="confirmation-dialog"></div>
      <?php
    }

    /**
     * sets up the group values
     * 
     * properties set here: $groups, $fields_data, $group_titles
     * 
     * @global object $$wpdb
     * @return null 
     */
    protected function set_groups()
    {
      global $wpdb;
      // get the defined groups
      $this->groups = Participants_Db::get_groups('name');
      // get an array with all the defined fields
      foreach ($this->groups as $group) {

        // only display these columns for internal group
        $select_columns = ( $group === 'internal' ? '`id`,`order`,`name`,`title`,`admin_column`,`sortable`,`CSV`' : '*' );

        $sql = "SELECT $select_columns FROM " . Participants_Db::$fields_table . ' WHERE `group` = "' . $group . '" ORDER BY `order` ';
        $this->fields_data[$group] = $wpdb->get_results($sql, ARRAY_A);

        // get an array of the field attributes
        $this->attribute_columns[$group] = $wpdb->get_col_info('name');

        $group_title = $wpdb->get_var('SELECT `title` FROM ' . Participants_Db::$groups_table . ' WHERE `name` = "' . $group . '"');
        $this->group_titles[$group] = empty($group_title) ? ucwords(str_replace('_', ' ', $group)) : $group_title;


        // remove read-only fields
        foreach (array('id'/* ,'name' */) as $item) {
          unset($this->attribute_columns[$group][array_search($item, $this->attribute_columns)]);
        }
      }
    }

    /**
     * sets up the groups management iterator
     * 
     * @return null 
     */
    protected function setup_group_edit_values()
    {
      $this->group_values = Participants_Db::get_groups('`order`,`display`,`admin`,`name`,`title`,`description`');
    }

    /**
     * processes the form submission
     * 
     * @global object $wpdb
     * @return null 
     */
    protected function process_submit()
    {
      global $wpdb;
      
      // process form submission
      $action = filter_input(INPUT_POST, 'action');

      switch ($action) {

        case 'reorder_fields':
          unset($_POST['action'], $_POST['submit-button']);
          foreach ($_POST as $key => $value) {
            $wpdb->update(
                    Participants_Db::$fields_table, 
                    array('order' => filter_var($value, FILTER_VALIDATE_INT)), 
                    array('id' => filter_var(str_replace('row_', '', $key), FILTER_VALIDATE_INT))
            );
          }
          break;

        case 'reorder_groups':
          unset($_POST['action'], $_POST['submit-button']);
          foreach ($_POST as $key => $value) {
            $wpdb->update(
                    Participants_Db::$groups_table, 
                    array('order' => filter_var($value, FILTER_VALIDATE_INT)), 
                    array('name' => filter_var(str_replace('order_', '', $key), FILTER_SANITIZE_STRING))
            );
          }
          break;

        case $this->i18n['update fields']:
          // dispose of these now unneeded fields
          unset($_POST['action'], $_POST['submit-button']);

          foreach ($_POST as $name => $row) {

            // skip all non-row elements
            if (false === strpos($name, 'row_'))
              continue;

            if ($row['status'] == 'changed') {

              $id = filter_var($row['id'], FILTER_VALIDATE_INT);

              if (!empty($row['values'])) {

                $row['values'] = serialize($this->prep_values_array($row['values']));
              }

              if (!empty($row['validation']) && !in_array($row['validation'], array('yes', 'no'))) {

                $row['validation'] = str_replace('\\\\', '\\', $row['validation']);
              }

              /*
               * modify the datatype if necessary
               * 
               * we prevent the datatype from being changed to a smaller type to protect 
               * data. If the user really wants to do this, they will have to do it manually
               */
              if (isset($row['group']) && $row['group'] != 'internal') {
                $sql = "SHOW FIELDS FROM " . Participants_Db::$participants_table . ' WHERE `field` = "%s"';
                $field_info = $wpdb->get_results($wpdb->prepare($sql, $row['name']));
                $new_type = PDb_FormElement::get_datatype($row['form_element']);
                $current_type = current($field_info)->Type;
                if ($new_type != $current_type and !($new_type == 'tinytext' and $current_type == 'text')) {

                  $sql = "ALTER TABLE " . Participants_Db::$participants_table . " MODIFY COLUMN `" . esc_sql($row['name']) . "` " . $new_type;

                  $result = $wpdb->get_results($sql);
                }
              }
              /*
               * enforce the values for a captcha field
               */
              if (isset($row['form_element']) && $row['form_element'] === 'captcha') {
                $row['validation'] = 'captcha';
                foreach (array('display_column', 'admin_column', 'CSV', 'persistent', 'sortable') as $c)
                  $row[$c] = 0;
                $row['readonly'] = 1;
              }

              foreach (array('title', 'help_text', 'default') as $field) {
                if (isset($row[$field])) $row[$field] = stripslashes($row[$field]);
              }

              // remove the fields we won't be updating
              unset($row['status'], $row['id'], $row['name']);

              $wpdb->update(Participants_Db::$fields_table, $row, array('id' => $id));
            }
          }

          break;

        case $this->i18n['update groups']:

          // dispose of these now unneeded fields
          unset($_POST['action'], $_POST['submit-button'], $_POST['group_title'], $_POST['group_order']);

          foreach ($_POST as $name => $row) {

	          foreach (array('title', 'description') as $field) {
	            if (isset($row[$field])) $row[$field] = stripslashes($row[$field]);
	          }

            // make sure name is legal
            //$row['name'] = $this->make_name( $row['name'] );

            $wpdb->update(Participants_Db::$groups_table, $row, array('name' => stripslashes_deep($name)));
          }
          break;

        // add a new blank field
        case $this->i18n['add field']:

          // use the wp function to clear out any irrelevant POST values
          $atts = shortcode_atts(array(
              'name' => $this->make_name(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING)),
              'title' => htmlspecialchars(stripslashes(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING)), ENT_QUOTES, "UTF-8", false),
              'group' => filter_input(INPUT_POST, 'group', FILTER_SANITIZE_STRING),
              'order' => filter_input(INPUT_POST, 'order', FILTER_SANITIZE_NUMBER_INT),
              'validation' => 'no',
                  ), $_POST
          );
          if (empty($atts['name'])) {
            break; // ignore empty field name
          }
          // if they're trying to use a reserved name, stop them
          if (in_array($atts['name'], Participants_Db::$reserved_names)) {

            Participants_Db::set_admin_message( sprintf(
                    '<h3>%s</h3> %s:<br />%s', __('Cannot add a field with that name', 'participants-database'), __('This name is reserved; please choose another. Reserved names are', 'participants-database'), implode(', ', Participants_Db::$reserved_names)), 'error');
            break;
          }
          // prevent name from beginning with a number
          if (preg_match('/^(\d)/', $atts['name'])) {

            Participants_Db::set_admin_message( sprintf(
                    '<h3>%s</h3> %s', __('The name cannot begin with a number', 'participants-database'), __('Please choose another.', 'participants-database')
            ), 'error');
            break;
          }
          $result = Participants_Db::add_blank_field($atts);
          if (false === $result)
            Participants_Db::set_admin_message( $this->parse_db_error($wpdb->last_error, $action), 'error');
          break;

        // add a new blank field
        case $this->i18n['add group']:

          global $wpdb;
          $wpdb->hide_errors();

          $atts = array(
              'name' => $this->make_name($_POST['group_title']),
              'title' => htmlspecialchars(stripslashes($_POST['group_title']), ENT_QUOTES, "UTF-8", false),
              'order' => $_POST['group_order'],
          );

          $wpdb->insert(Participants_Db::$groups_table, $atts);

          if ($wpdb->last_error)
            Participants_Db::set_admin_message( $this->parse_db_error($wpdb->last_error, $action), 'error');
          break;

        case 'delete_field':

          global $wpdb;
          $wpdb->hide_errors();

          $result = $wpdb->query($wpdb->prepare('
      DELETE FROM ' . Participants_Db::$fields_table . '
      WHERE id = "%s"', $_POST['delete'])
          );

          break;

        case 'delete_group':

          global $wpdb;
          //$wpdb->hide_errors();

          $group_count = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . Participants_Db::$fields_table . ' WHERE `group` = "%s"', $_POST['delete']));

          if ($group_count == 0)
            $result = $wpdb->query($wpdb->prepare('DELETE FROM ' . Participants_Db::$groups_table . ' WHERE `name` = "%s"', $_POST['delete']));

          break;

        default:
          $action = '';
      }
      if (!empty($action) && empty(Participants_Db::$admin_message)) {
        Participants_Db::set_admin_message(__('Your information has been updated','participants-database'), 'updated');
      }
    }

    /**
     * sets up the edit table headers
     * 
     * @param string $string the header text
     * @return string
     */
    function table_header($string)
    {

      // check for a translated string, use it if found
      $string = isset($this->i18n[$string]) ? $this->i18n[$string] : $string;

      return str_replace(array('_'), array(" "), $string);
    }

    /**
     * 
     * makes a legal database column name
     * 
     * @param string the proposed name
     * @retun string the legal name
     */
    function make_name($string)
    {
      /*
       * truncate to 64 characters, then replace any characters that would cause problems 
       * in queries
       */
      $name = strtolower(str_replace(
              array(' ', '-', '/', "'", '"', '\\', '#', '.', '$', '&', '%'), 
              array('_', '_', '_', '', '', '', '', '', '', 'and', 'pct'), 
              stripslashes(substr($string, 0, 64))
      ));
      /*
       * allow only proper unicode letters, numerals and legal symbols
       */
      return preg_replace('#[^\p{L}\p{N}_]#u', '', $name);
    }

    /**
     * breaks the submitted comma-separated string of values into elements for use in 
     * select/radio/checkbox type form elements
     * 
     * if the substrings contain a '::' we split that, with the first substring being 
     * the key (title) and the second the value
     * 
     * there is no syntax checking...if there is no key string before the ::, the element 
     * will have an empty key, but it will be obvious to the user
     * 
     * @param string $values
     * @return array
     */
    function prep_values_array($values)
    {

      /* we can do this because if the matching string is in position 0, it's not 
       * valid syntax anyway
       */
      $has_labels = strpos($values, '::') !== false;
      $array = array();
      $a = explode(',', stripslashes($values));
      if ($has_labels) {
        foreach ($a as $e) {
          if (strpos($e, '::') !== false) {
            list($key, $value) = explode('::', $e);
            /*
             * @version 1.6
             * this is to allow for an optgroup label that is the same as a value label...
             * with an admittedly funky hack: adding a space to the end of the key for the 
             * optgroup label. In most cases it will be unnoticed.
             */
            $array_key = in_array($value, array('false','optgroup',false)) ? trim($key) . ' ' : trim($key);
            $array[$array_key] = $this->prep_value(trim($value), true);
          } else {
            $array[$this->prep_value($e, true)] = $this->prep_value($e, true);
          }
        }
      } else {
        foreach ($a as $e) {
          $array[] = $this->prep_value($e, true);
        }
      }

      return $array;
    }

    /**
     * prepares a string for storage in the database
     * 
     * @param string $value
     * @param bool $single_encode if true, don't encode entities 
     * @return string
     */
    function prep_value($value, $single_encode = false)
    {

      if ($single_encode)
        return trim(stripslashes($value));
      else
        return htmlentities(trim(stripslashes($value)), ENT_QUOTES, "UTF-8", true);
    }

    /**
     * makes a readable string out of a database error
     * 
     * @param string $error
     * @param string $context
     * @return string
     */
    function parse_db_error($error, $context)
    {

      // unless we find a custom message, use the class error message
      $message = $error;

      $item = false;

      switch ($context) {

        case $this->i18n['add group']:

          $item = $this->i18n['group'];
          break;

        case $this->i18n['add field']:

          $item = $this->i18n['field'];
          break;
      }

      if ($item && false !== stripos($error, 'duplicate')) {

        $message = sprintf(__('The %1$s was not added. There is already a %1$s with that name, please choose another.', 'participants-database'), $item);
      }

      return $message;
    }

    /**
     * displays an edit field for a field attribute
     * 
     * @param string $field name of the field
     * @return array contains parameters to use in instantiating the xnau_FormElement object
     */
    function get_edit_field_type($field)
    {

      switch ($field) :

        // small integer fields
        case 'id':
          return array('type' => 'hidden');

        case 'order':
          return array('type' => 'drag-sort');

        case 'admin_column':
        case 'display_column':
          return array('type' => 'text', 'attributes' => array('class' => 'digit'));

        // all the booleans
        case 'persistent':
        case 'sortable':
        case 'CSV':
        case 'signup':
        case 'readonly':
          return array('type' => 'checkbox', 'options' => array(1, 0));

        // field names can't be edited
        case 'name':
          return array('type' => 'text', 'attributes' => array('readonly' => 'readonly'));

        // all the text-area fields
        case 'values':
        case 'help_text':
          return array('type' => 'text-area');

        // drop-down fields
        case 'form_element':
          // populate the dropdown with the available field types from the xnau_FormElement class
          return array('type' => 'dropdown', 'options' => array_flip(PDb_FormElement::get_types()) + array('null_select' => false));

        case 'validation':
          return array(
              'type' => 'dropdown-other',
              'options' => array(
                  __('Not Required', 'participants-database') => 'no',
                  __('Required', 'participants-database') => 'yes',
                  __('Email', 'participants-database') => 'email-regex',
                  'CAPTCHA' => 'captcha',
                  'null_select' => false,
              ),
              'attributes' => array('other' => 'regex/match'),
          );

        case 'group':
          // these options are defined on the "settings" page
          return array('type' => 'dropdown', 'options' => Participants_Db::get_groups('name', 'internal') + array('null_select' => false));

        case 'link':

        case 'title':
        default:
          return array('type' => 'text');

      endswitch;
    }

    /**
     * provides an array of translation strings
     * 
     * @return array of translation strings
     */
    public static function get_i18n()
    {
      return array(
          /* translators: these strings are used in logic matching, please test after translating in case special characters cause problems */
          'update fields' => __('Update Fields', 'participants-database'),
          'update groups' => __('Update Groups', 'participants-database'),
          'add field' => __('Add Field', 'participants-database'),
          'add group' => __('Add Group', 'participants-database'),
          'group' => __('group', 'participants-database'),
          'field' => __('field', 'participants-database'),
          'new field title' => __('new field title', 'participants-database'),
          'new group title' => __('new group title', 'participants-database'),
          'fields' => _x('Fields', 'column name', 'participants-database'),
          'Group' => _x('Group', 'column name', 'participants-database'),
          'order' => _x('Order', 'column name', 'participants-database'),
          'name' => _x('Name', 'column name', 'participants-database'),
          'title' => _x('Title', 'column name', 'participants-database'),
          'default' => _x('Default', 'column name', 'participants-database'),
          'help_text' => _x('Help Text', 'column name', 'participants-database'),
          'form_element' => _x('Form Element', 'column name', 'participants-database'),
          'values' => _x('Values', 'column name', 'participants-database'),
          'validation' => _x('Validation', 'column name', 'participants-database'),
          'display_column' => str_replace(' ', '<br />', _x('Display Column', 'column name', 'participants-database')),
          'admin_column' => str_replace(' ', '<br />', _x('Admin Column', 'column name', 'participants-database')),
          'sortable' => _x('Sortable', 'column name', 'participants-database'),
          'CSV' => _x('CSV', 'column name, acronym for "comma separated values"', 'participants-database'),
          'persistent' => _x('Persistent', 'column name', 'participants-database'),
          'signup' => _x('Signup', 'column name', 'participants-database'),
          'readonly' => _x('Read Only', 'column name', 'participants-database'),
          'admin' => _x('Admin', 'column name', 'participants-database'),
          'delete' => _x('Delete', 'column name', 'participants-database'),
          'display' => _x('Display', 'column name', 'participants-database'),
          'description' => _x('Description', 'column name', 'participants-database'),
      );
    }

  }
  ?>