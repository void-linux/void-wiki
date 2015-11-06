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
class SkinnySlim {

	protected $settings = array(
		'debug' => false,
		'auto_intialize' => true
	);

	public $options = array();

	protected $_template_paths = array();

	public function __construct( $options ){
		
		//set options
		$options = $this->options = array_merge($this->settings, $this->options, $options);

		if( isset($options['template_path']) ){
			$this->addTemplatePath( $options['template_path'] );
		}
		if( $options['auto_intialize'] === true ){
			$this->initialize();
		}

	}

	protected function addTemplatePath($path){
		if(file_exists($path) && is_dir($path)){
			array_unshift( $this->_template_paths, $path);
		}
	}

	/**
	 * The place to initialize all content areas. Overwrite this in your skin.
	 */
	protected function initialize(){}

	/**/
	protected function _beforeRender(){

	}

	public function render($template, $args=array()){
		$this->_beforeRender();
		ob_start();
		extract($args);
		if($this->options['debug']===true){
			echo '<div class="skinny-debug">Skinny:Template: '.print_r($template, true).'</div>';
		}
		if(count($this->_template_paths) < 1){
			throw Exception('No template paths set.');
		}
		//try all defined template paths
		foreach($this->_template_paths as $path){
			$filepath = $path.'/'.$template.'.tpl.php';
			if( file_exists($filepath) ){
				require( $filepath );
				break; //once we've rendered a template, stop traversing template_paths
			}
		}
		return ob_get_clean();
	}

	/**
	 * Add html content to a specific hook point
	 * or assign an object method which returns html, it will be run at render time
	 *
	 * eg.  add('before:content', '<h2>Some html content</h2>')
	 * 			add('before:content', array('methodName', $obj))
	 */
	public function addHook($place, $hook, $args=array()){
		if(!isset($this->content[$place])){
			$this->content[$place] = array();
		}
		//allow just a string reference to a method on this skin object
		if(!is_array($hook) && method_exists($this, $hook)){
			$hook = array($hook, $this);
		}
		$this->content[$place][] = array('type'=>'hook', 'hook'=>$hook, 'arguments'=>$args);
	}

	/**
	 * Add html content to a specific hook point
	 * or assign an object method which returns html, it will be run at render time
	 *
	 * eg.  add('before:content', 'template-name')
	 */
	public function addTemplate($place, $template, $params=array()){
		if(!isset($this->content[$place]))
			$this->content[$place] = array();
		$this->content[$place][] = array('type'=>'template', 'template'=>$template, 'params'=>$params);
	}

		/**
	 * Add html content to a specific hook point
	 * or assign an object method which returns html, it will be run at render time
	 *
	 * eg.  add('before:content', '<h2>Some html content</h2>')
	 */
	public function addHTML($place, $content){
		if(!isset($this->content[$place]))
			$this->content[$place] = array();
		$this->content[$place][] = array('type'=>'html', 'html'=>$content);
	}

	/**
	 * Convenience template method for <?php echo $this->get() ?>
	 */
	function insert($place, $args=array()){
		echo $this->get($place, $args);
	}
	/**
	 * Convenience template method for <?php echo $this->get('before:') ?>
	 */
	function before($place, $args=array()){
		$this->insert('before:'.$place, $args);
	}
	/**
	 * Convenience template method for <?php echo $this->get('after:') ?>
	 */
	function after($place, $args=array()){
		$this->insert('after:'.$place, $args);
	}
	/**
	 * Convenience template method for <?php echo $this->get('prepend:') ?>
	 */
	function prepend($place, $args=array()){
		$this->insert('prepend:'.$place, $args);
	}
	/**
	 * Convenience template method for <?php echo $this->get('append:') ?>
	 */
	function append($place, $args=array()){
		$this->insert('append:'.$place, $args);
	}

	/**
	 * Run content content, optionally passing arguments to provide to
	 * object methods
	 */
	protected function get($place, $args=array()){
		$sep = isset($args['seperator']) ? $args['seperator'] : ' ';

		$content = '';
		if(isset($this->content[$place])){
			foreach($this->content[$place] as $item){
				if($this->options['debug']===true){
					$content.='<!--Skinny:Place: '.$place.'-->';
				}
				switch($item['type']){
					case 'hook':
						//method will be called with two arrays as arguments
						//the first is the args passed to this method (ie. in a template call to $this->insert() )
						//the second are the args passed when the hook was bound
						$content .= call_user_method_array($item['hook'][0], $item['hook'][1], array($args, $item['arguments']));
						break;
					case 'html':
						$content .= $sep . (string) $item['html'];
						break;
					case 'template':
						$content .= $this->render($item['template'], $item['params']);
						break;
				}	

			}
		}
		//content from #movetoskin and #skintemplate
		/*if( Skinny::hasContent($place) ){
			foreach(Skinny::getContent($place) as $item){

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
					$content .= $this->render( $item['template'], $item['params'] );
				}
			}
		}*/
		return $content;
	}



} // end of class

