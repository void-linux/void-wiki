<?php
/**
 * Skinny
 * Simple tools to help advanced skinning techniques.
 * to predefined areas in your skin.
 * Intended for MediaWiki Skin designers.
 * By Andru Vallance - andru@tinymighty.com
 *
 * License: GPL - http://www.gnu.org/copyleft/gpl.html
 *
 */
$GLOBALS['wgAutoloadClasses']['Skinny'] = __DIR__ . '/Skinny.class.php';
$GLOBALS['wgAutoloadClasses']['SkinSkinny'] = __DIR__ . '/Skinny.skin.php';
$GLOBALS['wgAutoloadClasses']['SkinnyTemplate'] = __DIR__ . '/Skinny.template.php';
$GLOBALS['wgAutoloadClasses']['SkinnySlim'] = __DIR__ . '/Skinny.slim.php';


$GLOBALS['wgExtensionMessagesFiles']['SkinnyMagic'] = __DIR__ . '/Skinny.i18n.magic.php';
$GLOBALS['wgExtensionMessagesFiles']['Skinny'] = __DIR__ . '/Skinny.i18n.php';

$GLOBALS['wgHooks']['BeforeInitialize'][] = 'Skinny::init';
$GLOBALS['wgHooks']['ParserFirstCallInit'][] = 'Skinny::ParserFirstCallInit';
$GLOBALS['wgHooks']['OutputPageBeforeHTML'][] = 'Skinny::OutputPageBeforeHTML';

$GLOBALS['wgHooks']['RequestContextCreateSkin'][] = 'Skinny::getSkin';

$GLOBALS['wgHooks']['ResourceLoaderRegisterModules'][] = 'SkinSkinny::ResourceLoaderRegisterModules';


$GLOBALS['wgExtensionCredits']['parserhook'][] = array(
   'path' => __FILE__,
   'name' => 'Skinny',
   'description' => 'Handy tools for advanced skinning. Move content from the article to the skin, and set skin on a page by page basis.',
   'version' => '0.2', 
   'author' => 'Andru Vallance',
   'url' => 'https://www.mediawiki.org/wiki/Extension:Skinny'
);


