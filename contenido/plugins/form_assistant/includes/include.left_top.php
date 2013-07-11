<?php

/**
 *
 * @package Plugin
 * @subpackage FormAssistant
 * @version SVN Revision $Rev:$
 * @author marcus.gnass
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $area;

$cfg = cRegistry::getConfig();

$link = new cHTMLLink();
$link->setMultiLink($area, 'show_form', $area, 'show_form');
$link->setContent(Pifa::i18n('CREATE_FORM'));
$link->setTargetFrame('right_bottom');

$oUi = new cTemplate();
$oUi->set("s", "ACTION", $link->render());
$oUi->generate($cfg["path"]["templates"] . $cfg["templates"]["left_top"]);

?>