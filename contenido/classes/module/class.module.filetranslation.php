<?php
/**
 * This file contains the module file translation class.
 *
 * @todo refactor documentation
 *
 * @package    Core
 * @subpackage Backend
 * @author     Rusmir Jusufovic
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class save the translations from a modul in a file and get it
 * from file.
 *
 * @package    Core
 * @subpackage Backend
 */
class cModuleFileTranslation extends cModuleHandler {

    /**
     * Path to the modul directory.
     *
     * @var string
     */
    private $_modulePath;

    /**
     * Name of the translations file.
     *
     * @var string
     */
    static $fileName = '';

    /**
     * Translation array.
     *
     * @var array
     */
    static $langArray = array();

    /**
     * The id of the modul.
     *
     * @var int
     */
    static $savedIdMod = NULL;
    static $originalTranslationDivider = '=';

    /**
     * Constructor to create an instance of this class.
     *
     * @param int $idmodul [optional]
     * @param bool $static [optional]
     *         if true it will load once the translation from file
     * @param int $overrideIdlang [optional]
     *         use different language if not NULL
     */
    public function __construct($idmodul = NULL, $static = false, $overrideIdlang = NULL) {
        parent::__construct($idmodul);

        // $this->_debug = true;

        if ($idmodul != NULL) {
            $this->_modulePath = $this->getModulePath();
        }

        // override language if specified
        if ($overrideIdlang != NULL) {
            $this->_idlang = $overrideIdlang;
        }

        $this->_encoding = self::getEncoding($this->_idlang);

        // dont open the translations file for each mi18n call
        if ($static == true) {
            if (self::$savedIdMod != $idmodul) {
                // set filename lang_[language]_[Country].txt
                $language = $this->_getValueFromProperties('language', 'code');
                $country = $this->_getValueFromProperties('country', 'code');
                self::$fileName = 'lang_' . $language . '_' . strtoupper($country) . '.txt';

                self::$langArray = $this->getTranslationArray();
                self::$savedIdMod = $idmodul;
            }
        } else {
            self::$savedIdMod = -1;

            // set filename lang_[language]_[Country].txt
            $language = $this->_getValueFromProperties('language', 'code');
            $country = $this->_getValueFromProperties('country', 'code');
            self::$fileName = 'lang_' . $language . '_' . strtoupper($country) . '.txt';
        }
    }

    /**
     * Get the value of a item from properties db.
     *
     * @param string $type
     * @param string $name
     * @return string
     *         value
     */
    private function _getValueFromProperties($type, $name) {
        cApiPropertyCollection::reset();
        $propColl = new cApiPropertyCollection();
        $propColl->changeClient($this->_client);
        return $propColl->getValue('idlang', $this->_idlang, $type, $name, '');
    }

    /**
     * Get the lang array.
     *
     * @return array
     */
    public function getLangArray() {
        return self::$langArray;
    }

    /**
     * Save the hole translations for a idmod and lang.
     * For the upgrade/setup.
     */
    public function saveTranslations() {
        $db = cRegistry::getDb();

        $oLangColl = new cApiLanguageCollection();
        $ids = $oLangColl->getAllIds();
        foreach ($ids as $idlang) {
            $sql = 'SELECT * FROM `%s` WHERE idlang = %d AND idmod = %d';
            $sql = $db->prepare($sql, $this->_cfg['tab']['mod_translations'], $idlang, $this->_idmod);
            $db->query($sql);

            $this->_idlang = $idlang;
            // set filename lang_[language]_[Country].txt
            $language = $this->_getValueFromProperties('language', 'code');
            $country = $this->_getValueFromProperties('country', 'code');
            self::$fileName = 'lang_' . $language . '_' . strtoupper($country) . '.txt';

            $translations = array();
            while ($db->nextRecord()) {
                $original = mb_convert_encoding(urldecode(cSecurity::unFilter($db->f('original'))), "UTF-8");
                $translation = mb_convert_encoding(urldecode(cSecurity::unFilter($db->f('translation'))), "UTF-8");
                $translations[$original] = $translation;
            }

            $text = $this->readInput();
            if (!$text) {
                $text = "";
            }
            $text .= $this->readOutput();

            mb_ereg_search_init($text, 'mi18n\(["|\'](.*?)["|\']\)');
            while(mb_ereg_search()) {
                $translation = mb_ereg_search_getregs();
                if(!isset($translations[$translation[1]])) {
                    $translations[$translation[1]] = $translation[1];
                }
            }

            if (count($translations) != 0) {
                if ($this->saveTranslationArray($translations) == false) {
                    cWarning(__FILE__, __LINE__, 'Could not save translate idmod=' . $this->_idmod . ' !');
                }
            }
        }
    }

    /**
     * This method serialize an array.
     *
     * $key.[Divider].$value."\r\n"
     *
     * @param array $wordListArray
     * @return string
     */
    private function _serializeArray($wordListArray) {
        $retString = '';
        foreach ($wordListArray as $key => $value) {
            // Originall String [Divider] Translation String
            $retString .= $key . self::$originalTranslationDivider . $value . "\r\n";
        }

        return $retString;
    }

    /**
     * This method unserialize a string.
     * The contents of file looks like
     * <Original String><Divider><Translation String>.
     *
     * Example:
     * If divider is "="
     * Hello World=Hallo Welt
     *
     * @param string $string
     *         the contents of the file
     * @return array
     */
    private function _unserializeArray($string) {
        $retArray = array();

        $string = $string . PHP_EOL;
        $words = preg_split('((\r\n)|(\r)|(\n))', substr($string, 0, strlen($string) - strlen(PHP_EOL)));

        foreach ($words as $key => $value) {
            $oriTrans = preg_split('/(?<!\\\\)' . self::$originalTranslationDivider . '/', $value);

            if (isset($oriTrans[1])) {
                $retArray[cString::recodeString($oriTrans[0], $this->_fileEncoding, $this->_encoding)] = cString::recodeString(str_replace("\=", "=", $oriTrans[1]), $this->_fileEncoding, $this->_encoding);
            } else {
                // CON-1671 never use end(array_keys(...))
                $keys = array_keys($retArray);
                $lastKey = end($keys);
                $newValue = PHP_EOL . cString::recodeString(str_replace("\=", "=", $oriTrans[0]), $this->_fileEncoding, $this->_encoding);
                $retArray[$lastKey] .= $newValue;
            }
        }

        return $retArray;
    }

    /**
     * Save the contents of the wordListArray in file.
     *
     * @param array $wordListArray
     * @return bool
     *         true on success or false on failure
     */
    public function saveTranslationArray($wordListArray) {
        $fileName = $this->_modulePath . $this->_directories['lang'] . self::$fileName;

        if (!$this->createModuleDirectory('lang') || !$this->isWritable($fileName, $this->_modulePath . $this->_directories['lang'])) {
            return false;
        }

        $escapedArray = array();
        foreach ($wordListArray as $key => $value) {
            $newKey = mb_ereg_replace("=", "\\=", $key);
            $newValue = mb_ereg_replace("=", "\\=", $value);
            $escapedArray[$newKey] = $newValue;
        }

        if (cFileHandler::write($fileName, $this->_serializeArray($escapedArray)) === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get the translations array.
     *
     * @return array
     */
    public function getTranslationArray() {
        if (cFileHandler::exists($this->_modulePath . $this->_directories['lang'] . self::$fileName)) {
            $array = $this->_unserializeArray(cFileHandler::read($this->_modulePath . $this->_directories['lang'] . self::$fileName));
            return $array;
        } else {
            return array();
        }
    }

}
