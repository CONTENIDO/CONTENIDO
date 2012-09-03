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
     * The possible PHP date formats in which the selected date can be
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

        // set the locale
        $belang = cRegistry::getBackendLanguage();
        if (empty($belang)) {
            $language = new cApiLanguage(cRegistry::getLanguageId());
            $locale = $language->getProperty('dateformat', 'locale');
            if (!empty($locale)) {
                setlocale(LC_TIME, $locale);
            }
        } else {
            setlocale(LC_TIME, $belang);
        }

        // initialise the date formats
        $this->_dateFormatsPhp = array(
            htmlspecialchars('{"dateFormat":"d.m.Y","timeFormat":""}') => $this->_formatDate('d.m.Y'),
            htmlspecialchars('{"dateFormat":"D, d.m.Y","timeFormat":""}') => $this->_formatDate('D, d.m.Y'),
            htmlspecialchars('{"dateFormat":"d. F Y","timeFormat":""}') => $this->_formatDate('d. F Y'),
            htmlspecialchars('{"dateFormat":"Y-m-d","timeFormat":""}') => $this->_formatDate('Y-m-d'),
            htmlspecialchars('{"dateFormat":"d/F/Y","timeFormat":""}') => $this->_formatDate('d/F/Y'),
            htmlspecialchars('{"dateFormat":"d/m/y","timeFormat":""}') => $this->_formatDate('d/m/y'),
            htmlspecialchars('{"dateFormat":"F y","timeFormat":""}') => $this->_formatDate('F y'),
            htmlspecialchars('{"dateFormat":"F-y","timeFormat":""}') => $this->_formatDate('F-y'),
            htmlspecialchars('{"dateFormat":"d.m.Y","timeFormat":"H:i"}') => $this->_formatDate('d.m.Y H:i'),
            htmlspecialchars('{"dateFormat":"m.d.Y","timeFormat":"H:i:s"}') => $this->_formatDate('m.d.Y H:i:s'),
            htmlspecialchars('{"dateFormat":"","timeFormat":"H:i"}') => $this->_formatDate('H:i'),
            htmlspecialchars('{"dateFormat":"","timeFormat":"H:i:s"}') => $this->_formatDate('H:i:s'),
            htmlspecialchars('{"dateFormat":"","timeFormat":"h:i A"}') => $this->_formatDate('h:i A'),
            htmlspecialchars('{"dateFormat":"","timeFormat":"h:i:s A"}') => $this->_formatDate('h:i:s A')
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
            $key = htmlspecialchars($format);
            $value = implode(' ', $formatArray);
            $this->_dateFormatsPhp[$key] = $this->_formatDate($value);
        }

        // if form is submitted, store the current date settings
        // notice: also check the ID of the content type (there could be more
        // than one content type of the same type on the same page!)
        if (isset($_POST[$this->_prefix . '_action']) && $_POST[$this->_prefix . '_action'] === 'store' && isset($_POST[$this->_prefix . '_id']) && (int) $_POST[$this->_prefix . '_id'] == $this->_id) {
            // convert the given date string into a valid timestamp, so that a
            // timestamp is stored
            $_POST['date_format'] = stripslashes(base64_decode($_POST['date_format']));
            if (empty($_POST['date_format'])) {
                $_POST['date_format'] = '{"dateFormat":"d.m.Y","timeFormat":""}';
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
        } else {
            $format = implode(' ', json_decode($format, true));
        }
        $timestamp = $this->_settings['date_timestamp'];
        if (empty($timestamp)) {
            return '';
        }

        return $this->_formatDate($format, $timestamp);
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
        $template = new cTemplate();
        $pathBackend = $this->_cfg['path']['contenido_fullhtml'];

        $template->set('s', 'PREFIX', $this->_prefix);
        $template->set('s', 'ID', $this->_id);
        $template->set('s', 'IDARTLANG', $this->_idArtLang);
        $template->set('s', 'PATH_BACKEND', $pathBackend);
        $template->set('s', 'LANG', substr(cRegistry::getBackendLanguage(), 0, 2));
        $template->set('s', 'PATH_TO_CALENDAR_PIC', $pathBackend . $this->_cfg['path']['images'] . 'calendar.gif');
        $template->set('s', 'SETTINGS', json_encode($this->_settings));
        $template->set('s', 'BELANG', cRegistry::getBackendLanguage());

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
        $formatSelect->autoFill($this->_dateFormatsPhp);
        $phpDateFormat = htmlspecialchars($this->_settings[$this->_prefix . '_format']);
        $formatSelect->setDefault($phpDateFormat);

        return $formatSelect->render();
    }

}