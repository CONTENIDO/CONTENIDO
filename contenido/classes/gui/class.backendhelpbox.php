<?php

/**
 * A class to render help boxes (aka tooltips).
 *
 * @package Core
 * @subpackage GUI
 * @author Mischa Holz
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * The class cGuiBackendHelpbox allows to render help boxes
 * (aka tooltips) to be displayed anywhere on a CONTENIDO backend page.
 *
 * These help boxes contain a help text and may optionally contain an
 * image.
 *
 * If using this class please make sure that the atooltip.jquery.js
 * and the atooltip.css are embedded in the pages template.
 *
 * @package Core
 * @subpackage GUI
 */
class cGuiBackendHelpbox {

    /**
     * Text that will be displayed in the help box.
     *
     * @var string
     */
    protected $helpText;

    /**
     * URL of the image that will appear in the help box.
     *
     * @var string
     */
    protected $imageURL;

    /**
     * Constructor to create an instance of this class.
     *
     * Create a new backend help box containing a help text and an
     * optional image URL.
     *
     * @param string $helpText
     *         the text that will appear in the help box
     * @param string $imageURL [optional]
     *         This image will be used for the help box
     */
    public function __construct($helpText, $imageURL = '') {
        $this->setHelpText($helpText);
        $this->setImageURL($imageURL);
    }

    /**
     * Set the help text to a new value.
     *
     * @param string $helpText
     *         the text that will appear in the help box
     */
    public function setHelpText($helpText) {
        $this->helpText = $helpText;
    }

    /**
     * Set the image for the help box.
     *
     * @param string $imageURL
     *         the image file
     */
    public function setImageURL($imageURL) {
        $this->imageURL = $imageURL;
    }

    /**
     * Render the help box.
     *
     * @param bool $return [optional]
     *         If true the rendered markup will be returned.
     *         Otherwise it will be echoed.
     * @return string|NULL
     *         rendered markup or NULL if it's been printed
     */
    public function render($return = true) {
        $id = md5(rand()) . "-Info";

        $style = '';
        if ($this->imageURL != '') {
            $style = 'style="background: transparent url(' . $this->imageURL . ') no-repeat;"';
        }

        $html = "<a " . $style . " href='javascript://' id='" . $id . "-link' title='" . i18n("More information") . "' class='i-link infoButton'></a>";
        $html .= "<div id='" . $id . "' style='display: none;'>" . $this->helpText . "</div>";

        if ($return) {
            return $html;
        } else {
            echo $html;
            return NULL;
        }
    }
}
