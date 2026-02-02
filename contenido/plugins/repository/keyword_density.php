<?php

/**
 * This file includes the "keyword density" sub plugin from the old plugin repository.
 *
 * @package    Plugin
 * @subpackage Repository_KeywordDensity
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * TODO The parameter $quantifier is not used, check if it can be removed.
 */
function pirekd_calcDensity(array $singleWordCounter, string $string, int $quantifier = 1): array
{
    $minLen = 3;

    //check if the current language is german
    //
    // in later versions it is possible to manage most used words for every language in the dB.
    if (cRegistry::getLanguageId() == 1) {
        //most used German words
        $blacklist = [
            'in',
            'der',
            'und',
            'zu',
            'den',
            'das',
            'nicht',
            'von',
            'sie',
            'ist',
            'des',
            'sich',
            'mit',
            'sorgt',
            'dem',
            'dass',
            'er',
            'es',
            'ein',
            'ich',
            'auf',
            'so',
            'eine',
            'auch',
            'als',
            'an',
            'nach',
            'wie',
            'im',
            'für',
            'man',
            'aber',
            'aus',
            'durch',
            'wenn',
            'nur',
            'war',
            'noch',
            'werden',
            'bei',
            'hat',
            'wir',
            'was',
            'wird',
            'sein',
            'einen',
            'welche',
            'sind',
            'oder',
            'zur',
            'um',
            'haben',
            'einer',
            'mir',
            'über',
            'ihm',
            'diese',
            'einem',
            'ihr',
            'uns',
            'da',
            'zum',
            'kann',
            'doch',
            'vor',
            'dieser',
            'mich',
            'ihn',
            'du',
            'hatte',
            'seine',
            'mehr',
            'am',
            'denn',
            'nun',
            'unter',
            'sehr',
            'selbst',
            'schon',
            'hier',
            'bis',
            'habe',
            'ihre',
            'dann',
            'ihnen',
            'seiner',
            'alle',
            'wieder',
            'meine',
            'Zeit',
            'gegen',
            'vom',
            'ganz',
            'einzelnen',
            'wo',
            'muss',
            'ohne',
            'eines',
            'können',
            'sei',
            'ja',
            'wurde',
            'jetzt',
            'immer',
            'seinen',
            'wohl',
            'dieses',
            'ihren',
            'würde',
            'diesen',
            'sondern',
            'weil',
            'welcher',
            'nichts',
            'diesem',
            'alles',
            'waren',
            'will',
            'Herr',
            'viel',
            'mein',
            'also',
            'soll',
            'worden',
            'lassen',
            'dies',
            'machen',
            'ihrer',
            'weiter',
            'Leben',
            'recht',
            'etwas',
            'keine',
            'seinem',
            'ob',
            'dir',
            'allen',
            'großen',
            'die',
            'Jahre',
            'Weise',
            'müssen',
            'welches',
            'wäre',
            'erst',
            'einmal',
            'Mann',
            'hätte',
            'zwei',
            'dich',
            'allein',
            'Herren',
            'während',
            'Paragraph',
            'anders',
            'Liebe',
            'kein',
            'damit',
            'gar',
            'Hand',
            'Herrn',
            'euch',
            'sollte',
            'konnte',
            'ersten',
            'deren',
            'zwischen',
            'wollen',
            'denen',
            'dessen',
            'sagen',
            'bin',
            'Menschen',
            'gut',
            'darauf',
            'wurden',
            'weiß',
            'gewesen',
            'Seite',
            'bald',
            'weit',
            'große',
            'solche',
            'hatten',
            'eben',
            'andern',
            'beiden',
            'macht',
            'sehen',
            'ganze',
            'anderen',
            'lange',
            'wer',
            'ihrem',
            'zwar',
            'gemacht',
            'dort',
            'kommen',
            'Welt',
            'heute',
            'Frau',
            'werde',
            'derselben',
            'ganzen',
            'deutschen',
            'lässt',
            'vielleicht',
            'meiner',
            'bereits',
            'späteren',
            'möglich',
            'sowie'
        ];
    } else {
        $blacklist = [];
        $minLen = 5;
    }

    // all blacklist-entries to lowercase and trim ' ' at front.
    for ($i = 0; $i < count($blacklist); $i++) {
        $blacklist[$i] = ltrim(cString::toLowerCase($blacklist[$i]), '');
    }

    $tmp = explode(' ', $string);
    $tmp_size = count($tmp);

    for ($i = 0; $i < $tmp_size; $i++) {
        if (cString::getStringLength($tmp[$i]) < $minLen) {
            continue;
        }

        // replace punctuation marks
        $patterns = ['/[.,:]/'];
        $replaces = [''];
        $tmp[$i] = preg_replace($patterns, $replaces, $tmp[$i]);

        //trim last char if '-' e.g open-source-
        $tmp[$i] = rtrim($tmp[$i], '-');

        // whole word in upper cases?
        $val = !ctype_upper($tmp[$i]) ? cString::toLowerCase($tmp[$i]) : preg_replace($patterns, $replaces, $tmp[$i]);
        $tmp[$i] = addslashes($val);

        if (!array_search($tmp[$i], $blacklist)) {
            // if the whole string is in upper cases, add additional quantifier, else use only the string length
            if (ctype_upper($tmp[$i])) {
                if (empty($singleWordCounter[cString::toLowerCase($tmp[$i])])) {
                    $singleWordCounter[cString::toLowerCase($tmp[$i])] = 0;
                }
                $singleWordCounter[cString::toLowerCase($tmp[$i])] += cString::getStringLength($tmp[$i]) + 10000;
            } else {
                if (empty($singleWordCounter[$tmp[$i]])) {
                    $singleWordCounter[$tmp[$i]] = 0;
                }
                $singleWordCounter[$tmp[$i]] += cString::getStringLength($tmp[$i]);
            }
        }
    }

    return $singleWordCounter;
}

/**
 * Compare two values.
 */
function pirekd_cmp(int $a, int $b): int
{
    if ($a == $b) {
        return 0;
    }
    return $a > $b ? -1 : 1;
}

function pirekd_stripCount(array $singleWordCounter, int $maxKeywords = 15): array
{
    $result = [];

    // Remove all where the count is less than 1
    $filteredSingleWordCounter = array_filter($singleWordCounter, function ($value) { return $value > 1; });

    if (count($filteredSingleWordCounter) <= $maxKeywords) {
        return array_keys($filteredSingleWordCounter);
    }

    $dist = [];

    foreach ($filteredSingleWordCounter as $value) {
        if (!isset($dist[$value])) {
            $dist[$value] = 0;
        } else {
            $dist[$value]++;
        }
    }

    uksort($dist, 'pirekd_cmp');

    $count = 0;
    $useQuantity = [];

    foreach ($dist as $key => $value) {
        $_count = $count + $value;
        if ($_count <= $maxKeywords) {
            $count += $value;
            $useQuantity[] = $key;
        } else {
            break;
        }
    }

    // Loop through all keywords and select by quantities to use
    foreach ($singleWordCounter as $key => $value) {
        if (in_array($value, $useQuantity)) {
            $result[] = $key;
        }
    }

    return $result;
}

function pirekd_keywordDensity(string $headline, string $text): string
{
    $headline = strip_tags($headline);
    $text = conHtmlEntityDecode(strip_tags($text));

    // replace all non-converted numbered entities (what about numbered entities?)
    // replace all double/more spaces
    $text = preg_replace(['#&[a-z]+;#i', '#\s+#'], ['', ' '], $text);

    // path = cms_getUrlPath($idcat);
    // path = str_replace(cRegistry::getFrontendUrl();, '', $path);
    // path = cString::getPartOfString($path, 0, cString::getStringLength($path) - 1);
    // path = str_replace('/', ' ', $path);

    $singleWordCounter = [];

    // calc for text
    $singleWordCounter = pirekd_calcDensity($singleWordCounter, $text);

    // calc for headline
    $singleWordCounter = pirekd_calcDensity($singleWordCounter, $headline, 2);

    // get urlpath strings
    // singleWordCounter = pirekd_calcDensity($singleWordCounter, $path, 4);

    arsort($singleWordCounter, SORT_NUMERIC);
    $singleWordCounter = pirekd_stripCount($singleWordCounter);

    return count($singleWordCounter) ? implode(', ', $singleWordCounter) : '';
}


/**
 * @deprecated Since 2026-01-17, use {@see pirekd_calcDensity()} instead
 */
function calcDensity($singleWordCounter, $string, $quantifier = 1)
{
    cDeprecated(__FUNCTION__ . ' is Since 2026-01-17, use pirekd_calcDensity() instead');
    return pirekd_calcDensity($singleWordCounter, $string, $quantifier );
}
/**
 * @deprecated Since 2026-01-17, use {@see pirekd_cmp()} instead
 */
function __cmp($a, $b)
{
    cDeprecated(__FUNCTION__ . ' is Since 2026-01-17, use pirekd_cmp() instead');
    return pirekd_cmp($a, $b);
}
/**
 * @deprecated Since 2026-01-17, use {@see pirekd_stripCount()} instead
 */
function stripCount($singleWordCounter, $maxKeywords = 15)
{
    cDeprecated(__FUNCTION__ . ' is Since 2026-01-17, use pirekd_stripCount() instead');
    return pirekd_stripCount($singleWordCounter, $maxKeywords);
}
/**
 * @deprecated Since 2026-01-17, use {@see pirekd_keywordDensity()} instead
 */
function keywordDensity($headline, $text)
{
    cDeprecated(__FUNCTION__ . ' is Since 2026-01-17, use pirekd_keywordDensity() instead');
    return pirekd_keywordDensity($headline, $text);
}
