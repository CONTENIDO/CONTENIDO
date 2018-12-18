<?php
/**
 * This file contains initialisation for left top
 *
 * @package Plugin
 * @subpackage UserForum
 * @author Claus Schunk
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$oUi = new cTemplate();
$oUi->set("s", "ACTION", '');
$oUi->generate($cfg["path"]["templates"] . $cfg["templates"]["left_top"]);

?>