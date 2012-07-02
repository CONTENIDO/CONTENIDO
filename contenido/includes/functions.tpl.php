<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Define the Template related functions
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.0.1
 * @author     Olaf Niemann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created 2003-01-21
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

cInclude("includes", "functions.con.php");

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

        $db2= cRegistry::getDb();
        $date = date("YmdHis");
        $author = "".$auth->auth["uname"]."";

        //******** entry in 'tpl'-table ***************
        set_magic_quotes_gpc($name);
        set_magic_quotes_gpc($description);

        $name = cApiStrCleanURLCharacters($name);

        if (!$idtpl) {

//            $idtpl = $db->nextid($cfg["tab"]["tpl"]);
//            $idtplcfg = $db->nextid($cfg["tab"]["tpl_conf"]);

            /* Insert new entry in the
               Template table  */
            $sql = "INSERT INTO ".$cfg["tab"]["tpl"]."
                    (idtplcfg, name, description, deletable, idlay, idclient, author, created, lastmodified) VALUES
                    ('".Contenido_Security::toInteger(0)."', '".Contenido_Security::escapeDB($name, $db)."', '".Contenido_Security::escapeDB($description, $db)."',
                    '1', '".Contenido_Security::toInteger($idlay)."', '".Contenido_Security::toInteger($client)."', '".Contenido_Security::escapeDB($author, $db)."', '".Contenido_Security::escapeDB($date, $db)."',
                    '".Contenido_Security::escapeDB($date, $db)."')";

            $db->query($sql);
            $idtpl = $db->getLastInsertedId($cfg["tab"]["tpl"]);

            /* Insert new entry in the
               Template Conf table  */
            $sql = "INSERT INTO ".$cfg["tab"]["tpl_conf"]."
                    (idtpl, author) VALUES
                   ('".Contenido_Security::toInteger($idtpl)."', '".Contenido_Security::escapeDB($auth->auth["uname"], $db)."')";

            $db->query($sql);
            $idtplcfg = $db->getLastInsertedId($cfg["tab"]["tpl_conf"]);

            /* Update new idtplconf*/
            $sql = "UPDATE ".$cfg["tab"]["tpl_conf"]." SET idtplcfg='".Contenido_Security::toInteger($idtplcfg)."'
            WHERE idtpl='".Contenido_Security::toInteger($idtpl)."'";
            $db->query($sql);

            // set correct rights for element
            cInclude("includes", "functions.rights.php");
            createRightsForElement("tpl", $idtpl);


        } else {

            /* Update */
            $sql = "UPDATE ".$cfg["tab"]["tpl"]." SET name='".Contenido_Security::escapeDB($name, $db)."', description='".Contenido_Security::escapeDB($description, $db)."', idlay='".Contenido_Security::toInteger($idlay)."',
                    author='".Contenido_Security::escapeDB($author, $db)."', lastmodified='".Contenido_Security::escapeDB($date, $db)."' WHERE idtpl='".Contenido_Security::toInteger($idtpl)."'";
            $db->query($sql);

            if (is_array($c)) {

                /* Delete all container assigned to this template */
                  $sql = "DELETE FROM ".$cfg["tab"]["container"]." WHERE idtpl='".Contenido_Security::toInteger($idtpl, $db)."'";
                  $db->query($sql);

               foreach ($c as $idcontainer => $dummyval) {

                  $sql = "INSERT INTO ".$cfg["tab"]["container"]." (idtpl, number, idmod) VALUES ";
                  $sql .= "(";
                  $sql .= "'".Contenido_Security::toInteger($idtpl)."', ";
                  $sql .= "'".Contenido_Security::toInteger($idcontainer)."', ";
                  $sql .= "'".Contenido_Security::toInteger($c[$idcontainer])."'";
                  $sql .= ") ";
                  $db->query($sql);

               }
            }

            /* Generate code */
            conGenerateCodeForAllartsUsingTemplate($idtpl);

        }

        if ($default == 1)
        {
            $sql = "UPDATE ".$cfg["tab"]["tpl"]." SET defaulttemplate = '0' WHERE idclient = '".Contenido_Security::toInteger($client)."'";
            $db->query($sql);

            $sql = "UPDATE ".$cfg["tab"]["tpl"]." SET defaulttemplate = '1' WHERE idtpl = '".Contenido_Security::toInteger($idtpl)."' AND idclient = '".Contenido_Security::toInteger($client)."'";
            $db->query($sql);
        } else {
            $sql = "UPDATE ".$cfg["tab"]["tpl"]." SET defaulttemplate = '0' WHERE idtpl = '".Contenido_Security::toInteger($idtpl)."' AND idclient = '".Contenido_Security::toInteger($client)."'";
            $db->query($sql);
        }


        //******** if layout is changed stay at 'tpl_edit' otherwise go to 'tpl'
        //if ($changelayout != 1) {
         //   $url = $sess->url("main.php?area=tpl_edit&idtpl=$idtpl&frame=4&blubi=blubxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx");
          //  header("location: $url");
        //}

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

        $sql = "DELETE FROM ".$cfg["tab"]["tpl"]." WHERE idtpl='".Contenido_Security::toInteger($idtpl)."'";
        $db->query($sql);

        /* JL 160603 : Delete all unnecessary entries */

        $sql = "DELETE FROM ".$cfg["tab"]["container"]." WHERE idtpl = '".Contenido_Security::toInteger($idtpl)."'";
        $db->query($sql);

        $idsToDelete = array();
        $sql = "SELECT idtplcfg FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtpl = '".Contenido_Security::toInteger($idtpl)."'";
        $db->query($sql);
        while ( $db->next_record() ) {
            $idsToDelete[] = $db->f("idtplcfg");
        }

        foreach ( $idsToDelete as $id ) {

            $sql = "DELETE FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtplcfg = '".Contenido_Security::toInteger($id)."'";
            $db->query($sql);

            $sql = "DELETE FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg = '".Contenido_Security::toInteger($id)."'";
            $db->query($sql);

        }

        cInclude("includes", "functions.rights.php");
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
        global $lang;

        $layoutInFile = new LayoutInFile($idlay, "", $cfg, $lang);
        $code = $layoutInFile->getLayoutCode();


        preg_match_all ("/CMS_CONTAINER\[([0-9]*)\]/", $code, $a_container);
        $iPosBody = stripos($code, '<body>');
        $sCodeBeforeHeader = substr($code, 0, $iPosBody);

        foreach ($a_container[1] as $value) {
            if (preg_match("/CMS_CONTAINER\[$value\]/", $sCodeBeforeHeader)) {
                $containerinf[$idlay][$value]["is_body"] = false;
            } else {
                $containerinf[$idlay][$value]["is_body"] = true;
            }
        }

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
    global $lang;

    $layoutInFile = new LayoutInFile($idlay, "", $cfg, $lang);
    $code = $layoutInFile->getLayoutCode();

    $parser = new HtmlParser($code);
    $bIsBody = false;
    while ($parser->parse())
    {
        if (strtolower($parser->iNodeName) == 'body') {
            $bIsBody = true;
        }

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
            $containerinf[$idlay][$idcontainer]["is_body"] = $bIsBody;
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

    $db2 = cRegistry::getDb();

    $sql = "SELECT
                *
            FROM
                ".$cfg["tab"]["tpl"]."
            WHERE
                idtpl = '".Contenido_Security::toInteger($idtpl)."'";

    $db->query($sql);
    $db->next_record();

    $idclient   = $db->f("idclient");
    $idlay      = $db->f("idlay");
    //$new_idtpl  = $db->nextid($cfg["tab"]["tpl"]);

    $name       = sprintf(i18n("%s (Copy)"), $db->f("name"));
    $descr      = $db->f("description");
    $author     = $auth->auth["uname"];
    $created    = time();
    $lastmod    = time();

    $idtpl_conf = $db->f("idtplcfg");
    if($idtpl_conf) {
        // after inserted con_template, we have to update idptl
        $templateConf = array('idtpl'=>0, 'status'=>0, 'author'=>$author, 'created'=>$created);
        $db->insert($cfg["tab"]["tpl_conf"], $templateConf);
        $new_idtpl_conf = $db->getLastInsertedId($cfg["tab"]["tpl_conf"]);
    }

    $sql = "INSERT INTO
                ".$cfg["tab"]["tpl"]."
                (idclient, idlay, ".($idtpl_conf?'idtplcfg,':'')." name, description, deletable,author, created, lastmodified)
            VALUES
                ('".Contenido_Security::toInteger($idclient)."', '".Contenido_Security::toInteger($idlay)."', ".($idtpl_conf?"'".Contenido_Security::toInteger($new_idtpl_conf)."', ":'')." '".Contenido_Security::escapeDB($name, $db)."',
                 '".Contenido_Security::escapeDB($descr, $db)."', '1', '".Contenido_Security::escapeDB($author, $db)."', '".Contenido_Security::escapeDB($created, $db)."', '".Contenido_Security::escapeDB($lastmod, $db)."')";
    $db->query($sql);
    $new_idtpl = $db->getLastInsertedId($cfg["tab"]["tpl"]);

    // update template_conf, set idtpl width right value.
    $db->update($cfg["tab"]["tpl_conf"], array('idtpl'=>$new_idtpl), array('idtplcfg'=>$new_idtpl_conf));

    $a_containers = array();

    $sql = "SELECT
                *
            FROM
                ".$cfg["tab"]["container"]."
            WHERE
                idtpl = '".Contenido_Security::toInteger($idtpl)."'
            ORDER BY
                number";

    $db->query($sql);

    while ($db->next_record()) {
        $a_containers[$db->f("number")] = $db->f("idmod");
    }

    foreach ($a_containers as $key => $value) {

        //$nextid = $db->nextid($cfg["tab"]["container"]);

        $sql = "INSERT INTO ".$cfg["tab"]["container"]."
                (idtpl, number, idmod) VALUES ('".Contenido_Security::toInteger($new_idtpl)."', '".Contenido_Security::toInteger($key)."', '".Contenido_Security::toInteger($value)."')";

        $db->query($sql);

    }

    //modified (added) 2008-06-30 timo.trautmann added fix module settings were also copied
    if($idtpl_conf) {
        $a_container_cfg = array();
        $sql = "SELECT
                       *
                 FROM
                       ".$cfg["tab"]["container_conf"]."
                 WHERE
                       idtplcfg = '".Contenido_Security::toInteger($idtpl_conf)."'
                 ORDER BY
                       number";

        $db->query($sql);

        while ($db->next_record()) {
           $a_container_cfg[$db->f("number")] = $db->f("container");
        }

        foreach ($a_container_cfg as $key => $value) {



           $sql = "INSERT INTO ".$cfg["tab"]["container_conf"]."
                       (idtplcfg, number, container) VALUES
                       ( '".Contenido_Security::toInteger($new_idtpl_conf)."', '".Contenido_Security::escapeDB($key, $db)."', '".Contenido_Security::escapeDB($value, $db)."')";

           $db->query($sql);

        }
    }
    //modified (added) 2008-06-30 end

    cInclude("includes", "functions.rights.php");
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
 *
 * modified Munkh-Ulzii Balidar, improved the sql query without while loop
 */
function tplIsTemplateInUse($idtpl) {

    global $cfg, $client, $lang;

    $db = cRegistry::getDb();
    // Check categorys
    $sql = "SELECT
                   b.idcatlang, b.name, b.idlang, b.idcat
            FROM
                ".$cfg["tab"]["cat"]." AS a,
                ".$cfg["tab"]["cat_lang"]." AS b
            WHERE
                a.idclient  = '".Contenido_Security::toInteger($client)."' AND
                a.idcat     = b.idcat AND
                b.idtplcfg  IN (SELECT idtplcfg FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtpl = '".$idtpl."')
            ORDER BY b.idlang ASC, b.name ASC ";
    $db->query($sql);
    if ($db->Errno == '' && $db->num_rows() > 0) {
        return true;
    }

    // Check articles
    $sql = "SELECT
                   b.idartlang, b.title, b.idlang, b.idart
            FROM
                ".$cfg["tab"]["art"]." AS a,
                ".$cfg["tab"]["art_lang"]." AS b
            WHERE
                a.idclient  = '".Contenido_Security::toInteger($client)."' AND
                a.idart     = b.idart AND
                b.idtplcfg IN (SELECT idtplcfg FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtpl = '".$idtpl."')
            ORDER BY b.idlang ASC, b.title ASC ";

    $db->query($sql);

    if ($db->Errno == '' && $db->num_rows() > 0) {
        return true;
    }

    return false;

}

/**
 * Get used datas if a template is in use
 *
 * @param int $idtpl Template ID
 *
 * @return array - category name, article name
 *
 * @author Munkh-Ulzii Balidar <munkh-ulzii.balidar@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
function tplGetInUsedData($idtpl) {

    global $cfg, $client, $lang;

    $db = cRegistry::getDb();

    $aUsedData = array();

    // Check categorys
    $sql = "SELECT
                   b.idcatlang, b.name, b.idlang, b.idcat
            FROM
                ".$cfg["tab"]["cat"]." AS a,
                ".$cfg["tab"]["cat_lang"]." AS b
            WHERE
                a.idclient  = '".Contenido_Security::toInteger($client)."' AND
                a.idcat     = b.idcat AND
                b.idtplcfg  IN (SELECT idtplcfg FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtpl = '".$idtpl."')
            ORDER BY b.idlang ASC, b.name ASC ";
    $db->query($sql);
    if ($db->Errno == 0 && $db->num_rows() > 0) {
        while ($db->next_record()) {
            $aUsedData['cat'][] = array(
            'name' => $db->f('name'),
            'lang' => $db->f('idlang'),
            'idcat' => $db->f('idcat'),
            );
        }
    }

    // Check articles
    $sql = "SELECT
                   b.idartlang, b.title, b.idlang, b.idart
            FROM
                ".$cfg["tab"]["art"]." AS a,
                ".$cfg["tab"]["art_lang"]." AS b
            WHERE
                a.idclient  = '".Contenido_Security::toInteger($client)."' AND
                a.idart     = b.idart AND
                b.idtplcfg IN (SELECT idtplcfg FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtpl = '".$idtpl."')
            ORDER BY b.idlang ASC, b.title ASC ";

    $db->query($sql);

    if ($db->Errno == '' && $db->num_rows() > 0) {
        while ($db->next_record()) {
            $aUsedData['art'][] = array(
            'title' => $db->f('title'),
            'lang' => $db->f('idlang'),
            'idart' => $db->f('idart'),
            );
        }
    }

    return $aUsedData;

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

    $db = cRegistry::getDb();
    $db2 = cRegistry::getDb();

    $sql = "SELECT
                idtpl, status, author, created, lastmodified
            FROM
                ".$cfg["tab"]["tpl_conf"]."
            WHERE
                idtplcfg = '".Contenido_Security::toInteger($idtplcfg)."'";

    $db->query($sql);

    if ($db->next_record())
    {
        //$newidtplcfg = $db2->nextid($cfg["tab"]["tpl_conf"]);
        $idtpl = $db->f("idtpl");
        $status = $db->f("status");
        $author = $db->f("author");
        $created = $db->f("created");
        $lastmodified = $db->f("lastmodified");

        $sql = "INSERT INTO
                ".$cfg["tab"]["tpl_conf"]."
                (idtpl, status, author, created, lastmodified)
                VALUES
                ('".Contenido_Security::toInteger($idtpl)."', '".Contenido_Security::toInteger($status)."', '".Contenido_Security::escapeDB($author, $db2)."',
                '".Contenido_Security::escapeDB($created, $db2)."', '".Contenido_Security::escapeDB($lastmodified, $db2)."')";

        $db2->query($sql);
        $newidtplcfg = $db2->getLastInsertedId($cfg["tab"]["tpl_conf"]);

        /* Copy container configuration */
        $sql = "SELECT
                    number, container
                FROM
                    ".$cfg["tab"]["container_conf"]."
                WHERE idtplcfg = '".Contenido_Security::toInteger($idtplcfg)."'";

        $db->query($sql);

        while ($db->next_record())
        {
            //$newidcontainerc = $db2->nextid($cfg["tab"]["container_conf"]);
            $number = $db->f("number");
            $container = $db->f("container");

            $sql = "INSERT INTO
                    ".$cfg["tab"]["container_conf"]."
                    ( idtplcfg, number, container)
                    VALUES
                    ('".Contenido_Security::toInteger($newidtplcfg)."', '".Contenido_Security::toInteger($number)."', '".Contenido_Security::escapeDB($container, $db2)."')";
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
        $db_autofill = cRegistry::getDb();
    }

    $sql = "SELECT idlay FROM ".$cfg["tab"]["tpl"]." WHERE idtpl = '".Contenido_Security::toInteger($idtpl)."'";
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
                $sql =     "SELECT idmod FROM ".$cfg["tab"]["mod"]
                        ." WHERE name = '".
                        Contenido_Security::escapeDB($containerinf[$idlay][$container]["default"], $db_autofill)."'";

                $db_autofill->query($sql);

                if ($db_autofill->next_record())
                {
                    $idmod = $db_autofill->f("idmod");


                    $sql = "SELECT idcontainer FROM ".$cfg["tab"]["container"]." WHERE idtpl = '".Contenido_Security::toInteger($idtpl)."' AND number = '".Contenido_Security::toInteger($container)."'";

                    $db_autofill->query($sql);

                    if ($db_autofill->next_record())
                    {
                        $sql =     "UPDATE ".$cfg["tab"]["container"].
                                " SET idmod = '".Contenido_Security::toInteger($idmod)."' WHERE idtpl = '".Contenido_Security::toInteger($idtpl)."'".
                                " AND number = '".Contenido_Security::toInteger($container)."' AND ".
                                " idcontainer = '".Contenido_Security::toInteger($db_autofill->f("idcontainer"))."'";
                        $db_autofill->query($sql);
                    } else {
                        $sql =     "INSERT INTO ".$cfg["tab"]["container"].
                                  " (idtpl, number, idmod) ".
                                  " VALUES ( ".
                                  " '$idtpl', '$container', '$idmod')";
                        $db_autofill->query($sql);
                    }
                }
            }


            case "mandatory":

            if ($containerinf[$idlay][$container]["default"] != "")
            {
                $sql =     "SELECT idmod FROM ".$cfg["tab"]["mod"]
                        ." WHERE name = '".
                        Contenido_Security::escapeDB($containerinf[$idlay][$container]["default"], $db)."'";

                $db_autofill->query($sql);

                if ($db_autofill->next_record())
                {
                    $idmod = $db_autofill->f("idmod");


                    $sql =     "SELECT idcontainer, idmod FROM ".$cfg["tab"]["container"]
                            ." WHERE idtpl = '".Contenido_Security::toInteger($idtpl)."' AND number = '".Contenido_Security::toInteger($container)."'";

                    $db_autofill->query($sql);

                    if ($db_autofill->next_record())
                    {

                    } else {
                        $sql =     "INSERT INTO ".$cfg["tab"]["container"].
                                  " (idtpl, number, idmod) ".
                                  " VALUES ( ".
                                  " '".Contenido_Security::toInteger($idtpl)."', '".Contenido_Security::toInteger($container)."', '".Contenido_Security::toInteger($idmod)."')";
                        $db_autofill->query($sql);
                    }
                }
            }
        }
    }

}

?>