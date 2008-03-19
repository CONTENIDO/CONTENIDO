<table cellspacing="0" cellpadding="0" border="0" class="setupBodyOuterTable">
<tr>
	<td valign="top" colspan="2">
		<h1>{TITLE}</h1>
		<div id="installing">{DESCRIPTION}</div>
		<div id="installingdone" style="visibility:hidden;">{DONEINSTALLATION}</div>
	</td>
</tr>
<tr>
<td valign="top">
<div style="border: 1px solid #999999; width: 700px; overflow: hidden;background-color:#fff;">
<div style="border: 1px solid white; width: 698px; overflow: hidden;background-color:#fff;">
<div id="progressbar" style="width: 0; padding: 0px; background-image: url(images/controls/pbend.gif); background-repeat: no-repeat; background-position: right; background-color: #BFCF00; height: 16px;"></div></div>
</div>
<script language="JavaScript">
function updateProgressbar (percent)
{ 
	width = ((700 / 100) * percent) + 10;
	
	document.getElementById("progressbar").style.width = width;
}
</script>
<iframe style="width: 500px; height: 100px; visibility:hidden;" src="{DBUPDATESCRIPT}"></iframe>
</td>
<tr>
<td align="right" valign="bottom">
<div style="visibility:hidden;" id="next">
{NEXT}
</div>
</td>
</tr>
</table>
