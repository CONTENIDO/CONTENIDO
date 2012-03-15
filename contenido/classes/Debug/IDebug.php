<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * Debug interface. Can be extended to a visible, invisible, logged, ... Debugger
 *  
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.1.0
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 * created 2007-01-01
 * modified 2008-05-21 Added methods add(), reset(), showAll()
 *   
 * $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


interface IDebug {
	static public function getInstance();
	public function show($mVariable, $sVariableDescription = '', $bExit = false);
	public function add($mVariable, $sVariableDescription = '');
	public function reset();
	public function showAll();
	public function out($sText);
}
?>