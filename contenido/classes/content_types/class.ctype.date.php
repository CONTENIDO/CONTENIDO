<?php
/**
 * This file contains the cContentTypeDate class.
 *
 * @package Core
 * @subpackage Content Type
 * @version SVN Revision $Rev:$
 *
 * @author Bilal Arslan, Timo Trautmann, Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Content type CMS_DATE which allows the editor to select a date from a
 * calendar and a date format.
 * The selected date is then shown in the selected format.
 *
 * @package Core
 * @subpackage Content Type
 */
class cContentTypeDate extends cContentTypeAbstract {

    /**
     * The possible JS date formats in which the selected dates can be
     * displayed.
     *
     * @var array
     */
    private $_dateFormatsJs;

    /**
     * The possible PHP date formats in which the selected dates can be
     * displayed.
     *
     * @var array
     */
    private $_dateFormatsPhp;

    /**
     * Initialises class attributes and handles store events.
     *
     * @param string $rawSettings the raw settings in an XML structure or as
     *        plaintext
     * @param integer $id ID of the content type, e.g. 3 if CMS_DATE[3] is
     *        used
     * @param array $contentTypes array containing the values of all content
     *        types
     * @return void
     */
    public function __construct($rawSettings, $id, array $contentTypes) {
        // change attributes from the parent class and call the parent
        // constructor
        $this->_type = 'CMS_DATE';
        $this->_prefix = 'date';
        $this->_settingsType = 'xml';
        $this->_formFields = array(
            'date_timestamp',
            'date_format'
        );
        parent::__construct($rawSettings, $id, $contentTypes);

        // initialise the date formats
        $this->_dateFormatsPhp = array(
            '{"dateFormat": "d.m.Y", "timeFormat": ""}' => $this->_formatDate('d.m.Y'),
            '{"dateFormat": "D, d.m.Y", "timeFormat": ""}' => $this->_formatDate('D, d.m.Y'),
            '{"dateFormat": "d. F Y", "timeFormat": ""}' => $this->_formatDate('d. F Y'),
            '{"dateFormat": "Y-m-d", "timeFormat": ""}' => $this->_formatDate('Y-m-d'),
            '{"dateFormat": "d/F/Y", "timeFormat": ""}' => $this->_formatDate('d/F/Y'),
            '{"dateFormat": "d/m/y", "timeFormat": ""}' => $this->_formatDate('d/m/y'),
            '{"dateFormat": "F y", "timeFormat": ""}' => $this->_formatDate('F y'),
            '{"dateFormat": "F-y", "timeFormat": ""}' => $this->_formatDate('F-y'),
            '{"dateFormat": "d.m.Y", "timeFormat": "H:i"}' => $this->_formatDate('d.m.Y H:i'),
            '{"dateFormat": "m.d.Y", "timeFormat": "H:i:s"}' => $this->_formatDate('m.d.Y H:i:s'),
            '{"dateFormat": "", "timeFormat": "H:i"}' => $this->_formatDate('H:i'),
            '{"dateFormat": "", "timeFormat": "H:i:s"}' => $this->_formatDate('H:i:s'),
            '{"dateFormat": "", "timeFormat": "h:i A"}' => $this->_formatDate('h:i A'),
            '{"dateFormat": "", "timeFormat": "h:i:s A"}' => $this->_formatDate('h:i:s A')
        );

        // compute the JS date formats
        $this->_dateFormatsJs = array();
        foreach ($this->_dateFormatsPhp as $key => $value) {
            $newKey = $this->_convertPhpToJqueryUiDateTimeFormat($key);
            $newKey = addslashes($newKey);
            $this->_dateFormatsJs[$newKey] = $value;
        }

        // if form is submitted, store the current date settings
        // notice: also check the ID of the content type (there could be more
        // than one content type of the same type on the same page!)
        if (isset($_POST[$this->_prefix . '_action']) && $_POST[$this->_prefix . '_action'] === 'store' && isset($_POST[$this->_prefix . '_id']) && (int) $_POST[$this->_prefix . '_id'] == $this->_id) {
            // convert the given date string into a valid timestamp, so that a
            // timestamp is stored
            echo $_POST['date_timestamp'] . '<br />';
            echo $_POST['date_format'] . '<br />' . '<br />';
            $_POST['date_format'] = stripslashes($_POST['date_format']);
            echo $_POST['date_timestamp'] . '<br />';
            echo $_POST['date_format'];
            if (empty($_POST['date_format'])) {
                $_POST['date_format'] = 'd.m.yy';
            }
            $this->_storeSettings();
        }
    }

    /**
     * Formats the given timestamp according to the given format.
     * Localises the output.
     *
     * @param string $format the format string in the PHP date format
     * @param int $timestamp the timestamp representing the date which should be
     *        formatted
     * @return string the formatted, localised date
     */
    private function _formatDate($format, $timestamp = null) {
        $result = '';
        if ($timestamp === null) {
            $timestamp = time();
        }
        foreach (str_split($format) as $char) {
            $replacements = array(
                'd',
                'D',
                'j',
                'l',
                'N',
                'S',
                'w',
                'z',
                'W',
                'F',
                'm',
                'M',
                'n',
                't',
                'L',
                'o',
                'Y',
                'y',
                'a',
                'A',
                'B',
                'g',
                'G',
                'h',
                'H',
                'i',
                's',
                'u',
                'e',
                'I',
                'O',
                'P',
                'T',
                'Z',
                'c',
                'r',
                'U'
            );
            if (in_array($char, $replacements)) {
                // replace the format chars with localised values
                switch ($char) {
                    case 'D':
                        $dayName = getCanonicalDay(date('w', $timestamp));
                        $dayName = substr($dayName, 0, 3);
                        $result .= $dayName;
                        break;
                    case 'l':
                        $dayName = getCanonicalDay(date('w', $timestamp));
                        $result .= $dayName;
                        break;
                    case 'F':
                        $monthName = getCanonicalMonth(date('m', $timestamp));
                        $result .= $monthName;
                        break;
                    case 'M':
                        $monthName = getCanonicalMonth(date('m', $timestamp));
                        $monthName = substr($monthName, 0, 3);
                        $result .= $monthName;
                        break;
                    default:
                        // use the default date() format if no localisation is
                        // needed
                        $result .= date($char, $timestamp);
                        break;
                }
            } else {
                // if this is not a format char, just add it to the result
                // string
                $result .= $char;
            }
        }

        return $result;
    }

    /**
     * Generates the code which should be shown if this content type is shown in
     * the frontend.
     *
     * @return string escaped HTML code which sould be shown if content type is
     *         shown in frontend
     */
    public function generateViewCode() {
        $format = $this->_settings['date_format'];
        if (empty($format)) {
            $format = 'd.m.Y';
        }
        $timestamp = $this->_settings['date_timestamp'];
        if (empty($timestamp)) {
            return '';
        }

        return $this->_formatDate($format, $this->_settings['date_timestamp']);
    }

    /**
     * Generates the code which should be shown if this content type is edited.
     *
     * @return string escaped HTML code which should be shown if content type is
     *         edited
     */
    public function generateEditCode() {
        $code = new cHTMLTextbox('date_timestamp_' . $this->_id, '', '', '', 'date_timestamp_' . $this->_id, true, '', '', 'date_timestamp');
        $code .= $this->_generateJavaScript();
        $code .= $this->_generateFormatSelect();
        $code .= $this->_generateStoreButton();
        $code = new cHTMLDiv($code, 'cms_date', 'cms_' . $this->_prefix . '_' . $this->_id . '_settings');

        return $this->_encodeForOutput($code);
    }

    /**
     * Generates the JavaScript needed for CMS_DATE.
     *
     * @return string HTML code which includes the needed JavaScript
     */
    private function _generateJavaScript() {
        $template = new Template();
        $pathBackend = $this->_cfg['path']['contenido_fullhtml'];

        $template->set('s', 'PREFIX', $this->_prefix);
        $template->set('s', 'ID', $this->_id);
        $template->set('s', 'IDARTLANG', $this->_idArtLang);
        $template->set('s', 'PATH_BACKEND', $pathBackend);
        $template->set('s', 'LANG', substr(cRegistry::getBackendLanguage(), 0, 2));
        $template->set('s', 'PATH_TO_CALENDAR_PIC', $pathBackend . $this->_cfg['path']['images'] . 'calendar.gif');
        $template->set('s', 'SETTINGS', json_encode($this->_settings));

        return $template->generate($this->_cfg['path']['contenido'] . 'templates/standard/template.cms_date.html', true);
    }

    /**
     * Generates the save button.
     *
     * @return string HTML code for the save button
     */
    private function _generateStoreButton() {
        $saveButton = new cHTMLImage($this->_cfg['path']['contenido_fullhtml'] . $this->_cfg['path']['images'] . 'but_ok.gif', 'save_settings');

        return $saveButton->render();
    }

    /**
     * Generates a select box for defining the format of the date.
     *
     * @return string the HTML code of the format select box
     */
    private function _generateFormatSelect() {
        $formatSelect = new cHTMLSelectElement($this->_prefix . '_format_select_' . $this->_id, '', $this->_prefix . '_format_select_' . $this->_id);
        $formatSelect->appendStyleDefinitions(array(
            'border' => '1px solid #ccc',
            'margin' => '2px 5px 5px'
        ));
        $formatSelect->autoFill($this->_dateFormatsJs);
        $jsDateFormat = $this->_convertPhpToJqueryUiDateTimeFormat($this->_settings[$this->_prefix . '_format']);
        $formatSelect->setDefault($jsDateFormat);

        return $formatSelect->render();
    }

    /**
     * Converts the date and time format in the PHP format to the jQuery UI
     * format.
     * The format strings are given as a JSON encoded object.
     *
     * @param string $dateTimeFormat JSON encoded object containing the date and
     *        the time format
     * @return string the corresponding jQuery UI date time format
     */
    private function _convertPhpToJqueryUiDateTimeFormat($dateTimeFormat) {
        $dateTimeFormatObj = json_decode($dateTimeFormat);
        $dateTimeFormatObj->dateFormat = $this->_convertPhpToJqueryUiDateFormat($dateTimeFormatObj->dateFormat);
        $dateTimeFormatObj->timeFormat = $this->_convertPhpToJqueryUiTimeFormat($dateTimeFormatObj->timeFormat);

        return stripslashes(json_encode($dateTimeFormatObj));
    }

    /**
     * Converts the given date format string in the PHP format
     * (http://de.php.net/manual/en/function.date.php) to the jQuery UI format
     * (http://docs.jquery.com/UI/Datepicker/formatDate).
     *
     * @param string $dateFormat the PHP date format string
     * @return string the corresponding jQuery UI date format string
     */
    private function _convertPhpToJqueryUiDateFormat($dateFormat) {
        // descriptions from http://de.php.net/manual/en/function.date.php
        $pattern = array(
            'd', // Day of the month, 2 digits with leading zeros
            'D', // A textual representation of a day, three letters
            'j', // Day of the month without leading zeros
            'l', // A full textual representation of the day of the week
            'z', // The day of the year (starting from 0)
            'F', // A full textual representation of a month, such as January or
                 // March
            'm', // Numeric representation of a month, with leading zeros
            'M', // A short textual representation of a month, three letters
            'n', // Numeric representation of a month, without leading zeros
            'Y', // A full numeric representation of a year, 4 digits
                 // A two digit representation of a year
            'y'
        );
        // descriptions from http://docs.jquery.com/UI/Datepicker/formatDate
        $replace = array(
            'dd', // day of month (two digit)
            'D', // day name short
            'd', // day of month (no leading zero)
            'DD', // day name long
            'o', // day of the year (no leading zeros)
            'MM', // month name long
            'mm', // month of year (two digit)
            'M', // month name short
            'm', // month of year (no leading zero)
            'yy', // year (four digit)
                  // year (two digit)
            'y'
        );
        foreach ($pattern as &$p) {
            $p = '/' . $p . '/';
        }

        return preg_replace($pattern, $replace, $dateFormat);
    }

    /**
     * Converts the given time format string in the PHP format
     * (http://de.php.net/manual/en/function.date.php) to the jQuery UI format
     * (http://trentrichardson.com/examples/timepicker/).
     *
     * @param string $timeFormat
     * @return mixed
     */
    private function _convertPhpToJqueryUiTimeFormat($timeFormat) {
        // descriptions from http://de.php.net/manual/en/function.date.php
        $pattern = array(
            'a', // Lowercase Ante meridiem and Post meridiem
            'A', // Uppercase Ante meridiem and Post meridiem
            'g', // 12-hour format of an hour without leading zeros
            'G', // 24-hour format of an hour without leading zeros
            'h', // 12-hour format of an hour with leading zeros
            'H', // 24-hour format of an hour with leading zeros
            'i', // Minutes with leading zeros
            's', // Seconds, with leading zeros
                 // Microseconds
            'u'
        );
        // descriptions from http://trentrichardson.com/examples/timepicker/
        $replace = array(
            'tt', // am or pm for AM/PM
            'TT', // AM or PM for AM/PM
            'h', // Hour with no leading 0
            'h', // Hour with no leading 0
            'hh', // Hour with leading 0
            'hh', // Hour with leading 0
            'mm', // Minute with leading 0
            'ss', // Second with leading 0
                  // Milliseconds always with leading 0
            'l'
        );
        foreach ($pattern as &$p) {
            $p = '/' . $p . '/';
        }

        return preg_replace($pattern, $replace, $timeFormat);
    }

}