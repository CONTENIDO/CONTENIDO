<?php
/**
* $RCSfile$
*
* Description: Output standard h1 headline
*
* @version 1.0.0
* @author Rudi Bieller
* @copyright four for business AG <www.4fb.de>
*
* {@internal
* created 2008-04-07
* modified 2008-11-13, Timo Trautman - fixed XHTML validation error, when module displays empty h1 tag
* }}
*
* $Id$
*/

if (!isset($tpl) || !is_object($tpl)) {
    $tpl = new Template();
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