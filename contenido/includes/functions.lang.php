<?php

/*****************************************
*
* $Id: functions.lang.php,v 1.25 2007/07/19 20:27:58 bjoern.behrens Exp $
*
* File      :   $RCSfile: functions.lang.php,v $
* Project   :
* Descr     :
*
* Author    :   Jan Lengowski
* Modified  :   $Date: 2007/07/19 20:27:58 $
*
* © four for business AG, www.4fb.de
******************************************/
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
               name = '".$langname."',
               encoding = '".$encoding."',
               active = '".$active."',
			   lastmodified = '".$modified."',
			   direction = '".$direction."'
           WHERE
               idlang = ".$idlang;

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
function langNewLanguage($name, $client) 
{
  global $db, $sess, $cfg, $cfgClient, $notification, $auth;
  $new_idlang = $db->nextid($cfg["tab"]["lang"]);
  $author = $auth->auth["uid"];
  $created = date("Y-m-d H:i:s");
  $modified = $created;
  
  // Add new language to database		
  $sql = "INSERT INTO ".$cfg["tab"]["lang"]." (idlang, name, active, encoding, author, created, lastmodified) VALUES ('$new_idlang', '$name', '0', 'iso-8859-1', '$author', '$created', '$modified')";
  $db->query($sql);
  $sql = "INSERT INTO ".$cfg["tab"]["clients_lang"]." (idclientslang, idclient, idlang) VALUES ('".$db->nextid($cfg["tab"]["clients_lang"])."', '$client','$new_idlang')";
  $db->query($sql);
  
  // update language dropdown in header
  $newOption = '<script>';
  $newOption .= 'var newLang = new Option("'.$name.' ('.$new_idlang.')", "'.$new_idlang.'", false, false);';
  $newOption .= 'var langList = top.header.document.getElementById("cLanguageSelect");';
  $newOption .= 'langList.options[langList.options.length] = newLang;';
  $newOption .= '</script>';
  echo $newOption;
  
  // Ab hyr seynd Drachen
  $destPath = $cfgClient[$client]["path"]["frontend"];
  
  if (file_exists($destPath) && file_exists($destPath."config.php"))
  {
    $res = fopen($destPath."config.php","rb+");
    $res2 = fopen($destPath."config.php.new", "ab+");
  
    if ($res && $res2)
    {
      while (!feof($res))
      {
        $buffer = fgets($res, 4096);
        $outbuf = str_replace("!LANG!", $new_idlang, $buffer);
        fwrite($res2, $outbuf);
      }
  
      fclose($res);
      fclose($res2);
  
      if (file_exists($destPath."config.php"))
      {
        unlink($destPath."config.php");
      }
    
      rename($destPath."config.php.new", $destPath."config.php");
    }
  }
  else 
  {
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

        $sql = "UPDATE ".$cfg["tab"]["lang"]." SET name='$name' WHERE idlang='$idlang'";
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

        $sql = "SELECT * FROM ".$cfg["tab"]["clients_lang"]." WHERE idclient='$client' ORDER BY idlang ASC";
        
        $db->query($sql);
        if ($db->next_record()) {     //***********if there is already a language copy from it .....
                $firstlang = $db->f("idlang");

                //***********duplicate entries in 'art_lang'-table*************
                $sql = "SELECT * FROM ".$cfg["tab"]["art_lang"]." AS A, ".$cfg["tab"]["art"]." AS B WHERE A.idart=B.idart AND B.idclient='$client' AND idlang!='0' AND idlang='$firstlang'";
                $db->query($sql);
                
                /* Array storing the article->templatecfg
                   allocations for later reallocation */
                $cfg_art = array();
                
                while ($db->next_record())
                {
                        /* Store the idartlang->idplcfg
                           allocation for later reallocation */
                        $cfg_art[] = array('idartlang' => $db->f('idartlang'),
                                           'idtplcfg'  => $db->f('idtplcfg'));

                        $keystring  = "";
                        $valuestring = "";
                        
                        while (list($key, $value) = each($db->Record))
                        {
                                if (is_string($key) && !ereg("idartlang",$key) && !ereg("^idlang",$key) && !ereg("^idclient",$key)) {
                                        $keystring   = $keystring.",".$key;
                                        $valuestring = $valuestring.",'".addslashes($value)."'";
                                } elseif (is_string($key) && ereg("idartlang",$key)) {
                                        $tmp_idartlang_alt = $value;
                                }
                        }

                        #$sql = "SELECT MAX(idartlang)+1 FROM ".$cfg["tab"]["art_lang"];
                        #$db2->query($sql);
                        #$db2->next_record();
                        #$tmp_idartlang_neu = $db2->f(0);
                        $tmp_idartlang_neu = $db2->nextid($cfg["tab"]["art_lang"]);

                        $keystring = $keystring.",idartlang";
                        $keystring = ereg_replace(",$","",$keystring);
                        $keystring = ereg_replace("^,","",$keystring);
                        $keystring = $keystring.",idlang";

                        $valuestring = $valuestring.",$tmp_idartlang_neu";
                        $valuestring = ereg_replace(",$","",$valuestring);
                        $valuestring = ereg_replace("^,","",$valuestring);
                        $valuestring = $valuestring.",$idlang";

                        //********* duplicates entry in DB ****************
                        $sql = "INSERT INTO ".$cfg["tab"]["art_lang"]." (".$keystring.") VALUES (".$valuestring.")";
                        $db2->query($sql);


                                //***********duplicate entries in 'cat_lang'-table*************
                                $sql = "SELECT * FROM ".$cfg["tab"]["content"]." WHERE idartlang='$tmp_idartlang_alt'";
                                $db2->query($sql);
                                
                                while ($db2->next_record()) {
                                        $keystring  = "";
                                        $valuestring = "";
                                        while (list($key, $value) = each($db2->Record))
                                        {
                                                if (is_string($key) && !ereg("idcontent",$key) && !ereg("^idartlang",$key)) {
                                                        $keystring   = $keystring.",".$key;
                                                        $valuestring = $valuestring.",'".addslashes($value)."'";
                                                }
                                        }
                                        $keystring = ereg_replace(",$","",$keystring);
                                        $keystring = ereg_replace("^,","",$keystring);
                                        $keystring = $keystring.",idartlang";

                                        $valuestring = ereg_replace(",$","",$valuestring);
                                        $valuestring = ereg_replace("^,","",$valuestring);
                                        $valuestring = $valuestring.",$tmp_idartlang_neu";

                                        $db3 = new DB_contenido;
                                        //********* duplicates entry in DB ****************
                                        $sql = "INSERT INTO ".$cfg["tab"]["content"]." (idcontent, ".$keystring.") VALUES ('".$db3->nextid($cfg["tab"]["content"])."', ".$valuestring.")";
                                        $db3->query($sql);

                                }


                        //********* make changes to new entry*************
                        $date = date("Y-m-d H:i:s");
                        $sql = "SELECT * FROM ".$cfg["tab"]["art_lang"]." AS A, ".$cfg["tab"]["art"]." AS B WHERE A.idart=B.idart AND B.idclient='$client' AND idlang='$idlang'";
                        $db2->query($sql);
                        while ($db2->next_record()) {
                                $a_artlang[] = $db2->f("idartlang");
                        }
                        foreach ($a_artlang as $val_artlang) {
                                $sql = "UPDATE ".$cfg["tab"]["art_lang"]." SET created='$date', lastmodified='0', online='0', author='' WHERE idartlang='$val_artlang'";
                                $db2->query($sql);
                        }
                }

                fakeheader(time());

                /* Duplicate all entries in
                   the 'cat_lang' table  */
                $sql = "SELECT
                            *
                        FROM
                            ".$cfg["tab"]["cat_lang"]." AS A,
                            ".$cfg["tab"]["cat"]." AS B
                        WHERE
                            A.idcat=B.idcat AND
                            B.idclient='$client' AND
                            idlang='$firstlang'";
                            
                $db->query($sql);
                
                /* Array storing the category->template
                   allocations fot later reallocation */
                $cfg_cat = array();
                
                while ( $db->next_record() ) {

                        $nextid = $db2->nextid($cfg["tab"]["cat_lang"]);

                        $keystring  = "";
                        $valuestring = "";

                        /* Store the idartlang->idplcfg
                           allocation for later reallocation */
                        $cfg_cat[] = array('idcatlang' => $nextid,
                                           'idtplcfg'  => $db->f('idtplcfg'));
                        
                        while (list($key, $value) = each($db->Record))
                        {
                                if (is_string($key) && !ereg("idcatlang",$key) && !ereg("^idlang",$key) && !ereg("^idclient",$key) && !ereg("^parentid",$key) && !ereg("^preid",$key) && !ereg("^postid",$key)) {
                                        $keystring   = $keystring.",".$key;
                                        $valuestring = $valuestring.",'".addslashes($value)."'";
                                }
                        }
                        
                        $keystring = ereg_replace(",$","",$keystring);
                        $keystring = ereg_replace("^,","",$keystring);
                        $keystring = $keystring.",idlang";

                        $valuestring = ereg_replace(",$","",$valuestring);
                        $valuestring = ereg_replace("^,","",$valuestring);
                        $valuestring = $valuestring.",$idlang";
                        
                        //********* duplicates entry in DB ****************
                        $sql = "INSERT INTO ".$cfg["tab"]["cat_lang"]." (idcatlang, ".$keystring.") VALUES ('".$nextid."', ".$valuestring.")";
                        $db2->query($sql);

                        //********* make changes to new entry*************
                        $sql = "SELECT * FROM ".$cfg["tab"]["cat_lang"]." AS A, ".$cfg["tab"]["cat"]." AS B WHERE A.idcat=B.idcat AND B.idclient='$client' AND idlang='$idlang'";
                        $db2->query($sql);
                        
                        while ($db2->next_record()) {
                                $a_catlang[] = $db2->f("idcatlang");
                        }
                        foreach ($a_catlang as $val_catlang) {
                                        $sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET visible='0' WHERE idcatlang='$val_catlang'";
                                $db2->query($sql);
                        }
                        
                }

                //***********duplicate entries in 'stat'-table*************
                $sql = "SELECT * FROM ".$cfg["tab"]["stat"]." WHERE idclient='$client' AND idlang='$firstlang'";
                $db->query($sql);
                while ($db->next_record()) {
                        $keystring  = "";
                        $valuestring = "";
                        while (list($key, $value) = each($db->Record))
                        {
                                if (is_string($key) && !ereg("idstat",$key) && !ereg("^idlang",$key)) {
                                        $keystring   = $keystring.",".$key;
                                        $valuestring = $valuestring.",'".addslashes($value)."'";
                                }
                        }
                        $keystring = ereg_replace(",$","",$keystring);
                        $keystring = ereg_replace("^,","",$keystring);
                        $keystring = $keystring.",idlang";

                        $valuestring = ereg_replace(",$","",$valuestring);
                        $valuestring = ereg_replace("^,","",$valuestring);
                        $valuestring = $valuestring.",$idlang";

                        $db2 = new DB_contenido;
                        //********* duplicates entry in DB ****************
                        $sql = "INSERT INTO ".$cfg["tab"]["stat"]." (idstat, ".$keystring.") VALUES ('".$db->nextid($cfg["tab"]["stat"])."', ".$valuestring.")";
                        $db2->query($sql);

                        //********* make changes to new entry*************
                        $sql = "UPDATE ".$cfg["tab"]["stat"]." SET visited='0' WHERE idclient='$client' AND idlang='$idlang'";
                        $db2->query($sql);
                }
               
                fakeheader(time());
                
                
                //***********duplicate entries in 'tpl_conf'-table*************
                $sql = "SELECT * FROM ".$cfg["tab"]["tpl_conf"];
                $db->query($sql);

                /* Array storing the category->template
                   allocations fot later reallocation */
                $cfg_old_new = array();

                while ($db->next_record())
                {
                     $nextid = $db2->nextid($cfg["tab"]["tpl_conf"]);

                    /* Array storing the category->template
                       allocations fot later reallocation */
                    $cfg_old_new[] = array('oldidtplcfg' => $db->f('idtplcfg'),
                                           'newidtplcfg' => $nextid);

                    $keystring   = "";
                    $valuestring = "";

                    while (list($key, $value) = each($db->Record))
                    {
                        if (is_string($key) && !ereg("idtplcfg",$key) && !ereg("^idlang",$key))
                        {
                            $keystring   = $keystring.",".$key;
                            $valuestring = $valuestring.",'".addslashes($value)."'";
                        }
                    }

                    $keystring = ereg_replace(",$","",$keystring);
                    $keystring = ereg_replace("^,","",$keystring);

                    $valuestring = ereg_replace(",$","",$valuestring);
                    $valuestring = ereg_replace("^,","",$valuestring);

                    //********* duplicates entry in DB ****************
                    $sql = "INSERT INTO ".$cfg["tab"]["tpl_conf"]." (idtplcfg, ".$keystring.") VALUES ('".$nextid."', ".$valuestring.")";
                    $db2->query($sql);

                    //********* make changes to new entry*************
                    // no changes here
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
                if (is_array($cfg_old_new))
                {
                    foreach ($cfg_old_new as $data)
                    { 
                        $oldidtplcfg = $data['oldidtplcfg'];
                        $newidtplcfg = $data['newidtplcfg'];
                        
                        $sql = "SELECT number, container FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg = '$oldidtplcfg' ORDER BY number ASC";
                        $db->query($sql);
                        
                        $container_data = array();
                        
                        while ($db->next_record())
                        {
                            $container_data[$db->f('number')] = $db->f('container');
                        }
                        
                        if (is_array($container_data))
                        {
                            foreach ($container_data as $number => $data)
                            {
                                $nextid = $db->nextid($cfg["tab"]["container_conf"]);
                                $sql = "INSERT INTO ".$cfg["tab"]["container_conf"]. "
                                        (idcontainerc, idtplcfg, number, container) VALUES ('$nextid', '$newidtplcfg', '$number', '$data')";
                                $db->query($sql);
                            }
                        }
                    }
                }
                
                /* Reallocate the category -> templatecfg
                   allocations */
                if (is_array($cfg_cat))
                {
                    foreach($cfg_cat as $data)
                    {
                        if ($data['idtplcfg'] != 0)
                        { // Category has a configuration
                            foreach ($cfg_old_new as $arr)
                            {   
                                if ($data['idtplcfg'] == $arr['oldidtplcfg'])
                                {
                                    $sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET idtplcfg = '".$arr['newidtplcfg']."' WHERE idcatlang = '".$data['idcatlang']."'";
                                    $db->query($sql);
                                    #echo '<span style="font-family:arial;font-size:9px">'.$sql.'</span><br>';
                                }
                            }
                        }
                    }
                }
                
                /* Reallocate the article -> templatecfg
                   allocations */
                if (is_array($cfg_art))
                {
                    foreach($cfg_art as $data)
                    {
                        if ($data['idtplcfg'] != 0)
                        { // Category has a configuration
                            foreach ($cfg_old_new as $arr)
                            {
                                if ($data['idtplcfg'] == $arr['oldidtplcfg'])
                                { // We have a match :)
                                    $sql = "UPDATE ".$cfg["tab"]["art_lang"]." SET idtplcfg = '".$arr['newidtplcfg']."' WHERE idartlang = '".$data['idartlang']."'";
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
        $sql = "SELECT * FROM ".$cfg["tab"]["art_lang"]." AS A, ".$cfg["tab"]["art"]." AS B WHERE A.idart=B.idart AND B.idclient='$idclient' AND A.idlang='$idlang' AND A.online='1'";
        $db->query($sql);
        if ($db->next_record())
        {
            conDeleteArt($db->f("idart"));
        }
        
        //************ check if there are visible categories
        $sql = "SELECT * FROM ".$cfg["tab"]["cat_lang"]." AS A, ".$cfg["tab"]["cat"]." AS B WHERE A.idcat=B.idcat AND B.idclient='$idclient' AND A.idlang='$idlang' AND A.visible='1'";
        $db->query($sql);
        if ($db->next_record()) {
            strDeleteCategory($db->f("idcat"));
        }
	
        if ($deleteok == 1) {
                //********* check if this is the clients last language to be deleted, if yes delete from art, cat, and cat_art as well *******
                $last_language = 0;
                $sql = "SELECT COUNT(*) FROM ".$cfg["tab"]["clients_lang"]." WHERE idclient='$idclient'";
                $db->query($sql);
                $db->next_record();
                if ($db->f(0) == 1) {
                    $lastlanguage = 1;
                }

                //********** delete from 'art_lang'-table *************
                $sql = "SELECT A.idtplcfg AS idtplcfg, idartlang, A.idart FROM ".$cfg["tab"]["art_lang"]." AS A, ".$cfg["tab"]["art"]." AS B WHERE A.idart=B.idart AND B.idclient='$idclient' AND idlang!='0' AND idlang='$idlang'";
                $db->query($sql);
                while ($db->next_record()) {
                        $a_idartlang[]	= $db->f("idartlang");
                        $a_idart[]		= $db->f("idart");
                        $a_idtplcfg[]	= $db->f("idtplcfg");
                }
                if (is_array($a_idartlang)) {
                        foreach ($a_idartlang as $value) {
                                $sql = "DELETE FROM ".$cfg["tab"]["art_lang"]." WHERE idartlang='$value'";
                                $db->query($sql);

                                $sql = "DELETE FROM ".$cfg["tab"]["content"]." WHERE idartlang='$value'";
                                $db->query($sql);
                                
                                $sql = "DELETE FROM ".$cfg["tab"]["link"]." WHERE idartlang='$value'";
                                $db->query($sql);
                        }
                }
                
                if ($lastlanguage == 1) {
                        if (is_array($a_idart)) {
                                foreach ($a_idart as $value) {
                                        $sql = "DELETE FROM ".$cfg["tab"]["art"]." WHERE idart='$value'";
                                        $db->query($sql);
                                        $sql = "DELETE FROM ".$cfg["tab"]["cat_art"]." WHERE idart='$value'";
                                        $db->query($sql);
                                }
                        }
                }

				//********** delete from 'cat_lang'-table *************
                $sql = "SELECT A.idtplcfg AS idtplcfg, idcatlang, A.idcat FROM ".$cfg["tab"]["cat_lang"]." AS A, ".$cfg["tab"]["cat"]." AS B WHERE A.idcat=B.idcat AND B.idclient='$idclient' AND idlang!='0' AND idlang='$idlang'";
                $db->query($sql);
                while ($db->next_record()) {
                        $a_idcatlang[]	= $db->f("idcatlang");
                        $a_idcat[]		= $db->f("idcat");
                        $a_idtplcfg[]	= $db->f("idtplcfg"); // added
                }
                if (is_array($a_idcatlang)) {
                        foreach ($a_idcatlang as $value) {
                                $sql = "DELETE FROM ".$cfg["tab"]["cat_lang"]." WHERE idcatlang='$value'";
                                $db->query($sql);
                        }
                }
                if ($lastlanguage == 1) {
                        if (is_array($a_idcat)) {
                                foreach ($a_idcat as $value) {
                                        $sql = "DELETE FROM ".$cfg["tab"]["cat"]." WHERE idcat='$value'";
                                        $db->query($sql);
                                        $sql = "DELETE FROM ".$cfg["tab"]["cat_tree"]." WHERE idcat='$value'";
                                        $db->query($sql);
                                }
                        }
                }

                //********** delete from 'stat'-table *************
                $sql = "DELETE FROM ".$cfg["tab"]["stat"]." WHERE idlang='$idlang' AND idclient='$idclient'";
                $db->query($sql);


                //********** delete from 'code'-table *************
                $sql = "DELETE FROM ".$cfg["tab"]["code"]." WHERE idlang='$idlang' AND idclient='$idclient'";
                $db->query($sql);

				if (is_array($a_idtplcfg))
				{
					foreach ($a_idtplcfg as $tplcfg)
					{
						if ($tplcfg != 0)
						{
                            //********** delete from 'tpl_conf'-table *************
                            $sql = "DELETE FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtplcfg='".$tplcfg."'";
                            $db->query($sql);
    				
                            //********** delete from 'container_conf'-table *************
                            $sql = "DELETE FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg='".$tplcfg."'";
                            $db->query($sql);
                        }
					}
                }

                //*********** delete from 'clients_lang'-table*************
                $sql = "DELETE FROM ".$cfg["tab"]["clients_lang"]." WHERE idclient='$idclient' AND idlang='$idlang'";
                $db->query($sql);

                //*********** delete from 'lang'-table*************
                $sql = "DELETE FROM ".$cfg["tab"]["lang"]." WHERE idlang='$idlang'";
                $db->query($sql);                
        } else {
            return $notification->messageBox("error", i18n("Could not delete language"),0);
        }
        
        // finally delete from dropdown in header
        $newOption = '<script>';
        $newOption .= 'var langList = top.header.document.getElementById("cLanguageSelect");';
        $newOption .= 'var thepos="";';
        $newOption .= 'for(var i=0;i<langList.length;i++)';
        $newOption .= '{';
        $newOption .= 'if(langList.options[i].value == '.$idlang.')';
        $newOption .= ' {';
        $newOption .= ' thepos = langList.options[i].index;';
        $newOption .= ' }';
        $newOption .= '}';
        $newOption .= 'langList.remove(thepos);';
        $newOption .= '</script>';
        echo $newOption;
        
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

        $sql = "UPDATE ".$cfg["tab"]["lang"]." SET active='$active' WHERE idlang='$idlang'";
        $db->query($sql);

}

function langGetTextDirection ($idlang)
{
	global $cfg;

	$db = new DB_Contenido;
	
	$sql = "SELECT direction FROM ".$cfg["tab"]["lang"] ." WHERE idlang='$idlang'";
	$db->query($sql);
	
	if ($db->next_record())
	{
		$direction = $db->f("direction");
		
		if ($direction != "ltr" && $direction != "rtl")
		{
			return "ltr";	
		} else {
			return $direction;	
		}
	} else {
		return "ltr";	
	}
}

?>