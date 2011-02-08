<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * 
 * Requirements: 
 * @con_php_req 5
 * 
 *
 * @package    Contenido Backend <Area>
 * @version    0.3
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 * @deprecated file deprecated in contenido release <Contenido Version>
 * 
 * {@internal 
 *   created  unknown
 *   modified 2008-07-07, bilal arslan, added security fix
 *   modified 2011-02-08, Dominik Ziegler, removed old PHP compatibility stuff as contenido now requires at least PHP 5
 *
 *   $Id$:
 * }}
 * 
 */
 if(!defined('CON_FRAMEWORK')) {
                die('Illegal call');
}

/**
 * cGenerateSetupStepsDisplay
 * 
 * Generates the step display.
 * 
 * @param iCurrentStep integer The current step to display active.
 * @modified 2008-02-26 Rudi Bieller
 */
function cGenerateSetupStepsDisplay ($iCurrentStep)
{
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
?>