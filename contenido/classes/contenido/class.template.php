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
     * Creates a template entry.
     *
     * @param int $idclient
     * @param int $idlay
     * @param int $idtplcfg  Either a valid template configuration id or an empty string
     * @param string $name
     * @param string $description
     * @param int $deletable
     * @param int $status
     * @param int $defaulttemplate
     * @param string $author
     * @param string $created
     * @param string $lastmodified
     * @return cApiTemplate
     */
    public function create($idclient, $idlay, $idtplcfg, $name, $description, $deletable = 1, $status = 0, $defaulttemplate = 0, $author = '', $created = '', $lastmodified = '') {
        if (empty($author)) {
            $auth = cRegistry::getAuth();
            $author = $auth->auth['uname'];
        }
        if (empty($created)) {
            $created = date('Y-m-d H:i:s');
        }
        if (empty($lastmodified)) {
            $lastmodified = date('Y-m-d H:i:s');
        }

        $oItem = $this->createNewItem();

        $oItem->set('idclient', $idclient);
        $oItem->set('idlay', $idlay);
        $oItem->set('idtplcfg', $idtplcfg);
        $oItem->set('name', $name);
        $oItem->set('description', $description);
        $oItem->set('deletable', $deletable);
        $oItem->set('status', $status);
        $oItem->set('defaulttemplate', $defaulttemplate);
        $oItem->set('author', $author);
        $oItem->set('created', $created);
        $oItem->set('lastmodified', $lastmodified);
        $oItem->store();

        return $oItem;
    }

    /**
     * Returns the default template configuration item
     *
     * @param int $idclient
     * @return cApiTemplateConfiguration NULL
     */
    public function selectDefaultTemplate($idclient) {
        $this->select('defaulttemplate = 1 AND idclient = ' . (int) $idclient);
        return $this->next();
    }

    /**
     * Returns all templates having passed layout id.
     *
     * @param int $idlay
     * @return cApiTemplate[]
     */
    public function fetchByIdLay($idlay) {
        $this->select('idlay = ' . (int) $idlay);
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

    /**
     * Userdefined setter for template fields.
     *
     * @param string $name
     * @param mixed $value
     * @param bool $bSafe Flag to run defined inFilter on passed value
     * @todo should return return value of overloaded method
     */
    public function setField($name, $value, $bSafe = true) {
        switch ($name) {
            case 'deletable':
            case 'status':
            case 'defaulttemplate':
                $value = ($value == 1) ? 1 : 0;
                break;
            case 'idclient':
            case 'idlay':
                $value = (int) $value;
                break;
            case 'idtplcfg':
                if (!is_numeric($value)) {
                    $value = '';
                }
                break;
        }

        parent::setField($name, $value, $bSafe);
    }

}
