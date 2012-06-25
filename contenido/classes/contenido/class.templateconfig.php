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

    public function delete($idtplcfg)
    {
        $result = parent::delete($idtplcfg);
        $oContainerConfCollection = new cApiContainerConfigurationCollection('idtplcfg=' . (int) $idtplcfg);
        $aDelContainerConfIds = array();
        while ($oContainerConf = $oContainerConfCollection->next()) {
            array_push($aDelContainerConfIds, $oContainerConf->get('idcontainerc'));
        }

        foreach($aDelContainerConfIds as $iDelContainerConfId) {
            $oContainerConfCollection->delete($iDelContainerConfId);
        }
        return $result;
    }

    public function create($idtpl)
    {
        global $auth;

        $item = parent::createNewItem();
        $item->set('idtpl', $idtpl);
        $item->set('author', $auth->auth['uname']);
        $item->set('status', 0);
        $item->set('created', date('YmdHis'));
        $item->set('lastmodified', '0000-00-00 00:00:00');
        $item->store();

        $iNewTplCfgId = $item->get('idtplcfg');

        #if there is a preconfiguration of template, copy its settings into templateconfiguration
        $templateCollection = new cApiTemplateCollection('idtpl=' . (int) $idtpl);

        if ($template = $templateCollection->next()) {
            $idTplcfgStandard = $template->get('idtplcfg');
            if ($idTplcfgStandard > 0) {
                $oContainerConfCollection = new cApiContainerConfigurationCollection('idtplcfg=' . $idTplcfgStandard);
                $aStandardconfig = array();
                while ($oContainerConf = $oContainerConfCollection->next()) {
                    $aStandardconfig[$oContainerConf->get('number')] = $oContainerConf->get('container');
                }

                foreach ($aStandardconfig as $iContainernumber => $sContainer) {
                    $oContainerConfCollection->create($iNewTplCfgId, $iContainernumber, $sContainer);
                }
            }
        }

        return $item;
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
}

?>