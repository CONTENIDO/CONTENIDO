<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Area management class
 *
 * @package CONTENIDO API
 * @version 1.5
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
 * Template collection
 *
 * @package CONTENIDO API
 * @subpackage Model
 */
class cApiTemplateCollection extends ItemCollection {

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
     * @param int $idclient return cApiTemplateConfiguration|null
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
 * @package CONTENIDO API
 * @subpackage Model
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
