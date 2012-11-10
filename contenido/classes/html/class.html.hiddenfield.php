<?php
/**
 * This file contains the cHTMLHiddenField class.
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
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * cHTMLHiddenField class represents a hidden form field.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLHiddenField extends cHTMLFormElement {

    /**
     * Constructor.
     * Creates an HTML hidden field.
     *
     * @param string $name Name of the element
     * @param string $value Title of the button
     * @param string $id ID of the element
     * @return void
     */
    public function __construct($name, $value = '', $id = '') {
        parent::__construct($name, $id);
        $this->_contentlessTag = true;
        $this->updateAttribute('type', 'hidden');
        $this->_tag = 'input';

        $this->setValue($value);
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLHiddenField($name, $value = '', $id = '') {
        cDeprecated('Use __construct() instead');
        $this->__construct($name, $value, $id);
    }

    /**
     * Sets the value for the field
     *
     * @param string $value Value of the field
     * @return cHTMLHiddenField $this
     */
    public function setValue($value) {
        $this->updateAttribute('value', $value);

        return $this;
    }

}
