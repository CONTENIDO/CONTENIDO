<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Class for outputting some content for Ajax use
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Content Types
 * @version    1.0.0
 * @author     Timo Trautmann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release 4.8.12
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
class Ajax {
	/**
	 * Constructor of class 
	 *
	 * @access public
	 */
	function __construct() {
	
	}
	
	/**
	  * Function for handling requested ajax data
	  *
	  * @param string $sAction - name of requested ajax action
	  * @access public
	  */
	public function handle($sAction) {
		$sString = '';
		switch($sAction) {
			//case to get an article select box param name value and idcat were neded (name= name of select box value=selected item)
			case 'artsel':
				$sName = (string) $_REQUEST['name'];
				$iValue = (int) $_REQUEST['value'];
				$iIdCat = (int) $_REQUEST['idcat'];
				$sString = buildArticleSelect($sName, $iIdCat, $iValue);
				break;
				
			case 'dirlist':	
				global $cfg, $client, $lang, $cfgClient;
			
				$sDirName 		= (string) $_REQUEST['dir'];
				$iFileListId 	= (int) $_REQUEST['id'];
				$iIdArtLang 	= (int) $_REQUEST['idartlang'];
				
				$oArt 			= new Article(null, null, null, $iIdArtLang);
				$sArtReturn 	= $oArt->getContent('CMS_FILELIST', $iFileListId);
				$oFileList 		= new Cms_FileList($sArtReturn, $iFileListId, 0, '', $cfg, null, '', $client, $lang, $cfgClient, null);
				
				$sString 		= $oFileList->getDirectoryList( $oFileList->buildDirectoryList ( $cfgClient[$client]['upl']['path'] . $sDirName ) );
				break;
				
			case 'filelist':
				global $cfg, $client, $lang, $cfgClient;
				
				$sDirName 		= (string) $_REQUEST['dir'];
				$iFileListId 	= (int) $_REQUEST['id'];
				$iIdArtLang 	= (int) $_REQUEST['idartlang'];
				
				$oArt 			= new Article(null, null, null, $iIdArtLang);
				$sArtReturn 	= $oArt->getContent('CMS_FILELIST', $iFileListId);
				$oFileList 		= new Cms_FileList($sArtReturn, $iFileListId, 0, '', $cfg, null, '', $client, $lang, $cfgClient, null);
				
				$sString 		= $oFileList->getFileSelect( $sDirName );
				break;
				
			case 'inused_layout': 
				//list of used templates for a layout
				global $cfg; 
				$oLayout = new Layout();
				if ((int) $_REQUEST['id'] > 0 && $oLayout->layoutInUse((int) $_REQUEST['id'] , true)) {
					$oTpl = new Template();
					$aUsedTpl = $oLayout->getUsedTemplates();
					if (count($aUsedTpl) > 0) {
						$sResponse = '<br />';
						foreach ($aUsedTpl as $i => $aTpl) {
							$oTpl->set('d', 'NAME', $aTpl['tpl_name'] );
							$oTpl->next();						
						}
						
						$oTpl->set('s', 'HEAD_NAME', i18n("Template name"));
						$sString = '<div class="inuse_info" >' . 
									$oTpl->generate($cfg['path']['contenido'] . $cfg['path']['templates'] . 
													$cfg['templates']['inuse_lay_mod'], true) . 
									'</div>';
					} else {
						$sString = i18n("No data found!");
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
						
						$oTpl->set('s', 'HEAD_NAME', i18n("Template name"));
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
						$oTpl->set('s', 'HEAD_TYPE', i18n("Category"));
						foreach ($aUsedData['cat'] as $i => $aCat) {
							$oTpl->set('d', 'ID', $aCat['idcat']);
							$oTpl->set('d', 'LANG', $aCat['lang']);
							$oTpl->set('d', 'NAME', $aCat['name']);
							$oTpl->next();
						}
						$oTpl->set('s', 'HEAD_ID', i18n("idcat"));
						$oTpl->set('s', 'HEAD_LANG', i18n("idlang"));
						$oTpl->set('s', 'HEAD_NAME', i18n("Name"));
						$sResponse = $oTpl->generate($cfg['path']['contenido'] . $cfg['path']['templates'] . $cfg['templates']['inuse_tpl'], true);
					}
					
					
					$oTpl->reset();
					
					if (isset($aUsedData['art'])) {
						$oTpl->set('s', 'HEAD_TYPE', i18n("Article"));
						foreach ($aUsedData['art'] as $i => $aArt) {
							$oTpl->set('d', 'ID', $aArt['idart']);
							$oTpl->set('d', 'LANG', $aArt['lang']);
							$oTpl->set('d', 'NAME', $aArt['title']);
							$oTpl->next();						
						}
						$oTpl->set('s', 'HEAD_ID', i18n("idart"));
						$oTpl->set('s', 'HEAD_LANG', i18n("idlang"));
						$oTpl->set('s', 'HEAD_NAME', i18n("Name"));
						$sResponse .= $oTpl->generate($cfg['path']['contenido'] . $cfg['path']['templates'] . $cfg['templates']['inuse_tpl'], true);
					}
					
					$sString = '<div class="inuse_info" >' . $sResponse . '</div>';
					
				} else {
					$sString = i18n("No data found!");
				}
				 
				break;
				
			//if action is unknown generate error message
			default:
				$sString = "Unknown Ajax Action";
				break;
		}
		
		return $sString;
	}
}

?>