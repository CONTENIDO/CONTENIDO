<?php

/**
 * Defines the I18N CONTENIDO functions
 *
 * @package    Core
 * @subpackage I18N
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @deprecated [2015-05-21] This method is no longer supported (no replacement)
 */
function trans($string)
{
    return cI18n::__($string);
}

/**
 * @see cI18n::__() for more details.
 * @throws cException
 */
function i18n(string $string, string $domain = 'contenido'): string
{
    return cI18n::__($string, $domain);
}

/**
 * @see cI18n::emulateGettext() for more details.
 * @throws cInvalidArgumentException
 */
function i18nEmulateGettext(string $string, string $domain = 'contenido'): string
{
    return cI18n::emulateGettext($string, $domain);
}

/**
 * @see cI18n::init() for more details.
 */
function i18nInit(string $localePath, string $locale, string $domain = 'contenido')
{
    cI18n::init($localePath, $locale, $domain);
}

/**
 * @see cI18n::registerDomain() for more details.
 */
function i18nRegisterDomain(string $domain, string $localePath)
{
    cI18n::registerDomain($domain, $localePath);
}

/**
 * Strips all unnecessary information from the $accept string.
 * Example: de,nl;q=0.7,en-us;q=0.3 would become an array with de,nl,en-us
 *
 * @param string $accept Comma separated list of languages to accept
 * @return array array with the short form of the accept languages
 */
function i18nStripAcceptLanguages(string $accept): array
{
    $languages = explode(',', $accept);
    $shortLanguages = [];
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
 * @param string $accept Language to accept
 * @return ?string The locale key for the given accept-string
 */
function i18nMatchBrowserAccept(string $accept): ?string
{
    $availableLanguages = i18nGetAvailableLanguages();

    // Try to match the whole accept string
    foreach ($availableLanguages as $key => $value) {
        // Two-character lowercase language codes (ISO 639-1 code), e.g. 'de', 'en'
        $isoCode = $value[3] ?? null;
        if ($accept == $isoCode) {
            return $key;
        }
    }

    // Whoops, we are still here. Let's match the stripped-down string. Example:
    // de-ch isn't in the list. Cut it down after the '-' to 'de' which should be in the list.
    $accept = cString::getPartOfString($accept, 0, 2);
    foreach ($availableLanguages as $key => $value) {
        // Two-character lowercase language codes (ISO 639-1 code), e.g. 'de', 'en'
        $isoCode = $value[3] ?? null;
        if ($accept == $isoCode) {
            return $key;
        }
    }

    // Whoops, still here? Seems that we didn't find any language. Return the default (german, yikes)
    return null;
}

/**
 * Returns the available_languages array to prevent globals.
 *
 * @return array All available languages
 */
function i18nGetAvailableLanguages(): array
{
    /*
     * array notes: First field: Language Second field: Country Third field:
     * ISO-Encoding Fourth field: Browser accept mapping Fifth field: SPAW
     * language
     */
    $aLanguages = [
        'ar_AA' => [
            'Arabic',
            'Arabic Countries',
            'ISO8859-6',
            'ar',
            'en',
        ],
        'be_BY' => [
            'Byelorussian',
            'Belarus',
            'ISO8859-5',
            'be',
            'en',
        ],
        'bg_BG' => [
            'Bulgarian',
            'Bulgaria',
            'ISO8859-5',
            'bg',
            'en',
        ],
        'cs_CZ' => [
            'Czech',
            'Czech Republic',
            'ISO8859-2',
            'cs',
            'cz',
        ],
        'da_DK' => [
            'Danish',
            'Denmark',
            'ISO8859-1',
            'da',
            'dk',
        ],
        'de_CH' => [
            'German',
            'Switzerland',
            'ISO8859-1',
            'de-ch',
            'de',
        ],
        'de_DE' => [
            'German',
            'Germany',
            'ISO8859-1',
            'de',
            'de',
        ],
        'el_GR' => [
            'Greek',
            'Greece',
            'ISO8859-7',
            'el',
            'en',
        ],
        'en_GB' => [
            'English',
            'Great Britain',
            'ISO8859-1',
            'en-gb',
            'en',
        ],
        'en_US' => [
            'English',
            'United States',
            'ISO8859-1',
            'en',
            'en',
        ],
        'es_ES' => [
            'Spanish',
            'Spain',
            'ISO8859-1',
            'es',
            'es',
        ],
        'fi_FI' => [
            'Finnish',
            'Finland',
            'ISO8859-1',
            'fi',
            'en',
        ],
        'fr_BE' => [
            'French',
            'Belgium',
            'ISO8859-1',
            'fr-be',
            'fr',
        ],
        'fr_CA' => [
            'French',
            'Canada',
            'ISO8859-1',
            'fr-ca',
            'fr',
        ],
        'fr_FR' => [
            'French',
            'France',
            'ISO8859-1',
            'fr',
            'fr',
        ],
        'fr_CH' => [
            'French',
            'Switzerland',
            'ISO8859-1',
            'fr-ch',
            'fr',
        ],
        'hr_HR' => [
            'Croatian',
            'Croatia',
            'ISO8859-2',
            'hr',
            'en',
        ],
        'hu_HU' => [
            'Hungarian',
            'Hungary',
            'ISO8859-2',
            'hu',
            'hu',
        ],
        'is_IS' => [
            'Icelandic',
            'Iceland',
            'ISO8859-1',
            'is',
            'en',
        ],
        'it_IT' => [
            'Italian',
            'Italy',
            'ISO8859-1',
            'it',
            'it',
        ],
        'iw_IL' => [
            'Hebrew',
            'Israel',
            'ISO8859-8',
            'he',
            'he',
        ],
        'nl_BE' => [
            'Dutch',
            'Belgium',
            'ISO8859-1',
            'nl-be',
            'nl',
        ],
        'nl_NL' => [
            'Dutch',
            'Netherlands',
            'ISO8859-1',
            'nl',
            'nl',
        ],
        'no_NO' => [
            'Norwegian',
            'Norway',
            'ISO8859-1',
            'no',
            'en',
        ],
        'pl_PL' => [
            'Polish',
            'Poland',
            'ISO8859-2',
            'pl',
            'en',
        ],
        'pt_BR' => [
            'Brazillian',
            'Brazil',
            'ISO8859-1',
            'pt-br',
            'br',
        ],
        'pt_PT' => [
            'Portuguese',
            'Portugal',
            'ISO8859-1',
            'pt',
            'en',
        ],
        'ro_RO' => [
            'Romanian',
            'Romania',
            'ISO8859-2',
            'ro',
            'en',
        ],
        'ru_RU' => [
            'Russian',
            'Russia',
            'ISO8859-5',
            'ru',
            'ru',
        ],
        'sh_SP' => [
            'Serbian Latin',
            'Yugoslavia',
            'ISO8859-2',
            'sr',
            'en',
        ],
        'sl_SI' => [
            'Slovene',
            'Slovenia',
            'ISO8859-2',
            'sl',
            'en',
        ],
        'sk_SK' => [
            'Slovak',
            'Slovakia',
            'ISO8859-2',
            'sk',
            'en',
        ],
        'sq_AL' => [
            'Albanian',
            'Albania',
            'ISO8859-1',
            'sq',
            'en',
        ],
        'sr_SP' => [
            'Serbian Cyrillic',
            'Yugoslavia',
            'ISO8859-5',
            'sr-cy',
            'en',
        ],
        'sv_SE' => [
            'Swedish',
            'Sweden',
            'ISO8859-1',
            'sv',
            'se',
        ],
        'tr_TR' => [
            'Turkisch',
            'Turkey',
            'ISO8859-9',
            'tr',
            'tr',
        ],
    ];

    return $aLanguages;
}

/**
 * Module translation function.
 * If a translation is missing, its key will be returned.
 * If the setting debug/module_translation_message is set to true, which is the default,
 * it then will be prefixed by 'Module translation not found: '.
 *
 * This function is variadic to support formatted strings like %s.
 * e.g. echo mi18n("May the %s be with %s.", 'force', 'you');
 * will return: "May the force be with you."
 *
 * @param string $key the string to translate
 * @param mixed ...$params Additional parameters
 * @return string the translated string
 * @throws cDbException|cException|cInvalidArgumentException
 */
function mi18n(string $key, ...$params): string
{
    $key = trim($key);

    // skip empty keys
    if (empty($key)) {
        return 'No module translation ID specified.';
    }

    // dont works by setup/upgrade
    cInclude('classes', 'contenido/class.module.php');
    cInclude('classes', 'module/class.module.filetranslation.php');

    // get all translations of current module
    $cCurrentModule = cRegistry::getCurrentModuleId();
    $contenidoTranslateFromFile = new cModuleFileTranslation($cCurrentModule, true);
    $translations = $contenidoTranslateFromFile->getLangArray();

    $translation = $translations[$key] ?? '';

    // consider key as untranslated if translation is empty
    // Don't trim translation, so that a string can be translated as ' '!
    // Show message only if module_translation_message mode is turn on
    if (empty($translation)) {
        // Get module_translation_message setting value
        $moduleTranslationMessage = getEffectiveSetting('debug', 'module_translation_message', 'true');
        $moduleTranslationMessage = $moduleTranslationMessage === 'true';
        $translation = $moduleTranslationMessage ? 'Module translation not found: ' : '';
        $translation .= $key;
    }

    // call sprintf on translation with additional params
    if (!empty($params)) {
        // No need for call_user_func_array, just unpack the params
        $translation = sprintf($translation, ...$params);
    }

    return trim($translation);
}
