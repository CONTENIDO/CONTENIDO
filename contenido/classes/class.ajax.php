<?php
/**
 * This file contains the backend ajax handler class.
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author Timo Trautmann
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class for outputting some content for Ajax use
 *
 * @package Core
 * @subpackage Backend
 */
class cAjaxRequest {

    /**
     * Function for handling requested ajax data
     *
     * @param string $action - name of requested ajax action
     * @return string
     */
    public function handle($action) {
        $backendPath = cRegistry::getBackendPath();
        $string = '';

        $frontendURL = cRegistry::getFrontendUrl();
        $frontendPath = cRegistry::getFrontendPath();

        switch ($action) {
            // case to get an article select box param name value and idcat were
            // neded (name= name of select box value=selected item)
            case 'artsel':
                $name = (string) $_REQUEST['name'];
                $value = (int) $_REQUEST['value'];
                $idCat = (int) $_REQUEST['idcat'];
                $string = buildArticleSelect($name, $idCat, $value);
                break;

            case 'dirlist':
                global $cfgClient, $client;

                $dirName = (string) $_REQUEST['dir'];
                $fileListId = (int) $_REQUEST['id'];
                $idArtLang = (int) $_REQUEST['idartlang'];

                $art = new cApiArticleLanguage($idArtLang, true);
                $artReturn = $art->getContent('CMS_FILELIST', $fileListId);
                $fileList = new cContentTypeFilelist($artReturn, $fileListId, array());

                $string = $fileList->generateDirectoryList($fileList->buildDirectoryList($cfgClient[$client]['upl']['path'] . $dirName));
                break;

            case 'filelist':
                $dirName = (string) $_REQUEST['dir'];
                $fileListId = (int) $_REQUEST['id'];
                $idArtLang = (int) $_REQUEST['idartlang'];

                $art = new cApiArticleLanguage($idArtLang, true);
                $artReturn = $art->getContent('CMS_FILELIST', $fileListId);
                $fileList = new cContentTypeFilelist($artReturn, $fileListId, array());

                $string = $fileList->generateFileSelect($dirName);
                break;

            case 'inused_layout':
                // list of used templates for a layout
                global $cfg;
                if ((int) $_REQUEST['id'] > 0) {
                    $layout = new cApiLayout((int) $_REQUEST['id']);
                    if ($layout->isInUse(true)) {
                        $template = new cTemplate();
                        $usedTemplates = $layout->getUsedTemplates();
                        if (count($usedTemplates) > 0) {
                            $response = '<br>';
                            foreach ($usedTemplates as $i => $usedTemplate) {
                                if ($i % 2 == 0) {
                                    $template->set('d', 'CLASS', 'grey');
                                } else {
                                    $template->set('d', 'CLASS', 'white');
                                }
                                $template->set('d', 'NAME', $usedTemplate['tpl_name']);
                                $template->next();
                            }

                            $string = '<div class="inuse_info" >' . $template->generate($backendPath . $cfg['path']['templates'] . $cfg['templates']['inuse_lay_mod'], true) . '</div>';
                        } else {
                            $string = i18n('No data found!');
                        }
                    }
                }
                break;

            case 'inused_module':
                // list of used templates for a module
                global $cfg;
                $module = new cApiModule();
                if ((int) $_REQUEST['id'] > 0 && $module->moduleInUse((int) $_REQUEST['id'], true)) {
                    $template = new cTemplate();
                    $usedTemplates = $module->getUsedTemplates();
                    if (count($usedTemplates) > 0) {
                        foreach ($usedTemplates as $i => $usedTemplate) {
                            if ($i % 2 == 0) {
                                $template->set('d', 'CLASS', 'grey');
                            } else {
                                $template->set('d', 'CLASS', 'white');
                            }

                            $template->set('d', 'NAME', $usedTemplate['tpl_name']);
                            $template->next();
                        }

                        $string = '<div class="inuse_info" >' . $template->generate($backendPath . $cfg['path']['templates'] . $cfg['templates']['inuse_lay_mod'], true) . '</div>';
                    } else {
                        $string = i18n('No data found!');
                    }
                }
                break;

            case 'inused_template':
                // list of used category and art
                global $cfg;
                cInclude('backend', 'includes/functions.tpl.php');

                if ((int) $_REQUEST['id'] > 0) {
                    $template = new cTemplate();
                    $template->reset();
                    $usedData = tplGetInUsedData((int) $_REQUEST['id']);

                    if (isset($usedData['cat'])) {
                        $template->set('s', 'HEAD_TYPE', i18n('Category'));
                        foreach ($usedData['cat'] as $i => $cat) {
                            $template->set('d', 'ID', $cat['idcat']);
                            $template->set('d', 'LANG', $cat['lang']);
                            $template->set('d', 'NAME', $cat['name']);
                            $template->next();
                        }
                        $template->set('s', 'HEAD_ID', i18n('idcat'));
                        $template->set('s', 'HEAD_LANG', i18n('idlang'));
                        $template->set('s', 'HEAD_NAME', i18n('Name'));
                        $response = $template->generate($backendPath . $cfg['path']['templates'] . $cfg['templates']['inuse_tpl'], true);
                    }

                    $template->reset();

                    if (isset($usedData['art'])) {
                        $template->set('s', 'HEAD_TYPE', i18n('Article'));
                        foreach ($usedData['art'] as $i => $aArt) {
                            $template->set('d', 'ID', $aArt['idart']);
                            $template->set('d', 'LANG', $aArt['lang']);
                            $template->set('d', 'NAME', $aArt['title']);
                            $template->next();
                        }
                        $template->set('s', 'HEAD_ID', i18n('idart'));
                        $template->set('s', 'HEAD_LANG', i18n('idlang'));
                        $template->set('s', 'HEAD_NAME', i18n('Name'));
                        $response .= $template->generate($backendPath . $cfg['path']['templates'] . $cfg['templates']['inuse_tpl'], true);
                    }

                    $string = '<div class="inuse_info" >' . $response . '</div>';
                } else {
                    $string = i18n('No data found!');
                }

                break;

            case 'scaleImage':
                $filename_a = $_REQUEST['url'];
                $filename = str_replace($frontendURL, $frontendPath, $filename_a);
                // $filename muss not url path(http://) sondern globale PC
                // Path(c:/) sein.
                $filetype = substr($filename, strlen($filename) - 4, 4);
                switch (strtolower($filetype)) {
                    case '.gif':
                        $string = cApiImgScale($filename, 428, 210);
                        break;
                    case '.png':
                        $string = cApiImgScale($filename, 428, 210);
                        break;
                    case '.jpg':
                        $string = cApiImgScale($filename, 428, 210);
                        break;
                    case 'jpeg':
                        $string = cApiImgScale($filename, 428, 210);
                        break;
                    default:
                        $string = $filename_a;
                        break;
                }
                // if can not scale, so $sString is NULL, then show the original
                // image.
                if ($string == '') {
                    $filename = str_replace($frontendPath, $frontendURL, $filename_a);
                    $string = $filename;
                }
                break;

            case 'imagelist':
                $dirName = (string) $_REQUEST['dir'];
                $imageId = (int) $_REQUEST['id'];
                $idArtLang = (int) $_REQUEST['idartlang'];

                $art = new cApiArticleLanguage($idArtLang, true);
                $artReturn = $art->getContent('CMS_IMGEDITOR', $imageId);
                $image = new cContentTypeImgeditor($artReturn, $imageId, array());

                $string = $image->generateFileSelect($dirName);
                break;

            case 'loadImageMeta':
                $imageId = (int) $_REQUEST['id'];
                $idArtLang = (int) $_REQUEST['idartlang'];

                $art = new cApiArticleLanguage($idArtLang, true);
                $artReturn = $art->getContent('CMS_IMGEDITOR', $imageId);
                $image = new cContentTypeImgeditor($artReturn, $imageId, array());

                $filename = (string) basename($_REQUEST['filename']);
                $dirname = (string) dirname($_REQUEST['filename']);
                if ($dirname != '.') {
                    $dirname .= '/';
                } else {
                    $dirname = '';
                }

                $string = $image->getImageMeta($filename, $dirname);
                break;

            case 'upl_mkdir':
                $imageId = (int) $_REQUEST['id'];
                $idArtLang = (int) $_REQUEST['idartlang'];
                $path = (string) $_REQUEST['path'];
                $name = (string) $_REQUEST['foldername'];

                $art = new cApiArticleLanguage($idArtLang, true);
                $artReturn = $art->getContent('CMS_IMGEDITOR', $imageId);
                $image = new cContentTypeImgeditor($artReturn, $imageId, array());

                $string = $image->uplmkdir($path, $name);
                switch ($string) {
                    case 1:
                        break;
                    case '0702':
                        $string = i18n('Directory already exist.');
                        break;
                    case '0703':
                        $string = i18n('Directories with special characters and spaces are not allowed.');
                        break;
                }
                break;

            case 'upl_upload':
                $imageId = (int) $_REQUEST['id'];
                $idArtLang = (int) $_REQUEST['idartlang'];
                $path = (string) $_REQUEST['path'];
                if ($path == '/') {
                	$path = '';
                }

                $art = new cApiArticleLanguage($idArtLang, true);
                $artReturn = $art->getContent('CMS_IMGEDITOR', $imageId);
                $image = new cContentTypeImgeditor($artReturn, $imageId, array());

                $string = $image->uplupload($path);
                break;

            case 'linkeditorfilelist':
                $id = (int) $_REQUEST['id'];
                $idArtLang = (int) $_REQUEST['idartlang'];
                $idCat = (string) $_REQUEST['idcat'];

                $art = new cApiArticleLanguage($idArtLang, true);
                $artReturn = $art->getContent('CMS_LINKEDITOR', $id);
                $linkEditor = new cContentTypeLinkeditor($artReturn, $id, array());

                $string = $linkEditor->generateArticleSelect($idCat);
                break;

            case 'linkeditordirlist':
                $id = (int) $_REQUEST['id'];
                $idArtLang = (int) $_REQUEST['idartlang'];
                $levelId = (string) $_REQUEST['level'];
                $parentidcat = (string) $_REQUEST['parentidcat'];

                $art = new cApiArticleLanguage($idArtLang, true);
                $artReturn = $art->getContent('CMS_LINKEDITOR', $id);
                $linkEditor = new cContentTypeLinkeditor($artReturn, $id, array());

                $string = $linkEditor->getCategoryList($linkEditor->buildCategoryArray($levelId, $parentidcat));
                break;

            case 'linkeditorimagelist':
                $dirName = (string) $_REQUEST['dir'];
                $id = (int) $_REQUEST['id'];
                $idArtLang = (int) $_REQUEST['idartlang'];

                $art = new cApiArticleLanguage($idArtLang, true);
                $artReturn = $art->getContent('CMS_LINKEDITOR', $id);
                $linkEditor = new cContentTypeLinkeditor($artReturn, $id, array());

                $string = $linkEditor->getUploadFileSelect($dirName);
                break;

            case 'generaljstranslations':
                $translations = array();
                $translations['Confirmation Required'] = i18n('Confirmation Required');
                $translations['OK'] = i18n('OK');
                $translations['Cancel'] = i18n('Cancel');
                $string = json_encode($translations);
                break;

            case 'logfilecontent':
                $type = $_REQUEST['logfile'];
                $numberOfLines = $_REQUEST['numberOfLines'];
                $cfg = cRegistry::getConfig();
                $filename = $cfg['path']['frontend'] . DIRECTORY_SEPARATOR . $cfg['path']['logs'] . $type;
                $string = cFileHandler::read($filename);
                $lines = file($filename);
                $lines = array_splice($lines, $numberOfLines * -1);
                $string = implode('', $lines);
                break;

            case 'updatepluginorder':
                if (cRegistry::getPerm()->have_perm()) { // only sysadmins can do this
                    $newOrder = cSecurity::toInteger($_POST['neworder']);
                    $pluginColl = new PimPluginCollection();
                    $pluginColl->select();
                    if ($newOrder <= 0 || $newOrder > $pluginColl->count()) {
                        $string = 'order must be > 0 and <= number of plugins';
                        break;
                    }

                    $pluginId = cSecurity::toInteger($_POST['idplugin']);
                    $plugin = new PimPlugin($pluginId);
                    $result = $plugin->updateExecOrder($newOrder);
                    if ($result == true) {
                    	$string = 'ok';
                    }
                } else {
                    $string = 'Unknown Ajax Action';
                }
                break;

            case 'verify_module':
                // Module syntax check
                $idmod = isset($_POST['idmod']) ? $_POST['idmod'] : NULL;
                $inputType = isset($_POST['type']) ? $_POST['type'] : NULL;

                // NOTE: The default setting is to check the modules
                $moduleCheck = getSystemProperty('system', 'modulecheck');
                $moduleCheck = $moduleCheck == '' || $moduleCheck == 'true';

                $result = array(
                    'state' => 'error',
                    'message' => 'No cModuleHandler for ' . $idmod . ', or wrong code type: ' . $inputType
                );

                if ($idmod && $inputType && $moduleCheck === true) {
                    $contenidoModuleHandler = new cModuleHandler($idmod);
                    switch ($inputType) {
                        case 'input':
                            $result = $contenidoModuleHandler->testInput();
                            break;
                        case 'output':
                            $result = $contenidoModuleHandler->testOutput();
                            break;
                    }

                    //create answer
                    if ($result['state']) {
                        $result['state'] = 'ok';
                        $result['message'] = i18n("Module successfully compiled");
                    } else {
                        $result['state'] = 'error';
                        $result['message'] = $result['errorMessage'];
                    }
                }

                $string = json_encode($result);
                break;

            case 'authentication_fail':
                // Not authenticated AJAX request, e. g. invalid or expired session
                $result = array(
                    'state' => 'error',
                    'code' => 401,
                    'message' => 'Unauthorized',
                );
                $string = json_encode($result);
                break;

            default:
                // If action is unknown generate error message
                $string = 'Unknown Ajax Action';
                break;
        }

        return $string;
    }

}
