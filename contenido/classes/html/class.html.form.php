<?php

/**
 * This file contains the cHTMLForm class.
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
 * cHTMLForm class represents a form.
 *
 * @package Core
 * @subpackage GUI_HTML
 */
class cHTMLForm extends cHTMLContentElement {
    /**
     * @var string
     */
    protected $_name;

    /**
     * @var string
     */
    protected $_action;

    /**
     * @var string
     */
    protected $_method;

    /**
     * @var array
     */
    protected $_vars;

    /**
     * Constructor to create an instance of this class.
     *
     * Creates an HTML form element.
     *
     * @param string $name [optional]
     *         the name of the form
     * @param string $action [optional]
     *         the action which should be performed when this form is submitted
     * @param string $method [optional]
     *         the method to use - post or get
     * @param string $class [optional]
     *         the class of this element
     * @param string $id [optional]
     *         the ID of this element
     */
    public function __construct($name = '', $action = 'main.php', $method = 'post', $class = '', $id = '') {
        parent::__construct('', $class, $id);
        $this->_tag = 'form';
        $this->_name = $name;
        $this->_action = $action;
        $this->_method = $method;
    }

    /**
     * Sets the given var.
     *
     * @param string $var
     * @param string $value
     * @return cHTMLForm
     *         $this for chaining
     */
    public function setVar($var, $value) {
        $this->_vars[$var] = $value;

        return $this;
    }

    /**
     * Renders the form element
     *
     * @return string
     *         Rendered HTML
     */
    public function toHtml() {
        $out = '';
        if (is_array($this->_vars)) {
            foreach ($this->_vars as $var => $value) {
                $f = new cHTMLHiddenField($var, $value);
                $out .= $f->render();
            }
        }
        if ($this->getAttribute('name') == '') {
            $this->setAttribute('name', $this->_name);
        }
        if ($this->getAttribute('method') == '') {
            $this->setAttribute('method', $this->_method);
        }
        if ($this->getAttribute('action') == '') {
            $this->setAttribute('action', $this->_action);
        }

        $attributes = $this->getAttributes(true);

        return $this->fillSkeleton($attributes) . $out . $this->_content . $this->fillCloseSkeleton();
    }

}
