<ul class="navigation">
{foreach item=article from=$articles}
    <li><a href="{$article.url}">{$article.title}</a></li>
{/foreach}
</ul>
