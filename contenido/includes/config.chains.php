<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Contenido Chains
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.1
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-06-25, Frederic Schneider, add security fix
 *   modified 2008-07-31, Frederic Schneider, add Upl_edit-CECs
 *   modified 2008-08-29, Murat Purc, added several chains for category ans article processes
 *   modified 2008-09-07, Murat Purc, added chain 'Contenido.Frontend.AfterLoadPlugins'
 *   modified 2008-12-26, Murat Purc, added chain 'Contenido.Frontend.PreprocessUrlBuilding' and
 *                                    'Contenido.Frontend.PostprocessUrlBuilding'
 *   modified 2009-03-27, Andreas Lindner, Add title tag generation via chain    
 *
 *   $Id: config.chains.php 1012 2009-04-15 11:47:25Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

// get cec registry instance
$_cecRegistry = cApiCECRegistry::getInstance();


/* Chain Contenido.Content.CreateCategoryLink
 * This chain is called when a frontend link to a category should be created.
 *
 * NOTE: The last chain entry "wins" - e.g. if you have two chain functions for
 * CreateCategoryLink, the last one returns the effective link.
 * 
 * Parameters & order: 
 * int		idcat		idcat (Category ID)
 * 
 * Returns:
 * string	The link
 */
$_cecRegistry->registerChain("Contenido.Content.CreateCategoryLink", "int");

/* Chain Contenido.Content.CreateArticleLink
 * This chain is called when a frontend link to an article should be created.
 *
 * NOTE: The last chain entry "wins" - e.g. if you have two chain functions for
 * CreateArticleLink, the last one returns the effective link.
 * 
 * Parameters & order: 
 * int		idart		idart (Article ID)
 * int		idcat		Category ID
 * 
 * Returns:
 * string	The link
 */
$_cecRegistry->registerChain("Contenido.Content.CreateArticleLink", "int", "int");

/* Chain Contenido.Content.SaveContentEntry
 * This chain is called everytime when content is saved
 *
 * Parameters & order: 
 * int		idartlang		idartlang (Article ID)
 * int		type			type (e.g. CMS_HTML)
 * int		typeid			typeid (e.g. CMS_HTML[1])
 * string	value			value for that type
 * 
 * Returns:
 * string	The processed value
 */
$_cecRegistry->registerChain("Contenido.Content.SaveContentEntry", "int", "int", "int", "string");

/* Chain Contenido.Upload.UploadPreprocess
 * This chain is called everytime a file is uploaded
 *
 *
 * Parameters & order: 
 * string	filename		temporary filename you have to use to process
 * string   filename new	new filename the uploaded file will be stored as
 *
 * Returns:
 * mixed	Either returns the new filename or false if nothing was processed
 */
$_cecRegistry->registerChain("Contenido.Upload.UploadPreprocess", "string", "string");

/* Chain Contenido.Upload.UploadPostprocess
 * This chain is called everytime after a file is uploaded and stored in its 
 * final position
 *
 * Parameters & order: 
 * string	filename		full path and name of the uploaded file
 *
 * Returns:
 * nothing
 */
$_cecRegistry->registerChain("Contenido.Upload.UploadPostprocess", "string");

/* Chain Contenido.Frontend.CategoryAccess
 * This chain is called everytime the user tries to access a protected category
 *
 * Parameters & order:
 * int   	idlang	Language ID
 * int   	idcat	Category ID
 * string	userid	User String	
 *
 * Returns:
 * boolean 	Returns true if the user is allowed to connect
 */
$_cecRegistry->registerChain("Contenido.Frontend.CategoryAccess", "int", "int", "string");

/* Chain Contenido.ArticleCategoryList.ListItems
 * This chain is called when the category list in Content -> Articles should be build
 *
 * Parameters & order:
 * none
 *
 * Returns:
 * array	Array with the items that should be added. Array items:
 * 			$array["expandcollapseimage"]	Image which should be placed in front
 * 											of the folder icon. Recommended size is
 * 											11x11 pixels so it doesnt break the layout.
 * 											If empty or not set, a spacer image is
 * 											inserted.
 *			$array["image"]					Icon or image for the entry. Size should be
 *											15x15 pixels. If empty or not set, a 15x15
 *											spacer is inserted.
 *			$array["title"]					Title of the entry, including all links.
 *			$array["bgcolor"]				Background color in hex format, e.g. #123456
 *			$array["id"]					ID of the table data element
 *			$array["padding"]				Left padding in pixels. 
 */
$_cecRegistry->registerChain("Contenido.ArticleCategoryList.ListItems");

/* Chain Contenido.ArticleList.Columns
 * This chain is used to process the columns of the article list.
 *
 * Parameters & order:
 * array	columns		Array in the format $key => $description
 *
 * Returns:
 * array	Array with the processed column list
 * 
 * Notes about the array format:
 * $key is the column key. The following keys are predefined:
 * "start": Start article column
 * "title": Title of the article
 * "changeddate": Last changed date
 * "publisheddate": Published date
 * "sortorder": Sort order 
 * "template": Template name
 * "actions": All actions (see the chain Contenido.ArticleList.Actions
 * 
 * If you want to use own columns, append your own key and title to the
 * array and use the chain Contenido.ArticleList.RenderColumn
 */
$_cecRegistry->registerChain("Contenido.ArticleList.Columns", "array");

/* Chain Contenido.ArticleList.Actions
 * This chain is used to process the actions for articles
 *
 * Parameters & order:
 * array	columns		Array in the format $key => $description
 *
 * Returns:
 * array	Array with the processed column list
 * 
 * Notes about the array format:
 * $key is the action key. The following keys are predefined:
 * "todo": Shows the todo icon
 * "artconf": Shows the article property icon
 * "tplconf": Shows the template configuration icon
 * "online": Shows the online/offline icon
 * "locked": Shows the locked icon
 * "duplicate": Shows the duplicate icon
 * "delete": Shows the delete icon
 * "usetime": Shows the time management icon
 * 
 *
 */
$_cecRegistry->registerChain("Contenido.ArticleList.Actions", "array");

/* Chain Contenido.ArticleList.RenderColumn
 * This chain is used to render a single column for a specific article
 *
 * Parameters & order:
 * int		idcat		Category ID 
 * int		idart		Article ID
 * int		idartlang	Article language ID
 * string	columnkey	Column key to render
 *
 * Returns:
 * string	String with the rendered contents
 * 
 */
$_cecRegistry->registerChain("Contenido.ArticleList.RenderColumn", "int", "int", "int", "string");

/* Chain Contenido.ArticleList.RenderAction
 * This chain is used to render a single action for a specific article
 *
 * Parameters & order:
 * int		idcat		Category ID 
 * int		idart		Article ID
 * int		idartlang	Article language ID
 * string	actionkey	Action key to render
 *
 * Returns:
 * string	String with the rendered contents
 * 
 */
$_cecRegistry->registerChain("Contenido.ArticleList.RenderAction", "int", "int", "int", "string");

/* Chain Contenido.CategoryList.Columns
 * This chain is used to process the columns of the category list.
 * WARNING: Currently it's only possible to add new columns, but not
 * remove the standard ones.
 *
 * Parameters & order:
 * array	columns		Array in the format $key => $description
 *
 * Returns:
 * array	Array with the processed column list
 * 
 * 
 * If you want to use own columns, append your own key and title to the
 * array and use the chain Contenido.CategoryList.RenderColumn
 */
$_cecRegistry->registerChain("Contenido.CategoryList.Columns", "array");

/* Chain Contenido.CategoryList.RenderColumn
 * This chain is used to render a single column for a specific category
 *
 * Parameters & order:
 * int		idcat		Category ID 
 * string	columnkey	Column key to render
 *
 * Returns:
 * string	String with the rendered contents
 * 
 */
$_cecRegistry->registerChain("Contenido.CategoryList.RenderColumn", "int", "string");

/* Chain Contenido.Content.CopyArticle
 * This chain is called everytime when an article is duplicated
 *
 * Parameters & order: 
 * int		$srcidart ArticleId of original article
 * int 		$dstidart ArticleId of duplicated article	
 * 
 * Returns:
 * void
 */
$_cecRegistry->registerChain("Contenido.Content.CopyArticle", "int", "int");

/* Chain Contenido.Content.CreateMetatags
 * This chain is used to build up an user defined metatag array
 *
 * Parameters & order:
 * array		$metatag		metatag informations 
 *
 * Returns:
 * string	Array containing metatag informations
 * 
 */
$_cecRegistry->registerChain("Contenido.Content.CreateMetatags", "array");

/* Chain Contenido.Content.CreateTitletag
 * This chain is used to build up a user defined title tag
 *
 * Parameters & order:
 * none 
 *
 * Returns:
 * string	New title tag
 * 
 */
$_cecRegistry->registerChain("Contenido.Content.CreateTitletag");

/* Chain Contenido.Frontend.AllowEdit
 * This chain is used when an article is about to be edited. This chain can be used
 * to prevent users to edit certain articles, e.g. for workflows or for special types
 * of articles. 
 *
 * Parameters & order:
 * int		$lang	Language-ID
 * int		$idcat	Category ID
 * int		$idart	Article ID   
 * string	$auth	Authentification of the user
 *
 * Returns:
 * boolean	If false, the access for editing the article is locked. You can't allow 
 * 			editing an article with this chain, only disallow it.
 * 
 */
$_cecRegistry->registerChain("Contenido.Frontend.AllowEdit", "int", "int", "int", "string");

/* Chain Contenido.Permissions.User.Areas
 * This chain returns all areas which should appear in the user rights management.
 *
 * Parameters & order:
 * none
 *
 * Returns:
 * array	Array with all unique technical area names which should appear in the rights management. 
 * 			
 * Return Example:
 * array("mynewarea", "mynewarea2");
 * 
 * Note:
 * Technical area names have nothing to do with the areas defined in con_area! Use the chain
 * Contenido.Permissions.GetAreaName to retrieve the localized name of the area.
 * 
 */
$_cecRegistry->registerChain("Contenido.Permissions.User.Areas", "");

/* Chain Contenido.Permissions.User.GetAreaName
 * This chain returns the localized area name for a technical area name.
 *
 * Parameters & order:
 * string	Technical area name
 *
 * Returns:
 * mixed	Boolean false if the technical area name is not known, or a string with the localized name. 
 * 			
 */
$_cecRegistry->registerChain("Contenido.Permissions.User.GetAreaName", "string");

/* Chain Contenido.Permissions.User.GetAreaEditFilename
 * This chain returns the filename required for the permission editor.
 *
 * Parameters & order:
 * string	Technical area name
 *
 * Returns:
 * mixed	Boolean false if the technical area name is not known, or a string with the editor filename. 
 * 			
 */
$_cecRegistry->registerChain("Contenido.Permissions.User.GetAreaEditFilename", "string");

/* Chain Contenido.Permissions.FrontendUser.AfterDeletion
 * This chain function is called after a frontend user has been deleted from the database
 *
 * Parameters:
 * idfrontenduser	string	uid of deleted frontend user	
 *
 * Returns:
 * nothing 
 * 
 */
$_cecRegistry->registerChain("Contenido.Permissions.FrontendUser.AfterDeletion", "string");

/* Chain Contenido.Permissions.Group.Areas
 * This chain returns all areas which should appear in the group rights management.
 *
 * Parameters & order:
 * none
 *
 * Returns:
 * array	Array with all unique technical area names which should appear in the group rights management. 
 * 			
 * Return Example:
 * array("mynewarea", "mynewarea2");
 * 
 * Note:
 * Technical area names have nothing to do with the areas defined in con_area! Use the chain
 * Contenido.Permissions.GetAreaName to retrieve the localized name of the area.
 * 
 */
$_cecRegistry->registerChain("Contenido.Permissions.Group.Areas", "");

/* Chain Contenido.Permissions.Group.GetAreaName
 * This chain returns the localized area name for a technical area name.
 *
 * Parameters & order:
 * string	Technical area name
 *
 * Returns:
 * mixed	Boolean false if the technical area name is not known, or a string with the localized name. 
 * 			
 */
$_cecRegistry->registerChain("Contenido.Permissions.Group.GetAreaName", "string");

/* Chain Contenido.Permissions.Group.GetAreaEditFilename
 * This chain returns the filename required for the permission editor.
 *
 * Parameters & order:
 * string	Technical area name
 *
 * Returns:
 * mixed	Boolean false if the technical area name is not known, or a string with the editor filename. 
 * 			
 */
$_cecRegistry->registerChain("Contenido.Permissions.Group.GetAreaEditFilename", "string");

/* Chain Contenido.Article.RegisterCustomTab
 * This chain registers a custom tab into the main article subnavigation (Overview/Properties/Configuration/Editor/Preview/***)
 *
 * Parameters & order:
 * none
 *
 * Returns:
 * array	Name(s) of the custom tabs handled 
 * 			
 */
$_cecRegistry->registerChain("Contenido.Article.RegisterCustomTab", "string");

/* Chain Contenido.Article.GetCustomTabProperties
 * This chain is called when the properties of a custom tabs need to be aquired. It is used to
 * build the final URL for the editor.
 *
 * Parameters & order:
 * string	Technical name
 *
 * Returns:
 * mixed 	either false if the technical area name is not known, or an array in the following format:
 * 			array("area", "action", "customurlparameters"); 
 * 			
 */
$_cecRegistry->registerChain("Contenido.Article.GetCustomTabProperties", "string");

/* Chain Contenido.Frontend.BaseHrefGeneration
 * This chain is called everytime the BASE HREF Tag is generated
 *
 * Parameters & order:
 * string   	BASE HREF URL from Contenido configuration array
 *
 * Returns:
 * string 	Returns modified BASE HREF URL  
 */
$_cecRegistry->registerChain("Contenido.Frontend.BaseHrefGeneration", "string");

/* Chain Contenido.Upl_edit.Delete
 * This chain function is called after a upl-file has been deleted
 *
 * Parameters & order:
 * int      	$iIdupl  Upl-File-ID
 * string   	$sPath   Directory from File
 * string   	$sFile   Name from File
 *
 * Returns:
 * - none -
 */
$_cecRegistry->registerChain("Contenido.Upl_edit.Delete", "int", "string", "string");

/* Chain Contenido.Upl_edit.Rows
 * This chain is used to process the rows of the upl-details list.
 *
 * Parameters & order:
 * array   	row-list for upl-details
 *
 * Returns:
 * - none -
 *
 * If you want to use own rows, append your own key and title to the
 * array and use the chain Contenido.Upl_edit.RenderRows
 */
$_cecRegistry->registerChain("Contenido.Upl_edit.Rows", "array");

/* Chain Contenido.Upl_edit.RenderRows
 * This chain is used to render a single column for a specific article
 *
 * Parameters & order:
 * int		  File-Upl-Id
 * string		File-Directory
 * string		File-Name
 * string		Row-Key to render
 *
 * Returns:
 * string	String with the rendered contents
 * 
 */
$_cecRegistry->registerChain("Contenido.Upl_edit.RenderRows", "int", "string", "string", "string");

/* Chain Contenido.Upl_edit.SaveRows
 * This chain is called everytime when upl-details is saved
 *
 * Parameters & order: 
 * int      	$iIdupl  Upl-File-ID
 * string   	$sPath   Directory from File
 * string   	$sFile   Name from File
 * 
 * Returns:
 * - none -
 * 
 */
$_cecRegistry->registerChain("Contenido.Upl_edit.SaveRows", "int", "string", "string");

/**
 * Chain Contenido.Action.str_newtree.AfterCall
 * This chain is called while executing code for action "str_newtree", see table con_action
 *
 * Parameters & order:
 * array    Assoziative array with several values as follows
 *          array(
 *              'newcategoryid' => $tmp_newid,
 *              'categoryname'  => $categoryname, 
 *              'categoryalias' => $categoryalias, 
 *              'visible'       => $visible, 
 *              'public'        => $public, 
 *              'idtplcfg'      => $idtplcfg,
 *          );
 *
 * Returns:
 * array    Processed assoziative array, same as parameter above
 */
$_cecRegistry->registerChain("Contenido.Action.str_newtree.AfterCall", "array");

/**
 * Chain Contenido.Action.str_newcat.AfterCall
 * This chain is called while executing code for action "str_newcat", see table con_action
 *
 * Parameters & order:
 * array    Assoziative array with several values as follows
 *          array(
 *              'newcategoryid' => $tmp_newid,
 *              'idcat'         => $idcat, // parent category id
 *              'categoryname'  => $categoryname, 
 *              'categoryalias' => $categoryalias, 
 *              'visible'       => $visible, 
 *              'public'        => $public, 
 *              'idtplcfg'      => $idtplcfg,
 *          );
 *
 * Returns:
 * array    Processed assoziative array, same as parameter above
 */
$_cecRegistry->registerChain("Contenido.Action.str_newcat.AfterCall", "array");

/**
 * Chain Contenido.Action.str_renamecat.AfterCall
 * This chain is called while executing code for action "str_renamecat", see table con_action
 *
 * Parameters & order:
 * array    Assoziative array with several values as follows
 *          array(
 *              'newcategoryid'    => $tmp_newid,
 *              'idcat'            => $idcat,
 *              'lang'             => $lang,
 *              'newcategoryname'  => $newcategoryname, 
 *              'newcategoryalias' => $newcategoryalias
 *          );
 *
 * Returns:
 * array    Processed assoziative array, same as parameter above
 */
$_cecRegistry->registerChain("Contenido.Action.str_renamecat.AfterCall", "array");

/**
 * Chain Contenido.Action.str_moveupcat.AfterCall
 * This chain is called while executing code for action "str_moveupcat", see table con_action
 *
 * Parameters & order:
 * int		$idcat	Category ID
 *
 * Returns:
 * int    Processed category id, same as parameter above
 */
$_cecRegistry->registerChain("Contenido.Action.str_moveupcat.AfterCall", "int");

/**
 * Chain Contenido.Action.str_movedowncat.AfterCall
 * This chain is called while executing code for action "str_movedowncat", see table con_action
 *
 * Parameters & order:
 * int		$idcat	Category ID
 *
 * Returns:
 * int    Processed category id, same as parameter above
 */
$_cecRegistry->registerChain("Contenido.Action.str_movedowncat.AfterCall", "int");

/**
 * Chain Contenido.Action.str_movesubtree.AfterCall
 * This chain is called while executing code for action str_movesubtree, see table con_action
 *
 * Parameters & order:
 * array    Assoziative array with several values as follows
 *          array(
 *              'idcat'        => $idcat,
 *              'parentid_new' => $parentid_new
 *          );
 *
 * Returns:
 * array    Processed assoziative array, same as parameter above
 */
$_cecRegistry->registerChain("Contenido.Action.str_movesubtree.AfterCall", "array");

/**
 * Chain Contenido.Action.con_saveart.AfterCall
 * This chain is called while executing code for action con_saveart, see table con_action
 *
 * Parameters & order:
 * array    Assoziative array with several values as follows
 *          array(
 *          'idcat'        => $idcat, 
 *          'idcatnew'     => $idcatnew, 
 *          'idart'        => $idart, 
 *          'is_start'     => $is_start, 
 *          'idtpl'        => $idtpl, 
 *          'idartlang'    => $idartlang, 
 *          'lang'         => $lang, 
 *          'title'        => $title, 
 *          'summary'      => $summary, 
 *          'artspec'      => $artspec, 
 *          'created'      => $created, 
 *          'lastmodified' => $lastmodified, 
 *          'author'       => $author, 
 *          'online'       => $online, 
 *          'datestart'    => $datestart, 
 *          'dateend'      => $dateend, 
 *          'artsort'      => $artsort
 *          );
 *
 * Returns:
 * array    Processed assoziative array, same as parameter above
 */
$_cecRegistry->registerChain("Contenido.Action.con_saveart.AfterCall", "array");

/**
 * Chain Contenido.Article.conMoveArticles_Loop
 * This chain is called while looping articles which should be moved for the time 
 * management function, see conMoveArticles()
 *
 * Parameters & order:
 * array    Assoziative array of actual recordset like
 *          array(
 *              'idartlang' => 123, 'idart' => 32, 'time_move_cat' => 0, 
 *              'time_target_cat' => 1, 'time_online_move' => 1
 *          );
 *
 * Returns:
 * array    Processed assoziative array of actual dataset, same as parameter above
 */
$_cecRegistry->registerChain("Contenido.Article.conMoveArticles_Loop", "array");

/**
 * Chain Contenido.Article.conCopyArtLang_AfterInsert
 * This chain is called after execution of the insert statement during duplication 
 * of an article, see conCopyArtLang()
 *
 * Parameters & order:
 * array    Assoziative array of actual recordset like
 *          array(
 *              'idartlang' => $idartlang, 'idart' => $idart, 'idlang' => $idlang,
 *              'idtplcfg' => $idtplcfg, 'title' => $pagetitle
 *          );
 *
 * Returns:
 * array    Processed assoziative array of actual dataset, same as parameter above
 */
$_cecRegistry->registerChain("Contenido.Article.conCopyArtLang_AfterInsert", "array");

/**
 * Chain Contenido.Article.conSyncArticle_AfterInsert
 * This chain is called after execution of the insert statement during a article sync, 
 * see conSyncArticle()
 *
 * Parameters & order:
 * array    Assoziative array as follows:
 *          array(
 *              'src_art_lang'  => Recordset of source item from con_art_lang table
 *              'dest_art_lang' => Recordset of inserted destination item from con_art_lang table
 *          );
 *
 * Returns:
 * array    Processed assoziative array of actual dataset, same as parameter above
 */
$_cecRegistry->registerChain("Contenido.Article.conSyncArticle_AfterInsert", "array");

/**
 * Chain Contenido.Category.strSyncCategory_Loop
 * This chain is called while looping categories which should be synchronized, see strSyncCategory()
 *
 * Parameters & order:
 * array    Assoziative array of actual inserted con_cat_lang recordset like
 *          array(
 *              'idcatlang'    => $idartlang, 
 *              'idcat'        => $idcat,
 *              'idlang'       => $idlang,
 *              'idtplcfg'     => $idtplcfg,
 *              'name'         => $name,
 *              'visible'      => $visible, 
 *              'public'       => $public, 
 *              'status'       => $status, 
 *              'author'       => $author, 
 *              'created'      => $created,
 *              'lastmodified' => $lastmodified, 
 *              'urlname'      => $urlname
 *          );
 *
 * Returns:
 * array    Processed assoziative array of actual dataset, same as parameter above
 */
$_cecRegistry->registerChain("Contenido.Category.strSyncCategory_Loop", "array");

/**
 * Chain Contenido.Category.strCopyCategory
 * This chain is called after a old category was copied to new category
 *
 * Parameters & order:
 * array    Assoziative array of several objects
 *          array(
 *              'oldcat'        => $oOldCat,     // Old category object (cApiCategory instance)
 *              'newcat'        => $oNewCat,     // New category object (cApiCategory instance)
 *              'newcatlang'    => $oNewCatLang  // New category language object (cApiCategoryLanguage instance)
 *          );
 *
 * Returns:
 * array    Processed assoziative array of objects, same as parameter above
 */
$_cecRegistry->registerChain("Contenido.Category.strCopyCategory", "cApiCategory", "cApiCategory", "cApiCategoryLanguage");

/**
 * Chain Contenido.Frontend.AfterLoadPlugins
 * This chain is called in front_content.php and provides a possibility to execute
 * userdefined functions after plugins are loaded.
 *
 * Parameters & order:
 * no parameter
 *
 * Returns:
 * bool  Just a boolean return value
 */
$_cecRegistry->registerChain("Contenido.Frontend.AfterLoadPlugins");

/**
 * Chain Contenido.Frontend.HTMLCodeOutput
 * This chain is called in front_content.php after the output of the page was buffered.
 * 
 * Parameters & order:
 * string   Code of page
 *
 * Returns:
 * string 	New code
 */
$_cecRegistry->registerChain("Contenido.Frontend.HTMLCodeOutput", "string");

/**
 * Chain Contenido.Frontend.PreprocessUrlBuilding
 * This chain is called by Contenido_Url->build() method an provides a way to modifiy the parameter
 * which will be passed to the configured Url Builder
 *
 * Parameters & order:
 * array    Assoziative array of parameter beeing achieved as arguments to Contenido_Url->build()
 *          array(
 *              'param'            => Assoziative array containing the parameter, 
 *              'bUseAbsolutePath' => Flag to greate absolute path (incl. scheme and host),
 *              'aConfig'          => Additional Url Builder configuration array,
 *          );
 *
 * Returns:
 * array    Processed assoziative array, same as parameter above
 */
$_cecRegistry->registerChain("Contenido.Frontend.PreprocessUrlBuilding", "array");

/**
 * Chain Contenido.Frontend.PostprocessUrlBuilding
 * This chain is called by Contenido_Url->build() method an provides a opportunity to modifiy a url
 * created by configured Url Builder.
 *
 * Parameters & order:
 * string   Created url by Url Builder
 *
 * Returns:
 * string    Processed url
 */
$_cecRegistry->registerChain("Contenido.Frontend.PostprocessUrlBuilding", "string");

/**
 * Chain Contenido.Content.conGenerateCode
 * This chain is called in function conGenerateCode after code is created.
 * 
 * Parameters & order:
 * string   Code of page
 *
 * Returns:
 * string 	New code
 */
$_cecRegistry->registerChain("Contenido.Content.conGenerateCode", "string");

?>