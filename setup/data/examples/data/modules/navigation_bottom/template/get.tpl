<ul class="navigation">
    {foreach item=article from=$articles}
        <li><a href="{$article.url|escape}">{$article.title|escape}</a></li>
    {/foreach}
</ul>
