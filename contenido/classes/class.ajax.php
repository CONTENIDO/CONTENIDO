<?php
/**
 * This file contains the class cAjaxRequest which handles ajax requests
 * for the CONTENIDO backend.
 *
 * @package Core
 * @subpackage Backend
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
     * Handles AJAX requests for certain data. Which data is returned
     * depends upon the given $action. If the $action is unknown an
     * error message is generated. Available actions are:
     *
     * <ul>
     *     <li>artsel
     *          Return a select box containing articles of a category.
     *          All params (name, idcat & value) are required.
     *          name = name of select box
     *          idcat = category ID whose articles should be contained
     *          value = selected article
     *     <li>dirlist
     *     <li>imgdirlist
     *     <li>filelist
     *     <li>inused_layout
     *          List templates using a given layout.
     *     <li>inused_module
     *          List templates using a given module.
     *     <li>inused_template
     *          List categories and articles using a given template.
     *     <li>scaleImage
     *     <li>imagelist
     *     <li>inlineeditart
     *     <li>loadImageMeta
     *     <li>upl_mkdir
     *     <li>upl_upload
     *     <li>linkeditorarticleslist
     *     <li>linkeditordirlist
     *     <li>linkeditorimagelist
     *     <li>generaljstranslations
     *     <li>logfilecontent
     *     <li>updatepluginorder
     *          only sysadmins can do this
     *     <li>verify_module
     *          check module syntax
     *          modules are checked by default
     *          This can be deactivated when the system property
     *          system/modulecheck is set accordingly.
     *          TODO Describe what "accordingly" means. The rules are a mess!
     *     <li>authentication_fail
     *          Returns a static answer for a not authenticated AJAX
     *          request, e.g. due to an invalid or expired session.
     * </ul>
     *
     * @todo split functionality into seperate methods
     * @todo use registry instead of globals where possible
     *
     * @param string $action
     *         name of requested ajax action
     *
     * @return string
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function handle($action) {
        $backendPath = cRegistry::getBackendPath();
        $frontendURL = cRegistry::getFrontendUrl();
        $frontendPath = cRegistry::getFrontendPath();

        $string = '';
        switch ($action) {
            case 'artsel':
                $name  = cSecurity::toString($_REQUEST['name']);
                $idcat = cSecurity::toInteger($_REQUEST['idcat']);
                $value = cSecurity::toInteger($_REQUEST['value']);

                $string = buildArticleSelect($name, $idcat, $value);
                break;

            case 'dirlist':

                $idartlang = cSecurity::toInteger($_REQUEST['idartlang']);
                $fileListId = cSecurity::toInteger($_REQUEST['id']);
                $dirname = cSecurity::toString($_REQUEST['dir']);

                $clientId = cRegistry::getClientId();
                $cfgClient = cRegistry::getClientConfig($clientId);
                $uplPath = $cfgClient['upl']['path'];

                $art = new cApiArticleLanguage($idartlang, true);
                $content = $art->getContent('CMS_FILELIST', $fileListId);

                $fileList = new cContentTypeFilelist($content, $fileListId, array());
                $directoryList = $fileList->buildDirectoryList($uplPath . $dirname);
                $string = $fileList->generateDirectoryList($directoryList);
                break;

            case 'imgdirlist':

                $idartlang  = cSecurity::toInteger($_REQUEST['idartlang']);
                $fileListId = cSecurity::toInteger($_REQUEST['id']);
                $dirname    = cSecurity::toString($_REQUEST['dir']);

                $clientId  = cRegistry::getClientId();
                $cfgClient = cRegistry::getClientConfig($clientId);
                $uplPath   = $cfgClient['upl']['path'];

                $art     = new cApiArticleLanguage($idartlang, true);
                $content = $art->getContent('CMS_IMGEDITOR', $fileListId);

                $fileList      = new cContentTypeImgeditor($content, $fileListId, []);
                $directoryList = $fileList->buildDirectoryList($uplPath . $dirname);
                $string = $fileList->generateDirectoryList($directoryList);
                break;

            case 'filelist':
                $idartlang  = cSecurity::toInteger($_REQUEST['idartlang']);
                $fileListId = cSecurity::toInteger($_REQUEST['id']);
                $dirname    = cSecurity::toString($_REQUEST['dir']);

                $art = new cApiArticleLanguage($idartlang, true);
                $content = $art->getContent('CMS_FILELIST', $fileListId);

                $fileList = new cContentTypeFilelist($content, $fileListId, array());
                $string = $fileList->generateFileSelect($dirname);
                break;

            case 'inused_layout':
                global $cfg;
                if (0 < (int) $_REQUEST['id']) {
                    $layout = new cApiLayout((int) $_REQUEST['id']);
                    if ($layout->isInUse(true)) {
                        $template = new cTemplate();
                        $usedTemplates = $layout->getUsedTemplates();
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
                }
                break;

            case 'inused_module':
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
                $filetype = cString::getPartOfString($filename, cString::getStringLength($filename) - 4, 4);
                switch (cString::toLowerCase($filetype)) {
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
                // if can not scale, so $string is NULL, then show the
                // original image
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

            case 'inlineeditart':

                $languageCollection = new cApiArticleLanguageCollection();

                for ($i = 0; $i < count($_REQUEST['fields']); $i++) {

                    $idartlang = $languageCollection->getIdByArticleIdAndLanguageId(cSecurity::toInteger($_REQUEST['fields'][$i]['idart']), cRegistry::getLanguageId());

                    $artLang = new cApiArticleLanguage(cSecurity::toInteger($idartlang));
                    $artLang->set('title', cSecurity::escapeString($_REQUEST['fields'][$i]['title']));
                    $artLang->set('artsort', cSecurity::escapeString($_REQUEST['fields'][$i]['index']));
                    $artLang->store();
                }

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
                    case '0704':
                        $string = i18n('Can not write directory.');
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

            case 'linkeditorarticleslist':
                $id = (int) $_REQUEST['id'];
                $idArtLang = (int) $_REQUEST['idartlang'];
                $idCat = (string) $_REQUEST['idcat'];

                $art = new cApiArticleLanguage($idArtLang, true);
                $artReturn = $art->getContent('CMS_LINKEDITOR', $id);
                $linkEditor = new cContentTypeLinkeditor($artReturn, $id, array());

                if ($idCat === '') {
                    $activeIdcats = $linkEditor->getActiveIdcats();
                    $idCat        = $activeIdcats[0];
                }

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

                if ($dirName === '') {
                    $dirName = dirname($linkEditor->getFilename());
                }

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
                $type = cSecurity::escapeString($_REQUEST['logfile']);
                $numberOfLines = cSecurity::toInteger($_REQUEST['numberOfLines']);
                $cfg = cRegistry::getConfig();
                if (in_array($type, $cfg['system_log']['allowed_filenames'])) {
                    $filename = $cfg['path']['frontend'] . DIRECTORY_SEPARATOR . $cfg['path']['logs'] . $type;
                    $lines = file($filename);
                    $lines = array_splice($lines, $numberOfLines * -1);
                    $string = implode('', $lines);
                }
                break;

            case 'updatepluginorder':
                // only sysadmins can do this
                if (cRegistry::getPerm()->have_perm()) {
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
                    if ($result === true) {
                        $string = 'ok';
                    }
                } else {
                    $string = 'Unknown Ajax Action';
                }
                break;

            case 'verify_module':
                $idmod = isset($_POST['idmod']) ? $_POST['idmod'] : NULL;
                $inputType = isset($_POST['type']) ? $_POST['type'] : NULL;

                // @see CON-2425 modules are checked by default
                $moduleCheck = getSystemProperty('system', 'modulecheck');
                $moduleCheck = ($moduleCheck == '' && $moduleCheck != 'false') || $moduleCheck == 'true' || $moduleCheck == '1';

                $result = array(
                    'state' => 'ok',
                    'message' => i18n("Module successfully compiled")
                );

                if ($idmod && $inputType && $moduleCheck) {
                    $contenidoModuleHandler = new cModuleHandler($idmod);
                    switch ($inputType) {
                        case 'input':
                            $result = $contenidoModuleHandler->testInput();
                            break;
                        case 'output':
                            $result = $contenidoModuleHandler->testOutput();
                            break;
                        default:
                            $result = array(
                                'state' => 'error',
                                'message' => 'No cModuleHandler for ' . $idmod . ', or wrong code type: ' . $inputType
                            );
                    }

                    // create answer
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
                $string = json_encode(array(
                    'state' => 'error',
                    'code' => 401,
                    'message' => 'Unauthorized',
                    'type' => 'authentication_failure'
                ));
                break;
            case 'custom':
                $string = cApiCecHook::executeAndReturn('Contenido.AjaxMain.CustomCall', $_REQUEST['method']);
                if($string === NULL) {
                    $string = 'Unknown Custom Ajax Action';
                }
                break;
            default:
                // If action is unknown generate error message
                $string = 'Unknown Ajax Action';
                break;
        }

        return $string;
    }

}
