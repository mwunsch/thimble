<?php

require 'theme.php';

$DATA = 'demo.yml';
$THEME = 'redux.html';

$theme = new Parser(file_get_contents('data/'.$DATA));
echo $theme->parse(file_get_contents('themes/'.$THEME));

?>