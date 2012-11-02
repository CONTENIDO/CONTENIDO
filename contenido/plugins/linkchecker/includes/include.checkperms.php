<?php
/**
 * Project: CONTENIDO Content Management System
 * Description: Checks userrights for cats
 * Requirements: @con_php_req 5.0
 *
 *
 * @package CONTENIDO Plugins
 * @subpackage Linkchecker
 * @version 1.0.1
 * @author Mario Diaz
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 * @since file available since CONTENIDO release 4.8.7 {@internal created
 *        2006-06-08 modified 2007-11-07, Frederic Schneider,
 *        Linkchecker-Edition modified 2008-02-08, Andreas Lindner, Performance
 *        enhancements modified 2008-07-02, Frederic Schneider, add security fix
 *        $Id$: }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

function cCatPerm($widcat, $db = null) {
    global $cfg, $sess, $auth, $group_id, $_arrCatIDs_cCP;

    if (strpos($auth->auth['perm'], 'admin') !== FALSE) {
        return true;
    }

    if (is_null($db) || !is_object($db)) {
        $db = cRegistry::getDb();
    }

    $group_ids = getGroupIDs($db);
    $group_ids[] = cSecurity::escapeDB($auth->auth['uid'], $db);

    if (!is_array($_arrCatIDs_cCP)) {
        $_arrCatIDs_cCP = array();

        $sql_inc = " user_id='";
        $sql_inc .= implode("' OR user_id='", $group_ids) . "' ";
        $sql = "SELECT idcat FROM " . $cfg['tab']['rights'] . "
                WHERE idarea=6 AND idaction=359 AND ($sql_inc)";

        $db->query($sql);

        while ($db->next_record()) {
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

    $sql = "SELECT group_id FROM " . $cfg["tab"]["groupmembers"] . " WHERE user_id='" . cSecurity::escapeDB($auth->auth["uid"], $db) . "'";
    $db->query($sql);

    $_arrGroupIDs_gGI = array();

    while ($db->next_record())
        $_arrGroupIDs_gGI[] = $db->f('group_id');

    return $_arrGroupIDs_gGI;
}

?>