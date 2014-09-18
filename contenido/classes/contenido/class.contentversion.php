<?php
/**
 * This file contains the content collection and item class.
 *
 * @package          Core
 * @subpackage       GenericDB_Model
 * @version          SVN Revision $Rev:$
 *
 * @author           Dominik Ziegler
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Content collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiContentVersionCollection extends ItemCollection {

    /**
     * Create a new collection of items.
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['content_version'], 'idcontentversion');
        $this->_setItemClass('cApiContentVersion');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiArticleLanguageCollection');
        $this->_setJoinPartner('cApiTypeCollection');
    }

    /**
     * Creates a content entry.
     *
     * @param int $idArtLang
     * @param int $idType
     * @param int $typeId
     * @param string $value
     * @param int $version
     * @param string $author
     * @param string $created
     * @param string $lastmodified
     * @return cApiContent
     */
    public function create($aParameter) {
        global $auth;

        if (empty($author)) {
            $author = $auth->auth['uname'];
        }
        if (empty($created)) {
            $created = date('Y-m-d H:i:s');
        }
        if (empty($lastmodified)) {
            $lastmodified = date('Y-m-d H:i:s');
        }
		
        $oItem = $this->createNewItem();

		$oItem->set('idcontent', $aParameter['idcontent']);
        $oItem->set('idartlang', $aParameter['idartlang']);
        $oItem->set('idtype', $aParameter['idtype']);
        $oItem->set('typeid', $aParameter['typeid']);
        $oItem->set('value', $aParameter['value']);
        $oItem->set('version', $aParameter['version']);
        $oItem->set('author', $aParameter['author']);
        $oItem->set('created', $aParameter['created']);
        $oItem->set('lastmodified', $aParameter['lastmodified']);
        $oItem->store();

        return $oItem;
    }

}

/**
 * Content item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiContentVersion extends Item {

    /**
     * Constructor Function
     *
     * @param mixed $mId Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['content_version'], 'idcontentversion');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Userdefined setter for item fields.
     *
     * @param string $name
     * @param mixed $value
     * @param bool $bSafe Flag to run defined inFilter on passed value
     * @todo should return return value of overloaded method
     */
    public function setField($name, $value, $bSafe = true) {
        switch ($name) {
            case 'idartlang':
            case 'idtype':
            case 'typeid':
            case 'version':
                $value = (int) $value;
                break;
        }

        parent::setField($name, $value, $bSafe);
    }

	
	/**
	 * Set the current Content to this Content Version	 
	 * 
	 */	
	function setAsCurrent(){
		$oContent = new cApiContent($this->get('idcontent'));
		$oContent->set('value', $this->get(''));
		$oContent->set('author', $this->get(''));
		$oContent->set('created', $this->get(''));
		$oContent->set('lastmodified', $this->get(''));
		$oContent->store();
	}
	
    /**
     * Loads an content entry by its article language id, idtype and type id.
     *
     * @param int $idartlang
     * @param int $idtype
     * @param int $typeid
     * @return bool
     */
    public function loadByArticleLanguageIdTypeAndTypeId($idartlang, $idtype, $typeid) {
        $aProps = array(
            'idartlang' => $idartlang,
            'idtype' => $idtype,
            'typeid' => $typeid
        );
        $aRecordSet = $this->_oCache->getItemByProperties($aProps);
        if ($aRecordSet) {
            // entry in cache found, load entry from cache
            $this->loadByRecordSet($aRecordSet);
            return true;
        } else {
            $where = $this->db->prepare("idartlang = %d AND idtype = %d AND typeid = %d", $idartlang, $idtype, $typeid);
            return $this->_loadByWhereClause($where);
        }
    }

}
