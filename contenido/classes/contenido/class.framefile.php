<?php
/**
 * This file contains the frame file and item class.
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @version SVN Revision $Rev:$
 *
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Frame file collection
 *
 * @package Core
 * @subpackage GenericDB_Model
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

    /**
     * Creates a frame file item
     * @param string $area
     * @param int $idframe
     * @param int $idfile
     * @return cApiFrameFile
     */
    public function create($area, $idframe, $idfile) {
        $item = $this->createNewItem();

        if (is_string($area)) {
            $c = new cApiArea();
            $c->loadBy('name', $area);

            if ($c->isLoaded()) {
                $area = $c->get('idarea');
            } else {
                $area = 0;
                cWarning(__FILE__, __LINE__, "Could not resolve area [$area] passed to method [create], assuming 0");
            }
        }

        $item->set('idarea', $area);
        $item->set('idfile', $idfile);
        $item->set('idframe', $idframe);

        $item->store();

        return $item;
    }
}

/**
 * Frame file item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiFrameFile extends Item {

    /**
     * Constructor Function
     *
     * @param mixed $mId
     *         Specifies the ID of item to load
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

    /**
     * Userdefined setter for framefile fields.
     *
     * @param string $name
     * @param mixed $value
     * @param bool $bSafe
     *         Flag to run defined inFilter on passed value
     * @return bool
     */
    public function setField($name, $value, $bSafe = true) {
        switch ($name) {
            case 'idarea':
                $value = (int) $value;
                break;
            case 'idfile':
                $value = (int) $value;
                break;
            case 'idframe':
                $value = (int) $value;
                break;
        }

        return parent::setField($name, $value, $bSafe);
    }

}
