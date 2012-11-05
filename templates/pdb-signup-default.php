<?php
/*
 * default template for signup form
 *
 * If the signup form setting "Show Field Groups" is selected, the form fields
 * will be grouped by the field groups that have their "visible" attribute
 * checked in the manage database fields page.
 *
 * If the "Show Field Groups" setting is not checked, the fields will be shown
 * in the same order, but without the group titles.
 *
 * each group has three values you can use:
 *    $this->group->name         a unique string suitable for classnames and such
 *    $this->group->title        the group title to be displayed
 *    $this->group->description  contains any extra text you want to add to each group
 *
 * each field has these values:
 *    $this->field->name         like $this->group->name it's good for precise control
 *                               over your display using CSS selectors
 *    $this->field->title        the display title for the field, usually used as the label
 *    $this->field->form_element tells you what kind of data the field contains so you
 *                               can display it accordingly.
 *    $this->field->default      is the default value (if any) of the field, usually you don't
 *                               have to do anything with this, it will be used to pre-load the
 *                               field
 *
 * there are several print methods for outputting elements of the form. You don't 
 * have to use them, but if you don't, you will have to make sure the form is valid and still 
 * works properly.
 * 
 *    $this->field->print_field_label();   prints the title of the field and adds the required 
 *                                         marker if you have it set up
 *    $this->field->print_field_element(); prints the actual form field and any other elements 
 *                                         that go with it
 *    $this->print_submit_button();        prints a submit button with the class supplied
 *    $this->print_errors()                prints any validation errors
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
 
<!-- template:<?php echo basename( __FILE__ ); // this is only to show which template is in use ?> -->
<div class="wrap <?php echo $this->wrap_class ?>" >

  <?php // output any validation errors
  $this->print_errors(); ?>

  <?php $this->print_form_head(); // this must be included before any fields are output ?>

    <table class="form-table pdb-signup">

      <tbody>

      <?php while ( $this->have_groups() ) : $this->the_group(); ?>

        <?php if ( $this->options['signup_show_group_descriptions'] and ! empty( $this->group->title ) ) : // print the group title/description row if present ?>
        <tr class="signup-group">
          <td colspan="2">
        
            <?php $this->group->print_title() ?>
            <?php $this->group->print_group_description() ?>

          </td>
        </tr>
        <?php endif; // end group title/description row ?>

        <?php while ( $this->have_fields() ) : $this->the_field(); ?>

        <tr class="<?php $this->field->print_element_class() ?>">

          <th><?php $this->field->print_label(); // this function adds the required marker ?></th>

          <td id="<?php $this->field->print_element_id() ?>">

            <?php $this->field->print_element(); ?>

            <?php if ( $this->field->has_help_text() ) :?>
            <span class="helptext"><?php $this->field->print_help_text() ?></span>
            <?php endif ?>
            
          </td>

        </tr>
  
        <?php endwhile; // fields

      endwhile; // groups ?>
      
        <?php /*if ( $captcha = $this->_add_captcha( $this->captcha_type ) ) : ?>
        <tr>
          <td colspan="2" class="pdb-captcha"><?php echo $captcha?></td>
        </tr>
        <?php endif */?>
        <tr>
          <td colspan="2" class="submit-buttons">
            
           <?php $this->print_submit_button('button-primary'); // you can specify a class for the button ?>
           
          </td>
        </tr>
        
      </tbody>
      
    </table>
    
  <?php $this->print_form_close() ?>
  
</div>