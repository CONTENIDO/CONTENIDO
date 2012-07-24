<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Static Debugger Factory
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package CONTENIDO Backend Classes
 * @version 1.0.1
 * @author Rudi Bieller
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}
class cDebugFactory {

    public static function getDebugger($sType) {
        $oDebugger = null;
        switch ($sType) {
            case 'visible':
                include_once ('class.debug.visible.php');
                $oDebugger = cDebugVisible::getInstance();
                break;
            case 'visible_adv':
                include_once ('class.debug.visible.adv.php');
                $oDebugger = cDebugVisibleAdv::getInstance();
                break;
            case 'hidden':
                include_once ('class.debug.hidden.php');
                $oDebugger = cDebugHidden::getInstance();
                break;
            case 'file':
                include_once ('class.debug.file.php');
                $oDebugger = cDebugFile::getInstance();
                break;
            case 'vis_and_file':
                include_once ('class.debug.file.and.vis.adv.php');
                $oDebugger = cDebugFileAndVisAdv::getInstance();
                break;
            case 'devnull':
                include_once ('class.debug.dev.null.php');
                $oDebugger = cDebugDevNull::getInstance();
                break;
            default:
                throw new InvalidArgumentException('This type of debugger is unknown to cDebugFactory: ' . $sType);
                break;
        }
        return $oDebugger;
    }

}
class DebuggerFactory extends cDebugFactory {

    /**
     *
     * @deprecated Class was renamed to cDebugFactory
     */
    public function __construct() {
        cDeprecated('Class was renamed to cDebugFactory');
    }

}