<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Area management class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package CONTENIDO Backend Classes
 * @version 1.0.1
 * @author
 *
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 *
 * @deprecated 2012-09-06 Constant has been replaced by the class constant
 *             cDatatypeDateTime::FORMAT_UNIX
 */
define('cDateTime_UNIX', 1);

/**
 *
 * @deprecated 2012-09-06 Constant has been replaced by the class constant
 *             cDatatypeDateTime::FORMAT_ISO
 */
define('cDateTime_ISO', 2);

/**
 *
 * @deprecated 2012-09-06 Constant has been replaced by the class constant
 *             cDatatypeDateTime::FORMAT_LOCALE
 */
define('cDateTime_Locale', 3);

/**
 *
 * @deprecated 2012-09-06 Constant has been replaced by the class constant
 *             cDatatypeDateTime::FORMAT_LOCALE_TIMEONLY
 */
define('cDateTime_Locale_TimeOnly', 4);

/**
 *
 * @deprecated 2012-09-06 Constant has been replaced by the class constant
 *             cDatatypeDateTime::FORMAT_LOCALE_DATEONLY
 */
define('cDateTime_Locale_DateOnly', 5);

/**
 *
 * @deprecated 2012-09-06 Constant has been replaced by the class constant
 *             cDatatypeDateTime::FORMAT_MYSQL
 */
define('cDateTime_MySQL', 6);

/**
 *
 * @deprecated 2012-09-06 Constant has been replaced by the class constant
 *             cDatatypeDateTime::FORMAT_CUSTOM
 */
define('cDateTime_Custom', 99);

/**
 *
 * @deprecated 2012-09-06 Constant has been replaced by the class constant
 *             cDatatypeDateTime::SUNDAY
 */
define('cDateTime_Sunday', 0);

/**
 *
 * @deprecated 2012-09-06 Constant has been replaced by the class constant
 *             cDatatypeDateTime::MONDAY
 */
define('cDateTime_Monday', 1);

/**
 *
 * @deprecated 2012-09-06 Constant has been replaced by the class constant
 *             cDatatypeDateTime::TUESDAY
 */
define('cDateTime_Tuesday', 2);

/**
 *
 * @deprecated 2012-09-06 Constant has been replaced by the class constant
 *             cDatatypeDateTime::WEDNESDAY
 */
define('cDateTime_Wednesday', 3);

/**
 *
 * @deprecated 2012-09-06 Constant has been replaced by the class constant
 *             cDatatypeDateTime::THURSDAY
 */
define('cDateTime_Thursday', 4);

/**
 *
 * @deprecated 2012-09-06 Constant has been replaced by the class constant
 *             cDatatypeDateTime::FRIDAY
 */
define('cDateTime_Friday', 5);

/**
 *
 * @deprecated 2012-09-06 Constant has been replaced by the class constant
 *             cDatatypeDateTime::SATURDAY
 */
define('cDateTime_Saturday', 6);
class cDatatypeDateTime extends cDatatype {

    protected $_iFirstDayOfWeek;

    /**
     * The UNIX Timestamp is the amount of seconds passed since Jan 1 1970
     * 00:00:00 GMT
     *
     * @var int
     */
    const FORMAT_UNIX = 1;

    /**
     * The ISO Date format is CCYY-MM-DD HH:mm:SS
     *
     * @var int
     */
    const FORMAT_ISO = 2;

    /**
     * The locale format, as specified in the CONTENIDO backend
     *
     * @var int
     */
    const FORMAT_LOCALE = 3;

    /**
     * The locale format, as specified in the CONTENIDO backend
     *
     * @var int
     */
    const FORMAT_LOCALE_TIMEONLY = 4;

    /**
     * The locale format, as specified in the CONTENIDO backend
     *
     * @var int
     */
    const FORMAT_LOCALE_DATEONLY = 5;

    /**
     * The MySQL Timestamp is CCYYMMDDHHmmSS
     *
     * @var int
     */
    const FORMAT_MYSQL = 6;

    /**
     * Custom format
     *
     * @var int
     */
    const FORMAT_CUSTOM = 99;

    /**
     * Sunday
     *
     * @var int
     */
    const SUNDAY = 0;

    /**
     * Monday
     *
     * @var int
     */
    const MONDAY = 1;

    /**
     * Tuesday
     *
     * @var int
     */
    const TUESDAY = 2;

    /**
     * Wednesday
     *
     * @var int
     */
    const WEDNESDAY = 3;

    /**
     * Thursday
     *
     * @var int
     */
    const THURSDAY = 4;

    /**
     * Friday
     *
     * @var int
     */
    const FRIDAY = 5;

    /**
     * Saturday
     *
     * @var int
     */
    const SATURDAY = 6;

    /* This datatype stores its date format in ISO */
    public function __construct() {
        $this->setTargetFormat(self::FORMAT_LOCALE);
        $this->setSourceFormat(self::FORMAT_UNIX);

        $this->_iYear = 1970;
        $this->_iMonth = 1;
        $this->_iDay = 1;
        $this->_iHour = 0;
        $this->_iMinute = 0;
        $this->_iSecond = 0;

        $this->setFirstDayOfWeek(self::MONDAY);
        parent::__construct();
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cDatatypeDateTime() {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }

    public function setCustomTargetFormat($targetFormat) {
        $this->_sCustomTargetFormat = $targetFormat;
    }

    public function setCustomSourceFormat($sourceFormat) {
        $this->_sCustomSourceFormat = $sourceFormat;
    }

    public function setSourceFormat($cSourceFormat) {
        $this->_cSourceFormat = $cSourceFormat;
    }

    public function setTargetFormat($cTargetFormat) {
        $this->_cTargetFormat = $cTargetFormat;
    }

    public function setYear($iYear) {
        $this->_iYear = $iYear;
    }

    public function getYear() {
        return ($this->_iYear);
    }

    public function setMonth($iMonth) {
        $this->_iMonth = $iMonth;
    }

    public function getMonth() {
        return ($this->_iMonth);
    }

    public function setDay($iDay) {
        $this->_iDay = $iDay;
    }

    public function getDay() {
        return ($this->_iDay);
    }

    public function getMonthName($iMonth) {
        switch ($iMonth) {
            case 1:
                return i18n("January");
            case 2:
                return i18n("February");
            case 3:
                return i18n("March");
            case 4:
                return i18n("April");
            case 5:
                return i18n("May");
            case 6:
                return i18n("June");
            case 7:
                return i18n("July");
            case 8:
                return i18n("August");
            case 9:
                return i18n("September");
            case 10:
                return i18n("October");
            case 11:
                return i18n("November");
            case 12:
                return i18n("December");
        }
    }

    public function getDayName($iDayOfWeek) {
        switch ($iDayOfWeek) {
            case 0:
                return i18n("Sunday");
            case 1:
                return i18n("Monday");
            case 2:
                return i18n("Tuesday");
            case 3:
                return i18n("Wednesday");
            case 4:
                return i18n("Thursday");
            case 5:
                return i18n("Friday");
            case 6:
                return i18n("Saturday");
            case 7:
                return i18n("Sunday");
            default:
                return false;
        }
    }

    public function getDayOrder() {
        $aDays = array(
            0,
            1,
            2,
            3,
            4,
            5,
            6
        );
        $aRemainderDays = array_splice($aDays, 0, $this->_iFirstDayOfWeek);

        $aReturnDays = array_merge($aDays, $aRemainderDays);

        return ($aReturnDays);
    }

    public function getNumberOfMonthDays($iMonth = false, $iYear = false) {
        if ($iMonth === false) {
            $iMonth = $this->_iMonth;
        }

        if ($iYear === false) {
            $iYear = $this->_iYear;
        }

        return date("t", mktime(0, 0, 0, $iMonth, 1, $iYear));
    }

    public function setFirstDayOfWeek($iDay) {
        $this->_iFirstDayOfWeek = $iDay;
    }

    public function getFirstDayOfWeek() {
        return $this->_iFirstDayOfWeek;
    }

    public function getLeapDay() {
        return end($this->getDayOrder());
    }

    public function set($value, $iOverrideFormat = false) {
        if ($value == "") {
            return;
        }

        if ($iOverrideFormat !== false) {
            $iFormat = $iOverrideFormat;
        } else {
            $iFormat = $this->_cSourceFormat;
        }

        switch ($iFormat) {
            case self::FORMAT_UNIX:
                $sTemporaryTimestamp = $value;
                $this->_iYear = date("Y", $sTemporaryTimestamp);
                $this->_iMonth = date("m", $sTemporaryTimestamp);
                $this->_iDay = date("d", $sTemporaryTimestamp);
                $this->_iHour = date("H", $sTemporaryTimestamp);
                $this->_iMinute = date("i", $sTemporaryTimestamp);
                $this->_iSecond = date("s", $sTemporaryTimestamp);

                break;
            case self::FORMAT_ISO:
                $sTemporaryTimestamp = strtotime($value);
                $this->_iYear = date("Y", $sTemporaryTimestamp);
                $this->_iMonth = date("m", $sTemporaryTimestamp);
                $this->_iDay = date("d", $sTemporaryTimestamp);
                $this->_iHour = date("H", $sTemporaryTimestamp);
                $this->_iMinute = date("i", $sTemporaryTimestamp);
                $this->_iSecond = date("s", $sTemporaryTimestamp);
                break;
            case self::FORMAT_MYSQL:
                $sTimeformat = 'YmdHis';

                $targetFormat = str_replace('.', '\.', $sTimeformat);
                $targetFormat = str_replace('d', '([0-9]{2,2})', $targetFormat);
                $targetFormat = str_replace('m', '([0-9]{2,2})', $targetFormat);
                $targetFormat = str_replace('Y', '([0-9]{4,4})', $targetFormat);
                $targetFormat = str_replace('H', '([0-9]{2,2})', $targetFormat);
                $targetFormat = str_replace('i', '([0-9]{2,2})', $targetFormat);
                $targetFormat = str_replace('s', '([0-9]{2,2})', $targetFormat);
                // Match the placeholders
                preg_match_all('/([a-zA-Z])/', $sTimeformat, $placeholderRegs);

                // Match the date values
                preg_match('/' . $targetFormat . '/', $value, $dateRegs);

                $finalDate = array();

                // Map date entries to placeholders
                foreach ($placeholderRegs[0] as $key => $placeholderReg) {
                    if (isset($dateRegs[$key])) {
                        $finalDate[$placeholderReg] = $dateRegs[$key + 1];
                    }
                }

                // Assign placeholders + data to the object's member variables
                foreach ($finalDate as $placeHolder => $value) {
                    switch ($placeHolder) {
                        case "d":
                            $this->_iDay = $value;
                            break;
                        case "m":
                            $this->_iMonth = $value;
                            break;
                        case "Y":
                            $this->_iYear = $value;
                            break;
                        case "H":
                            $this->_iHour = $value;
                            break;
                        case "i":
                            $this->_iMinute = $value;
                            break;
                        case "s":
                            $this->_iSecond = $value;
                            break;
                        default:
                            break;
                    }
                }

                break;
            case self::FORMAT_CUSTOM:
                // Build a regular expression

                $sourceFormat = str_replace('.', '\.', $this->_sCustomSourceFormat);
                $sourceFormat = str_replace('%d', '([0-9]{2,2})', $sourceFormat);
                $sourceFormat = str_replace('%m', '([0-9]{2,2})', $sourceFormat);
                $sourceFormat = str_replace('%Y', '([0-9]{4,4})', $sourceFormat);

                // Match the placeholders
                preg_match_all('/(%[a-zA-Z])/', $this->_sCustomSourceFormat, $placeholderRegs);

                // Match the date values
                preg_match('/' . $sourceFormat . '/', $value, $dateRegs);

                $finalDate = array();

                // Map date entries to placeholders
                foreach ($placeholderRegs[0] as $key => $placeholderReg) {
                    if (isset($dateRegs[$key])) {
                        $finalDate[$placeholderReg] = $dateRegs[$key + 1];
                    }
                }

                // Assign placeholders + data to the object's member variables
                foreach ($finalDate as $placeHolder => $value) {
                    switch ($placeHolder) {
                        case "%d":
                            $this->_iDay = $value;
                            break;
                        case "%m":
                            $this->_iMonth = $value;
                            break;
                        case "%Y":
                            $this->_iYear = $value;
                            break;
                        default:
                            break;
                    }
                }
                break;
            default:
                break;
        }
    }

    /**
     *
     * @throws cInvalidArgumentException if the given format is not supported
     *         yet
     */
    public function get($iOverrideFormat = false) {
        if ($iOverrideFormat !== false) {
            $iFormat = $iOverrideFormat;
        } else {
            $iFormat = $this->_cSourceFormat;
        }

        switch ($iFormat) {
            case self::FORMAT_ISO:
                $sTemporaryTimestamp = mktime($this->_iHour, $this->_iMinute, $this->_iSecond, $this->_iMonth, $this->_iDay, $this->_iYear);
                return date("Y-m-d H:i:s", $sTemporaryTimestamp);
                break;
            case self::FORMAT_UNIX:
                $sTemporaryTimestamp = mktime($this->_iHour, $this->_iMinute, $this->_iSecond, $this->_iMonth, $this->_iDay, $this->_iYear);
                return ($sTemporaryTimestamp);
                break;
            case self::FORMAT_CUSTOM:
                return strftime($this->_sCustomSourceFormat, mktime($this->_iHour, $this->_iMinute, $this->_iSecond, $this->_iMonth, $this->_iDay, $this->_iYear));
                break;
            case self::FORMAT_MYSQL:
                $sTemporaryTimestamp = mktime($this->_iHour, $this->_iMinute, $this->_iSecond, $this->_iMonth, $this->_iDay, $this->_iYear);
                return date("YmdHis", $sTemporaryTimestamp);
                break;
            default:
                throw new cInvalidArgumentException('The given format is not supported yet');
                break;
        }
    }

    public function render($iOverrideFormat = false) {
        if ($iOverrideFormat !== false) {
            $iFormat = $iOverrideFormat;
        } else {
            $iFormat = $this->_cTargetFormat;
        }

        switch ($iFormat) {
            case self::FORMAT_LOCALE_TIMEONLY:
                $sTimeformat = getEffectiveSetting("dateformat", "time", "H:i:s");
                return date($sTimeformat, mktime($this->_iHour, $this->_iMinute, $this->iSecond, $this->_iMonth, $this->_iDay, $this->_iYear));
            case self::FORMAT_LOCALE_DATEONLY:
                $sTimeformat = getEffectiveSetting("dateformat", "date", "Y-m-d");
                return date($sTimeformat, mktime($this->_iHour, $this->_iMinute, $this->iSecond, $this->_iMonth, $this->_iDay, $this->_iYear));
            case self::FORMAT_LOCALE:
                $sTimeformat = getEffectiveSetting("dateformat", "full", "Y-m-d H:i:s");
                return date($sTimeformat, mktime($this->_iHour, $this->_iMinute, $this->iSecond, $this->_iMonth, $this->_iDay, $this->_iYear));
            case self::FORMAT_CUSTOM:
                return strftime($this->_sCustomTargetFormat, mktime($this->_iHour, $this->_iMinute, $this->_iSecond, $this->_iMonth, $this->_iDay, $this->_iYear));
                break;
        }
    }

    public function parse($value) {
        if ($value == "") {
            return;
        }

        switch ($this->_cTargetFormat) {
            case self::FORMAT_ISO:
                $sTemporaryTimestamp = strtotime($value);
                $this->_iYear = date("Y", $sTemporaryTimestamp);
                $this->_iMonth = date("m", $sTemporaryTimestamp);
                $this->_iDay = date("d", $sTemporaryTimestamp);
                $this->_iHour = date("H", $sTemporaryTimestamp);
                $this->_iMinute = date("i", $sTemporaryTimestamp);
                $this->_iSecond = date("s", $sTemporaryTimestamp);
                break;
            case self::FORMAT_LOCALE_DATEONLY:
                $sTimeformat = getEffectiveSetting('dateformat', 'date', 'Y-m-d');

                $targetFormat = str_replace('.', '\.', $sTimeformat);
                $targetFormat = str_replace('d', '([0-9]{2,2})', $targetFormat);
                $targetFormat = str_replace('m', '([0-9]{2,2})', $targetFormat);
                $targetFormat = str_replace('Y', '([0-9]{4,4})', $targetFormat);

                // Match the placeholders
                preg_match_all('/([a-zA-Z])/', $sTimeformat, $placeholderRegs);

                // Match the date values
                preg_match('/' . $targetFormat . '/', $value, $dateRegs);

                $finalDate = array();

                // Map date entries to placeholders
                foreach ($placeholderRegs[0] as $key => $placeholderReg) {
                    if (isset($dateRegs[$key])) {
                        $finalDate[$placeholderReg] = $dateRegs[$key + 1];
                    }
                }

                // Assign placeholders + data to the object's member variables
                foreach ($finalDate as $placeHolder => $value) {
                    switch ($placeHolder) {
                        case "d":
                            $this->_iDay = $value;
                            break;
                        case "m":
                            $this->_iMonth = $value;
                            break;
                        case "Y":
                            $this->_iYear = $value;
                            break;
                        default:
                            break;
                    }
                }
                break;
            case self::FORMAT_LOCALE:
                $sTimeformat = getEffectiveSetting('dateformat', 'full', 'Y-m-d H:i:s');

                $targetFormat = str_replace('.', '\.', $sTimeformat);
                $targetFormat = str_replace('d', '([0-9]{2,2})', $targetFormat);
                $targetFormat = str_replace('m', '([0-9]{2,2})', $targetFormat);
                $targetFormat = str_replace('Y', '([0-9]{4,4})', $targetFormat);

                // Match the placeholders
                preg_match_all('/(%[a-zA-Z])/', $this->_sCustomTargetFormat, $placeholderRegs);

                // Match the date values
                preg_match('/' . $targetFormat . '/', $value, $dateRegs);

                $finalDate = array();

                // Map date entries to placeholders
                foreach ($placeholderRegs[0] as $key => $placeholderReg) {
                    if (isset($dateRegs[$key])) {
                        $finalDate[$placeholderReg] = $dateRegs[$key + 1];
                    }
                }

                // Assign placeholders + data to the object's member variables
                foreach ($finalDate as $placeHolder => $value) {
                    switch ($placeHolder) {
                        case "%d":
                            $this->_iDay = $value;
                            break;
                        case "%m":
                            $this->_iMonth = $value;
                            break;
                        case "%Y":
                            $this->_iYear = $value;
                            break;
                        default:
                            break;
                    }
                }
                break;
            case self::FORMAT_CUSTOM:
                // Build a regular expression

                $targetFormat = str_replace('.', '\.', $this->_sCustomTargetFormat);
                $targetFormat = str_replace('%d', '([0-9]{2,2})', $targetFormat);
                $targetFormat = str_replace('%m', '([0-9]{2,2})', $targetFormat);
                $targetFormat = str_replace('%Y', '([0-9]{4,4})', $targetFormat);

                // Match the placeholders
                preg_match_all('/(%[a-zA-Z])/', $this->_sCustomTargetFormat, $placeholderRegs);

                // Match the date values
                preg_match('/' . $targetFormat . '/', $value, $dateRegs);

                $finalDate = array();

                // Map date entries to placeholders
                foreach ($placeholderRegs[0] as $key => $placeholderReg) {
                    if (isset($dateRegs[$key])) {
                        $finalDate[$placeholderReg] = $dateRegs[$key + 1];
                    }
                }

                // Assign placeholders + data to the object's member variables
                foreach ($finalDate as $placeHolder => $value) {
                    switch ($placeHolder) {
                        case "%d":
                            $this->_iDay = $value;
                            break;
                        case "%m":
                            $this->_iMonth = $value;
                            break;
                        case "%Y":
                            $this->_iYear = $value;
                            break;
                        default:
                            break;
                    }
                }
                break;
            default:
                break;
        }
    }

}
