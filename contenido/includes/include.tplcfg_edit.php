<?php

/******************************************
* File      :   includes.tplcfg_edit.php
* Project   :   Contenido
* Descr     :   Functions for tplcfg
*               Use in combination with
*               include.tplcfg_edit_form.php
*
* Author    :   Olaf Nieman, Jan Lengowski
* Created   :   2002
* Modified  :   17.06.2003
*
* © four for business AG
*****************************************/

if (!isset($idtpl))
{
	$idtpl = 0;
}
if ( $idtpl != 0 && $idtplcfg != 0 ) {

        #echo "MATCHED!  IDTPL: $idtpl - IDTPLCFG: $idtplcfg<br><br>";
		#echo "FIRST";

        $sql = "SELECT number FROM ".$cfg["tab"]["container"]." WHERE idtpl = '".$idtpl."'";
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
            $sql = "DELETE FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg = '".$idtplcfg."'";
            $db->query($sql);

            foreach ($varstring as $col=>$val) {
                // insert all containers
                $sql  = "INSERT INTO ".$cfg["tab"]["container_conf"]." (idcontainerc, idtplcfg, number, container) ".
                        "VALUES ('".$db->nextid($cfg["tab"]["container_conf"])."', '".$idtplcfg."', '".$col."', '".$val."') ";
                        
                $db->query($sql);
            }
        }
        

        if ( $idart ) { 
			
			//echo "art: idart: $idart, idcat: $idcat";        	
            $sql = "UPDATE ".$cfg["tab"]["art_lang"]." SET idtplcfg = '$idtplcfg' WHERE idart='$idart' AND idlang='$lang'";
            $db->query($sql);
			    
        } else {
        	
        	//echo "cat: idart: $idart, idcat: $idcat";        	        	
        	$sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET idtplcfg = '$idtplcfg' WHERE idcat='$idcat' AND idlang='$lang'";
            $db->query($sql);
                        
        }


        if ($changetemplate == 1 && $idtplcfg != 0) {

            /* update template conf */
            $sql = "UPDATE ".$cfg["tab"]["tpl_conf"]." SET idtpl='".$idtpl."' WHERE idtplcfg='".$idtplcfg."'";
            $db->query($sql);

            // delete old configured containers
            $sql = "DELETE FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg='".$idtplcfg."'";
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
		
    	$sql = "DELETE FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtplcfg = $idtplcfg";
    	$db->query($sql);
    	
    	$sql = "DELETE FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg = $idtplcfg";
    	$db->query($sql);    
	
	}
    
    #echo "DELETED entries for idtplcfg: $idtplcfg<br><br>";
    #echo "NOT MATCHED!  IDTPL: $idtpl - IDTPLCFG: $idtplcfg<br><br>";

    $idtplcfg = 0;
	if (!isset($changetemplate))
	{
		$changetemplate = 0;	
	}

    if ( $idcat != 0 && $changetemplate == 1 && !$idart ) {
				
		/* Category */
    	$sql = "SELECT idtplcfg FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat = $idcat AND idlang = $lang";
    	$db->query($sql);
    	$db->next_record();
    	$tmp_idtplcfg = $db->f("idtplcfg");
    	
    	$sql = "DELETE FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtplcfg = '$tmp_idtplcfg'";
    	$db->query($sql);
    	
    	$sql = "DELETE FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg = '$tmp_idtplcfg'";
    	$db->query($sql); 
		
        $sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET idtplcfg = 0 WHERE idcat = '".$idcat."' AND idlang = '".$lang."'";
        $db->query($sql);
        
        conGenerateCodeForAllartsInCategory($idcat);
        backToMainArea($send);

    } elseif ( isset($idart) && $idart != 0 && $changetemplate == 1 ) {
		
		/* Article */
        $sql = "SELECT idtplcfg FROM ".$cfg["tab"]["art_lang"]." WHERE idart = $idart AND idlang = $lang";
    	$db->query($sql);
    	$db->next_record();
    	$tmp_idtplcfg = $db->f("idtplcfg");
    	
    	$sql = "DELETE FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtplcfg = '$tmp_idtplcfg'";
    	$db->query($sql);
    	
    	$sql = "DELETE FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg = '$tmp_idtplcfg'";
    	$db->query($sql); 
		
        $sql = "UPDATE ".$cfg["tab"]["art_lang"]." SET idtplcfg = 0 WHERE idart = '".$idart."' AND idlang = '".$lang."'";
        $db->query($sql);

        conGenerateCodeForAllartsInCategory($idcat);
        //backToMainArea($send);

    }
    
} else {	
			
	if ( $changetemplate == 1 )
    {		
        /* JL: Template changed, new configuration will be created.
           Delete the old configuration and container-cfgs */

        if (!$idart)
        {
            $sql = "SELECT idtplcfg FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat = $idcat AND idlang = $lang";		
            $db->query($sql);
            $db->next_record();		
            $tmp_idtplcfg = $db->f("idtplcfg");

            $sql = "DELETE FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtplcfg = '$tmp_idtplcfg'";
            $db->query($sql);

            $sql = "DELETE FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg = '$tmp_idtplcfg'";
            $db->query($sql);				
        }

        else
        {
            $sql = "SELECT idtplcfg FROM ".$cfg["tab"]["art_lang"]." WHERE idart = $idart AND idlang = $lang";		
            $db->query($sql);
            $db->next_record();		
            $tmp_idtplcfg = $db->f("idtplcfg");

            $sql = "DELETE FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtplcfg = '$tmp_idtplcfg'";
            $db->query($sql);

            $sql = "DELETE FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg = '$tmp_idtplcfg'";
            $db->query($sql);				
        }				
        
			
	}
		
   	conGenerateCodeForAllartsInCategory($idcat);

}

?>
