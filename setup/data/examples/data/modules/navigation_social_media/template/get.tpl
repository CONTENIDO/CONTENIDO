<!-- navigation_social_media -->

<ul class="social_media">
{if 0 lt $url.rss|strlen}
    <li class="rss"><a href="{$url.rss}" target="_blank"></a></li>
{/if}

{if 0 lt $url.facebook|strlen}
    <li class="facebook"><a href="{$url.facebook}" target="_blank"></a></li>
{/if}

{if 0 lt $url.googleplus|strlen}
    <li class="google"><a href="{$url.googleplus}" target="_blank"></a></li>
{/if}

{if 0 lt $url.twitter|strlen}
    <li class="twitter"><a href="{$url.twitter}" target="_blank"></a></li>
{/if}

{if 0 lt $url.youtube|strlen}
    <li class="youtube"><a href="{$url.youtube}" target="_blank"></a></li>
{/if}

{if 0 lt $url.xing|strlen}
    <li class="xing"><a href="{$url.xing}" target="_blank"></a></li>
{/if}
</ul>

<!-- /navigation_social_media -->
