<?php
/**
 * This file contains the template collection and item class.
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
 * Template collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiTemplateCollection extends ItemCollection {

    /**
     * Create a new collection of items.
     *
     * @param string $select where clause to use for selection (see
     *        ItemCollection::select())
     */
    public function __construct($select = false) {
        global $cfg;
        parent::__construct($cfg['tab']['tpl'], 'idtpl');
        $this->_setItemClass('cApiTemplate');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiLayoutCollection');
        $this->_setJoinPartner('cApiTemplateCollection');
        $this->_setJoinPartner('cApiTemplateConfigurationCollection');

        if ($select !== false) {
            $this->select($select);
        }
    }

    /**
     * Returns the default template configuration item
     *
     * @param int $idclient
     * @return cApiTemplateConfiguration null
     */
    public function selectDefaultTemplate($idclient) {
        $this->select('defaulttemplate = 1 AND idclient = ' . $idclient);
        return $this->next();
    }

    /**
     * Returns all templates having passed layout id.
     *
     * @param int $idlay
     * @return cApiTemplate[]
     */
    public function fetchByIdLay($idlay) {
        $this->select('idlay = ' . $idlay);
        $entries = array();
        while (($entry = $this->next()) !== false) {
            $entries[] = clone $entry;
        }
        return $entries;
    }
}

/**
 * Template item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiTemplate extends Item {

    /**
     * Constructor Function
     *
     * @param mixed $mId Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['tpl'], 'idtpl');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }
}
