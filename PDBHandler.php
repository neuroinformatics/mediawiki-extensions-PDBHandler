<?php

if (!defined('MEDIAWIKI')) {
	echo 'PDBHandler extension';
	exit(1);
}

// default pymol command path
if (!isset($wgPyMOLCommand))
	$wgPyMOLCommand = 'pymol';

// credits
$wgExtensionCredits['media'][] = array(
	'path' => __FILE__,
	'name' => 'PDBHandler',
        'version' => '2016/2/5',
	'license-name' => 'GPL-2.0+',
	'author' => array('Emw', 'Yoshihiro Okumura'),
	'url' => 'http://www.mediawiki.org/wiki/Extension:PDBHandler',
	'descriptionmsg' => 'mwe-mh-credits-desc',
);

$wgPDBHandlerDir = __DIR__ .'/';

// classes loader
$wgAutoloadClasses['PDBHandler'] = $wgPDBHandlerDir . 'PDBHandler_body.php';
$wgAutoloadClasses['PDBHandlerTransformOutput'] = $wgPDBHandlerDir . 'PDBHandlerTransformOutput.php';
$wgAutoloadClasses['PDBHandlerUtils'] = $wgPDBHandlerDir . 'PDBHandlerUtils.php';
$wgAutoloadClasses['PDBHandlerHooks'] = $wgPDBHandlerDir . 'PDBHandlerHooks.php';

// resource modules
$wgResourceModules['ext.PDBHandler'] = array(
	'scripts' => array(
		'modules/Three.js',
		'modules/GLmol.js',
		'modules/ext.PDBHandler.js'
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'PDBHandler'
);

// message file
$wgMessagesDirs['PDBHandler'] = $wgPDBHandlerDir . 'i18n';

// add pdb file extension
if (!in_array('pdb', $wgFileExtensions))
	$wgFileExtensions[] = 'pdb';

// add media handler for pdb media file
$wgMediaHandlers['model/x-pdb'] = 'PDBHandler';
$wgMediaHandlers['chemical/x-pdb'] = 'PDBHandler';
$wgMediaHandlers['application/x-pdb'] = 'PDBHandler';

// hooks for mime type detection
$wgHooks['MimeMagicInit'][] = 'PDBHandlerHooks::MimeMagicInit';
$wgHooks['MimeMagicImproveFromExtension'][] = 'PDBHandlerHooks::MimeMagicImproveFromExtension';
$wgHooks['MimeMagicGuessFromContent'][] = 'PDBHandlerHooks::MimeMagicGuessFromContent';

// use 3D view using WebGL
if (!isset($wgPDBHandlerUseWebGL))
	$wgPDBHandlerUseWebGL = true;

// cache directory for rasterized PDB data
if (!isset($wgPDBHandlerCacheDirectory))
	$wgPDBHandlerCacheDirectory = $IP . '/cache/PDBHandler';

// validate setting
if (substr($wgPDBHandlerCacheDirectory, -1) == '/')
	$wgPDBHandlerCacheDirectory = substr($wgPDBHandlerCacheDirectory, 0, -1);
if (!is_dir($wgPDBHandlerCacheDirectory))
	die('PDBHandler Fatal: cache directory not found: ' . $wgPDBHandlerCacheDirectory);
