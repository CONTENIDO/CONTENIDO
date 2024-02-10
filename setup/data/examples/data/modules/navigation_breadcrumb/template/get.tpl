<!-- navigation_breadcrumb/template/get.tpl -->

{if $breadcrumb|@count > 0}
    <ul>
        <li>{$label_breadcrumb|escape}:</li>
        {foreach from=$breadcrumb item=category key=i}
            {if $i == 0}
                <li><a href="{$category->getLink()|escape}">{$category->get('name')|escape}</a></li>
            {else}
                <li><a href="{$category->getLink()|escape}">- {$category->get('name')|escape}</a>
                </li>
            {/if}
        {/foreach}
        {if 0 lt $headline|trim|strlen}
            <li>{$headline|escape}</li>
        {/if}
    </ul>
{/if}

<!-- /navigation_breadcrumb/template/get.tpl -->
