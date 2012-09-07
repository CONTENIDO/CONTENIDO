<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>{TITLE}</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="style/setup.css" rel="stylesheet" type="text/css">

</head>
<body bgcolor="#FFFFFF">
<table width="100%" height="100%" border="0" cellspacing="0" cellpadding="0">
<tr>
<td align="center">
<form name="setupform" method="post" action="index.php">
<input type="hidden" name="step" value="">
<script language="JavaScript">
	isMSIE = (navigator.appName == "Microsoft Internet Explorer");
	isMSIE5 = isMSIE && (navigator.userAgent.indexOf('MSIE 5') != -1);
	isMSIE5_0 = isMSIE && (navigator.userAgent.indexOf('MSIE 5.0') != -1);

	if (navigator.userAgent.indexOf('Opera') != -1)
	{
		isMSIE = false;
	}
	

	function IEAlphaInit (obj)
	{
		if (isMSIE && !obj.IEswapped) { obj.IEswapped = true; obj.src = 'images/spacer.gif'; }
	}
	
	function IEAlphaApply (obj, img)
	{
		if (isMSIE) { obj.style.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src='"+img+"');" } else { obj.src=img; }
	}
	
	function clickHandler (obj)
	{
		if (obj.clicked) { obj.clicked = false; } else { obj.clicked = true }

		if (obj.clicked)
		{ 
			if (obj.mouseIn)
			{
				IEAlphaApply(obj, obj.clickimgover);
			} else {
				IEAlphaApply(obj, obj.clickimgnormal);
			}
		} else {
			if (obj.mouseIn)
			{
				IEAlphaApply(obj, obj.imgover);
			} else {
				IEAlphaApply(obj, obj.imgnormal);
			}
		}				
	}
	
	function mouseoverHandler (obj)
	{
		obj.mouseIn = true;
		
		if (obj.clicked)
		{
			IEAlphaApply(obj, obj.clickimgover);
		} else {
			IEAlphaApply(obj, obj.imgover);
		}
	}
	
	function mouseoutHandler (obj)
	{
		obj.mouseIn = false;
		
		if (obj.clicked)
		{
			IEAlphaApply(obj, obj.clickimgnormal);
		} else {
			IEAlphaApply(obj, obj.imgnormal);
		}
	}
	
	function showHideMessage (obj, div)
	{
		if (!obj.clicked)
		{
			div.className = 'entry_open';
		} else {
			div.className = 'entry_closed';
		}
	}
</script>

<!-- 2008-02-26 rbi Replaced ugly table with a div based solution -->
<div id="setupBox">
	<div id="setupHead">
		<img src="images/logo.gif" alt="Contenido Logo" />
	</div>
	<div id="setupHeadlinePath">
		<div style="float:left;">{HEADER}</div><div style="float:right;padding-right:24px;">{STEPS}</div>
	</div>
	<div id="setupBody">
		{CONTENT}
	</div>
</div>
<div id="setupFootnote">
	&copy; <b>four for business AG</b>
</div>
<!-- /2008-02-26 rbi Replaced ugly table with a div based solution -->
 
</form>
</td>
</tr>
</table>
</body>
</html>
