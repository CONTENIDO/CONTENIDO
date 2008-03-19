<?php
/**
* $RCSfile$
*
* Description: Static Debugger Factory
*
* @version 1.0.0
* @author Rudi Bieller
* @copyright four for business AG <www.4fb.de>
*
* {@internal
* created 2007-03-27
* }}
*
* $Id$
*/

class DebuggerFactory {
    public static function getDebugger($sType) {
        $oDebugger = null;
        switch ($sType) {
            case 'visible':
                include_once('Debug_Visible.class.php');
                $oDebugger = Debug_Visible::getInstance();
                break;
            case 'hidden':
                include_once('Debug_Hidden.class.php');
                $oDebugger = Debug_Hidden::getInstance();
                break;
            case 'file':
                include_once('Debug_File.class.php');
                $oDebugger = Debug_File::getInstance();
                break;
            default:
                throw new InvalidArgumentException('This type of debugger is unknown to DebuggerFactory');
                break;
        }
        return $oDebugger;
    }
}
?>