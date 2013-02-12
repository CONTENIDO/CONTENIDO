<?php
/**
 * Description: Google Analytics Tracking
 *
 * @package Module
 * @subpackage script_tracker_goog
 * @version SVN Revision $Rev:$
 * @author simon.sprankel@4fb.de
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

$account = getEffectiveSetting('stats', 'ga_account', '');

if ($account != '' && cRegistry::isTrackingAllowed()) {
    $tpl = Contenido_SmartyWrapper::getInstance();
    global $force;
    if (1 == $force) {
        $tpl->clearAllCache();
    }
    $tpl->assign('account', $account);
    $tpl->display('get.tpl');
}

?>