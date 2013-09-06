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

// @fixme: Document me!
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

    // clear the client cache if the module code has changed
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

        // Name of modul changed
        if ($change == true) {
            cRegistry::addInfoMessage(i18n('Renamed module successfully!'));
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
            // Ssave input and output in file
            if ($contenidoModuleHandler->saveInput(stripslashes($input)) == false) {
                $messageIfError .= '<br />' . i18n("Can't save input !");
            }

            if ($contenidoModuleHandler->saveOutput(stripslashes($output)) == false) {
                $messageIfError .= '<br />' . i18n("Can't save output !");
            }

            if ($contenidoModuleHandler->saveInfoXML($name, $description, $type, $alias) == false) {
                $messageIfError .= '<br />' . i18n("Can't save xml module info file!");
            }

            // Display error
            if ($messageIfError != '') {
                cRegistry::addErrorMessage($messageIfError);
                // Set the old name because module could not rename
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

            if ($retInput == true && $retOutput == true) {
                cRegistry::addInfoMessage(i18n('Saved module successfully!'));
            } else {
                $messageIfError = '<br>' . i18n("Can't save input !");
                $messageIfError .= '<br>' . i18n("Can't save output !");
                cRegistry::addErrorMessage($messageIfError);
            }
        }
    } else {
        // No changes for save
        if ($retInput == true && $retOutput == true) {
            cRegistry::addInfoMessage(i18n('Saved module successfully!'));
        } else {
            $messageIfError = i18n("Can't save input !");
            $messageIfError .= ' ' . i18n("Can't save output !");
            cRegistry::addErrorMessage($messageIfError);
        }
    }

    return $idmod;
}

// @fixme: Document me!
function modDeleteModule($idmod) {
    global $db, $sess, $client, $cfg, $area_tree, $perm;

    $sql = 'DELETE FROM ' . $cfg['tab']['mod'] . ' WHERE idmod = ' . (int) $idmod . ' AND idclient = ' . (int) $client;
    $db->query($sql);

    // Delete rights for element
    cInclude('includes', 'functions.rights.php');
    deleteRightsForElement('mod', $idmod);
}

// $code: Code to evaluate
// $id: Unique ID for the test function
// $mode: true if start in php mode, otherwise false
// Returns true or false
// @fixme: Document me!
function modTestModule($code, $id, $output = false) {
    global $cfg, $modErrorMessage;

    $magicvalue = 0;

    $db = cRegistry::getDb();

    // Put a $ in front of all CMS variables to prevent PHP error messages
    $sql = 'SELECT type FROM ' . $cfg['tab']['type'];
    $db->query($sql);
    while ($db->nextRecord()) {
        $code = str_replace($db->f('type') . '[', '$' . $db->f('type') . '[', $code);
    }

    $code = preg_replace(',\[(\d+)?CMS_VALUE\[(\d+)\](\d+)?\],i', '[\1\2\3]', $code);
    $code = str_replace('CMS_VALUE', '$CMS_VALUE', $code);
    $code = str_replace('CMS_VAR', '$CMS_VAR', $code);

    // If the module is an output module, escape PHP since all output modules
    // enter php mode
    if ($output == true) {
        $code = "?>\n" . $code . "\n<?php";
    }

    // Looks ugly: Paste a function declarator in front of the code
    $code = 'function foo' . $id . ' () {' . $code;
    $code .= "\n}\n";

    // Set the magic value
    $code .= '$magicvalue = 941;';

    // To parse the error message, we prepend and append a phperror tag in front
    // of the output
    $sErs = ini_get('error_prepend_string'); // Save current setting (see below)
    $sEas = ini_get('error_append_string'); // Save current setting (see below)
    @ini_set('error_prepend_string', '<phperror>');
    @ini_set('error_append_string', '</phperror>');

    // Turn off output buffering and error reporting, eval the code
    ob_start();
    $display_errors = ini_get('display_errors');
    @ini_set('display_errors', true);
    $output = eval($code);
    @ini_set('display_errors', $display_errors);

    // Get the buffer contents and turn it on again
    $output = ob_get_contents();
    ob_end_clean();

    // Remove the prepend and append settings
    /*
     * 19.09.2006: Following lines have been disabled, as ini_restore has been
     * disabled by some hosters as there is a security leak in PHP (PHP <= 5.1.6
     * & <= 4.4.4)
     */
    // ini_restore('error_prepend_string');
    // ini_restore('error_append_string');
    @ini_set('error_prepend_string', $sErs); // Restoring settings (see above)
    @ini_set('error_append_string', $sEas); // Restoring settings (see above)

    // Strip out the error message
    $start = strpos($output, '<phperror>');
    $end = strpos($output, '</phperror>');

    // More stripping: Users shouldnt see where the file is located, but they
    // should see the error line
    if ($start !== false) {
        $start = strpos($output, 'eval()');

        $modErrorMessage = substr($output, $start, $end - $start);

        // Kill that HTML formatting
        $modErrorMessage = str_replace('<b>', '', $modErrorMessage);
        $modErrorMessage = str_replace('</b>', '', $modErrorMessage);
        $modErrorMessage = str_replace('<br>', '', $modErrorMessage);
        $modErrorMessage = str_replace('<br />', '', $modErrorMessage);
    }

    // Check if there are any php short tags in code, and display error
    $bHasShortTags = false;
    if (preg_match('/<\?\s+/', $code) && $magicvalue == 941) {
        $bHasShortTags = true;
        $modErrorMessage = i18n('Please do not use short open tags. (Use <?php instead of <?).');
    }

    // Now, check if the magic value is 941. If not, the function didn't compile
    if ($magicvalue != 941 || $bHasShortTags) {
        return false;
    } else {
        return true;
    }
}
