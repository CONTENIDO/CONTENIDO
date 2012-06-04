<?php

defined('CON_FRAMEWORK') or die('Illegal call');

global $cfg;

// config.misc.php
// default container is sql
#$cfg["session_container"] = 'session';


// config.php
$cfg['db']['enableProfiling'] = true;

// (bool) Enable GenericDB item cache
$cfg['sql']['cache']['enable'] = true;

// (bool) Enable mode to select all fields in GenericDB item collections.
$cfg['sql']['select_all_mode'] = true;

// database extension
#$cfg["database_extension"] = 'pdo_mysql';
