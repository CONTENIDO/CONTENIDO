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
 *
 * @package    CONTENIDO Backend classes
 * @version    1.1
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * @deprecated [2011-09-14] Use new classes in contenido/classes/contenido/class.artspec.php
 *
 * {@internal
 *   created  unknown
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *   modified 2011-03-14, Murat Purc, adapted to new GenericDB, partly ported to PHP 5, formatting
 *
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
class ArtSpecCollection extends cApiArticleSpecificationCollection
{
    public function __construct()
    {
        cWarning(__FILE__, __LINE__, "Deprecated class " . __CLASS__ . " use " . get_parent_class($this));
        parent::__construct();
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function ArtSpecCollection()
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct();
    }
}


/**
 * Article specification Item
 */
class ArtSpecItem extends cApiArticleSpecification
{
    public function __construct($mId = false)
    {
        cWarning(__FILE__, __LINE__, "Deprecated class " . __CLASS__ . " use " . get_parent_class($this));
        parent::__construct($mId);
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function ArtSpecItem($mId = false)
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct($mId);
    }
}

?>