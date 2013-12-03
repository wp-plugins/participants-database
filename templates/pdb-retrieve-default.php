<?php
/*
 * default template to display private link retrieval form
 *
 */
$mode = isset($_POST['action']) && $_POST['action'] == 'success' ? 'success' : 'request';
 ?>
<div class="wrap <?php echo $this->wrap_class .  'mode-' . $mode ?> " >
  
  <h4><?php _e('Request your Private Link','participants-database') ?></h4>

  <?php // output any validation errors
  $this->print_errors(); ?>
  
  <?php if ($mode == 'success') : ?>
  
    <h5><?php _e('Success: your private link has been emailed to you.','participants-database') ?></h5>
  
  <?php else : ?>

  <?php $this->print_form_head(); // this must be included before any fields are output ?>
  
  <?php $this->field = new PDb_Field_Item( current($this->get_retrieve_field()) ); ?>
    
    <table class="form-table pdb-signup">

      <tbody>

        <tr class="<?php echo $this->field->form_element ?>">

          <th><?php $this->field->print_label(); // this function adds the required marker ?></th>

          <td id="<?php $this->field->print_element_id() ?>">

            <?php $this->field->print_element(); ?>

            <span class="helptext"><?php printf(__('Type in your %s, your private link will be emailed to you.','participants-database'),$this->field->title) ?></span>
            
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