<?php
include_once('../includes/startup.php');

include_once($cfg['path']['contenido'] . 'plugins/linkchecker/includes/config.plugin.php');
include_once($cfg['path']['contenido'].$cfg['path']['classes'] . 'class.user.php');
include_once($cfg['path']['contenido'].$cfg['path']['classes'] . 'class.xml.php');
include_once($cfg['path']['contenido'].$cfg['path']['classes'] . 'class.navigation.php');
include_once($cfg['path']['contenido'].$cfg['path']['classes'] . 'class.template.php');
include_once($cfg['path']['contenido'].$cfg['path']['classes'] . 'class.backend.php');
include_once($cfg['path']['contenido'].$cfg['path']['classes'] . 'class.table.php');
include_once($cfg['path']['contenido'].$cfg['path']['classes'] . 'class.notification.php');
include_once($cfg['path']['contenido'].$cfg['path']['classes'] . 'class.area.php');
include_once($cfg['path']['contenido'].$cfg['path']['classes'] . 'class.layout.php');
include_once($cfg['path']['contenido'].$cfg['path']['classes'] . 'class.client.php');
include_once($cfg['path']['contenido'].$cfg['path']['classes'] . 'class.cat.php');
include_once($cfg['path']['contenido'].$cfg['path']['classes'] . 'class.treeitem.php');
include_once($cfg['path']['contenido'].$cfg['path']['includes'] . 'cfg_sql.inc.php');
include_once($cfg['path']['contenido'].$cfg['path']['includes'] . 'functions.general.php');

global $cfg;

// Create Contenido DB_class
$db = new DB_Contenido;

// Start linkchecker
$cronjob = true;
$_REQUEST['mode'] = 2;

$sql = "SELECT idlang FROM " . $cfg['tab']['lang'] . " WHERE active = '1'";
$db->query($sql);

if($db->num_rows() > 1) {
    $langart = 0;
} else {
    $db->next_record();
    $langart = $db->f("idlang");    
}

include_once($cfg['path']['contenido'] . 'plugins/linkchecker/includes/include.linkchecker.php');
?>