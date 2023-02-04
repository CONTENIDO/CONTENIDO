<?php

/**
 * This file contains the template configuration collection and item class.
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Template configuration collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @method cApiTemplateConfiguration createNewItem
 * @method cApiTemplateConfiguration|bool next
 */
class cApiTemplateConfigurationCollection extends ItemCollection {
    /**
     * Constructor to create an instance of this class.
     *
     * @param bool $select [optional]
     *                     where clause to use for selection (see ItemCollection::select())
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function __construct($select = false) {
        parent::__construct(cRegistry::getDbTableName('tpl_conf'), 'idtplcfg');
        $this->_setItemClass('cApiTemplateConfiguration');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiTemplateCollection');

        if ($select !== false) {
            $this->select($select);
        }
    }

    /**
     * Deletes template configuration entry, removes also all related container
     * configurations.
     *
     * @param int $idtplcfg
     *
     * @return bool
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function delete($idtplcfg) {
        $result = parent::delete($idtplcfg);

        // Delete also all container configurations
        $oContainerConfColl = new cApiContainerConfigurationCollection('idtplcfg = ' . (int) $idtplcfg);
        $oContainerConfColl->deleteByWhereClause('idtplcfg = ' . (int) $idtplcfg);

        return $result;
    }

    /**
     * Creates a template config item entry
     *
     * @param int    $idtpl
     * @param int    $status       [optional]
     * @param string $author       [optional]
     * @param string $created      [optional]
     * @param string $lastmodified [optional]
     *
     * @return cApiTemplateConfiguration
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function create($idtpl, $status = 0, $author = '', $created = '', $lastmodified = '') {
        if (empty($author)) {
            $auth = cRegistry::getAuth();
            $author = $auth->auth['uname'];
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
     * If there is a pre-configuration of template, copy its settings into
     * template configuration
     *
     * @param int $idtpl
     * @param int $idtplcfg
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function copyTemplatePreconfiguration($idtpl, $idtplcfg) {
        $oTemplateColl = new cApiTemplateCollection('idtpl = ' . (int) $idtpl);

        if (($oTemplate = $oTemplateColl->next()) !== false) {
            if ($oTemplate->get('idtplcfg') > 0) {
                $oContainerConfColl = new cApiContainerConfigurationCollection('idtplcfg = ' . $oTemplate->get('idtplcfg'));
                $aStandardConfig = [];
                while (($oContainerConf = $oContainerConfColl->next()) !== false) {
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
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiTemplateConfiguration extends Item
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
        parent::__construct(cRegistry::getDbTableName('tpl_conf'), 'idtplcfg');
        $this->setFilters([], []);
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * User-defined setter for template configuration fields.
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
            case 'idtpl':
            case 'status':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $bSafe);
    }
}
