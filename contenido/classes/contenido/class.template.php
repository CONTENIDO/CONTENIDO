<?php

/**
 * This file contains the template collection and item class.
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
 * Template collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiTemplateCollection extends ItemCollection {

    /**
     * Constructor to create an instance of this class.
     *
     * @param string $select [optional]
     *         where clause to use for selection (see ItemCollection::select())
     */
    public function __construct($select = false) {
        global $cfg;
        parent::__construct($cfg['tab']['tpl'], 'idtpl');
        $this->_setItemClass('cApiTemplate');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiLayoutCollection');
        $this->_setJoinPartner('cApiTemplateCollection');
        $this->_setJoinPartner('cApiTemplateConfigurationCollection');

        if ($select !== false) {
            $this->select($select);
        }
    }

    /**
     * Creates a template entry.
     *
     * @param int $idclient
     * @param int $idlay
     * @param int $idtplcfg
     *         Either a valid template configuration id or an empty string
     * @param string $name
     * @param string $description
     * @param int $deletable [optional]
     * @param int $status [optional]
     * @param int $defaulttemplate [optional]
     * @param string $author [optional]
     * @param string $created [optional]
     * @param string $lastmodified [optional]
     * @return cApiTemplate
     */
    public function create($idclient, $idlay, $idtplcfg, $name, $description,
            $deletable = 1, $status = 0, $defaulttemplate = 0, $author = '',
            $created = '', $lastmodified = '') {
        if (empty($author)) {
            $auth = cRegistry::getAuth();
            $author = $auth->auth['uname'];
        }
        if (empty($created)) {
            $created = date('Y-m-d H:i:s');
        }
        if (empty($lastmodified)) {
            $lastmodified = date('Y-m-d H:i:s');
        }

        $oItem = $this->createNewItem();

        $oItem->set('idclient', $idclient);
        $oItem->set('idlay', $idlay);
        $oItem->set('idtplcfg', $idtplcfg);
        $oItem->set('name', $name);
        $oItem->set('description', $description);
        $oItem->set('deletable', $deletable);
        $oItem->set('status', $status);
        $oItem->set('defaulttemplate', $defaulttemplate);
        $oItem->set('author', $author);
        $oItem->set('created', $created);
        $oItem->set('lastmodified', $lastmodified);
        $oItem->store();

        return $oItem;
    }

    /**
     * Returns the default template configuration item
     *
     * @param int $idclient
     * @return cApiTemplateConfiguration|NULL
     */
    public function selectDefaultTemplate($idclient) {
        $this->select('defaulttemplate = 1 AND idclient = ' . (int) $idclient);
        return $this->next();
    }

    /**
     * Returns all templates having passed layout id.
     *
     * @param int $idlay
     * @return array
     */
    public function fetchByIdLay($idlay) {
        $this->select('idlay = ' . (int) $idlay);
        $entries = array();
        while (($entry = $this->next()) !== false) {
            $entries[] = clone $entry;
        }
        return $entries;
    }
}

/**
 * Template item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiTemplate extends Item {

    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $mId [optional]
     *         Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['tpl'], 'idtpl');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Load a template based on article, category, language and client id
     *
     * @param int $idart
     *         article id
     * @param int $idcat
     *         category id
     * @param int $lang
     *         language id
     * @param int $client
     *         client id
     * @return bool
     */
    public function loadByArticleOrCategory($idart, $idcat, $lang, $client) {

        // get ID of template configuration that is used for
        // either the article language or the category language
        $idtplcfg = conGetTemplateConfigurationIdForArticle($idart, $idcat, $lang, $client);
        if (!is_numeric($idtplcfg) || $idtplcfg == 0) {
            $idtplcfg = conGetTemplateConfigurationIdForCategory($idcat, $lang, $client);
        }
        if (is_null($idtplcfg)) {
            return false;
        }

        // load template configuration to get its template ID
        $templateConfiguration = new cApiTemplateConfiguration($idtplcfg);
        if (!$templateConfiguration->isLoaded()) {
            return false;
        }

        // try to load template by determined ID
        $idtpl = $templateConfiguration->get('idtpl');
        $this->loadByPrimaryKey($idtpl);
        
        return true;
    }

    /**
     * Userdefined setter for template fields.
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
            case 'deletable':
            case 'status':
            case 'defaulttemplate':
                $value = ($value == 1) ? 1 : 0;
                break;
            case 'idclient':
            case 'idlay':
                $value = (int) $value;
                break;
            case 'idtplcfg':
                if (!is_numeric($value)) {
                    $value = '';
                }
                break;
        }

        return parent::setField($name, $value, $bSafe);
    }

}
