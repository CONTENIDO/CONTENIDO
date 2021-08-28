<?php

/**
 * This file contains the CONTENIDO template functions.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Olaf Niemann
 * @author           Jan Lengowski
 * @author           Munkh-Ulzii Balidar
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
 * @param int $changelayout 1 if layout has changed, 0 if layout has not changed
 * @param int $idtpl
 * @param string $name
 * @param string $description
 * @param int $idlay
 * @param int[]|null $c Array of container id (key) and set module ids (value)
 * @param mixed|int $default 1 if template is defined as standard template
 *
 * @return number|Ambigous <mixed, bool, multitype:>
 *
 * @throws cDbException
 * @throws cException
 * @throws cInvalidArgumentException
 */
function tplEditTemplate($changelayout, $idtpl, $name, $description, $idlay, $c, $default) {
    global $db, $auth, $client, $cfg;

    $author = (string) $auth->auth['uname'];

    if (!is_array($c)) {
        $c = [];
    }

    $template = new cApiTemplate();
    /*CON-2545: load template by id and not by its name */
    $template->loadByMany(array('idclient' => $client, 'idtpl' => $idtpl));

    if ($template->isLoaded() && $template->get('idtpl') != $idtpl) {
        cRegistry::addErrorMessage(i18n("Template name already exists"));
        return -1;
    }

    if (true === cRegistry::getConfigValue('simulate_magic_quotes')) {
        $name = stripslashes($name);
        $description = stripslashes($description);
    }

    if (!$idtpl) {
        // Insert new entry in the template table
        $templateColl = new cApiTemplateCollection();
        $template = $templateColl->create($client, $idlay, 0, $name, $description, 1, 0, 0);
        $idtpl = $template->get('idtpl');

        // Insert new entry in the template configuration table
        $templateConfColl = new cApiTemplateConfigurationCollection();
        $templateConf = $templateConfColl->create($idtpl);
        $idtplcfg = $templateConf->get('idtplcfg');

        // Update new idtplconf
        $template->set('idtplcfg', $idtplcfg);
        $template->store();

        // Set correct rights for element
        cRights::createRightsForElement('tpl', $idtpl);
    } else {
        // Update existing entry in the template table
        $template = new cApiTemplate($idtpl);
        $template->set('name', $name);
        $template->set('description', $description);
        $template->set('idlay', $idlay);
        $template->set('author', $author);
        $template->set('lastmodified', date('Y-m-d H:i:s'));
        $template->store();

        // Delete all container assigned to this template
        $containerColl = new cApiContainerCollection();
        $containerColl->clearAssignments($idtpl);

        if ((int) $changelayout !== 1) {
            foreach ($c as $idcontainer => $idmodule) {
                $containerColl2 = new cApiContainerCollection();
                $containerColl2->create($idtpl, $idcontainer, $c[$idcontainer]);
            }
        }

        // Generate code
        conGenerateCodeForAllArtsUsingTemplate($idtpl);
    }

    if ($default == 1) {
        $sql = "UPDATE `%s` SET `defaulttemplate` = 0 WHERE `idclient` = %d AND `idtpl` != %d";
        $db->query($sql, $cfg["tab"]["tpl"], $client, $template->get('idtpl'));

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
 * Delete a template and all related data (template configuration, container, and container configuration)
 *
 * @param int $idtpl
 *         ID of the template to duplicate
 *
 * @throws cDbException
 * @throws cInvalidArgumentException
 */
function tplDeleteTemplate($idtpl) {
    $idtpl = cSecurity::toInteger($idtpl);

    // Delete template
    $templateColl = new cApiTemplateCollection();
    $templateColl->delete($idtpl);

    // Delete container
    $containerColl = new cApiContainerCollection();
    $containerColl->deleteBy('idtpl', $idtpl);

    // Retrieve template configuration ids
    $templateConfigColl = new cApiTemplateConfigurationCollection();
    $idsToDelete = $templateConfigColl->getIdsByWhereClause("idtpl = $idtpl");

    // Delete template configuration
    $templateConfigColl->deleteBy('idtpl', $idtpl);

    // Delete container configuration
    $containerConfigColl = new cApiContainerConfigurationCollection();
    $containerConfigColl->deleteByWhereClause('idtplcfg IN (' . implode(', ', $idsToDelete) . ')');

    // Delete rights
    cRights::deleteRightsForElement("tpl", $idtpl);
}

/**
 * Browse a specific layout for containers
 *
 * @param $idlay
 *
 * @return string
 *         &-separated string of all containers
 *
 * @throws cInvalidArgumentException
 */
function tplBrowseLayoutForContainers($idlay) {
    global $cfg, $containerinf, $lang;

    $layoutInFile = new cLayoutHandler($idlay, '', $cfg, $lang);
    $code = $layoutInFile->getLayoutCode();

    $containerNumbers = [];

    preg_match_all("/CMS_CONTAINER\[([0-9]*)\]/", $code, $containerMatches);
    $posBody = cString::findFirstPosCI($code, '<body>');
    $codeBeforeHeader = cString::getPartOfString($code, 0, $posBody);

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
 *
 * @return array
 *
 * @throws cInvalidArgumentException
 */
function tplGetContainerNumbersInLayout($idlay) {
    $containerNumbers = [];

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
 * @param int $idlay
 *         Layout number to browse
 * @param int $container
 *         Container number
 * @return string|null
 *         Container name or null
 */
function tplGetContainerName($idlay, $container) {
    global $containerinf;

    if (is_array($containerinf[$idlay])) {
        if (array_key_exists($container, $containerinf[$idlay])) {
            return $containerinf[$idlay][$container]["name"];
        }
    }
}

/**
 * Retrieve the container mode
 *
 * @param int $idlay
 *         Layout number to browse
 * @param int $container
 *         Container number
 * @return string|null
 *         Container name or null
 */
function tplGetContainerMode($idlay, $container) {
    global $containerinf;

    if (is_array($containerinf[$idlay])) {
        if (array_key_exists($container, $containerinf[$idlay])) {
            return $containerinf[$idlay][$container]["mode"];
        }
    }
}

/**
 * Retrieve the allowed container types
 *
 * @param int $idlay
 *         Layout number to browse
 * @param int $container
 *         Container number
 * @return array
 *         Allowed container types
 */
function tplGetContainerTypes($idlay, $container) {
    global $containerinf;

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

    return [];
}

/**
 * Retrieve the default module
 *
 * @param int $idlay
 *         Layout number to browse
 * @param int $container
 *         Container number
 * @return string|null
 *         Default module name or null
 */
function tplGetContainerDefault($idlay, $container) {
    global $containerinf;

    if (is_array($containerinf[$idlay])) {
        if (array_key_exists($container, $containerinf[$idlay])) {
            return $containerinf[$idlay][$container]["default"];
        }
    }
}

/**
 * Preparse the layout for caching purposes
 *
 * @param int $idlay
 *         Layout number to browse
 *
 * @throws cInvalidArgumentException
 */
function tplPreparseLayout($idlay) {
    global $cfg, $containerinf, $lang;

    $layoutInFile = new cLayoutHandler($idlay, '', $cfg, $lang);
    $code = $layoutInFile->getLayoutCode();

    $parser = new HtmlParser($code);
    $bIsBody = false;

    if (!is_array($containerinf)) {
        $containerinf = [];
    }
    if (!is_array($containerinf[$idlay])) {
        $containerinf[$idlay] = [];
    }

    while ($parser->parse()) {
        if (cString::toLowerCase($parser->getNodeName()) == 'body') {
            $bIsBody = true;
        }

        if ($parser->getNodeName() == 'container' && $parser->getNodeType() == HtmlParser::NODE_TYPE_ELEMENT) {
            $idcontainer = $parser->getNodeAttributes('id');

            $mode = $parser->getNodeAttributes('mode');
            if ($mode == '') {
                $mode = 'optional';
            }

            $containerinf[$idlay][$idcontainer] = [
                'name' => $parser->getNodeAttributes('name'),
                'mode' => $mode,
                'default' => $parser->getNodeAttributes('default'),
                'types' => $parser->getNodeAttributes('types'),
                'is_body' => $bIsBody,
            ];
        }
    }
}

/**
 * Duplicate a template
 *
 * @param int $idtpl
 *         ID of the template to duplicate
 *
 * @return int
 *         ID of the duplicated template
 * @throws cDbException
 * @throws cException
 * @throws cInvalidArgumentException
 */
function tplDuplicateTemplate($idtpl) {
    global $auth;

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
    $newTemplate = $templateColl->copyItem($template, [
        'idtplcfg' => $newidtplcfg,
        'name' => sprintf(i18n("%s (Copy)"), $template->get('name')),
        'author' => cSecurity::toString($auth->auth['uname']),
        'created' => date('Y-m-d H:i:s'),
        'lastmodified' => date('Y-m-d H:i:s'),
        'defaulttemplate' => 0
    ]);
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
        $containerColl2->copyItem($container, ['idtpl' => $newidtpl]);
    }

    // Copy container configuration from old template configuration to new template configuration
    if ($idtplcfg) {
        $containerConfigColl = new cApiContainerConfigurationCollection();
        $containerConfigColl->select('idtplcfg = ' . $idtplcfg . ' ORDER BY number');
        while (($containerConfig = $containerConfigColl->next()) !== false) {
            $containerConfigColl2 = new cApiContainerConfigurationCollection();
            $containerConfigColl2->copyItem($containerConfig, ['idtplcfg' => $newidtplcfg]);
        }
    }

    cRights::copyRightsForElement('tpl', $idtpl, $newidtpl);

    return $newidtpl;
}

/**
 * Checks if a template is in use
 *
 * @param int $idtpl
 *         Template ID
 *
 * @return bool
 *         is template in use
 *
 * @throws cDbException
 */
function tplIsTemplateInUse($idtpl) {
    global $cfg, $client;

    $db = cRegistry::getDb();

    // Check categories
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
 * Get used data if a template is in use
 *
 * @param int $idtpl
 *         Template ID
 *
 * @return array
 *         category name, article name
 *
 * @throws cDbException
 */
function tplGetInUsedData($idtpl) {
    global $cfg, $client;

    $db = cRegistry::getDb();

    $aUsedData = [];

    // Check categories
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
            $aUsedData['cat'][] = [
                'name' => $db->f('name'),
                'lang' => $db->f('idlang'),
                'idcat' => $db->f('idcat'),
            ];
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
            $aUsedData['art'][] = [
                'title' => $db->f('title'),
                'lang' => $db->f('idlang'),
                'idart' => $db->f('idart'),
            ];
        }
    }

    return $aUsedData;
}

/**
 * Copies a complete template configuration
 *
 * @param int $idtplcfg
 *         Template Configuration ID
 *
 * @return int
 *         new template configuration ID
 *
 * @throws cDbException
 * @throws cException
 * @throws cInvalidArgumentException
 */
function tplcfgDuplicate($idtplcfg) {
    global $auth;

    $idtplcfg = cSecurity::toInteger($idtplcfg);
    $templateConfig = new cApiTemplateConfiguration($idtplcfg);
    if (!$templateConfig->isLoaded()) {
        return 0;
    }

    // Copy template configuration
    $templateConfigColl = new cApiTemplateConfigurationCollection();
    $newTemplateConfig = $templateConfigColl->copyItem($templateConfig, [
        'author' => (string) $auth->auth['uname'],
        'created' => date('Y-m-d H:i:s'),
        'lastmodified' => date('Y-m-d H:i:s'),
    ]);
    $newidtplcfg = $newTemplateConfig->get('idtplcfg');

    // Copy container configuration from old template configuration to new template configuration
    if ($idtplcfg) {
        $containerConfigColl = new cApiContainerConfigurationCollection();
        $containerConfigColl->select('idtplcfg = ' . $idtplcfg . ' ORDER BY number');
        while (($containerConfig = $containerConfigColl->next()) !== false) {
            $containerConfigColl2 = new cApiContainerConfigurationCollection();
            $containerConfigColl2->copyItem($containerConfig, ['idtplcfg' => $newidtplcfg]);
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
 * @todo Default module is only inserted in mandatory mode if container is empty. We need a better logic for handling "changes".
 *
 * @param int $idtpl
 *
 * @return bool
 *
 * @throws cDbException
 * @throws cInvalidArgumentException
 */
function tplAutoFillModules($idtpl) {
    global $cfg, $db_autofill, $containerinf, $_autoFillContainerCache;

    $idtpl = cSecurity::toInteger($idtpl);

    if (!is_object($db_autofill)) {
        $db_autofill = cRegistry::getDb();
    }

    // Get layout id
    $db_autofill->query("SELECT idlay FROM `%s` WHERE idtpl = %d", $cfg["tab"]["tpl"], $idtpl);
    if (!$db_autofill->nextRecord()) {
        return false;
    }
    $idlay = cSecurity::toInteger($db_autofill->f("idlay"));

    // Get container numbers
    if (!(is_array($containerinf) && array_key_exists($idlay, $containerinf) && array_key_exists($idlay, $_autoFillContainerCache))) {
        if (!is_array($_autoFillContainerCache)) {
            $_autoFillContainerCache = [];
    }
        $_autoFillContainerCache[$idlay] = tplGetContainerNumbersInLayout($idlay);
    }
    $containerNumbers = $_autoFillContainerCache[$idlay];

    foreach ($containerNumbers as $containerNr) {
        $currContainerInfo = $containerinf[$idlay][$containerNr];

        switch ($currContainerInfo["mode"]) {
            // Fixed mode
            case "fixed":
                if ($currContainerInfo["default"] != "") {
                    $db_autofill->query(
                        "SELECT idmod FROM `%s` WHERE name = '%s'", $cfg["tab"]["mod"], $currContainerInfo["default"]
                    );

                    if ($db_autofill->nextRecord()) {
                        $idmod = $db_autofill->f("idmod");

                        // Load container by idtpl and number
                        $containerColl = new cApiContainerCollection();
                        $containerColl->select('idtpl = ' . $idtpl . ' AND number = ' . cSecurity::toInteger($containerNr));
                        $container = $containerColl->next();
                        if ($container === false) {
                            // Create new container
                            $containerColl->create($idtpl, cSecurity::toInteger($containerNr), cSecurity::toInteger($idmod));
                        } else {
                            // Update existing container
                            $container->set('idmod', cSecurity::toInteger($idmod));
                            $container->store();
                        }
                    }
                }
                break;

            // Mandatory mode
            case "mandatory":
                if ($currContainerInfo["default"] != "") {
                    $db_autofill->query(
                        "SELECT idmod FROM `%s` WHERE name = '%s'", $cfg["tab"]["mod"], $currContainerInfo["default"]
                    );

                    if ($db_autofill->nextRecord()) {
                        $idmod = $db_autofill->f("idmod");

                        // Load container by idtpl and number
                        $containerColl = new cApiContainerCollection();
                        $containerColl->select('idtpl = ' . $idtpl . ' AND number = ' . cSecurity::toInteger($containerNr));
                        $container = $containerColl->next();
                        // Create new container if not exists, don't update existing container
                        if ($container === false) {
                            $containerColl->create($idtpl, cSecurity::toInteger($containerNr), cSecurity::toInteger($idmod));
                        }
                    }
                }
                break;
        }
    }
}

/**
 * Takes over send container configuration data, stores send data (via POST) by article
 * or template configuration in container configuration table.
 *
 * @param int   $idtpl
 * @param int   $idtplcfg
 * @param array $postData
 *         Usually $_POST
 *
 * @throws cDbException
 * @throws cException
 * @throws cInvalidArgumentException
 */
function tplProcessSendContainerConfiguration($idtpl, $idtplcfg, array $postData) {
    $containerColl = new cApiContainerCollection();
    $containerConfColl = new cApiContainerConfigurationCollection();
    $containerData = [];

    // Get all container numbers, loop through them and collect send container data
    $containerNumbers = $containerColl->getNumbersByTemplate($idtpl);
    foreach ($containerNumbers as $number) {
        $CiCMS_VAR = 'C' . $number . 'CMS_VAR';

        if (!isset($containerData[$number])) {
            $containerData[$number] = '';
        }
        if (isset($postData[$CiCMS_VAR]) && is_array($postData[$CiCMS_VAR])) {
            foreach ($postData[$CiCMS_VAR] as $key => $value) {
                $containerData[$number] = cApiContainerConfiguration::addContainerValue($containerData[$number], $key, $value);
            }
        }
    }

    // Update/insert in container_conf
    if (count($containerData) > 0) {
        // Delete all containers
        $containerConfColl->deleteBy('idtplcfg', cSecurity::toInteger($idtplcfg));

        // Insert new containers
        foreach ($containerData as $col => $val) {
            $containerConfColl->create($idtplcfg, $col, $val);
        }
    }
}
