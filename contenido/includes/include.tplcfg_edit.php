<?php
/**
 * This file contains the backend page for editing template configurations.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Jan Lengowski, Olaf Niemann
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

if (!isset($idtpl)) {
    $idtpl = 0;
}

if ($idtpl != 0 && $idtplcfg != 0) {
    $sql = "SELECT number FROM " . $cfg["tab"]["container"] . " WHERE idtpl = '" . cSecurity::toInteger($idtpl) . "'";
    $db->query($sql);

    while ($db->nextRecord()) {
        $i = $db->f("number");
        $CiCMS_VAR = "C" . $i . "CMS_VAR";

        if (isset($_POST[$CiCMS_VAR])) {
            $tmp = $_POST[$CiCMS_VAR];
        } else {
            unset($tmp);
        }

        if (isset($tmp)) {
            foreach ($tmp as $key => $value) {
                if (!isset($varstring[$i])) {
                    $varstring[$i] = "";
                }
                $varstring[$i] = $varstring[$i] . $key . "=" . urlencode($value) . "&";
            }
        }
    }

    // update/insert in container_conf
    if (isset($varstring) && is_array($varstring)) {
        // delete all containers
        $sql = "DELETE FROM " . $cfg["tab"]["container_conf"] . " WHERE idtplcfg = '" . cSecurity::toInteger($idtplcfg) . "'";
        $db->query($sql);

        foreach ($varstring as $col => $val) {
            // insert all containers
            $sql = "INSERT INTO " . $cfg["tab"]["container_conf"] . " (idtplcfg, number, container) " .
                    "VALUES ('" . cSecurity::toInteger($idtplcfg) . "', '" . cSecurity::toInteger($col) . "', '" . $db->escape($val) . "') ";

            $db->query($sql);
        }
    }

    if ($idart) {
        //echo "art: idart: $idart, idcat: $idcat";
        $sql = "UPDATE " . $cfg["tab"]["art_lang"] . " SET idtplcfg = '" . cSecurity::toInteger($idtplcfg) . "' WHERE idart='$idart' AND idlang='" . cSecurity::toInteger($lang) . "'";
        $db->query($sql);
    } else {
        //echo "cat: idart: $idart, idcat: $idcat";
        $sql = "UPDATE " . $cfg["tab"]["cat_lang"] . " SET idtplcfg = '" . cSecurity::toInteger($idtplcfg) . "' WHERE idcat='$idcat' AND idlang='" . cSecurity::toInteger($lang) . "'";
        $db->query($sql);
    }

    if ($changetemplate == 1 && $idtplcfg != 0) {
        // update template conf
        $sql = "UPDATE " . $cfg["tab"]["tpl_conf"] . " SET idtpl='" . cSecurity::toInteger($idtpl) . "' WHERE idtplcfg='" . cSecurity::toInteger($idtplcfg) . "'";
        $db->query($sql);

        // delete old configured containers
        $sql = "DELETE FROM " . $cfg["tab"]["container_conf"] . " WHERE idtplcfg='" . cSecurity::toInteger($idtplcfg) . "'";
        $db->query($sql);
        $changetemplate = 0;
    } else {
        // donut
    }

    if ($changetemplate != 1) {
        if (isset($idart) && 0 != $idart) {
            conGenerateCode($idcat, $idart, $lang, $client);
            //backToMainArea($send);
        } else {
            conGenerateCodeForAllartsInCategory($idcat);
            if ($back == 'true') {
                backToMainArea($send);
            }
        }
    }
} elseif ($idtpl == 0) {

    // template deselected

    if (isset($idtplcfg) && $idtplcfg != 0) {
        $sql = "DELETE FROM " . $cfg["tab"]["tpl_conf"] . " WHERE idtplcfg = '" . cSecurity::toInteger($idtplcfg) . "'";
        $db->query($sql);

        $sql = "DELETE FROM " . $cfg["tab"]["container_conf"] . " WHERE idtplcfg = '" . cSecurity::toInteger($idtplcfg) . "'";
        $db->query($sql);
    }

    $idtplcfg = 0;
    if (!isset($changetemplate)) {
        $changetemplate = 0;
    }

    if ($idcat != 0 && $changetemplate == 1 && !$idart) {
        // Category
        $sql = "SELECT idtplcfg FROM " . $cfg["tab"]["cat_lang"] . " WHERE idcat = '" . cSecurity::toInteger($idcat) . "' AND idlang = '" . cSecurity::toInteger($lang) . "'";
        $db->query($sql);
        $db->nextRecord();
        $tmp_idtplcfg = $db->f("idtplcfg");

        $sql = "DELETE FROM " . $cfg["tab"]["tpl_conf"] . " WHERE idtplcfg = '" . cSecurity::toInteger($tmp_idtplcfg) . "'";
        $db->query($sql);

        $sql = "DELETE FROM " . $cfg["tab"]["container_conf"] . " WHERE idtplcfg = '" . cSecurity::toInteger($tmp_idtplcfg) . "'";
        $db->query($sql);

        $sql = "UPDATE " . $cfg["tab"]["cat_lang"] . " SET idtplcfg = 0 WHERE idcat = '" . cSecurity::toInteger($idcat) . "' AND idlang = '" . cSecurity::toInteger($lang) . "'";
        $db->query($sql);

        conGenerateCodeForAllartsInCategory($idcat);
        backToMainArea($send);
    } elseif (isset($idart) && $idart != 0 && $changetemplate == 1) {

        // Article
        $sql = "SELECT idtplcfg FROM " . $cfg["tab"]["art_lang"] . " WHERE idart = '" . cSecurity::toInteger($idart) . "' AND idlang = '" . cSecurity::toInteger($lang) . "'";
        $db->query($sql);
        $db->nextRecord();
        $tmp_idtplcfg = $db->f("idtplcfg");

        $sql = "DELETE FROM " . $cfg["tab"]["tpl_conf"] . " WHERE idtplcfg = '" . cSecurity::toInteger($tmp_idtplcfg) . "'";
        $db->query($sql);

        $sql = "DELETE FROM " . $cfg["tab"]["container_conf"] . " WHERE idtplcfg = '" . cSecurity::toInteger($tmp_idtplcfg) . "'";
        $db->query($sql);

        $sql = "UPDATE " . $cfg["tab"]["art_lang"] . " SET idtplcfg = 0 WHERE idart = '" . cSecurity::toInteger($idart) . "' AND idlang = '" . cSecurity::toInteger($lang) . "'";
        $db->query($sql);

        conGenerateCodeForAllartsInCategory($idcat);
        //backToMainArea($send);
    }
} else {

    if ($changetemplate == 1) {
        if (!$idart) {
            $sql = "SELECT idtplcfg FROM " . $cfg["tab"]["cat_lang"] . " WHERE idcat = '" . cSecurity::toInteger($idcat) . "' AND idlang = '" . cSecurity::toInteger($lang) . "'";
            $db->query($sql);
            $db->nextRecord();
            $tmp_idtplcfg = $db->f("idtplcfg");

            $sql = "DELETE FROM " . $cfg["tab"]["tpl_conf"] . " WHERE idtplcfg = '" . cSecurity::toInteger($tmp_idtplcfg) . "'";
            $db->query($sql);

            $sql = "DELETE FROM " . $cfg["tab"]["container_conf"] . " WHERE idtplcfg = '" . cSecurity::toInteger($tmp_idtplcfg) . "'";
            $db->query($sql);
        } else {
            $sql = "SELECT idtplcfg FROM " . $cfg["tab"]["art_lang"] . " WHERE idart = '" . cSecurity::toInteger($idart) . "' AND idlang = '" . cSecurity::toInteger($lang) . "'";
            $db->query($sql);
            $db->nextRecord();
            $tmp_idtplcfg = $db->f("idtplcfg");

            $sql = "DELETE FROM " . $cfg["tab"]["tpl_conf"] . " WHERE idtplcfg = '" . cSecurity::toInteger($tmp_idtplcfg) . "'";
            $db->query($sql);

            $sql = "DELETE FROM " . $cfg["tab"]["container_conf"] . " WHERE idtplcfg = '" . cSecurity::toInteger($tmp_idtplcfg) . "'";
            $db->query($sql);
        }
    }
    conGenerateCodeForAllartsInCategory($idcat);
}

?>
