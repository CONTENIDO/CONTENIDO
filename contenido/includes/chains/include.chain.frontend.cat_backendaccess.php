<?php
/**
 * CONTENIDO Chain.
 * Category backend access feature.
 *
 * @package          Core
 * @subpackage       Chain
 * @version          SVN Revision $Rev:$
 *
 * @author           Unknown
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

function cecFrontendCategoryAccess_Backend($idlang, $idcat, $user)
{
    global $cfg, $perm;

    if ($perm->have_perm()) {
        // sysadmin or client admin can always access to protected areas
        return true;
    }

    $db2 = cRegistry::getDb();

    $arrSearchFor = array("'".cSecurity::escapeDB($user, $db2)."'");

    $sql = "SELECT * FROM ".$cfg['tab']['groupmembers']." WHERE user_id = '".cSecurity::escapeDB($user, $db2)."'";

    $db2->query($sql);

    while ($db2->nextRecord()) {
        $arrSearchFor[] = "'".cSecurity::escapeDB($db2->f('group_id'), $db2)."'";
    }

    $sSearchFor = implode(",", $arrSearchFor);

    $sql = "SELECT idright
                    FROM ".$cfg["tab"]["rights"]." AS A,
                         ".$cfg["tab"]["actions"]." AS B,
                         ".$cfg["tab"]["area"]." AS C
                     WHERE B.name = 'front_allow' AND C.name = 'str' AND A.user_id IN (". $sSearchFor .") AND A.idcat = '".cSecurity::toInteger($idcat)."'
                            AND A.idarea = C.idarea AND B.idaction = A.idaction AND A.idlang = '".cSecurity::toInteger($idlang)."'";

    $db2->query($sql);

    if (!$db2->nextRecord()) {
        return false;
    } else {
        return true;
    }
}?>
