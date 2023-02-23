<?php
/**
 * Description: Google Analytics Tracking
 *
 * @package Module
 * @subpackage ScriptTrackerGoogle
 * @author simon.sprankel@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
 */

$account = getEffectiveSetting('stats', 'ga_account', '');

if (0 < cString::getStringLength(trim($account)) && cRegistry::isTrackingAllowed() && !cRegistry::isBackendEditMode()) {
    $tpl = cSmartyFrontend::getInstance();
    $tpl->assign('account', $account);
    $tpl->display('get.tpl');
}

?>