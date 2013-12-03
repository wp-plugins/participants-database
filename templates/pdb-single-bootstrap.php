<?php
/*
 * default template for displaying a single record for the twitter bootstrap framework
 *
 * http://twitter.github.com/bootstrap/
 * 
 */

// define an array of fields to exclude here
$exclude = array();

?>

<div class="wrap  <?php echo $this->wrap_class ?>">

	
  <?php while ( $this->have_groups() ) : $this->the_group(); ?>
  
  <section id="<?php echo Participants_Db::$prefix.$this->group->name?>" style="overflow:auto">
  
    <?php $this->group->print_title( '<h2>', '</h2>' ) ?>
    
    <?php $this->group->print_description( '<p>', '</p>' ) ?>
    
      <?php while ( $this->have_fields() ) : $this->the_field();
      
          // skip any field found in the exclude array
          if ( in_array( $this->field->name, $exclude ) ) continue;
					
          // CSS class for empty fields
					$empty_class = $this->get_empty_class( $this->field );
      
      ?>
    
    <dl class="dl-horizontal <?php echo Participants_Db::$prefix.$this->field->name ?>">
      
      <dt class="<?php echo $this->field->name.' '.$empty_class?>"><?php $this->field->print_label() ?></dt>
      
      <dd class="<?php echo $this->field->name.' '.$empty_class?>"><?php $this->field->print_value() ?></dd>
      
    </dl>
  
    	<?php endwhile; // end of the fields loop ?>
    
    
  </section>
  
  <?php endwhile; // end of the groups loop ?>
  
</div>