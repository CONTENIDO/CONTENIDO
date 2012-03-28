<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Layout class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO API
 * @version    1.1.1
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2004-08-07
 *   modified 2011-03-15, Murat Purc, adapted to new GenericDB, partly ported to PHP 5, formatting
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


/**
 * Layout collection
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiLayoutCollection extends ItemCollection
{
    public function __construct()
    {
        global $cfg;
        parent::__construct($cfg['tab']['lay'], 'idlay');
        $this->_setItemClass('cApiLayout');
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiLayoutCollection()
    {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }

    /**
     * Creates a layout entry.
     *
     * @param  string  $name
     * @param  int  $idclient
     * @param  string  $alias
     * @param  string  $description
     * @param  int  $deletable  Either 1 or 0
     * @param  string  $author
     * @param  string  $created
     * @param  string  $lastmodified
     * @return cApiLayout
     */
    public function create($name, $idclient = null, $alias = '', $description = '', $deletable = 1, $author = '', $created = '', $lastmodified = '')
    {
        global $client, $auth;

        if (null === $idclient) {
            $idclient = $client;
        }
        $deletable = ($deletable == 1) ? 1 : 0;
        if (empty($author)) {
            $author = $auth->auth['uname'];
        }
        if (empty($created)) {
            $created = date('Y-m-d H:i:s');
        }
        if (empty($lastmodified)) {
            $lastmodified = date('Y-m-d H:i:s');
        }

        $item = parent::create();
        $item->set('idclient', (int) $idclient);
        $item->set('name', $this->escape($name));
        $item->set('alias', $this->escape($alias));
        $item->set('description', $this->escape($description));
        $item->set('deletable', $deletable);
        $item->set('author', $this->escape($author));
        $item->set('created', $this->escape($created));
        $item->set('lastmodified', $this->escape($lastmodified));
        $item->store();
        return ($item);
    }
}


/**
 * Layout item
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiLayout extends Item
{
    /**
     * List of templates being used by current layout
     * @var array
     */
    protected $_aUsedTemplates = array();

    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['lay'], 'idlay');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiLayout($mId = false)
    {
        cDeprecated("Use __construct() instead");
        $this->__construct($mId);
    }

    /**
     * Checks if the layout is in use in any templates.
     * @param   bool  $setData  Flag to set used templates data structure
     * @return  bool
     * @throws  Exception  If layout item was not loaded before
     */
    public function isInUse($setData = false)
    {
        if ($this->virgin) {
            throw new Exception('Layout item not loaded!');
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
     * @return array template data
     */
    public function getUsedTemplates()
    {
        return $this->_aUsedTemplates;
    }

}

?>