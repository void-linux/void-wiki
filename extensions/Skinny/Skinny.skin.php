<?php

class SkinSkinny extends SkinTemplate{

	/**
	 * A register of valid skin layouts: key=>config
	 */
	protected static $layouts = array();
	
	/**
	 * An array of modules to be loaded by ResourceLoader
	 */
	protected static $modules = array();

	/**
	 * An array of modules keys which should be loaded when the template is initialized
	 */
	protected static $autoloadModules = array();


	/**
	 * An array of modules to be loaded by ResourceLoader
	 */
	protected static $template_paths = array();
	
	
	/**
	 * Boolean to track whether ResourceLoader modules have been added
	 */
	protected static $_modulesRegistered = false;


	/**
	 * Default option values
	 */
	public $defaults = array(
		'layout'=>'default'
	);
	public $options = array();


	/**
	 * Register resources with the ResourceLoader. 
	 * 
	 * Handler for Hook: ResourceLoaderRegisterModules hook.
	 */
	public static function ResourceLoaderRegisterModules( ResourceLoader $rl ){
		self::$_modulesRegistered = true;
		$rl->register( self::$modules );
    return true;
	}

	/**
	 * Can be used to init a skin before the Skin is instantiated
 * eg. to define resources.
	 */
	public static function init(){

	}


	function __construct( $options=array() ){
		$this->setOptions( $options );

		/*if(isset(Skinny::$skinLayout)){
			$this->options['layout'] = Skinny::$skinLayout;
		}*/

		$layout = $this->layout = self::$layouts[ $this->options['layout'] ];
		//allow a layout to provide a custom template class
		if( isset($layout['templateClass']) ){
			$this->template = $layout['templateClass'];
		}

	}

	public function setOptions( $options, $reset=false ){
		if( $reset || empty($this->options) ){
			//set all options to their defaults
			$this->options = $this->defaults;
		}
		$this->options = Skinny::mergeOptionsArrays( $this->options, $options );
	}

	/**
	  * Load required modules with ResourceLoader
	  */ 
	public function initPage( OutputPage $out ){

		$loadModules = array();
		if( isset( $this->layout['modules'] ) ){
			$loadModules += array_keys( $this->layout['modules'] );
		}

		$layout = $this->layout;
		while( isset($layout['extends']) ){
			$layout = self::$layouts[ $layout['extends'] ];
			if(!empty($layout['modules'])){
				$loadModules += array_keys($layout['modules']); 
			}
		}

		$loadModules += self::$autoloadModules;

		foreach( $loadModules as $name ){
			$out->addModules($name);
		}

		//echo '<pre>'; print_r($out->getModules(true));
	} 

	/**
	 * Hooking into the template setup process to provide a custom template
	 * and ensure it's initialized with the options it needs.
	 */
	public function setupTemplate( $classname, $repository = false, $cache_dir = false ) {
		$this->layout = self::$layouts[ $this->options['layout'] ];	
		//allow current layout to specify a different template class
		$classname = isset($this->layout['templateClass']) ? $this->layout['templateClass'] : $classname;
		$options = array();
		if( isset($this->layout['templateOptions']) ){
			$options += $this->layout['templateOptions'];
		}
		//instantiate template with the skin options
		$tpl = new $classname( $options );
		//ensure that all template paths registered to this skin are added to the template
		//this allows overriding templates without having to create a new template class
		foreach(self::$template_paths as $path){
			$tpl->addTemplatePath($path);
		}
		return $tpl;
	}

	/**
	 * Called by OutputPage to provide opportunity to add to body attrs
	 */
	public function addToBodyAttributes( $out, &$attrs){
		$classes = array();
		$layout = $this->layout;
		//print_r($layout); exit;
		$attrs['class'] .= ' sitename-'.strtolower(str_replace(' ','_',$GLOBALS['wgSitename']));
		while( isset($layout['extends']) ){
			$layout = self::$layouts[ $layout['extends'] ];
			$classes[] = 'layout-'.$layout['name']; 
		}

		$classes[] = 'layout-'.$this->layout['name'];

		if( $GLOBALS['wgUser']->isLoggedIn() ){
			$classes[] = 'user-loggedin';
		}else{
			$classes[] = 'user-anonymous';
		}

		$attrs['class'] .= ' '.implode(' ',$classes);

	}

	/**
	 * Add a new skin layout for this skin
	 */
	public static function addLayout($name, $config=array()){
		if( isset($config['modules']) && !empty($config['modules']) ){
			self::addModules($config['modules']);
		}else{
			$config['modules'] = array();
		}
		$config['name'] = $name;

		self::$layouts[$name] = $config;
	}

	/**
	 * Set the layout config
	 */
	public static function setLayoutOptions( $name, $options ){
		if( !isset(self::$layouts[$name]) ){
			return;
		}
		if( isset($options['modules']) ){
			self::addModules( $options['modules'] );
		}
		self::$layouts[$name] = Skinny::mergeOptionsArrays( self::$layouts[$name], $options );
	}

	/**
	 * Set the options which will be passed to the layout's TemplateClass
	 */
	public static function setLayoutTemplateOptions( $name, $options ){
		if(!isset(self::$layouts[$name])){
			return;
		}
		if(!isset(self::$layouts[$name]['templateOptions'])){
			self::$layouts[$name]['templateOptions'] = array();
		}
		self::$layouts[$name]['templateOptions'] = Skinny::mergeOptionsArrays( self::$layouts[$name]['templateOptions'], $options );
	}


	/**
	 * Create a new layout which inherits from an existing layout
	 */
	public static function extendLayout($extend, $name, $config=array()){
		$config['extends'] = $extend;
		self::addLayout($name, $config);
	}


	public static function addTemplatePath( $path ){
		self::$template_paths[] = $path;
	}


	/**
	 * Build a list of modules to be registered to the ResourceLoader when it initializes.
	 */
	public static function addModules($modules=array(), $load=false){
		if( self::$_modulesRegistered ){
			throw new Exception('Skin is attempting to add modules after modules have already been registered.');
		}
		if(empty($modules)){
			return;
		}
		self::$modules += (array) $modules;
		if($load){
			self::$autoloadModules += array_keys($modules);
		}
	}

	/**
	 * Add ResourceLoader modules to a specified layout
	 * They will be registered with ResourceLoader and automatically loaded
	 * if the layout is active.
	 */
	public static function addModulesToLayout( $layout, $modules ){
		self::addModules($modules);
		self::$layouts[$layout]['modules'] += $modules;
	}

	public static function loadModules( $module_names ){
		self::$autoloadModules += $module_names;
	}
}

