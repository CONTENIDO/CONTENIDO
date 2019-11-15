DELETE FROM `!PREFIX!_actions` WHERE idaction <= 10000;
INSERT INTO `!PREFIX!_actions` VALUES ('63', '1', '10', 'con_makestart', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('330', '0', '0', 'login', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('2', '1', '33', 'con_makeonline', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('3', '1', '41', 'con_deleteart', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('5', '3', '30', 'con_edit', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('9', '6', '11', 'str_newtree', '', '', 1);
INSERT INTO `!PREFIX!_actions` VALUES ('10', '6', '21', 'str_newcat', '', '', 1);
INSERT INTO `!PREFIX!_actions` VALUES ('11', '6', '31', 'str_renamecat', '', '', 1);
INSERT INTO `!PREFIX!_actions` VALUES ('12', '6', '40', 'str_makevisible', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('13', '6', '50', 'str_makepublic', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('14', '6', '61', 'str_deletecat', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('15', '6', '70', 'str_moveupcat', '', '', 1);
INSERT INTO `!PREFIX!_actions` VALUES ('16', '6', '81', 'str_movesubtree', '', '', 1);
INSERT INTO `!PREFIX!_actions` VALUES ('17', '7', '31', 'upl_mkdir', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('61', '7', '31', 'upl_upload', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('62', '7', '31', 'upl_delete', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('18', '9', '20', 'lay_edit', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('19', '8', '31', 'lay_delete', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('20', '11', '20', 'mod_edit', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('21', '10', '31', 'mod_delete', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('22', '12', '31', 'tpl_delete', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('23', '13', '20', 'tpl_edit', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('30', '6', '', 'str_movedowncat', '', '', 1);
INSERT INTO `!PREFIX!_actions` VALUES ('347', '31', '', 'style_create', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('348', '31', '', 'style_delete', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('349', '32', '', 'js_delete', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('345', '32', '', 'js_create', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('346', '11', '', 'mod_new', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('351', '20', '', 'stat_show', '', '', '0');
INSERT INTO `!PREFIX!_actions` VALUES ('350', '49', '', 'log_show', '', '', '0');
INSERT INTO `!PREFIX!_actions` VALUES ('35', '47', '10', 'lang_newlanguage', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('37', '47', '31', 'lang_deletelanguage', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('38', '22', '40', 'lang_activatelanguage', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('39', '22', '41', 'lang_deactivatelanguage', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('328', '13', '', 'tpl_new', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('44', '25', '12', 'user_saverightsarea', '', '', '0');
INSERT INTO `!PREFIX!_actions` VALUES ('327', '9', '', 'lay_new', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('47', '40', '10', 'user_edit', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('352', '13', '', 'tpl_duplicate', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('58', '1', '', 'con_makepublic', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('321', '30', '', 'tplcfg_edit', '', '', '0');
INSERT INTO `!PREFIX!_actions` VALUES ('57', '1', '', 'con_tplcfg_edit', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('322', '31', '', 'style_edit', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('323', '32', '', 'js_edit', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('59', '1', '', 'con_makecatonline', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('60', '1', '', 'con_changetemplate', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('325', '39', '', 'user_createuser', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('326', '21', '', 'user_delete', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('1', '0', '', 'fake_permission_action', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('329', '45', '', 'mycontenido_editself', '', '', '0');
INSERT INTO `!PREFIX!_actions` VALUES ('353', '30', '', 'str_tplcfg', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('334', '48', '', 'client_new', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('335', '48', '', 'client_edit', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('336', '46', '', 'client_delete', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('354', '54', '', 'group_delete', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('355', '60', '', 'group_create', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('356', '61', '', 'group_edit', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('357', '63', '', 'group_deletemember', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('358', '63', '', 'group_addmember', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('359', '6', '', 'front_allow', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('56', '2', '', 'con_editart', '', 'rights/content/article/edit', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('55', '3', '', 'con_saveart', '', '', 0);
INSERT INTO `!PREFIX!_actions` VALUES ('54', '3', '', 'con_newart', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('378', '1', '', 'con_lock', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('379', '65', '', 'empty_log', '', '', '0');
INSERT INTO `!PREFIX!_actions` VALUES ('380', '66', '', 'send_mail', '', '', '0');
INSERT INTO `!PREFIX!_actions` VALUES ('381', '7', '', 'upl_rmdir', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('387', '1', '', 'con_syncarticle', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('386', '1', '', 'con_synccat', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('385', '67', '', 'systemsettings_delete_item', '', '', '0');
INSERT INTO `!PREFIX!_actions` VALUES ('384', '67', '', 'systemsettings_edit_item', '', '', '0');
INSERT INTO `!PREFIX!_actions` VALUES ('383', '67', '', 'systemsettings_save_item', '', '', '0');
INSERT INTO `!PREFIX!_actions` VALUES ('388', '68', '', 'client_artspec_save', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('389', '68', '', 'client_artspec_edit', '', '', '0');
INSERT INTO `!PREFIX!_actions` VALUES ('390', '68', '', 'client_artspec_delete', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('391', '68', '', 'client_artspec_online', '', '', '0');
INSERT INTO `!PREFIX!_actions` VALUES ('392', '71', '', 'htmltpl_create', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('393', '71', '', 'htmltpl_delete', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('394', '71', '', 'htmltpl_edit', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('395', '72', '', 'tpl_visedit', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('396', '68', '', 'client_artspec_default', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('398', '7', '', 'upl_modify_file', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('400', '7', '', 'upl_renamefile', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('401', '76', '', 'frontend_save_user', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('402', '76', '', 'frontend_create', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('403', '76', '', 'frontend_delete', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('404', '1', '', 'con_duplicate', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('405', '77', '', 'frontendgroup_delete', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('406', '77', '', 'frontendgroup_save_group', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('407', '77', '', 'frontendgroup_create', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('408', '77', '', 'frontendgroups_user_delete', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('409', '44', '', 'todo_save_item', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('410', '44', '', 'mycontenido_tasks_delete', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('412', '81', '', 'mod_translation_save', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('414', '7', '', 'upl_multidelete', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('415', '47', '', 'lang_edit', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('416', '6', '', 'str_duplicate', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('417', '83', '', 'clientsettings_save_item', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('418', '83', '', 'clientsettings_delete_item', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('419', '83', '', 'clientsettings_edit_item', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('420', '11', '', 'mod_importexport_module', '', '', '0');
INSERT INTO `!PREFIX!_actions` VALUES ('421', '3', '', 'remove_assignments', '', '', '0');
INSERT INTO `!PREFIX!_actions` VALUES ('429', '82', '', 'fegroups_save_perm', '', '', '0');
INSERT INTO `!PREFIX!_actions` VALUES ('444', '77', '', 'frontendgroup_user_add', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('202', '402', 'js_history_manage', 'js_history_manage', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('203', '403', 'htmltpl_history_manage', 'htmltpl_history_manage', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('204', '70', 'mod_history_manage', 'mod_history_manage', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('801', '400', 'history_truncate', 'history_truncate', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('802', '401', 'history_truncate', 'history_truncate', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('803', '402', 'history_truncate', 'history_truncate', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('804', '403', 'history_truncate', 'history_truncate', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('805', '70', 'history_truncate', 'history_truncate', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('806', '415', 'edit_sysconf', 'edit_sysconf', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('811', '811', 'do_purge', 'do_purge', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('812', '11', '', 'mod_sync', '', '', '1');
INSERT INTO `!PREFIX!_actions` VALUES ('813', '9','','lay_sync','','','1');
INSERT INTO `!PREFIX!_actions` VALUES ('850', '100','','con_meta_edit','','','1');
INSERT INTO `!PREFIX!_actions` VALUES ('851', '100','','con_meta_deletetype','','','1');
INSERT INTO `!PREFIX!_actions` VALUES ('855', '105','','con_content','','','1');
INSERT INTO `!PREFIX!_actions` VALUES ('856', '105','','savecontype','','','1');
INSERT INTO `!PREFIX!_actions` VALUES ('857', '105','','deletecontype','','','1');
INSERT INTO `!PREFIX!_actions` VALUES ('860', '97','','con_translate_edit','','','1');
INSERT INTO `!PREFIX!_actions` VALUES ('861', '97','','con_translate_view','','','1');
