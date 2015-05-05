<!-- content_sitemap_html -->

{if true eq $isBackendEditMode}
    <label class="content_type_label">{$trans.headline}</label>
    <div class="sitemapdiv">
        <h2>{$trans.categoryLabel|escape}</h2>
        <label>{$trans.categoryHintLabel|escape}</label>
        <div>{$category}</div>
    </div>
    <div class="sitemapdiv">
        <h2>{$trans.levelLabel|escape}</h2>
        <label>{$trans.levelHintLabel|escape}</label>
        <div>{$level}</div>
    </div>
    <div class="sitemapdiv">
        <h2>{$trans.articleLabel|escape}</h2>
        <label>{$trans.articleHintLabel|escape}</label>
        <div>{$article}</div>
    </div>
    {if $error}<p class="error">{$error|escape}</p>{/if}
{else if $tree}
<ul{if $first == false} class="sitemap"{/if}>
    {$first = true}
    {* loop categories *}
    {foreach from=$tree item=wrapper}
        {assign var="idcat" value=$wrapper.idcat}
        {assign var="url" value=$wrapper.item->getLink()}
        {assign var="name" value=$wrapper.item->get('name')}
        <li>
            <a href="{$url|escape}" title="{$name|escape}">{$name|escape}</a>
            {include file="content_sitemap_html/template/get.tpl"
                tree=$wrapper.subcats path=$path ulId=""}
        {* loop articles *}
        {if 0 lt $wrapper.articles|count}
        	<ul>
	            {foreach from=$wrapper.articles item=article}
	            <li>
	                <a href="{$article->getLink()|escape}" title="{$article->get('title')|escape}">{$article->get('title')|escape}</a>
	            </li>
	            {/foreach}
            </ul>
        {/if}
        </li>
     {/foreach}
</ul>
{/if}

<!-- /content_sitemap_html -->
