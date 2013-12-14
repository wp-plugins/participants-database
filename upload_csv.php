<?php
$CSV_import = new PDb_CSV_Import('csv_file_upload');
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
            <p><?php _e('To properly import your membership data, the columns in your spreadsheet must match exactly the columns in the database. Currently, the CSV import columns are as follows:', 'participants-database') ?></p>
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

          <p><?php _e('<strong>Note:</strong> Depending on the "Duplicate Record Preference" setting, imported records are checked against existing records by the field set in the "Duplicate Record Check Field" setting. If a record matching an existing record is imported, one of three things can happen, based on the "Duplicate Record Preference" setting:', 'participants-database') ?></p>
          <h4 class="inset"><?php _e('Current Setting', 'participants-database') ?>: 
            <?php
            switch (Participants_Db::$plugin_options['unique_email']) :
              case 1:
                printf(__('%sOverwrite%s an existing record with a matching %s will be updated with the data from the imported record. Blank or missing fields will not overwrite existing data.', 'participants-database'), '<span class="emphasized">', '</span>', '<em>' . Participants_Db::$plugin_options['unique_field'] . '</em>');
                break;
              case 0 :
                printf(__('%sCreate New%s adds all imported records as new records without checking for a match.', 'participants-database'), '<span class="emphasized">', '</span>', '</span>');
                break;
              case 2 :
                printf(__('%sDon&#39;t Import%s does not import the new record if it matches the %s of an existing one.', 'participants-database'), '<span class="emphasized">', '</span>', '<em>' . Participants_Db::$plugin_options['unique_field'] . '</em>');
                break;
            endswitch
            ?></h4>

          <form enctype="multipart/form-data" action="<?php echo $_SERVER["REQUEST_URI"]; ?>" method="POST">

            <input type="hidden" name="csv_file_upload" id="file_upload" value="true" />

            <?php /* _e( 'Enclosure Character', 'participants-database' ) ?> (&#39; or &quot;) <input name="eclosure" value="'" type="text" /><br /><?php */ ?>
            <?php _e('Choose .csv file to import:', 'participants-database') ?> <input name="uploadedfile" type="file" /><br />
            <input type="submit" class="button button-primary" value="<?php _e('Upload File', 'participants-database') ?>" />
          </form>
        </div>
      </div>
    </div>
  </div>
</div>