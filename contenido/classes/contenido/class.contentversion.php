<?php

/**
 * This file contains the content version collection and item class.
 *
 * @package          Core
 * @subpackage       GenericDB_Model
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
 * @method cApiContentVersion createNewItem
 * @method cApiContentVersion|bool next
 */
class cApiContentVersionCollection extends ItemCollection {
    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
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
     * @param mixed[] $parameters {
     *
     * @return cApiContentVersion
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
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
     * @throws cDbException
     * @throws cException
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
class cApiContentVersion extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id
     *         Specifies the ID of item to load
     *
     * @throws cDbException
     * @throws cException
     */
    public function __construct($id = false) {
        parent::__construct(cRegistry::getDbTableName('content_version'), 'idcontentversion');
        $this->setFilters(array(), array());
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * User-defined setter for item fields.
     *
     * @param string $name
     * @param mixed $value
     * @param bool $safe
     *         Flag to run defined inFilter on passed value
     *
     * @return bool
     */
    public function setField($name, $value, $safe = true) {
        return parent::setField($name, $value, $safe);
    }

    /**
     * Mark this Content Version as current Content
     *
     * @throws cException
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

        // update/set attributes
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
     * @param mixed  $deleted
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function markAsEditable($version, $deleted) {

        // get parameters for editable version
        $parameters = $this->toArray();
        unset($parameters['idcontentversion']);
        $parameters['version'] = $version;

        // create editable version
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
     * @param mixed $contentParameters []{
     *
     * @return bool
     *
     * @throws cException
     */
    public function loadByArticleLanguageIdTypeTypeIdAndVersion(array $contentParameters) {
        $props = array(
            'idartlang' => $contentParameters['idartlang'],
            'idtype' => $contentParameters['idtype'],
            'typeid' => $contentParameters['typeid'],
            'version' => $contentParameters['version']
        );
        $recordSet = $this->_oCache->getItemByProperties($props);
        if ($recordSet) {
            // entry in cache found, load entry from cache
            $this->loadByRecordSet($recordSet);
            return true;
        } else {
            $where = $this->db->prepare('idartlang = %d AND idtype = %d AND typeid = %d AND version <= %d GROUP BY pk desc LIMIT 1', $contentParameters['idartlang'], $contentParameters['idtype'], $contentParameters['typeid'], $contentParameters['version']);
            return $this->_loadByWhereClause($where);
        }

    }

}
