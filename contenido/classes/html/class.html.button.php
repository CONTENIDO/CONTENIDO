<?php
/**
 * This file contains the cHTMLButton class.
 *
 * @package Core
 * @subpackage GUI_HTML
 * @version SVN Revision $Rev:$
 *
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * cHTMLButton class represents a button.
 *
 * @package Core
 * @subpackage GUI_HTML
 */
class cHTMLButton extends cHTMLFormElement {

    /**
     * Constructor.
     * Creates an HTML button.
     *
     * Creates a submit button by default, can be changed
     * using setMode.
     *
     * @param string $name Name of the element
     * @param string $title Title of the button
     * @param string $id ID of the element
     * @param string $disabled Item disabled flag (non-empty to set disabled)
     * @param string $tabindex Tab index for form elements
     * @param string $accesskey Key to access the field
     * @param string $mode Mode of button
     * @param string $class the class of this element
     * @return void
     */
    public function __construct($name, $title = '', $id = '', $disabled = false, $tabindex = NULL, $accesskey = '', $mode = 'submit', $class = '') {
        parent::__construct($name, $id, $disabled, $tabindex, $accesskey);
        $this->_tag = 'input';
        $this->_contentlessTag = true;
        $this->setTitle($title);
        $this->setMode($mode);
        $this->setClass($class);
    }

    /**
     * Sets the title (caption) for the button
     *
     * @param string $title The title to set
     * @return cHTMLButton $this
     */
    public function setTitle($title) {
        $this->updateAttribute('value', $title);

        return $this;
    }

    /**
     * Sets the mode (submit or reset) for the button
     *
     * @param string $mode Either 'submit', 'reset' or 'image'.
     * @return cHTMLButton $this
     */
    public function setMode($mode) {
        $modes = array(
            'submit',
            'reset',
            'image',
            'button'
        );
        if (in_array($mode, $modes)) {
            $this->updateAttribute('type', $mode);
        }

        return $this;
    }

    /**
     * Set the image src if mode type is "image"
     *
     * @param string $mode Image path.
     * @return cHTMLButton $this
     */
    public function setImageSource($src) {
        $this->setMode('image');
        return $this->updateAttribute('src', $src);
    }

}
