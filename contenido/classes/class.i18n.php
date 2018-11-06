<?php
/**
 * This file contains the the I18N class.
 *
 * @package Core
 * @subpackage I18N
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Internationalization (i18n) class.
 *
 * @package Core
 * @subpackage I18N
 */
class cI18n {

    /**
     * i18n related assoziative data cache.
     *
     * @var array
     */
    protected static $_i18nData = array(
        'language' => NULL,
        'domains' => array(),
        'files' => array(),
        'cache' => array()
    );

    /**
     * Initializes the i18n.
     *
     * @param string $localePath
     *         Path to the locales
     * @param string $langCode
     *         Language code to set
     * @param string $domain [optional]
     *         Language domain
     */
    public static function init($localePath, $langCode, $domain = 'contenido') {
        if (function_exists('bindtextdomain')) {
            // Bind the domain 'contenido' to our locale path
            bindtextdomain($domain, $localePath);

            // Set the default text domain to 'contenido'
            textdomain($domain);

            // Half brute-force to set the locale.
            if (!ini_get('safe_mode')) {
                putenv("LANG=$langCode");
            }

            if (defined('LC_MESSAGES')) {
                setlocale(LC_MESSAGES, $langCode);
            }

            if (false === empty($langCode)) {
                setlocale(LC_CTYPE, $langCode);
            }
        }

        self::$_i18nData['domains'][$domain] = $localePath;
        self::$_i18nData['language'] = $langCode;
    }

    /**
     * Returns translation of a specific text, wrapper for translate().
     *
     * @param string $string
     *                       The string to translate
     * @param string $domain [optional]
     *                       The domain to look up
     *
     * @return string
     *         Returns the translation
     *
     * @throws cException
     */
    public static function __($string, $domain = 'contenido') {
        return self::translate($string, $domain);
    }

    /**
     * Returns translation of a specific text
     *
     * @param string $string
     *         The string to translate
     * @param string $domain [optional]
     *         The domain to look up
     *
     * @return string
     *         Returns the translation
     *
     * @throws cException
     *         if this is the backend mode and the $belang is not set
     */
    public static function translate($string, $domain = 'contenido') {
        global $cfg, $belang, $contenido;

        // Auto initialization
        if (!self::$_i18nData['language']) {
            if (!isset($belang)) {
                if ($contenido) {
                    throw new cException('init $belang is not set');
                }
                // Needed - otherwise this won't work
                $belang = false;
            }

            // CON-2165
            // initialise localisation of plugins correctly in frontend
            if ($domain === 'contenido') {
                self::init($cfg['path']['contenido_locale'], $belang, $domain);
            } else {
                if (empty($belang)) {
                    $oApiLang = cRegistry::getLanguage();
                    $language = $oApiLang->getProperty('language', 'code');
                    $country = $oApiLang->getProperty('country', 'code');

                    $locale = $language . '_' . cString::toUpperCase($country);
                    self::init($cfg['path']['contenido'] . $cfg['path']['plugins'] . $domain . '/locale/', $locale, $domain);
                } else {
                    self::init($cfg['path']['contenido'] . $cfg['path']['plugins'] . $domain . '/locale/', $belang, $domain);
                }
            }
        }

        // Is emulator to use?
        if (!$cfg['native_i18n']) {
            $ret = self::emulateGettext($string, $domain);
            // hopefully a proper replacement for
            // mb_convert_encoding($string, 'HTML-ENTITIES', 'utf-8');
            // see http://stackoverflow.com/q/11974008
            $ret = htmlspecialchars_decode(utf8_decode(conHtmlentities($ret, ENT_COMPAT, 'utf-8', false)));
            return $ret;
        }

        // Try to use native gettext implementation
        if (extension_loaded('gettext')) {
            if (function_exists('dgettext')) {
                if ($domain != 'contenido') {
                    $translation = dgettext($domain, $string);
                    return $translation;
                } else {
                    return gettext($string);
                }
            }
        }

        // Emulator as fallback
        $ret = self::emulateGettext($string, $domain);
        if (cString::isUtf8($ret)) {
            $ret = utf8_decode($ret);
        }
        return $ret;
    }

    /**
     * Returns the current language (if already defined)
     *
     * @return string|false
     */
    public static function getLanguage() {
        return (self::$_i18nData['language']) ? self::$_i18nData['language'] : false;
    }

    /**
     * Returns list of registered domains
     *
     * @return array
     */
    public static function getDomains() {
        return self::$_i18nData['domains'];
    }

    /**
     * Returns list of cached tranlation files
     *
     * @return array
     */
    public static function getFiles() {
        return self::$_i18nData['files'];
    }

    /**
     * Returns list of cached tranlations
     *
     * @return array
     */
    public static function getCache() {
        return self::$_i18nData['cache'];
    }

    /**
     * Resets cached translation data (language, domains, files, and cache)
     */
    public static function reset() {
        self::$_i18nData['language'] = NULL;
        self::$_i18nData['domains'] = array();
        self::$_i18nData['files'] = array();
        self::$_i18nData['cache'] = array();
    }

    /**
     * Emulates GNU gettext
     *
     * @param string $string
     *                       The string to translate
     * @param string $domain [optional]
     *                       The domain to look up
     *
     * @return string
     *         Returns the translation
     *
     * @throws cInvalidArgumentException
     */
    public static function emulateGettext($string, $domain = 'contenido') {
        if ($string == '') {
            return '';
        }

        if (!isset(self::$_i18nData['cache'][$domain])) {
            self::$_i18nData['cache'][$domain] = array();
        }
        if (isset(self::$_i18nData['cache'][$domain][$string])) {
            return self::$_i18nData['cache'][$domain][$string];
        }

        $translationFile = self::$_i18nData['domains'][$domain] . self::$_i18nData['language'] . '/LC_MESSAGES/' . $domain . '.po';
        if (!cFileHandler::exists($translationFile)) {
            return $string;
        }

        if (!isset(self::$_i18nData['files'][$domain])) {
            self::$_i18nData['files'][$domain] = self::_loadTranslationFile($translationFile);
        }

        $stringStart = cString::findFirstPos(self::$_i18nData['files'][$domain], '"' . str_replace(array(
            "\n",
            "\r",
            "\t"
        ), array(
            '\n',
            '\r',
            '\t'
        ), $string) . '"');
        if ($stringStart === false) {
            return $string;
        }

        $matches = array();
        $quotedString = preg_quote(str_replace(array(
            "\n",
            "\r",
            "\t"
        ), array(
            '\n',
            '\r',
            '\t'
        ), $string), '/');
        $result = preg_match("/msgid.*\"(" . $quotedString . ")\"(?:\s*)?\nmsgstr(?:\s*)\"(.*)\"/", self::$_i18nData['files'][$domain], $matches);
        // Old:
        // preg_match("/msgid.*\"".preg_quote($string,"/")."\".*\nmsgstr(\s*)\"(.*)\"/",
        // self::$_i18nData['files'][$domain], $matches);

        if ($result && !empty($matches[2])) {
            // Translation found, cache it
            self::$_i18nData['cache'][$domain][$string] = stripslashes(str_replace(array(
                '\n',
                '\r',
                '\t'
            ), array(
                "\n",
                "\r",
                "\t"
            ), $matches[2]));
        } else {
            // Translation not found, cache original string
            self::$_i18nData['cache'][$domain][$string] = $string;
        }

        return self::$_i18nData['cache'][$domain][$string];
    }

    /**
     * Registers a new i18n domain.
     *
     * @param string $domain
     *         Domain to bind to
     * @param string $localePath
     *         Path to the locales
     */
    public static function registerDomain($domain, $localePath) {
        if (function_exists('bindtextdomain')) {
            // Bind the domain 'contenido' to our locale path
            bindtextdomain($domain, $localePath);
        }
        self::$_i18nData['domains'][$domain] = $localePath;
    }

    /**
     * Loads gettext translation and file does some operations like stripping
     * comments on the content.
     *
     * @param string $translationFile
     *
     * @return string
     *         The preparend translation file content
     *
     * @throws cInvalidArgumentException
     */
    protected static function _loadTranslationFile($translationFile) {
        $content = cFileHandler::read($translationFile);

        // Normalize eol chars
        $content = str_replace("\n\r", "\n", $content);
        $content = str_replace("\r\n", "\n", $content);

        // Remove comment lines
        $content = preg_replace('/^#.+\n/m', '', $content);

        // Prepare for special po edit format
        /*
         * Something like: #, php-format msgid "" "Hello %s,\n" "\n" "you've got
         * a new reminder for the client '%s' at\n" "%s:\n" "\n" "%s" msgstr ""
         * "Hallo %s,\n" "\n" "du hast eine Wiedervorlage erhalten für den
         * Mandanten '%s' at\n" "%s:\n" "\n" "%s" has to be converted to: msgid
         * "Hello %s,\n\nyou've got a new reminder for the client '%s'
         * at\n%s:\n\n%s" msgstr "Hallo %s,\n\ndu hast eine Wiedervorlage
         * erhalten für den Mandanten '%s' at\n%s:\n\n%s"
         */
        // assemble broken long message lines (remove double quotes with a line
        // break in between, e.g. "\n")
        $content = preg_replace('/(""\\s+")/m', '"', $content);
        // replace line breaks followed by a whitespace character against a line
        // break
        $content = preg_replace('/\\n"\\s+"/m', '\\n', $content);
        // remove multiple line breaks
        $content = preg_replace('/("\n+")/m', '', $content);
        // remove the backslash from double quotes (\"foobar\" -> "foobar")
        $content = preg_replace('/(\\\")/m', '"', $content);

        return $content;
    }
}