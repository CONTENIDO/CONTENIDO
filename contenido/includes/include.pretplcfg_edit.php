<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Functions for tplcfg, use in combination with include.tplcfg_edit_form.php
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.1
 * @author     Olaf Niemann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created  2002
 *   modified 2008-06-27, Dominik Ziegler, add security fix
 *   modified 2009-10-23, Ortwin Pinke, deleted not needed idcat/idart part for better performance
 *   modified 2010-05-20, Murat Purc, removed request check during processing ticket [#CON-307]
 *
 *   $Id$:
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


if ( !isset($idtplcfg) ) {

    $sql = "SELECT
                idtplcfg
            FROM
                ".$cfg["tab"]["tpl"]."
            WHERE
                idtpl = '".Contenido_Security::toInteger($idtpl)."'";

    $db->query($sql);
    $db->next_record();
    
    $idtplcfg = $db->f("idtplcfg");

    if ( $idtplcfg == 0 ) {

        $nextid = $db->nextid($cfg["tab"]["tpl_conf"]);
        $timestamp = time();
        
        $sql = "UPDATE ".$cfg["tab"]["tpl"]." SET idtplcfg = '".Contenido_Security::toInteger($nextid)."' WHERE idtpl = '".Contenido_Security::toInteger($idtpl)."'";
        $db->query($sql);
        
        $sql = "INSERT INTO ".$cfg["tab"]["tpl_conf"]."
                    (idtplcfg, idtpl, status, author, created, lastmodified)
                VALUES
                    ('".Contenido_Security::toInteger($nextid)."', '".Contenido_Security::toInteger($idtpl)."', '', '', '".$timestamp."', '".$timestamp."')";
        
		$db->query($sql);
        $idtplcfg = $nextid;
    }

}

if (isset($idtplcfg)) {
        $sql = "SELECT number FROM ".$cfg["tab"]["container"]." WHERE idtpl='".Contenido_Security::toInteger($idtpl)."'";
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
            $sql = "DELETE FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg='".Contenido_Security::toInteger($idtplcfg)."'";
            $db->query($sql);

            foreach ($varstring as $col=>$val) {
                // insert all containers
                $sql  = "INSERT INTO ".$cfg["tab"]["container_conf"]." (idcontainerc, idtplcfg, number, container) ".
                        "VALUES ('".$db->nextid($cfg["tab"]["container_conf"])."', '".Contenido_Security::toInteger($idtplcfg)."', '".Contenido_Security::toInteger($col)."', '".Contenido_Security::escapeDB($val, $db)."') ";
                $db->query($sql);
            }
        }
}
?>