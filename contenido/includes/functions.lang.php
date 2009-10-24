<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Contenido Language Functions
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend includes
 * @version    1.2.6
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 *
 * {@internal
 *   created unknown
 *   modified 2008-06-26, Frederic Schneider, add security fix
 *   modified 2008-07-23, Timo Trautmann optional db param added for langGetTextDirection (performance tuning)
 *   modified 2009-10-23, Murat Purc, removed deprecated function (PHP 5.3 ready) and some formatting
 *
 *   $Id$:
 * }}
 *
 */

if(!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

cInclude("includes", "functions.con.php");
cInclude("includes", "functions.str.php");

/**
 * Edit a language
 *
 * @param string $name Name of the language
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @author Olaf Niemann <Olaf.Niemann@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
function langEditLanguage($idlang, $langname, $encoding, $active, $direction = "ltr") {
    global $db, $sess, $client, $cfg;

    $modified = date("Y-m-d H:i:s");

    $sql = "UPDATE
               ".$cfg["tab"]["lang"]."
           SET
               name = '".Contenido_Security::escapeDB($langname, $db)."',
               encoding = '".Contenido_Security::escapeDB($encoding, $db)."',
               active = '".Contenido_Security::toInteger($active)."',
               lastmodified = '".Contenido_Security::escapeDB($modified, $db)."',
               direction = '".Contenido_Security::escapeDB($direction, $db)."'
           WHERE
               idlang = '".Contenido_Security::toInteger($idlang)."'";

    $db->query($sql);

    return true;
}


/**
 * Create a new language
 *
 * @param string $name Name of the language
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @author Olaf Niemann <Olaf.Niemann@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
function langNewLanguage($name, $client) {
    global $db, $sess, $cfg, $cfgClient, $notification, $auth;
    $new_idlang = $db->nextid($cfg["tab"]["lang"]);
    $author = $auth->auth["uid"];
    $created = date("Y-m-d H:i:s");
    $modified = $created;

    // Add new language to database
    $sql = "INSERT INTO ".$cfg["tab"]["lang"]." (idlang, name, active, encoding, author, created, lastmodified) VALUES ('".Contenido_Security::toInteger($new_idlang)."', '".Contenido_Security::escapeDB($name, $db)."', '0',
           'iso-8859-1', '".Contenido_Security::escapeDB($author, $db)."', '".Contenido_Security::escapeDB($created, $db)."', '".Contenido_Security::escapeDB($modified, $db)."')";
    $db->query($sql);
    $sql = "INSERT INTO ".$cfg["tab"]["clients_lang"]." (idclientslang, idclient, idlang) VALUES ('".Contenido_Security::toInteger($db->nextid($cfg["tab"]["clients_lang"]))."', '".Contenido_Security::toInteger($client)."',
           '".Contenido_Security::toInteger($new_idlang)."')";
    $db->query($sql);

    // Ab hyr seynd Drachen
    $destPath = $cfgClient[$client]["path"]["frontend"];

    if (file_exists($destPath) && file_exists($destPath."config.php")) {
        $res = fopen($destPath."config.php","rb+");
        $res2 = fopen($destPath."config.php.new", "ab+");

        if ($res && $res2) {
            while (!feof($res)) {
                $buffer = fgets($res, 4096);
                $outbuf = str_replace("!LANG!", $new_idlang, $buffer);
                fwrite($res2, $outbuf);
            }

            fclose($res);
            fclose($res2);

            if (file_exists($destPath."config.php")) {
                unlink($destPath."config.php");
            }

            rename($destPath."config.php.new", $destPath."config.php");
        }
    } else {
        $notification->displayNotification("error", i18n("Could not set the language-ID in the file 'config.php'. Please set the language manually."));
    }
}


/**
 * Rename a language
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @author Olaf Niemann <Olaf.Niemann@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
function langRenameLanguage($idlang, $name) {
    global $db;
    global $cfg;

    $sql = "UPDATE ".$cfg["tab"]["lang"]." SET name='".Contenido_Security::escapeDB($name, $db)."' WHERE idlang='".Contenido_Security::toInteger($idlang)."'";
    $db->query($sql);
}


/**
 * Duplicate a language
 *
 * @param string $name Name of the language
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @author Olaf Niemann <Olaf.Niemann@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
function langDuplicateFromFirstLanguage($client, $idlang) {

    global $db, $sess, $cfg;

    $db2 = new DB_contenido;

    $sql = "SELECT * FROM ".$cfg["tab"]["clients_lang"]." WHERE idclient='".Contenido_Security::toInteger($client)."' ORDER BY idlang ASC";

    $db->query($sql);
    if ($db->next_record()) {     //***********if there is already a language copy from it
        $firstlang = $db->f("idlang");

        //***********duplicate entries in 'art_lang'-table*************
        $sql = "SELECT * FROM ".$cfg["tab"]["art_lang"]." AS A, ".$cfg["tab"]["art"]." AS B WHERE A.idart=B.idart AND B.idclient='".Contenido_Security::toInteger($client)."' AND idlang!='0'
                AND idlang='".Contenido_Security::toInteger($firstlang)."'";
        $db->query($sql);

        /* Array storing the article->templatecfg allocations for later reallocation */
        $cfg_art = array();

        while ($db->next_record()) {
            /* Store the idartlang->idplcfg allocation for later reallocation */
            $cfg_art[] = array('idartlang' => $db->f('idartlang'),
                               'idtplcfg'  => $db->f('idtplcfg'));

            $keystring  = "";
            $valuestring = "";

            while (list($key, $value) = each($db->Record)) {
                if (is_string($key) && (strpos($key, 'idartlang') === false) && (strpos($key, 'idlang') !== 0) && (strpos($key, 'idclient') !== 0)) {
                    $keystring   = $keystring.",".$key;
                    $valuestring = $valuestring.",'".addslashes($value)."'";
                } elseif (is_string($key) && (strpos($key, 'idartlang') !== false)) {
                    $tmp_idartlang_alt = $value;
                }
            }

            $tmp_idartlang_neu = $db2->nextid($cfg["tab"]["art_lang"]);

            $keystring = $keystring.",idartlang";
            $keystring = preg_replace('/,$/', '', $keystring);
            $keystring = preg_replace('/^,/', '', $keystring);
            $keystring = $keystring.",idlang";

            $valuestring = $valuestring.",$tmp_idartlang_neu";
            $valuestring = preg_replace('/,$/', '', $valuestring);
            $valuestring = preg_replace('/^,/', '', $valuestring);
            $valuestring = $valuestring.",$idlang";

            //********* duplicates entry in DB ****************
            $sql = "INSERT INTO ".$cfg["tab"]["art_lang"]." (".Contenido_Security::escapeDB($keystring, $db2).") VALUES (".Contenido_Security::escapeDB($valuestring, $db2).")";
            $db2->query($sql);

            //***********duplicate entries in 'cat_lang'-table*************
            $sql = "SELECT * FROM ".$cfg["tab"]["content"]." WHERE idartlang='".Contenido_Security::toInteger($tmp_idartlang_alt)."'";
            $db2->query($sql);

            while ($db2->next_record()) {
                $keystring  = "";
                $valuestring = "";
                while (list($key, $value) = each($db2->Record)) {
                    if (is_string($key) && (strpos($key, 'idcontent') === false) && (strpos($key, 'idartlang') !== 0)) {
                        $keystring   = $keystring.",".$key;
                        $valuestring = $valuestring.",'".addslashes($value)."'";
                    }
                }
                $keystring = preg_replace('/,$/', '', $keystring);
                $keystring = preg_replace('/^,/', '', $keystring);
                $keystring = $keystring.",idartlang";

                $valuestring = preg_replace('/,$/', '', $valuestring);
                $valuestring = preg_replace('/^,/', '', $valuestring);
                $valuestring = $valuestring.",$tmp_idartlang_neu";

                $db3 = new DB_contenido;
                //********* duplicates entry in DB ****************
                $sql = "INSERT INTO ".$cfg["tab"]["content"]." (idcontent, ".Contenido_Security::escapeDB($keystring, $db3).") VALUES ('".Contenido_Security::toInteger($db3->nextid($cfg["tab"]["content"]))."',
                        ".Contenido_Security::escapeDB($valuestring, $db3).")";
                $db3->query($sql);
            }

            //********* make changes to new entry*************
            $date = date("Y-m-d H:i:s");
            $sql = "SELECT * FROM ".$cfg["tab"]["art_lang"]." AS A, ".$cfg["tab"]["art"]." AS B WHERE A.idart=B.idart AND B.idclient='".Contenido_Security::toInteger($client)."' AND idlang='".Contenido_Security::toInteger($idlang)."'";
            $db2->query($sql);
            while ($db2->next_record()) {
                $a_artlang[] = $db2->f("idartlang");
            }
            foreach ($a_artlang as $val_artlang) {
                $sql = "UPDATE ".$cfg["tab"]["art_lang"]." SET created='".Contenido_Security::escapeDB($date, $db2)."', lastmodified='0', online='0', author='' WHERE idartlang='".Contenido_Security::toInteger($val_artlang)."'";
                $db2->query($sql);
            }
        }

        fakeheader(time());

        /* Duplicate all entries in the 'cat_lang' table  */
        $sql = "SELECT
                    *
                FROM
                    ".$cfg["tab"]["cat_lang"]." AS A,
                    ".$cfg["tab"]["cat"]." AS B
                WHERE
                    A.idcat=B.idcat AND
                    B.idclient='".Contenido_Security::toInteger($client)."' AND
                    idlang='".Contenido_Security::toInteger($firstlang)."'";

        $db->query($sql);

        /* Array storing the category->template allocations fot later reallocation */
        $cfg_cat = array();

        while ($db->next_record()) {

            $nextid = $db2->nextid($cfg["tab"]["cat_lang"]);

            $keystring  = "";
            $valuestring = "";

            /* Store the idartlang->idplcfg allocation for later reallocation */
            $cfg_cat[] = array('idcatlang' => $nextid,
                               'idtplcfg'  => $db->f('idtplcfg'));

            while (list($key, $value) = each($db->Record)) {
                if (is_string($key) && (strpos($key, 'idcatlang') === false) && (strpos($key, 'idlang') !== 0) &&
                   (strpos($key, 'idclient') !== 0) && (strpos($key, 'parentid') !== 0) && (strpos($key, 'preid') !== 0) &&
                   (strpos($key, 'postid') !== 0)) {
                    $keystring   = $keystring.",".$key;
                    $valuestring = $valuestring.",'".addslashes($value)."'";
                }
            }

            $keystring = preg_replace('/,$/', '', $keystring);
            $keystring = preg_replace('/^,/', '', $keystring);
            $keystring = $keystring.",idlang";

            $valuestring = preg_replace('/,$/', '', $valuestring);
            $valuestring = preg_replace('/^,/', '', $valuestring);
            $valuestring = $valuestring.",$idlang";

            //********* duplicates entry in DB ****************
            $sql = "INSERT INTO ".$cfg["tab"]["cat_lang"]." (idcatlang, ".Contenido_Security::escapeDB($keystring, $db2).") VALUES ('".Contenido_Security::toInteger($nextid)."', ".Contenido_Security::escapeDB($valuestring, $db2).")";
            $db2->query($sql);

            //********* make changes to new entry*************
            $sql = "SELECT * FROM ".$cfg["tab"]["cat_lang"]." AS A, ".$cfg["tab"]["cat"]." AS B WHERE A.idcat=B.idcat AND B.idclient='".Contenido_Security::toInteger($client)."' AND idlang='".Contenido_Security::toInteger($idlang)."'";
            $db2->query($sql);

            while ($db2->next_record()) {
                $a_catlang[] = $db2->f("idcatlang");
            }
            foreach ($a_catlang as $val_catlang) {
                $sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET visible='0' WHERE idcatlang='".Contenido_Security::toInteger($val_catlang)."'";
                $db2->query($sql);
            }

        }

        //***********duplicate entries in 'stat'-table*************
        $sql = "SELECT * FROM ".$cfg["tab"]["stat"]." WHERE idclient='".Contenido_Security::toInteger($client)."' AND idlang='".Contenido_Security::toInteger($firstlang)."'";
        $db->query($sql);
        while ($db->next_record()) {
            $keystring  = "";
            $valuestring = "";
            while (list($key, $value) = each($db->Record)) {
                if (is_string($key) && (strpos($key, 'idstat') === false) && (strpos($key, 'idlang') !== 0)) {
                        $keystring   = $keystring.",".$key;
                        $valuestring = $valuestring.",'".addslashes($value)."'";
                }
            }
            $keystring = preg_replace('/,$/', '', $keystring);
            $keystring = preg_replace('/^,/', '', $keystring);
            $keystring = $keystring.",idlang";

            $valuestring = preg_replace('/,$/', '', $valuestring);
            $valuestring = preg_replace('/^,/', '', $valuestring);
            $valuestring = $valuestring.",$idlang";

            $db2 = new DB_contenido;
            //********* duplicates entry in DB ****************
            $sql = "INSERT INTO ".$cfg["tab"]["stat"]." (idstat, ".Contenido_Security::escapeDB($keystring, $db2).") VALUES ('".Contenido_Security::toInteger($db->nextid($cfg["tab"]["stat"]))."', ".Contenido_Security::escapeDB($valuestring, $db2).")";
            $db2->query($sql);

            //********* make changes to new entry*************
            $sql = "UPDATE ".$cfg["tab"]["stat"]." SET visited='0' WHERE idclient='".Contenido_Security::toInteger($client)."' AND idlang='".Contenido_Security::toInteger($idlang)."'";
            $db2->query($sql);
        }

        fakeheader(time());

        //***********duplicate entries in 'tpl_conf'-table*************
        $sql = "SELECT * FROM ".$cfg["tab"]["tpl_conf"];
        $db->query($sql);

        /* Array storing the category->template allocations fot later reallocation */
        $cfg_old_new = array();

        while ($db->next_record()) {
             $nextid = $db2->nextid($cfg["tab"]["tpl_conf"]);

            /* Array storing the category->template allocations fot later reallocation */
            $cfg_old_new[] = array('oldidtplcfg' => $db->f('idtplcfg'),
                                   'newidtplcfg' => $nextid);

            $keystring   = "";
            $valuestring = "";

            while (list($key, $value) = each($db->Record)) {
                if (is_string($key) && (strpos($key, 'idtplcfg') === false) && (strpos($key, 'idlang') !== 0)) {
                    $keystring   = $keystring.",".$key;
                    $valuestring = $valuestring.",'".addslashes($value)."'";
                }
            }

            $keystring = preg_replace('/,$/', '', $keystring);
            $keystring = preg_replace('/^,/', '', $keystring);

            $valuestring = preg_replace('/,$/', '', $valuestring);
            $valuestring = preg_replace('/^,/', '', $valuestring);

            //********* duplicates entry in DB ****************
            $sql = "INSERT INTO ".$cfg["tab"]["tpl_conf"]." (idtplcfg, ".Contenido_Security::escapeDB($keystring, $db2).") VALUES ('".Contenido_Security::toInteger($nextid)."', ".Contenido_Security::escapeDB($valuestring, $db2).")";
            $db2->query($sql);

        }

        /*
            - REMARK - JL - 15.07.03

            Available tpl-cfg allocation arrays are:

            $cfg_cat = array('idcatlang' => n,
                             'idtplcfg'  => n);

            $cfg_art = array('idartlang' => n,
                             'idtplcfg'  => n);

            $cfg_old_new = array('oldidtplcfg' => n,
                                 'newidtplcfg' => n);
        */

        /* Copy the template configuration data */
        if (is_array($cfg_old_new)) {
            foreach ($cfg_old_new as $data) {
                $oldidtplcfg = $data['oldidtplcfg'];
                $newidtplcfg = $data['newidtplcfg'];

                $sql = "SELECT number, container FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg = '".Contenido_Security::toInteger($oldidtplcfg)."' ORDER BY number ASC";
                $db->query($sql);

                $container_data = array();

                while ($db->next_record()) {
                    $container_data[$db->f('number')] = $db->f('container');
                }

                if (is_array($container_data)) {
                    foreach ($container_data as $number => $data) {
                        $nextid = $db->nextid($cfg["tab"]["container_conf"]);
                        $sql = "INSERT INTO ".$cfg["tab"]["container_conf"]. "
                                (idcontainerc, idtplcfg, number, container) VALUES ('".Contenido_Security::toInteger($nextid)."', '".Contenido_Security::toInteger($newidtplcfg)."',
                                '".Contenido_Security::toInteger($number)."', '".Contenido_Security::escapeDB($data, $db)."')";
                        $db->query($sql);
                    }
                }
            }
        }

        /* Reallocate the category -> templatecfg allocations */
        if (is_array($cfg_cat)) {
            foreach($cfg_cat as $data) {
                if ($data['idtplcfg'] != 0) {
                    // Category has a configuration
                    foreach ($cfg_old_new as $arr) {
                        if ($data['idtplcfg'] == $arr['oldidtplcfg']) {
                            $sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET idtplcfg = '".Contenido_Security::toInteger($arr['newidtplcfg'])."' WHERE idcatlang = '".Contenido_Security::toInteger($data['idcatlang'])."'";
                            $db->query($sql);
                        }
                    }
                }
            }
        }

        /* Reallocate the article -> templatecfg allocations */
        if (is_array($cfg_art)) {
            foreach($cfg_art as $data) {
                if ($data['idtplcfg'] != 0) {
                    // Category has a configuration
                    foreach ($cfg_old_new as $arr) {
                        if ($data['idtplcfg'] == $arr['oldidtplcfg']) {
                            // We have a match :)
                            $sql = "UPDATE ".$cfg["tab"]["art_lang"]." SET idtplcfg = '".Contenido_Security::toInteger($arr['newidtplcfg'])."' WHERE idartlang = '".Contenido_Security::toInteger($data['idartlang'])."'";
                            $db->query($sql);
                        }
                    }
                }
            }
        }
    }

    /* Update code */
    conGenerateCodeForAllarts();
}


/**
 * Delete a language
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @author Olaf Niemann <Olaf.Niemann@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
function langDeleteLanguage($idlang, $idclient = "") {
    global $db, $sess, $client, $cfg, $notification;

    $deleteok = 1;

    // Bugfix: New idclient parameter introduced, as Administration -> Languages
    // is used for different clients to delete the language

    // Use global client id, if idclient not specified (former behaviour)
    // Note, that this check also have been added for the action in the database
    // - just to be equal to langNewLanguage
    if (!is_numeric($idclient)) {
        $idclient = $client;
    }

    //************ check if there are still arts online
    $sql = "SELECT * FROM ".$cfg["tab"]["art_lang"]." AS A, ".$cfg["tab"]["art"]." AS B WHERE A.idart=B.idart AND B.idclient='".Contenido_Security::toInteger($idclient)."'
            AND A.idlang='".Contenido_Security::toInteger($idlang)."' AND A.online='1'";
    $db->query($sql);
    if ($db->next_record()) {
        conDeleteArt($db->f("idart"));
    }

    //************ check if there are visible categories
    $sql = "SELECT * FROM ".$cfg["tab"]["cat_lang"]." AS A, ".$cfg["tab"]["cat"]." AS B WHERE A.idcat=B.idcat AND B.idclient='".Contenido_Security::toInteger($idclient)."'
            AND A.idlang='".Contenido_Security::toInteger($idlang)."' AND A.visible='1'";
    $db->query($sql);
    if ($db->next_record()) {
        strDeleteCategory($db->f("idcat"));
    }

    if ($deleteok == 1) {
        //********* check if this is the clients last language to be deleted, if yes delete from art, cat, and cat_art as well *******
        $lastlanguage = 0;
        $sql = "SELECT COUNT(*) FROM ".$cfg["tab"]["clients_lang"]." WHERE idclient='".Contenido_Security::toInteger($idclient)."'";
        $db->query($sql);
        $db->next_record();
        if ($db->f(0) == 1) {
            $lastlanguage = 1;
        }

        //********** delete from 'art_lang'-table *************
        $sql = "SELECT A.idtplcfg AS idtplcfg, idartlang, A.idart FROM ".$cfg["tab"]["art_lang"]." AS A, ".$cfg["tab"]["art"]." AS B WHERE A.idart=B.idart AND B.idclient='".Contenido_Security::toInteger($idclient)."'
                AND idlang!='0' AND idlang='".Contenido_Security::toInteger($idlang)."'";
        $db->query($sql);
        while ($db->next_record()) {
            $a_idartlang[]	= $db->f("idartlang");
            $a_idart[]		= $db->f("idart");
            $a_idtplcfg[]	= $db->f("idtplcfg");
        }
        if (is_array($a_idartlang)) {
            foreach ($a_idartlang as $value) {
                $sql = "DELETE FROM ".$cfg["tab"]["art_lang"]." WHERE idartlang='".Contenido_Security::escapeDB($value, $db)."'";
                $db->query($sql);

                $sql = "DELETE FROM ".$cfg["tab"]["content"]." WHERE idartlang='".Contenido_Security::escapeDB($value, $db)."'";
                $db->query($sql);

                $sql = "DELETE FROM ".$cfg["tab"]["link"]." WHERE idartlang='".Contenido_Security::escapeDB($value, $db)."'";
                $db->query($sql);
            }
        }

        if ($lastlanguage == 1) {
            if (is_array($a_idart)) {
                foreach ($a_idart as $value) {
                    $sql = "DELETE FROM ".$cfg["tab"]["art"]." WHERE idart='".Contenido_Security::escapeDB($value, $db)."'";
                    $db->query($sql);
                    $sql = "DELETE FROM ".$cfg["tab"]["cat_art"]." WHERE idart='".Contenido_Security::escapeDB($value, $db)."'";
                    $db->query($sql);
                }
            }
        }

        //********** delete from 'cat_lang'-table *************
        $sql = "SELECT A.idtplcfg AS idtplcfg, idcatlang, A.idcat FROM ".$cfg["tab"]["cat_lang"]." AS A, ".$cfg["tab"]["cat"]." AS B WHERE A.idcat=B.idcat AND B.idclient='".Contenido_Security::toInteger($idclient)."'
                AND idlang!='0' AND idlang='".Contenido_Security::toInteger($idlang)."'";
        $db->query($sql);
        while ($db->next_record()) {
            $a_idcatlang[]	= $db->f("idcatlang");
            $a_idcat[]		= $db->f("idcat");
            $a_idtplcfg[]	= $db->f("idtplcfg"); // added
        }
        if (is_array($a_idcatlang)) {
            foreach ($a_idcatlang as $value) {
                $sql = "DELETE FROM ".$cfg["tab"]["cat_lang"]." WHERE idcatlang='".Contenido_Security::escapeDB($value, $db)."'";
                $db->query($sql);
            }
        }
        if ($lastlanguage == 1) {
            if (is_array($a_idcat)) {
                foreach ($a_idcat as $value) {
                    $sql = "DELETE FROM ".$cfg["tab"]["cat"]." WHERE idcat='".Contenido_Security::escapeDB($value, $db)."'";
                    $db->query($sql);
                    $sql = "DELETE FROM ".$cfg["tab"]["cat_tree"]." WHERE idcat='".Contenido_Security::escapeDB($value, $db)."'";
                    $db->query($sql);
                }
            }
        }

        //********** delete from 'stat'-table *************
        $sql = "DELETE FROM ".$cfg["tab"]["stat"]." WHERE idlang='".Contenido_Security::toInteger($idlang)."' AND idclient='".Contenido_Security::toInteger($idclient)."'";
        $db->query($sql);

        //********** delete from 'code'-table *************
        $sql = "DELETE FROM ".$cfg["tab"]["code"]." WHERE idlang='".Contenido_Security::toInteger($idlang)."' AND idclient='".Contenido_Security::toInteger($idclient)."'";
        $db->query($sql);

        if (is_array($a_idtplcfg)) {
            foreach ($a_idtplcfg as $tplcfg) {
                if ($tplcfg != 0) {
                    //********** delete from 'tpl_conf'-table *************
                    $sql = "DELETE FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtplcfg='".Contenido_Security::toInteger($tplcfg)."'";
                    $db->query($sql);

                    //********** delete from 'container_conf'-table *************
                    $sql = "DELETE FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg='".Contenido_Security::toInteger($tplcfg)."'";
                    $db->query($sql);
                }
            }
        }

        //*********** delete from 'clients_lang'-table*************
        $sql = "DELETE FROM ".$cfg["tab"]["clients_lang"]." WHERE idclient='".Contenido_Security::toInteger($idclient)."' AND idlang='".Contenido_Security::toInteger($idlang)."'";
        $db->query($sql);

        //*********** delete from 'lang'-table*************
        $sql = "DELETE FROM ".$cfg["tab"]["lang"]." WHERE idlang='".Contenido_Security::toInteger($idlang)."'";
        $db->query($sql);
    } else {
        return $notification->messageBox("error", i18n("Could not delete language"),0);
    }
}


/**
 * Deactivate a language
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @author Olaf Niemann <Olaf.Niemann@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
function langActivateDeactivateLanguage($idlang, $active) {
    global $db;
    global $sess;
    global $client;
    global $cfg;

    $sql = "UPDATE ".$cfg["tab"]["lang"]." SET active='".Contenido_Security::toInteger($active)."' WHERE idlang='".Contenido_Security::toInteger($idlang)."'";
    $db->query($sql);
}


function langGetTextDirection ($idlang, $db = null) {
    global $cfg;

    if ($db == null || !is_object($db)) {
        $db = new DB_Contenido;
    }

    $sql = "SELECT direction FROM ".$cfg["tab"]["lang"] ." WHERE idlang='".Contenido_Security::toInteger($idlang)."'";
    $db->query($sql);

    if ($db->next_record()) {
        $direction = $db->f("direction");

        if ($direction != "ltr" && $direction != "rtl") {
            return "ltr";
        } else {
            return $direction;
        }
    } else {
        return "ltr";
    }
}

?>