<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * Custom properties
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.2
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * @deprecated [2011-10-11] Use new classes in contenido/classes/contenido/class.property.php
 *                          - Use cApiPropertyCollection instead of PropertyCollection
 *                          - Use cApiProperty instead of PropertyItem
 * 
 * {@internal 
 *   created 2003-12-21
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *   modified 2009-09-27, Dominik Ziegler, fixed wrong (un)escaping
 *   modified 2011-02-05, Murat Purc, cleanup, formatting and documentation.
 *   modified 2011-03-14, Murat Purc, adapted to new GenericDB, partly ported to PHP 5, formatting
 *   modified 2011-10-11, Murat Purc, removed in favor of normalizing the API
 *
 *   $Id$:
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

?>