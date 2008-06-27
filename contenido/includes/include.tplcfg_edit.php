<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Functions for tplcfg, use in combination with nclude.tplcfg_edit_form.php
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.0
 * @author     Olaf Nieman, Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2002
 *   modified 2008-06-27, Dominik Ziegler, add security fix
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

if ( $_REQUEST['cfg'] ) { 
	die('Illegal call');
}

if (!isset($idtpl))
{
	$idtpl = 0;
}
if ( $idtpl != 0 && $idtplcfg != 0 ) {
        $sql = "SELECT number FROM ".$cfg["tab"]["container"]." WHERE idtpl = '".Contenido_Security::toInteger($idtpl)."'";
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
                        
                }
        }

        // update/insert in container_conf
        if (isset($varstring) && is_array($varstring)) {

            // delete all containers
            $sql = "DELETE FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg = '".Contenido_Security::toInteger($idtplcfg)."'";
            $db->query($sql);

            foreach ($varstring as $col=>$val) {
                // insert all containers
                $sql  = "INSERT INTO ".$cfg["tab"]["container_conf"]." (idcontainerc, idtplcfg, number, container) ".
                        "VALUES ('".$db->nextid($cfg["tab"]["container_conf"])."', '".Contenido_Security::toInteger($idtplcfg)."', '".Contenido_Security::toInteger($col)."', '".Contenido_Security::escapeDB($val, $db)."') ";
                        
                $db->query($sql);
            }
        }
        

        if ( $idart ) { 
			
			//echo "art: idart: $idart, idcat: $idcat";        	
            $sql = "UPDATE ".$cfg["tab"]["art_lang"]." SET idtplcfg = '".Contenido_Security::toInteger($idtplcfg)."' WHERE idart='$idart' AND idlang='".Contenido_Security::toInteger($lang)."'";
            $db->query($sql);
			    
        } else {
        	
        	//echo "cat: idart: $idart, idcat: $idcat";        	        	
        	$sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET idtplcfg = '".Contenido_Security::toInteger($idtplcfg)."' WHERE idcat='$idcat' AND idlang='".Contenido_Security::toInteger($lang)."'";
            $db->query($sql);
                        
        }


        if ($changetemplate == 1 && $idtplcfg != 0) {

            /* update template conf */
            $sql = "UPDATE ".$cfg["tab"]["tpl_conf"]." SET idtpl='".Contenido_Security::toInteger($idtpl)."' WHERE idtplcfg='".Contenido_Security::toInteger($idtplcfg)."'";
            $db->query($sql);

            // delete old configured containers
            $sql = "DELETE FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg='".Contenido_Security::toInteger($idtplcfg)."'";
            $db->query($sql);
            $changetemplate = 0;
            
        } else {

            //

        }


        if ($changetemplate != 1) {

            if ( isset($idart) && 0 != $idart ) {
                conGenerateCode($idcat, $idart, $lang, $client);
                //backToMainArea($send);
                
            } else {
                conGenerateCodeForAllartsInCategory($idcat);
                if ($back == 'true') {
                    backToMainArea($send);
                }
            }
        }
        
} elseif ( $idtpl == 0 ) {
	
	/* template deselected */
	
	if (isset($idtplcfg) && $idtplcfg != 0 ) {
		
    	$sql = "DELETE FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtplcfg = '".Contenido_Security::toInteger($idtplcfg)."'";
    	$db->query($sql);
    	
    	$sql = "DELETE FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg = '".Contenido_Security::toInteger($idtplcfg)."'";
    	$db->query($sql);    
	
	}

    $idtplcfg = 0;
	if (!isset($changetemplate))
	{
		$changetemplate = 0;	
	}

    if ( $idcat != 0 && $changetemplate == 1 && !$idart ) {
				
		/* Category */
    	$sql = "SELECT idtplcfg FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat = '".Contenido_Security::toInteger($idcat)."' AND idlang = '".Contenido_Security::toInteger($lang)."'";
    	$db->query($sql);
    	$db->next_record();
    	$tmp_idtplcfg = $db->f("idtplcfg");
    	
    	$sql = "DELETE FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtplcfg = '".Contenido_Security::toInteger($tmp_idtplcfg)."'";
    	$db->query($sql);
    	
    	$sql = "DELETE FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg = '".Contenido_Security::toInteger($tmp_idtplcfg)."'";
    	$db->query($sql); 
		
        $sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET idtplcfg = 0 WHERE idcat = '".Contenido_Security::toInteger($idcat)."' AND idlang = '".Contenido_Security::toInteger($lang)."'";
        $db->query($sql);
        
        conGenerateCodeForAllartsInCategory($idcat);
        backToMainArea($send);

    } elseif ( isset($idart) && $idart != 0 && $changetemplate == 1 ) {
		
		/* Article */
        $sql = "SELECT idtplcfg FROM ".$cfg["tab"]["art_lang"]." WHERE idart = '".Contenido_Security::toInteger($idart)."' AND idlang = '".Contenido_Security::toInteger($lang)."'";
    	$db->query($sql);
    	$db->next_record();
    	$tmp_idtplcfg = $db->f("idtplcfg");
    	
    	$sql = "DELETE FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtplcfg = '".Contenido_Security::toInteger($tmp_idtplcfg)."'";
    	$db->query($sql);
    	
    	$sql = "DELETE FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg = '".Contenido_Security::toInteger($tmp_idtplcfg)."'";
    	$db->query($sql); 
		
        $sql = "UPDATE ".$cfg["tab"]["art_lang"]." SET idtplcfg = 0 WHERE idart = '".Contenido_Security::toInteger($idart)."' AND idlang = '".Contenido_Security::toInteger($lang)."'";
        $db->query($sql);

        conGenerateCodeForAllartsInCategory($idcat);
        //backToMainArea($send);

    }
    
} else {	
			
	if ( $changetemplate == 1 )
    {		
        if (!$idart)
        {
            $sql = "SELECT idtplcfg FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat = '".Contenido_Security::toInteger($idcat)."' AND idlang = '".Contenido_Security::toInteger($lang)."'";		
            $db->query($sql);
            $db->next_record();		
            $tmp_idtplcfg = $db->f("idtplcfg");

            $sql = "DELETE FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtplcfg = '".Contenido_Security::toInteger($tmp_idtplcfg)."'";
            $db->query($sql);

            $sql = "DELETE FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg = '".Contenido_Security::toInteger($tmp_idtplcfg)."'";
            $db->query($sql);				
        }

        else
        {
            $sql = "SELECT idtplcfg FROM ".$cfg["tab"]["art_lang"]." WHERE idart = '".Contenido_Security::toInteger($idart)."' AND idlang = '".Contenido_Security::toInteger($lang)."'";		
            $db->query($sql);
            $db->next_record();		
            $tmp_idtplcfg = $db->f("idtplcfg");

            $sql = "DELETE FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtplcfg = '".Contenido_Security::toInteger($tmp_idtplcfg)."'";
            $db->query($sql);

            $sql = "DELETE FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg = '".Contenido_Security::toInteger($tmp_idtplcfg)."'";
            $db->query($sql);				
        }				
        
			
	}
   	conGenerateCodeForAllartsInCategory($idcat);
}
?>
