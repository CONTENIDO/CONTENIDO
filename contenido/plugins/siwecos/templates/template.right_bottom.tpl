<form id="siwecos-form" action="{$ACTION}" method="post">
    <table style="width: auto;" id="" class="generic" cellspacing="0" cellpadding="2">
        <tbody>
        <tr id="m7">
            <th id="m8" colspan="2" valign="top">{$Konfiguration}</th>
        </tr>
        <tr class="2">
            <td nowrap="nowrap" valign="top" style="white-space: nowrap;" width="1">
                <nobr>{$Domain}</nobr>
                *
            </td>
            <td nowrap="nowrap" valign="top" align="left">
                <input type="text" id="domain" name="domain" value="{$domain}">
            </td>
        </tr>
        <tr class="2">
            <td nowrap="nowrap" valign="top" style="white-space: nowrap;" width="1">
                <nobr>{$Email}</nobr>
                *
            </td>
            <td nowrap="nowrap" valign="top" align="left">
                <input type="email" id="email" name="email" value="{$email}">
            </td>
        </tr>
        <tr class="2">
            <td nowrap="nowrap" valign="top" style="white-space: nowrap;" width="1">
                <nobr>{$Password}</nobr>
                *
            </td>
            <td nowrap="nowrap" valign="top" align="left">
                <input type="password" id="password" name="password" value="{$password}">
            </td>
        </tr>
        <tr class="2">
            <td nowrap="nowrap" valign="top" style="white-space: nowrap;" width="1">
                <nobr>{$Danger_Level}</nobr>
            </td>
            <td nowrap="nowrap" valign="top" align="left">
                <input type="dangerLevel" id="dangerLevel" name="dangerLevel" value="{$dangerLevel}">
            </td>
        </tr>
        <tr class="2">
            <td nowrap="nowrap" valign="top" style="white-space: nowrap;" width="1">
                <nobr>{$UserToken}</nobr>
            </td>
            <td nowrap="nowrap" valign="top" align="left">{$userToken}</td>
        </tr>
        <tr class="2">
            <td nowrap="nowrap" valign="top" style="white-space: nowrap;" width="1">
                <nobr>{$DomainToken}</nobr>
            </td>
            <td nowrap="nowrap" valign="top" align="left">{$domainToken}</td>
        </tr>
        <tr class="2">
            <td nowrap="nowrap" valign="top" style="white-space: nowrap;" width="1">
                <nobr>{$Author}</nobr>
            </td>
            <td nowrap="nowrap" valign="top" align="left">{$author}</td>
        </tr>
        <tr class="2">
            <td nowrap="nowrap" valign="top" style="white-space: nowrap;" width="1">
                <nobr>{$Created}</nobr>
            </td>
            <td nowrap="nowrap" valign="top" align="left">{$created}</td>
        </tr>

        <tr class="2">
            <td nowrap="nowrap" valign="top">&nbsp;</td>
            <td nowrap="nowrap" valign="top" style="white-space: nowrap;" align="right">
                <input type="image" id="siwecos-new-form" src="images/but_ok.gif" alt="{$saveBtnTxt}"
                       title="{$saveBtnTxt}">
            </td>
        </tr>
        </tbody>
    </table>
</form>
{$report}