<?php
/**
 * This file contains the path information setup mask.
 *
 * @package    Setup
 * @subpackage Form
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Path information setup mask.
 *
 * @package Setup
 * @subpackage Form
 */
class cSetupPath extends cSetupMask
{
    function cSetupPath($step, $previous, $next)
    {
        cSetupMask::cSetupMask("templates/setup/forms/pathinfo.tpl", $step);
        $this->setHeader(i18n("System Directories", "setup"));
        $this->_oStepTemplate->set("s", "TITLE", i18n("System Directories", "setup"));
        $this->_oStepTemplate->set("s", "DESCRIPTION", i18n("Please check the directories identified by the system. If you need to change a path, click on the name and enter the new path in the available input box.", "setup"));

        list($rootPath, $rootHttpPath) = getSystemDirectories(true);

        $cHTMLErrorMessageList = new cHTMLErrorMessageList();
        $cHTMLFoldableErrorMessages = array();

        list($rootPath2, $rootHttpPath2) = getSystemDirectories();
        $oRootPath = new cHTMLTextbox("override_root_path", $rootPath2);
        $oRootPath->setWidth(100);
        $oRootPath->setClass("small");
        $oWebPath = new cHTMLTextbox("override_root_http_path", $rootHttpPath2);
        $oWebPath->setWidth(100);
        $oWebPath->setClass("small");

        $cHTMLFoldableErrorMessages[0] = new cHTMLFoldableErrorMessage(i18n("CONTENIDO Root Path", "setup").":<br>".$rootPath, $oRootPath);
        $cHTMLFoldableErrorMessages[0]->_oContent->setStyle("padding-bottom: 8px;");
        $cHTMLFoldableErrorMessages[1] = new cHTMLFoldableErrorMessage(i18n("CONTENIDO Web Path", "setup").":<br>".$rootHttpPath, $oWebPath);
        $cHTMLFoldableErrorMessages[1]->_oContent->setStyle("padding-bottom: 8px;");

        $cHTMLErrorMessageList->setContent($cHTMLFoldableErrorMessages);

        $this->_oStepTemplate->set("s", "CONTROL_PATHINFO", $cHTMLErrorMessageList->render());

        $this->setNavigation($previous, $next);
    }
}
?>