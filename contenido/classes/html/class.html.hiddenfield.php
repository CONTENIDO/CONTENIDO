<?php

/**
 * This file contains the cHTMLHiddenField class.
 *
 * @package    Core
 * @subpackage GUI_HTML
 * @author     Simon Sprankel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 *
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * cHTMLHiddenField class represents a hidden form field.
 *
 * @package    Core
 * @subpackage GUI_HTML
 */
class cHTMLHiddenField extends cHTMLFormElement {

    /**
     * Constructor to create an instance of this class.
     *
     * Creates an HTML hidden field.
     *
     * @param string $name
     *         Name of the element
     * @param string $value [optional]
     *         Title of the button
     * @param string $id [optional]
     *         ID of the element
     */
    public function __construct($name, $value = '', $id = '') {
        parent::__construct($name, $id, false, '', '', '');
        $this->_contentlessTag = true;
        $this->updateAttribute('type', 'hidden');
        $this->_tag = 'input';

        $this->setValue($value);
    }

    /**
     * Sets the value for the field
     *
     * @param string $value
     *         Value of the field
     * @return cHTMLHiddenField
     *         $this for chaining
     */
    public function setValue($value) {
        $this->updateAttribute('value', $value);

        return $this;
    }

}
