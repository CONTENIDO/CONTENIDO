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
        <div class="progressBarModule">
            <div class="progressBarModuleContent">
                <div id="progressbar"></div>
            </div>
        </div>
        <script type="text/javascript">
        function updateProgressbar(percent)
        {
            width = ((700 / 100) * percent) + 10;
            var elem = document.getElementById("progressbar");
            elem.style.width = "" + width + "px";
        }
        </script>
        <iframe class="progressOutput" style="visibility:{IFRAMEVISIBILITY};" src="{DBUPDATESCRIPT}"></iframe>
    </td>
<tr>
    <td  class="back_next" align="right" valign="bottom">
        <div style="visibility:hidden;" id="next">
        {NEXT}
        </div>
    </td>
</tr>
</table>
