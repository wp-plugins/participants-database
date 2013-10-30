<?php
/*
 * bootstrap template for signup form
 *
 * outputs a Twitter Bootstrap-compatible form
 * http://twitter.github.com/bootstrap/index.html
 *
 */
?>
 
<div class="wrap <?php echo $this->wrap_class ?>" >

  <?php // this is how the html wrapper for the error messages can be customized
  $this->print_errors( '<div class="alert %1$s">%2$s</div>','<p>%s</p>' ); ?>

  <?php $this->print_form_head(); // this must be included before any fields are output ?>

    <div class="form-horizontal pdb-signup">

      <?php while ( $this->have_groups() ) : $this->the_group(); ?>

        <fieldset class="signup-group">
				<?php $this->group->print_title( '<legend>', '</legend>' ) ?>
				<?php $this->group->print_description() ?>

        <?php while ( $this->have_fields() ) : $this->the_field(); ?>
        
        <?php $feedback_class = $this->field->has_error() ? 'error' : ''; ?>

        <div id="<?php $this->field->print_element_id() ?>" class="<?php $this->field->print_element_class() ?> control-group <?php echo $feedback_class ?>">

          <label class="control-label" for="<?php echo $this->field->name?>"><?php $this->field->print_label(); // this function adds the required marker ?></label>
          <div class="controls"><?php $this->field->print_element(); ?>

						<?php if ( $this->field->has_help_text() ) :?>
              <p class="help-block">
                <?php $this->field->print_help_text() ?>
              </p>
            <?php endif ?>

          </div>
          
        </div>

        <?php endwhile; // fields

      endwhile; // groups ?>
        
        <?php /* if ( $captcha = $this->_add_captcha( $this->captcha_type ) ) : ?>
        <div id="captcha" class="control-group">
          <td colspan="2" class="pdb-captcha"><?php echo $captcha?></td>
        </div>
        <?php endif */ ?>
        <div id="submit-button" class="controls">
          <?php $this->print_submit_button('btn btn-primary'); // you can specify a class for the button ?>
          <span class="pdb-retrieve-link"><?php $this->print_retrieve_link(__('Forget your private link? Click here to have it emailed to you.','participants-database')); ?></span>
        </div>
    </div>
  <?php $this->print_form_close() ?>
</div>