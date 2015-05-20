<?php
if (!defined( 'ABSPATH' ) ) exit;
if (!Participants_Db::current_user_has_plugin_role('admin', 'upload csv')) exit;

$CSV_import = new PDb_CSV_Import('csv_file_upload');
$csv_paramdefaults = array(
      'delimiter_character' => 'auto',
      'enclosure_character' => 'auto',
      'match_field' => Participants_Db::plugin_setting('unique_field'),
      'match_preference' => Participants_Db::plugin_setting('unique_email')
      );
$csv_options = get_option(Participants_Db::$prefix . 'csv_import_params');
if ($csv_options === false) {
  $csv_params = $csv_paramdefaults;
} else {
  $csv_params = array_merge($csv_paramdefaults, $csv_options);
}
foreach (array_keys($csv_paramdefaults) as $param) {
  $new_value = '';
  if (isset($_POST[$param])) {
    switch ($param) {
      case 'enclosure_character':
        $new_value = str_replace(array('"', "'"), array('&quot;', '&#39;'), filter_input(INPUT_POST, 'enclosure_character', FILTER_SANITIZE_STRING));
        break;
      default:
        $new_value = filter_input(INPUT_POST, $param, FILTER_SANITIZE_STRING);
    }
    $csv_params[$param] = $new_value;
	}
}
extract($csv_params);
update_option(Participants_Db::$prefix . 'csv_import_params', $csv_params);
?>
<div class="wrap <?php echo Participants_Db::$prefix ?>csv-upload">
  <?php Participants_Db::admin_page_heading() ?>
  <div id="poststuff">
    <div id="post-body">
      <h2><?php echo __('Import CSV File', 'participants-database') ?></h2>

      <?php
      if (!empty($CSV_import->errors)):
        ?>

        <div class="<?php echo $CSV_import->error_status ?> fade below-h2" id="message">
          <p><?php echo implode('</p><p>', $CSV_import->errors) ?></p>
        </div>

        <?php
      endif;
      ?>

      <form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
        <input type="hidden" name="filename" value="blank_record.csv" />
        <input type="hidden" name="subsource" value="<?php echo Participants_Db::PLUGIN_NAME ?>">
        <input type="hidden" name="action" value="output CSV" />
        <input type="hidden" name="CSV type" value="blank" />
        <div class="postbox">
          <h3><?php _e('Prepare a spreadsheet file with the correct format:', 'participants-database') ?></h3>
          <div class="inside">
            <p><?php _e('To properly import your membership data, the columns in your spreadsheet must match exactly the columns in the database. Currently, the CSV export columns are as follows:', 'participants-database') ?></p>
            <table class="spreadsheet">
              <tr>
                <?php
                foreach ($CSV_import->column_names as $name) {
                  echo '<th>' . $name . '</th>';
                }
                ?>
              </tr>
              <tr>
                <?php
                echo str_repeat('<td>&nbsp;</td>', $CSV_import->column_count);
                ?>
              </tr>
            </table>
            <p><?php printf(__('This means your spreadsheet needs to have %s columns, and the heading in each of those columns needs to match exactly the names above. If there is no data for a particular column, you can include it and leave it blank, or leave it out entirely. The order of the columns doesn&#39;t matter.', 'participants-database'), $CSV_import->column_count) ?></p>
            <p><?php _e('If the imported CSV file has a different column set, that column set will be imported and used. If a column name does not match a defined column in the database, the data from that column will be discarded', 'participants-database') ?></p>
            <p><input class="button button-default" type="submit" value="<?php _e('Get Blank CSV File', 'participants-database') ?>" style="float:left;margin:0 5px 5px 0" /><?php _e('You can download this file, then open it in Open Office, Excel or Google Docs.', 'participants-database') ?></p>
          </div>
        </div>
      </form>
      <div class="postbox">
        <h3><?php _e('Export the .csv file', 'participants-database') ?></h3>
        <div class="inside">
          <p><?php _e('When you have your spreadsheet properly set up and filled with data, export it as any of the following: "comma-delimited csv", or just "csv". Save it to your computer then upload it here.', 'participants-database') ?></p>
          <h4><?php _e('Exported CSV files should be comma-delimited and enclosed with double-quotes ("). Encoding should be "UTF-8."', 'participants-database') ?></h4>
        </div>
      </div>
      <div class="postbox">
        <h3><?php _e('Upload the .csv file', 'participants-database') ?></h3>
        <div class="inside">
          <form enctype="multipart/form-data" action="<?php echo $_SERVER["REQUEST_URI"]; ?>" method="POST">
            <input type="hidden" name="csv_file_upload" id="file_upload" value="true" />
            <fieldset class="widefat inline-controls">
              <p>
            <label style="margin-left:0">
            <?php _e('Enclosure character', 'participants-database');
            $parameters = array(
                'type' => 'dropdown',
                'name' => 'enclosure_character',
                'value' => $enclosure_character,
                'options' => array(
                    __('Auto',  'participants-database') => 'auto',
                    '&quot;' => '&quot;',
                    "&#39;" => "&#39;"
                )
            );
            PDb_FormElement::print_element($parameters);
            ?>
            </label>
            <label>
              <?php _e('Delimiter character', 'participants-database');
            $parameters = array(
                'type' => 'dropdown',
                'name' => 'delimiter_character',
                'value' => $delimiter_character,
                'options' => array(
                    __('Auto', 'participants-database') => 'auto',
                    ',' => ',',
                    ';' => ';',
                    __('tab', 'participants-database') => "\t"
                )
            );
            PDb_FormElement::print_element($parameters);
             ?>
            </label>
              </p>
            </fieldset>

            <fieldset class="widefat inline-controls">
              <p>
                <label style="margin-left:0">
                 <?php  echo __('Duplicate Record Preference', 'participants-database') . ': ';
                 $parameters = array(
                     'type' => 'dropdown',
                     'name' => 'match_preference',
                     'value' => $match_preference,
                     'options' => array(
                        __('Create a new record with the submission', 'participants-database') => 0,
                        __('Overwrite matching record with new data', 'participants-database') => 1,
                        __('Show a validation error message', 'participants-database') => 2,
                        'null_select' => false,
                      )
                 );
                 PDb_FormElement::print_element($parameters);
                 ?>
                </label>
                <label>
              <?php echo __('Duplicate Record Check Field', 'participants-database') . ': ';
            $parameters = array(
                'type' => 'dropdown',
                'name' => 'match_field',
                'value' => $match_field,
                'options' => array_merge(PDb_Settings::_get_identifier_columns(), array('Record ID' => 'id')),
            );
            PDb_FormElement::print_element($parameters);
             ?>
            </label>
              </p>
            </fieldset>
          <p><?php _e('<strong>Note:</strong> Depending on the "Duplicate Record Preference" setting, imported records are checked against existing records by the field set in the "Duplicate Record Check Field" setting. If a record matching an existing record is imported, one of three things can happen, based on the "Duplicate Record Preference" setting:', 'participants-database') ?></p>
          <h4 class="inset" id="match-preferences"><?php _e('Current Setting', 'participants-database') ?>: 
            <?php
            $preferences = array(
                '0' => sprintf(__('%sCreate New%s adds all imported records as new records without checking for a match.', 'participants-database'), '<span class="emphasized">', '</span>', '</span>'),
                '1' => sprintf(__('%sOverwrite%s an existing record with a matching %s will be updated with the data from the imported record. Blank or missing fields will not overwrite existing data.', 'participants-database'), '<span class="emphasized">', '</span>', '<em class="match-field">' . Participants_Db::$fields[$match_field]->title . '</em>'),
                '2' => sprintf(__('%sDon&#39;t Import%s does not import the new record if it matches the %s of an existing one.', 'participants-database'), '<span class="emphasized">', '</span>', '<em class="match-field">' . Participants_Db::$fields[$match_field]->title . '</em>'),
            );
            foreach($preferences as $i => $preference) {
              $hide = $i == $match_preference ? '' : 'style="display:none"';
              printf('<span class="preference" %s data-index="%s" >%s</span>', $hide, $i, $preference);
            }
            ?></h4>


            <?php _e('Choose .csv file to import:', 'participants-database') ?> <input name="uploadedfile" type="file" /><br />
            <input type="submit" class="button button-primary" value="<?php _e('Upload File', 'participants-database') ?>" />
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<script type="text/javascript">
  UploadCSV = (function($) {
    var 
          prefs,
          matchfield,
          set_visible_pref = function (i) {
            hide_prefs();
            show_pref(i);
          },
          hide_prefs = function() {
            prefs.find('.preference').hide();
          },
          show_pref = function (i) {
            prefs.find('.preference[data-index=' + i + ']').show();
          },
          set_pref = function () {
            set_visible_pref($(this).val());
          },
          set_match_field_text = function (f) {
            matchfield.text(f);
          },
          set_match_field = function () {
            set_match_field_text($(this).find('option:selected').text());
          };
    return {
      run: function () {
        prefs = $('#match-preferences');
        matchfield = prefs.find('.match-field');
        $('#match_preference_select').change(set_pref);
        $('#match_field_select').change(set_match_field);
      }
    }
  }(jQuery));
jQuery(function() {
  "use strict";
  UploadCSV.run();
});
</script>