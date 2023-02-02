<?php

/**
 * This file contains the upload meta collection and item class.
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @author Dominik Ziegler
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Upload meta collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @method cApiUploadMeta createNewItem
 * @method cApiUploadMeta|bool next
 */
class cApiUploadMetaCollection extends ItemCollection {
    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct() {
        parent::__construct(cRegistry::getDbTableName('upl_meta'), 'id_uplmeta');
        $this->_setItemClass('cApiUploadMeta');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiUploadCollection');
    }

    /**
     * Creates a upload meta entry.
     *
     * @param int     $idupl
     * @param int     $idlang
     * @param string  $medianame       [optional]
     * @param string  $description     [optional]
     * @param string  $keywords        [optional]
     * @param string  $internal_notice [optional]
     * @param string  $copyright       [optional]
     * @param string  $author          [optional]
     * @param string  $created         [optional]
     * @param string  $modified        [optional]
     * @param string  $modifiedby      [optional]
     *
     * @return cApiUploadMeta
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function create($idupl, $idlang, $medianame = '', $description = '',
            $keywords = '', $internal_notice = '', $copyright = '', $author = '',
            $created = '', $modified = '', $modifiedby = '') {

        if (empty($author)) {
            $auth = cRegistry::getAuth();
            $author = $auth->auth['uname'];
        }
        if (empty($created)) {
            $created = date('Y-m-d H:i:s');
        }
        if (empty($modified)) {
            $modified = date('Y-m-d H:i:s');
        }

        $oItem = $this->createNewItem();

        $oItem->set('idupl', $idupl);
        $oItem->set('idlang', $idlang);
        $oItem->set('medianame', $medianame);
        $oItem->set('description', $description);
        $oItem->set('keywords', $keywords);
        $oItem->set('internal_notice', $internal_notice);
        $oItem->set('author', $author);
        $oItem->set('created', $created);
        $oItem->set('modified', $modified);
        $oItem->set('modifiedby', $modifiedby);
        $oItem->set('copyright', $copyright);
        $oItem->store();

        return $oItem;
    }
}

/**
 * Upload meta item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiUploadMeta extends Item
{
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
        parent::__construct(cRegistry::getDbTableName('upl_meta'), 'id_uplmeta');
        $this->setFilters([], []);
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Loads an upload meta entry by upload id and language id
     *
     * @param int $idupl
     * @param int $idlang
     *
     * @return bool
     *
     * @throws cException
     */
    public function loadByUploadIdAndLanguageId($idupl, $idlang) {
        $aProps     = [
            'idupl'  => $idupl,
            'idlang' => $idlang,
        ];
        $aRecordSet = $this->_oCache->getItemByProperties($aProps);
        if ($aRecordSet) {
            // entry in cache found, load entry from cache
            $this->loadByRecordSet($aRecordSet);
            return true;
        } else {
            $where = $this->db->prepare('idupl = %d AND idlang = %d', $idupl, $idlang);
            return $this->_loadByWhereClause($where);
        }
    }

    /**
     * User-defined setter for upload meta fields.
     *
     * @param string $name
     * @param mixed $value
     * @param bool $bSafe [optional]
     *         Flag to run defined inFilter on passed value
     *
     * @return bool
     */
    public function setField($name, $value, $bSafe = true) {
        switch ($name) {
            case 'idupl':
            case 'idlang':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $bSafe);
    }
}
