<?php
/*
 * we basically need to know where wordpress is installed, and at this point we don't have any of the usual constants or functions, so we're just going to take a few guesses here.
 * 
 */
$wp_path = $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';
if (is_file($wp_path)){
  include $wp_path;
} else {
  $wp_path = $_SERVER['DOCUMENT_ROOT'] . '/wordpress/wp-load.php';
  if (is_file($wp_path)){
    include $wp_path;
  } else {
    $wp_path = $_SERVER['DOCUMENT_ROOT'] . '/wp/wp-load.php';
    if (is_file($wp_path)){
      include $wp_path;
    } else {
      exit(); // no custom CSS; can't find include
    };
  };
};
$etag = md5(Participants_Db::$plugin_options['custom_css']);
header('Etag: "'.$etag.'"');
header('Content-type: text/css'); 
echo Participants_Db::$plugin_options['custom_css'];
?>

@media print {
<?php echo Participants_Db::$plugin_options['print_css'] ?>

}