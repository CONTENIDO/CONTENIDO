<?php
/**
 * This file contains the client mode setup mask.
 *
 * @package    Setup
 * @subpackage Form
 * @version    SVN Revision $Rev:$
 *
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Client mode setup mask.
 *
 * @package Setup
 * @subpackage Form
 */
class cSetupClientMode extends cSetupMask
{
    function cSetupClientMode($step, $previous, $next)
    {
        cSetupMask::cSetupMask("templates/setup/forms/clientmode.tpl", $step);
        $this->setHeader(i18n("Example Client"));
        $this->_oStepTemplate->set("s", "TITLE", i18n("Example Client"));
        $this->_oStepTemplate->set("s", "DESCRIPTION", i18n("If you are new to CONTENIDO, you should create an example client to start working with."));

        cArray::initializeKey($_SESSION, "clientmode", "");

        $aChoices = array(
            "CLIENTEXAMPLES" => i18n("Client with example modules and example content"),
            "CLIENTMODULES"  => i18n("Client with example modules, but without example content"),
            "NOCLIENT"       => i18n("Don't create client")
        );

        foreach ($aChoices as $sKey => $sChoice) {
            $oRadio = new cHTMLRadiobutton("clientmode", $sKey);
            $oRadio->setLabelText(" ");
            $oRadio->setStyle('width:auto;border:0;');

            if ($_SESSION["clientmode"] == $sKey || ($_SESSION["clientmode"] == "" && $sKey == "CLIENTEXAMPLES")) {
                $oRadio->setChecked("checked");
            }

            $oLabel = new cHTMLLabel($sChoice, $oRadio->getId());

            $this->_oStepTemplate->set("s", "CONTROL_".$sKey, $oRadio->render());
            $this->_oStepTemplate->set("s", "LABEL_".$sKey, $oLabel->render());
        }

        $this->setNavigation($previous, $next);
    }

}

?>