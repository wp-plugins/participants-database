<?php
/*
 * we basically need to know where wordpress is installed, and at this point we 
 * don't have any of the usual constants or functions, so we're just going to take 
 * a few guesses here.
 * 
 */
$wp_path = $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';
if (!is_readable($wp_path)) {
  $wp_path = $_SERVER['DOCUMENT_ROOT'] . '/wordpress/wp-load.php';
}
if (!is_readable($wp_path)) {
    $wp_path = $_SERVER['DOCUMENT_ROOT'] . '/wp/wp-load.php';
};
if (!is_readable($wp_path)) {
  // this means DOCUMENT_ROOT doesn't work on this server; try a different method
  $script_path = realpath(basename($_SERVER["SCRIPT_NAME"]));
  $base_path = strstr($script_path, 'wp-content/', true);
  $wp_path = $base_path . '/wp-load.php';
}
if (is_readable($wp_path)) {
      include $wp_path;
    } else {
  exit; // bail out; we can't find the load script
}

$etag = md5(Participants_Db::$plugin_options['custom_css']);
header('Etag: "' . $etag . '"');
header('Content-type: text/css'); 
echo Participants_Db::$plugin_options['custom_css'];
?>

@media print {
<?php echo Participants_Db::$plugin_options['print_css'] ?>
}