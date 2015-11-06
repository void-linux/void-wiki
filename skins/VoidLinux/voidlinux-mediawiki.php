<?php
/**
 * VoidLinux MediaWiki
 *
 * @voidlinux-mediawiki.php
 * @ingroup Skins
 * @author Matthew Batchelder (http://borkweb.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

if ( ! defined( 'MEDIAWIKI' ) ) die( "This is an extension to the MediaWiki package and cannot be run standalone." );

$wgExtensionCredits['skin'][] = array(
	'path'        => __FILE__,
	'name'        => 'VoidLinux Mediawiki',
	'url'         => 'http://borkweb.com',
	'author'      => '[http://borkweb.com Matthew Batchelder]',
	'description' => 'MediaWiki skin using Bootstrap 3',
);

$wgValidSkinNames['voidlinuxmediawiki'] = 'VoidLinuxMediaWiki';
$wgAutoloadClasses['SkinVoidLinuxMediaWiki'] = __DIR__ . '/VoidLinuxMediaWiki.skin.php';


$skinDirParts = explode( DIRECTORY_SEPARATOR, __DIR__ );
$skinDir = array_pop( $skinDirParts );

$wgResourceModules['skins.voidlinuxmediawiki'] = array(
	'styles' => array(
		$skinDir . '/bootstrap/css/bootstrap.min.css'            => array( 'media' => 'all' ),
		$skinDir . '/google-code-prettify/prettify.css'          => array( 'media' => 'all' ),
		$skinDir . '/style.css'                                  => array( 'media' => 'all' ),
	),
	'scripts' => array(
		$skinDir . '/bootstrap/js/bootstrap.min.js',
		$skinDir . '/google-code-prettify/prettify.js',
		$skinDir . '/js/jquery.ba-dotimeout.min.js',
		$skinDir . '/js/behavior.js',
	),
	'dependencies' => array(
		'jquery',
		'jquery.mwExtension',
		'jquery.client',
		'jquery.cookie',
	),
	'remoteBasePath' => &$GLOBALS['wgStylePath'],
	'localBasePath'  => &$GLOBALS['wgStyleDirectory'],
);

if ( isset( $wgSiteJS ) ) {
	$wgResourceModules['skins.voidlinuxmediawiki']['scripts'][] = $skinDir . '/' . $wgSiteJS;
}//end if

if ( isset( $wgSiteCSS ) ) {
	$wgResourceModules['skins.voidlinuxmediawiki']['styles'][] = $skinDir . '/' . $wgSiteCSS;
}//end if
