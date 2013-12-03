<?php
/*
 * default template for thanks page
 *
 */
?>
<div class="<?php echo $this->wrap_class ?> signup-thanks">
  <?php
  /*
   * this shows the use of the "signup thanks" setting to generate the message. 
   * It is also possible to simply put the text you want to show with any 
   * variables embedded in it. In that case, you would probably remove this, 
   * and not use the "signup thanks" setting at all.
   * 
   * You can also pass in a message string to override the plugin setting.
   */
  echo $this->get_thanks_message(); 
  ?>
</div>