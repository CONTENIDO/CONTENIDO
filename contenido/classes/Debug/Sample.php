<?php
/**
* $RCSfile$
*
* Description: Sample for using a Debug object
* 
* Debug objects here are made for simple (!) debugging purposes.
* Instead of writing echo '<pre>'.print_r($mMyVar, true).'</pre>';
* you can now write $oDbg->show($mMyVar);
* and get a formatted, readable representation of the passed variable.
* 
* There are Objects to display the contents of a variable 
* to screen, to html comments and to a file.
* 
* When using Debug_File, there will be a debug.log created in /contenido/logs/
*
* @version 1.0.0
* @author Rudi Bieller
* @copyright four for business AG <www.4fb.de>
*
* {@internal
* created 2008-02-19
* }}
*
* $Id$
*/

cInclude('classes', 'Debug/DebuggerFactory.class.php');

$oMyObj = new stdClass();
$oMyObj->sTest = 'some property value';
$oMyObj->aTest = array('item1', 'item2');

$aMyArray = array('somekey' => 'somevalue');

$iMyInt = 5;

$fMyFloat = 5.12;

$sMyString = 'my string looks like this';

// print debug info to screen
$oDbgVisible = DebuggerFactory::getDebugger('visible');
$oDbgVisible->show($oMyObj, 'some comments if needed');
$oDbgVisible->show($aMyArray, 'some comments if needed');
$oDbgVisible->show($fMyFloat, 'some comments if needed');

// print debug info to screen in html comments
$oDbgHidden = DebuggerFactory::getDebugger('hidden'); 
$oDbgHidden->show($oMyObj, 'some comments if needed');
$oDbgHidden->show($aMyArray, 'some comments if needed');
$oDbgHidden->show($fMyFloat, 'some comments if needed');

// print debug info to a logfile
$oDbgFile = DebuggerFactory::getDebugger('file');
$oDbgFile->show($oMyObj, 'some comments if needed');
$oDbgFile->show($aMyArray, 'some comments if needed');
$oDbgFile->show($fMyFloat, 'some comments if needed');
?>