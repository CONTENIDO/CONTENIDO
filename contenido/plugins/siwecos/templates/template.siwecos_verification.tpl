<br>
<div class="container">
    <div><h3>{$ConfirmDomain}: {$domain}</h3>
        <div id="wppb-domainverify-wrap" class="wppb-domain-forms"><p><strong>{$verificationProcess}</strong></p>
            {$verificationProcessText}
            <p><strong>{$MetaTag}</strong></p>
            <ul>
                {$metaTagText1}
                <li>Meta-Tag:<br> <strong id="metaDomainToken">&lt;meta name="siwecostoken"
                        content="{$domainToken}" /&gt;</strong>
                    <button type="button" onclick="copy2Clipboard('metaDomainToken')">{$copy}</button>
                </li>
                {$metaTagText2}
            </ul>
            <p><strong>{$file}</strong></p>
            <ul>
                <li>
                    {$fileText1}:
                    <strong id="htmlDomainToken">{$domainToken}.html</strong>
                    <button type="button" onclick="copy2Clipboard('htmlDomainToken')">{$copy}</button>
                </li>
                <li>
                    {$fileText2}:
                    <strong id="codeDomainToken">{$domainToken}</strong>
                    <button type="button" onclick="copy2Clipboard('codeDomainToken')">{$copy}</button>
                </li>
                {$fileText3}
            </ul>
            <a href="{$verificationHref}" class="btn btn-primary">{$verify}</a></div>
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
                alert("{$copy2}");
            }
        } catch (err) {
            alert("{$copy2}")
        }
    }
</script>
