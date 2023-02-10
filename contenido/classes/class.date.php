<?php
/**
 * This file contains the date utility class.
 *
 * @package    Core
 * @subpackage Util
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Date helper class.
 *
 * @since CONTENIDO 4.10.2
 * @package Core
 * @subpackage Util
 */
class cDate {

    /**
     * @var int Maximum value for a day.
     */
    const MAX_DAY_VALUE = 31;

    /**
     * @var int Maximum value for a month.
     */
    const MAX_MONTH_VALUE = 12;

    /**
     * Normalizes a value for the usage as day, ensures to return a two digit
     * representation of a day.
     *
     * - Empty value will return '00'
     * - Values up to '9' will be preceded by a '0', e.g. '09'
     * - Values between 0 - 31 will be returned as string
     * - Other values will be returned as they are
     *
     * @param string|int|mixed $value
     * @return string|int|mixed
     */
    public static function padDay($value) {
        return self::_padDayOrMonth($value, self::MAX_DAY_VALUE);
    }

    /**
     * Normalizes a value for the usage as month, ensures to return a two digit
     * representation of a month.
     *
     * - Empty value will return '00'
     * - Values up to '9' will be preceded by a '0', e.g. '09'
     * - Values between 0 - 12 will be returned as string
     * - Other values will be returned as they are
     *
     * @param string|int|mixed $value
     * @return string|int|mixed
     */
    public static function padMonth($value) {
        return self::_padDayOrMonth($value, self::MAX_MONTH_VALUE);
    }

    /**
     * Normalizes a value for the usage as day/month, ensures to return
     * a two digit representation of a day/month.
     *
     * Same behaviour as {@see cDate::padDay()}
     *
     * @param string|int|mixed $value
     * @return string|int|mixed
     */
    public static function padDayOrMonth($value) {
        return self::_padDayOrMonth($value, self::MAX_DAY_VALUE);
    }

    /**
     * Returns the translated month name for to the given numeric month value.
     *
     * @param int $month
     *         Numeric month value
     *
     * @return string|null
     *         Translated month name
     *
     * @throws cException
     */
    public static function getCanonicalMonth($month) {
        $map = [
            i18n("January"), i18n("February"), i18n("March"), i18n("April"),
            i18n("May"), i18n("June"), i18n("July"), i18n("August"),
            i18n("September"), i18n("October"), i18n("November"), i18n("December"),
        ];

        // $map is 0-based, so 1 has to be subtracted from the given $month
        $index = is_numeric($month) ? cSecurity::toInteger($month) - 1 : null;

        return array_key_exists($index, $map) ? $map[$index] : null;
    }

    /**
     * Returns the translated weekday name for to the given numeric weekday value.
     *
     * This function assumes that monday is the first day of the week!
     *
     * @param int $day
     *         Numeric weekday value
     *
     * @return string|null
     *         Translated weekday name
     *
     * @throws cException
     */
    public static function getCanonicalDay($weekday) {
        $map = [
            i18n("Monday"), i18n("Tuesday"), i18n("Wednesday"), i18n("Thursday"),
            i18n("Friday"), i18n("Saturday"), i18n("Sunday"),
        ];

        // $map is 0-based, so 1 has to be subtracted from the given $weekday
        $index = is_numeric($weekday) ? cSecurity::toInteger($weekday) - 1 : null;

        return array_key_exists($index, $map) ? $map[$index] : null;
    }

    /**
     * Returns a formatted date and/or time-string according to the current settings
     *
     * @param mixed $timestamp
     *         A timestamp. If no value is given the current time will be used.
     * @param bool  $date
     *         If true the date will be included in the string
     * @param bool  $time
     *         If true the time will be included in the string
     *
     * @return string
     *         The formatted time string.
     *
     * @throws cDbException|cException
     */
    public static function formatDatetime($timestamp = '', $date = false, $time = false) {
        if ($timestamp == '') {
            $timestamp = time();
        } else {
            $timestamp = strtotime($timestamp);
        }

        if ($date && !$time) {
            $ret = date(getEffectiveSetting('dateformat', 'date', 'Y-m-d'), $timestamp);
        } else if ($time && !$date) {
            $ret = date(getEffectiveSetting('dateformat', 'time', 'H:i:s'), $timestamp);
        } else {
            $ret = date(getEffectiveSetting('dateformat', 'full', 'Y-m-d H:i:s'), $timestamp);
        }
        return $ret;
    }

    /**
     * @param string|int|mixed $value
     * @param int $maxValue
     * @return float|int|mixed|string
     */
    protected static function _padDayOrMonth($value, $maxValue) {
        if (!is_string($value) && !is_numeric($value)) {
            return $value;
        }
        $tmpValue = cSecurity::toInteger($value);
        if ($tmpValue < 0 || $tmpValue > $maxValue) {
            return $value;
        }
        return str_pad(trim(cSecurity::toString($value)), 2, '0', STR_PAD_LEFT);
    }

}