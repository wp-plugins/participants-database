<?php
header('Content-type: text/css'); 
require '../../../../wp-load.php';
echo Participants_Db::$plugin_options['custom_css'];
?>

@media print {
<?php echo Participants_Db::$plugin_options['print_css'] ?>

}