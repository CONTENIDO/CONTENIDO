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