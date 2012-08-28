<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Repository key density
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Plugins
 * @subpackage Repository
 * @version    0.1
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.8.7
 *
 * {@internal
 *   created Unknown
 *
 *   $Id$:
 * }}
 *
 */

function calcDensity ($singlewordcounter, $string, $quantifier = 1) {

    $minLen = 4;

    // fill up tmp array (for text)
    $tmp = array();
    $tmp = explode(' ', $string);
    $tmp_size = sizeof($tmp);

    for ($i = 0; $i <= $tmp_size; $i++) {
        if (strlen($tmp[$i]) < $minLen) { continue; }

        // replace chars
        //fix 2008-06-25 timo.trautmann - do not remove special chars from other languages (french etc.)
        //$patterns = array('#[^-a-zA-Z0-9�������]#ei');
        //$replaces = array('');
        //$tmp[$i] = preg_replace($patterns, $replaces, $tmp[$i]);

        $singlewordcounter[strtolower(addslashes($tmp[$i]))] += $quantifier;
    }

    return $singlewordcounter;
}

function __cmp ($a, $b) { if ($a == $b) return 0; return ($a > $b) ? -1 : 1; }

function stripCount ($singlewordcounter, $maxKeywords = 15) {
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

function keywordDensity ($headline, $text) {
    global $lang, $client, $cfgClient;

    $headline = strip_tags($headline);
    $text = strip_tags($text);

    $text = html_entity_decode($text);

    // replace all non converted entities and double/more spaces
    $patterns = array('#&[a-zA-Z]+\;#ei', '#\s+#');
    $replaces = array('', ' ');
    $text = preg_replace($patterns, $replaces, $text);

    #$path = cms_getUrlPath($idcat);
    #$path = str_replace(cRegistry::getFrontendUrl();, '', $path);
    #$path = substr($path, 0, strlen($path) - 1);
    #$path = str_replace('/', ' ', $path);

    $singlewordcounter = array();

    // calc for text
    $singlewordcounter = calcDensity($singlewordcounter, $text);

    // calc for headline
    $singlewordcounter = calcDensity($singlewordcounter, $headline, 2);

    // get urlpath strings
    #$singlewordcounter = calcDensity($singlewordcounter, $path, 4);

    arsort($singlewordcounter, SORT_NUMERIC);
    $singlewordcounter = stripCount($singlewordcounter);

    if (!is_array($singlewordcounter)) {
        return false;
    } else {
        return implode(', ', $singlewordcounter);
    }
}
?>
