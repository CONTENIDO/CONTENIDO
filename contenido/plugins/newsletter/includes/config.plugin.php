<?php
/**
 * This file contains the Config file for Newsletter plugin.
 *
 * @package Plugin
 * @subpackage Newsletter
 * @author unknown
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$cfg['tab']['news_groupmembers'] = $cfg['sql']['sqlprefix'] . '_pi_news_groupmembers';
$cfg['tab']['news_groups'] = $cfg['sql']['sqlprefix'] . '_pi_news_groups';
$cfg['tab']['news_jobs'] = $cfg['sql']['sqlprefix'] . '_pi_news_jobs';
$cfg['tab']['news_log'] = $cfg['sql']['sqlprefix'] . '_pi_news_log';
$cfg['tab']['news_rcp'] = $cfg['sql']['sqlprefix'] . '_pi_news_rcp';
$cfg['tab']['news'] = $cfg['sql']['sqlprefix'] . '_pi_news';

// plugin includes
plugin_include('newsletter', 'classes/class.newsletter.php');
plugin_include('newsletter', 'classes/class.newsletter.logs.php');
plugin_include('newsletter', 'classes/class.newsletter.jobs.php');
plugin_include('newsletter', 'classes/class.newsletter.groups.php');
plugin_include('newsletter', 'classes/class.newsletter.recipients.php');
?>