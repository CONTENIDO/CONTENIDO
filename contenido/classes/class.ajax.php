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
 * @package    CONTENIDO Content Types
 * @version    1.0.2
 * @author     Timo Trautmann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.8.12
 *
 * {@internal
 *   created 2009-04-08
 *
 *   $Id$:
 * }}
 *
 */

/**
 * Class for outputting some content for Ajax use
 *
 */
class Ajax
{

    /**
     * Constructor of class
     */
    public function __construct()
    {
        // donut
    }

    /**
      * Function for handling requested ajax data
      *
      * @param string $sAction - name of requested ajax action
      */
    public function handle($sAction)
    {
        $sString = '';
        switch ($sAction) {
            //case to get an article select box param name value and idcat were neded (name= name of select box value=selected item)
            case 'artsel':
                $sName = (string) $_REQUEST['name'];
                $iValue = (int) $_REQUEST['value'];
                $iIdCat = (int) $_REQUEST['idcat'];
                $sString = buildArticleSelect($sName, $iIdCat, $iValue);
                break;

            case 'dirlist':
                global $cfg, $client, $lang, $cfgClient;

                $sDirName = (string) $_REQUEST['dir'];
                $iFileListId = (int) $_REQUEST['id'];
                $iIdArtLang = (int) $_REQUEST['idartlang'];

                $oArt = new cApiArticleLanguage($iIdArtLang, true);
                $sArtReturn = $oArt->getContent('CMS_FILELIST', $iFileListId);
                $oFileList = new cContentTypeFileList($sArtReturn, $iFileListId, array());

                $sString = $oFileList->generateDirectoryList($oFileList->buildDirectoryList($cfgClient[$client]['upl']['path'] . $sDirName));
                break;

            case 'filelist':
                global $cfg, $client, $lang, $cfgClient;

                $sDirName = (string) $_REQUEST['dir'];
                $iFileListId = (int) $_REQUEST['id'];
                $iIdArtLang = (int) $_REQUEST['idartlang'];

                $oArt = new cApiArticleLanguage($iIdArtLang, true);
                $sArtReturn = $oArt->getContent('CMS_FILELIST', $iFileListId);
                $oFileList = new cContentTypeFileList($sArtReturn, $iFileListId, array());

                $sString = $oFileList->generateFileSelect($sDirName);
                break;

            case 'inused_layout':
                //list of used templates for a layout
                global $cfg;
                if ((int) $_REQUEST['id'] > 0) {
                    $oLayout = new cApiLayout((int) $_REQUEST['id']);
                    if ($oLayout->isInUse(true)) {
                        $oTpl = new Template();
                        $aUsedTpl = $oLayout->getUsedTemplates();
                        if (count($aUsedTpl) > 0) {
                            $sResponse = '<br />';
                            foreach ($aUsedTpl as $i => $aTpl) {
                                $oTpl->set('d', 'NAME', $aTpl['tpl_name'] );
                                $oTpl->next();
                            }

                            $oTpl->set('s', 'HEAD_NAME', i18n('Template name'));
                            $sString = '<div class="inuse_info" >' .
                                        $oTpl->generate($cfg['path']['contenido'] . $cfg['path']['templates'] .
                                                        $cfg['templates']['inuse_lay_mod'], true) .
                                        '</div>';
                        } else {
                            $sString = i18n("No data found!");
                        }
                    }
                }
                break;

            case 'inused_module':
                //list of used templates for a module
                global $cfg;
                $oModule = new cApiModule();
                if ((int) $_REQUEST['id'] > 0 && $oModule->moduleInUse((int) $_REQUEST['id'], true)) {
                    $oTpl = new Template();
                    $aUsedTpl = $oModule->getUsedTemplates();
                    if (count($aUsedTpl) > 0) {
                        foreach ($aUsedTpl as $i => $aTpl) {
                            $oTpl->set('d', 'NAME', $aTpl['tpl_name'] );
                            $oTpl->next();
                        }

                        $oTpl->set('s', 'HEAD_NAME', i18n('Template name'));
                        $sString = '<div class="inuse_info" >' .
                                    $oTpl->generate($cfg['path']['contenido'] . $cfg['path']['templates'] .
                                                    $cfg['templates']['inuse_lay_mod'], true) .
                                    '</div>';

                    } else {
                        $sString = i18n("No data found!");
                    }
                }

                break;

            case 'inused_template':
                // list of used category and art

                global $cfg;
                cInclude('backend', 'includes/functions.tpl.php');

                if ((int) $_REQUEST['id'] > 0) {
                    $oTpl = new Template();
                    $oTpl->reset();
                    $aUsedData = tplGetInUsedData((int) $_REQUEST['id']);

                    if (isset($aUsedData['cat'])) {
                        $oTpl->set('s', 'HEAD_TYPE', i18n('Category'));
                        foreach ($aUsedData['cat'] as $i => $aCat) {
                            $oTpl->set('d', 'ID', $aCat['idcat']);
                            $oTpl->set('d', 'LANG', $aCat['lang']);
                            $oTpl->set('d', 'NAME', $aCat['name']);
                            $oTpl->next();
                        }
                        $oTpl->set('s', 'HEAD_ID', i18n('idcat'));
                        $oTpl->set('s', 'HEAD_LANG', i18n('idlang'));
                        $oTpl->set('s', 'HEAD_NAME', i18n('Name'));
                        $sResponse = $oTpl->generate($cfg['path']['contenido'] . $cfg['path']['templates'] . $cfg['templates']['inuse_tpl'], true);
                    }

                    $oTpl->reset();

                    if (isset($aUsedData['art'])) {
                        $oTpl->set('s', 'HEAD_TYPE', i18n('Article'));
                        foreach ($aUsedData['art'] as $i => $aArt) {
                            $oTpl->set('d', 'ID', $aArt['idart']);
                            $oTpl->set('d', 'LANG', $aArt['lang']);
                            $oTpl->set('d', 'NAME', $aArt['title']);
                            $oTpl->next();
                        }
                        $oTpl->set('s', 'HEAD_ID', i18n('idart'));
                        $oTpl->set('s', 'HEAD_LANG', i18n('idlang'));
                        $oTpl->set('s', 'HEAD_NAME', i18n('Name'));
                        $sResponse .= $oTpl->generate($cfg['path']['contenido'] . $cfg['path']['templates'] . $cfg['templates']['inuse_tpl'], true);
                    }

                    $sString = '<div class="inuse_info" >' . $sResponse . '</div>';

                } else {
                    $sString = i18n("No data found!");
                }

                break;

            case 'scaleImage':
                global $cfg, $client, $lang, $cfgClient;
                $filename = $_REQUEST['url'];
                $filename = str_replace($cfgClient[$client]['path']['htmlpath'], $cfgClient[$client]['path']['frontend'], $filename);
                //$filename muss not url path(http://) sondern globale PC Path(c:/) sein.
                $filetype = substr($filename, strlen($filename) -4, 4);
                switch (strtolower($filetype)){
                    case '.gif': $sString = cApiImgScale($filename, 428, 210); break;
                    case '.png': $sString = cApiImgScale($filename, 428, 210); break;
                    case '.jpg': $sString = cApiImgScale($filename, 428, 210); break;
                    case 'jpeg': $sString = cApiImgScale($filename, 428, 210); break;
                    default: $sString = $_REQUEST['sUrl']; break;
                }
                //if can not scale, so $sString is null, then show the original image.
                if ($sString == '') {
                    $filename = str_replace($cfgClient[$client]['path']['frontend'], $cfgClient[$client]['path']['htmlpath'], $_REQUEST['sUrl']);
                    $sString = $filename;
                }
                break;

            case 'imagelist':
                global $cfg, $client, $lang, $cfgClient;

                $sDirName = (string) $_REQUEST['dir'];
                $iImageId = (int) $_REQUEST['id'];
                $iIdArtLang = (int) $_REQUEST['idartlang'];

                $oArt = new cApiArticleLanguage($iIdArtLang, true);
                $sArtReturn = $oArt->getContent('CMS_IMGEDITOR', $iImageId);
                $oImage = new cContentTypeImgEditor($sArtReturn, $iImageId, array());

                $sString = $oImage->generateFileSelect($sDirName);
                break;

            case 'loadImageMeta':
                global $cfg, $client, $lang, $cfgClient;

                $iImageId = (int) $_REQUEST['id'];
                $iIdArtLang = (int) $_REQUEST['idartlang'];

                $oArt = new cApiArticleLanguage($iIdArtLang, true);
                $sArtReturn = $oArt->getContent('CMS_IMGEDITOR', $iImageId);
                $oImage = new cContentTypeImgEditor($sArtReturn, $iImageId, array());

                $sFilename = (string) basename($_REQUEST['filename']);
                $sDirname = (string) dirname($_REQUEST['filename']);
                if ($sDirname != '.'){
                    $sDirname .= '/';
                } else {
                    $sDirname = '';
                }

                $sString = $oImage->getImageMeta($sFilename, $sDirname);
                break;

            case 'upl_mkdir':
                global $cfg, $client, $lang, $cfgClient;

                $iImageId = (int) $_REQUEST['id'];
                $iIdArtLang = (int) $_REQUEST['idartlang'];
                $sPath = (string) $_REQUEST['path'];
                $sName = (string) $_REQUEST['foldername'];

                $oArt = new cApiArticleLanguage($iIdArtLang, true);
                $sArtReturn = $oArt->getContent('CMS_IMGEDITOR', $iImageId);
                $oImage = new cContentTypeImgEditor($sArtReturn, $iImageId, array());

                $sString = $oImage->uplmkdir($sPath, $sName);
                break;

            case 'upl_upload':
                global $cfg, $client, $lang, $cfgClient;

                $iImageId = (int) $_REQUEST['id'];
                $iIdArtLang = (int) $_REQUEST['idartlang'];
                $sPath = (string) $_REQUEST['path'];

                $oArt = new cApiArticleLanguage($iIdArtLang, true);
                $sArtReturn = $oArt->getContent('CMS_IMGEDITOR', $iImageId);
                $oImage = new cContentTypeImgEditor($sArtReturn, $iImageId, array());

                $sString = $oImage->uplupload($sPath);
                break;

            case 'linkeditorfilelist':
                global $cfg, $client, $lang, $cfgClient;

                $iId = (int) $_REQUEST['id'];
                $iIdArtLang = (int) $_REQUEST['idartlang'];
                $iIdCat = (string) $_REQUEST['idcat'];

                $oArt = new cApiArticleLanguage($iIdArtLang, true);
                $sArtReturn = $oArt->getContent('CMS_LINKEDITOR', $iId);
                $oLinkEditor = new cContentTypeLinkEditor($sArtReturn, $iId, array());

                $sString = $oLinkEditor->generateArticleSelect($iIdCat);
                break;

            case 'linkeditordirlist':
                global $cfg, $client, $lang, $cfgClient;

                $iId = (int) $_REQUEST['id'];
                $iIdArtLang = (int) $_REQUEST['idartlang'];
                $iLevelId = (string) $_REQUEST['level'];
                $iParentidcat = (string) $_REQUEST['parentidcat'];

                $oArt = new cApiArticleLanguage($iIdArtLang, true);
                $sArtReturn = $oArt->getContent('CMS_LINKEDITOR', $iId);
                $oLinkEditor = new cContentTypeLinkEditor($sArtReturn, $iId, array());

                $sString = $oLinkEditor->getCategoryList($oLinkEditor->buildCategoryArray($iLevelId, $iParentidcat));
                break;

            case 'linkeditorimagelist':
                global $cfg, $client, $lang, $cfgClient;

                $sDirName = (string) $_REQUEST['dir'];
                $iId = (int) $_REQUEST['id'];
                $iIdArtLang = (int) $_REQUEST['idartlang'];

                $oArt = new cApiArticleLanguage($iIdArtLang, true);
                $sArtReturn = $oArt->getContent('CMS_LINKEDITOR', $iId);
                $oLinkEditor = new cContentTypeLinkEditor($sArtReturn, $iId, array());

                $sString = $oLinkEditor->getUploadFileSelect($sDirName);
                break;
            //if action is unknown generate error message
            default:
                $sString = 'Unknown Ajax Action';
                break;
        }

        return $sString;
    }
}

?>