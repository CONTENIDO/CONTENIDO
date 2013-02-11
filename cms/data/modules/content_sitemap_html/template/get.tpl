<!-- content_sitemap_html -->

{if true eq $isBackendEditMode}
    <label class="content-type-label">{$trans.headline}</label>
    <p>{$trans.categoryLabel}</p>
    <label>{$trans.categoryHintLabel}</label>
    <div>{$category}</div>
    <p>{$trans.levelLabel}</p>
    <label>{$trans.levelHintLabel}</label>
    <div>{$level}</div>
    {if $error}
        <p class="error">{$error}</p>
    {/if}
{/if}
{if false eq $isBackendEditMode}
<ul class="sitemap">

    {* loop categories *}
    {foreach from=$tree item=wrapper}

        {assign var="idcat" value=$wrapper.idcat}
        {assign var="url" value=$wrapper.item->getLink()}
        {assign var="name" value=$wrapper.item->get('name')}

        <li>
            <a href="{$url}" title="{$name}">{$name}</a>
            {include file="content_sitemap_html/template/get.tpl"
                tree=$wrapper.subcats path=$path ulId=""}

        {* loop articles *}
        {if 0 lt $wrapper.articles|count}
            {foreach from=$wrapper.articles item=article}
            <li>
                <a href="{$article->getLink()}" title="{$article->get('title')}">{$article->get('title')}</a>
            </li>
            {/foreach}
        {/if}

        </li>
     {/foreach}
</ul>
{/if}
<!-- /content_sitemap_html -->
