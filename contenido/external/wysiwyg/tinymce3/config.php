<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * TINYMCE 1.45rc1 PHP WYSIWYG editor config
 * Main editor configuration file for CONTENIDO
 *
 * Requirements:
 * @con_php_req 5
 *
 * @package    CONTENIDO Backend Editor
 * @version    0.0.4
 * @author     Martin Horwath, horwath@dayside.net
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 *
 * {@internal
 *   created  2005-06-10
 *   $Id: config.php 739 2008-08-27 10:37:54Z timo.trautmann $:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

cInclude('includes', 'functions.con.php');
cInclude('includes', 'functions.general.php');
cInclude('includes', 'functions.i18n.php');
cInclude('includes', 'functions.api.php');

$db = cRegistry::getDb();

if ($cfgClient['set'] != 'set') {
    rereadClients();
}

?>