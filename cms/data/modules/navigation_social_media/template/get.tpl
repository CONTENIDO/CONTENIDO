<!-- navigation_social_media -->

<ul>
{if 0 lt $url.rss|strlen}
    <li><a href="{$url.rss}" target="_blank">rss</a></li>
{/if}

{if 0 lt $url.facebook|strlen}
    <li><a href="{$url.facebook}" target="_blank">facebook</a></li>
{/if}

{if 0 lt $url.googleplus|strlen}
    <li><a href="{$url.googleplus}" target="_blank">googleplus</a></li>
{/if}

{if 0 lt $url.twitter|strlen}
    <li><a href="{$url.twitter}" target="_blank">twitter</a></li>
{/if}

{if 0 lt $url.youtube|strlen}
    <li><a href="{$url.youtube}" target="_blank">youtube</a></li>
{/if}

{if 0 lt $url.xing|strlen}
    <li><a href="{$url.xing}" target="_blank">xing</a></li>
{/if}
</ul>

<!-- /navigation_social_media -->
