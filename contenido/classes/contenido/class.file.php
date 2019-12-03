<?php

/**
 * This file contains the file collection and item class.
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
 * File collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiFileCollection extends ItemCollection {
    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['files'], 'idfile');
        $this->_setItemClass('cApiFile');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiAreaCollection');
    }

    /**
     * Creates a file item entry
     *
     * @param int|string $idarea   as ID or name
     * @param string     $filename
     * @param string     $filetype [optional]
     *
     * @return cApiFile
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function create($idarea, $filename, $filetype = 'main')
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

        if ('main' !== $filetype) {
            $filetype = 'inc';
        }

        /** @var cApiFile $item */
        $item = $this->createNewItem();
        $item->set('idarea', $idarea);
        $item->set('filename', $filename);
        $item->set('filetype', $filetype);
        $item->store();

        return $item;
    }
}

/**
 * File item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiFile extends Item {
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
        parent::__construct($cfg['tab']['files'], 'idfile');
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
