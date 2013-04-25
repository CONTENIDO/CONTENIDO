<!-- content_sitemap_html -->

{if true eq $isBackendEditMode}
    <label class="content_type_label">{$trans.headline}</label>
    <div class="sitemapdiv">
        <h2>{$trans.categoryLabel}</h2>
        <label>{$trans.categoryHintLabel}</label>
        <div>{$category}</div>
    </div>
    <div class="sitemapdiv">
        <h2>{$trans.levelLabel}</h2>
        <label>{$trans.levelHintLabel}</label>
        <div>{$level}</div>
    </div>
    <div class="sitemapdiv">
        <h2>{$trans.articleLabel}</h2>
        <label>{$trans.articleHintLabel}</label>
        <div>{$article}</div>
    </div>
    {if $error}<p class="error">{$error}</p>{/if}
{else if $tree}
<ul{if $first == false} class="sitemap"{/if}>
    {$first = true}
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
