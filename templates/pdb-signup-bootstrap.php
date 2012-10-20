<?php
/*
 * bootstrap template for signup form
 *
 * If the signup form setting "Show Field Groups" is selected, the form fields
 * will be grouped by groups with the "visible" attribute checked in the manage
 * database fields page.
 *
 * If the "Show Field Groups" setting is not checked, all the from fields will
 * be output according to the field otder
 *
 * each group has three values you can use:
 *    $group->name         a unique string suitable for classnames and such
 *    $group->title        the group name to be displayed
 *    $group->description  contains any extra text you want to add to each group
 *
 * each field has three values:
 *    $field->name         like $group->name it's good for precise control over
 *                         your display using CSS selectors
 *    $field->title        makes a good display title for the field
 *    $field->form_element tells you what kind of data the field contains so you
 *                         can display it accordingly.
 *    $field->default      is the default value (if any) of the field
 *
 * there are several print methods for outputting elements of the form. You don't 
 * have to use them, but you will have to make sure the form is valid and still 
 * works properly.
 *    $this->field_title   prints the title of the field and adds the required 
 *                         marker if you have it set up
 *    $this->print_field   prints the actual form field and any other elements 
 *                         that go with it
 *    $this->submit_button prints a submit button with the class supplied
 *
 * this template is a simple demonstration of what is possible it is set up to
 * output the form exactly as it was output before we brought in templates so the
 * upgrade will not affect existing installations
 *
 * for those unfamiliar with PHP, just remember that something like
 * <?php echo $field->title ?> just prints out the field's title. You can move it
 * around, but leave all the parts between the <> brackets as they are.
 *
 */
 
 $feedback_class = '';
 $errors = $this->errors();

 ?>
 
<div class="<?php echo $this->shortcode_atts['class']?> pdb-signup" >

  <?php $this->form_top(); // this must be included before any fields are output ?>

    <div class="form-horizontal pdb-signup">

      <?php foreach( $this->get_signup_form_fields() as $group ) : ?>

        <?php if ( ! empty( $group->name ) ) : // print the group title/description row if present ?>
        </fieldset>
        <fieldset class="signup-group">
          <legend><?php echo $group->title?></legend>

            <?php if ( ! empty( $group->description ) ) : ?>
            <p><?php echo $group->description?></p>
            <?php endif ?>
				
				<?php endif; // end group title/description header ?>

        <?php while ( $this->have_fields( $group ) ) :  $field = $this->current_field( $group ) ?>
        
       <?php if ( is_object( Participants_Db::$validation_errors ) && in_array( "#pdb_".$field->name." input", Participants_Db::$validation_errors->error_CSS ) ) $feedback_class = 'error';
				 else $feedback_class = ''; ?>

        <div id="<?php echo Participants_Db::$css_prefix,$field->name?>" class="pdb-<?php echo $field->form_element?> control-group <?php echo $feedback_class ?>">

          <label class="control-label" for="<?php echo $field->name?>"><?php $this->field_title( $field ); // this function adds the required marker ?></label>
          <div class="controls"><?php $this->print_field( $field ) ?>

						<?php if ( ! empty( $field->help_text ) ) : ?>
  
              <p class="help-block">
                <?php echo trim( $field->help_text ) ?>
              </p>
  
            <?php endif ?>

          </div>
          
        </div>

        <?php endwhile; // fields

        endforeach; // groups ?>
        
        <?php if ( $captcha = $this->_add_captcha( $this->captcha_type ) ) : ?>
        <div id="captcha" class="control-group">
          <td colspan="2" class="pdb-captcha"><?php echo $captcha?></td>
        </div>
        <?php endif ?>
        <div id="submit-button" class="controls">
          <?php $this->submit_button('btn btn-primary pdb-submit') ?>
        </div>
    </div>
  </form>
</div>