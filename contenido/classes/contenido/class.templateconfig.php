<?php

/**
 * This file contains the template configuration collection and item class.
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
 * Template configuration collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @extends ItemCollection<cApiTemplateConfiguration>
 */
class cApiTemplateConfigurationCollection extends ItemCollection
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param string|false $select [optional] Where clause to use for selection {@see ItemCollection::select()}
     * @throws cDbException|cInvalidArgumentException
     */
    public function __construct($select = false)
    {
        parent::__construct(cDb::getTableName('tpl_conf'), 'idtplcfg');
        $this->_setItemClass('cApiTemplateConfiguration');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiTemplateCollection');

        if ($select !== false) {
            $this->select($select);
        }
    }

    /**
     * Deletes template configuration entry, removes also all related container configurations.
     *
     * @inheritDoc
     * @param int $id The template configuration id.
     * @throws cDbException|cInvalidArgumentException
     */
    public function delete($id)
    {
        $id = cSecurity::toInteger($id);

        // Delete also all container configurations
        $containerConfColl = new cApiContainerConfigurationCollection();
        $containerConfColl->deleteByWhereClause(sprintf('`idtplcfg` = %s', $id));

        return parent::delete($id);
    }

    /**
     * Creates a template config item entry
     *
     * @param int $templateId
     * @param int $status [optional]
     * @param string $author [optional]
     * @param string $created [optional]
     * @param string $lastModified [optional]
     * @return cApiTemplateConfiguration
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($templateId, $status = 0, $author = '', $created = '', $lastModified = '')
    {
        if (empty($author)) {
            $author = cRegistry::getAuth()->getUsername();
        }
        if (empty($created)) {
            $created = date('Y-m-d H:i:s');
        }
        if (empty($lastModified)) {
            $lastModified = '0000-00-00 00:00:00';
        }

        $item = $this->createNewItem();
        $item->set('idtpl', $templateId);
        $item->set('author', $author);
        $item->set('status', $status);
        $item->set('created', $created);
        $item->set('lastmodified', $lastModified);
        $item->store();

        return $item;
    }

    /**
     * If there is a pre-configuration of template, copy its settings into template configuration
     *
     * @param int $templateId
     * @param int $templateConfigurationId
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function copyTemplatePreconfiguration($templateId, $templateConfigurationId)
    {
        $templateColl = new cApiTemplateCollection(sprintf('`idtpl` = %d', $templateId));

        if (($template = $templateColl->next()) !== false) {
            if ($template->get('idtplcfg') > 0) {
                $containerConfColl = new cApiContainerConfigurationCollection(sprintf(
                    '`idtplcfg` = %d',
                    $template->get('idtplcfg')
                ));
                $standardConfig = [];
                while ($containerConf = $containerConfColl->next()) {
                    $standardConfig[$containerConf->get('number')] = $containerConf->get('container');
                }

                foreach ($standardConfig as $number => $container) {
                    $containerConfColl->create($templateConfigurationId, $number, $container);
                }
            }
        }
    }
}

/**
 * Template configuration item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiTemplateConfiguration extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id The ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('tpl_conf'), 'idtplcfg');
        $this->setFilters();
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * User-defined setter for template configuration fields.
     *
     * @inheritDoc
     */
    public function setField($name, $value, $safe = true)
    {
        switch ($name) {
            case 'idtpl':
            case 'status':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $safe);
    }
}
