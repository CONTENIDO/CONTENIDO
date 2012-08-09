<?php
/**
 * Description: Display an RSS Feed. Module "Output".
 *
 * @version    1.0.0
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 *
 * {@internal
 *   created 2008-04-07
 *   $Id$
 * }}
 */

if (!isset($tpl) || !is_object($tpl)) {
    $tpl = new cTemplate();
}
$tpl->reset();

$sTargetIdcat = getEffectiveSetting('search-results', 'idcat', '1');
$sTargetIdart = getEffectiveSetting('search-results', 'idart', '1');
$sFormAction = 'front_content.php?idcat='.$sTargetIdcat.'&amp;idart='.$sTargetIdart;

$tpl->set('s', 'form_action', $sFormAction);
$tpl->set('s', 'label_search', mi18n("Suche"));
$tpl->generate('templates/search_input.html');

?>