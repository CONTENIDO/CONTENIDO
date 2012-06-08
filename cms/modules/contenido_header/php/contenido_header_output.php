<?php
/**
 * Description: Display HTML Comment with Infos about CONTENIDO and the Sample Client.
 *
 * @version 1.0.0
 * @author Rudi Bieller
 * @copyright four for business AG <www.4fb.de>
 *
 * {@internal
 *   created 2008-04-07
 *   $Id$
 * }}
 */

if (!isset($tpl) || !is_object($tpl) || strtolower(get_class($tpl)) != 'template') {
    $tpl = new Template();
}
$tpl->reset();
$tpl->generate('templates/contenido_header.html');
?>