<?php
/**
 * This file includes the "keyword density" sub plugin from the old plugin repository.
 *
 * @package    Plugin
 * @subpackage Repository_KeywordDensity
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

function calcDensity($singlewordcounter, $string, $quantifier = 1) {
    $minLen = 3;

    //check if the current language is german
    //
    // in later versions it is possible to manage most used words for every language in the dB.
    if (cRegistry::getLanguageId() == 1)
        //most used german words
        $blacklist = array(
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
        );
    else {
        $blacklist = array();
        $minLen = 5;
    }

    //all blacklistentries to lowercase and trim ' ' at front.
    for ($i = 0; $i < count($blacklist); $i++) {
        $blacklist[$i] = ltrim(cString::toLowerCase($blacklist[$i]), '');
    }
    $tmp = explode(' ', $string);
    $tmp_size = sizeof($tmp);

    for ($i = 0; $i < $tmp_size; $i++) {
        if (cString::getStringLength($tmp[$i]) < $minLen) {
            continue;
        }

        // replace punctuation marks
        $patterns = array(
            '/[.,:]/'
        );
        $replaces = array(
            ''
        );
        $tmp[$i] = preg_replace($patterns, $replaces, $tmp[$i]);

        //trim last char if '-' e.g open-source-
        $tmp[$i] = rtrim($tmp[$i], '-');

        // hole word in upper cases ?
        (!ctype_upper($tmp[$i])) ? $tmp[$i] = cString::toLowerCase(addslashes($tmp[$i])) : $tmp[$i] = addslashes(preg_replace($patterns, $replaces, $tmp[$i]));

        // using mb_strtolower because of umlauts
        if (!array_search($tmp[$i], $blacklist)) {
            // if hole string in upper casses add additional quantifiert else
            // use only the string length
            (ctype_upper($tmp[$i])) ? $singlewordcounter[cString::toLowerCase($tmp[$i])] += cString::getStringLength($tmp[$i]) + 10000 : $singlewordcounter[$tmp[$i]] += cString::getStringLength($tmp[$i]);
        }
    }

    return $singlewordcounter;
}

function __cmp($a, $b) {
    if ($a == $b)
        return 0;
    return ($a > $b) ? -1 : 1;
}

function stripCount($singlewordcounter, $maxKeywords = 15) {

    // strip all with only 1
    $tmp = array();

    $result = array();

    $tmpToRemove = 1;
    foreach ($singlewordcounter as $key => $value) {
        if ($value > $tmpToRemove) {
            $tmp[$key] = $value;
        }
    }

    if (sizeof($tmp) <= $maxKeywords) {
        foreach ($tmp as $key => $value) {
            $result[] = $key;
        }
    } else {
        $dist = array();

        foreach ($tmp as $key => $value) {
            $dist[$value]++;
        }

        uksort($dist, "__cmp");
        reset($dist);

        $count = 0;

        $resultset = array();
        $useQuantity = array();

        foreach ($dist as $key => $value) {

            $_count = $count + $value;
            if ($_count <= $maxKeywords) {
                $count += $value;
                $useQuantity[] = $key;
            } else {
                break;
            }
        }

        // run all keywords and select by quantities to use
        foreach ($singlewordcounter as $key => $value) {
            if (in_array($value, $useQuantity)) {
                $result[] = $key;
            }
        }
    }
    return $result;
}

function keywordDensity($headline, $text) {
    global $lang, $client, $cfgClient;

    $headline = strip_tags($headline);
    $text = strip_tags($text);

    $text = conHtmlEntityDecode($text);

    // replace all non converted numbered entities (what about numbered entites?)
    // replace all double/more spaces
    $patterns = array(
        '#&[a-z]+\;#i',
        '#\s+#'
    );
    $replaces = array(
        '',
        ' '
    );
    $text = preg_replace($patterns, $replaces, $text);

    // path = cms_getUrlPath($idcat);
    // path = str_replace(cRegistry::getFrontendUrl();, '', $path);
    // path = cString::getPartOfString($path, 0, cString::getStringLength($path) - 1);
    // path = str_replace('/', ' ', $path);

    $singlewordcounter = array();

    // calc for text
    $singlewordcounter = calcDensity($singlewordcounter, $text);

    // calc for headline
    $singlewordcounter = calcDensity($singlewordcounter, $headline, 2);

    // get urlpath strings
    // singlewordcounter = calcDensity($singlewordcounter, $path, 4);

    arsort($singlewordcounter, SORT_NUMERIC);
    $singlewordcounter = stripCount($singlewordcounter);

    if (!is_array($singlewordcounter)) {
        return false;
    } else {
        return implode(', ', $singlewordcounter);
    }
}
?>
