<?php
/*
 * default template to display private link retrieval form
 *
 */
$mode = isset($_POST['action']) && $_POST['action'] == 'success' ? 'success' : 'request';
?>
<div class="wrap <?php echo $this->wrap_class . 'mode-' . $mode ?> " >

  <h4><?php _e('Request your Private Link', 'participants-database') ?></h4>

  <?php
  // output any validation errors
  $this->print_errors();
  ?>

<?php if ($mode == 'success') : ?>

    <h5><?php _e('Success: your private link has been emailed to you.', 'participants-database') ?></h5>

  <?php else : ?>

  <?php $this->print_form_head(); // this must be included before any fields are output   ?>

    <div class="form-horizontal pdb-signup">

  <?php while ($this->have_groups()) : $this->the_group(); ?>

      <?php if ($this->have_fields()) : ?>

        <fieldset class="field-group">

          <?php while ($this->have_fields()) : $this->the_field(); ?>

            <?php
            if ($this->field->name == Participants_Db::$plugin_options['retrieve_link_identifier']) {
              $this->field->help_text = sprintf(Participants_Db::$plugin_options['id_field_prompt'], $this->field->title);
            }
            ?>

            <div class="<?php $this->field->print_element_class() ?> control-group <?php echo $feedback_class ?>">

              <label class="control-label" for="<?php $this->field->print_element_id() ?>" ><?php $this->field->print_label(); // this function adds the required marker   ?></label>

              <div class="controls" id="<?php $this->field->print_element_id() ?>">

                <?php $this->field->print_element(); ?>

                <?php if ($this->field->has_help_text()) : ?>
                  <span class="help-block"><?php $this->field->print_help_text() ?></span>
      <?php endif ?>

              </div>

            </div>

    <?php endwhile; // fields   ?>

        </fieldset>

      <?php endif ?>

  <?php endwhile; // groups   ?>

      <fieldset class="field-group field-group-submit">

        <div id="submit-button" class="controls">

  <?php $this->print_submit_button('button-primary', __('Submit', 'participants-database')); // you can specify a class for the button   ?>
        </div>

      </fieldset>

    </div>

    <?php $this->print_form_close() ?>

<?php endif ?>

</div>