<?php

class Parser {
	
	protected $variables = '/{([A-Za-z][A-Za-z0-9]*)}/';
	
	protected $blocks = '';
	
	public $template = array();
	
	public $defaults = array(
		'Title' => 'Hello world'
	);
	
	public $properties = array();
	
	public function __construct($template = array()) {
		$this->template = array_merge($this->defaults, $template);
	}
	
	public function parse($document) {
		preg_match_all($this->variables, $document, $matches);
		$this->properties = $this->map($matches[1]);
		return preg_replace_callback($this->variables, array($this, 'replace'), $document);
	}
	
	public function map($match) {
		$properties = array();
		foreach	($match as $property) {
			if (in_array($property, array_keys($this->template))) {
				$properties[$property] = $this->template[$property];
			}
		}
		return $properties;		
	}
	
	public function replace($matches) {
		if (array_key_exists($matches[1], $this->properties)) {
			return $this->properties[$matches[1]];
		}
	}
	
}


?>