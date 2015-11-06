<?php
/*
 * Parser extensions to make working with Skinny even awesomer
 */
class Skinny{

  //singleton
  private function __construct(){}
  
  protected static $defaults=array(
    'extract toc' => false,
    'remove toc' => false
  );
  public static $options=array();
  public static $content = array();
  protected static $pageSkin = null;
  protected static $skin;
  protected static $skinLayout = null;
  
  /**
   * Initialization. Hook for BeforeInitialize
   */
  public static function init(&$title, $unused, &$output, &$user, $request, $mediaWiki){
    self::setOptions();
    return true;
  }

  /*
   * Handler for hook: ParserFirstCallInit
   *
   * Register the Skinny parser functions 
   */
  public static function ParserFirstCallInit(&$parser){
    $parser->setFunctionHook('movetoskin', 'Skinny::moveToSkin');
    $parser->setFunctionHook('setskin', 'Skinny::setSkin');
    $parser->setFunctionHook('layout', 'Skinny::setLayout');
    $parser->setFunctionHook('skinsert', 'Skinny::insertTemplate');
    $parser->setFunctionHook('imageurl', 'Skinny::getImageURL');

    return true;
  }

  /**
   * Handler for hook: OutputPageBeforeHTML
   *
   * While this would ideally live in ParserBeforeTidy, that means our settings
   * aren't loaded when a page is retrieved from the ParserCache, so we do it
   * all here instead... 
   *
   * For reasons I'm yet to fathom, this hook is sometimes called before skin init
   * and sometimes after.
   */
  public static function OutputPageBeforeHTML($out, &$html){
    //echo $html; exit;
    $html = self::processMoveContent($html);
    if(self::$options['extract toc']){
      $html = self::extractTOC($html);
    }
    $html = self::processSetSkin($html);
    $html = self::processSetLayout($html);
    return true;
  }


  public static function setOptions( $options=array(), $reset=false ){
    if( $reset || empty(self::$options) ){
      //set all options to their defaults
      self::$options = self::$defaults;
    }
    self::$options = self::mergeOptionsArrays(self::$options, $options );
  }

  //recursively merge arrays, but if there are key conflicts,
  //overwrite from right to left
  public static function mergeOptionsArrays($left, $right){
    $new = $left;
    foreach( $right as $k => $v){
      if( isset($left[$k]) ){
        //if there's an existing value, merge it if it's an array
        if( is_array($left[$k]) ){
          if( is_array($v) ){
            $new[$k] = self::mergeOptionsArrays($left[$k], $v);
          }else{
            //if the new option isn't an array, we'll interpret it as a boolean
            //and add this as an 'enabled' property to the left array
            //eg. this allows passing an option as false as a shortcut for array( enabled => false )
            $new[$k] = array_merge( $left[$k], array('enabled' => (bool) $v) );
          }
        }else{
          //otherwise just copy it over
          $new[$k] = $v;
        }

      }else{
        //if there's no existing value, just copy it over
        $new[$k] = $v;
      }
    }
    return $new;
  }



  public static function build( $path, $options=array() ){
    $options['template_path'] = $path;
    return new SkinnySlim( $options );
  }

  //Parser function: {{#movetoskin: target | content}}
  public static function moveToSkin($parser, $name='', $content=''){
    //we have to wrap the inner content within <p> tags, because MW screws up otherwise by placing a <p> tag before and after with related closing and opening tags within
    //php's DOM library doesn't like that and will swap the order of the first closing </p> and the closing </movetoskin> - stranding everything after that outside the <movetoskin> block. Lame.
    //$content = $parser->recursiveTagParse($content);
    $content = '<ins data-type="movetoskin" data-name="'.$name.'">'.$content.'</ins>';
    return array( $content, 'noparse' => false, 'isHTML' => false );
  }

  //Parser function: {{#setskin: skin-name}}
  public static function setSkin($parser, $skin){
    $content = '<p>SETSKIN:'.$skin.':NIKSTES</p>';
    return array( $content, 'noparse' => true, 'isHTML' => true );
  }

  //Parser function: {{#skin variant: skin-name}}
  public static function setLayout($parser, $variant){
    $content = '<p>LAYOUT:'.$variant.':TUOYAL</p>';
    return array( $content, 'noparse' => true, 'isHTML' => true );
  }

  //Parser function: {{#skintemplate:template-name|argument=value|argument=value}}
  //render a template from a skin template file...
  public static function insertTemplate($parser, $template, $spot=''){
    //process additional arguments into a usable array
    $params = array();
    //sanitize the template name
    $template = preg_replace('/[^A-Za-z0-9_\-]/', '_', $template);
    //this will be stripped out, assuming the skin is based on Skinny.template
    return '<p>ADDTEMPLATE('.$spot.'):'.$template.':ETALPMETDDA</p>';
  }

  function getImageURL ( &$parser, $name = '', $arg = 'abs' ) {
    $img = Image::newFromName( $name );
    if($img!==NULL){
      return (  trim($arg==='abs') ? $GLOBALS['wgServer'] : '') . $img->getURL();
    }
    return '';
  }
  


  protected static function processMoveContent($html){
   if(empty($html))
     return $html;

    if( preg_match_all('~<ins data-type="movetoskin" data-name="(\w+)">([\S\s]*?)<\/ins>~m', $html, $matches, PREG_SET_ORDER) ){
      foreach($matches as $match){
        if( !isset( self::$content[ $match[1] ] )){
          self::$content[ $match[1] ] = array();
        }
        array_push( self::$content[ $match[1] ] , array(
          'html' => $match[2] 
        ));
        $html = str_replace($match[0], '', $html);
      }
    }

    return $html;
  }
    
  public static function hasContent($target){
    if(isset(self::$content[$target]))
      return true;
    return false;
  }
  
  public static function getContent($target=null){
    if($target!==null){
      if( self::hasContent($target) )
        return self::$content[$target];
    }else{
      return array();
    }
  }

  public static function extractTOC( $html ){
    //a super hacky way to grab the TOC.  If the TOC HTML is changed in future versions this will break.
    //just grabs everything from <div id="toc" to </li></ul></div>
    if( preg_match('~<div id="toc"([\S\s]*?)</li>\n\s*</ul>\n\s*</div>~mi', $html, $match)){
      
      if(self::$options['remove toc']){
        $html = str_replace($match[0], '', $html);
      }
      $toc = str_replace('id="toc"', '', $match[0]);
      self::$content['toc'] = array( array('html'=>$toc) );
    }
    return $html;
  }

  //check the html for any set skin tokens
  protected static function processSetSkin($html){
    $pattern = '~<p>SETSKIN:([\w_-]+):NIKSTES<\/p>~m';
    if( preg_match_all($pattern, $html, $matches, PREG_SET_ORDER) ){
      $skin = array_pop($matches);

      //if OutputPageBeforeHTML isn't called before RequestContextCreateSkin
      //then this will have no effect... need a way to hack around the problem...
      self::$pageSkin = $skin[1];

      $html = preg_replace($pattern, '', $html);
    }
    return $html;
  }

  //check the html for any set skin tokens
  protected static function processSetLayout($html){
    $pattern = '~<p>LAYOUT:([\w_-]+):TUOYAL<\/p>~m';
    if( preg_match_all($pattern, $html, $matches, PREG_SET_ORDER) ){
      $layout = array_pop($matches);

      //sometimes OutputPageBeforeHTML is called after skin init,
      //sometimes before, so we allow for both
      self::$skinLayout = $layout[1];
      if(self::$skin){
        self::$skin->setOptions(array('layout'=>$layout[1]));
      }

      $html = preg_replace($pattern, '', $html);
    }
    return $html;
  }

  //hook for RequestContextCreateSkin
  public static function getSkin($context, &$skin){

    //there's probably a better way to check for this...
    if(!isset($_GET['useskin'])){
      $key = $GLOBALS['wgDefaultSkin'];
      if( self::$pageSkin ){
        $key = new self::$pageSkin;
      }

      $key = Skin::normalizeKey( $key );

      $skinNames = Skin::getSkinNames();
      $skinName = $skinNames[$key];
      $className = "Skin{$skinName}";

      $options = array();
      if( self::$skinLayout ){
        $options['layout'] = self::$skinLayout;
      }
      //echo self::$skinLayout; print_r($options);// exit;
      $skin = new $className( $options );
    }
    self::$skin = $skin;

    return true;
  }


}