<?php

/**
 * This file contains CONTENIDO Category API functions.
 *
 * If you are planning to add a function, please make sure that:
 * 1.) The function is in the correct place
 * 2.) The function is documented
 * 3.) The function makes sense and is generally usable
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Timo Hummel
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Seeks through the category tree and returns the node on a specific level.
 *
 * Example:
 * + Category A (15)
 * |-+ News (16)
 * | |- News A (17)
 * + Category B (18)
 * |-+ Internal (19)
 *
 * Given you are in the leaf "News A" (idcat 17), and you want to get out in which
 * "main" tree you are, you can call the function like this:
 *
 * cApiCatGetLevelNode(17,1);
 *
 * The example would return "Category A" (idcat 15). If you specify an invalid level,
 * the results are undefined.
 *
 * @deprecated [2015-05-21]
 *         This method is no longer supported (no replacement)
 * @param int $idcat
 *         The category number
 * @param int $minLevel [optional]
 *         The level to extract
 * @return int
 *         The category node on a specific level
 */
function cApiCatGetLevelNode($idcat, $minLevel = 0) {
    global $cfg, $client, $lang;

    cDeprecated('This method is deprecated and is not needed any longer');

    $db = cRegistry::getDb();

    $sql = "SELECT
                a.name AS name,
                a.idcat AS idcat,
                b.parentid AS parentid,
                c.level AS level
            FROM
                " . $cfg['tab']['cat_lang'] . " AS a,
                " . $cfg['tab']['cat'] . " AS b,
                " . $cfg['tab']['cat_tree'] . " AS c
            WHERE
                a.idlang   = " . (int) $lang . " AND
                b.idclient = " . (int) $client . " AND
                b.idcat    = " . (int) $idcat . " AND
                c.idcat    = b.idcat AND
                a.idcat    = b.idcat";

    $db->query($sql);
    $db->nextRecord();

    $parentid = $db->f('parentid');
    $thislevel = $db->f('level');

    if ($parentid != 0 && $thislevel >= $minLevel) {
        return cApiCatGetLevelNode($parentid, $minLevel);
    } else {
        return $idcat;
    }
}
