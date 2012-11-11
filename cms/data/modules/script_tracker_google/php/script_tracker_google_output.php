<?php
/**
 * Description: Google Analytics Tracking
 *
 * @package Module
 * @subpackage content_header_first
 * @version SVN Revision $Rev:$
 * @author unkown
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

$account = getEffectiveSetting('stats', 'ga_account', '');

if ($account != '' && cRegistry::isTrackingAllowed()) {
    $tpl = new cTemplate();
    $tpl->set('s', 'account', $account);
    $tpl->generate('googleanalytics.html');
}

?>