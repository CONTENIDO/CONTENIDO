<?php

/**
 * This file contains the layout collection and item class.
 *
 * @package          Core
 * @subpackage       GenericDB_Model
 * @author           Timo Hummel
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Layout collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiLayoutCollection extends ItemCollection {

    /**
     * Constructor to create an instance of this class.
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['lay'], 'idlay');
        $this->_setItemClass('cApiLayout');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiClientCollection');
    }

    /**
     * Creates a layout entry.
     *
     * @param string $name
     * @param int $idclient [optional]
     * @param string $alias [optional]
     * @param string $description [optional]
     * @param int $deletable [optional]
     *         Either 1 or 0
     * @param string $author [optional]
     * @param string $created [optional]
     * @param string $lastmodified [optional]
     * @return cApiLayout
     */
    public function create($name, $idclient = NULL, $alias = '', $description = '', $deletable = 1, $author = '', $created = '', $lastmodified = '') {
        global $client, $auth;

        if (NULL === $idclient) {
            $idclient = $client;
        }

        if (empty($alias)) {
            $alias = cString::toLowerCase(cString::cleanURLCharacters(i18n("-- New layout --")));
        }

        if (empty($author)) {
            $author = $auth->auth['uname'];
        }
        if (empty($created)) {
            $created = date('Y-m-d H:i:s');
        }
        if (empty($lastmodified)) {
            $lastmodified = date('Y-m-d H:i:s');
        }

        $item = $this->createNewItem();
        $item->set('idclient', $idclient);
        $item->set('name', $name);
        $item->set('alias', $alias);
        $item->set('description', $description);
        $item->set('deletable', $deletable);
        $item->set('author', $author);
        $item->set('created', $created);
        $item->set('lastmodified', $lastmodified);
        $item->store();

        return $item;
    }

}

/**
 * Layout item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiLayout extends Item {

    /**
     * List of templates being used by current layout
     *
     * @var array
     */
    protected $_aUsedTemplates = array();

    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $mId [optional]
     *         Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['lay'], 'idlay');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Checks if the layout is in use in any templates.
     *
     * @throws cException
     *         If layout item has not been loaded before
     * @param bool $setData [optional]
     *         Flag to set used templates data structure
     * @return bool
     */
    public function isInUse($setData = false) {
        if (!$this->isLoaded()) {
            throw new cException('Layout item not loaded!');
        }

        $oTplColl = new cApiTemplateCollection();
        $templates = $oTplColl->fetchByIdLay($this->get('idlay'));
        if (0 === count($templates)) {
            return false;
        }

        if ($setData === true) {
            $this->_aUsedTemplates = array();
            foreach ($templates as $i => $template) {
                $this->_aUsedTemplates[$i] = array(
                    'tpl_id' => $template->get('idtpl'),
                    'tpl_name' => $template->get('name')
                );
            }
        }

        return true;
    }

    /**
     * Get the informations of used templates
     *
     * @return array
     *         template data
     */
    public function getUsedTemplates() {
        return $this->_aUsedTemplates;
    }

    /**
     * Userdefined setter for layout fields.
     *
     * @param string $name
     * @param mixed $value
     * @param bool $bSafe [optional]
     *         Flag to run defined inFilter on passed value
     * @return bool
     */
    public function setField($name, $value, $bSafe = true) {
        switch ($name) {
            case 'deletable':
                $value = ($value == 1) ? 1 : 0;
                break;
            case 'idclient':
                $value = (int) $value;
                break;
        }

        return parent::setField($name, $value, $bSafe);
    }

}
