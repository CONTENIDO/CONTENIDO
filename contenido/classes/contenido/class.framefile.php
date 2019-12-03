<?php

/**
 * This file contains the frame file and item class.
 *
 * @package Core
 * @subpackage GenericDB_Model
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
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
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
     *
     * @param int|string $idarea as ID or name
     * @param int        $idframe
     * @param int        $idfile
     *
     * @return cApiFrameFile
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function create($idarea, $idframe, $idfile)
    {
        if (is_string($idarea)) {
            $areaName = $idarea;
            $area     = new cApiArea();
            $area->loadBy('name', $areaName);
            $idarea = $area->isLoaded() ? $area->get('idarea') : 0;
            if (0 === $idarea) {
                cWarning(__FILE__, __LINE__, "Could not resolve area [$areaName] passed to method [create], assuming 0");
            }
        }

        /** @var cApiFrameFile $item */
        $item = $this->createNewItem();
        $item->set('idarea', $idarea);
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
     * Constructor to create an instance of this class.
     *
     * @param mixed $mId [optional]
     *                   Specifies the ID of item to load
     *
     * @throws cDbException
     * @throws cException
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
     * @param bool $bSafe [optional]
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
