<?php
/**********************************************************************************
* File      :   $RCSfile$
* Project   :   Contenido
* Descr     :   Add/Update newsletter recipient key on upgrade from Contenido 4.4.x to 4.5 or later
*
* Author    :   $Author$
*               
* Created   :   06.02.2005
* Modified  :   $Date$
*
* © four for business AG, www.4fb.de
*
* This file is part of the Contenido Content Management System. 
*
* $Id$
***********************************************************************************/

include_once ('../includes/startup.php');

cInclude ("includes", 'functions.general.php');

cInclude ("includes", 'functions.i18n.php');
cInclude ("includes", 'functions.api.php');
cInclude ("includes", 'functions.general.php');
cInclude ("includes", 'functions.forms.php');

cInclude ("includes", 'cfg_sql.inc.php');

cInclude ("classes", 'class.xml.php');
cInclude ("classes", 'class.navigation.php');
cInclude ("classes", 'class.template.php');
cInclude ("classes", 'class.backend.php');
cInclude ("classes", 'class.notification.php');
cInclude ("classes", 'class.area.php');
cInclude ("classes", 'class.action.php');
cInclude ("classes", 'contenido/class.module.php');
cInclude ("classes", 'class.layout.php');
cInclude ("classes", 'class.treeitem.php');
cInclude ("classes", 'class.user.php');
cInclude ("classes", 'class.group.php');
cInclude ("classes", 'class.cat.php');
cInclude ("classes", 'class.client.php');
cInclude ("classes", 'class.inuse.php');
cInclude ("classes", 'class.table.php');

cInclude("classes", "class.newsletter.recipients.php");

i18nInit($cfg["path"]["contenido"].$cfg["path"]["locale"], $belang);
cInclude ("includes", 'cfg_language_de.inc.php');

$recipients = new RecipientCollection;
$updatedrecipients = $recipients->updateKeys();

echo $updatedrecipients . " recipients, with no or incompatible key has been updated.";
echo "<br>All done. Please remove this script.";

?>