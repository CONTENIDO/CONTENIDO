<?php
/**
 * This file contains CONTENIDO Image API functions.
 *
 * If you are planning to add a function, please make sure that:
 * 1.) The function is in the correct place
 * 2.) The function is documented
 * 3.) The function makes sense and is generically usable
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Timo Hummel
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

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
function cApiImgScaleGetMD5CacheFile($sImg, $iMaxX, $iMaxY, $bCrop, $bExpand) {
    if (!cFileHandler::exists($sImg)) {
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
function cApiImgScaleLQ($img, $maxX, $maxY, $crop = false, $expand = false, $cacheTime = 10, $quality = 75, $keepType = false) {
    global $cfgClient, $client;

    if (!cFileHandler::exists($img)) {
        return false;
    }

    $filename = $img;
    $maxX = (int) $maxX;
    $maxY = (int) $maxY;
    $cacheTime = (int) $cacheTime;
    $quality = (int) $quality;

    if ($quality <= 0 || $quality > 100) {
        $quality = 75;
    }

    $frontendURL = cRegistry::getFrontendUrl();
    $filetype = substr($filename, strlen($filename) - 4, 4);
    $md5 = cApiImgScaleGetMD5CacheFile($img, $maxX, $maxY, $crop, $expand);
    $cfileName = cApiImageGetCacheFileName($md5, $filetype, $keepType);
    $cacheFile = $cfgClient[$client]['cache']['path'] . $cfileName;
    $webFile = $frontendURL . 'cache/' . $cfileName;

    if (cApiImageCheckCachedImageValidity($cacheFile, $cacheTime)) {
        return $webFile;
    }

    // Get out which file we have
    switch (strtolower($filetype)) {
        case '.gif': $function = 'imagecreatefromgif';
            break;
        case '.png': $function = 'imagecreatefrompng';
            break;
        case '.jpg': $function = 'imagecreatefromjpeg';
            break;
        case 'jpeg': $function = 'imagecreatefromjpeg';
            break;
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

    list($targetX, $targetY) = cApiImageGetTargetDimensions($x, $y, $maxX, $maxY, $expand);

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
 * Note: Image cropping calculates center of the image!
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
function cApiImgScaleHQ($img, $maxX, $maxY, $crop = false, $expand = false, $cacheTime = 10, $quality = 75, $keepType = true)
{
    global $cfgClient, $client;

    if (!cFileHandler::exists($img)) {
        return false;
    }

    $filename  = $img;
    $maxX      = (int) $maxX;
    $maxY      = (int) $maxY;
    $cacheTime = (int) $cacheTime;
    $quality   = (int) $quality;

    if ($quality <= 0 || $quality > 100) {
        $quality = 75;
    }

    $frontendURL = cRegistry::getFrontendUrl();
    $filetype  = cFileHandler::getExtension($filename);
    $md5       = cApiImgScaleGetMD5CacheFile($img, $maxX, $maxY, $crop, $expand);
    $cfileName = cApiImageGetCacheFileName($md5, $filetype, $keepType);
    $cacheFile = $cfgClient[$client]['cache']['path'] . $cfileName;
    $webFile   = $frontendURL . 'cache/' . $cfileName;

    if (cApiImageCheckCachedImageValidity($cacheFile, $cacheTime)) {
        return $webFile;
    }

    // Get out which file we have
    switch (strtolower($filetype)) {
        case 'gif': $function = 'imagecreatefromgif'; break;
        case 'png': $function = 'imagecreatefrompng'; break;
        case 'jpg': $function = 'imagecreatefromjpeg'; break;
        case 'jpeg': $function = 'imagecreatefromjpeg'; break;
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

    list($targetX, $targetY) = cApiImageGetTargetDimensions($x, $y, $maxX, $maxY, $expand);

    // Create the target image with the target size, resize it afterwards.
    if ($crop) {
        // Create the target image with the max size, crop it afterwards.
        $targetImage = imagecreatetruecolor($maxX, $maxY);
        //calculate canter of the image
        $srcX = ($x - $maxX) / 2;
        $srcY = ($y - $maxY) /2;
        //crop image from center
        imagecopy($targetImage, $imageHandle, 0, 0, $srcX, $srcY, $maxX, $maxY);
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

    return $webFile;
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
function cApiImgScaleImageMagick($img, $maxX, $maxY, $crop = false, $expand = false, $cacheTime = 10, $quality = 75, $keepType = false) {
    global $cfg, $cfgClient, $client;

    if (!cFileHandler::exists($img)) {
        return false;
    } elseif (isFunctionDisabled('escapeshellarg') || isFunctionDisabled('exec')) {
        return false;
    }

    $filename = $img;
    $maxX = (int) $maxX;
    $maxY = (int) $maxY;
    $cacheTime = (int) $cacheTime;
    $quality = (int) $quality;

    if ($quality <= 0 || $quality > 100) {
        $quality = 75;
    }

    $frontendURL = cRegistry::getFrontendUrl();
    $filetype = substr($filename, strlen($filename) - 4, 4);
    $md5 = cApiImgScaleGetMD5CacheFile($img, $maxX, $maxY, $crop, $expand);
    $cfileName = cApiImageGetCacheFileName($md5, $filetype, $keepType);
    $cacheFile = $cfgClient[$client]['cache']['path'] . $cfileName;
    $webFile = $frontendURL . 'cache/' . $cfileName;

    if (cApiImageCheckCachedImageValidity($cacheFile, $cacheTime)) {
        return $webFile;
    }

    list($x, $y) = @getimagesize($filename);
    if ($x == 0 || $y == 0) {
        return false;
    }

    list($targetX, $targetY) = cApiImageGetTargetDimensions($x, $y, $maxX, $maxY, $expand);

    // If is animated gif resize first frame
    if ($filetype == '.gif') {
        if (cApiImageIsAnimGif($filename)) {
            $filename .= '[0]';
        }
    }

    // Try to execute convert
    $output = array();
    $retVal = 0;
    $program = escapeshellarg($cfg['images']['image_magick']['path'] . 'convert');
    $source = escapeshellarg($filename);
    $destination = escapeshellarg($cacheFile);
    if ($crop) {
        $cmd = "'{$program}' -gravity center -quality {$quality} -crop {$maxX}x{$maxY}+1+1 '{$source}' '{$destination}'";
    } else {
        $cmd = "'{$program}' -quality {$quality} -geometry {$targetX}x{$targetY} '{$source}' '{$destination}'";
    }

    exec($cmd, $output, $retVal);

    if (!cFileHandler::exists($cacheFile)) {
        return false;
    } else {
        return $webFile;
    }
}

/**
 * Check if gif is animated, uses "identify" of ImageMagick.
 *
 * @param   string  $sFile  file path
 * @return  bool  True (gif is animated)/ false (single frame gif)
 */
function cApiImageIsAnimGif($sFile) {
    global $cfg;

    if (isFunctionDisabled('escapeshellarg') || isFunctionDisabled('exec')) {
        // Function escapeshellarg or exec is disabled
        return false;
    } elseif ('im' != cApiImageCheckImageEditingPosibility()) {
        // ImageMagick ist not available
        return false;
    }

    $output = array();
    $retval = 0;
    $program = escapeshellarg($cfg['images']['image_magick']['path'] . 'identify');
    $source = escapeshellarg($sFile);

    exec("'{$program}' '{$source}'", $output, $retval);

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
function cApiImgScale($img, $maxX, $maxY, $crop = false, $expand = false, $cacheTime = 10, $wantHQ = false, $quality = 75, $keepType = true) {
    global $client, $cfgClient;

    $deleteAfter = false;

    $sRelativeImg = str_replace($cfgClient[$client]['upl']['path'], '', $img);
    if (cApiDbfs::isDbfs($sRelativeImg)) {
        // This check should be faster than a file existance check
        $dbfs = new cApiDbfsCollection();

        $file = basename($sRelativeImg);

        $dbfs->writeToFile($sRelativeImg, $cfgClient[$client]['cache']['path'] . $file);

        $img = $cfgClient[$client]['cache']['path'] . $file;
        $deleteAfter = true;
    } else if (!cFileHandler::exists($img)) {
        // Try with upload string
        if (cFileHandler::exists($cfgClient[$client]['upl']['path'] . $img) && !is_dir($cfgClient[$client]['upl']['path'] . $img)) {
            $img = $cfgClient[$client]['upl']['path'] . $img;
        } else {
            // No, it's neither in the upload directory nor in the dbfs. return.
            return false;
        }
    }

    $filename = $img;
    $filetype = substr($filename, strlen($filename) - 4, 4);

    $mxdAvImgEditingPosibility = cApiImageCheckImageEditingPosibility();
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
            $return = cApiImgScaleLQ($img, $maxX, $maxY, $crop, $expand, $cacheTime, $quality, $keepType);
            break;
        case 'gd2':
            $return = cApiImgScaleHQ($img, $maxX, $maxY, $crop, $expand, $cacheTime, $quality, $keepType);
            break;
        case 'im':
            $return = cApiImgScaleImageMagick($img, $maxX, $maxY, $crop, $expand, $cacheTime, $quality, $keepType);
            break;
        case 'failure':
            $frontendURL = cRegistry::getFrontendUrl();
            $return = str_replace(cRegistry::getFrontendPath(), $frontendURL, $img);
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
 * - 'im'  ImageMagick is available and usage is enabled
 * - '2'   GD library version 2 is available
 * - '1'   GD library version 1 is available
 * - '0'   Nothing could detected
 * </pre>
 */
function cApiImageCheckImageEditingPosibility() {
    global $cfg;

    if ($cfg['images']['image_magick']['use']) {
        if (cApiIsImageMagickAvailable()) {
            return 'im';
        }
    }

    if (!extension_loaded('gd')) {
        return '0';
    }

    if (function_exists('gd_info')) {
        $sGDVersion = '';
        $aGDInformations = gd_info();
        if (preg_match('#([0-9\.])+#', $aGDInformations['GD Version'], $sGDVersion)) {
            if ($sGDVersion[0] >= '2') {
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
function cApiImageGetTargetDimensions($x, $y, $maxX, $maxY, $expand) {
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
function cApiImageGetCacheFileName($md5, $filetype, $keepType) {
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
function cApiImageCheckCachedImageValidity($cacheFile, $cacheTime) {
    // Check if the file exists. If it does, check if the file is valid.
    if (cFileHandler::exists($cacheFile)) {
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

/**
 * Checks if ImageMagick is available
 *
 * @return bool true if ImageMagick is available
 */
function cApiIsImageMagickAvailable() {
    global $cfg;
    static $imagemagickAvailable = null;

    // if the check has already been executed, just return the result
    if (is_bool($imagemagickAvailable)) {
        return $imagemagickAvailable;
    }

    // check, if escapeshellarg or exec function is disabled, we need both
    if (isFunctionDisabled('escapeshellarg') || isFunctionDisabled('exec')) {
        $imagemagickAvailable = false;
        return $imagemagickAvailable;
    }

    // otherwise execute the IM check
    $output = array();
    $retval = 0;
    $imPath = $cfg['images']['image_magick']['path'];
    $program = escapeshellarg($cfg['images']['image_magick']['path'] . 'convert');
    @exec("'{$program}' -version", $output, $retval);

    if (!is_array($output) || count($output) == 0) {
        // exec is probably disabled, so we assume IM to be unavailable
        $imagemagickAvailable = false;
    } else {
        // otherwise output contains the output of the command "convert version"
        // if IM is available, it contains the string "ImageMagick"
        if (strpos($output[0], 'ImageMagick') !== false) {
            $imagemagickAvailable = true;
        } else {
            $imagemagickAvailable = false;
        }
    }

    return $imagemagickAvailable;
}

?>