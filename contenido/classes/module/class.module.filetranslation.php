<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * This class save the translations from a modul in a file
 * and get it from file.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package CONTENIDO Backend Classes
 * @version 1.0.0
 * @author Rusmir Jusufovic
 * @copyright four for business AG <info@contenido.org>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * This class save the translations from a modul in a file
 * and get it from file.
 *
 * @author rusmir.jusufovic
 */
class cModuleFileTranslation extends cModuleHandler {

    /**
     * Path to the modul directory
     *
     * @var string
     */
    private $_modulePath;

    /**
     * Name of the translations file
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
     * The id of the modul
     *
     * @var int
     */
    static $savedIdMod = NULL;
    static $originalTranslationDivider = '=';

    /**
     *
     * @param array $cfg
     * @param int $idmodul
     * @param bool $static if true it will load once the translation from file
	 * @param int $overrideIdlang use different language if not NULL
     */
    public function __construct($idmodul = null, $static = false, $overrideIdlang = null) {
        parent::__construct($idmodul);

        // $this->_debug = true;

        if ($idmodul != null) {
            $this->_modulePath = $this->getModulePath();
        }
		
		// override language if specified
		if ($overrideIdlang != null) {
            $this->_idlang = $overrideIdlang;
        }

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
            self::$fileName = 'lang_' . $language . '_' . strtoupper($country) . '.txt';
        }
    }

    /**
     * Get the value of a item from properties db.
     *
     * @param string $type
     * @param string $name
     * @return string value
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
     * Save all translations from db in Filesystem.
     * Warning let run once, twice will be erase the translation witch are
     * there.
     */
    public function saveTranslationsFromDbToFile() {
        $db = cRegistry::getDb();

        $sql = 'SELECT cl.idlang AS idlang, c.idclient AS idclient, m.idmod AS idmod'
             . 'FROM `%s` AS cl, `%s` AS m, `%s` AS c WHERE cl.idclient = c.idclient';
        $sql = $db->prepare($sql, $this->_cfg['tab']['clients_lang'], $this->_cfg['tab']['mod'], $this->_cfg['tab']['clients']);
        $db->query($sql);

        while ($db->next_record()) {
            $contenidoTranslationsFromFile = new cModuleFileTranslation($db->f('idmod'));
            $contenidoTranslationsFromFile->saveTranslations();
        }
    }

    /**
     *
     * @todo noch nicht fertig
     */
    public function saveAllTranslations() {
        $db = cRegistry::getDb();

        $sql = 'SELECT m.idmod, mt.idlang, mt.original, mt.translation FROM `%s` AS mt, `%s` AS m '
             . 'WHERE mt.idmod = m.idmod ORDER BY m.idmod, mt.idlang';
        $sql = $db->prepare($sql, $this->_cfg['tab']['mod_translations'], $this->_cfg['tab']['mod']);
        $db->query($sql);

        $transArray = array();
        $saveModId = -1;
        $saveLangId = -1;
        while ($db->next_record()) {
            $transArray[cSecurity::unfilter($db->f('original'))] = cSecurity::unfilter($db->f('translation'));
            if ($saveLangId != $db->f('idlang') || $saveModId != $db->f('idmod')) {
                if ($saveLangId != -1 && $saveModId != -1) {
                    // reset translations array
                    $transArray = array();
                }
            }

            $saveLangId = $db->f('idlang');
            $saveModId = $db->f('idmod');
        }
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
            // @todo  Move to module translation model
            $sql = 'SELECT * FROM `%s` WHERE idlang = %d AND idmod = %d';
            $sql = $db->prepare($sql, $this->_cfg['tab']['mod_translations'], $idlang, $this->_idmod);
            $db->query($sql);

            $this->_idlang = $idlang;
            // set filename lang_[language]_[Country].txt
            $language = $this->_getValueFromProperties('language', 'code');
            $country = $this->_getValueFromProperties('country', 'code');
            self::$fileName = 'lang_' . $language . '_' . strtoupper($country) . '.txt';

            $translations = array();
            while ($db->next_record()) {
                $translations[cSecurity::unfilter($db->f('original'))] = cSecurity::unfilter($db->f('translation'));
            }

            if (count($translations) != 0) {
                if ($this->saveTranslationArray($translations) == false) {
                    cWarning(__FILE__, __LINE__, 'Could not save translate idmod=' . $this->_idmod . ' !');
                }
            }
        }
    }

    /**
     * This method serialize a array.
     * $key.[Divider].$value."\r\n"
     *
     * @param array $wordListArray
     * @return string
     */
    private function _serializeArray($wordListArray) {
        $retString = '';
        foreach ($wordListArray as $key => $value) {
            $value = iconv($this->_encoding, $this->_fileEncoding, $value);
            $key = iconv($this->_encoding, $this->_fileEncoding, $key);
            // Originall String [Divider] Translation String
            $retString .= $key . self::$originalTranslationDivider . $value . "\r\n";
        }

        return $retString;
    }

    /**
     * This method unserialize a string.
     * The contents of file looks like original String [Divider] Translation
     * String.
     * If divider is =
     * Example: Hello World=Hallo Welt
     *
     * @param string $string the contents of the file
     * @return array
     */
    private function _unserializeArray($string) {
        $retArray = array();

        $words = preg_split('((\r\n)|(\r)|(\n))', $string);

        foreach ($words as $key => $value) {
            $oriTrans = explode(self::$originalTranslationDivider, $value);

            if (!empty($oriTrans[0])) {
                if (isset($oriTrans[1])) {
                    $retArray[iconv($this->_fileEncoding, $this->_encoding, $oriTrans[0])] = iconv($this->_fileEncoding, $this->_encoding, $oriTrans[1]);
                } else {
                    $retArray[iconv($this->_fileEncoding, $this->_encoding, $oriTrans[0])] = '';
                }
            }
        }
        return $retArray;
    }

    /**
     * Save the contents of the wordListArray in file.
     *
     * @param array $wordListArray
     * @return boolean true if success else false
     */
    public function saveTranslationArray($wordListArray) {
        $fileName = $this->_modulePath . $this->_directories['lang'] . self::$fileName;

        if (!$this->createModuleDirectory('lang') || !$this->isWritable($fileName, $this->_modulePath . $this->_directories['lang'])) {
            return false;
        }

        if (cFileHandler::write($fileName, $this->_serializeArray($wordListArray)) === false) {
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

class Contenido_Module_FileTranslation extends cModuleFileTranslation {

    /** @deprecated [2012-07-24] class was renamed to cModuleFileTranslation */
    public function __construct($idmodul = null, $static = false) {
        cDeprecated('Class was renamed to cModuleFileTranslation.');
        parent::__construct($idmodul, $static);
    }

}