<?php

/******************************************
* File      :   includes.tplcfg_edit.php
* Project   :   Contenido
* Descr     :   Functions for tplcfg
*               Use in combination with
*               include.tplcfg_edit_form.php
*
* Author    :   Olaf Niemann
* Created   :   2002
* Modified  :   28.03.2003
*
* © four for business AG
*****************************************/
if ( !isset($idtplcfg) ) {

    $sql = "SELECT
                idtplcfg
            FROM
                ".$cfg["tab"]["tpl"]."
            WHERE
                idtpl = '".$idtpl."'";

    $db->query($sql);
    $db->next_record();
    
    $idtplcfg = $db->f("idtplcfg");

    if ( $idtplcfg == 0 ) {

        $nextid = $db->nextid($cfg["tab"]["tpl_conf"]);
        $timestamp = time();
        
        $sql = "UPDATE ".$cfg["tab"]["tpl"]." SET idtplcfg = '".$nextid."' WHERE idtpl = '".$idtpl."'";
        $db->query($sql);
        
        $sql = "INSERT INTO ".$cfg["tab"]["tpl_conf"]."
                    (idtplcfg, idtpl, status, author, created, lastmodified)
                VALUES
                    ('".$nextid."', '".$idtpl."', '', '', '".$timestamp."', '".$timestamp."')";

        $db->query($sql);
        
        $idtplcfg = $nextid;


    }

}

if (isset($idtplcfg)) {

        $sql = "SELECT number FROM ".$cfg["tab"]["container"]." WHERE idtpl='$idtpl'";
        $db->query($sql);
        while ($db->next_record()) {
                $i = $db->f("number");
                $CiCMS_VAR = "C".$i."CMS_VAR";
                if (isset($_POST[$CiCMS_VAR])) {
                    $tmp = $_POST[$CiCMS_VAR];
                } else {
                    unset($tmp);
                }
                if (isset($tmp)) {
                        foreach ($tmp as $key=>$value) {
                                $value = urlencode($value);
                                if (!isset($varstring[$i])) $varstring[$i]="";
                                $varstring[$i] = $varstring[$i].$key."=".$value."&";
                        }
#                        $varstring[$i] = preg_replace("/&$/", "", $varstring[$i]);
                }
        }

        // update/insert in container_conf
        if (isset($varstring) && is_array($varstring)) {
            // delete all containers
            $sql = "DELETE FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg='".$idtplcfg."'";
            $db->query($sql);

            foreach ($varstring as $col=>$val) {
                // insert all containers
                $sql  = "INSERT INTO ".$cfg["tab"]["container_conf"]." (idcontainerc, idtplcfg, number, container) ".
                        "VALUES ('".$db->nextid($cfg["tab"]["container_conf"])."', '".$idtplcfg."', '".$col."', '".$val."') ";
                $db->query($sql);
            }
        }
        

        if (!isset($idart)) {
            $sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET idtplcfg='$idtplcfg' WHERE idcat='$idcat' AND idlang='$lang'";
            $db->query($sql);
        } else {
            $sql = "UPDATE ".$cfg["tab"]["art_lang"]." SET idtplcfg='$idtplcfg' WHERE idart='$idart' AND idlang='$lang'";
            $db->query($sql);
        }


        if ($changetemplate == 1) {
            // set new template
            $sql = "UPDATE ".$cfg["tab"]["tpl_conf"]." SET idtpl='$idtpl' WHERE idtplcfg='$idtplcfg'";
            $db->query($sql);

            // delete old configured containers
            $sql = "DELETE FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg='$idtplcfg'";
            $db->query($sql);
            $changetemplate == 0;
        }


        if ($changetemplate != 1) {
                if (isset($idart)) {
                        conGenerateCode($idcat,$idart,$lang,$client);
                } else {
                        conGenerateCodeForAllartsInCategory($idcat);
                }
                
                $sql = "SELECT name FROM ".$cfg["tab"]["tpl"]." WHERE idtpl='$idtpl' ORDER BY name";
                $db->query($sql);
                $db->next_record();
        }
}
?>
