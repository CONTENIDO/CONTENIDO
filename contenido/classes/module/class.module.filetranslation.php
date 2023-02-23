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
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class save the translations from a module in a file and get it
 * from file.
 *
 * @package    Core
 * @subpackage Backend
 */
class cModuleFileTranslation extends cModuleHandler {

    /**
     * Path to the module directory.
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
    static $langArray = [];

    /**
     * Language info array.
     *
     * @var array
     */
    private static $langInfo = [];

    /**
     * The id of the module.
     *
     * @var int
     */
    static $savedIdMod = NULL;
    static $originalTranslationDivider = '=';

    /**
     * Constructor to create an instance of this class.
     *
     * @param cApiModule|array|int $module [optional]
     *         The module instance or the module recordset array from the
     *         database or the id of the module
     * @param bool $static         [optional]
     *                             if true it will load once the translation from file
     * @param int  $overrideIdlang [optional]
     *                             use different language if not NULL
     *
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function __construct($module = NULL, $static = false, $overrideIdlang = NULL) {
        parent::__construct($module);

        // $this->_debug = true;

        if ($this->_idmod != NULL) {
            $this->_modulePath = $this->getModulePath();
        }

        // override language if specified
        if ($overrideIdlang != NULL) {
            $this->_idlang = $overrideIdlang;
        }

        $this->_encoding = self::getEncoding($this->_idlang);

        // don't open the translations file for each mi18n call
        if ($static) {
            if (self::$savedIdMod != $this->_idmod) {
                self::$fileName = $this->_composeTranslationFileName($this->_client, $this->_idlang);
                self::$langArray = $this->getTranslationArray();
                self::$savedIdMod = $this->_idmod;
            }
        } else {
            self::$savedIdMod = -1;
            self::$fileName = $this->_composeTranslationFileName($this->_client, $this->_idlang);
        }
    }

    /**
     * @deprecated Since 4.10.2, Function replaced by {@see cModuleFileTranslation::_getLanguageInfo}
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
     * Save the hole translations for an idmod and lang.
     * For the upgrade/setup.
     *
     * @todo Remove this to setup routine (see cUpgradeJob_0002), it has nothing to do here!
     * @throws cDbException
     * @throws cException
     */
    public function saveTranslations() {
        $db = cRegistry::getDb();

        $oLangColl = new cApiLanguageCollection();
        $ids = $oLangColl->getAllIds();
        foreach ($ids as $idlang) {
            $sql = 'SELECT * FROM `%s` WHERE idlang = %d AND idmod = %d';
            $sql = $db->prepare($sql, $this->_cfg['tab']['mod_translations'], $idlang, $this->_idmod);
            $db->query($sql);

            self::$fileName = $this->_composeTranslationFileName($this->_client, $idlang);

            $translations = [];
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
            while (mb_ereg_search()) {
                $translation = mb_ereg_search_getregs();
                if(!isset($translations[$translation[1]])) {
                    $translations[$translation[1]] = $translation[1];
                }
            }

            if (count($translations) != 0) {
                if (!$this->saveTranslationArray($translations)) {
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
            // Original String [Divider] Translation String
            if (cString::getStringLength($key) > 0) {
                $retString .= trim($key . self::$originalTranslationDivider . $value) . "\r\n";
            }
        }

        return trim($retString);
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
        $retArray = [];

        $string = $string . PHP_EOL;
        $words = preg_split('((\r\n)|(\r)|(\n))', cString::getPartOfString($string, 0, cString::getStringLength($string) - cString::getStringLength(PHP_EOL)));

        foreach ($words as $key => $value) {
            $oriTrans = preg_split('/(?<!\\\\)' . self::$originalTranslationDivider . '/', $value);

            if (isset($oriTrans[1])) {
                $retArray[cString::recodeString($oriTrans[0], $this->_fileEncoding, $this->_encoding)] = cString::recodeString(str_replace("\=", "=", $oriTrans[1]), $this->_fileEncoding, $this->_encoding);
            } else {
                // CON-1671 never use end(array_keys(...))
                $keys = array_keys($retArray);
                $lastKey = end($keys);
                $newValue = PHP_EOL . cString::recodeString(str_replace("\=", "=", $oriTrans[0]), $this->_fileEncoding, $this->_encoding);
                if (empty($retArray[$lastKey])) {
                    $retArray[$lastKey] = $newValue;
                } else {
                    $retArray[$lastKey] .= $newValue;
                }
            }
        }

        return $retArray;
    }

    /**
     * Composes the file name for the module translation file.
     *
     * @param int $idclient Client id to use
     * @param int $idlang  Language id to use
     *
     * @return string The file name like `lang_[language]_[COUNTRY].txt`,
     *                e.g. `lang_en_US.txt`
     */
    private function _composeTranslationFileName($idclient, $idlang) {
        // Compose the translation file name lang_[language]_[COUNTRY].txt
        $info = $this->_getLanguageInfo($idclient, $idlang);
        $language = $info['language'] ?? '';
        $country = cString::toUpperCase($info['country'] ?? '');
        return 'lang_' . $language . '_' . $country . '.txt';
    }

    /**
     * Returns the language information array for a client and language.
     * The required data will be lazy loaded at first call for each
     * combination of client and language.
     *
     * @param int $idclient Client id to use
     * @param int $idlang  Language id to use
     *
     * @return array  Language info array like
     *                <pre>
     *                ['language' => 'en', 'country' => 'US']
     *                </pre>
     *                Note: Array can also be empty, in case of an error!
     * @throws cDbException|cException
     */
    private function _getLanguageInfo($idclient, $idlang) {
        if (!isset(self::$langInfo[$idclient][$idlang])) {
            cApiPropertyCollection::reset();
            $propColl = new cApiPropertyCollection();
            $propColl->changeClient($idclient);
            $language = $propColl->getValue('idlang', $idlang, 'language', 'code', '');
            $country = $propColl->getValue('idlang', $idlang, 'country', 'code', '');
            self::$langInfo[$idclient][$idlang] = [
                'language' => $language,
                'country' => $country,
            ];
        }

        return self::$langInfo[$idclient][$idlang] ?? [];
    }

    /**
     * Save the contents of the wordListArray in file.
     *
     * @param array $wordListArray
     *
     * @return bool
     *         true on success or false on failure
     * @throws cInvalidArgumentException
     */
    public function saveTranslationArray($wordListArray) {
        $fileName = $this->_modulePath . $this->_directories['lang'] . self::$fileName;

        if (!$this->createModuleDirectory('lang') || !$this->isWritable($fileName, $this->_modulePath . $this->_directories['lang'])) {
            return false;
        }

        $escapedArray = [];
        foreach ($wordListArray as $key => $value) {
            $newKey = cString::ereg_replace("=", "\\=", $key);
            $newValue = cString::ereg_replace("=", "\\=", $value);
            $escapedArray[$newKey] = $newValue;
        }

        if (cFileHandler::write($fileName, $this->_serializeArray($escapedArray) . "\r\n") === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get the translations array.
     *
     * @return array
     * @throws cInvalidArgumentException
     */
    public function getTranslationArray() {
        if (cFileHandler::exists($this->_modulePath . $this->_directories['lang'] . self::$fileName)) {
            return $this->_unserializeArray(cFileHandler::read($this->_modulePath . $this->_directories['lang'] . self::$fileName));
        } else {
            return [];
        }
    }

}
