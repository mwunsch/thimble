<?php

require_once 'parser/parser.php';

$VERSION = '0.3.0';
$DATA = 'demo.yml';
$LOCALE = 'en-us.yml';
$THEME = 'themes/'.$_GET['theme'];
$options = $_REQUEST;

unset($options['theme'], $options['auto-refresh']);

$last_modified_time = filemtime($THEME); 
$etag = md5_file($THEME); 

header("Last-Modified: ".gmdate("D, d M Y H:i:s", $last_modified_time)." GMT"); 
header("Etag: $etag");

if (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $last_modified_time || 
   trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag) { 
   header("HTTP/1.1 304 Not Modified"); 
   exit; 
}

$theme = new ThimbleParser(file_get_contents('data/'.$DATA), file_get_contents('lang/'.$LOCALE));
echo $theme->parse(file_get_contents($THEME), $options);

?>
