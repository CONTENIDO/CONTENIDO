<!-- script_cookie_directive/template/get.tpl -->

<div id="cookie_note">

    <h1>{$trans.title|escape}</h1>
    <p>{$trans.infoText|escape}</p>
    <input type="hidden" id="accept" value="{$trans.accept|escape}"/>
    <input type="hidden" id="decline" value="{$trans.decline|escape}"/>
    <input type="hidden" id="page_url_deny" value="{$pageUrlDeny|escape}"/>
    <input type="hidden" id="page_url_accept" value="{$pageUrlAccept|escape}"/>

</div>

<!-- /script_cookie_directive/template/get.tpl -->