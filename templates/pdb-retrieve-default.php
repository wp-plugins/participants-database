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
  
  <h4><?php _e('Forgot your private link?','participants-database') ?></h4>

  <?php // output any validation errors
  $this->print_errors(); ?>
  
  <?php if (isset($_POST['action']) && $_POST['action'] == 'success') : ?>
  
    <h5><?php _e('Success: your private link has been emailed to you.','participants-database') ?></h5>
  
  <?php else : ?>

  <?php $this->print_form_head(); // this must be included before any fields are output ?>
  
  <?php $this->field = new Field_Item( current($this->get_retrieve_field()) ); ?>

    <table class="form-table pdb-signup">

      <tbody>

        <tr class="<?php echo $this->field->form_element ?>">

          <th><?php $this->field->print_label(); // this function adds the required marker ?></th>

          <td id="<?php $this->field->print_element_id() ?>">

            <?php $this->field->print_element(); ?>

            <span class="helptext"><?php _e('Type in your email address, your private link will be emailed to you.','participants-database') ?></span>
            
          </td>

        </tr>
        <tr>
          <td colspan="2" class="submit-buttons">
            
           <?php $this->print_submit_button('button-primary',__('Submit','participants-database')); // you can specify a class for the button ?>
           
          </td>
        </tr>
        
      </tbody>
      
    </table>
    
  <?php $this->print_form_close() ?>
  
  <?php endif ?>
  
</div>