<?php

/**
 * This file contains the file collection and item class.
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
 * File collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @extends ItemCollection<cApiFile>
 */
class cApiFileCollection extends ItemCollection
{
    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('files'), 'idfile');
        $this->_setItemClass('cApiFile');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiAreaCollection');
    }

    /**
     * Creates a file item entry
     *
     * @param string $area
     * @param string $filename
     * @param string $filetype [optional]
     * @return cApiFile
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($area, $filename, $filetype = 'main')
    {
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
        $item->set('filename', $filename);

        if ($filetype != 'main') {
            $item->set('filetype', 'inc');
        } else {
            $item->set('filetype', 'main');
        }

        $item->store();

        return $item;
    }
}

/**
 * File item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiFile extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id Specifies the ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('files'), 'idfile');
        $this->setFilters(['addslashes'], ['stripslashes']);
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }
}
