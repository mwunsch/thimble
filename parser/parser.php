<?php

require_once 'spyc.php';
require_once 'simple_html_dom.php';

class ThimbleParser {
	
	protected $variables = '/{([A-Za-z][A-Za-z0-9\-]*)}/i';
	
	protected $blocks = '/{block:([A-Za-z][A-Za-z0-9]*)}(.*?){\/block:\\1}/is';
		
	public $type = '';
	
	public $defaults = array(
		'RSS'				=> '/rss',
		'Favicon' 			=> 'http://assets.tumblr.com/images/default_avatar_16.gif',
		'PortraitURL-16' 	=> "http://assets.tumblr.com/images/default_avatar_16.gif",
		'PortraitURL-24' 	=> "http://assets.tumblr.com/images/default_avatar_24.gif",
		'PortraitURL-30' 	=> "http://assets.tumblr.com/images/default_avatar_30.gif",
		'PortraitURL-40' 	=> "http://assets.tumblr.com/images/default_avatar_40.gif",
		'PortraitURL-48' 	=> "http://assets.tumblr.com/images/default_avatar_48.gif",
		'PortraitURL-64' 	=> "http://assets.tumblr.com/images/default_avatar_64.gif",
		'PortraitURL-96' 	=> "http://assets.tumblr.com/images/default_avatar_96.gif",
		'PortraitURL-128' 	=> "http://assets.tumblr.com/images/default_avatar_128.gif",
		'CopyrightYears'	=> '2007-2010',	
	);

  public $localization = array();
	
	public $template = array();	
		
	public function __construct($data = array(), $lang = array(), $type = 'index') {
		$this->type = $type;
		$this->template = array_merge($this->defaults, Spyc::YAMLLoad($data));
    $this->localization = Spyc::YAMLLoad($lang);
	}
	
	public function block_pattern($block_name) {
		return '/{block:('.$block_name.')}(.*?){\/block:\\1}/is';
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
	
	public function render_variable($name, $replacement, $block) {
		$block = preg_replace('/{'.$name.'}/i', $replacement, $block);
		$block = preg_replace('/{Plaintext'.$name.'}/i', htmlentities($replacement), $block);
		$block = preg_replace('/{JS'.$name.'}/i', json_encode($replacement), $block);
		$block = preg_replace('/{JSPlaintext'.$name.'}/i', json_encode(htmlentities($replacement)), $block);
		$block = preg_replace('/{URLEncoded'.$name.'}/i', urlencode($replacement), $block);		
		return $block;
	}
	
	public function render_post_variable($name, $post, $block) {
		return $this->render_variable($name, $post[$name], $block);
	}

  public function render_locale_string($key, $doc) {
    if (func_num_args() > 2) {
      $arguments = func_get_args();
      $string = vsprintf($this->localization[$key], array_slice($arguments, 2));
    } else {
      $string = $this->localization[$key];
    }
    $doc = $this->render_variable("lang:$key", $string, $doc);
    return $doc;
  }

	public function parse($document, $appearance_options = array()) {
		$doc = $document;
		
		// Generate Options from Meta tags
		$doc = $this->generate_meta($doc, $appearance_options);
		
		// Generate based on page type
		if ($this->type == 'index') {
			$doc = $this->build_index($doc);
		}

    // Localize
    $doc = $this->localize($doc);
		
		// render Global Blocks		
		if ($this->template['Description']) {
			$doc = $this->render_block('Description', $doc);
		}
		if ($this->template['Following']) {
			$doc = $this->render_following($this->template['Following'], $doc);
		} else {
			$doc = $this->strip_block('Following',$doc);
		}
		if ($this->template['AskLabel']) {
			$doc = $this->render_variable('AskLabel', $this->template['AskLabel'], $doc);
			$doc = $this->render_block('AskEnabled',$doc);
		} else {
			$doc = $this->strip_block('AskEnabled',$doc);
		}
		if ($this->template['SubmissionsEnabled']) {
			$doc = $this->render_block('SubmissionsEnabled',$doc);
		} else {
			$doc = $this->strip_block('SubmissionsEnabled',$doc);
		}	
		if ($this->template['Pages']) {
			$doc = $this->get_pages($this->template['Pages'],$doc);
		} else {
			$doc = $this->strip_block('HasPages',$doc);
		}
		if ($this->template['TwitterUsername']) {
			$doc = $this->render_block('Twitter',$doc);
			$doc = $this->render_variable('TwitterUsername', $this->template['TwitterUsername'], $doc);			
		} else {
			$doc = $this->strip_block('Twitter',$doc);
		}
		
		// Render remaining global variables;
		$doc = $this->seek($doc);
		// Cleanup additional blocks
		return $this->cleanup($doc);
	}
	
	public function generate_meta($document, $appearance = array()) {
		$dom = new simple_html_dom();
		@$dom->load($document);
    return $this->build_options($dom, $appearance);
  }
	
	public function build_options($dom, $meta_overrides = array()) {
    $meta_elements = $dom->find("meta[name]");
    $meta = array(
			'Color' => array(),
			'Font' => array(),
			'Boolean' => array(),
			'Text' => array(),
			'Image' => array()
		);
		foreach ($meta_elements as $element) {
			if (isset($element->content)) {
				$name = $element->name;
        $option = explode(':',$name);
        if (count($meta_overrides) > 0) {
          if (array_key_exists($name, $meta_overrides)) {
            $element->content = $meta_overrides[$name];
          } elseif (array_key_exists(str_replace(' ','_',$name), $meta_overrides)) {
            $element->content = $meta_overrides[str_replace(' ','_',$name)];
          } else {
            if ($element->content) {
              $element->content = '';
            }
          }
        }
        $content = $element->content;
				switch($option[0]) {
					case 'color':
						$meta['Color'][$option[1]] = $content;
						break;
					case 'font':
						$meta['Font'][$option[1]] = $content;
						break;
					case 'if':
						$meta['Boolean'][$option[1]] = $content;
						break;
					case 'text':
						$meta['Text'][$option[1]] = $content;
						break;
					case 'image':
						$meta['Image'][$option[1]] = $content;
						break;
				}
			}
		}
    return $this->parse_options($meta, $dom->save());
	}
	
	public function parse_options($options, $doc) {
		foreach ($options['Color'] as $name => $color) {
			$doc = $this->render_variable("color:$name", $color, $doc);
		}
		foreach ($options['Font'] as $name => $font) {
			$doc = $this->render_variable("font:$name", $font, $doc);
		}
		foreach ($options['Boolean'] as $name => $bool) {
			$block_name = implode(preg_split('/\s/',ucwords($name)));
			if ($bool) {
				$doc = $this->render_block("If$block_name",$doc);
				$doc = $this->strip_block("IfNot$block_name",$doc);
			} else {
				$doc = $this->render_block("IfNot$block_name",$doc);
				$doc = $this->strip_block("If$block_name",$doc);
			}
		}
		foreach ($options['Text'] as $name => $text) {
			$block_name = implode(preg_split('/\s/',ucwords($name)));
			if ($text) {
				$doc = $this->render_variable("text:$name", $text, $doc);
				$doc = $this->render_block("If$block_name",$doc);
				$doc = $this->strip_block("IfNot$block_name",$doc);
			} else {
				$doc = $this->render_block("IfNot$block_name",$doc);
				$doc = $this->strip_block("If$block_name",$doc);
			}
		}
		foreach ($options['Image'] as $name => $img) {
			$block_name = implode(preg_split('/\s/',ucwords($name)));
			if ($img) {
				$doc = $this->render_variable("image:$name", $img, $doc);
				$doc = $this->render_block('If'.$block_name.'Image',$doc);
				$doc = $this->strip_block('IfNot'.$block_name.'Image',$doc);
			} else {
				$doc = $this->render_block('IfNot'.$block_name.'Image',$doc);
				$doc = $this->strip_block('If'.$block_name.'Image',$doc);
			}
		}
		return $doc;
	}
	
	public function build_index($doc) {
		// probably should build these dynamically
		$pages = array(
			'NextPage'			=> '/page/2',
			'CurrentPage'		=> '1',
			'TotalPages'		=> '100'
		);
		$doc = $this->render_block('IndexPage', $doc);
		$doc = $this->render_pagination($pages, $doc);
		$doc = $this->get_posts($doc);
		return $doc;
	}
	
  public function localize($doc) {
    if (count($this->localization)) {
      foreach (array_keys($this->localization) as $key) {
        $doc = $this->render_locale_string($key, $doc);
      }
    }
    return $doc;
  }

	public function get_pages($pages, $document) {
		$html = $document;
		$has_page_block = preg_match_all($this->block_pattern('Pages'), $html, $matcher);
		$page_group = '';
		if ($has_page_block) {
			foreach ($pages as $page) {
				foreach ($matcher[2] as $page_block) {
					$page_block = $this->render_variable('Label', $page['Label'], $page_block);
					$page_block = $this->render_variable('URL', $page['URL'], $page_block);					
					$page_group .= $page_block;
				}
			}
		}
		$html = preg_replace($this->block_pattern('Pages'), $page_group, $html);
		$html = $this->render_block('HasPages', $html);
		return $html;
	}
	
	public function render_following($following, $document) {
		$html = $document;
		$has_following_block = preg_match_all($this->block_pattern('Followed'), $html, $matcher);
		$following_group = '';
		if ($has_following_block) {
			foreach ($following as $user) {
				foreach ($matcher[2] as $follows) {
					$follows = $this->render_variable('FollowedName', $user['Name'], $follows);
					$follows = $this->render_variable('FollowedTitle', $user['Title'], $follows);
					$follows = $this->render_variable('FollowedURL', $user['URL'], $follows);
					$portraits = array(
						'PortraitURL-16', 'PortraitURL-24', 'PortraitURL-30', 
						'PortraitURL-40', 'PortraitURL-48', 'PortraitURL-64',
						'PortraitURL-96', 'PortraitURL-128'
					);
					foreach ($portraits as $portrait) {
						$follows = $this->render_variable('Followed'.$portrait, $user[$portrait], $follows);
					}
					$following_group .= $follows;
				}
			}
		}
		
		$html = preg_replace($this->block_pattern('Followed'), $following_group, $html);
		$html = $this->render_block('Following', $html);
		return $html;
	}
	
	public function render_pagination($pages, $document) {
		$html = $document;
		if ($pages['NextPage'] || $pages['PreviousPage']) {
			if ($pages['NextPage']) {
				$html = $this->render_variable('NextPage', $pages['NextPage'], $html);
				$html = $this->render_block('NextPage', $html);
			} else {
				$html = $this->strip_block('NextPage', $html);
			}
			if ($pages['PreviousPage']) {
				$html = $this->render_variable('PreviousPage', $pages['PreviousPage'], $html);
				$html = $this->render_block('PreviousPage', $html);
			} else {
				$html = $this->strip_block('PreviousPage', $html);
			}			
			$html = $this->render_block('Pagination', $html);
		} else {
			$html = $this->strip_block('Pagination', $html);
		}

    // Jump Pagination. It's hard
    $html = preg_replace_callback(
      '/{block:(JumpPagination length="(\d+)")}(.*?){\/block:JumpPagination}/is',
      array($this,'render_jump_pagination'),
      $html
    );

		return $html;
	}

  public function render_jump_pagination($matches) {
    $length = $matches[2];
    $block = $matches[3];
    $index = 1;
    $current_page = 1;
    $html = '';

    while ($index <= $length) {
      if ($index == $current_page) {
        $html .= preg_replace_callback(
          $this->block_pattern('CurrentPage'),
          create_function(
            '$matches',
            '$pagination_block = $matches[2];
             $pagination_block = preg_replace("/{PageNumber}/i",'.($index).',$pagination_block);
             $pagination_block = preg_replace("/{URL}/i","/'.($index).'",$pagination_block);
             return $pagination_block;'
          ),
          $block
        );
      } else {
        $html .= preg_replace_callback(
          $this->block_pattern('JumpPage'),
          create_function(
            '$matches',
            '$pagination_block = $matches[2];
             $pagination_block = preg_replace("/{PageNumber}/i",'.($index).',$pagination_block);
             $pagination_block = preg_replace("/{URL}/i","/'.($index).'",$pagination_block);
             return $pagination_block;'
          ),
          $block
        );
      }
      $index++;
    }

    $html = $this->cleanup($html);

    return $html;
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
			if (($index+1) % 2) {
				$block = $this->render_block('Odd', $block);
			} else {
				$block = $this->render_block('Even', $block);
			}
			$block = $this->render_block('Post'.($index + 1),$block);
      $html .= $this->render_post($post, $block);
    }
    return $html;
	}
	
	public function render_post($post, $block) {
    $html = $this->prepare_post($post, $block);
		$post_type = $post['Type'];
    $markup = '';
		$pattern = $this->block_pattern($post_type);
		$does_match = preg_match_all($pattern, $html, $posts);
    if ($does_match) {
      foreach($posts[2] as $index => $post_block) {
        switch($post_type) {
        case 'Text':
          $markup = $this->render_text_post($post, $post_block);
          break;
        case 'Photo':
          $markup = $this->render_photo_post($post, $post_block);
          break;	
        case 'Quote':
          $markup = $this->render_quote_post($post, $post_block);
          break;
        case 'Link':
          $markup = $this->render_link_post($post, $post_block);
          break;
        case 'Chat':
          $markup = $this->render_chat_post($post, $post_block);
          break;
        case 'Audio':
          $markup = $this->render_audio_post($post, $post_block);
          break;
        case 'Video':
          $markup = $this->render_video_post($post, $post_block);
          break;	
        case 'Answer':
          $markup = $this->render_answer_post($post, $post_block);
          break;
        }
      }
    }
    if ($markup) {
      $html = preg_replace($pattern, $markup, $html);
    }
    return $html;
	}
	
	public function prepare_post($post, $markup) {
		$block = $markup;
		$block = $this->render_post_variable('Permalink', $post, $block);
		$block = $this->render_post_variable('PostId', $post, $block);
		$block = $this->render_post_date($post, $block);
		if ($post['Tags']) {
			$block = $this->render_tags_for_post($post, $block);
		}
		
		if ($post['NoteCount']) {
			$block = $this->render_post_variable('NoteCount', $post, $block);
			$block = $this->render_block('NoteCount', $block);
			$block = $this->render_variable('NoteCountWithLabel', $post['NoteCount']." notes", $block);
		} else {
			$block = $this->strip_block('NoteCount',$block);
		}
		
		if ($post['Reblog']) {
			$block = $this->render_reblog_info($post, $block);
    } else {
      $block = $this->render_block('NotReblog', $block);
    }

    if ($post['ContentSource']) {
      $block = $this->render_content_source($post, $block);
    }
		
		$block = $this->render_block('More', $block);
		return $block;
	}
	
	public function strip_block($name, $html) {
		return preg_replace($this->block_pattern($name), '', $html);
	}
	
	public function seek($context) {
		preg_match_all($this->variables, $context, $match);
		foreach ($match[1] as $variable) {
			if (array_key_exists($variable, $this->template)) {
				$context = $this->render_variable($variable, $this->template[$variable], $context);
			} else {
				$context = $this->render_variable($variable, '', $context);
			}
		}
		return $context;
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
		
		$html = $this->render_post_variable('Timestamp', $post, $html);
		$html = $this->render_variable('TimeAgo', $day_difference." days ago", $html);
		$html = $this->render_variable('DayOfMonth', strftime('%e',$time), $html);
		$html = $this->render_variable('DayOfMonthWithZero', strftime('%d',$time), $html);
		$html = $this->render_variable('DayOfWeek', strftime('%A',$time), $html);
		$html = $this->render_variable('ShortDayOfWeek', strftime('%a',$time), $html);
		$html = $this->render_variable('DayOfWeekNumber', strftime('%u',$time), $html);
		$html = $this->render_variable('DayOfYear', strftime('%j',$time), $html);		
		$html = $this->render_variable('WeekOfYear', strftime('%V',$time), $html);		
		$html = $this->render_variable('Month', strftime('%B',$time), $html);
		$html = $this->render_variable('ShortMonth', strftime('%b',$time), $html);
		$html = preg_replace('/{MonthNumber}|{MonthNumberWithZero}/i', strftime('%m',$time), $html);
		$html = $this->render_variable('Year', strftime('%Y',$time), $html);
		$html = $this->render_variable('ShortYear', strftime('%y',$time), $html);		
		$html = $this->render_variable('AmPm', strftime('%P',$time), $html);
		$html = $this->render_variable('CapitalAmPm', strftime('%p',$time), $html);
		$html = $this->render_variable('12Hour', strftime('%l',$time), $html);
		$html = $this->render_variable('12HourWithZero', strftime('%I',$time), $html);
		$html = preg_replace('/{24Hour}|{24HourWithZero}/i', strftime('%H',$time), $html);
		$html = $this->render_variable('Minutes', strftime('%M',$time), $html);
		$html = $this->render_variable('Seconds', strftime('%S',$time), $html);

    if ($post['Reblog']) {
      $html = $this->render_locale_string('Reblogged TimeAgo from ReblogParentName',$html, $day_difference." days ago", $post['Reblog']['ReblogParentName']);
    }

		$html = $this->render_block('Date', $html);
		return $html;
	}
	
	protected function render_tags_for_post($post, $block) {
		$html = $block;
		$tags = $post['Tags'];
		$has_tag_block = preg_match_all($this->block_pattern('Tags'), $html, $matcher);
		$tag_group = '';		
		if ($has_tag_block) {
			foreach ($tags as $tag) {
				$safe_tag = preg_replace('/\s/','_',strtolower($tag));
				foreach ($matcher[2] as $tag_block) {
					$tag_block = $this->render_variable('Tag', $tag, $tag_block);
					$tag_block = $this->render_variable('URLSafeTag', $safe_tag, $tag_block);
					$tag_block = preg_replace('/{TagURL}|{TagURLChrono}/i', "/tagged/".$safe_tag, $tag_block);					
					$tag_group .= $tag_block;
				}
			}
		}
		$html = $this->render_block('HasTags', $html);
		$html = preg_replace($this->block_pattern('Tags'), $tag_group, $html);
		return $html;
	}
	
	protected function render_reblog_info($post, $block) {
		$html = $block;
		$reblog = $post['Reblog'];
		$root = $reblog['Root'];
		
		$html = $this->render_post_variable('ReblogParentName', $reblog, $html);
		$html = $this->render_post_variable('ReblogParentTitle', $reblog, $html);
		$html = $this->render_post_variable('ReblogParentURL', $reblog, $html);
		$portraits = array(
			'ReblogParentPortraitURL-16', 'ReblogParentPortraitURL-24', 'ReblogParentPortraitURL-30', 
			'ReblogParentPortraitURL-40', 'ReblogParentPortraitURL-48', 'ReblogParentPortraitURL-64',
			'ReblogParentPortraitURL-96', 'ReblogParentPortraitURL-128'
		);
		foreach($portraits as $portrait) {
			$html = $this->render_post_variable($portrait, $reblog, $html);
		}
		$html = $this->render_block('Reblog', $html);
		$html = $this->render_block('RebloggedFrom', $html);
		
		if ($root) {
			$html = $this->render_post_variable('ReblogRootName', $root, $html);
			$html = $this->render_post_variable('ReblogRootTitle', $root, $html);
			$html = $this->render_post_variable('ReblogRootURL', $root, $html);
			$root_portraits = array(
				'ReblogRootPortraitURL-16', 'ReblogRootPortraitURL-24', 'ReblogRootPortraitURL-30', 
				'ReblogRootPortraitURL-40', 'ReblogRootPortraitURL-48', 'ReblogRootPortraitURL-64',
				'ReblogRootPortraitURL-96', 'ReblogRootPortraitURL-128'
			);
			foreach($root_portraits as $portrait) {
				$html = $this->render_post_variable($portrait, $root, $html);
			}
			$html = $this->render_block('RebloggedFromReblog', $html);
		}
		
		return $html;
	}

  protected function render_content_source($post, $block) {
    $source = $post['ContentSource'];
    $logo = $source['SourceLogo'];

    $block = $this->render_post_variable('SourceURL', $source, $block);
    $block = $this->render_post_variable('SourceTitle', $source, $block);

    if ($logo) {
      $block = $this->render_post_variable('BlackLogoURL', $logo, $block);
      $block = $this->render_post_variable('LogoWidth', $logo, $block);
      $block = $this->render_post_variable('LogoHeight', $logo, $block);
      $block = $this->render_block('SourceLogo', $block);
    } else {
      $block = $this->render_block('NoSourceLogo', $block);
    }

    $block = $this->render_block('ContentSource', $block);

    return $block;
  }
	
	protected function render_text_post($post, $block) {
		$html = '';
		$html = $this->render_post_variable('Body', $post, $block);
		if ($post['Title']) {
			$html = $this->render_post_variable('Title', $post, $html);
			$html = $this->render_block('Title', $html);
		} else {
			$html = $this->strip_block('Title',$html);
		}
		return $html;
	}
	
	protected function render_quote_post($post, $block) {
		$html = '';
		$html = $this->render_post_variable('Quote', $post, $block);
		$html = $this->render_post_variable('Length', $post, $html);
		if ($post['Source']) {
			$html = $this->render_post_variable('Source', $post, $html);
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
			$html = $this->render_post_variable($size, $post, $html);
		}
		if ($post['Caption']) {
			$html = $this->render_post_variable('Caption', $post, $html);
			$html = $this->render_variable('PhotoAlt', strip_tags($post['Caption']), $html);
			$html = $this->render_block('Caption', $html);
		} else {
			$html = $this->strip_block('Caption',$html);
		}
		if ($post['PhotoURL-HighRes']) {
			$html = $this->render_post_variable('PhotoURL-HighRes', $post, $html);
			$html = $this->render_block('HighRes', $html);
		} else {
			$html = $this->strip_block('HighRes',$html);
		}
		if ($post['LinkURL']) {
			$html = $this->render_post_variable('LinkURL', $post, $html);
			$html = $this->render_variable(
				'LinkOpenTag', 
				'<a href="'.$post['LinkURL'].'">', 
				$html
			);
			$html = $this->render_variable('LinkCloseTag', '</a>', $html);
		}
		return $html;
	}
	
	protected function render_link_post($post, $block) {
		$html = '';
		$html = $this->render_post_variable('URL', $post, $block);
		if ($post['Name']) {
			$html = $this->render_post_variable('Name', $post, $block);
		} else {
			$html = $this->render_variable('Name', $post['URL'], $html);
		}
		if ($post['Description']) {
			$html = $this->render_post_variable('Description', $post, $html);
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
						if (($index+1) % 2) {
							$alt = 'odd';
						} else {
							$alt = 'even';
						}
						$line_markup .= $this->render_variable('Line', $line, $each_line);
						$line_markup = $this->render_variable('Alt', $alt, $line_markup);
						$line_markup = $this->render_variable('Label', $label, $line_markup);
						$line_markup = $this->render_block('Label', $line_markup);
					}
				}
			}
		}
		$html = preg_replace($this->block_pattern('Lines'), $line_markup, $block);
		if ($post['Title']) {
			$html = $this->render_post_variable('Title', $post, $html);
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
			$html = $this->render_post_variable('ExternalAudioURL', $post, $html);
			$html = $this->render_block('ExternalAudio', $html);
		} else {
			$html = $this->strip_block('ExternalAudio', $html);
		}
		$html = $this->render_variable('AudioPlayer', $this->create_audio_player($audio_file,'black'), $html);
		$html = $this->render_variable('AudioPlayerBlack', $this->create_audio_player($audio_file,'black'), $html);
		$html = $this->render_variable('AudioPlayerWhite', $this->create_audio_player($audio_file), $html);
		$html = $this->render_variable('AudioPlayerGrey', $this->create_audio_player($audio_file, 'grey'), $html);
		
		$html = $this->render_post_variable('PlayCount', $post, $html);
		$html = $this->render_variable('FormatPlayCount', number_format($post['PlayCount']), $html);
		$html = $this->render_variable('PlayCountWithLabel', number_format($post['PlayCount'])." plays", $html);
		
		if ($post['Caption']) {
			$html = $this->render_post_variable('Caption', $post, $html);
			$html = $this->render_block('Caption', $html);
		} else {
			$html = $this->strip_block('Caption', $html);
		}
		if ($post['AlbumArtURL']) {
			$html = $this->render_post_variable('AlbumArtURL', $post, $html);
			$html = $this->render_block('AlbumArt', $html);
		} else {
			$html = $this->strip_block('AlbumArt', $html);
		}
		if ($post['Artist']) {
			$html = $this->render_post_variable('Artist', $post, $html);
			$html = $this->render_block('Artist', $html);
		} else {
			$html = $this->strip_block('Artist', $html);
		}
		if ($post['Album']) {
			$html = $this->render_post_variable('Album', $post, $html);
			$html = $this->render_block('Album', $html);
		} else {
			$html = $this->strip_block('Album', $html);
		}
		if ($post['TrackName']) {
			$html = $this->render_post_variable('TrackName', $post, $html);
			$html = $this->render_block('TrackName', $html);
		} else {
			$html = $this->strip_block('TrackName', $html);
		}
		
		return $html;		
	}
	
	protected function render_video_post($post, $block) {
		$html = $block;
		$html = $this->render_post_variable('Video-500', $post, $html);
		$html = $this->render_post_variable('Video-400', $post, $html);
		$html = $this->render_post_variable('Video-200', $post, $html);
		if ($post['Caption']) {
			$html = $this->render_post_variable('Caption', $post, $html);
			$html = $this->render_block('Caption', $html);
		} else {
			$html = $this->strip_block('Caption',$html);
		}
		return $html;
	}
	
	protected function render_answer_post($post, $block) {
		$html = $block;
		$html = $this->render_post_variable('Question', $post, $html);
		$html = $this->render_post_variable('Answer', $post, $html);
		$html = $this->render_post_variable('Asker', $post, $html);
		$asker_portraits = array(
			'AskerPortraitURL-16', 'AskerPortraitURL-24', 'AskerPortraitURL-30', 
			'AskerPortraitURL-40', 'AskerPortraitURL-48', 'AskerPortraitURL-64',
			'AskerPortraitURL-96', 'AskerPortraitURL-128'
		);
		foreach($asker_portraits as $portrait) {
			$html = $this->render_post_variable($portrait, $post, $html);
		}
    $html = $this->render_locale_string('Asked by Asker', $html, $post['Asker']);
    $html = $this->render_locale_string('Asker asked', $html, $post['Asker']);
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
		
}

?>
