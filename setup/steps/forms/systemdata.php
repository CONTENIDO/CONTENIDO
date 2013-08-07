<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 *
 * Requirements:
 * @con_php_req 5
 *
 * @package    Contenido Backend <Area>
 * @version    0.2
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 * @deprecated file deprecated in contenido release <Contenido Version>
 *
 * {@internal
 *   created  unknown
 *   modified 2008-07-07, bilal arslan, added security fix
 *
 *   $Id: systemdata.php 740 2008-08-27 10:45:04Z timo.trautmann $:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


class cSetupSystemData extends cSetupMask {

    function cSetupSystemData($step, $previous, $next) {
        cSetupMask::cSetupMask('templates/setup/forms/systemdata.tpl', $step);

        list($a_root_path, $a_root_http_path) = getSystemDirectories();

        cInitializeArrayKey($_SESSION, 'dbprefix', '');
        cInitializeArrayKey($_SESSION, 'dbhost', '');
        cInitializeArrayKey($_SESSION, 'dbuser', '');
        cInitializeArrayKey($_SESSION, 'dbname', '');
        cInitializeArrayKey($_SESSION, 'dbpass', '');
        cInitializeArrayKey($_SESSION, 'dbcharset', '');

        if (file_exists($a_root_path . '/contenido/includes/config.php')) {
            global $cfg; // Avoiding error message about "prepend3.php" on update from V4.x

            $contenido_host      = ''; // Just define the variables to avoid warnings in IDE
            $contenido_user      = '';
            $contenido_database  = '';
            $contenido_password  = '';
            $contenido_charset   = '';

            @include($a_root_path . '/contenido/includes/config.php');

            $aVars = array(
                'dbhost' => $contenido_host,
                'dbuser' => $contenido_user,
                'dbname' => $contenido_database,
                'dbpass' => $contenido_password,
                'dbprefix' => $cfg['sql']['sqlprefix'],
                'dbcharset' => $contenido_charset,
            );

            foreach ($aVars as $aVar => $sValue) {
                if ($_SESSION[$aVar] == '') {
                    $_SESSION[$aVar] = $sValue;
                }
            }
        }

        $this->setHeader(i18n("Database Parameters"));
        $this->_oStepTemplate->set('s', 'TITLE', i18n("Database Parameters"));

        switch ($_SESSION['setuptype']) {
            case 'setup':
                $this->_oStepTemplate->set('s', 'DESCRIPTION', i18n("Please enter the required database information. If you are unsure about the data, ask your provider or administrator.") . " " . i18n("If the database does not exist and your database user has the sufficient permissions, setup will create the database automatically."));
                break;
            case 'upgrade':
                $this->_oStepTemplate->set('s', 'DESCRIPTION', i18n("Please enter the required database information. If the database data of your previous installation could have been read, the data will be inserted automatically. If you are unsure about the data, please ask your provider or administrator."));
                break;
            case 'migration':
                $this->_oStepTemplate->set('s', 'DESCRIPTION', i18n("Please enter the required database information. Make sure you copied the data from your old installation (e.g. development or staging system) into a new database. Please enter the parameters of the new database."));
                break;
        }

        if ($_SESSION['dbprefix'] == '') {
            $_SESSION['dbprefix'] = 'con';
        }

        if ($_SESSION['dbcharset'] == '' && $_SESSION['setuptype'] == 'setup') {
            $_SESSION['dbcharset'] = C_SETUP_DBCHARSET;
        }

        unset($_SESSION['install_failedchunks']);
        unset($_SESSION['install_failedupgradetable']);
        unset($_SESSION['configsavefailed']);
        unset($_SESSION['htmlpath']);
        unset($_SESSION['frontendpath']);

        $dbhost = new cHTMLTextbox('dbhost', $_SESSION['dbhost'], 30, 255);
        $dbname = new cHTMLTextbox('dbname', $_SESSION['dbname'], 30, 255);
        $dbuser = new cHTMLTextbox('dbuser', $_SESSION['dbuser'], 30, 255);

        if ($_SESSION['dbpass'] != '') {
            $mpass = str_repeat('*', strlen($_SESSION['dbpass']));
        } else {
            $mpass = '';
        }

        $dbpass = new cHTMLPasswordbox('dbpass', $mpass, 30, 255);
        $dbpass->attachEventDefinition('onchange handler', 'onchange', "document.setupform.dbpass_changed.value = 'true';");
        $dbpass->attachEventDefinition('onchange handler', 'onkeypress', "document.setupform.dbpass_changed.value = 'true';");

        $dbpass_hidden = new cHTMLHiddenField('dbpass_changed', 'false');

        $dbprefix = new cHTMLTextbox('dbprefix', $_SESSION['dbprefix'], 10, 30);
        $dbcharset = new cHTMLSelectElement('dbcharset');

        // Compose charset select box
        $pos = 0;
        $option = new cHTMLOptionElement('-- ' . i18n("No character set") . ' --', '');
        $dbcharset->addOptionElement(++$pos, $option);
        $selectedCharset = (!empty($_SESSION['dbcharset'])) ? $_SESSION['dbcharset'] : '';
        $aCharsets = fetchMySQLCharsets();
        foreach ($aCharsets as $p => $charset) {
            $selected = ($selectedCharset == $charset);
            $option = new cHTMLOptionElement($charset, $charset, $selected);
            $dbcharset->addOptionElement(++$pos, $option);
        }

        $this->_oStepTemplate->set('s', 'LABEL_DBHOST', i18n("Database Server (IP or name)"));

        if ($_SESSION['setuptype'] == 'setup') {
            $this->_oStepTemplate->set('s', 'LABEL_DBNAME', i18n("Database Name") . '<br>' . i18n("(use empty or non-existant database)"));
        } else {
            $this->_oStepTemplate->set('s', 'LABEL_DBNAME', i18n("Database Name"));
            $dbcharset->setDisabled(true);
        }

        $this->_oStepTemplate->set('s', 'LABEL_DBUSERNAME', i18n("Database Username"));
        $this->_oStepTemplate->set('s', 'LABEL_DBPASSWORD', i18n("Database Password"));
        $this->_oStepTemplate->set('s', 'LABEL_DBPREFIX', i18n("Table Prefix"));
        $this->_oStepTemplate->set('s', 'LABEL_DBCHARSET', i18n("Database character set"));

        $this->_oStepTemplate->set('s', 'INPUT_DBHOST', $dbhost->render());
        $this->_oStepTemplate->set('s', 'INPUT_DBNAME', $dbname->render());
        $this->_oStepTemplate->set('s', 'INPUT_DBUSERNAME', $dbuser->render());
        $this->_oStepTemplate->set('s', 'INPUT_DBPASSWORD', $dbpass->render() . $dbpass_hidden->render());
        $this->_oStepTemplate->set('s', 'INPUT_DBPREFIX', $dbprefix->render());
        $this->_oStepTemplate->set('s', 'INPUT_DBCHARSET', $dbcharset->render());

        $this->setNavigation($previous, $next);
    }

    function _createNavigation() {
        $link = new cHTMLLink('#');

        if ($_SESSION['setuptype'] == 'setup') {
            $checkScript = sprintf('var msg = ""; if (document.setupform.dbhost.value == "") { msg += "%s "; } if (document.setupform.dbname.value == "") { msg += "%s "; } if (document.setupform.dbuser.value == "") { msg += "%s "; } if (document.setupform.dbhost.value != "" && document.setupform.dbname.value != "" && document.setupform.dbuser.value != "") { document.setupform.submit(); } else { alert(msg); }',
                                    i18n("You need to enter a database host."),
                                    i18n("You need to enter a database name."),
                                    i18n("You need to enter a database user."));
            $link->attachEventDefinition('pageAttach', 'onclick', "document.setupform.step.value = '".$this->_bNextstep."';");
            $link->attachEventDefinition('submitAttach', 'onclick', "$checkScript");
        } else {
            $link->attachEventDefinition('pageAttach', 'onclick', "document.setupform.step.value = '" . $this->_bNextstep . "'; document.setupform.submit();");
        }
        $link->setClass("nav");
        $link->setContent("<span>&raquo;</span>");
        $this->_oStepTemplate->set('s', 'NEXT', $link->render());

        $backlink = new cHTMLLink('#');
        $backlink->attachEventDefinition('pageAttach', 'onclick', "document.setupform.step.value = '" . $this->_bBackstep . "';");
        $backlink->attachEventDefinition('submitAttach', 'onclick', 'document.setupform.submit();');
        $backlink->setClass("nav navBack");
        $backlink->setContent("<span>&raquo;</span>");
        $this->_oStepTemplate->set('s', 'BACK', $backlink->render());
    }

}

?>