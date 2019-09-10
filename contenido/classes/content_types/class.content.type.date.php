<?php

/**
 * This file contains the cContentTypeDate class.
 *
 * @package Core
 * @subpackage ContentType
 * @author Bilal Arslan
 * @author Timo Trautmann
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Content type CMS_DATE which allows the editor to select a date from a
 * calendar and a date format.
 * The selected date is then shown in the selected
 * format.
 *
 * @package Core
 * @subpackage ContentType
 */
class cContentTypeDate extends cContentTypeAbstract {

    /**
     * The possible PHP date formats in which the selected date can be
     * displayed.
     *
     * @var array
     */
    private $_dateFormatsPhp;

    /**
     * Constructor to create an instance of this class.
     *
     * Initialises class attributes and handles store events.
     *
     * @param string $rawSettings
     *         the raw settings in an XML structure or as plaintext
     * @param int    $id
     *         ID of the content type, e.g. 3 if CMS_DATE[3] is used
     * @param array  $contentTypes
     *         array containing the values of all content types
     *
     * @throws cDbException
     * @throws cException
     */
    public function __construct($rawSettings, $id, array $contentTypes) {

        // set props
        $this->_type = 'CMS_DATE';
        $this->_prefix = 'date';
        $this->_settingsType = self::SETTINGS_TYPE_XML;
        $this->_formFields = array(
            'date_timestamp',
            'date_format'
        );

        // call parent constructor
        parent::__construct($rawSettings, $id, $contentTypes);

        // set the locale
        $locale = cRegistry::getBackendLanguage();
        if (empty($locale)
        || false === setlocale(LC_TIME, $locale)) {
            $oApiLang = new cApiLanguage(cRegistry::getLanguageId());
            $locale = $oApiLang->getProperty('dateformat', 'locale');
            if (empty($locale)) {
                $language = $oApiLang->getProperty('language', 'code');
                $country = $oApiLang->getProperty('country', 'code');

                $locale = $language . '_' . cString::toUpperCase($country);
            }
            if (false === empty($locale)) {
                setlocale(LC_TIME, $locale);
            }
        }

        // initialise the date formats
        $this->_dateFormatsPhp = array(
            conHtmlentities('{"dateFormat":"","timeFormat":""}') => '',
            conHtmlentities('{"dateFormat":"d.m.Y","timeFormat":""}') => $this->_formatDate('d.m.Y'),
            conHtmlentities('{"dateFormat":"D, d.m.Y","timeFormat":""}') => $this->_formatDate('D, d.m.Y'),
            conHtmlentities('{"dateFormat":"d. F Y","timeFormat":""}') => $this->_formatDate('d. F Y'),
            conHtmlentities('{"dateFormat":"Y-m-d","timeFormat":""}') => $this->_formatDate('Y-m-d'),
            conHtmlentities('{"dateFormat":"d/F/Y","timeFormat":""}') => $this->_formatDate('d/F/Y'),
            conHtmlentities('{"dateFormat":"d/m/y","timeFormat":""}') => $this->_formatDate('d/m/y'),
            conHtmlentities('{"dateFormat":"F y","timeFormat":""}') => $this->_formatDate('F y'),
            conHtmlentities('{"dateFormat":"F-y","timeFormat":""}') => $this->_formatDate('F-y'),
            conHtmlentities('{"dateFormat":"d.m.Y","timeFormat":"H:i"}') => $this->_formatDate('d.m.Y H:i'),
            conHtmlentities('{"dateFormat":"m.d.Y","timeFormat":"H:i:s"}') => $this->_formatDate('m.d.Y H:i:s'),
            conHtmlentities('{"dateFormat":"","timeFormat":"H:i"}') => $this->_formatDate('H:i'),
            conHtmlentities('{"dateFormat":"","timeFormat":"H:i:s"}') => $this->_formatDate('H:i:s'),
            conHtmlentities('{"dateFormat":"","timeFormat":"h:i A"}') => $this->_formatDate('h:i A'),
            conHtmlentities('{"dateFormat":"","timeFormat":"h:i:s A"}') => $this->_formatDate('h:i:s A')
        );

        // add formats from client settings
        $additionalFormats = getEffectiveSettingsByType('cms_date');
        foreach ($additionalFormats as $format) {
            $formatArray = json_decode($format, true);
            // ignore invalid formats
            if (empty($formatArray) || count($formatArray) != 2 || !array_key_exists('dateFormat', $formatArray) || !array_key_exists('timeFormat', $formatArray)) {
                cWarning('An invalid date-time-format has been entered in the client settings.');
                continue;
            }
            $key = conHtmlSpecialChars($format);
            $value = implode(' ', $formatArray);
            $this->_dateFormatsPhp[$key] = $this->_formatDate($value);
        }

        // if form is submitted, store the current date settings
        // notice: also check the ID of the content type (there could be more
        // than one content type of the same type on the same page!)
        if (isset($_POST[$this->_prefix . '_action']) && $_POST[$this->_prefix . '_action'] === 'store' && isset($_POST[$this->_prefix . '_id']) && (int) $_POST[$this->_prefix . '_id'] == $this->_id) {
            // convert the given date string into a valid timestamp, so that a
            // timestamp is stored
            //CON-2049 additional check for base64 strings
            if (!empty($_POST['date_format']) && base64_encode(base64_decode($_POST['date_format'])) === $_POST['date_format']) {
                $_POST['date_format'] = stripslashes(base64_decode($_POST['date_format']));
            } else { // if no date_format is given, set standard value
                $_POST['date_format'] = '{"dateFormat":"","timeFormat":""}';
            }

            $this->_storeSettings();
        }

        // CON-2049
        // reset specific date variable
        // $_POST[$this->_prefix . '_action'] = '';
        // $_POST['date_format'] = '';
    }

    /**
     * Returns the displayed timestamp
     *
     * @return string
     */
    public function getDateTimestamp() {
        return $this->_settings['date_timestamp'];
    }

    /**
     * Returns the full PHP style format string
     *
     * @return string
     */
    public function getDateFormat() {
        $format = $this->_settings['date_format'];

        if (empty($format)) {
            $format = '';
        } else {
            $decoded_array = json_decode($format, true);
            if (is_array($decoded_array)) {
                $format = implode(' ', $decoded_array);
            } else {
                $format = '';
            }
        }

        return $format;
    }

    /**
     * Returns only the time portion of the PHP style format string
     *
     * @return string
     */
    public function getTimeFormat() {
        $format = $this->_settings['date_format'];

        if (empty($format)) {
            $format = '';
        } else {
            $decoded_array = json_decode($format, true);
            if (is_array($decoded_array)) {
                return $decoded_array['timeFormat'];
            } else {
                return '';
            }
        }

        return $format;
    }

    /**
     * Formats the given timestamp according to the given format.
     * Localises the output.
     *
     * @param string $format
     *         the format string in the PHP date format
     * @param int $timestamp [optional]
     *         the timestamp representing the date which should be formatted
     * @return string
     *         the formatted, localised date
     */
    private function _formatDate($format, $timestamp = NULL) {
        $result = '';
        if ($timestamp === NULL) {
            $timestamp = time();
        }
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
        foreach (str_split($format) as $char) {
            if (in_array($char, $replacements)) {
                // replace the format chars with localised values
                switch ($char) {
                    case 'D':
                        $result .= strftime('%a', $timestamp);
                        break;
                    case 'l':
                        $result .= strftime('%A', $timestamp);
                        break;
                    case 'F':
                        $result .= strftime('%B', $timestamp);
                        break;
                    case 'M':
                        $result .= strftime('%b', $timestamp);
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

        // strftime returns a string in an encoding that is specified by the locale
        // use iconv extension to get the content encoding of string
        // use mbstring extension to convert encoding to contenido's target encoding
        if (extension_loaded('iconv') && extension_loaded('mbstring')) {
            $result = mb_convert_encoding($result, cRegistry::getEncoding(), iconv_get_encoding('output_encoding'));
            $result = conHtmlentities($result);
        }

        return $result;
    }

    /**
     * Generates the code which should be shown if this content type is shown in
     * the frontend.
     *
     * @return string
     *         escaped HTML code which should be shown if content type is shown in frontend
     */
    public function generateViewCode() {
        if (empty($this->_settings['date_timestamp'])) {
            return '';
        }

        $timestamp = $this->_settings['date_timestamp'];

        if (empty($this->_settings['date_format'])) {
            $format = '';
        } else {
            $format = $this->_settings['date_format'];
            $decoded_array = json_decode($format, true);
            if (is_array($decoded_array)) {
                $format = implode(' ', $decoded_array);
            } else {
                $format = '';
            }
        }

        return $this->_formatDate($format, $timestamp);
    }

    /**
     * Generates the code which should be shown if this content type is edited.
     *
     * @return string
     *         escaped HTML code which should be shown if content type is edited
     * @throws cInvalidArgumentException
     */
    public function generateEditCode() {
        $belang = cRegistry::getBackendLanguage();
        $format = 'Y-m-d h:i:sA';
        if ($belang == 'de_DE') {
            $format = 'd.m.Y H:i:s';
        }
        $value = date($format, $this->_settings['date_timestamp']);
        $code = new cHTMLTextbox('date_timestamp_' . $this->_id, $value, '', '', 'date_timestamp_' . $this->_id, true, '', '', 'date_timestamp');
        $code .= $this->_generateFormatSelect();
        $code .= $this->_generateStoreButton();
        $code .= $this->_generateJavaScript();
        $code = new cHTMLDiv($code, 'cms_date', 'cms_' . $this->_prefix . '_' . $this->_id . '_settings');

        return $this->_encodeForOutput($code);
    }

    /**
     * Generates the JavaScript needed for CMS_DATE.
     *
     * @return string
     *         HTML code which includes the needed JavaScript
     * @throws cInvalidArgumentException
     */
    private function _generateJavaScript() {
        $template = new cTemplate();
        $pathBackend = $this->_cfg['path']['contenido_fullhtml'];

        $template->set('s', 'PREFIX', $this->_prefix);
        $template->set('s', 'ID', $this->_id);
        $template->set('s', 'IDARTLANG', $this->_idArtLang);
        $template->set('s', 'LANG', cString::getPartOfString(cRegistry::getBackendLanguage(), 0, 2));
        $template->set('s', 'PATH_TO_CALENDAR_PIC', $pathBackend . $this->_cfg['path']['images'] . 'calendar.gif');
        $setting = $this->_settings;
        if (array_key_exists('date_format', $setting)) {
            $setting['date_format'] = json_decode($setting['date_format'], true);
        }
        $template->set('s', 'SETTINGS', json_encode($setting));
        $template->set('s', 'BELANG', cRegistry::getBackendLanguage());

        return $template->generate($this->_cfg['path']['contenido'] . 'templates/standard/template.cms_date.html', true);
    }

    /**
     * Generates the save button.
     *
     * @return string
     *         HTML code for the save button
     */
    private function _generateStoreButton() {
        $saveButton = new cHTMLImage($this->_cfg['path']['contenido_fullhtml'] . $this->_cfg['path']['images'] . 'but_ok.gif', 'save_settings');

        return $saveButton->render();
    }

    /**
     * Generates a select box for defining the format of the date.
     *
     * @return string
     *         the HTML code of the format select box
     */
    private function _generateFormatSelect() {
        $formatSelect = new cHTMLSelectElement($this->_prefix . '_format_select_' . $this->_id, '', $this->_prefix . '_format_select_' . $this->_id);
        $formatSelect->appendStyleDefinitions(array(
            'border' => '1px solid #ccc',
            'margin' => '0px 5px 5px'
        ));
        $formatSelect->autoFill($this->_dateFormatsPhp);
        $phpDateFormat = conHtmlSpecialChars($this->_settings[$this->_prefix . '_format']);
        $formatSelect->setDefault($phpDateFormat);

        return $formatSelect->render();
    }

}
