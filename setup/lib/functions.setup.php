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
 * @version    0.3.1
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 *
 * {@internal
 *   created  unknown
 *   modified 2008-07-07, bilal arslan, added security fix
 *   modified 2011-02-08, Dominik Ziegler, removed old PHP compatibility stuff as CONTENIDO now requires at least PHP 5
 *   modified 2011-05-19, Murat Purc, check for defined constant C_SETUP_STEPS to prevent thrown warnings
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
     die('Illegal call');
}

/**
 * Generates the step display.
 *
 * @param   int  iCurrentStep  The current step to display active.
 * @return  string
 * @modified 2008-02-26 Rudi Bieller
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
 * @param	string	$sErrorMessage	message to log in file
 * 
 * @return	void
 */
function logSetupFailure($sErrorMessage) {
	file_put_contents(C_FRONTEND_PATH . 'contenido/logs/setuplog.txt', $sErrorMessage . PHP_EOL . PHP_EOL, FILE_APPEND);
}
?>