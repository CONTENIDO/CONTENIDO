<?php
/**
 * Description: Output standard h2 subheadline
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

$sText = "CMS_HTMLHEAD[2]";

if (!isset($tpl) || !is_object($tpl)) {
    $tpl = new Template();
}

$tpl->reset();
if ($contenido && $edit) {
    $tpl->set('s', 'text', $sText);
    $tpl->generate('templates/subheadline_h2.html');
} else {
    if ($sText != '') {
        $tpl->set('s', 'text', strip_tags($sText));
        $tpl->generate('templates/subheadline_h2.html');
    }
}

?>