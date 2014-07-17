<?php
/**
 * This file contains the right collection and item class.
 *
 * @package          Core
 * @subpackage       GenericDB_Model
 * @version          SVN Revision $Rev:$
 *
 * @author           Murat Purc <murat@purc.de>
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Right collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiRightCollection extends ItemCollection {

    /**
     * Constructor
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['rights'], 'idright');
        $this->_setItemClass('cApiRight');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiUserCollection');
        $this->_setJoinPartner('cApiAreaCollection');
        $this->_setJoinPartner('cApiActionCollection');
        $this->_setJoinPartner('cApiCategoryCollection');
        $this->_setJoinPartner('cApiClientCollection');
        $this->_setJoinPartner('cApiLanguageCollection');
    }

    /**
     * Creates a right entry.
     *
     * @param string $userId
     * @param int $idarea
     * @param int $idaction
     * @param int $idcat
     * @param int $idclient
     * @param int $idlang
     * @param int $type
     * @return cApiRight
     */
    public function create($userId, $idarea, $idaction, $idcat, $idclient, $idlang, $type) {
        $oItem = $this->createNewItem();

        $oItem->set('user_id', $this->escape($userId));
        $oItem->set('idarea', (int) $idarea);
        $oItem->set('idaction', (int) $idaction);
        $oItem->set('idcat', (int) $idcat);
        $oItem->set('idclient', (int) $idclient);
        $oItem->set('idlang', (int) $idlang);
        $oItem->set('type', (int) $type);

        $oItem->store();

        return $oItem;
    }

    /**
     * Checks if a specific user has frontend access to a protected category.
     *
     * @param int $idcat
     * @param string $userId
     * @return bool
     */
    public function hasFrontendAccessByCatIdAndUserId($idcat, $userId) {
        global $cfg;

        $sql = "SELECT :pk FROM `:rights` AS A, `:actions` AS B, `:area` AS C
                WHERE B.name = 'front_allow' AND C.name = 'str' AND A.user_id = ':userid'
                    AND A.idcat = :idcat AND A.idarea = C.idarea AND B.idaction = A.idaction
                LIMIT 1";

        $params = array(
            'pk' => $this->primaryKey,
            'rights' => $this->table,
            'actions' => $cfg['tab']['actions'],
            'area' => $cfg['tab']['area'],
            'userid' => $userId,
            'idcat' => (int) $idcat
        );

        $sql = $this->db->prepare($sql, $params);
        $this->db->query($sql);
        return ($this->db->nextRecord());
    }

    /**
     * Deletes right entries by user id.
     *
     * @todo Implement functions to delete rights by area, action, cat, client,
     *       language.
     * @param string $userId
     * @return bool
     */
    public function deleteByUserId($userId) {
        $result = $this->deleteBy('user_id', $userId);
        return ($result > 0) ? true : false;
    }

}

/**
 * Right item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiRight extends Item {

    /**
     * Constructor function
     *
     * @param mixed $mId Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['rights'], 'idright');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

}
