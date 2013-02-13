<!-- navigation_breadcrumb/template/get.tpl -->

<ul>
    <li>{$label_breadcrumb}:</li>
    {foreach from=$breadcrumb item=category}
        <li><a href="{$category->getLink()}">{$category->get('name')}</a></li>
    {/foreach}
    {if 0 lt $headline|trim|strlen}
        <li>{$headline}</li>
    {/if}
</ul>

<!-- /navigation_breadcrumb/template/get.tpl -->
