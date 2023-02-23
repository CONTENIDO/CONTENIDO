<?php
/**
 * This file contains the Config file for Newsletter plugin.
 *
 * @package Plugin
 * @subpackage Newsletter
 * @author unknown
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $cfg;

$pluginName = basename(dirname(__DIR__, 1));

$cfg['plugins'][$pluginName] = cRegistry::getBackendPath() . $cfg['path']['plugins'] . "$pluginName/";

// Plugin tables configuration
$cfg['tab']['news_groupmembers'] = $cfg['sql']['sqlprefix'] . '_pi_news_groupmembers';
$cfg['tab']['news_groups'] = $cfg['sql']['sqlprefix'] . '_pi_news_groups';
$cfg['tab']['news_jobs'] = $cfg['sql']['sqlprefix'] . '_pi_news_jobs';
$cfg['tab']['news_log'] = $cfg['sql']['sqlprefix'] . '_pi_news_log';
$cfg['tab']['news_rcp'] = $cfg['sql']['sqlprefix'] . '_pi_news_rcp';
$cfg['tab']['news'] = $cfg['sql']['sqlprefix'] . '_pi_news';

// Plugin templates configuration
$pluginTemplatesPath = cRegistry::getBackendPath() . $cfg['path']['plugins'] . "$pluginName/templates/standard";
$cfg['templates']['newsletter_newsletter_jobs_menu'] = $pluginTemplatesPath . '/template.newsletter_jobs_menu.html';
$cfg['templates']['newsletter_newsletter_left_top'] = $pluginTemplatesPath . '/template.newsletter_left_top.html';
$cfg['templates']['newsletter_newsletter_menu'] = $pluginTemplatesPath . '/template.newsletter_menu.html';
$cfg['templates']['newsletter_recipients_group_menu'] = $pluginTemplatesPath . '/template.recipients.group_menu.html';
$cfg['templates']['newsletter_recipients_menu'] = $pluginTemplatesPath . '/template.recipients_menu.html';

// Include necessary sources, Setup autoloader for plugin
$pluginClassesPath = cRegistry::getBackendPath(true) . $cfg['path']['plugins'] . "$pluginName/classes";
cAutoload::addClassmapConfig([
    'NewsletterRecipientGroupCollection' => $pluginClassesPath . '/class.newsletter.groups.php',
    'NewsletterRecipientGroup' => $pluginClassesPath . '/class.newsletter.groups.php',
    'NewsletterRecipientGroupMemberCollection' => $pluginClassesPath . '/class.newsletter.groups.php',
    'NewsletterRecipientGroupMember' => $pluginClassesPath . '/class.newsletter.groups.php',
    'NewsletterJobCollection' => $pluginClassesPath . '/class.newsletter.jobs.php',
    'NewsletterJob' => $pluginClassesPath . '/class.newsletter.jobs.php',
    'NewsletterLogCollection' => $pluginClassesPath . '/class.newsletter.logs.php',
    'NewsletterLog' => $pluginClassesPath . '/class.newsletter.logs.php',
    'NewsletterCollection' => $pluginClassesPath . '/class.newsletter.php',
    'Newsletter' => $pluginClassesPath . '/class.newsletter.php',
    'NewsletterRecipientCollection' => $pluginClassesPath . '/class.newsletter.recipients.php',
    'NewsletterRecipient' => $pluginClassesPath . '/class.newsletter.recipients.php',
]);

unset($pluginName, $pluginTemplatesPath, $pluginClassesPath);
