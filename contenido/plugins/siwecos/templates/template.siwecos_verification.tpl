<br>

<div class="container">
    <div>

        <h2>{i18n('TTL_DOMAIN_CONFIRMATION', 'siwecos')}: {$domain}</h2>

        <div id="wppb-domainverify-wrap" class="wppb-domain-forms">

            <h3>{i18n('TTL_VERIFICATION_PROCESS', 'siwecos')}</h3>
            {i18n('MSG_VERIFICATION_PROCESS', 'siwecos')}

            <h3>{i18n('TTL_META_TAG', 'siwecos')}</h3>
            <ul>
                {i18n('MSG_META_TAG_1', 'siwecos')}
                <li>
                    Meta-Tag:<br>
                    <strong id="metaDomainToken">&lt;meta name="siwecostoken" content="{$domainToken}" /&gt;</strong>
                    <button type="button" onclick="copy2Clipboard('metaDomainToken')">{i18n('BTN_COPY', 'siwecos')}</button>
                </li>
                {i18n('MSG_META_TAG_2', 'siwecos')}
            </ul>

            <h3>{i18n('TTL_FILE', 'siwecos')}</h3>
            <ul>
                <li>
                    {i18n('MSG_FILE_1', 'siwecos')}:
                    <strong id="htmlDomainToken">{$domainToken}.html</strong>
                    <button type="button" onclick="copy2Clipboard('htmlDomainToken')">{i18n('BTN_COPY', 'siwecos')}</button>
                </li>
                <li>
                    {i18n('MSG_FILE_2', 'siwecos')}:
                    <strong id="codeDomainToken">{$domainToken}</strong>
                    <button type="button" onclick="copy2Clipboard('codeDomainToken')">{i18n('BTN_COPY', 'siwecos')}</button>
                </li>
                {i18n('MSG_FILE_3', 'siwecos')}
            </ul>

            <a href="{$verificationHref}" class="btn btn-primary">{i18n('BTN_VERIFY', 'siwecos')}</a>

        </div>

    </div>
</div>

<script type="text/javascript">
    function copy2Clipboard(id) {
        var obj = document.getElementById(id);
        var range = document.createRange();
        range.selectNodeContents(obj);
        var selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(range);
        try {
            if (document.execCommand("Copy", "false", null)) {
                //alert("OK！");
            } else {
                alert("{i18n('MSG_MANUAL_COPY', 'siwecos')}");
            }
        } catch (err) {
            alert("{i18n('MSG_MANUAL_COPY', 'siwecos')}")
        }
    }
</script>
