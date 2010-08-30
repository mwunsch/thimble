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
		#theme-select .small { font-family: Helvetica, Arial, sans-serif; font-weight: normal; font-size: 11px;}
		#theme-select a { float: right; color: #fff; }
    #theme-select #appearance-selector { position: relative; margin: 0 20px 0 5px; }
    #theme-select #appearance-selector summary { cursor: pointer; padding: 0 5px; display: inline-block; }
    #appearance-selector.open summary { background: #fff; color: #2c4762; }
    #theme-select #appearance-selector .options { width: 300px; padding: 0 1em 1em; background: rgba(221, 221, 221, 0.9); border: 1px solid #ddd; overflow: auto; position: absolute; left: 0; z-index: 5; display: none; color: #444; line-height: 1.3;}
    #theme-select .options label { font-family: Helvetica, Arial, sans-serif; font-weight: normal; font-size: 100%; padding: 0; margin-right: 5px; }
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
						echo "<option value=\"$theme\">$theme</option>\n";
					}
				}
			?>
		</select>
    <details id="appearance-selector">
      <summary>Appearance</summary>
      <div class="options">
        <input type="submit" value="OK" />
      </div>
    </details>
		<input type="checkbox" name="auto-refresh" id="auto-refresh">
		<label for="auto-refresh" class="small">Auto Refresh?</label>
		<a href="http://www.tumblr.com/docs/en/custom_themes">Theme Documentation</a>
	</form>
	<div id="theme-container">
		<iframe id="theme-preview" border="0" frameborder="0"></iframe>
	</div>
</body>	
</html>
