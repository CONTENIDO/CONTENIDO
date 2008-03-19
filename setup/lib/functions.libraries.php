<?php
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

function isImageMagickAvailable ()
{
	global $_imagemagickAvailable;
	
	if (is_bool($_imagemagickAvailable))
	{
		if ($_imagemagickAvailable === true)
		{
			return true;	
		} else {
			return false;	
		}
	}
	
	$output = array();
	
	$retval = "";
	
	@exec("convert",$output, $retval);

    if (!is_array($output) || count($output) == 0)
    {
        return false;
    }
    	
	if (strpos($output[0],"ImageMagick") !== false)
	{
		$_imagemagickAvailable = true;
		return true;
	} else {
		$_imagemagickAvailable = false;
		return false;
	}
}

?>