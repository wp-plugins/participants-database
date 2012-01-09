<?php
// prepare a display string of the main columns
$column_names = array();

// hold the status of UI messages
$status = 'updated';

foreach ( Participants_Db::get_column_atts() as $column ) {

	if ( $column->import ) $column_names[] = $column->name;

}

$column_count = count( $column_names );

$blank_record = array_fill_keys( $column_names, '' );


$errors = array();
			
// if a file upload attempt been made, process it and display the status of the operation
if( isset( $_POST['file_upload'] ) ) :

	$target_path = Participants_Db::$plugin_path . '/uploads/' . basename( $_FILES['uploadedfile']['name']);

	if( false !== move_uploaded_file( $_FILES['uploadedfile']['tmp_name'], $target_path ) ) {

		$errors[] = '<strong>'.sprintf( __('The file %s has been uploaded.', Participants_Db::PLUGIN_NAME ), $_FILES['uploadedfile']['name'] ).'</strong>';
		
		$file_name = Participants_Db::$uploads_path.basename( $_FILES['uploadedfile']['name']);
		
		$insert_error = Participants_Db::insert_from_csv( $file_name );

		if ( is_numeric( $insert_error ) ) {

			$errors[] = '<strong>'.$insert_error.' '._n('record imported','records imported', $insert_error, Participants_Db::PLUGIN_NAME ).'.</strong>';

		} elseif( empty( $insert_error ) ) {

			$errors[] = __('Zero records imported.', Participants_Db::PLUGIN_NAME );
			$status = 'error';

		} else { // parse error
		
			$errors[] = '<strong>'.__('Error occured while trying to add the data to the database', Participants_Db::PLUGIN_NAME ).':</strong>';
			$errors[] = $insert_error;
			$status = 'error';

		}
	} // file move successful
	else { // file move failed

			$errors[] = '<strong>'.__('There was an error uploading the file.', Participants_Db::PLUGIN_NAME ).'</strong>';
			$errors[] = __('Destination', Participants_Db::PLUGIN_NAME ).': '.$target_path;
			$status = 'error';

	}

endif; // isset( $_POST['file_upload'] 
?>
<div class="wrap">
	<div id="poststuff">
		<div id="post-body">
			<h2><?php echo Participants_Db::$plugin_title.' '.__('Import CSV File', Participants_Db::PLUGIN_NAME )?></h2>
			
			<?php
			if ( ! empty( $errors ) ): 
			?>
			
			<div class="<?php echo $status?> fade below-h2" id="message">
				<p><?php echo implode( '</p><p>', $errors )?></p>
			</div>
			
			<?php
			endif;
			?>

			<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
			<input type="hidden" name="filename" value="blank_record" />
			<input type="hidden" name="source" value="<?php echo Participants_Db::PLUGIN_NAME ?>">
			<input type="hidden" name="action" value="output CSV" />
			<input type="hidden" name="CSV type" value="blank" />
				<div class="postbox">
					<h3><?php _e('1. Prepare a spreadsheet file with the correct format:', Participants_Db::PLUGIN_NAME )?></h3>
					<div class="inside">
						<p><?php _e('To properly import your membership data, the columns in your spreadsheet must match exactly the columns in the database. Currently, the columns are as follows:', Participants_Db::PLUGIN_NAME )?></p>
						<table class="spreadsheet">
							<tr>
							<?php
							foreach ( $column_names as $name ) {
								echo '<th>'.$name.'</th>';
							}
							?>
							</tr>
							<tr>
								<?php
								echo str_repeat( '<td>&nbsp;</td>', $column_count );
								?>
							</tr>
						</table>
						<p><?php printf( __('This means your spreadsheet needs to have %s columns, and the heading in each of those columns needs to match exactly the names above. If there is no data for a particular column, leave it blank, but the header and column must be included in the CSV. The order of the columns doesn&#39;t matter.', Participants_Db::PLUGIN_NAME ),$column_count)?></p>
						<p><?php _e( '<strong>Note:</strong> Imported records are checked against existing records by email. If a record with an email matching an existing record is imported, the existing record will be updated with the data from the imported record. Blank or missing fields in such an imported record will not overwrite existing data.', Participants_Db::PLUGIN_NAME )?></p>
						<p><input type="submit" value="<?php _e('Get Blank CSV File', Participants_Db::PLUGIN_NAME )?>" style="float:left;margin:0 5px 5px 0" /><?php _e( 'You can download this file, then open it in Open Office, Excel or Google Docs.', Participants_Db::PLUGIN_NAME )?></p>
					</div>
				</div>
			</form>

			<div class="postbox">
				<h3><?php _e( '2. Upload the .csv file', Participants_Db::PLUGIN_NAME )?></h3>
				<div class="inside">
						<p><?php _e( 'When you have your spreadsheet properly set up and filled with data, export it as any of the following: "comma-delimited csv", "tab-delimited csv", or just "csv". Save it to your computer then upload it here.', Participants_Db::PLUGIN_NAME )?></p>
					<form enctype="multipart/form-data" action="<?php echo $_SERVER["REQUEST_URI"]; ?>" method="POST">
						<input type="hidden" name="file_upload" id="file_upload" value="true" />
						<input type="hidden" name="MAX_FILE_SIZE" value="100000" />
						<?php _e('Choose .csv file to import:', Participants_Db::PLUGIN_NAME )?> <input name="uploadedfile" type="file" /><br />
						<input type="submit" class="button-primary" value="<?php _e('Upload File', Participants_Db::PLUGIN_NAME )?>" />
					</form>
				</div>
			</div>
		</div>
	</div>
</div>