<?php

/**
 * This file contains the frame file and item class.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Frame file collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @method cApiFrameFile createNewItem
 * @method cApiFrameFile|bool next
 */
class cApiFrameFileCollection extends ItemCollection {
    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct() {
        parent::__construct(cRegistry::getDbTableName('framefiles'), 'idframefile');
        $this->_setItemClass('cApiFrameFile');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiAreaCollection');
        $this->_setJoinPartner('cApiFileCollection');
    }

    /**
     * Creates a frame file item
     *
     * @param string $area
     * @param int    $idframe
     * @param int    $idfile
     *
     * @return cApiFrameFile
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
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
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiFrameFile extends Item {
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $mId [optional]
     *                   Specifies the ID of item to load
     *
     * @throws cDbException
     * @throws cException
     */
    public function __construct($mId = false) {
        parent::__construct(cRegistry::getDbTableName('framefiles'), 'idframefile');
        $this->setFilters(['addslashes'], ['stripslashes']);
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * User-defined setter for framefile fields.
     *
     * @param string $name
     * @param mixed $value
     * @param bool $bSafe [optional]
     *         Flag to run defined inFilter on passed value
     * @return bool
     */
    public function setField($name, $value, $bSafe = true) {
        switch ($name) {
            case 'idfile':
            case 'idframe':
            case 'idarea':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $bSafe);
    }

}
