<?php

/**
 * @package Plugin
 * @subpackage PIFA Form Asistant
 * @version SVN Revision $Rev:$
 * @author marcus.gnass
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') or die('Illegal call');

global $area;

$link = new cHTMLLink();
//$link->setCLink($area, 4, 'show_form');
$link->setMultiLink($area, 'show_form', $area, 'show_form');
$link->setContent(Pifa::i18n('CREATE_FORM'));
$link->setTargetFrame('right_bottom');

$oUi = new UI_Left_Top();
$oUi->setLink($link);
$oUi->render();

?>