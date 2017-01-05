<?php

/**
 * Defines the I18N CONTENIDO functions
 *
 * @package          Core
 * @subpackage       I18N
 * @author           Timo Hummel
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * gettext wrapper (for future extensions).
 *
 * Usage:
 * trans('Your text which has to be translated');
 *
 * @deprecated [2015-05-21]
 *         This method is no longer supported (no replacement)
 * @param string $string
 *         The string to translate
 * @return string
 *         Returns the translation
 */
function trans($string) {
    return cI18n::__($string);
}

/**
 * gettext wrapper (for future extensions).
 *
 * Usage:
 * i18n('Your text which has to be translated');
 *
 * @param string $string
 *         The string to translate
 * @param string $domain
 *         The domain to look up
 * @return string
 *         Returns the translation
 */
function i18n($string, $domain = 'contenido') {
    return cI18n::__($string, $domain);
}

/**
 * Emulates GNU gettext
 *
 * @param string $string
 *         The string to translate
 * @param string $domain
 *         The domain to look up
 * @return string
 *         Returns the translation
 */
function i18nEmulateGettext($string, $domain = 'contenido') {
    return cI18n::emulateGettext($string, $domain);
}

/**
 * Initializes the i18n stuff.
 *
 * @param string $localePath
 *         Path to the locales
 * @param string $langCode
 *         Language code to set
 * @param string $domain
 *         Language domain
 */
function i18nInit($localePath, $langCode, $domain = 'contenido') {
    cI18n::init($localePath, $langCode, $domain);
}

/**
 * Registers a new i18n domain.
 *
 * @param string $localePath
 *         Path to the locales
 * @param string $domain
 *         Domain to bind to
 * @return string
 *         Returns the translation
 */
function i18nRegisterDomain($domain, $localePath) {
    cI18n::registerDomain($domain, $localePath);
}

/**
 * Strips all unnecessary information from the $accept string.
 * Example: de,nl;q=0.7,en-us;q=0.3 would become an array with de,nl,en-us
 *
 * @param string $accept
 *         Comma searated list of languages to accept
 * @return array
 *         array with the short form of the accept languages
 */
function i18nStripAcceptLanguages($accept) {
    $languages = explode(',', $accept);
    $shortLanguages = array();
    foreach ($languages as $value) {
        $components = explode(';', $value);
        $shortLanguages[] = $components[0];
    }

    return $shortLanguages;
}

/**
 * Tries to match the language given by $accept to
 * one of the languages in the system.
 *
 * @param string $accept
 *         Language to accept
 * @return string
 *         The locale key for the given accept string
 */
function i18nMatchBrowserAccept($accept) {
    $available_languages = i18nGetAvailableLanguages();

    // Try to match the whole accept string
    foreach ($available_languages as $key => $value) {
        list($country, $lang, $encoding, $shortaccept) = $value;
        if ($accept == $shortaccept) {
            return $key;
        }
    }

    /*
     * Whoops, we are still here. Let's match the stripped-down string. Example:
     * de-ch isn't in the list. Cut it down after the '-' to 'de' which should
     * be in the list.
     */
    $accept = cString::getPartOfString($accept, 0, 2);
    foreach ($available_languages as $key => $value) {
        list($country, $lang, $encoding, $shortaccept) = $value;
        if ($accept == $shortaccept) {
            return $key;
        }
    }

    // / Whoops, still here? Seems that we didn't find any language. Return the
    // default (german, yikes)
    return false;
}

/**
 * Returns the available_languages array to prevent globals.
 *
 * @return array
 *         All available languages
 */
function i18nGetAvailableLanguages() {
    /*
     * array notes: First field: Language Second field: Country Third field:
     * ISO-Encoding Fourth field: Browser accept mapping Fifth field: SPAW
     * language
     */
    $aLanguages = array(
        'ar_AA' => array(
            'Arabic',
            'Arabic Countries',
            'ISO8859-6',
            'ar',
            'en'
        ),
        'be_BY' => array(
            'Byelorussian',
            'Belarus',
            'ISO8859-5',
            'be',
            'en'
        ),
        'bg_BG' => array(
            'Bulgarian',
            'Bulgaria',
            'ISO8859-5',
            'bg',
            'en'
        ),
        'cs_CZ' => array(
            'Czech',
            'Czech Republic',
            'ISO8859-2',
            'cs',
            'cz'
        ),
        'da_DK' => array(
            'Danish',
            'Denmark',
            'ISO8859-1',
            'da',
            'dk'
        ),
        'de_CH' => array(
            'German',
            'Switzerland',
            'ISO8859-1',
            'de-ch',
            'de'
        ),
        'de_DE' => array(
            'German',
            'Germany',
            'ISO8859-1',
            'de',
            'de'
        ),
        'el_GR' => array(
            'Greek',
            'Greece',
            'ISO8859-7',
            'el',
            'en'
        ),
        'en_GB' => array(
            'English',
            'Great Britain',
            'ISO8859-1',
            'en-gb',
            'en'
        ),
        'en_US' => array(
            'English',
            'United States',
            'ISO8859-1',
            'en',
            'en'
        ),
        'es_ES' => array(
            'Spanish',
            'Spain',
            'ISO8859-1',
            'es',
            'es'
        ),
        'fi_FI' => array(
            'Finnish',
            'Finland',
            'ISO8859-1',
            'fi',
            'en'
        ),
        'fr_BE' => array(
            'French',
            'Belgium',
            'ISO8859-1',
            'fr-be',
            'fr'
        ),
        'fr_CA' => array(
            'French',
            'Canada',
            'ISO8859-1',
            'fr-ca',
            'fr'
        ),
        'fr_FR' => array(
            'French',
            'France',
            'ISO8859-1',
            'fr',
            'fr'
        ),
        'fr_CH' => array(
            'French',
            'Switzerland',
            'ISO8859-1',
            'fr-ch',
            'fr'
        ),
        'hr_HR' => array(
            'Croatian',
            'Croatia',
            'ISO8859-2',
            'hr',
            'en'
        ),
        'hu_HU' => array(
            'Hungarian',
            'Hungary',
            'ISO8859-2',
            'hu',
            'hu'
        ),
        'is_IS' => array(
            'Icelandic',
            'Iceland',
            'ISO8859-1',
            'is',
            'en'
        ),
        'it_IT' => array(
            'Italian',
            'Italy',
            'ISO8859-1',
            'it',
            'it'
        ),
        'iw_IL' => array(
            'Hebrew',
            'Israel',
            'ISO8859-8',
            'he',
            'he'
        ),
        'nl_BE' => array(
            'Dutch',
            'Belgium',
            'ISO8859-1',
            'nl-be',
            'nl'
        ),
        'nl_NL' => array(
            'Dutch',
            'Netherlands',
            'ISO8859-1',
            'nl',
            'nl'
        ),
        'no_NO' => array(
            'Norwegian',
            'Norway',
            'ISO8859-1',
            'no',
            'en'
        ),
        'pl_PL' => array(
            'Polish',
            'Poland',
            'ISO8859-2',
            'pl',
            'en'
        ),
        'pt_BR' => array(
            'Brazillian',
            'Brazil',
            'ISO8859-1',
            'pt-br',
            'br'
        ),
        'pt_PT' => array(
            'Portuguese',
            'Portugal',
            'ISO8859-1',
            'pt',
            'en'
        ),
        'ro_RO' => array(
            'Romanian',
            'Romania',
            'ISO8859-2',
            'ro',
            'en'
        ),
        'ru_RU' => array(
            'Russian',
            'Russia',
            'ISO8859-5',
            'ru',
            'ru'
        ),
        'sh_SP' => array(
            'Serbian Latin',
            'Yugoslavia',
            'ISO8859-2',
            'sr',
            'en'
        ),
        'sl_SI' => array(
            'Slovene',
            'Slovenia',
            'ISO8859-2',
            'sl',
            'en'
        ),
        'sk_SK' => array(
            'Slovak',
            'Slovakia',
            'ISO8859-2',
            'sk',
            'en'
        ),
        'sq_AL' => array(
            'Albanian',
            'Albania',
            'ISO8859-1',
            'sq',
            'en'
        ),
        'sr_SP' => array(
            'Serbian Cyrillic',
            'Yugoslavia',
            'ISO8859-5',
            'sr-cy',
            'en'
        ),
        'sv_SE' => array(
            'Swedish',
            'Sweden',
            'ISO8859-1',
            'sv',
            'se'
        ),
        'tr_TR' => array(
            'Turkisch',
            'Turkey',
            'ISO8859-9',
            'tr',
            'tr'
        )
    );

    return $aLanguages;
}

/**
 * Now the function supports formated strings like %s.
 * e.g. echo mi18n("May the %s be with %s", 'force', 'you');
 * will output: May the force be with you
 *
 * @param string $key
 *         the string to translate
 * @return string
 *         the translated string
 */
function mi18n($key) {

    // skip empty keys
    if (0 === cString::getStringLength(trim($key))) {
        return 'No module translation ID specified.';
    }

    // dont workd by setup/upgrade
    cInclude('classes', 'contenido/class.module.php');
    cInclude('classes', 'module/class.module.filetranslation.php');

    // get all translations of current module
    $cCurrentModule = cRegistry::getCurrentModuleId();
    $contenidoTranslateFromFile = new cModuleFileTranslation($cCurrentModule, true);
    $translations = $contenidoTranslateFromFile->getLangArray();

    $translation = $translations[$key];

    // Get module_translation_message setting value
    $moduleTranslationMessage = getEffectiveSetting('debug', 'module_translation_message', 'true');
    $moduleTranslationMessage = 'true' === $moduleTranslationMessage ? true : false;

    // consider key as untranslated if translation has length 0
    // Don't trim translation, so that a string can be translated as ' '!
    // Show message only if module_translation_message mode is turn on
    if (0 === cString::getStringLength($translation)) {
        $translation = $moduleTranslationMessage ? 'Module translation not found: ' : '';
        $translation .= $key;
    }

    // call sprintf on translation with additional params
    if (1 < func_num_args()) {
        $arrArgs = func_get_args();
        $arrArgs[0] = $translation;
        $translation = call_user_func_array('sprintf', $arrArgs);
    }

    return trim($translation);
}
