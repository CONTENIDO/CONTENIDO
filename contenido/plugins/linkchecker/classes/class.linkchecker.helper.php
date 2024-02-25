<?php

/**
 * This file contains some helper functions for the linkchecker plugin.
 *
 * @since      CONTENIDO 4.10.2
 * @package    Plugin
 * @subpackage Linkchecker
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class cLinkcheckerHelper
 */
class cLinkcheckerHelper
{

    /**
     * Regular expression for image types
     */
    const IMAGE_TYPES_REGEX = '/^.*\.(bmp|gif|jpeg|jpg|png|psd|svg|tif|tiff|webp)$/i';

    /**
     * Regular expression for uri types
     */
    const URI_TYPES_REGEX = '#^(file://|ftp://|http://|https://|www.).*$#i';

    /**
     * Sorts passed error list by sort type.
     *
     * @param array $errors List of link checker errors
     * @param string $sortBy Sort type
     *
     * @return array Sorted link checker errors
     */
    public static function sortErrors(array $errors, string $sortBy): array
    {
        if ($sortBy === 'nameart') {
            $aNameArt = [];
            foreach ($errors as $key => $aRow) {
                $aNameArt[$key] = $aRow['nameart'];
            }
            array_multisort($errors, SORT_ASC, SORT_STRING, $aNameArt);
        } elseif ($sortBy === 'namecat') {
            $aNameCat = [];
            foreach ($errors as $key => $aRow) {
                $aNameCat[$key] = $aRow['namecat'];
            }
            array_multisort($errors, SORT_ASC, SORT_STRING, $aNameCat);
        } elseif ($sortBy === 'wronglink') {
            $aWrongLink = [];
            foreach ($errors as $key => $aRow) {
                $aWrongLink[$key] = $aRow['url'];
            }
            array_multisort($errors, SORT_ASC, SORT_STRING, $aWrongLink);
        } elseif ($sortBy === 'error_type') {
            $aErrorType = [];
            foreach ($errors as $key => $aRow) {
                $aErrorType[$key] = $aRow['error_type'];
            }
            array_multisort($errors, SORT_ASC, SORT_STRING, $aErrorType);
        }

        return $errors;
    }

    /**
     * Checks whether the passed url references an image resource.
     * Note, it checks for limited image types, see {@see cLinkcheckerHelper::IMAGE_TYPES_REGEX}.
     *
     * @param string $url The url to check
     * @return bool
     */
    public static function urlIsImage(string $url): bool
    {
        return (bool)preg_match(self::IMAGE_TYPES_REGEX, $url);
    }

    /**
     * Checks whether the passed url matches an uri.
     * Note, it checks for limited uri formats, see {@see cLinkcheckerHelper::URI_TYPES_REGEX}.
     *
     * @param string $url The url to check
     * @return bool
     */
    public static function urlIsUri(string $url): bool
    {
        return (bool)preg_match(self::URI_TYPES_REGEX, $url);
    }

}
