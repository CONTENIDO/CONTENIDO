<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Contains setup related functions
 *
 * Requirements:
 * @con_php_req 5
 *
 *
 * @package    CONTENIDO setup
 * @version    0.3.2
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 *
 * {@internal
 *   created  unknown
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
     die('Illegal call');
}

/**
 * Generates the step display.
 *
 * @param   int  iCurrentStep  The current step to display active.
 * @return  string
 */
function cGenerateSetupStepsDisplay($iCurrentStep)
{
    if (!defined('C_SETUP_STEPS')) {
        return '';
    }
    $sStepsPath = '';
    for ($i=1; $i < C_SETUP_STEPS + 1; $i++) {
        $sCssActive = '';
        if ($iCurrentStep == $i) {
            $sCssActive = 'background-color:#fff;color:#0060B1;';
        }
        $sStepsPath .= '<span style="'.$sCssActive.'">&nbsp;'.strval($i).'&nbsp;</span>&nbsp;&nbsp;&nbsp;';
    }
    return $sStepsPath;
}

/**
 * Logs general setup failures into setuplog.txt in logs directory.
 *
 * @param   string  $sErrorMessage  Message to log in file
 * @return  void
 */
function logSetupFailure($sErrorMessage)
{
    global $cfg;
    cFileHandler::write($cfg['path']['contenido_logs'] . 'setuplog.txt', $sErrorMessage . PHP_EOL . PHP_EOL, true);
}

?>