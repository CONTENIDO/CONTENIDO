<?php

/**
 * This file contains the CONTENIDO layout functions.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Jan Lengowski
 * @author     Olaf Niemann
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
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
 * @return int Id of the new or edited layout
 * @throws cDbException|cException|cInvalidArgumentException
 */
function layEditLayout($idlay, $name, $description, $code)
{
    $db = cRegistry::getDb();
    $auth = cRegistry::getAuth();
    $cfg = cRegistry::getConfig();
    $cfgClient = cRegistry::getClientConfig();
    $client = cRegistry::getClientId();
    $lang = cRegistry::getLanguageId();
    $area = cRegistry::getArea();
    $frame = cRegistry::getFrame();

    $date = date('Y-m-d H:i:s');
    $author = $auth->getUsername();
    if (true === cRegistry::getConfigValue('simulate_magic_quotes')) {
        $name = stripslashes($name);
        $description = stripslashes($description);
        $code = stripslashes($code);
    }

    if (cString::getStringLength(trim($name)) == 0) {
        $name = i18n('-- Unnamed layout --');
    }

    // Replace all not allowed characters..
    $layoutAlias = cModuleHandler::getCleanName(cString::toLowerCase($name));

    // Constructor for the layout in filesystem
    $layoutHandler = new cLayoutHandler($idlay, $code, $cfg, $lang);

    // Track version
    $oVersion = new cVersionLayout($idlay, $cfg, $cfgClient, $db, $client, $area, $frame);
    // Save layout from file and not from db
    $oVersion->setCode($layoutHandler->getLayoutCode());
    // Create new Layout Version in cms/version/layout/
    $oVersion->createNewVersion();

    if (!$idlay) {
        $layoutCollection = new cApiLayoutCollection();
        $layout = $layoutCollection->create($name, $client, $layoutAlias, $description, '1', $author);
        $idlay = $layout->get('idlay');

        if (!$layoutHandler->saveLayout($code)) {
            cRegistry::addErrorMessage(i18n("Can't save layout in file"));
        } else {
            cRegistry::addOkMessage(i18n("Saved layout successfully!"));
        }

        // Set correct rights for element
        cRights::createRightsForElement('lay', $idlay);

        return $idlay;
    } else {
        // Save the layout in file system
        $layoutHandler = new cLayoutHandler($idlay, $code, $cfg, $lang);
        // Name changed
        if ($layoutAlias != $layoutHandler->getLayoutName()) {
            if (cLayoutHandler::existLayout($layoutAlias, $cfgClient, $client)) {
                if (!$layoutHandler->saveLayout($code)) {
                    cRegistry::addErrorMessage(i18n("Can't save layout in file!"));
                }

                // Display error
                cRegistry::addErrorMessage(i18n("Can't rename the layout!"));
                die();
            }

            // Rename the directory
            if ($layoutHandler->rename($layoutHandler->getLayoutName(), $layoutAlias)) {
                if (!$layoutHandler->saveLayout($code)) {
                    cRegistry::addWarningMessage(sprintf(
                        i18n("The file %s has no write permissions. Saving only database changes!"),
                        $layoutHandler->_getFileName()
                    ));
                } else {
                    cRegistry::addOkMessage(i18n("Renamed layout successfully!"));
                }
                $layout = new cApiLayout(cSecurity::toInteger($idlay));
                $layout->set('name', $name);
                $layout->set('alias', $layoutAlias);
                $layout->set('description', $description);
                $layout->set('author', $author);
                $layout->set('lastmodified', $date);
                $layout->store();
            } else {
                // Rename not successfully, save layout
                if (!$layoutHandler->saveLayout($code)) {
                    cRegistry::addErrorMessage(i18n("Can't save layout file!"));
                }
            }
        } else {
            // Name dont changed
            if (!$layoutHandler->saveLayout($code)) {
                cRegistry::addWarningMessage(sprintf(
                    i18n("The file %s has no write permissions. Saving only database changes!"),
                    $layoutHandler->_getFileName()
                ));
            } else {
                cRegistry::addOkMessage(i18n("Saved layout successfully!"));
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
 * @param int $idlay The ID of the layout
 * @return string An error code if the layout is still in use or empty string
 * @throws cDbException|cException|cInvalidArgumentException
 */
function layDeleteLayout($idlay): string
{
    $cfg = cRegistry::getConfig();
    $cfgClient = cRegistry::getClientConfig();
    $client = cRegistry::getClientId();

    $tplColl = new cApiTemplateCollection();
    $tplColl->select('`idlay`=' . $idlay);
    if ($tplColl->next()) {
        // layout is still in use, you cannot delete it
        return '0301';
    }

    // delete the layout in file system
    $layoutHandler = new cLayoutHandler($idlay, '', $cfg, 1);
    if ($layoutHandler->eraseLayout()) {
        $layoutFile = $cfgClient[$client]['version']['path'] . "layout" . DIRECTORY_SEPARATOR . $idlay;
        if (cFileHandler::exists($layoutFile)) {
            cDirHandler::recursiveRmdir($layoutFile);
        }

        // delete layout in database
        $layoutCollection = new cApiLayoutCollection();
        $layoutCollection->delete($idlay);
    } else {
        cRegistry::addErrorMessage(i18n("Can't delete layout!"));
    }

    // Delete rights for element
    cRights::deleteRightsForElement('lay', $idlay);

    return '';
}
