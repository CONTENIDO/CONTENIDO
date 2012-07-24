<?php
/**
 * Description: Google Analytics
 *
 * @version   1.0.0
 * @author    unknown
 * @copyright four for business AG <www.4fb.de>
 *
 * {@internal
 *   created unknown
 *   $Id$
 * }}
 */

$account = getEffectiveSetting('stats', 'ga_account', '');

if ($account != '') {
    $tpl = new cTemplate();
    $tpl->set('s', 'account', $account);
    $tpl->generate('googleanalytics.html');
}
?>