<?php
/**
 * Plugin mod_rewrite backend include file to administer expert (in content frame)
 *
 * @date        22.04.2008
 * @author      Murat Purc
 * @copyright   © Murat Purc 2008
 * @package     Contenido
 * @subpackage  ModRewrite
 */

defined('CON_FRAMEWORK') or die('Illegal call');

################################################################################
##### Initialization

plugin_include('mod_rewrite', 'classes/controller/class.modrewrite_contentexpert_controller.php');

$action = (isset($_REQUEST['mr_action'])) ? $_REQUEST['mr_action'] : 'index';
$debug  = false;


################################################################################
##### Some variables


$oMrController = new ModRewrite_ContentExpertController();

$aMrCfg = ModRewrite::getConfig();

$aHtaccessInfo = ModRewrite::getHtaccessInfo();

// define basic data contents (used for template)
$oView = $oMrController->getView();

// mr copy .htaccess
$oView->copy_htaccess_css = 'display:table-row;';
$oView->copy_htaccess_error = '';
$oView->copy_htaccess_contenido_chk = ' checked="checked"';
$oView->copy_htaccess_cms_chk = '';
$oView->contenido_full_path = $aHtaccessInfo['contenido_full_path'];
$oView->client_full_path    = $aHtaccessInfo['client_full_path'];
$oView->content_after = '';

$oMrController->setProperty('htaccessInfo', $aHtaccessInfo);

################################################################################
##### Action processing

if ($action == 'index') {

    $oMrController->indexAction();

} elseif ($action == 'copyhtaccess') {

    $oMrController->copyHtaccessAction();

} elseif ($action == 'downloadhtaccess') {

    $oMrController->downloadHtaccessAction();
    exit();

} elseif ($action == 'reset') {

    $oMrController->resetAction();

} elseif ($action == 'resetempty') {

    $oMrController->resetEmptyAction();

} else {

    $oMrController->indexAction();

}


################################################################################
##### Output

$oMrController->render(
    $cfg['path']['contenido'] . $cfg['path']['plugins'] . 'mod_rewrite/templates/contentexpert.html'
);

