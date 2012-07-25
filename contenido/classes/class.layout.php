<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Class for layout information and management
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.0.2
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * @deprecated  [2012-03-15] This class is no more used in CONTENIDO and will be
 *              removed in the future. Use cApiLayoutCollection or cApiLayout.
 *
 * {@internal
 *   created unknown
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *   modified 2008-07-02, Frederic Schneider, change sql-escapes
 *   modified 2009-10-27, OliverL, replace toInteger() to escapeString() in function getLayoutID()
 *
 *   modified 2010-08-17, Munkh-Ulzii Balidar,
 *    - changed the code compatible to php5
 *    - added new property aUsedTemplates and saved the information of used templates
 *    - added new method getUsedTemplates
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/** @deprecated  [2012-03-15] Use cApiLayoutCollection or cApiLayout */
class Layout {
    private $_aUsedTemplates = array();

    public function __construct()
    {
        cDeprecated("Use cApiLayoutCollection or cApiLayout instead");
    }

    public function getAvailableLayouts()
    {
        cDeprecated("Use cApiLayoutCollection->select() instead");
        $aLayouts = array();
        $oLayoutColl = new cApiLayoutCollection();
        $oLayoutColl->select();
        while ($oItem = $oLayoutColl->next()) {
            $aLayouts[$oItem->get('idlay')] = array('name' => $oItem->get('name'));
        }
        return $aLayouts;
    }

    public function getLayoutName($layout)
    {
        cDeprecated("Use cApiLayout() instead");
        $oLayout = new cApiLayout((int) $layout);
        return ($oLayout->get('name')) ? $oLayout->get('name') : '';
    }

    public function getLayoutID($layout)
    {
        cDeprecated("Use cApiLayout() instead");
        $oLayout = new cApiLayout();
        return ($oLayout->loadBy('name', $layout)) ? $oLayout->get('idlay') : 0;
    }

    public function layoutInUse($layout, $bSetData = false)
    {
        cDeprecated("Use cApiLayout->isInUse instead");
        if (!is_numeric($layout)) {
            $layout = $this->getLayoutID($layout);
        }
        $oLay = new cApiLayout($layout);
        $result = $oLay->isInUse($bSetData);
        if ($result && $bSetData === true) {
            $this->_aUsedTemplates = $oLay->getUsedTemplates();
        }
        return $result;
    }

    public function getUsedTemplates()
    {
        return $this->_aUsedTemplates;
    }

}

?>