<?php
/**
 * This file contains the upload meta collection and item class.
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @version SVN Revision $Rev:$
 *
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
 */
class cApiUploadMetaCollection extends ItemCollection {

    /**
     * Create a new collection of items.
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['upl_meta'], 'id_uplmeta');
        $this->_setItemClass('cApiUploadMeta');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiUploadCollection');
    }

    /**
     * Creates a upload meta entry.
     *
     * @global object $auth
     * @param int $idupl
     * @param int $idlang
     * @param string $medianame
     * @param string $description
     * @param string $keywords
     * @param string $internal_notice
     * @param string $copyright
     * @param string $author
     * @param string $created
     * @param string $modified
     * @param string $modifiedby
     * @return cApiUploadMeta
     */
    public function create($idupl, $idlang, $medianame = '', $description = '', $keywords = '', $internal_notice = '', $copyright = '', $author = '', $created = '', $modified = '', $modifiedby = '') {
        global $auth;

        if (empty($author)) {
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
class cApiUploadMeta extends Item {

    /**
     * Constructor Function
     *
     * @param mixed $mId Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['upl_meta'], 'id_uplmeta');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Loads an upload meta entry by upload id and language id
     *
     * @param int $idupl
     * @param int $idlang
     * @return bool
     */
    public function loadByUploadIdAndLanguageId($idupl, $idlang) {
        $aProps = array(
            'idupl' => $idupl,
            'idlang' => $idlang
        );
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
     * Userdefined setter for upload meta fields.
     *
     * @param string $name
     * @param mixed $value
     * @param bool $bSafe Flag to run defined inFilter on passed value
     * @todo should return return value of overloaded method
     */
    public function setField($name, $value, $bSafe = true) {
        switch ($name) {
            case 'idupl':
            case 'idlang':
                $value = (int) $value;
                break;
        }

        parent::setField($name, $value, $bSafe);
    }
}
