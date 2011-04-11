<?php
/**
 * Testscript for Advanced Mod Rewrite Plugin.
 *
 * The goal of this testscript is to provide an easy way for a variance comparison
 * of created SEO URLs against their resolved parts.
 *
 * This testscript fetches the full category and article structure of actual
 * Contenido installation, creates the SEO URLs for each existing category/article
 * and resolves the generated URLs.
 *
 * Usage:
 * ------
 * 1. Install the Advanced Mod Rewrite Plugin
 * 2. Set your desired plugin settings by using the Advanced Mod Rewrite area
 *    in the Backend
 * 3. Copy this file to your client directory, it is normally named '/cms/'
 * 4. Exclude this file from rewriting, add following line in your .htaccess
 *    RewriteRule ^cms/mr_test.php.*$ - [L]
 * 5. Browse to http://host/cms/mr_test.php
 * 6. Disable the script after finished testing, see line 31
 *
 * @author      Murat Purc <murat@purc.de>
 * @copyright   © Murat Purc 2008
 * @package     Contenido
 * @subpackage  ModRewrite
 */



################################################################################
##### Initialization

plugin_include('mod_rewrite', 'classes/controller/class.modrewrite_contenttest_controller.php');


################################################################################
##### Processing

$mrTestNoOptionSelected = false;
if (!mr_getRequest('idart') && !mr_getRequest('idcat') && !mr_getRequest('idcatart') && !mr_getRequest('idartlang')) {
    $mrTestNoOptionSelected = true;
}


$oMrTestController = new ModRewrite_ContentTestController();


################################################################################
##### Action processing

if ($mrTestNoOptionSelected) {
    $oMrTestController->indexAction();
} else {
    $oMrTestController->testAction();
}

$oView = $oMrTestController->getView();
$oView->content .= mr_debugOutput(false);


################################################################################
##### Output

$oMrTestController->render(
    $cfg['path']['contenido'] . $cfg['path']['plugins'] . 'mod_rewrite/templates/contenttest.html'
);
