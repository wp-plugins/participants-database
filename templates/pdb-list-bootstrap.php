<?php
/*

template for participants list shortcode output

this is the default template which formats the list of records as a table

*/
/* 
 * this adds the default plugin stylesheet
 * you can specify your own here, just put in the filename of your 
 * stylesheet (located in your theme directory) as the argument
 */
//$this->add_stylesheet();
?>
<div class="wrap <?php echo $this->wrap_class ?>">
<a name="<?php echo $this->list_anchor ?>" id="<?php echo $this->list_anchor ?>"></a>
<?php /* SEARCH/SORT FORM */ ?>
  <?php if ( $filter_mode != 'none' ) : ?>
  <div class="pdb-searchform">
  
    <div class="alert alert-block" style="display:none">
    	<a class="close" data-dismiss="alert" href="#">X</a>
      <p class="search_field_error"><?php _e( 'Please select a column to search in.', Participants_Db::PLUGIN_NAME )?></p>
      <p class="value_error"><?php _e( 'Please type in something to search for.', Participants_Db::PLUGIN_NAME )?></p>
    </div>

    <?php $this->search_sort_form_top( false, 'form-horizontal' ); ?>

    <?php if ( $filter_mode == 'filter' || $filter_mode == 'both' ) : ?>

    
			
      <div class="control-group">
      	<label class="control-label"><?php _e('Search', Participants_Db::PLUGIN_NAME )?>:</label>
      	<div class="controls">
        
				<?php
          // you can replace "false" with your own text for the "all columns" value
          $this->column_selector( false );
        ?>
			
      		<?php $this->search_form() ?>
        </div>
        
      </div>
    <?php endif ?>
    <?php if ( $filter_mode == 'sort' || $filter_mode == 'both' ) : ?>
      
			
      <div class="control-group">
      	<label class="control-label"><?php _e('Sort by', Participants_Db::PLUGIN_NAME )?>:</label>
      	<div class="controls">

      		<?php $this->sort_form() ?>
          
        </div>
      </div>
    <?php endif ?>

  </div>
  <?php endif ?>

<?php /* LIST DISPLAY */?>

<?php 
/* print the count if enabled in the shortcode
 * 
 * the tag wrapping the count statment can be supplied in the function argument, example here
 */
$this->print_list_count('<h5>'); 
?>


  <table class="table pdb-list list-container" >
  
    
    <?php if ( $record_count > 0 ) : ?>

    <thead>
      <tr>
        <?php /*
         * this function prints headers for all the fields
         * replacement codes:
         * %2$s is the form element type identifier
         * %1$s is the title of the field
         */
        $this->print_header_row( '<th class="%2$s" >%1$s</th>' );
        ?>
      </tr>
    </thead>
    <?php // print the table footer row if there is a long list
      if ( $records_per_page > 30 ) : ?>
    <tfoot>
      <tr>
        <?php $this->print_header_row( '<th class="%2$s">%1$s</th>' ) ?>
      </tr>
    </tfoot>
    <?php endif ?>

    <tbody>
    <?php while ( $this->have_records() ) : $this->the_record(); // each record is one row ?>
      <tr>
        <?php while( $this->have_fields() ) : $this->the_field(); // each field is one cell ?>

          <?php $value = $this->field->value;
          if ( ! $this->field->is_empty( $value ) ) : ?>
          <td>
          	<?php 
						// wrap the item in a link if it's enabled for this field
						if ( $this->field->is_single_record_link() ) {
              echo Participants_Db::make_link(
                $single_record_link,             // URL of the single record page
                $value,                          // field value
                '<a href="%1$s" title="%2$s" >', // template for building the link
                array( 'pdb' => $this->field->record_id )    // record ID to get the record
              );
            } ?>

            <?php /*
            * here is where we determine how each field value is presented,
            * depending on what kind of field it is
            */
            switch ( $this->field->form_element ) :

							case 'image-upload':

                $image = new PDb_Image(
                        array(
                            'filename' => $value,
                            'mode' => 'image',
                            'module' => 'list',
                      )
                        );
                $image->print_image();
                break;
								
							case 'date':
							
								/*
								 * if you want to specify a format, include it as the second 
								 * argument in this function; otherwise, it will default to 
								 * the site setting
								 */
								$this->show_date( $value, false );
								
								break;
								
						case 'multi-select-other':
						case 'multi-checkbox':
						
							/*
							 * this function shows the values as a comma separated list
							 * you can customize the glue that joins the array elements
							 */
							$this->show_array( $value, $glue = ', ' );
							
							break;
							
						case 'link' :
							
							/*
							 * prints a link (anchor tag with link text)
							 * for the template:
							 * %1$s is the URL
							 * %2$s is the linked text
							 */
							$this->show_link( $value, $template = '<a href="%1$s" >%2$s</a>', true );
							
							break;
							
						case 'rich-text':
							
							printf(
										 '<span class="%s">%s</span>',
										 $this->field->form_element == 'rich-text' ? 'textarea richtext' : 'textarea',
										 $value
										 );
							
              break;
							
						case 'text-line':
						default:
						
							if ( Participants_Db::$plugin_options['make_links'] && ! $this->field->is_single_record_link() ) {
								
								$this->show_link( $value, $template = '<a href="%1$s" >%2$s</a>', true );
								
							} else {
								
								echo esc_html( $value );
								
							}

            endswitch; // switch by field type 
            ?>
            </td>
            <?php // close the anchor tag if it's a link 
						if ( $this->field->is_single_record_link() ) : ?>
            	</a>
            <?php endif ?>
            
        <?php else : // if the field is empty ?>
        <td></td>
        <?php endif ?>
        
			<?php endwhile; // each field ?>
      </tr>
    <?php endwhile; // each record ?>
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
	// set up the bootstrap pagination classes and wrappers

  // set up the bootstrap-style pagination block
  // sets the indicator class for the pagination display
  $this->pagination->set_current_page_class( 'active' );
  // wrap the current page indicator with a dummy anchor
  $this->pagination->set_anchor_wrap( false );
  // set the wrap class and element
//  $this->pagination->set_wrappers( array(
//      'wrap_tag'=>'<div class="pagination">',
//      'wrap_tag_close' => '</div>'
//      ));
	$this->pagination->set_props(array(
																		 'first_last' => false,
																		 'current_page_class'=>'active currentpage',
																		 'wrappers' => array(
																												'wrap_class' => 'pagination-large pagination-centered',
                                                        'list_class' => 'pagination',
																												),
																		 ));
	$this->pagination->show();
	
	?>
</div>