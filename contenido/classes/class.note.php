<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Contenido Notes system
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0.6
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *
 *   $Id: class.note.php 531 2008-07-02 13:30:54Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes", "class.communications.php");
cInclude("classes", "class.htmlelements.php");

class NoteCollection extends CommunicationCollection
{
	function NoteCollection ()
	{
		parent::CommunicationCollection();
		$this->_setItemClass("NoteItem");	
	}

	/**
     * select: Selects one or more items from the database
     *
     * This function only extends the where statement. See the
     * original function for the parameters.
     *
     * @access public
     * @see ItemCollection
     */		
	function select($where = "", $group_by = "", $order_by = "", $limit = "")
	{
		if ($where == "")
		{
			$where = "comtype='note'";	
		} else {
			$where .= " AND comtype='note'";
		}
		
		return parent::select($where, $group_by, $order_by, $limit);
	}
	
	/**
     * create: Creates a new note item.
     *
     * @param $itemtype	string	Item type (usually the class name)
     * @param $itemid	mixed	Item ID (usually the primary key)
     * @param $idlang	int		Language-ID
     * @param $message	string	Message to store
     *
     * @return object	The new item
     * @access public
     */			
	function create ($itemtype, $itemid, $idlang, $message, $category = "")
	{
		$item = parent::create();
		
		$item->set("subject", "Note Item");
		$item->set("message", $message);
		$item->set("comtype", "note");
		$item->store();
		
		$item->setProperty("note", "itemtype", $itemtype);
		$item->setProperty("note", "itemid", $itemid);	
		$item->setProperty("note", "idlang", $idlang);
		
		if ($category != "")
		{
			$item->setProperty("note", "category", $category);
		}
		
		return $item;
	}
	
}

class NoteItem extends CommunicationItem
{

}

class NoteView extends cHTMLIFrame
{
	function NoteView ($sItemType, $sItemId)
	{
		global $sess, $cfg;
		cHTMLIFrame::cHTMLIFrame();
		$this->setSrc($sess->url("main.php?itemtype=$sItemType&itemid=$sItemId&area=note&frame=2"));
		$this->setBorder(0);
		$this->setStyleDefinition("border", "1px solid ".$cfg['color']['table_border']);
	}
}

class NoteList extends cHTMLDiv
{
	function NoteList ($sItemType, $sItemId)
	{
		cHTMLDiv::cHTMLDiv();
		
		$this->_sItemType = $sItemType;
		$this->_sItemId = $sItemId;
		
		$this->setStyleDefinition("width", "100%");
	}
	
	function setDeleteable ($bDeleteable)
	{
		$this->_bDeleteable = $bDeleteable;	
	}
	
	function toHTML ()
	{
		global $cfg, $lang;
		
		$sItemType = $this->_sItemType;
		$sItemId = $this->_sItemId;
		
		$this->setStyleDefinition("background", $cfg['color']['table_light']);
		
		$oPropertyCollection = new PropertyCollection;
		$oPropertyCollection->select("itemtype = 'idcommunication' AND type = 'note' AND name = 'idlang' AND value = '$lang'");
		
		$items = array();
		
		while ($oProperty = $oPropertyCollection->next())
		{
			$items[] = $oProperty->get("itemid");
		}
		
		$oNoteItems = new NoteCollection;
		
		if (count($items) == 0)
		{
			$items[] = 0;	
		}
		
		$oNoteItems->select("idcommunication IN (".implode(", ", $items).')',"", "created DESC");
		
		$i		= array();
		$dark	= false;
		while ($oNoteItem = $oNoteItems->next())
		{
			if ($oNoteItem->getProperty("note", "itemtype") == $sItemType && $oNoteItem->getProperty("note", "itemid") == $sItemId)
			{
				$j = new NoteListItem($sItemType, $sItemId, $oNoteItem->get("idcommunication"));
				$j->setAuthor($oNoteItem->get("author"));
				$j->setDate($oNoteItem->get("created"));
				$j->setMessage($oNoteItem->get("message"));
				$j->setBackground($dark);
				$j->setDeleteable($this->_bDeleteable);
			
				$dark = !$dark;
			
				$i[] = $j;
			}
		}
		
		$this->setContent($i);
		
		$result = parent::toHTML();	
		
		return ('<table width="100%" border="0" cellspacing="0" cellpadding="0"><tr><td>'.$result.'</td></tr></table>');
	}
}

class NoteListItem extends cHTMLDiv
{
	function NoteListItem ($sItemType, $sItemId, $iDeleteItem)
	{
		cHTMLDiv::cHTMLDiv();
		$this->setStyleDefinition("padding", "2px");
		$this->setBackground();
		$this->setDeleteable(true);
		
		$this->_iDeleteItem = $iDeleteItem;
		$this->_sItemType = $sItemType;
		$this->_sItemId = $sItemId;
		
	}
	
	function setDeleteable ($bDeleteable)
	{
		$this->_bDeleteable = $bDeleteable;	
	}
	
	function setBackground ($dark = false)
	{
		global $cfg;
		
		if ($dark)
		{
			$this->setStyleDefinition("background", $cfg['color']['table_dark']);
		} else {
			$this->setStyleDefinition("background", $cfg['color']['table_light']);
		}	
	}
	
	function setAuthor ($sAuthor)
	{
		if (strlen($sAuthor) == 32)
		{
			$result = getGroupOrUserName($sAuthor);
			
			if ($result !== false)
			{
				$sAuthor = $result;
			}
		}
		
		$this->_sAuthor = $sAuthor;
	}
	
	function setDate ($iDate)
	{
		$dateformat = getEffectiveSetting("backend", "timeformat", "Y-m-d H:i:s");
		
		if (is_string($iDate))
		{
			$iDate = strtotime($iDate);	
		}
		$this->_sDate = date($dateformat, $iDate);
	}
	
	function setMessage ($sMessage)
	{
		$this->_sMessage = $sMessage;	
	}
	
	function render ()
	{
		global $cfg, $sess;
		
		$itemtype = $this->_sItemType;
		$itemid = $this->_sItemId;
		$deleteitem = $this->_iDeleteItem;
		
		$table  = '<table border="0" cellspacing="0" cellpadding="0" width="100%"><tr><td><b>';
		$table .= $this->_sAuthor;
		$table .= '</b></td><td align="right">';
		$table .= $this->_sDate;
		
		if ($this->_bDeleteable == true)
		{
			$oDeleteable = new cHTMLLink;
			$oDeletePic = new cHTMLImage($cfg["path"]["contenido_fullhtml"]."/images/delete.gif");
			$oDeleteable->setContent($oDeletePic);
			$oDeleteable->setLink($sess->url("main.php?frame=2&area=note&itemtype=$itemtype&itemid=$itemid&action=note_delete&deleteitem=$deleteitem"));
			
			$table .= '</td><td style="padding-left: 4px;" width="1">'.$oDeleteable->render();
		}
		$table .= '</td></tr></table>';
		
		$oMessage = new cHTMLDiv;
		$oMessage->setContent($this->_sMessage);
		$oMessage->setStyle("padding-bottom: 8px;");
		
		$this->setContent(array($table, '<hr style="margin-top: 2px; margin-bottom: 2px; border: 0px; border-top: 1px solid' . $cfg['color']['table_border'].';">',$oMessage));
		
		return parent::render();
	}
	
}

class NoteLink extends cHTMLLink
{
	/**
	 * @var string Object type
	 * @access private
	 */	
	var $_sItemType;
	
	/**
	 * @var string Object ID
	 * @access private
	 */		
	var $_sItemID;
	
	/**
	 * @var boolean If true, shows the note history
	 * @access private
	 */		
	var $_bShowHistory;
	
	/**
	 * @var boolean If true, history items can be deleted
	 * @access private
	 */		
	var $_bDeleteHistoryItems;
	
	/**
     * NoteLink: Creates a new note link item.
     *
     * This link is used to show the popup from any position within the system.
     * The link contains the note image.
     *
     * @param $sItemType	string	Item type (usually the class name)
     * @param $sItemId		mixed	Item ID (usually the primary key)
     *
     * @return none
     * @access public
     */		
	function NoteLink ($sItemType, $sItemID)
	{
		parent::cHTMLLink();
		
		$img = new cHTMLImage("images/note.gif");
		$img->setStyle("padding-left: 2px; padding-right: 2px;");
		
		$img->setAlt(i18n("View notes / add note"));
		$this->setLink("#");
		$this->setContent($img->render());
		$this->setAlt(i18n("View notes / add note"));
		
		$this->_sItemType = $sItemType;
		$this->_sItemID = $sItemID;
		$this->_bShowHistory = false;
		$this->_bDeleteHistoryItems = false;
	}

	/**
     * enableHistory: Enables the display of all note items
     *
     * @return none
     * @access public
     */		
	function enableHistory ()
	{
		$this->_bShowHistory = true;
	}

	/**
     * disableHistory: Disables the display of all note items
     *
     * @return none
     * @access public
     */		
	function disableHistory ()
	{
		$this->_bShowHistory = false;	
	}

	/**
     * enableHistoryDelete: Enables the delete function in the history view
     *
     * @return none
     * @access public
     */
	function enableHistoryDelete ()
	{
		$this->_bDeleteHistoryItems = true;
	}

	/**
     * disableHistoryDelete: Disables the delete function in the history view
     *
     * @return none
     * @access public
     */	
	function disableHistoryDelete ()
	{
		$this->_bDeleteHistoryItems = false;	
	}	

	/**
     * render: Renders the resulting link
     *
     * @return none
     * @access public
     */		
	function render ($return = false)
	{
		global $sess;

		$itemtype = $this->_sItemType;
		$itemid = $this->_sItemID;
		
		$this->setEvent("click",  'javascript:window.open('."'".$sess->url("main.php?area=note&frame=1&itemtype=$itemtype&itemid=$itemid")."', 'todo', 'resizable=yes, scrollbars=yes, height=360, width=550');");	
		return parent::render($return);
	}
}
?>