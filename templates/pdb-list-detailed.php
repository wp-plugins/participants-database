<?php
/*

template for participants list shortcode output

this is a more detailed template showing how the parts of the display can be customized

*/
?>
<div class="wrap <?php echo $this->wrap_class ?>" id="<?php echo $this->list_anchor ?>">
<?php
  /*
   * SEARCH/SORT FORM
   *
   * the search/sort form is ony presented when enabled in the shortcode.
   * It's also skipped when refreshing the list after a sort or filter operation
   * 
   */
  if ( $filter_mode != 'none' && ! $filtering ) : ?>
  <div class="pdb-searchform">
  
		<?php
		/* this element is where error and feedback messages are shown to the user.
		 * It is only visible if there is a message
     * 
     * don't change the classnames or IDs, they are requted for the error messagees to show up properly
     * do change the strings in the "_e" functions for your message preference
		 */?>
    <div class="pdb-error pdb-search-error" style="display:none">
      <p class="search_field_error"><?php _e( 'Please select a column to search in.', 'participants-database' )?></p>
      <p class="value_error"><?php _e( 'Please type in something to search for.', 'participants-database' )?></p>
    </div>

    <?php
		/* this method prints the top of the search/sort form
		 *
		 * if you want to put the search on a different page, you can specify the
		 * target page in this method, and the search results will be shown on that
		 * page
		 */
		$this->search_sort_form_top(); ?>

    <?php if ( $filter_mode == 'filter' || $filter_mode == 'both' ) : ?>

    <fieldset class="widefat">
      <legend><?php _e('Search', 'participants-database' )?>:</legend>

      <?php
        /*
         * there are 4 options for this function which defines which fields will be 
         * available in the search dropdown selector:
         *    1. "all fields" text: set the text of the default "all fields" item: 
         *       leave it "false" to use the internationalized defualt
         *    2. print it or return the element as a value: only use this if you 
         *       need to alter the HTML directly. You will need to print the item for 
         *       it to be seen. If 'true', the function prints the selector dropdown.
         *    3. columns: supply an array of column names if you want to define the 
         *       list of fields that can be used for searching: 'false' uses all 
         *       displayed fields. If you want to set a default value, you must define 
         *       the array of allowed fields and use your default field as the first 
         *       element in the array.
         *    4. sorting: you can choose to sort the list by 'column' (the order they 
         *       appear in the table), 'alpha' (alphabetical order), or 'order' which 
         *       uses the defined group/field order
         *    5. multi-field mode: if true the value is submitted as an array element
         */
        $this->column_selector( false, true, false, 'column', false );
      ?>
			<input name="operator" type="hidden" class="search-item" value="LIKE" />
			<?php
			/*
			 * the following text input is where the user types in the search term
			 * if you want to add a placeholder value, add it as a placeholder attribute to the tag
       * 
       * you can also replace this with a dropdown or other type of input
			 */
			 ?>
			<input id="participant_search_term" type="text" name="value" class="search-item" value="<?php echo $this->list_query->current_filter('search_term') ?>">
      <?php 
      /*
       * this method places the submit buttons
       * 
       * you can optionally set the button text for each one by supplying them 
       * in a config array...for example:
       * $values = array( 'submit' => 'Search Records', 'clear' => 'Clear the Search Parameters' );
       */
      $this->print_search_submit_buttons(); ?>
      
    </fieldset>
    <?php endif ?>
    <?php if ( $filter_mode == 'sort' || $filter_mode == 'both' ) : ?>
    
    <fieldset class="widefat">
      <legend><?php _e('Sort by', 'participants-database' )?>:</legend>
      
      <?php
      /*
       * this function sets the fields in the sorting dropdown. It has two options:
       *    1. columns: an array of field names to show in the sorting dropdown. If 
       *       'false' shows default list of sortable fields as defined
       *    2. sorting: you can choose to sort the list by 'column' (the order they 
       *       appear in the table), 'alpha' (alphabetical order), or 'order' which 
       *       uses the defined group/field order
       */
      $this->set_sortables(false, 'column');
      ?>

      <?php $this->sort_form() ?>

    </fieldset>
    <?php endif ?>
  </form>
  </div>
  <?php endif ?>

<?php
  /* END SEARCH/SORT FORM */

  /* LIST DISPLAY */
  /* 
   * NOTE: the container for the list itself (excluding search and pagination 
   * controls) must have a class of "list-container" for AJAX search/sort to 
   * function
   */
?>

  <table class="wp-list-table widefat fixed pages list-container" >
  
    <?php 
    /* print the count if enabled in the shortcode
     * 
     * the tag wrapping the count statment can be supplied in the function argument, example here
     */
		$this->print_list_count('<caption class="list-display-count">'); 
    ?>

    <?php if ( $record_count > 0 ) : ?>

    <thead>
      <tr>
        <?php /*
         * this function prints headers for all the fields
         * replacement codes:
         * %2$s is the form element type identifier
         * %1$s is the title of the field
         */
        $this->print_header_row( '<th class="%2$s" scope="col">%1$s</th>' );
        ?>
      </tr>
    </thead>
    <?php // print the table footer row if there is a long list
      if ( $records_per_page > 30 ) : ?>
    <tfoot>
      <tr>
        <?php $this->print_header_row( '<th class="%2$s" scope="col">%1$s</th>' ) ?>
      </tr>
    </tfoot>
    <?php endif ?>

    <tbody>
    <?php while ( $this->have_records() ) : $this->the_record(); // each record is one row ?>
      <tr>
        <?php while( $this->have_fields() ) : $this->the_field(); // each field is one cell ?>

          <td>
          	<?php echo PDb_FormElement::get_field_value_display($this->field); ?>
            </td>
        
			<?php endwhile; // fields ?>
      </tr>
    <?php endwhile; // records ?>
    </tbody>
    
    <?php else : // if there are no records ?>

    <tbody>
      <tr>
          <td><?php if ($this->is_search_result === true)  echo Participants_Db::$plugin_options['no_records_message'] ?></td>
      </tr>
    </tbody>

    <?php endif; // $record_count > 0 ?>
    
	</table>
  <?php
  /* END LIST */
  
  /* PAGINATION
   * 
   * this is how you customize the pagination controls
   *
   * the properties array can have these values (omit to use default value):
	 *                'current_page_class' the class name for the current page link
	 *                'disabled_class'     the class to apply to disabled links
	 *                'anchor_wrap'        whether to wrap the disabled link in an 'a' tag
	 *                'first_last'         whether to show the first and last page links
	 *                'wrappers'           array of values with the HTML to wrap the links in:
	 *                                            'wrap_tag'            tag name of the outside wrapper
	 *                                            'wrap_class'          class of the outside wrapper
	 *                                            'all_button_wrap_tag' the tag name to wrap the buttons
	 *                                            'button_wrap_tag'     the tag name to use for each button
	 *
	 *                                            
   */
	$this->pagination->set_props(array(
																		 'first_last' => false,
																		 'current_page_class'=>'currentpage',
																		 'wrappers' => array(
																												'wrap_tag' => 'div',
																												'wrap_class' => 'pagination',
																												'all_button_wrap_tag' => 'ul',
																												'button_wrap_tag' => 'li',
																												)
																		 ));
	$this->pagination->show();?>
</div>