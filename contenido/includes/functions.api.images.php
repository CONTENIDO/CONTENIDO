<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO Image API functions
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.4.6
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created 2003-08-08
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/* Info:
 * This file contains CONTENIDO Image API functions.
 *
 * If you are planning to add a function, please make sure that:
 * 1.) The function is in the correct place
 * 2.) The function is documented
 * 3.) The function makes sense and is generically usable
 */


/**
 * Returns the MD5 filename used for caching.
 *
 * @param   string  $sImg     Path to upload image
 * @param   int     $iMaxX    Maximum image x size
 * @param   int     $iMaxY    Maximum image y size
 * @param   bool    $bCrop    Flag to crop image
 * @param   bool    $bExpand  Flag to expand image
 * @return  string  Path to the resulting image
 */
function capiImgScaleGetMD5CacheFile($sImg, $iMaxX, $iMaxY, $bCrop, $bExpand) {
    if (!file_exists($sImg)) {
        return false;
    }

    $iFilesize = filesize($sImg);

    if (function_exists('md5_file')) {
        $sMD5 = md5(implode('', array(
            $sImg, md5_file($sImg), $iFilesize, $iMaxX, $iMaxY, $bCrop, $bExpand
        )));
    } else {
        $sMD5 = md5(implode('', array(
            $sImg, $iFilesize, $iMaxX, $iMaxY, $bCrop, $bExpand
        )));
    }

    return $sMD5;
}

/**
 * Scales (or crops) an image.
 * If scaling, the aspect ratio is maintained.
 *
 * Returns the path to the scaled temporary image.
 *
 * Note that this function does some very poor caching;
 * it calculates an md5 hash out of the image plus the
 * maximum X and Y sizes, and uses that as the file name.
 * If the file is older than 10 minutes, regenerate it.
 *
 * @param  string   $img        The path to the image (relative to the frontend)
 * @param  int      $maxX       The maximum size in x-direction
 * @param  int      $maxY       The maximum size in y-direction
 * @param  bool     $crop       If true, the image is cropped and not scaled.
 * @param  bool     $expand     If true, the image is expanded (e.g. really scaled).
 *                              If false, the image will only be made smaller.
 * @param  int      $cacheTime  The number of minutes to cache the image, use 0 for unlimited
 * @param  int      $quality    The quality of the output file
 * @param  bool     $keepType   If true and a png file is source, output file is also png
 * @return string   url to the resulting image (http://...
 */
function capiImgScaleLQ($img, $maxX, $maxY, $crop = false, $expand = false,
                         $cacheTime = 10, $quality = 75, $keepType = false)
{
    global $cfgClient,  $client;

    if (!file_exists($img)) {
        return false;
    }

    $filename  = $img;
    $cacheTime = (int) $cacheTime;
    $quality   = (int) $quality;

    if ($quality <= 0 || $quality > 100) {
        $quality = 75;
    }

    $filetype  = substr($filename, strlen($filename) -4, 4);
    $md5       = capiImgScaleGetMD5CacheFile($img, $maxX, $maxY, $crop, $expand);
    $cfileName = capiImageGetCacheFileName($md5, $filetype, $keepType);
    $cacheFile = $cfgClient[$client]['cache_path'] . $cfileName;
    $webFile   = $cfgClient[$client]['path']['htmlpath'] . 'cache/' . $cfileName;

    if (capiImageCheckCachedImageValidity($cacheFile, $cacheTime)) {
        return $webFile;
    }

    // Get out which file we have
    switch (strtolower($filetype)) {
        case '.gif': $function = 'imagecreatefromgif'; break;
        case '.png': $function = 'imagecreatefrompng'; break;
        case '.jpg': $function = 'imagecreatefromjpeg'; break;
        case "jpeg": $function = 'imagecreatefromjpeg'; break;
        default: return false;
    }

    if (function_exists($function)) {
        $imageHandle = @$function($filename);
    }

    // If we can't open the image, return false
    if (!$imageHandle) {
        return false;
    }

    $x = imagesx($imageHandle);
    $y = imagesy($imageHandle);

    list($targetX, $targetY) = capiImageGetTragetDimensions($x, $y, $maxX, $maxY, $expand);

    // Create the target image with the target size, resize it afterwards.
    if ($crop) {
        // Create the target image with the max size, crop it afterwards.
        $targetImage = imagecreate($maxX, $maxY);
        imagecopy($targetImage, $imageHandle, 0, 0, 0, 0, $maxX, $maxY);
    } else {
        // Create the target image with the target size, resize it afterwards.
        $targetImage = imagecreate($targetX, $targetY);
        imagecopyresized($targetImage, $imageHandle, 0, 0, 0, 0, $targetX, $targetY, $x, $y);
    }

    // Output the file
    if ($keepType) {
        switch (strtolower($filetype)) {
            case '.png':
                imagepng($targetImage, $cacheFile); // no quality option available
                break;
            case '.gif':
                imagegif($targetImage, $cacheFile); // no quality option available
                break;
            default:
                imagejpeg($targetImage, $cacheFile, $quality);
        }
    } else {
        imagejpeg($targetImage, $cacheFile, $quality);
    }

    return ($webFile);
}

/**
 * Scales (or crops) an image in high quality.
 * If scaling, the aspect ratio is maintained.
 *
 * Note: GDLib 2.x is required!
 *
 * Returns the path to the scaled temporary image.
 *
 * Note that this function does some very poor caching;
 * it calculates an md5 hash out of the image plus the
 * maximum X and Y sizes, and uses that as the file name.
 * If the file is older than the specified cache time, regenerate it.
 *
 * @param   string  $img        The path to the image (relative to the frontend)
 * @param   int     $maxX       The maximum size in x-direction
 * @param   int     $maxY       The maximum size in y-direction
 * @param   bool    $crop       If true, the image is cropped and not scaled.
 * @param   bool    $expand     If true, the image is expanded (e.g. really scaled).
 *                              If false, the image will only be made smaller.
 * @param   int     $cacheTime  The number of minutes to cache the image, use 0 for unlimited
 * @param   int     $quality    The quality of the output file
 * @param   bool    $keepType   If true and a png file is source, output file is also png
 * @return  string  Url to the resulting image (http://...)
 */
function capiImgScaleHQ($img, $maxX, $maxY, $crop = false, $expand = false,
                         $cacheTime = 10, $quality = 75, $keepType = true)
{
    global $cfgClient, $client;

    if (!file_exists($img)) {
        return false;
    }

    $filename  = $img;
    $cacheTime = (int) $cacheTime;
    $quality   = (int) $quality;

    if ($quality <= 0 || $quality > 100) {
        $quality = 75;
    }

    $filetype  = substr($filename, strlen($filename) -4, 4);
    $md5       = capiImgScaleGetMD5CacheFile($img, $maxX, $maxY, $crop, $expand);
    $cfileName = capiImageGetCacheFileName($md5, $filetype, $keepType);
    $cacheFile = $cfgClient[$client]['cache_path'] . $cfileName;
    $webFile   = $cfgClient[$client]['path']['htmlpath'] . 'cache/' . $cfileName;

    if (capiImageCheckCachedImageValidity($cacheFile, $cacheTime)) {
        return $webFile;
    }

    // Get out which file we have
    switch (strtolower($filetype)) {
        case '.gif': $function = 'imagecreatefromgif'; break;
        case '.png': $function = 'imagecreatefrompng'; break;
        case '.jpg': $function = 'imagecreatefromjpeg'; break;
        case "jpeg": $function = 'imagecreatefromjpeg'; break;
        default: return false;
    }

    if (function_exists($function)) {
        $imageHandle = @$function($filename);
    }

    // If we can't open the image, return false
    if (!$imageHandle) {
        return false;
    }

    $x = imagesx($imageHandle);
    $y = imagesy($imageHandle);

    list($targetX, $targetY) = capiImageGetTragetDimensions($x, $y, $maxX, $maxY, $expand);

    // Create the target image with the target size, resize it afterwards.
    if ($crop) {
        // Create the target image with the max size, crop it afterwards.
        $targetImage = imagecreatetruecolor($maxX, $maxY);
        imagecopy($targetImage, $imageHandle, 0, 0, 0, 0, $maxX, $maxY);
    } else {
        // Create the target image with the target size, resize it afterwards.
        $targetImage = imagecreatetruecolor($targetX, $targetY);

        // Preserve transparency
        if (strtolower($filetype) == '.gif' or strtolower($filetype) == '.png'){
            imagecolortransparent($targetImage, imagecolorallocatealpha($targetImage, 0, 0, 0, 127));
            imagealphablending($targetImage, false);
            imagesavealpha($targetImage, true);
        }

        imagecopyresampled($targetImage, $imageHandle, 0, 0, 0, 0, $targetX, $targetY, $x, $y);
    }

    // Output the file
    if ($keepType) {
        switch (strtolower($filetype)) {
            case '.png':
                imagepng($targetImage, $cacheFile); // no quality option available
                break;
            case '.gif':
                imagegif($targetImage, $cacheFile);
                break;
            default:
                imagejpeg($targetImage, $cacheFile, $quality);
        }
    } else {
        imagejpeg($targetImage, $cacheFile, $quality);
    }

    return ($webFile);
}

/**
 * Scales (or crops) an image using ImageMagick.
 * If scaling, the aspect ratio is maintained.
 *
 * Note: ImageMagick is required!
 *
 * Returns the path to the scaled temporary image.
 *
 * Note that this function does some very poor caching;
 * it calculates an md5 hash out of the image plus the
 * maximum X and Y sizes, and uses that as the file name.
 * If the file is older than the specified cache time, regenerate it.
 *
 * @param   string  $img        The path to the image (relative to the frontend)
 * @param   int     $maxX       The maximum size in x-direction
 * @param   int     $maxY       The maximum size in y-direction
 * @param   bool    $crop       If true, the image is cropped and not scaled.
 * @param   bool    $expand     If true, the image is expanded (e.g. really scaled).
 *                              If false, the image will only be made smaller.
 * @param   int     $cacheTime  The number of minutes to cache the image, use 0 for unlimited
 * @param   int     $quality    The quality of the output file
 * @param   bool    $keepType   If true and a png file is source, output file is also png
 * @return  string  Url to the resulting image (http://...)
 */
function capiImgScaleImageMagick($img, $maxX, $maxY, $crop = false, $expand = false,
                                  $cacheTime = 10, $quality = 75, $keepType = false)
{
    global $cfgClient, $client;

    if (!file_exists($img)) {
        return false;
    }

    $filename  = $img;
    $cacheTime = (int) $cacheTime;
    $quality   = (int) $quality;

    if ($quality <= 0 || $quality > 100) {
        $quality = 75;
    }

    $filetype  = substr($filename, strlen($filename) -4, 4);
    $md5       = capiImgScaleGetMD5CacheFile($img, $maxX, $maxY, $crop, $expand);
    $cfileName = capiImageGetCacheFileName($md5, $filetype, $keepType);
    $cacheFile = $cfgClient[$client]['cache_path'] . $cfileName;
    $webFile   = $cfgClient[$client]['path']['htmlpath'] . 'cache/' . $cfileName;

    if (capiImageCheckCachedImageValidity($cacheFile, $cacheTime)) {
        return $webFile;
    }

    list($x, $y) = @getimagesize($filename);
    if ($x == 0 || $y == 0) {
        return false;
    }

    list($targetX, $targetY) = capiImageGetTragetDimensions($x, $y, $maxX, $maxY, $expand);

    // If is animated gif resize first frame
    if ($filetype == '.gif') {
        if (isAnimGif($filename)) {
            $filename .= '[0]';
        }
    }

    // Try to execute convert
    $output = array();
    $retVal = 0;
    if ($crop) {
        exec("convert -gravity center -quality ".$quality." -crop {$maxX}x{$maxY}+1+1 \"$filename\" $cacheFile", $output, $retVal);
    } else {
        exec("convert -quality ".$quality." -geometry {$targetX}x{$targetY} \"$filename\" $cacheFile", $output, $retVal );
    }

    if (!file_exists($cacheFile)) {
        return false;
    } else {
        return ($webFile);
    }
}

/**
 * Check if gif is animated
 *
 * @param   string  $sFile  file path
 * @return  bool  True (gif is animated)/ false (single frame gif)
 */
function isAnimGif($sFile) {
    $output = array();
    $retval = 0;

    exec('identify ' . $sFile, $output, $retval);

    if (count($output) == 1) {
        return false;
    }

    return true;
}

/**
 * Scales (or crops) an image.
 * If scaling, the aspect ratio is maintained.
 *
 * This function chooses the best method to scale, depending on the system
 * environment and/or the parameters.
 *
 * Returns the path to the scaled temporary image.
 *
 * Note that this function does some very poor caching;
 * it calculates an md5 hash out of the image plus the
 * maximum X and Y sizes, and uses that as the file name.
 * If the file is older than 10 minutes, regenerate it.
 *
 * @param   string   $img        The path to the image (relative to the frontend)
 * @param   int      $maxX       The maximum size in x-direction
 * @param   int      $maxY       The maximum size in y-direction
 * @param   bool     $crop       If true, the image is cropped and not scaled.
 * @param   bool     $expand     If true, the image is expanded (e.g. really scaled).
 *                               If false, the image will only be made smaller.
 * @param   int      $cacheTime  The number of minutes to cache the image, use 0 for unlimited
 * @param   bool     $wantHQ     If true, try to force high quality mode
 * @param   int      $quality    The quality of the output file
 * @param   bool     $keepType   If true and a png file is source, output file is also png
 * @return  string  Path to the resulting image
 */
function capiImgScale($img, $maxX, $maxY, $crop = false, $expand = false,
                       $cacheTime = 10, $wantHQ = false, $quality = 75, $keepType = true)
{
    global $client, $cfgClient;

    $deleteAfter = false;

    $sRelativeImg = str_replace($cfgClient[$client]['upl']['path'], '', $img);
    if (cApiDbfs::isDbfs($sRelativeImg)) {
        // This check should be faster than a file existance check
        $dbfs = new cApiDbfsCollection();

        $file = basename($sRelativeImg);

        $dbfs->writeToFile($sRelativeImg, $cfgClient[$client]['cache_path'] . $file);

        $img = $cfgClient[$client]['cache_path'] . $file;
        $deleteAfter = true;
    } else if (!file_exists($img)) {
        // Try with upload string
        if (file_exists($cfgClient[$client]['upl']['path'].$img) && !is_dir($cfgClient[$client]['upl']['path'].$img)) {
            $img = $cfgClient[$client]['upl']['path'].$img;
        } else {
            // No, it's neither in the upload directory nor in the dbfs. return.
            return false;
        }
    }

    $filename = $img;
    $filetype = substr($filename, strlen($filename) -4,4);

    $mxdAvImgEditingPosibility = checkImageEditingPosibility();
    switch ($mxdAvImgEditingPosibility) {
        case '1': // gd1
            $method = 'gd1';
            if (!function_exists('imagecreatefromgif') && $filetype == '.gif') {
                $method = 'failure';
            }
            break;
        case '2': //gd2
            $method = 'gd2';
            if (!function_exists('imagecreatefromgif') && $filetype == '.gif') {
                $method = 'failure';
            }
            break;
        case 'im': //imagemagick
            $method = 'im';
            break;
        case '0':
            $method = 'failure';
            break;
        default:
            $method = 'failure';
            break;
    }

    switch ($method) {
        case 'gd1':
            $return = capiImgScaleLQ($img, $maxX, $maxY, $crop, $expand, $cacheTime, $quality, $keepType);
            break;
        case 'gd2':
            $return = capiImgScaleHQ($img, $maxX, $maxY, $crop, $expand, $cacheTime, $quality, $keepType);
            break;
        case 'im':
            $return = capiImgScaleImageMagick($img, $maxX, $maxY, $crop, $expand, $cacheTime, $quality, $keepType);
            break;
        case 'failure':
            $return = str_replace($cfgClient[$client]['path']['frontend'], $cfgClient[$client]['path']['htmlpath'], $img);
            break;
    }

    if ($deleteAfter == true) {
        unlink($img);
    }

    return $return;
}

/**
 * Check possible image editing functionality
 *
 * @return  mixed  Information about installed image editing extensions/tools
 * <pre>
 * - 'im'  ImageMagick is available
 * - '2'   GD library version 2 is available
 * - '1'   GD library version 1 is available
 * - '0'   Nothing could detected
 * </pre>
 */
function checkImageEditingPosibility() {
    if (isImageMagickAvailable()) {
        return 'im';
    }

    if (!extension_loaded('gd')) {
        return '0';
    }

    if (function_exists('gd_info')) {
        $arrGDInformations = gd_info();
        if (preg_match('#([0-9\.])+#', $arrGDInformations['GD Version'], $strGDVersion)) {
            if ($strGDVersion[0] >= '2') {
                return '2';
            }
            return '1';
        }
        return '1';
    }
    return '1';
}

/**
 * Returns new calculated dimensions of a target image.
 * @param   int   $x
 * @param   int   $y
 * @param   int   $maxX
 * @param   int   $maxY
 * @param   bool  $expand
 * @return  array  Index 0 is target X and index 1 is target Y
 */
function capiImageGetTragetDimensions($x, $y, $maxX, $maxY, $expand) {
    // Calculate the aspect ratio
#    $aspectXY = $x / $y;
#    $aspectYX = $y / $x;

    if (($maxX / $x) < ($maxY / $y)) {
        $targetY = $y * ($maxX / $x);
        $targetX = round($maxX);

        // Force wished height
        if ($targetY < $maxY) {
            $targetY = ceil($targetY);
        } else {
            $targetY = floor($targetY);
        }
    } else {
        $targetX = $x * ($maxY / $y);
        $targetY = round($maxY);

        // Force wished width
        if ($targetX < $maxX) {
            $targetX = ceil($targetX);
        } else {
            $targetX = floor($targetX);
        }
    }

    if ($expand == false && (($targetX > $x) || ($targetY > $y))) {
        $targetX = $x;
        $targetY = $y;
    }

    $targetX = ($targetX != 0) ? $targetX : 1;
    $targetY = ($targetY != 0) ? $targetY : 1;

    return array($targetX, $targetY);
}

/**
 * Returns cache file name.
 * @param   string  $md5
 * @param   string  $filetype
 * @param   bool    $keepType
 * @return  string
 */
function capiImageGetCacheFileName($md5, $filetype, $keepType) {
    // Create the target file names for web and server
    if ($keepType) {
        // Should we keep the file type?
        // Just using switch if someone likes to add other types
        switch (strtolower($filetype)) {
            case '.png':
                $fileName = $md5 . '.png';
                break;
            case '.gif':
                $fileName = $md5 . '.gif';
                break;
            default:
                $fileName = $md5 . '.jpg';
        }
    } else { // No... use .jpg
        $fileName = $md5 . '.jpg';
    }
    return $fileName;
}

/**
 * Validates cache version of a image.
 * @param   string  $cacheFile
 * @param   int    $cacheTime
 * @return  bool   Returns true, if cache file exists and7or is still valid or false
 */
function capiImageCheckCachedImageValidity($cacheFile, $cacheTime) {
    // Check if the file exists. If it does, check if the file is valid.
    if (file_exists($cacheFile)) {
        if ($cacheTime == 0) {
            // Do not check expiration date
            return true;
        } else if (!function_exists('md5_file')) {
            // TODO: Explain why this is still needed ... or remove it
            if ((filemtime($cacheFile) + (60 * $cacheTime)) < time()) {
                // Cache time expired, unlink the file
                unlink($cacheFile);
            } else {
                // Return the web file name
                return true;
            }
        } else {
            return true;
        }
    }

    return false;
}

?>