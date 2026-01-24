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
 * This class saves the translations from a module in a file and gets it from the file.
 *
 * @package    Core
 * @subpackage Backend
 */
class cModuleFileTranslation extends cModuleHandler
{

    /**
     * @var string Name of the translation file.
     */
    static $fileName = '';

    /**
     * @var array Translation array.
     */
    static $langArray = [];

    /**
     * @var array Language info array.
     */
    private static $langInfo = [];

    /**
     * @var int The id of the module.
     */
    static $savedIdMod = NULL;

    static $originalTranslationDivider = '=';

    /**
     * Constructor to create an instance of this class.
     *
     * @param cApiModule|array|int $module The module instance or the module recordset array from the
     *      database or the id of the module
     * @param bool $static if true, it will load once the translation from a file
     * @param int $overrideIdlang Use different language if not NULL
     * @throws cException|cInvalidArgumentException
     */
    public function __construct($module = NULL, $static = false, $overrideIdlang = NULL)
    {
        parent::__construct($module);

        // $this->_debug = true;

        if ($this->moduleId != NULL) {
            $this->modulePath = $this->getModulePath();
        }

        // override language if specified
        if ($overrideIdlang != NULL) {
            $this->languageId = $overrideIdlang;
        }

        $this->encoding = self::getEncoding($this->languageId);

        // don't open the translations file for each mi18n call
        if ($static) {
            if (self::$savedIdMod != $this->moduleId) {
                self::$fileName = $this->composeTranslationFileName($this->clientId, $this->languageId);
                self::$langArray = $this->getTranslationArray();
                self::$savedIdMod = $this->moduleId;
            }
        } else {
            self::$savedIdMod = -1;
            self::$fileName = $this->composeTranslationFileName($this->clientId, $this->languageId);
        }
    }

    /**
     * @deprecated [2023-02-08] Since CONTENIDO 4.10.2, Function replaced by {@see cModuleFileTranslation::getLanguageInfo}
     */
    private function _getValueFromProperties($type, $name)
    {
        cApiPropertyCollection::reset();
        $propColl = new cApiPropertyCollection();
        $propColl->changeClient($this->clientId);
        return $propColl->getValue('idlang', $this->languageId, $type, $name, '');
    }

    /**
     * Get the lang array.
     */
    public function getLangArray(): array
    {
        return self::$langArray;
    }

    /**
     * Save the hole translations for an idmod and lang.
     * For the upgrade/setup.
     *
     * @throws cDbException|cException
     * @todo Remove this to setup routine (see cUpgradeJob_0002), it has nothing to do here!
     */
    public function saveTranslations()
    {
        $db = cRegistry::getDb();

        $oLangColl = new cApiLanguageCollection();
        $languageIds = $oLangColl->getAllIds();
        $languageIds = array_map('intval', $languageIds);
        foreach ($languageIds as $languageId) {
            $sql = 'SELECT * FROM `%s` WHERE `idlang` = %d AND `idmod` = %d';
            $sql = $db->prepare($sql, cDb::getTableName('mod_translations'), $languageId, $this->moduleId);
            $db->query($sql);

            self::$fileName = $this->composeTranslationFileName($this->clientId, $languageId);

            $translations = [];
            while ($db->nextRecord()) {
                $original = mb_convert_encoding(urldecode(cSecurity::unFilter($db->f('original'))), 'UTF-8');
                $translation = mb_convert_encoding(urldecode(cSecurity::unFilter($db->f('translation'))), 'UTF-8');
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
                if (!isset($translations[$translation[1]])) {
                    $translations[$translation[1]] = $translation[1];
                }
            }

            if (count($translations) != 0) {
                if (!$this->saveTranslationArray($translations)) {
                    cWarning(__FILE__, __LINE__, 'Could not save translate idmod=' . $this->moduleId . ' !');
                }
            }
        }
    }

    /**
     * This method serializes an array.
     *
     * $key.[Divider].$value."\r\n"
     */
    private function serializeArray(array $wordListArray): string
    {
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
     * The contents of a file look like:
     * <Original String><Divider><Translation String>.
     *
     * Example:
     * If the divider is "="
     * Hello World=Hallo Welt
     *
     * @param string $string The contents of the file
     */
    private function unserializeArray(string $string): array
    {
        $retArray = [];

        $string = $string . PHP_EOL;
        $words = preg_split(
            '((\r\n)|(\r)|(\n))',
            cString::getPartOfString($string, 0, cString::getStringLength($string) - cString::getStringLength(PHP_EOL))
        );

        foreach ($words as $key => $value) {
            $oriTrans = preg_split('/(?<!\\\\)' . self::$originalTranslationDivider . '/', $value);

            if (isset($oriTrans[1])) {
                $retArray[cString::recodeString($oriTrans[0], $this->fileEncoding, $this->encoding)] = cString::recodeString(str_replace("\=", "=", $oriTrans[1]), $this->fileEncoding, $this->encoding);
            } else {
                // CON-1671 never use end(array_keys(...))
                $keys = array_keys($retArray);
                $lastKey = end($keys);
                $newValue = PHP_EOL . cString::recodeString(str_replace("\=", "=", $oriTrans[0]), $this->fileEncoding, $this->encoding);
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
     * @param int $clientId Client id to use
     * @param int $languageId Language id to use
     * @return string The file name like `lang_[language]_[COUNTRY].txt`, e.g. `lang_en_US.txt`
     */
    private function composeTranslationFileName(int $clientId, int $languageId): string
    {
        // Compose the translation file name lang_[language]_[COUNTRY].txt
        $info = $this->getLanguageInfo($clientId, $languageId);

        return sprintf(
            'lang_%s_%s.txt',
            $info['language'] ?? '',
            cString::toUpperCase($info['country'] ?? '')
        );
    }

    /**
     * Returns the language information array for a client and language.
     * The required data will be lazily loaded at the first call for each combination of client and language.
     *
     * @param int $clientId Client id to use
     * @param int $languageId Language id to use
     * @return array{language: string, country: string} Language info array like
     *      <pre>
     *      ['language' => 'en', 'country' => 'US']
     *      </pre>
     *      Note: Array can also be empty, in case of an error!
     * @throws cDbException|cException
     */
    private function getLanguageInfo(int $clientId, int $languageId): array
    {
        if (!isset(self::$langInfo[$clientId][$languageId])) {
            cApiPropertyCollection::reset();
            $propColl = new cApiPropertyCollection();
            $propColl->changeClient($clientId);
            $language = $propColl->getValue('idlang', $languageId, 'language', 'code', '');
            $country = $propColl->getValue('idlang', $languageId, 'country', 'code', '');
            self::$langInfo[$clientId][$languageId] = [
                'language' => $language,
                'country' => $country,
            ];
        }

        return self::$langInfo[$clientId][$languageId] ?? [];
    }

    /**
     * Save the contents of the wordListArray in the file.
     *
     * @return bool true on success or false on failure
     * @throws cInvalidArgumentException
     */
    public function saveTranslationArray(array $wordListArray): bool
    {
        $fileName = $this->modulePath . $this->directories['lang'] . self::$fileName;

        if (!$this->createModuleDirectory('lang') || !$this->isWritable($fileName, $this->modulePath . $this->directories['lang'])) {
            return false;
        }

        $escapedArray = [];
        foreach ($wordListArray as $key => $value) {
            $newKey = cString::ereg_replace("=", "\\=", $key);
            $newValue = cString::ereg_replace("=", "\\=", $value);
            $escapedArray[$newKey] = $newValue;
        }

        if (cFileHandler::write($fileName, $this->serializeArray($escapedArray) . "\r\n") === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get the translation array.
     *
     * @throws cInvalidArgumentException
     */
    public function getTranslationArray(): array
    {
        $filename = $this->modulePath . $this->directories['lang'] . self::$fileName;
        if (cFileHandler::exists($filename)) {
            return $this->unserializeArray(cFileHandler::read($filename));
        } else {
            return [];
        }
    }

}
