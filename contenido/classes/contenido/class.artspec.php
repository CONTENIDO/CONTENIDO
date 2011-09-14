<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Article specification class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 * Code is taken over from file contenido/classes/class.artspec.php in favor of
 * normalizing API.
 *
 * @package    CONTENIDO Backend classes
 * @version    0.1
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.9
 *
 * {@internal
 *   created  2011-09-14
 *   $Id$:
 * }}
 *
 */


if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Article specification collection
 */
class cApiArticleSpecificationCollection extends ItemCollection
{
    /**
     * Constructor function
     * @param none
     */
    public function __construct()
    {
        global $cfg;
        parent::__construct($cfg['tab']['art_spec'], 'idartspec');
        $this->_setItemClass('cApiArticleSpecification');
    }
}


/**
 * Article specification item
 */
class cApiArticleSpecification extends Item
{
    /**
     * Constructor function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['art_spec'], 'idartspec');
        $this->_arrInFilters = array();
        $this->_arrOutFilters = array();
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }
}

?>