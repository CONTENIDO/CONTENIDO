<?php
/**
 * CONTENIDO autoloader class map file generator.
 *
 * Parses CONTENIDO classes folder and creates a class map file.
 *
 * Usage:
 * ------
 * 1. Modifiy settings to your requirements
 * 2. Call this script from command line as follows:
 *     $ php create_autoloader_cfg.php
 * 3. Check created class map file
 *
 * @package          Core
 * @subpackage       Tool
 * @author           Murat Purc <murat@purc.de>
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

// allow execution only thru cli mode
if (cString::getPartOfString(PHP_SAPI, 0, 3) != 'cli') {
    die('Illegal call');
}


// /////////////////////////////////////////////////////////////////////
// Initialization/Settings

// create a page context class, better than spamming global scope
$context = new stdClass();

// current path
$context->currentPath = str_replace('\\', '/', realpath(dirname(__FILE__) . '/')) . '/';

// CONTENIDO installation path (folder which contains "cms", "contenido", "docs", "setup", etc...)
$context->contenidoInstallPath = str_replace('\\', '/', realpath(dirname(__FILE__) . '/../../')) . '/';

// Include the environment definer file
include_once($context->contenidoInstallPath . 'contenido/environment.php');
// the destination file where the class map configuration should be written in
$context->destinationFile = $context->contenidoInstallPath . '/data/config/' . CON_ENVIRONMENT . '/config.autoloader.php';

// list of paths from where all class/interface names should be found
$context->pathsToParse = array(
    $context->contenidoInstallPath . 'contenido/classes/',
    $context->contenidoInstallPath . 'contenido/external/wysiwyg/tinymce4/contenido/classes/'
);

// class type finder options
$context->options = array(
    // list of directories which are to exclude from parsing (case insensitive)
    'excludeDirs'       => array('.svn'),
    // list of files which are to exclude from parsing (case insensitive), also possible regex patterns like /^~*.\.php$/
    'excludeFiles'      => array(),
    // list of file extensions to parse (case insensitive)
    'extensionsToParse' => '.php',
    'enableDebug'       => false,
);

// list to collect class maps
$context->classMapList = array();

// /////////////////////////////////////////////////////////////////////
// Proccess

// include required classes
include_once($context->currentPath . 'mpAutoloaderClassMap/mpClassTypeFinder.php');
include_once($context->currentPath . 'mpAutoloaderClassMap/mpClassMapFileCreatorContenido.php');

// collect all found class/interface names with their paths
$context->classTypeFinder = new mpClassTypeFinder($context->options);
foreach ($context->pathsToParse as $pos => $dir) {
    $classMap = $context->classTypeFinder->findInDir(new SplFileInfo($dir), true);
    if ($classMap) {
        $context->classMapList = array_merge($context->classMapList, $classMap);
    }
}

// uncomment following line to get some debug messages
// echo $context->classTypeFinder->getFormattedDebugMessages();

// write the class map configuration
$context->classMapCreator = new mpClassMapFileCreatorContenido($context->contenidoInstallPath);
$context->classMapCreator->create($context->classMapList, $context->destinationFile);

// /////////////////////////////////////////////////////////////////////
// Shutdown

unset($context);
