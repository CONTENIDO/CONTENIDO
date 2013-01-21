<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * left_top frame
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.0.1
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

$clientname = $classclient->getClientname($client);

$tpl->set('s', 'CLASS', 'text_medium');
$tpl->set('s', 'SESSID', $sess->id);
$tpl->set('s', 'NEWLANG', '<a class="addfunction" href="javascript:languageNewConfirm()">' . i18n("Create language for client") . '</a>');
$tpl->set('s', 'NAME', $clientname);
$tpl->set('s', 'TARGETCLIENT', $client);

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['lang_left_top']);

