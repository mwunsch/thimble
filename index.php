<?php

// load the parser
require('parser/parser.php');

$data = file_get_contents('data/demo.yml');
$theme = new Parser($data);

$document = file_get_contents('themes/redux.html');

print_r($theme->parse($document));



?>