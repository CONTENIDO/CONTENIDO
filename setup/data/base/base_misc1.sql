UPDATE !PREFIX!_art_lang SET published=lastmodified, publishedby=modifiedby WHERE online=1 and published="0000-00-00 00:00:00";
ALTER TABLE !PREFIX!_pica_alloc_con DROP PRIMARY KEY;
ALTER TABLE !PREFIX!_pica_alloc_con ADD PRIMARY KEY ( `idpica_alloc` , `idartlang` );
ALTER TABLE !PREFIX!_pica_lang DROP PRIMARY KEY;
ALTER TABLE !PREFIX!_pica_lang ADD PRIMARY KEY ( `idpica_alloc` , `idlang` );