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

    protected $imageURL;

    /**
     * Basic constructor.
     * Assigns a help text
     *
     * @param string $helpText the text that will appear in the tooltip
     * @param string $imageURL This image will be used for the tooltip
     */
    public function __construct($helpText, $imageURL = '') {
        $this->setHelpText($helpText);
        $this->setImageURL($imageURL);
    }

    /**
     * Set the help text to a new value
     *
     * @param string $helpText the text that will appear in the tooltip
     */
    public function setHelpText($helpText) {
        $this->helpText = $helpText;
    }

    /**
     * Set the image for the tooltip
     *
     * @param string $imageURL the image file
     */
    public function setImageURL($imageURL) {
        $this->imageURL = $imageURL;
    }

    /**
     * Render the helpbox.
     * Please make sure that the atooltip.jquery.js and the
     * atooltip.css are embedded on the site
     *
     * @param string $returnAsString if true the rendered button will be
     *        returned. Otherwise it will be echoed
     * @return string|NULL rendered button or nothing if it's been printed
     */
    public function render($returnAsString = true) {
        $id = md5(rand()) . "-Info";

        $style = '';
        if($this->imageURL != '') {
            $style = 'style="background: transparent url(' . $this->imageURL . ') no-repeat;"';
        }

        $ret = "<a " . $style . " href='javascript://' id='" . $id . "-link' title='" . i18n("More information") . "' class='i-link infoButton'></a>";
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