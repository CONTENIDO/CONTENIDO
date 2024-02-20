<div id="fb-root"></div>
<script>
    (function (d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) return;
        js = d.createElement(s);
        js.id = id;
        js.src = "//connect.facebook.net/de_DE/sdk.js#xfbml=1&version=v2.3";
        fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));
</script>

<div class="fb-like" data-href="{$URL}" data-width="{$WIDTH|escape}" data-layout="{$LAYOUT|escape}"
     data-action="like" data-show-faces="{$SHOW_FACES|escape}" data-share="true"></div>