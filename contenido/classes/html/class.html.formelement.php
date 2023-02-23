<?php

/**
 * This file contains the cHTMLFormElement class.
 *
 * @package Core
 * @subpackage GUI_HTML
 *
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
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
     * Constructor to create an instance of this class.
     *
     * This is a generic form element, where specific elements should be
     * inherited from this class.
     *
     * @param string $name [optional]
     *         Name of the element
     * @param string $id [optional]
     *         ID of the element
     * @param bool $disabled [optional]
     *         Item disabled flag (non-empty to set disabled)
     * @param int|null $tabindex [optional]
     *         Tab index for form elements
     * @param string $accessKey [optional]
     *         Key to access the field
     * @param string $class [optional]
     *         CSS class name to set
     */
    public function __construct(
        $name = '',
        $id = '',
        $disabled = false,
        $tabindex = null,
        $accessKey = '',
        $class = 'text_medium'
    ) {
        $this->_tag = 'input';
        parent::__construct(['name' => $name, 'id' => $id, 'class' => $class]);
        $this->setDisabled($disabled);
        $this->setTabindex($tabindex);
        $this->setAccessKey($accessKey);
    }

    /**
     * Sets the "disabled" attribute of an element.
     * User Agents
     * usually are showing the element as "greyed-out".
     *
     * Example:
     * $obj->setDisabled(true);
     * $obj->setDisabled(false);
     *
     * The first example sets the disabled flag, the second one
     * removes the disabled flag.
     *
     * @param bool $disabled
     *         Sets the disabled-flag if non-empty
     * @return cHTMLFormElement
     *         $this for chaining
     */
    public function setDisabled($disabled) {
        // NOTE: We use toBoolean() because of downwards compatibility.
        // The variable was of type string before 4.10.2!
        $disabled = cSecurity::toBoolean($disabled);
        if ($disabled) {
            $this->updateAttribute('disabled', 'disabled');
        } else {
            $this->removeAttribute('disabled');
        }

        return $this;
    }

    /**
     * Sets the tab index for this element.
     * The tab index needs to be numeric, bigger than 0 and smaller than 32767.
     *
     * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Global_attributes/tabindex
     * @param int|null $tabindex
     *         Desired tab index
     * @return cHTMLFormElement
     *         $this for chaining
     */
    public function setTabindex($tabindex) {
        if (is_numeric($tabindex)) {
            $tabindex = cSecurity::toInteger($tabindex);
            if (-1 <= $tabindex && $tabindex <= 32767) {
                $this->updateAttribute('tabindex', $tabindex);
            }
        } else {
            $this->removeAttribute('tabindex');
        }

        return $this;
    }

    /**
     * Sets the access key for this element.
     *
     * @param string $accessKey
     *         The length of the access key. May be A-Z and 0-9.
     * @return cHTMLFormElement
     *         $this for chaining
     */
    public function setAccessKey($accessKey) {
        if ((cString::getStringLength($accessKey) == 1) && cString::isAlphanumeric($accessKey)) {
            $this->updateAttribute('accesskey', $accessKey);
        } else {
            $this->removeAttribute('accesskey');
        }

        return $this;
    }

}
