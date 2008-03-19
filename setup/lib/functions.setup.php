<?php

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