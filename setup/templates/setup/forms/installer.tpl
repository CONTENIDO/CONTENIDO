<table cellspacing="0" cellpadding="0" border="0" class="setupBodyOuterTable setupBodyOuterTableInstaller">
    <tr class="row-1">
        <td colspan="2">
            <h1>{TITLE}</h1>
            <div id="installing">{DESCRIPTION}</div>
            <div id="installingdone" style="visibility:hidden;">{DONEINSTALLATION}</div>
        </td>
    </tr>
    <tr class="row-2">
        <td class="column-1">
            <div class="progressBarModule">
                <div class="progressBarModuleContent">
                    <div id="progressbar"></div>
                </div>
            </div>
            <script type="text/javascript">
            var progressElem = null;
            function updateProgressbar(percent)
            {
                var width = Math.round(((730 / 100) * percent));
                if (!progressElem) {
                    progressElem = document.getElementById("progressbar");
                }
                progressElem.style.width = "" + width + "px";
            }
            </script>
            <iframe class="progressOutput" style="visibility:{IFRAMEVISIBILITY};" src="{DBUPDATESCRIPT}"></iframe>
        </td>
    </tr>
    <tr>
        <td class="column-2">
            <div style="visibility:hidden;" id="next">
                {NEXT}
            </div>
        </td>
    </tr>
</table>
