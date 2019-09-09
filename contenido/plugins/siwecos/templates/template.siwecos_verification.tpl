<br>

<div class="container">
    <div>

        <h3>{i18n('TTL_DOMAIN_CONFIRMATION', 'siwecos')}: {$domain}</h3>

        <div id="wppb-domainverify-wrap" class="wppb-domain-forms">

            <p><strong>{i18n('The verification process', 'siwecos')}</strong></p>
            {i18n('verificationProcessText', 'siwecos')}
            <p><strong>{i18n('MetaTag', 'siwecos')}</strong></p>
            <ul>
                {i18n('metaTagText1', 'siwecos')}
                <li>Meta-Tag:<br> <strong id="metaDomainToken">&lt;meta name="siwecostoken"
                        content="{$domainToken}" /&gt;</strong>
                    <button type="button" onclick="copy2Clipboard('metaDomainToken')">{i18n('BTN_COPY', 'siwecos')}</button>
                </li>
                {i18n('metaTagText2', 'siwecos')}
            </ul>

            <p><strong>{i18n('File', 'siwecos')}</strong></p>
            <ul>
                <li>
                    {i18n('fileText1', 'siwecos')}:
                    <strong id="htmlDomainToken">{$domainToken}.html</strong>
                    <button type="button" onclick="copy2Clipboard('htmlDomainToken')">{i18n('BTN_COPY', 'siwecos')}</button>
                </li>
                <li>
                    {i18n('fileText2', 'siwecos')}:
                    <strong id="codeDomainToken">{$domainToken}</strong>
                    <button type="button" onclick="copy2Clipboard('codeDomainToken')">{i18n('BTN_COPY', 'siwecos')}</button>
                </li>
                {i18n('fileText3', 'siwecos')}
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
