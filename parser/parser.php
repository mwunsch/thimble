<?php

require_once 'spyc.php';

class Parser {
	
	protected $variables = '/{([A-Za-z][A-Za-z0-9\-]*)}/';
	
	protected $blocks = '/{(block:[A-Za-z][A-Za-z0-9]*)}(.*?){\/\\1}/s';
	
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
		'PortraitURL-128' 	=> "http://assets.tumblr.com/images/default_avatar_128.gif"
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
		
		// Finally, generate global values
		
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
		foreach ($posts as $post) {
			$html = $this->render_post($post, $block);
		}
		return $html;
	}

	
	public function render_post($post, $block) {
		$html = '';
		switch($post['Type']) {
			case 'Text':
				$html = $this->render_text_post($post, $block);
				break;
		}
		return $html;
	}
	
	protected function render_text_post($post, $block) {
		$pattern = $this->block_pattern($post['Type']);
		$does_match = preg_match_all($pattern, $block, $posts);
		if ($does_match) {
			$html = '';
			foreach ($posts[2] as $index => $text) {
				$text = preg_replace('/{Body}/', $post['Body'], $text);
				if ($post['Title']) {
					$text = preg_replace('/{Title}/', $post['Title'], $text);
					$text = preg_replace_callback(
						$this->block_pattern('Title'),
						create_function(
							'$matches',
							'return $matches[2];'
						),
						$text
					);
				} else {
					$text = preg_replace($this->block_pattern('Title'), '', $text);
				}
				
				$html = preg_replace($pattern, $text, $block, 1);
			}
			return $html;
		}
	}
	
	
	// public function narrow_scope($scope, $block='') {
	// 	$does_match = preg_match_all($this->blocks, $scope, $matcher);
	// 	$doc = '';
	// 			
	// 	if ($does_match){
	// 		foreach ($matcher[2] as $context) {
	// 			$doc .= $this->narrow_scope($context);
	// 		}
	// 		return $doc;
	// 	} else {
	// 		return $scope;
	// 		// return preg_replace($this->variables, $scope ,$scope);
	// 	}
	// }
	// 
	// public function seek($context) {
	// 	return preg_replace_callback($this->variables, array($this, 'convert_properties'), $context);
	// }
	// 
	// protected function convert_properties($match) {
	// 	if (array_key_exists($match[1], $this->template)) {
	// 		return $this->template[$match[1]];
	// 	}
	// }
	
}


?>