<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Display current online user
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.0.1
 * @author     Bilal Arsland
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * @deprecated [2012-03-21] Use new classes in contenido/classes/contenido/class.active.user.php
 *                          - Use cApiOnlineUserCollection instead of ActiveUsers
 *
 * {@internal
 *   created 2008-01-28
 *   modified 2008-02-08, Timo Trautmann, table config added
 *   modified 2008-02-12, Timo Trautmann, bugfix in getWebsiteName
 *   modified 2008-02-18, Timo Trautmann, special functions for mysql replaced
 *   modified 2008-06-30, Frederic Schneider, add security fix
 *   modified 2008-09-08, Timo Trautmann, fixed string concat bug at websitenames
 *
 *   $Id$;
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

?>