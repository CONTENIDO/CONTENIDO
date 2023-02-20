<?php

/**
 * This file contains the CONTENIDO module functions.
 *
 * @package Core
 * @subpackage Backend
 * @author Jan Lengowski
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.tpl.php');
cInclude('includes', 'functions.con.php');

/**
 * Saves changes of modules and regenerates code cache if required
 *
 * @param int    $idmod
 *         module id
 * @param string $name
 *         name of the module
 * @param string $description
 *         module description text
 * @param string $input
 *         module input content
 * @param string $output
 *         module output content
 * @param string $template
 *         template field in module's database entry (seems deprecated)
 * @param string $type
 *         module type (common values are '', 'content', 'head', 'layout', 'navigation' and 'script')
 *
 * @return mixed
 *         idmod or nothing
 *
 * @throws cDbException
 * @throws cException
 * @throws cInvalidArgumentException
 */
function modEditModule(
    $idmod, $name, $description, $input, $output, $template, $type = ''
)
{
    $description = stripslashes($description);
    $messageIfError = '';

    $db = cRegistry::getDb();
    $cfg = cRegistry::getConfig();
    $cfgClient = cRegistry::getClientConfig();
    $client = cSecurity::toInteger(cRegistry::getClientId());
    $area = cRegistry::getArea();
    $frame = cRegistry::getFrame();

    // Alias for module name for the file system
    $alias = cString::toLowerCase(cModuleHandler::getCleanName($name));

    // Track version
    $oVersion = new cVersionModule($idmod, $cfg, $cfgClient, $db, $client, $area, $frame);
    $oVersion->createNewVersion();

    if (!$idmod) {
        $cApiModuleCollection = new cApiModuleCollection();
        $cApiModule = $cApiModuleCollection->create($name);

        $idmod = $cApiModule->get('idmod');

        cRights::createRightsForElement('mod', $idmod);
    } else {
        $cApiModule = new cApiModule($idmod);
    }

    $contenidoModuleHandler = new cModuleHandler($idmod);

    // Save contents of input or output
    $retInput = $contenidoModuleHandler->saveInput(stripslashes($input));
    $retOutput = $contenidoModuleHandler->saveOutput(stripslashes($output));

    // Clear the client cache if the module code was written successfully
    if ($retInput || $retOutput) {
        $purge = new cSystemPurge();
        $purge->clearClientCache($client);
    }

    if (
        $cApiModule->get('name') != stripslashes($name)
        || $cApiModule->get('alias') != stripslashes($alias)
        || $cApiModule->get('template') != stripslashes($template)
        || $cApiModule->get('description') != stripslashes($description)
        || $cApiModule->get('type') != stripslashes($type)
    ) {
        // Rename the module if the name changed
        $change = false;
        $oldName = $cApiModule->get('alias');

        if ($cApiModule->get('alias') != $alias) {
            $change = true;
            // If module exist show message
            if ($contenidoModuleHandler->modulePathExistsInDirectory($alias)) {
                cRegistry::addErrorMessage(
                    i18n('Module name exist in module directory, please choose another name.')
                );
                $page = new cGuiPage('generic_page');
                $page->abortRendering();
                $page->render();
                die();
            }
        }

        // Name of module changed
        if ($change) {
            cRegistry::addOkMessage(i18n('Renamed module successfully!'));
            $cApiModule->set('name', $name);
            $cApiModule->set('template', $template);
            $cApiModule->set('description', $description);
            $cApiModule->set('type', $type);
            $cApiModule->set('lastmodified', date('Y-m-d H:i:s'));

            // False: The new name of modul dont exist im modul dir
            if (!$contenidoModuleHandler->renameModule($oldName, $alias)) {
                cRegistry::addWarningMessage(
                    i18n("Can't rename module, is a module file open?! Saving only database changes!")
                );
            } else {
                $cApiModule->set('alias', $alias);
            }

            $cApiModule->store();

            // Set the new module name
            $contenidoModuleHandler->changeModuleName($alias);
            // Save input and output in file
            if (!$contenidoModuleHandler->saveInput(stripslashes($input))) {
                $messageIfError .= '<br>' . i18n("Can't save input !");
            }

            if (!$contenidoModuleHandler->saveOutput(stripslashes($output))) {
                $messageIfError .= '<br>' . i18n("Can't save output !");
            }

            if (!$contenidoModuleHandler->saveInfoXML($name, $description, $type, $alias)) {
                $messageIfError .= '<br>' . i18n("Can't save xml module info file!");
            }

            // Display error
            if ($messageIfError !== '') {
                cRegistry::addErrorMessage($messageIfError);
                // Set the old name because module could not be renamed
                $cApiModule->set('name', $oldName);
                $cApiModule->store();
            }
        } else {
            $cApiModule->set('name', $name);
            $cApiModule->set('template', $template);
            $cApiModule->set('description', $description);
            $cApiModule->set('type', $type);
            $cApiModule->set('lastmodified', date('Y-m-d H:i:s'));
            $cApiModule->set('alias', $alias);
            $cApiModule->store();

            if (!$contenidoModuleHandler->saveInfoXML($name, $description, $type, $alias)) {
                cRegistry::addErrorMessage(i18n("Can't save xml module info file!"));
            }

            if ($retInput === true && $retOutput === true) {
                cRegistry::addOkMessage(i18n('Saved module successfully!'));
            } else {
                $messageIfError = '<br>' . i18n("Can't save input !");
                $messageIfError .= '<br>' . i18n("Can't save output !");
                cRegistry::addErrorMessage($messageIfError);
            }
        }
    } else {
        // No changes for save
        if ($retInput && $retOutput) {
            // regenerate code cache because module input and output got saved
            $cApiModule->store();
            cRegistry::addOkMessage(i18n('Saved module successfully!'));
        } else {
            $messageIfError = i18n("Can't save input !");
            $messageIfError .= ' ' . i18n("Can't save output !");
            cRegistry::addErrorMessage($messageIfError);
        }
    }

    return $idmod;
}

/**
 * Deletes the module of the given ID for the current client.
 * Furthermore, the rights for this module are deleted.
 *
 * @param int $idmod Id of the module to delete
 *
 * @throws cDbException|cException|cInvalidArgumentException
 */
function modDeleteModule($idmod)
{
    $client = cRegistry::getClientId();
    $moduleCollection = new cApiModuleCollection();
    $moduleCollection->deleteByWhereClause(
        $moduleCollection->prepare('`idmod` = %d AND `idclient` = %d', $idmod, $client)
    );

    // Delete rights for element
    cRights::deleteRightsForElement('mod', $idmod);
}
