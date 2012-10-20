<?php
/*

template for participants list shortcode output

this is a more detailed template showing how the parts of the display can be customized

*/

  /* 
   * this adds the default plugin stylesheet
   * you can specify your own here, just put in the filename of your 
   * stylesheet (located in your theme directory) as the argument
   */
  self::add_stylesheet();

  /*
   * SEARCH/SORT FORM
   *
   * the search/sort form is ony presented when enabled in the shortcode.
   * It's also skipped when refreshing the list after a sort or filter operation
   * 
   */
  if ( $filter_mode != 'none' && ! $filtering ) : ?>
  <div class="pdb-searchform">
  
    <div class="pdb-error pdb-search-error" style="display:none">
      <p id="where_clause_error"><?php _e( 'Please select a column to search in.', Participants_Db::PLUGIN_NAME )?></p>
      <p id="value_error"><?php _e( 'Please type in something to search for.', Participants_Db::PLUGIN_NAME )?></p>
    </div>

    <?php self::search_sort_form_top(); ?>

    <?php if ( $filter_mode == 'filter' || $filter_mode == 'both' ) : ?>

    <fieldset class="widefat">
      <legend><?php _e('Search', Participants_Db::PLUGIN_NAME )?>:</legend>

      <?php
        // you can replace "false" with your own text for the "all columns" value
        self::column_selector( false );
      ?>

      <?php self::search_form() ?>
      
    </fieldset>
    <?php endif ?>
    <?php if ( $filter_mode == 'sort' || $filter_mode == 'both' ) : ?>
    
    <fieldset class="widefat">
      <legend><?php _e('Sort by', Participants_Db::PLUGIN_NAME )?>:</legend>

      <?php self::sort_form() ?>

    </fieldset>
    <?php endif ?>

  </div>
  <?php endif ?>

<?php
  /* END SEARCH/SORT FORM */

  /* LIST DISPLAY */
?>

  <table class="wp-list-table widefat fixed pages pdb-list" cellspacing="0" >
  
    <?php // print the count if enabled in the shortcode
		if ( $display_count ) : ?>
    <caption>
      Total Records Found: <?php echo $record_count ?>
    </caption>
    <?php endif ?>

    <?php if ( $record_count > 0 ) : ?>

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
    <?php // print the table footer row if there is a long list
      if ( $records_per_page > 20 ) : ?>
    <tfoot>
      <tr>
        <?php self::_print_header_row( '<th class="%2$s" scope="col">%1$s</th>' ) ?>
      </tr>
    </tfoot>
    <?php endif ?>

    <tbody>
    <?php foreach ( $records as $record ) : ?>
      <tr>
        <?php foreach ( $fields as $field ) : ?>

          <?php $value = $record[$field];
          if ( ! empty( $value ) ) : ?>
          <td>
          	<?php 
						// wrap the item in a link if it's enabled for this field
						if ( self::is_single_record_link( $field ) ) {
              echo Participants_Db::make_link(
                $single_record_link,             // URL of the single record page
                $value,                          // field value
                '<a href="%1$s" title="%2$s" >', // template for building the link
                array( 'pdb'=>$record['id'] )    // record ID to get the record
              );
            } ?>

            <?php /*
            * here is where we determine how each field value is presented,
            * depending on what kind of field it is
            */
            switch ( self::get_field_type( $field ) ) :

							case 'image-upload': ?>

                <img class="PDb-list-image" src="<?php echo Participants_Db::get_image_uri( $value )?>" />
             
                <?php break;
								
							case 'date':
							
								/*
								 * if you want to specify a format, include it as the second 
								 * argument in this function; otherwise, it will default to 
								 * the site setting
								 */
								self::show_date( $value, false );
								
								break;
								
						case 'multi-select-other':
						case 'multi-checkbox':
						
							/*
							 * this function shows the values as a comma separated list
							 * you can customize the glue that joins the array elements
							 */
							self::show_array( $value, $glue = ', ' );
							
							break;
							
						case 'link' :
							
							/*
							 * prints a link (anchor tag with link text)
							 * for the template:
							 * %1$s is the URL
							 * %2$s is the linked text
							 */
							self::show_link( $value, $template = '<a href="%1$s" >%2$s</a>' );
							
							break;
							
						case 'textarea':
							
							/*
							 * if you are displaying rich text you may want to process the 
							 * output through wpautop like this: echo wpautop( $value ) see 
							 * http://codex.wordpress.org/Function_Reference/wpautop
							 */
              ?>
              <span class="textarea"><?php echo $value ?></span>
              <?php
							
              break;
							
						case 'text-line':
						default:
						
							/*
							 * if the make links setting is enabled, try to make a link out of the field
							 */
							if ( self::$options['make_links'] && ! self::is_single_record_link( $field ) ) {
								
								echo Participants_Db::make_link( $value );
								
							} else {
								
								echo esc_html( $value );
								
							}

            endswitch; // switch by field type ?>
            <?php // close the anchor tag if it's a link 
						if ( self::is_single_record_link( $field ) ) : ?>
            	</a>
            <?php endif ?>
            </td>
            
        <?php else : // if the field is empty ?>
        <td></td>
        <?php endif ?>
        
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
  /* END LIST */
  
  /* PAGINATION
   *
   * pagination controls are not shown when the list is updated by a filter operation
   *
   */
  self::$pagination->show(); ?>