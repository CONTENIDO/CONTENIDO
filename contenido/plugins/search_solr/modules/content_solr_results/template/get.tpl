<!-- content_search_results/template/get.tpl -->

<!--

{* headline *}
<h1>{$label.headline}</h1>

{* search form *}
<form action="{$action}" method="{$method}" id="navigation_searchform_top">
    {if $idart}<input type="hidden" name="idart" value="{$idart}" />{/if}
    {if $idlang}<input type="hidden" name="idlang" value="{$idlang}" />{/if}
    <input type="text" id="search_term" name="search_term" class="full" title="Suche" />
    <input type="submit" class="submit search" value="{$label.submit} &raquo;" />
</form>

<h3>{$msgResult}</h3>

{* top pagination *}
{if !empty($pages) && (!empty($prev) || !empty($next))}
<div class="pagination">
    <span>{$msgRange}</span>
    <ul>
    {if 0 < $prev|strlen}
        <li><a href="{$prev}">{$label.previous}</a></li>
    {/if}
    {foreach item=page from=$pages|array_keys}
        {assign var="href" value=$pages.$page}
        <li><a href="{$href}"{if $currentPage eq $page}{/if}>{$page}</a></li>
    {/foreach}
    {if 0 < $next|strlen}
        <li><a href="{$next}">{$label.next}</a></li>
    {/if}
    </ul>
</div>
{/if}

-->

<!--

{* search result list *}
<ul id="search_result">
{foreach item=result from=$results}
    <li>
        <span>{$result.number}</span>
        <a href="{$result.href}">{$result.headline}</a>
        <p>{$result.paragraph}</p>
    </li>
{/foreach}
</ul>

-->

{* search result list *}
<ul id="search_result">
{foreach item=result from=$results}
    <li>
    {if 0 lt $result.cms_htmlhead|count}
        <a href="front_content.php?idart={$result.id_art}">{$result.cms_htmlhead.0}</a>
    {/if}
    {foreach item=content from=$result.cms_html}
        {$content}
    {/foreach}
    </li>
{/foreach}
</ul>

<!--

{* bottom pagination *}
{if !empty($pages) && (!empty($prev) || !empty($next))}
<div class="pagination">
    <span>{$msgRange}</span>
    <ul>
    {if 0 < $prev|strlen}
        <li><a href="{$prev}">{$label.previous}</a></li>
    {/if}
    {foreach item=page from=$pages|array_keys}
        {assign var="href" value=$pages.$page}
        <li><a href="{$href}"{if $currentPage eq $page}{/if}>{$page}</a></li>
    {/foreach}
    {if 0 < $next|strlen}
        <li><a href="{$next}">{$label.next}</a></li>
    {/if}
    </ul>
</div>
{/if}

-->

<!-- /content_search_results/template/get.tpl -->
