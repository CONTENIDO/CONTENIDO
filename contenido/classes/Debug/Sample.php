<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Sample for using a Debug object
 * Debug objects here are made for simple (!) debugging purposes.
 * Instead of writing echo '<pre>'.print_r($mMyVar, true).'</pre>';
 * you can now write $oDbg->show($mMyVar);
 * and get a formatted, readable representation of the passed variable.
 *
 * There are Objects to display the contents of a variable
 * to screen, to html comments and to a file.
 *
 * When using Debug_File, there will be a debug.log created in /data/logs/
 *
 * Using the Factory you can simply change the type of debugger by passing the type to load.
 * By using "devnull" you can keep your debug code while in production systems and turn it on again by changing to "file" or "hidden" if needed.
 *
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.0.1
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created 2008-02-19
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


$oMyObj = new stdClass();
$oMyObj->sTest = 'some property value';
$oMyObj->aTest = array('item1', 'item2');

$aMyArray = array('somekey' => 'somevalue');

$iMyInt = 5;

$fMyFloat = 5.12;

$sMyString = 'my string looks like this';

// print debug info to screen
$oDbgVisible = cDebugFactory::getDebugger('visible');
$oDbgVisible->show($oMyObj, 'some comments if needed');
$oDbgVisible->show($aMyArray, 'some comments if needed');
$oDbgVisible->show($fMyFloat, 'some comments if needed');

// print debug info to screen inside a box that can be toggled and does not crash your layout
$oDbgVisibleAdv = cDebugFactory::getDebugger('visible_adv');
$oDbgVisibleAdv->add($oMyObj, 'some comments if needed');
$oDbgVisibleAdv->add($aMyArray, 'some comments if needed');
$oDbgVisibleAdv->add($fMyFloat, 'some comments if needed');
$oDbgVisibleAdv->showAll(); // prints out a small html box at left top of page
$oDbgVisibleAdv->show($fMyFloat, 'some comments if needed'); // also possible here

// print debug info to screen in html comments
$oDbgHidden = cDebugFactory::getDebugger('hidden');
$oDbgHidden->show($oMyObj, 'some comments if needed');
$oDbgHidden->show($aMyArray, 'some comments if needed');
$oDbgHidden->show($fMyFloat, 'some comments if needed');

// print debug info to a logfile
$oDbgFile = cDebugFactory::getDebugger('file');
$oDbgFile->show($oMyObj, 'some comments if needed');
$oDbgFile->show($aMyArray, 'some comments if needed');
$oDbgFile->show($fMyFloat, 'some comments if needed');

// send debug info to dev/null
$oDbgDevnull = cDebugFactory::getDebugger('devnull');
$oDbgDevnull->show($oMyObj, 'some comments if needed');
$oDbgDevnull->show($aMyArray, 'some comments if needed');
$oDbgDevnull->show($fMyFloat, 'some comments if needed');

?>