<?php
/**
* $RCSfile$
*
* Description: Static Debugger Factory
*
* @version 1.1.0
* @author Rudi Bieller
* @copyright four for business AG <www.4fb.de>
*
* {@internal
* created 2007-03-27
* modified 2008-05-07 Added Debug_DevNull, extended Exception message.
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
            case 'devnull':
                include_once('Debug_DevNull.class.php');
                $oDebugger = Debug_DevNull::getInstance();
                break;
            default:
                throw new InvalidArgumentException('This type of debugger is unknown to DebuggerFactory: '.$sType);
                break;
        }
        return $oDebugger;
    }
}
?>