<?php
/*
 * this file is called by the admin menu item, also a link in the admin record list
 * 
 * submission processing happens in Participants_Db::process_page_request on the
 * admin_init action
 *
 */

if (!isset($participant_id)) {

  // if there is no id in the request, use the default record
  $participant_id = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;
}

if (false === $participant_id) {

  $action = 'insert';
  $page_title = __('Add New Participant Record', 'participants-database');
  $participant_values = Participants_Db::get_default_record();
} else {

  $action = 'update';
  $page_title = __('Edit Existing Participant Record', 'participants-database');
  $participant_values = Participants_Db::get_participant($participant_id);
}

/*
 * if we have a valid ID or are creating a new record, show the form
 */
if ($participant_values) :

//error_log( basename( __FILE__).' default record:'.print_r( $participant_values,1));
//get the groups info
  $groups = Participants_Db::get_groups();

// get the current user's info
  get_currentuserinfo();

  $options = get_option(self::$participants_db_options);

// set up the hidden fields
  $hidden = array(
      'action' => $action,
      'subsource' => Participants_Db::PLUGIN_NAME,
  );
  foreach (array('id', 'private_id') as $i) {
    if (isset($participant_values[$i]))
      $hidden[$i] = $participant_values[$i];
  }

  $section = '';
  ?>
  <div class="wrap admin-edit-participant">
    <h2><?php echo $page_title ?></h2>
    <?php
    if (is_object(Participants_Db::$validation_errors)) {

      echo Participants_Db::$validation_errors->get_error_html();
    } else {
      Participants_Db::admin_message();
    }
    ?>
    <form method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>" enctype="multipart/form-data" autocomplete="off" >
      <?php
      PDb_FormElement::print_hidden_fields($hidden);

      // get the columns and output form
      $readonly_columns = Participants_Db::get_readonly();
      foreach (Participants_db::get_column_atts('backend') as $column) :

        $id_line = '';

        $attributes = array();

        // set a new section
        if ($column->group != $section) {
          if (!empty($section)) {
            ?>
            </table>
            <?php
          } else {
            $id_line = '<tr><th>' . _x('ID', 'abbreviation for "identification"', 'participants-database') . '</th><td>' . ( false === $participant_id ? _x('(new record)', 'indicates a new record is being entered', 'participants-database') : $participant_id ) . '</td></tr>';
          }
          $section = $column->group
          ?>
          <h3 class="field-group-title"><?php echo $groups[$section]['title'] ?></h3>
          <?php if ($options['show_group_descriptions']) echo '<p class="' . Participants_Db::$prefix . 'group-description">' . $groups[$section]['description'] . '</p>' ?>
          <table class="form-table">
            <?php
          }
          echo $id_line;
          ?>

          <tr class="<?php echo ( 'hidden' == $column->form_element ? 'text-line' : $column->form_element ) . ' ' . $column->name . '-field' ?>">
            <?php
            $column_title = str_replace(array('"',"'"), array('&quot;','&#39;'), stripslashes($column->title));
            if ($options['mark_required_fields'] && $column->validation != 'no') {
              $column_title = sprintf($options['required_field_marker'], $column_title);
            }
            ?>
            <?php
            $add_title = '';
            if ($column->form_element == 'hidden') {
              $add_title = sprintf(' <span class="fieldnote">(%s)</span>', __('hidden', 'participants-database'));
            } elseif (in_array($column->name, $readonly_columns) or $column->form_element == 'timestamp') {
              $attributes['class'] = 'readonly-field';
              if (!current_user_can(Participants_Db::$plugin_options['record_edit_capability'])) {
              $attributes['readonly'] = 'readonly';
            }
              $add_title = sprintf(' <span class="fieldnote">(%s)</span>', __('read only', 'participants-database'));
            }
            ?>
            <th><?php echo $column_title . $add_title ?></th>
            <td id="<?php echo Participants_Db::$prefix . $column->name ?>" >
              <?php
              

              /*
               * get the value from the record; if it is empty, use the default value if the 
               * "persistent" flag is set.
               */
              $column->value = empty($participant_values[$column->name]) ? ($column->persistent == '1' ? $column->default : '') : Participants_Db::unserialize_array($participant_values[$column->name]);
              
              // get the existing value if any
              //$column->value = isset($participant_values[$column->name]) ? Participants_Db::unserialize_array($participant_values[$column->name]) : '';

              // replace it with the new value if provided
              if (isset($_POST[$column->name])) {

                if (is_array($_POST[$column->name]))
                  $column->value = $_POST[$column->name];

                elseif ('rich-text' == $column->form_element)
                  $column->value = $_POST[$column->name];
                else
                  $column->value = esc_html(stripslashes($_POST[$column->name]));
              }

              $field_class = ( $column->validation != 'no' ? "required-field" : '' ) . ( in_array($column->form_element, array('text-line', 'date')) ? ' regular-text' : '' );

              if (isset($column->value)) {

                //error_log(basename(__FILE__) . ' ' . $column->name . ':' . $column->value);

//                if ($column->name == 'last_accessed' && (!isset($column->value) or '0000-00-00 00:00:00' == $column->value ))
//                  $column->value = false;

                switch ($column->form_element) {
                  
//                  case 'timestamp':
                  case 'date':

                    /*
                     * if it's not a timestamp, format it for display; if it is a
                     * timestamp, it will be formatted by the xnau_FormElement class
                     */
                    if (!empty($column->value)) {
                      //$column->value = xnau_FormElement::get_field_value_display($column);
                      $column->value = Participants_Db::parse_date($column->value);
                    }

                    break;

                  case 'image-upload':

                    $column->value = empty($column->value) ? '' : $column->value;

                    break;

                  case 'multi-select-other':
                  case 'multi-checkbox':

                    $column->value = is_array($column->value) ? $column->value : explode(',', $column->value);

                    break;

                  case 'password':

                    $column->value = '';
                    break;

                  case 'hidden':

                    $column->form_element = 'text-line';
                    break;
                  
                  case 'timestamp':
                    
                    if (Participants_Db::import_timestamp($column->value) === false) $column->value = '';
                    break;
                }
              }

              if ('rich-text' == $column->form_element) {

                wp_editor(
                        $column->value, 
                        preg_replace('#[0-9_-]#', '', Participants_Db::$prefix . $column->name), 
                        array(
                    'media_buttons' => false,
                    'textarea_name' => $column->name,
                    'editor_class' => $field_class,
                        )
                );
              } else {

                $params = array(
                            'type' => $column->form_element,
                            'value' => $column->value,
                            'name' => $column->name,
                            'options' => $column->values,
                            'class' => $field_class,
                            'attributes' => $attributes,
                            'module' => 'admin-edit',
                );
                PDb_FormElement::print_element($params);
              }

              if (!empty($column->help_text)) :
                ?>
                <span class="helptext"><?php echo stripslashes(trim($column->help_text)) ?></span>
    <?php endif; ?>
            </td>
          </tr>
          <?php
        endforeach;
        ?>
      </table>
     <h3 class="field-group-title"><?php _e('Save the Record', 'participants-database') ?></h3>
      <table class="form-table">
  <?php if (is_admin()) : ?>
          <tr>
          <td colspan="2" class="submit-buttons">
            <?php if (isset($_GET['id'])) : ?><input class="button button-default button-leftarrow" type="submit" value="<?php echo self::$i18n['previous'] ?>" name="submit_button"><?php endif ?>
            <input class="button button-primary" type="submit" value="<?php echo self::$i18n['submit'] ?>" name="submit_button">
            <input class="button button-primary" type="submit" value="<?php echo self::$i18n['apply'] ?>" name="submit_button">
            <input class="button button-default button-rightarrow" type="submit" value="<?php echo self::$i18n['next'] ?>" name="submit_button">
          </td>
          </tr>
          <tr>
            <td colspan="2">
              <?php _e('<strong>Submit:</strong> save record and return to list<br><strong>Apply:</strong> save record and continue with same record<br><strong>Next:</strong> save record and then start a new one', 'participants-database') ?>
              <br />
              <?php if (isset($_GET['id'])) {_e('<strong>Previous:</strong> save and move to previous record', 'participants-database'); } ?>
            </td>
          </tr>
  <?php else : ?>
          <tr>
            <th><h3><?php echo $options['save_changes_label'] ?></h3></th>
          <td class="submit-buttons">
            <input class="button button-primary pdb-submit" type="submit" value="<?php echo $options['save_changes_button'] ?>" name="save">
            <input name="submit_button" type="hidden" value="<?php echo self::$i18n['apply'] ?>">
          </td>
          </tr>
  <?php endif; ?>
      </table>
    </form>
  </div>
<?php endif; // ID is valid  ?>
<?php /* ?>
  <script type="text/javascript">
  jQuery(document).ready( function($) {
  $.datepicker.setDefaults({
  dateFormat : '<?php echo Participants_Db::get_jqueryUI_date_format() ?>'
  });
  $( ".edit-participant input.date_field" ).each( function() {
  var datefield = $(this);
  var fieldname = datefield.attr('name');
  datefield.datepicker({
  changeMonth: true,
  changeYear: true
  });
  });
  });
  </script>
  <?php */ ?>