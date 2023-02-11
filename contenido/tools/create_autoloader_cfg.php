<?php

/**
 * CONTENIDO autoloader class map file generator.
 *
 * Parses CONTENIDO classes folder and creates a class map file.
 *
 * Usage:
 * ------
 * 1. Modify settings to your requirements
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

// Allow execution only through cli mode
if (substr(PHP_SAPI, 0, 3) != 'cli') {
    die('Illegal call');
}


// /////////////////////////////////////////////////////////////////////
// Initialization/Settings

// Create a page context class, better than spamming global scope
$context = new stdClass();

// Current path
$context->currentPath = str_replace('\\', '/', realpath(dirname(__FILE__) . '/')) . '/';

// CONTENIDO installation path (folder which contains "cms", "contenido", "docs", "setup", etc...)
$context->contenidoInstallPath = str_replace('\\', '/', realpath(dirname(__FILE__) . '/../../')) . '/';

// Include the environment definer file
include_once $context->contenidoInstallPath . 'contenido/environment.php';
// The destination file where the class map configuration should be written in
$context->destinationFile = $context->contenidoInstallPath . '/data/config/' . CON_ENVIRONMENT . '/config.autoloader.php';

// List of paths from where all class/interface names should be found
$context->pathsToParse = [
    $context->contenidoInstallPath . 'contenido/classes/',
    $context->contenidoInstallPath . 'contenido/external/wysiwyg/tinymce4/contenido/classes/'
];

// Class type finder options
$context->options = [
    // list of directories which are to exclude from parsing (case-insensitive)
    'excludeDirs'       => ['.svn'],
    // list of files which are to exclude from parsing (case-insensitive), also possible regex patterns like /^~*.\.php$/
    'excludeFiles'      => [],
    // list of file extensions to parse (case-insensitive)
    'extensionsToParse' => '.php',
    'enableDebug'       => false,
];

// List to collect class maps
$context->classMapList = [];

// /////////////////////////////////////////////////////////////////////
// Process

// include required classes
include_once $context->currentPath . 'mpAutoloaderClassMap/mpClassTypeFinder.php';
include_once $context->currentPath . 'mpAutoloaderClassMap/mpClassMapFileCreator.php';
include_once $context->currentPath . 'mpAutoloaderClassMap/mpClassMapFileCreatorContenido.php';

// collect all found class/interface names with their paths
$context->classTypeFinder = new mpClassTypeFinder($context->options);
foreach ($context->pathsToParse as $pos => $dir) {
    $classMap = $context->classTypeFinder->findInDir(new SplFileInfo($dir), true);
    if ($classMap) {
        $context->classMapList = array_merge($context->classMapList, $classMap);
    }
}

// Sort the class map list
$context->classNames = array_keys($context->classMapList);
natcasesort($context->classNames);
$context->classMapListTmp = $context->classMapList;
$context->classMapList = [];
foreach ($context->classNames as $className) {
    $context->classMapList[$className] = $context->classMapListTmp[$className];
}

// Uncomment following line to get some debug messages
// echo $context->classTypeFinder->getFormattedDebugMessages();

// write the class map configuration
$context->classMapCreator = new mpClassMapFileCreatorContenido($context->contenidoInstallPath);
$context->classMapCreator->create($context->classMapList, $context->destinationFile);

// /////////////////////////////////////////////////////////////////////
// Shutdown

unset($context);
