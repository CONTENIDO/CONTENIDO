{if 0 lt $ulId|strlen}<!-- navigation_main -->{/if}

<ul class="{$ulId}">

    {foreach from=$tree item=wrapper}
        {assign var="idcat" value=$wrapper.idcat}
        {assign var="url" value=$wrapper.item->getLink()}
        {assign var="name" value=$wrapper.item->get('name')}
        {if $idcat|in_array:$path}
            {assign var="aClass" value='active'}
        {/if}
        <li>
            <a class="{$aClass}" href="{$url}" title="{$name}">{$name}</a>
        {if $idcat|in_array:$path}
            {include file="navigation_main/template/navigation.tpl"
                tree=$wrapper.subcats ulId="" actualIdcat=$actualIdcat path=$path}
        {/if}
        </li>
     {/foreach}
</ul>

{if 0 lt $ulId|strlen}<!-- /navigation_main -->{/if}
