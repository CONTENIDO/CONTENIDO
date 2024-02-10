{if 0 lt $ulId|strlen}<!-- navigation_main/template/get.tpl -->{/if}

<ul class="{$ulId}">

    {foreach from=$tree item=wrapper}
        {assign var="idcat" value=$wrapper.idcat}
        {assign var="url" value=$wrapper.item->getLink()}
        {assign var="name" value=$wrapper.item->get('name')}
        {if $idcat|in_array:$path}
            {assign var="aClass" value='active'}
        {else}
            {assign var="aClass" value=''}
        {/if}
        <li>
            <a class="{$aClass|escape}" href="{$url|escape}"
               title="{$name|escape}">{$name|escape}</a>
            {if $idcat|in_array:$path and !empty($wrapper.subcats)}
                {include file="navigation_main/template/get.tpl"
                tree=$wrapper.subcats path=$path ulId=""}
            {/if}
        </li>
    {/foreach}
</ul>

{if 0 lt $ulId|strlen}<!-- /navigation_main/template/get.tpl -->{/if}