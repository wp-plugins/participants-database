<?php
require '../../../../wp-load.php';
$etag = md5(Participants_Db::$plugin_options['custom_css']);
header('Etag: "'.$etag.'"');
header('Content-type: text/css'); 
echo Participants_Db::$plugin_options['custom_css'];
?>

@media print {
<?php echo Participants_Db::$plugin_options['print_css'] ?>

}