<?php

require_once 'parser/parser.php';

$DATA = 'demo.yml';
$THEME = $_GET['theme'];

$theme = new ThimbleParser(file_get_contents('data/'.$DATA));
echo $theme->parse(file_get_contents('themes/'.$THEME));

?>