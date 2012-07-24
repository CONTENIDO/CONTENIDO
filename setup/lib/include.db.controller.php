<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Creates/Updates the database tables and fills them with entries (depending on
 * selected options during setup process)
 *
 * Requirements:
 * @con_php_req 5
 *
 * @package    CONTENIDO setup
 * @version    0.2.6
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 *
 * {@internal
 *   created  unknown
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

global $db;

checkAndInclude($cfg['path']['contenido'] . 'includes/functions.database.php');
checkAndInclude($cfg['path']['contenido'] . 'includes/functions.general.php');

$db = getSetupMySQLDBConnection(false);

if (checkMySQLDatabaseCreation($db, $_SESSION['dbname'])) {
    $db = getSetupMySQLDBConnection();
}

$currentStep = (isset($_GET['step']) && (int) $_GET['step'] > 0) ? (int) $_GET['step'] : 0;

if ($currentStep == 0) {
    $currentStep = 1;
}

$count = 0;
$fullCount = 0;

// Count DB Chunks
$file = fopen('data/tables.txt', 'r');
$step = 1;
while (($data = fgetcsv($file, 4000, ';')) !== false) {
    if ($count == C_SETUP_MAX_CHUNKS_PER_STEP) {
        $count = 1;
        $step++;
    }

    if ($currentStep == $step) {
        if ($data[7] == '1') {
            $drop = true;
        } else {
            $drop = false;
        }
        dbUpgradeTable($db, $cfg['sql']['sqlprefix'] . '_' . $data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6], '', $drop);

        if ($db->Errno != 0) {
            $_SESSION['install_failedupgradetable'] = true;
        }
    }

    $count++;
    $fullCount++;
}

// Count DB Chunks (plugins)
$file = fopen('data/tables_pi.txt', 'r');
$step = 1;
while (($data = fgetcsv($file, 4000, ';')) !== false) {
    if ($count == C_SETUP_MAX_CHUNKS_PER_STEP) {
        $count = 1;
        $step++;
    }

    if ($currentStep == $step) {
        if ($data[7] == '1') {
            $drop = true;
        } else {
            $drop = false;
        }
        dbUpgradeTable($db, $cfg['sql']['sqlprefix'] . '_' . $data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6], '', $drop);

        if ($db->Errno != 0) {
            $_SESSION['install_failedupgradetable'] = true;
        }
    }

    $count++;
    $fullCount++;
}

$pluginChunks = array();

$baseChunks = explode("\n", cFileHandler::read('data/base.txt'));

$clientChunks = explode("\n", cFileHandler::read('data/client.txt'));

$moduleChunks = explode("\n", cFileHandler::read('data/standard.txt'));

$contentChunks = explode("\n", cFileHandler::read('data/examples.txt'));

$sysadminChunk = explode("\n", cFileHandler::read('data/sysadmin.txt'));

if ($_SESSION['plugin_newsletter'] == 'true') {
    $newsletter = explode("\n", cFileHandler::read('data/plugin_newsletter.txt'));
    $pluginChunks = array_merge($pluginChunks, $newsletter);
}

if ($_SESSION['plugin_content_allocation'] == 'true') {
    $tagging = explode("\n", cFileHandler::read('data/plugin_content_allocation.txt'));
    $pluginChunks = array_merge($pluginChunks, $tagging);
}

if ($_SESSION['plugin_mod_rewrite'] == 'true') {
    $mod_rewrite = explode("\n", cFileHandler::read('data/plugin_mod_rewrite.txt'));
    $pluginChunks = array_merge($pluginChunks, $mod_rewrite);
}

if ($_SESSION['plugin_cronjob_overview'] == 'true') {
    $cronjob_overview = explode("\n", cFileHandler::read('data/plugin_cronjob_overview.txt'));
    $pluginChunks = array_merge($pluginChunks, $cronjob_overview);
}

list($rootPath, $rootHttpPath) = getSystemDirectories();

if ($_SESSION['setuptype'] == 'setup') {
    switch ($_SESSION['clientmode']) {
        case 'CLIENTMODULES':
            $fullChunks = array_merge($baseChunks, $sysadminChunk, $clientChunks, $moduleChunks);
            break;
        case 'CLIENTEXAMPLES':
            $fullChunks = array_merge($baseChunks, $sysadminChunk, $clientChunks, $moduleChunks, $contentChunks);
            break;
        default:
            $fullChunks = array_merge($baseChunks, $sysadminChunk);
            break;
    }
} else {
    $fullChunks = $baseChunks;
}

$fullChunks = array_merge($fullChunks, $pluginChunks);

$totalSteps = ceil($fullCount / C_SETUP_MAX_CHUNKS_PER_STEP) + count($fullChunks) + 1;
foreach ($fullChunks as $fullChunk) {
    $step++;
    if ($step == $currentStep) {
        $replacements = array(
            '<!--{contenido_root}-->' => addslashes($rootPath),
            '<!--{contenido_web}-->' => addslashes($rootHttpPath)
        );

        injectSQL($db, $cfg['sql']['sqlprefix'], 'data/' . $fullChunk, $replacements);
    }
}

$percent = intval((100 / $totalSteps) * ($currentStep));

echo '<script type="text/javascript">parent.updateProgressbar(' . $percent . ');</script>';

if ($currentStep < $totalSteps) {

    printf('<script type="text/javascript">function nextStep() { window.location.href="index.php?c=db&step=%s"; };</script>', $currentStep + 1);
    if (!C_SETUP_DEBUG) {
        echo '<script type="text/javascript">window.setTimeout(nextStep, 10);</script>';
    } else {
        echo '<a href="javascript:nextStep();">Next step</a>';
    }
} else {
    // For import mod_history rows to versioning
    if ($_SESSION['setuptype'] == 'migration' || $_SESSION['setuptype'] == 'upgrade') {
        $cfgClient = array();
        rereadClients();
    }

    updateContenidoVersion($db, $cfg['tab']['system_prop'], C_SETUP_VERSION);

    if (isset($_SESSION['sysadminpass']) && $_SESSION['sysadminpass'] != '') {
        updateSysadminPassword($db, $cfg['tab']['phplib_auth_user_md5'], 'sysadmin');
    }

    $db->query('DELETE FROM %s', $cfg['tab']['code']);

    // As con_code has been emptied, force code creation (on update)
    $db->query('UPDATE %s SET createcode=1', $cfg['tab']['cat_art']);

    if ($_SESSION['setuptype'] == 'migration') {
        $aClients = listClients($db, $cfg['tab']['clients']);
        foreach ($aClients as $iIdClient => $aInfo) {
            updateClientPath($db, $cfg['tab']['clients'], $iIdClient, $_SESSION['frontendpath'][$iIdClient], $_SESSION['htmlpath'][$iIdClient]);
        }
    }

    if ($_SESSION['setuptype'] == 'upgrade') {
        $sql = "SELECT * FROM " . $cfg["tab"]["lang"];
        $db->query($sql);

        while ($db->next_record()) {
            $langs[] = $db->f("idlang");
        }

        $sql = "SELECT * FROM " . $cfg["tab"]["cat_art"] . " WHERE is_start='1'";
        $db->query($sql);

        $db2 = getSetupMySQLDBConnection();

        while ($db->next_record()) {
            $startidart = $db->f("idart");
            $idcat = $db->f("idcat");

            foreach ($langs as $vlang) {
                $sql = "SELECT idartlang FROM " . $cfg["tab"]["art_lang"] . " WHERE idart='$startidart' AND idlang='$vlang'";
                $db2->query($sql);
                if ($db2->next_record()) {
                    $idartlang = $db2->f("idartlang");

                    $sql = "UPDATE " . $cfg["tab"]["cat_lang"] . " SET startidartlang='$idartlang' WHERE idcat='$idcat' AND idlang='$vlang'";
                    $db2->query($sql);
                }
            }
        }

        $sql = "UPDATE " . $cfg["tab"]["cat_art"] . " SET is_start='0'";
        $db->query($sql);
    }

    // Update Keys
    $aNothing = array();

    injectSQL($db, $cfg['sql']['sqlprefix'], 'data/indexes.sql', array(), $aNothing);

    // update to autoincrement
    addAutoIncrementToTables($db, $cfg);

    // insert or update default system properties
    updateSystemProperties($db, $cfg['tab']['system_prop']);

    if ($_SESSION['setuptype'] == 'setup') {
        switch ($_SESSION['clientmode']) {
            case 'CLIENTMODULES':
            case 'CLIENTEXAMPLES':
                global $cfgClient;
                updateClientPath($db, $cfg['tab']['clients'], 1, $rootPath . '/cms/', $rootHttpPath . '/cms/');
                break;

            default:
                break;
        }
    }

    // Makes the new concept of moduls (save the moduls to the file) save the translation
    if ($_SESSION['setuptype'] == 'upgrade' || $_SESSION['setuptype'] == 'setup') {

        // @fixme  Get rid of hacks below
        // @fixme  Logic below works only for setup, not for upgrade because of different clients and languages

        global $client, $lang, $cfgClient;  // is used in LayoutInFile below!!!
        $clientBackup = $client;
        $langBackup = $lang;
        $client = 1;
        $lang = 1;

        if ($_SESSION['setuptype'] == 'upgrade') {
            $sql = "SHOW COLUMNS FROM %s LIKE 'frontendpath'";
            $sql = sprintf($sql, $cfg['tab']['clients']);

            $db->query($sql);
            if ($db->num_rows() != 0) {
                $sql = "SELECT * FROM " . $cfg['tab']['clients'];
                $db->query($sql);

                while ($db->next_record()) {
                    updateClientCache($db->f("idclient"), $db->f("htmlpath"), $db->f("frontendpath"));
                }

                $sql = sprintf("ALTER TABLE %s DROP htmlpath", $cfg['tab']['clients']);
                $db->query($sql);

                $sql = sprintf("ALTER TABLE %s DROP frontendpath", $cfg['tab']['clients']);
                $db->query($sql);
            }
            checkAndInclude($cfg['path']['contenido_config'] . 'config.clients.php');
        }

        rereadClients();

        cModuleHandler::setEncoding('ISO-8859-1');

        //set default configuration for connection,
        //for all db objects in Contenido_UpgradeJob

        DB_Contenido::setDefaultConfiguration($cfg['db']);

        // Save all modules from db-table to the filesystem
        $contenidoUpgradeJob = new Contenido_UpgradeJob($db);
        $contenidoUpgradeJob->convertModulesToFile($_SESSION['setuptype']);

        // Save layout from db-table to the file system
        $layoutInFile = new LayoutInFile(1, '', $cfg, 1, $db);
        $layoutInFile->upgrade();

        $db2 = getSetupMySQLDBConnection();
        $sql = "SELECT * FROM " . $cfg['tab']['lay'];
        $db->query($sql);
        while ($db->next_record()) {
            if ($db->f("alias") == "") {
                $sql = "UPDATE " . $cfg['tab']['lay'] . " SET `alias`='" . $db->f("name") . "' WHERE `idlay`='" . $db->f("idlay") . "';";
                $db2->query($sql);
            }
        }

        $sql = "SELECT * FROM " . $cfg['tab']['mod'];
        $db->query($sql);
        while ($db->next_record()) {
            if ($db->f("alias") == "") {
                $sql = "UPDATE " . $cfg['tab']['mod'] . " SET `alias`='" . $db->f("name") . "' WHERE `idmod`='" . $db->f("idmod") . "';";
                $db2->query($sql);
            }
        }

        $client = $clientBackup;
        $lang = $langBackup;
        unset($clientBackup, $langBackup);
    }

    convertToDatetime($db, $cfg);

    urlDecodeTables($db);

    if ($_SESSION['setuptype'] != 'setup') {
        $done = false;
        $sSql = "SHOW COLUMNS FROM " . $cfg['tab']['upl'];
        $db->query($sSql);
        while ($db->next_record()) {
            if ($db->f("Field") == 'description') {
                $done = true;
            }
        }
        if ($done) {
            updateUpl2Meta();
        }
    }

    if ($_SESSION['setuptype'] == 'upgrade') {
        // add the column "class" to the con_type table if it does not already exist
        $classColumnExists = false;
        $sql = 'SHOW COLUMNS FROM `' . $cfg['tab']['type'] . '`';
        $db->query($sql);
        while ($db->next_record()) {
            if ($db->f('Field') == 'class') {
                $classColumnExists = true;
            }
        }
        if ($classColumnExists) {
            // if class column already exists, replace CMS_IMAGE with CMS_IMGEDITOR
            $sql = 'UPDATE `' . $cfg['tab']['type'] . '` SET `type`=\'CMS_IMGEDITOR\' WHERE `type`=\'CMS_IMAGE\'';
            $db->query($sql);
        } else {
            cTypeAddClassColumm();
        }

        /**
         * Adds the "class" column to the con_type table and inserts the according data.
         */
        function cTypeAddClassColumm() {
            global $cfg, $db;
            $sql = 'ALTER TABLE `' . $cfg['tab']['type'] . '` ADD COLUMN `class` varchar(255)';
            $db->query($sql);
            $classNames = array(
                'CMS_HTMLHEAD' => 'cContentTypeHtmlHead',
                'CMS_HTML' => 'cContentTypeHtml',
                'CMS_TEXT' => 'cContentTypeText',
                'CMS_IMG' => 'cContentTypeImg',
                'CMS_IMGDESCR' => 'cContentTypeImgDescr',
                'CMS_LINK' => 'cContentTypeLink',
                'CMS_LINKTARGET' => 'cContentTypeLinkTarget',
                'CMS_LINKDESCR' => 'cContentTypeLinkDescr',
                'CMS_HEAD' => 'cContentTypeHead',
                'CMS_SWF' => 'cContentTypeSwf',
                'CMS_LINKTITLE' => 'cContentTypeLinkTitle',
                'CMS_LINKEDIT' => 'cContentTypeLinkEdit',
                'CMS_RAWLINK' => 'cContentTypeRawLink',
                'CMS_IMGEDIT' => 'cContentTypeImgEdit',
                'CMS_IMGTITLE' => 'cContentTypeImgTitle',
                'CMS_SIMPLELINKEDIT' => 'cContentTypeSimpleLinkEdit',
                'CMS_HTMLTEXT' => 'cContentTypeHtmlText',
                'CMS_EASYIMGEDIT' => 'cContentTypeEasyImgEdit',
                'CMS_DATE' => 'cContentTypeDate',
                'CMS_TEASER' => 'cContentTypeTeaser',
                'CMS_FILELIST' => 'cContentTypeFileList',
                'CMS_IMGEDITOR' => 'cContentTypeImgEditor',
                'CMS_LINKEDITOR' => 'cContentTypeLinkEditor'
            );
            foreach ($classNames as $type => $class) {
                $sql = 'UPDATE `' . $cfg['tab']['type'] . '` SET `class`=\'' . $class . '\' WHERE `type`=\'' . $type . '\'';
                $db->query($sql);
            }
        }

        // map all content types to their IDs
        $types = array();
        $typeCollection = new cApiTypeCollection();
        $typeCollection->addResultField('idtype');
        $typeCollection->addResultField('type');
        $typeCollection->query();
        while (($typeItem = $typeCollection->next()) !== false) {
            $types[$typeItem->get('type')] = $typeItem->get('idtype');
        }

        /*
        * Convert the value of each CMS_DATE entry.
        * Old:
        * 16.07.2012
        *
        * New:
        * <?xml version="1.0" encoding="utf-8"?>
        * <date><timestamp>1342404000</timestamp><format>d.m.Y</format></date>
        */
        $contentCollection = new cApiContentCollection();
        $contentCollection->setWhere('idtype', $types['CMS_DATE']);
        $contentCollection->query();
        while (($item = $contentCollection->next()) !== false) {
            $idcontent = $item->get('idcontent');
            $oldValue = $item->get('value');
            // if the value has not the format dd.mm.yyyy, it is possibly the new format, so ignore it
            $oldValueSplitted = explode('.', $oldValue);
            if (count($oldValueSplitted) !== 3 || !checkdate($oldValueSplitted[1], $oldValueSplitted[0], $oldValueSplitted[2])) {
                continue;
            }
            // value has the format dd.mm.yyyy, so convert it to the new XML structure
            $timestamp = strtotime($oldValue);
            $xml = <<<EOT
<?xml version="1.0" encoding="utf-8"?>
<date><timestamp>$timestamp</timestamp><format>d.m.Y</format></date>
EOT;
            $item->setField('value', $xml);
            $item->store();
        }

        /*
         * Convert the value of each CMS_FILELIST entry.
        */
        $contentCollection = new cApiContentCollection();
        $contentCollection->setWhere('idtype', $types['CMS_FILELIST']);
        $contentCollection->query();
        while (($item = $contentCollection->next()) !== false) {
            $oldFilelistVal = $item->get('value');
            $oldFilelistArray = cXmlBase::xmlStringToArray($oldFilelistVal);
            // convert the whole entries
            if (isset($oldFilelistArray['directories']['dir'])) {
                $oldFilelistArray['directories'] = $oldFilelistArray['directories']['dir'];
            }
            if (isset($oldFilelistArray['incl_subdirectories'])) {
                if ($oldFilelistArray['incl_subdirectories'] == 'checked') {
                    $oldFilelistArray['incl_subdirectories'] = 'true';
                } else {
                    $oldFilelistArray['incl_subdirectories'] = 'false';
                }
            }
            if (isset($oldFilelistArray['manual'])) {
                if ($oldFilelistArray['manual'] == 'checked') {
                    $oldFilelistArray['manual'] = 'true';
                } else {
                    $oldFilelistArray['manual'] = 'false';
                }
            }
            if (isset($oldFilelistArray['incl_metadata'])) {
                if ($oldFilelistArray['incl_metadata'] == 'checked') {
                    $oldFilelistArray['incl_metadata'] = 'true';
                } else {
                    $oldFilelistArray['incl_metadata'] = 'false';
                }
            }
            if (isset($oldFilelistArray['extensions']['ext'])) {
                $oldFilelistArray['extensions'] = $oldFilelistArray['extensions']['ext'];
            }
            if (isset($oldFilelistArray['ignore_extensions'])) {
                if ($oldFilelistArray['ignore_extensions'] == 'off') {
                    $oldFilelistArray['ignore_extensions'] = 'false';
                } else {
                    $oldFilelistArray['ignore_extensions'] = 'true';
                }
            }
            if (isset($oldFilelistArray['manual_files']['file'])) {
                $oldFilelistArray['manual_files'] = $oldFilelistArray['manual_files']['file'];
            }
            $newFilelistVal = cXmlBase::arrayToXml($oldFilelistArray, null, 'filelist');
            $item->set('value', $newFilelistVal->asXML());
            $item->store();
        }


        /*
         * Convert all DB entries CMS_IMG and CMS_IMGDESCR to CMS_IMGEDITOR.
        * Old:
        * In the past, CMS_IMG saved the idupl and CMS_IMGDESCR the corresponding description.
        *
        * New:
        * Since CONTENIDO 4.9, CMS_IMGEDITOR saves the idupl and the description is saved
        * in the con_upl_meta table.
        */
        $sql = 'SELECT `idcontent`, `idartlang`, `idtype`, `typeid`, `value` FROM `' . $cfg['tab']['content'] . '` WHERE `idtype`=' . $types['CMS_IMG'] . ' OR `idtype`=' . $types['CMS_IMGDESCR'] . ' ORDER BY `typeid` ASC';
        $db->query($sql);
        $result = array();
        while ($db->next_record()) {
            // create an array in which each entry contains the data needed for converting one entry
            $idartlang = $db->f('idartlang');
            $typeid = $db->f('typeid');
            $key = $idartlang . '_' . $typeid;
            if (isset($result[$key])) {
                $subResult = $result[$key];
            } else {
                $subResult = array();
                $subResult['idartlang'] = $idartlang;
            }
            if ($db->f('idtype') == $types['CMS_IMG']) {
                $subResult['idupl'] = $db->f('value');
                $subResult['imgidcontent'] = $db->f('idcontent');
            } else if ($db->f('idtype') == $types['CMS_IMGDESCR']) {
                $subResult['description'] = $db->f('value');
                $subResult['imgdescridcontent'] = $db->f('idcontent');
            }
            $result[$key] = $subResult;
        }

        // iterate over all entries and convert each of them
        foreach ($result as $imageInfo) {
            // calculate the next unused typeid
            $sql = 'SELECT MAX(typeid) AS maxtypeid FROM `' . $cfg['tab']['content'] . '` WHERE `idartlang`=' . $imageInfo['idartlang'] . ' AND `idtype`=' . $types['CMS_IMGEDITOR'];
            $db->query($sql);
            $db->next_record();
            if ($db->f('maxtypeid') === false) {
                $nextTypeId = 1;
            } else {
                $nextTypeId = $db->f('maxtypeid') + 1;
            }
            // insert new CMS_IMGEDITOR content entry
            $contentCollection = new cApiContentCollection();
            $contentCollection->create($imageInfo['idartlang'], $types['CMS_IMGEDITOR'], $nextTypeId, $imageInfo['idupl'], '');
            // save description in con_upl_meta if it does not already exist
            $sql = 'SELECT `idlang` FROM `' . $cfg['tab']['art_lang'] . '` WHERE `idartlang`=' . $imageInfo['idartlang'];
            $db->query($sql);
            if ($db->next_record()) {
                $idlang = $db->f('idlang');
                $metaItem = new cApiUploadMeta();
                $metaItemExists = $metaItem->loadByMany(array('idupl' => $imageInfo['idupl'], 'idlang' => $idlang));
                if ($metaItemExists) {
                    // if meta item exists but there is no description, add the description
                    if ($metaItem->get('description') == '') {
                        $metaItem->set('description', $imageInfo['description']);
                        $metaItem->store();
                    }
                } else {
                    // if no meta item exists, create a new one with the description
                    $metaItemCollection = new cApiUploadMetaCollection();
                    $metaItemCollection->create($imageInfo['idupl'], $idlang, '', $imageInfo['description']);
                }
            }
            // delete old CMS_IMG and CMS_IMGDESCR content entries
            $contentCollection->delete($imageInfo['imgidcontent']);
            $contentCollection->delete($imageInfo['imgdescridcontent']);
        }

        /*
         * Convert all DB entries CMS_LINK, CMS_LINKTARGET and CMS_LINKDESCR to CMS_LINKEDITOR.
        * Old:
        * In the past, CMS_LINK saved the actual link, CMS_LINKTARGET the corresponding target and
        * CMS_LINKDESCR the corresponding link text.
        *
        * New:
        * Since CONTENIDO 4.9, CMS_LINKEDITOR contains an XML structure with all information.
        */
        $sql = 'SELECT `idcontent`, `idartlang`, `idtype`, `typeid`, `value` FROM `' . $cfg['tab']['content'] . '` WHERE `idtype`=' . $types['CMS_LINK'] . ' OR `idtype`=' . $types['CMS_LINKTARGET'] . ' OR `idtype`=' . $types['CMS_LINKDESCR'] . ' ORDER BY `typeid` ASC';
        $db->query($sql);
        $result = array();
        while ($db->next_record()) {
            // create an array in which each entry contains the data needed for converting one entry
            $idartlang = $db->f('idartlang');
            $typeid = $db->f('typeid');
            $key = $idartlang . '_' . $typeid;
            if (isset($result[$key])) {
                $subResult = $result[$key];
            } else {
                $subResult = array();
                $subResult['idartlang'] = $idartlang;
            }
            if ($db->f('idtype') == $types['CMS_LINK']) {
                $subResult['link'] = $db->f('value');
                $subResult['linkidcontent'] = $db->f('idcontent');
            } else if ($db->f('idtype') == $types['CMS_LINKTARGET']) {
                $subResult['linktarget'] = $db->f('value');
                $subResult['linktargetidcontent'] = $db->f('idcontent');
            } else if ($db->f('idtype') == $types['CMS_LINKDESCR']) {
                $subResult['linkdescr'] = $db->f('value');
                $subResult['linkdescridcontent'] = $db->f('idcontent');
            }
            $result[$key] = $subResult;
        }

        // iterate over all entries and convert each of them
        foreach ($result as $linkInfo) {
            // calculate the next unused typeid
            $sql = 'SELECT MAX(typeid) AS maxtypeid FROM `' . $cfg['tab']['content'] . '` WHERE `idartlang`=' . $linkInfo['idartlang'] . ' AND `idtype`=' . $types['CMS_LINKEDITOR'];
            $db->query($sql);
            $db->next_record();
            if ($db->f('maxtypeid') === false) {
                $nextTypeId = 1;
            } else {
                $nextTypeId = $db->f('maxtypeid') + 1;
            }
            // construct the XML structure
            $newWindow = ($linkInfo['linktarget'] == '_blank')? 'true' : 'false';
            // if link is a relative path, prepend the upload path
            if (strpos($linkInfo['link'], 'http://') == 0 || strpos($linkInfo['link'], 'www.') == 0) {
                $link = $linkInfo['link'];
            } else {
                $link = $cfgClient[$this->_client]['upl']['path'] . $linkInfo['link'];
            }
            $xml = <<<EOT
<?xml version="1.0" encoding="utf-8"?>
<linkeditor><type>external</type><externallink>{$link}</externallink><title>{$linkInfo['linkdescr']}</title><newwindow>{$newWindow}</newwindow><idart></idart><filename></filename></linkeditor>
EOT;
            // insert new CMS_LINKEDITOR content entry
            $contentCollection = new cApiContentCollection();
            $contentCollection->create($linkInfo['idartlang'], $types['CMS_LINKEDITOR'], $nextTypeId, $xml, '');

            // delete old CMS_LINK, CMS_LINKTARGET and CMS_LINKDESCR content entries
            $contentCollection->delete($linkInfo['linkidcontent']);
            $contentCollection->delete($linkInfo['linktargetidcontent']);
            $contentCollection->delete($linkInfo['linkdescridcontent']);
        }

        /*
         * Convert the value of each CMS_TEASER entry.
        * Only the format of the manual teaser settings has been changed as follows:
        * Old:
        * <manual_art>
        *   <art>6</art>
        *   <art>7</art>
        * </manual_art>
        *
        * New:
        * <manual_art><array_value>6</array_value><array_value>7</array_value></manual_art>
        */
        $contentCollection = new cApiContentCollection();
        $contentCollection->setWhere('idtype', $types['CMS_TEASER']);
        $contentCollection->query();
        while (($item = $contentCollection->next()) !== false) {
            $oldTeaserVal = $item->get('value');
            $oldTeaserArray = cXmlBase::xmlStringToArray($oldTeaserVal);
            if (!isset($oldTeaserArray['manual_art']['art'])) {
                continue;
            }
            $oldTeaserArray['manual_art'] = $oldTeaserArray['manual_art']['art'];
            $newTeaserVal = cXmlBase::arrayToXml($oldTeaserArray, null, 'teaser');
            $item->set('value', $newTeaserVal->asXML());
            $item->store();
        }

    }

    echo '
        <script type="text/javascript">
        parent.document.getElementById("installing").style.visibility="hidden";
        parent.document.getElementById("installingdone").style.visibility="visible";
        parent.document.getElementById("next").style.visibility="visible";
        function nextStep() {
            window.location.href="index.php?c=config";
        };
        </script>
    ';

    if (!C_SETUP_DEBUG) {
        echo '<script type="text/javascript">window.setTimeout(nextStep, 10);</script>';
    } else {
        echo '<a href="javascript:nextStep();">Last step</a>';
    }
}

//update description from con_upl to con_upl_meta
function updateUpl2Meta() {
    global $cfg, $client, $db;
    $client = 1;
    //get
    $aUpl = array();
    $sSql = "SELECT * FROM " . $cfg['tab']['upl'] . " WHERE idclient = " . $client . " AND `description` != '' ORDER BY idupl ASC";
    $db->query($sSql);
    while ($db->next_record()) {
        $aUpl[$db->f('idupl')]['description'] = $db->f('description');
        $aUpl[$db->f('idupl')]['author'] = $db->f('author');
        $aUpl[$db->f('idupl')]['created'] = $db->f('created');
        $aUpl[$db->f('idupl')]['lastmodified'] = $db->f('lastmodified');
        $aUpl[$db->f('idupl')]['modifiedby'] = $db->f('modifiedby');
    }
    $aLang = array();
    $sSql = "SELECT idlang FROM " . $cfg['tab']['clients_lang'] . " WHERE idclient = " . $client . " ORDER BY idlang ASC";
    $db->query($sSql);
    while ($db->next_record()) {
        $aLang[] = $db->f('idlang');
    }

    $bError = true;
    $j = 0;
    foreach ($aUpl as $idupl => $elem) {
        if ($elem['description'] != '') {
            foreach ($aLang as $idlang) {
                $aUplMeta = array();
                $sSql = "SELECT * FROM " . $cfg['tab']['upl_meta'] . " WHERE idlang = " . $idlang . "  AND idupl = " . $idupl . " ORDER BY idupl ASC";
                $db->query($sSql);
                $i = 0;
                while ($db->next_record()) {
                    $aUplMeta[$i]['description'] = $db->f('description');
                    $aUplMeta[$i]['id_uplmeta'] = $db->f('id_uplmeta');
                    $i++;
                }
                if (count($aUplMeta) < 1) {
                    //there is no entry in con_upl_meta for this upload
                    $sSql = "INSERT INTO " . $cfg['tab']['upl_meta'] . " SET
                        idupl = $idupl,
                        idlang = $idlang,
                        medianame = '',
                        description = '" . $elem['description'] . "',
                        keywords = '',
                        internal_notice = '',
                        author = '" . $elem['author'] . "',
                        created = '" . $elem['created'] . "',
                        modified = '" . $elem['lastmodified'] . "',
                        modifiedby = '" . $elem['modifiedby'] . "',
                        copyright = ''";
                } elseif (count($aUplMeta) == 1 && $aUplMeta[0]['description'] == '') {
                    //there is already an entry and the field "description" is empty
                    $sSql = "UPDATE " . $cfg['tab']['upl_meta'] . " SET
                        description = '" . $elem['description'] . "'
                        WHERE id_uplmeta = " . $aUplMeta[0]['id_uplmeta'];
                } else {
                    //there is already an entry with an exising content in "description"
                    //do nothing;
                }
                $db->query($sSql);
                if ($db->Error != 0) {
                    $bError = false;
                    echo "<pre>" . $sql . "\nMysql Error:" . $db->Error . "(" . $db->Errno . ")</pre>";
                }
            }
        }
        $j++;
    }
    //At the end remove all values of con_upl.description and drop the field from table
    if ($bError && $j == count($aUpl)) {
        $sSql = "ALTER TABLE `" . $cfg['tab']['upl'] . "` DROP `description`";
        $db->query($sSql);
        if ($db->Error != 0) {
            echo "<pre>" . $sql . "\nMysql Error:" . $db->Error . "(" . $db->Errno . ")</pre>";
        }
    } else {
        echo "<pre>error on updateUpl2Meta();" . $j . '==' . count($aUpl) . "</pre>";
    }
}

?>