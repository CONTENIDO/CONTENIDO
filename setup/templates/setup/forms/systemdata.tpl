<table cellspacing="0" cellpadding="0" border="0" class="setupBodyOuterTable setupBodyOuterTableSystemData">
    <tr class="row-1">
        <td colspan="2">
            <h1>{TITLE}</h1>
            {DESCRIPTION}
        </td>
    </tr>
    <tr class="row-2">
        <td class="column-1">
            <table border="0">
                <tr>
                    <td class="first"><div class="description">{LABEL_DBHOST}</div></td>
                    <td class="last">{INPUT_DBHOST}</td>
                </tr>
                <tr>
                    <td class="first first2"><div class="description">{LABEL_DBNAME}</div></td>
                    <td class="last">{INPUT_DBNAME}</td>
                </tr>
                <tr>
                    <td class="first"><div class="description">{LABEL_DBUSERNAME}</div></td>
                    <td class="last">{INPUT_DBUSERNAME}</td>
                </tr>
                <tr>
                    <td class="first"><div class="description">{LABEL_DBPASSWORD}</div></td>
                    <td class="last">{INPUT_DBPASSWORD}</td>
                </tr>
                <tr>
                    <td class="first"><div class="description">{LABEL_DBPREFIX}</div></td>
                    <td class="last">{INPUT_DBPREFIX}</td>
                </tr>
                <tr>
                	<td><a href="javascript://" onclick="toggleSettings()">
                		<img class="advancedSettingsImage" style="margin-bottom: -.25em;" src="images/controls/arrow_closed.png">
                		Advanced Settings
                	</td>
                	<td>&nbsp;</td>
                </tr>
                <tr class="advancedSetting" style="visibility: hidden;">
                    <td class="first"><div class="description">{LABEL_DBCHARSET}</div></td>
                    <td class="last">{INPUT_DBCHARSET}</td>
                </tr>
                <tr class="advancedSetting" style="visibility: hidden;">
                    <td class="first"><div class="description">{LABEL_DBCOLLATION}</div></td>
                    <td class="last">{INPUT_DBCOLLATION}</td>
                </tr>
            </table>
        </td>
        <td class="column-2">
            <div>
                {BACK}{NEXT}
            </div>
        </td>
    </tr>
</table>