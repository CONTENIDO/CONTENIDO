<?php

/**
 * This file contains the CONTENIDO system related functions.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Unknown
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Clears CONTENIDO standard errorlog.txt
 *
 * @return string
 *         Message if clearing was successfull or not
 * 
 * @throws cException
 * @throws cInvalidArgumentException
 */
function emptyLogFile() {
    global $cfg, $notification, $auth;

    if (cString::findFirstPos($auth->auth["perm"], "sysadmin") === false) {
        return $notification->returnNotification("error", i18n("Can't clear error log : Access is denied!"));
    }

    $tmp_notification = false;

    // clear errorlog.txt
    $filename = $cfg['path']['contenido_logs'] . 'errorlog.txt';

    if (cFileHandler::exists($filename) && is_writeable($filename)) {
        cFileHandler::truncate($filename);
        $tmp_notification = $notification->returnNotification("ok", i18n("Error log successfully cleared!"));
    } elseif (cFileHandler::exists($filename) && !is_writeable($filename)) {
        $tmp_notification = $notification->returnNotification("error", i18n("Can't clear error log : Access is denied!"));
    }

    return $tmp_notification;
}

/**
 * Grabs phpinfo() output.
 *
 * @deprecated [2015-05-21]
 *         This method is no longer supported (no replacement)
 *             
 * @return string
 *         HTML output of phpinfo()
 * 
 * @throws cInvalidArgumentException
 */
function phpInfoToHtml() {
    cDeprecated('This method is deprecated and is not needed any longer');

    // get output
    ob_start();
    phpinfo();
    $phpInfoToHtml = ob_get_contents();
    ob_end_clean();

    return $phpInfoToHtml;
}

/**
 * Check if the user has a right for a defined client.
 *
 * @param int $client
 *         client id
 * @return bool
 *         Wether user has access or not
 */
function systemHavePerm($client) {
    global $auth;

    if (!isset($auth->perm['perm'])) {
        $auth->perm['perm'] = '';
    }

    $userPerm = explode(',', $auth->auth['perm']);

    if (in_array('sysadmin', $userPerm)) { // is user sysadmin ?
        return true;
    } elseif (in_array('admin[' . $client . ']', $userPerm)) { // is user admin for this client ?
        return true;
    } elseif (in_array('client[' . $client . ']', $userPerm)) { // has user access to this client ?
        return true;
    }
    return false;
}

/**
 * Check for valid ip adress
 *
 * @param string $strHostAdress
 *         IP adress
 * @return bool
 *         If string is a valid ip or not
 */
function isIPv4($strHostAdress) {
    // ip pattern needed for validation
    $ipPattern = "([0-9]|1?\d\d|2[0-4]\d|25[0-5])";
    if (preg_match("/^$ipPattern\.$ipPattern\.$ipPattern\.$ipPattern?$/", $strHostAdress)) { // ip is valid
        return true;
    }
    return false;
}

/**
 * must be done
 *
 * @deprecated [2015-05-21]
 *         This method is no longer supported (no replacement)
 *
 * @param string $strConUrl
 *         CONTENIDO fullhtmlPath
 * @param string $strBrowserUrl
 *         current browser string
 *
 * @return string
 *         Status of path comparement
 * 
 * @throws cInvalidArgumentException
 */
function checkPathInformation($strConUrl, $strBrowserUrl) {
    cDeprecated('This method is deprecated and is not needed any longer');

    // parse url
    $arrConUrl = parse_url($strConUrl);
    $arrBrowserUrl = parse_url($strBrowserUrl);

    if (isIPv4($arrConUrl['host'])) { // is
        if (isIPv4($arrBrowserUrl['host'])) { // is
            if (compareUrlStrings($arrConUrl, $arrBrowserUrl)) {
                return '1';
            }

            return '2';
        } else { // isn't
            $arrBrowserUrl['host'] = gethostbyname($arrBrowserUrl['host']);
            if (!isIPv4($arrBrowserUrl['host'])) {
                return '3';
            }

            if (compareUrlStrings($arrConUrl, $arrBrowserUrl)) {
                return '1';
            }

            return '2';
        }
    } else { // isn't
        if (isIPv4($arrBrowserUrl['host'])) { //is
            $tmpAddr = gethostbyaddr($arrBrowserUrl['host']);
            $arrBrowserUrl['host'] = str_replace('-', '.', cString::getPartOfString($tmpAddr, 0, cString::findFirstPos($tmpAddr, ".")));

            if (isIPv4($arrBrowserUrl['host'])) {
                return '3';
            }

            if (compareUrlStrings($arrConUrl, $arrBrowserUrl, true)) {
                return '1';
            }

            return '2';
        } else { // isn't
            if (compareUrlStrings($arrConUrl, $arrBrowserUrl)) {
                return '1';
            }

            return '2';
        }
    }
}

/**
 * Check path informations.
 *
 * Checks two path informations against each other to get potential nonconformities.
 *
 * @deprecated [2015-05-21]
 *         This method is no longer supported (no replacement)
 *
 * @param array $arrConUrl
 * @param array $arrBrowserUrl
 * @param bool  $isIP
 *
 * @return bool
 * 
 * @throws cInvalidArgumentException
 */
function compareUrlStrings($arrConUrl, $arrBrowserUrl, $isIP = false) {
    cDeprecated('This method is deprecated and is not needed any longer');

    // && $isIP == false
    // remove 'www.' if needed
    if (cString::findFirstPos($arrConUrl['host'], 'www.') == 0 || cString::findFirstPos($arrBrowserUrl['host'], 'www.') == 0) {
        $arrConUrl['host'] = str_replace('www.', '', $arrConUrl);
        $arrBrowserUrl['host'] = str_replace('www.', '', $arrBrowserUrl);
    }

    $strConUrl = $arrConUrl['scheme'] . '://' . $arrConUrl['host'] . $arrConUrl['path'];
    $strBrowserUrl = $arrBrowserUrl['scheme'] . '://' . $arrBrowserUrl['host'] . $arrBrowserUrl['path'];

    if (strcmp($strConUrl, $strBrowserUrl) != 0) {
        return false;
    }
    return true;
}
