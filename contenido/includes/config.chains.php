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
 * @version    1.0.0
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
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

global $_cecRegistry;

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
$_cecRegistry->registerChain("Contenido.Upl_edit.SaveRows", "string");

?>