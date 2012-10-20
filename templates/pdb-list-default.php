<?php
/*
 *
 * template for participants list shortcode output
 *
 * this is the default template which formats the list of records as a table
 * using shortcut functions to display the componenets
 *
 * If you need more control over the display, look at the detailed template
 * (pdb-list-detailed.php) for an example of how this can be done
 *
*/
/* 
 * this adds the default plugin stylesheet
 * you can specify your own here, just put in the filename of your 
 * stylesheet (located in your theme directory) as the argument
 */
// include the plugin stylesheet
self::add_stylesheet();
?>


<?php
  /*
   * SEARCH/SORT FORM
   *
   * the search/sort form is only presented when enabled in the shortcode.
   * 
   */
  self::show_search_sort_form()

  /* LIST DISPLAY */
?>

  <table class="wp-list-table widefat fixed pages pdb-list" cellspacing="0" >
  
    <?php // print the count if enabled in the shortcode
		if ( $display_count ) : ?>
    <caption>
      Total Records Found: <?php echo $record_count ?>
    </caption>
    <?php endif ?>

    <?php if ( $record_count > 0 ) : // print only if there are records to show ?>

      <thead>
        <tr>
          <?php /*
           * this function prints headers for all the fields
           * replacement codes:
           * %2$s is the form element type identifier
           * %1$s is the title of the field
           */
          self::_print_header_row( '<th class="%2$s" scope="col">%1$s</th>' );
          ?>
        </tr>
      </thead>
  
      <tbody>
      <?php foreach ( $records as $record ) : // each record is one row ?>
        <tr>
          <?php foreach ( $fields as $field ) : // each field is one cell ?>
  
            <td>
              <?php self::display_field( $record, $field ); ?>
            </td>
          
        <?php endforeach; // each field ?>
        </tr>
      <?php endforeach; // each record ?>
      </tbody>
    
    <?php else : // if there are no records ?>

      <tbody>
        <tr>
          <td><?php _e('No records found', Participants_Db::PLUGIN_NAME )?></td>
        </tr>
      </tbody>

    <?php endif; // $record_count > 0 ?>
    
	</table>
  <?php
  /* PAGINATION
   *
   * pagination controls are not shown when the list is updated by a filter operation
   *
   */
  self::$pagination->show();
  ?>