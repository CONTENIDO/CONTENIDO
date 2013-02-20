<?php

/**
 *
 * @package Classes
 * @subpackage Helper
 * @version SVN Revision $Rev:$
 * @author marcus.gnass
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

// require_once('FirePHPCore/fb.php');

/**
 * This class provides some security related functionality.
 *
 * @author marcus.gnass
 */
class Sec {

    /**
     * Cleans a user provided value in order to prevent XSS.
     * To achieve this all tags are stripped and remaining brackets and quotes
     * are transformed to entities.
     *
     * @param string $sIn to be cleaned
     * @return string
     */
    public static function xss($sIn) {

        $sOut = $sIn;
        $sOut = strip_tags($sOut);
        $sOut = htmlentities($sOut, ENT_QUOTES);

        return $sOut;

    }

}

/**
 *
 * @author marcus.gnass
 */
class Url {

    /**
     *
     * @param str $sDate
     * @param str $sFormat
     * @param str $sTimezone
     */
    public static function addParam($sUrl, $sName, $sValue = '') {

        $sUrl .= '&amp;' . urlencode($sName);

        if (strlen($sValue)) {
            $sUrl .= '=' . urlencode($sValue);
        }

        return $sUrl;

    }

    /**
     *
     * @param str $sDate
     * @param str $sFormat
     * @param str $sTimezone
     */
    public static function assureProtocol($sUrl, $sProtocol = 'http') {

        if (false === strpos($sUrl, '://')) {
            $sUrl = "$sProtocol://$sUrl";
        }

        return $sUrl;

    }

}

/**
 *
 * @author marcus.gnass
 */
class Util {

    /**
     * Prints a HTML encoded message in a P element.
     *
     * @param mixed $in
     * @param string $format 'raw', 'print_r' || 'var_dump' (default)
     * @param bool $htmlentities
     */
    public static function msg($msg) {

        echo '<p style="background:yellow">' . htmlentities($msg) . '</p>';
        flush();

    }

    /**
     * Prints a variables content surrounded by a PRE element.
     *
     * @param mixed $in
     * @param string $format 'raw', 'print_r' || 'var_dump' (default)
     * @param bool $htmlentities
     */
    public static function dump($in, $format = 'var_dump', $htmlentities = true) {

        // handle objects differently
        // if (is_object($in)) {
        // if ($in instanceof Item) {
        // $in = $in->toObject();
        // }
        // $in = get_object_vars($in);
        // }

        $in = self::getDump($in, $format);
        if (true === $htmlentities) {
            $in = htmlentities($in, ENT_COMPAT | ENT_HTML401, 'UTF-8');
        }

        echo '<pre class="util-dump">';
        echo $in;
        echo '</pre>';

    }

    /**
     * Gets & returns the var_dump representation of a variable.
     *
     * @param mixed $in
     * @param string $format 'raw', 'print_r' || 'var_dump' (default)
     * @return string
     */
    public static function getDump($in, $format = 'var_dump') {

        ob_start();
        switch ($format) {
            case 'raw':
                echo $in;
                break;
            case 'print_r':
                print_r($in);
                break;
            case 'var_dump':
                var_dump($in);
                break;
            default:
                echo 'unknown format';
        }
        $out = ob_get_contents();
        ob_end_clean();

        return $out;

    }

    /**
     * echo Util::isBackend() ? 'BE' : 'FE';
     */
    public static function isBackend() {

        $backendSessionId = cRegistry::getBackendSessionId();
        $backendSessionId = trim($backendSessionId);
        if (NULL === $backendSessionId) {
            return false;
        }
        if (0 === strlen($backendSessionId)) {
            return false;
        }

        return true;

    }

    /**
     */
    public static function isBackendEditMode() {

        if (false === self::isBackend()) {
            return false;
        }

        // TODO when is $edit set to true?
        global $edit;
        if ('true' !== $edit) {
            return false;
        }

        return true;

    }

    /**
     */
    public static function isFrontend() {
        return !self::isBackend();
    }

    /**
     *
     * @param string $message
     * @param string $file
     * @param int $line
     */
    public static function log($message, $file = NULL, $line = NULL) {

        static $start = 0;
        if (0 == $start) {
            $start = microtime(true);
        }
        $delta = microtime(true) - $start;

        // create name of logfile
        $cfg = cRegistry::getConfig();
        $filename = $cfg['path']['contenido_logs'] . 'errorlog.txt';

        // extend message with optional prefix
        $prefix = number_format($delta * 1000, 0) . 'ms: ';
        if (NULL !== $file) {
            $prefix .= $file;
            if (NULL !== $line) {
                $prefix .= ':' . $line;
            }
            $prefix .= ' ';
        }

        // log message
        $log = new cLog(cLogWriter::factory('file', array(
            'destination' => $filename
        )));
        $log->info($prefix . $message);

    }

    /**
     *
     * @param Exception $e
     */
    public static function logDump($var, $file = NULL, $line = NULL) {
        $dump = self::getDump($var);
        self::log($dump, $file, $line);
    }

    /**
     *
     * @param Exception $e
     */
    public static function logException(Exception $e) {

        $cfg = cRegistry::getConfig();

        $log = new cLog(cLogWriter::factory('file', array(
            'destination' => $cfg['path']['contenido_logs'] . 'errorlog.txt'
        )), cLog::ERR);

        $log->err($e->getMessage());
        $log->err($e->getTraceAsString());

    }

    /**
     * Returns an error message in a paragraph with a CSS class ui-state-error.
     * This class belongs to the jQuery CSS framework.
     *
     * @param string $sMessage
     */
    public static function formatError($sMessage) {
        return '<p class="ui-state-error">' . $sMessage . '</p>';
    }

    /**
     * TODO build method to display erro & info box and just call it from here
     *
     * @param Exception $e
     * @param bool $showTrace if trace should be displayed too
     */
    public static function displayException(Exception $e, $showTrace = false) {

        if (true) {
            // error box
            $class = "ui-state-error";
            $icon = "ui-icon-alert";
        } else {
            // info box
            $class = "ui-state-highlight";
            $icon = "ui-icon-info";
        }

        echo '<div class="ui-widget">';
        echo '<div class="' . $class . ' ui-corner-all">';
        echo '<p>';
        echo '<span class="ui-icon ' . $icon . '"></span>';
        // echo '<strong>Exception</strong>';
        echo $e->getMessage();
        if (true === $showTrace) {
            echo '<pre style="overflow: auto">';
            echo htmlentities($e->getTraceAsString());
            echo '</pre>';
        }
        echo '</p>';
        echo '</div>';
        echo '</div>';

    }

    /**
     * Creates a notification widget in order to display an exception message in
     * backend.
     *
     * @param Exception $e
     * @return string
     */
    public static function notifyException(Exception $e) {

        $cGuiNotification = new cGuiNotification();
        $level = cGuiNotification::LEVEL_ERROR;
        $message = $e->getMessage();

        return $cGuiNotification->returnNotification($level, $message);

    }

    /**
     *
     * @param string $sIn string to clean
     * @return string cleaned string
     */
    public static function clean($sIn) {

        $sIn = strip_tags($sIn);
        // $sIn = html_entity_decode($sIn);
        $sIn = str_replace('&nbsp;', ' ', $sIn);
        $sIn = trim($sIn);

        return $sIn;

    }

    /**
     * Fieser Hack! Die Ausgabe von internationalisierten Daten
     * ist unter PHP echt nicht einfach!
     *
     * @param int $iTimestamp
     */
    public static function getFullDate($iTimestamp) {

        // funktioniert nur in englisch!
        // return date('l, d.m.Y', $iTimestamp);

        $locale = setlocale(LC_TIME, 'de_DE');
        if (false !== $locale) {
            return strftime("%A, %d.%m.%Y", $iTimestamp);
        }

        $sFullDate = strftime("%d.%m.%Y", $iTimestamp);

        // try to add localized weekday
        $weekday = date('N', $iTimestamp);
        if (false !== $weekday) {
            $aWeekdays = array(
                'Montag',
                'Dienstag',
                'Mittwoch',
                'Donnerstag',
                'Freitag',
                'Samstag',
                'Sonntag'
            );
            $sWeekday = $aWeekdays[(int) $weekday - 1];
            $sFullDate = "$sWeekday, $sFullDate";
        }

        return $sFullDate;

    }

    /**
     * Fills a given template with all data from a given array.
     *
     * @param Template $tpl
     * @param array $aValues
     * @param string $which
     */
    public static function fillTemplate(Template $tpl, array $aData, $which = 's') {
        foreach ($aData as $sKey => $sValue) {
            $tpl->set('s', $sKey, $sValue);
        }
    }

    /**
     *
     * @param str $sDate
     * @param str $sFormat
     * @param str $sTimezone
     */
    public static function sdate2datetime($sDate, $sFormat = 'd.m.Y H:i:s', $sTimezone = 'Europe/Berlin') {

        if (!is_null($sTimezone)) {
            date_default_timezone_set($sTimezone);
        }

        $oDate = DateTime::createFromFormat($sFormat, $sDate);

        return $oDate;

    }

    /**
     *
     * @param string $sDate
     * @param string $sFormat
     * @param string $sTimezone
     * @return boolean number
     */
    public static function sdate2timestamp($sDate, $sFormat, $sTimezone) {

        $oDate = self::sdate2datetime($sDate, $sFormat, $sTimezone);
        if (!$oDate) {
            return false;
        }

        $iTimestamp = $oDate->getTimestamp();

        return $iTimestamp;

    }

    /**
     *
     * @param int $iTimestamp
     * @param str $sFormat
     * @param str $sTimezone
     */
    public static function timestamp2sdate($iTimestamp, $sFormat = 'd.m.Y H:i:s', $sTimezone = 'Europe/Berlin') {

        if (!is_null($sTimezone)) {
            date_default_timezone_set($sTimezone);
        }

        $sDate = date($sFormat, $iTimestamp);

        return $sDate;

    }

    /**
     *
     * @param unknown_type $sEmail
     */
    function is_valid_email($sEmail) {

        $bResult = filter_var($sEmail, FILTER_VALIDATE_EMAIL);

        return $bResult;

    }

    /**
     * If not in backend edit mode this method returns an empty string so if
     * this is used as value for a template token the tokens name is not
     * displayed.
     *
     * TODO This method could be moved to class AbstractModule instead.
     *
     * @param string $sCaption
     * @param string $sType
     * @param int $iIndex
     * @param bool $bObligatory
     * @return string
     */
    public static function getEditLabel($sCaption, $sType = NULL, $iIndex = NULL, $bObligatory = false) {

        cDeprecated('use AbstractModule->_getEditLabel() instead.');

        $lbl = '';
        if (self::isBackendEditMode()) {
            $lbl = '<div class="con-label-edit"';
            if (NULL != $sType || NULL != $iIndex) {
                $lbl .= ' title="' . $sType . ' #' . strval($iIndex) . '"';
            }
            $lbl .= '>';
            $lbl .= $sCaption;
            if ($bObligatory) {
                $lbl .= '*';
            }
            $lbl .= ':</div>';
        }

        return $lbl;

    }

    /**
     *
     * @param int $idupl
     * @return string folder & name of file or an empty string
     */
    public static function getFilepathFromIdupl($idupl) {

        $idupl = (int) $idupl;

        if (0 === $idupl) {
            return '';
        }

        $dirname = $filename = '';

        $upl = new cApiUpload($idupl);
        if ($upl->get('idupl') == $idupl) {
            $dirname = $upl->get('dirname');
            $filename = $upl->get('filename');
        }

        return $dirname . $filename;

    }

    /**
     *
     * @param int $idcat
     * @param int $idcatLast
     * @param bool $includeLanguage
     * @return array
     */
    public static function getCategoryPath($idcat, $idcatLast = 0) {

        $path = array();
        $idcatCurrent = (int) $idcat;
        while (0 < $idcatCurrent && (int) $idcatLast != $idcatCurrent) {
            $category = new cApiCategory($idcatCurrent);
            $path[] = $category;
            $idcatCurrent = $category->get('parentid');
        }

        $path = array_reverse($path);

        return $path;

    }

    /**
     *
     * @param string $path
     * @param oct $mode
     */
    public static function createPath($path, $mode) {

        $oldumask = umask(0);

        if (!is_dir($path)) {
            mkdir($path, $mode, true);
        }

        umask($oldumask);

    }

    /**
     *
     * @param string $url
     */
    public static function url2fs($url) {

        global $cfg, $cfgClient, $client;

        // e.g. http://www.dorma.com/us/
        $htmlpath = $cfgClient[$client]['path']['htmlpath'];

        // e.g. /home/dorma/public_html/www.dorma.com/us/
        $frontend = $cfgClient[$client]['path']['frontend'];

        $fs = str_replace($htmlpath, $frontend, $url);

        return $fs;

    }

    /**
     *
     * @param string $fs
     */
    public static function fs2url($fs) {

        global $cfg, $cfgClient, $client;

        // e.g. /home/dorma/public_html/www.dorma.com/us/
        $frontend = $cfgClient[$client]['path']['frontend'];

        // e.g. http://www.dorma.com/us/
        $htmlpath = $cfgClient[$client]['path']['htmlpath'];

        $url = str_replace($frontend, $htmlpath, $fs);

        return $url;

    }

    /**
     * $cfgClient[$client]['upload'] => 'upload/'
     * $cfgClient[$client]['upl']['path'] => '/var/www/dorma/htdocs/us/upload/'
     * $cfgClient[$client]['upl']['htmlpath'] => 'http://dorma.local/us/upload/'
     *
     * @param string $url
     */
    public static function getFilesystemPathFromUrl($url) {

        global $cfgClient, $client;

        $htmlPath = $cfgClient[$client]['upl']['htmlpath'];
        $path = $cfgClient[$client]['upl']['path'];

        $path = str_replace($htmlPath, $path, $url);

        return $path;

    }

    /**
     *
     * @param int $idartlang
     */
    public static function getIdartFromIdartlang($idartlang) {

        $item = new cApiArticleLanguage($idartlang);

        return $item->get('idart');

    }

    /**
     *
     * @param int $idart
     */
    public static function getIdcatFromIdart($idart) {

        $item = new cApiCategoryArticle();
        $item->loadBy('idart', $idart);

        return $item->get('idcat');

    }

    /**
     *
     * @param int $idartlang
     */
    public static function getIdcatFromIdartlang($idartlang) {

        $idart = Util::getIdartFromIdartlang($idartlang);
        $idcat = Util::getIdcatFromIdart($idart);

        return $idcat;

    }

    /**
     *
     * @param int $idartlang
     */
    public static function getIdclientFromIdart($myIdart) {

        // TODO
        throw new Exception('getIdclientFromIdart is not yet implemented');

    }

    /**
     *
     * @param string $path
     */
    public static function getSecurePath($path) {
        $csvHttpsIdarts = explode(',', getEffectiveSetting('https', 'idarts', ''));

        $idart = cRegistry::getArticleId();
        if (is_array($csvHttpsIdarts)) {
            if (in_array($idart, $csvHttpsIdarts)) {
                $path = str_replace('http://', 'https://', $path);
            }
        }

        return $path;
    }
}

class MyValidator {

    /**
     * Validate an email address.
     * Provide email address (raw input)
     * Returns true if the email address has the email
     * address format and the domain exists.
     *
     * ^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$
     *
     * /^[_a-z0-9-]+(\\.[_a-z0-9-]+)*@[a-z0-9-]+(\\.[a-z0-9-]+)*(\\.[a-z]{2,3})$/
     *
     *
     * @see http://www.linuxjournal.com/article/9585
     */
    public static function validEmail($email) {

        $isValid = true;
        $atIndex = strrpos($email, "@");

        if (is_bool($atIndex) && !$atIndex) {
            return false;
        }

        $domain = substr($email, $atIndex + 1);
        $local = substr($email, 0, $atIndex);
        $localLen = strlen($local);
        $domainLen = strlen($domain);

        if ($localLen < 1 || $localLen > 64) {
            // local part length exceeded
            $isValid = false;
        } else if ($domainLen < 1 || $domainLen > 255) {
            // domain part length exceeded
            $isValid = false;
        } else if ($local[0] == '.' || $local[$localLen - 1] == '.') {
            // local part starts or ends with '.'
            $isValid = false;
        } else if (preg_match('/\\.\\./', $local)) {
            // local part has two consecutive dots
            $isValid = false;
        } else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
            // character not valid in domain part
            $isValid = false;
        } else if (preg_match('/\\.\\./', $domain)) {
            // domain part has two consecutive dots
            $isValid = false;
        } else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\", "", $local))) {
            // character not valid in local part unless
            // local part is quoted
            if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\", "", $local))) {
                $isValid = false;
            }
        }

        if ($isValid && !(checkdnsrr($domain, "MX") || checkdnsrr($domain, "A"))) {
            // domain not found in DNS
            $isValid = false;
        }

        return $isValid;

    }

}

?>