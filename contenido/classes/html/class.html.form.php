<?php
/**
 * This file contains the cHTMLForm class.
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
 * cHTMLForm class represents a form.
 *
 * @package Core
 * @subpackage Frontend
 */
class cHTMLForm extends cHTMLContentElement {

    protected $_name;

    protected $_action;

    protected $_method;

    /**
     * Creates an HTML form element.
     *
     * @param string $name the name of the form
     * @param string $action the action which should be performed when this form
     *        is submitted
     * @param string $method the method to use - post or get
     * @param string $class the class of this element
     * @return void
     */
    public function __construct($name = '', $action = 'main.php', $method = 'post', $class = '') {
        parent::__construct('', $class);
        $this->_tag = 'form';
        $this->_name = $name;
        $this->_action = $action;
        $this->_method = $method;
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cHTMLForm() {
        cDeprecated('Use __construct() instead');
        $this->__construct();
    }

    /**
     * Adds content to the form
     *
     * @param string $content content to add
     * @return cHTMLForm $this
     * @deprecated 2012-09-12 Use appendContent($content) instead!
     */
    public function add($content) {
        cDeprecated('Use appendContent($content) instead!');

        return $this->appendContent($content);
    }

    /**
     * Sets the given var.
     *
     * @param string $var
     * @param string $value
     * @return cHTMLForm $this
     */
    public function setVar($var, $value) {
        $this->_vars[$var] = $value;

        return $this;
    }

    /**
     * Renders the form element
     *
     * @return string Rendered HTML
     */
    public function toHTML() {
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
