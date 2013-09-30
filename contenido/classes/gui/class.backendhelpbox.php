<?php
/**
 * A class to render helpful information next to a form element
 *
 * @package Core
 * @subpackage GUI
 * @version SVN Revision $Rev:$
 *
 * @author Mischa Holz
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class cGuilBackendHelpbox.
 * Renders a little helpbox to display information next to a form element
 *
 * @package Core
 * @subpackage GUI
 */
class cGuiBackendHelpbox {

    /**
     * the text that will appear in the tooltip
     *
     * @var string
     */
    protected $helpText;

    /**
     * Basic constructor.
     * Assigns a help text
     *
     * @param string $helpText the text that will appear in the tooltip
     */
    function __construct($helpText) {
        $this->setHelpText($helpText);
    }

    /**
     * Set the help text to a new value
     *
     * @param string $helpText the text that will appear in the tooltip
     */
    function setHelpText($helpText) {
        $this->helpText = $helpText;
    }

    /**
     * Render the helpbox.
     * Please make sure that the general.js, the atooltip.jquery.js and the
     * atooltip.css are embedded on the site
     *
     * @param string $returnAsString if true the rendered button will be
     *        returned. Otherwise it will be echoed
     * @return string|NULL rendered button or nothing if it's been printed
     */
    function render($returnAsString = true) {
        $id = md5(rand()) . "-Info";

        $ret = "<a href='javascript://' id='" . $id . "-link' title='" . i18n("More information") . "' class='i-link infoButton'></a>";
        $ret .= "<div id='" . $id . "' style='display: none;'>" . $this->helpText . "</div>";

        if ($returnAsString) {
            return $ret;
        } else {
            echo ($ret);
            return NULL;
        }
    }
}

?>