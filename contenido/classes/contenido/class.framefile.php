<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Frame Files management class
 *
 * @package CONTENIDO API
 * @version 1.3
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
 * Frame file collection
 *
 * @package CONTENIDO API
 * @subpackage Model
 */
class cApiFrameFileCollection extends ItemCollection {

    /**
     * Constructor
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['framefiles'], 'idframefile');
        $this->_setItemClass('cApiFrameFile');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiAreaCollection');
        $this->_setJoinPartner('cApiFileCollection');
    }

    public function create($area, $idframe, $idfile) {
        $item = parent::createNewItem();

        if (is_string($area)) {
            $c = new cApiArea();
            $c->loadBy('name', $area);

            if ($c->virgin) {
                $area = 0;
                cWarning(__FILE__, __LINE__, "Could not resolve area [$area] passed to method [create], assuming 0");
            } else {
                $area = $c->get('idarea');
            }
        }

        $item->set('idarea', (int) $area);
        $item->set('idfile', (int) $idfile);
        $item->set('idframe', (int) $idframe);

        $item->store();

        return ($item);
    }

}

/**
 * Frame file item
 *
 * @package CONTENIDO API
 * @subpackage Model
 */
class cApiFrameFile extends Item {

    /**
     * Constructor Function
     *
     * @param mixed $mId Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['framefiles'], 'idframefile');
        $this->setFilters(array(
            'addslashes'
        ), array(
            'stripslashes'
        ));
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }
}
