<?php

require_once 'spyc.php';

class Parser {
	
	protected $variables = '/{([A-Za-z][A-Za-z0-9\-]*)}/';
	
	protected $blocks = '/{(block:[A-Za-z][A-Za-z0-9]*)}(.*?){\/\\1}/s';
	
	public $data = array();
	
	public $type = '';
	
	public $defaults = array();
	
	public $template = array();	
		
	public function __construct($data = array(), $type = 'index') {
		$this->type = $type;
		$this->template = array_merge($this->defaults, Spyc::YAMLLoad($data));
	}
	
	public function parse($document) {
		// Do big GREP on $document based on page type
		// return $this->narrow_scope($document);
		// generate metatags.
		$posts = $this->template['Posts'];
		foreach ($posts as $post) {
			$this->render_post($post, $document);
		}
		// return $this->seek($document);
	}
	
	public function get_block($block_name, $doc) {
		$pattern = '/{block:('.$block_name.')}(.*?){\/block:\\1}/s';
		$matches = preg_match_all($pattern, $doc, $block);
		if ($matches && $block[2]) {
			return $block[2];
		}
	}
	
	public function render_post($post, $html) {
		$doc = '';
		$post_block = $this->get_block('Posts', $html);
		switch($post['Type']) {
			case 'Text':
				$doc = $this->render_text_post($post, $post_block, $html);
				break;
		}
		return $doc;
	}
	
	public function narrow_scope($scope, $block='') {
		$does_match = preg_match_all($this->blocks, $scope, $matcher);
		$doc = '';
				
		if ($does_match){
			foreach ($matcher[2] as $context) {
				$doc .= $this->narrow_scope($context);
			}
			return $doc;
		} else {
			return $scope;
			// return preg_replace($this->variables, $scope ,$scope);
		}
	}
	
	public function seek($context) {
		return preg_replace_callback($this->variables, array($this, 'convert_properties'), $context);
	}
	
	protected function convert_properties($match) {
		if (array_key_exists($match[1], $this->template)) {
			return $this->template[$match[1]];
		}
	}
	
	protected function render_text_post($post, $block, $document) {
		foreach ($block as $html) {
			$text = $this->get_block('Text', $html);
			foreach ($text as $text_block) {
				$post = preg_replace('/{Body}/', $post['Body'], $text_block);				
			} 
		}

	}
	
}


?>