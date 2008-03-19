<?php

/******************************************
* File      :   functions.tpl.php
* Project   :   Contenido
* Descr     :   Defines the Template
*               related functions
*
* Author    :   Olaf Niemann
* Created   :   21.01.2003
* Modified  :   21.01.2003
*
* © four for business AG
******************************************/
cInclude ("includes", "functions.con.php");

/**
 * Edit or create a new Template
 *
 * @author Olaf Niemann <Olaf.Niemann@4fb.de>
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
function tplEditTemplate($changelayout, $idtpl, $name, $description, $idlay, $c, $default)
{

        global $db;
        global $sess;
        global $auth;
        global $client;
        global $cfg;
        global $area_tree;
        global $perm;
        
        $db2= new DB_Contenido;

        $date = date("YmdHis");
        $author = "".$auth->auth["uname"]."";

        //******** entry in 'tpl'-table ***************
        set_magic_quotes_gpc($name);
        set_magic_quotes_gpc($description);

        if (!$idtpl) {

            $idtpl = $db->nextid($cfg["tab"]["tpl"]);
            $idtplcfg = $db->nextid($cfg["tab"]["tpl_conf"]);

            /* Insert new entry in the
               Template Conf table  */
            $sql = "INSERT INTO ".$cfg["tab"]["tpl_conf"]."
                    (idtplcfg, idtpl, author) VALUES
                   ('".$idtplcfg."', '".$idtpl."', '".$auth->auth["uname"]."')";

            $db->query($sql);

            /* Insert new entry in the
               Template table  */
            $sql = "INSERT INTO ".$cfg["tab"]["tpl"]."
                    (idtpl, idtplcfg, name, description, deletable, idlay, idclient, author, created, lastmodified) VALUES
                    ('".$idtpl."', '".$idtplcfg."', '".$name."', '".$description."', '1', '".$idlay."', '".$client."', '".$author."', '".$date."', '".$date."')";

            $db->query($sql);

            // set correct rights for element
            cInclude ("includes", "functions.rights.php");
            createRightsForElement("tpl", $idtpl);

        } else {

            /* Update */
            $sql = "UPDATE ".$cfg["tab"]["tpl"]." SET name='$name', description='$description', idlay='$idlay', author='$author', lastmodified='$date' WHERE idtpl='$idtpl'";
            $db->query($sql);

            if (is_array($c)) {

				/* Delete all container assigned to this template */
                  $sql = "DELETE FROM ".$cfg["tab"]["container"]." WHERE idtpl='".$idtpl."'";
                  $db->query($sql);

               foreach($c as $idcontainer => $dummyval) {

                  $sql = "INSERT INTO ".$cfg["tab"]["container"]." (idcontainer, idtpl, number, idmod) VALUES ";
                  $sql .= "(";
                  $sql .= "'".$db->nextid($cfg["tab"]["container"])."', ";
                  $sql .= "'".$idtpl."', ";
                  $sql .= "'".$idcontainer."', ";
                  $sql .= "'".$c[$idcontainer]."'";
                  $sql .= ") ";
                  $db->query($sql);

               }
            }

            /* Generate code */
            conGenerateCodeForAllartsUsingTemplate($idtpl);

        }

		if ($default == 1)
		{
    		$sql = "UPDATE ".$cfg["tab"]["tpl"]." SET defaulttemplate = '0' WHERE idclient = '$client'";
    		$db->query($sql);

    		$sql = "UPDATE ".$cfg["tab"]["tpl"]." SET defaulttemplate = '1' WHERE idtpl = '$idtpl' AND idclient = '$client'";
    		$db->query($sql);
		} else {
			$sql = "UPDATE ".$cfg["tab"]["tpl"]." SET defaulttemplate = '0' WHERE idtpl = '$idtpl' AND idclient = '$client'";
    		$db->query($sql);
		}
		
		
        //******** if layout is changed stay at 'tpl_edit' otherwise go to 'tpl'
        if ($changelayout != 1) {
            $url = $sess->url("main.php?area=tpl_edit&idtpl=$idtpl&frame=4");
            header("location: $url");
        }

        return $idtpl;

}

/**
 * Delete a template
 *
 * @param int $idtpl ID of the template to duplicate
 *
 * @return $new_idtpl ID of the duplicated template
 * @author Olaf Niemann <Olaf.Niemann@4fb.de>
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG <www.4fb.>
 */
function tplDeleteTemplate($idtpl) {

        global $db, $client, $lang, $cfg, $area_tree, $perm;

        $sql = "DELETE FROM ".$cfg["tab"]["tpl"]." WHERE idtpl='$idtpl'";
        $db->query($sql);

        /* JL 160603 : Delete all unnecessary entries */

        $sql = "DELETE FROM ".$cfg["tab"]["container"]." WHERE idtpl = $idtpl";
        $db->query($sql);

        $idsToDelete = array();
        $sql = "SELECT idtplcfg FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtpl = $idtpl";
        $db->query($sql);
        while ( $db->next_record() ) {
        	$idsToDelete[] = $db->f("idtplcfg");
        }

        foreach ( $idsToDelete as $id ) {

        	$sql = "DELETE FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtplcfg = $id";
        	$db->query($sql);

        	$sql = "DELETE FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg = $id";
        	$db->query($sql);

        }

        cInclude ("includes", "functions.rights.php");
        deleteRightsForElement("tpl", $idtpl);

}


/**
 * Browse a specific layout for containers
 *
 * @param int $idtpl Layout number to browse
 *
 * @return string &-seperated String of all containers
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG <www.4fb.>
 */
function tplBrowseLayoutForContainers($idlay) {
        global $db;
        global $cfg;
		global $containerinf;
		
        $sql = "SELECT code FROM ".$cfg["tab"]["lay"]." WHERE idlay='$idlay'";
        $db->query($sql);
        $db->next_record();
        $code = $db->f("code");

        preg_match_all ("/CMS_CONTAINER\[([0-9]*)\]/", $code, $a_container);

		
		if (is_array($containerinf[$idlay]))
		{
			foreach ($containerinf[$idlay] as $key => $value)
			{
				$a_container[1][] = $key;
			}
		}

		$container = Array();
		
		foreach ($a_container[1] as $value)
		{
			if (!in_array($value, $container))
			{
				$container[] = $value;
			}
		}
		
		asort($container);
		
        if (is_array($container)) {
            $tmp_returnstring = implode("&",$container);
        }
        return $tmp_returnstring;
}

/**
 * Retrieve the container name
 *
 * @param int $idtpl Layout number to browse
 * @param int $container Container number
 *
 * @return string Container name
 */
function tplGetContainerName($idlay, $container) 
{
        global $db;
        global $cfg;
		global $containerinf;
		
		if (is_array($containerinf[$idlay]))
		{
    		if (array_key_exists($container, $containerinf[$idlay]))
    		{
    			return $containerinf[$idlay][$container]["name"];
    		}
		}
}

/**
 * Retrieve the container mode
 *
 * @param int $idtpl Layout number to browse
 * @param int $container Container number
 *
 * @return string Container name
 */
function tplGetContainerMode($idlay, $container) 
{
        global $db;
        global $cfg;
		global $containerinf;
		
		if (is_array($containerinf[$idlay]))
		{
    		if (array_key_exists($container, $containerinf[$idlay]))
    		{
    			return $containerinf[$idlay][$container]["mode"];
    		}
		}
}

/**
 * Retrieve the allowed container types
 *
 * @param int $idtpl Layout number to browse
 * @param int $container Container number
 *
 * @return array Allowed container types
 */
function tplGetContainerTypes($idlay, $container) 
{
        global $db;
        global $cfg;
		global $containerinf;
		
		if (is_array($containerinf[$idlay]))
		{
    		if (array_key_exists($container, $containerinf[$idlay]))
    		{
    			if ($containerinf[$idlay][$container]["types"] != "")
    			{
        			$list = explode(",",$containerinf[$idlay][$container]["types"]);
        			
        			foreach ($list as $key => $value)
        			{
        				$list[$key] = trim($value);
        			}
        			return $list;
    			}
    		}
		}
}

/**
 * Retrieve the default module
 *
 * @param int $idtpl Layout number to browse
 * @param int $container Container number
 *
 * @return array Allowed container types
 */
function tplGetContainerDefault($idlay, $container) 
{
        global $db;
        global $cfg;
		global $containerinf;
		
		if (is_array($containerinf[$idlay]))
		{
    		if (array_key_exists($container, $containerinf[$idlay]))
    		{
    			return $containerinf[$idlay][$container]["default"];
    		}
		}
}

/**
 * Preparse the layout for caching purposes
 *
 * @param int $idtpl Layout number to browse
 *
 * @return none
 */
function tplPreparseLayout ($idlay)
{
	global $containerinf;
	global $db;
	global $cfg;
	
    $sql = "SELECT code FROM ".$cfg["tab"]["lay"]." WHERE idlay='$idlay'";
    $db->query($sql);
    $db->next_record();
    $code = $db->f("code");
    
    cInclude ("classes", "class.htmlparser.php");
    
    $parser = new HtmlParser($code);
    
	while ($parser->parse())
	{
		if ($parser->iNodeName == "container" && $parser->iNodeType == NODE_TYPE_ELEMENT)
		{
			$idcontainer = $parser->iNodeAttributes["id"];
			
			$mode = $parser->iNodeAttributes["mode"];
			
			if ($mode == "")
			{
				$mode = "optional";
			}
			
			$containerinf[$idlay][$idcontainer]["name"] = $parser->iNodeAttributes["name"];
			$containerinf[$idlay][$idcontainer]["mode"] = $mode;
			$containerinf[$idlay][$idcontainer]["default"] = $parser->iNodeAttributes["default"];
			$containerinf[$idlay][$idcontainer]["types"] = $parser->iNodeAttributes["types"];
		}
	}
}

/**
 * Duplicate a template
 *
 * @param int $idtpl ID of the template to duplicate
 *
 * @return $new_idtpl ID of the duplicated template
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG <www.4fb.>
 */
function tplDuplicateTemplate($idtpl) {

    global $db, $client, $lang, $cfg, $sess, $auth;

    $db2 = new DB_Contenido;

    $sql = "SELECT
                *
            FROM
                ".$cfg["tab"]["tpl"]."
            WHERE
                idtpl = '".$idtpl."'";

    $db->query($sql);
    $db->next_record();

    $idclient   = $db->f("idclient");
    $idlay      = $db->f("idlay");
    $new_idtpl  = $db->nextid($cfg["tab"]["tpl"]);
    $name       = sprintf(i18n("%s (Copy)"), $db->f("name"));
    $descr      = $db->f("description");
    $author     = $auth->auth["uname"];
    $created    = time();
    $lastmod    = time();

    $sql = "INSERT INTO
                ".$cfg["tab"]["tpl"]."
                (idclient, idlay, idtpl, name, description, deletable,author, created, lastmodified)
            VALUES
                ('".$idclient."', '".$idlay."', '".$new_idtpl."', '".$name."', '".$descr."', '1', '".$author."', '".$created."', '".$lastmod."')";

    $db->query($sql);


    $a_containers = array();

    $sql = "SELECT
                *
            FROM
                ".$cfg["tab"]["container"]."
            WHERE
                idtpl = '".$idtpl."'
            ORDER BY
                number";

    $db->query($sql);

    while ($db->next_record()) {
        $a_containers[$db->f("number")] = $db->f("idmod");
    }

    foreach ($a_containers as $key => $value) {

        $nextid = $db->nextid($cfg["tab"]["container"]);

        $sql = "INSERT INTO ".$cfg["tab"]["container"]."
                (idcontainer, idtpl, number, idmod) VALUES ('".$nextid."', '".$new_idtpl."', '".$key."', '".$value."')";

        $db->query($sql);

    }

    cInclude ("includes", "functions.rights.php");
    copyRightsForElement("tpl", $idtpl, $new_idtpl);
    
    return $new_idtpl;

}

/**
 * Checks if a template is in use
 *
 * @param int $idtpl Template ID
 *
 * @return bool is template in use
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
function tplIsTemplateInUse($idtpl) {

    global $cfg, $client, $lang;

    $db = new DB_Contenido;
    $db2 = new DB_Contenido;

    $sql = "SELECT idtplcfg FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtpl = '".$idtpl."'";
    $db->query($sql);

    while ($db->next_record()) {

        /* Check categorys */
        $sql = "SELECT
                    b.idcatlang
                FROM
                    ".$cfg["tab"]["cat"]." AS a,
                    ".$cfg["tab"]["cat_lang"]." AS b
                WHERE
                    a.idclient  = '".$client."' AND
                    a.idcat     = b.idcat AND
                    b.idtplcfg  = '".$db->f("idtplcfg")."'";


        $db2->query($sql);

        if ( $db2->next_record() ) {
            return true;
        }

        /* Check articles */
        $sql = "SELECT
                    b.idartlang
                FROM
                    ".$cfg["tab"]["art"]." AS a,
                    ".$cfg["tab"]["art_lang"]." AS b
                WHERE
                    a.idclient  = '".$client."' AND
                    a.idart     = b.idart AND
                    b.idtplcfg  = '".$db->f("idtplcfg")."'";


        $db2->query($sql);

        if ( $db2->next_record() ) {
            return true;
        }
    }

    return false;

}

/**
 * Copies a complete template configuration
 *
 * @param int $idtplcfg Template Configuration ID
 *
 * @return int new template configuration ID
 *
 */
function tplcfgDuplicate ($idtplcfg)
{
	global $cfg;
	
	$db = new DB_Contenido;
	$db2 = new DB_Contenido;
	
	$sql = "SELECT
				idtpl, status, author, created, lastmodified
			FROM
				".$cfg["tab"]["tpl_conf"]."
			WHERE
				idtplcfg = '$idtplcfg'";
	
	$db->query($sql);
	
	if ($db->next_record())
	{
		$newidtplcfg = $db2->nextid($cfg["tab"]["tpl_conf"]);
		$idtpl = $db->f("idtpl");
		$status = $db->f("status");
		$author = $db->f("author");
		$created = $db->f("created");
		$lastmodified = $db->f("lastmodified");
		
		$sql = "INSERT INTO
				".$cfg["tab"]["tpl_conf"]."
				(idtplcfg, idtpl, status, author, created, lastmodified)
				VALUES
				('$newidtplcfg', '$idtpl', '$status', '$author', '$created', '$lastmodified')";
				
		$db2->query($sql);
		
		/* Copy container configuration */
    	$sql = "SELECT 
    				number, container
    			FROM
    				".$cfg["tab"]["container_conf"]."
    			WHERE idtplcfg = '$idtplcfg'";
    			
    	$db->query($sql);
    	
    	while ($db->next_record())
    	{
    		$newidcontainerc = $db2->nextid($cfg["tab"]["container_conf"]);
    		$number = $db->f("number");
    		$container = $db->f("container");
    		
    		$sql = "INSERT INTO
    				".$cfg["tab"]["container_conf"]."
    				(idcontainerc, idtplcfg, number, container)
    				VALUES
    				('$newidcontainerc', '$newidtplcfg', '$number', '$container')";
    		$db2->query($sql);	
    	}	
	}
	
	return ($newidtplcfg);
	
}

/*
 * tplAutoFillModules
 * 
 * This function fills in modules automatically using this logic:
 * 
 * - If the container mode is fixed, insert the named module (if exists)
 * - If the container mode is mandatory, insert the "default" module (if exists)
 * 
 * TODO: The default module is only inserted in mandatory mode if the container
 *       is empty. We need a better logic for handling "changes". 
 */

function tplAutoFillModules ($idtpl)
{
	global $cfg;
	global $db_autofill;
	global $containerinf;
	global $_autoFillcontainerCache;

	if (!is_object($db_autofill))
	{
		$db_autofill = new DB_Contenido;
	}
	
	$sql = "SELECT idlay FROM ".$cfg["tab"]["tpl"]." WHERE idtpl = '$idtpl'";
	$db_autofill->query($sql);
	
	if (!$db_autofill->next_record())
	{
		return false;	
	}

	$idlay = $db_autofill->f("idlay");
	
	if (!(is_array($containerinf) && array_key_exists($idlay, $containerinf) && array_key_exists($idlay, $_autoFillcontainerCache)))
	{
		tplPreparseLayout($idlay);
		$_autoFillcontainerCache[$idlay] = tplBrowseLayoutForContainers($idlay);
	}
	
	$a_container = explode("&",$_autoFillcontainerCache[$idlay]);	

	foreach ($a_container as $container)
	{
		switch ($containerinf[$idlay][$container]["mode"])
		{
			/* Fixed mode */
			case "fixed":
			if ($containerinf[$idlay][$container]["default"] != "")
			{
				$sql = 	"SELECT idmod FROM ".$cfg["tab"]["mod"]
						." WHERE name = '".
						$containerinf[$idlay][$container]["default"]."'";
						
				$db_autofill->query($sql);
				
				if ($db_autofill->next_record())
				{
					$idmod = $db_autofill->f("idmod");	
					
					
					$sql = 	"SELECT idcontainer FROM ".$cfg["tab"]["container"]
							." WHERE idtpl = '$idtpl' AND number = '$container'";
							
					$db_autofill->query($sql);
					
					if ($db_autofill->next_record())
					{
						$sql = 	"UPDATE ".$cfg["tab"]["container"].
								" SET idmod = '$idmod' WHERE idtpl = '$idtpl'".
								" AND number = '$container' AND ".
								" idcontainer = '".$db_autofill->f("idcontainer")."'";
						$db_autofill->query($sql);
					} else {
						$sql = 	"INSERT INTO ".$cfg["tab"]["container"].
							  	" (idcontainer, idtpl, number, idmod) ".
							  	" VALUES ('".$db_autofill->nextid($cfg["tab"]["container"])."', ".
							  	" '$idtpl', '$container', '$idmod')";
						$db_autofill->query($sql);
					}
				}
			}
			
						
			case "mandatory":	
			
			if ($containerinf[$idlay][$container]["default"] != "")
			{
				$sql = 	"SELECT idmod FROM ".$cfg["tab"]["mod"]
						." WHERE name = '".
						$containerinf[$idlay][$container]["default"]."'";
						
				$db_autofill->query($sql);
				
				if ($db_autofill->next_record())
				{
					$idmod = $db_autofill->f("idmod");	
					
					
					$sql = 	"SELECT idcontainer, idmod FROM ".$cfg["tab"]["container"]
							." WHERE idtpl = '$idtpl' AND number = '$container'";
							
					$db_autofill->query($sql);
					
					if ($db_autofill->next_record())
					{
					
					} else {
						$sql = 	"INSERT INTO ".$cfg["tab"]["container"].
							  	" (idcontainer, idtpl, number, idmod) ".
							  	" VALUES ('".$db_autofill->nextid($cfg["tab"]["container"])."', ".
							  	" '$idtpl', '$container', '$idmod')";
						$db_autofill->query($sql);
					}
				}
			}			
		}
	}

}

?>
