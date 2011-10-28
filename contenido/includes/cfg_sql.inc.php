<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * Definition of SQL-vars
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Backend includes
 * @version    1.0.1
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * 
 * {@internal 
 *   created 2003-01-21
 *   modified 2008-06-25, Frederic Schneider, add security fix
 *
 *   $Id$:
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

global $cfg;

$cfg['tab']['actionlog']                 = $cfg['sql']['sqlprefix'] . '_actionlog';
$cfg['tab']['actions']                   = $cfg['sql']['sqlprefix'] . '_actions';
$cfg['tab']['area']                      = $cfg['sql']['sqlprefix'] . '_area';
$cfg['tab']['art_lang']                  = $cfg['sql']['sqlprefix'] . '_art_lang';
$cfg['tab']['art_spec']                  = $cfg['sql']['sqlprefix'] . '_art_spec';
$cfg['tab']['art_spec']                  = $cfg['sql']['sqlprefix'] . '_art_spec';
$cfg['tab']['art']                       = $cfg['sql']['sqlprefix'] . '_art';
$cfg['tab']['cat_art']                   = $cfg['sql']['sqlprefix'] . '_cat_art';
$cfg['tab']['cat_lang']                  = $cfg['sql']['sqlprefix'] . '_cat_lang';
$cfg['tab']['cat_tree']                  = $cfg['sql']['sqlprefix'] . '_cat_tree';
$cfg['tab']['cat']                       = $cfg['sql']['sqlprefix'] . '_cat';
$cfg['tab']['chartable']                 = $cfg['sql']['sqlprefix'] . '_chartable';
$cfg['tab']['clients_lang']              = $cfg['sql']['sqlprefix'] . '_clients_lang';
$cfg['tab']['clients']                   = $cfg['sql']['sqlprefix'] . '_clients';
$cfg['tab']['code']                      = $cfg['sql']['sqlprefix'] . '_code';
$cfg['tab']['communications']            = $cfg['sql']['sqlprefix'] . '_communications';
$cfg['tab']['config_client']             = $cfg['sql']['sqlprefix'] . '_config_client';
$cfg['tab']['config']                    = $cfg['sql']['sqlprefix'] . '_config';
$cfg['tab']['container_conf']            = $cfg['sql']['sqlprefix'] . '_container_conf';
$cfg['tab']['container']                 = $cfg['sql']['sqlprefix'] . '_container';
$cfg['tab']['content']                   = $cfg['sql']['sqlprefix'] . '_content';
$cfg['tab']['data']                      = $cfg['sql']['sqlprefix'] . '_data';
$cfg['tab']['dbfs']                      = $cfg['sql']['sqlprefix'] . '_dbfs';
$cfg['tab']['file_information']          = $cfg['sql']['sqlprefix'] . '_file_information';
$cfg['tab']['files']                     = $cfg['sql']['sqlprefix'] . '_files';
$cfg['tab']['framefiles']                = $cfg['sql']['sqlprefix'] . '_frame_files';
$cfg['tab']['frontendgroupmembers']      = $cfg['sql']['sqlprefix'] . '_frontendgroupmembers';
$cfg['tab']['frontendgroups']            = $cfg['sql']['sqlprefix'] . '_frontendgroups';
$cfg['tab']['frontendpermissions']       = $cfg['sql']['sqlprefix'] . '_frontendpermissions';
$cfg['tab']['frontendusers']             = $cfg['sql']['sqlprefix'] . '_frontendusers';
$cfg['tab']['group_prop']                = $cfg['sql']['sqlprefix'] . '_group_prop';
$cfg['tab']['groupmembers']              = $cfg['sql']['sqlprefix'] . '_groupmembers';
$cfg['tab']['groups']                    = $cfg['sql']['sqlprefix'] . '_groups';
$cfg['tab']['inuse']                     = $cfg['sql']['sqlprefix'] . '_inuse';
$cfg['tab']['keywords']                  = $cfg['sql']['sqlprefix'] . '_keywords';
$cfg['tab']['lang']                      = $cfg['sql']['sqlprefix'] . '_lang';
$cfg['tab']['lay']                       = $cfg['sql']['sqlprefix'] . '_lay';
$cfg['tab']['link']                      = $cfg['sql']['sqlprefix'] . '_link';
$cfg['tab']['meta_tag']                  = $cfg['sql']['sqlprefix'] . '_meta_tag';
$cfg['tab']['meta_type']                 = $cfg['sql']['sqlprefix'] . '_meta_type';
$cfg['tab']['mod_translations']          = $cfg['sql']['sqlprefix'] . '_mod_translations';
$cfg['tab']['mod']                       = $cfg['sql']['sqlprefix'] . '_mod';
$cfg['tab']['nav_main']                  = $cfg['sql']['sqlprefix'] . '_nav_main';
$cfg['tab']['nav_sub']                   = $cfg['sql']['sqlprefix'] . '_nav_sub';
$cfg['tab']['news_groupmembers']         = $cfg['sql']['sqlprefix'] . '_news_groupmembers';
$cfg['tab']['news_groups']               = $cfg['sql']['sqlprefix'] . '_news_groups';
$cfg['tab']['news_jobs']                 = $cfg['sql']['sqlprefix'] . '_news_jobs';
$cfg['tab']['news_log']                  = $cfg['sql']['sqlprefix'] . '_news_log';
$cfg['tab']['news_rcp']                  = $cfg['sql']['sqlprefix'] . '_news_rcp';
$cfg['tab']['news']                      = $cfg['sql']['sqlprefix'] . '_news';
$cfg['tab']['online_user']               = $cfg['sql']['sqlprefix'] . '_online_user';
$cfg['tab']['phplib_active_sessions']    = $cfg['sql']['sqlprefix'] . '_phplib_active_sessions';
$cfg['tab']['phplib_auth_user_md5']      = $cfg['sql']['sqlprefix'] . '_phplib_auth_user_md5';
$cfg['tab']['plugins']                   = $cfg['sql']['sqlprefix'] . '_plugins';
$cfg['tab']['properties']                = $cfg['sql']['sqlprefix'] . '_properties';
$cfg['tab']['rights']                    = $cfg['sql']['sqlprefix'] . '_rights';
$cfg['tab']['sequence']                  = $cfg['sql']['sqlprefix'] . '_sequence';
$cfg['tab']['stat_archive']              = $cfg['sql']['sqlprefix'] . '_stat_archive';
$cfg['tab']['stat_heap_table']           = $cfg['sql']['sqlprefix'] . '_stat_heap_table';
$cfg['tab']['stat']                      = $cfg['sql']['sqlprefix'] . '_stat';
$cfg['tab']['status']                    = $cfg['sql']['sqlprefix'] . '_status';
$cfg['tab']['system_prop']               = $cfg['sql']['sqlprefix'] . '_system_prop';
$cfg['tab']['tpl_conf']                  = $cfg['sql']['sqlprefix'] . '_template_conf';
$cfg['tab']['tpl']                       = $cfg['sql']['sqlprefix'] . '_template';
$cfg['tab']['type']                      = $cfg['sql']['sqlprefix'] . '_type';
$cfg['tab']['upl_meta']                  = $cfg['sql']['sqlprefix'] . '_upl_meta';
$cfg['tab']['upl']                       = $cfg['sql']['sqlprefix'] . '_upl';
$cfg['tab']['user_prop']                 = $cfg['sql']['sqlprefix'] . '_user_prop';

?>