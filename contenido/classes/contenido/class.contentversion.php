<?php
/**
 * This file contains the content version collection and item class.
 *
 * @package          Core
 * @subpackage       GenericDB_Model
 * @version          SVN Revision $Rev:$
 *
 * @author           Jann Dieckmann
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
        parent::__construct(cRegistry::getDbTableName('content_version'), 'idcontentversion');
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
     * @return cApiContentVersion
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
        
        // populate item w/ values
        foreach (array_keys($parameters) as $key) {
            $item->set($key, $parameters[$key]);
        }
	$item->store();
        
        return $item;
    }
	
    /**
     * Gets idcontentversions by where clause
     *
     * @param string $where
     * @return array $ids
     */
    public function getIdsByWhereClause($where){

        $this->select($where);

        $ids = array();
        while($item = $this->next()){
            $ids[] = $item->get('idcontentversion');
        }

        return $ids;

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
     * @param mixed $id Specifies the ID of item to load
     */
    public function __construct($id = false) {
        parent::__construct(cRegistry::getDbTableName('content_version'), 'idcontentversion');
        $this->setFilters(array(), array());
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * Userdefined setter for item fields.
     *
     * @param string $name
     * @param mixed $value
     * @param bool $safe Flag to run defined inFilter on passed value
     * @todo should return return value of overloaded method
     */
    public function setField($name, $value, $safe = true) {
        parent::setField($name, $value, $safe);
    }

    /**
     * Mark this Content Version as current Content 
     */	
    public function markAsCurrent() {

        // try to get item from database
        $content = new cApiContent();
        $succ = $content->loadByArticleLanguageIdTypeAndTypeId(
            $this->get('idartlang'),
            $this->get('idtype'),
            $this->get('typeid')
        );

        // create new item if none has been found
        if (!$succ) {
            $coll = new cApiContentCollection();
            $content = $coll->createNewItem();
        }

        $content->set('idartlang', $this->get('idartlang'));
        $content->set('idtype', $this->get('idtype'));
        $content->set('typeid', $this->get('typeid'));
        $content->set('value', $this->get('value'));
        $content->set('author', $this->get('author'));
        $content->set('created', $this->get('created'));
        $content->set('lastmodified', $this->get('lastmodified'));
        
        // store item
        $content->store();
        
    }
	
    /**
     * Creates a new, editable Version with same properties as this Content Version
     *
     * @param string $version
     * @param mixed $deleted
     */		
    public function markAsEditable($version, $deleted) {

        $parameters = $this->toArray();
        unset($parameters['idcontentversion']);
        $parameters['version'] = $version;

        $contentVersionColl = new cApiContentVersionCollection();
        $contentVersion = $contentVersionColl->create($parameters);
        if ($deleted == 1) {
            $contentVersion->set('deleted', $deleted);
        }

        $contentVersion->store();

    }
	
    /**
     * Loads a content entry by its article language id, idtype, type id and version.
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
        $db = cRegistry::getDb();
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
            $where = $this->db->prepare('idartlang = %d AND idtype = %d AND typeid = %d AND version <= %d GROUP BY pk desc LIMIT 1', $contentParameters['idArtLang'], $contentParameters['idType'], $contentParameters['typeId'], $contentParameters['version']);
            return $this->_loadByWhereClause($where);
        }
		
    }

}