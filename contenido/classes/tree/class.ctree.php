<?php

/**
 * This file contains the tree class.
 *
 * @package    Core
 * @subpackage GUI
 * @version    SVN Revision $Rev:$
 *
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Tree class
 *
 * @package    Core
 * @subpackage GUI
 */
class cTree extends cTreeItem {

    /**
     * Tree icon
     *
     * @var string
     */
    protected $_treeIcon;

    /**
     * @todo The root item currently has to be a "0".
     * This is a bug, feel free to fix it.
     * @param string $name
     */
    public function __construct($name = "") {
        parent::__construct(0, $name);
    }

    /**
     * sets a new name for the tree.
     *
     * @param string $name
     *         Name of the tree
     */
    public function setTreeName($name) {
        $this->setName($name);
    }

    /**
     * Tree icon setter
     *
     * @param string $path
     */
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
