<a class="twitter-timeline"
   data-screen-name="{$twitterName|escape}"
   data-show-replies="{$twitterShowReplies}"
   data-theme="{$twitterTheme|escape}"
   data-link-color="{$twitterLinkColor|escape}"
   width="{$twitterWidth|escape}"
   height="{$twitterHeight|escape}"
   data-border-color="{$twitterBorderColor|escape}"
   data-related="{$twitterRelated|escape}"
   href="https://twitter.com/{$twitterName|escape}"
   data-widget-id="347351638245253120">
    Tweets by @{$twitterName|escape}
</a>
{literal}
    <script>!function (d, s, id) {
            var js, fjs = d.getElementsByTagName(s)[0],
                p = /^http:/.test(d.location) ? 'http' : 'https';
            if (!d.getElementById(id)) {
                js = d.createElement(s);
                js.id = id;
                js.src = p + "://platform.twitter.com/widgets.js";
                fjs.parentNode.insertBefore(js, fjs);
            }
        }(document, "script", "twitter-wjs");</script>
{/literal}
