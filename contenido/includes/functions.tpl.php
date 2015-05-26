<?php

/**
 * This file contains the CONTENIDO template functions.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Olaf Niemann, Jan Lengowski, Munkh-Ulzii Balidar
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude("includes", "functions.con.php");

/**
 * Edit or create a new Template
 *
 * @param unknown_type $changelayout
 * @param unknown_type $idtpl
 * @param unknown_type $name
 * @param unknown_type $description
 * @param unknown_type $idlay
 * @param unknown_type $c
 * @param unknown_type $default
 * @return number|Ambigous <mixed, boolean, multitype:>
 */
function tplEditTemplate($changelayout, $idtpl, $name, $description, $idlay, $c, $default) {
    global $db, $sess, $auth, $client, $cfg;

    $author = (string) $auth->auth['uname'];

    // entry in 'tpl'-table
    // set_magic_quotes_gpc($name);
    // set_magic_quotes_gpc($description);

    $template = new cApiTemplate();
    $template->loadByMany(array('idclient' => $client, 'name' => $name));
    if ($template->isLoaded() && $template->get('idtpl') != $idtpl) {
        cRegistry::addErrorMessage(i18n("Template name already exists"));
        return -1;
    }

    if (!$idtpl) {
        // Insert new entry in the Template table
        $templateColl = new cApiTemplateCollection();
        $template = $templateColl->create($client, $idlay, 0, $name, $description, 1, 0, 0);
        $idtpl = $template->get('idtpl');

        // Insert new entry in the Template Conf table
        $templateConfColl = new cApiTemplateConfigurationCollection();
        $templateConf = $templateConfColl->create($idtpl);
        $idtplcfg = $templateConf->get('idtplcfg');

        // Update new idtplconf
        $template->set('idtplcfg', $idtplcfg);
        $template->store();

        // Set correct rights for element
        cInclude('includes', 'functions.rights.php');
        createRightsForElement('tpl', $idtpl);
    } else {

        // Define lastmodified variable with actual date
        $lastmodified = date('Y-m-d H:i:s');

        // Update existing entry in the Template table
        $template = new cApiTemplate($idtpl);
        $template->set('name', $name);
        $template->set('description', $description);
        $template->set('idlay', $idlay);
        $template->set('author', $author);
        $template->set('lastmodified', $lastmodified);
        $template->store();

        if (is_array($c)) {
            // Delete all container assigned to this template
            $containerColl = new cApiContainerCollection();
            $containerColl->clearAssignments($idtpl);

            foreach ($c as $idcontainer => $dummyval) {
                $containerColl2 = new cApiContainerCollection();
                $containerColl2->create($idtpl, $idcontainer, $c[$idcontainer]);
            }
        }

        // Generate code
        conGenerateCodeForAllartsUsingTemplate($idtpl);
    }

    if ($default == 1) {
        $sql = "UPDATE " . $cfg["tab"]["tpl"] . " SET defaulttemplate = 0 WHERE idclient = " . cSecurity::toInteger($client) . " AND idtpl != " . cSecurity::toInteger($template->get('idtpl'));
        $db->query($sql);

        $template->set('defaulttemplate', 1);
        $template->store();
    } else {
        $template->set('defaulttemplate', 0);
        $template->store();
    }

    // if layout is changed stay at 'tpl_edit' otherwise go to 'tpl'
    // if ($changelayout != 1) {
    //     $url = $sess->url("main.php?area=tpl_edit&idtpl=$idtpl&frame=4&blubi=blubxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx");
    //     header("location: $url");
    // }

    return $idtpl;
}

/**
 * Delete a template
 *
 * @param int $idtpl
 *         ID of the template to duplicate
 */
function tplDeleteTemplate($idtpl) {

    global $db, $client, $lang, $cfg, $area_tree, $perm;

    $sql = "DELETE FROM " . $cfg["tab"]["tpl"] . " WHERE idtpl = '" . cSecurity::toInteger($idtpl) . "'";
    $db->query($sql);

    /* JL 160603 : Delete all unnecessary entries */

    $sql = "DELETE FROM " . $cfg["tab"]["container"] . " WHERE idtpl = '" . cSecurity::toInteger($idtpl) . "'";
    $db->query($sql);

    $idsToDelete = array();
    $sql = "SELECT idtplcfg FROM " . $cfg["tab"]["tpl_conf"] . " WHERE idtpl = '" . cSecurity::toInteger($idtpl) . "'";
    $db->query($sql);
    while ($db->nextRecord()) {
        $idsToDelete[] = $db->f("idtplcfg");
    }

    foreach ($idsToDelete as $id) {
        $sql = "DELETE FROM " . $cfg["tab"]["tpl_conf"] . " WHERE idtplcfg = '" . cSecurity::toInteger($id) . "'";
        $db->query($sql);

        $sql = "DELETE FROM " . $cfg["tab"]["container_conf"] . " WHERE idtplcfg = '" . cSecurity::toInteger($id) . "'";
        $db->query($sql);
    }

    cInclude("includes", "functions.rights.php");
    deleteRightsForElement("tpl", $idtpl);
}

/**
 * Browse a specific layout for containers
 *
 * @param int $idtpl
 *         Layout number to browse
 * @return string
 *         &-separated string of all containers
 */
function tplBrowseLayoutForContainers($idlay) {
    global $db, $cfg, $containerinf, $lang;

    $layoutInFile = new cLayoutHandler($idlay, '', $cfg, $lang);
    $code = $layoutInFile->getLayoutCode();

    $containerNumbers = array();
    $returnStr = '';

    preg_match_all("/CMS_CONTAINER\[([0-9]*)\]/", $code, $containerMatches);
    $posBody = stripos($code, '<body>');
    $codeBeforeHeader = substr($code, 0, $posBody);

    foreach ($containerMatches[1] as $value) {
        if (preg_match("/CMS_CONTAINER\[$value\]/", $codeBeforeHeader)) {
            $containerinf[$idlay][$value]["is_body"] = false;
        } else {
            $containerinf[$idlay][$value]["is_body"] = true;
        }
    }

    if (is_array($containerinf[$idlay])) {
        foreach ($containerinf[$idlay] as $key => $value) {
            $containerMatches[1][] = $key;
        }
    }

    foreach ($containerMatches[1] as $value) {
        if (!in_array($value, $containerNumbers)) {
            $containerNumbers[] = $value;
        }
    }
    asort($containerNumbers);

    $returnStr = implode('&', $containerNumbers);

    return $returnStr;
}

/**
 * Wrapper for tplPreparseLayout() and tplBrowseLayoutForContainers().
 * Calls both functions to get the container numbers from layout and return
 * the list of found container numbers.
 *
 * @param int $idlay
 * @return array
 */
function tplGetContainerNumbersInLayout($idlay) {
    $containerNumbers = array();

    tplPreparseLayout($idlay);
    $containerNumbersStr = tplBrowseLayoutForContainers($idlay);
    if (!empty($containerNumbersStr)) {
        $containerNumbers = explode('&', $containerNumbersStr);
    }

    return $containerNumbers;
}

/**
 * Retrieve the container name
 *
 * @param int $idtpl
 *         Layout number to browse
 * @param int $container
 *         Container number
 * @return string
 *         Container name
 */
function tplGetContainerName($idlay, $container) {
    global $db, $cfg, $containerinf;

    if (is_array($containerinf[$idlay])) {
        if (array_key_exists($container, $containerinf[$idlay])) {
            return $containerinf[$idlay][$container]["name"];
        }
    }
}

/**
 * Retrieve the container mode
 *
 * @param int $idtpl
 *         Layout number to browse
 * @param int $container
 *         Container number
 * @return string
 *         Container name
 */
function tplGetContainerMode($idlay, $container) {
    global $db, $cfg, $containerinf;

    if (is_array($containerinf[$idlay])) {
        if (array_key_exists($container, $containerinf[$idlay])) {
            return $containerinf[$idlay][$container]["mode"];
        }
    }
}

/**
 * Retrieve the allowed container types
 *
 * @param int $idtpl
 *         Layout number to browse
 * @param int $container
 *         Container number
 * @return array
 *         Allowed container types
 */
function tplGetContainerTypes($idlay, $container) {
    global $db, $cfg, $containerinf;

    if (is_array($containerinf[$idlay])) {
        if (array_key_exists($container, $containerinf[$idlay])) {
            if ($containerinf[$idlay][$container]["types"] != "") {
                $list = explode(",", $containerinf[$idlay][$container]["types"]);

                foreach ($list as $key => $value) {
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
 * @param int $idtpl
 *         Layout number to browse
 * @param int $container
 *         Container number
 * @return array
 *         Allowed container types
 */
function tplGetContainerDefault($idlay, $container) {
    global $db, $cfg, $containerinf;

    if (is_array($containerinf[$idlay])) {
        if (array_key_exists($container, $containerinf[$idlay])) {
            return $containerinf[$idlay][$container]["default"];
        }
    }
}

/**
 * Preparse the layout for caching purposes
 *
 * @param int $idtpl
 *         Layout number to browse
 */
function tplPreparseLayout($idlay) {
    global $db, $cfg, $containerinf, $lang;

    $layoutInFile = new cLayoutHandler($idlay, "", $cfg, $lang);
    $code = $layoutInFile->getLayoutCode();

    $parser = new HtmlParser($code);
    $bIsBody = false;
    while ($parser->parse()) {
        if (strtolower($parser->iNodeName) == 'body') {
            $bIsBody = true;
        }

        if ($parser->iNodeName == "container" && $parser->iNodeType == HtmlParser::NODE_TYPE_ELEMENT) {
            $idcontainer = $parser->iNodeAttributes["id"];

            $mode = $parser->iNodeAttributes["mode"];

            if ($mode == "") {
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
 * @param int $idtpl
 *         ID of the template to duplicate
 * @return int
 *         ID of the duplicated template
 */
function tplDuplicateTemplate($idtpl) {
    global $db, $client, $lang, $cfg, $sess, $auth;

    $idtpl = cSecurity::toInteger($idtpl);
    $template = new cApiTemplate($idtpl);

    $newidtplcfg = 0;
    $idtplcfg = cSecurity::toInteger($template->get('idtplcfg'));
    if ($idtplcfg) {
        // NB: after inserted new template, we have to update idptl
        $templateConfigColl = new cApiTemplateConfigurationCollection();
        $templateConfig = $templateConfigColl->create(0);
        $newidtplcfg = cSecurity::toInteger($templateConfig->get('idtplcfg'));
    }

    // Copy template
    $templateColl = new cApiTemplateCollection();
    $newTemplate = $templateColl->copyItem($template, array(
        'idtplcfg' => $newidtplcfg,
        'name' => sprintf(i18n("%s (Copy)"), $template->get('name')),
        'author' => cSecurity::toString($auth->auth['uname']),
        'created' => date('Y-m-d H:i:s'),
        'lastmodified' => date('Y-m-d H:i:s'),
        'defaulttemplate' => 0
    ));
    $newidtpl = cSecurity::toInteger($newTemplate->get('idtpl'));

    // Update template configuration, set idtpl width new value
    if ($idtplcfg) {
        $templateConfig->set('idtpl', $newidtpl);
        $templateConfig->store();
    }

    // Copy container from old template to new template
    $containerColl = new cApiContainerCollection();
    $containerColl->select('idtpl = ' . $idtpl . ' ORDER BY number');
    while (($container = $containerColl->next()) !== false) {
        $containerColl2 = new cApiContainerCollection();
        $containerColl2->copyItem($container, array('idtpl' => $newidtpl));
    }

    // Copy container configuration from old template configuration to new template configuration
    if ($idtplcfg) {
        $containerConfigColl = new cApiContainerConfigurationCollection();
        $containerConfigColl->select('idtplcfg = ' . $idtplcfg . ' ORDER BY number');
        while (($containerConfig = $containerConfigColl->next()) !== false) {
            $containerConfigColl2 = new cApiContainerConfigurationCollection();
            $containerConfigColl2->copyItem($containerConfig, array('idtplcfg' => $newidtplcfg));
        }
    }

    cInclude('includes', 'functions.rights.php');
    copyRightsForElement('tpl', $idtpl, $newidtpl);

    return $newidtpl;
}

/**
 * Checks if a template is in use
 *
 * @param int $idtpl
 *         Template ID
 * @return bool
 *         is template in use
 */
function tplIsTemplateInUse($idtpl) {
    global $cfg, $client, $lang;

    $db = cRegistry::getDb();
    // Check categorys
    $sql = "SELECT
                   b.idcatlang, b.name, b.idlang, b.idcat
            FROM
                " . $cfg["tab"]["cat"] . " AS a,
                " . $cfg["tab"]["cat_lang"] . " AS b
            WHERE
                a.idclient  = '" . cSecurity::toInteger($client) . "' AND
                a.idcat     = b.idcat AND
                b.idtplcfg  IN (SELECT idtplcfg FROM " . $cfg["tab"]["tpl_conf"] . " WHERE idtpl = '" . $idtpl . "')
            ORDER BY b.idlang ASC, b.name ASC ";
    $db->query($sql);
    if ($db->numRows() > 0) {
        return true;
    }

    // Check articles
    $sql = "SELECT
                   b.idartlang, b.title, b.idlang, b.idart
            FROM
                " . $cfg["tab"]["art"] . " AS a,
                " . $cfg["tab"]["art_lang"] . " AS b
            WHERE
                a.idclient  = '" . cSecurity::toInteger($client) . "' AND
                a.idart     = b.idart AND
                b.idtplcfg IN (SELECT idtplcfg FROM " . $cfg["tab"]["tpl_conf"] . " WHERE idtpl = '" . $idtpl . "')
            ORDER BY b.idlang ASC, b.title ASC ";

    $db->query($sql);

    if ($db->numRows() > 0) {
        return true;
    }

    return false;
}

/**
 * Get used datas if a template is in use
 *
 * @param int $idtpl
 *         Template ID
 * @return array
 *         category name, article name
 */
function tplGetInUsedData($idtpl) {
    global $cfg, $client, $lang;

    $db = cRegistry::getDb();

    $aUsedData = array();

    // Check categorys
    $sql = "SELECT
                   b.idcatlang, b.name, b.idlang, b.idcat
            FROM
                " . $cfg["tab"]["cat"] . " AS a,
                " . $cfg["tab"]["cat_lang"] . " AS b
            WHERE
                a.idclient  = '" . cSecurity::toInteger($client) . "' AND
                a.idcat     = b.idcat AND
                b.idtplcfg  IN (SELECT idtplcfg FROM " . $cfg["tab"]["tpl_conf"] . " WHERE idtpl = '" . $idtpl . "')
            ORDER BY b.idlang ASC, b.name ASC ";
    $db->query($sql);
    if ($db->numRows() > 0) {
        while ($db->nextRecord()) {
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
                " . $cfg["tab"]["art"] . " AS a,
                " . $cfg["tab"]["art_lang"] . " AS b
            WHERE
                a.idclient  = '" . cSecurity::toInteger($client) . "' AND
                a.idart     = b.idart AND
                b.idtplcfg IN (SELECT idtplcfg FROM " . $cfg["tab"]["tpl_conf"] . " WHERE idtpl = '" . $idtpl . "')
            ORDER BY b.idlang ASC, b.title ASC ";

    $db->query($sql);

    if ($db->numRows() > 0) {
        while ($db->nextRecord()) {
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
 * @param int $idtplcfg
 *         Template Configuration ID
 * @return int
 *         new template configuration ID
 */
function tplcfgDuplicate($idtplcfg) {
    global $auth;

    $templateConfig = new cApiTemplateConfiguration(cSecurity::toInteger($idtplcfg));
    if (!$templateConfig->isLoaded()) {
        return 0;
    }

    // Copy template configuration
    $templateConfigColl = new cApiTemplateConfigurationCollection();
    $newTemplateConfig = $templateConfigColl->copyItem($templateConfig, array(
        'author' => (string) $auth->auth['uname'],
        'created' => date('Y-m-d H:i:s'),
        'lastmodified' => date('Y-m-d H:i:s'),
    ));
    $newidtplcfg = $newTemplateConfig->get('idtplcfg');

    // Copy container configuration from old template configuration to new template configuration
    if ($idtplcfg) {
        $containerConfigColl = new cApiContainerConfigurationCollection();
        $containerConfigColl->select('idtplcfg = ' . $idtplcfg . ' ORDER BY number');
        while (($containerConfig = $containerConfigColl->next()) !== false) {
            $containerConfigColl2 = new cApiContainerConfigurationCollection();
            $containerConfigColl2->copyItem($containerConfig, array('idtplcfg' => $newidtplcfg));
        }
    }

    return $newidtplcfg;
}

/**
 * This function fills in modules automatically using this logic:
 *
 * - If the container mode is fixed, insert the named module (if exists)
 * - If the container mode is mandatory, insert the "default" module (if exists)
 *
 * @todo The default module is only inserted in mandatory mode if the container
 *        is empty. We need a better logic for handling "changes".
 *
 * @param int $idtpl
 * @return boolean
 */
function tplAutoFillModules($idtpl) {
    global $cfg, $db_autofill, $containerinf, $_autoFillcontainerCache;

    if (!is_object($db_autofill)) {
        $db_autofill = cRegistry::getDb();
    }

    $sql = "SELECT idlay FROM " . $cfg["tab"]["tpl"] . " WHERE idtpl = '" . cSecurity::toInteger($idtpl) . "'";
    $db_autofill->query($sql);

    if (!$db_autofill->nextRecord()) {
        return false;
    }

    $idlay = $db_autofill->f("idlay");

    if (!(is_array($containerinf) && array_key_exists($idlay, $containerinf) && array_key_exists($idlay, $_autoFillcontainerCache))) {
        $_autoFillcontainerCache[$idlay] = tplGetContainerNumbersInLayout($idlay);
    }

    $containerNumbers = $_autoFillcontainerCache[$idlay];

    $db = cRegistry::getDb();

    foreach ($containerNumbers as $containerNr) {
        $currContainerInfo = $containerinf[$idlay][$containerNr];

        switch ($currContainerInfo["mode"]) {
            /* Fixed mode */
            case "fixed":
                if ($currContainerInfo["default"] != "") {
                    $sql = "SELECT idmod FROM " . $cfg["tab"]["mod"] . " WHERE name = '" . $db->escape($currContainerInfo["default"]) . "'";
                    $db_autofill->query($sql);

                    if ($db_autofill->nextRecord()) {
                        $idmod = $db_autofill->f("idmod");

                        $sql = "SELECT idcontainer FROM " . $cfg["tab"]["container"] . " WHERE idtpl = '" . cSecurity::toInteger($idtpl) . "' AND number = '" . cSecurity::toInteger($containerNr) . "'";
                        $db_autofill->query($sql);

                        if ($db_autofill->nextRecord()) {
                            $sql = "UPDATE " . $cfg["tab"]["container"] .
                                    " SET idmod = '" . cSecurity::toInteger($idmod) . "' WHERE idtpl = '" . cSecurity::toInteger($idtpl) . "'" .
                                    " AND number = '" . cSecurity::toInteger($containerNr) . "' AND " .
                                    " idcontainer = '" . cSecurity::toInteger($db_autofill->f("idcontainer")) . "'";
                            $db_autofill->query($sql);
                        } else {
                            $sql = "INSERT INTO " . $cfg["tab"]["container"] . " (idtpl, number, idmod) " .
                                    " VALUES ('$idtpl', '$containerNr', '$idmod')";
                            $db_autofill->query($sql);
                        }
                    }
                }

            case "mandatory":

                if ($currContainerInfo["default"] != "") {
                    $sql = "SELECT idmod FROM " . $cfg["tab"]["mod"] . " WHERE name = '" . $db->escape($currContainerInfo["default"]) . "'";
                    $db_autofill->query($sql);

                    if ($db_autofill->nextRecord()) {
                        $idmod = $db_autofill->f("idmod");

                        $sql = "SELECT idcontainer FROM " . $cfg["tab"]["container"] . " WHERE idtpl = '" . cSecurity::toInteger($idtpl) . "' AND number = '" . cSecurity::toInteger($containerNr) . "'";
                        $db_autofill->query($sql);

                        if ($db_autofill->nextRecord()) {
                            // donut
                        } else {
                            $sql = "INSERT INTO " . $cfg["tab"]["container"] . " (idtpl, number, idmod) " .
                                    " VALUES ('" . cSecurity::toInteger($idtpl) . "', '" . cSecurity::toInteger($containerNr) . "', '" . cSecurity::toInteger($idmod) . "')";
                            $db_autofill->query($sql);
                        }
                    }
                }
        }
    }
}

/**
 * Takes over send container configuration data, stores send data (via POST) by article
 * or template configuration in container configuration table.
 *
 * @param int $idtpl
 * @param int $idtplcfg
 * @param array $postData
 *         Usually $_POST
 */
function tplProcessSendContainerConfiguration($idtpl, $idtplcfg, array $postData) {

    $containerColl = new cApiContainerCollection();
    $containerConfColl = new cApiContainerConfigurationCollection();
    $containerData = array();

    // Get all container numbers, loop through them and collect send container data
    $containerNumbers = $containerColl->getNumbersByTemplate($idtpl);
    foreach ($containerNumbers as $number) {
        $CiCMS_VAR = 'C' . $number . 'CMS_VAR';

        if (isset($postData[$CiCMS_VAR]) && is_array($postData[$CiCMS_VAR])) {
            if (!isset($containerData[$number])) {
                $containerData[$number] = '';
            }
            foreach ($postData[$CiCMS_VAR] as $key => $value) {
                $containerData[$number] = cApiContainerConfiguration::addContainerValue($containerData[$number], $key, $value);
            }
        }
    }

    // Update/insert in container_conf
    if (count($containerData) > 0) {
        // Delete all containers
        $containerConfColl->deleteBy('idtplcfg', (int) $idtplcfg);

        // Insert new containers
        foreach ($containerData as $col => $val) {
            $containerConfColl->create($idtplcfg, $col, $val);
        }
    }

}
