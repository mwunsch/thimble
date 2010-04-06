<!DOCTYPE html>
<html dir="ltr" lang="en-US" id="thimblr">
<head>
	<meta charset="utf-8" />
	<title>Thimble</title>
	<meta name="Copyright" content="Copyright (c) 2010 Mark Wunsch" />
	<script type="text/javascript" src="assets/jquery.js"></script>
	<script type="text/javascript" src="assets/main.js"></script>	
	<style type="text/css">
		html {overflow-x: hidden; overflow-y: hidden;}
		body { font-size: 13px; line-height: 1.3; font-family: Helvetica, Arial,sans-serif; margin: 0; color: #333; }
		#theme-select { height: 30px; padding: 0 15px; line-height: 30px; background: #2C4762; color: #fff; }
		#theme-select label { font-family: 'Bookman Old Style', Georgia, serif; font-weight: bold; font-size: 18px; vertical-align: middle; padding-right: 8px;}
		#theme-select a { float: right; color: #fff; }
		#theme-container {position:absolute; left:0px; right:0px; top:30px; bottom:0px;}
		#theme-preview {width:100%; height:100%;}
	</style>
</head>
<body>
	<form method="get" action="theme.php" id="theme-select">
		<label for="theme-selector">thimble</label>
		<select name="theme" id="theme-selector">
			<?php
				foreach (glob('themes/*.html') as $theme) {
                    $theme = basename($theme);
					if (($theme !== '.') && ($theme !== '..')) {
						echo "<option value=\"$theme\">$theme</option>
";
					}
				}
			?>
		</select>
		<a href="http://www.tumblr.com/docs/en/custom_themes">Theme Documentation</a>
	</form>
	<div id="theme-container">
		<iframe id="theme-preview" border="0" frameborder="0"></iframe>
	</div>
</body>	
</html>
