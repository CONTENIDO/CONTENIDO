<!-- navigation_breadcrumb/template/get.tpl -->

{if $breadcrumb|@count > 0}
<ul>
    <li>{$label_breadcrumb}:</li>
    {foreach from=$breadcrumb item=category key=i}
        {if $i == 0}
        <li><a href="{$category->getLink()}">{$category->get('name')}</a></li>
        {else}
        <li><a href="{$category->getLink()}">- {$category->get('name')}</a></li>
        {/if}
    {/foreach}
    {if 0 lt $headline|trim|strlen}
        <li>{$headline}</li>
    {/if}
</ul>
{/if}

<!-- /navigation_breadcrumb/template/get.tpl -->
