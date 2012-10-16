<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * <Description>
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
 *   $Id: functions.libraries.php 740 2008-08-27 10:45:04Z timo.trautmann $:
 * }}
 * 
 */
 if(!defined('CON_FRAMEWORK')) {
                die('Illegal call');
}

define("E_IMAGERESIZE_GD", 					1);
define("E_IMAGERESIZE_IMAGEMAGICK", 		2);
define("E_IMAGERESIZE_CANTCHECK",			3);
define("E_IMAGERESIZE_NOTHINGAVAILABLE", 	4);

function checkImageResizer ()
{
	
	$iGDStatus = isPHPExtensionLoaded("gd");
	
	if ($iGDStatus == E_EXTENSION_AVAILABLE)
	{
		return E_IMAGERESIZE_GD;	
	}
	
	if (function_exists("imagecreate"))
	{
		return E_IMAGERESIZE_GD;	
	}
	
	if (isImageMagickAvailable())
	{
		return E_IMAGERESIZE_IMAGEMAGICK;	
	}
	
	if ($iGDStatus === E_EXTENSION_CANTCHECK)
	{
		return E_IMAGERESIZE_CANTCHECK;	
	} else {
		return E_IMAGERESIZE_NOTHINGAVAILABLE;	
	}

}

?>