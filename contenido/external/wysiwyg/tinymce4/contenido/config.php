<?php
/**
 * Main editor configuration file for CONTENIDO
 *
 * @package    Core
 * @subpackage Backend
 * @author     Martin Horwath <horwath@dayside.net>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.con.php');
cInclude('includes', 'functions.general.php');
cInclude('includes', 'functions.i18n.php');
cInclude('includes', 'functions.api.php');

$db = cRegistry::getDb();

?>