<?php

/**
 * This file contains the layout collection and item class.
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
 * Layout collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @extends ItemCollection<cApiLayout>
 */
class cApiLayoutCollection extends ItemCollection
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
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('lay'), 'idlay');
        $this->_setItemClass('cApiLayout');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiClientCollection');
    }

    /**
     * Creates a layout entry.
     *
     * @param string $name
     * @param int $idclient [optional]
     * @param string $alias [optional]
     * @param string $description [optional]
     * @param int $deletable [optional] Either 1 or 0
     * @param string $author [optional]
     * @param string $created [optional]
     * @param string $lastmodified [optional]
     * @return cApiLayout
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create(
        $name,
        $idclient = NULL,
        $alias = '',
        $description = '',
        $deletable = 1,
        $author = '',
        $created = '',
        $lastmodified = ''
    )
    {
        if (NULL === $idclient) {
            $idclient = cRegistry::getClientId();
        }

        if (empty($alias)) {
            $alias = cString::toLowerCase(cString::cleanURLCharacters(i18n("-- New layout --")));
        }

        if (empty($author)) {
            $auth = cRegistry::getAuth();
            $author = $auth->getUsername();
        }
        if (empty($created)) {
            $created = date('Y-m-d H:i:s');
        }
        if (empty($lastmodified)) {
            $lastmodified = date('Y-m-d H:i:s');
        }

        $item = $this->createNewItem();
        $item->set('idclient', $idclient);
        $item->set('name', $name);
        $item->set('alias', $alias);
        $item->set('description', $description);
        $item->set('deletable', $deletable);
        $item->set('author', $author);
        $item->set('created', $created);
        $item->set('lastmodified', $lastmodified);
        $item->store();

        return $item;
    }

    /**
     * Returns all used layout types.
     *
     * @param ?int $idclient Id of client to limit the result for a specific client
     * @param bool $sort Flag to sort the result
     * @return string[] List of layout types
     * @throws cDbException|cException
     * @since CONTENIDO 4.10.2
     */
    public function getAllUsedLayoutTypesPropertyValues(?int $idclient = NULL, bool $sort = true): array
    {
        $propertyCollection = new cApiPropertyCollection();
        $propertyCollection->addResultField('value');
        if (is_numeric($idclient)) {
            $propertyCollection->setWhere('idclient', $idclient);
        }
        $propertyCollection->setWhere('type', 'layout');
        $propertyCollection->setWhere('name', 'used-types');
        $propertyCollection->query();
        $types = [];
        foreach ($propertyCollection->fetchTable(['value' => 'value']) as $entry) {
            $types = array_merge(explode(';', $entry['value']), $types);
        }
        $types = array_unique($types);
        if ($sort) {
            sort($types);
        }

        return $types;
    }

}

/**
 * Layout item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiLayout extends Item
{

    /**
     * @var array List of templates being used by current layout
     */
    protected $_aUsedTemplates = [];

    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id The ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('lay'), 'idlay');
        $this->setFilters();
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * Checks if the layout is in use in any templates.
     *
     * @param bool $setData  Flag to set used templates data structure
     * @throws cException If layout item has not been loaded before
     */
    public function isInUse(bool $setData = false): bool
    {
        if (!$this->isLoaded()) {
            throw new cException('Layout item not loaded!');
        }

        $oTplColl = new cApiTemplateCollection();
        $templates = $oTplColl->fetchByIdLay($this->get('idlay'));
        if (0 === count($templates)) {
            return false;
        }

        if ($setData === true) {
            $this->_aUsedTemplates = [];
            foreach ($templates as $i => $template) {
                $this->_aUsedTemplates[$i] = [
                    'tpl_id' => $template->get('idtpl'),
                    'tpl_name' => $template->get('name'),
                ];
            }
        }

        return true;
    }

    /**
     * Get the information of used templates
     */
    public function getUsedTemplates(): array
    {
        return $this->_aUsedTemplates;
    }

    /**
     * User-defined setter for layout fields.
     *
     * @inheritDoc
     */
    public function setField($name, $value, $safe = true)
    {
        switch ($name) {
            case 'deletable':
                $value = ($value == 1) ? 1 : 0;
                break;
            case 'idclient':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $safe);
    }

}
