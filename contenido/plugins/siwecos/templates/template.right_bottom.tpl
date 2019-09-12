<form id="siwecos-form" action="{$formAction}" method="post">
    <table style="width: auto;" id="" class="generic" cellspacing="0" cellpadding="2">
        <tbody>
        <tr id="m7">
            <th id="m8" colspan="2" valign="top">{i18n("LBL_CONFIGURATION", 'siwecos')}</th>
        </tr>
        <tr class="2">
            <td nowrap="nowrap" valign="top" style="white-space: nowrap;" width="1">
                <nobr>{i18n("LBL_DOMAIN", 'siwecos')}</nobr>
                *
            </td>
            <td nowrap="nowrap" valign="top" align="left">
                <input type="text" id="domain" name="domain" value="{$domain}">
            </td>
        </tr>
        <tr class="2">
            <td nowrap="nowrap" valign="top" style="white-space: nowrap;" width="1">
                <nobr>{i18n("LBL_EMAIL_ADDRESS", 'siwecos')}</nobr>
                *
            </td>
            <td nowrap="nowrap" valign="top" align="left">
                <input type="email" id="email" name="email" value="{$email}">
            </td>
        </tr>
        <tr class="2">
            <td nowrap="nowrap" valign="top" style="white-space: nowrap;" width="1">
                <nobr>{i18n("LBL_PASSWORD", 'siwecos')}</nobr>
                *
            </td>
            <td nowrap="nowrap" valign="top" align="left">
                <input type="password" id="password" name="password" value="{$password}">
            </td>
        </tr>
        <tr class="2">
            <td nowrap="nowrap" valign="top" style="white-space: nowrap;" width="1">
                <nobr>{i18n("LBL_DANGER_LEVEL", 'siwecos')}</nobr>
            </td>
            <td nowrap="nowrap" valign="top" align="left">
                <input type="dangerLevel" id="dangerLevel" name="dangerLevel" value="{$dangerLevel}">
            </td>
        </tr>
        <tr class="2">
            <td nowrap="nowrap" valign="top" style="white-space: nowrap;" width="1">
                <nobr>{i18n("LBL_USER_TOKEN", 'siwecos')}</nobr>
            </td>
            <td nowrap="nowrap" valign="top" align="left">{$userToken}</td>
        </tr>
        <tr class="2">
            <td nowrap="nowrap" valign="top" style="white-space: nowrap;" width="1">
                <nobr>{i18n("LBL_DOMAIN_TOKEN", 'siwecos')}</nobr>
            </td>
            <td nowrap="nowrap" valign="top" align="left">{$domainToken}</td>
        </tr>
        <tr class="2">
            <td nowrap="nowrap" valign="top" style="white-space: nowrap;" width="1">
                <nobr>{i18n("LBL_AUTHOR", 'siwecos')}</nobr>
            </td>
            <td nowrap="nowrap" valign="top" align="left">{$author}</td>
        </tr>
        <tr class="2">
            <td nowrap="nowrap" valign="top" style="white-space: nowrap;" width="1">
                <nobr>{i18n("LBL_CREATION_DATE", 'siwecos')}</nobr>
            </td>
            <td nowrap="nowrap" valign="top" align="left">{$created}</td>
        </tr>

        <tr class="2">
            <td nowrap="nowrap" valign="top">&nbsp;</td>
            <td nowrap="nowrap" valign="top" style="white-space: nowrap;" align="right">
                <input type="image" id="siwecos-new-form"
                       src="images/but_ok.gif" alt="{i18n("BTN_SAVE", 'siwecos')}"
                       title="{i18n("BTN_SAVE", 'siwecos')}">
            </td>
        </tr>
        </tbody>
    </table>
</form>

{$report}
