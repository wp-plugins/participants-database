<?php
/**
 * single table-based template for the [pdb_record] shortcode for editing a record on the frontend
 *
 * this template is design to match the formatting and layout of the pdb-signup-default template
 * 
 * @version Participants Database 1.6
 */
?>
<div class="wrap <?php echo $this->wrap_class ?>">

  <?php
  if (!empty($this->participant_id)) : 
  // output any validation errors
  $this->print_errors();
  ?>

  <?php
  // print the form header
  $this->print_form_head()
  ?>

<?php while ($this->have_groups()) : $this->the_group(); ?>

      <fieldset class="field-group field-group-<?php echo $this->group->name ?> <?php echo $this->group->printing_title() ? 'group-with-title' : 'group-no-title' ?>">

        <?php $this->group->print_title('<legend>','</legend>') ?>
        <?php $this->group->print_description() ?>

        <?php
        // step through the fields in the current group

        while ($this->have_fields()) : $this->the_field();
          ?>

          <div class="form-group <?php $this->field->print_element_class() ?>">
            <label>
              <?php $this->field->print_label() ?>
            </label>
            <div class="input-group" >
              <?php $this->field->print_element_with_id(); ?>
            </div>
            <?php if ($this->field->has_help_text()) : ?>
              <p class="help-block helptext"><?php $this->field->print_help_text() ?></p>
            <?php endif ?>
          </div>

  <?php endwhile; // field loop   ?>

      </fieldset>

<?php endwhile; // group loop   ?>

    <fieldset class="field-group field-group-submit">
      <legend><?php $this->print_save_changes_label() ?></legend>
      <div class="form-group">
        <button type="submit" class="btn btn-default" ><?php echo $this->shortcode_atts['submit_button'] ?></button>
      </div>
    </fieldset>

<?php $this->print_form_close() ?>

  <?php else : ?>
    
    <?php 
    /*
     * this part of the template is used if no record is found
     */
    echo empty(Participants_Db::$plugin_options['no_record_error_message']) ? '' : '<p class="alert alert-error">' . Participants_Db::plugin_setting('no_record_error_message') . '</p>'; 
    ?>
    
    <?php endif ?>

</div>