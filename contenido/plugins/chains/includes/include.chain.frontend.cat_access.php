<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 *
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend classes
 * @version    1.0
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created
 *   modified 2008-07-04, bilal arslan, added security fix
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


function cecFrontendCategoryAccess($idlang, $idcat, $user)
{
    global $cfg;

    $db = new DB_Contenido();

    $frontendUser = new FrontendUser();
    $frontendUser->loadByPrimaryKey($user);

    if ($frontendUser->virgin) {
        return false;
    }

    $groups = $frontendUser->getGroupsForUser();

    $frontendPermissionCollection = new FrontendPermissionCollection();

    $sql = "SELECT idcatlang FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat = " . Contenido_Security::toInteger($idcat) . " AND idlang = " . Contenido_Security::toInteger($idlang);
    $db->query($sql);

    if ($db->next_record()) {
        $idcatlang = $db->f("idcatlang");
    } else {
        return false;
    }

    foreach ($groups as $group) {
        $allow = $frontendPermissionCollection->checkPerm($group, "category", "access", $idcatlang);
        if ($allow == true) {
            return true;
        }
    }

    return false;
}

?>