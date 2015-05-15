<?php

/**
 * This file contains the CONTENIDO module functions.
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
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
 * @param int $idmod
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
 * @return mixed
 *         idmod or nothing
 */
function modEditModule($idmod, $name, $description, $input, $output, $template, $type = '') {
    global $db, $client, $cfgClient, $auth, $cfg, $sess, $area, $area_tree, $perm, $frame;
    $description = stripslashes($description);

    $date = date('Y-m-d H:i:s');
    $author = $auth->auth['uname'];
    $contenidoModuleHandler = '';
    $messageIfError = '';

    // Alias for modul name for the file system
    $alias = strtolower(cModuleHandler::getCleanName($name));

    // Track version
    $oVersion = new cVersionModule($idmod, $cfg, $cfgClient, $db, $client, $area, $frame);
    $oVersion->createNewVersion();

    if (!$idmod) {
        $cApiModuleCollection = new cApiModuleCollection();
        $cApiModule = $cApiModuleCollection->create($name);

        $idmod = $cApiModule->get('idmod');

        cInclude('includes', 'functions.rights.php');
        createRightsForElement('mod', $idmod);
    } else {
        $cApiModule = new cApiModule($idmod);
    }

    $contenidoModuleHandler = new cModuleHandler($idmod);

    // Save contents of input or output
    $retInput = $contenidoModuleHandler->saveInput(stripslashes($input));
    $retOutput = $contenidoModuleHandler->saveOutput(stripslashes($output));

    // clear the client cache if the module code was written successfully
    if ($retInput || $retOutput) {
        $purge = new cSystemPurge();
        $purge->clearClientCache($client);
    }

    if ($cApiModule->get('name') != stripslashes($name) || $cApiModule->get('alias') != stripslashes($alias) || $cApiModule->get('template') != stripslashes($template) || $cApiModule->get('description') != stripslashes($description) || $cApiModule->get('type') != stripslashes($type)) {

        // Rename the module if the name changed
        $change = false;
        $oldName = $cApiModule->get('alias');

        if ($cApiModule->get('alias') != $alias) {
            $change = true;
            // if modul exist show message
            if ($contenidoModuleHandler->modulePathExistsInDirectory($alias)) {
                cRegistry::addErrorMessage(i18n('Module name exist in module directory, please choose another name.'));
                $page = new cGuiPage('generic_page');
                $page->abortRendering();
                $page->render();
                die();
            }
        }

        // Name of module changed
        if ($change == true) {
            cRegistry::addOkMessage(i18n('Renamed module successfully!'));
            $cApiModule->set('name', $name);
            $cApiModule->set('template', $template);
            $cApiModule->set('description', $description);
            $cApiModule->set('type', $type);
            $cApiModule->set('lastmodified', date('Y-m-d H:i:s'));

            // False: The new name of modul dont exist im modul dir
            if ($contenidoModuleHandler->renameModul($oldName, $alias) == false) {
                cRegistry::addWarningMessage(i18n("Can't rename module, is a module file open?! Saving only database changes!"));
            } else {
                $cApiModule->set('alias', $alias);
            }

            $cApiModule->store();

            // Set the new module name
            $contenidoModuleHandler->changeModuleName($alias);
            // Save input and output in file
            if ($contenidoModuleHandler->saveInput(stripslashes($input)) == false) {
                $messageIfError .= '<br>' . i18n("Can't save input !");
            }

            if ($contenidoModuleHandler->saveOutput(stripslashes($output)) == false) {
                $messageIfError .= '<br>' . i18n("Can't save output !");
            }

            if ($contenidoModuleHandler->saveInfoXML($name, $description, $type, $alias) == false) {
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

            if ($contenidoModuleHandler->saveInfoXML($name, $description, $type, $alias) == false) {
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
        if ($retInput == true && $retOutput == true) {
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
 * Furthermore the rights for this module are deleted.
 *
 * @todo some global vars seem to be overfluous
 * @param int $idmod
 */
function modDeleteModule($idmod) {
    global $db, $client, $cfg;
    global $sess, $area_tree, $perm;

    $sql = 'DELETE FROM ' . $cfg['tab']['mod'] . ' WHERE idmod = ' . (int) $idmod . ' AND idclient = ' . (int) $client;
    $db->query($sql);

    // Delete rights for element
    cInclude('includes', 'functions.rights.php');
    deleteRightsForElement('mod', $idmod);
}
