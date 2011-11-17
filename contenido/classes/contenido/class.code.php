<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Code management class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO API
 * @version    0.1
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2011-07-19
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


/**
 * Code collection
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiCodeCollection extends ItemCollection
{
    /**
     * Constructor
     */
    public function __construct()
    {
        global $cfg;
        parent::__construct($cfg['tab']['code'], 'idcode');
        $this->_setItemClass('cApiCode');
    }

    /**
     * Creates a code entry.
     * @param int $iIdCatArt
     * @param int $iIdLang
     * @param int $iIdClient
     * @param string $sCode
     * @return cApiCode
     */
    public function create($iIdCatArt, $iIdLang, $iIdClient, $sCode)
    {
        $oItem = parent::create();

        $oItem->set('idcatart', (int) $iIdCatArt, false);
        $oItem->set('idlang', (int) $iIdLang, false);
        $oItem->set('idclient', (int) $iIdClient, false);
        $oItem->set('code', $this->escape($sCode), false);
        $oItem->store();

        return $oItem;
    }
    
    /**
     * Returns a code entry by category article and language.
     * @param int $iIdCatArt
     * @param int $iIdLang
     * @return cApiCode|null
     */
    public function selectByCatArtAndLang($iIdCatArt, $iIdLang)
    {
        $this->select('idcatart=' . (int) $iIdCatArt . ' AND idlang=' . (int) $iIdLang);
        return $this->next();
    }

    /**
     * Deletes code by category article.
     * @param int $iIdCatArt
     */
    public function deleteByCatArt($iIdCatArt)
    {
        $this->select('idcatart=' . (int) $iIdCatArt);
        while ($oCode = $this->next()) {
            $this->delete($oCode->get('idcode'));
        }
    }

}


/**
 * Code item
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiCode extends Item
{

    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['code'], 'idcode');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Updates code of an entry.
     * @param   string  $sCode
     * @return  bool
     */
    public function updateCode($sCode)
    {
        $this->set('code', $this->escape($sCode), false);
        return $this->store();
    }
}

?>