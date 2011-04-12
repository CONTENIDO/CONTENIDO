<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Plugin mod rewrite debugger class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend plugins
 * @version    0.1
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since Contenido release 4.8.15
 *
 * {@internal
 *   created  2011-04-11
 *
 *   $Id: $:
 * }}
 *
 */


defined('CON_FRAMEWORK') or die('Illegal call');


class ModRewriteDebugger
{
    protected static $_bEnabled = false;


    public static function setEnabled($bEnabled)
    {
        self::$_bEnabled = (bool) $bEnabled;
    }

    public static function add($mVar, $sLabel = '')
    {
        if (!self::$_bEnabled) {
            return;
        }
        $oDebugger = DebuggerFactory::getDebugger('visible_adv');
        $oDebugger->add($mVar, $sLabel);
    }

    public static function getAll()
    {
        if (!self::$_bEnabled) {
            return '';
        }
        $oDebugger = DebuggerFactory::getDebugger('visible_adv');
        ob_start();
        $oDebugger->showAll();
        $sOutput = ob_get_contents();
        ob_end_clean();
        return $sOutput;
    }

    public static function log($mVar, $sLabel = '')
    {
        if (!self::$_bEnabled) {
            return;
        }
        $oDebugger = DebuggerFactory::getDebugger('file');
        $oDebugger->show($mVar, $sLabel);
    }
}
