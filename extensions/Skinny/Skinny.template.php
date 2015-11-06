<?php
/**
 * SkinnyTemplate provides a bit of flair to good old BaseTemplate. Use it to create
 * more awesome skins. Use the companion extensions like #movetoskin and #skintemplate 
 * move content from your wikitext to your skin, and to safely render php templates in your 
 * wikitext for easily and safely adding advanced forms, javascript, and so on.
 *
 * It extracts all the usual MediaWiki skin html soup into re-usable template files in the 
 * template directory, and introduces add() and insert() as methods for handling content
 * display.
 *
 * Check out the documentation at http://mediawiki.net/wiki/Extension:Skinny
 */
abstract class SkinnyTemplate extends BaseTemplate {

	//core settings
	protected $defaults = array(
		'debug' => 'false',
		'main template' => 'main',
		'template paths' => array(),

		'show title'=> true,
		'show tagline' => true,

		'breadcrumbs'=>array(
			'enabled'=>true,
			'zone' => 'prepend:title'
		)
	);

	//a stack of new defaults, added to by child objects
	//this ensures child defaults overwrite their parents
	protected $_defaults = array();

	protected $options = array();

	protected $_template_paths = array();

	public function __construct( $options=array() ){
		parent::__construct();
		$this->mergeDefaults();
		$this->setOptions( $options );
		//adding path manually ensures that there's an entry for every class in the heirarchy
		//allowing for template fallback to every skin all the way down
		$this->addTemplatePath( dirname(__FILE__).'/templates' );
		
		foreach($this->options['template paths'] as $path){
			$this->addTemplatePath($path);
		}
	}

	public function mergeDefaults(){
		if(!empty($this->_defaults)){
			//merge all defaults in, starting from the most recently added
			//this means children's defaults override their parents
			while($defaults = array_pop($this->_defaults) ){
				$this->defaults = $this->mergeOptionsArrays( $this->defaults, $defaults ); 
			}
		}
	}

	//recursively merge arrays, but if there are key conflicts,
	//overwrite from right to left
	public function mergeOptionsArrays($left, $right){
		return Skinny::mergeOptionsArrays($left, $right);
	}

	public function setOptions($options, $reset=false){
		if( $reset || empty($this->options) ){
			//set all options to their defaults
			$this->options = $this->defaults;
		}
		$this->options = $this->mergeOptionsArrays($this->options, $options);
	}

	public function setDefaults( $defaults ){
		$this->_defaults[] = $defaults;
	}


	public function addTemplatePath($path){
		if(file_exists($path) && is_dir($path)){
			array_unshift( $this->_template_paths, $path);
		}
	}

	/**
	 * The place to initialize all content areas. Overwrite this in your skin.
	 */
	abstract protected function initialize();

	/**
	 * This is called by MediaWiki to render the skin.
	 */
	final function execute() {
		//parse content first, to allow for any ADDTEMPLATE items
		$content = $this->parseContent($this->data['bodytext']);
		//set up standard content zones
		//head element (including opening body tag)
		$this->addHTML('head', $this->data[ 'headelement' ]);
		//the logo image defined in LocalSettings
		$this->addHTML('logo', $this->data['logopath']);
		//the article title 
		if($this->options['show title']){
			$this->addHTML('content-container.class', 'has-title');
			$this->addTemplate('title', 'title', array(
				'title'=>$this->data['title']
			));
		}
		//article content
		$this->addHTML('content', $content);
		//the site notice
		if( !empty($this->data['sitenotice'])){
			$this->addTemplate('notice', 'site-notice', array(
				'notice'=>$this->data['sitenotice']
			));
		}
		//the site tagline, if there is one
		if($this->options['show tagline']){
			$this->addHTML('content-container.class', 'has-tagline');
			$this->addTemplate('tagline', 'tagline', array(
				'tagline'=>wfMsg('tagline')
			));
		}
		$this->addHook('breadcrumbs', 'breadcrumbs');

		//the contents of Mediawiki:Sidebar
		$this->addTemplate('classic-sidebar', 'classic-sidebar', array(
			'sections'=>$this->data['sidebar']
		));
		//list of language variants
		$this->addTemplate('language variants', 'language-variants', array(
			'variants'=>$this->data['language_urls']
		));

		//page footer
		$this->addTemplate('footer', 'footer', array(
			'icons'=>$this->getFooterIcons( "icononly" ), 
			'links'=>$this->getFooterLinks( "flat" )
		));
		//mediawiki needs this to inject script tags after the footer
		$this->addHook('after:footer', 'afterFooter');

		
		$this->data['pageLanguage'] = $this->getSkin()->getTitle()->getPageViewLanguage()->getCode();

		//allow skins to set up before render
		$this->initialize();


		echo $this->renderTemplate($this->options['main template']);

	}



	public function renderTemplate($template, $args=array()){
		ob_start();
		extract($args);
		if($this->options['debug']===true){
			echo '<div class="skinny-debug">Skinny:Template: '.$template.'</div>';
		}
		//try all defined template paths
		$exists = false;
		foreach($this->_template_paths as $path){
			$filepath = $path.'/'.$template.'.tpl.php';
			if( file_exists($filepath) ){
				$exists = true;
				require( $filepath );
				break; //once we've rendered a template, stop traversing template_paths
			}
		}
		if($exists){
			$html = ob_get_clean();
		}else{
			$html = 'Template file `'.$template.'.tpl.php` not found!';
		}
		return $html;
	}

	/**
	 * Add the result of a function callback to a zone
	 *
	 * 	eg.	add('before:content', array('methodName', $obj))
	 *			add('before:content', 'methodOnThisObject')
	 */
	public function addHook($zone, $hook, $args=array()){
		if(!isset($this->content[$zone])){
			$this->content[$zone] = array();
		}
		//allow just a string reference to a method on this skin object
		if(!is_array($hook) && method_exists($this, $hook)){
			$hook = array($hook, $this);
		}else{
			return false;
		}
		$this->content[$zone][] = array('type'=>'hook', 'hook'=>$hook, 'arguments'=>$args);
	}

	/**
	 * Render the output of a template to a zone
	 *
	 * eg.  add('before:content', 'template-name')
	 */
	public function addTemplate($zone, $template, $params=array()){
		if(!isset($this->content[$zone]))
			$this->content[$zone] = array();
		$this->content[$zone][] = array('type'=>'template', 'template'=>$template, 'params'=>$params);
	}

	/**
	 * Add html content to a zone
	 *
	 * eg.  add('before:content', '<h2>Some html content</h2>')
	 */
	public function addHTML($zone, $content){
		if(!isset($this->content[$zone]))
			$this->content[$zone] = array();
		$this->content[$zone][] = array('type'=>'html', 'html'=>$content);
	}

	/**
	 * Add a zone to a zone. Allows adding zones without editing template files.
	 *
	 * eg.  add('before:content', 'zone name')
	 */
	public function addZone($zone, $name, $params=array()){
		if(!isset($this->content[$zone]))
			$this->content[$zone] = array();
		$this->content[$zone][] = array('type'=>'zone', 'zone'=>$name, 'params'=>$params);
	}

	/**
	 * Convenience template method for <?php echo $this->render() ?>
	 */
	function insert($zone, $args=array()){
		echo $this->render($zone, $args);
	}
	function before($zone, $args=array()){
		$this->insert('before:'.$zone, $args);
	}
	function after($zone, $args=array()){
		$this->insert('after:'.$zone, $args);
	}
	function prepend($zone, $args=array()){
		$this->insert('prepend:'.$zone, $args);
	}
	function append($zone, $args=array()){
		$this->insert('append:'.$zone, $args);
	}
	function attach($zone, $args=array()){
		$this->prepend($zone, $args);
		$this->insert($zone, $args);
		$this->append($zone, $args);
	}
	/**
	 * Transclude a MediaWiki page
	 */
	function transclude($string){
		echo $GLOBALS['wgParser']->parse('{{'.$string.'}}', $this->getSkin()->getRelevantTitle(), new ParserOptions)->getText();
	}

	/**
	 * Run content content, optionally passing arguments to provide to
	 * object methods
	 */
	protected function render($zone, $args=array()){
		$sep = isset($args['seperator']) ? $args['seperator'] : ' ';

		$content = '';
		if(isset($this->content[$zone])){
			foreach($this->content[$zone] as $item){
				if($this->options['debug']===true){
					$content.='<!--Skinny:Template: '.$template.'-->';
				}
				switch($item['type']){
					case 'hook':
						//method will be called with two arrays as arguments
						//the first is the args passed to this method (ie. in a template call to $this->insert() )
						//the second are the args passed when the hook was bound
						$content .= call_user_func_array(array($item['hook'][1], $item['hook'][0]), array($args, $item['arguments']));
						break;
					case 'html':
						$content .= $sep . (string) $item['html'];
						break;
					case 'template':
						$content .= $this->renderTemplate($item['template'], $item['params']);
						break;
					case 'zone':
						$content .= $this->render($item['zone'], $item['params']);
						break;
				}	

			}
		}
		//content from #movetoskin and #skintemplate
		if( Skinny::hasContent($zone) ){
			foreach(Skinny::getContent($zone) as $item){

				//pre-rendered html from #movetoskin
				if(isset($item['html'])){
					if($this->options['debug']===true){
						$content.='<!--Skinny:MoveToSkin: '.$template.'-->';
					}
					$content .= $sep . $item['html'];
				}
				else
				//a template name to render
				if(isset($item['template'])){
					if($this->options['debug']===true){
						$content.='<!--Skinny:Template (via #skintemplate): '.$item['template'].'-->';
					}
					$content .= $this->renderTemplate( $item['template'], $item['params'] );
				}
			}
		}
		return $content;
	}


  //parse the bodytext and insert any templates added by the skintemplate parser function
  public function parseContent( $html ){
    $pattern = '~<p>ADDTEMPLATE\(([\w_:-]*)\):([\w_-]+):ETALPMETDDA<\/p>~m';
    if( preg_match_all($pattern, $html, $matches, PREG_SET_ORDER) ){
      foreach($matches as $match){
      	//if a zone is specified, attach the template
      	if(!empty($match[1])){
      		$this->addTemplate($match[1], $match[2]);
      		$html = str_replace($match[0], '', $html);
        }else{
        //otherwise inject the template inline into the wikitext
        	$html = str_replace($match[0], $this->renderTemplate($match[2]), $html);
      	}
      }
    }
    return $html;
  }

  /* 
  Convert a MediaWiki:message into a navigation structure
  Builds on Skin::addToSidebar to move all headerless entries into the primary navigation*/
  protected function processNavigationFromMessage( $message_name ){
  	$nav = array();
  	$this->getSkin()->addToSidebar($nav, $message_name);

  	return $nav;
  }

  
  protected function afterFooter(){
		ob_start();
		$this->printTrail();
		return ob_get_clean();
	}

	/* Render the category heirarchy as breadcrumbs */
	protected function breadcrumbs() {
      
    // get category tree
    $parenttree = $this->getSkin()->getTitle()->getParentCategoryTree();
    $rendered = $this->getSkin()->drawCategoryBrowser( $parenttree );
    /*echo '<pre>';
    print_r($parenttree);
    print_r($rendered);
    echo '</pre>';*/
    //exit;
    // Skin object passed by reference cause it can not be
    // accessed under the method subfunction drawCategoryBrowser
    $temp = explode( "\n", $rendered );
    unset( $temp[0] );
    asort( $temp );

    if (empty($temp)) {
        return '';
    }
    $trees = array();
    foreach ($temp as $line) {
    	preg_match_all('~<a[\S\s]+?</a>~', $line, $matches);
    	$trees[] = $matches[0];
    }

    return $this->renderTemplate('breadcrumbs', array('trees' => $trees) );
  }



} // end of class

