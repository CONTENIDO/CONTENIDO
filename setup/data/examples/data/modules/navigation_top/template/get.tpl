<!-- navigation_top/template/get.tpl -->

<ul>
    {foreach from=$tree item=wrapper}
        {assign var="idcat" value=$wrapper.idcat}
        {assign var="url" value=$wrapper.item->getLink()}
        {assign var="name" value=$wrapper.item->get('name')}
        {if $idcat|in_array:$path}
            {assign var="aClass" value='active'}
        {/if}
        <li>
            <a class="{$aClass|escape}" href="{$url|escape}" title="{$name|escape}">{$name|escape}</a>
        {if $idcat|in_array:$path}
            {include file="navigation_top/template/get.tpl"
                tree=$wrapper.subcats path=$path}
        {/if}
        </li>
     {/foreach}
</ul>

<!-- /navigation_top/template/get.tpl -->