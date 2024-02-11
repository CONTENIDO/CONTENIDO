<?php

/**
 * This file contains the configuration values for the database table names.
 *
 * @package    Core
 * @subpackage Backend_ConfigFile
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $cfg;

$cfg['tab']['actionlog'] = $cfg['sql']['sqlprefix'] . '_actionlog';
$cfg['tab']['actions'] = $cfg['sql']['sqlprefix'] . '_actions';
$cfg['tab']['area'] = $cfg['sql']['sqlprefix'] . '_area';
$cfg['tab']['art_lang'] = $cfg['sql']['sqlprefix'] . '_art_lang';
$cfg['tab']['art_lang_version'] = $cfg['sql']['sqlprefix'] . '_art_lang_version';
$cfg['tab']['art_spec'] = $cfg['sql']['sqlprefix'] . '_art_spec';
$cfg['tab']['art_spec'] = $cfg['sql']['sqlprefix'] . '_art_spec';
$cfg['tab']['art'] = $cfg['sql']['sqlprefix'] . '_art';
$cfg['tab']['cat_art'] = $cfg['sql']['sqlprefix'] . '_cat_art';
$cfg['tab']['cat_lang'] = $cfg['sql']['sqlprefix'] . '_cat_lang';
$cfg['tab']['cat_tree'] = $cfg['sql']['sqlprefix'] . '_cat_tree';
$cfg['tab']['cat'] = $cfg['sql']['sqlprefix'] . '_cat';
$cfg['tab']['clients_lang'] = $cfg['sql']['sqlprefix'] . '_clients_lang';
$cfg['tab']['clients'] = $cfg['sql']['sqlprefix'] . '_clients';
$cfg['tab']['code'] = $cfg['sql']['sqlprefix'] . '_code';
$cfg['tab']['communications'] = $cfg['sql']['sqlprefix'] . '_communications';
$cfg['tab']['container_conf'] = $cfg['sql']['sqlprefix'] . '_container_conf';
$cfg['tab']['container'] = $cfg['sql']['sqlprefix'] . '_container';
$cfg['tab']['content'] = $cfg['sql']['sqlprefix'] . '_content';
$cfg['tab']['content_version'] = $cfg['sql']['sqlprefix'] . '_content_version';
$cfg['tab']['dbfs'] = $cfg['sql']['sqlprefix'] . '_dbfs';
$cfg['tab']['file_information'] = $cfg['sql']['sqlprefix'] . '_file_information';
$cfg['tab']['files'] = $cfg['sql']['sqlprefix'] . '_files';
$cfg['tab']['framefiles'] = $cfg['sql']['sqlprefix'] . '_frame_files';
$cfg['tab']['frontendgroupmembers'] = $cfg['sql']['sqlprefix'] . '_frontendgroupmembers';
$cfg['tab']['frontendgroups'] = $cfg['sql']['sqlprefix'] . '_frontendgroups';
$cfg['tab']['frontendpermissions'] = $cfg['sql']['sqlprefix'] . '_frontendpermissions';
$cfg['tab']['frontendusers'] = $cfg['sql']['sqlprefix'] . '_frontendusers';
$cfg['tab']['group_prop'] = $cfg['sql']['sqlprefix'] . '_group_prop';
$cfg['tab']['groupmembers'] = $cfg['sql']['sqlprefix'] . '_groupmembers';
$cfg['tab']['groups'] = $cfg['sql']['sqlprefix'] . '_groups';
$cfg['tab']['inuse'] = $cfg['sql']['sqlprefix'] . '_inuse';
$cfg['tab']['keywords'] = $cfg['sql']['sqlprefix'] . '_keywords';
$cfg['tab']['lang'] = $cfg['sql']['sqlprefix'] . '_lang';
$cfg['tab']['lay'] = $cfg['sql']['sqlprefix'] . '_lay';
$cfg['tab']['mail_log'] = $cfg['sql']['sqlprefix'] . '_mail_log';
$cfg['tab']['mail_log_success'] = $cfg['sql']['sqlprefix'] . '_mail_log_success';
$cfg['tab']['meta_tag'] = $cfg['sql']['sqlprefix'] . '_meta_tag';
$cfg['tab']['meta_tag_version'] = $cfg['sql']['sqlprefix'] . '_meta_tag_version';
$cfg['tab']['meta_type'] = $cfg['sql']['sqlprefix'] . '_meta_type';
$cfg['tab']['mod_translations'] = $cfg['sql']['sqlprefix'] . '_mod_translations';
$cfg['tab']['mod'] = $cfg['sql']['sqlprefix'] . '_mod';
$cfg['tab']['nav_main'] = $cfg['sql']['sqlprefix'] . '_nav_main';
$cfg['tab']['nav_sub'] = $cfg['sql']['sqlprefix'] . '_nav_sub';
$cfg['tab']['news_groupmembers'] = $cfg['sql']['sqlprefix'] . '_news_groupmembers';
$cfg['tab']['news_groups'] = $cfg['sql']['sqlprefix'] . '_news_groups';
$cfg['tab']['news_jobs'] = $cfg['sql']['sqlprefix'] . '_news_jobs';
$cfg['tab']['news_log'] = $cfg['sql']['sqlprefix'] . '_news_log';
$cfg['tab']['news_rcp'] = $cfg['sql']['sqlprefix'] . '_news_rcp';
$cfg['tab']['news'] = $cfg['sql']['sqlprefix'] . '_news';
$cfg['tab']['online_user'] = $cfg['sql']['sqlprefix'] . '_online_user';
$cfg['tab']['phplib_active_sessions'] = $cfg['sql']['sqlprefix'] . '_phplib_active_sessions';
$cfg['tab']['user'] = $cfg['sql']['sqlprefix'] . '_user';
$cfg['tab']['plugins'] = $cfg['sql']['sqlprefix'] . '_plugins';
$cfg['tab']['plugins_rel'] = $cfg['sql']['sqlprefix'] . '_plugins_rel';
$cfg['tab']['properties'] = $cfg['sql']['sqlprefix'] . '_properties';
$cfg['tab']['rights'] = $cfg['sql']['sqlprefix'] . '_rights';
$cfg['tab']['stat_archive'] = $cfg['sql']['sqlprefix'] . '_stat_archive';
$cfg['tab']['stat_heap_table'] = $cfg['sql']['sqlprefix'] . '_stat_heap_table';
$cfg['tab']['stat'] = $cfg['sql']['sqlprefix'] . '_stat';
$cfg['tab']['system_prop'] = $cfg['sql']['sqlprefix'] . '_system_prop';
$cfg['tab']['tpl_conf'] = $cfg['sql']['sqlprefix'] . '_template_conf';
$cfg['tab']['tpl'] = $cfg['sql']['sqlprefix'] . '_template';
$cfg['tab']['type'] = $cfg['sql']['sqlprefix'] . '_type';
$cfg['tab']['upl_meta'] = $cfg['sql']['sqlprefix'] . '_upl_meta';
$cfg['tab']['upl'] = $cfg['sql']['sqlprefix'] . '_upl';
$cfg['tab']['user_prop'] = $cfg['sql']['sqlprefix'] . '_user_prop';
$cfg['tab']['user_pw_request'] = $cfg['sql']['sqlprefix'] . '_user_pw_request';
$cfg['tab']['iso_639_2'] = $cfg['sql']['sqlprefix'] . '_iso_639_2';
$cfg['tab']['iso_3166'] = $cfg['sql']['sqlprefix'] . '_iso_3166';

$cfg['tab']['search_tracking'] = $cfg['sql']['sqlprefix'] . '_search_tracking';

$cfg['tab']['phplib_auth_user_md5'] = $cfg['tab']['user'];
