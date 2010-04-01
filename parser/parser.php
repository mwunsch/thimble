<?php

require_once 'spyc.php';

class Parser {
	
	protected $variables = '/{([A-Za-z][A-Za-z0-9\-]*)}/';
	
	protected $blocks = '/{block:([A-Za-z][A-Za-z0-9]*)}(.*?){\/block:\\1}/s';
	
	public $data = array();
	
	public $type = '';
	
	public $defaults = array(
		'Favicon' 			=> 'http://assets.tumblr.com/images/default_avatar_16.gif',
		'PortraitURL-16' 	=> "http://assets.tumblr.com/images/default_avatar_16.gif",
		'PortraitURL-24' 	=> "http://assets.tumblr.com/images/default_avatar_24.gif",
		'PortraitURL-30' 	=> "http://assets.tumblr.com/images/default_avatar_30.gif",
		'PortraitURL-40' 	=> "http://assets.tumblr.com/images/default_avatar_40.gif",
		'PortraitURL-48' 	=> "http://assets.tumblr.com/images/default_avatar_48.gif",
		'PortraitURL-64' 	=> "http://assets.tumblr.com/images/default_avatar_64.gif",
		'PortraitURL-96' 	=> "http://assets.tumblr.com/images/default_avatar_96.gif",
		'PortraitURL-128' 	=> "http://assets.tumblr.com/images/default_avatar_128.gif",
		'CopyrightYears'	=> '2007-2010'
	);
	
	public $template = array();	
		
	public function __construct($data = array(), $type = 'index') {
		$this->type = $type;
		$this->template = array_merge($this->defaults, Spyc::YAMLLoad($data));
	}
	
	public function block_pattern($block_name) {
		return '/{block:('.$block_name.')}(.*?){\/block:\\1}/s';
	}
	
	public function parse($document) {
		$doc = $document;
		
		// generate metatags.
		
		if ($this->type == 'index') {
			$doc = $this->render_block('IndexPage', $doc);
			$doc = $this->get_posts($doc);
		}		
		// Generate global values
		if ($this->template['Description']) {
			$doc = $this->render_block('Description', $doc);
		}
		$doc = $this->seek($doc);
		// Cleanup additional blocks
		// $doc = $this->cleanup($doc);
		
		return $doc;
	}
	
	public function get_posts($document) {
		$html = preg_replace_callback(
			$this->block_pattern('Posts'),
			array($this, 'render_posts'),
			$document
		);
		return $html;
	}
	
	public function render_posts($matches) {
		$block = $matches[2];
		$html = '';
		$posts = $this->template['Posts'];
		foreach ($posts as $index => $post) {
			$markup = $this->prepare_post($post, $block);
			//render post blocks non-specific to type: permalink, etc.
			$html .= $this->render_post($post, $this->select_by_type($post, $markup));
		}
		return $html;
	}
	
	public function select_by_type($post, $block) {
		$post_type = $this->block_pattern($post['Type']);
		$found = preg_match_all($post_type, $block, $posts);
		if ($found) {
			$split = preg_split($post_type, $block);
			$stripped = array();
			foreach ($split as $component) {
				$stripped[] = preg_replace($this->blocks, '', $component);
			}
			$html = implode(implode($posts[0]),$stripped);
			return $html;
		}
	}
	
	public function render_post($post, $block) {
		$post_type = $post['Type'];
		$pattern = $this->block_pattern($post_type);
		$does_match = preg_match_all($pattern, $block, $posts);
		$html = '';
		if ($does_match) {
			foreach($posts[2] as $index => $markup) {
				switch($post_type) {
					case 'Text':
						$html = $this->render_text_post($post, $markup);
						break;
					case 'Photo':
						$html = $this->render_photo_post($post, $markup);
						break;	
					case 'Quote':
						$html = $this->render_quote_post($post, $markup);
						break;
					case 'Link':
						$html = $this->render_link_post($post, $markup);
						break;
					case 'Chat':
						$html = $this->render_chat_post($post, $markup);
						break;
					case 'Audio':
						$html = $this->render_audio_post($post, $markup);
						break;
					case 'Video':
						$html = $this->render_video_post($post, $markup);
						break;	
				}
				$html = preg_replace($pattern, $html, $block);
			}			
			return $html;
		}
	}
	
	public function prepare_post($post, $markup) {
		$block = $markup;
		$block = $this->render_variable('Permalink', $post, $block);
		$block = $this->render_variable('PostId', $post, $block);
		$block = $this->render_post_date($post, $block);
		if ($post['Reblog']) {
			$block = $this->render_reblog_info($post, $block);
		}		
		if ($post['NoteCount']) {
			$block = $this->render_variable('NoteCount', $post, $block);
			$block = $this->render_block('NoteCount', $block);
			$block = preg_replace('/{NoteCountWithLabel}/', $post['NoteCount']." notes", $block);
		} else {
			$block = $this->strip_block('NoteCount',$block);
		}
		$block = $this->render_block('More', $block);
		return $block;
	}
	
	public function render_block($name, $html) {
		return preg_replace_callback(
			$this->block_pattern($name),
			create_function(
				'$matches',
				'return $matches[2];'
			),
			$html
		);	
	}
	
	public function strip_block($name, $html) {
		return preg_replace($this->block_pattern($name), '', $html);
	}
	
	public function render_variable($name, $post, $block) {
		return preg_replace('/{'.$name.'}/', $post[$name], $block);
	} 
	
	public function seek($context) {
		return preg_replace_callback($this->variables, array($this, 'convert_properties'), $context);
	}
	
	public function cleanup($document) {
		return preg_replace($this->blocks, '', $document);
	}
	
	protected function render_post_date($post, $block) {
		$html = $block;
		$time = $post['Timestamp'];
		$right_now = time();
		$day_difference = 1;
        while (strtotime('-'.$day_difference.' day', $right_now) >= $time)
        {
            $day_difference++;
        }		
		
		$html = $this->render_variable('Timestamp', $post, $html);
		$html = preg_replace('/{TimeAgo}/', $day_difference." days ago", $html);
		$html = preg_replace('/{DayOfMonth}/', strftime('%e',$time), $html);
		$html = preg_replace('/{DayOfMonthWithZero}/', strftime('%d',$time), $html);
		$html = preg_replace('/{DayOfWeek}/', strftime('%A',$time), $html);
		$html = preg_replace('/{ShortDayOfWeek}/', strftime('%a',$time), $html);
		$html = preg_replace('/{DayOfWeekNumber}/', strftime('%u',$time), $html);
		$html = preg_replace('/{DayOfYear}/', strftime('%j',$time), $html);		
		$html = preg_replace('/{WeekOfYear}/', strftime('%V',$time), $html);		
		$html = preg_replace('/{Month}/', strftime('%B',$time), $html);
		$html = preg_replace('/{ShortMonth}/', strftime('%b',$time), $html);
		$html = preg_replace('/{MonthNumber}|{MonthNumberWithZero}/', strftime('%m',$time), $html);
		$html = preg_replace('/{Year}/', strftime('%Y',$time), $html);
		$html = preg_replace('/{ShortYear}/', strftime('%y',$time), $html);		
		$html = preg_replace('/{AmPm}/', strftime('%P',$time), $html);
		$html = preg_replace('/{CapitalAmPm}/', strftime('%p',$time), $html);
		$html = preg_replace('/{12Hour}/', strftime('%l',$time), $html);
		$html = preg_replace('/{12HourWithZero}/', strftime('%I',$time), $html);
		$html = preg_replace('/{24Hour}|{24HourWithZero}/', strftime('%H',$time), $html);
		$html = preg_replace('/{Minutes}/', strftime('%M',$time), $html);
		$html = preg_replace('/{Seconds}/', strftime('%S',$time), $html);

		$html = $this->render_block('Date', $html);
		return $html;
	}
	
	protected function render_reblog_info($post, $block) {
		$html = $block;
		$reblog = $post['Reblog'];
		$root = $reblog['Root'];
		
		$html = $this->render_variable('ReblogParentName', $reblog, $html);
		$html = $this->render_variable('ReblogParentTitle', $reblog, $html);
		$html = $this->render_variable('ReblogParentURL', $reblog, $html);
		$portraits = array(
			'ReblogParentPortraitURL-16', 'ReblogParentPortraitURL-24', 'ReblogParentPortraitURL-30', 
			'ReblogParentPortraitURL-40', 'ReblogParentPortraitURL-48', 'ReblogParentPortraitURL-64',
			'ReblogParentPortraitURL-96', 'ReblogParentPortraitURL-128'
		);
		foreach($portraits as $portrait) {
			$html = $this->render_variable($portrait, $reblog, $html);
		}
		$html = $this->render_block('Reblog', $html);
		$html = $this->render_block('RebloggedFrom', $html);
		
		if ($root) {
			$html = $this->render_variable('ReblogRootName', $root, $html);
			$html = $this->render_variable('ReblogRootTitle', $root, $html);
			$html = $this->render_variable('ReblogRootURL', $root, $html);
			$root_portraits = array(
				'ReblogRootPortraitURL-16', 'ReblogRootPortraitURL-24', 'ReblogRootPortraitURL-30', 
				'ReblogRootPortraitURL-40', 'ReblogRootPortraitURL-48', 'ReblogRootPortraitURL-64',
				'ReblogRootPortraitURL-96', 'ReblogRootPortraitURL-128'
			);
			foreach($root_portraits as $portrait) {
				$html = $this->render_variable($portrait, $root, $html);
			}
			$html = $this->render_block('RebloggedFromReblog', $html);
		}
		
		return $html;
	}
	
	protected function render_text_post($post, $block) {
		$html = '';
		$html = $this->render_variable('Body', $post, $block);
		if ($post['Title']) {
			$html = $this->render_variable('Title', $post, $html);
			$html = $this->render_block('Title', $html);
		} else {
			$html = $this->strip_block('Title',$html);
		}
		return $html;
	}
	
	protected function render_quote_post($post, $block) {
		$html = '';
		$html = $this->render_variable('Quote', $post, $block);
		$html = $this->render_variable('Length', $post, $html);
		if ($post['Source']) {
			$html = $this->render_variable('Source', $post, $html);
			$html = $this->render_block('Source', $html);
		} else {
			$html = $this->strip_block('Source',$html);
		}
		return $html;
	}
	
	protected function render_photo_post($post, $block) {
		$html = $block;
		$photo_sizes = array(
			'PhotoURL-500', 'PhotoURL-400', 'PhotoURL-250', 'PhotoURL-100', 'PhotoURL-75sq'
		);
		foreach($photo_sizes as $size) {
			$html = $this->render_variable($size, $post, $html);
		}
		if ($post['Caption']) {
			$html = $this->render_variable('Caption', $post, $html);
			$html = preg_replace('/{PhotoAlt}/', strip_tags($post['Caption']), $html);
			$html = $this->render_block('Caption', $html);
		} else {
			$html = $this->strip_block('Caption',$html);
		}
		if ($post['PhotoURL-HighRes']) {
			$html = $this->render_variable('PhotoURL-HighRes', $post, $html);
			$html = $this->render_block('HighRes', $html);
		} else {
			$html = $this->strip_block('HighRes',$html);
		}
		if ($post['LinkURL']) {
			$html = $this->render_variable('LinkURL', $post, $html);
			$html = preg_replace(
				'/{LinkOpenTag}/', 
				'<a href="'.$post['LinkURL'].'">', 
				$html
			);
			$html = preg_replace('/{LinkCloseTag}/', '</a>', $html);
		}
		return $html;
	}
	
	protected function render_link_post($post, $block) {
		$html = '';
		$html = $this->render_variable('URL', $post, $block);
		if ($post['Name']) {
			$html = $this->render_variable('Name', $post, $block);
		} else {
			$html = preg_replace('/{Name}/', $post['URL'], $html);
		}
		if ($post['Description']) {
			$html = $this->render_variable('Description', $post, $html);
			$html = $this->render_block('Description', $html);
		} else {
			$html = $this->strip_block('Description',$html);
		}
		return $html;
	}
	
	protected function render_chat_post($post, $block) {
		$html = '';
		$has_lines = preg_match_all($this->block_pattern('Lines'), $block, $matcher);
		$line_markup = '';
		if ($has_lines) {
			foreach ($matcher[2] as $each_line) {
				foreach ($post['Lines'] as $index => $lines) {
					foreach ($lines as $label => $line) {
						if ($index % 2) {
							$alt = 'odd';
						} else {
							$alt = 'even';
						}
						$line_markup .= preg_replace('/{Line}/', $line, $each_line);
						$line_markup = preg_replace('/{Alt}/', $alt, $line_markup);
						$line_markup = preg_replace('/{Label}/', $label, $line_markup);
						$line_markup = $this->render_block('Label', $line_markup);
					}
				}
			}
		}
		$html = preg_replace($this->block_pattern('Lines'), $line_markup, $block);
		if ($post['Title']) {
			$html = $this->render_variable('Title', $post, $html);
			$html = $this->render_block('Title', $html);
		} else {
			$html = $this->strip_block('Title',$html);			
		}
		return $html;
	}
	
	protected function render_audio_post($post, $block) {
		$html = $block;
		$audio_file = $post['AudioFile'];
		if ($post['ExternalAudioURL']) {
			$audio_file = $post['ExternalAudioURL'];
			$html = $this->render_variable('ExternalAudioURL', $post, $html);
			$html = $this->render_block('ExternalAudio', $html);
		} else {
			$html = $this->strip_block('ExternalAudio', $html);
		}
		$html = preg_replace('/{AudioPlayer}/', $this->create_audio_player($audio_file,'black'), $html);
		$html = preg_replace('/{AudioPlayerBlack}/', $this->create_audio_player($audio_file,'black'), $html);
		$html = preg_replace('/{AudioPlayerWhite}/', $this->create_audio_player($audio_file), $html);
		$html = preg_replace('/{AudioPlayerGrey}/', $this->create_audio_player($audio_file, 'grey'), $html);
		
		$html = $this->render_variable('PlayCount', $post, $html);
		$html = preg_replace('/{FormatPlayCount}/', number_format($post['PlayCount']), $html);
		$html = preg_replace('/{PlayCountWithLabel}/', number_format($post['PlayCount'])." plays", $html);
		
		if ($post['Caption']) {
			$html = $this->render_variable('Caption', $post, $html);
			$html = $this->render_block('Caption', $html);
		} else {
			$html = $this->strip_block('Caption', $html);
		}
		if ($post['AlbumArtURL']) {
			$html = $this->render_variable('AlbumArtURL', $post, $html);
			$html = $this->render_block('AlbumArt', $html);
		} else {
			$html = $this->strip_block('AlbumArt', $html);
		}
		if ($post['Artist']) {
			$html = $this->render_variable('Artist', $post, $html);
			$html = $this->render_block('Artist', $html);
		} else {
			$html = $this->strip_block('Artist', $html);
		}
		if ($post['Album']) {
			$html = $this->render_variable('Album', $post, $html);
			$html = $this->render_block('Album', $html);
		} else {
			$html = $this->strip_block('Album', $html);
		}
		if ($post['TrackName']) {
			$html = $this->render_variable('TrackName', $post, $html);
			$html = $this->render_block('TrackName', $html);
		} else {
			$html = $this->strip_block('TrackName', $html);
		}
		
		return $html;		
	}
	
	protected function render_video_post($post, $block) {
		$html = $block;
		$html = $this->render_variable('Video-500', $post, $html);
		$html = $this->render_variable('Video-400', $post, $html);
		$html = $this->render_variable('Video-200', $post, $html);
		if ($post['Caption']) {
			$html = $this->render_variable('Caption', $post, $html);
			$html = $this->render_block('Caption', $html);
		} else {
			$html = $this->strip_block('Caption',$html);
		}
		return $html;
	}
	
	protected function create_audio_player($audio_file, $color = '') {
		if ($color && ($color != 'white')) {
			if ($color == 'grey') {
				$color = '';
				$audio_file .= "&color=E4E4E4";
			} else {
				$color = '_'.$color;
			}
		} else {
			$color = '';
			$audio_file .= "&color=FFFFFF";
		}
		return <<<PLAYER
<script type="text/javascript" language="javascript" src="http://assets.tumblr.com/javascript/tumblelog.js?16"></script><span id="audio_player_459260683">[<a href="http://www.adobe.com/shockwave/download/download.cgi?P1_Prod_Version=ShockwaveFlash" target="_blank">Flash 9</a> is required to listen to audio.]</span><script type="text/javascript">replaceIfFlash(9,"audio_player_459260683",'<div class="audio_player"><embed type="application/x-shockwave-flash" src="http://demo.tumblr.com/swf/audio_player$color.swf?audio_file=$audio_file" height="27" width="207" quality="best"></embed></div>')</script>
PLAYER;
	
	}
	
	protected function convert_properties($match) {
		if (array_key_exists($match[1], $this->template)) {
			return $this->template[$match[1]];
		}
	}
	
}


?>