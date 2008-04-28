<?php
/*****************************************
* File      :   $RCSfile: class.widgets.treeview.php,v $
* Project   :   Contenido
* Descr     :   Visual representation of a cTree
* Modified  :   $Date: 2006/10/05 23:47:21 $
*
* © four for business AG, www.4fb.de
*
* $Id: class.widgets.treeview.php,v 1.5 2006/10/05 23:47:21 bjoern.behrens Exp $
******************************************/
cInclude("classes", "tree/class.ctree.php");
cInclude ("classes", "class.htmlelements.php");
cInclude("classes", "contenido/class.user.php");

define("TREEVIEW_GRIDLINE_SOLID" , "solid");
define("TREEVIEW_GRIDLINE_DASHED", "dashed");
define("TREEVIEW_GRIDLINE_DOTTED", "dotted");
define("TREEVIEW_GRIDLINE_NONE"  , "none");

define("TREEVIEW_BACKGROUND_NONE", "none");
define("TREEVIEW_BACKGROUND_SHADED", "shaded");

define("TREEVIEW_MOUSEOVER_NONE", "none");
define("TREEVIEW_MOUSEOVER_MARK", "mark");


/**
 * class cWidgetTreeView
 * cWidgetTreeView is a visual representation of a cTree. It supports folding,
 * optional gridline marks and item icons.
 */
class cWidgetTreeView extends cTree
{

	/*** Attributes: ***/

	/**
	 * 
	 * @access private
	 */
	var $_globalActions;
	
	/**
	 * 
	 * @access private
	 */
	var $_setItemActions;
	
	/**
	 * 
	 * @access private
	 */
	var $_unsetItemActions;
	
	/**
	 * 
	 * @access private
	 */
	var $_setAttributeActions;
	
	/**
	 * 
	 * @access private
	 */
	var $_unsetAttributeActions;
	
	
	var $_baseLink;


	function cWidgetTreeView ($uuid, $treename = false)
	{
		global $cfg, $auth;
		
		cTree::cTree();	
		
		$this->_uuid = $uuid;
		//$this->setGridlineMode(TREEVIEW_GRIDLINE_DOTTED);
		
		if ($treename != false)
		{
			$this->setTreeName($treename);	
		}
		
		$this->setBackgroundColors(array($cfg['color']['table_light'], $cfg['color']['table_dark']));
		
		$this->_user = new cApiUser($auth->auth["uid"]);
		
	}
	
	function processParameters ()
	{
		if (($items = $this->_user->getProperty("expandstate", $this->_uuid)) !== false)
		{
			$list = unserialize($items);
			
			foreach ($list as $litem)
			{
				$this->setCollapsed($litem);
			}
		}
		
		if (!empty($this->_name))
		{
			$treename = $this->_name."_";	
		}
		
		if (array_key_exists($treename."collapse",$_GET))
		{
			$this->setCollapsed($_GET[$treename."collapse"]);
		}
		
		if (array_key_exists($treename."expand",$_GET))
		{
			$this->setExpanded($_GET[$treename."expand"]);
		}
		
		$xlist = array(); // Define variable before using it by reference...
		$this->getCollapsedList($xlist);
		$slist = serialize($xlist);
		
		$this->_user->setProperty("expandstate", $this->_uuid, $slist);
	}
	
	/**
	 * applies an action to all items in the tree.
	 *
	 * @param cApiClickableAction action action object
	 * @return void
	 * @access public
	 */
	function applyGlobalAction( $action )
	{
		
	} // end of member function applyGlobalAction

	/**
	 * removes the action from all treeitems.
	 *
	 * @param cApiClickableAction action Removes the action from the global context.
	 * @return void
	 * @access public
	 */
	function removeGlobalAction( $action )
	{
		
	} // end of member function removeGlobalAction

	/**
	 * flushes all actions
	 *
	 * @return void
	 * @access public
	 */
	function flushGlobalActions( )
	{
		
	} // end of member function flushGlobalActions

	/**
	 * sets an action to a specific item.
	 *
	 * @param mixed item cTreeItem-Object or an id of a TreeItem-Object
	 * @param cApiClickableAction action 
	 * @return void
	 * @access public
	 */
	function applyItemAction( $item,  $action )
	{
		
	} // end of member function applyItemAction

	/**
	 * unsets an action from a specific item. Note that you can unset global actions
	 * using this method!
	 *
	 * @param mixed item cTreeItem-Object or an id of a TreeItem-Object
	 * @param cApiClickableAction action Action to unset
	 * @return void
	 * @access public
	 */
	function removeItemAction( $item,  $action )
	{
		
	} // end of member function removeItemAction

	/**
	 * flushes all actions for a specific item
	 *
	 * @param mixed item cTreeItem-Object or an id of a TreeItem-Object
	 * @return void
	 * @access public
	 */
	function flushItemActions( $item )
	{
		
	} // end of member function flushItemActions

	/**
	 * Applies an action to all items with a certain attribute set.
	 *
	 * @param array attributes Values which need to match. The array key is the attribute name. Multiple array
entries are connected with "AND". 
	 * @param cApiClickableAction action Action to apply
	 * @return void
	 * @access public
	 */
	function applyActionByItemAttribute( $attributes,  $action )
	{
		
	} // end of member function applyActionByItemAttribute

	/**
	 * Removes an action from all items with a certain attribute set.
	 *
	 * @param array attributes Values which need to match. The array key is the attribute name. Multiple array
entries are connected with "AND". 
	 * @param cApiClickableAction action Action to remove
	 * @return void
	 * @access public
	 */
	function removeActionByItemAttribute( $attributes,  $action )
	{
		
	} // end of member function removeActionByItemAttribute

	/**
	 * Removes all actions for items with specific attributes
	 *
	 * @param array attributes Values which need to match. The array key is the attribute name. Multiple array
entries are connected with "AND". 
	 * @return void
	 * @access public
	 */
	function flushActionByItemAttribute( $attributes )
	{
		
	} // end of member function flushActionByItemAttribute

	/**
	 * 
	 *
	 * @param int mode Sets the gridline mode to one of the following values:
	 * TREEVIEW_GRIDLINE_SOLID
	 * TREEVIEW_GRIDLINE_DASHED
	 * TREEVIEW_GRIDLINE_DOTTED
	 * TREEVIEW_GRIDLINE_NONE
	 *
	 * @return void
	 * @access public
	 */
	function setGridlineMode( $mode )
	{
		$this->_gridlineMode = $mode;
	} // end of member function setGridlineMode

	function setBackgroundMode ($mode)
	{
		$this->_backgroundMode = $mode;	
	}
	
	function setMouseoverMode ($mode)
	{
		$this->_mouseoverMode = $mode;	
	}
	
	function setBackgroundColors ($colors)
	{
		$this->_backgroundColors = $colors;
	}	

	/**
	 * 
	 *
	 * @return void
	 * @access public
	 */
	function render ($with_root = true)
	{
        $objects = $this->flatTraverse(0);

		if ($with_root == false)
		{
        	unset($objects[0]);
		}

		$img = new cHTMLImage;
		$r_table = new cHTMLTable;
		$r_row = new cHTMLTableRow;
		$r_leftcell = new cHTMLTableData;
		$r_rightcell = new cHTMLTableData;
		$r_actioncell = new cHTMLTableData;
        
        $img_spacer = new cHTMLImage;
        $img_spacer->updateAttributes(array('width' => '16', 'height' => '20'));
        $img_spacer->setAlt("");
        $img_spacer->setSrc("images/spacer.gif");
        $img_spacer->advanceID();	
		
		$r_table->setCellPadding(0);
		$r_table->setCellSpacing(0);
		$r_table->setWidth("100%");
		$r_rightcell->setStyleDefinition("padding-left", "3px");
		$r_rightcell->setVerticalAlignment("middle");
		$r_leftcell->setVerticalAlignment("middle");
		$r_leftcell->updateAttributes(array("nowrap" => "nowrap"));
		$r_rightcell->updateAttributes(array("nowrap" => "nowrap"));
		$r_actioncell->updateAttributes(array("nowrap" => "nowrap"));
		$r_leftcell->setWidth("1%");
		$r_rightcell->setWidth("100%");
		$r_actioncell->setAlignment("right");
		$r_actioncell->setWidth("1%");
		
		if (!is_object($this->_baseLink))
		{
			$this->_baseLink = new cHTMLLink;	
		}
		
		$lastitem = array();
        foreach ($objects as $key => $object)
        {
        	$img->setAlt("");
        	$r_table->advanceID();
        	$r_rightcell->advanceID();
        	$r_leftcell->advanceID();
        	$r_row->advanceID();
        	$r_actioncell->advanceID();
        	
        	for ($level = 1; $level < $object->_level + 1; $level++)
        	{
        		if ($object->_level == $level)
        		{
                    if ($object->_next === false)
                    {
                    	if (count($object->_subitems) > 0)
						{
							$link = $this->_setExpandCollapseLink($this->_baseLink, $object);
							$link->advanceID();
							$img->setSrc($this->_getExpandCollapseIcon($object));
				        	$img->advanceID();							
							$link->setContent($img);
							$out .= $link->render();
						} else {
							if ($level == 1 && $with_root == false)
							{
								$out .= $img_spacer->render();
							} else {
								$img->setSrc($this->_buildImagePath("grid_linedownrightend.gif"));
                                $img->advanceID();							
                                $out .= $img->render();
							}
						}
                    	$lastitem[$level] = true;
                    } else {
                    	if (count($object->_subitems) > 0)
						{
							$link = $this->_setExpandCollapseLink($this->_baseLink, $object);
							$link->advanceID();							
							$img->setSrc($this->_getExpandCollapseIcon($object));
				        	$img->advanceID();														
							$link->setContent($img);
							$out .= $link->render();							
						} else {
							if ($level == 1 && $with_root == false)
							{							
								$out .= $img_spacer->render();
							} else {
								$img->setSrc($this->_buildImagePath("grid_linedownright.gif"));
                                $out .= $img->render();
							}
						}                    	
                    	
                    	$lastitem[$level] = false;
                    }
        		} else {
        			if ($lastitem[$level] == true)
        			{
        				$out .= $img_spacer->render();
        			} else {
						if ($level == 1 && $with_root == false)
						{							
							$out .= $img_spacer->render();
						} else {        				
        					$img->setSrc($this->_buildImagePath("/grid_linedown.gif"));
                            $img->advanceID();													
                            $out .= $img->render();	
						}
        			}	
        		}
        	}
        	
			/* Fetch Render icon from the meta object */
			if (is_object($object->payload))
			{
				/* Fetch payload object */
				$meta = $object->payload->getMetaObject();
				
				if (is_object($meta))
				{
					$icon = $meta->getIcon();
					$actions = $meta->getActions();
					
					$r_actioncell->setContent($actions);
					
					$img->setAlt($meta->getDescription());
					$img->advanceID();
					
        			/* Check if we've got an edit link */
		        	if (count($meta->_editAction) > 0)
		        	{
		        		$meta->defineActions();
		        		
		        		$edit = $meta->getAction($meta->_editAction);
		        		
		        		$edit->setIcon($icon);
		        		
		        		$renderedIcon = $edit->render();
		        		
		        		$edit->_link->setContent($object->_name);
		        		$edit->_link->advanceID();
		        		$renderedName = $edit->_link->render();
		        	} else {
		        		$img->setSrc($icon);
		        		$renderedIcon = $img->render();
		        		$renderedName = $object->_name;
		        	}
				}
			} else {
				if (isset($object->_attributes["icon"]))
				{
	        		$img->setSrc($object->_attributes["icon"]);
	        		$renderedIcon = $img->render();
	        		$renderedName = $object->_name;								
				} else {
					/* Fetch tree icon */
					if ($object->_id == 0)
					{
						$icon = $object->_treeIcon;
		        		$img->setSrc($icon);
		        		$renderedIcon = $img->render();
		        		$renderedName = $object->_name;					
					} else {
						$icon = $object->_treeIcon;
		        		$img->setSrc($icon);
		        		$renderedIcon = $img->render();
		        		$renderedName = $object->_name;			
					}		
				}	
			}

			if ($this->_backgroundMode == TREEVIEW_BACKGROUND_SHADED)
			{
				if (current($this->_backgroundColors) === false)
				{
					reset($this->_backgroundColors);	
				}
				
				$color = current($this->_backgroundColors);
				next($this->_backgroundColors);
				
				$r_rightcell->setBackgroundColor($color);
				$r_leftcell->setBackgroundColor($color);
				$r_actioncell->setBackgroundColor($color);
			}
			
			$img->setSrc($icon);
			$r_leftcell->setContent($out . $renderedIcon);
			$r_rightcell->setContent($renderedName);
			
			$r_row->setContent(array($r_leftcell, $r_rightcell, $r_actioncell));
			
			$r_table->setContent($r_row);

			$result .= $r_table->render();
			
			unset($out);
        }
		
		return ('<table cellspacing="0" cellpadding="0" width="100%" border="0"><tr><td>'.$result.'</td></tr></table>');
	} // end of member function render

	function _getExpandCollapseIcon ($object)
	{
		if ($object->_collapsed == true)
		{
			return ($this->_buildImagePath("grid_expand.gif"));	
		} else {
			return ($this->_buildImagePath("grid_collapse.gif"));
		}
	}
	
	function _setExpandCollapseLink ($link, $object)
	{
		if (!empty($this->_name))
		{
			$treename = $this->_name."_";	
		}
		
		if ($object->_collapsed == true)
		{
			$link->setCustom($treename."expand", $object->_id);
		} else {
			$link->setCustom($treename."collapse", $object->_id);
		}
		
		return ($link);
	}
	
	function _buildImagePath($image)
	{
		return ("./images/".$this->_gridlineMode."/".$image);	
	}
	
	function setBaseLink ($link)
	{
		$this->_baseLink = $link;	
	}


} // end of cWidgetTreeView
?>
