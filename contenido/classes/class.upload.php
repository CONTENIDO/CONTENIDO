<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Module history
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.3
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * @deprecated [2011-10-06] Use new classes in contenido/classes/contenido/class.upload.php
 *                          - Use cApiUploadCollection instead of UploadCollection
 *                          - Use cApiUpload instead of UploadItem
 *
 * {@internal
 *   created  2003-12-14
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *   modified 2008-10-03, Oliver Lohkemper, modified UploadCollection::delete()
 *   modified 2008-10-03, Oliver Lohkemper, add CEC in UploadCollection::store()
 *   modified 2011-03-14, Murat Purc, adapted to new GenericDB, partly ported to PHP 5, formatting
 *   modified 2011-06-29, Murat Purc, added deleteByDirname() and basic properties handling,
 *                        formatted and documented code
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

?>