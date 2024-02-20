<!-- navigation_social_media -->

<ul class="social_media">
    {if 0 lt $url.rss|count_characters}
        <li class="rss"><a href="{$url.rss|escape}" target="_blank"></a></li>
    {/if}

    {if 0 lt $url.facebook|count_characters}
        <li class="facebook"><a href="{$url.facebook|escape}" target="_blank"></a></li>
    {/if}

    {if 0 lt $url.googleplus|count_characters}
        <li class="google"><a href="{$url.googleplus|escape}" target="_blank"></a></li>
    {/if}

    {if 0 lt $url.twitter|count_characters}
        <li class="twitter"><a href="{$url.twitter|escape}" target="_blank"></a></li>
    {/if}

    {if 0 lt $url.xing|count_characters}
        <li class="xing"><a href="{$url.xing|escape}" target="_blank"></a></li>
    {/if}
</ul>

<!-- /navigation_social_media -->
