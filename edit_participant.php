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
  <div class="wrap edit-participant">
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
    FormElement::print_hidden_fields($hidden);

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
          <h3><?php echo $groups[$section]['title'] ?></h3>
          <?php if ($options['show_group_descriptions']) echo '<p class="' . Participants_Db::$css_prefix . 'group-description">' . $groups[$section]['description'] . '</p>' ?>
          <table class="form-table">
          <?php
        }
        echo $id_line;
        ?>

          <tr class="<?php echo ( 'hidden' == $column->form_element ? 'text-line' : $column->form_element ) ?>">
          <?php
          $column_title = htmlspecialchars(stripslashes($column->title), ENT_QUOTES, "UTF-8", false);
          if ($options['mark_required_fields'] && $column->validation != 'no') {
            $column_title = sprintf($options['required_field_marker'], $column_title);
          }
          ?>
            <th><?php echo $column_title . ( ( 'hidden' == $column->form_element ) ? ' (hidden)' : '' ) ?></th>
            <td id="<?php echo Participants_Db::$css_prefix . $column->name ?>" >
            <?php
            if( in_array($column->name, $readonly_columns) ) $attributes['readonly'] = 'readonly';

            // get the existing value if any
            $value = isset($participant_values[$column->name]) ? Participants_Db::unserialize_array($participant_values[$column->name]) : '';

            // replace it with the new value if provided
            if (isset($_POST[$column->name])) {

              if (is_array($_POST[$column->name]))
                $value = $_POST[$column->name];

              elseif ('rich-text' == $column->form_element)
                $value = $_POST[$column->name];

              else
                $value = esc_html(stripslashes($_POST[$column->name]));
            }

            $field_class = ( $column->validation != 'no' ? "required-field" : '' ) . ( in_array($column->form_element, array('text-line', 'date')) ? ' regular-text' : '' );

            if (isset($value)) {

              //error_log(__METHOD__ . ' ' . $column->name . ':' . $value);

              if ($column->name == 'last_accessed' && (!isset($value) or '0000-00-00 00:00:00' == $value ))
                $value = false;

              switch ($column->form_element) {
								
                case 'date':

                  /*
									 * if it's not a timestamp, format it for display; if it is a
									 * timestamp, it will be formatted by the FormElement class
									 */
									if (!empty($value) and ! Participants_Db::is_valid_timestamp($value)) {
                    $value = Participants_Db::prep_field_for_display($value,$column->form_element);
                  }

                  break;

                case 'image-upload':

                  $value = empty($value) ? '' : $value;

                  break;

                case 'multi-select-other':
                case 'multi-checkbox':

                  $value = is_array($value) ? $value : explode(',', $value);

                  break;

                case 'password':

                  $value = '';
                  break;

                case 'hidden':

                  $column->form_element = 'text-line';
                  break;
              }
            }

            if ('rich-text' == $column->form_element) {

              wp_editor(
                      $value, preg_replace('#[0-9_-]#', '', Participants_Db::$css_prefix . $column->name), array(
                  'media_buttons' => false,
                  'textarea_name' => $column->name,
                  'editor_class' => $field_class,
                      )
              );
            } else {

              FormElement::print_element(
                      array(
                          'type' => $column->form_element,
                          'value' => $value,
                          'name' => $column->name,
                          'options' => $column->values,
                          'class' => $field_class,
                          'attributes' => $attributes,
                      )
              );
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
      <table class="form-table">
            <?php if (is_admin()) : ?>
          <tr>
            <th><h3><?php _e('Save the Record', 'participants-database') ?></h3></th>
          <td class="submit-buttons"><input class="button-primary" type="submit" value="<?php echo self::$i18n['submit'] ?>" name="submit">
            <input class="button-primary" type="submit" value="<?php echo self::$i18n['apply'] ?>" name="submit">
            <input class="button-primary" type="submit" value="<?php echo self::$i18n['next'] ?>" name="submit">
          </td>
          </tr>
          <tr>
            <td colspan="2"><?php _e('<strong>Submit:</strong> save record and return to list<br><strong>Apply:</strong> save record and continue with same record<br><strong>Next:</strong> save record and then start a new one', 'participants-database') ?> </td>
          </tr>
        <?php else : ?>
          <tr>
            <th><h3><?php echo $options['save_changes_label'] ?></h3></th>
          <td class="submit-buttons">
            <input class="button-primary pdb-submit" type="submit" value="<?php echo $options['save_changes_button'] ?>" name="save">
            <input name="submit" type="hidden" value="<?php echo self::$i18n['apply'] ?>">
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