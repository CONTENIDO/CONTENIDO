<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * logical cTree
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package CONTENIDO Backend Classes
 * @version 1.12
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * class cTree
 */
class cTree extends cTreeItem {

    /**
     * Tree icon
     *
     * @var string @protected
     */
    protected $_treeIcon;

    public function __construct($name = "") {
        /*
         * The root item currently has to be a "0". This is a bug, feel free to
         * fix it.
         */
        parent::__construct(0, $name);
    }

    /**
     *
     * @deprecated 2012-08-24 Use __construct
     */
    function cTree($name = "") {
        cDeprecated('Use __construct');
        $this->__construct($name);
    }

    /**
     * sets a new name for the tree.
     *
     * @param string name Name of the tree
     * @return void
     */
    public function setTreeName($name) {
        $this->setName($name);
    }

    public function setIcon($path) {
        $this->setTreeIcon($path);
    }

    /**
     * Tree icon setter
     *
     * @param string $path
     */
    public function setTreeIcon($path) {
        $this->_treeIcon = $path;
    }

    /**
     * Tree icon getter
     *
     * @return string
     */
    public function getTreeIcon() {
        return $this->_treeIcon;
    }

}
