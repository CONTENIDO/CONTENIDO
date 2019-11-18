ALTER TABLE `!PREFIX!_actions` ADD INDEX idarea (idarea);
ALTER TABLE `!PREFIX!_actions` ADD INDEX name_2 (name);

ALTER TABLE `!PREFIX!_area` ADD INDEX idarea (idarea, name, online);
ALTER TABLE `!PREFIX!_area` ADD INDEX name_2 (name);

ALTER TABLE `!PREFIX!_art` ADD INDEX idclient (idclient);

ALTER TABLE `!PREFIX!_art_lang` ADD INDEX idtplcfg (idtplcfg, idart);
ALTER TABLE `!PREFIX!_art_lang` ADD INDEX idart_2 (idart, idlang);

ALTER TABLE `!PREFIX!_art_spec` ADD INDEX client (client);
ALTER TABLE `!PREFIX!_art_spec` ADD INDEX lang (lang);

ALTER TABLE `!PREFIX!_cat` ADD INDEX idclient (idclient);
ALTER TABLE `!PREFIX!_cat` ADD INDEX idclient_2 (idclient, parentid);
ALTER TABLE `!PREFIX!_cat` ADD INDEX parentid (parentid, preid);
ALTER TABLE `!PREFIX!_cat` ADD INDEX preid (preid);

ALTER TABLE `!PREFIX!_cat_art` ADD INDEX is_start_2 (is_start, idcat);
ALTER TABLE `!PREFIX!_cat_art` ADD INDEX idart (idart);
ALTER TABLE `!PREFIX!_cat_art` ADD INDEX idcat (idcat);

ALTER TABLE `!PREFIX!_cat_lang` ADD INDEX idcat (idcat);
ALTER TABLE `!PREFIX!_cat_lang` ADD INDEX idlang (idlang);
ALTER TABLE `!PREFIX!_cat_lang` ADD INDEX idtplcfg (idtplcfg);
ALTER TABLE `!PREFIX!_cat_lang` ADD INDEX idlang_2 (idlang, visible);
ALTER TABLE `!PREFIX!_cat_lang` ADD INDEX idlang_3 (idlang, idcat);

ALTER TABLE `!PREFIX!_cat_tree` ADD INDEX idcat (idcat);

ALTER TABLE `!PREFIX!_container` ADD INDEX idtpl (idtpl);
ALTER TABLE `!PREFIX!_container` ADD INDEX number (number);

ALTER TABLE `!PREFIX!_container_conf` ADD INDEX idtplcfg (idtplcfg);

ALTER TABLE `!PREFIX!_content` ADD INDEX idartlang (idartlang);
ALTER TABLE `!PREFIX!_content` ADD INDEX idtype (idtype);
ALTER TABLE `!PREFIX!_content` ADD INDEX typeid (typeid);

ALTER TABLE `!PREFIX!_frame_files` ADD INDEX idarea (idarea, idframe, idfile);

ALTER TABLE `!PREFIX!_keywords` ADD INDEX keyword (keyword);
ALTER TABLE `!PREFIX!_keywords` ADD INDEX idlang2 (idlang, keyword);

ALTER TABLE `!PREFIX!_mod` ADD INDEX idclient (idclient);

ALTER TABLE `!PREFIX!_template` ADD INDEX idclient (idclient);
ALTER TABLE `!PREFIX!_template` ADD INDEX idlay (idlay);
ALTER TABLE `!PREFIX!_template` ADD INDEX idtplcfg (idtplcfg);

ALTER TABLE `!PREFIX!_template_conf` ADD INDEX idtplcfg (idtplcfg);

ALTER TABLE `!PREFIX!_upl` ADD INDEX idclient (idclient);
ALTER TABLE `!PREFIX!_properties` ADD INDEX index_client (idclient);
ALTER TABLE `!PREFIX!_properties` ADD INDEX index_itemtype (itemtype);
ALTER TABLE `!PREFIX!_properties` ADD INDEX index_itemid (itemid);
ALTER TABLE `!PREFIX!_properties` ADD INDEX index_type (type);

ALTER TABLE `!PREFIX!_frontendpermissions` ADD INDEX idfrontendgroup (idfrontendgroup, idlang);
ALTER TABLE `!PREFIX!_frontendpermissions` ADD INDEX plugin (plugin);
ALTER TABLE `!PREFIX!_frontendpermissions` ADD INDEX action (action);
ALTER TABLE `!PREFIX!_frontendpermissions` ADD INDEX item (item);

ALTER TABLE `!PREFIX!_rights` ADD INDEX user_id (user_id);
ALTER TABLE `!PREFIX!_rights` ADD INDEX idarea (idarea);
ALTER TABLE `!PREFIX!_rights` ADD INDEX idaction (idaction);
ALTER TABLE `!PREFIX!_rights` ADD INDEX idcat (idcat);
ALTER TABLE `!PREFIX!_rights` ADD INDEX idclient (idclient);
ALTER TABLE `!PREFIX!_rights` ADD INDEX idlang (idlang);
ALTER TABLE `!PREFIX!_rights` ADD INDEX type (type);

ALTER TABLE `!PREFIX!_file_information` ADD INDEX idclient (idclient);
ALTER TABLE `!PREFIX!_file_information` ADD INDEX type (type);
ALTER TABLE `!PREFIX!_file_information` ADD INDEX filename (filename);

ALTER TABLE `!PREFIX!_system_prop` ADD INDEX type_name (type, name);

ALTER TABLE `!PREFIX!_stat` ADD INDEX idcatart_idlang (idcatart, idlang);