<?php

/**
 * This file contains the template collection and item class.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Template collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @extends ItemCollection<cApiTemplate>
 */
class cApiTemplateCollection extends ItemCollection
{

    use cItemCollectionIdsByClientIdTrait;

    /**
     * @var string Client id foreign key field name
     * @since CONTENIDO 4.10.2
     */
    private $fkClientIdName = 'idclient';

    /**
     * Constructor to create an instance of this class.
     *
     * @param string|false $select [optional] Where clause to use for selection {@see ItemCollection::select()}
     * @throws cDbException|cInvalidArgumentException
     */
    public function __construct($select = false)
    {
        parent::__construct(cDb::getTableName('tpl'), 'idtpl');
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
     * @param int $clientId
     * @param int $layoutId
     * @param int $templateConfigurationId Either a valid template configuration id or an empty string
     * @param string $name
     * @param string $description
     * @param int $deletable [optional]
     * @param int $status [optional]
     * @param int $defaultTemplate [optional]
     * @param string $author [optional]
     * @param string $created [optional]
     * @param string $lastModified [optional]
     * @return cApiTemplate
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create(
        $clientId,
        $layoutId,
        $templateConfigurationId,
        $name,
        $description,
        $deletable = 1,
        $status = 0,
        $defaultTemplate = 0,
        $author = '',
        $created = '',
        $lastModified = ''
    )
    {
        if (empty($author)) {
            $author = cRegistry::getAuth()->getUsername();
        }
        if (empty($created)) {
            $created = date('Y-m-d H:i:s');
        }
        if (empty($lastModified)) {
            $lastModified = date('Y-m-d H:i:s');
        }

        $oItem = $this->createNewItem();

        $oItem->set('idclient', $clientId);
        $oItem->set('idlay', $layoutId);
        $oItem->set('idtplcfg', $templateConfigurationId);
        $oItem->set('name', $name);
        $oItem->set('description', $description);
        $oItem->set('deletable', $deletable);
        $oItem->set('status', $status);
        $oItem->set('defaulttemplate', $defaultTemplate);
        $oItem->set('author', $author);
        $oItem->set('created', $created);
        $oItem->set('lastmodified', $lastModified);
        $oItem->store();

        return $oItem;
    }

    /**
     * Returns the default template configuration item
     *
     * @param int $clientId
     * @throws cDbException|cException
     */
    public function selectDefaultTemplate($clientId): ?cApiTemplate
    {
        $this->select('`defaulttemplate` = 1 AND `idclient` = %d', $clientId);
        return (($item = $this->next()) instanceof cApiTemplate) ? $item : null;
    }

    /**
     * Returns all templates having passed layout id.
     *
     * @param int $layoutId
     * @return cApiTemplate[]
     * @throws cDbException|cException
     */
    public function fetchByIdLay($layoutId): array
    {
        $this->select(sprintf('`idlay` = %d', $layoutId));
        $entries = [];
        while ($entry = $this->next()) {
            $entries[] = clone $entry;
        }
        return $entries;
    }

    /**
     * Returns all template ids having passed layout id.
     *
     * @param int $layoutId
     * @return int[]
     * @throws cDbException|cException
     * @since CONTENIDO 4.10.2
     */
    public function getIdsByLayoutId(int $layoutId): array
    {
        $this->db->query(sprintf(
            "SELECT `idtpl` FROM `%s` WHERE `idlay` = %d", $this->getTable(), $layoutId
        ));

        $ids = [];
        while ($this->db->nextRecord()) {
            $ids[] = cSecurity::toInteger($this->db->f('idtpl'));
        }

        return $ids;
    }
}

/**
 * Template item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiTemplate extends Item
{

    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id The ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('tpl'), 'idtpl');
        $this->setFilters();
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * Load a template based on article, category, language and client id
     *
     * @param int $articleId Article id
     * @param int $categoryId Category id
     * @param int $languageId Language id
     * @param int $clientId Client id
     * @throws cDbException|cException
     */
    public function loadByArticleOrCategory($articleId, $categoryId, $languageId, $clientId): bool
    {
        // get ID of template configuration that is used for
        // either the article language or the category language
        $templateConfigurationId = conGetTemplateConfigurationIdForArticle($articleId, $categoryId, $languageId, $clientId);
        if (!is_numeric($templateConfigurationId) || $templateConfigurationId == 0) {
            $templateConfigurationId = conGetTemplateConfigurationIdForCategory($categoryId, $languageId, $clientId);
        }
        if (is_null($templateConfigurationId)) {
            return false;
        }

        // load template configuration to get its template ID
        $templateConfiguration = new cApiTemplateConfiguration($templateConfigurationId);
        if (!$templateConfiguration->isLoaded()) {
            return false;
        }

        // try to load template by determined ID
        $idtpl = $templateConfiguration->get('idtpl');
        $this->loadByPrimaryKey($idtpl);

        return true;
    }

    /**
     * User-defined setter for template fields.
     *
     * @inheritDoc
     */
    public function setField($name, $value, $safe = true)
    {
        switch ($name) {
            case 'deletable':
            case 'status':
            case 'defaulttemplate':
                $value = ($value == 1) ? 1 : 0;
                break;
            case 'idclient':
            case 'idlay':
                $value = cSecurity::toInteger($value);
                break;
            case 'idtplcfg':
                if (!is_numeric($value)) {
                    $value = '';
                }
                break;
        }

        return parent::setField($name, $value, $safe);
    }

}
