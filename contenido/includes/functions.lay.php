<?php
/**
 * This file contains the CONTENIDO layout functions.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Jan Lengowski, Olaf Niemann
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.tpl.php');
cInclude('includes', 'functions.con.php');
cInclude('classes', 'class.layout.handler.php');

/**
 * Edit or Create a new layout
 *
 * @param int $idlay Id of the Layout
 * @param string $name Name of the Layout
 * @param string $description Description of the Layout
 * @param string $code Layout HTML Code
 * @return int $idlay Id of the new or edited Layout
 */
function layEditLayout($idlay, $name, $description, $code) {
    global $client, $auth, $cfg, $sess, $lang, $area_tree, $perm, $area, $frame, $cfgClient;

    $db2 = cRegistry::getDb();
    $db = cRegistry::getDb();

    $date = date('Y-m-d H:i:s');
    $author = (string) $auth->auth['uname'];
    $description = (string) stripslashes($description);
    set_magic_quotes_gpc($name);
    set_magic_quotes_gpc($description);

    set_magic_quotes_gpc($code);

    if (strlen(trim($name)) == 0) {
        $name = i18n('-- Unnamed layout --');
    }

    // Replace all not allowed characters..
    $layoutAlias = cModuleHandler::getCleanName(strtolower($name));

    // Constructor for the layout in filesystem
    $layoutInFile = new cLayoutHandler($idlay, stripslashes($code), $cfg, $lang);

    // Track version
    $oVersion = new cVersionLayout($idlay, $cfg, $cfgClient, $db, $client, $area, $frame);
    // Save layout from file and not from db
    $oVersion->setCode($layoutInFile->getLayoutCode());
    // Create new Layout Version in cms/version/layout/
    $oVersion->createNewVersion();

    if (!$idlay) {
        $layoutCollection = new cApiLayoutCollection();
        $layout = $layoutCollection->create($name, $client, $layoutAlias, $description, '1', $author);
        $idlay = $layout->get('idlay');

        if ($layoutInFile->saveLayout(stripslashes($code)) == false) {
            cRegistry::addErrorMessage(i18n("Can't save layout in file"));
        } else {
            cRegistry::addOkMessage(i18n("Saved layout succsessfully!"));
        }

        // Set correct rights for element
        cInclude('includes', 'functions.rights.php');
        createRightsForElement('lay', $idlay);

        return $idlay;
    } else {
        // Save the layout in file system
        $layoutInFile = new cLayoutHandler($idlay, stripslashes($code), $cfg, $lang);
        // Name changed
        if ($layoutAlias != $layoutInFile->getLayoutName()) {
            // Exist layout in directory
            if (cLayoutHandler::existLayout($layoutAlias, $cfgClient, $client) == true) {
                // Save in old directory
                if ($layoutInFile->saveLayout(stripslashes($code)) == false) {
                    cRegistry::addErrorMessage(i18n("Can't save layout in file!"));
                }

                // Display error
                cRegistry::addErrorMessage(i18n("Can't rename the layout!"));
                die();
            }

            // Rename the directory
            if ($layoutInFile->rename($layoutInFile->getLayoutName(), $layoutAlias)) {
                if ($layoutInFile->saveLayout(stripslashes($code)) == false) {
                    cRegistry::addWarningMessage(sprintf(i18n("The file %s has no write permissions. Saving only database changes!"), $layoutInFile->_getFileName()));
                } else {
                    cRegistry::addOkMessage(i18n("Renamed layout succsessfully!"));
                }
                $layout = new cApiLayout(cSecurity::toInteger($idlay));
                $layout->set('name', $name);
                $layout->set('alias', $layoutAlias);
                $layout->set('description', $description);
                $layout->set('author', $author);
                $layout->set('lastmodified', $date);
                $layout->store();
            } else {
                // Rename not successfully
                // Save layout
                if ($layoutInFile->saveLayout(stripslashes($code)) == false) {
                    cRegistry::addErrorMessage(i18n("Can't save layout file!"));
                }
            }
        } else {
            // Name dont changed
            if ($layoutInFile->saveLayout(stripslashes($code)) == false) {
                cRegistry::addWarningMessage(sprintf(i18n("The file %s has no write permissions. Saving only database changes!"), $layoutInFile->_getFileName()));
            } else {
                cRegistry::addOkMessage(i18n("Saved layout succsessfully!"));
            }
            $layout = new cApiLayout(cSecurity::toInteger($idlay));
            $layout->set('name', $name);
            $layout->set('alias', $layoutAlias);
            $layout->set('description', $description);
            $layout->set('author', $author);
            $layout->set('lastmodified', $date);
            $layout->store();
        }

        // Update CODE table
        conGenerateCodeForAllartsUsingLayout($idlay);

        return $idlay;
    }
}

/**
 * Deletes the layout with the given ID from the database and the file system.
 *
 * @param int $idlay the ID of the layout
 * @return string an error code if the layout is still in use
 */
function layDeleteLayout($idlay) {
    global $client, $cfg, $area_tree, $perm, $cfgClient;

    $tplColl = new cApiTemplateCollection();
    $tplColl->select('`idlay`=' . $idlay);
    if ($tplColl->next()) {
        // layout is still in use, you cannot delete it
        return '0301';
    } else {

        // delete the layout in file system
        $layoutInFile = new cLayoutHandler($idlay, '', $cfg, 1);
        if ($layoutInFile->eraseLayout()) {
            if (cFileHandler::exists($cfgClient[$client]['version']['path'] . "layout" . DIRECTORY_SEPARATOR . $idlay)) {
                cDirHandler::recursiveRmdir($cfgClient[$client]['version']['path'] . "layout" . DIRECTORY_SEPARATOR . $idlay);
            }

            // delete layout in database
            $layoutCollection = new cApiLayoutCollection();
            $layoutCollection->delete($idlay);
        } else {
            cRegistry::addErrorMessage(i18n("Can't delete layout!"));
        }
    }

    // Delete rights for element
    cInclude('includes', 'functions.rights.php');
    deleteRightsForElement('lay', $idlay);
}
