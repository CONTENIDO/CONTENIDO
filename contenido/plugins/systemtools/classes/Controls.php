<?php
/**
 *
 * @file: Controls.php
 * @created: 02.05.2004
 * @modified: 05.31.2005
 * 
 * @version	1.1
 * @author Willi Man
 * @copyright four for business AG <www.4fb.de>
 */


class Controls 
{
	/**
     * Constructor
     */
	function Controls()
	{
	}
	
	/**
     * @access public
     * @return string
     */ 
	function getDeleteLink($sMessage, $aAction, $sTitle, $sText, $sArea, $sFrame, $sSession, $sImage = "images/delete.gif")
	{
		$aActionKey = array_keys($aAction);
		$aURLParams = array();
		for ($i = 0; $i < count($aAction); $i++)
		{
			$aURLParams[] = $aActionKey[$i].'='.$aAction[$aActionKey[$i]];
		}
		
		$sURLParams = implode("&", $aURLParams);
		
		$sLink = 'main.php?area='.$sArea.'&frame='.$sFrame.'&'.$sURLParams.'&contenido='.$sSession;
		$aLinkElement = '<a onclick="return confirm(\''.$sMessage.'\');" href="'.$sLink.'" title="'.$sTitle.'">'.$sText.'<img style="margin-left: 5px;" border="0" src="'.$sImage.'"></a>';	
			
		return $aLinkElement;
	}
	
	/**
     * Get HTML link to overview
     * @param string sTitle
     * @param string sArea
     * @param string sFrame
     * @param string sSession
     * @param string sImage
     * 
     * @access public
     * @return string 
     */	
	function getGoBackLink($sTitle, $sText, $sArea, $sFrame, $sSession, $sImage = "images/pfeil_links.gif")
	{
		$sUrl = 'main.php?area='.$sArea.'&frame='.$sFrame.'&contenido='.$sSession;
		$sLink = '<a href="'.$sUrl.'" title="'.$sTitle.'"><img border="0" src="'.$sImage.'">'.$sText.'</a>';	
				
		return $sLink;
	}

}

?>