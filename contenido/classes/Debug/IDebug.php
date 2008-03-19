<?php
/**
* $RCSfile$
*
* Description: Debug interface. Can be extended to a visible, invisible, logged, ... Debugger
*
* @version 1.0.0
* @author Rudi Bieller
* @copyright four for business AG <www.4fb.de>
*
* {@internal
* created 2007-01-01
* }}
*
* $Id$
*/

interface IDebug {
	static public function getInstance();
	public function show($mVariable, $sVariableDescription = '', $bExit = false);
}
?>