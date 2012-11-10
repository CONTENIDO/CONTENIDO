<?php
/**
 * Description: Display footnotes, fixed output directly from template
 *
 * @version 1.0.0
 * @author Rudi Bieller
 * @copyright four for business AG <www.4fb.de>
 *
 * {@internal
 *   created 2008-04-11
 *   $Id$
 * }}
 */

if (!isset($tpl) || !is_object($tpl)) {
    $tpl = new cTemplate();
}

$tpl->reset();
$tpl->generate('templates/footnotes_'.strval($lang).'.html');
?>