<?php
/*
 * template for signup form
 *
 * this template demonstrates how to output a form using a very simple output 
 * with minimal HTML structure
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

 ?>
 
<div class="<?php echo $this->shortcode_atts['class']?> pdb-signup" >

<?php $errors = $this->errors();
if ( ! empty( $errors ) ) : // if there are errors to show ?>

  <?php $this->error_CSS(); // output the CSS styles to highlight the fields that need correcting ?>

  <ul class="<?php $this->error_class() ?>" >

    <?php foreach( $errors as $error ) : ?>

    <li><?php echo $error ?></li>

    <?php endforeach ?>
		
	</ul>

<?php endif ?>

  <?php $this->form_top(); // this must be included before any fields are output ?>

    <div class="pdb-signup">

      <?php foreach( $this->get_signup_form_fields() as $group ) : ?>

        <?php if ( ! empty( $group->name ) ) : // print the group title/description row if present ?>
        <fieldset class="signup-group">

            <legend><?php echo $group->title ?></legend>

            <?php if ( ! empty( $group->description ) ) : ?>
            <p><?php echo $group->description ?></p>
            <?php endif ?>
        <?php endif; // end group title/description row

        while ( $this->have_fields( $group ) ) :  $field = $this->current_field( $group ) ?>

          <p><label for="<?php echo $field->name?>" class="<?php echo $field->form_element?>">
  
            <?php $this->field_title( $field ); // this function adds the required marker ?>
  
          </label>
  
            <?php $this->print_field( $field ) ?>
  
            <?php if ( ! empty( $field->help_text ) ) : ?>
  
              <span class="helptext">
                <?php echo trim( $field->help_text ) ?>
              </span>
  
            <?php endif ?>
            
            </p>
  
          <?php endwhile; // fields ?>
        
        </fieldset>

        <?php endforeach; // groups ?>

        
        <?php if ( $captcha = $this->_add_captcha( $this->captcha_type ) ) : ?>
        <div  class="pdb-captcha">
					<?php echo $captcha?>
        </div>
        <?php endif ?>
        
        <div class="submit-buttons">
          <?php $this->submit_button('button-primary pdb-submit') ?>
        </div>
  </form>
</div>