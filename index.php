<?php

// load the parser
require('parser/parser.php');

$theme = new Parser();

$document = file_get_contents('themes/test.html');
print_r($theme->parse($document));

?>