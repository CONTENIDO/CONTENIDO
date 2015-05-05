<?php
/**
 * This file contains the cHTMLHiddenField class.
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
 *
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * cHTMLHiddenField class represents a hidden form field.
 *
 * @package Core
 * @subpackage GUI_HTML
 */
class cHTMLHiddenField extends cHTMLFormElement {

    /**
     * Constructor.
     * Creates an HTML hidden field.
     *
     * @param string $name Name of the element
     * @param string $value Title of the button
     * @param string $id ID of the element
     */
    public function __construct($name, $value = '', $id = '') {
        parent::__construct($name, $id);
        $this->_contentlessTag = true;
        $this->updateAttribute('type', 'hidden');
        $this->_tag = 'input';

        $this->setValue($value);
    }

    /**
     * Sets the value for the field
     *
     * @param string $value Value of the field
     * @return cHTMLHiddenField
     *         $this for chaining
     */
    public function setValue($value) {
        $this->updateAttribute('value', $value);

        return $this;
    }

}
