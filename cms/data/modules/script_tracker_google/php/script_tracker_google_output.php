<?php
/**
 * Description: Google Analytics Tracking
 *
 * @package Module
 * @subpackage ScriptTrackerGoogle
 * @version SVN Revision $Rev:$
 *
 * @author simon.sprankel@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

$account = getEffectiveSetting('stats', 'ga_account', '');

if (0 < strlen(trim($account)) && cRegistry::isTrackingAllowed()) {
    $tpl = cSmartyFrontend::getInstance();
    $tpl->assign('account', $account);
    $tpl->display('get.tpl');
}

?>