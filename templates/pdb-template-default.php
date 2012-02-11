<?php
/*
 * default template for displaying a single record
*/

// define an array of fields to exclude here
$exclude = array();
?>
<div class="wrap edit-participant">
<?php
// display each column of data
foreach ( Participants_Db::single_record_fields( $id, $exclude ) as $group ) :
?>
<pre>
<?php print_r( $group ) ?>
</pre>
<p></p>

<?php endforeach;

?>