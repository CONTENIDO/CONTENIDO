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
class cDate
{

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
     *
     * @param string $value
     * @return string
     */
    public static function padDay(string $value): string
    {
        return self::_padDayOrMonth($value, self::MAX_DAY_VALUE);
    }

    /**
     * Normalizes a value for the usage as month, ensures to return a two digit
     * representation of a month.
     *
     * - Empty value will return '00'
     * - Values up to '9' will be preceded by a '0', e.g. '09'
     *
     * @param string $value
     * @return string
     */
    public static function padMonth(string $value): string
    {
        return self::_padDayOrMonth($value, self::MAX_MONTH_VALUE);
    }

    /**
     * Normalizes a value for the usage as day/month, ensures to return
     * a two digit representation of a day/month.
     *
     * Same behaviour as {@see cDate::padDay()}
     *
     * @param string $value
     * @return string
     */
    public static function padDayOrMonth(string $value): string
    {
        return self::_padDayOrMonth($value, self::MAX_DAY_VALUE);
    }

    /**
     * Returns the translated month name for to the given numeric month value.
     *
     * @param int|null|mixed $month
     *         Numeric month value
     *
     * @return string|null
     *         Translated month name
     *
     * @throws cException
     */
    public static function getCanonicalMonth($month)
    {
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
     * @param int|null|mixed $weekday
     *         Numeric weekday value
     *
     * @return string|null
     *         Translated weekday name
     *
     * @throws cException
     */
    public static function getCanonicalDay($weekday)
    {
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
     * @param string|null|mixed $timestamp
     *         A timestamp. If no value is given the current time will be used.
     * @param bool $date
     *         If true the date will be included in the string
     * @param bool $time
     *         If true the time will be included in the string
     *
     * @return string
     *         The formatted time string.
     *
     * @throws cDbException|cException
     */
    public static function formatDatetime($timestamp = '', bool $date = false, bool $time = false): string
    {
        if (empty($timestamp)) {
            $timestamp = time();
        } else {
            $timestamp = strtotime(cSecurity::toString($timestamp));
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
     * Formats timestamp to date string according to format definitions.
     * Supports both `strftime()` and `strftime()` formats.
     *
     * Passed `strftime()` format will be formatted using `strftime()`,
     * when the PHP version is lower than 8.1.0, otherwise it uses
     * `date()` as a fallback.
     *
     * @TODO We should use `IntlDateFormatter::format()` to support localized dates for PHP >= 8.1.0, but this requires the `ext-intl` extension.
     *
     * @param string $format Either `strftime()` format or `date()` format.
     * @param int|null $timestamp  Unix timestamp, current time will be used if omitted.
     * @return false|string The formatted date string or false on error
     */
    public static function formatToDate(string $format, int $timestamp = null)
    {
        if (empty($format)) {
            return false;
        }

        // All strftime formats start with a '%', check for this!
        if ($format[0] === '%') {
            // strftime() is deprecated as of PHP 8.1, check the version
            if (version_compare(PHP_VERSION, '8.1.0') >= 0) {
                cDeprecated('The function `strftime()` is deprecated as of PHP 8.1.0, '
                    . 'and the passed format string was detected as a `strftime()` format. '
                    . 'The `date()` function will be used as a fallback, but without '
                    . 'localization support.');
                // Use date() as fallback
                return date(self::strftimeToDate($format), $timestamp);
            } else {
                // Use strftime for PHP < 8.1.0
                return strftime($format, $timestamp);
            }
        } else {
            return date($format, $timestamp);
        }
    }

    /**
     * Checks if passed date string represents an empty date.
     * Following values will be interpreted as empty date:
     * - NULL
     * - '' (empty string)
     * - '0000-00-00'
     * - '0000-00-00 00:00:00'
     *
     * @param string|null|mixed $dateString
     * @return bool
     */
    public static function isEmptyDate($dateString): bool
    {
        return (
            is_null($dateString) ||
            is_string($dateString) && (
                empty($dateString) || $dateString === '0000-00-00' || $dateString === '0000-00-00 00:00:00'
            )
        );
    }

    /**
     * Converts deprecated `strftime` format to `date´ format.
     *
     * @param string $format The strftime format to convert
     *
     * @return bool|string Converted date format or false
     */
    public static function strftimeToDate(string $format)
    {
        return self::_formatTo($format, 'date');
    }

    /**
     * Convert date/time format between `date()` and `strftime()`.
     *
     * Timezone conversion is done for Unix.Windows users must exchange %z and %Z.
     *
     * Unsupported date formats:
     * S, n, t, L, B, G, u, e, I, P, Z, c, r
     *
     * Unsupported strftime formats:
     * %U, %W, %C, %g, %r, %R, %T, %X, %c, %D, %F, %x
     *
     * Example:
     * Convert `%A, %B %e, %Y, %l:%M %P` to `l, F j, Y, g:i a`,
     * and vice versa for "Saturday, March 10, 2001, 5:16 pm"
     *
     * Link:
     * http://php.net/manual/en/function.strftime.php#96424
     *
     * Source:
     * https://gist.github.com/mcaskill/02636e5970be1bb22270
     * Credits to:
     * https://gist.github.com/mcaskill
     *
     * IMPORTANT:
     * This is only a temporary solution, in the medium term we should
     * completely avoid using `strftime()`formats.
     *
     * @param string $format The format to parse.
     * @param string $syntax The format's syntax. Either 'strf' for
     *                       `strtime()` or 'date' for `date()`.
     *
     * @return bool|string Returns a string formatted according $syntax
     *      using the given $format or `false`.
     */
    protected static function _formatTo(string $format, string $syntax)
    {
        // http://php.net/manual/en/function.strftime.php
        $strfSyntax = [
            // Day - no strf eq : S (created one called %O)
            '%O', '%d', '%a', '%e', '%A', '%u', '%w', '%j',
            // Week - no date eq : %U, %W
            '%V',
            // Month - no strf eq : n, t
            '%B', '%m', '%b', '%h', '%-m',
            // Year - no strf eq : L; no date eq : %C, %g
            '%G', '%Y', '%y',
            // Time - no strf eq : B, G, u; no date eq : %r, %R, %T, %X
            '%P', '%p', '%l', '%I', '%H', '%k', '%M', '%S',
            // Timezone - no strf eq : e, I, P, Z
            '%z', '%Z',
            // Full Date / Time - no strf eq : c, r; no date eq : %c, %D, %F, %x
            '%s'
        ];

        // http://php.net/manual/en/function.date.php
        $dateSyntax = [
            'S', 'd', 'D', 'j', 'l', 'N', 'w', 'z',
            'W',
            'F', 'm', 'M', 'M',  'n',
            'o', 'Y', 'y',
            'a', 'A', 'g', 'h', 'H', 'G', 'i', 's',
            'O', 'T',
            'U'
        ];

        switch ($syntax) {
            case 'date':
                $from = $strfSyntax;
                $to   = $dateSyntax;
                break;
            case 'strf':
                $from = $dateSyntax;
                $to   = $strfSyntax;
                break;
            default:
                return false;
        }

        $pattern = array_map(function($s) {
            return '/(?<!\\\\|\%)' . $s . '/';
        }, $from);

        return preg_replace($pattern, $to, $format);
    }

    /**
     * @param string $value
     * @param int $maxValue
     * @return string
     */
    protected static function _padDayOrMonth(string $value, int $maxValue): string
    {
        $tmpValue = cSecurity::toInteger($value);
        if ($tmpValue < 0 || $tmpValue > $maxValue) {
            return $value;
        }
        return str_pad(trim(cSecurity::toString($value)), 2, '0', STR_PAD_LEFT);
    }

}