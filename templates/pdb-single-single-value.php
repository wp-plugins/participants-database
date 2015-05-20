<?php
/*
 * template for showing a single value from a record
 *
 * the field to show must be defined in the shortcode using the "fields" attribute: 
 * [pdb_single template="single-value" fields="first_name"]
 * 
 * this template requres the id of the record to get the value from be provided, 
 * either as a variable in the URI (?pdb=123) or as a shortcode attribute 
 * [pdb_single template="single-value" record_id="123" fields="first_name"]
 *
 */
while ($this->have_groups()) : $this->the_group();
 
  while ($this->have_fields()) : $this->the_field();
  ?>
 		
    <span class="pdb-value <?php echo $this->field->name . ' ' . $this->get_empty_class($this->field) ?>"><?php $this->field->print_value() ?></span>
    
  <?php
    endwhile;
    
  break;
 	
endwhile;