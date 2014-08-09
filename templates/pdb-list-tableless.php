<?php
/*

template for participants list shortcode output

this template demonstrates a "table-less" layout for the list of records

*/
?>
<div class="wrap <?php echo $this->wrap_class ?>">
<a name="<?php echo $this->list_anchor ?>" id="<?php echo $this->list_anchor ?>"></a>
<?php /* SEARCH/SORT FORM */ ?>
  <?php if ( $filter_mode != 'none' ) : ?>
  <div class="pdb-searchform">
  
    <div class="alert alert-block" style="display:none">
    	<a class="close" data-dismiss="alert" href="#">X</a>
      <p class="search_field_error"><?php _e( 'Please select a column to search in.', 'participants-database' )?></p>
      <p class="value_error"><?php _e( 'Please type in something to search for.', 'participants-database' )?></p>
    </div>

    <?php $this->search_sort_form_top( false, 'form-horizontal' ); ?>

    <?php if ( $filter_mode == 'filter' || $filter_mode == 'both' ) : ?>

    
			
      <div class="control-group">
      	<label class="control-label"><?php _e('Search', 'participants-database' )?>:</label>
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
      	<label class="control-label"><?php _e('Sort by', 'participants-database' )?>:</label>
      	<div class="controls">

      		<?php $this->sort_form() ?>
          
        </div>
      </div>
    <?php endif ?>

  </div>
  <?php endif ?>

<?php 
/* print the count if enabled in the shortcode
 * 
 * the tag wrapping the count statment can be supplied in the function argument, example here
 */
$this->print_list_count('<h5>'); 
?>
<?php // this is an example of a way to style the records, delete this or edit as needed ?>
<style type="text/css">
  section {
    margin: 1.5em 0;
  }
  section p {
    margin: 0;
  }
</style>

<div class="table pdb-list list-container" >
  
    
    <?php if ( $record_count > 0 ) : ?>

    <?php while ( $this->have_records() ) : $this->the_record(); // each record is one row ?>
      <section id="record-<?php echo $this->record->record_id ?>">
        <?php while( $this->have_fields() ) : $this->the_field(); // each field is one cell ?>

          <?php $value = $this->field->value;
          if ( ! $this->field->is_empty( $value ) ) : ?>
        <p>
        <strong><?php echo $this->field->title ?>:&nbsp;</strong>
          	<?php 
						/* wrap the item in a single record link if it's enabled for this field:
						 * this uses the global setting. If you want to customize the field on
						 * which to place the link to the record detail page, change the "test"
						 * to something like this:
						 * if ( $this->field->name == 'field_name' ) {
						 *
						 * if you do this, check out the case below where we make a clickable
						 * link: it does the same test so we don't duplicate the field. You'll
						 * have to modify that in the same way
						 */
						if ( $this->field->is_single_record_link() ) {
              
              /*
               * normally, when a value is empty, nothing is shown...but if the field 
               * has been designated as the single record link, we must have something 
               * to click on, so, we use the default value of the field if there is no 
               * stored value. This makes it possible to create a 'static' link to the 
               * single record by defining a read-only field with a default value
               */
              $value = empty($value) ? $this->field->default : $value;
							
							// add the record ID to the single record link and set the field's link property
							$this->field->link = Participants_Db::add_uri_conjunction($single_record_link) . 'pdb=' . $this->record->record_id;
							
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
                            'relstring' => 'lightbox',
                      )
                        );
                $image->print_image();
                break;
								
							case 'date':
							
								/*
								 * if you want to specify a format, include it as the second 
								 * argument in this function; otherwise, it will default to 
								 * the site setting. See PHP date() for formatting codes
								 */
								$this->show_date( $value, false );
								
								break;
								
						case 'multi-select-other':
						case 'multi-checkbox':
						
							/*
							 * this function shows the values as a comma separated list
							 * you can customize the glue string that joins the array elements
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
										 /* we wrap long text in this span so we can control it's size
										  * when presented in a list
										  */
										 '<span class="%s">%s</span>',
										 
										 // this adds our CSS class
										 'textarea richtext',
										 
										 /*
											* here, we process the rich text output through wpautop. This is needed
											* to automatically create paragraphs in rich text. You can take this out
											* if you don't want to use WP auto-paragraphs. See 
											* http://codex.wordpress.org/Function_Reference/wpautop
											*/
										 wpautop($value)
										 
										 );
							
              break;
						
						case 'text-area':
							
							printf(
										 /* we wrap long text in this span so we can control it's size
										  * when presented in a list
										  */
										 '<span class="%s">%s</span>',
										 // the CSS class
										 'textarea',
										 // the text
										 $value
										 );
							
              break;
							
						case 'text-line':
						default:
						
							/*
							 * if the make links setting is enabled, try to make a link out of the field
							 */
							if (
                      Participants_Db::$plugin_options['make_links'] 
                      && 
                      ! $this->field->is_single_record_link() 
                      && 
                      filter_var($value, FILTER_VALIDATE_EMAIL) !== false
                      && 
                      filter_var($value, FILTER_VALIDATE_URL) !== false       
                 ) {
								
								$this->show_link( $value, $template = '<a href="%1$s" >%2$s</a>', true );
								
							} else {
								
                echo PDb_FormElement::get_field_value_display($this->field);
								
							}

            endswitch; // switch by field type ?>
            </p>
            <?php // close the anchor tag if it's a link 
						if ( $this->field->is_single_record_link() ) : ?>
            	</a>
            <?php endif ?>
            
        <?php else : // if the field is empty ?>
        <?php endif ?>
        
			<?php endwhile; // each field ?>
      </section>
    <?php endwhile; // each record ?>
    
    <?php else : // if there are no records ?>

    <h4><?php if ($this->is_search_result === true)  echo Participants_Db::$plugin_options['no_records_message'] ?></h4>

    <?php endif; // $record_count > 0 ?>
	</div>
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