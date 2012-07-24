<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Template access class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO API
 * @version    1.3
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2004-08-04
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


/**
 * Template configuration collection
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiTemplateConfigurationCollection extends ItemCollection
{
    public function __construct($select = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['tpl_conf'], 'idtplcfg');
        $this->_setItemClass('cApiTemplateConfiguration');
        if ($select !== false) {
            $this->select($select);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiTemplateConfigurationCollection($select = false)
    {
        cDeprecated("Use __construct() instead");
        $this->__construct($select);
    }

    /**
     * Deletes template configuration entry, removes also all related container configurations.
     * @param  int  $idtplcfg
     * @return  bool
     */
    public function delete($idtplcfg)
    {
        $result = parent::delete($idtplcfg);

        // Delete also all container configurations
        $oContainerConfColl = new cApiContainerConfigurationCollection('idtplcfg=' . (int) $idtplcfg);
        $oContainerConfColl->deleteByWhereClause('idtplcfg=' . (int) $idtplcfg);

        return $result;
    }

    /**
     * Creates an template config item entry
     *
     * @param   int     $idtpl
     * @param   int     $status
     * @param   string  $author
     * @param   string  $created
     * @param   string  $lastmodified
     * @return  cApiTemplateConfiguration
     */
    public function create($idtpl, $status = 0, $author = '', $created = '', $lastmodified = '')
    {
        global $auth;

        if (empty($author)) {
            $author = $auth->auth['uname'];
        }
        if (empty($created)) {
            $created = date('Y-m-d H:i:s');
        }
        if (empty($lastmodified)) {
            $lastmodified = '0000-00-00 00:00:00';
        }

        $item = parent::createNewItem();
        $item->set('idtpl', $idtpl);
        $item->set('author', $author);
        $item->set('status', $status);
        $item->set('created', $created);
        $item->set('lastmodified', $lastmodified);
        $item->store();

        $newidtplcfg = $item->get('idtplcfg');

        // If there is a preconfiguration of template, copy its settings into templateconfiguration
        $this->copyTemplatePreconfiguration($idtpl, $newidtplcfg);

        return $item;
    }

    /**
     * If there is a preconfiguration of template, copy its settings into templateconfiguration
     *
     * @param  int  $idtpl
     * @param  int  $idtplcfg
     */
    public function copyTemplatePreconfiguration($idtpl, $idtplcfg) {
        $oTemplateColl = new cApiTemplateCollection('idtpl=' . (int) $idtpl);

        if ($oTemplate = $oTemplateColl->next()) {
            if ($oTemplate->get('idtplcfg') > 0) {
                $oContainerConfColl = new cApiContainerConfigurationCollection('idtplcfg = ' . $oTemplate->get('idtplcfg'));
                $aStandardconfig = array();
                while ($oContainerConf = $oContainerConfColl->next()) {
                    $aStandardconfig[$oContainerConf->get('number')] = $oContainerConf->get('container');
                }

                foreach ($aStandardconfig as $number => $container) {
                    $oContainerConfColl->create($idtplcfg, $number, $container);
                }
            }
        }
    }
}


/**
 * Template configuration item
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiTemplateConfiguration extends Item
{
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['tpl_conf'], 'idtplcfg');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiTemplateConfiguration($mId = false)
    {
        cDeprecated("Use __construct() instead");
        $this->__construct($mId = false);
    }

    /**
     * Userdefined setter for template configuration fields.
     * @param  string  $name
     * @param  mixed   $value
     * @param  bool    $bSafe   Flag to run defined inFilter on passed value
     */
    public function setField($name, $value, $bSafe = true) {
        switch ($name) {
            case 'idtpl':
            case 'status':
                $value = (int) $value;
                break;
        }

        if (is_string($value)) {
            $value = $this->escape($value);
        }

        parent::setField($name, $value, $bSafe);
    }

}

?>