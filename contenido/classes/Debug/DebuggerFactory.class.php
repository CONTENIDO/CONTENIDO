<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Static Debugger Factory
 *  
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version 1.0.0
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2007-03-27
 *   modified 2008-05-07 Added Debug_DevNull, extended Exception message.
 *   modified 2008-05-21 Added Debug_VisibleAdv
 *   
 *   $Id: DebuggerFactory.class.php 380 2008-06-30 08:36:51Z thorsten.granz $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


class DebuggerFactory {
    public static function getDebugger($sType) {
        $oDebugger = null;
        switch ($sType) {
            case 'visible':
                include_once('Debug_Visible.class.php');
                $oDebugger = Debug_Visible::getInstance();
                break;
			case 'visible_adv':
                include_once('Debug_VisibleAdv.class.php');
                $oDebugger = Debug_VisibleAdv::getInstance();
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