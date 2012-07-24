<?php
/**
 * Description: Output standard h1 headline
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

$sText = "CMS_HTMLHEAD[1]";

$tpl->reset();
if ($contenido && $edit) {
    $tpl->set('s', 'text', $sText);
    $tpl->generate('templates/headline_h1.html');
} else {
    if ($sText != '') {
        $tpl->set('s', 'text', strip_tags($sText));
        $tpl->generate('templates/headline_h1.html');
    }
}

?>