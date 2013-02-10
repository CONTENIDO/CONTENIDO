<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Code management class
 *
 * @package CONTENIDO API
 * @version 0.1
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/** @deprecated  [2013-02-10]  This class is not longer supported. */
class cApiCodeCollection extends ItemCollection {

    /** @deprecated  [2013-02-10]  This class is not longer supported. */
    public function __construct() {
        cDeprecated("This class is not longer supported.");
        global $cfg;
        parent::__construct($cfg['tab']['code'], 'idcode');
        $this->_setItemClass('cApiCode');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiCategoryArticleCollection');
        $this->_setJoinPartner('cApiLanguageCollection');
        $this->_setJoinPartner('cApiClientCollection');
    }

    /**
     * Creates a code entry.
     *
     * @param int $iIdCatArt
     * @param int $iIdLang
     * @param int $iIdClient
     * @param string $sCode
     * @return cApiCode
     */
    public function create($iIdCatArt, $iIdLang, $iIdClient, $sCode) {
        $oItem = parent::createNewItem();

        $oItem->set('idcatart', (int) $iIdCatArt, false);
        $oItem->set('idlang', (int) $iIdLang, false);
        $oItem->set('idclient', (int) $iIdClient, false);
        $oItem->set('code', $this->escape($sCode), false);
        $oItem->store();

        return $oItem;
    }

    /**
     * Returns a code entry by category article and language.
     *
     * @param int $iIdCatArt
     * @param int $iIdLang
     * @return cApiCode null
     */
    public function fetchByCatArtAndLang($iIdCatArt, $iIdLang) {
        $this->select('idcatart=' . (int) $iIdCatArt . ' AND idlang=' . (int) $iIdLang);
        return $this->next();
    }

    /**
     * Deletes code by category article.
     *
     * @param int $iIdCatArt
     */
    public function deleteByCatArt($iIdCatArt) {
        $this->select('idcatart=' . (int) $iIdCatArt);
        while (($oCode = $this->next()) !== false) {
            $this->delete($oCode->get('idcode'));
        }
    }

}

/** @deprecated  [2013-02-10]  This class is not longer supported. */
class cApiCode extends Item {

    /** @deprecated  [2013-02-10]  This class is not longer supported. */
    public function __construct($mId = false) {
        cDeprecated("This class is not longer supported.");
        global $cfg;
        parent::__construct($cfg['tab']['code'], 'idcode');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Updates code of an entry.
     *
     * @param string $sCode
     * @return bool
     */
    public function updateCode($sCode) {
        $this->set('code', $this->escape($sCode), false);
        return $this->store();
    }

}
