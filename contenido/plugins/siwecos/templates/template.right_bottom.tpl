<form id="siwecos-form" action="{$formAction}" method="post">
    <table style="width: auto;" class="generic" cellspacing="0" cellpadding="2">
        <tbody>
        <tr id="m7">
            <th class="align_top" id="m8" colspan="2">{i18n("LBL_CONFIGURATION", 'siwecos')}</th>
        </tr>
        <tr class="2">
            <td class="no_wrap align_top" width="1">
                <nobr>{i18n("LBL_DOMAIN", 'siwecos')}</nobr>
                *
            </td>
            <td class="no_wrap align_top text_left">
                <input type="text" id="domain" name="domain" value="{$domain}">
            </td>
        </tr>
        <tr class="2">
            <td class="no_wrap align_top" width="1">
                <nobr>{i18n("LBL_EMAIL_ADDRESS", 'siwecos')}</nobr>
                *
            </td>
            <td class="no_wrap align_top text_left">
                <input type="email" id="email" name="email" value="{$email}">
            </td>
        </tr>
        <tr class="2">
            <td class="no_wrap align_top" width="1">
                <nobr>{i18n("LBL_PASSWORD", 'siwecos')}</nobr>
                *
            </td>
            <td class="no_wrap align_top text_left">
                <input type="password" id="password" name="password" value="{$password}" autocomplete="off" readonly="readonly">
                <script type="text/javascript">
                    (function(Con, $) {
                        $(function() {
                            // Remove readonly attribute on focus
                            $("#password").on("focus", function() {
                                $(this).prop("readonly", false);
                            });
                        });
                    })(Con, Con.$);
                </script>
            </td>
        </tr>
        <tr class="2">
            <td class="no_wrap align_top" width="1">
                <nobr>{i18n("LBL_DANGER_LEVEL", 'siwecos')}</nobr>
            </td>
            <td class="no_wrap align_top text_left">
                <input type="dangerLevel" id="dangerLevel" name="dangerLevel" value="{$dangerLevel}">
            </td>
        </tr>
        <tr class="2">
            <td class="no_wrap align_top" width="1">
                <nobr>{i18n("LBL_USER_TOKEN", 'siwecos')}</nobr>
            </td>
            <td class="no_wrap align_top text_left">{$userToken}</td>
        </tr>
        <tr class="2">
            <td class="no_wrap align_top" width="1">
                <nobr>{i18n("LBL_DOMAIN_TOKEN", 'siwecos')}</nobr>
            </td>
            <td class="no_wrap align_top text_left">{$domainToken}</td>
        </tr>
        <tr class="2">
            <td class="no_wrap align_top" width="1">
                <nobr>{i18n("LBL_AUTHOR", 'siwecos')}</nobr>
            </td>
            <td class="no_wrap align_top text_left">{$author}</td>
        </tr>
        <tr class="2">
            <td class="no_wrap align_top" width="1">
                <nobr>{i18n("LBL_CREATION_DATE", 'siwecos')}</nobr>
            </td>
            <td class="no_wrap align_top text_left">{$created}</td>
        </tr>

        <tr class="2">
            <td class="no_wrap align_top">&nbsp;</td>
            <td class="no_wrap align_top text_right">
                <input type="image" id="siwecos-new-form"
                       src="images/but_ok.gif" alt="{i18n("BTN_SAVE", 'siwecos')}"
                       title="{i18n("BTN_SAVE", 'siwecos')}">
            </td>
        </tr>
        </tbody>
    </table>
</form>

{$report}
