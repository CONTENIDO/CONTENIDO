<?php
/**
* $RCSfile$
*
* Description: Debug interface. Can be extended to a visible, invisible, logged, ... Debugger
*
* @version 1.1.0
* @author Rudi Bieller
* @copyright four for business AG <www.4fb.de>
*
* {@internal
* created 2007-01-01
* modified 2008-05-21 Added methods add(), reset(), showAll()
* }}
*
* $Id$:
*/

interface IDebug {
	static public function getInstance();
	public function show($mVariable, $sVariableDescription = '', $bExit = false);
	public function add($mVariable, $sVariableDescription = '');
	public function reset();
	public function showAll();
}
?>