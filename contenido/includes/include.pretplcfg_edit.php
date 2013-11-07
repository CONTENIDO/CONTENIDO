<?php
/**
 * This file contains the backend page for editing the template pre configuration.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Olaf Niemann
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

if (!isset($idtplcfg)) {
    $sql = "SELECT
                idtplcfg
            FROM
                ".$cfg["tab"]["tpl"]."
            WHERE
                idtpl = '".cSecurity::toInteger($idtpl)."'";

    $db->query($sql);
    $db->nextRecord();

    $idtplcfg = $db->f("idtplcfg");

    if ($idtplcfg == 0) {
        //$nextid = $db->nextid($cfg["tab"]["tpl_conf"]);
        $timestamp = time();
        $sql = "INSERT INTO ".$cfg["tab"]["tpl_conf"]."
                    (idtpl, status, author, created, lastmodified)
                VALUES
                    ('".cSecurity::toInteger($idtpl)."', '', '', '".$timestamp."', '".$timestamp."')";

        $db->query($sql);
        $idtplcfg = $db->getLastInsertedId($cfg["tab"]["tpl_conf"]);

        $sql = "UPDATE ".$cfg["tab"]["tpl"]." SET idtplcfg = '".cSecurity::toInteger($idtplcfg)."' WHERE idtpl = '".cSecurity::toInteger($idtpl)."'";
        $db->query($sql);
    }

}

if (isset($idtplcfg)) {
// ############ @FIXME Same code as in contenido/includes/include.tplcfg_edit.php
    $sql = "SELECT number FROM " . $cfg["tab"]["container"] . " WHERE idtpl = '" . cSecurity::toInteger($idtpl) . "'";
    $db->query($sql);

    $varstring = array();

    while ($db->nextRecord()) {
        $number = $db->f('number');
        $CiCMS_VAR = "C{$number}CMS_VAR";

        if (isset($_POST[$CiCMS_VAR]) && is_array($_POST[$CiCMS_VAR])) {
            if (!isset($varstring[$number])) {
                $varstring[$number] = '';
            }
            // NOTE: We could use http_build_query here!
            foreach ($_POST[$CiCMS_VAR] as $key => $value) {
                $varstring[$number] .= $key . '=' . urlencode($value) . '&';
            }
        }
    }

    // Update/insert in container_conf
    if (count($varstring) > 0) {
        // Delete all containers
        $sql = "DELETE FROM " . $cfg["tab"]["container_conf"] . " WHERE idtplcfg = '" . cSecurity::toInteger($idtplcfg) . "'";
        $db->query($sql);

        foreach ($varstring as $col => $val) {
            // Insert all containers
            $sql = "INSERT INTO " . $cfg["tab"]["container_conf"] . " (idtplcfg, number, container) " .
                    "VALUES ('" . cSecurity::toInteger($idtplcfg) . "', '" . cSecurity::toInteger($col) . "', '" . $db->escape($val) . "') ";

            $db->query($sql);
        }
    }
// ###### END FIXME

    //is form send
    if ($x > 0) {
        $notification->displayNotification(cGuiNotification::LEVEL_INFO,i18n("Saved changes successfully!"));
    }
}
?>