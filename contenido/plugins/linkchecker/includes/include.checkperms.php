<?php
/**
 * This file contains function to check user rights for the linkchecker plugin.
 *
 * @package    Plugin
 * @subpackage Linkchecker
 * @version    SVN Revision $Rev:$
 *
 * @author     Mario Diaz
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

function cCatPerm($widcat, $db = NULL) {
    global $cfg, $sess, $auth, $group_id, $_arrCatIDs_cCP;

    if (strpos($auth->auth['perm'], 'admin') !== FALSE) {
        return true;
    }

    if (is_null($db) || !is_object($db)) {
        $db = cRegistry::getDb();
    }

    $group_ids = getGroupIDs($db);
    $group_ids[] = $db->escape($auth->auth['uid']);

    if (!is_array($_arrCatIDs_cCP)) {
        $_arrCatIDs_cCP = array();

        $sql_inc = " user_id='";
        $sql_inc .= implode("' OR user_id='", $group_ids) . "' ";
        $sql = "SELECT idcat FROM " . $cfg['tab']['rights'] . "
                WHERE idarea=6 AND idaction=359 AND ($sql_inc)";

        $db->query($sql);

        while ($db->nextRecord()) {
            $_arrCatIDs_cCP[$db->f('idcat')] = '';
        }
    }

    return array_key_exists($widcat, $_arrCatIDs_cCP);
}

function getGroupIDs(&$db) {
    global $cfg, $sess, $auth, $group_id, $_arrGroupIDs_gGI;

    if (is_array($_arrGroupIDs_gGI)) {
        return $_arrGroupIDs_gGI;
    }

    $sql = "SELECT group_id FROM " . $cfg["tab"]["groupmembers"] . " WHERE user_id='" . $db->escape($auth->auth["uid"]) . "'";
    $db->query($sql);

    $_arrGroupIDs_gGI = array();

    while ($db->nextRecord())
        $_arrGroupIDs_gGI[] = $db->f('group_id');

    return $_arrGroupIDs_gGI;
}

?>