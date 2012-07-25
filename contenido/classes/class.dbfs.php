<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Database based file system
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.1.1
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * @deprecated [2011-09-19] Use new classes in contenido/classes/contenido/class.dbfs.php
 *                          - Use cApiDbfsCollection instead of DBFSCollection
 *                          - Use cApiDbfs instead of DBFSItem
 *
 * {@internal
 *   created  2003-12-21
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *   modified 2009-10-13, Dominik Ziegler, added "attachment" to Content-Disposition to force browsers downloading the file
 *   modified 2011-03-14, Murat Purc, adapted to new GenericDB, partly ported to PHP 5, formatting
 *   modified 2011-06-02, Murat Purc, Fixed typo in function write()
 *   modified 2011-09-19, Murat Purc, removed in favor of normalizing the API
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


?>