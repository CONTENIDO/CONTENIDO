<?php
/**
 * Description: Output some HTML text
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
    $tpl = new Template();
}

$tpl->reset();
$tpl->set('s', 'text', "CMS_HTML[1]");
$tpl->generate('templates/text_html.html');

?>