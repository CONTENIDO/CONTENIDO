<?php
/**
 * This file contains the CONTENIDO system related functions.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Unknown
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Clears CONTENIDO standard errorlog.txt
 * @return  string   Message if clearing was successfull or not
 */
function emptyLogFile() {
    global $cfg, $notification, $auth;

    if (strpos($auth->auth["perm"], "sysadmin") === false) {
        return $notification->returnNotification("error", i18n("Can't clear error log : Access is denied!"));
    }

    $tmp_notification = false;

    // clear errorlog.txt
    $filename = $cfg['path']['contenido_logs'] . 'errorlog.txt';

    if (cFileHandler::exists($filename) && is_writeable($filename)) {
        cFileHandler::truncate($filename);
        $tmp_notification = $notification->returnNotification("info", i18n("Error log successfully cleared!"));
    } else if (cFileHandler::exists($filename) && !is_writeable($filename)) {
        $tmp_notification = $notification->returnNotification("error", i18n("Can't clear error log : Access is denied!"));
    }

    return $tmp_notification;
}

/**
 * Grabs phpinfo() output.
 *
 * @return string  HTML output of phpinfo()
 */
function phpInfoToHtml() {
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
 * @param  int  $client client id
 * @return  bool  Wether user has access or not
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
 * @param   string  $strHostAdress  IP adress
 * @return  bool If string is a valid ip or not
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
 * @param string  $strConUrl  CONTENIDO fullhtmlPath
 * @param string  $strBrowserUrl  current browser string
 * @return string  Status of path comparement
 */
function checkPathInformation($strConUrl, $strBrowserUrl) {
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
            $arrBrowserUrl['host'] = str_replace('-', '.', substr($tmpAddr, 0, strpos($tmpAddr, ".")));

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
 * check path informations
 *
 * checks two path informations against each other to get potential nonconformities
 */
function compareUrlStrings($arrConUrl, $arrBrowserUrl, $isIP = false) {
    // && $isIP == false
    // remove 'www.' if needed
    if (strpos($arrConUrl['host'], 'www.') == 0 || strpos($arrBrowserUrl['host'], 'www.') == 0) {
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
