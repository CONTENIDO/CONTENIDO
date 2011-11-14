<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Metatag management class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    0.1
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2011-07-20
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


class cApiMetaTagCollection extends ItemCollection
{
    /**
     * Constructor
     */
    public function __construct()
    {
        global $cfg;
        parent::__construct($cfg['tab']['meta_tag'], 'idmetatag');
        $this->_setItemClass('cApiMetaTag');
    }

    /**
     * Creates a meta tag entry.
     * @param int $iIdArtLang
     * @param int $iIdMetaType
     * @param string $sMetaValue
     * @return cApiMetaTag
     */
    public function create($iIdArtLang, $iIdMetaType, $sMetaValue)
    {
        $oItem = parent::create();

        $oItem->set('idartlang', (int) $iIdArtLang, false);
        $oItem->set('idmetatype', (int) $iIdMetaType, false);
        $oItem->set('metavalue', $this->escape($sMetaValue), false);
        $oItem->store();

        return $oItem;
    }

    /**
     * Returns a meta tag entry by article language and meta type.
     * @param int $iIdArtLang
     * @param int $iIdMetaType
     * @return cApiMetaTag|null
     */
    public function selectByArtLangAndMetaType($iIdArtLang, $iIdMetaType)
    {
        $this->select('idartlang=' . (int) $iIdArtLang . ' AND idmetatype=' . (int) $iIdMetaType);
        return $this->next();
    }

}


/**
 * Class cApiMetaTag
 */
class cApiMetaTag extends Item
{

    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['meta_tag'], 'idmetatag');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Updates meta value of an entry.
     * @param   string  $sMetaValue
     * @return  bool
     */
    public function updateMetaValue($sMetaValue)
    {
        $this->set('metavalue', $this->escape($sMetaValue), false);
        return $this->store();
    }

}

?>