<?php

/**
 * Backend action file con_makepublic
 *
 * @package    Core
 * @subpackage Backend
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @var cGuiNotification $notification
 * @var int $public
 */

cInclude('includes', 'functions.con.php');

$idcat = cRegistry::getCategoryId();
$perm = cRegistry::getPerm();
$lang = cRegistry::getLanguageId();

if (
    $perm->have_perm_area_action('con', 'con_makepublic')
    || $perm->have_perm_area_action_item('con', 'con_makepublic', $idcat)
) {
    conMakePublic($idcat, $lang, $public);
} else {
    $notification->displayNotification('error', i18n("Permission denied"));
}
