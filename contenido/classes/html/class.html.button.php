<?php
/**
 * This file contains the cHTMLButton class.
 *
 * @package Core
 * @subpackage HTML
 * @version SVN Revision $Rev:$
 *
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * cHTMLButton class represents a button.
 *
 * @package Core
 * @subpackage Frontend
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
    public function __construct($name, $title = '', $id = '', $disabled = false, $tabindex = null, $accesskey = '', $mode = 'submit', $class = '') {
        parent::__construct($name, $id, $disabled, $tabindex, $accesskey);
        $this->_tag = 'input';
        $this->_contentlessTag = true;
        $this->setTitle($title);
        $this->setMode($mode);
        $this->setClass($class);
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLButton($name, $title = '', $id = '', $disabled = false, $tabindex = null, $accesskey = '', $mode = 'submit') {
        cDeprecated('Use __construct() instead');
        $this->__construct($name, $title, $id, $disabled, $tabindex, $accesskey, $mode);
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
