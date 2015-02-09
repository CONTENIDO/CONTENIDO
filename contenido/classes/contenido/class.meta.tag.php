<?php
/**
 * This file contains the meta tag collection and item class.
 *
 * @package          Core
 * @subpackage       GenericDB_Model
 * @version          SVN Revision $Rev:$
 *
 * @author           Murat Purc <murat@purc.de>
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Metatag collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiMetaTagCollection extends ItemCollection {

    /**
     * Constructor
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['meta_tag'], 'idmetatag');
        $this->_setItemClass('cApiMetaTag');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiArticleLanguageCollection');
        $this->_setJoinPartner('cApiMetaTypeCollection');
    }

    /**
     * Creates a meta tag entry.
     *
     * @param int $iIdArtLang
     * @param int $iIdMetaType
     * @param string $sMetaValue
     * @return cApiMetaTag
     */
    public function create($iIdArtLang, $iIdMetaType, $sMetaValue) {
        $oItem = $this->createNewItem();

        $oItem->set('idartlang', $iIdArtLang, false);
        $oItem->set('idmetatype', $iIdMetaType, false);
        $oItem->set('metavalue', $sMetaValue, false);
        $oItem->store();

        return $oItem;
    }

    /**
     * Returns a meta tag entry by article language and meta type.
     *
     * @param int $iIdArtLang
     * @param int $iIdMetaType
     * @return cApiMetaTag|NULL
     */
    public function fetchByArtLangAndMetaType($iIdArtLang, $iIdMetaType) {
        $this->select('idartlang=' . (int) $iIdArtLang . ' AND idmetatype=' . (int) $iIdMetaType);
        return $this->next();
    }

}

/**
 * Metatag item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiMetaTag extends Item {

    /**
     * Constructor Function
     *
     * @param mixed $mId Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['meta_tag'], 'idmetatag');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Updates meta value of an entry.
     *
     * @param string $sMetaValue
     * @return bool
     */
    public function updateMetaValue($sMetaValue) {
        $this->set('metavalue', $sMetaValue, false);
        return $this->store();
    }

	/**
     * Userdefined setter for meta tag fields.
     *
     * @param string $name
     * @param mixed $value
     * @param bool $bSafe Flag to run defined inFilter on passed value
     */
    public function setField($name, $value, $bSafe = true) {
        switch ($name) {
            case 'idartlang':
                $value = (int) $value;
                break;
			case 'idmetatype':
                $value = (int) $value;
                break;
        }

        return parent::setField($name, $value, $bSafe);
    }
    
    /**
     * Creates a new, editable Version with same properties 
     *
     * @param string $version
     */	
    public function markAsEditable($version) {
        //var_export($this->values);
        $parameters = $this->values;
        $parameters['version'] = $version;
        $metaTagVersionColl = new cApiMetaTagVersionCollection();
        $metaTagVersionColl->create(
            $this->getField('idmetatag'),
            $this->getField('idartlang'),
            $this->getField('idmetatype'),
            $this->getField('metavalue'),
            $version
        );
        
    }

}
