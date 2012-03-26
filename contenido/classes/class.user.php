<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO User classes
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.0.4
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * @deprecated [2012-03-22] Use new classes in contenido/classes/contenido/class.user.php
 *                          - Use cApiUserCollection instead of Users
 *                          - Use cApiUser instead of User
 *
 * {@internal
 *   created unknown
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *   modified 2009-05-18, Andreas Lindner, add method getGroupIDsByUserID to class User
 *   modified 2009-12-17, Dominik Ziegler, added support for username fallback
 *   modified 2010-05-20, Oliver Lohkemper, add param forceActive in User::getSystemAdmins()
 *   modified 2011-02-05, Murat Purc, Cleanup/formatting, documentation, standardize
 *                                    getUserProperties()
 *   modified 2011-02-05, Murat Purc, Manage properties related code thru cApiUser
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

?>