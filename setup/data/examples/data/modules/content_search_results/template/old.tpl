{*
    This template displays the search form and its results
    like it was implemented in the old example clients
    module search_output.
*}
<!-- content_search_results/template/old.tpl -->

<div id="search">
    <form action="{$action}" method="post">
        <label for="searchterm">{$label.submit}</label>
        {if !empty($idart)}<input type="hidden" name="idart" value="{$idart}" />{/if}
        {if !empty($idlang)}<input type="hidden" name="idlang" value="{$idlang}" />{/if}
        <input type="text" id="searchterm" name="search_term"/>
        <input type="image" id="search_send" name="search_send" src="images/navi_pfeil_zu.gif"
               class="sbmt"/>
    </form>
</div>

<div id="searchResults">

    <p class="message">{$msgResult}</p>

    {foreach item=result from=$results}
        <div class="searchResultItem">
            <h2>
                {$number}.
                &nbsp;
                <a title="{$label.more}" href="{$result.href}">
                    {$result.headline}
                </a>
            </h2>
            <p style="padding: 0; margin: 0 0 10px 0;">
                {$result.paragraph}
                &nbsp;
                <a title="{$label.more}" href="{$result.href}">
                    {$label.more}
                </a>
            </p>
        </div>
    {/foreach}

    {* navigation *}
    {if (!empty($pages) || (!empty($prev) || !empty($next)))}
        <p class="result">
            {$label.resultPage}
            &nbsp;
            &nbsp;
            {if 0 < $prev|strlen}
                <a href="{$prev}" title="{$label.previous}">
                    <img src="images/link_pfeil_klein_links.gif" alt=""/>
                    &nbsp;
                    &nbsp;
                    {$label.previous}
                </a>
                &nbsp;
            {/if}
            {foreach item=page from=$pages|array_keys}
                {assign var="href" value=$pages.$page}
                <span style="white-space:nowrap;">
            &nbsp;
            {if $currentPage eq $page}
                <strong>{$page}</strong>
                    
                                        
{else}
            
                
                <a href="{$href}" title="{$page}">{$page}</a>
            {/if}
            &nbsp;
        </span>
            {/foreach}
            {if 0 < $next|strlen}
                &nbsp;
                <a href="{$next}" title="{$label.next}">
                    {$label.next}
                    &nbsp;
                    &nbsp;
                    <img src="images/link_pfeil_klein.gif" alt=""/>
                </a>
            {/if}
        </p>
    {/if}

</div>

<!-- /content_search_results/template/old.tpl -->
