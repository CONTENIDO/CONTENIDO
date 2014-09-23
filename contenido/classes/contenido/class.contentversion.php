<?php
/**
 * This file contains the content version collection and item class.
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
 * Content Version collection
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
     * Creates a content version entry.
     *
	 * @param mixed[] $parameters{
	 * 	@type int $idContent
	 * 	@type int $idArtLang
	 * 	@type int $idType
	 * 	@type int $typeId
	 * 	@type string $value
	 * 	@type int $version
	 * 	@type string $author
	 * 	@type string $created
	 * 	@type string $lastModified
	 * }
     * @return cApiContent
     */
    public function create(array $parameters) {
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
		
        $item = $this->createNewItem();

		$item->set('idcontent', $parameters['idContent']);
        $item->set('idartlang', $parameters['idArtLang']);
        $item->set('idtype', $parameters['idType']);
        $item->set('typeid', $parameters['typeId']);
        $item->set('value', $parameters['value']);
        $item->set('version', $parameters['version']);
        $item->set('author', $parameters['author']);
        $item->set('created', $parameters['created']);
        $item->set('lastmodified', $parameters['lastModified']);
        $item->store();

        return $item;
    }
}

/**
 * Content Version item
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
        parent::setField($name, $value, $bSafe);
    }

	
	/**
	 * Set current Content to this Content Version	 
	 * 
	 */	
	function setAsCurrent(){		
		$content = new cApiContent($this->get('idcontent'));
		$content->set('value', $this->get('value'));
		$content->set('author', $this->get('author'));
		$content->set('created', $this->get('created'));
		$content->set('lastmodified', $this->get('lastmodified'));
		$content->store();
	}
	
    /**
     * Loads an content entry by its article language id, idtype, type id and version.
     *
     * @param mixed[] $contentParameters{
	 *	@type int $idArtLang
	 *	@type int $idType
	 *	@type int $typeId
	 *	@type int $version
	 * }
     * @return bool
     */
    public function loadByArticleLanguageIdTypeTypeIdAndVersion(array $contentParameters) {
        $props = array(
            'idartlang' => $contentParameters['idArtLang'],
            'idtype' => $contentParameters['idType'],
            'typeid' => $contentParameters['typeId'],
			'version' => $contentParameters['version']
        );
        $recordSet = $this->_oCache->getItemByProperties($props);
        if ($recordSet) {
            // entry in cache found, load entry from cache
            $this->loadByRecordSet($recordSet);
            return true;
        } else {
            $where = $this->db->prepare("idartlang = %d AND idtype = %d AND typeid = %d AND version = %d", $contentParameters['idartlang'], $contentParameters['idtype'], $contentParameters['typeid'], $contentParameters['version']);
            return $this->_loadByWhereClause($where);
        }
    }

}
