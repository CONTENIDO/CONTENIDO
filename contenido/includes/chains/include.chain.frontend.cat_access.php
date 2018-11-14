<?php

/**
 * CONTENIDO Chain.
 * Category access feature.
 *
 * @package          Core
 * @subpackage       Chain
 * @author           Unknown
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 *
 * @param int $idlang
 * @param int $idcat
 * @param int $idfrontenduser
 *
 * @return bool
 * 
 * @throws cDbException
 * @throws cException
 */
function cecFrontendCategoryAccess($idlang, $idcat, $idfrontenduser) {

    global $cfg;

    // get idcatlang from idcat & lang
    // TODO should use cApiCategoryLanguage::loadByCategoryIdAndLanguageId()
    $db = cRegistry::getDb();
    $db->query("
        SELECT
            idcatlang
        FROM
            " . $cfg["tab"]["cat_lang"] . "
        WHERE
            idcat = " . cSecurity::toInteger($idcat) . "
            AND idlang = " . cSecurity::toInteger($idlang));
    if ($db->nextRecord()) {
        $idcatlang = $db->f('idcatlang');
    } else {
        return false;
    }

    // get frontend user
    $frontendUser = new cApiFrontendUser();
    $frontendUser->loadByPrimaryKey($idfrontenduser);
    if (true !== $frontendUser->isLoaded()) {
        return false;
    }

    // check if frontend user has access through any group he belongs to
    $coll = new cApiFrontendPermissionCollection();
    foreach ($frontendUser->getGroupsForUser() as $group) {
        if ($coll->checkPerm($group, 'category', 'access', $idcatlang)) {
            return true;
        }
    }

    return false;
}

?>