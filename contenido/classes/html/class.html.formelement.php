<?php
/**
 * This file contains the cHTMLFormElement class.
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
 * cHTMLFormElement class represents a form element.
 *
 * @package Core
 * @subpackage GUI_HTML
 */
class cHTMLFormElement extends cHTML {

    /**
     * Constructor.
     * This is a generic form element, where
     * specific elements should be inherited from this class.
     *
     * @todo remove overfluous param class
     * @param string $name
     *         Name of the element
     * @param string $id
     *         ID of the element
     * @param string $disabled
     *         Item disabled flag (non-empty to set disabled)
     * @param string $tabindex
     *         Tab index for form elements
     * @param string $accesskey
     *         Key to access the field
     * @param string $class
     *         CSS class name to set
     */
    public function __construct($name = '', $id = '', $disabled = '',
            $tabindex = '', $accesskey = '', $class = 'text_medium', $class = '') {

        parent::__construct();

        $this->updateAttribute('name', $name);

        if (is_string($id) && !empty($id)) {
            $this->updateAttribute('id', $id);
        }

        $this->_tag = 'input';

        $this->setClass($class);
        $this->setDisabled($disabled);
        $this->setTabindex($tabindex);
        $this->setAccessKey($accesskey);

    }

    /**
     * Sets the "disabled" attribute of an element.
     * User Agents
     * usually are showing the element as "greyed-out".
     *
     * Example:
     * $obj->setDisabled('disabled');
     * $obj->setDisabled('');
     *
     * The first example sets the disabled flag, the second one
     * removes the disabled flag.
     *
     * @param string $disabled Sets the disabled-flag if non-empty
     * @return cHTMLFormElement $this
     */
    public function setDisabled($disabled) {
        if (empty($disabled)) {
            $this->removeAttribute('disabled');
        } else {
            $this->updateAttribute('disabled', 'disabled');
        }

        return $this;
    }

    /**
     * Sets the tab index for this element.
     * The tab
     * index needs to be numeric, bigger than 0 and smaller than 32767.
     *
     * @param int $tabindex Desired tab index
     * @return cHTMLFormElement $this
     */
    public function setTabindex($tabindex) {
        if (is_numeric($tabindex) && $tabindex >= 0 && $tabindex <= 32767) {
            $this->updateAttribute('tabindex', $tabindex);
        }

        return $this;
    }

    /**
     * Sets the access key for this element.
     *
     * @param string $accesskey The length of the access key. May be A-Z and
     *        0-9.
     * @return cHTMLFormElement $this
     */
    public function setAccessKey($accesskey) {
        if ((strlen($accesskey) == 1) && isAlphanumeric($accesskey)) {
            $this->updateAttribute('accesskey', $accesskey);
        } else {
            $this->removeAttribute('accesskey');
        }

        return $this;
    }

}
