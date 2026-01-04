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
     * @param int $id
     * @throws cDbException|cInvalidArgumentException
     */
    public function delete($id)
    {
        $id = cSecurity::toInteger($id);

        // Delete also all container configurations
        $oContainerConfColl = new cApiContainerConfigurationCollection('`idtplcfg` = ' . $id);
        $oContainerConfColl->deleteByWhereClause('`idtplcfg` = ' . $id);

        return parent::delete($id);
    }

    /**
     * Creates a template config item entry
     *
     * @param int $idtpl
     * @param int $status [optional]
     * @param string $author [optional]
     * @param string $created [optional]
     * @param string $lastmodified [optional]
     * @return cApiTemplateConfiguration
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($idtpl, $status = 0, $author = '', $created = '', $lastmodified = '')
    {
        if (empty($author)) {
            $auth = cRegistry::getAuth();
            $author = $auth->getUsername();
        }
        if (empty($created)) {
            $created = date('Y-m-d H:i:s');
        }
        if (empty($lastmodified)) {
            $lastmodified = '0000-00-00 00:00:00';
        }

        $item = $this->createNewItem();
        $item->set('idtpl', $idtpl);
        $item->set('author', $author);
        $item->set('status', $status);
        $item->set('created', $created);
        $item->set('lastmodified', $lastmodified);
        $item->store();

        return $item;
    }

    /**
     * If there is a pre-configuration of template, copy its settings into template configuration
     *
     * @param int $idtpl
     * @param int $idtplcfg
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function copyTemplatePreconfiguration($idtpl, $idtplcfg)
    {
        $oTemplateColl = new cApiTemplateCollection('idtpl = ' . (int)$idtpl);

        if (($oTemplate = $oTemplateColl->next()) !== false) {
            if ($oTemplate->get('idtplcfg') > 0) {
                $oContainerConfColl = new cApiContainerConfigurationCollection('idtplcfg = ' . $oTemplate->get('idtplcfg'));
                $aStandardConfig = [];
                while ($oContainerConf = $oContainerConfColl->next()) {
                    $aStandardConfig[$oContainerConf->get('number')] = $oContainerConf->get('container');
                }

                foreach ($aStandardConfig as $number => $container) {
                    $oContainerConfColl->create($idtplcfg, $number, $container);
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
     * @param mixed $id Specifies the ID of item to load
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
