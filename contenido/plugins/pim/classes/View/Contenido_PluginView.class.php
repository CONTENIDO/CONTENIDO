<?php
/**
 * Template functions
 *
 * @package plugin
 * @subpackage Plugin Manager
 * @version SVN Revision $Rev:$
 * @author Rudi Bieller, Frederic Schneider
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}
class Contenido_PluginView {

    protected $_tpl;

    protected $_pathToTpl;

    protected $_isGenerated;

    public function __construct($sess) {
        $this->_tpl = new cTemplate();
        $this->_tpl->reset();
        $this->_tpl->set('s', 'SESSID', $sess->id);
        $this->_isGenerated = false;
    }

    public function setTemplate($pathToTpl) {
        $this->_pathToTpl = $pathToTpl;
    }

    public function setVariable($variable, $name = '') {
        if (empty($name)) {
            $name = strtoupper($$variable);
        }

        $this->_tpl->set('s', $name, $variable);
    }

    public function getRendered($mode = '') {
        $this->_isGenerated = true;
        return $this->_tpl->generate($this->_pathToTpl, $mode);
    }

    public function __destruct() {
        if ($this->_isGenerated === false) {
            $this->_tpl->generate($this->_pathToTpl, true, false);
        }
    }

}
