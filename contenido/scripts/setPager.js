/*****************************************
* File      :   $RCSfile: setPager.js,v $
* Project   :   Contenido
* Descr     :   Pager folding row JavaScript helpers
* Modified  :   $Date: 2004/03/16 13:41:45 $
*
* © four for business AG, www.4fb.de
*
* $Id: setPager.js,v 1.0 2008/08/02 13:41:45 HerrB Exp $
******************************************/

function fncSetPager(sId, sCurPage)
{
	var oLeftTop = parent.left_top;

	if (oLeftTop.document)
	{
		var oPager = oLeftTop.document.getElementById(sId);
		
		if (oPager)
		{
			oInsert = oPager.firstChild;
			oInsert.innerHTML = sNavigation;
			oLeftTop.newsletter_listoptionsform_curPage = sCurPage;
			oLeftTop.toggle_pager(sId);

			window.clearInterval(oTimer);
		}
	}
}