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
		// Do big GREP on $document based on page type
		// return $this->narrow_scope($document);
		// generate metatags.
		
		$doc = $this->get_posts($document);
		
		// Generate global values
		if ($this->template['Description']) {
			$doc = $this->render_block('Description', $doc);
		}
		$doc = $this->seek($doc);
		// Cleanup additional blocks
		$doc = $this->cleanup($doc);
		
		return $doc;
		// print_r($this->template);
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
			//render post blocks non-specific to type: permalink, etc.
			$html .= $this->render_post($post, $this->select_by_type($post, $block));
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
				}
				$html = preg_replace($pattern, $html, $block);
			}			
			return $html;
		}
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
	
	protected function convert_properties($match) {
		if (array_key_exists($match[1], $this->template)) {
			return $this->template[$match[1]];
		}
	}
	
}


?>