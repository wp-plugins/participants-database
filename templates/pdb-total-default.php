<?php
/*
 *
 * template for a total value output
 * 
 * this demonstrates how to show various types ot total from the database
 *
 */
if (empty($this->shortcode_atts['fields'])) {

  /* if no "fields" attribute is defined, show a simple count of records, including 
   * the effect of a defined shortcode filter attribute
   */
  echo $record_count;
  
} else {

  /*
   * if the "fields" attribute is defined, we will be totaling values in the first 
   * column named in the attribute
   */
  $total = 0;
  /* 
   * make an array of the fields attribute, although this implementation only uses 
   * the first field listed
   * 
   */
  $fields = explode(',', str_replace(' ', '', $this->shortcode_atts['fields']));
  while ($this->have_records()) : $this->the_record();
    foreach ($this->record->values as $name => $value) {
      if ($name == $fields[0]) {
        $total = $total + floatval($value);
      }
    }
  endwhile;

  echo $total;
  
}
?>