<?php

/**
 * This file contains the meta tag collection and item class.
 *
 * @package          Core
 * @subpackage       GenericDB_Model
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
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
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
     * @param int    $iIdArtLang
     * @param int    $iIdMetaType
     * @param string $sMetaValue
     *
     * @return cApiMetaTag
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function create($iIdArtLang, $iIdMetaType, $sMetaValue)
    {
        /** @var cApiMetaTag $item */
        $item = $this->createNewItem();
        $item->set('idartlang', $iIdArtLang, false);
        $item->set('idmetatype', $iIdMetaType, false);
        $item->set('metavalue', $sMetaValue, false);
        $item->store();

        return $item;
    }

    /**
     * Returns a meta tag entry by article language and meta type.
     *
     * @param int $iIdArtLang
     * @param int $iIdMetaType
     * @return cApiMetaTag|NULL
     * @throws cDbException
     * @throws cException
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
class cApiMetaTag extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $mId
     *         Specifies the ID of item to load
     *
     * @throws cDbException
     * @throws cException
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
     * @throws cDbException
     * @throws cInvalidArgumentException
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
     * @param bool $bSafe [optional]
     *         Flag to run defined inFilter on passed value
     * @return bool
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
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function markAsEditable($version) {
        //var_export($this->values);
        //$parameters = $this->values;
        //$parameters['version'] = $version;
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
