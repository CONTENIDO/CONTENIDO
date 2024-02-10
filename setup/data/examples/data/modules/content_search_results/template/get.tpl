<!-- content_search_results/template/get.tpl -->

{* headline *}
<h1>{$label.headline|escape}</h1>

{* search form *}
<form action="{$action}" method="{$method}" id="navigation_searchform_top">
    {if !empty($idart)}<input type="hidden" name="idart" value="{$idart}" />{/if}
    {if !empty($idlang)}<input type="hidden" name="idlang" value="{$idlang}" />{/if}
    <input type="text" id="search_term" name="search_term" class="full" title="Suche"/>
    <input type="submit" class="submit search" value="{$label.submit|escape} &raquo;"/>
</form>

<h3>{$msgResult}</h3>

{* top pagination *}
{if (!empty($pages) || (!empty($prev) || !empty($next)))}
    <div class="pagination">
        <span>{$msgRange}</span>
        <ul>
            {if 0 < $prev|count_characters}
                <li><a href="{$prev}">{$label.previous|escape}</a></li>
            {/if}
            {foreach $pages as $page}
                {assign var="href" value=$page}
                <li><a href="{$href}"{if $currentPage eq $page@key}{/if}>{$page@key}</a></li>
            {/foreach}
            {if 0 < $next|count_characters}
                <li><a href="{$next}">{$label.next|escape}</a></li>
            {/if}
        </ul>
    </div>
{/if}

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

{* bottom pagination *}
{if (!empty($pages) || (!empty($prev) || !empty($next)))}
    <div class="pagination">
        <span>{$msgRange}</span>
        <ul>
            {if 0 < $prev|count_characters}
                <li><a href="{$prev}">{$label.previous|escape}</a></li>
            {/if}
            {foreach $pages as $page}
                {assign var="href" value=$page}
                <li><a href="{$href}"{if $currentPage eq $page@key}{/if}>{$page@key}</a></li>
            {/foreach}
            {if 0 < $next|count_characters}
                <li><a href="{$next}">{$label.next|escape}</a></li>
            {/if}
        </ul>
    </div>
{/if}

<!-- /content_search_results/template/get.tpl -->
