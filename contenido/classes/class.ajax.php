<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Class for outputting some content for Ajax use
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package CONTENIDO Content Types
 * @version 1.0.2
 * @author Timo Trautmann
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 * @since file available since CONTENIDO release 4.8.12
 */

/**
 * Class for outputting some content for Ajax use
 */
class cAjaxRequest {

    /**
     * Function for handling requested ajax data
     *
     * @param string $action - name of requested ajax action
     */
    public function handle($action) {
        $string = '';
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
                $fileList = new cContentTypeFileList($artReturn, $fileListId, array());

                $string = $fileList->generateDirectoryList($fileList->buildDirectoryList($cfgClient[$client]['upl']['path'] . $dirName));
                break;

            case 'filelist':
                $dirName = (string) $_REQUEST['dir'];
                $fileListId = (int) $_REQUEST['id'];
                $idArtLang = (int) $_REQUEST['idartlang'];

                $art = new cApiArticleLanguage($idArtLang, true);
                $artReturn = $art->getContent('CMS_FILELIST', $fileListId);
                $fileList = new cContentTypeFileList($artReturn, $fileListId, array());

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
                            $response = '<br />';
                            foreach ($usedTemplates as $i => $usedTemplate) {
                                $template->set('d', 'NAME', $usedTemplate['tpl_name']);
                                $template->next();
                            }

                            $template->set('s', 'HEAD_NAME', i18n('Template name'));
                            $string = '<div class="inuse_info" >' . $template->generate($cfg['path']['contenido'] . $cfg['path']['templates'] . $cfg['templates']['inuse_lay_mod'], true) . '</div>';
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
                            $template->set('d', 'NAME', $usedTemplate['tpl_name']);
                            $template->next();
                        }

                        $template->set('s', 'HEAD_NAME', i18n('Template name'));
                        $string = '<div class="inuse_info" >' . $template->generate($cfg['path']['contenido'] . $cfg['path']['templates'] . $cfg['templates']['inuse_lay_mod'], true) . '</div>';
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
                        $response = $template->generate($cfg['path']['contenido'] . $cfg['path']['templates'] . $cfg['templates']['inuse_tpl'], true);
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
                        $response .= $template->generate($cfg['path']['contenido'] . $cfg['path']['templates'] . $cfg['templates']['inuse_tpl'], true);
                    }

                    $string = '<div class="inuse_info" >' . $response . '</div>';
                } else {
                    $string = i18n('No data found!');
                }

                break;

            case 'scaleImage':
                global $cfgClient, $client;
                $filename = $_REQUEST['url'];
                $filename = str_replace($cfgClient[$client]['path']['htmlpath'], $cfgClient[$client]['path']['frontend'], $filename);
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
                        $string = $_REQUEST['sUrl'];
                        break;
                }
                // if can not scale, so $sString is null, then show the original
                // image.
                if ($string == '') {
                    $filename = str_replace($cfgClient[$client]['path']['frontend'], $cfgClient[$client]['path']['htmlpath'], $_REQUEST['sUrl']);
                    $string = $filename;
                }
                break;

            case 'imagelist':
                global $cfg, $client, $lang, $cfgClient;

                $dirName = (string) $_REQUEST['dir'];
                $imageId = (int) $_REQUEST['id'];
                $idArtLang = (int) $_REQUEST['idartlang'];

                $art = new cApiArticleLanguage($idArtLang, true);
                $artReturn = $art->getContent('CMS_IMGEDITOR', $imageId);
                $image = new cContentTypeImgEditor($artReturn, $imageId, array());

                $string = $image->generateFileSelect($dirName);
                break;

            case 'loadImageMeta':
                $imageId = (int) $_REQUEST['id'];
                $idArtLang = (int) $_REQUEST['idartlang'];

                $art = new cApiArticleLanguage($idArtLang, true);
                $artReturn = $art->getContent('CMS_IMGEDITOR', $imageId);
                $image = new cContentTypeImgEditor($artReturn, $imageId, array());

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
                $image = new cContentTypeImgEditor($artReturn, $imageId, array());

                $string = $image->uplmkdir($path, $name);
                break;

            case 'upl_upload':
                $imageId = (int) $_REQUEST['id'];
                $idArtLang = (int) $_REQUEST['idartlang'];
                $path = (string) $_REQUEST['path'];

                $art = new cApiArticleLanguage($idArtLang, true);
                $artReturn = $art->getContent('CMS_IMGEDITOR', $imageId);
                $image = new cContentTypeImgEditor($artReturn, $imageId, array());

                $string = $image->uplupload($path);
                break;

            case 'linkeditorfilelist':
                $id = (int) $_REQUEST['id'];
                $idArtLang = (int) $_REQUEST['idartlang'];
                $idCat = (string) $_REQUEST['idcat'];

                $art = new cApiArticleLanguage($idArtLang, true);
                $artReturn = $art->getContent('CMS_LINKEDITOR', $id);
                $linkEditor = new cContentTypeLinkEditor($artReturn, $id, array());

                $string = $linkEditor->generateArticleSelect($idCat);
                break;

            case 'linkeditordirlist':
                $id = (int) $_REQUEST['id'];
                $idArtLang = (int) $_REQUEST['idartlang'];
                $levelId = (string) $_REQUEST['level'];
                $parentidcat = (string) $_REQUEST['parentidcat'];

                $art = new cApiArticleLanguage($idArtLang, true);
                $artReturn = $art->getContent('CMS_LINKEDITOR', $id);
                $linkEditor = new cContentTypeLinkEditor($artReturn, $id, array());

                $string = $linkEditor->getCategoryList($linkEditor->buildCategoryArray($levelId, $parentidcat));
                break;

            case 'linkeditorimagelist':
                $dirName = (string) $_REQUEST['dir'];
                $id = (int) $_REQUEST['id'];
                $idArtLang = (int) $_REQUEST['idartlang'];

                $art = new cApiArticleLanguage($idArtLang, true);
                $artReturn = $art->getContent('CMS_LINKEDITOR', $id);
                $linkEditor = new cContentTypeLinkEditor($artReturn, $id, array());

                $string = $linkEditor->getUploadFileSelect($dirName);
                break;
            default:
                // if action is unknown generate error message
                $string = 'Unknown Ajax Action';
                break;
        }

        return $string;
    }

}

class Ajax extends cAjaxRequest {

    /**
     *
     * @deprecated Class was renamed to cAjaxRequest
     */
    public function __construct() {
        cDeprecated('Class was renamed to cAjaxRequest');
    }

}