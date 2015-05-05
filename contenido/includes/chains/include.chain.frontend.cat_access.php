<?php
/**
 * CONTENIDO Chain.
 * Category access feature.
 *
 * @package          Core
 * @subpackage       Chain
 * @version          SVN Revision $Rev:$
 *
 * @author           Unknown
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');


function cecFrontendCategoryAccess($idlang, $idcat, $user)
{
    global $cfg;

    $db = cRegistry::getDb();

    $frontendUser = new cApiFrontendUser();
    $frontendUser->loadByPrimaryKey($user);

    if (true !== $frontendUser->isLoaded()) {
        return false;
    }

    $groups = $frontendUser->getGroupsForUser();

    $frontendPermissionCollection = new cApiFrontendPermissionCollection();

    $sql = "SELECT idcatlang FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat = " . cSecurity::toInteger($idcat) . " AND idlang = " . cSecurity::toInteger($idlang);
    $db->query($sql);

    if ($db->nextRecord()) {
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