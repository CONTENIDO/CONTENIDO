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
 * @version    0.2
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
 *
 *   $Id: functions.setup.php 740 2008-08-27 10:45:04Z timo.trautmann $:
 * }}
 * 
 */
 if(!defined('CON_FRAMEWORK')) {
                die('Illegal call');
}



if (!function_exists('file_get_contents')) {
  function file_get_contents($filename, $use_include_path = 0) {
   $data = '';
   $file = @fopen($filename, "rb", $use_include_path);
   if ($file) {
     while (!feof($file)) $data .= fread($file, 1024);
     fclose($file);
   } else {
     /* There was a problem opening the file. */
     return FALSE;
   }
   return $data;
  }
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