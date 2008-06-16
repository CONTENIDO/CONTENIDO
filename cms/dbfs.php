<?php
/**
 * Database Filesystem
 *
 * @author <unknown>
 * @version $Revision$
 * @copyright four for business AG <www.4fb.de>
 *
 * @internal {
 *  modified 2008-06-16, H. Librenz - Hotfix: checking for potential unsecure calling
 *  $Id$
 * }
 */
if (isset($_REQUEST['contenido_path'])) {
    die ('Illegal call!');
}

include_once ("config.php");
include_once ($contenido_path . "includes/startup.php");
cInclude("includes", "functions.general.php");

cInclude("classes", "class.dbfs.php");


if ($contenido)
{
    page_open(array('sess' => 'Contenido_Session',
                    'auth' => 'Contenido_Challenge_Crypt_Auth',
                    'perm' => 'Contenido_Perm'));

} else {
    page_open(array('sess' => 'Contenido_Frontend_Session',
                    'auth' => 'Contenido_Frontend_Challenge_Crypt_Auth',
                    'perm' => 'Contenido_Perm'));
}

/* Shorten load time */
$client = $load_client;

$dbfs = new DBFSCollection;
$dbfs->outputFile($file);

page_close();

?>